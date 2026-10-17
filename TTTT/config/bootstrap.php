<?php
declare(strict_types=1);

if (!defined('MOSHOP_ROOT')) {
    define('MOSHOP_ROOT', dirname(__DIR__));
}

$autoload = MOSHOP_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists(\Dotenv\Dotenv::class)) {
    $environmentDirectory = is_file(dirname(MOSHOP_ROOT) . '/.env') ? dirname(MOSHOP_ROOT) : MOSHOP_ROOT;
    if (is_file($environmentDirectory . '/.env')) {
        \Dotenv\Dotenv::createImmutable($environmentDirectory)->safeLoad();
    }
}

if (!function_exists('app_env')) {
    function app_env(string $key, ?string $default = null): ?string
    {
        // Process/container environment is authoritative. Values loaded from a
        // local .env file are fallbacks and must not override runtime injection.
        $processValue = getenv($key);
        $value = $processValue !== false ? $processValue : ($_SERVER[$key] ?? $_ENV[$key] ?? null);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}

if (!function_exists('app_bool')) {
    function app_bool(string $key, bool $default = false): bool
    {
        $value = app_env($key);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}

if (!function_exists('app_db_env')) {
    function app_db_env(string $key, ?string $default = null): ?string
    {
        if (strtolower(app_env('APP_ENV', 'local') ?? 'local') === 'local'
            && app_bool('USE_LARAGON_DB', false)) {
            $localKey = 'LARAGON_' . $key;
            $localValue = app_env($localKey);
            if ($localValue !== null || $localKey === 'LARAGON_MYSQL_PASSWORD') {
                return $localValue ?? '';
            }
        }
        return app_env($key, $default);
    }
}

if (!function_exists('app_ip_in_cidr')) {
    function app_ip_in_cidr(string $ip, string $cidr): bool
    {
        [$network, $prefix] = array_pad(explode('/', trim($cidr), 2), 2, null);
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($network, FILTER_VALIDATE_IP)) {
            return false;
        }
        $ipBinary = inet_pton($ip);
        $networkBinary = inet_pton($network);
        if (!is_string($ipBinary) || !is_string($networkBinary) || strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }
        $maxBits = strlen($ipBinary) * 8;
        $bits = $prefix === null ? $maxBits : filter_var($prefix, FILTER_VALIDATE_INT);
        if ($bits === false || $bits < 0 || $bits > $maxBits) {
            return false;
        }
        $fullBytes = intdiv($bits, 8);
        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($networkBinary, 0, $fullBytes)) {
            return false;
        }
        $remaining = $bits % 8;
        if ($remaining === 0) {
            return true;
        }
        $mask = (0xff << (8 - $remaining)) & 0xff;
        return (ord($ipBinary[$fullBytes]) & $mask) === (ord($networkBinary[$fullBytes]) & $mask);
    }
}

if (!function_exists('app_remote_is_trusted_proxy')) {
    function app_remote_is_trusted_proxy(): bool
    {
        if (!app_bool('TRUST_PROXY_HEADERS', false)) {
            return false;
        }
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $configured = app_env('TRUSTED_PROXY_CIDRS', '') ?? '';
        if (!filter_var($remote, FILTER_VALIDATE_IP) || trim($configured) === '') {
            return false;
        }
        foreach (array_filter(array_map('trim', explode(',', $configured))) as $cidr) {
            if (app_ip_in_cidr($remote, $cidr)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('app_client_ip')) {
    function app_client_ip(): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (app_remote_is_trusted_proxy()) {
            $forwarded = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
            if ($forwarded === '') {
                $forwarded = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''), 2)[0]);
            }
            if (filter_var($forwarded, FILTER_VALIDATE_IP)) {
                return $forwarded;
            }
        }
        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
    }
}

if (!function_exists('app_is_production')) {
    function app_is_production(): bool
    {
        return strtolower(app_env('APP_ENV', 'local') ?? 'local') === 'production';
    }
}

if (!function_exists('app_is_https')) {
    function app_is_https(): bool
    {
        if (($_SERVER['HTTPS'] ?? '') === 'on') {
            return true;
        }

        // Trust the proxy header only when explicitly enabled. Production behind
        // Cloudflare Tunnel should set TRUST_PROXY_HEADERS=true.
        return app_remote_is_trusted_proxy()
            && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}

if (!function_exists('app_pdo')) {
    function app_pdo(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        $host = app_db_env('MYSQL_HOST');
        $database = app_db_env('MYSQL_DB');
        $username = app_db_env('MYSQL_USER');
        if ($host === null || $database === null || $username === null) {
            throw new RuntimeException('Database environment is incomplete (MYSQL_HOST, MYSQL_DB, MYSQL_USER).');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            (int) app_db_env('MYSQL_PORT', '3306'),
            $database,
            app_db_env('MYSQL_CHARSET', 'utf8mb4')
        );
        $pdo = new PDO($dsn, $username, app_db_env('MYSQL_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    }
}

date_default_timezone_set(app_env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh') ?? 'Asia/Ho_Chi_Minh');

error_reporting(E_ALL);
ini_set('display_errors', app_is_production() ? '0' : '1');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli' && !defined('MOSHOP_SKIP_SESSION') && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_name(app_env('SESSION_NAME', 'MOSHOPSESSID') ?? 'MOSHOPSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => app_is_https() || app_is_production(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// Defense in depth for legacy POST handlers that have not yet been migrated to
// explicit CSRF tokens. Token verification remains mandatory on sensitive flows.
if (PHP_SAPI !== 'cli' && !defined('MOSHOP_SKIP_ORIGIN_CHECK')
    && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $allowedHosts = [];
    $requestHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($requestHost !== '') {
        $allowedHosts[] = $requestHost;
    }
    $appHost = strtolower((string) parse_url(app_env('APP_URL', '') ?? '', PHP_URL_HOST));
    if ($appHost !== '') {
        $allowedHosts[] = $appHost;
    }

    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
    $suppliedHost = $origin !== ''
        ? strtolower((string) parse_url($origin, PHP_URL_HOST))
        : ($referer !== '' ? strtolower((string) parse_url($referer, PHP_URL_HOST)) : '');

    if ($fetchSite === 'cross-site' || ($suppliedHost !== '' && !in_array($suppliedHost, array_unique($allowedHosts), true))) {
        http_response_code(403);
        exit('Cross-site request rejected.');
    }
}
