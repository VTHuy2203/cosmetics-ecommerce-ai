# export_products_to_csv.py
# Export products from MySQL database to CSV for training prompts generation
import os
import mysql.connector
from mysql.connector import Error as MySQLError
import csv
from env_loader import load_project_env

load_project_env(__file__)

# ---- ENV ----
def required_env(name):
    value = os.getenv(name)
    if not value:
        raise RuntimeError(f"Missing required database environment: {name}")
    return value

DB_HOST = required_env("MYSQL_HOST")
DB_PORT = int(os.getenv("MYSQL_PORT", "3306"))
DB_USER = required_env("MYSQL_USER")
DB_PASS = os.getenv("MYSQL_PASSWORD", "")
DB_NAME = required_env("MYSQL_DB")
DB_CHARSET = os.getenv("MYSQL_CHARSET", "utf8mb4")

def get_db():
    return mysql.connector.connect(
        host=DB_HOST, port=DB_PORT,
        user=DB_USER, password=DB_PASS,
        database=DB_NAME, charset=DB_CHARSET, use_unicode=True,
    )

def export_to_csv(output_path):
    """Export products to CSV file."""
    conn = get_db()
    try:
        cur = conn.cursor(dictionary=True)
        
        # Check if tables exist
        cur.execute("SHOW TABLES LIKE 'danhmuc'")
        has_dm = cur.fetchone() is not None
        cur.execute("SHOW TABLES LIKE 'thuonghieu'")
        has_th = cur.fetchone() is not None
        
        if has_dm and has_th:
            sql = """
                SELECT sp.IDSanPham,
                       sp.TenSanPham,
                       sp.MoTaChiTiet,
                       sp.IDDanhMuc,
                       dm.TenDanhMuc,
                       sp.IDThuongHieu,
                       th.TenThuongHieu,
                       sp.GiaTien AS GiaTien,
                       sp.SoLuong
                FROM sanpham sp
                LEFT JOIN danhmuc dm     ON sp.IDDanhMuc = dm.IDDanhMuc
                LEFT JOIN thuonghieu th  ON sp.IDThuongHieu = th.IDThuongHieu
                ORDER BY sp.IDSanPham DESC
            """
        else:
            sql = """
                SELECT sp.IDSanPham,
                       sp.TenSanPham,
                       sp.MoTaChiTiet,
                       sp.IDDanhMuc,
                       NULL AS TenDanhMuc,
                       sp.IDThuongHieu,
                       NULL AS TenThuongHieu,
                       sp.GiaTien AS GiaTien,
                       sp.SoLuong
                FROM sanpham sp
                ORDER BY sp.IDSanPham DESC
            """
        
        cur.execute(sql)
        rows = cur.fetchall()
        
        # Write to CSV
        with open(output_path, 'w', encoding='utf-8', newline='') as f:
            if rows:
                fieldnames = rows[0].keys()
                writer = csv.DictWriter(f, fieldnames=fieldnames)
                writer.writeheader()
                writer.writerows(rows)
        
        print(f"Exported {len(rows)} products to {output_path}")
        return len(rows)
    except MySQLError as e:
        print(f"MySQL Error: {e}")
        return 0
    finally:
        try:
            conn.close()
        except:
            pass

if __name__ == "__main__":
    from pathlib import Path
    csv_path = Path(__file__).parent.parent.parent.parent / "sanpham.csv"
    export_to_csv(csv_path)
