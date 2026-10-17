# Kế hoạch chuẩn hóa Mơ Shop và quy tắc thực hiện dành cho AI

Ngày lập: 10/09/2026. Đối tượng thực hiện: GPT-5.6 SOL hoặc lập trình viên tiếp nhận dự án.

Thư mục gốc: `D:\VKU\DACS\DACS4_WebBanHangMiPhamAI\TTTT1`.

## 1. Mục tiêu và phạm vi ủy quyền

Chuyển dự án thành hệ thống có cấu trúc, dữ liệu và quy trình vận hành chuyên nghiệp; có thể dựng mới, kiểm thử và triển khai bằng Docker một cách tái lập. Không tối ưu theo giả định ngân sách thấp. Không dùng việc tắt AI, realtime hoặc bỏ nghiệp vụ để tuyên bố hoàn thành chuẩn hóa.

Tài liệu này là hướng dẫn thực hiện khi chủ dự án giao nhiệm vụ sửa code. Việc tạo tài liệu không đồng nghĩa đã sửa các lỗi liệt kê bên dưới.

Phạm vi mặc định khi được giao thực hiện: đọc dự án, lập baseline, sửa code/config/tài liệu, tạo migrations và kiểm thử trên môi trường thử nghiệm cô lập. Không tự public, deploy production, gửi thông báo thật, thu/hoàn tiền thật, thay credential thật, xóa dữ liệu thật, viết lại lịch sử Git hoặc reset thay đổi có sẵn. Những hành động đó cần ủy quyền riêng, cụ thể.

Không tự chuyển framework, viết lại toàn bộ dự án, tách microservices hoặc thêm hạ tầng vì cho rằng như vậy mới chuyên nghiệp. Đề xuất thay đổi kiến trúc lớn bằng một quyết định kỹ thuật có vấn đề, phương án, tác động, chi phí vận hành và kế hoạch chuyển đổi để chủ dự án duyệt.

## 2. Vai trò và kỷ luật của người thực hiện

Làm việc như một junior có kỷ luật dưới quy trình review nghiêm ngặt: không đoán nghiệp vụ, không che lỗi, không tự mở rộng phạm vi, không tự kết luận thành công khi thiếu bằng chứng. Đây là yêu cầu về trách nhiệm thực hiện, không phải yêu cầu viết giải pháp đơn giản hóa hay kém chất lượng.

Các quy tắc bắt buộc:

1. Đọc `AGENTS.md` nếu có, tài liệu này, code liên quan, migrations, cấu hình và tests trước khi sửa. Không chỉ dựa vào báo cáo cũ.
2. Giữ nguyên thay đổi có sẵn của người dùng. Ghi nhận `git status`, diff và file chưa được theo dõi trước khi bắt đầu. Không dùng `git reset --hard`, `git clean`, checkout đè file hoặc xóa volume để có môi trường “sạch”.
3. Mỗi lần chỉ có **một hạng mục sửa đang triển khai**. Hạng mục có thể gồm nhiều file cần thiết cho cùng một lỗi. Không trộn refactor không liên quan vào bản sửa.
4. Trước khi sửa phải trả lời: lỗi phát sinh từ đâu, ai gọi đoạn này, dữ liệu nào bị thay đổi, những luồng nào có thể bị ảnh hưởng và test nào sẽ phát hiện hồi quy.
5. Chứng minh lỗi bằng ca tái hiện an toàn hoặc đường đi dữ liệu cụ thể. Gắn nhãn `đã tái hiện`, `xác nhận từ code`, `rủi ro cần xác minh` hoặc `yêu cầu cải tiến`.
6. Sửa nguyên nhân gốc. Không che lỗi bằng `@`, `catch` rỗng, trả thành công giả, bỏ validation, bỏ test hoặc đổi expected cho khớp kết quả sai.
7. Không sửa lỗi A xong chỉ kiểm tra A. Phải chạy test trực tiếp của A, test vùng ảnh hưởng và test bảo vệ các lỗi đã hoàn thành có liên quan.
8. Nếu sửa B làm A hỏng: dừng chuyển sang C, đánh dấu A và B chưa đạt, xử lý hồi quy rồi kiểm tra lại cả A và B. Không tiếp tục khi còn lỗi mới chưa giải quyết trong phạm vi thay đổi.
9. Không sửa migration đã được áp dụng. Viết migration mới; mô tả backfill, khóa bảng, tương thích, khả năng phục hồi và dữ liệu không thể tự khôi phục.
10. Không chạy integration test, migration, seed hoặc load test trên database đang chứa dữ liệu người dùng nếu chưa có ủy quyền cụ thể. Dùng database/volume riêng với guard kiểm tra môi trường.
11. Không xuất `.env`, password, token, nội dung chứng từ hoặc dữ liệu cá nhân vào log, báo cáo, fixture hay hội thoại. Chỉ báo tên biến và trạng thái cấu hình đã che giá trị.
12. Không tự nâng tất cả dependencies lên bản mới nhất. Chọn phiên bản có căn cứ, kiểm tra tài liệu chính thức, audit và compatibility; cập nhật lockfile có kiểm soát.
13. Không tuyên bố “an toàn”, “production-ready”, “Docker chạy được” chỉ vì lint, smoke test hoặc build thành công. Phải có runtime và integration evidence tương ứng.
14. Không tự đánh dấu đạt các kiểm tra cần dịch vụ ngoài, tài khoản thật hoặc hạ tầng chưa có. Ghi `BLOCKED`, điều kiện cần và phần đã kiểm tra; tiếp tục phần độc lập được phép.
15. Hỏi chủ dự án khi quyết định làm thay đổi quyền lợi khách hàng hoặc ý nghĩa tài chính: phí ship, hoàn voucher, hoàn phí ship, số lần hoàn, phương thức hoàn, quyền nhân viên. Chuẩn bị phương án cụ thể trước khi hỏi. Thời gian chờ không phải sự đồng ý.
16. Không yêu cầu xác nhận lặp lại cho sửa code/kiểm thử đã được giao. Không dừng ở việc nêu kế hoạch khi có thể tiếp tục công việc được phép.
17. Không đổi tên file, URL, bảng hoặc field hàng loạt vì thẩm mỹ. Mọi thay đổi hợp đồng cần kế hoạch tương thích và kiểm tra nơi sử dụng.
18. Khi cần nguồn ngoài, dùng tài liệu chính thức hiện hành và ghi nguồn/phiên bản. Không suy đoán cách doanh nghiệp khác tổ chức backend từ giao diện công khai.

