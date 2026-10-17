<?php
declare(strict_types=1);

/** Explicit opt-in before loading any application bootstrap or DB connection. */
function require_test_environment(): void
{
    $expected = [
        'APP_ENV' => 'testing',
        'MYSQL_HOST' => '127.0.0.1',
        'MYSQL_PORT' => '13316',
        'MYSQL_DB' => 'moshop_test_standardization',
        'MYSQL_USER' => 'moshop_test',
    ];
    foreach ($expected as $key => $value) {
        if (getenv($key) !== $value) {
            throw new RuntimeException('Unsafe test environment: configure the isolated test runner before running database tests.');
        }
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
    $password = getenv('MYSQL_PASSWORD');
    if (!is_string($password) || $password === '') {
        throw new RuntimeException('The isolated test database password is required.');
    }
    $_ENV['MYSQL_PASSWORD'] = $_SERVER['MYSQL_PASSWORD'] = $password;
    foreach ([
        'USE_LARAGON_DB' => 'false', 'MYSQL_CHARSET' => 'utf8mb4',
        'AI_CHAT_ENABLED' => 'false', 'SEPAY_PG_ENABLED' => 'false',
        'REALTIME_WS_URL' => '', 'AI_SERVICE_URL' => '',
        'APP_URL' => 'http://127.0.0.1:18089/TTTT',
    ] as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

require_test_environment();
