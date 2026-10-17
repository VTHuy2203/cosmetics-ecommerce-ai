<?php
declare(strict_types=1);
require_once __DIR__ . '/support/environment.php';

foreach (['APP_ENV' => 'production', 'MYSQL_DB' => 'webmipham1', 'MYSQL_PORT' => '3306', 'MYSQL_USER' => 'root', 'MYSQL_HOST' => 'example.com'] as $key => $bad) {
    $before = getenv($key);
    putenv($key . '=' . $bad);
    $rejected = false;
    try {
        require_test_environment();
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        putenv($key . '=' . $before);
    }
    if (!$rejected) {
        throw new RuntimeException('Guard accepted unsafe configuration: ' . $key);
    }
}
require_test_environment();
require_once dirname(__DIR__) . '/TTTT/config/bootstrap.php';
$savedEnv = $_ENV['APP_ENV'] ?? null;
$savedServer = $_SERVER['APP_ENV'] ?? null;
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production-from-dotenv-fixture';
putenv('APP_ENV=testing');
if (app_env('APP_ENV') !== 'testing') {
    throw new RuntimeException('Process environment did not override dotenv values.');
}
$_ENV['APP_ENV'] = $savedEnv;
$_SERVER['APP_ENV'] = $savedServer;
$db = app_pdo();
if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'moshop_test_standardization') {
    throw new RuntimeException('Application resolved the wrong database.');
}
try {
    $db->query('SELECT * FROM mysql.user');
    throw new RuntimeException('Test account unexpectedly has access outside the test database.');
} catch (PDOException $error) {
    if ((int) ($error->errorInfo[1] ?? 0) !== 1142) { throw $error; }
}
echo "environment_guard=ok; isolated_database=ok; restricted_grants=ok\n";
