<?php include("../Layouts/headHTML.php"); ?>

<?php
  // Kết nối cũ của dự án
  include("../DB_Connect.php");
  
  // Session is already started in headHTML.php, no need to start again
  
  // Access control is handled by Admin_Access_Check.php (included via headHTML.php)
  // Just read admin session info for display purposes
  $admin_role = $_SESSION['admin_role'] ?? null;
  $is_admin = isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true;

  // Danh sách KH
  $sql = "SELECT * FROM khachhang";
  $result = mysqli_query($conn, $sql);
  if (!$result) { die("Có lỗi xảy ra: " . mysqli_error($conn)); }

  // Nạp nhãn lead từ bảng customer_analysis (prioritize this) và leads (fallback)
  $leadMap = [];
  // Try PDO for customer_analysis table FIRST (new system)
  try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=webmipham1;charset=utf8mb4", "root", "", [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    // First try customer_analysis table - get latest data per user_id
    try {
      $caStmt = $pdo->query("SELECT user_id, label, has_appointment, product_interest, last_need 
                             FROM customer_analysis 
                             WHERE user_id IS NOT NULL 
                             ORDER BY updated_at DESC");
      while($ca = $caStmt->fetch()) {
        $uid = (int)$ca['user_id'];
        // Only update if we haven't seen this user_id yet (to get the latest record)
        if (!isset($leadMap[$uid])) {
          $label = $ca['label'] ?? 'spam';
          // Map customer_analysis labels: potential -> potential, follow-up -> neutral, spam -> spam
          $leadLabel = $label === 'potential' ? 'potential' : ($label === 'follow-up' ? 'neutral' : 'spam');
          $score = $label === 'potential' ? 90 : ($label === 'follow-up' ? 50 : 30);
          $leadMap[$uid] = [
            'label' => $leadLabel,
            'score' => $score,
            'probability' => $score / 100.0,
            'last_need' => $ca['last_need'] ?? null,
            'product_interest' => $ca['product_interest'] ?? null
          ];
        }
      }
    } catch (Exception $e) {
      // customer_analysis table might not exist, try leads table
      error_log("Manage_Users.php: Error fetching customer_analysis: " . $e->getMessage());
    }
    
    // Fallback to leads table if customer_analysis didn't have data
    if (empty($leadMap)) {
      try {
        $leadStmt = $pdo->query("SELECT user_id, label, score FROM leads WHERE user_id IS NOT NULL");
        while($lr = $leadStmt->fetch()) {
          $uid = (int)$lr['user_id'];
          if (!isset($leadMap[$uid])) {
            $leadMap[$uid] = [
              'label' => $lr['label'] ?? 'neutral',
              'score' => $lr['score'] ?? 0,
              'probability' => min(100, max(0, (int)$lr['score'])) / 100.0
            ];
          }
        }
      } catch (Exception $e) {
        // leads table might not exist
      }
    }
  } catch (Exception $e) {
    // PDO connection failed, try mysqli fallback
  }
  
  // Fallback to lead_scores if PDO failed
  if (empty($leadMap)) {
    $leadRes = mysqli_query($conn, "SELECT user_id, label, probability FROM lead_scores WHERE user_id IS NOT NULL");
    if ($leadRes) {
      while($lr = mysqli_fetch_assoc($leadRes)) {
        $uid = (int)$lr['user_id'];
        if (!isset($leadMap[$uid])) {
          $leadMap[$uid] = $lr;
        }
      }
    }
  }
?>

