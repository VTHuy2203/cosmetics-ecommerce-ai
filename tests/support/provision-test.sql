-- Run ONLY against a separately initialized MySQL instance on loopback:13316.
-- Synthetic credentials; this user has no privileges outside the test database.
CREATE DATABASE IF NOT EXISTS moshop_test_standardization CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'moshop_test'@'127.0.0.1' IDENTIFIED BY 'isolated-test-only-not-a-production-secret';
GRANT ALL PRIVILEGES ON moshop_test_standardization.* TO 'moshop_test'@'127.0.0.1';
