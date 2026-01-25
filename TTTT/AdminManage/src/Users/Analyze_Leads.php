<?php
// Admin/Analyze_Leads.php
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

$label = isset($_GET['label']) ? trim($_GET['label']) : '';
$prod  = isset($_GET['product']) ? trim($_GET['product']) : '';
$kw    = isset($_GET['q']) ? trim($_GET['q']) : '';

// Check which timestamp column exists
$columns = $pdo->query("SHOW COLUMNS FROM leads LIKE '%created_at%'")->fetchAll();
$has_created_at = !empty($columns);
$order_col = $has_created_at ? "created_at" : "ThoiGianTao";

$sql = "SELECT * FROM leads WHERE 1=1";
$params = [];
if ($label!==''){ $sql.=" AND label=:l"; $params[":l"]=$label; }
if ($prod!==''){ $sql.=" AND product_interest=:p"; $params[":p"]=$prod; }
if ($kw!==''){ 
  // Check which name columns exist
  $name_cols = $pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_COLUMN);
  $search_cols = [];
  if (in_array('session_id', $name_cols)) $search_cols[] = "session_id";
  if (in_array('phone', $name_cols)) $search_cols[] = "phone";
  if (in_array('SDT', $name_cols)) $search_cols[] = "SDT";
  if (in_array('email', $name_cols)) $search_cols[] = "email";
  if (in_array('Email', $name_cols)) $search_cols[] = "Email";
  if (!empty($search_cols)) {
    $sql.=" AND (" . implode(" LIKE :k OR ", $search_cols) . " LIKE :k)";
    $params[":k"]="%$kw%";
  }
}
$sql.=" ORDER BY IFNULL(last_interaction, $order_col) DESC LIMIT 200";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$leads = $stmt->fetchAll();

function get_conv($pdo,$sid,$uid){
  // Try lead_events first, fallback to chat_messages
  try {
    $st = $pdo->prepare("SELECT direction,message,ts FROM lead_events WHERE session_id=:sid AND ((:uid IS NULL AND user_id IS NULL) OR user_id=:uid) ORDER BY id ASC LIMIT 200");
    $st->execute([":sid"=>$sid, ":uid"=>$uid ?: null]);
    $result = $st->fetchAll();
    if (!empty($result)) return $result;
  } catch (Exception $e) {
    // lead_events table might not exist
  }
  
  // Fallback to chat_messages
  try {
    $st = $pdo->prepare("SELECT direction,message,created_at as ts FROM chat_messages WHERE session_id=:sid AND ((:uid IS NULL AND user_id IS NULL) OR user_id=:uid) ORDER BY id ASC LIMIT 200");
    $st->execute([":sid"=>$sid, ":uid"=>$uid ?: null]);
    return $st->fetchAll();
  } catch (Exception $e) {
    return [];
  }
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Analyze Leads</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:20px}
table{border-collapse:collapse;width:100%;margin:12px 0}
th,td{border:1px solid #ddd;padding:8px} th{background:#f3f4f6}
.card{border:1px solid #e5e7eb;padding:12px;border-radius:8px;margin-bottom:16px}
.badge{display:inline-block;padding:2px 8px;border-radius:12px;background:#eef2ff}
.conv{background:#fafafa;border:1px solid #eee;padding:8px;border-radius:6px;max-height:240px;overflow:auto}
.msg{margin:6px 0}
.msg.user .who{color:#2563eb;font-weight:600}
.msg.bot .who{color:#047857;font-weight:600}
</style>
</head><body>
<h1>Analyze Leads</h1>

<form method="GET" class="card">
  <label>Nhãn: <input type="text" name="label" value="<?=htmlspecialchars($label)?>"></label>
  <label style="margin-left:12px">Sản phẩm: <input type="text" name="product" value="<?=htmlspecialchars($prod)?>"></label>
  <label style="margin-left:12px">Từ khoá (sid/phone/email): <input type="text" name="q" value="<?=htmlspecialchars($kw)?>"></label>
  <button type="submit">Lọc</button>
</form>

<table>
  <tr>
    <th>ID</th><th>User</th><th>Session</th><th>Sản phẩm</th>
    <th>Score</th><th>Label</th><th>Last</th><th>Hẹn</th><th>Hội thoại</th>
  </tr>
  <?php foreach($leads as $r){ $conv = get_conv($pdo,$r['session_id'],$r['user_id']); ?>
    <tr>
      <td><?=$r['id']?></td>
      <td><?= $r['user_id'] ?? '-' ?></td>
      <td><?= htmlspecialchars($r['session_id']) ?></td>
      <td><?= htmlspecialchars($r['product_interest'] ?: '') ?></td>
      <td><?= (int)$r['score'] ?></td>
      <td><span class="badge"><?= htmlspecialchars($r['label'] ?: '') ?></span></td>
      <td><?= htmlspecialchars($r['last_interaction'] ?: '') ?></td>
      <td><?= htmlspecialchars($r['appointment_at'] ?: '') ?></td>
      <td style="min-width:360px">
        <div class="conv">
          <?php foreach($conv as $m){
            $cls = $m['direction'] === 'user' ? 'msg user' : 'msg bot';
            $who = $m['direction'] === 'user' ? 'Khách' : 'Bot';
            echo "<div class='$cls'><span class='who'>$who:</span> ".nl2br(htmlspecialchars($m['message']))."<div style='color:#888;font-size:12px'>".$m['ts']."</div></div>";
          } ?>
        </div>
      </td>
    </tr>
  <?php } ?>
</table>
</body></html>
