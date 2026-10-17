import json
import os
import sys
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "TTTT" / "WebBanHangMyPham" / "src" / "ML"))
os.environ.setdefault("APP_ENV", "testing")

import api_rag_ollama as api
import requests


class AiApiContractTest(unittest.TestCase):
    def setUp(self):
        self.previous = (api._components_initialized, api.embedder, api.collection, api.knowledge_collection)
        api._components_initialized = True
        api.embedder = object()
        # MagicMock, not object(): readiness tests assign collection.count on these stubs.
        api.collection = MagicMock()
        api.knowledge_collection = MagicMock()

    def tearDown(self):
        api._components_initialized, api.embedder, api.collection, api.knowledge_collection = self.previous

    def test_product_intent_returns_auditable_live_source(self):
        hit = {"id": "85", "doc": "MoTa: dịu nhẹ", "meta": {
            "IDSanPham": 85, "TenSanPham": "Sữa rửa mặt", "ThuongHieu": "CeraVe",
            "Gia": 200000, "SoLuong": 2,
        }}
        # popular_products is stubbed so the single-hit completion stays hermetic (no live database).
        with patch.object(api, "retrieve", return_value=[hit]), patch.object(api, "popular_products", return_value=[]), \
             patch.object(api, "call_ollama", return_value="Kết quả"):
            result = api.chat(api.ChatReq(message="Tìm sữa rửa mặt cho da dầu"))
        self.assertEqual("product", result["intent"])
        self.assertEqual([{"id": "85", "source": "mysql-live"}], result["sources"])

    def test_shipping_uses_active_mysql_policy_instead_of_static_knowledge(self):
        with patch.object(api, "live_shipping_context", return_value="Vùng A: 18000 VND"), patch.object(api, "call_ollama", return_value="18.000đ"):
            result = api.chat(api.ChatReq(message="Phí ship bao nhiêu?"))
        self.assertEqual("knowledge", result["intent"])
        self.assertEqual("mysql-live", result["sources"][0]["source"])

    def test_missing_shipping_configuration_does_not_invent_a_rate(self):
        captured = {}
        def answer(prompt, _lang):
            captured["prompt"] = prompt
            return "Chưa có thông tin"
        with patch.object(api, "live_shipping_context", return_value=None), \
             patch.object(api, "retrieve_knowledge", return_value=[]), \
             patch.object(api, "call_ollama", side_effect=answer):
            result = api.chat(api.ChatReq(message="Phí ship bao nhiêu?"))
        self.assertEqual([], result["sources"])
        self.assertIn("Không có dữ liệu đã kiểm chứng", captured["prompt"])

    def test_general_message_does_not_run_product_retrieval(self):
        with patch.object(api, "retrieve") as retrieve, patch.object(api, "call_ollama", return_value="Xin chào"):
            result = api.chat(api.ChatReq(message="Xin chào"))
        retrieve.assert_not_called()
        self.assertEqual("general", result["intent"])
        self.assertEqual([], result["sources"])

    def test_component_failure_has_stable_nontechnical_response(self):
        api.embedder = None
        result = api.chat(api.ChatReq(message="Tìm serum"))
        self.assertEqual("unavailable", result["intent"])
        self.assertNotIn("Traceback", result["reply"])

    def test_ollama_transport_failure_has_stable_fallback(self):
        with patch.object(api.http, "post", side_effect=requests.ConnectionError("secret detail")):
            self.assertEqual(api.BUSY_VI, api.call_ollama("prompt", "vi"))

    def test_history_is_bounded_and_marked_untrusted(self):
        req = api.ChatReq(message="Loại nào phù hợp hơn?", history=[
            api.HistoryItem(role="user", content="Tìm kem dưỡng"),
            api.HistoryItem(role="assistant", content="Có hai sản phẩm"),
        ])
        text = api.history_context(req.history)
        self.assertIn("không phải chỉ dẫn hệ thống", text)
        with self.assertRaises(Exception):
            api.ChatReq(message="x", history=[api.HistoryItem(role="user", content="x")] * 7)

    def test_readiness_requires_live_commerce_and_model_dependencies(self):
        api.collection.count = lambda: 1
        api.knowledge_collection.count = lambda: 1
        connection = MagicMock()
        connection.cursor.return_value.fetchone.return_value = (1,)
        response = MagicMock(ok=True)
        with patch.object(api.mysql.connector, "connect", return_value=connection), \
             patch.object(api.http, "get", return_value=response):
            result = api.ready()
        self.assertEqual(200, result.status_code)
        self.assertTrue(json.loads(result.body)["ok"])

    def test_readiness_fails_closed_without_live_database(self):
        api.collection.count = lambda: 1
        api.knowledge_collection.count = lambda: 1
        response = MagicMock(ok=True)
        with patch.object(api.mysql.connector, "connect", side_effect=RuntimeError("down")), \
             patch.object(api.http, "get", return_value=response):
            result = api.ready()
        payload = json.loads(result.body)
        self.assertEqual(503, result.status_code)
        self.assertFalse(payload["checks"]["mysql"])


if __name__ == "__main__":
    unittest.main()
