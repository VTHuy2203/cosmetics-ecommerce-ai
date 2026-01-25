<?php
// Score_Lead.php
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

function label_from_score($score, $has_appt=false, $has_product_query=false, $has_any_message=false, $weekly_purchase=0){
  /**
   * NEW CUSTOMER CLASSIFICATION LOGIC (Updated):
   *
   * 1. SPAM: Only chatting without booking or purchasing
   * 2. FOLLOW-UP (Theo dõi): Made a booking appointment
   * 3. POTENTIAL (Tiềm năng): Purchased > 5,000,000 VND in 1 week
   *
   * Note: The weekly purchase check is done in Customer_Weekly_Evaluation.php
   * This function handles the chat-based classification as a fallback
   */

  // If has significant weekly purchase (>5M VND), mark as potential
  if ($weekly_purchase >= 5000000) {
    return "potential";
  }

  // If has booking appointment, mark as follow-up
  if ($has_appt) {
    return "follow-up";
  }

  // If only chatting without booking or purchase, mark as spam
  if ($has_any_message && !$has_appt && $weekly_purchase == 0) {
    return "spam";
  }

  // Fallback: use score-based logic for legacy compatibility
  if ($score >= 80) return "potential";
  if ($score >= 50) return "follow-up";
  if ($score > 0) return "follow-up";
  return "spam"; // No interaction at all
}

