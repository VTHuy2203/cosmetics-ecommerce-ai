CREATE TABLE IF NOT EXISTS admin_account_activity (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_activity_admin_created (admin_id, created_at),
    KEY idx_admin_activity_action_created (action, created_at),
    CONSTRAINT fk_admin_activity_user
        FOREIGN KEY (admin_id) REFERENCES khachhang(ID_KhachHang) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
