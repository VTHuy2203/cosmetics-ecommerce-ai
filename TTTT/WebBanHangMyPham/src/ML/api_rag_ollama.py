# api_rag_ollama.py  — fast & safe RAG for cosmetics
from fastapi import FastAPI
from pydantic import BaseModel
from fastapi.middleware.cors import CORSMiddleware
from typing import Optional, List
import os, requests, time, re
from dotenv import load_dotenv
from langdetect import detect, DetectorFactory
from sentence_transformers import SentenceTransformer
import chromadb
from chromadb.config import Settings
from functools import lru_cache
import traceback

# -------- Stable langdetect
# đặt seed=0 để kết quả ổn định
DetectorFactory.seed = 0

# -------- ENV
load_dotenv()
CHROMA_DIR   = os.getenv("CHROMA_DIR", "D:/TTTT1/TTTT/WebBanHangMyPham/src/RAG/chroma_cosmetics")
OLLAMA_HOST  = os.getenv("OLLAMA_HOST", "http://127.0.0.1:11434").rstrip("/")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3.1:8b")
EMB_MODEL    = os.getenv("EMB_MODEL", "BAAI/bge-m3")

TOP_K        = int(os.getenv("TOP_K", "2"))                # số đoạn context (MAX 2 products for better relevance)
CTX_CHARS    = int(os.getenv("CTX_CHARS", "1800"))         # cắt độ dài context
# Tránh đụng biến TEMP của Windows => dùng TEMP_VAL
try:
    TEMP = float(os.getenv("TEMP_VAL", "0.3"))
except Exception:
    TEMP = 0.3

NUM_CTX      = int(os.getenv("NUM_CTX", "3072"))
NUM_THREAD   = int(os.getenv("NUM_THREAD", "0"))           # 0 = để Ollama tự chọn
GEN_TIMEOUT  = int(os.getenv("GEN_TIMEOUT", "50"))         # timeout gọi model (s)

BUSY_VI = "Xin lỗi, mô hình đang bận. Vui lòng thử lại sau."
BUSY_EN = "Sorry, the model is busy. Please try again."

# -------- Global session (HTTP keep-alive)
http = requests.Session()
http.headers.update({"Connection": "keep-alive"})

# -------- Init components (safe)
def _init_embedder():
    try:
        return SentenceTransformer(EMB_MODEL)
    except Exception as e:
        print("!!! EMBEDDER INIT ERROR:", e); traceback.print_exc()
        return None

def _init_chroma():
    try:
        c = chromadb.PersistentClient(path=CHROMA_DIR, settings=Settings(anonymized_telemetry=False))
        return c.get_or_create_collection("cosmetics_products")
    except Exception as e:
        print("!!! CHROMA INIT ERROR:", e); traceback.print_exc()
        return None

def _init_knowledge_collection():
    """Initialize knowledge collection for FAQs and shop info"""
    try:
        c = chromadb.PersistentClient(path=CHROMA_DIR, settings=Settings(anonymized_telemetry=False))
        return c.get_or_create_collection("shop_knowledge")
    except Exception as e:
        print("!!! KNOWLEDGE COLLECTION INIT ERROR:", e); traceback.print_exc()
        return None

embedder  = _init_embedder()
collection = _init_chroma()
knowledge_collection = _init_knowledge_collection()

# -------- FastAPI
app = FastAPI(title="Cosmetics RAG API (Ollama + LLM)", version="0.2.0")
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"], allow_credentials=True, allow_methods=["*"], allow_headers=["*"],
)

# ===== Schemas
class ChatReq(BaseModel):
    message: str
    user_id: Optional[int] = None
    session_id: Optional[str] = None

class DetectReq(BaseModel):
    message: str

