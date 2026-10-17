INSERT INTO danhmuc(IDDanhMuc,TenDanhMuc,AnhDanhMuc)
VALUES(9901,'F10 test','') ON DUPLICATE KEY UPDATE TenDanhMuc=VALUES(TenDanhMuc);
INSERT INTO thuonghieu(IDThuongHieu,TenThuongHieu,AnhThuongHieu)
VALUES(9901,'F10 test','') ON DUPLICATE KEY UPDATE TenThuongHieu=VALUES(TenThuongHieu);
INSERT INTO khachhang(ID_KhachHang,TenDangNhap,MatKhau,HoVaTen,Email,SDT,DiaChi,Role)
VALUES(9901,'f10-admin','$2y$10$synthetic','F10 Admin','f10@example.invalid','0000000000','Test','admin')
ON DUPLICATE KEY UPDATE Role='admin';
INSERT INTO sanpham(IDSanPham,TenSanPham,IDDanhMuc,IDThuongHieu,SoLuong,TrongLuongGram,GiaTien,MoTaChiTiet,AnhSanPham,NoiBat)
VALUES
 (37,'Son dưỡng Dior Addict Lip Glow SELENA STORE',9901,9901,3,100,123456,'Son dưỡng môi','',0),
 (85,'Sữa rửa mặt Cerave cho da dầu mụn',9901,9901,4,250,234567,'Sữa rửa mặt dịu nhẹ','',0)
ON DUPLICATE KEY UPDATE TenSanPham=VALUES(TenSanPham),SoLuong=VALUES(SoLuong),TrongLuongGram=VALUES(TrongLuongGram),GiaTien=VALUES(GiaTien);
DELETE FROM shipping_config_audit WHERE version_id=9901;
DELETE FROM shipping_rate_bands WHERE region_id=9901;
DELETE FROM shipping_regions WHERE version_id=9901;
DELETE FROM shipping_config_versions WHERE id=9901;
INSERT INTO shipping_config_versions(id,name,origin_label,free_shipping_min,status,created_by,activated_by,activated_at)
VALUES(9901,'F10 active test','Kho kiểm thử',900000,'active',9901,9901,NOW());
INSERT INTO shipping_regions(id,version_id,code,name,sort_order)
VALUES(9901,9901,'F10_LOCAL','Nội vùng kiểm thử',1);
INSERT INTO shipping_rate_bands(region_id,min_weight_grams,max_weight_grams,fee)
VALUES(9901,1,NULL,18000);
