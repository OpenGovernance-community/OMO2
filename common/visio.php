<?php
declare(strict_types=1);

function visioConfig(): array
{
    $url = rtrim(trim((string)envValue('JITSI_URL', '')), '/');
    $parsed = parse_url($url);
    $local = function_exists('envIsLocalRuntimeHost') && envIsLocalRuntimeHost();
    if (!is_array($parsed) || !filter_var($url, FILTER_VALIDATE_URL)
        || !in_array($parsed['scheme'] ?? '', $local ? ['https', 'http'] : ['https'], true)
        || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['query']) || isset($parsed['fragment'])
        || !empty($parsed['path'])) $url = '';
    $appId = trim((string)envValue('JITSI_APP_ID', 'omo2'));
    $secret = (string)envValue('JITSI_APP_SECRET', '');
    $xmpp = trim((string)envValue('JITSI_XMPP_DOMAIN', 'meet.jitsi'));
    return ['enabled' => envBool('JITSI_ENABLED', false) && $url !== ''
            && preg_match('/\A[A-Za-z0-9._-]{1,64}\z/D', $appId)
            && preg_match('/\A[A-Za-z0-9.-]{1,253}\z/D', $xmpp) && strlen($secret) >= 32,
        'url' => $url, 'appId' => $appId, 'secret' => $secret, 'xmpp' => $xmpp];
}

function visioBase64Url(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }

/** Server-derived identity and role only; never accept role claims from the browser. */
function visioIssueAccess(\dbObject\VideoRoom $room, int $userId): array
{
    $config = visioConfig();
    if (!$config['enabled']) throw new RuntimeException('not_configured');
    if (!$room->getId() || !preg_match('/\A[a-f0-9]{32}\z/D', (string)$room->get('code'))) throw new RuntimeException('not_found');
    $moderator = false;
    if ($userId > 0) {
        // The caller must have restored the session; disallow alternate identities.
        if ($userId !== (int)($_SESSION['currentUser'] ?? 0)) throw new RuntimeException('login_required');
        $user = new \dbObject\User();
        if (!$user->load($userId) || !$user->get('active')) throw new RuntimeException('login_required');
        $name = trim((string)$user->getScopedDisplayName());
        if ($name === '') throw new RuntimeException('invalid_name');
        $identity = 'user-' . hash_hmac('sha256', (string)$userId, $config['secret']);
        $moderator = $room->isOwner($userId);
    } else {
        // Jitsi collects the anonymous display name on its own prejoin screen.
        $name = '';
        $_SESSION['visio_guest_id'] ??= bin2hex(random_bytes(16));
        $identity = 'guest-' . $_SESSION['visio_guest_id'];
    }
    $role = $room->accessRole($userId);
    $policy = $room->policy();
    if ($policy['native_password'] !== '' && !str_starts_with($policy['native_password'], 'v1:')) throw new RuntimeException('room_password_reset_required');
    $settings = $role === 'owner' ? [] : $policy[$role];
    $now = time();
    $payload = ['iss' => $config['appId'], 'aud' => $config['appId'], 'sub' => $config['xmpp'],
        'room' => $room->jitsiRoomName(), 'iat' => $now, 'nbf' => $now - 10, 'exp' => $now + 7200,
        'context' => ['room' => ['omo_policy' => $policy], 'user' => ['id' => $identity, 'name' => mb_substr($name, 0, 120, 'UTF-8'),
            'moderator' => $moderator, 'affiliation' => $moderator ? 'owner' : 'member', 'access_role' => $role,
            'lobby_bypass' => $moderator || empty($settings['lobby'])]]];
    if ($userId <= 0) unset($payload['context']['user']['name']);
    $header = visioBase64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
    $body = visioBase64Url(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    $jwt = $header . '.' . $body . '.' . visioBase64Url(hash_hmac('sha256', $header . '.' . $body, $config['secret'], true));
    return ['jwt' => $jwt, 'moderator' => $moderator, 'role' => $role, 'settings' => $settings, 'lobbyBypass' => $payload['context']['user']['lobby_bypass'],
        'nativePasswordEnabled' => $room->hasLobbyPassword(),
        'name' => $name, 'expiresAt' => $payload['exp']];
}
