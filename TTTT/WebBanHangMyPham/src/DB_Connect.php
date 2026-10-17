<?php
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$servername = app_db_env('MYSQL_HOST');
$username = app_db_env('MYSQL_USER');
$password = app_db_env('MYSQL_PASSWORD', '');
$database = app_db_env('MYSQL_DB');
$missingDatabaseConfig = $servername === null || $username === null || $database === null;
if ($missingDatabaseConfig) {
    throw new RuntimeException('Database environment is incomplete (MYSQL_HOST, MYSQL_DB, MYSQL_USER).');
}
$port = (int) app_db_env('MYSQL_PORT', '3306');

$conn = new mysqli($servername, $username, $password, $database, $port);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(503);
    die(app_is_production() ? 'Dịch vụ tạm thời không khả dụng.' : 'Database connection failed.');
}
$conn->set_charset(app_db_env('MYSQL_CHARSET', 'utf8mb4') ?? 'utf8mb4');

// ====== BỔ SUNG HÀM db() TƯƠNG THÍCH ======
if (!function_exists('db')) {
    function db() {
        global $conn;
        return $conn;
    }
}
