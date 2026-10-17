-- One guest profile and one analysis row per chat session.
-- guest_customers had no unique key, and UNIQUE(user_id, session_id) on customer_analysis
-- ignores guests because MySQL treats every NULL user_id as distinct. Existing duplicates
-- are merged first: the guest row that holds a phone number wins, counters are summed.

CREATE TEMPORARY TABLE guest_keep AS
SELECT g.session_id,
       COALESCE(
           (SELECT g2.id FROM guest_customers g2
            WHERE g2.session_id = g.session_id AND COALESCE(g2.phone, '') <> ''
            ORDER BY g2.updated_at DESC, g2.id DESC LIMIT 1),
           MAX(g.id)
       ) AS keep_id,
       MIN(g.created_at) AS first_seen,
       MAX(g.updated_at) AS last_seen
FROM guest_customers g
GROUP BY g.session_id
HAVING COUNT(*) > 1;

UPDATE guest_customers g
JOIN guest_keep k ON g.id = k.keep_id
SET g.created_at = k.first_seen, g.updated_at = k.last_seen;

DELETE g FROM guest_customers g
JOIN guest_keep k ON g.session_id = k.session_id AND g.id <> k.keep_id;

DROP TEMPORARY TABLE guest_keep;

ALTER TABLE guest_customers ADD UNIQUE KEY uq_guest_session (session_id);

ALTER TABLE customer_analysis
    ADD COLUMN IF NOT EXISTS subject_user_id INT GENERATED ALWAYS AS (COALESCE(user_id, 0)) STORED AFTER user_id;

CREATE TEMPORARY TABLE analysis_keep AS
SELECT a.subject_user_id,
       a.session_id,
       MAX(a.id) AS keep_id,
       MAX(a.has_appointment) AS has_appointment,
       SUM(a.message_count) AS message_count,
       MAX(a.updated_at) AS last_seen,
       (SELECT a2.product_interest FROM customer_analysis a2
        WHERE a2.subject_user_id = a.subject_user_id AND a2.session_id = a.session_id
          AND COALESCE(a2.product_interest, '') <> ''
        ORDER BY a2.updated_at DESC, a2.id DESC LIMIT 1) AS product_interest,
       (SELECT a3.last_need FROM customer_analysis a3
        WHERE a3.subject_user_id = a.subject_user_id AND a3.session_id = a.session_id
          AND COALESCE(a3.last_need, '') <> ''
        ORDER BY a3.updated_at DESC, a3.id DESC LIMIT 1) AS last_need
FROM customer_analysis a
GROUP BY a.subject_user_id, a.session_id
HAVING COUNT(*) > 1;

UPDATE customer_analysis a
JOIN analysis_keep k ON a.id = k.keep_id
SET a.has_appointment = k.has_appointment,
    a.message_count = k.message_count,
    a.product_interest = k.product_interest,
    a.last_need = k.last_need,
    a.updated_at = k.last_seen;

DELETE a FROM customer_analysis a
JOIN analysis_keep k ON a.subject_user_id = k.subject_user_id AND a.session_id = k.session_id AND a.id <> k.keep_id;

DROP TEMPORARY TABLE analysis_keep;

ALTER TABLE customer_analysis
    DROP INDEX unique_user_session,
    ADD UNIQUE KEY uq_analysis_subject_session (subject_user_id, session_id);
