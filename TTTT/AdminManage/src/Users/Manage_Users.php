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
  $sql = "SELECT * FROM khachhang WHERE Role = 'customer' OR Role IS NULL";
  $result = mysqli_query($conn, $sql);
  if (!$result) { die("Có lỗi xảy ra: " . mysqli_error($conn)); }

  // One scoring source of truth. khachhang is a compatibility fallback until
  // the first scoring run has created a current snapshot.
  $leadMap = [];
  try {
    $pdo = app_pdo();
    $scoreStmt = $pdo->query("SELECT user_id, segment AS label, intent_score AS score,
                                    spam_risk_score, value_score, value_tier, reason_codes,
                                    scoring_version, calculated_at, manual_segment
                             FROM customer_scores_current WHERE user_id IS NOT NULL");
    while ($scoreRow = $scoreStmt->fetch()) {
      $leadMap[(int) $scoreRow['user_id']] = $scoreRow;
    }

    $fallback = $pdo->query("SELECT ID_KhachHang AS user_id, customer_label AS label,
                                   lead_score AS score, spam_risk_score, customer_value_score AS value_score,
                                   customer_value_tier AS value_tier, scoring_version, label_updated_at AS calculated_at
                            FROM khachhang WHERE Role = 'customer' OR Role IS NULL");
    while ($scoreRow = $fallback->fetch()) {
      $uid = (int) $scoreRow['user_id'];
      if (!isset($leadMap[$uid])) {
        $leadMap[$uid] = $scoreRow;
      }
    }
  } catch (Exception $e) {
    error_log('Manage_Users scoring data error: ' . $e->getMessage());
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
                  <option value="follow-up" <?php echo (isset($_GET['lead']) && $_GET['lead']==='follow-up')?'selected':''; ?>>Đang theo dõi</option>
                  <option value="normal"    <?php echo (isset($_GET['lead']) && $_GET['lead']==='normal')   ?'selected':''; ?>>Bình thường</option>
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
                               .(in_array($admin_role, ['admin', 'manager'], true)
                                    ? admin_deposit_form((int) $p['ID_Nap'], true) . ' ' . admin_deposit_form((int) $p['ID_Nap'], false)
                                    : '<span class="badge bg-secondary">Chỉ quản trị viên xử lý</span>')
                               .'</div>';
                        }
                      }
                      echo '</td>';

                      // Lead badge + explainable score from the centralized engine.
                      $badge = '<span class="badge bg-secondary">N/A</span>';
                      $prob  = '';
                      $leadId = 'lead-' . $uid;
                      
                      if ($lead) {
                          $label = $lead['label'] ?? 'normal';
                          if ($label === 'potential') {
                            $badge = '<span class="badge bg-primary" id="' . $leadId . '-badge">Tiềm năng</span>';
                          } else if ($label === 'follow-up' || $label === 'neutral') {
                            $badge = '<span class="badge bg-warning text-dark" id="' . $leadId . '-badge">Đang theo dõi</span>';
                          } else if ($label === 'normal') {
                            $badge = '<span class="badge bg-secondary" id="' . $leadId . '-badge">Bình thường</span>';
                          } else if ($label==='spam') {
                            $badge = '<span class="badge bg-danger" id="' . $leadId . '-badge">Spam</span>';
                          } else {
                            $badge = '<span class="badge bg-secondary" id="' . $leadId . '-badge">Chưa phân loại</span>';
                          }

                          $score = max(0, min(100, (int) ($lead['score'] ?? 0)));
                          $reasonText = '';
                          if (!empty($lead['reason_codes'])) {
                            $decodedReasons = json_decode((string) $lead['reason_codes'], true);
                            if (is_array($decodedReasons)) {
                              $reasonLines = [];
                              foreach ($decodedReasons as $reason) {
                                $reasonLines[] = sprintf('%+d %s', (int) ($reason['points'] ?? 0), (string) ($reason['label'] ?? ''));
                              }
                              $reasonText = implode("\n", $reasonLines);
                            }
                          }
                          $prob = '<span id="' . $leadId . '-prob" title="' . htmlspecialchars($reasonText, ENT_QUOTES) . '">' . $score . ' điểm</span>';
                          $valueTier = htmlspecialchars((string) ($lead['value_tier'] ?? 'new'));
                          $prob .= '<div class="small text-muted">Giá trị: ' . $valueTier . '</div>';
                      } else {
                          // Chưa có đủ dữ liệu để engine phân loại.
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
                      }

                      echo ' <td id="' . $leadId . '">'.$badge.' '.$prob.' <div class="small text-muted">'.htmlspecialchars($need).'</div></td>';

                      // Action buttons
                      $segmentButton = in_array($admin_role, ['admin', 'manager'], true)
                        ? '<button class="btn btn-sm btn-outline-secondary" onclick="overrideSegment('.$uid.')" title="Điều chỉnh phân loại">Phân loại</button>'
                        : '';
                      $deleteButton = in_array($admin_role, ['admin', 'manager'], true)
                        ? '<button class="btn btn-sm btn-outline-danger" onclick="deleteUser('.$uid.', \''.htmlspecialchars($ten, ENT_QUOTES).'\')" title="Xóa khách hàng">Xóa</button>'
                        : '';
                      echo '<td>
                        <a class="btn btn-sm btn-outline-primary" href="Customer_Detail.php?id='.$uid.'">Xem</a>
                        '.$segmentButton.'
                        '.$deleteButton.'
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
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-Token': <?php echo json_encode(csrf_token()); ?>
        }
      })
      .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          throw new Error(data.error || 'Máy chủ không thể xử lý yêu cầu');
        }
        return data;
      })
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
        alert('Không thể phân tích khách hàng: ' + err.message);
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
    async function overrideSegment(userId) {
      const segment = prompt('Nhập nhãn: auto, normal, follow-up, potential hoặc spam');
      if (segment === null) return;
      const normalized = segment.trim().toLowerCase();
      if (!['auto', 'normal', 'follow-up', 'potential', 'spam'].includes(normalized)) {
        alert('Nhãn không hợp lệ.');
        return;
      }
      const reason = normalized === 'auto' ? 'automatic' : prompt('Nhập lý do thay đổi nhãn:');
      if (reason === null || (normalized !== 'auto' && reason.trim().length < 3)) {
        alert('Cần nhập lý do ít nhất 3 ký tự.');
        return;
      }
      try {
        const body = new URLSearchParams({user_id: String(userId), segment: normalized, reason: reason.trim()});
        const response = await fetch('Update_Customer_Segment.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'Accept': 'application/json',
            'X-CSRF-Token': <?php echo json_encode(csrf_token()); ?>
          },
          body
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) throw new Error(data.error || 'Không thể cập nhật');
        alert(data.message || 'Đã cập nhật phân loại.');
        location.reload();
      } catch (error) {
        alert('Lỗi: ' + error.message);
      }
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
          'X-CSRF-Token': <?php echo json_encode(csrf_token()); ?>
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
