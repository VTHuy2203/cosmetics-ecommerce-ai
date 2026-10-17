CREATE TABLE IF NOT EXISTS contact_messages (
    ID_LienHe INT AUTO_INCREMENT PRIMARY KEY,
    HoTen VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL,
    SoDienThoai VARCHAR(20) NULL,
    NoiDung TEXT NOT NULL,
    TrangThai TINYINT(1) NOT NULL DEFAULT 0,
    ThoiGianTao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_status (TrangThai),
    KEY idx_contact_created (ThoiGianTao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS danhgia (
    ID_DanhGia INT AUTO_INCREMENT PRIMARY KEY,
    ID_KhachHang INT NOT NULL,
    ID_SanPham INT NOT NULL,
    SoSao TINYINT UNSIGNED NOT NULL,
    ThoiGianTao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rating_customer_product (ID_KhachHang, ID_SanPham),
    CONSTRAINT fk_rating_customer FOREIGN KEY (ID_KhachHang) REFERENCES khachhang (ID_KhachHang) ON DELETE CASCADE,
    CONSTRAINT fk_rating_product FOREIGN KEY (ID_SanPham) REFERENCES sanpham (IDSanPham) ON DELETE RESTRICT,
    CONSTRAINT chk_rating_value CHECK (SoSao BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
