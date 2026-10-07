<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{User, UserRemember, UserLoginToken, UserOrganization, Holon};

// Concurrent consumers use independent PHP processes and database connections.
if (($argv[1] ?? '') === 'consume') {
    $user = new User();
    $user->load((int)$argv[2], true);
    echo $user->consumePasswordResetCode($argv[3], commonHashUserPassword('Concurrent-Test-Password!48')) ? '1' : '0';
    exit;
}

$items = [];
$jar = tempnam(sys_get_temp_dir(), 'omo-security-');
$uploadPath = tempnam(sys_get_temp_dir(), 'omo-upload-');
$storedImage = '';
$base = 'https://localtest.me';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

function securityHttp(string $path, ?array $body = null, ?string $origin = 'https://localtest.me', bool $multipart = false): array
{
    global $jar, $base;
    $curl = curl_init($base . $path);
    $headers = ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'];
    if ($origin !== null) $headers[] = 'Origin: ' . $origin;
    if ($body !== null && !$multipart) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'], CURLOPT_PROXY => '',
        CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar, CURLOPT_HTTPHEADER => $headers]);
    if ($body !== null) curl_setopt_array($curl, [CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $multipart ? $body : http_build_query($body)]);
    $response = curl_exec($curl);
    mcpCheck(is_string($response), 'Local request failed: ' . curl_error($curl));
    return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => $response,
        'json' => json_decode($response, true)];
}

