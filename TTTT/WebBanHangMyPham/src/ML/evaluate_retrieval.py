"""Calibrate and measure product retrieval against a fixed Vietnamese set."""
from __future__ import annotations

import argparse
import json
import statistics
import time
from pathlib import Path

import chromadb
from chromadb.config import Settings
from embedding import MODEL_NAME, create_embedder
from rag_config import KNOWLEDGE_COLLECTION, PRODUCT_COLLECTION

MODULE_DIR = Path(__file__).resolve().parent
ROOT = MODULE_DIR.parents[3]


def metrics(results: list[dict], threshold: float, top_k: int = 2) -> dict:
    tp = fp = fn = 0
    passed_cases = 0
    for case in results:
        predicted = [item["id"] for item in case["candidates"] if item["distance"] <= threshold][:top_k]
        relevant = set(case["relevant_ids"])
        predicted_set = set(predicted)
        tp += len(predicted_set & relevant)
        fp += len(predicted_set - relevant)
        fn += len(relevant - predicted_set)
        if relevant:
            passed_cases += int(bool(predicted_set & relevant))
        else:
            passed_cases += int(not predicted)
    precision = tp / (tp + fp) if tp + fp else 1.0
    recall = tp / (tp + fn) if tp + fn else 1.0
    f1 = 2 * precision * recall / (precision + recall) if precision + recall else 0.0
    return {
        "threshold": round(threshold, 6), "precision": round(precision, 4),
        "recall": round(recall, 4), "f1": round(f1, 4),
        "case_pass_rate": round(passed_cases / len(results), 4),
    }


def choose_threshold(results: list[dict]) -> dict:
    distances = sorted({item["distance"] for case in results for item in case["candidates"]})
    candidates = [0.0] + distances
    scored = [metrics(results, value) for value in candidates]
    # Prefer higher F1, then fewer false positives (precision), then lower threshold.
    return max(scored, key=lambda row: (row["f1"], row["precision"], -row["threshold"]))


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--index", default=str(MODULE_DIR.parent / "RAG" / "chroma_cosmetics"))
    parser.add_argument("--cases", default=str(ROOT / "tests" / "ai_retrieval_eval.json"))
    parser.add_argument("--output", default=str(MODULE_DIR / "rag_retrieval_config.json"))
    parser.add_argument("--model", default=MODEL_NAME)
    args = parser.parse_args()

    cases = json.loads(Path(args.cases).read_text(encoding="utf-8"))
    model_started = time.perf_counter()
    embedder = create_embedder(args.model)
    model_load_ms = (time.perf_counter() - model_started) * 1000
    client = chromadb.PersistentClient(
        path=str(Path(args.index).resolve()), settings=Settings(anonymized_telemetry=False)
    )
    latencies = []
    results = []
    collection_names = {"cosmetics_products": PRODUCT_COLLECTION, "shop_knowledge": KNOWLEDGE_COLLECTION}
    for case in cases:
        collection = client.get_collection(collection_names[case["collection"]])
        started = time.perf_counter()
        vector = embedder.encode([case["question"]], normalize_embeddings=True)[0]
        response = collection.query(query_embeddings=[vector], n_results=min(10, collection.count()))
        latencies.append((time.perf_counter() - started) * 1000)
        results.append({
            **case,
            "candidates": [
                {"id": str(product_id), "distance": float(distance)}
                for product_id, distance in zip(response["ids"][0], response["distances"][0])
            ],
        })
    grouped = {}
    for collection_name in sorted({case["collection"] for case in results}):
        grouped[collection_name] = choose_threshold([case for case in results if case["collection"] == collection_name])
    selected = grouped["cosmetics_products"]
    case_results = []
    for case in results:
        threshold = grouped[case["collection"]]["threshold"]
        predicted = [item["id"] for item in case["candidates"] if item["distance"] <= threshold][:2]
        relevant = set(case["relevant_ids"])
        case_results.append({
            "id": case["id"], "collection": case["collection"], "predicted_ids": predicted,
            "passed": bool(set(predicted) & relevant) if relevant else not predicted,
        })
    report = {
        "schema": "moshop-rag-eval-v1", "embedding_model": args.model,
        "distance_metric": "l2_on_normalized_embeddings", "top_k": 2,
        "product_max_distance": grouped["cosmetics_products"]["threshold"],
        "knowledge_max_distance": grouped["shop_knowledge"]["threshold"],
        "quality": grouped,
        "latency_ms": {
            "model_load": round(model_load_ms, 2), "median_query": round(statistics.median(latencies), 2),
            "p95_query": round(sorted(latencies)[max(0, int(len(latencies) * .95) - 1)], 2),
        },
        "cases": len(cases), "catalog_count": client.get_collection(PRODUCT_COLLECTION).count(),
        "case_results": case_results,
    }
    Path(args.output).write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report, ensure_ascii=False, indent=2))
    accepted = all(row["case_pass_rate"] >= 0.8 and row["precision"] >= 0.75 for row in grouped.values())
    return 0 if accepted else 1


if __name__ == "__main__":
    raise SystemExit(main())
