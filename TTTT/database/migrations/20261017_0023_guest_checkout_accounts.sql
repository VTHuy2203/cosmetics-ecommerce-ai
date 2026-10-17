-- Guest checkout: an order still belongs to a khachhang row (donhang.ID_KhachHang is NOT NULL and
-- many staff pages join it), so each guest phone number gets a passwordless account flagged here.
-- Login refuses these accounts; registration cannot claim the reserved "guest_" usernames.
ALTER TABLE khachhang
    ADD COLUMN IF NOT EXISTS is_guest TINYINT(1) NOT NULL DEFAULT 0 AFTER Role;