try {
    $nonce = bin2hex(random_bytes(6));
    $password = 'Security-Fixture-Password!48-' . $nonce;
    $items['user'] = $user = mcpFixture(User::class, ['email' => 'sec-' . $nonce . '@example.invalid',
        'password' => commonHashUserPassword($password), 'active' => 1, 'siteadmin' => 0,
        'allow_password_login' => 1, 'security_version' => 0, 'parameters' => ['isSiteAdmin' => false]]);
    $id = (int)$user->getId();
    $originalEmail = $user->get('email');
    $items['other'] = $other = mcpFixture(User::class, ['email' => 'sec-other-' . $nonce . '@example.invalid', 'active' => 1]);
    $items['org'] = $org = mcpFixture(McpTestOrganization::class, ['name' => 'Security ' . $nonce]);
    $items['membership'] = $membership = mcpFixture(UserOrganization::class, ['IDuser' => $id,
        'IDorganization' => $org->getId(), 'active' => 1, 'parameters' => ['isAdmin' => false]]);
    $membership->loadProfileInput(['id' => 999999, 'IDuser' => $other->getId(), 'active' => 0,
        'parameters' => ['isAdmin' => true], 'username' => 'Allowed']);
    mcpCheck((int)$membership->getId() !== 999999 && (int)$membership->get('IDuser') === $id
        && (bool)$membership->get('active') && !$membership->isOrganizationAdmin()
        && $membership->get('username') === 'Allowed', 'Organization mass assignment rejected');

    foreach ([null, '', [], str_repeat('a', 63)] as $invalid) mcpCheck(User::findByPasswordResetCode($invalid) === null, 'Invalid recovery code rejected');
    $reset = securityHttp('/ajax/createaccount.php', ['id' => $id, 'code' => '', 'password' => $password, 'password2' => $password]);
    mcpCheck($reset['status'] === 403, 'Empty-code account takeover blocked over HTTP');
    $login = securityHttp('/common/login_password.php', ['email' => $originalEmail, 'password' => $password, 'remember' => 1]);
    mcpCheck(($login['json']['status'] ?? '') === 'ok', 'Normal password login works');
    foreach (['https://evil.invalid', 'https://other.localtest.me', 'http://localtest.me', null] as $origin) {
        mcpCheck(securityHttp('/ajax/saveaccount.php', ['firstname' => 'CSRF'], $origin)['status'] === 403, 'Cross-origin or tokenless mutation blocked');
    }
    $profile = securityHttp('/ajax/saveaccount.php', ['id' => $other->getId(), 'siteadmin' => 1,
        'security_version' => 0, 'active' => 0, 'email' => 'attacker@example.invalid', 'password' => 'raw-hash',
        'totp_enabled' => 1, 'totp_secret' => 'attacker-secret', 'telegramID' => '999',
        'parameters' => ['isSiteAdmin' => true, 'basic' => 'unsafe'], 'firstname' => 'Allowed']);
    mcpCheck(!empty($profile['json']['status']), 'Normal profile update accepted');
    $user->load($id, true);
    mcpCheck(!$user->isSiteAdmin() && (int)$user->get('active') === 1 && $user->get('email') === $originalEmail
        && commonVerifyUserPassword($password, (string)$user->get('password')) && !(bool)$user->get('totp_enabled')
        && !$user->get('telegramID') && $user->get('firstname') === 'Allowed', 'Protected fields remain unchanged in database');
    mcpCheck((int)$user->get('allow_password_login') === 1, 'Preference save preserves password-login setting');
    $textPayload = '</textarea><img id="security-editor-xss-proof" src=x onerror=alert(1)>';
    securityHttp('/ajax/saveaccount.php', ['presentation' => $textPayload]);
    $form = securityHttp('/popup/profil_scope.php?section=profile');
    $formDom = new DOMDocument();
    @$formDom->loadHTML($form['body']);
    mcpCheck((new DOMXPath($formDom))->query('//*[@id="security-editor-xss-proof"]')->length === 0, 'Profile text cannot escape its textarea');
    mcpCheck(preg_match('/name=[\x27\"]_csrf[\x27\"] value=[\x27\"]([a-f0-9]{64})/', $form['body'], $match) === 1, 'Session form token rendered');
    mcpCheck(!empty(securityHttp('/ajax/saveaccount.php', ['firstname' => 'Token allowed', '_csrf' => $match[1]], null)['json']['status']), 'Session token supports clients without Origin');
    mcpCheck(securityHttp('/ajax/totp_setup.php', ['action' => 'disable'])['status'] === 422, 'MFA cannot be disabled without a valid code');
    if (commonTotpGetEncryptionKey() !== null) {
        $secret = commonTotpGenerateSecret();
        $user->set('totp_enabled', 1);
        $user->set('totp_secret', commonTotpEncryptSecret($secret));
        $user->save();
        mcpCheck(securityHttp('/ajax/totp_setup.php', ['action' => 'disable', 'code' => 'invalid'])['status'] === 422, 'Invalid MFA code rejected');
        mcpCheck(!empty(securityHttp('/ajax/totp_setup.php', ['action' => 'disable', 'code' => commonTotpGetCode($secret)])['json']['status']), 'Valid MFA disable succeeds');
        $user->load($id, true);
        mcpCheck(!(bool)$user->get('totp_enabled') && (int)$user->get('security_version') === 1, 'MFA changes revoke other sessions');
    }
    $versionBeforeReset = (int)$user->get('security_version');

    require_once dirname(__DIR__) . '/common/topbar.php';
    $payload = '</script><img id="security-xss-proof" src=x onerror=alert(1)>';
    ob_start();
    commonRenderTopbar(['profile' => ['data' => ['displayName' => $payload, 'email' => $originalEmail]]]);
    $html = ob_get_clean();
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    mcpCheck((new DOMXPath($dom))->query('//*[@id="security-xss-proof"]')->length === 0, 'Stored profile cannot escape the inline script');
    mcpCheck(preg_match('/window.commonTopbarConfig = (.*);/u', $html, $config) === 1
        && is_array(json_decode($config[1], true, 512, JSON_THROW_ON_ERROR)), 'Topbar script remains valid JSON');

    file_put_contents($uploadPath, '<?php echo "must-never-execute"; ?>');
    $upload = securityHttp('/ajax/saveaccount.php', ['image' => 'newimage',
        'image_file' => new CURLFile($uploadPath, 'image/jpeg', 'payload.PHP')], $base, true);
    mcpCheck($upload['status'] === 400 && empty($upload['json']['status']), 'Fake image and uppercase executable upload rejected');
    $image = imagecreatetruecolor(8, 8);
    imagepng($image, $uploadPath);
    unset($image);
    $upload = securityHttp('/ajax/saveaccount.php', ['image' => 'newimage',
        'image' . '_file' => new CURLFile($uploadPath, 'image/png', 'payload.phtml')], $base, true);
    mcpCheck(!empty($upload['json']['status']), 'Real image safely accepted regardless of client filename');
    $user->load($id, true);
    $storedImage = (string)$user->get('image');
    mcpCheck(str_ends_with($storedImage, '.webp') && is_file($_SERVER['DOCUMENT_ROOT'] . $storedImage), 'Upload reencoded with server-generated filename: ' . $storedImage);

    $items['root'] = $root = mcpFixture(McpTestHolon::class, ['IDtypeholon' => 4, 'IDuser' => $id, 'name' => 'Private root', 'active' => 1]);
    $items['alien'] = $alien = mcpFixture(McpTestHolon::class, ['IDtypeholon' => 4, 'IDuser' => $other->getId(), 'name' => 'Other root', 'active' => 1]);
    $tree = ['type' => '4', 'name' => 'Private root', 'ID' => $root->getId(), 'IDdb' => $root->getId(), 'children' => []];
    mcpCheck(Holon::validateLegacyCircleInput($tree, $id), 'Owned legacy root allowed');
    $tree['children'][] = ['name' => 'Injected', 'IDdb' => $alien->getId(), 'children' => [], 'data' => []];
    mcpCheck(!Holon::validateLegacyCircleInput($tree, $id), 'Foreign child rejected before writes');
    mcpCheck(!Holon::validateLegacyCircleInput(['type' => '4', 'name' => 'Injected', 'IDdb' => $alien->getId()], $id), 'Foreign legacy root rejected');

    $items['pending'] = $pending = mcpFixture(User::class, ['email' => 'sec-pending-' . $nonce . '@example.invalid',
        'active' => 0, 'activation_pending' => 1]);
    $pendingCode = $pending->issuePasswordResetCode();
    $confirmation = securityHttp('/common/confirm.php?code=' . $pendingCode);
    mcpCheck(str_contains($confirmation['body'], 'name=\'code\'') && str_contains($confirmation['body'], $pendingCode), 'Legacy confirmation sends the original token');
    mcpCheck($pending->consumePasswordResetCode($pendingCode, commonHashUserPassword($password))
        && (int)$pending->get('active') === 1 && !(int)$pending->get('activation_pending'), 'Verified pending registration becomes active');
    $other->set('active', 0);
    $other->save();
    mcpCheck(!$other->activateForVerifiedLogin(), 'Disabled established account cannot reactivate through login');

    foreach (['/.env', '/.git/config', '/docker/app/.env.private', '/sql/2026-10-06-01-auth-security.sql',
        '/img/upload/payload.PHP', '/img/upload/payload.phtml', '/telegram/data/1.txt'] as $path) {
        mcpCheck(securityHttp($path)['status'] === 403, 'Private or executable path denied: ' . $path);
    }
    mcpCheck(securityHttp('/telegram/memo_hook.php', ['update_id' => 1])['status'] === 403, 'Forged Telegram webhook rejected');
    mcpCheck(securityHttp('/omo/health.php')['status'] === 200, 'Public health endpoint remains available');

    $rawRemember = bin2hex(random_bytes(32));
    mcpCheck(!empty(UserRemember::issue($id, $rawRemember, '127.0.0.1', 'Security test', 'test', 'test')['status']), 'Remember token issued');
    $remember = UserRemember::findValidByToken($rawRemember);
    mcpCheck($remember && $remember->get('token') === hash('sha256', $rawRemember), 'Only token digest stored');
    $items['loginToken'] = $loginToken = UserLoginToken::issue($id, bin2hex(random_bytes(32)), hash('sha256', '123456'), '127.0.0.1');
    $items['client'] = $client = \dbObject\McpOauthClient::register('Security fixture', ['https://client.example.invalid/callback']);
    $oauthCode = \dbObject\McpOauthGrant::issueCode($client, $id, (int)$org->getId(), mcpAuthorizationRequest($client));
    $oauth = \dbObject\McpOauthGrant::exchange($client, mcpExchangeRequest($client, $oauthCode));
    mcpCheck($oauth !== null && \dbObject\McpOauthGrant::authenticate($oauth['access_token'], omoMcpPublicUrl()) !== null, 'MCP access issued before recovery');
    $items['grant'] = new \dbObject\McpOauthGrant();
    $items['grant']->load(\dbObject\McpOauthGrant::authenticate($oauth['access_token'], omoMcpPublicUrl())['id']);
    $code = $user->issuePasswordResetCode();
    mcpCheck(strlen($code) === 64 && $user->get('code') === hash('sha256', $code), 'High-entropy recovery token stored hashed');
    $user->set('codeexpiration', new DateTime('-1 second'));
    $user->save();
    mcpCheck(User::findByPasswordResetCode($code) === null && !$user->consumePasswordResetCode($code, commonHashUserPassword($password)), 'Expired recovery rejected');
    $code = $user->issuePasswordResetCode();
    $newPassword = 'Changed-Security-Password!72';
    $stale = new User();
    $stale->load($id, true);
    $reset = securityHttp('/ajax/createaccount.php', ['id' => $id, 'code' => $code, 'password' => $newPassword,
        'password2' => $newPassword, 'siteadmin' => 1]);
    mcpCheck(!empty($reset['json']['status']), 'Valid legacy recovery works');
    mcpCheck(!UserRemember::findValidByToken($rawRemember), 'All remember tokens revoked');
    mcpCheck(!UserLoginToken::findValidByToken($loginToken->get('token')), 'Pending login token revoked');
    mcpCheck(\dbObject\McpOauthGrant::authenticate($oauth['access_token'], omoMcpPublicUrl()) === null, 'MCP bearer access revoked by recovery');
    $user->load($id, true);
    mcpCheck((int)$user->get('security_version') === $versionBeforeReset + 1 && !$user->isSiteAdmin()
        && !$user->consumePasswordResetCode($code, commonHashUserPassword($password)), 'Recovery consumed once and session version incremented');
    $stale->set('firstname', 'Stale write');
    mcpCheck(empty($stale->save()['status']), 'Stale object cannot overwrite credentials after revocation');
    mcpCheck(empty(securityHttp('/ajax/saveaccount.php', ['firstname' => 'Stale session'])['json']['status']), 'Previous browser session revoked');
    mcpCheck(($login = securityHttp('/common/login_password.php', ['email' => $originalEmail, 'password' => $newPassword]))['json']['status'] === 'ok', 'Login with changed password works');
    $change = securityHttp('/ajax/saveaccount.php', ['current_password' => $newPassword,
        'new_password' => $password, 'new_password_confirm' => $password, 'allow_password_login' => '1']);
    mcpCheck(!empty($change['json']['status']), 'Authenticated password change works atomically');
    mcpCheck(!empty(securityHttp('/ajax/saveaccount.php', ['firstname' => 'Fresh session'])['json']['status']), 'Changing browser retains refreshed session');

    $user->load($id, true);
    $code = $user->issuePasswordResetCode();
    $processes = [];
    for ($i = 0; $i < 2; $i++) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, 'consume', (string)$id, $code],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        mcpCheck(is_resource($process), 'Concurrent recovery process started');
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    $results = [];
    foreach ($processes as [$process, $pipes]) {
        $results[] = trim(stream_get_contents($pipes[1]));
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        mcpCheck(proc_close($process) === 0, 'Concurrent recovery finished: ' . $error);
    }
    sort($results);
    mcpCheck($results === ['0', '1'], 'Exactly one simultaneous recovery succeeds');
    $user->load($id, true);
    $code = $user->issuePasswordResetCode();
    $modern = securityHttp('/common/password_reset.php', ['code' => $code,
        'password' => $password, 'password_confirm' => $password]);
    $user->load($id, true);
    mcpCheck($modern['status'] === 200 && commonVerifyUserPassword($password, (string)$user->get('password'))
        && User::findByPasswordResetCode($code) === null, 'Modern recovery also consumes and revokes');
    $previousHost = $_SERVER['HTTP_HOST'] ?? null;
    $_SERVER['HTTP_HOST'] = 'attacker.invalid';
    try {
        $link = commonSecurityUrl('/common/password_reset.php?code=test');
        mcpCheck(!str_contains($link, 'attacker.invalid'), 'Recovery link cannot inherit an attacker Host');
    } catch (RuntimeException $error) {
        mcpCheck(str_contains($error->getMessage(), 'AUTH_PUBLIC_URL'), 'Missing canonical URL fails closed');
    } finally {
        if ($previousHost === null) unset($_SERVER['HTTP_HOST']); else $_SERVER['HTTP_HOST'] = $previousHost;
    }
    mcpCheck(commonSecurityRequestScheme(['REMOTE_ADDR' => '203.0.113.254', 'SERVER_PORT' => '80',
        'HTTP_X_FORWARDED_PROTO' => 'https']) === 'http', 'Untrusted forwarded protocol ignored');
    echo "security_hardening_test: OK (HTTP recovery, mass assignment, CSRF, uploads, access revocation, concurrent recovery, private paths, legacy isolation, Telegram)\n";
} finally {
    UserRemember::revokeForUser((int)($items['user'] ?? new User())->getId());
    mcpCleanup($items);
    foreach ([$jar, $uploadPath] as $path) if (is_file($path)) unlink($path);
    if ($storedImage !== '' && str_starts_with($storedImage, '/img/upload/user/') && is_file($_SERVER['DOCUMENT_ROOT'] . $storedImage)) unlink($_SERVER['DOCUMENT_ROOT'] . $storedImage);
}
