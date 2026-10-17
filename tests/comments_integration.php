<?php
declare(strict_types=1);
require_once __DIR__ . '/support/environment.php';
require_once dirname(__DIR__) . '/TTTT/config/bootstrap.php';
$db = app_pdo();
// Synthetic, minimal contract fixtures, only in the guarded isolated database.
$schema = [
    'CREATE TABLE IF NOT EXISTS khachhang (ID_KhachHang INT PRIMARY KEY, TenDangNhap VARCHAR(50), HoVaTen VARCHAR(100), Role VARCHAR(20))',
    'CREATE TABLE IF NOT EXISTS sanpham (IDSanPham INT PRIMARY KEY, TenSanPham VARCHAR(100))',
    'CREATE TABLE IF NOT EXISTS donhang (ID_DonHang INT PRIMARY KEY, ID_KhachHang INT, TrangThai INT, GhiChu TEXT)',
    'CREATE TABLE IF NOT EXISTS donhangchitiet (ID_DonHang INT, ID_SanPham INT)',
    'CREATE TABLE IF NOT EXISTS return_refund_requests (ID_Request INT PRIMARY KEY, ID_DonHang INT, ID_KhachHang INT, ID_SanPham INT, TrangThai VARCHAR(20))',
    'CREATE TABLE IF NOT EXISTS binhluan (ID_BinhLuan INT AUTO_INCREMENT PRIMARY KEY, ID_KhachHang INT, ID_SanPham INT, NoiDungBinhLuan VARCHAR(1000), DaMuaHang INT)',
];
foreach ($schema as $sql) { $db->exec($sql . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); }
$tables = ['binhluan', 'return_refund_requests', 'donhangchitiet', 'donhang', 'sanpham', 'khachhang'];
foreach ($tables as $table) { $db->exec('DELETE FROM ' . $table); }
$db->exec("INSERT INTO khachhang (ID_KhachHang,TenDangNhap,HoVaTen,Role) VALUES (1,'buyer','Khách <script>bad</script>','customer'),(2,'visitor','Khách thử','customer'),(3,'admin','Admin thử','admin'),(4,'employee','Nhân viên thử','employee'),(5,'returned','Khách hoàn','customer')");
$db->exec("INSERT INTO sanpham (IDSanPham,TenSanPham) VALUES (1,'Sản phẩm thử'),(2,'Chưa mua')");
$db->exec("INSERT INTO donhang (ID_DonHang,ID_KhachHang,TrangThai,GhiChu) VALUES (1,1,3,''),(2,5,3,'')");
$db->exec('INSERT INTO donhangchitiet (ID_DonHang,ID_SanPham) VALUES (1,1),(2,1)');
$db->exec("INSERT INTO return_refund_requests (ID_Request,ID_DonHang,ID_KhachHang,ID_SanPham,TrangThai) VALUES (1,2,5,1,'approved')");