# ===== Helpers
def detect_lang(text: str) -> str:
    text_lower = text.lower().strip()
    
    # 0. Nếu chỉ toàn số, ngày tháng, dấu câu (ví dụ: "09/01/2026 00:00") -> Mặc định VI
    if re.match(r'^[0-9\s\.:,/\-]+$', text_lower):
        return "vi"

    # 1. Ưu tiên kiểm tra từ khóa tiếng Việt đặc trưng trước
    vi_keywords = [
        "so sánh", "so sanh", "tư vấn", "tu van", "giá", "gia", 
        "mua", "bán", "ban", "là gì", "la gi", "ở đâu", "o dau",
        "bao nhiêu", "bao nhieu", "cái nào", "cai nao", "tốt không", "tot khong",
        "xin chào", "chào", "hi", "alo", "ơi", "shop", "cửa hàng",
        "đặt lịch", "hen gio", "lịch hẹn"
    ]
    if any(kw in text_lower for kw in vi_keywords):
        return "vi"

    # 2. Kiểm tra từ khóa Tiếng Anh đặc trưng
    en_keywords = [
        "hello", "thank", "please", "price", "product", "buy", "order", 
        "cost", "much", "compare", "best", "good", "morning", "afternoon"
    ]
    if any(kw in text_lower for kw in en_keywords):
        return "en"

    # 3. Dùng thư viện detect (nhưng ưu tiên VI)
    try:
        l = detect(text)
        if l.startswith("en"): return "en"
    except Exception:
        pass
        
    # 4. Fallback cuối cùng: MẶC ĐỊNH LÀ TIẾNG VIỆT (cho Mơ Shop)
    return "vi"

@lru_cache(maxsize=256)
def _cached_query_vec(q: str):
    """Cache embedding cho cùng một câu hỏi => nhanh hơn."""
    if embedder is None:
        return None
    try:
        return embedder.encode([q], normalize_embeddings=True)[0].tolist()      # embedding dạng list 
    except Exception:
        return None

def is_product_search(text: str) -> bool:
    """Check if the text is a product search query."""
    product_keywords = [
        'tìm', 'tim', 'có', 'co', 'bán', 'ban', 'mua', 'giá', 'gia', 'sản phẩm', 'san pham',
        'son', 'kem', 'mặt nạ', 'mat na', 'serum', 'sữa rửa mặt', 'sua rua mat',
        'nước tẩy trang', 'nuoc tay trang', 'tẩy trang', 'tay trang',
        'chống nắng', 'chong nang', 'dưỡng', 'duong', 'makeup'
    ]
    text_lower = text.lower()
    return any(kw in text_lower for kw in product_keywords)

def retrieve(query: str, k: int = TOP_K) -> List[dict]:
    if embedder is None or collection is None:
        return []
    try:
        q_emb = _cached_query_vec(query)
        if q_emb is None:
            return []
        res = collection.query(query_embeddings=[q_emb], n_results=k)
        out = []
        if res and res.get("documents"):
            for i in range(len(res["documents"][0])):
                out.append({
                    "id": res["ids"][0][i],
                    "doc": res["documents"][0][i],
                    "meta": res["metadatas"][0][i],
                    "distance": res["distances"][0][i] if "distances" in res else None
                })
        return out
    except Exception as e:
        print("!!! RETRIEVE ERROR:", e); traceback.print_exc()
        return []

def retrieve_knowledge(query: str, k: int = 3) -> List[dict]:
    """Retrieve from knowledge collection (FAQs, shop info, skin types)"""
    if embedder is None or knowledge_collection is None:
        return []
    try:
        q_emb = _cached_query_vec(query)
        if q_emb is None:
            return []
        res = knowledge_collection.query(query_embeddings=[q_emb], n_results=k)
        out = []
        if res and res.get("documents"):
            for i in range(len(res["documents"][0])):
                out.append({
                    "id": res["ids"][0][i],
                    "doc": res["documents"][0][i],
                    "meta": res["metadatas"][0][i],
                    "distance": res["distances"][0][i] if "distances" in res else None
                })
        return out
    except Exception as e:
        print("!!! RETRIEVE KNOWLEDGE ERROR:", e); traceback.print_exc()
        return []

def is_shop_info_query(text: str) -> bool:
    """Check if query is about shop info (hours, staff, policies, etc.)"""
    shop_keywords = [
        'giờ làm việc', 'giờ mở cửa', 'mấy giờ', 'lúc nào', 'khi nào',
        'nhân viên', 'shipper', 'người giao hàng', 'bao nhiêu người',
        'địa chỉ', 'ở đâu', 'chỗ nào', 'cửa hàng',
        'đổi trả', 'bảo hành', 'chính sách', 'freeship', 'miễn phí ship',
        'thanh toán', 'cod', 'momo', 'zalopay', 'chuyển khoản',
        'hotline', 'liên hệ', 'số điện thoại', 'email',
        'working hours', 'open', 'address', 'contact', 'return policy'
    ]
    text_lower = text.lower()
    return any(kw in text_lower for kw in shop_keywords)

