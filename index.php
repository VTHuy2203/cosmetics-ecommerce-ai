<?php
declare(strict_types=1);

// Local launcher for Laragon's "Web" button. Only reachable from this machine.
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

function port_open(int $port): bool
{
    $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.3);
    if ($socket === false) {
        return false;
    }
    fclose($socket);
    return true;
}

$sections = [
    'Website' => [
        ['Trang khách hàng', '/TTTT/', 'Cửa hàng Mơ Shop', 80],
        ['Trang quản trị', '/TTTT/admin', 'Admin, nhân viên, quản lý', 80],
        ['Health check', '/TTTT/health.php', 'Kiểm tra web và database', 80],
    ],
    'Dịch vụ AI và realtime' => [
        ['AI API (FastAPI)', 'http://127.0.0.1:8010/ping', 'Chatbot RAG, cổng 8010', 8010],
        ['AI API docs', 'http://127.0.0.1:8010/docs', 'Swagger của AI API', 8010],
        ['Ollama', 'http://127.0.0.1:11434/api/tags', 'Danh sách model LLM, cổng 11434', 11434],
        ['WebSocket', null, 'Thông báo realtime, cổng 8080', 8080],
    ],
    'Công cụ' => [
        ['phpMyAdmin', '/phpmyadmin', 'Quản lý database MySQL, cổng 3306', 3306],
    ],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mơ Shop Local</title>
    <style>
        :root { --bg: #f6f7fb; --card: #fff; --text: #1f2330; --muted: #6b7080; --line: #e4e6ee; --accent: #d63384; --ok: #1a9c5b; --off: #c0392b; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
        main { max-width: 760px; margin: 0 auto; padding: 32px 16px; }
        h1 { margin: 0 0 4px; font-size: 26px; color: var(--accent); }
        .sub { margin: 0 0 24px; color: var(--muted); }
        h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin: 24px 0 8px; }
        .list { background: var(--card); border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-top: 1px solid var(--line); color: inherit; text-decoration: none; }
        .item:first-child { border-top: 0; }
        a.item:hover { background: #fdf2f8; }
        .dot { width: 10px; height: 10px; border-radius: 50%; flex: none; }
        .dot.ok { background: var(--ok); } .dot.off { background: var(--off); }
        .name { font-weight: 600; }
        .desc { color: var(--muted); font-size: 14px; }
        .state { margin-left: auto; font-size: 13px; color: var(--muted); white-space: nowrap; }
        .hint { margin-top: 24px; font-size: 14px; color: var(--muted); }
        code { background: #eceef4; padding: 1px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<main>
    <h1>Mơ Shop · Local</h1>
    <p class="sub">Chọn mục cần mở. Đèn xanh là dịch vụ đang chạy.</p>

    <?php foreach ($sections as $title => $items): ?>
        <h2><?= htmlspecialchars($title) ?></h2>
        <div class="list">
            <?php foreach ($items as [$name, $url, $desc, $port]): ?>
                <?php $up = port_open($port); ?>
                <?php if ($url !== null): ?><a class="item" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"><?php else: ?><div class="item"><?php endif; ?>
                    <span class="dot <?= $up ? 'ok' : 'off' ?>"></span>
                    <span><span class="name"><?= htmlspecialchars($name) ?></span><br><span class="desc"><?= htmlspecialchars($desc) ?></span></span>
                    <span class="state"><?= $up ? 'Đang chạy' : 'Chưa chạy' ?></span>
                <?= $url !== null ? '</a>' : '</div>' ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <p class="hint">Bật AI, WebSocket và scheduler bằng <code>.\run-local.ps1</code>, tắt bằng <code>.\stop-local.ps1</code>.</p>
</main>
</body>
</html>
