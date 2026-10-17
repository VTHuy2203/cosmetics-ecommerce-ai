<?php
declare(strict_types=1);
require_once __DIR__ . '/support/environment.php';
require_once dirname(__DIR__) . '/TTTT/config/bootstrap.php';
$db = app_pdo();
$db->exec('SET FOREIGN_KEY_CHECKS=0');
$db->exec('DROP TABLE IF EXISTS return_refund_requests');
$db->exec('DROP TABLE IF EXISTS khachhang');
$db->exec('SET FOREIGN_KEY_CHECKS=1');
$db->exec('CREATE TABLE khachhang (ID_KhachHang INT PRIMARY KEY, TenDangNhap VARCHAR(50), Role VARCHAR(20)) ENGINE=InnoDB');
$db->exec('CREATE TABLE return_refund_requests (ID_Request INT PRIMARY KEY, ID_KhachHang INT, Attachments JSON) ENGINE=InnoDB');
$db->exec("INSERT INTO khachhang VALUES (1,'owner','customer'),(2,'other','customer'),(3,'admin','admin')");

$filename = str_repeat('a', 40) . '.png';
$directory = dirname(__DIR__) . '/TTTT/storage/private/return_refunds';
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create isolated private-file fixture directory.');
}
$path = $directory . '/' . $filename;
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
if (!is_string($png) || file_put_contents($path, $png) === false) {
    throw new RuntimeException('Cannot write private-file fixture.');
}
$statement = $db->prepare('INSERT INTO return_refund_requests VALUES (?,?,?)');
$attachments = json_encode([['filename'=>$filename]], JSON_THROW_ON_ERROR);
$statement->execute([10,1,$attachments]);

function privateFileRequest(string $target, array $session, array $get): array
{
    $pipes=[];
    $process=proc_open([PHP_BINARY,'-d','extension=mysqli','-d','extension=pdo_mysql','-d','extension=fileinfo',__DIR__.'/support/private_file_request.php'],
        [['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start private-file request process.'); }
    fwrite($pipes[0],json_encode(compact('target','session','get'),JSON_THROW_ON_ERROR)); fclose($pipes[0]);
    $output=stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors=stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exit=proc_close($process);
    $result=json_decode($output,true);
    return is_array($result) ? $result : ['status'=>500,'length'=>0,'exit'=>$exit,'stderr'=>$errors];
}

$failures=[];
$check=static function(bool $ok,string $message)use(&$failures):void{if(!$ok){$failures[]=$message;}};
try {
    $owner=['user'=>'owner','IDNguoiDung'=>1];
    $other=['user'=>'other','IDNguoiDung'=>2];
    $admin=['admin_user'=>'admin','admin_id'=>3,'admin_role'=>'admin','admin_is_admin'=>true];
    $get=['request_id'=>10,'file'=>$filename];
    $result=privateFileRequest('customer',$owner,$get);
    $check($result['status']===200 && $result['length']===strlen($png),
        'Owner cannot read authorized evidence; status=' . $result['status'] . ', length=' . $result['length']);
    $check(privateFileRequest('customer',[],$get)['status']===401,'Anonymous customer download was not rejected.');
    $otherResult=privateFileRequest('customer',$other,$get);
    $check($otherResult['status']===403,'Another customer evidence result is wrong; status=' . $otherResult['status']);
    $check(privateFileRequest('customer',$owner,['request_id'=>10,'file'=>str_repeat('b',40).'.png'])['status']===404,
        'Unlisted evidence filename was not rejected.');
    $result=privateFileRequest('admin',$admin,['path'=>'return_refunds/'.$filename]);
    $check($result['status']===200 && $result['length']===strlen($png),
        'Authorized admin cannot read private evidence; status=' . $result['status'] . ', length=' . $result['length']);
    $check(privateFileRequest('admin',$admin,['path'=>'return_refunds/../../.env'])['status']===404,
        'Admin private-file traversal was not rejected.');
} finally {
    if (is_file($path)) { unlink($path); }
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $db->exec('DROP TABLE IF EXISTS return_refund_requests');
    $db->exec('DROP TABLE IF EXISTS khachhang');
    $db->exec('SET FOREIGN_KEY_CHECKS=1');
}
if($failures!==[]){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}
echo "private_file_access=ok\n";