def is_faq_query(text: str) -> bool:
    """Check if query is a FAQ (skin type, product suitability, etc.)"""
    faq_keywords = [
        'da dầu', 'da khô', 'da nhạy cảm', 'da hỗn hợp', 'loại da',
        'phù hợp', 'không phù hợp', 'tại sao', 'vì sao', 'có nên',
        'bà bầu', 'mang thai', 'an toàn',
        'hàng thật', 'hàng giả', 'chính hãng',
        'cách dùng', 'cách sử dụng', 'sử dụng thế nào',
        'oily skin', 'dry skin', 'sensitive', 'suitable', 'how to use'
    ]
    text_lower = text.lower()
    return any(kw in text_lower for kw in faq_keywords)

def is_comparison_request(msg: str) -> bool:
    """Check if user is requesting a product comparison"""
    compare_keywords = ['so sánh', 'khác nhau', 'giống nhau', 'nên chọn', 'cái nào tốt',
                        'compare', 'difference', 'which one', 'better', 'vs', 'versus',
                        'hay là', 'hoặc là', 'hoặc', 'hay', 'tốt hơn', 'phù hợp hơn',
                        'muốn so sánh', 'muốn xem', 'có', 'ok', 'được', 'yes']
    msg_lower = msg.lower()
    return any(kw in msg_lower for kw in compare_keywords)

def is_lipstick_search(msg: str) -> bool:
    """Check if user is searching for lipstick products"""
    lipstick_keywords = [
        'son môi', 'son', 'lipstick', 'lip', 'son dưỡng', 'son lì', 'son tint',
        'son bóng', 'son kem', 'son thỏi', 'son nước', 'lip gloss', 'lip tint',
        'màu son', 'màu môi', 'lip color', 'lip stick'
    ]
    msg_lower = msg.lower()
    return any(kw in msg_lower for kw in lipstick_keywords)

def is_comparison_confirmation(msg: str) -> bool:
    """Check if user confirms wanting to compare products"""
    confirm_keywords = ['có', 'ok', 'được', 'yes', 'muốn', 'ừ', 'uh', 'đồng ý',
                        'so sánh', 'xem thử', 'cho xem', 'so sánh đi', 'thử so sánh']
    msg_lower = msg.lower().strip()
    return any(kw in msg_lower for kw in confirm_keywords)

