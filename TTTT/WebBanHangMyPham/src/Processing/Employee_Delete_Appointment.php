<?php
/**
 * Employee Delete Appointment Processing
 * Handles deletion of chat appointments (usually past ones)
 */

require_once dirname(__DIR__, 3) . '/config/security.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
require_csrf(true);

// Get appointment ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'invalid_id'], 422);
}

try {
    $pdo = app_pdo();
    $employeeId = filter_var($_SESSION['IDNguoiDung'] ?? null, FILTER_VALIDATE_INT);
    $username = trim((string) ($_SESSION['user'] ?? ''));
    if (!$employeeId || $username === '') {
        json_response(['ok' => false, 'error' => 'unauthorized'], 401);
    }

    // Session roles can become stale. Authorize this destructive action using
    // the current database identity and role on every request.
    $roleStatement = $pdo->prepare(
        'SELECT Role FROM khachhang WHERE ID_KhachHang = ? AND TenDangNhap = ? LIMIT 1'
    );
    $roleStatement->execute([(int) $employeeId, $username]);
    if ($roleStatement->fetchColumn() !== 'employee') {
        json_response(['ok' => false, 'error' => 'forbidden'], 403);
    }

    // The employee screen exposes deletion only for past appointments. Keep
    // that state rule in the mutation itself so a forged ID cannot delete a
    // future booking.
    $stmt = $pdo->prepare('DELETE FROM chat_appointments WHERE id = ? AND appt_at < NOW()');
    $stmt->execute([$id]);
    if ($stmt->rowCount() !== 1) {
        json_response(['ok' => false, 'error' => 'appointment_not_found_or_not_past'], 404);
    }

    json_response(['ok' => true, 'message' => 'Đã xóa lịch hẹn thành công']);

} catch (Throwable $e) {
    error_log("Employee_Delete_Appointment.php: Error: " . $e->getMessage());
    json_response(['ok' => false, 'error' => 'database_error'], 500);
}
