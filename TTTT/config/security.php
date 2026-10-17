<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_is_valid')) {
    function csrf_is_valid(?string $token = null): bool
    {
        $token ??= $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $expected = $_SESSION['_csrf_token'] ?? null;

        return is_string($token)
            && is_string($expected)
            && $token !== ''
            && hash_equals($expected, $token);
    }
}

if (!function_exists('require_csrf')) {
    function require_csrf(bool $json = false): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || csrf_is_valid()) {
            return;
        }

        // 419 is non-standard and Apache/PHP 8.1 can translate it to 500.
        // Use the portable HTTP status for a rejected security token.
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'csrf_invalid'], JSON_UNESCAPED_UNICODE);
        } else {
            echo 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
        }
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('sepay_signature_is_valid')) {
    function sepay_signature_is_valid(
        string $rawBody,
        string $secret,
        string $signature,
        int $timestamp,
        ?int $now = null
    ): bool {
        $now ??= time();
        if ($secret === '' || $signature === '' || $timestamp <= 0 || abs($now - $timestamp) > 300) {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        return hash_equals($expected, $signature);
    }
}

if (!function_exists('password_matches_and_upgrade')) {
    function password_matches_and_upgrade(mysqli $conn, int $userId, string $plain, string $stored): bool
    {
        $info = password_get_info($stored);
        $isHash = ($info['algoName'] ?? 'unknown') !== 'unknown';
        $valid = $isHash ? password_verify($plain, $stored) : hash_equals($stored, $plain);

        if (!$valid) {
            return false;
        }

        if (!$isHash || password_needs_rehash($stored, PASSWORD_DEFAULT)) {
            $hash = password_hash($plain, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE khachhang SET MatKhau = ? WHERE ID_KhachHang = ?');
            $stmt->bind_param('si', $hash, $userId);
            $stmt->execute();
        }

        return true;
    }
}

if (!function_exists('auth_rate_limit')) {
    function auth_rate_limit(string $scope, int $maxAttempts = 8, int $windowSeconds = 900): bool
    {
        if ($scope === '' || $maxAttempts < 1 || $windowSeconds < 1 || $windowSeconds > 86400) {
            return false;
        }
        $scopeHash = hash('sha256', $scope . '|' . app_client_ip());
        try {
            $db = app_pdo();
            $statement = $db->prepare(
                'INSERT INTO auth_rate_limits (scope_hash, window_started_at, attempts, expires_at, updated_at)
                 VALUES (UNHEX(?), UTC_TIMESTAMP(), 1, TIMESTAMPADD(SECOND, ?, UTC_TIMESTAMP()), UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE
                    attempts = IF(expires_at <= UTC_TIMESTAMP(), 1, attempts + 1),
                    window_started_at = IF(expires_at <= UTC_TIMESTAMP(), UTC_TIMESTAMP(), window_started_at),
                    expires_at = IF(expires_at <= UTC_TIMESTAMP(), TIMESTAMPADD(SECOND, ?, UTC_TIMESTAMP()), expires_at),
                    updated_at = UTC_TIMESTAMP()'
            );
            $statement->execute([$scopeHash, $windowSeconds, $windowSeconds]);
            $read = $db->prepare('SELECT attempts FROM auth_rate_limits WHERE scope_hash = UNHEX(?)');
            $read->execute([$scopeHash]);
            $attempts = (int) $read->fetchColumn();
            if (random_int(1, 100) === 1) {
                $db->exec('DELETE FROM auth_rate_limits WHERE expires_at < UTC_TIMESTAMP() - INTERVAL 1 DAY LIMIT 500');
            }
            return $attempts <= $maxAttempts;
        } catch (Throwable $error) {
            error_log('Auth rate limiter unavailable; code=' . (int) $error->getCode());
            if (app_is_production()) {
                return false;
            }
            $key = '_rate_fallback_' . $scopeHash;
            $now = time();
            $state = $_SESSION[$key] ?? ['started_at' => $now, 'attempts' => 0];
            if ((int) ($state['started_at'] ?? 0) + $windowSeconds <= $now) {
                $state = ['started_at' => $now, 'attempts' => 0];
            }
            $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
            $_SESSION[$key] = $state;
            return $state['attempts'] <= $maxAttempts;
        }
    }
}

if (!function_exists('auth_rate_limit_reset')) {
    function auth_rate_limit_reset(string $scope): void
    {
        if ($scope === '') { return; }
        $scopeHash = hash('sha256', $scope . '|' . app_client_ip());
        try {
            $statement = app_pdo()->prepare('DELETE FROM auth_rate_limits WHERE scope_hash = UNHEX(?)');
            $statement->execute([$scopeHash]);
        } catch (Throwable $error) {
            unset($_SESSION['_rate_fallback_' . $scopeHash]);
        }
    }
}

if (!function_exists('turnstile_widget')) {
    function turnstile_widget(string $action): string
    {
        $siteKey = app_env('TURNSTILE_SITE_KEY', '') ?? '';
        if ($siteKey === '') {
            return '';
        }

        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
            . '<div class="cf-turnstile my-3" data-sitekey="'
            . htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8')
            . '" data-action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '"></div>';
    }
}

if (!function_exists('turnstile_is_valid')) {
    function turnstile_is_valid(string $token, string $expectedAction): bool
    {
        $secret = app_env('TURNSTILE_SECRET_KEY', '') ?? '';
        if ($secret === '') {
            return !app_is_production();
        }
        if ($token === '' || strlen($token) > 2048 || !function_exists('curl_init')) {
            return false;
        }

        $request = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($request, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => app_client_ip(),
                'idempotency_key' => bin2hex(random_bytes(16)),
            ], JSON_UNESCAPED_SLASHES),
        ]);
        $body = curl_exec($request);
        $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE);
        curl_close($request);
        if ($status !== 200 || !is_string($body)) {
            return false;
        }

        $result = json_decode($body, true);
        if (!is_array($result) || ($result['success'] ?? false) !== true) {
            return false;
        }

        return empty($result['action']) || hash_equals($expectedAction, (string) $result['action']);
    }
}

