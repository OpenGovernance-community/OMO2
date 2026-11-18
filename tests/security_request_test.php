<?php
declare(strict_types=1);

// The HTTP fixture uses the real guards and file headers, without a database or credentials.
if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true)) { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/document/download.php';

if (PHP_SAPI === 'cli-server') {
    session_start();
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path === '/token') {
        header('Content-Type: application/json');
        echo json_encode(['token' => commonCsrfToken()]);
    } elseif ($path === '/file') {
        commonDocumentFileSendHeaders("rapport-été.html", (string)($_GET['mime'] ?? ''), !empty($_GET['inline']));
        echo 'INERT_TEST_CONTENT';
    } else {
        commonGuardBrowserMutation();
        header('Content-Type: application/json');
        echo json_encode(['allowed' => true]);
    }
    exit;
}

function securityRequestCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

foreach (['/\\evil.invalid/path', '//evil.invalid', 'https://evil.invalid', "https:\tevil.invalid", "/safe\r\nLocation: evil", "/\0evil"] as $path) {
    securityRequestCheck(commonNormalizeLocalPath($path, '/safe') === '/safe', 'Reject unsafe return path: ' . json_encode($path));
}
securityRequestCheck(commonNormalizeLocalPath('/\\evil.invalid', '//fallback.invalid') === '/', 'Validate the fallback too');
securityRequestCheck(commonNormalizeLocalPath('', 'https://fallback.invalid') === '/', 'Reject absolute fallback');
foreach (['/omo/o/17?tab=documents#details', '/doc/42/ABC12345', '/common/login_verify.php?token=a%2Fb'] as $path) {
    securityRequestCheck(commonNormalizeLocalPath($path) === $path, 'Preserve local paths and encoded query values');
}
securityRequestCheck(commonNormalizeLocalPath('omo/') === '/omo/', 'Preserve relative local paths');
securityRequestCheck(commonBuildLoginFeedbackUrl('/\\evil.invalid', ['login_error' => 'invalid']) === '/?login_error=invalid', 'Feedback must not restore an unsafe redirect');

$socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
securityRequestCheck(is_resource($socket), 'Reserve an available local port');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$base = 'http://' . $address;
$cookieJar = tempnam(sys_get_temp_dir(), 'omo-security-cookie-');
$log = tempnam(sys_get_temp_dir(), 'omo-security-http-');
$pipes = [];
$process = proc_open([PHP_BINARY, '-S', $address, __FILE__],
    [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, dirname(__DIR__));
securityRequestCheck(is_resource($process), 'Start isolated local HTTP fixture');
fclose($pipes[0]);

$request = static function (string $path, ?array $body = null, ?string $origin = null, array $extraHeaders = []) use ($base, $cookieJar): array {
    $headers = $extraHeaders;
    if ($origin !== null) $headers[] = 'Origin: ' . $origin;
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 1, CURLOPT_TIMEOUT => 5, CURLOPT_PROXY => '',
        CURLOPT_COOKIEFILE => $cookieJar, CURLOPT_COOKIEJAR => $cookieJar, CURLOPT_HTTPHEADER => $headers]);
    if ($body !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($body)]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    return ['status' => $status, 'headers' => substr((string)$response, 0, $headerSize),
        'body' => substr((string)$response, $headerSize)];
};

try {
    for ($attempt = 0; $attempt < 100; $attempt++) {
        if (($response = $request('/token'))['status'] === 200) break;
        usleep(50000);
    }
    securityRequestCheck($response['status'] === 200, 'HTTP fixture must start: ' . file_get_contents($log));
    $token = json_decode($response['body'], true)['token'];
    // No currentUser is set: this specifically covers the pre-login bypass.
    foreach (['https://evil.invalid', $base . '.evil.invalid', 'https://' . $address, 'http://other.localtest.me', 'null', '', null] as $origin) {
        securityRequestCheck($request('/common/login_password.php', ['email' => 'test@example.invalid'], $origin)['status'] === 403,
            'Reject anonymous cross-origin or tokenless POST: ' . json_encode($origin));
    }
    securityRequestCheck($request('/common/login_password.php', ['_csrf' => $token], 'https://evil.invalid')['status'] === 403, 'Token must not override an external Origin');
    securityRequestCheck($request('/guard', [], $base)['status'] === 200, 'Accept exact same-origin POST');
    securityRequestCheck($request('/guard', ['_csrf' => $token])['status'] === 200, 'Accept a session-bound token without Origin');
    securityRequestCheck($request('/guard', ['_csrf' => str_repeat('0', 64)])['status'] === 403, 'Reject wrong session token');
    securityRequestCheck($request('/guard', ['_csrf' => $token], null, ['Sec-Fetch-Site: cross-site'])['status'] === 403, 'Reject explicit cross-site metadata even with a token');
    securityRequestCheck($request('/guard')['status'] === 200, 'Read requests remain available');
    securityRequestCheck($request('/api/v1/records', [])['status'] === 200, 'Stateless API keeps its own authentication boundary');

    foreach (['text/html', 'image/svg+xml', 'application/xhtml+xml', 'text/xml', 'text/plain', 'application/octet-stream'] as $mime) {
        $file = $request('/file?inline=1&mime=' . rawurlencode($mime));
        securityRequestCheck(str_contains($file['headers'], 'Content-Disposition: attachment;'), 'Download active or unrecognized types: ' . $mime);
        securityRequestCheck(str_contains($file['headers'], 'X-Content-Type-Options: nosniff'), 'Do not allow MIME sniffing');
        securityRequestCheck($file['body'] === 'INERT_TEST_CONTENT', 'Keep exact downloaded contents');
    }
    foreach (['application/pdf', 'image/png', 'image/jpeg', 'audio/mpeg', 'video/mp4', 'IMAGE/PNG; charset=UTF-8'] as $mime) {
        $file = $request('/file?inline=1&mime=' . rawurlencode($mime));
        securityRequestCheck(str_contains($file['headers'], 'Content-Disposition: inline;'), 'Keep safe media previews: ' . $mime);
    }
    securityRequestCheck(str_contains($request('/file?mime=image/png')['headers'], 'Content-Disposition: attachment;'), 'Inline remains opt-in');
    securityRequestCheck(str_contains($request('/file?inline=1&mime=' . rawurlencode("text/html\r\nX-Injected: yes"))['headers'], 'Content-Type: application/octet-stream'), 'Reject malformed MIME headers');
    echo "security_request_test: OK (redirects, anonymous POST origins, session CSRF, real HTTP download headers)\n";
} finally {
    proc_terminate($process);
    proc_close($process);
    unlink($cookieJar);
    unlink($log);
}