function compute_score_for_lead($pdo, $lead){
  $score = 0;
  $has_product_query = false;
  $has_any_message = false;

  // tín hiệu mạnh
  if (!empty($lead['appointment_at'])) $score += 60;
  if (!empty($lead['phone'])) $score += 20;
  if (!empty($lead['email'])) $score += 10;
  if (!empty($lead['product_interest'])) {
    $score += 15;
    $has_product_query = true;
  }

  // Check chat_messages for product queries
  try {
    $msg_stmt = $pdo->prepare("SELECT message, direction FROM chat_messages 
                                 WHERE session_id=:sid AND ((:uid IS NULL AND user_id IS NULL) OR user_id=:uid) 
                                 ORDER BY id DESC LIMIT 50");
    $msg_stmt->execute([":sid"=>$lead['session_id'], ":uid"=>$lead['user_id'] ?: null]);
    $msg_rows = $msg_stmt->fetchAll();
    
    $product_keywords = ['mua', 'tìm', 'son', 'kem', 'mặt nạ', 'serum', 'chống nắng', 'dưỡng', 
                         'makeup', 'sản phẩm', 'giá', 'spf', 'moisturizer', 'lipstick'];
    
    foreach($msg_rows as $msg_row) {
      if ($msg_row['direction'] === 'user') {
        $has_any_message = true;
        $msg_lower = mb_strtolower($msg_row['message'] ?? '', 'UTF-8');
        
        // Check if message contains product-related keywords
        foreach ($product_keywords as $kw) {
          if (mb_strpos($msg_lower, $kw, 0, 'UTF-8') !== false) {
            $has_product_query = true;
            $score += 10;
            break;
          }
        }
      }
    }
  } catch (Exception $e) {
    // Table might not exist
  }

  // xem events gần đây
  try {
    $stmt = $pdo->prepare("SELECT intent, message, ts FROM lead_events WHERE session_id=:sid AND ((:uid IS NULL AND user_id IS NULL) OR user_id=:uid) ORDER BY id DESC LIMIT 30");
    $stmt->execute([":sid"=>$lead['session_id'], ":uid"=>$lead['user_id'] ?: null]);
    $rows = $stmt->fetchAll();

    foreach($rows as $r){
      if ($r['intent']==='booking_yes') $score += 30;
      if ($r['intent']==='product') {
        $score += 10;
        $has_product_query = true;
      }

      $t = mb_strtolower($r['message'] ?? '', 'UTF-8');
      foreach (['mua','đặt','giá','ship','khuyến mãi','giảm'] as $kw){
        if (mb_strpos($t,$kw,0,'UTF-8') !== false){ $score += 3; }
      }
    }
  } catch (Exception $e) {
    // Table might not exist
  }

  // clamp
  if ($score > 100) $score = 100;
  
  // Return score and flags
  return [
    'score' => $score,
    'has_product_query' => $has_product_query,
    'has_any_message' => $has_any_message,
    'has_appointment' => !empty($lead['appointment_at'])
  ];
}

try{
  $pdo = pdo_connect($DEFAULTS);

  // Ensure customer_analysis table exists
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_analysis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        session_id VARCHAR(128) NOT NULL,
        label ENUM('spam','follow-up','potential') NOT NULL DEFAULT 'spam',
        product_interest TEXT NULL,
        last_need TEXT NULL,
        message_count INT NOT NULL DEFAULT 0,
        has_appointment TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        INDEX idx_label (label)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Exception $e) {
    error_log("Score_Lead.php: Schema error: " . $e->getMessage());
  }

  // Analyze customer_analysis table FIRST (new system)
  $updated = 0;
  try {
    $stmt = $pdo->query("SELECT * FROM customer_analysis ORDER BY updated_at DESC LIMIT 1000");
    $customers = $stmt->fetchAll();
    
    foreach($customers as $customer) {
      $user_id = $customer['user_id'];
      $session_id = $customer['session_id'];
      $has_appt = $customer['has_appointment'] > 0;
      $has_product = !empty($customer['product_interest']);
      $message_count = $customer['message_count'] ?? 0;
      
      // Determine label based on customer_analysis data
      $label = 'spam'; // Default
      if ($has_appt && $has_product) {
        $label = 'potential'; // Asked about product AND scheduled consultation
      } elseif ($has_product && !$has_appt) {
        $label = 'follow-up'; // Asked about product but NO consultation
      } elseif ($message_count > 0 && !$has_product) {
        $label = 'spam'; // Only opens chat but no product question
      }
      
      // Update customer_analysis label
      try {
        $updateStmt = $pdo->prepare("UPDATE customer_analysis SET label = ? WHERE id = ?");
        $updateStmt->execute([$label, $customer['id']]);
        $updated++;
      } catch (Exception $e) {
        error_log("Score_Lead.php: Error updating customer_analysis: " . $e->getMessage());
      }
      
      // Also update leads table if user_id exists (for compatibility)
      if ($user_id) {
        try {
          // Check if leads table exists
          $leadsCheck = $pdo->query("SHOW TABLES LIKE 'leads'")->fetch();
          if ($leadsCheck) {
            // Map labels: potential -> potential, follow-up -> neutral, spam -> spam
            $leadLabel = $label === 'potential' ? 'potential' : ($label === 'follow-up' ? 'neutral' : 'spam');
            $score = $label === 'potential' ? 90 : ($label === 'follow-up' ? 50 : 30);
            
            // Try to update leads table
            try {
              $leadUpdate = $pdo->prepare("UPDATE leads SET label = ?, score = ? WHERE user_id = ?");
              $leadUpdate->execute([$leadLabel, $score, $user_id]);
            } catch (Exception $e) {
              // leads table might not have label/score columns, skip
            }
          }
        } catch (Exception $e) {
          // leads table might not exist, skip
        }
      }
    }
  } catch (Exception $e) {
    error_log("Score_Lead.php: Error analyzing customer_analysis: " . $e->getMessage());
  }

  // Also analyze leads table for compatibility (old system)
  try {
    // Check which timestamp column exists in leads table
    $columns = $pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_COLUMN);
    $has_updated_at = in_array('updated_at', $columns);
    $has_created_at = in_array('created_at', $columns);
    
    // Build ORDER BY clause based on available columns
    $orderBy = "id DESC"; // Default fallback
    if ($has_updated_at) {
      $orderBy = "updated_at DESC";
    } else if ($has_created_at) {
      $orderBy = "created_at DESC";
    } else if (in_array('ThoiGianTao', $columns)) {
      $orderBy = "ThoiGianTao DESC";
    }

    // Chấm điểm cho tất cả leads chưa label ổn định hoặc updated gần đây
    $stmt = $pdo->query("SELECT * FROM leads ORDER BY $orderBy LIMIT 1000");
    $leads = $stmt->fetchAll();

    foreach($leads as $lead){
      $result = compute_score_for_lead($pdo, $lead);
      $score = $result['score'];
      $has_product_query = $result['has_product_query'];
      $has_any_message = $result['has_any_message'];
      $has_appt = $result['has_appointment'];
      
      $label = label_from_score($score, $has_appt, $has_product_query, $has_any_message);
      
      // Adjust score based on label
      if ($label === 'potential') {
        $score = max($score, 90); // Minimum 90 for potential
      } else if ($label === 'neutral') {
        $score = max($score, 50); // Minimum 50 for neutral
      } else if ($label === 'spam') {
        $score = min($score, 30); // Maximum 30 for spam
      }
      
      // Build UPDATE query based on available columns
      if ($has_updated_at) {
        $pdo->prepare("UPDATE leads SET score=:s, label=:l, updated_at=NOW() WHERE id=:id")
            ->execute([":s"=>$score, ":l"=>$label, ":id"=>$lead['id']]);
      } else {
        // If updated_at doesn't exist, just update score and label
        $pdo->prepare("UPDATE leads SET score=:s, label=:l WHERE id=:id")
            ->execute([":s"=>$score, ":l"=>$label, ":id"=>$lead['id']]);
      }
      $updated++;
    }
  } catch (Exception $e) {
    // leads table might not exist, skip
  }
  
  // Also update lead_scores table for compatibility (if it exists)
  try {
    // Check if lead_scores table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'lead_scores'")->fetch();
    if ($tableCheck) {
      // Get session_id from leads table
      $leadScoresStmt = $pdo->query("SELECT user_id, session_id FROM leads WHERE user_id IS NOT NULL AND label='potential'");
      while($ls = $leadScoresStmt->fetch()) {
        $uid = $ls['user_id'];
        $sid = $ls['session_id'] ?? '';
        
        // Try to insert/update - handle different schemas
        try {
          $pdo->prepare("
            INSERT INTO lead_scores (user_id, session_id, label, probability, updated_at)
            VALUES (:uid, :sid, 'potential', 0.9, NOW())
            ON DUPLICATE KEY UPDATE
              label = 'potential',
              probability = 0.9,
              updated_at = NOW()
          ")->execute([":uid" => $uid, ":sid" => $sid]);
        } catch (Exception $e) {
          // Try without session_id if column doesn't exist
          try {
            $pdo->prepare("
              INSERT INTO lead_scores (user_id, label, probability, updated_at)
              VALUES (:uid, 'potential', 0.9, NOW())
              ON DUPLICATE KEY UPDATE
                label = 'potential',
                probability = 0.9,
                updated_at = NOW()
            ")->execute([":uid" => $uid]);
          } catch (Exception $e2) {
            // Schema mismatch, skip
          }
        }
      }
    }
  } catch (Exception $e) {
    // lead_scores table might not exist, that's ok
  }

  echo json_encode(["ok"=>true, "updated"=>$updated], JSON_UNESCAPED_UNICODE);
}catch(Exception $e){
  echo json_encode(["ok"=>false, "error"=>$e->getMessage()]);
}
