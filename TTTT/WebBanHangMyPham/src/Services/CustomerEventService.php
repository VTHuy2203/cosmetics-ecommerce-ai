<?php
declare(strict_types=1);

final class CustomerEventService
{
    /** @param array<string,mixed> $metadata */
    public static function recordMysqli(mysqli $db, ?int $userId, ?string $sessionId, string $eventType, array $metadata = []): bool
    {
        $eventType = strtolower(trim($eventType));
        $sessionId = trim((string) $sessionId);
        if (!preg_match('/^[a-z][a-z0-9_.-]{1,49}$/', $eventType)
            || (($userId ?? 0) <= 0 && $sessionId === '')) {
            return false;
        }
        if (($userId ?? 0) <= 0 && !self::isTrackableGuest()) {
            return false;
        }
        try {
            $payload = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $statement = $db->prepare('INSERT INTO user_events(user_id,session_id,event_type,metadata,created_at) VALUES (?,?,?,?,NOW())');
            $statement->bind_param('isss', $userId, $sessionId, $eventType, $payload);
            $statement->execute();
            return true;
        } catch (Throwable $error) {
            // Analytics is fail-open: it must never interrupt checkout or cart.
            error_log('Customer event tracking failed: ' . $error->getMessage());
            return false;
        }
    }

    /**
     * Guests are tracked only when the browser sent back an existing session cookie and is not
     * a known bot. Crawlers and scripts open a fresh session on every request, so without this
     * each of their hits would be scored as a separate "guest customer".
     */
    public static function isTrackableGuest(): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }
        $agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($agent === '' || preg_match('~bot|crawl|spider|slurp|curl|wget|python-requests|httpclient|headless|preview~i', $agent) === 1) {
            return false;
        }
        $cookie = session_status() === PHP_SESSION_ACTIVE ? session_name() : (string) ini_get('session.name');
        return $cookie !== '' && !empty($_COOKIE[$cookie]);
    }

    public static function sessionId(): string
    {
        $sessionId = trim((string) ($_SESSION['chat_session_id'] ?? ''));
        if ($sessionId === '' && session_status() === PHP_SESSION_ACTIVE) {
            $sessionId = session_id();
        }
        return substr($sessionId, 0, 128);
    }

    public static function currentUserId(): ?int
    {
        $id = (int) ($_SESSION['IDNguoiDung'] ?? $_SESSION['ID_KhachHang'] ?? 0);
        return $id > 0 ? $id : null;
    }
}
