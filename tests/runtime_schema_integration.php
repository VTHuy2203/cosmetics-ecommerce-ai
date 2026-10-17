<?php
declare(strict_types=1);
require_once __DIR__ . '/support/environment.php';
require_once dirname(__DIR__) . '/TTTT/config/bootstrap.php';
$db = app_pdo();
$db->exec('SET FOREIGN_KEY_CHECKS=0');
$db->exec('DROP TABLE IF EXISTS danhgia');
$db->exec('DROP TABLE IF EXISTS contact_messages');
$db->exec('SET FOREIGN_KEY_CHECKS=1');
$migration = file_get_contents(dirname(__DIR__) . '/TTTT/database/migrations/20260911_0013_contact_and_rating_runtime_schema.sql');
if (!is_string($migration)) { throw new RuntimeException('Cannot read runtime schema migration.'); }
$db->exec($migration);
$db->exec($migration);
$failures=[];
$check=static function(bool $ok,string $message)use(&$failures):void{if(!$ok){$failures[]=$message;}};
$tables=$db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('contact_messages','danhgia')")->fetchAll(PDO::FETCH_COLUMN);
$check(count($tables)===2,'Migration did not create both runtime tables.');
$constraints=$db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='danhgia'")->fetchAll(PDO::FETCH_COLUMN);
$check(in_array('uq_rating_customer_product',$constraints,true),'Rating uniqueness is missing.');
$check(in_array('chk_rating_value',$constraints,true),'Rating range check is missing.');
$deleteRule=$db->query("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_rating_product'")->fetchColumn();
$check($deleteRule==='RESTRICT','Product rating history is not protected by RESTRICT.');
if($failures!==[]){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}
echo "runtime_schema_integration=ok\n";