## 3. Mức độ bằng chứng của báo cáo đầu vào

Đợt đọc ban đầu đã xem các phần quan trọng của PHP, JS, Python, SQL, Docker, CI và tài liệu. Chưa phải kiểm toán toàn bộ từng file, chưa có kiểm thử xâm nhập, load test, browser E2E, Docker runtime hoặc thanh toán thực tế.

Đã chạy qua bốn test ở thời điểm lập báo cáo: `tests/security_smoke.php`, `tests/sepay_gateway_smoke.php`, `tests/voucher_smoke.php`, `tests/customer_scoring_test.php`. Phải chạy lại trên baseline mới; không sử dụng kết quả cũ làm bằng chứng cho code mới.

Đã tái hiện riêng biểu thức `PurePosixPath('/app/api_rag_ollama.py').parents[4]` gây `IndexError`. Đây là bằng chứng lỗi đường dẫn dự kiến của Docker AI, không phải bằng chứng image đã build hoặc container đã chạy.

Working tree lúc khảo sát có nhiều thay đổi và file mới. Không được coi tất cả diff hiện có là do AI thực hiện hoặc mặc định có thể checkout bản hiện tại từ Git.

## 4. Vòng lặp sửa lỗi bắt buộc

Thực hiện đầy đủ cho từng ID trong backlog:

1. **Xác minh:** đọc code hiện tại và test; cập nhật nhãn bằng chứng. Nếu lỗi đã được sửa, vẫn xác minh regression test trước khi đóng.
2. **Khoanh vùng:** liệt kê file dự kiến sửa, caller, database, API, UI, job nền, AI, cấu hình và deployment có liên quan. Ghi rõ các phần không liên quan.
3. **Chốt hành vi đúng:** ghi input/output, invariant và các trường hợp biên. Nếu thiếu quyết định nghiệp vụ thì yêu cầu trả lời, không tự chọn âm thầm.
4. **Tạo hoặc bổ sung test cần thiết:** lỗi bảo mật, tiền, dữ liệu và deployment cần test hành vi. Chứng minh test lỗi trước bản sửa nếu có thể; nếu không, giải thích giới hạn. Không viết test chỉ dò chuỗi source thay cho test bảo mật.
5. **Sửa tối thiểu nhưng đầy đủ:** cập nhật tất cả producer/consumer chịu ảnh hưởng của cùng hợp đồng; không để hai cách tính cùng tồn tại.
6. **Kiểm tra trực tiếp:** lint/type/syntax phù hợp và test tái hiện lỗi.
7. **Kiểm tra thống nhất:** dùng checklist ở mục 5. Chạy các nhóm hồi quy cần thiết trong mục 6, gồm test của các lỗi đã sửa chịu ảnh hưởng.
8. **Review diff:** kiểm tra file ngoài phạm vi, debug còn sót, secret, migration, lockfile, xóa dữ liệu và thay đổi giao diện không chủ ý.
9. **Ghi bằng chứng:** lệnh chạy, môi trường, kết quả, ca chưa chạy, diff liên quan và cách khôi phục. Không đưa dữ liệu nhạy cảm vào log.
10. **Đóng có điều kiện:** chỉ chuyển `DONE` khi tiêu chí đạt. Sau đó mới bắt đầu ID tiếp theo. Nếu phụ thuộc chưa giải quyết, ghi `BLOCKED` hoặc `READY_FOR_REVIEW`, không dùng `DONE`.

