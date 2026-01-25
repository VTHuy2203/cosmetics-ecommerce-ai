<?php
// Get_Lead_Label.php
header('Content-Type: application/json; charset=utf-8');

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
function label_from_score($score, $has_appt=false){
  if ($has_appt) return "Đã đặt lịch";
  if ($score >= 80) return "Tiềm năng cao";
  if ($score >= 50) return "Cần chăm sóc";
  return "Không tiềm năng";
}

try{
  $pdo = pdo_connect($DEFAULTS);

  // GET by session_id or id
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $sid = isset($_GET['sid']) ? trim($_GET['sid']) : '';

  if (!$id && !$sid){ echo json_encode(["ok"=>false,"error"=>"missing id/sid"]); exit; }

  if ($id){
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id=:id");
    $stmt->execute([":id"=>$id]);
  }else{
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE session_id=:sid ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([":sid"=>$sid]);
  }
  $lead = $stmt->fetch();
  if (!$lead){ echo json_encode(["ok"=>false, "error"=>"lead not found"]); exit; }

  $label = label_from_score((int)$lead['score'], !empty($lead['appointment_at']));

  // allow update label manually by ?set=...
  if (isset($_GET['set']) && $_GET['set']!==''){
    $label = trim($_GET['set']);
    $pdo->prepare("UPDATE leads SET label=:l WHERE id=:id")->execute([":l"=>$label, ":id"=>$lead['id']]);
  }

  echo json_encode(["ok"=>true, "lead_id"=>$lead['id'], "score"=>(int)$lead['score'], "label"=>$label], JSON_UNESCAPED_UNICODE);
}catch(Exception $e){
  echo json_encode(["ok"=>false, "error"=>$e->getMessage()]);
}
