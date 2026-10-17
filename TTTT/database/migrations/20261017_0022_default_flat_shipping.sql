-- Default shipping policy: 25.000đ per order nationwide, free from 500.000đ of goods.
-- A single 1g..unlimited band makes the fee independent of product weight.
-- Seeded only when no configuration is active, so an admin-activated version is never replaced.

INSERT INTO shipping_config_versions (name, origin_label, free_shipping_min, status, created_by, activated_by, activated_at)
SELECT 'Mặc định: 25.000đ, miễn phí từ 500.000đ', 'Kho Mơ Shop', 500000, 'active', k.ID_KhachHang, k.ID_KhachHang, NOW()
FROM khachhang k
WHERE k.Role = 'admin'
  AND NOT EXISTS (SELECT 1 FROM shipping_config_versions WHERE status = 'active')
ORDER BY k.ID_KhachHang
LIMIT 1;

INSERT INTO shipping_regions (version_id, code, name, sort_order)
SELECT v.id, 'TOANQUOC', 'Toàn quốc', 0
FROM shipping_config_versions v
WHERE v.status = 'active' AND v.free_shipping_min = 500000 AND v.origin_label = 'Kho Mơ Shop'
  AND NOT EXISTS (SELECT 1 FROM shipping_regions r WHERE r.version_id = v.id);

INSERT INTO shipping_rate_bands (region_id, min_weight_grams, max_weight_grams, fee)
SELECT r.id, 1, NULL, 25000
FROM shipping_regions r
JOIN shipping_config_versions v ON v.id = r.version_id
WHERE v.status = 'active' AND r.code = 'TOANQUOC'
  AND NOT EXISTS (SELECT 1 FROM shipping_rate_bands b WHERE b.region_id = r.id);

INSERT INTO shipping_config_audit (version_id, actor_id, action, details)
SELECT v.id, v.created_by, 'activated', JSON_OBJECT('source', 'migration 0022 default flat shipping')
FROM shipping_config_versions v
WHERE v.status = 'active' AND v.origin_label = 'Kho Mơ Shop'
  AND NOT EXISTS (SELECT 1 FROM shipping_config_audit a WHERE a.version_id = v.id);