Ví dụ hồi quy: sửa F01 bình luận → chạy R0/R1/R6; sửa F02 WebSocket → chạy R0/R1/R5 và phần F01 liên quan đến session/shared security. Nếu helper xác thực thay đổi làm bình luận lỗi thì mở lại F01, sửa và chạy lại cả hai. Các test đã thêm trở thành regression suite lâu dài.

Không chạy lại mọi test tốn kém sau từng thay đổi nội dung nhỏ. Chọn test theo dependency map; khi sửa thành phần dùng chung hoặc kết thúc một giai đoạn, chạy toàn bộ suite liên quan. Mục tiêu là phát hiện hồi quy có căn cứ, không chạy lặp hình thức.

## 5. Checklist tính thống nhất cho mỗi bản sửa

- [ ] Backend và frontend thống nhất trường dữ liệu, kiểu, giá trị enum, mã lỗi và HTTP status.
- [ ] MySQL là nguồn chuẩn cho dữ liệu giao dịch; cache, Chroma, CSV và UI không có quyền tự quyết định giá/tồn kho.
- [ ] Trang chính sách, checkout, voucher, hoàn tiền, thông báo và kiến thức AI dùng cùng quy tắc đã được duyệt.
- [ ] Danh tính/quyền lấy từ nguồn máy chủ đáng tin; ID từ client luôn được kiểm tra quyền sở hữu.
- [ ] Trạng thái đơn, thanh toán, giữ kho, hủy, giao hàng và hoàn trả chuyển hợp lệ; không cập nhật một nơi rồi bỏ nơi khác.
- [ ] Luồng thành công, thất bại, retry, request trùng, đồng thời và service mất kết nối đều được xét nếu có liên quan.
- [ ] Migrations hỗ trợ bản cài mới và nâng cấp dữ liệu cũ; không mất dữ liệu hoặc tự bịa lịch sử giao dịch.
- [ ] Local Windows và Docker Linux thống nhất đường dẫn, chữ hoa/thường, timezone, charset, dependencies và biến môi trường cần thiết.
- [ ] Web, CLI, scheduler, AI, ingest và realtime đọc cấu hình nhất quán.
- [ ] Session/cookie/CSRF/authorization không bị suy yếu bởi bản sửa.
- [ ] Log, tài liệu, tests và hướng dẫn triển khai đã cập nhật theo hành vi cuối cùng.
- [ ] Test của các lỗi đã sửa trong vùng ảnh hưởng vẫn đạt; không có lỗi mới chưa giải thích.

## 6. Ma trận kiểm thử và invariant

| Nhóm | Phạm vi tối thiểu | Khi bắt buộc chạy |
|---|---|---|
| R0 | Syntax/lint file thay đổi, validate cấu hình, review diff | Mọi bản sửa code/config |
| R1 | Đăng nhập/đăng xuất, session, CSRF, role, ownership, SQL/XSS, upload | Sửa auth, helper, input/output, endpoint |
| R2 | Giá, giỏ hàng, voucher, checkout, giữ/trả kho, request trùng và đồng thời | Sửa catalog/đơn/voucher/database liên quan |
| R3 | Webhook, thanh toán, số dư, hủy, hoàn một phần, đối soát | Mọi thay đổi về tiền và trạng thái đơn |
| R4 | Schema mới, migration nâng cấp, constraint, dữ liệu lịch sử, backup/restore | Sửa dữ liệu/migration/release |
| R5 | AI/ingest, chat ownership, realtime auth, outbox/scheduler/retry | Sửa chat, AI, job, notification hoặc dữ liệu dùng chung |
| R6 | Browser hành trình mua hàng, trạng thái lỗi/rỗng/loading, mobile, keyboard | Sửa UI hoặc API/UI contract |
| R7 | Build + start từ môi trường sạch, health/readiness, volume persistence, restart, profile đầy đủ | Sửa Docker/dependency/env/path/entrypoint; trước đóng giai đoạn triển khai |

Các invariant phải có test phù hợp:

- Tổng đơn và tiền giảm/ship tuân theo một công thức đã được duyệt; tiền dùng đơn vị nguyên VND hoặc kiểu tiền chính xác, không dùng float tùy tiện.
- Không bán vượt tồn kho, không trừ/hoàn kho hai lần cho cùng chuyển trạng thái.
- Webhook trùng không cộng tiền hoặc xác nhận thanh toán thêm lần nữa; browser callback không tự đánh dấu đã trả tiền.
- Hoàn tiền dựa trên tiền thực trả được phân bổ; tổng hoàn không vượt giới hạn đã chốt. Retry không hoàn trùng.
- Một người không xem/sửa đơn, chứng từ, chat hoặc thông báo của người khác ngoài quyền được cấp.
- Xóa/ngừng bán catalog không làm mất chứng từ hoặc thay đổi giá/tên/SKU lịch sử cần lưu.
- Nếu thông báo realtime thất bại, giao dịch đã commit vẫn đúng và thông báo bền vững còn truy xuất được.
- AI không quyết định tiền, quyền, trạng thái giao dịch hoặc tồn kho; dữ liệu không đủ phải có hành vi fallback xác định.
- Một image/version cùng cấu hình hợp lệ có thể dựng lại hệ thống theo tài liệu, không phụ thuộc file bí mật hoặc dữ liệu chỉ có trên máy tác giả.

## 7. Backlog lỗi và hạng mục chuẩn hóa

P0: chặn sử dụng công khai/nghiệp vụ tiền tương ứng. P1: bắt buộc cho nền tảng triển khai chuyên nghiệp. P2: chuẩn hóa sản phẩm và khả năng mở rộng. Ưu tiên không đồng nghĩa được bỏ qua phụ thuộc.

### F00 — Baseline, môi trường thử nghiệm và sổ theo dõi [P1; làm đầu tiên]

- Ghi nhận branch, HEAD, working tree có sẵn; kiểm kê runtime, lockfile, schema version và dịch vụ đang chạy mà không in secret.
- Tạo môi trường test riêng, tên database/Compose project/volume khác môi trường người dùng. Test phải từ chối chạy khi cấu hình trỏ sai môi trường.
- Lập dependency map và chạy suite hiện có sau khi đọc tác động của từng test. Không chạy script có mutation chỉ vì tên chứa `smoke`.
- Đạt khi phân biệt được lỗi có sẵn/lỗi mới, test data có thể tạo lại và không đụng dữ liệu thực. Thiết lập sổ theo dõi theo mục 9.

### F01 — SQL injection và stored XSS ở bình luận [P0; xác nhận từ code]

- Vị trí: `TTTT/WebBanHangMyPham/src/Processing/XuLy_BinhLuan.php`, `src/View/Detail_Product.php`; rà thêm mọi nơi hiển thị bình luận trong admin/client.
- Đầu vào POST được ghép vào SQL, bình luận được xuất trực tiếp vào HTML. Xác minh các caller và điều kiện đã mua hàng.
- Sửa bằng prepared statements, validation, method/CSRF/ownership và escaping đúng ngữ cảnh. Nếu nội dung cho phép HTML, chốt allowlist và sanitizer; không escape rồi decode trở lại.
- Đạt: bình luận tiếng Việt/ký tự đặc biệt lưu đúng; HTML không chạy; input bất thường không đổi ngữ nghĩa SQL; người không đủ quyền bị từ chối. R1/R6 đạt.

### F02 — Xác thực WebSocket và quyền kênh [P0 khi bật realtime; xác nhận từ code]

- Vị trí: `TTTT/WebSocketServer/src/RealtimeServer.php`, `server.php`, client JS và nơi phát thông báo.
- Server đang nhận role/channel/user_id từ client để đăng ký kênh. Lọc người nhận bằng ID tự khai không phải xác thực.
- Thiết kế vé ngắn hạn có chữ ký hoặc cơ chế session server-side tương đương; server tự suy ra quyền. Kiểm tra expiry, origin theo topology và hành vi logout/thu hồi quyền.
- Đạt: không có vé/vé giả/hết hạn/sai quyền không đăng ký được; không giả danh user khác; admin/staff/shipper/customer chỉ nhận dữ liệu được phép. Test endpoint thực, không chỉ kiểm tra chuỗi source.

### F03 — Hoàn tiền, voucher và hoàn từng phần [P0; xác nhận từ code]

- Vị trí: `TTTT/config/returns.php`, `CheckoutService.php`, `VoucherService.php`, `Submit_Return_Refund_Request.php`, các màn hình duyệt và lịch sử.
- `ThanhTien` dòng hàng là tiền trước voucher; hàm hoàn dùng giá trị đó. Ví dụ đơn 500.000đ giảm 20%, miễn ship, trả 400.000đ: hoàn 500.000đ bị chặn vì vượt tổng đơn.
- Request chọn sản phẩm nhưng kiểm tra trùng theo toàn bộ đơn. Không tự kết luận hiện cho phép hoàn nhiều sản phẩm thành nhiều request: phải kiểm chứng code hiện tại.
- Chốt nghiệp vụ phân bổ giảm giá/làm tròn, phí ship, số lượng hoàn, giới hạn lũy kế, hoàn về đâu, xử lý COD và hàng lỗi trước khi sửa.
- Lưu phân bổ bất biến và lịch sử hoàn có thể đối soát; khóa/idempotency phù hợp. Không dùng giá catalog hiện tại để tái dựng lịch sử thiếu dữ liệu.
- Đạt: test đơn một/nhiều dòng, voucher có trần, hoàn một phần/toàn phần, retry, concurrent approval, hủy/hoàn cạnh tranh và lịch sử cũ. R2/R3/R4 đạt.