function commentRequest(string $target, array $session, array $post = [], string $method = 'POST'): array
{
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-d', 'extension=mysqli', '-d', 'extension=pdo_mysql', __DIR__ . '/support/comment_request.php'],
        [['pipe','r'],['pipe','w'],['pipe','w']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start endpoint test process.'); }
    fwrite($pipes[0], json_encode(compact('target','session','post','method'), JSON_UNESCAPED_UNICODE));
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exit = proc_close($process);
    $result = json_decode($output, true);
    return is_array($result) ? $result : ['status' => 500, 'body' => '', 'process_exit' => $exit];
}

$failures = [];
$check = static function (bool $ok, string $label) use (&$failures): void {
    if (!$ok) { $failures[] = $label; }
};
$buyer = ['user'=>'buyer','IDNguoiDung'=>1,'_csrf_token'=>'fixture-csrf'];
$admin = ['admin_user'=>'admin','admin_id'=>3,'admin_role'=>'admin','_csrf_token'=>'fixture-csrf'];
$post = ['IDSanPham'=>1,'NoiDungBinhLuan'=>'Bình luận thử','csrf_token'=>'fixture-csrf'];
try {
    $check(commentRequest('create', $buyer, [], 'GET')['status'] === 405, 'Create rejects GET');
    $check(commentRequest('create', [], $post)['status'] === 401, 'Create requires login');
    $check(commentRequest('create', $buyer, array_merge($post, ['csrf_token'=>'wrong']))['status'] === 403, 'Create rejects invalid CSRF');
    foreach (["1' OR '1'='1", ['1'], '0', '-1'] as $badId) {
        $check(commentRequest('create', $buyer, array_merge($post, ['IDSanPham'=>$badId]))['status'] === 422, 'Create validates product ID');
    }
    foreach (['', str_repeat('a',1001), ['bad']] as $badText) {
        $check(commentRequest('create', $buyer, array_merge($post, ['NoiDungBinhLuan'=>$badText]))['status'] === 422, 'Create validates content');
    }
    $visitor = ['user'=>'visitor','IDNguoiDung'=>2,'_csrf_token'=>'fixture-csrf'];
    $r = commentRequest('create', $visitor, $post);
    $check($r['status'] === 403 && trim($r['body']) === 'not_verified_purchase', 'Unpurchased product rejected');
    $returned = ['user'=>'returned','IDNguoiDung'=>5,'_csrf_token'=>'fixture-csrf'];
    $r = commentRequest('create', $returned, $post);
    $check($r['status'] === 403 && trim($r['body']) === 'return_approved', 'Returned purchase rejected');
    $text = "Mỹ phẩm O'Reilly <img src=x onerror=alert(1)> & tốt";
    $r = commentRequest('create', $buyer, array_merge($post, ['NoiDungBinhLuan'=>$text]));
    $check($r['status'] === 200, 'Valid quoted Vietnamese content accepted');
    $check(str_contains($r['body'], '&lt;img') && !str_contains($r['body'], '<img'), 'Response escapes comment HTML');
    $check(str_contains($r['body'], '&lt;script&gt;') && !str_contains($r['body'], '<script>'), 'Response escapes customer name');
    $stmt = $db->prepare('SELECT ID_BinhLuan,NoiDungBinhLuan FROM binhluan WHERE NoiDungBinhLuan = ?');
    $stmt->execute([$text]); $saved = $stmt->fetch();
    $check($saved && $saved['NoiDungBinhLuan'] === $text, 'Stored content preserves original text');
    $id = (int) ($saved['ID_BinhLuan'] ?? 1);
    $delete = ['IDComment'=>$id,'csrf_token'=>'fixture-csrf'];
    $check(commentRequest('delete', [], $delete)['status'] === 403, 'Anonymous delete rejected');
    $check(commentRequest('delete', $buyer, $delete)['status'] === 403, 'Customer delete rejected');
    $staff = ['admin_user'=>'employee','admin_id'=>4,'admin_role'=>'employee','_csrf_token'=>'fixture-csrf'];
    $check(commentRequest('delete', $staff, $delete)['status'] === 403, 'Employee delete rejected consistently with admin page');
    $check(commentRequest('delete', $admin, array_merge($delete,['csrf_token'=>'wrong']))['status'] === 403, 'Delete rejects invalid CSRF');
    $check(commentRequest('delete', $admin, array_merge($delete,['IDComment'=>"1' OR '1'='1"]))['status'] === 422, 'Delete rejects injected ID');
    $r = commentRequest('delete', $admin, $delete);
    $check($r['status'] === 200 && trim($r['body']) === 'xoathanhcong', 'Authorized delete succeeds');
    $stmt = $db->prepare('SELECT COUNT(*) FROM binhluan WHERE ID_BinhLuan=?'); $stmt->execute([$id]);
    $check((int)$stmt->fetchColumn() === 0, 'Authorized delete removes requested comment');
} finally {
    foreach ($tables as $table) { $db->exec('DELETE FROM ' . $table); }
}
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "comments_integration=ok\n";
