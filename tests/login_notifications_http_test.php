<?php
declare(strict_types=1);

// Local CLI only; disposable HTTP server, real endpoints and fixture accounts.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Notification, NotificationPreference, UserLoginToken};

$items = mcpFixtures();
$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/login-notifications-' . bin2hex(random_bytes(6));
mkdir($directory, 0700);
$process = null;
$key = str_repeat('http-login-notification-key-', 2);
putenv('AUTH_TOTP_ENCRYPTION_KEY=' . $key); $_ENV['AUTH_TOTP_ENCRYPTION_KEY'] = $key;
$router = $directory . '/router.php';
file_put_contents($router, '<?php '
    . 'function notificationCenterSendEmail(...$args) { return true; } '
    . 'function notificationCenterSendPush(...$args) { return true; } '
    . 'function notificationCenterSendTelegram(...$args) { return true; } '
    . 'require ' . var_export($root . '/shared_functions.php', true) . '; '
    . 'require_once ' . var_export($root . '/common/auth.php', true) . '; '
    . '$_ENV["AUTH_TOTP_ENCRYPTION_KEY"] = ' . var_export($key, true) . '; '
    . 'if ($_SERVER["REQUEST_URI"] === "/fixture/logout") { commonLogoutUser(); echo "ok"; return; } '
    . 'if ($_SERVER["REQUEST_URI"] === "/fixture/restore") { echo commonRestoreRememberedUser(); return; } '
    . '$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH); '
    . '$allowed = ["/common/login_password.php", "/common/login_verify.php", "/common/login_totp.php", '
    . '"/omo/api/parameters/notifications/index.php", "/omo/api/parameters/notifications/preferences.php"]; '
    . 'if (!in_array($path, $allowed, true)) { http_response_code(404); return; } '
    . 'require ' . var_export($root, true) . ' . $path;');
try {
    session_write_close();
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    $inbox = static fn() => Notification::getInboxForUser($uid, $oid);
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false); fclose($socket);
    $process = proc_open([PHP_BINARY, '-S', $address, $router], [0 => ['pipe', 'r'],
        1 => ['file', $directory . '/server.log', 'a'], 2 => ['file', $directory . '/server.log', 'a']], $pipes, $root);
    mcpCheck(is_resource($process), 'HTTP fixture server starts.'); fclose($pipes[0]);
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $socket = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
        if ($socket) { fclose($socket); break; }
        usleep(100000);
    }
    $jar = [];
    $request = static function (string $path, ?array $post = null) use ($address, &$jar): array {
        $curl = curl_init('http://' . $address . $path);
        $cookies = []; foreach ($jar as $name => $value) $cookies[] = $name . '=' . $value;
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_COOKIE => implode('; ', $cookies), CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Origin: http://localtest.me', 'X-Requested-With: XMLHttpRequest']]);
        if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
        $response = curl_exec($curl);
        if ($response === false) throw new RuntimeException(curl_error($curl));
        $length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $length);
        preg_match_all('/^Set-Cookie: ([^=;]+)=([^;]*)/mi', $headers, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ($match[2] === '' || $match[2] === 'deleted') unset($jar[$match[1]]);
            else $jar[$match[1]] = $match[2];
        }
        return [curl_getinfo($curl, CURLINFO_RESPONSE_CODE), $headers, substr($response, $length)];
    };
    $password = ['email' => $items['user']->get('email'), 'password' => $items['password'], 'remember' => 1];
    [$status, $headers, $body] = $request('/common/login_password.php', $password);
    mcpCheck($status === 200 && json_decode($body, true)['status'] === 'ok' && count($inbox()) === 1, 'Password endpoint emits one login notification.');
    $deviceName = commonGetLoginDeviceCookieName($uid);
    mcpCheck(isset($jar[$deviceName]) && preg_match('/Set-Cookie: ' . preg_quote($deviceName, '/') . '=[^\r\n]*HttpOnly/i', $headers), 'Recognition cookie is persisted and HttpOnly.');
    unset($jar[session_name()]);
    [, , $body] = $request('/fixture/restore');
    mcpCheck((int)$body === $uid && count($inbox()) === 1, 'Actual remember cookie restores a session without notification.');
    [$status, , $body] = $request('/omo/api/parameters/notifications/index.php?oid=' . $oid);
    mcpCheck($status === 200 && str_contains($body, 'preferences[user_login_new_device][email]')
        && str_contains($body, 'preferences[user_login_known_device][in_app]'), 'Settings render both personal login events.');
    mcpCheck(preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $body, $match) === 1, 'Settings expose a CSRF token.');
    [$status] = $request('/omo/api/parameters/notifications/preferences.php?oid=' . $oid, ['csrf_token' => 'invalid']);
    mcpCheck($status === 403, 'Preferences reject invalid CSRF.');
    [$status, , $body] = $request('/omo/api/parameters/notifications/preferences.php?oid=' . $oid,
        ['csrf_token' => $match[1], 'preferences' => ['user_login_known_device' => ['in_app' => 1, 'email' => 1]]]);
    mcpCheck($status === 200 && json_decode($body, true)['status'], 'Settings save through the real endpoint.');
    mcpCheck(NotificationPreference::getChannelsFor($uid, (int)$items['other_org']->getId(), 'user_login_known_device')['email'], 'Saved personal preference applies to another organization.');
    $request('/fixture/logout');
    mcpCheck(isset($jar[$deviceName]), 'Logout retains the recognition cookie.');
    $secret = commonTotpGenerateSecret();
    $items['user']->set('totp_enabled', 1); $items['user']->set('totp_secret', commonTotpEncryptSecret($secret));
    mcpCheck(!empty($items['user']->save()['status']), 'TOTP enabled.');
    [, , $body] = $request('/common/login_password.php', $password);
    $pending = json_decode($body, true);
    mcpCheck(($pending['status'] ?? '') === 'mfa_required' && count($inbox()) === 1, 'Password first factor is silent while TOTP is pending: ' . $body);
    [, , $body] = $request('/common/login_totp.php', ['token' => $pending['mfa_token'], 'code' => commonTotpGetCode($secret)]);
    mcpCheck(json_decode($body, true)['status'] === 'ok' && count($inbox()) === 2
        && $inbox()[0]->get('event_key') === 'user_login_known_device', 'TOTP completion emits exactly one known-device notification.');
    $request('/fixture/logout');
    $items['user']->set('totp_enabled', 0); $items['user']->save();
    $token = bin2hex(random_bytes(32)); $code = 'ABC123';
    mcpCheck((bool)UserLoginToken::issue($uid, $token, password_hash($code, PASSWORD_DEFAULT), '127.0.0.1'), 'Email-code token issued.');
    [, , $body] = $request('/common/login_verify.php', ['token' => $token, 'code' => $code]);
    mcpCheck(json_decode($body, true)['status'] === 'ok' && count($inbox()) === 3, 'Email-code login emits exactly one notification.');
    echo "login_notifications_http_test: OK\n";
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    mcpCleanup($items);
    foreach ([$router, $directory . '/server.log'] as $file) if (is_file($file)) unlink($file);
    rmdir($directory);
}
