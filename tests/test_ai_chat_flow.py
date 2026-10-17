"""Conversation-flow contracts: comparison, popularity and one-question-per-turn behaviour."""
import os
import sys
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "TTTT" / "WebBanHangMyPham" / "src" / "ML"))
os.environ.setdefault("APP_ENV", "testing")

import api_rag_ollama as api


def product(pid, name, price, sold=0, likes=0, views=0, doc="Giúp dưỡng ẩm cho môi mềm mịn suốt ngày."):
    return {"id": str(pid), "doc": doc, "distance": None, "meta": {
        "IDSanPham": pid, "TenSanPham": name, "ThuongHieu": "Brand", "DanhMuc": "Son Dưỡng",
        "Gia": price, "SoLuong": 5, "sold": sold, "likes": likes, "views": views}}


class ChatFlowTest(unittest.TestCase):
    def setUp(self):
        self.previous = (api._components_initialized, api.embedder, api.collection, api.knowledge_collection)
        api._components_initialized = True
        api.embedder = object()
        api.collection = MagicMock()
        api.knowledge_collection = MagicMock()

    def tearDown(self):
        api._components_initialized, api.embedder, api.collection, api.knowledge_collection = self.previous

    def test_confirmation_is_a_short_yes_not_any_sentence_with_duoc(self):
        for yes in ["có", "ok", "Có nha!", "so sánh đi", "được"]:
            self.assertTrue(api.is_comparison_confirmation(yes), yes)
        for question in ["son nào được yêu thích nhất", "không", "kem chống nắng cho da dầu được không bạn ơi nhé"]:
            self.assertFalse(api.is_comparison_confirmation(question), question)

    def test_compare_offer_is_detected_from_the_last_assistant_turn(self):
        history = [api.HistoryItem(role="user", content="tư vấn son"),
                   api.HistoryItem(role="assistant", content="... " + api.COMPARE_OFFER["vi"])]
        self.assertTrue(api.assistant_offered_compare(history))
        history.append(api.HistoryItem(role="assistant", content="Bạn có muốn đặt lịch tư vấn không?"))
        self.assertFalse(api.assistant_offered_compare(history))

    def test_comparison_table_uses_live_numbers_and_recommends(self):
        reply = api.comparison_reply([product(1, "Son A", 100000, sold=1), product(2, "Son B", 950000, sold=3)], "vi")
        self.assertIn("| Giá | 100.000đ | 950.000đ |", reply)
        self.assertIn("Tiết kiệm hơn: **Son A** (rẻ hơn 850.000đ)", reply)
        self.assertIn("chọn nhiều hơn: **Son B**", reply)
        self.assertNotIn("?", reply)

    def test_yes_after_offer_compares_the_products_kept_in_context(self):
        req = api.ChatReq(message="có", context_product_ids=[37, 45], history=[
            api.HistoryItem(role="assistant", content="Gợi ý 2 son. " + api.COMPARE_OFFER["vi"])])
        products = [product(37, "Son A", 950000), product(45, "Son B", 100000)]
        with patch.object(api, "match_products_by_name", return_value=[]), \
             patch.object(api, "products_by_ids", return_value=products) as by_ids, \
             patch.object(api, "call_ollama") as llm:
            result = api.chat(req)
        by_ids.assert_called_once()
        llm.assert_not_called()
        self.assertEqual("compare_done", result["follow_up"])
        self.assertEqual(["37", "45"], [h["id"] for h in result["hits"]])
        self.assertIn("|---|---|---|", result["reply"])

    def test_popularity_question_lists_best_sellers_without_asking_back(self):
        products = [product(37, "Son A", 950000, sold=3, likes=1, views=2), product(45, "Son B", 100000),
                    product(51, "Son C", 159000)]
        with patch.object(api, "popular_products", return_value=products) as popular, \
             patch.object(api, "call_ollama") as llm:
            result = api.chat(api.ChatReq(message="son nào được yêu thích nhất"))
        self.assertEqual(("son",), popular.call_args.args[1])
        llm.assert_not_called()
        self.assertIn("đã bán 3 · 1 lượt yêu thích · 2 lượt xem", result["reply"])
        self.assertTrue(result["reply"].endswith(api.COMPARE_OFFER_TOP["vi"]))
        self.assertEqual("compare_offer", result["follow_up"])

    def test_found_products_are_listed_from_live_data_not_rewritten_by_the_model(self):
        hits = [product(85, "Sữa rửa mặt A", 109000), product(48, "Sữa rửa mặt B", 819000)]
        history = [api.HistoryItem(role="assistant", content="Son môi waterproof - Maybelline - 450.000 VND")]
        with patch.object(api, "retrieve", return_value=hits), patch.object(api, "call_ollama") as llm:
            result = api.chat(api.ChatReq(message="tìm sữa rửa mặt cho da dầu", history=history))
        llm.assert_not_called()
        self.assertIn("**Sữa rửa mặt A** – Brand – 109.000đ", result["reply"])
        self.assertNotIn("Maybelline", result["reply"])
        self.assertEqual(1, result["reply"].count("?"))
        self.assertTrue(result["reply"].endswith(api.COMPARE_OFFER["vi"]))
        self.assertEqual("compare_offer", result["follow_up"])

    def test_single_hit_is_completed_with_a_real_best_seller(self):
        hit = product(48, "Sữa rửa mặt A", 819000)
        seller = product(85, "Sữa rửa mặt B", 109000)
        with patch.object(api, "retrieve", return_value=[hit]),              patch.object(api, "popular_products", return_value=[hit, seller]),              patch.object(api, "call_ollama") as llm:
            result = api.chat(api.ChatReq(message="mình muốn tìm sữa rửa mặt"))
        llm.assert_not_called()
        self.assertIn("Sữa rửa mặt B", result["reply"])
        self.assertEqual(["48", "85"], [h["id"] for h in result["hits"]])

    def test_named_products_missing_from_the_shop_are_reported_not_swapped(self):
        found = product(51, "Son Môi gecomo Chống Thấm Nước", 159000)
        seller = product(37, "Son dưỡng Dior Addict", 950000)
        named = [{"name": "son water", "hit": found}, {"name": "son hydrating", "hit": None}]
        req = api.ChatReq(message="so sánh son water và son hydrating", context_product_ids=[90, 91])
        with patch.object(api, "match_named_parts", return_value=named),              patch.object(api, "popular_products", return_value=[found, seller]),              patch.object(api, "products_by_ids") as by_ids, patch.object(api, "call_ollama") as llm:
            result = api.chat(req)
        by_ids.assert_not_called()
        llm.assert_not_called()
        self.assertIn('chưa có sản phẩm khớp với "son hydrating"', result["reply"])
        self.assertIn('Với "son water", sản phẩm gần nhất là **Son Môi gecomo Chống Thấm Nước**', result["reply"])
        self.assertNotIn("|---|", result["reply"])
        self.assertEqual(["51", "37"], [h["id"] for h in result["hits"]])

    def test_named_products_that_exist_are_compared(self):
        a, b = product(51, "Son A", 159000), product(37, "Son B", 950000)
        named = [{"name": "son A", "hit": a}, {"name": "son B", "hit": b}]
        with patch.object(api, "match_named_parts", return_value=named), patch.object(api, "products_by_ids") as by_ids:
            result = api.chat(api.ChatReq(message="so sánh son A và son B", context_product_ids=[90, 91]))
        by_ids.assert_not_called()
        self.assertEqual("compare_done", result["follow_up"])
        self.assertEqual(["51", "37"], [h["id"] for h in result["hits"]])

    def test_named_parts_use_synonyms_and_keep_the_product_family(self):
        import live_catalog
        def row(pid, name, brand):
            return {"IDSanPham": pid, "TenSanPham": name, "TenThuongHieu": brand, "TenDanhMuc": "", "GiaTien": 100000,
                    "SoLuong": 5, "MoTaChiTiet": "", "NoiBat": 0, "views": 0, "likes": 0, "sold": 0}
        rows = [row(51, "Son Môi gecomo Chất Nhung Lì Chống Thấm Nước", "GECOMO"),
                row(37, "Son dưỡng Dior Addict Lip Glow", "DIOR"),
                row(59, "Bộ Kem dưỡng ẩm & làm sáng LAGUMI", "LAGUMI"),
                row(85, "Sữa rửa mặt Cerave 236ml", "CERAVE")]
        connection = MagicMock()
        connection.cursor.return_value.fetchall.return_value = rows
        env = {"MYSQL_HOST": "h", "MYSQL_USER": "u", "MYSQL_DB": "d"}
        def names(text):
            return [(i["name"], i["hit"] and i["hit"]["id"]) for i in
                    live_catalog.match_named_parts(lambda **_: connection, text, env)]
        self.assertEqual([("son water", "51"), ("son hydrating", "37")], names("so sánh son water và son hydrating"))
        self.assertEqual([("Cerave", "85"), ("son mac", None)], names("so sánh Cerave và son mac"))
        self.assertEqual([], names("so sánh 2 sản phẩm này"))

    def test_highlight_skips_names_and_legal_notes(self):
        doc = ("LƯU Ý: Công ty thông báo đổi bao bì. Tên sản phẩm: Son X. "
               "Công dụng: Giúp môi mềm mịn và lên màu chuẩn suốt 8 giờ. Hướng dẫn: thoa đều.")
        self.assertEqual("Giúp môi mềm mịn và lên màu chuẩn suốt 8 giờ", api.product_highlight(doc))


if __name__ == "__main__":
    unittest.main()