if (!function_exists('require_turnstile')) {
    function require_turnstile(string $action): void
    {
        $token = (string) ($_POST['cf-turnstile-response'] ?? '');
        if (turnstile_is_valid($token, $action)) {
            return;
        }

        http_response_code(422);
        echo 'turnstile_invalid';
        exit;
    }
}

if (!function_exists('admin_cancel_order_form')) {
    function admin_cancel_order_form(int $orderId, string $view): string
    {
        $safeView = in_array($view, ['all', 'approve', 'prepare', 'shipping', 'success'], true) ? $view : 'all';
        return '<form method="post" action="Delete_Oder.php" class="d-inline" '
            . 'onsubmit="return confirm(\'Bạn có chắc muốn hủy đơn hàng #' . $orderId . '?\')">'
            . csrf_field()
            . '<input type="hidden" name="order_id" value="' . $orderId . '">'
            . '<input type="hidden" name="view" value="' . $safeView . '">'
            . '<button type="submit" class="btn btn-danger">Hủy</button></form>';
    }
}

if (!function_exists('admin_order_transition_form')) {
    function admin_order_transition_form(
        int $orderId,
        string $action,
        string $label,
        string $buttonClass,
        string $returnTo
    ): string {
        $targets = [
            'approve' => 'Order_Status_Action.php',
            'prepare' => 'Order_Status_Action.php',
            'shipping' => 'Order_Status_Action.php',
        ];
        if (!isset($targets[$action])) {
            return '';
        }
        $safeReturn = $returnTo === 'all' ? 'all' : $action;
        return '<form method="post" action="' . $targets[$action] . '" class="d-inline js-order-status-form">'
            . csrf_field()
            . '<input type="hidden" name="order_id" value="' . $orderId . '">'
            . '<input type="hidden" name="action" value="' . $action . '">'
            . '<input type="hidden" name="return_to" value="' . $safeReturn . '">'
            . '<button type="submit" class="btn ' . htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button></form>';
    }
}

if (!function_exists('admin_mark_paid_form')) {
    function admin_mark_paid_form(int $orderId): string
    {
        return '<form method="post" action="Order_Status_Action.php" class="d-inline js-order-payment-form" '
            . 'data-confirm="Chỉ xác nhận sau khi đã trực tiếp đối soát khoản tiền nhận được. Tiếp tục?">'
            . csrf_field()
            . '<input type="hidden" name="order_id" value="' . $orderId . '">'
            . '<input type="hidden" name="action" value="mark_paid">'
            . '<input type="hidden" name="return_to" value="all">'
            . '<button type="submit" class="btn btn-outline-success">Đánh dấu đã thanh toán</button></form>';
    }
}

if (!function_exists('admin_delete_form')) {
    function admin_delete_form(string $action, string $field, int $id): string
    {
        if (!preg_match('/^[A-Za-z0-9_\/-]+\.php$/', $action) || !preg_match('/^[A-Za-z0-9_]+$/', $field)) {
            return '';
        }
        return '<form method="post" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" class="d-inline" '
            . 'onsubmit="return confirm(\'Bạn có chắc muốn xóa mục này?\')">'
            . csrf_field()
            . '<input type="hidden" name="' . $field . '" value="' . $id . '">'
            . '<button type="submit" class="btn btn-danger">Xóa</button></form>';
    }
}

if (!function_exists('admin_deposit_form')) {
    function admin_deposit_form(int $depositId, bool $approve): string
    {
        $action = $approve ? 'Confirm_Deposit.php' : 'Reject_Deposit.php';
        $class = $approve ? 'btn-success' : 'btn-outline-danger';
        $label = $approve ? 'Xác nhận' : 'Từ chối';
        return '<form method="post" action="' . $action . '" class="d-inline">'
            . csrf_field()
            . '<input type="hidden" name="id" value="' . $depositId . '">'
            . '<button type="submit" class="btn btn-sm ' . $class . '">' . $label . '</button></form>';
    }
}