### F04 — Cascade delete và bảo toàn lịch sử [P0; rủi ro theo dump cần đối chiếu DB]

- Vị trí: `AdminManage/src/Categorys/Delete_Category.php`, `Brands/Delete_Brand.php`, `Products/Delete_Product.php`, SQL/migrations và truy vấn chi tiết đơn.
- Dump có cascade từ danh mục/thương hiệu sang sản phẩm, trong khi chức năng xóa trực tiếp sản phẩm có bảo vệ đơn hàng.
- Xác minh constraint thực tế; dùng restrict/ngừng hoạt động hoặc soft delete theo thiết kế. Chặn bypass qua quan hệ cha. Bảo toàn snapshot lịch sử.
- Đạt: xóa/ngừng bán danh mục, thương hiệu, sản phẩm không phá đơn cũ; báo lỗi dễ hiểu; migration không xóa dữ liệu. R2/R4/R6 đạt.

### F05 — Dữ liệu nhạy cảm, upload và ranh giới public [P0; cần kiểm kê]

- `webmipham1.sql` được Git theo dõi; tài liệu deployment ghi có dữ liệu demo/cá nhân và credential mail từng tồn tại. Không khẳng định credential còn hiệu lực nếu chưa biết.
- Kiểm kê tracked files, build context và nội dung image: `.env` lồng, `.venv`, Chroma, CSV, chứng từ, avatar và dữ liệu cá nhân. `.gitignore` không gỡ file đã tracked, `.dockerignore` không thay thế kiểm tra image.
- Tách schema/seed giả; bảo vệ file private và endpoint tải file bằng ownership. Kiểm tra Apache có thể phục vụ file ML/config/data ngoài ý muốn hay không.
- Đạt: không có secret/chứng từ riêng tư trong image hoặc nguồn public; có kế hoạch xử lý credential/history cần chủ dự án thực hiện/ủy quyền. Không tự rotate hoặc rewrite history.

### F06 — Auth, rate limit và session dùng chung [P0/P1; cần rà toàn tuyến]

- Vị trí: `config/bootstrap.php`, `security.php`, login/register/OTP, admin access, chat, endpoint mutation.
- Rate limit hiện lưu session nên có thể bắt đầu lại bằng session mới. Rà thêm POST thiếu CSRF, GET làm thay đổi trạng thái, role lưu session bị cũ và định danh không thống nhất.
- Thiết kế giới hạn server-side theo endpoint/tài khoản/IP phù hợp proxy; kiểm tra trusted proxy, không tin header tùy ý. Chuẩn hóa danh tính và quyền mà không làm mất phân tách phiên admin/customer hiện có.
- Đạt: đổi cookie không vô hiệu hóa giới hạn hệ thống; token/quyền/ownership được kiểm tra ở server; không tạo khóa tài khoản quá dễ bị lạm dụng. R1 và regression F01/F02 đạt.

### F07 — Một nguồn chuẩn cho giá và quy tắc thương mại [P1]

- `GiaTien` dùng bán hàng, `ingest_rag.py` ưu tiên `COALESCE(Gia, GiaTien)`. Xác minh mức lệch bằng truy vấn chỉ đọc có tổng hợp, không in dữ liệu nhạy cảm.
- Chính sách ship nói theo khoảng cách/trọng lượng và freeship nội thành trên 500.000đ; checkout cố định 25.000đ, miễn từ 500.000đ không xét khu vực.
- `shop_knowledge.json` còn FAQ VIP 5 triệu/tuần giảm 20%, thông tin cửa hàng chưa cấu hình, trong khi UI/scoring đã thay đổi.
- Chốt nguồn và hợp đồng cho giá, voucher, shipping, phân hạng. Cập nhật admin, storefront, checkout, thông báo, export, AI và tài liệu cùng lúc theo từng quy tắc.
- Đạt: test các ranh giới 499.999/500.000/500.001 và địa chỉ theo chính sách đã duyệt; giá AI/UI/checkout khớp; không còn ưu đãi cũ bị công bố sai. R2/R3/R5/R6 đạt.

### F08 — Schema sạch, migrations và dữ liệu lịch sử [P1]

