"""Versioned, offline Vietnamese-friendly embedding used by every RAG producer/consumer."""
from __future__ import annotations

import hashlib
import math
import re
import unicodedata

MODEL_NAME = "moshop-char-word-hash-v1"
DIMENSIONS = 768


def _features(text: str) -> list[tuple[str, float]]:
    normalized = unicodedata.normalize("NFC", text).lower()
    words = re.findall(r"[\wÀ-ỹ]+", normalized, flags=re.UNICODE)
    compact = " ".join(words)
    features = [(f"w:{word}", 4.0) for word in words]
    features.extend((f"w2:{words[i]}_{words[i+1]}", 7.0) for i in range(max(0, len(words) - 1)))
    features.extend((f"c3:{compact[i:i+3]}", 0.35) for i in range(max(0, len(compact) - 2)))
    features.extend((f"c4:{compact[i:i+4]}", 0.5) for i in range(max(0, len(compact) - 3)))
    # Product names and FAQ questions carry more retrieval signal than long descriptions.
    for prefix in ("tensanpham:", "câu hỏi:"):
        for line in normalized.splitlines():
            if line.startswith(prefix):
                key_words = re.findall(r"[\wÀ-ỹ]+", line[len(prefix):], flags=re.UNICODE)
                features.extend((f"w:{word}", 12.0) for word in key_words)
                features.extend((f"w2:{key_words[i]}_{key_words[i+1]}", 18.0) for i in range(max(0, len(key_words) - 1)))
    return features


def embed_text(text: str) -> list[float]:
    vector = [0.0] * DIMENSIONS
    for feature, weight in _features(text):
        digest = hashlib.blake2b(feature.encode("utf-8"), digest_size=8).digest()
        value = int.from_bytes(digest, "little")
        index = value % DIMENSIONS
        vector[index] += -weight if value & (1 << 63) else weight
    norm = math.sqrt(sum(value * value for value in vector))
    return [value / norm for value in vector] if norm else vector


class LocalTextEmbedder:
    def encode(self, texts, **_options):
        return [embed_text(str(text)) for text in texts]


def create_embedder(model_name: str):
    if model_name != MODEL_NAME:
        raise ValueError(f"Unsupported embedding model: {model_name}")
    return LocalTextEmbedder()
