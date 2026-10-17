-- Explainable customer scoring v1.
-- Lead intent, customer value and spam risk are deliberately independent.

ALTER TABLE khachhang
    MODIFY COLUMN customer_label ENUM('normal','follow-up','potential','spam') NOT NULL DEFAULT 'normal',
    ADD COLUMN IF NOT EXISTS lead_score TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER customer_label,
    ADD COLUMN IF NOT EXISTS spam_risk_score TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER lead_score,
    ADD COLUMN IF NOT EXISTS customer_value_score TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER spam_risk_score,
    ADD COLUMN IF NOT EXISTS customer_value_tier ENUM('new','regular','loyal','vip') NOT NULL DEFAULT 'new' AFTER customer_value_score,
    ADD COLUMN IF NOT EXISTS scoring_version VARCHAR(32) NULL AFTER label_updated_at;

ALTER TABLE customer_analysis
    MODIFY COLUMN label ENUM('normal','follow-up','potential','spam') NOT NULL DEFAULT 'normal',
    ADD COLUMN IF NOT EXISTS lead_score TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER label,
    ADD COLUMN IF NOT EXISTS spam_risk_score TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER lead_score,
    ADD COLUMN IF NOT EXISTS score_reasons JSON NULL AFTER spam_risk_score,
    ADD COLUMN IF NOT EXISTS scoring_version VARCHAR(32) NULL AFTER score_reasons;

ALTER TABLE lead_scores
    MODIFY COLUMN label ENUM('normal','follow-up','neutral','potential','spam') NOT NULL DEFAULT 'normal';

CREATE TABLE IF NOT EXISTS customer_scores_current (
    subject_key VARCHAR(190) NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(128) NULL,
    intent_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    spam_risk_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    value_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    segment ENUM('normal','follow-up','potential','spam') NOT NULL DEFAULT 'normal',
    value_tier ENUM('new','regular','loyal','vip') NOT NULL DEFAULT 'new',
    reason_codes JSON NOT NULL,
    scoring_version VARCHAR(32) NOT NULL,
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    manual_segment ENUM('normal','follow-up','potential','spam') NULL,
    manual_reason VARCHAR(500) NULL,
    manual_by INT NULL,
    manual_at TIMESTAMP NULL,
    PRIMARY KEY (subject_key),
    KEY idx_customer_score_user (user_id),
    KEY idx_customer_score_session (session_id),
    KEY idx_customer_score_segment (segment, intent_score),
    KEY idx_customer_score_calculated (calculated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_score_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_key VARCHAR(190) NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(128) NULL,
    intent_score TINYINT UNSIGNED NOT NULL,
    spam_risk_score TINYINT UNSIGNED NOT NULL,
    value_score TINYINT UNSIGNED NOT NULL,
    segment ENUM('normal','follow-up','potential','spam') NOT NULL,
    value_tier ENUM('new','regular','loyal','vip') NOT NULL,
    reason_codes JSON NOT NULL,
    scoring_version VARCHAR(32) NOT NULL,
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_score_snapshot_subject (subject_key, calculated_at),
    KEY idx_score_snapshot_user (user_id, calculated_at),
    KEY idx_score_snapshot_segment (segment, calculated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preserve weekly value history without allowing an inactive customer to
-- become spam merely because they did not purchase in the current week.
CREATE TABLE IF NOT EXISTS customer_weekly_stats (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    total_orders INT NOT NULL DEFAULT 0,
    total_amount BIGINT NOT NULL DEFAULT 0,
    has_booking TINYINT(1) NOT NULL DEFAULT 0,
    has_chat TINYINT(1) NOT NULL DEFAULT 0,
    customer_label ENUM('normal','follow-up','potential','spam') NOT NULL DEFAULT 'normal',
    previous_label ENUM('normal','follow-up','potential','spam') NULL,
    discount_percent INT NOT NULL DEFAULT 0,
    evaluated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customer_week (user_id, week_start),
    KEY idx_weekly_user (user_id),
    KEY idx_weekly_period (week_start),
    KEY idx_weekly_label (customer_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
