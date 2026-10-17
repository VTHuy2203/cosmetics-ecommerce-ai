# Mơ Shop production deployment

## 1. Accounts and infrastructure

Production requires:

- A Cloudflare account with the production domain, Zero Trust Tunnel and two Turnstile widgets (staging and production).
- A SePay account connected to the receiving bank account.
- A managed MySQL 8.4-compatible database with automated backups and point-in-time recovery.
- An SMTP provider. Rotate the Gmail App Password that previously existed in Git before any release.
- A container registry and a Linux host. The AI profile additionally needs an Ollama host; a GPU host is strongly recommended.

Never put tokens in commits, screenshots, tickets or chat. Use the host/deployment secret store. Because a mail credential previously existed in source, rotate it and purge it from Git history before publishing the repository.

## 2. Local development

`compose.yaml` is development-only because it imports the historical `webmipham1.sql` dump, which contains demo credentials and personal data.

1. Copy `.env.example.txt` to `.env` and set strong, different MySQL passwords.
2. Run `docker compose config`.
3. Run `docker compose up --build`.
4. Open `http://127.0.0.1:8088/TTTT/WebBanHangMyPham/src/View/index.php`.
5. Check `http://127.0.0.1:8088/TTTT/health.php`.

Do not import `webmipham1.sql` into production. Start from a reviewed/sanitized schema or a controlled production backup, then run `php TTTT/bin/migrate.php`. Resolve duplicate usernames/emails before the unique-index migration.

## 3. Build and release

Build once in CI, scan the image, then publish an immutable tag:

```sh
docker build -t registry.example.com/moshop:2026.08.17-1 .
docker push registry.example.com/moshop:2026.08.17-1
```

On the production host, copy `production.env.example.txt` to `.env`, fill it from the secret store and set `APP_IMAGE` to that immutable tag. Then:

```sh
docker compose -f compose.production.yaml config
docker compose -f compose.production.yaml up -d
docker compose -f compose.production.yaml exec app php /var/www/html/TTTT/bin/preflight.php
```

The container entrypoint applies versioned migrations before Apache starts. The scheduler releases stock for expired QR intents and dispatches the transactional notification outbox every minute; it must have only one replica. Provision the first administrator with the one-time `ADMIN_BOOTSTRAP_*` environment values and `php TTTT/bin/create-admin.php`, then remove those values immediately.

Run `php TTTT/bin/hash-legacy-passwords.php` first as a dry run and then with `--apply` if a legacy database is imported. Demo passwords are weak even after hashing; force affected users to reset them before launch.

## 4. Cloudflare

1. In Zero Trust, create a remotely managed Tunnel.
2. Create the public hostname (for example `shop.example.com`) with service `http://app:80`.
3. Put the generated token in `CLOUDFLARE_TUNNEL_TOKEN`; never expose origin ports publicly.
4. Set SSL/TLS to Full (strict) and enable Always Use HTTPS.
5. Create rate-limit rules for login, registration, OTP, chat and the SePay webhook.

Bypass cache for:

- `/TTTT/AdminManage/*`
- `/TTTT/WebBanHangMyPham/src/View/*`
- `/TTTT/WebBanHangMyPham/src/Processing/*`
- `/TTTT/WebBanHangMyPham/src/Webhooks/*`
- Any request carrying the `MOSHOPSESSID` cookie

Cache only versioned CSS/JavaScript and public images. Turnstile server verification fails closed in production, so configure both Turnstile keys before switching traffic.

## 5. SePay auto-payment

### Payment Gateway for order checkout (recommended)

1. Create a real public HTTPS hostname such as `staging.example.com`, then set:

   ```dotenv
   APP_URL=https://staging.example.com/TTTT
   SEPAY_PG_ENABLED=true
   SEPAY_PG_ENV=sandbox
   SEPAY_PG_MERCHANT_ID=...
   SEPAY_PG_SECRET_KEY=...
   ```

2. Configure this IPN in the SePay Sandbox merchant page:

   ```text
   https://staging.example.com/TTTT/WebBanHangMyPham/src/Webhooks/SePayGateway.php
   ```

