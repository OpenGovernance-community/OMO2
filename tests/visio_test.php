<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/visio.php';
require_once dirname(__DIR__) . '/includes/server_env_admin.php';

use dbObject\User;
use dbObject\VideoRoom;

function visioExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function visioReject(callable $action, string $expected): void
{
    try { $action(); } catch (RuntimeException $error) {
        visioExpect($error->getMessage() === $expected, 'Unexpected rejection: ' . $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $expected);
}
function visioClaims(array $access): array
{
    [$header, $body, $signature] = explode('.', $access['jwt']);
    visioExpect(hash_equals(visioBase64Url(hash_hmac('sha256', $header . '.' . $body, $_ENV['JITSI_APP_SECRET'], true)), $signature), 'JWT signature');
    return json_decode(base64_decode(strtr($body, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
}

$originalSession = $_SESSION;
$originalEnv = $_ENV;
$pdo = VideoRoom::getPdo();
$pdo->beginTransaction();
try {
    foreach (['JITSI_ENABLED' => 'true', 'JITSI_URL' => 'https://meet.example.test', 'JITSI_APP_ID' => 'omo2',
        'JITSI_APP_SECRET' => bin2hex(random_bytes(32)), 'JITSI_XMPP_DOMAIN' => 'meet.jitsi'] as $key => $value) $_ENV[$key] = $value;
    $users = [];
    foreach (['Owner', 'Member'] as $name) {
        $user = new User();
        foreach (['email' => 'visio-' . bin2hex(random_bytes(8)) . '@example.invalid', 'firstname' => $name, 'lastname' => 'Fixture', 'active' => 1] as $key => $value) $user->set($key, $value);
        visioExpect(!empty($user->save()['status']), 'Create fixture user');
        $users[] = $user;
    }
    [$owner, $member] = $users;
    $_SESSION['currentUser'] = $owner->getId();
    $room = VideoRoom::createForCurrentUser('Réunion de test');
    visioExpect($room->isOwner((int)$owner->getId()) && !$room->isOwner((int)$member->getId()), 'Room ownership');
    visioExpect(count(VideoRoom::listForCurrentUser()) === 1, 'Owner list');
    visioExpect(!str_contains($room->invitationPath(), 'jwt'), 'Clean invitation');
    $claims = visioClaims(visioIssueAccess($room, (int)$owner->getId()));
    visioExpect($claims['room'] === $room->jitsiRoomName() && $claims['sub'] === 'meet.jitsi' && $claims['iss'] === 'omo2' && $claims['aud'] === 'omo2', 'JWT scope');
    visioExpect($claims['exp'] - $claims['iat'] === 7200 && $claims['nbf'] <= time(), 'JWT validity');
    visioExpect($claims['context']['user']['moderator'] && $claims['context']['user']['lobby_bypass'] && $claims['context']['user']['name'] === 'Owner Fixture', 'Owner identity and role');
    visioReject(fn() => visioIssueAccess($room, (int)$member->getId()), 'login_required');
    visioReject(fn() => VideoRoom::createForCurrentUser(str_repeat('é', 191)), 'invalid_title');
    $_SESSION['currentUser'] = $member->getId();
    visioExpect(VideoRoom::listForCurrentUser() === [], 'No other owner rooms in list');
    $invited = VideoRoom::findByInvitation((string)$room->get('code'));
    visioExpect($invited !== null && !$invited->canEdit() && !$invited->canViewDetail(), 'Invitation does not grant data management');
    $claims = visioClaims(visioIssueAccess($invited, (int)$member->getId()));
    visioExpect(!$claims['context']['user']['moderator'] && $claims['context']['user']['affiliation'] === 'member' && $claims['context']['user']['lobby_bypass'], 'Authenticated participant role');
    unset($_SESSION['currentUser']);
    $invited = VideoRoom::findByInvitation((string)$room->get('code'));
    $claims = visioClaims(visioIssueAccess($invited, 0, 'Invité'));
    visioExpect(!$claims['context']['user']['moderator'] && !$claims['context']['user']['lobby_bypass'] && $claims['context']['user']['name'] === 'Invité', 'Guest stays in lobby');
    visioReject(fn() => visioIssueAccess($invited, 0, '  '), 'invalid_name');
    visioReject(fn() => VideoRoom::createForCurrentUser('Forbidden'), 'login_required');
    visioExpect(VideoRoom::findByInvitation('../anything') === null && VideoRoom::findByInvitation(str_repeat('0', 32)) === null, 'Malformed and unknown invitations rejected');
    $_ENV['JITSI_APP_SECRET'] = 'short';
    visioExpect(!visioConfig()['enabled'], 'Weak signing secret rejected');
    visioReject(fn() => visioIssueAccess($invited, 0, 'Guest'), 'not_configured');
    $_ENV['JITSI_APP_SECRET'] = str_repeat('x', 64);
    $adminSettings = array_intersect_key($_ENV, array_flip(['JITSI_ENABLED', 'JITSI_URL', 'JITSI_APP_ID', 'JITSI_APP_SECRET', 'JITSI_XMPP_DOMAIN']));
    visioExpect(serverEnvAdminValidateValues($adminSettings) === [], 'Valid server configuration accepted');
    $adminSettings['JITSI_APP_SECRET'] = 'short';
    visioExpect(serverEnvAdminValidateValues($adminSettings) !== [], 'Admin rejects enabled configuration with weak secret');
    foreach (['https://user:pass@meet.example.test', 'https://meet.example.test/path', 'https://meet.example.test?jwt=x', 'javascript:bad'] as $invalidUrl) {
        $_ENV['JITSI_URL'] = $invalidUrl;
        visioExpect(!visioConfig()['enabled'], 'Invalid service origin rejected');
    }
    echo "OK: video room ownership, invitations, identities, JWT signatures/roles, lobby claims and fail-closed configuration.\n";
} finally {
    $pdo->rollBack();
    $_SESSION = $originalSession;
    $_ENV = $originalEnv;
}
