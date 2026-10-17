<?php
declare(strict_types=1);
require_once __DIR__ . '/support/environment.php';
require_once dirname(__DIR__) . '/TTTT/config/security.php';
$db = app_pdo();
$db->exec('DROP TABLE IF EXISTS auth_rate_limits');
$migration = file_get_contents(dirname(__DIR__) . '/TTTT/database/migrations/20260911_0012_server_side_rate_limits.sql');
if (!is_string($migration)) { throw new RuntimeException('Cannot read rate-limit migration.'); }
$db->exec($migration);

$failures=[];
$check=static function(bool $ok,string $message)use(&$failures):void{if(!$ok){$failures[]=$message;}};
$setEnv=static function(string $key,string $value):void{putenv($key.'='.$value);$_ENV[$key]=$value;$_SERVER[$key]=$value;};

$_SERVER['REMOTE_ADDR']='203.0.113.10';
$_SERVER['HTTP_X_FORWARDED_FOR']='198.51.100.1';
$setEnv('TRUST_PROXY_HEADERS','false');
$setEnv('TRUSTED_PROXY_CIDRS','');
$check(app_client_ip()==='203.0.113.10','Untrusted forwarded header changed client IP.');
$check(auth_rate_limit('login:alice',2,900),'First attempt was blocked.');
$_SESSION=[];
$check(auth_rate_limit('login:alice',2,900),'Second attempt was blocked after cookie change.');
$_SESSION=[];
$check(!auth_rate_limit('login:alice',2,900),'Cookie change reset the server-side limit.');

$_SERVER['HTTP_X_FORWARDED_FOR']='198.51.100.99';
$check(!auth_rate_limit('login:alice',2,900),'Spoofed forwarded IP bypassed the limit.');
$_SERVER['REMOTE_ADDR']='203.0.113.11';
$check(auth_rate_limit('login:alice',2,900),'Separate source IP did not receive an independent bucket.');

$setEnv('TRUST_PROXY_HEADERS','true');
$setEnv('TRUSTED_PROXY_CIDRS','127.0.0.1/32,::1/128');
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_CF_CONNECTING_IP']='198.51.100.20';
$check(app_client_ip()==='198.51.100.20','Trusted proxy client IP was not accepted.');
$_SERVER['HTTP_X_FORWARDED_PROTO']='https';
$check(app_is_https(),'HTTPS proxy header from trusted proxy was ignored.');
$_SERVER['REMOTE_ADDR']='203.0.113.12';
$check(app_client_ip()==='203.0.113.12','Untrusted remote address supplied a proxy client IP.');
$check(!app_is_https(),'Untrusted remote address supplied HTTPS state.');

$_SERVER['REMOTE_ADDR']='203.0.113.20';
unset($_SERVER['HTTP_CF_CONNECTING_IP'],$_SERVER['HTTP_X_FORWARDED_FOR'],$_SERVER['HTTP_X_FORWARDED_PROTO']);
$setEnv('TRUST_PROXY_HEADERS','false');
$check(auth_rate_limit('login:reset',1,900),'Reset bucket first attempt failed.');
$check(!auth_rate_limit('login:reset',1,900),'Reset bucket did not reach limit.');
auth_rate_limit_reset('login:reset');
$check(auth_rate_limit('login:reset',1,900),'Successful-login reset did not clear bucket.');

$hash=hash('sha256','login:expiry|'.app_client_ip());
$db->prepare('UPDATE auth_rate_limits SET expires_at=UTC_TIMESTAMP()-INTERVAL 1 SECOND WHERE scope_hash=UNHEX(?)')->execute([$hash]);
$check(auth_rate_limit('login:expiry',1,60),'Initial expiry bucket failed.');
$db->prepare('UPDATE auth_rate_limits SET expires_at=UTC_TIMESTAMP()-INTERVAL 1 SECOND WHERE scope_hash=UNHEX(?)')->execute([$hash]);
$check(auth_rate_limit('login:expiry',1,60),'Expired bucket was not renewed.');

$columns=$db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='auth_rate_limits'")->fetchAll(PDO::FETCH_COLUMN);
$check(!in_array('scope',$columns,true) && !in_array('ip_address',$columns,true),'Rate-limit table stores raw scope or IP.');

if($failures!==[]){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}
echo "rate_limit_integration=ok\n";
