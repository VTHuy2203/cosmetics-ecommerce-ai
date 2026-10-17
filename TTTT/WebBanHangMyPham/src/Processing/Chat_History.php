<?php
declare(strict_types=1);

include '../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once dirname(__DIR__) . '/Services/ChatSessionService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

try {
    $pdo = chat_session_pdo();
    $userId = chat_session_user_id($pdo);
    $sessionId = chat_session_resolve($pdo, $userId, $_GET['sid'] ?? null);

    $statement = $pdo->prepare(
        $userId > 0
            ? 'SELECT id, message, direction, created_at
               FROM chat_messages
               WHERE user_id = ? AND session_id = ?
               ORDER BY created_at DESC, id DESC LIMIT 200'
            : 'SELECT id, message, direction, created_at
               FROM chat_messages
               WHERE user_id IS NULL AND session_id = ?
               ORDER BY created_at DESC, id DESC LIMIT 200'
    );
    $statement->execute($userId > 0 ? [$userId, $sessionId] : [$sessionId]);

    $messages = array_reverse($statement->fetchAll());
    $appointment = chat_session_upcoming_appointment($pdo, $userId, $sessionId);

    json_response([
        'ok' => true,
        'session_id' => $sessionId,
        'session' => chat_session_info($pdo, $sessionId),
        'messages' => $messages,
        'upcoming_appointment' => $appointment,
    ]);
} catch (Throwable $exception) {
    error_log('Chat_History.php: ' . $exception->getMessage());
    json_response([
        'ok' => false,
        'error' => 'history_unavailable',
        'messages' => [],
    ], 500);
}
