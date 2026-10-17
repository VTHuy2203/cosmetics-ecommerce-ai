<?php
declare(strict_types=1);
require_once __DIR__.'/support/environment.php';
require_once dirname(__DIR__).'/TTTT/config/bootstrap.php';
require_once dirname(__DIR__).'/TTTT/WebBanHangMyPham/src/Services/ShippingService.php';
$db=app_pdo();
$column=static function(string $table,string $name,string $definition)use($db):void{$q=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$q->execute([$table,$name]);if(!(int)$q->fetchColumn()){$db->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition");}};
$column('donhang','PhiVanChuyen','BIGINT NOT NULL DEFAULT 0');
$column('sanpham','SoLuong','INT NOT NULL DEFAULT 0');
$column('sanpham','GiaTien','BIGINT NOT NULL DEFAULT 0');
$db->exec('SET FOREIGN_KEY_CHECKS=0');
$foreign=$db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='donhang' AND CONSTRAINT_NAME='fk_order_shipping_version'")->fetchColumn();
if($foreign){$db->exec('ALTER TABLE donhang DROP FOREIGN KEY fk_order_shipping_version');}
foreach(['ShippingConfigVersionID','ShippingRegionCode','ShippingRegionName','TongKhoiLuongGram'] as $name){$q=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'donhang\' AND COLUMN_NAME=?');$q->execute([$name]);if((int)$q->fetchColumn()){$db->exec("ALTER TABLE donhang DROP COLUMN `$name`");}}
foreach(['shipping_config_audit','shipping_rate_bands','shipping_regions','shipping_config_versions'] as $table){$db->exec("DROP TABLE IF EXISTS `$table`");}
$q=$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sanpham' AND COLUMN_NAME='TrongLuongGram'");if((int)$q->fetchColumn()){$db->exec('ALTER TABLE sanpham DROP COLUMN TrongLuongGram');}
$db->exec('SET FOREIGN_KEY_CHECKS=1');
$migration=file_get_contents(dirname(__DIR__).'/TTTT/database/migrations/20260911_0015_shipping_configuration.sql');if(!is_string($migration)){throw new RuntimeException('Missing shipping migration.');}$db->exec($migration);
$db->exec("INSERT INTO danhmuc(IDDanhMuc,TenDanhMuc) VALUES(9501,'Shipping test') ON DUPLICATE KEY UPDATE TenDanhMuc=VALUES(TenDanhMuc)");
$db->exec("INSERT INTO thuonghieu(IDThuongHieu,TenThuongHieu) VALUES(9501,'Shipping test') ON DUPLICATE KEY UPDATE TenThuongHieu=VALUES(TenThuongHieu)");
$db->exec("INSERT INTO khachhang(ID_KhachHang,TenDangNhap,HoVaTen,Role) VALUES(9501,'shipping-test','Shipping test','admin') ON DUPLICATE KEY UPDATE Role='admin'");
$db->exec("INSERT INTO sanpham(IDSanPham,TenSanPham,IDDanhMuc,IDThuongHieu,SoLuong,GiaTien) VALUES(9501,'Shipping test',9501,9501,5,100000) ON DUPLICATE KEY UPDATE SoLuong=5,GiaTien=100000");
$mysqli=new mysqli((string)getenv('MYSQL_HOST'),(string)getenv('MYSQL_USER'),(string)getenv('MYSQL_PASSWORD'),(string)getenv('MYSQL_DB'),(int)getenv('MYSQL_PORT'));$mysqli->set_charset('utf8mb4');
$service=new ShippingService($mysqli);$failures=[];$check=static function(bool $ok,string $message)use(&$failures):void{if(!$ok)$failures[]=$message;};
$actorId=9501;
$check($service->activeRegions()===[],'Migration invented an active shipping rate.');
try{$service->quote('LOCAL',500,100000);$failures[]='Quote succeeded without active config.';}catch(ShippingException $e){$check($e->reason==='shipping_unavailable','Wrong unavailable reason.');}
$insertVersion=$db->prepare("INSERT INTO shipping_config_versions(id,name,origin_label,free_shipping_min,status,created_by) VALUES (9501,'Test','Kho test',500000,'active',?)");$insertVersion->execute([$actorId]);
$db->exec("INSERT INTO shipping_regions(id,version_id,code,name) VALUES (9501,9501,'LOCAL','Nội vùng')");
$db->exec("INSERT INTO shipping_rate_bands(region_id,min_weight_grams,max_weight_grams,fee) VALUES (9501,1,1000,20000),(9501,1001,NULL,30000)");
$light=$service->quote('local',500,100000);$heavy=$service->quote('LOCAL',1500,100000);$free=$service->quote('LOCAL',1500,500000);
$check($light['fee']===20000&&$light['total_weight_grams']===500,'Light band quote is incorrect.');
$check($heavy['fee']===30000,'Heavy band quote is incorrect.');
$check($free['fee']===0,'Configured free-shipping threshold is ignored.');
$duplicateActive=false;try{$other=$db->prepare("INSERT INTO shipping_config_versions(name,origin_label,status,created_by) VALUES ('Other','Kho','active',?)");$other->execute([$actorId]);}catch(PDOException){$duplicateActive=true;}$check($duplicateActive,'Database permits two active shipping versions.');
try{$service->quote('UNKNOWN',500,100000);$failures[]='Unknown region received a quote.';}catch(ShippingException){}
$db->exec("UPDATE sanpham SET TrongLuongGram=250 WHERE IDSanPham=9501");
$cartQuote=$service->quoteCart([[9501,'','',2,0]],'LOCAL');$check($cartQuote['total_weight_grams']===500,'Cart weight is not derived from live product data.');
$db->exec('SET FOREIGN_KEY_CHECKS=0');foreach(['shipping_config_audit','shipping_rate_bands','shipping_regions','shipping_config_versions'] as $table){$db->exec("DELETE FROM `$table`");}$db->exec('SET FOREIGN_KEY_CHECKS=1');
$db->exec('DELETE FROM sanpham WHERE IDSanPham=9501');$db->exec('DELETE FROM khachhang WHERE ID_KhachHang=9501');$db->exec('DELETE FROM danhmuc WHERE IDDanhMuc=9501');$db->exec('DELETE FROM thuonghieu WHERE IDThuongHieu=9501');
if($failures!==[]){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}echo "shipping_integration=ok\n";
