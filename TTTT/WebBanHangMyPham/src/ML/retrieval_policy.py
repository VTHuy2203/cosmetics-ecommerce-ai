"""Validated retrieval thresholds produced by the fixed evaluation set."""
from __future__ import annotations

import json
from pathlib import Path


def load_policy(path: str | Path, expected_model: str) -> dict | None:
    try:
        value = json.loads(Path(path).read_text(encoding="utf-8"))
        if value.get("schema") != "moshop-rag-eval-v1" or value.get("embedding_model") != expected_model:
            return None
        product = float(value["product_max_distance"])
        knowledge = float(value["knowledge_max_distance"])
        if product < 0 or knowledge < 0:
            return None
        return {"cosmetics_products": product, "shop_knowledge": knowledge}
    except (OSError, ValueError, TypeError, KeyError, json.JSONDecodeError):
        return None


def filter_hits(hits: list[dict], max_distance: float, limit: int) -> list[dict]:
    accepted = []
    for hit in hits:
        distance = hit.get("distance")
        if distance is None or float(distance) > max_distance:
            continue
        accepted.append(hit)
        if len(accepted) >= limit:
            break
    return accepted
