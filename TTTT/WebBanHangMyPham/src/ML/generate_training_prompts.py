# generate_training_prompts.py
# Tạo các câu hỏi huấn luyện từ CSV/SQL để huấn luyện chatbot RAG
import csv
import re
import json
from pathlib import Path

def strip_html(text):
    # làm sạch mô tả bằng regex  
    """Remove HTML tags and clean text."""
    if not text:
        return ""
    text = re.sub(r'<[^>]+>', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text

def read_csv_products(csv_path):
    """Read products from CSV file."""
    products = []
    with open(csv_path, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            products.append({
                'id': row.get('IDSanPham', '').strip(),
                'name': row.get('TenSanPham', '').strip(),
                'category': row.get('IDDanhMuc', '').strip(),
                'brand': row.get('IDThuongHieu', '').strip(),
                'price': row.get('GiaTien', '').strip() or row.get('Gia', '').strip(),
                'description': strip_html(row.get('MoTaChiTiet', '').strip())
            })
    return products

def generate_training_prompts(products):
    """Generate training prompts for RAG chatbot."""
    prompts = []
    
    # Template for product search scenarios
    search_templates = [
        {
            "user": "Tôi muốn tìm {product_type}",
            "bot": "Tôi đã tìm thấy một số {product_type} phù hợp. Bạn có muốn đặt lịch để nhân viên bên mình tư vấn cho bạn không? (Có/Không)",
            "context": "product_search"
        },
        {
            "user": "Bạn có sản phẩm {product_name} không?",
            "bot": "Có ạ! Chúng tôi có {product_name}. Bạn có muốn đặt lịch để nhân viên bên mình tư vấn chi tiết không? (Có/Không)",
            "context": "product_inquiry"
        },
        {
            "user": "Giá của {product_name} là bao nhiêu?",
            "bot": "Sản phẩm {product_name} có giá {price} VNĐ. Bạn có muốn đặt lịch tư vấn để được tư vấn chi tiết về sản phẩm không? (Có/Không)",
            "context": "price_inquiry"
        }
    ]
    
    # Product types from descriptions
    product_types = {
        'son': ['son', 'son môi', 'son dưỡng'],
        'kem': ['kem', 'kem chống nắng', 'kem dưỡng'],
        'mặt nạ': ['mặt nạ', 'mask'],
        'sữa rửa mặt': ['sữa rửa mặt', 'cleanser'],
        'serum': ['serum'],
        'nước tẩy trang': ['nước tẩy trang', 'tẩy trang']
    }
    
    # Tạo lời nhắc cho từng sản phẩm
    for product in products:
        if not product['name']:
            continue
            
        # Trích xuất loại sản phẩm từ tên/mô tả
        product_type = None
        name_lower = product['name'].lower()
        desc_lower = product['description'].lower()
        
        for pt, keywords in product_types.items():
            if any(kw in name_lower or kw in desc_lower for kw in keywords):
                product_type = pt
                break
        
        if not product_type:
            product_type = "sản phẩm"
        
        # Generate search prompts
        for template in search_templates:
            user_msg = template['user'].format(
                product_type=product_type,
                product_name=product['name'],
                price=product['price']
            )
            bot_msg = template['bot'].format(
                product_type=product_type,
                product_name=product['name'],
                price=product['price']
            )
            
            prompts.append({
                "instruction": "You are a cosmetics sales assistant. When a customer searches for a product, you should ask if they want to schedule a consultation.",
                "input": user_msg,
                "output": bot_msg,
                "context": template['context'],
                "metadata": {
                    "product_id": product['id'],
                    "product_name": product['name']
                }
            })
    
    # Thêm tình huống đặt lịch tư vấn
    booking_prompts = [
        {
            "instruction": "When customer says yes to consultation booking, show booking form.",
            "input": "Có, tôi muốn đặt lịch tư vấn",
            "output": "Bạn muốn đặt lịch tư vấn vào thời gian nào ạ? Bạn hãy chọn ở **biểu mẫu dưới đây** hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n[[BOOKING_FORM]]",
            "context": "booking_yes"
        },
        {
            "instruction": "When customer says no to consultation, give friendly response.",
            "input": "Không, cảm ơn",
            "output": "Cảm ơn bạn đã tin tưởng sản phẩm của chúng tôi. Nếu bạn có bất kỳ câu hỏi nào, hãy cứ nhắn tin cho trợ lý bán hàng này nhé!",
            "context": "booking_no"
        },
        {
            "instruction": "When customer provides booking date/time, confirm and save.",
            "input": "05/11/2025 14:30",
            "output": "Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **05/11/2025 14:30**.\nNhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch.",
            "context": "booking_confirmed"
        }
    ]
    
    prompts.extend(booking_prompts)
    
    return prompts

def read_products_from_db():
    """Read products directly from MySQL database."""
    import os
    import mysql.connector
    from env_loader import load_project_env

    load_project_env(__file__)
    def required(name):
        value = os.environ.get(name)
        if not value:
            raise RuntimeError(f"Missing required database environment: {name}")
        return value
    DB_HOST = required("MYSQL_HOST")
    DB_PORT = int(os.getenv("MYSQL_PORT", "3306"))
    DB_USER = required("MYSQL_USER")
    DB_PASS = os.getenv("MYSQL_PASSWORD", "")
    DB_NAME = required("MYSQL_DB")
    DB_CHARSET = os.getenv("MYSQL_CHARSET", "utf8mb4")
    
    conn = mysql.connector.connect(
        host=DB_HOST, port=DB_PORT,
        user=DB_USER, password=DB_PASS,
        database=DB_NAME, charset=DB_CHARSET, use_unicode=True,
    )
    
    products = []
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute("SHOW TABLES LIKE 'danhmuc'")
        has_dm = cur.fetchone() is not None
        cur.execute("SHOW TABLES LIKE 'thuonghieu'")
        has_th = cur.fetchone() is not None
        
        if has_dm and has_th:
            sql = """
                SELECT sp.IDSanPham,
                       sp.TenSanPham,
                       sp.MoTaChiTiet,
                       dm.TenDanhMuc AS DanhMuc,
                       th.TenThuongHieu AS ThuongHieu,
                       sp.GiaTien AS GiaTien
                FROM sanpham sp
                LEFT JOIN danhmuc dm ON sp.IDDanhMuc = dm.IDDanhMuc
                LEFT JOIN thuonghieu th ON sp.IDThuongHieu = th.IDThuongHieu
                ORDER BY sp.IDSanPham DESC
            """
        else:
            sql = """
                SELECT sp.IDSanPham,
                       sp.TenSanPham,
                       sp.MoTaChiTiet,
                       sp.IDDanhMuc AS DanhMuc,
                       sp.IDThuongHieu AS ThuongHieu,
                       sp.GiaTien AS GiaTien
                FROM sanpham sp
                ORDER BY sp.IDSanPham DESC
            """
        
        cur.execute(sql)
        rows = cur.fetchall()
        
        for row in rows:
            products.append({
                'id': str(row.get('IDSanPham', '')),
                'name': row.get('TenSanPham', '').strip(),
                'category': str(row.get('DanhMuc', '')),
                'brand': str(row.get('ThuongHieu', '')),
                'price': str(row.get('GiaTien', '')),
                'description': strip_html(row.get('MoTaChiTiet', ''))
            })
    finally:
        conn.close()
    
    return products

def main():
    # Paths
    csv_path = Path(__file__).parent.parent.parent.parent / "sanpham.csv"
    output_path = Path(__file__).parent / "training_prompts.json"
    
    products = []
    
    # Try to read from CSV first, then fallback to database
    if csv_path.exists():
        print(f"Reading products from CSV: {csv_path}")
        products = read_csv_products(csv_path)
    else:
        print("CSV file not found. Reading from database...")
        try:
            products = read_products_from_db()
        except Exception as e:
            print(f"ERROR: Could not read from database: {e}")
            print("Please ensure:")
            print("1. MySQL is running")
            print("2. Database credentials are correct in .env file")
            print("3. Or create a CSV file at:", csv_path)
            return
    
    if not products:
        print("ERROR: No products found!")
        return
    
    print(f"Loaded {len(products)} products")
    
    prompts = generate_training_prompts(products)
    print(f"Generated {len(prompts)} training prompts")
    
    # Save to JSON
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(prompts, f, ensure_ascii=False, indent=2)
    
    print(f"Saved training prompts to: {output_path}")
    
    # Also save as plain text for review
    txt_path = output_path.with_suffix('.txt')
    with open(txt_path, 'w', encoding='utf-8') as f:
        for i, prompt in enumerate(prompts, 1):
            f.write(f"\n=== Prompt {i} ===\n")
            f.write(f"Context: {prompt['context']}\n")
            f.write(f"User: {prompt['input']}\n")
            f.write(f"Bot: {prompt['output']}\n")
            if prompt.get('metadata'):
                f.write(f"Metadata: {prompt['metadata']}\n")
    
    print(f"Also saved as text file: {txt_path}")

if __name__ == "__main__":
    main()