def build_prompt(user_msg: str, hits: List[dict], lang: str, is_lipstick: bool = False, ask_compare: bool = False) -> str:
    # Check if this is a comparison request
    is_compare = is_comparison_request(user_msg)

    if lang == "vi":
        if is_compare and len(hits) >= 2:
            head = f"Khách hàng muốn SO SÁNH sản phẩm:\n{user_msg}\n\nDưới đây là các sản phẩm để so sánh:\n"
            rules = (
                "\nHƯỚNG DẪN SO SÁNH SẢN PHẨM (TIẾNG VIỆT, NGẮN GỌN):\n"
                "1) Tạo BẢNG SO SÁNH tóm tắt (ngắn gọn):\n"
                "   | Tiêu chí | SP 1 | SP 2 |\n"
                "   |----------|------|------|\n"
                "   | Tên      | (Tên ngắn) | (Tên ngắn) |\n"
                "   | Giá      | ...  | ...  |\n"
                "   | Công dụng| (Ý chính) | (Ý chính) |\n"
                "   | Loại da  | (Da dầu/Khô/Mụn...) | ... |\n"
                "2) KẾT LUẬN NGẮN: Khuyên dùng loại nào cho da gì (ví dụ: Da dầu nên dùng X, da khô nên dùng Y).\n"
                "3) KHÔNG viết dài dòng. Tập trung vào LOẠI DA và CÔNG DỤNG CHÍNH.\n"
            )
        elif is_lipstick and len(hits) >= 2 and ask_compare:
            # Lipstick search - recommend 2 and ask to compare
            head = f"Khách hàng đang tìm son môi:\n{user_msg}\n\nDưới đây là 2 sản phẩm son môi phù hợp:\n"
            rules = (
                "\nHƯỚNG DẪN TRẢ LỜI SON MÔI (TIẾNG VIỆT):\n"
                "1) Giới thiệu ĐÚNG 2 sản phẩm son môi, mỗi sản phẩm theo định dạng:\n"
                "   💄 Tên sản phẩm - Thương hiệu\n"
                "   • Giá: XXX VND\n"
                "   • Màu sắc/Đặc điểm nổi bật\n"
                "2) SAU KHI giới thiệu 2 sản phẩm, HỎI: 'Bạn có muốn so sánh 2 sản phẩm này không?'\n"
                "3) Trả lời thân thiện, tự nhiên.\n"
            )
        else:
            head = f"Khách hàng đang tìm kiếm:\n{user_msg}\n\nDưới đây là các sản phẩm phù hợp trong cửa hàng:\n"
            rules = (
                "\nHƯỚNG DẪN TRẢ LỜI (TIẾNG VIỆT, tự nhiên, thân thiện, dễ đọc):\n"
                "1) Chào hỏi khách hàng một cách thân thiện, tự nhiên như: 'Chào bạn!', 'Xin chào!', 'Cảm ơn bạn đã quan tâm!'\n"
                "2) CHỈ giới thiệu TỐI ĐA 2 sản phẩm phù hợp NHẤT, trình bày rõ ràng theo định dạng:\n"
                "   • Tên sản phẩm - Thương hiệu - Giá: XXX VND\n"
                "   • Đặc điểm nổi bật (1 câu ngắn gọn)\n"
                "3) Dùng dấu gạch đầu dòng (•) hoặc số thứ tự để dễ đọc.\n"
                "4) Trả lời tự nhiên, thân thiện, như đang tư vấn trực tiếp, không cứng nhắc.\n"
                "5) Nếu không có sản phẩm phù hợp, hãy hỏi thêm về nhu cầu cụ thể một cách thân thiện.\n"
                "6) Giữ độ dài khoảng 3-5 câu, ngắn gọn, súc tích, dễ đọc. CHỈ giới thiệu 2 sản phẩm phù hợp nhất.\n"
                "7) Kết thúc bằng câu hỏi thân thiện như: 'Bạn có muốn biết thêm thông tin gì không?' hoặc 'Bạn có câu hỏi gì khác không?'\n"
            )
    else:
        if is_compare and len(hits) >= 2:
            head = f"Customer wants to COMPARE products:\n{user_msg}\n\nHere are the products to compare:\n"
            rules = (
                "\nPRODUCT COMPARISON GUIDELINES (SHORT & CONCISE):\n"
                "1) Create a BRIEF comparison table:\n"
                "   • Product Name (Short)\n"
                "   • Price\n"
                "   • Main Benefit\n"
                "   • Skin Type (Oily/Dry/Sensitive...)\n"
                "2) SHORT CONCLUSION: Recommend based on skin type only.\n"
                "3) Keep it short. Focus on SKIN TYPE and KEY BENEFITS.\n"
            )
        else:
            head = f"Customer is searching for:\n{user_msg}\n\nHere are matching products in store:\n"
            rules = (
                "\nREPLY GUIDELINES (ENGLISH, natural like a real person):\n"
                "1) Reply friendly, greet the customer like a real sales consultant.\n"
                "2) Introduce 2-3 products matching the query, include: Product name, Brand, Price.\n"
                "3) If detailed info (description) is available, briefly mention key benefits/features.\n"
                "4) Reply naturally, not stiff, like you're consulting directly.\n"
                "5) If no matching products, ask for more specific needs.\n"
                "6) Keep length around 4-6 sentences, not too verbose.\n"
            )

    # Cắt ngữ cảnh theo CTX_CHARS để giảm độ trễ
    # CRITICAL: Limit to maximum 2 products for better relevance
    lines, acc = [], 0
    max_products = min(TOP_K, 2)  # Ensure maximum 2 products only
    
    for h in hits[:max_products]:
        # Tối ưu hóa Context: Lấy Metadata (Giá/Tên) trước để đảm bảo chính xác
        m = h.get("meta") or {}
        name  = (m.get("TenSanPham","") or "")[:150]
        brand = (m.get("ThuongHieu","") or "")[:80]
        price = str(m.get("Gia", ""))
        
        # Format giá tiền
        if price and price != "None":
            try:
                price_num = int(float(price))
                price_str = f"{price_num:,} VND"
            except:
                price_str = f"{price} VND"
        else:
            price_str = "Liên hệ"

        # Lấy description từ doc, nhưng cắt bớt nếu quá dài để nhường chỗ cho sản phẩm khác
        raw_doc = h.get("doc", "")
        # Loại bỏ phần trùng lặp Tên/Giá trong doc nếu có (để tiết kiệm token)
        # Chỉ lấy phần mô tả chi tiết (thường nằm sau)
        desc_start = raw_doc.find("MoTa:")
        if desc_start != -1:
            desc = raw_doc[desc_start:] 
        else:
            desc = raw_doc

        # Construct context block chuẩn xác
        block = (
            f"--- SẢN PHẨM ---\n"
            f"Tên: {name}\n"
            f"Thương hiệu: {brand}\n"
            f"Giá: {price_str} (CHÍNH XÁC)\n"
            f"{desc}\n"
            f"----------------"
        )
        
        # Nếu block quá dài, cắt bớt phần mô tả ở cuối, GIỮ LẠI TÊN VÀ GIÁ Ở ĐẦU
        # TỐI ƯU HIỆU NĂNG: Giảm giới hạn ký tự mỗi sản phẩm xuống 800 để xử lý nhanh hơn
        if len(block) > 800:
            block = block[:800] + "\n...(đã cắt bớt để tối ưu tốc độ)..."

        if acc + len(block) > CTX_CHARS: 
            # Nếu hết chỗ, vẫn cố nhét Tên/Giá của sản phẩm này vào
            mini_block = (
                f"--- SẢN PHẨM (Tóm tắt) ---\n"
                f"Tên: {name}\n"
                f"Giá: {price_str}\n"
                f"----------------"
            )
            if acc + len(mini_block) <= CTX_CHARS:
                lines.append(mini_block)
                acc += len(mini_block)
            break
        
        lines.append(block)
        acc += len(block)

    return head + "\n".join(lines) + rules