3. Run migrations and `php TTTT/bin/preflight.php`, then use SePay's **Send test** action. The endpoint only accepts `POST`, authenticates `X-Secret-Key`, validates invoice/currency/amount/status and is idempotent.
4. Success, error and cancel browser callbacks only display the server-side order state. They never mark an order paid; only a valid IPN does that.
5. Switch to `SEPAY_PG_ENV=production` only after rotating to Production credentials and passing all Sandbox cases.

Do not put a Cloudflare Access login policy in front of the IPN path because SePay must call it from the public Internet. Protect it with the SePay secret, a strict request method/body limit, WAF and a conservative rate limit. Never commit credentials to Git.

### Bank-balance webhook for wallet deposits/direct transfer

1. Set `VIETQR_BANK_BIN`, `VIETQR_ACCOUNT_NO`, `VIETQR_ACCOUNT_NAME`, `SEPAY_ACCOUNT_NUMBER` and `SEPAY_PAYMENT_PREFIX`.
2. Configure the identical payment-code prefix in SePay.
3. Create an incoming-only JSON webhook at:
   `https://shop.example.com/TTTT/WebBanHangMyPham/src/Webhooks/SePay.php`
4. Select HMAC-SHA256 authentication and store the secret as `SEPAY_WEBHOOK_SECRET` (32+ random characters).
5. Keep server time synchronized with NTP because signatures reject timestamps outside five minutes.

Use SePay Test mode to cover wallet top-up/direct QR: exact payment, wrong amount, unknown code, duplicate transaction, late payment, stale timestamp and invalid signature. Wallet top-ups use unique `${SEPAY_PAYMENT_PREFIX}N...` references so SePay extracts them with the same configured prefix; they are credited atomically by this separate webhook. Payment Gateway orders use `SePayGateway.php` and cannot be matched by this direct-transfer webhook, preventing double processing. Valid but unmatched transfers are retained in `payment_transactions` for manual reconciliation and never auto-credit an order or wallet.

Add a 15–30 minute reconciliation job against the SePay transaction API before high-volume launch; webhooks can still be delayed or exhausted after repeated delivery failures.

## 6. AI service

AI is off by default. The browser only calls the PHP backend; FastAPI must remain on the private Docker network or a private GPU network.

```sh
docker compose --profile ai up -d ollama ai
docker compose --profile ai exec ollama ollama pull llama3.1:8b
docker compose --profile ai-tools run --rm ai-ingest
```

Set a random `AI_SERVICE_TOKEN` of at least 32 characters in both PHP and AI environments, set `AI_SERVICE_URL=http://ai:8010`, then enable `AI_CHAT_ENABLED=true`. Do not route port 8010 through Cloudflare. Re-ingest products after catalog changes and evaluate retrieval quality on a fixed Vietnamese cosmetics question set before changing embeddings or prompts.

## 7. Backups, monitoring and release gate

- Back up managed MySQL and every persistent upload volume; test restore before launch.
- Monitor `/TTTT/health.php`, HTTP 5xx, failed webhooks, unmatched payments and pending `outbox_events`.
- Set the same random `REALTIME_TICKET_SECRET` (at least 32 characters) for PHP and the WebSocket service. Realtime clients receive short-lived signed tickets from PHP; never put tickets in URLs or logs.
- Run Composer audit in all three projects, PHP lint, Python compile and `php tests/security_smoke.php`.
- Test concurrent checkout, cancel/refund, stock expiry, role approval and SePay replay on staging.
- Keep identity documents and return evidence private; migrate persistent uploads to encrypted object storage such as R2 when moving beyond a single application host.
- The historical dump and legacy files under `ReturnRefundAttachments` contain demo/personal data. They are excluded from the production image, but must be removed from Git history before the repository is shared publicly.
- Confirm privacy policy, refund-to-wallet terms, seller/company identity, tax/invoice and Vietnamese e-commerce compliance with counsel before accepting real orders.
