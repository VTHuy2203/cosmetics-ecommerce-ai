CREATE TABLE chat_message_labels (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id INT NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    label ENUM('not_potential','needs_followup','potential') NOT NULL,
    notes TEXT NULL,
    labeled_by INT NULL,
    labeled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_chat_message_label (message_id),
    KEY idx_chat_label_user (user_id),
    KEY idx_chat_label_session (session_id),
    KEY idx_chat_label_value (label),
    CONSTRAINT fk_chat_label_message FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_label_actor FOREIGN KEY (labeled_by) REFERENCES khachhang(ID_KhachHang) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
