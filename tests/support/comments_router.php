<?php
declare(strict_types=1);
// Test-only router, never copied into the production document root/image.
require_once __DIR__ . '/environment.php';
require_once dirname(__DIR__, 2) . '/TTTT/config/bootstrap.php';
if (PHP_SAPI !== 'cli-server' || ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    http_response_code(404); exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/fixture-session') {
    require_once dirname(__DIR__, 2) . '/TTTT/config/security.php';
    $fixture = $_GET['as'] ?? '';
    $_SESSION = ['_csrf_token' => bin2hex(random_bytes(32))];
    if ($fixture === 'buyer') { $_SESSION += ['user'=>'buyer','IDNguoiDung'=>1]; }
    elseif ($fixture === 'admin') { $_SESSION += ['admin_user'=>'admin','admin_id'=>3,'admin_role'=>'admin']; }
    elseif ($fixture === 'admin_combined') { $_SESSION += ['admin_user'=>'admin','admin_id'=>3,'admin_role'=>'admin','user'=>'buyer','IDNguoiDung'=>1]; }
    elseif ($fixture === 'staff') { $_SESSION += ['admin_user'=>'staff','admin_id'=>4,'admin_role'=>'employee']; }
    elseif ($fixture === 'employee_ui') { $_SESSION += ['user'=>'staff','IDNguoiDung'=>4,'user_role'=>'employee']; }
    elseif ($fixture === 'manager') { $_SESSION += ['admin_user'=>'manager','admin_id'=>5,'admin_role'=>'manager']; }
    elseif ($fixture === 'shipper') { $_SESSION += ['user'=>'shipper','IDNguoiDung'=>6]; }
    header('Content-Type: application/json');
    echo json_encode(['csrf' => $_SESSION['_csrf_token']]); exit;
}
if ($path === '/fixture-appointment') {
    $database = app_pdo();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $database->exec("DELETE FROM chat_appointments WHERE session_id LIKE 'f06-appointment-%'");
        $database->exec("INSERT INTO chat_appointments (user_id,session_id,appt_at,note) VALUES
            (1,'f06-appointment-past',DATE_SUB(NOW(), INTERVAL 1 DAY),'past'),
            (1,'f06-appointment-future',DATE_ADD(NOW(), INTERVAL 1 DAY),'future')");
    }
    $rows = $database->query("SELECT id,session_id FROM chat_appointments WHERE session_id LIKE 'f06-appointment-%'")->fetchAll();
    header('Content-Type: application/json'); echo json_encode(['rows'=>$rows]); exit;
}
if ($path === '/fixture-employee-role') {
    $role = (string) ($_GET['role'] ?? '');
    if (!in_array($role, ['employee', 'customer'], true)) { http_response_code(422); exit; }
    $statement = app_pdo()->prepare('UPDATE khachhang SET Role=? WHERE ID_KhachHang=4');
    $statement->execute([$role]);
    header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit;
}
if ($path === '/fixture-session-state') {
    header('Content-Type: application/json');
    echo json_encode(['admin'=>isset($_SESSION['admin_user']),'customer'=>isset($_SESSION['user'])]); exit;
}
if ($path === '/fixture-product-state') {
    $database = app_pdo();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $database->exec('UPDATE sanpham SET NoiBat=0 WHERE IDSanPham=1');
    }
    header('Content-Type: application/json');
    echo json_encode(['featured' => (int) $database->query('SELECT NoiBat FROM sanpham WHERE IDSanPham=1')->fetchColumn()]);
    exit;
}
if ($path === '/fixture-admin-role') {
    $role = (string) ($_GET['role'] ?? '');
    if (!in_array($role, ['admin', 'customer'], true)) { http_response_code(422); exit; }
    $database = app_pdo();
    $statement = $database->prepare('UPDATE khachhang SET Role=? WHERE ID_KhachHang=3');
    $statement->execute([$role]);
    header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit;
}
if ($path === '/fixture-cart-state') {
    require_once dirname(__DIR__, 2) . '/TTTT/config/security.php';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $_SESSION['cart'] = [[1, 'Sản phẩm thử', '', 1, 100000]];
    }
    header('Content-Type: application/json');
    echo json_encode(['quantity'=>(int) ($_SESSION['cart'][0][3] ?? 0)]); exit;
}
$pages = [
    '/TTTT/WebBanHangMyPham/src/View/Detail_Product.php',
    '/TTTT/AdminManage/src/Comments/Manage_Comments.php',
    '/TTTT/WebBanHangMyPham/src/Processing/XuLy_BinhLuan.php',
    '/TTTT/WebBanHangMyPham/src/Processing/Realtime_Ticket.php',
    '/TTTT/AdminManage/src/Comments/Delete_Comment.php',
    '/TTTT/AdminManage/src/Categorys/Manage_Category.php',
    '/TTTT/AdminManage/src/Categorys/Delete_Category.php',
    '/TTTT/AdminManage/src/Brands/Manage_Brand.php',
    '/TTTT/AdminManage/src/Brands/Delete_Brand.php',
    '/TTTT/AdminManage/src/Products/Manage_Product.php',
    '/TTTT/AdminManage/src/Products/Delete_Product.php',
    '/TTTT/WebBanHangMyPham/src/View/Cart.php',
    '/TTTT/WebBanHangMyPham/src/Processing/Chat_Send.php',
    '/TTTT/WebBanHangMyPham/src/Processing/Process_Contact.php',
    '/TTTT/WebBanHangMyPham/src/Processing/XuLy_DanhGia.php',
    '/TTTT/AdminManage/src/Users/Processing_Update_User.php',
    '/TTTT/AdminManage/src/Processing/Admin_Logout.php',
    '/TTTT/WebBanHangMyPham/src/Processing/Employee_Delete_Appointment.php',
];
if (in_array($path, $pages, true)) {
    $file = dirname(__DIR__, 2) . $path;
    chdir(dirname($file)); require $file; return;
}
if (is_string($path) && preg_match('~^/TTTT/.*\.(?:css|js|png|jpe?g|gif|webp|woff2?|ttf|svg)$~i', $path)
    && !str_contains(rawurldecode($path), '..')) { return false; }
http_response_code(404);
