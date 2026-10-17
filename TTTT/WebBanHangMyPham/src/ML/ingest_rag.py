# ingest_rag.py
import os, re
from pathlib import Path
import mysql.connector
from mysql.connector import Error as MySQLError
import chromadb
from chromadb.config import Settings
from live_catalog import stale_vector_ids
from embedding import MODEL_NAME, create_embedder
from rag_config import CATALOG_INDEX_VERSION, PRODUCT_COLLECTION
from env_loader import load_project_env

# Không cần torchvision => tránh lỗi Windows
os.environ["TRANSFORMERS_NO_TORCHVISION"] = "1"

load_project_env(__file__)

# ---- ENV ----
def required_env(name):
    value = os.getenv(name)
    if not value:
        raise RuntimeError(f"Missing required database environment: {name}")
    return value

DB_HOST = required_env("MYSQL_HOST")
DB_PORT = int(os.getenv("MYSQL_PORT", "3306"))
DB_USER = required_env("MYSQL_USER")
DB_PASS = os.getenv("MYSQL_PASSWORD", "")
DB_NAME = required_env("MYSQL_DB")
DB_CHARSET = os.getenv("MYSQL_CHARSET", "utf8mb4")

CHROMA_DIR = os.getenv("CHROMA_DIR", str(Path(__file__).resolve().parent.parent / "RAG" / "chroma_cosmetics"))
EMB_MODEL_NAME = os.getenv("EMB_MODEL", MODEL_NAME)
INDEX_VERSION = CATALOG_INDEX_VERSION

os.makedirs(CHROMA_DIR, exist_ok=True)

def strip_html(s: str) -> str:
    if not s: return ""
    s = re.sub(r"<[^>]+>", " ", s)
    s = re.sub(r"\s+", " ", s).strip()
    return s

def get_db():
    return mysql.connector.connect(
        host=DB_HOST, port=DB_PORT,
        user=DB_USER, password=DB_PASS,
        database=DB_NAME, charset=DB_CHARSET, use_unicode=True,
    )

def read_products():
    """
    JOIN danhmuc/thuonghieu nếu có; nếu không sẽ lấy ID.
    Ưu tiên giá từ COALESCE(Gia, GiaTien).
    """
    conn = get_db()
    rows = []
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute("SHOW TABLES LIKE 'danhmuc'")
        has_dm = cur.fetchone() is not None
        cur.execute("SHOW TABLES LIKE 'thuonghieu'")
        has_th = cur.fetchone() is not None

        if has_dm and has_th:
            sql = """
                SELECT sp.IDSanPham,
                       sp.TenSanPham,
                       sp.MoTaChiTiet,
                       dm.TenDanhMuc   AS DanhMuc,
                       th.TenThuongHieu AS ThuongHieu,
                       sp.GiaTien AS Gia
                FROM sanpham sp
                LEFT JOIN danhmuc dm     ON sp.IDDanhMuc = dm.IDDanhMuc
                LEFT JOIN thuonghieu th  ON sp.IDThuongHieu = th.IDThuongHieu
                ORDER BY sp.IDSanPham DESC
            """
        else:
            sql = """
                SELECT sp.IDSanPham,
                       sp.TenSanPham,
                       sp.MoTaChiTiet,
                       sp.IDDanhMuc     AS DanhMuc,
                       sp.IDThuongHieu  AS ThuongHieu,
                       sp.GiaTien AS Gia
                FROM sanpham sp
                ORDER BY sp.IDSanPham DESC
            """
        cur.execute(sql)
        for r in (cur.fetchall() or []):
            r["MoTaChiTiet"] = strip_html(r.get("MoTaChiTiet"))
            rows.append(r)
    finally:
        try: conn.close()
        except: pass
    return rows

def build_doc(row):
    parts = []
    if row.get("TenSanPham"): parts.append(f"TenSanPham: {row['TenSanPham']}")
    if row.get("DanhMuc") is not None: parts.append(f"DanhMuc: {row['DanhMuc']}")
    if row.get("ThuongHieu") is not None: parts.append(f"ThuongHieu: {row['ThuongHieu']}")
    if row.get("Gia") is not None:
        try: parts.append(f"Gia: {int(float(row['Gia']))} VND")
        except: parts.append(f"Gia: {row['Gia']}")
    if row.get("MoTaChiTiet"): parts.append(f"MoTa: {row['MoTaChiTiet']}")
    return "\n".join(parts)

def main():
    print("Loading embed model:", EMB_MODEL_NAME)
    embedder = create_embedder(EMB_MODEL_NAME)

    print("Reading products from MySQL ...")
    rows = read_products()
    print("Total rows:", len(rows))

    client = chromadb.PersistentClient(
        path=CHROMA_DIR, settings=Settings(anonymized_telemetry=False)
    )
    col = client.get_or_create_collection(PRODUCT_COLLECTION)

    ids, docs, metas = [], [], []
    for r in rows:
        ids.append(str(r["IDSanPham"]))
        docs.append(build_doc(r))
        metas.append({
            "IDSanPham": r["IDSanPham"],
            "TenSanPham": r.get("TenSanPham"),
            "DanhMuc": r.get("DanhMuc"),
            "ThuongHieu": r.get("ThuongHieu"),
            "Gia": r.get("Gia"),
            "index_version": INDEX_VERSION,
        })

    current_ids = set(ids)
    indexed_ids = set(col.get(include=[]).get("ids", []))
    stale_ids = stale_vector_ids(indexed_ids, current_ids)
    if stale_ids:
        print("Deleting stale products:", len(stale_ids))
        col.delete(ids=stale_ids)

    if docs:
        print("Encoding ...")
        embs = embedder.encode(docs, batch_size=64, normalize_embeddings=True)
        print("Upsert to Chroma ...")
        col.upsert(ids=ids, documents=docs, embeddings=embs, metadatas=metas)
    print("Done. Indexed:", len(ids))

if __name__ == "__main__":
    main()
