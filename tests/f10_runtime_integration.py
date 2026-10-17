import json
import os
import re
import sys
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "TTTT" / "WebBanHangMyPham" / "src" / "ML"))

expected = {
    "APP_ENV": "testing", "MYSQL_HOST": "127.0.0.1", "MYSQL_PORT": "13316",
    "MYSQL_DB": "moshop_test_audit_20260911", "MYSQL_USER": "moshop_test",
}
for key, value in expected.items():
    if os.getenv(key) != value:
        raise RuntimeError(f"Unsafe F10 test environment: {key}")

import mysql.connector
import api_rag_ollama as api

connection = mysql.connector.connect(
    host=expected["MYSQL_HOST"], port=13316, user=expected["MYSQL_USER"],
    password=os.environ["MYSQL_PASSWORD"], database=expected["MYSQL_DB"],
)
report = {}
try:
    started = time.perf_counter()
    hits = api.retrieve("Sữa rửa mặt cho da dầu mụn", 2)
    report["initial_retrieval_ms"] = round((time.perf_counter() - started) * 1000, 2)
    product = next((hit for hit in hits if str(hit["id"]) == "85"), None)
    assert product and product["meta"]["Gia"] == 234567 and product["meta"]["SoLuong"] == 4

    cursor = connection.cursor()
    cursor.execute("UPDATE sanpham SET GiaTien=345678 WHERE IDSanPham=85")
    connection.commit()
    product = next(hit for hit in api.retrieve("Sữa rửa mặt cho da dầu mụn", 2) if str(hit["id"]) == "85")
    assert product["meta"]["Gia"] == 345678
    report["live_price"] = True

    cursor.execute("UPDATE sanpham SET SoLuong=0 WHERE IDSanPham=85")
    connection.commit()
    assert all(str(hit["id"]) != "85" for hit in api.retrieve("Sữa rửa mặt cho da dầu mụn", 2))
    report["out_of_stock_filtered"] = True

    cursor.execute("DELETE FROM sanpham WHERE IDSanPham=85")
    connection.commit()
    assert all(str(hit["id"]) != "85" for hit in api.retrieve("Sữa rửa mặt cho da dầu mụn", 2))
    report["deleted_filtered"] = True

    shipping = api.live_shipping_context()
    assert shipping and "18000 VND" in shipping and "900000 VND" in shipping
    report["shipping_source"] = "mysql-live"

    response = api.chat(api.ChatReq(message="Tìm son dưỡng môi"))
    assert response["intent"] == "product"
    assert {source["source"] for source in response["sources"]} == {"mysql-live"}
    normalized_reply = re.sub(r"\D", "", response["reply"])
    assert "123456" in normalized_reply, response["reply"]
    report["ollama_generation"] = True
    report["reply_chars"] = len(response["reply"])
finally:
    restore = connection.cursor()
    restore.execute(
        "INSERT INTO sanpham(IDSanPham,TenSanPham,IDDanhMuc,IDThuongHieu,SoLuong,TrongLuongGram,GiaTien,MoTaChiTiet,AnhSanPham,NoiBat) "
        "VALUES(85,'Sữa rửa mặt Cerave cho da dầu mụn',9901,9901,4,250,234567,'Sữa rửa mặt dịu nhẹ','',0) "
        "ON DUPLICATE KEY UPDATE SoLuong=4,GiaTien=234567"
    )
    connection.commit()
    connection.close()

(ROOT / ".runtime" / "f10-runtime-report.json").write_text(
    json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
)
print(json.dumps(report, ensure_ascii=False))
