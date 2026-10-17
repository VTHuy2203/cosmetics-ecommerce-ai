<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

/**
 * @param array<string, mixed> $metadata
 */
function admin_activity_log(
    mysqli $conn,
    int $adminId,
    string $action,
    string $description,
    array $metadata = []
): void {
    if ($adminId < 1) {
        return;
    }

    $action = mb_substr(trim($action), 0, 50);
    $description = mb_substr(trim($description), 0, 255);
    $metadataJson = $metadata === []
        ? null
        : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ipAddress = mb_substr(app_client_ip(), 0, 45);
    $userAgent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

    try {
        $statement = $conn->prepare(
            'INSERT INTO admin_account_activity
                (admin_id, action, description, metadata, ip_address, user_agent)
             VALUES (?, ?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'))'
        );
        $statement->bind_param(
            'isssss',
            $adminId,
            $action,
            $description,
            $metadataJson,
            $ipAddress,
            $userAgent
        );
        $statement->execute();
        $statement->close();
    } catch (Throwable $error) {
        // An unavailable audit table must not break authentication or profile updates.
        error_log('Admin activity log failed: ' . $error->getMessage());
    }
}
