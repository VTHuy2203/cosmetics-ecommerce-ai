<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/CustomerScoringService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$role = (string) ($_SESSION['admin_role'] ?? $_SESSION['Role'] ?? '');
$hasAdminSession = isset($_SESSION['admin_id']) || isset($_SESSION['ID_KhachHang']);
if (!$hasAdminSession || !in_array($role, ['admin', 'manager', 'employee'], true)) {
    json_response(['ok' => false, 'error' => 'Vui lòng đăng nhập lại trang quản trị.'], 401);
}

require_csrf(true);

try {
    $service = new CustomerScoringService(app_pdo());
    $result = $service->scoreAll();

    json_response([
        'ok' => $result['failed'] === 0,
        'updated' => $result['updated'],
        'failed' => $result['failed'],
        'errors' => app_is_production() ? [] : array_slice($result['errors'], 0, 10),
        'scoring_version' => CustomerScoringService::VERSION,
    ], $result['failed'] === 0 ? 200 : 207);
} catch (Throwable $error) {
    error_log('Customer scoring failed: ' . $error->getMessage());
    json_response([
        'ok' => false,
        'error' => app_is_production()
            ? 'Không thể phân tích khách hàng lúc này.'
            : $error->getMessage(),
    ], 500);
}
