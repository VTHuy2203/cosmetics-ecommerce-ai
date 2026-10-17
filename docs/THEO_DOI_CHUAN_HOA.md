# Sổ theo dõi chuẩn hóa Mơ Shop

## Baseline 10/09/2026

- Branch `main`, HEAD `b1c68a265594529ff5b1007575effb2eb070d6ef`.
- Working tree có nhiều thay đổi trước phiên sửa. Danh sách trạng thái, numstat và SHA256 của 292 file nguồn được lưu tại `.runtime/standardization-baseline/` (local, không commit dữ liệu riêng tư).
- PHP CLI 8.4.1; MySQL thử nghiệm 8.0.30 từ binary đã có. Docker CLI có sẵn nhưng Engine không chạy, xác nhận lại ngoài sandbox vẫn thiếu named pipe. Chưa build/chạy container.
- Đã đọc lại toàn bộ tài liệu yêu cầu và kiểm tra không tìm thấy AGENTS.md trong cây dự án bằng rg.
- Baseline bốn test pure/helper security, SePay gateway, voucher và customer scoring đều PASS. Chưa chạy legacy integration/render vì dùng database mặc định; HTTP integration cũ dùng cả localhost và session path Laragon.
- Không chạy migration/seed trên database thực; không reset hoặc commit thay đổi có sẵn.

## Bảng tiến độ

| ID | Trạng thái | Bằng chứng/phụ thuộc | File thay đổi | Test bảo vệ | Kết quả gần nhất | Hồi quy |
|---|---|---|---|---|---|---|
| F00 | DONE | MySQL process riêng, tài khoản giới hạn; Docker chưa kiểm chứng | tests/support, run-isolated, environment_guard_test; sổ này | environment guard + baseline smoke | PASS 10/09/2026 | R0/R1 |
| F01 | DONE | Tái hiện trước sửa; endpoint + browser desktop/mobile đạt sau sửa | 4 file module bình luận, tests comments | comments_integration; comments_browser | PASS 10/09/2026 | R0/R1/R6 + 4 smoke |
| F02 | DONE | Ticket HMAC, quyền server-side, lease 60 giây; socket thật đã kiểm chứng | realtime config/endpoint/server/client/layout; local launcher; tests realtime | realtime_legacy_repro; realtime_socket_browser; comments regression | PASS 11/09/2026 | F01/R1/R5 |
| F03 | DONE | Hoàn tiền hàng thực trả sau voucher; partial/idempotency/concurrency; ship = 0 trong refund | migration 0014, checkout allocation, returns helper, request/admin/customer UI | refund integration + R0/R2/R3/R4 hồi quy | PASS 11/09/2026 | F04/F06/F08/R2/R3/R4 |
| F04 | DONE | Dump xác nhận cascade danh mục/thương hiệu; migration đổi sang RESTRICT | catalog helper, 3 endpoint + 3 trang admin, migration 0011, tests catalog | catalog_delete_integration; catalog_delete_browser | PASS 11/09/2026 | R2/R4/R6 + F01/F02 |
| F05 | READY_FOR_REVIEW | Current index/public HTTP đã khóa; history và image runtime chưa xử lý | git/docker ignore, Apache boundaries, private download test, CI boundary test | repository_boundary; private_file_access; Apache HTTP probes | PASS phần code 11/09/2026 | R1 + F01/F02/F04; R7 deferred |
| F06 | DONE | Rate limit/proxy/role freshness; logout admin và xóa lịch hẹn đã khép kín POST/CSRF/role DB/state | bootstrap/security, endpoint + caller F06 | integration và browser auth/realtime/comments | PASS toàn tuyến 11/09/2026 | F01/F02/F04/F05/R1/R6 |
| F07 | DONE | Version phí vùng/gram do admin cấu hình; không seed giả; checkout snapshot | migration 0015, ShippingService, admin UI, cart/COD/QR/balance | shipping integration + fresh DB | PASS 11/09/2026; chờ admin nhập bảng vận hành | R2/R3/R5/R6 |
| F08 | DONE | Core schema; không DDL trong HTTP; DB env tập trung; fresh/upgrade/restore | migration 0000/0016, migrator, runtime DB callers | fresh + legacy adoption + backup/restore | PASS 11/09/2026 | R4 + F03/F04/F06/F07 |
| F09 | DEFERRED | Chủ dự án hoãn Docker runtime; source path đã giảm phụ thuộc máy tác giả nhưng chưa build/start image | Docker/Compose/env/AI paths | Compose config/CI build definition mới là kiểm tra tĩnh | Chưa có R7 runtime | R7 |
| F10 | IN_PROGRESS | Live giá/tồn kho/phí ship; xóa vector stale; catalog outbox đang triển khai | ML live catalog/ingest/sync worker, catalog event, migration 0017, AI eval fixture | 7 AI data-contract tests + Python syntax | PASS phần thuần; Chroma local lỗi tương thích NumPy, chưa đo quality/latency | R5 |
| F11 | IN_PROGRESS | Webhook/refund/voucher có lock/idempotency; delivery control 0017 mới qua fresh migration | payment/checkout/returns/outbox + migration 0017 | smoke tiền và các integration riêng | Chưa có checkout idempotency, full concurrency/fault/reconciliation suite; shared test DB vừa phát hiện nhiễm fixture | R2/R3/R5 |
| F12 | TODO | Refactor có kiểm soát | — | — | — | Theo dependency |
| F13 | TODO | Catalog cần chốt nghiệp vụ | — | — | — | R2/R4/R5 |
| F14 | TODO | UX/SEO/browser | — | — | — | R6 |
| F15 | IN_PROGRESS | Có CI/lint/audit/smoke/health và tài liệu local/deploy, chưa đủ release gate/observability/runbook | CI, health, docs, test harness | PHP/Python syntax, 6 smoke/boundary tests | PASS phần tĩnh/thuần; integration suite chưa độc lập, Docker runtime hoãn | R0–R7 |

