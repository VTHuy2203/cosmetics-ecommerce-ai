-- Last real customer activity per scored subject, used by the analysis date filter.
-- calculated_at only says when the scorer ran, which is the same moment for every row.

ALTER TABLE customer_scores_current
    ADD COLUMN IF NOT EXISTS last_activity_at DATETIME NULL AFTER reason_codes;

CREATE INDEX idx_customer_score_activity ON customer_scores_current (last_activity_at);
