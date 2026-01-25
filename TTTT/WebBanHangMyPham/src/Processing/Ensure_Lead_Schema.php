<?php
// Ensure_Lead_Schema.php
header('Content-Type: application/json; charset=utf-8');

$DEFAULTS = [
  "DB_HOST"    => "127.0.0.1",
  "DB_NAME"    => "webmipham1",
  "DB_USER"    => "root",
  "DB_PASS"    => "",
  "DB_CHARSET" => "utf8mb4",
];

function pdo_connect($cfg){
  try{
    $dsn = "mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset={$cfg['DB_CHARSET']}";
    return new PDO($dsn, $cfg['DB_USER'], $cfg['DB_PASS'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
  }catch(Exception $e){
    http_response_code(500);
    echo json_encode(["ok"=>false, "error"=>"DB connect fail"]); exit;
  }
}

try{
  $pdo = pdo_connect($DEFAULTS);

  // leads: thông tin tổng hợp mỗi khách (theo user_id+session_id)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS leads (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NULL,
      session_id VARCHAR(128) NOT NULL,
      name VARCHAR(255) NULL,
      phone VARCHAR(50) NULL,
      email VARCHAR(255) NULL,
      product_interest VARCHAR(255) NULL,
      last_message TEXT NULL,
      last_interaction DATETIME NULL,
      appointment_at DATETIME NULL,
      appointment_note VARCHAR(255) NULL,
      score INT NOT NULL DEFAULT 0,
      label VARCHAR(50) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_lead_user_sid (session_id, user_id),
      KEY idx_user (user_id),
      KEY idx_label (label),
      KEY idx_last_interaction (last_interaction),
      KEY idx_appointment (appointment_at),
      KEY idx_product (product_interest)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // lead_events: log từng câu chat (user/bot) + phân tích NLP cơ bản
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS lead_events (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NULL,
      session_id VARCHAR(128) NOT NULL,
      direction ENUM('user','bot') NOT NULL,
      message TEXT NOT NULL,
      intent VARCHAR(50) NULL,        -- booking_yes / booking_no / info / product / other
      product_interest VARCHAR(255) NULL,
      phone VARCHAR(50) NULL,
      email VARCHAR(255) NULL,
      ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      KEY idx_session (session_id),
      KEY idx_user (user_id),
      KEY idx_intent (intent),
      FULLTEXT KEY ft_msg (message)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  echo json_encode(["ok"=>true, "msg"=>"Schema ensured"]);
}catch(Exception $e){
  echo json_encode(["ok"=>false, "error"=>$e->getMessage()]);
}