- Migration đầu tiên `20260817_0001_production_baseline.sql` bắt đầu bằng `ALTER TABLE khachhang`; không dựng được DB trống chỉ bằng chuỗi migration hiện tại.
- Rà charset/collation, cột trùng ý nghĩa, bảng legacy, enum trạng thái, unique/foreign key và truy vấn tạo bảng trong HTTP (`Email_API.php`, `Process_Contact.php`, `XuLy_DanhGia.php`...).
- Thiết kế đường cài mới và đường nâng cấp rõ ràng; không sửa checksum migration đã áp dụng. Tách schema và seed. Không tự backfill giá lịch sử bằng giá hiện tại rồi gọi đó là số liệu chính xác.
- Dời DDL khỏi request sau khi migration tương ứng có sẵn; phân biệt quyền tài khoản migration và runtime.
- Đạt: DB trống cài được, bản dữ liệu cũ nâng cấp được, chạy lại không làm hỏng dữ liệu, constraint được kiểm chứng, restore thử thành công. R4 và regression thương mại đạt.

### F09 — Docker build/runtime và tính tái lập [P1; nhiều lỗi xác nhận từ cấu hình]

- Vị trí: `Dockerfile`, `Dockerfile.ai`, `.dockerignore`, hai Compose, entrypoint, Apache/PHP config, ML API và API địa chỉ.
- Sửa lỗi `parents[4]` khi ML được copy vào `/app`; không dùng đường dẫn máy tác giả làm fallback production.
- File JSON địa giới nằm ngoài `TTTT`, image PHP chỉ copy `TTTT`; chuẩn hóa nơi đóng gói và nơi đọc, kiểm tra cả local và container.
- Loại môi trường `.venv` lồng và dữ liệu không cần khỏi image; kiểm tra pattern bằng kết quả build thực tế. Không copy secret để “sửa” thiếu config.
- Production Compose hiện thiếu cấu hình chạy AI/Ollama/WebSocket tương ứng local. Cung cấp topology đầy đủ hoặc cấu hình external service có hướng dẫn kiểm thử; không chỉ bỏ tính năng để đạt health.
- Tách migration thành bước release có kiểm soát; tránh app/scheduler/replica cùng chạy migration. Đánh giá readiness, thứ tự khởi động, retry và restart.
- Kiểm tra `.env` bắt buộc trong CI, composer autoload cho từng service, quyền volume, Linux case sensitivity, đường dẫn asset, image tag/digest và dependencies đã khóa.
- Đạt: build và start stack trên môi trường sạch; AI import được và trả lời fixture; địa chỉ/catalog hoạt động; restart giữ dữ liệu; recreate không mất upload/DB; profile tài liệu mô tả thực sự chạy được. R7 và R2/R5 liên quan đạt.

### F10 — AI/RAG đồng bộ, đúng dữ liệu và có đánh giá [P1]

- Vị trí: `src/ML/*`, `Processing/Chat_Send.php`, chat/session services và JS widget.
- Ingest chỉ upsert, chưa thấy xóa document của sản phẩm đã xóa. Retrieval trả top-k có distance nhưng chưa lọc ngưỡng phù hợp trong hàm đã xem. Giá/tồn kho có thể cũ.
- Tạo pipeline thêm/sửa/xóa có version; live lookup dữ liệu thương mại trước khi trả lời. Chọn ngưỡng/retrieval strategy bằng bộ đánh giá, không bịa threshold để đủ checklist.
- Rà intent trùng PHP/Python, memory nhiều lượt, phủ định, fallback, prompt injection từ user/document, giới hạn đầu vào/concurrency/timeouts và lỗi trả ra người dùng.
- `CustomerScoringService` là `rules-v1.0`: mô tả đúng là rule-based scoring, có giải thích điểm; không quảng bá là mô hình học máy đã được chứng minh.
- Đạt: bộ câu hỏi tiếng Việt cố định có expected/source và tiêu chí duyệt; test sản phẩm xóa/hết hàng/đổi giá, không có dữ liệu, chính sách cũ, service down, quyền chat; có số đo latency/chất lượng. Không tự kết luận tư vấn chuyên môn mỹ phẩm/y tế chính xác chỉ vì LLM trả lời trôi chảy.

### F11 — Tiền, kho, đối soát và tác vụ nền [P1; cần đánh giá sâu]

- Rà toàn bộ checkout/cancel/payment/return/wallet, `bin/process-outbox.php`, expiry, marketing, scoring scheduler và WebSocket queue.
- Xác minh idempotency tạo đơn, thứ tự khóa/deadlock/retry, payment đến sau expiry, manual mark paid, số dư và audit trail. Đây là yêu cầu xác minh, không mặc định tất cả đang lỗi.
- WebSocket hiện dùng file JSON queue; đánh giá race, mất tin khi restart và phân tách filesystem container. Persistent notification phải là nguồn tin bền vững.
- Có đối soát giao dịch chưa khớp/thất bại, retry hữu hạn, nơi giữ lỗi cần xử lý, heartbeat và alert. Không ghi log thành công khi job chỉ bỏ qua lỗi.
- Đạt: test đồng thời/fault/replay và đối chiếu tổng tiền/kho; không mất dữ liệu giao dịch khi notification/AI chết. R2/R3/R5/R7 đạt.

### F12 — Chuẩn hóa kiến trúc và hợp đồng code [P2]

