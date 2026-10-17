<?php
declare(strict_types=1);
require_once __DIR__ . '/environment.php';
require_once dirname(__DIR__, 2) . '/TTTT/config/bootstrap.php';
$db = app_pdo();
// Add only synthetic fixture columns needed to render the existing pages.
foreach ([
    'khachhang' => ['AnhDaiDien'=>'VARCHAR(255) NULL', 'SoDu'=>'BIGINT DEFAULT 0'],
    'danhmuc' => ['AnhDanhMuc'=>"VARCHAR(255) DEFAULT ''"],
    'thuonghieu' => ['AnhThuongHieu'=>"VARCHAR(255) DEFAULT ''"],
    'sanpham' => ['IDDanhMuc'=>'INT DEFAULT 1', 'IDThuongHieu'=>'INT DEFAULT 1', 'SoLuong'=>'INT DEFAULT 5',
        'GiaTien'=>'INT DEFAULT 100000', 'MoTaChiTiet'=>"TEXT NULL", 'AnhSanPham'=>"VARCHAR(255) DEFAULT ''",
        'NoiBat'=>'INT DEFAULT 0', 'ThoiGianTao'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 'ThoiGianCapNhat'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
] as $table => $columns) {
    foreach ($columns as $column => $definition) {
        $q = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $q->execute([$table,$column]);
        if (!(int)$q->fetchColumn()) { $db->exec("ALTER TABLE $table ADD COLUMN $column $definition"); }
    }
}
foreach ([
    'danhmuc'=>'IDDanhMuc INT PRIMARY KEY, TenDanhMuc VARCHAR(100)',
    'thuonghieu'=>'IDThuongHieu INT PRIMARY KEY, TenThuongHieu VARCHAR(100)',
    'donhang'=>'ID_DonHang INT PRIMARY KEY, ID_KhachHang INT, TrangThai INT, GhiChu TEXT',
    'donhangchitiet'=>'ID_DonHangChiTiet INT AUTO_INCREMENT PRIMARY KEY, ID_DonHang INT, ID_SanPham INT',
    'return_refund_requests'=>'ID_Request INT PRIMARY KEY, ID_DonHang INT, ID_KhachHang INT, ID_SanPham INT, TrangThai VARCHAR(20)',
    'binhluan'=>'ID_BinhLuan INT AUTO_INCREMENT PRIMARY KEY, ID_KhachHang INT, ID_SanPham INT, NoiDungBinhLuan VARCHAR(1000), DaMuaHang INT',
    'product_images'=>'ID_SanPham INT, DuongDan VARCHAR(255), ThuTu INT',
    'danhgia'=>'ID_KhachHang INT, ID_SanPham INT, SoSao INT',
    'admin_notifications'=>'id INT PRIMARY KEY, is_read INT',
    'user_events'=>'id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, session_id VARCHAR(128), event_type VARCHAR(50), metadata JSON, created_at DATETIME',
    'chat_appointments'=>'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, session_id VARCHAR(128) NOT NULL, appt_at DATETIME NOT NULL, note TEXT NULL',
] as $table => $columns) { $db->exec("CREATE TABLE IF NOT EXISTS $table ($columns) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }
$db->exec("INSERT INTO danhmuc (IDDanhMuc,TenDanhMuc,AnhDanhMuc) VALUES (1,'Danh mục thử',''),(2,'Danh mục chưa dùng','')
    ON DUPLICATE KEY UPDATE TenDanhMuc=VALUES(TenDanhMuc),AnhDanhMuc=VALUES(AnhDanhMuc)");
$db->exec("INSERT INTO thuonghieu (IDThuongHieu,TenThuongHieu,AnhThuongHieu) VALUES (1,'Thương hiệu thử',''),(2,'Thương hiệu chưa dùng','')
    ON DUPLICATE KEY UPDATE TenThuongHieu=VALUES(TenThuongHieu),AnhThuongHieu=VALUES(AnhThuongHieu)");
$db->exec("REPLACE INTO khachhang (ID_KhachHang,TenDangNhap,HoVaTen,Role) VALUES
    (1,'buyer','Khách <script>window.fixtureXss=1</script>','customer'),
    (3,'admin','Admin thử','admin'),
    (4,'staff','Nhân viên thử','employee'),
    (5,'manager','Quản lý thử','manager'),
    (6,'shipper','Giao hàng thử','shipper')");
$db->exec("INSERT INTO sanpham (IDSanPham,TenSanPham,IDDanhMuc,IDThuongHieu,MoTaChiTiet) VALUES
    (1,'Sản phẩm có đơn',1,1,'Mô tả thử'),(2,'Sản phẩm chưa bán',1,1,'Mô tả thử')
    ON DUPLICATE KEY UPDATE TenSanPham=VALUES(TenSanPham),IDDanhMuc=VALUES(IDDanhMuc),
        IDThuongHieu=VALUES(IDThuongHieu),MoTaChiTiet=VALUES(MoTaChiTiet),NoiBat=0");
$db->exec("REPLACE INTO donhang (ID_DonHang,ID_KhachHang,TrangThai,GhiChu) VALUES (1,1,3,'')");
$db->exec('DELETE FROM donhangchitiet WHERE ID_DonHang=1');
$db->exec('INSERT INTO donhangchitiet (ID_DonHang,ID_SanPham) VALUES (1,1)');
$db->exec("DELETE FROM binhluan WHERE NoiDungBinhLuan LIKE 'Browser %'");
$db->exec("INSERT INTO binhluan (ID_KhachHang,ID_SanPham,NoiDungBinhLuan,DaMuaHang) VALUES (1,1,'<img src=x onerror=window.fixtureXss=1> Nội dung cũ',1)");
echo "comments_browser_fixture=ok\n";
