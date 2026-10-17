<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__, 3) . '/WebBanHangMyPham/src/Services/CustomerScoringService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
$role = (string) ($_SESSION['admin_role'] ?? '');
if (!isset($_SESSION['admin_id']) || !in_array($role, ['admin', 'manager'], true)) {
    json_response(['ok' => false, 'error' => 'Bạn không có quyền thay đổi phân loại.'], 403);
}
require_csrf(true);

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$segment = trim((string) ($_POST['segment'] ?? ''));
$reason = trim((string) ($_POST['reason'] ?? ''));
if (!$userId || !in_array($segment, ['auto', 'normal', 'follow-up', 'potential', 'spam'], true)) {
    json_response(['ok' => false, 'error' => 'Dữ liệu phân loại không hợp lệ.'], 422);
}
if ($segment !== 'auto' && mb_strlen($reason) < 3) {
    json_response(['ok' => false, 'error' => 'Vui lòng nhập lý do thay đổi nhãn.'], 422);
}

try {
    $pdo = app_pdo();
    $scoring = new CustomerScoringService($pdo);
    $scoring->scoreSubject((int) $userId, null);

    if ($segment === 'auto') {
        $statement = $pdo->prepare(
            'UPDATE customer_scores_current SET manual_segment=NULL,manual_reason=NULL,manual_by=NULL,manual_at=NULL WHERE subject_key=?'
        );
        $statement->execute(['user:' . $userId]);
        $score = $scoring->scoreSubject((int) $userId, null);
        json_response(['ok' => true, 'segment' => $score['segment'], 'message' => 'Đã trở lại phân loại tự động.']);
    }

    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'UPDATE customer_scores_current SET segment=?,manual_segment=?,manual_reason=?,manual_by=?,manual_at=NOW(),calculated_at=NOW() WHERE subject_key=?'
    );
    $statement->execute([$segment, $segment, mb_substr($reason, 0, 500), (int) $_SESSION['admin_id'], 'user:' . $userId]);
    $pdo->prepare('UPDATE khachhang SET customer_label=?,label_updated_at=NOW() WHERE ID_KhachHang=?')->execute([$segment, $userId]);
    $pdo->prepare(
        'INSERT INTO customer_score_snapshots(subject_key,user_id,session_id,intent_score,spam_risk_score,value_score,segment,value_tier,reason_codes,scoring_version)
         SELECT subject_key,user_id,session_id,intent_score,spam_risk_score,value_score,segment,value_tier,reason_codes,CONCAT(scoring_version,\'-manual\')
         FROM customer_scores_current WHERE subject_key=?'
    )->execute(['user:' . $userId]);
    $pdo->commit();

    json_response(['ok' => true, 'segment' => $segment, 'message' => 'Đã lưu phân loại thủ công.']);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Customer segment override failed: ' . $error->getMessage());
    json_response(['ok' => false, 'error' => app_is_production() ? 'Không thể cập nhật phân loại.' : $error->getMessage()], 500);
}
