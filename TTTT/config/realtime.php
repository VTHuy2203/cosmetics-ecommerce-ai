<?php
declare(strict_types=1);

function realtime_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function realtime_base64url_decode(string $value): ?string
{
    if ($value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
        return null;
    }
    $padding = (4 - strlen($value) % 4) % 4;
    $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);
    return is_string($decoded) ? $decoded : null;
}

function realtime_normalize_origin(string $url): ?string
{
    $parts = parse_url(trim($url));
    if (!is_array($parts) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        return null;
    }
    $scheme = strtolower((string) $parts['scheme']);
    $host = strtolower((string) $parts['host']);
    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    return $scheme . '://' . $host . $port;
}

/** @param array{sub:int,role:string,channel:string,context:string,origin:string} $identity */
function realtime_ticket_issue(array $identity, string $secret, int $now, int $ttl = 60): string
{
    if (strlen($secret) < 32 || $ttl < 15 || $ttl > 90) {
        throw new InvalidArgumentException('Realtime ticket configuration is invalid.');
    }
    $roleChannels = [
        'guest' => 'user_channel', 'customer' => 'user_channel',
        'shipper' => 'shipper_channel', 'employee' => 'staff_channel',
        'manager' => 'admin_channel', 'admin' => 'admin_channel',
    ];
    $role = $identity['role'] ?? '';
    $channel = $identity['channel'] ?? '';
    $origin = realtime_normalize_origin((string) ($identity['origin'] ?? ''));
    if (!isset($roleChannels[$role]) || $roleChannels[$role] !== $channel || $origin === null
        || !in_array($identity['context'] ?? '', ['customer', 'admin'], true)) {
        throw new InvalidArgumentException('Realtime identity is invalid.');
    }
    $payload = [
        'aud' => 'moshop-realtime-v1', 'sub' => max(0, (int) ($identity['sub'] ?? 0)),
        'role' => $role, 'channel' => $channel, 'context' => $identity['context'],
        'origin' => $origin, 'iat' => $now, 'exp' => $now + $ttl,
        'jti' => bin2hex(random_bytes(16)),
    ];
    if ($role !== 'guest' && $payload['sub'] < 1) {
        throw new InvalidArgumentException('Authenticated realtime identity has no subject.');
    }
    $encoded = realtime_base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    return $encoded . '.' . realtime_base64url_encode(hash_hmac('sha256', $encoded, $secret, true));
}

/** @return array<string,mixed>|null */
function realtime_ticket_verify(string $ticket, string $secret, string $requestOrigin, int $now): ?array
{
    if (strlen($secret) < 32 || strlen($ticket) > 2048 || substr_count($ticket, '.') !== 1) {
        return null;
    }
    [$encoded, $signature] = explode('.', $ticket, 2);
    $rawSignature = realtime_base64url_decode($signature);
    $json = realtime_base64url_decode($encoded);
    if ($rawSignature === null || $json === null
        || !hash_equals(hash_hmac('sha256', $encoded, $secret, true), $rawSignature)) {
        return null;
    }
    try {
        $payload = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }
    if (!is_array($payload)) {
        return null;
    }
    $roleChannels = [
        'guest' => 'user_channel', 'customer' => 'user_channel',
        'shipper' => 'shipper_channel', 'employee' => 'staff_channel',
        'manager' => 'admin_channel', 'admin' => 'admin_channel',
    ];
    $origin = realtime_normalize_origin($requestOrigin);
    $ticketOrigin = realtime_normalize_origin((string) ($payload['origin'] ?? ''));
    $iat = filter_var($payload['iat'] ?? null, FILTER_VALIDATE_INT);
    $exp = filter_var($payload['exp'] ?? null, FILTER_VALIDATE_INT);
    $sub = filter_var($payload['sub'] ?? null, FILTER_VALIDATE_INT);
    $role = $payload['role'] ?? '';
    if (($payload['aud'] ?? '') !== 'moshop-realtime-v1' || !isset($roleChannels[$role])
        || ($payload['channel'] ?? '') !== $roleChannels[$role]
        || !in_array($payload['context'] ?? '', ['customer', 'admin'], true)
        || !is_string($payload['jti'] ?? null) || !preg_match('/^[a-f0-9]{32}$/', $payload['jti'])
        || $origin === null || $ticketOrigin === null || !hash_equals($ticketOrigin, $origin)
        || $iat === false || $exp === false || $iat > $now + 5 || $iat < $now - 90
        || $exp <= $now || $exp > $iat + 90 || $sub === false || $sub < 0
        || ($role !== 'guest' && $sub < 1) || ($role === 'guest' && $sub !== 0)) {
        return null;
    }
    return $payload;
}