def build_knowledge_prompt(user_msg: str, knowledge_hits: List[dict], lang: str) -> str:
    """Build prompt for knowledge-based queries (FAQs, shop info)"""
    if lang == "vi":
        head = f"Khách hàng hỏi:\n{user_msg}\n\nThông tin liên quan:\n"
        rules = (
            "\nHƯỚNG DẪN TRẢ LỜI:\n"
            "1) Trả lời trực tiếp, rõ ràng dựa trên thông tin được cung cấp.\n"
            "2) Nếu hỏi về loại da, giải thích cụ thể sản phẩm nào phù hợp.\n"
            "3) Nếu hỏi về cửa hàng, cung cấp thông tin chính xác.\n"
            "4) Giữ câu trả lời ngắn gọn, dễ hiểu.\n"
        )
    else:
        head = f"Customer asks:\n{user_msg}\n\nRelated information:\n"
        rules = (
            "\nGUIDELINES:\n"
            "1) Answer directly based on provided information.\n"
            "2) If about skin type, explain which products are suitable.\n"
            "3) If about shop, provide accurate information.\n"
            "4) Keep answer concise and easy to understand.\n"
        )

    lines = []
    for h in knowledge_hits:
        doc = h.get("doc", "")[:500]
        lines.append(doc)

    return head + "\n---\n".join(lines) + rules

# Shop information for chatbot knowledge (hardcoded backup)
SHOP_INFO_VI = """
THÔNG TIN CỬA HÀNG MƠ SHOP:
• Giờ làm việc: Thứ 2 - Thứ 7: 8:00 - 21:00, Chủ nhật: 9:00 - 18:00
• Đội ngũ nhân viên: 8 người (5 tư vấn viên, 3 shipper)
• Địa chỉ: 123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh
• Hotline: 1900-XXXX (8:00 - 21:00)
• Email: contact@moshop.vn
• Website: www.moshop.vn
• Chính sách: Đổi trả trong 7 ngày, Freeship đơn từ 500.000đ
• Thanh toán: COD, Chuyển khoản, Ví điện tử (MoMo, ZaloPay)
"""

SHOP_INFO_EN = """
MƠ SHOP INFORMATION:
• Working hours: Mon-Sat: 8:00 AM - 9:00 PM, Sun: 9:00 AM - 6:00 PM
• Staff: 8 people (5 consultants, 3 shippers)
• Address: 123 Nguyen Hue Street, District 1, Ho Chi Minh City
• Hotline: 1900-XXXX (8:00 AM - 9:00 PM)
• Email: contact@moshop.vn
• Website: www.moshop.vn
• Policy: 7-day return policy, Free shipping for orders over 500,000 VND
• Payment: COD, Bank transfer, E-wallets (MoMo, ZaloPay)
"""

