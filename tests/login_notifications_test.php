<?php
declare(strict_types=1);

// Real database and authentication, with external deliveries intercepted.
$deliveries = [];
function notificationCenterSendEmail($user, $organization, $title, $body, $url)
{
    if (!empty($GLOBALS['failLoginDelivery'])) throw new RuntimeException('Test delivery failure');
    $GLOBALS['deliveries'][] = ['email', (int)$user->getId(), $organization, $body];
    return true;
}
function notificationCenterSendPush($userId, array $payload) { $GLOBALS['deliveries'][] = ['push', $userId, $payload]; }
function notificationCenterSendTelegram($user, $title, $body, $url) { $GLOBALS['deliveries'][] = ['telegram', (int)$user->getId()]; return true; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['REMOTE_ADDR'] = '192.0.2.123';
$_SERVER['HTTP_USER_AGENT'] = 'Login notification fixture browser';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/notification_center.php';

use dbObject\{Notification, NotificationPreference, UserLoginDevice, UserRemember};

$items = mcpFixtures();
$before = $_SESSION;
$cookies = $_COOKIE;
try {
    $uid = (int)$items['user']->getId();
    $oid = (int)$items['org']->getId();
    $otherOid = (int)$items['other_org']->getId();
    $email = (string)$items['user']->get('email');
    $new = 'user_login_new_device';
    $known = 'user_login_known_device';
    $cookieName = commonGetLoginDeviceCookieName($uid);
    $inbox = static fn() => Notification::getInboxForUser($uid, $oid);
    $login = static fn($remember = 0) => commonAttemptPasswordLogin($email, $items['password'], $remember);
    mcpCheck(isset(notificationCenterGetActiveEventGroups(0, $uid)['personal']), 'Personal events do not require an organization.');
    mcpCheck(NotificationPreference::getChannelsFor($uid, 0, $new)['in_app'] && !NotificationPreference::getChannelsFor($uid, 0, $new)['email'], 'Default is in-app, external channels are opt-in.');
    mcpCheck(!commonAttemptPasswordLogin($email, 'wrong-password')['status'] && !$inbox() && !isset($_COOKIE[$cookieName]), 'Rejected login neither notifies nor identifies a browser.');
    foreach ([$new, $known] as $key) {
        mcpCheck(NotificationPreference::saveChannels($uid, 0, $key, ['in_app' => 1, 'email' => 1, 'push' => 1, 'telegram' => 1]), 'Personal preferences can be saved without an organization.');
        mcpCheck(NotificationPreference::getChannelsFor($uid, $otherOid, $key)['email'], 'Preferences are shared across organizations.');
    }
    mcpCheck($login()['status'], 'First password login succeeds.');
    $first = $inbox();
    mcpCheck(count($first) === 1 && $first[0]->get('event_key') === $new && !$first[0]->get('IDorganization'), 'First login is personal and uses the new-device event.');
    mcpCheck(array_column($deliveries, 0) === ['push', 'telegram', 'email'] && $deliveries[2][2] === null, 'Every selected channel is dispatched without an organization.');
    mcpCheck(str_contains($first[0]->get('body'), $_SERVER['REMOTE_ADDR']) && str_contains($first[0]->get('body'), $_SERVER['HTTP_USER_AGENT']), 'Notification identifies the time, IP and browser.');
    mcpCheck(count(Notification::getInboxForUser($uid, $otherOid)) === 1 && Notification::getInboxForUser($uid + 1000000, $oid) === [], 'Personal notifications appear across own organizations only.');
    $token = $_COOKIE[$cookieName];
    $device = new UserLoginDevice();
    mcpCheck($device->load([['IDuser', $uid], ['token_hash', hash('sha256', $token)]]) && $device->get('token_hash') !== $token, 'Recognition token is stored hashed.');
    commonLogoutUser();
    mcpCheck($_COOKIE[$cookieName] === $token && commonRestoreRememberedUser() === 0, 'Logout preserves recognition; the device token cannot authenticate.');
    mcpCheck($login(1)['status'], 'Explicit remembered login succeeds.');
    mcpCheck(count($inbox()) === 2 && $inbox()[0]->get('event_key') === $known, 'Explicit login after logout is recognized and still notifies.');
    $rememberToken = bin2hex(random_bytes(32));
    mcpCheck(!empty(UserRemember::issue($uid, $rememberToken, $_SERVER['REMOTE_ADDR'], '', '', '')['status']), 'Remember fixture saved.');
    $_COOKIE[commonGetRememberCookieName()] = $rememberToken;
    $_SESSION = [];
    mcpCheck(commonRestoreRememberedUser() === $uid && count($inbox()) === 2 && count($deliveries) === 6, 'Automatic remember-me restoration stays silent.');
    mcpCheck(commonRestoreRememberedUser() === $uid && count($inbox()) === 2, 'Refreshing an active session stays silent.');
    NotificationPreference::saveChannels($uid, $oid, $known, []);
    mcpCheck($login()['status'] && count($inbox()) === 2 && count($deliveries) === 6, 'Disabling known-device channels silences that event.');
    unset($_COOKIE[$cookieName]);
    mcpCheck($login()['status'] && count($inbox()) === 3 && $inbox()[0]->get('event_key') === $new, 'Missing recognition cookie is a new browser.');
    $freshToken = $_COOKIE[$cookieName];
    $device = new UserLoginDevice();
    mcpCheck($device->load(['token_hash', hash('sha256', $freshToken)]), 'Second device is saved.');
    $device->set('expires_at', new DateTimeImmutable('-1 day')); $device->save();
    mcpCheck($login()['status'] && count($inbox()) === 4 && $_COOKIE[$cookieName] !== $freshToken, 'Expired device is new and receives a fresh recognition token.');
    mcpCheck(!UserLoginDevice::recordLogin($uid, 'malformed')['known'], 'Malformed recognition cookie is treated as new.');
    $items['second_user'] = mcpFixture(\dbObject\User::class, ['email' => bin2hex(random_bytes(8)) . '@example.invalid', 'active' => 1]);
    $foreign = UserLoginDevice::recordLogin((int)$items['second_user']->getId(), $token);
    mcpCheck(!$foreign['known'] && $foreign['token'] !== $token, 'A different account cannot reuse recognition.');

    $key = str_repeat('login-notification-test-key-', 2);
    putenv('AUTH_TOTP_ENCRYPTION_KEY=' . $key); $_ENV['AUTH_TOTP_ENCRYPTION_KEY'] = $key;
    $items['user']->set('totp_secret', commonTotpEncryptSecret(commonTotpGenerateSecret()));
    $items['user']->set('totp_enabled', 1);
    mcpCheck(!empty($items['user']->save()['status']), 'TOTP fixture saved.');
    $result = $login();
    mcpCheck(($result['error'] ?? '') === 'mfa_required' && count($inbox()) === 4 && count($deliveries) === 12, 'First factor does not notify while TOTP is pending.');
    $items['user']->set('totp_enabled', 0); $items['user']->save();
    NotificationPreference::saveChannels($uid, 0, $known, ['email' => 1]);
    $GLOBALS['failLoginDelivery'] = true;
    mcpCheck($login()['status'] && (int)$_SESSION['currentUser'] === $uid, 'Delivery failure cannot block a valid login.');
    echo "login_notifications_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
    $_COOKIE = $cookies;
}
