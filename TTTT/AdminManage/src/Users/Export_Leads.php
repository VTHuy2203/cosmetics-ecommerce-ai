<?php
// Admin/Export_Leads.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="leads_export.csv"');

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

$out = fopen('php://output', 'w');
fputcsv($out, ['lead_id','user_id','session_id','name','phone','email','product_interest','score','label','appointment_at','last_interaction','last_message','created_at','updated_at']);

$stmt = $pdo->query("SELECT * FROM leads ORDER BY id DESC");
while($r = $stmt->fetch()){
  fputcsv($out, [
    $r['id'], $r['user_id'], $r['session_id'],
    $r['name'], $r['phone'], $r['email'], $r['product_interest'],
    $r['score'], $r['label'], $r['appointment_at'], $r['last_interaction'],
    preg_replace('/\s+/u',' ', trim($r['last_message'] ?? '')),
    $r['created_at'], $r['updated_at']
  ]);
}
fclose($out);
