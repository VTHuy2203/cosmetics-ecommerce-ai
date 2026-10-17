# Chạy Mơ Shop local

Mở PowerShell tại thư mục dự án và chạy:

```powershell
.\run-local.ps1
```

Lệnh trên tự bật Apache, MySQL, Ollama, AI API, WebSocket và scheduler; sau đó mở trang client. Nếu model Ollama chưa có, model được tải nền và website vẫn hoạt động bình thường.

Các lệnh vận hành:

```powershell
.\status-local.ps1
.\stop-local.ps1
.\run-local.ps1 -NoBrowser
```

URL:

- Client: <http://localhost/TTTT/>
- Admin: <http://localhost/TTTT/admin>
- AI health: <http://127.0.0.1:8010/ping>
- Ollama: <http://127.0.0.1:11434/api/tags>

Log nằm trong `logs/local/`; mỗi dịch vụ có file `*.out.log` và `*.error.log` riêng. PID do dự án quản lý nằm trong `.runtime/`. `stop-local.ps1` chỉ dừng tiến trình có PID và command line khớp với tiến trình do `run-local.ps1` tạo.

Sau khi có migration mới, chạy thủ công nếu cần:

```powershell
C:\laragon\bin\php\php-8.4.1-Win32-vs17-x64\php.exe TTTT\bin\migrate.php
```

Môi trường web và CLI local dùng PHP 8.4.1 Thread Safe; CI và Docker production dùng nhánh PHP 8.4.
