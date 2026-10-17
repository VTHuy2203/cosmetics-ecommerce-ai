"""Build an isolated evaluation index from non-transactional Chroma documents."""
import argparse
import sys
from pathlib import Path

import chromadb
from chromadb.config import Settings

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "TTTT" / "WebBanHangMyPham" / "src" / "ML"))
from embedding import MODEL_NAME, create_embedder
from rag_config import KNOWLEDGE_COLLECTION, PRODUCT_COLLECTION


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--source", required=True)
    parser.add_argument("--target", required=True)
    args = parser.parse_args()
    source_path = Path(args.source).resolve()
    target_path = Path(args.target).resolve()
    runtime = (ROOT / ".runtime").resolve()
    if runtime not in target_path.parents or target_path.exists():
        raise RuntimeError("Target must be a new directory below .runtime")
    source = chromadb.PersistentClient(path=str(source_path), settings=Settings(anonymized_telemetry=False))
    target = chromadb.PersistentClient(path=str(target_path), settings=Settings(anonymized_telemetry=False))
    embedder = create_embedder(MODEL_NAME)
    for old_name, new_name in (("cosmetics_products", PRODUCT_COLLECTION), ("shop_knowledge", KNOWLEDGE_COLLECTION)):
        old = source.get_collection(old_name).get(include=["documents", "metadatas"])
        destination = target.get_or_create_collection(new_name)
        documents = old["documents"]
        if documents:
            destination.add(
                ids=old["ids"], documents=documents, metadatas=old["metadatas"],
                embeddings=embedder.encode(documents),
            )
        print(f"{new_name}={len(documents)}")


if __name__ == "__main__":
    main()
