# ingest_knowledge.py
# Script to ingest shop knowledge (FAQs, shop info) into ChromaDB embeddings

import os
import json
from pathlib import Path
import chromadb
from chromadb.config import Settings
from embedding import MODEL_NAME, create_embedder
from rag_config import KNOWLEDGE_COLLECTION, KNOWLEDGE_INDEX_VERSION
from env_loader import load_project_env

os.environ["TRANSFORMERS_NO_TORCHVISION"] = "1"
load_project_env(__file__)

CHROMA_DIR = os.getenv("CHROMA_DIR", str(Path(__file__).resolve().parent.parent / "RAG" / "chroma_cosmetics"))
EMB_MODEL_NAME = os.getenv("EMB_MODEL", MODEL_NAME)
KNOWLEDGE_FILE = os.path.join(os.path.dirname(__file__), "shop_knowledge.json")

os.makedirs(CHROMA_DIR, exist_ok=True)

def load_knowledge():
    """Load knowledge from JSON file"""
    with open(KNOWLEDGE_FILE, 'r', encoding='utf-8') as f:
        return json.load(f)

def build_shop_info_doc(info):
    """Build document from shop info"""
    docs = []
    
    # General shop info
    shop_doc = f"""Thông tin cửa hàng {info['name']}:
Giờ làm việc: {info['working_hours']['weekdays']}, {info['working_hours']['weekend']}
Số nhân viên: {info['staff']['total_employees']} người (gồm {info['staff']['consultants']} tư vấn viên và {info['staff']['shippers']} shipper)
Địa chỉ: {info['address']}
Hotline: {info['hotline']}
Email: {info['email']}
Website: {info['website']}
Chính sách đổi trả: {info['policies']['return']}
Chính sách vận chuyển: {info['policies']['shipping']}
Phương thức thanh toán: {', '.join(info['policies']['payment'])}"""
    
    docs.append({
        "id": "shop_info_general",
        "doc": shop_doc,
            "meta": {"type": "shop_info", "category": "general", "index_version": KNOWLEDGE_INDEX_VERSION}
    })
    
    return docs

def build_faq_docs(faqs):
    """Build documents from FAQs"""
    docs = []
    for i, faq in enumerate(faqs):
        doc = f"Câu hỏi: {faq['question']}\nTrả lời: {faq['answer']}"
        docs.append({
            "id": f"faq_{i}",
            "doc": doc,
            "meta": {"type": "faq", "question": faq['question'][:100], "index_version": KNOWLEDGE_INDEX_VERSION}
        })
    return docs

def build_skin_type_docs(skin_types):
    """Build documents for skin type recommendations"""
    docs = []
    for key, info in skin_types.items():
        doc = f"""Loại da: {info['name']}
Đặc điểm: {info['characteristics']}
Sản phẩm phù hợp: {', '.join(info['recommended'])}
Nên tránh: {', '.join(info['avoid'])}"""
        docs.append({
            "id": f"skin_type_{key}",
            "doc": doc,
            "meta": {"type": "skin_type", "skin": key, "name": info['name'], "index_version": KNOWLEDGE_INDEX_VERSION}
        })
    return docs

def main():
    print("Loading knowledge from:", KNOWLEDGE_FILE)
    knowledge = load_knowledge()
    
    print("Loading embed model:", EMB_MODEL_NAME)
    embedder = create_embedder(EMB_MODEL_NAME)
    
    # Build all documents
    all_docs = []
    all_docs.extend(build_shop_info_doc(knowledge['shop_info']))
    all_docs.extend(build_faq_docs(knowledge['faqs']))
    all_docs.extend(build_skin_type_docs(knowledge['skin_types']))
    
    print(f"Total knowledge documents: {len(all_docs)}")
    
    # Connect to ChromaDB
    client = chromadb.PersistentClient(
        path=CHROMA_DIR, settings=Settings(anonymized_telemetry=False)
    )
    
    # Create or get knowledge collection
    col = client.get_or_create_collection(KNOWLEDGE_COLLECTION)
    
    # Prepare data
    ids = [d['id'] for d in all_docs]
    docs = [d['doc'] for d in all_docs]
    metas = [d['meta'] for d in all_docs]
    
    stale = sorted(set(col.get(include=[]).get("ids", [])) - set(ids))
    if stale:
        col.delete(ids=stale)
    print("Encoding documents...")
    embs = embedder.encode(docs, batch_size=16, normalize_embeddings=True)
    
    print("Upserting to ChromaDB...")
    col.upsert(ids=ids, documents=docs, embeddings=embs, metadatas=metas)
    
    print(f"Done! Indexed {len(ids)} knowledge documents.")
    print("\nDocuments indexed:")
    for d in all_docs:
        print(f"  - {d['id']}: {d['meta']['type']}")

if __name__ == "__main__":
    main()