## Dependency map ban đầu

- Bình luận: customer session → POST/CSRF → DB purchase/return eligibility → lưu binhluan → response HTML → product page/admin list. Xóa bình luận: admin session/quyền → POST/CSRF → delete → danh sách admin.
- Auth/security dùng chung với checkout/voucher/chat: mọi sửa helper phải chạy lại bốn baseline smoke và tests chức năng đã hoàn thành chịu ảnh hưởng.
- F03 phụ thuộc chính sách F07 và snapshot DB F08; không tự quyết định tiền trong bản sửa bảo mật.
- Docker/paths/env tác động web, CLI, scheduler, AI/ingest/realtime; build không thay runtime test.

## F00 — Thiết lập thử nghiệm

- Instance MySQL mới có datadir `.runtime/standardization-mysql`, bind `127.0.0.1:13316`, tắt MySQL X; khác hẳn dịch vụ người dùng.
- Account `moshop_test` chỉ được cấp quyền trên `moshop_test_standardization`; credential giả chỉ dùng fixture này.
- `tests/run-isolated.ps1` chỉ cho chạy danh sách test đã review. `tests/support/environment.php` từ chối sai APP_ENV/host/port/user/database trước khi load bootstrap; không dùng cấu hình DB Laragon.
- Không tuyên bố các legacy integration test an toàn: chưa đưa vào allowlist, chưa chạy. Chuyển từng test khi đến đúng hạng mục.
- Chưa có thay đổi production code/schema trong F00.
- Kết quả: `php -l` hai file guard/test PASS; chạy test không có cấu hình bị từ chối trước DB (expected negative); `powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-isolated.ps1 -TestFile environment_guard_test.php` PASS kiểm tra APP_ENV/DB/port/user/host sai, kết nối bootstrap đúng database và không có quyền đọc `mysql.user`.
- CLI mặc định chưa bật mysqli/pdo_mysql. Runner bật module thiếu bằng tham số tiến trình; không thay php.ini hệ thống. ExecutionPolicy của máy chặn `.ps1` trực tiếp, vì vậy lệnh runner ghi rõ policy chỉ cho tiến trình kiểm thử.
- Database giả cấp từ `tests/support/provision-test.sql` qua MySQL CLI `--no-defaults --host=127.0.0.1 --port=13316 --user=root` trên instance riêng vừa khởi tạo; không import dump người dùng.
- Khôi phục F00: chỉ loại bỏ file test mới khi cần; không có migration runtime cần đảo. Datadir test đang giữ lại để kiểm thử tiếp, không xóa tự động.

## F01 — Kế hoạch trước khi sửa

- Phạm vi: POST thêm/xóa bình luận; render bình luận ở product/admin; AJAX token và mã lỗi. Giữ quy tắc hiện tại về mua/nhận hàng và đã hoàn; không thay chính sách đánh giá trong bản bảo mật.
- Rủi ro bổ sung xác nhận: `Delete_Comment.php` chưa kiểm tra admin/CSRF, ghép ID vào SQL. Sửa trong cùng F01 vì là mutation của cùng module bình luận.
- Tests cần có: nội dung có dấu nháy/tiếng Việt/HTML, product ID không hợp lệ, chưa đăng nhập/chưa mua/đã hoàn, CSRF, xóa không quyền, xóa ID injection và xóa hợp lệ; output cũ/mới phải escape.
- Các test cũ không đụng helper global nên chạy lại bốn baseline smoke sau bản sửa; browser/admin/product render sẽ kiểm chứng riêng, chưa mặc định đạt R6.

### Kết quả F01

