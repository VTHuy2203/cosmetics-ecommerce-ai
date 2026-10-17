<?php
declare(strict_types=1);
require_once '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once '../Layouts/Admin_Access_Check.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit; }
require_csrf();
if (!in_array($_SESSION['admin_role'] ?? null, ['admin', 'manager'], true)) { http_response_code(403); exit; }
$id = (int) ($_POST['id'] ?? 0);
$conn->begin_transaction();
try {
    $lock = $conn->prepare('SELECT ID_KhachHang, SoTien, TrangThai FROM nap_tien WHERE ID_Nap = ? FOR UPDATE');
    $lock->bind_param('i', $id);
    $lock->execute();
    $deposit = $lock->get_result()->fetch_assoc();
    if (!$deposit || (int) $deposit['TrangThai'] !== 0 || (int) $deposit['SoTien'] <= 0) {
        throw new RuntimeException('Invalid deposit state.');
    }
    $manualReference = 'MANUAL-' . (int) $_SESSION['admin_id'] . '-' . time();
    $update = $conn->prepare(
        'UPDATE nap_tien SET TrangThai = 1, MaThamChieuThanhToan = ?, ThanhToanLuc = NOW()
         WHERE ID_Nap = ? AND TrangThai = 0'
    );
    $update->bind_param('si', $manualReference, $id);
    $update->execute();
    if ($update->affected_rows !== 1) {
        throw new RuntimeException('Deposit was concurrently processed.');
    }
    $credit = $conn->prepare('UPDATE khachhang SET SoDu = SoDu + ? WHERE ID_KhachHang = ?');
    $amount = (int) $deposit['SoTien'];
    $userId = (int) $deposit['ID_KhachHang'];
    $credit->bind_param('ii', $amount, $userId);
    $credit->execute();
    $payload = json_encode(['deposit_id' => $id, 'user_id' => $userId, 'amount' => $amount, 'admin_id' => (int) $_SESSION['admin_id']]);
    $event = $conn->prepare("INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload) VALUES ('wallet.deposit_confirmed', 'deposit', ?, ?)");
    $aggregateId = (string) $id;
    $event->bind_param('ss', $aggregateId, $payload);
    $event->execute();
    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Deposit confirmation failed: ' . $error->getMessage());
    http_response_code(409); exit('Không thể xác nhận giao dịch.');
}
header('Location: Manage_Users.php');
exit;
