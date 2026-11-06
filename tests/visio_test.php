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
    $originalPath = $room->invitationPath();
    $room->updateForCurrentUser(['title' => 'Salle renommée', 'guest_lobby' => '0', 'member_camera_restricted' => '1',
        'guest_screen_restricted' => '1', 'guest_audio_muted' => '1', 'IDuser' => $member->getId(), 'settings_version' => 999]);
    visioExpect($room->invitationPath() === $originalPath && $room->get('title') === 'Salle renommée' && $room->isOwner((int)$owner->getId()), 'Settings preserve link and owner');
    visioExpect($room->policy()['version'] === 2 && $room->policy()['member']['camera_restricted'] && !$room->policy()['guest']['lobby'], 'Settings persisted and versioned');
    $claims = visioClaims(visioIssueAccess($room, (int)$owner->getId()));
    visioExpect($claims['context']['user']['access_role'] === 'owner' && $claims['context']['user']['lobby_bypass'], 'Owner has no configurable restrictions');
    visioExpect($claims['context']['room']['omo_policy']['guest']['screen_restricted'], 'Full room policy signed');
    visioReject(fn() => $room->updateForCurrentUser(['member_lobby' => ['1']]), 'invalid_settings');
    $room->updateForCurrentUser(['guest_lobby' => '1']);
    $organization = new \dbObject\Organization();
    $organization->set('name', 'Visio fixture ' . bin2hex(random_bytes(6)));
    visioExpect(!empty($organization->save()['status']), 'Fixture organization');
    $membership = new \dbObject\UserOrganization();
    foreach (['IDuser' => $owner->getId(), 'IDorganization' => $organization->getId(), 'active' => 1] as $key => $value) $membership->set($key, $value);
    visioExpect(!empty($membership->save()['status']), 'Fixture owner organization membership');
    $room->updateForCurrentUser(['IDorganization' => (string)$organization->getId()]);
    visioExpect($room->accessRole((int)$member->getId()) === 'guest', 'Other OMO accounts become guests in an organization room');
    visioReject(fn() => $room->updateForCurrentUser(['IDorganization' => '99999999']), 'invalid_organization');
    $membership2 = new \dbObject\UserOrganization();
    foreach (['IDuser' => $member->getId(), 'IDorganization' => $organization->getId(), 'active' => 1] as $key => $value) $membership2->set($key, $value);
    visioExpect(!empty($membership2->save()['status']) && $room->accessRole((int)$member->getId()) === 'member', 'Active organization members recognized');
    $membership2->set('active', 0); $membership2->save();
    visioExpect($room->accessRole((int)$member->getId()) === 'guest', 'Inactive membership grants no member access');
    $room->updateForCurrentUser(['IDorganization' => '']);
    visioReject(fn() => visioIssueAccess($room, (int)$member->getId()), 'login_required');
    visioReject(fn() => VideoRoom::createForCurrentUser(str_repeat('é', 191)), 'invalid_title');
    $_SESSION['currentUser'] = $member->getId();
    visioReject(fn() => $room->updateForCurrentUser(['title' => 'Stolen']), 'forbidden');
    visioExpect(VideoRoom::listForCurrentUser() === [], 'No other owner rooms in list');
    $invited = VideoRoom::findByInvitation((string)$room->get('code'));
    visioExpect($invited !== null && !$invited->canEdit() && !$invited->canViewDetail(), 'Invitation does not grant data management');
    $claims = visioClaims(visioIssueAccess($invited, (int)$member->getId()));
    visioExpect(!$claims['context']['user']['moderator'] && $claims['context']['user']['affiliation'] === 'member' && $claims['context']['user']['lobby_bypass'], 'Authenticated participant role');
    unset($_SESSION['currentUser']);
    $invited = VideoRoom::findByInvitation((string)$room->get('code'));
    $claims = visioClaims(visioIssueAccess($invited, 0));
    visioExpect(!$claims['context']['user']['moderator'] && !$claims['context']['user']['lobby_bypass'] && !isset($claims['context']['user']['name']), 'Guest stays in lobby and chooses a name in Jitsi');
    visioReject(fn() => VideoRoom::createForCurrentUser('Forbidden'), 'login_required');
    visioExpect(VideoRoom::findByInvitation('../anything') === null && VideoRoom::findByInvitation(str_repeat('0', 32)) === null, 'Malformed and unknown invitations rejected');
    $_SESSION['currentUser'] = $owner->getId();
    $password = 'room-' . bin2hex(random_bytes(8));
    $room->updateForCurrentUser(['room_password' => $password, 'member_lobby' => '1']);
    $encrypted = (string)$room->get('lobby_password_encrypted');
    $packed = base64_decode(substr($encrypted,3),true);
    $key = hash('sha256','omo-jitsi-room-password-v1:'.$_ENV['JITSI_APP_SECRET'].':'.$room->jitsiRoomName(),true);
    visioExpect(str_starts_with($encrypted,'v1:') && $encrypted !== $password && openssl_decrypt(substr($packed,12,-16),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($packed,0,12),substr($packed,-16)) === $password, 'Native password stored with authenticated encryption');
    $room->updateForCurrentUser(['room_password' => '', 'title' => 'Password retained']);
    visioExpect($room->get('lobby_password_encrypted') === $encrypted, 'Empty password retains existing encrypted secret');
    visioExpect(visioClaims(visioIssueAccess($room, (int)$owner->getId()))['context']['user']['lobby_bypass'], 'Owner keeps direct access');
    $_SESSION['currentUser'] = $member->getId();
    $access = visioIssueAccess($room,(int)$member->getId());
    $claims = visioClaims($access);
    visioExpect(!$claims['context']['user']['lobby_bypass'] && !$claims['context']['user']['moderator'], 'Native Jitsi handles password admission, not an OMO bypass');
    visioExpect($claims['context']['room']['omo_policy']['native_password'] === $encrypted && !str_contains(json_encode($access),$password) && !str_contains(json_encode($claims),$password), 'Only encrypted password envelope is sent to Jitsi');
    $_SESSION['currentUser'] = $owner->getId();
    $room->updateForCurrentUser(['room_password' => 'changed-' . $password]);
    visioExpect($room->get('lobby_password_encrypted') !== $encrypted, 'Replacing password updates encrypted configuration');
    visioReject(fn()=> $room->updateForCurrentUser(['room_password'=>str_repeat('a',73)]), 'invalid_room_password');
    visioReject(fn()=> $room->updateForCurrentUser(['room_password'=>'new','remove_room_password'=>'1']), 'invalid_room_password');
    $room->updateForCurrentUser(['room_password'=>'','remove_room_password'=>'1']);
    visioExpect(!$room->hasLobbyPassword() && $room->policy()['native_password'] === '', 'Native password can be removed without opening the lobby');
    unset($_SESSION['currentUser']);
    visioExpect(!visioClaims(visioIssueAccess($room,0))['context']['user']['lobby_bypass'], 'Removing password leaves the guest lobby active');
    $_ENV['JITSI_APP_SECRET'] = 'short';
    visioExpect(!visioConfig()['enabled'], 'Weak signing secret rejected');
    visioReject(fn() => visioIssueAccess($invited, 0), 'not_configured');
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
