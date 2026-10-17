CREATE TABLE IF NOT EXISTS chat_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    user_id INT NULL,
    title VARCHAR(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_message_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_chat_sessions_session (session_id),
    KEY idx_chat_sessions_user_updated (user_id, updated_at),
    KEY idx_chat_sessions_last_message (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO chat_sessions (session_id, user_id, created_at, updated_at, last_message_at)
SELECT
    cm.session_id,
    MAX(cm.user_id),
    MIN(cm.created_at),
    MAX(cm.created_at),
    MAX(cm.created_at)
FROM chat_messages cm
WHERE cm.session_id <> ''
GROUP BY cm.session_id
ON DUPLICATE KEY UPDATE
    user_id = COALESCE(chat_sessions.user_id, VALUES(user_id)),
    last_message_at = GREATEST(
        COALESCE(chat_sessions.last_message_at, '1970-01-01 00:00:00'),
        VALUES(last_message_at)
    );
