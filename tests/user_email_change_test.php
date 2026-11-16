<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = 'localtest.me';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{User, UserEmailChange, UserLoginToken, UserRemember, UserOrganization, Organization};

class EmailChangeTestOrganization extends Organization
{
    public function delete() { return \dbObject\DbObject::delete(); }
}

function emailChangeCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function emailChangeSave($object): void
{
    emailChangeCheck(!empty($object->save()['status']), 'Fixture save failed.');
}

// Exercise the real endpoint with a local SMTP sink and an isolated session.
if (($argv[1] ?? '') === 'endpoint') {
    $input = json_decode(base64_decode($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    $GLOBALS['mailHost'] = '127.0.0.1';
    $GLOBALS['mailPort'] = $input['port'];
    $GLOBALS['mailUser'] = 'noreply@example.org';
    $GLOBALS['mailSecure'] = '';
    $GLOBALS['mailAuth'] = false;
    $GLOBALS['mailTimeout'] = 3;
    $_ENV['AUTH_RATE_LIMITS_ENABLED'] = 'false';
    $_ENV['AUTH_SECURITY_LOG_ENABLED'] = 'false';
    $_SESSION = ['currentUser' => $input['user_id'], 'auth_security_version' => $input['version'],
        'common_csrf' => str_repeat('a', 64)];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_ORIGIN'] = $input['origin'] ?? 'https://localtest.me';
    $_SERVER['HTTPS'] = 'on';
    $_POST = $input['post'];
    if (($_POST['action'] ?? '') === 'render') {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['section' => 'profile', 'scope' => 'general'];
        chdir(dirname(__DIR__) . '/popup');
        ob_start();
        require dirname(__DIR__) . '/popup/profil_scope.php';
        echo json_encode(['html' => ob_get_clean()], JSON_THROW_ON_ERROR);
        exit;
    }
    require dirname(__DIR__) . '/ajax/user_email_change.php';
    exit;
}

function emailChangeEndpoint(User $user, int $port, array $post, string $origin = 'https://localtest.me'): array
{
    $input = ['user_id' => (int)$user->getId(), 'version' => (int)$user->get('security_version'),
        'port' => $port, 'post' => $post, 'origin' => $origin];
    $process = proc_open([PHP_BINARY, __FILE__, 'endpoint', base64_encode(json_encode($input, JSON_THROW_ON_ERROR))],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    emailChangeCheck(is_resource($process), 'Endpoint process starts.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    emailChangeCheck(proc_close($process) === 0, 'Endpoint process failed: ' . $error);
    $payload = json_decode($output, true);
    emailChangeCheck(is_array($payload), 'Endpoint returns JSON: ' . $output . $error);
    return $payload;
}

$fixtures = [];
$smtp = null;
$capture = tempnam(sys_get_temp_dir(), 'omo-email-change-');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
try {
    $nonce = bin2hex(random_bytes(6));
    foreach (['owner', 'other'] as $name) {
        $user = new User();
        $user->set('email', $name . '-' . $nonce . '@example.org');
        $user->set('active', 1);
        emailChangeSave($user);
        $fixtures[] = $users[$name] = $user;
    }
    $user = $users['owner'];
    $uid = (int)$user->getId();
    $oldEmail = $user->get('email');
    $newEmail = 'new-' . $nonce . '@example.org';
    emailChangeCheck((UserEmailChange::issue($uid, 'invalid', 0)['error'] ?? '') === 'invalid_email', 'Invalid addresses rejected.');
    emailChangeCheck((UserEmailChange::issue($uid, strtoupper($oldEmail), 0)['error'] ?? '') === 'same_email', 'Unchanged address rejected case-insensitively.');
    emailChangeCheck((UserEmailChange::issue($uid, $users['other']->get('email'), 0)['error'] ?? '') === 'email_in_use', 'Another primary email rejected.');

    $organization = new EmailChangeTestOrganization();
    $organization->set('name', 'Email change fixture ' . $nonce);
    emailChangeSave($organization);
    $fixtures[] = $organization;
    $membership = new UserOrganization();
    $membership->set('IDorganization', $organization->getId());
    $membership->set('IDuser', $users['other']->getId());
    $membership->set('active', 1);
    $membership->set('email', 'scoped-' . $nonce . '@example.org');
    emailChangeSave($membership);
    $fixtures[] = $membership;
    emailChangeCheck((UserEmailChange::issue($uid, strtoupper($membership->get('email')), 0)['error'] ?? '') === 'email_in_use', 'Another organization email rejected.');
    $ownMembership = new UserOrganization();
    $ownMembership->set('IDorganization', $organization->getId());
    $ownMembership->set('IDuser', $uid);
    $ownMembership->set('active', 1);
    $ownMembership->set('email', $newEmail);
    emailChangeSave($ownMembership);
    $fixtures[] = $ownMembership;

    $render = emailChangeEndpoint($user, 1, ['action' => 'render']);
    emailChangeCheck(str_contains($render['html'], 'Changer mon adresse principale de login')
        && str_contains($render['html'], 'data-email-change-start') && str_contains($render['html'], 'data-email-change-confirm'), 'General profile renders the complete email change flow.');
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $render['html']);
    $xpath = new DOMXPath($dom);
    emailChangeCheck($xpath->query('//form')->length === 3, 'Profile editor and both verification steps use separate forms.');
    emailChangeCheck($xpath->query('//*[@id="row_email"]/div[contains(@class, "admin-edit__field-content")]/following-sibling::*[@data-profile-email-change]')->length === 1,
        'Email change button and expandable fields appear directly below the primary email.');
    emailChangeCheck($xpath->query('//*[@id="row_email"]//*[@data-email-change-toggle]/following-sibling::*[@data-email-change-content and @hidden]')->length === 1,
        'The change form starts collapsed underneath its button.');
    emailChangeCheck($xpath->query('//form//form')->length === 0
        && $xpath->query('//*[@id="row_email"]//input[@form="profile-email-change-start-form"]')->length === 1
        && $xpath->query('//*[@id="row_email"]//input[@form="profile-email-change-confirm-form"]')->length === 2,
        'Inline email inputs belong to their own forms so profile saving is independent.');

    $smtp = proc_open([PHP_BINARY, __DIR__ . '/object_mail_smtp_fixture.php', $capture],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $smtpPipes);
    emailChangeCheck(is_resource($smtp), 'Local SMTP sink starts.');
    $address = trim(fgets($smtpPipes[1]));
    emailChangeCheck(str_starts_with($address, '127.0.0.1:'), 'SMTP stays on loopback.');
    $port = (int)substr(strrchr($address, ':'), 1);
    $blocked = emailChangeEndpoint($user, $port, ['action' => 'start', 'new_email' => $newEmail], 'https://foreign.example.org');
    emailChangeCheck(($blocked['error'] ?? '') === 'request', 'Foreign origins rejected.');
    $sent = emailChangeEndpoint($user, $port, ['action' => 'start', 'new_email' => $newEmail]);
    emailChangeCheck(!empty($sent['status']) && !isset($sent['old_code'], $sent['new_code']), 'Real endpoint sends codes without disclosing them.');
    $messages = array_map(static fn($line) => json_decode($line, true), file($capture, FILE_IGNORE_NEW_LINES));
    emailChangeCheck(count($messages) === 2, 'Exactly two messages delivered.');
    $codes = [];
    foreach ($messages as $index => $message) {
        $body = quoted_printable_decode($message['data']);
        emailChangeCheck(str_contains($body, 'text/plain') && str_contains($body, 'text/html'), 'Each email has readable text and HTML.');
        emailChangeCheck(preg_match('/Votre code de vérification est ([0-9]{6})\./u', $body, $match) === 1, 'Each email contains its code.');
        $codes[] = $match[1];
        emailChangeCheck(str_contains(implode('', $message['recipients']), $index === 0 ? $oldEmail : $newEmail), 'Each proof reaches its corresponding mailbox.');
    }
    [$oldCode, $newCode] = $codes;
    emailChangeCheck($oldCode !== $newCode, 'Mailbox codes differ.');
    $pending = new UserEmailChange();
    emailChangeCheck($pending->load(['IDuser', $uid], true), 'Pending request stored.');
    emailChangeCheck($pending->get('old_code_hash') !== $oldCode && $pending->get('new_code_hash') !== $newCode
        && password_verify($oldCode, $pending->get('old_code_hash')), 'Only code hashes stored.');
    emailChangeCheck((UserEmailChange::issue($uid, $newEmail, 0)['error'] ?? '') === 'rate_limited', 'Immediate resend blocked.');
    foreach ([[$oldCode, ''], ['', $newCode], [$newCode, $oldCode]] as [$old, $new]) {
        $result = UserEmailChange::confirm($uid, $sent['token'], $old, $new, 0);
        emailChangeCheck(($result['error'] ?? '') === 'invalid_codes', 'Both distinct proofs required in their own fields.');
        $user->load($uid, true);
        emailChangeCheck($user->get('email') === $oldEmail, 'Invalid proofs leave the email unchanged.');
    }
    $remember = bin2hex(random_bytes(32));
    emailChangeCheck(!empty(UserRemember::issue($uid, $remember, '127.0.0.1', 'Fixture', '', '')['status']), 'Remember token fixture.');
    $login = UserLoginToken::issue($uid, bin2hex(random_bytes(32)), password_hash('123456', PASSWORD_DEFAULT), '127.0.0.1');
    $fixtures[] = $login;
    $user->set('code', hash('sha256', 'old-reset'));
    $user->set('codeexpiration', new DateTime('+1 hour'));
    emailChangeSave($user);
    $confirmed = emailChangeEndpoint($user, $port, ['action' => 'confirm', 'token' => $sent['token'], 'old_code' => $oldCode, 'new_code' => $newCode]);
    emailChangeCheck(!empty($confirmed['status']) && $confirmed['email'] === $newEmail, 'Both codes change the primary email through the endpoint.');
    $user->load($uid, true);
    emailChangeCheck((int)$user->get('security_version') === 1 && !$user->get('code'), 'Security version advances and password reset proof is invalidated.');
    emailChangeCheck((int)User::findByLoginIdentifier($newEmail)?->getId() === $uid
        && User::findByLoginIdentifier($oldEmail) === null, 'Login lookup uses the new primary address.');
    $ownMembership->load($ownMembership->getId(), true);
    emailChangeCheck($ownMembership->get('email') === $newEmail, 'Organization contact data survives primary email changes.');
    emailChangeCheck(!UserRemember::findValidByToken($remember) && !UserLoginToken::findValidByToken($login->get('token')), 'Old login tokens revoked.');
    emailChangeCheck((UserEmailChange::confirm($uid, $sent['token'], $oldCode, $newCode, 1)['error'] ?? '') === 'expired', 'Consumed proof cannot be replayed.');

    // Failures, expiry, resending and collisions after the initial request.
    $request = UserEmailChange::issue($uid, $oldEmail, 1);
    emailChangeCheck((UserEmailChange::confirm($uid, $request['token'], $request['old_code'], $request['new_code'], 1)['error'] ?? '') === 'expired', 'Undelivered requests cannot change the email.');
    UserEmailChange::markDelivered($uid, $request['token']);
    for ($attempt = 0; $attempt < 5; $attempt++) UserEmailChange::confirm($uid, $request['token'], 'wrong', 'wrong', 1);
    emailChangeCheck((UserEmailChange::confirm($uid, $request['token'], $request['old_code'], $request['new_code'], 1)['error'] ?? '') === 'expired', 'Five failed attempts exhaust the request.');
    $pending->load(['IDuser', $uid], true);
    $pending->set('created_at', new DateTime('-2 minutes'));
    emailChangeSave($pending);
    $replacement = UserEmailChange::issue($uid, $oldEmail, 1);
    UserEmailChange::markDelivered($uid, $replacement['token']);
    emailChangeCheck((UserEmailChange::confirm($uid, $request['token'], $request['old_code'], $request['new_code'], 1)['error'] ?? '') === 'expired', 'Resending invalidates previous codes.');
    $other = $users['other'];
    $other->set('email', $oldEmail);
    emailChangeSave($other);
    emailChangeCheck((UserEmailChange::confirm($uid, $replacement['token'], $replacement['old_code'], $replacement['new_code'], 1)['error'] ?? '') === 'email_in_use', 'Final validation rechecks address availability.');
    $pending->load(['IDuser', $uid], true);
    $pending->set('expires_at', new DateTime('-1 minute'));
    emailChangeSave($pending);
    emailChangeCheck((UserEmailChange::confirm($uid, $replacement['token'], $replacement['old_code'], $replacement['new_code'], 1)['error'] ?? '') === 'expired', 'Expired proof rejected.');
    $user->load($uid, true);
    emailChangeCheck($user->get('email') === $newEmail, 'Failed requests preserve the current primary address.');

    $pending->load(['IDuser', $uid], true);
    $pending->set('created_at', new DateTime('-2 minutes'));
    emailChangeSave($pending);
    $deliveryFailure = emailChangeEndpoint($user, 1, ['action' => 'start', 'new_email' => 'failed-' . $nonce . '@example.org']);
    emailChangeCheck(($deliveryFailure['error'] ?? '') === 'delivery', 'SMTP failures return a recoverable delivery error.');
    $pending->load(['IDuser', $uid], true);
    emailChangeCheck(!(bool)$pending->get('ready'), 'Failed delivery leaves the pending request unusable.');
    $pending->set('created_at', new DateTime('-2 minutes'));
    emailChangeSave($pending);
    $secured = UserEmailChange::issue($uid, 'security-' . $nonce . '@example.org', 1);
    UserEmailChange::markDelivered($uid, $secured['token']);
    emailChangeCheck((UserEmailChange::confirm((int)$other->getId(), $secured['token'], $secured['old_code'], $secured['new_code'], 0)['error'] ?? '') === 'expired', 'Proofs are bound to the authenticated owner.');
    $user->revokeAuthentication();
    emailChangeCheck((UserEmailChange::confirm($uid, $secured['token'], $secured['old_code'], $secured['new_code'], 2)['error'] ?? '') === 'expired', 'Another security change invalidates pending proofs.');
    $pending->load(['IDuser', $uid], true);
    $pending->set('created_at', new DateTime('-2 minutes'));
    emailChangeSave($pending);
    $concurrent = UserEmailChange::issue($uid, 'concurrent-' . $nonce . '@example.org', 2);
    UserEmailChange::markDelivered($uid, $concurrent['token']);
    $input = ['user_id' => $uid, 'version' => 2, 'port' => $port, 'post' => ['action' => 'confirm',
        'token' => $concurrent['token'], 'old_code' => $concurrent['old_code'], 'new_code' => $concurrent['new_code']]];
    $consumers = [];
    for ($index = 0; $index < 2; $index++) {
        $process = proc_open([PHP_BINARY, __FILE__, 'endpoint', base64_encode(json_encode($input, JSON_THROW_ON_ERROR))],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        emailChangeCheck(is_resource($process), 'Concurrent consumer starts.');
        fclose($pipes[0]);
        $consumers[] = [$process, $pipes];
    }
    $successes = 0;
    foreach ($consumers as [$process, $pipes]) {
        $result = json_decode(stream_get_contents($pipes[1]), true);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        emailChangeCheck(proc_close($process) === 0 && is_array($result), 'Concurrent consumer returns JSON: ' . $errors);
        $successes += !empty($result['status']) ? 1 : 0;
    }
    emailChangeCheck($successes === 1, 'Concurrent confirmations consume the request exactly once.');
    echo "user_email_change_test: OK\n";
} finally {
    if (is_resource($smtp)) {
        proc_terminate($smtp);
        foreach ($smtpPipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        proc_close($smtp);
    }
    foreach (array_reverse($fixtures) as $fixture) $fixture->delete();
    if (is_file($capture)) unlink($capture);
}