<body id="page-top">
  <div id="wrapper">
    <?php include("../Layouts/SlideBar.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include("../Layouts/TopBar.php"); ?>

        <div class="container-fluid">

          <h1 class="h3 mb-2 text-gray-800">Quản Lý khách hàng</h1>

          <!-- Actions -->
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
              <form method="get" class="d-inline">
                <label for="lead_filter">Lọc Lead:</label>
                <select id="lead_filter" name="lead" onchange="this.form.submit()">
                  <option value="">Tất cả</option>
                  <option value="potential" <?php echo (isset($_GET['lead']) && $_GET['lead']==='potential')?'selected':''; ?>>Tiềm năng</option>
                  <option value="neutral"   <?php echo (isset($_GET['lead']) && $_GET['lead']==='neutral')  ?'selected':''; ?>>Trung tính</option>
                  <option value="spam"      <?php echo (isset($_GET['lead']) && $_GET['lead']==='spam')     ?'selected':''; ?>>Spam</option>
                </select>
              </form>
            </div>
            <div>
              <button class="btn btn-sm btn-primary" id="btn-analyze" onclick="analyzeCustomers()">
                <span id="analyze-text">Phân tích khách hàng</span>
                <span id="analyze-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
              </button>
              <a class="btn btn-sm btn-outline-secondary" href="Export_Users_CSV.php<?php 
                $params = [];
                if (isset($_GET['lead']) && $_GET['lead'] !== '') $params['lead'] = $_GET['lead'];
                echo !empty($params) ? '?' . http_build_query($params) : '';
              ?>">Xuất CSV</a>
            </div>
          </div>

          <!-- Bảng khách hàng -->
          <div class="card shadow mb-4">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                  <thead>
                  <tr>
                    <th>STT</th>
                    <th>Tên</th>
                    <th>SDT</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th>Số dư</th>
                    <th>Nạp tiền chờ duyệt</th>
                    <th>Lead</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $stt = 1;
                  $filterLead = isset($_GET['lead']) ? $_GET['lead'] : '';

                  while ($row = mysqli_fetch_array($result)) {
                      $uid = (int)$row['ID_KhachHang'];

                      // nhãn lead
                      $lead = isset($leadMap[$uid]) ? $leadMap[$uid] : null;
                      if ($filterLead !== '' && ($lead===null || $lead['label'] !== $filterLead)) {
                          continue;
                      }

                      // chờ duyệt nạp tiền (GIỮ NGUYÊN)
                      $pending = mysqli_query($conn, "SELECT * FROM nap_tien WHERE ID_KhachHang='$uid' AND TrangThai=0 ORDER BY NgayTao DESC");

                      echo '<tr>';
                      echo ' <td>'.$stt.'</td>';

                      // Tên (bấm vào để tới trang chi tiết)
                      $ten = htmlspecialchars($row['HoVaTen']);
                      echo ' <td><a href="Customer_Detail.php?id='.$uid.'" class="text-decoration-none">'.$ten.'</a></td>';

                      echo ' <td>'.htmlspecialchars($row['SDT']).'</td>';
                      echo ' <td>'.htmlspecialchars($row['Email']).'</td>';
                      echo ' <td>'.htmlspecialchars($row['DiaChi']).'</td>';
                      echo ' <td>'.number_format(isset($row['SoDu'])?$row['SoDu']:0,0,',','.').' đ</td>';

                      echo ' <td>';
                      if ($pending) {
                        while ($p = mysqli_fetch_array($pending)) {
                          echo '<div class="mb-1">'
                               .number_format($p['SoTien'],0,',','.').' đ - '.htmlspecialchars($p['NoiDung'])
                               .' <a class="btn btn-sm btn-success" href="Confirm_Deposit.php?id='.$p['ID_Nap'].'">Xác nhận</a>'
                               .' <a class="btn btn-sm btn-outline-danger" href="Reject_Deposit.php?id='.$p['ID_Nap'].'">Từ chối</a></div>';
                        }
                      }
                      echo '</td>';

                      // Lead badge + % - Default to Potential if analyzed
                      $badge = '<span class="badge bg-secondary">N/A</span>';
                      $prob  = '';
                      $leadId = 'lead-' . $uid;
                      
                      if ($lead) {
                          $label = $lead['label'] ?? 'neutral';
                          // Map labels to Potential if analyzed
                          if ($label==='potential' || $label==='Tiềm năng cao' || $label==='Đã đặt lịch') {
                            $badge = '<span class="badge bg-primary" id="' . $leadId . '-badge">Potential</span>';
                          } else if ($label==='average' || $label==='Cần chăm sóc') {
                            $badge = '<span class="badge bg-info text-dark" id="' . $leadId . '-badge">Potential</span>';
                          } else if ($label==='normal') {
                            $badge = '<span class="badge bg-warning text-dark" id="' . $leadId . '-badge">Potential</span>';
                          } else if ($label==='spam') {
                            $badge = '<span class="badge bg-danger" id="' . $leadId . '-badge">Spam</span>';
                          } else {
                            // Default to Potential for analyzed customers
                            $badge = '<span class="badge bg-primary" id="' . $leadId . '-badge">Potential</span>';
                          }

                          $score = isset($lead['score']) ? (int)$lead['score'] : (isset($lead['probability']) ? round(floatval($lead['probability'])*100) : 90);
                          $prob = '<span id="' . $leadId . '-prob">' . $score . '%</span>';
                      } else {
                          // Not analyzed yet - will be set to Potential after analysis
                          $badge = '<span class="badge bg-secondary" id="' . $leadId . '-badge">Chưa phân tích</span>';
                      }

                      // nhu cầu gần nhất từ customer_analysis table (prioritize this)
                      $need = '';
                      // First try from leadMap (already loaded from customer_analysis)
                      if ($lead && isset($lead['last_need']) && $lead['last_need']) {
                        $need = $lead['last_need'];
                      } elseif ($lead && isset($lead['product_interest']) && $lead['product_interest']) {
                        $need = $lead['product_interest'];
                      } else {
                        // Fallback: query customer_analysis table directly
                        try {
                          if (isset($pdo)) {
                            $needStmt = $pdo->prepare("SELECT last_need, product_interest FROM customer_analysis WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
                            $needStmt->execute([$uid]);
                            $needRow = $needStmt->fetch();
                            if ($needRow) {
                              $need = $needRow['last_need'] ?: $needRow['product_interest'] ?: '';
                            }
                          }
                        } catch (Exception $e) {}
                        
                        // Final fallback to leads table
                        if (!$need) {
                          try {
                            if (isset($pdo)) {
                              $needStmt = $pdo->prepare("SELECT last_message, product_interest FROM leads WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
                              $needStmt->execute([$uid]);
                              $needRow = $needStmt->fetch();
                              if ($needRow) {
                                $need = $needRow['product_interest'] ?: $needRow['last_message'] ?: '';
                              }
                            }
                          } catch (Exception $e) {}
                        }
                        
                        // Final fallback to customer_needs table
                        if (!$need) {
                          $qn = mysqli_query($conn, "SELECT need_text FROM customer_needs WHERE user_id=$uid ORDER BY created_at DESC LIMIT 1");
                          if ($qn && ($nr = mysqli_fetch_assoc($qn))) { $need = $nr['need_text']; }
                        }
                      }

                      echo ' <td id="' . $leadId . '">'.$badge.' '.$prob.' <div class="small text-muted">'.htmlspecialchars($need).'</div></td>';

                      // Action buttons
                      echo ' <td>
                        <a class="btn btn-sm btn-outline-primary" href="Customer_Detail.php?id='.$uid.'">Xem</a>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser('.$uid.', \''.htmlspecialchars($ten, ENT_QUOTES).'\')" title="Xóa khách hàng">Xóa</button>
                      </td>';

                      echo '</tr>';
                      $stt++;
                  }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div><!-- /.container-fluid -->
      </div><!-- End of Main Content -->

      <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div><!-- End content-wrapper -->
  </div><!-- End wrapper -->

  <?php include("../Layouts/Link.php"); ?>
  
  <script>
    // Analyze customers inline
    function analyzeCustomers() {
      const btn = document.getElementById('btn-analyze');
      const text = document.getElementById('analyze-text');
      const spinner = document.getElementById('analyze-spinner');
      
      btn.disabled = true;
      text.textContent = 'Đang phân tích...';
      spinner.classList.remove('d-none');
      
      // Call Score_Lead.php to analyze all leads
      fetch('/TTTT/WebBanHangMyPham/src/Processing/Score_Lead.php', {
        method: 'GET',
        credentials: 'same-origin'
      })
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          // Update all lead displays
          updateLeadDisplays();
          // Reload page after 1 second to show updated data
          setTimeout(() => {
            location.reload();
          }, 1000);
        } else {
          alert('Lỗi: ' + (data.error || 'Không thể phân tích'));
          btn.disabled = false;
          text.textContent = 'Phân tích khách hàng';
          spinner.classList.add ('d-none');
        }
      })
      .catch(err => {
        console.error('Analysis error:', err);
        alert('Lỗi khi phân tích: ' + err.message);
        btn.disabled = false;
        text.textContent = 'Phân tích khách hàng';
        spinner.classList.add('d-none');
      });
    }
    
    // Update lead displays in real-time
    function updateLeadDisplays() {
      // Fetch updated lead data
      fetch('?ajax=1&' + new URLSearchParams(window.location.search), {
        credentials: 'same-origin'
      })
      .then(res => res.text())
      .then(html => {
        // Parse and update lead badges
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const rows = doc.querySelectorAll('tbody tr');
        
        rows.forEach((row, index) => {
          const leadCell = row.querySelector('td:nth-child(8)');
          if (leadCell) {
            const currentRow = document.querySelectorAll('tbody tr')[index];
            if (currentRow) {
              const currentLeadCell = currentRow.querySelector('td:nth-child(8)');
              if (currentLeadCell) {
                currentLeadCell.innerHTML = leadCell.innerHTML;
              }
            }
          }
        });
      })
      .catch(err => console.error('Update error:', err));
    }
    
    // Delete user
    function deleteUser(userId, userName) {
      if (!confirm('Bạn có chắc chắn muốn xóa khách hàng "' + userName + '" (ID: ' + userId + ')?\n\nHành động này không thể hoàn tác!')) {
        return;
      }
      
      fetch('Delete_User.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin',
        body: 'user_id=' + userId
      })
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          alert('Đã xóa khách hàng thành công!');
          location.reload();
        } else {
          alert('Lỗi: ' + (data.error || 'Không thể xóa khách hàng'));
        }
      })
      .catch(err => {
        console.error('Delete error:', err);
        alert('Lỗi khi xóa: ' + err.message);
      });
    }
    
    // Auto-refresh lead data every 30 seconds
    setInterval(() => {
      updateLeadDisplays();
    }, 30000);
  </script>
</body>
</html>
