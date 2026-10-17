-- Retire the pre-scoring lead tracking tables.
-- Customer scoring (customer_scores_current, customer_score_snapshots, customer_analysis,
-- user_events) replaced them; no application code reads or writes these tables anymore.
-- Take a database backup first: the remaining rows (October 2025 test data) are not migrated.

DROP TABLE IF EXISTS lead_events;
DROP TABLE IF EXISTS lead_scores;
DROP TABLE IF EXISTS leads;
DROP TABLE IF EXISTS customer_needs;
DROP TABLE IF EXISTS user_intents;
DROP TABLE IF EXISTS user_actions;
