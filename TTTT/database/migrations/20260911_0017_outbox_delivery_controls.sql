ALTER TABLE outbox_events
    ADD COLUMN IF NOT EXISTS event_key VARCHAR(190) NULL AFTER aggregate_id,
    ADD COLUMN IF NOT EXISTS max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 8 AFTER attempts,
    ADD COLUMN IF NOT EXISTS last_error VARCHAR(500) NULL AFTER available_at,
    ADD COLUMN IF NOT EXISTS locked_at DATETIME NULL AFTER last_error,
    ADD COLUMN IF NOT EXISTS worker_id VARCHAR(100) NULL AFTER locked_at,
    ADD COLUMN IF NOT EXISTS dead_at DATETIME NULL AFTER worker_id;

CREATE UNIQUE INDEX uq_outbox_event_key ON outbox_events(event_key);
CREATE INDEX idx_outbox_claim ON outbox_events(processed_at, dead_at, available_at, locked_at);