- Tách nghiệp vụ khỏi view ở các vùng đã ổn định; thống nhất data access, validation, auth, response và tên định danh. PDO/mysqli cùng tồn tại là nợ kỹ thuật cần kế hoạch, không phải lý do đổi toàn bộ ngay.
- Giảm code trùng và file nhiều trách nhiệm; giữ tương thích URL/hành vi. Không đổi tên hàng loạt `Oders`/`Categorys` khi chưa kiểm kê caller.
- Đạt: cấu trúc trách nhiệm rõ, không thêm cách tính nghiệp vụ thứ hai, regression suite trước/sau refactor đều đạt. Mỗi refactor là hạng mục riêng.

### F13 — Catalog chuyên nghiệp và vận hành sản phẩm [P2]

- Thiết kế SKU/biến thể, dung tích/màu, thuộc tính loại da/thành phần, nguồn gốc, lô/hạn dùng, inventory movement theo nghiệp vụ đã duyệt.
- Đảm bảo giá/tồn kho theo đúng đơn vị bán; snapshot đơn đủ thông tin; admin có kiểm tra dữ liệu và thông báo lỗi rõ.
- Đạt: thêm/sửa/ngừng bán biến thể không phá cart/đơn/AI; truy xuất lịch sử nhập/xuất được; migration và test liên quan đạt.

### F14 — UX, SEO, accessibility và nội dung [P2]

- Bộ lọc `View_Product.php` dùng if/elseif nên chưa phối hợp category/brand/search. Chuẩn hóa query contract, filter/sort/pagination và trạng thái rỗng.
- Rà `lang=en`, title chung, metadata/canonical/structured data/sitemap, asset tải thừa, responsive và keyboard bằng browser thực tế. Không tự báo điểm Lighthouse chưa đo.
- Không phơi thuật ngữ CRM như “khách hàng tiềm năng” cho khách nếu chưa có lý do sản phẩm. Hoàn thiện thông tin doanh nghiệp, liên hệ, chính sách và liên kết thương hiệu bằng nội dung chủ dự án xác nhận; không bịa pháp nhân.
- Guest checkout hoặc thay đổi chính sách đăng nhập phải được quyết định riêng; không tự sửa khi đang xử lý filter/SEO.
- Đạt: browser E2E desktop/mobile, lỗi/rỗng/loading, bàn phím và nội dung chính sách khớp backend; đo hiệu năng trên môi trường ghi rõ. R6 đạt.

### F15 — CI, quan sát hệ thống và tiêu chuẩn phát hành [P1; xây dần, nghiệm thu cuối]

- CI hiện có lint/audit/smoke nhưng chưa chứng minh runtime/integration đầy đủ; AI build check không kiểm tra import lúc chạy.
- Bổ sung test database cô lập, migrations mới/nâng cấp, endpoint quyền, commerce integration, Docker runtime smoke và AI fixture phù hợp. Test source-string chỉ giữ như kiểm tra phụ, không thay test hành vi.
- Thiết lập log có cấu trúc/redaction, mã tương quan request, readiness/liveness, metrics và alert cho lỗi tiền/outbox/scheduler. Không log nguyên chat/chứng từ mặc định.
- Viết runbook cài mới, nâng cấp, khôi phục, đối soát, backup/restore DB và upload, vận hành model/ingest, rollback tương thích schema.
- Đạt: người khác làm theo tài liệu dựng được hệ thống; pipeline reproducible; lỗi runtime chặn release; phần cần credential ngoài ghi rõ trạng thái chưa kiểm chứng. R0–R7 theo phạm vi release đạt.

## 8. Trình tự triển khai và điểm dừng

1. F00 và nền test cần thiết của F15.
2. F01, F02, F04, F05, F06 theo dependency map; lần lượt từng hạng mục.
3. Chốt hợp đồng nghiệp vụ F07 và thiết kế dữ liệu cần thiết F08, sau đó sửa F03. Các migration nhỏ bắt buộc cho P0 được làm cùng bản sửa P0, không phải chờ toàn bộ F08.
4. Hoàn thiện F08, F09 và F11; kiểm chứng cài mới/nâng cấp/khôi phục.
5. F10, F12, F13, F14 theo phụ thuộc; mỗi bước giữ nguyên invariant đã được duyệt.
6. Hoàn tất F15 và nghiệm thu tích hợp.

Nếu một ID quá lớn, chia thành `F09.1`, `F09.2`... với tiêu chí riêng và dependency rõ. Chỉ đóng ID cha khi mọi phần bắt buộc đạt. Không sửa toàn bộ backlog trong một patch lớn.

Trạng thái hợp lệ: `TODO`, `VERIFYING`, `WAITING_DECISION`, `IN_PROGRESS`, `BLOCKED`, `READY_FOR_REVIEW`, `DONE`, `REOPENED`.