def call_ollama(prompt: str, lang: str) -> str:
    if lang == "vi":
        systemmsg = ("Bạn là nhân viên tư vấn mỹ phẩm chuyên nghiệp, thân thiện tại Mơ Shop. "
                     "Hãy trả lời khách hàng một cách tự nhiên, nhiệt tình như đang nói chuyện trực tiếp. "
                     "Luôn trả lời bằng TIẾNG VIỆT, dùng ngôn ngữ gần gũi, dễ hiểu, thân thiện. "
                     "Trình bày sản phẩm một cách rõ ràng, dễ đọc với định dạng: Tên sản phẩm - Thương hiệu - Giá. "
                     "Chỉ đề cập thông tin có trong dữ liệu, không tự bịa đặt giá cả hay thông tin sản phẩm. "
                     "Giữ câu trả lời ngắn gọn, súc tích, dễ đọc.\n\n"
                     "KHI KHÁCH HỎI VỀ THÔNG TIN CỬA HÀNG (giờ làm việc, địa chỉ, nhân viên, chính sách, hotline...), "
                     "hãy sử dụng thông tin sau:\n" + SHOP_INFO_VI)
        busy = BUSY_VI
    else:
        systemmsg = ("You are a friendly, professional cosmetics sales consultant at Mơ Shop. "
                     "Reply to customers in a natural, enthusiastic way like you're talking directly. "
                     "Always reply in ENGLISH, use approachable, easy-to-understand, friendly language. "
                     "Present products clearly and readably in format: Product Name - Brand - Price. "
                     "Only mention information available in the data, do not fabricate prices or product info. "
                     "Keep responses concise, clear, and easy to read.\n\n"
                     "WHEN CUSTOMERS ASK ABOUT SHOP INFO (working hours, address, staff, policies, hotline...), "
                     "use this information:\n" + SHOP_INFO_EN)
        busy = BUSY_EN

    payload = {
        "model": OLLAMA_MODEL,
        "messages": [
            {"role": "system", "content": systemmsg},
            {"role": "user", "content": prompt}
        ],
        "stream": False,
        "options": {
            "temperature": TEMP,
            "num_ctx": NUM_CTX,
            **({"num_thread": NUM_THREAD} if NUM_THREAD > 0 else {})
        }
    }
    try:
        # Check if Ollama is reachable first
        # try:
        #     print(f"[OLLAMA] Checking connection to {OLLAMA_HOST}/api/tags")
        #     check_req = http.get(f"{OLLAMA_HOST}/api/tags", timeout=3)
        #     print(f"[OLLAMA] Connection check status: {check_req.status_code}")
        #     if check_req.status_code != 200:
        #         print(f"!!! OLLAMA not reachable: HTTP {check_req.status_code}")
        #         return busy
        # except Exception as check_err:
        #     print(f"!!! OLLAMA connection check failed: {check_err}")
        #     traceback.print_exc()
        #     return busy
        
        print(f"[OLLAMA] Sending request to {OLLAMA_HOST}/api/chat")
        print(f"[OLLAMA] Model: {OLLAMA_MODEL}, Timeout: {GEN_TIMEOUT}s")
        r = http.post(f"{OLLAMA_HOST}/api/chat", json=payload, timeout=GEN_TIMEOUT)
        print(f"[OLLAMA] Response status: {r.status_code}")
        if r.status_code != 200:
            err_text = r.text
            print(f"!!! OLLAMA HTTP ERROR: {r.status_code} - {err_text}")
            return BUSY_VI + f" (HTTP {r.status_code}: {err_text[:50]})"
            
        r.raise_for_status()
        data = r.json()
        print(f"[OLLAMA] Response keys: {list(data.keys())}")
        reply = (data.get("message", {}) or {}).get("content", "").strip()
        
        if not reply:
            print("!!! OLLAMA returned empty reply")
            print(f"!!! OLLAMA response data: {data}")
            return BUSY_VI + " (Empty reply from Ollama)"
            
        print(f"[OLLAMA] Success! Reply length: {len(reply)}")
        return reply
    except requests.exceptions.Timeout:
        print(f"!!! OLLAMA timeout after {GEN_TIMEOUT} seconds")
        return BUSY_VI + f" (TIMEOUT {GEN_TIMEOUT}s)"
    except requests.exceptions.ConnectionError as ce:
        print(f"!!! OLLAMA connection error: {ce}")
        traceback.print_exc()
        return busy
    except Exception as e:
        print(f"!!! OLLAMA ERROR: {e}")
        traceback.print_exc()
        return BUSY_VI + f" (Lỗi Ollama: {str(e)})"

