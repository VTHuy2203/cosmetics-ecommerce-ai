<?php
declare(strict_types=1);

include '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/ChatSessionService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
require_csrf(true);

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'invalid_json'], 400);
}

$action = (string) ($input['action'] ?? '');
if (!in_array($action, ['new', 'delete'], true)) {
    json_response(['ok' => false, 'error' => 'invalid_action'], 422);
}

try {
    $pdo = chat_session_pdo();
    $userId = chat_session_user_id($pdo);

    if ($action === 'new') {
        $sessionId = chat_session_create($pdo, $userId);
    } else {
        $requestedSessionId = $input['session_id'] ?? null;
        if ($userId > 0) {
            if (!chat_session_id_is_valid($requestedSessionId)
                || !chat_session_is_owned($pdo, (string) $requestedSessionId, $userId)) {
                json_response(['ok' => false, 'error' => 'session_not_found'], 404);
            }
            $sessionId = (string) $requestedSessionId;
        } else {
            $currentSessionId = $_SESSION['chat_current_session_id'] ?? null;
            if (!chat_session_id_is_valid($currentSessionId)
                || !hash_equals((string) $currentSessionId, (string) $requestedSessionId)
                || !chat_session_is_owned($pdo, (string) $currentSessionId, 0)) {
                json_response(['ok' => false, 'error' => 'session_not_found'], 404);
            }
            $sessionId = (string) $currentSessionId;
        }

        $pdo->beginTransaction();
        if (!chat_session_delete($pdo, $sessionId, $userId)) {
            throw new RuntimeException('Chat session could not be deleted.');
        }
        $pdo->commit();

        json_response([
            'ok' => true,
            'action' => 'delete',
            'deleted_session_id' => $sessionId,
            'session_id' => null,
            'session' => null,
            // Lịch tư vấn là dữ liệu nghiệp vụ riêng nên vẫn được giữ lại.
            'upcoming_appointment' => chat_session_upcoming_appointment($pdo, $userId, $sessionId),
        ]);
    }

    json_response([
        'ok' => true,
        'action' => $action,
        'session_id' => $sessionId,
        'session' => chat_session_info($pdo, $sessionId),
        'upcoming_appointment' => chat_session_upcoming_appointment($pdo, $userId, $sessionId),
    ]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Chat_Session_Action.php: ' . $exception->getMessage());
    json_response(['ok' => false, 'error' => 'session_action_failed'], 500);
}