Phân biệt ba mốc: **code/config hoàn thành**, **staging được kiểm chứng**, **production được chủ dự án cho phép triển khai**. Đạt mốc trước không tự động đạt mốc sau.

## 9. Sổ theo dõi và mẫu báo cáo bắt buộc

Khi bắt đầu sửa, tạo `docs/THEO_DOI_CHUAN_HOA.md`. Không ghi đè tài liệu yêu cầu này để xóa tiêu chí chưa đạt.

Sổ theo dõi phải có bảng:

| ID | Trạng thái | Nhãn bằng chứng | Phụ thuộc/quyết định | File thay đổi | Test bảo vệ | Kết quả gần nhất | Hồi quy liên quan |
|---|---|---|---|---|---|---|---|
| F00 | TODO | Chưa xác minh lại | — | — | — | Chưa chạy | — |

Mỗi lần hoàn thành một hạng mục, báo cáo:

```text
ID và vấn đề:
Hành vi trước / hành vi đúng:
Bằng chứng nguyên nhân:
Quyết định nghiệp vụ áp dụng:
File và hợp đồng thay đổi:
Ảnh hưởng tới UI / API / DB / AI / job / Docker:
Tests thêm hoặc cập nhật:
Lệnh kiểm tra + môi trường + kết quả:
Các lỗi đã sửa được kiểm tra hồi quy lần này:
Ca chưa chạy và lý do:
Migration / backfill / backup / cách khôi phục:
Rủi ro còn lại:
Trạng thái và hạng mục tiếp theo:
```

Ghi lịch sử lần chạy kiểm tra quan trọng theo thời gian. Nếu không commit, dùng danh sách diff/patch hoặc định danh bản làm việc để gắn kết quả với đúng code. Không tự tạo commit nếu chưa được giao quy trình Git tương ứng.

## 10. Điều kiện nghiệm thu cuối

- [ ] Tất cả P0 đã đóng bằng test hành vi; không còn bypass quyền hoặc lỗi tiền đã biết chưa giải quyết.
- [ ] F07/F08/F09/F11/F15 đạt; các yêu cầu còn lại trong phạm vi chuẩn hóa đã hoàn thành hoặc chủ dự án chấp nhận rõ phần hoãn. Không tự hoãn vì ngân sách.
- [ ] Full regression suite phù hợp release đạt trên đúng revision; không bỏ qua test thất bại để lấy pipeline xanh.
- [ ] Database mới và nâng cấp từ fixture lịch sử đều chạy; schema và seed độc lập dữ liệu cá nhân.
- [ ] Docker build + runtime đã kiểm chứng; startup không phụ thuộc đường dẫn Windows/file ngoài image không được tài liệu hóa.
- [ ] Web, scheduler, realtime, AI và ingest có topology production rõ ràng và contract thống nhất.
- [ ] Backup/restore dữ liệu và upload đã diễn tập; rollback có xét tương thích migration, không chỉ đổi image cũ.
- [ ] Giá, tồn kho, voucher, shipping, refund và nội dung AI/chính sách đã đối chiếu.
- [ ] Có browser E2E, kiểm tra quyền, concurrency và failure scenarios cho các luồng trọng yếu.
- [ ] Image/repository/log không chứa secret hoặc dữ liệu riêng tư ngoài phạm vi cho phép.
- [ ] Các kiểm tra dịch vụ ngoài chưa thực hiện được liệt kê riêng; không gọi toàn hệ thống production-ready nếu còn blocker.
- [ ] Báo cáo cuối nêu kết quả thực tế, giới hạn, hướng dẫn chạy và công việc cần chủ dự án quyết định. Public/deploy production vẫn cần ủy quyền riêng.

## 11. Prompt giao việc gợi ý cho GPT-5.6 SOL

> Đọc `docs/KE_HOACH_CHUAN_HOA_VA_QUY_TAC_SUA_LOI.md` và hướng dẫn repository hiện có. Tôi giao bạn chuẩn hóa dự án theo phạm vi và quy tắc trong tài liệu. Bắt đầu F00, lập baseline và sổ theo dõi; xác minh lại từng phát hiện trước khi sửa. Mỗi lần sửa một hạng mục, kiểm tra trực tiếp, tính thống nhất và hồi quy các hạng mục đã sửa chịu ảnh hưởng rồi mới chuyển tiếp. Nếu B làm A hỏng, mở lại A và xử lý trước khi làm C. Không tự cắt chức năng, đổi kiến trúc lớn, sửa nghiệp vụ tiền khi chưa chốt, dùng dữ liệu thật để test hoặc public hệ thống. Không tuyên bố đạt khi chỉ có lint/build hoặc khi chưa chạy được test cần thiết. Tiếp tục tự chủ các công việc đã được giao; khi cần quyết định nghiệp vụ, đưa phương án cụ thể và chờ quyết định đối với phần phụ thuộc.