- Test trước sửa thất bại ở method/auth/CSRF, validation, nội dung có dấu nháy, HTML và quyền xóa. Sau sửa, cùng test PASS trên MySQL cô lập. Prepared statements thay toàn bộ truy vấn trong hai mutation endpoint.
- Escape nội dung/tên ở response thêm mới, product page và admin list; admin list escape cả tên sản phẩm. AJAX thêm/xóa gửi CSRF và xử lý HTTP lỗi. Endpoint xóa kiểm tra role cả session lẫn DB hiện tại, giữ quyền admin/manager tương ứng màn hình hiện có.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-isolated.ps1 -TestFile comments_integration.php`: PASS.
- `php -l` bốn file production thay đổi: PASS. Bốn baseline smoke: PASS. `git diff --check -- <4 file>`: PASS (chỉ cảnh báo chính sách LF/CRLF có sẵn).
- `node tests/comments_browser.mjs`: PASS cả 1440px và 390px trên Chrome headless thật, HTTP server riêng 18089, DB giả; thực hiện thêm bình luận có dấu nháy/HTML, reload xác nhận lưu, mở admin kiểm tra HTML không chạy, click xóa qua AJAX/CSRF và kiểm tra biến mất. Cả hai trang không xuất PHP warning/fatal.
- Browser fixtures ở `tests/support/prepare_comments_browser.php`; khởi động bằng `tests/start-comments-http.ps1`. Đây là schema fixture tối thiểu phục vụ F01, KHÔNG là schema cài mới production và KHÔNG chứng minh F08 đạt.
- Không sửa helper auth dùng chung, schema production, chính sách mua/hoàn/đánh giá hoặc model. XSS/SQL ở các vùng khác không thuộc bình luận vẫn cần rà F05/F06; không tuyên bố toàn website đã an toàn.
- Khôi phục: đảo riêng các thay đổi của F01 sau khi đối chiếu baseline và diff; không checkout/reset toàn file có thay đổi của người dùng. Test dữ liệu chỉ ở instance thử nghiệm.

## Quyết định chủ dự án trong phiên

- F03: hoàn tiền sản phẩm sau khi áp voucher, chỉ hoàn phần tiền hàng khách thực trả; admin duyệt với lý do, shop chịu chi phí ship khi lỗi sản phẩm. Không cộng/trừ phí ship vào công thức hoàn tiền hàng. Giữ phương thức hoàn vào số dư hiện có khi chưa có yêu cầu đổi.
- F07: tính ship theo khu vực/trọng lượng. Chủ dự án sẽ cung cấp bảng phí; đã hỏi kho gửi, phân vùng, bậc trọng lượng và điều kiện miễn ship. Không tự tạo bảng giá hoặc gọi công thức 25.000đ cũ là chính sách mới.
- Bổ sung F07: chủ dự án giao tạo phần admin cung cấp/điều chỉnh phí ship. Thiết kế cấu hình nháp/kích hoạt, validation các vùng/bậc, quyền admin, audit và tính nhất quán checkout. Không bịa mức phí lúc seed. Khối lượng sản phẩm và định danh khu vực là phụ thuộc phải kiểm tra.

## F02 — Thiết kế trước khi sửa

- PHP cấp ticket HMAC thời hạn tối đa 60 giây sau kiểm tra session/CSRF và role hiện tại trong DB. Ticket không chứa PHP session ID; chỉ gửi trong frame subscribe, không đưa vào URL/log.
- WebSocket xác minh chữ ký/audience/expiry/origin, chỉ lấy danh tính/kênh từ ticket đã xác minh. Client tự khai role/user_id/channel không được dùng để cấp quyền.
- Client gia hạn qua PHP mỗi 30 giây. Sau logout/quyền thay đổi, không cấp lease mới; server ngừng gửi và đóng lease hết hạn (tối đa 60 giây). Đây là hợp đồng thu hồi có giới hạn thời gian, không tuyên bố thu hồi tức thời.
- Server đóng kết nối không đăng ký trong 10 giây; từ chối payload quá lớn; không ghi raw auth frame. Kiểm tra lại hạn trước broadcast ngoài timer thu hồi.
- Session admin/customer tách qua context cấp ticket. Không lấy quyền admin từ UI user session. Cần secret chung PHP/WS và origin khớp APP_URL, sẽ ghi trong env examples/runbook; không tự thay credential thật.
- Test trực tiếp handler với connection giả lập + socket thực trên port riêng; hồi quy F01 vì thay layout/JS dùng chung.

### Kết quả F02

- Endpoint PHP cấp ticket sau POST/CSRF và đọc lại role hiện tại từ database. Session customer và admin vẫn tách biệt; guest/customer, shipper, employee, manager và admin được ánh xạ sang kênh tại server.
- WebSocket chỉ nhận danh tính từ ticket HMAC, kiểm tra audience, origin, thời hạn và `jti`; bỏ qua role/channel/user ID do client tự khai, từ chối ticket thiếu/giả/hết hạn/dùng lại và đóng lease hết hạn trước broadcast.
- Client lấy ticket qua same-origin request và gia hạn trước khi hết hạn. Layout customer/admin chỉ truyền endpoint, context và CSRF. `run-local.ps1` tạo secret ngẫu nhiên 32 byte trong `.env` nếu còn thiếu/yếu, không in giá trị; chạy lại không đổi secret hợp lệ.
- `powershell -NoProfile -ExecutionPolicy Bypass -File tests/run-isolated.ps1 -TestFile realtime_legacy_repro.php`: PASS. Ca kiểm tra gồm không ticket, giả mạo claim, replay, sửa ticket, hết hạn, sai origin và broadcast sau expiry.
- `node tests/realtime_socket_browser.mjs`: PASS trên HTTP/WebSocket/Chrome thật ở port cô lập. Đã kiểm tra customer, admin, employee, manager, shipper, kênh bị giới hạn và session sai context bị từ chối.
- Hồi quy F01: `comments_integration.php`, browser 1440px/390px và bốn baseline smoke đều PASS ngày 11/09/2026.
- Giới hạn: quyền bị thu hồi có hiệu lực chậm nhất khi lease 60 giây hết hạn. Ratchet hiện phát cảnh báo deprecated trên PHP 8.4; xử lý compatibility dependency trong F09/F15, không sửa trực tiếp vendor.
- Không thay schema, dữ liệu thật hoặc quyền tài chính. Queue của test nằm trong `.runtime`; không dùng queue production.

## F04 — Cascade delete và bảo toàn lịch sử

- Dump xác nhận `sanpham.IDDanhMuc` và `sanpham.IDThuongHieu` dùng `ON DELETE CASCADE`; `donhangchitiet.ID_SanPham` chưa có foreign key. Vì vậy xóa danh mục/thương hiệu có thể xóa sản phẩm rồi để chi tiết đơn không còn nguồn tên sản phẩm.
- Migration mới `20260911_0011_catalog_history_protection.sql` thay hai cascade bằng RESTRICT và thêm RESTRICT từ chi tiết đơn đến sản phẩm. Migration không xóa hoặc tự backfill dữ liệu; nếu có orphan lịch sử thì cố ý thất bại để yêu cầu đối chiếu backup. Các ALTER cần maintenance window do metadata lock.
- Helper catalog kiểm tra ID, tồn tại và quan hệ sử dụng bằng prepared statement; database constraint là chốt cuối cho concurrent/direct delete. Endpoint chỉ cho POST + CSRF qua kiểm soát admin hiện có, trả redirect 303 với trạng thái cố định.
- Trang danh mục, thương hiệu và sản phẩm hiển thị thông báo rõ khi dữ liệu đang được dùng, không tồn tại, ID sai hoặc xóa thành công. Sản phẩm chưa từng vào đơn và danh mục/thương hiệu không có sản phẩm vẫn có thể xóa.
- `catalog_delete_integration.php`: PASS trên MySQL 8.0.30 cô lập; đã áp migration lên schema có cascade, xác nhận ba DELETE_RULE là RESTRICT, chặn danh mục/thương hiệu đang dùng và sản phẩm có đơn, bảo toàn chi tiết đơn, cho xóa dữ liệu chưa dùng.
- `catalog_delete_browser.mjs`: PASS trên Chrome headless thật; form POST/CSRF, redirect và thông báo lỗi/thành công được kiểm tra qua ba trang admin.
- Trong lần đầu hồi quy, fixture F01 lỗi vì dùng INSERT không ghi tên cột và REPLACE trên row có FK RESTRICT. Đã sửa fixture dùng danh sách cột/upsert; sau đó chạy lại chuỗi F04 → F01 → F02 và toàn bộ đều PASS.
- Hồi quy cuối: comments integration, comments browser 1440/390, realtime handler/socket đủ role, environment guard và bốn baseline smoke đều PASS ngày 11/09/2026.
- Chưa áp migration lên dữ liệu người dùng. Khi release phải chạy truy vấn tìm orphan và backup trước; F08 sẽ kiểm chứng đường nâng cấp/restore toàn bộ migration.

## F05 — Dữ liệu nhạy cảm, upload và ranh giới public

- Kiểm kê xác nhận Git đang theo dõi dump database, CSV catalog và 10 chứng từ hoàn trả. Đã dùng `git rm --cached` cho 13 file, kiểm tra bản làm việc vẫn tồn tại và thêm ignore cụ thể. Đây là staged deletion khỏi revision kế tiếp, không xóa file người dùng và không viết lại lịch sử Git.
- `repository_boundary_test.php` chặn CI nếu dump/CSV/chứng từ/private storage/Chroma hoặc `.env` bị track lại. Quét tên file tracked không thấy private key hoặc giá trị gán trực tiếp cho SMTP/AI/SePay secret theo các pattern độ tin cậy cao; kết quả này không thay thế secret scanner chuyên dụng ở F15.
- `.dockerignore` loại `.env`, runtime, test/docs/scripts, private storage, legacy identity uploads, return attachments và CSV khỏi build context. Chưa build/inspect image vì Docker Engine không hoạt động và chủ dự án hoãn Docker runtime.
- `.htaccess` tại workspace và TTTT chặn directory listing, source/config/database/bin/vendor/storage/WebSocketServer, ML/RAG, legacy private uploads, dump và log. Các thư mục docs/tests/scripts/deploy/logs/shortcuts/venv có deny trực tiếp để chặn virtual host tự sinh của Laragon.
- Kiểm tra Apache thật: `.env`, docs, tests, PHP config, ML JSON, return attachments và các virtual host docs/tests/venv/logs/shortcuts trả 403; ảnh public hợp lệ trả 200.
- Customer return attachment tiếp tục kiểm tra owner từ DB và allowlist trong JSON; Admin private endpoint chặn traversal và MIME lạ. `private_file_access_integration.php` dùng PNG giả, kiểm tra anonymous/owner/other user/admin/path traversal, tự xóa file và schema fixture: PASS.
- Lần hồi quy đầu phát hiện private-file test làm rò schema tối giản sang F01; đã thêm cleanup trong `finally`. Chuỗi F05 → F04 → F01/F02, environment guard và bốn baseline smoke sau sửa đều PASS ngày 11/09/2026.
- Trạng thái `READY_FOR_REVIEW`: revision hiện tại sau khi commit sẽ không còn chứa 13 file riêng tư, nhưng các file vẫn tồn tại trong lịch sử Git cũ. Trước khi đưa repository thành public cần chủ dự án phê duyệt quy trình backup rồi mới rewrite lịch sử/rotate credential nếu cần; chưa thực hiện ở đây. Image runtime cũng chờ F09 theo quyết định hoãn Docker.

## F06 — Auth, rate limit và session dùng chung

- Migration mới `20260911_0012_server_side_rate_limits.sql` tạo bucket giới hạn trong MySQL bằng khóa băm của scope và IP. Login và OTP không còn dựa vào session client; đổi cookie không đặt lại số lần thử. Production fail-closed khi kho rate-limit lỗi, môi trường development/testing mới có fallback session.
- `app_client_ip()` chỉ đọc `CF-Connecting-IP`/`X-Forwarded-For` và forwarded HTTPS khi địa chỉ kết nối nằm trong `TRUSTED_PROXY_CIDRS`. Test xác nhận header giả từ client trực tiếp không đổi IP hoặc trạng thái HTTPS; env/Compose production đã có hợp đồng cấu hình biến này.
- Login customer/admin xóa bucket sau xác thực thành công. OTP chuẩn hóa email, áp giới hạn server-side, dùng prepared statement, chỉ tạo/lưu mã sau khi qua cooldown và không ghi email thô vào log. Hai endpoint development từng trả OTP/link trên HTTP đã bị vô hiệu hóa với 410.
- `Admin_Access_Check.php` đọc lại role bằng `admin_id` và username ở mỗi request bảo vệ. Tài khoản bị xóa, hạ quyền hoặc role trong DB khác session sẽ bị xóa phiên admin và chuyển về login; vẫn giữ phân tách biến phiên admin/customer.
- Đã chuyển các mutation còn hoạt động qua GET sang POST + CSRF: logout customer, cập nhật số lượng cart, bật/tắt sản phẩm nổi bật và đánh dấu hàng hoàn về shop. ID/trạng thái được validate; truy vấn ghi liên quan dùng prepared statement và redirect 303.
- Đã bổ sung CSRF cho gửi chat, đánh giá sản phẩm, liên hệ, admin sửa khách hàng và gắn nhãn chat. Form/JS caller gửi token tương ứng. API nhãn lead bỏ khả năng `?set=...`, chỉ đọc dữ liệu của chính customer hoặc vai trò admin/manager/employee được xác minh lại từ DB.
- Endpoint broadcast HTTP không xác thực, trang admin customer cũ và trang hiện OTP cũ đã bị vô hiệu hóa. WebSocket push nội bộ vẫn ghi queue qua helper server-side; thư mục WebSocket tiếp tục bị Apache chặn 403 theo F05.
- `rate_limit_integration.php`: PASS các ca đổi cookie, spoof proxy, trusted CIDR, reset sau login, expiry và không lưu scope/IP thô. `f06_browser.mjs`: PASS GET không mutation, CSRF sai không đổi dữ liệu, POST/303 hợp lệ, thu hồi role DB, lead ownership và CSRF cho cart/chat/contact/rating/admin update.
- Hồi quy sau F06: environment guard, catalog/private-file/comments/realtime integration, bốn baseline smoke, catalog browser, comments browser 1440/390 và realtime socket đủ role đều PASS ngày 11/09/2026. Browser test F04 từng đua với redirect; đã sửa chờ `document.readyState` rồi chạy lại F04 và F06 cùng đạt.
- Chưa kiểm thử gửi email/Turnstile thật vì cần dịch vụ ngoài; endpoint trả 503 khi không gửi được và không trả OTP. DDL legacy trong HTTP, log chat chứa PII và chuẩn hóa schema thuộc F08/F15, không được coi là đã giải quyết bởi F06.

## F07 — Một nguồn chuẩn cho phí vận chuyển

- Xác nhận từ code: `Cart.php`, `CheckOut.php` và `CheckoutService.php` cùng giữ hằng số 25.000đ/miễn phí từ 500.000đ. UI có thể hiển thị một số tiền nhưng backend tự tính lại; client không còn được quyền truyền phí ship vào tạo đơn, song vẫn tồn tại nhiều producer cùng công thức cũ.
- Địa chỉ checkout và snapshot đơn hiện chỉ có `DiaChi` dạng chuỗi tự do; không lưu mã tỉnh/huyện/xã. `sanpham` chưa có khối lượng vận chuyển. Vì vậy chưa đủ dữ liệu để ánh xạ bảng phí khu vực/trọng lượng một cách xác định.
- Trạng thái `WAITING_DECISION` theo thông tin chủ dự án sẽ cung cấp sau: cần địa chỉ kho, mã/nhóm khu vực, các khoảng gram không chồng lấn, phí từng khoảng và điều kiện miễn phí nếu có. Không seed mức giả và không kích hoạt cấu hình rỗng.
- Thiết kế đã chốt ở mức hợp đồng: admin/manager tạo bản nháp, validation phủ đủ khu vực và khoảng khối lượng, kích hoạt nguyên tử một phiên bản, giữ audit/version; checkout chỉ đọc phiên bản active và snapshot phí/quy tắc vào đơn. Việc thêm UI/schema và chuyển ba caller sẽ thực hiện cùng một hạng mục khi có bảng phí và quyết định mã khu vực.

## F08.1 — Dời schema contact/rating khỏi HTTP

- Migration mới `20260911_0013_contact_and_rating_runtime_schema.sql` tạo `contact_messages` và `danhgia`; rating có unique theo khách+sản phẩm, CHECK 1–5 và FK sản phẩm `RESTRICT` để khớp bảo vệ lịch sử F04.
- Đã bỏ `CREATE TABLE IF NOT EXISTS` khỏi trang chi tiết sản phẩm, endpoint đánh giá và endpoint liên hệ. Runtime web ở các luồng này chỉ cần quyền đọc/ghi dữ liệu, không cần CREATE/ALTER.
- `runtime_schema_integration.php` drop hai bảng trên database fixture, chạy migration hai lần, kiểm tra đủ bảng/unique/CHECK/DELETE_RULE: PASS. Hồi quy `catalog_delete_integration`, `comments_integration`, comments browser 1440/390, F06 browser và bốn baseline smoke đều PASS.
- Một lần chạy browser bị trùng nội dung tổng hợp do lượt trước gián đoạn; fixture nay xóa riêng comment bắt đầu `Browser ` trước mỗi lượt. Chạy lại F01/F06 đạt.
- F08 cha còn `IN_PROGRESS`: baseline vẫn chưa dựng database trống, còn DDL/ALTER trong request hoàn trả, shipper, lead và chat label; chưa kiểm chứng upgrade/restore toàn chuỗi migration.

## Kiểm tra lại mức độ hoàn thành ngày 11/09/2026

- Kết luận tại thời điểm kiểm tra: kế hoạch F00–F15 chưa hoàn thành đầy đủ. Sau khi đối chiếu lại code thay vì chỉ tin báo cáo cũ, F06 từng được mở lại và nay đã sửa, kiểm thử lại đầy đủ.
- Bằng chứng mở lại F06: `Admin_Logout.php` xóa phiên ngay trên GET và hai layout gọi bằng liên kết; `Employee_Delete_Appointment.php` nhận POST nhưng chưa yêu cầu CSRF, chỉ tin `user_role` trong session, dùng DB hardcode và có thể xóa appointment bất kỳ theo ID. Cần sửa endpoint và caller, thêm test GET/CSRF/role thu hồi/ownership rồi chạy lại F01/F02/F06.
- F07 vẫn còn ba nguồn phí ship 25.000đ/500.000đ tại cart, checkout và `CheckoutService`; chưa có bảng phí theo khu vực/trọng lượng nên chưa đạt invariant tiền.
- F08 còn DDL/ALTER trong HTTP ở return/refund, shipper, lead và chat labels; còn nhiều kết nối `root`/`webmipham1` hardcode. Chưa có bằng chứng cài DB trống, nâng cấp dữ liệu cũ và restore hoàn chỉnh.
- F09 vẫn còn `Path(__file__).resolve().parents[4]` trong AI và chưa có Docker runtime evidence. Việc chủ dự án hoãn Docker chỉ hoãn thực thi F09, không làm F09 đạt.
- F03 và F10–F15 chưa có tiêu chí nghiệm thu đầy đủ. F05 mới `READY_FOR_REVIEW` vì lịch sử Git/image runtime chưa được xử lý hoặc kiểm chứng.

## Bổ sung khép kín F06 ngày 11/09/2026

- `Admin_Logout.php` chỉ nhận POST, kiểm tra CSRF và trả redirect 303; cả topbar/sidebar dùng form POST. Test xác nhận GET và token sai không xóa phiên, POST hợp lệ chỉ xóa phiên admin và giữ phiên customer UI.
- `Employee_Delete_Appointment.php` dùng `app_pdo()`, chỉ nhận POST/CSRF, đọc lại danh tính và role `employee` từ database; mutation chỉ xóa lịch đã qua. Test xác nhận GET/token sai/quyền bị thu hồi/lịch tương lai đều bị chặn, lịch cũ được xóa đúng một lần.
- R0 đạt cho toàn bộ file thay đổi. `comments_integration.php`, `rate_limit_integration.php`, `realtime_legacy_repro.php`, comments browser 1440/390, F06 browser và realtime socket browser đều PASS sau khi dựng lại fixture theo đúng thứ tự.
- F06 trở lại `DONE`. Không thay đổi mô hình phân công lịch hẹn vì schema hiện không có trường nhân viên phụ trách; quyền xóa toàn bộ lịch đã qua của employee là hợp đồng đang có trên màn hình.

## F03 — Hoàn tiền hàng sau voucher

- Migration `20260911_0014_refund_money_invariants.sql` lưu `SoTienGiamPhanBo` và `ThanhTienThucTra` trên từng dòng đơn, backfill theo tỷ lệ VND nguyên và dồn phần dư làm tròn vào dòng dương cuối. Checkout mới ghi hai snapshot này ngay khi tạo đơn.
- Yêu cầu hoàn trả lưu số lượng; khách có thể tạo nhiều yêu cầu cho các sản phẩm hoặc phần số lượng khác nhau. Dòng đơn được khóa khi giữ số lượng nên các request đồng thời không thể vượt số đã mua.
- Duyệt hoàn khóa request+dòng đơn, đọc lại role actor trong DB, tính quyền lợi lũy kế từ `ThanhTienThucTra`, ghi `refund_transactions` có unique request, cập nhật request, cộng ví và outbox trong một transaction. Lịch sử approved cũ thiếu ledger bị chặn để đối soát thủ công thay vì đoán số tiền.
- Tiền ship không xuất hiện trong công thức/ledger và payload ghi rõ `shipping_refund_amount=0`. Ca 1.000.000đ, giảm 200.000đ, tổng order có thêm 25.000đ ship hoàn đúng 800.000đ; hai partial hoàn 400.000đ; retry không cộng lại; hai process cạnh tranh một đơn vị chỉ một process thắng.
- Các màn hình customer/staff/admin dùng giá snapshot, số lượng yêu cầu và tiền sau voucher; đã xóa ba khối xử lý POST cũ không thể tới được vốn dùng giá catalog và tự ALTER schema. `refund_integration.php`, catalog/comments regression và bốn baseline smoke PASS.

## F07 — Phí vận chuyển do admin cấu hình

- Migration `0015` tạo version draft/active/archive, vùng, bậc gram, audit, khối lượng sản phẩm và snapshot phí/vùng/version trên đơn. Unique key bảo đảm tối đa một version active. Không seed vùng hoặc mức phí giả.
- `Shipping_Settings.php` cho admin/manager khai báo kho, ngưỡng miễn ship tùy chọn, vùng, bậc phí và khối lượng sản phẩm. Kích hoạt bị chặn khi thiếu vùng, có khoảng trống/chồng lấn, bậc cuối chưa mở hoặc sản phẩm còn tồn thiếu khối lượng.
- Cart lấy vùng và quote từ server; COD/QR/balance truyền cùng mã vùng. Checkout khóa và đọc giá/tồn/khối lượng live, chỉ dùng `ShippingService`, rồi snapshot kết quả. Không còn công thức 25.000đ/500.000đ trong cart, checkout hoặc backend.
- `shipping_integration.php` PASS cho single-active, hai bậc khối lượng, miễn ship tùy chọn, vùng lạ và live product weight. Admin còn phải nhập bảng phí thực và kích hoạt trước khi nhận đơn.

## F08 — Schema và vòng đời database

- `20260816_0000_core_schema.sql` tạo 24 bảng lõi từ DDL của dump, không có INSERT/dữ liệu người dùng. Migrator adopt khi đủ 24 bảng cũ và fail-closed nếu schema chỉ có một phần.
- `0016` quản lý chat labels. Toàn bộ `CREATE TABLE`/`ALTER TABLE` đã rời HTTP request. Các trang PHP/exports/analytics/chat dùng cấu hình chung; connector và CLI AI không còn mặc định `root/webmipham1`.
- DB trống `moshop_test_fresh2` áp đủ 17 migration; fixture legacy đủ bảng được adopt rồi nâng cấp đủ 17. Backup bằng single transaction/no tablespaces và restore sang DB mới đạt 53 bảng, 17 migration, 117 constraint. Không đụng database người dùng.

## Kiểm tra lại theo kế hoạch ngày 11/09/2026 (lần hiện tại)

- Bảng tiến độ đầu tài liệu là trạng thái chuẩn hiện tại; các đoạn `WAITING_DECISION`, `IN_PROGRESS` hoặc nhận xét cũ phía trên là nhật ký tại thời điểm trước và đã được thay thế bởi kết quả F03/F07/F08 ghi sau đó.
- Đã hoàn tất có bằng chứng: F00, F01, F02, F03, F04, F06, F07 và F08. F05 ở `READY_FOR_REVIEW` vì current revision đã bỏ dữ liệu riêng tư khỏi index/public boundary nhưng Git history cũ chưa được rewrite và Docker image chưa được inspect.
- F09 được chủ dự án hoãn runtime Docker. Vì chưa build/start/restart/health/volume trên Docker nên không được tính `DONE`.
- F10 đang làm: retrieval ghi đè tên/giá/tồn từ MySQL và loại sản phẩm xóa/hết hàng; phí ship AI đọc phiên bản admin active; full ingest xóa vector stale và gắn `catalog-v2`; admin add/update/delete ghi sự kiện outbox; worker đồng bộ có lease, retry hữu hạn và dead-letter fields. Bộ 8 câu hỏi tiếng Việt có expected source/behavior; 7 unit test data contract PASS. Chưa đạt vì môi trường Python hiện có Chroma không tương thích NumPy 2 (`np.float_`), chưa chạy service thật, chưa hiệu chỉnh retrieval threshold, chưa đo latency/chất lượng và chưa test quyền chat end-to-end trong cùng suite.
- F11 mới đạt từng phần: SePay/refund/voucher có khóa/idempotency riêng và các money smoke PASS; migration 0017 bổ sung claim/retry/dead-letter cho outbox và đã áp thành công hai lần trên DB trống cô lập. Vẫn thiếu idempotency tạo đơn, test cạnh tranh toàn tuyến checkout–cancel–payment–expiry, báo cáo đối soát, heartbeat/alert và chứng minh không mất notification khi worker/service chết.
- F12 chưa đạt tiêu chí toàn mục. Một số nghiệp vụ đã tách thành service (`CheckoutService`, `VoucherService`, `ShippingService`, `MoneyAllocationService`) và DB config đã gom lại, nhưng view/endpoint lớn và PDO/mysqli song song vẫn còn; chưa có refactor độc lập kèm regression trước/sau.
- F13 chưa bắt đầu thiết kế nghiệp vụ SKU/biến thể/lô/hạn dùng/inventory movement. `TrongLuongGram` và snapshot giá/tiền của F03/F07 chỉ là nền, không đủ để đóng F13.
- F14 chưa đạt: `View_Product.php` vẫn chọn category/brand/search bằng `if/elseif`; layout còn `lang="en"`, title chung; chưa có canonical, JSON-LD, sitemap, audit keyboard/accessibility/performance và nội dung pháp nhân đã xác nhận.
- F15 đang làm một phần: có CI lint/audit/smoke/build definition, health DB, tài liệu local/deploy và môi trường MySQL cô lập. Chưa có CI MySQL integration, readiness tách liveness, request correlation/structured redacted logging, metrics/alert, runbook release/rollback/restore đầy đủ và Docker runtime gate.
- Kiểm tra hiện tại: toàn bộ PHP ngoài vendor syntax PASS; toàn bộ Python ML compile PASS; 7 AI data-contract test PASS; security, SePay, voucher, customer scoring, repository boundary và realtime targeting smoke PASS. Fresh database áp đủ migration 0000–0017 và lượt chạy lại skip đúng.
- Hạn chế mới phát hiện: chạy chuỗi integration trên database fixture dùng chung làm `refund_integration.php` lỗi khóa ngoại do sản phẩm fixture bị test trước thay đổi/xóa; migrator đúng khi từ chối schema chỉ có 9/24 bảng. Đây là lỗi cô lập test của F15, không được ghi thành regression business đã PASS. Cần chuyển mỗi integration test sang database riêng hoặc reset fixture bằng migration trước khi dùng kết quả suite làm release gate.