# ===== Routes
@app.get("/ping")
def ping():
    return {"ok": True}

@app.post("/detect_language")
def detect_language_ep(req: DetectReq):
    lang = detect_lang(req.message or "")
    return {"language": lang}

@app.get("/warm")
def warm():
    """Preload model & keep alive."""
    try:
        http.get(f"{OLLAMA_HOST}/api/tags", timeout=3)
        _ = call_ollama("Say 'ready'.", "en")
        return {"ok": True}
    except Exception as e:
        return {"ok": False, "error": str(e)}

@app.on_event("startup")
def _startup_warm():
    # best-effort warmup (không chặn server)
    try:
        time.sleep(0.1)
        warm()
    except Exception:
        pass

@app.post("/chat")
def chat(req: ChatReq):
    try:
        print(f"[CHAT] Received request: {req.message[:50] if req.message else 'empty'}")
        msg = (req.message or "").strip()
        if not msg:
            print("[CHAT] Empty message received")
            return {"language": "unknown", "reply": "Xin vui lòng nhập nội dung.", "hits": []}

        # Check if components are initialized
        if embedder is None:
            print("[CHAT] ERROR: Embedder not initialized")
            return {"language": "vi", "reply": BUSY_VI + " (Embedder not initialized)", "hits": []}
        if collection is None:
            print("[CHAT] ERROR: Chroma collection not initialized")
            return {"language": "vi", "reply": BUSY_VI + " (Chroma not initialized)", "hits": []}

        print("[CHAT] Components OK, processing...")
        lang  = detect_lang(msg)
        print(f"[CHAT] Detected language: {lang}")

        # Check if this is a shop info or FAQ query
        is_shop_query = is_shop_info_query(msg)
        is_faq = is_faq_query(msg)

        if is_shop_query or is_faq:
            # Search knowledge collection for FAQs and shop info
            print(f"[CHAT] Detected knowledge query (shop_info={is_shop_query}, faq={is_faq})")
            knowledge_hits = retrieve_knowledge(msg, k=3)
            print(f"[CHAT] Retrieved {len(knowledge_hits)} knowledge hits")

            if knowledge_hits:
                prompt = build_knowledge_prompt(msg, knowledge_hits, lang)
            else:
                # Fallback to hardcoded shop info
                if lang == "vi":
                    prompt = f"Khách hàng hỏi: {msg}\n\n{SHOP_INFO_VI}\n\nHãy trả lời dựa trên thông tin trên."
                else:
                    prompt = f"Customer asks: {msg}\n\n{SHOP_INFO_EN}\n\nAnswer based on the above information."

            hits = []  # No product hits for knowledge queries
        else:
            # Check if this is a lipstick search
            is_lipstick = is_lipstick_search(msg)
            print(f"[CHAT] Is lipstick search: {is_lipstick}")

            # Product search
            hits  = retrieve(msg, k=TOP_K)
            print(f"[CHAT] Retrieved {len(hits)} hits from Chroma")

            # If no hits but it's a product search, try to give a helpful response
            if not hits:
                if is_product_search(msg) or "so sánh" in msg.lower() or "so sanh" in msg.lower():
                    # Trường hợp hỏi so sánh nhưng không nói rõ tên sản phẩm
                    if "so sánh" in msg.lower() or "so sanh" in msg.lower():
                        prompt = (
                            "Khách hàng nói: 'so sánh'.\n"
                            "Bạn là nhân viên Mơ Shop. Hãy hỏi lại khách hàng thân thiện: 'Dạ, bạn muốn mình so sánh 2 sản phẩm nào ạ? Bạn có thể cho mình xin tên sản phẩm được không?'"
                        )
                    else:
                        # Vẫn search nhưng không ra kết quả
                        prompt = f"Khách hàng đang tìm kiếm: {msg}\n\nBạn là nhân viên tư vấn tại Mơ Shop. Hiện tại hệ thống chưa tìm thấy sản phẩm này. Hãy trả lời thân thiện và hỏi thêm về nhu cầu cụ thể của khách hàng."
                    print("[CHAT] Using fallback prompt (no RAG hits)")
                else:
                    # Chat chit xã giao thông thường
                     prompt = build_prompt(msg, [], lang) # Empty hits -> Chit chat mode
            else:
                # For lipstick search with 2+ products, ask if user wants to compare
                if is_lipstick and len(hits) >= 2:
                    prompt = build_prompt(msg, hits[:2], lang, is_lipstick=True, ask_compare=True)
                    print("[CHAT] Using lipstick prompt with compare question")
                else:
                    prompt = build_prompt(msg, hits, lang)
        
        print("[CHAT] Calling Ollama...")
        reply  = call_ollama(prompt, lang)
        print(f"[CHAT] Ollama reply length: {len(reply)}")
        
        # If reply is still the busy message, log it
        if BUSY_VI in reply or BUSY_EN in reply:
            print(f"!!! Warning: Ollama returned busy message for query: {msg[:50]}")

        result = {"language": lang, "reply": reply, "hits": hits}
        print("[CHAT] ===== LLM RESPONSE =====")
        print(f"[CHAT] Language: {lang}")
        print(f"[CHAT] Reply length: {len(reply)}")
        print(f"[CHAT] Reply preview: {reply[:200]}")
        print(f"[CHAT] Hits count: {len(hits)}")
        print(f"[CHAT] LLM response: {result}")
        print("[CHAT] Returning success response")
        return result
    except Exception as e:
        err_msg = str(e)
        print(f"!!! CHAT ERROR: {err_msg}")
        traceback.print_exc()
        return {"language": "vi", "reply": BUSY_VI + f"\n[LỖI KỸ THUẬT: {err_msg}]", "hits": []}


