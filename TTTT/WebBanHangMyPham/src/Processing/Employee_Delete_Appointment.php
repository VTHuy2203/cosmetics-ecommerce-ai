<?php
/**
 * Employee Delete Appointment Processing
 * Handles deletion of chat appointments (usually past ones)
 */

session_start();
include("../DB_Connect.php");

// Check if user is logged in and is an employee
if (!isset($_SESSION['user']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    die(json_encode(['ok' => false, 'error' => 'Unauthorized']));
}

// Get appointment ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    die(json_encode(['ok' => false, 'error' => 'Invalid ID']));
}

// Use PDO for chat tables
try {
    $dsn = "mysql:host=127.0.0.1;dbname=webmipham1;charset=utf8mb4";
    $pdo = new PDO($dsn, "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Check if appointment exists
    $stmt = $pdo->prepare("SELECT id FROM chat_appointments WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        die(json_encode(['ok' => false, 'error' => 'Appointment not found']));
    }

    // Delete appointment
    $stmt = $pdo->prepare("DELETE FROM chat_appointments WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['ok' => true, 'message' => 'Đã xóa lịch hẹn thành công']);

} catch (Exception $e) {
    error_log("Employee_Delete_Appointment.php: Error: " . $e->getMessage());
    die(json_encode(['ok' => false, 'error' => 'Database error']));
}
?>