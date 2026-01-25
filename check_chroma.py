'''
- Kiểm tra dữ liệu đã index vào ChromaDB hay chưa
- Debug khi hệ thống không trả kết quả
- Xác nhận collection " Cosmetics_products " đã có dữ liệu hay chưa
- Xem số lượng embedding đang có để biết có cần index thêm hay không

'''
import os, chromadb
from chromadb.config import Settings
CHROMA_DIR = r"D:/TTTT1/TTTT/WebBanHangMyPham/src/RAG/chroma_cosmetics"     # kết nối tới database ChromaDB
c = chromadb.PersistentClient(path=CHROMA_DIR, settings=Settings(anonymized_telemetry=False))


col = c.get_or_create_collection("cosmetics_products")                    

print("CHROMA_DIR =", CHROMA_DIR)
print("Docs in collection =", col.count() if hasattr(col, "count") else len(col.get().get("ids", [])))