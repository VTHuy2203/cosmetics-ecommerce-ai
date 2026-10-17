-- F08 core schema bootstrap generated from the legacy dump schema only.
-- Contains no INSERT statements, credentials, or user data.
-- Existing installations are unchanged because every base table uses CREATE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS `binhluan` (
  `ID_BinhLuan` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `ID_SanPham` int NOT NULL,
  `NoiDungBinhLuan` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DaMuaHang` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `chat_appointments` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `appt_at` datetime NOT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `chat_bookings` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `book_at` datetime NOT NULL,
  `note` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `reply` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `message` text NOT NULL,
  `direction` enum('user','bot') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `chat_session_state` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) NOT NULL,
  `state` enum('idle','await_schedule_confirm','await_datetime') NOT NULL DEFAULT 'idle',
  `lang` varchar(8) DEFAULT NULL,
  `tmp_note` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `consultations` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `appointment_time` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` enum('requested','confirmed','done','cancelled') NOT NULL DEFAULT 'requested',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `customer_needs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `need_text` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `danhmuc` (
  `IDDanhMuc` int NOT NULL,
  `TenDanhMuc` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `AnhDanhMuc` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `danhsachyeuthich` (
  `ID_DSYeuThich` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `ID_SanPham` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `donhang` (
  `ID_DonHang` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `HoVaTen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `SDT` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DiaChi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `TongTien` int NOT NULL,
  `TrangThai` int NOT NULL,
  `DaThanhToan` tinyint(1) NOT NULL DEFAULT '0',
  `GhiChu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `donhangchitiet` (
  `ID_DonHangChiTiet` int NOT NULL,
  `ID_DonHang` int NOT NULL,
  `ID_SanPham` int NOT NULL,
  `SoLuong` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `khachhang` (
  `ID_KhachHang` int NOT NULL,
  `TenDangNhap` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `MatKhau` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `HoVaTen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `SDT` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DiaChi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `SoDu` bigint NOT NULL DEFAULT '0',
  `AnhDaiDien` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ThoiGianTao` datetime DEFAULT CURRENT_TIMESTAMP,
  `ThoiGianCapNhat` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `leads` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `HoTen` varchar(255) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `SDT` varchar(50) DEFAULT NULL,
  `NoiDung` text,
  `ThoiGianTao` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_message` text,
  `product_interest` varchar(255) DEFAULT NULL,
  `last_interaction` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `lead_events` (
  `id` bigint NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `direction` enum('user','bot') NOT NULL,
  `message` text NOT NULL,
  `intent` varchar(50) DEFAULT NULL,
  `product_interest` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ts` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `lead_scores` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `label` enum('potential','neutral','spam') NOT NULL DEFAULT 'neutral',
  `probability` float DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `nap_tien` (
  `ID_Nap` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `SoTien` bigint NOT NULL,
  `NoiDung` varchar(255) NOT NULL,
  `TrangThai` tinyint(1) NOT NULL DEFAULT '0',
  `NgayTao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `sanpham` (
  `IDSanPham` int NOT NULL,
  `TenSanPham` varchar(5000) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `IDDanhMuc` int NOT NULL,
  `IDThuongHieu` int NOT NULL,
  `SoLuong` int NOT NULL,
  `GiaTien` int NOT NULL,
  `MoTaChiTiet` varchar(10000) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `AnhSanPham` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `NoiBat` int NOT NULL,
  `ThoiGianTao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ThoiGianCapNhat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Gia` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `thuonghieu` (
  `IDThuongHieu` int NOT NULL,
  `TenThuongHieu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `AnhThuongHieu` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tintucbaiviet` (
  `ID_TinTucBaiViet` int NOT NULL,
  `TieuDeBaiViet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `BaiViet` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `AnhBaiViet` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users` (
`email` varchar(100)
,`first_visit` datetime
,`id` int
,`last_visit` datetime
,`name` varchar(50)
,`phone` varchar(20)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_actions`
--

CREATE TABLE IF NOT EXISTS `user_actions` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `user_events` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `event_type` varchar(64) NOT NULL,
  `metadata` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `user_intents` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `intent` varchar(64) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `binhluan`
  ADD PRIMARY KEY (`ID_BinhLuan`),
  ADD KEY `ID_KhachHang` (`ID_KhachHang`),
  ADD KEY `sanpham5` (`ID_SanPham`);

ALTER TABLE `chat_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sid` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_appt` (`appt_at`);

ALTER TABLE `chat_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_book_at` (`book_at`);

ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user2` (`user_id`),
  ADD KEY `idx_time` (`timestamp`);

ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

ALTER TABLE `chat_session_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chat_state_session` (`session_id`),
  ADD UNIQUE KEY `uniq_chat_state_user` (`user_id`),
  ADD KEY `state` (`state`);

ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `appointment_time` (`appointment_time`);

ALTER TABLE `customer_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`);

ALTER TABLE `danhmuc`
  ADD PRIMARY KEY (`IDDanhMuc`);

ALTER TABLE `danhsachyeuthich`
  ADD PRIMARY KEY (`ID_DSYeuThich`),
  ADD KEY `ID_KhachHang` (`ID_KhachHang`),
  ADD KEY `ID_SanPham` (`ID_SanPham`);

ALTER TABLE `donhang`
  ADD PRIMARY KEY (`ID_DonHang`),
  ADD KEY `khachhang` (`ID_KhachHang`);

ALTER TABLE `donhangchitiet`
  ADD PRIMARY KEY (`ID_DonHangChiTiet`),
  ADD KEY `sanpham` (`ID_SanPham`),
  ADD KEY `donhang` (`ID_DonHang`);

ALTER TABLE `khachhang`
  ADD PRIMARY KEY (`ID_KhachHang`);

ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `lead_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_intent` (`intent`);

ALTER TABLE `lead_events` ADD FULLTEXT KEY `ft_msg` (`message`);

ALTER TABLE `lead_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`),
  ADD UNIQUE KEY `uniq_session` (`session_id`);

ALTER TABLE `nap_tien`
  ADD PRIMARY KEY (`ID_Nap`);

ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`IDSanPham`),
  ADD KEY `danhmuc` (`IDDanhMuc`),
  ADD KEY `thuonghieu` (`IDThuongHieu`);

ALTER TABLE `sanpham` ADD FULLTEXT KEY `ft_name_desc` (`TenSanPham`,`MoTaChiTiet`);

ALTER TABLE `thuonghieu`
  ADD PRIMARY KEY (`IDThuongHieu`);

ALTER TABLE `tintucbaiviet`
  ADD PRIMARY KEY (`ID_TinTucBaiViet`);

ALTER TABLE `user_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`);

ALTER TABLE `user_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `event_type` (`event_type`);

ALTER TABLE `user_intents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `intent` (`intent`);

ALTER TABLE `binhluan`
  MODIFY `ID_BinhLuan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

ALTER TABLE `chat_appointments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `chat_bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `chat_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

ALTER TABLE `chat_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=430;

ALTER TABLE `chat_session_state`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `consultations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `customer_needs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `danhmuc`
  MODIFY `IDDanhMuc` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

ALTER TABLE `danhsachyeuthich`
  MODIFY `ID_DSYeuThich` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

ALTER TABLE `donhang`
  MODIFY `ID_DonHang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

ALTER TABLE `donhangchitiet`
  MODIFY `ID_DonHangChiTiet` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

ALTER TABLE `khachhang`
  MODIFY `ID_KhachHang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

ALTER TABLE `leads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `lead_events`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

ALTER TABLE `lead_scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=484;

ALTER TABLE `nap_tien`
  MODIFY `ID_Nap` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `sanpham`
  MODIFY `IDSanPham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

ALTER TABLE `thuonghieu`
  MODIFY `IDThuongHieu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

ALTER TABLE `tintucbaiviet`
  MODIFY `ID_TinTucBaiViet` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

ALTER TABLE `user_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

ALTER TABLE `user_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=416;

ALTER TABLE `user_intents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

ALTER TABLE `binhluan`
  ADD CONSTRAINT `binhluan_ibfk_1` FOREIGN KEY (`ID_KhachHang`) REFERENCES `khachhang` (`ID_KhachHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sanpham5` FOREIGN KEY (`ID_SanPham`) REFERENCES `sanpham` (`IDSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `danhsachyeuthich`
  ADD CONSTRAINT `danhsachyeuthich_ibfk_1` FOREIGN KEY (`ID_KhachHang`) REFERENCES `khachhang` (`ID_KhachHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `danhsachyeuthich_ibfk_2` FOREIGN KEY (`ID_SanPham`) REFERENCES `sanpham` (`IDSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `donhang`
  ADD CONSTRAINT `khachhang` FOREIGN KEY (`ID_KhachHang`) REFERENCES `khachhang` (`ID_KhachHang`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `donhangchitiet`
  ADD CONSTRAINT `donhang` FOREIGN KEY (`ID_DonHang`) REFERENCES `donhang` (`ID_DonHang`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `sanpham`
  ADD CONSTRAINT `danhmuc` FOREIGN KEY (`IDDanhMuc`) REFERENCES `danhmuc` (`IDDanhMuc`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `thuonghieu` FOREIGN KEY (`IDThuongHieu`) REFERENCES `thuonghieu` (`IDThuongHieu`) ON DELETE CASCADE ON UPDATE CASCADE;
