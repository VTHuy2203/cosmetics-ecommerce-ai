"""One-time non-destructive bootstrap from legacy Chroma collections."""
import os
from pathlib import Path

import chromadb
from chromadb.config import Settings

from embedding import MODEL_NAME, create_embedder
from env_loader import load_project_env
from rag_config import CATALOG_INDEX_VERSION, PRODUCT_COLLECTION

load_project_env(__file__)
module_dir = Path(__file__).resolve().parent
chroma_dir = os.getenv("CHROMA_DIR", str(module_dir.parent / "RAG" / "chroma_cosmetics"))
client = chromadb.PersistentClient(path=chroma_dir, settings=Settings(anonymized_telemetry=False))
target = client.get_or_create_collection(PRODUCT_COLLECTION)
legacy = client.get_collection("cosmetics_products").get(include=["documents", "metadatas"])
documents = legacy["documents"]
metadatas = []
for metadata in legacy["metadatas"]:
    metadatas.append(dict(metadata or {}) | {"index_version": CATALOG_INDEX_VERSION})
if documents:
    target.upsert(
        ids=legacy["ids"], documents=documents, metadatas=metadatas,
        embeddings=create_embedder(MODEL_NAME).encode(documents),
    )
print(f"rebuilt={len(documents)} target={PRODUCT_COLLECTION}")