from fastapi.responses import StreamingResponse
import json

def sse_format(data_obj: dict) -> str:
    """Định dạng dữ liệu SSE."""
    return f"data: {json.dumps(data_obj, ensure_ascii=False)}\n\n"

def stream_ollama(prompt: str, lang: str):
    """Gọi Ollama stream để hiển thị typing."""
    if lang == "vi":
        systemmsg = "Bạn là trợ lý bán mỹ phẩm. Hãy trả lời TIẾNG VIỆT, tự nhiên, chính xác, ngắn gọn."
    else:
        systemmsg = "You are a cosmetics sales assistant. Answer in ENGLISH, concise and natural."

    payload = {
        "model": OLLAMA_MODEL,
        "messages": [
            {"role": "system", "content": systemmsg},
            {"role": "user", "content": prompt}
        ],
        "stream": True,
        "options": {
            "temperature": TEMP,
            "num_ctx": NUM_CTX
        }
    }

    try:
        with requests.post(f"{OLLAMA_HOST}/api/chat", json=payload, stream=True, timeout=GEN_TIMEOUT) as r:
            r.raise_for_status()
            full_text = ""
            yield sse_format({"event": "start"})
            for line in r.iter_lines(decode_unicode=True):
                if not line:
                    continue
                try:
                    obj = json.loads(line)
                except Exception:
                    continue
                msg = ((obj.get("message") or {}).get("content") or "")
                if msg:
                    full_text += msg
                    yield sse_format({"event": "chunk", "text": msg})
                if obj.get("done"):
                    break
            yield sse_format({"event": "end", "text": full_text})
    except Exception as e:
        yield sse_format({"event": "error", "text": f"Lỗi stream: {e}"})

@app.post("/chat_stream")
def chat_stream(req: ChatReq):
    """Endpoint stream giống ChatGPT (typing effect)."""
    msg = (req.message or "").strip()
    if not msg:
        return StreamingResponse(iter([sse_format({"event": "error", "text": "Nội dung trống"})]),
                                 media_type="text/event-stream")
    lang  = detect_lang(msg)
    hits  = retrieve(msg, k=TOP_K)
    prompt = build_prompt(msg, hits, lang)

    return StreamingResponse(
        stream_ollama(prompt, lang),
        media_type="text/event-stream",
        headers={"Cache-Control": "no-cache", "X-Accel-Buffering": "no"}
    )


# ---- Run directly
if __name__ == "__main__":
    import uvicorn
    print("[DEBUG] starting uvicorn ...")
    uvicorn.run("api_rag_ollama:app", host="127.0.0.1", port=8010, reload=False, log_level="info")
