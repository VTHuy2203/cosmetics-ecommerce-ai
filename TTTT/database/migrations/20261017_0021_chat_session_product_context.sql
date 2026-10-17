-- Products the assistant suggested last in a chat session (comma-separated IDs, max 5).
-- Lets "so sánh 2 sản phẩm này" / "có" after a suggestion compare the right products.

ALTER TABLE chat_sessions
    ADD COLUMN IF NOT EXISTS context_product_ids VARCHAR(64) NULL AFTER title;
