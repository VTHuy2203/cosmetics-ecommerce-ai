import json
import sys
import unittest
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "TTTT" / "WebBanHangMyPham" / "src" / "ML"))

from live_catalog import hydrate_products, mysql_connection_options, retry_delay, shipping_policy_context, stale_vector_ids
from retrieval_policy import filter_hits, load_policy


ENV = {"MYSQL_HOST": "db", "MYSQL_USER": "app", "MYSQL_PASSWORD": "secret", "MYSQL_DB": "shop"}


class FakeCursor:
    def __init__(self, result_sets):
        self.result_sets = list(result_sets)
        self.current = []
        self.executions = []

    def execute(self, sql, params=None):
        self.executions.append((sql, params))
        self.current = self.result_sets.pop(0)

    def fetchall(self):
        return self.current

    def fetchone(self):
        return self.current[0] if self.current else None


class FakeConnection:
    def __init__(self, result_sets):
        self.fake_cursor = FakeCursor(result_sets)
        self.closed = False

    def cursor(self, dictionary=False):
        self.dictionary = dictionary
        return self.fake_cursor

    def close(self):
        self.closed = True


class AiDataContractTest(unittest.TestCase):
    def test_live_lookup_overwrites_stale_money_and_filters_deleted_or_empty_stock(self):
        connection = FakeConnection([[
            {"IDSanPham": 1, "TenSanPham": "Tên mới", "GiaTien": 800000, "SoLuong": 3},
            {"IDSanPham": 2, "TenSanPham": "Hết hàng", "GiaTien": 20, "SoLuong": 0},
        ]])
        hits = [
            {"id": "1", "doc": "old", "meta": {"Gia": 1000000}},
            {"id": "2", "doc": "old", "meta": {"Gia": 10}},
            {"id": "3", "doc": "deleted", "meta": {"Gia": 30}},
        ]
        result = hydrate_products(hits, lambda **options: connection, ENV)
        self.assertEqual([1], [row["meta"]["IDSanPham"] for row in result])
        self.assertEqual(800000, result[0]["meta"]["Gia"])
        self.assertEqual(3, result[0]["meta"]["SoLuong"])
        self.assertEqual("mysql-live", result[0]["meta"]["commerce_source"])
        self.assertEqual(1000000, hits[0]["meta"]["Gia"], "input hits must not be mutated")
        self.assertTrue(connection.closed)

    def test_missing_database_configuration_fails_closed(self):
        with self.assertRaisesRegex(RuntimeError, "MYSQL_USER"):
            mysql_connection_options({"MYSQL_HOST": "db", "MYSQL_DB": "shop"})

    def test_stale_vector_ids_are_deterministic(self):
        self.assertEqual(["2", "9"], stale_vector_ids(["9", "1", "2"], [1]))

    def test_catalog_retry_is_bounded(self):
        self.assertEqual(5, retry_delay(1))
        self.assertEqual(900, retry_delay(99))

    def test_retrieval_policy_rejects_uncalibrated_or_distant_hits(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "policy.json"
            path.write_text(json.dumps({
                "schema": "moshop-rag-eval-v1", "embedding_model": "model-a",
                "product_max_distance": 0.4, "knowledge_max_distance": 0.5,
            }), encoding="utf-8")
            self.assertIsNone(load_policy(path, "different-model"))
            policy = load_policy(path, "model-a")
            self.assertEqual(0.4, policy["cosmetics_products"])
            hits = [{"id": "near", "distance": .2}, {"id": "far", "distance": .9}]
            self.assertEqual(["near"], [hit["id"] for hit in filter_hits(hits, .4, 2)])

    def test_shipping_context_comes_from_active_version(self):
        connection = FakeConnection([
            [{"id": 7, "warehouse_address": "Kho Đà Nẵng", "free_shipping_threshold": 900000}],
            [{"region_name": "Nội thành", "min_weight_grams": 1, "max_weight_grams": 1000, "fee_amount": 18000}],
        ])
        text = shipping_policy_context(lambda **options: connection, ENV)
        self.assertIn("900000 VND", text)
        self.assertIn("18000 VND", text)
        self.assertIn("Nội thành", text)
        self.assertTrue(connection.closed)
        self.assertIn("shipping_config_versions", connection.fake_cursor.executions[0][0])
        self.assertIn("b.fee AS fee_amount", connection.fake_cursor.executions[1][0])

    def test_no_active_shipping_version_returns_no_policy(self):
        connection = FakeConnection([[]])
        self.assertIsNone(shipping_policy_context(lambda **options: connection, ENV))

    def test_fixed_vietnamese_evaluation_set_has_sources_and_acceptance_behavior(self):
        cases = json.loads((ROOT / "tests" / "ai_eval_cases.json").read_text(encoding="utf-8"))
        self.assertGreaterEqual(len(cases), 8)
        self.assertEqual(len(cases), len({case["id"] for case in cases}))
        for case in cases:
            self.assertTrue(case["question"].strip())
            self.assertIn(case["expected_source"], {"mysql-live", "shop_knowledge", "fallback"})
            self.assertTrue(case.get("expected_behavior") or case.get("must_not_contain"))


if __name__ == "__main__":
    unittest.main()
