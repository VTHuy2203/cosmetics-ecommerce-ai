<?php
include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ho_ten']) && isset($_POST['email']) && isset($_POST['noi_dung'])) {
    require_csrf();
    if (!auth_rate_limit('contact', 5, 900)) {
        header('Location: ../View/View_contact.php?error=rate_limited', true, 303);
        exit();
    }
    $ho_ten = trim((string) $_POST['ho_ten']);
    $email = strtolower(trim((string) $_POST['email']));
    $sdt = trim((string) ($_POST['sdt'] ?? ''));
    $noi_dung = trim((string) $_POST['noi_dung']);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../View/View_contact.php?error=1');
        exit();
    }
    
    // Insert contact message
    if ($ho_ten === '' || mb_strlen($ho_ten) > 255 || mb_strlen($sdt) > 20 || $noi_dung === '' || mb_strlen($noi_dung) > 5000) {
        header('Location: ../View/View_contact.php?error=1', true, 303);
        exit();
    }
    $statement = $conn->prepare('INSERT INTO contact_messages (HoTen, Email, SoDienThoai, NoiDung) VALUES (?, ?, ?, ?)');
    $statement->bind_param('ssss', $ho_ten, $email, $sdt, $noi_dung);
    $result = $statement->execute();
    
    if ($result) {
        header('Location: ../View/View_contact.php?success=1', true, 303);
        exit();
    } else {
        header('Location: ../View/View_contact.php?error=1', true, 303);
        exit();
    }
} else {
    header('Location: ../View/View_contact.php?error=1');
    exit();
}
?>

