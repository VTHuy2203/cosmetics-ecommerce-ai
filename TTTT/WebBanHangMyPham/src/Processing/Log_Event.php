<?php
// Log_Event.php
// Dùng: include_once 'Log_Event.php'; log_chat_event($uid,$sid,$msg,'user', ['appointment_at'=>..., 'product_interest'=>...]);

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

// --- NLP rất gọn để trích ý định/thông tin ---
function detect_intent_and_entities($text){
  $t = mb_strtolower(trim($text), 'UTF-8');

  $intent = 'other';
  if (preg_match('/\b(có|ok|yes|đặt lịch|đăng ký|tư vấn|hẹn|gọi)\b/u', $t)) $intent = 'booking_yes';
  if (preg_match('/\b(không|ko|no|khỏi|thôi|để sau)\b/u', $t)) $intent = 'booking_no';
  if (preg_match('/\b(mua|giá|giảm|ship|đơn|size|ml|tuýp|mẫu|loại)\b/u', $t)) $intent = 'product';

  $phone = null;
  if (preg_match('/(0|\+84)\d{8,10}/u', $t, $m)) $phone = $m[0];

  $email = null;
  if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', $text, $m2)) $email = $m2[0];

  // trích sản phẩm quan tâm rất thô (có thể mở rộng)
  $product_interest = null;
  $dict = ['sữa rửa mặt','kem dưỡng','serum','kem chống nắng','son','tẩy trang','toner','mask','dưỡng ẩm','trắng da','trị mụn'];
  foreach($dict as $kw){
    if (mb_strpos($t, $kw, 0, 'UTF-8') !== false) { $product_interest = $kw; break; }
  }

  return [$intent, $product_interest, $phone, $email];
}

function upsert_lead($pdo, $user_id, $session_id, $fields){
  // tìm lead
  $stmt = $pdo->prepare("SELECT id FROM leads WHERE session_id=:sid AND ((:uid IS NULL AND user_id IS NULL) OR user_id=:uid) LIMIT 1");
  $stmt->execute([":sid"=>$session_id, ":uid"=>$user_id ?: null]);
  $row = $stmt->fetch();

  if ($row){
    // update
    $sets = []; $params = [":id"=>$row['id']];
    foreach($fields as $k=>$v){
      $sets[] = "$k = :$k";
      $params[":$k"] = $v;
    }
    if ($sets){
      $sql = "UPDATE leads SET ".implode(",", $sets).", updated_at=NOW() WHERE id=:id";
      $pdo->prepare($sql)->execute($params);
    }
    return $row['id'];
  }else{
    // insert
    $cols = ['user_id','session_id']; $vals=[':uid',':sid']; $params=[":uid"=>$user_id?:null, ":sid"=>$session_id];
    foreach($fields as $k=>$v){ $cols[]=$k; $vals[]=":$k"; $params[":$k"]=$v; }
    $sql = "INSERT INTO leads(".implode(",",$cols).") VALUES(".implode(",",$vals).")";
    $pdo->prepare($sql)->execute($params);
    return (int)$pdo->lastInsertId();
  }
}

function log_chat_event($user_id, $session_id, $message, $direction='user', $extras=[]){
  $pdo = pdo_connect($GLOBALS['DEFAULTS']);

  list($intent, $product_interest, $phone, $email) = detect_intent_and_entities($message);

  // extras override
  if (!empty($extras['intent'])) $intent = $extras['intent'];
  if (!empty($extras['product_interest'])) $product_interest = $extras['product_interest'];
  if (!empty($extras['phone'])) $phone = $extras['phone'];
  if (!empty($extras['email'])) $email = $extras['email'];

  // 1) lưu event
  $pdo->prepare("INSERT INTO lead_events(user_id, session_id, direction, message, intent, product_interest, phone, email)
                 VALUES(:uid,:sid,:dir,:msg,:intent,:pi,:ph,:em)")
      ->execute([
        ":uid"=>$user_id?:null, ":sid"=>$session_id, ":dir"=>$direction, ":msg"=>$message,
        ":intent"=>$intent, ":pi"=>$product_interest, ":ph"=>$phone, ":em"=>$email
      ]);

  // 2) cập nhật lead
  $fields = [
    "last_message" => $message,
    "last_interaction" => date('Y-m-d H:i:s'),
  ];
  if ($product_interest) $fields["product_interest"] = $product_interest;
  if (!empty($extras['appointment_at'])) $fields["appointment_at"] = $extras['appointment_at'];
  if (!empty($extras['appointment_note'])) $fields["appointment_note"] = $extras['appointment_note'];
  if ($phone) $fields["phone"] = $phone;
  if ($email) $fields["email"] = $email;

  $lead_id = upsert_lead($pdo, $user_id, $session_id, $fields);

  return [
    "ok"=>true,
    "lead_id"=>$lead_id,
    "intent"=>$intent,
    "product_interest"=>$product_interest,
    "phone"=>$phone,
    "email"=>$email
  ];
}

// ---- Nếu gọi trực tiếp bằng POST JSON ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = file_get_contents('php://input');
  $j = json_decode($raw, true) ?: [];
  $user_id = isset($j['user_id']) ? (int)$j['user_id'] : null;
  $session_id = isset($j['session_id']) ? trim($j['session_id']) : '';
  $message = isset($j['message']) ? (string)$j['message'] : '';
  $direction = isset($j['direction']) ? (string)$j['direction'] : 'user';
  $extras = isset($j['extras']) && is_array($j['extras']) ? $j['extras'] : [];

  if (!$session_id || !$message) { echo json_encode(["ok"=>false, "error"=>"missing session_id/message"]); exit; }

  echo json_encode(log_chat_event($user_id,$session_id,$message,$direction,$extras), JSON_UNESCAPED_UNICODE);
  exit;
}
