<?php
// Admin/Analytics.php
header('Content-Type: text/html; charset=utf-8');

$DEFAULTS = [
  "DB_HOST"    => "127.0.0.1",
  "DB_NAME"    => "webmipham1",
  "DB_USER"    => "root",
  "DB_PASS"    => "",
  "DB_CHARSET" => "utf8mb4",
];

function pdo_connect($cfg){
  $dsn = "mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset={$cfg['DB_CHARSET']}";
  return new PDO($dsn, $cfg['DB_USER'], $cfg['DB_PASS'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
}

$pdo = pdo_connect($DEFAULTS);
$byLabel = $pdo->query("SELECT label, COUNT(*) c FROM leads GROUP BY label ORDER BY c DESC")->fetchAll();
$byProduct = $pdo->query("
  SELECT COALESCE(product_interest,'(không rõ)') p, COUNT(*) c 
  FROM leads 
  GROUP BY p ORDER BY c DESC LIMIT 10
")->fetchAll();
// Check which timestamp column exists
$columns = $pdo->query("SHOW COLUMNS FROM leads LIKE '%created_at%'")->fetchAll();
$has_created_at = !empty($columns);
$order_col = $has_created_at ? "created_at" : "ThoiGianTao";

$recent = $pdo->query("
  SELECT id, user_id, session_id, product_interest, label, score, last_interaction, appointment_at
  FROM leads ORDER BY IFNULL(last_interaction, $order_col) DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Analytics</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:20px}
table{border-collapse:collapse;width:100%;margin:12px 0}
th,td{border:1px solid #ddd;padding:8px} th{background:#f3f4f6}
.card{border:1px solid #e5e7eb;padding:12px;border-radius:8px;margin-bottom:16px}
h2{margin:0 0 8px 0}
.badge{display:inline-block;padding:2px 8px;border-radius:12px;background:#eef2ff}
</style>
</head><body>
<h1>Analytics (Leads)</h1>

<div class="card">
  <h2>Leads theo nhãn</h2>
  <table><tr><th>Nhãn</th><th>Số lượng</th></tr>
    <?php foreach($byLabel as $r){ echo "<tr><td>".htmlspecialchars($r['label']?:'(chưa có)')."</td><td>{$r['c']}</td></tr>"; } ?>
  </table>
</div>

<div class="card">
  <h2>Top 10 sản phẩm quan tâm</h2>
  <table><tr><th>Sản phẩm</th><th>Số lead</th></tr>
    <?php foreach($byProduct as $r){ echo "<tr><td>".htmlspecialchars($r['p'])."</td><td>{$r['c']}</td></tr>"; } ?>
  </table>
</div>

<div class="card">
  <h2>10 lead tương tác gần nhất</h2>
  <table>
    <tr><th>ID</th><th>User</th><th>Session</th><th>Sản phẩm</th><th>Nhãn</th><th>Score</th><th>Last</th><th>Hẹn</th></tr>
    <?php foreach($recent as $r){
      echo "<tr>
        <td>{$r['id']}</td>
        <td>".($r['user_id'] ?? '-')."</td>
        <td>".htmlspecialchars($r['session_id'])."</td>
        <td>".htmlspecialchars($r['product_interest'] ?: '')."</td>
        <td><span class='badge'>".htmlspecialchars($r['label'] ?: '')."</span></td>
        <td>{$r['score']}</td>
        <td>".htmlspecialchars($r['last_interaction'] ?: '')."</td>
        <td>".htmlspecialchars($r['appointment_at'] ?: '')."</td>
      </tr>";
    } ?>
  </table>
</div>

</body></html>
