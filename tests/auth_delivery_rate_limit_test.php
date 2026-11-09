<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\AuthRateLimit;

function deliveryCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function deliveryBucket(string $policy, string $kind, string $value): AuthRateLimit
{
    $bucket = new AuthRateLimit();
    deliveryCheck($bucket->load([
        ['scope', $policy], ['key_hash', commonAuthHashIdentifier($kind, $value)],
    ]), 'Quota must exist: ' . $policy);
    return $bucket;
}

function deliverySnapshot(AuthRateLimit $bucket): array
{
    return array_map(function ($field) use ($bucket) {
        $value = $bucket->get($field);
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
    }, [
        'attempt_count', 'window_started_at', 'blocked_until', 'updated_at',
    ]);
}

function deliveryAge(AuthRateLimit $bucket, int $seconds): void
{
    $bucket->set('window_started_at', date('Y-m-d H:i:s', time() - $seconds));
    deliveryCheck(!empty($bucket->save()['status']), 'Quota clock must be adjusted.');
}

$_ENV['AUTH_RATE_LIMITS_ENABLED'] = 'true';
$_ENV['AUTH_TRUSTED_PROXY_IPS'] = '';
$_SERVER['REMOTE_ADDR'] = '2001:db8:' . bin2hex(random_bytes(2)) . ':' . bin2hex(random_bytes(2)) . '::1';
$email = 'rate-' . bin2hex(random_bytes(8)) . '@example.invalid';
$ip = commonGetRequestIp();
$magicLimits = [
    ['magic_request_ip', 'ip', $ip], ['magic_ip_hour', 'ip', $ip],
    ['magic_email_minute', 'email', $email], ['magic_email_hour', 'email', $email],
];
$resetLimits = [
    ['reset_ip_hour', 'ip', $ip], ['reset_email_short', 'email', $email],
    ['reset_email_hour', 'email', $email],
];
$passwordLimit = ['password_account', 'email', $email];
$sourceLang = commonGetAuthPhpSourceLang();
$directory = sys_get_temp_dir() . '/auth-delivery-' . bin2hex(random_bytes(6));
deliveryCheck(mkdir($directory, 0700), 'HTTP fixture directory must be created.');
$router = $directory . '/router.php';
$root = dirname(__DIR__);
file_put_contents($router, '<?php '
    . 'require ' . var_export($root . '/shared_functions.php', true) . '; '
    . '$_ENV["AUTH_RATE_LIMITS_ENABLED"] = "true"; '
    . '$_ENV["AUTH_SECURITY_ALERT_ENABLED"] = "false"; '
    . '$_ENV["AUTH_TRUSTED_PROXY_IPS"] = ""; '
    . '$_ENV["AUTH_PUBLIC_URL"] = "https://localtest.me"; '
    . '$_SERVER["REMOTE_ADDR"] = ' . var_export($ip, true) . '; '
    . '$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH); '
    . 'if (!in_array($path, ["/common/login_send.php", "/common/login_reset_request.php"], true)) { http_response_code(404); return; } '
    . 'require ' . var_export($root, true) . ' . $path;');
$process = null;
$request = null;

try {
    session_write_close();
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $process = proc_open([PHP_BINARY, '-S', $address, $router], [0 => ['pipe', 'r'],
        1 => ['file', $directory . '/server.log', 'a'], 2 => ['file', $directory . '/server.log', 'a']], $pipes, $root);
    deliveryCheck(is_resource($process), 'HTTP fixture server must start.');
    fclose($pipes[0]);
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $socket = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
        if ($socket) { fclose($socket); break; }
        usleep(100000);
    }
    $request = static function (string $path) use ($address, $email): array {
        $curl = curl_init('http://' . $address . $path);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Origin: http://localtest.me', 'X-Requested-With: XMLHttpRequest'],
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['email' => $email])]);
        $response = curl_exec($curl);
        deliveryCheck($response !== false, 'HTTP request must succeed: ' . curl_error($curl));
        $length = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        return [curl_getinfo($curl, CURLINFO_RESPONSE_CODE), substr($response, 0, $length), json_decode(substr($response, $length), true)];
    };

    // No email is sent: exercise the actual database admission path directly.
    deliveryCheck(commonAuthEmailDeliveryLimits('magic', $email, 'inspect')['allowed'], 'Initial inspection allows delivery.');
    $empty = new AuthRateLimit();
    deliveryCheck(!$empty->load(['key_hash', commonAuthHashIdentifier('email', $email)]), 'Inspection creates no quotas.');
    deliveryCheck(commonAuthEmailDeliveryLimits('magic', $email)['allowed'], 'First code request is accepted.');
    foreach ($magicLimits as $limit) {
        deliveryCheck((int)deliveryBucket(...$limit)->get('attempt_count') === 1, 'Accepted request counts once in every quota.');
    }
    deliveryAge(deliveryBucket(...$magicLimits[2]), 15);
    $before = array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $magicLimits);
    for ($click = 0; $click < 65; $click++) {
        $blocked = commonAuthEmailDeliveryLimits('magic', $email);
        deliveryCheck(!$blocked['allowed'] && $blocked['retry_after'] > 0 && $blocked['retry_after'] <= 45,
            'Repeated clicks must wait only for the remainder of the initial minute.');
    }
    deliveryCheck(array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $magicLimits) === $before,
        'Rejected clicks change neither counters nor deadlines, including the IP request quota.');
    for ($click = 0; $click < 3; $click++) {
        [$status, $headers, $body] = $request('/common/login_send.php');
        deliveryCheck($status === 429 && $body['error'] === 'rate_limited' && $body['retry_after'] <= 45
            && str_contains($body['message'], 'secondes') && str_contains($headers, 'Retry-After: ' . $body['retry_after']),
            'Login endpoint must report the remaining seconds consistently in JSON and Retry-After.');
    }
    deliveryCheck(array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $magicLimits) === $before,
        'Actual HTTP retries must not consume or extend any quota.');
    $feedback = commonAuthRateLimitFeedback(['retry_after' => 45], [], $sourceLang);
    deliveryCheck($feedback['retry_after'] === 45 && str_contains($feedback['message'], '45 secondes'), 'Seconds appear in translated feedback.');
    deliveryCheck(str_contains(commonAuthRateLimitFeedback(['retry_after' => 60], [], $sourceLang)['message'], '1 minute avant'), 'One minute uses the singular.');
    deliveryCheck(str_contains(commonAuthRateLimitFeedback(['retry_after' => 1], [], $sourceLang)['message'], '1 seconde avant'), 'One second uses the singular.');

    // Five real deliveries are allowed, even after many rejected clicks.
    for ($delivery = 2; $delivery <= 5; $delivery++) {
        deliveryAge(deliveryBucket(...$magicLimits[2]), 61);
        deliveryCheck(commonAuthEmailDeliveryLimits('magic', $email)['allowed'], 'A fresh minute permits the next delivery.');
    }
    deliveryAge(deliveryBucket(...$magicLimits[3]), 3500);
    $before = array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $magicLimits);
    $blocked = commonAuthEmailDeliveryLimits('magic', $email);
    deliveryCheck(!$blocked['allowed'] && $blocked['retry_after'] > 60 && $blocked['retry_after'] <= 100,
        'When both quotas are full, report the longer remaining wait from the hourly window.');
    deliveryCheck(str_contains(commonAuthRateLimitFeedback($blocked, [], $sourceLang)['message'], '2 minutes'), 'Long waits are rounded up to minutes.');
    [$status, , $body] = $request('/common/login_send.php');
    deliveryCheck($status === 429 && str_contains($body['message'], '2 minutes') && $body['retry_after'] <= 100,
        'Login endpoint must display the hourly wait in minutes.');
    deliveryCheck(array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $magicLimits) === $before,
        'Hourly rejection consumes no other quota.');
    deliveryAge(deliveryBucket(...$magicLimits[2]), 61);
    deliveryAge(deliveryBucket(...$magicLimits[3]), 3601);
    deliveryCheck(commonAuthEmailDeliveryLimits('magic', $email)['allowed'], 'Delivery resumes after the window expires.');
    deliveryCheck((int)deliveryBucket(...$magicLimits[3])->get('attempt_count') === 1, 'Expired hourly quota restarts at one.');

    deliveryCheck(commonAuthEmailDeliveryLimits('reset', $email)['allowed'], 'First password reset is accepted.');
    deliveryAge(deliveryBucket(...$resetLimits[1]), 255);
    $before = array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $resetLimits);
    $blocked = commonAuthEmailDeliveryLimits('reset', $email);
    deliveryCheck(!$blocked['allowed'] && $blocked['retry_after'] <= 45, 'Password reset uses the same fixed cooldown.');
    deliveryCheck(array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $resetLimits) === $before,
        'Rejected password resets consume no other quota.');
    [$status, , $body] = $request('/common/login_reset_request.php');
    deliveryCheck($status === 429 && $body['retry_after'] <= 45 && str_contains($body['message'], 'secondes'),
        'Password reset endpoint must display its remaining wait.');
    deliveryCheck(array_map(fn($limit) => deliverySnapshot(deliveryBucket(...$limit)), $resetLimits) === $before,
        'Actual password reset requests preserve the original quotas.');

    for ($failure = 1; $failure <= 5; $failure++) {
        $result = commonAuthRunLimit(...[...$passwordLimit, 'failure']);
        deliveryCheck($result['allowed'] === ($failure < 5), 'Password failures keep the existing five-failure protection.');
    }
    $before = deliverySnapshot(deliveryBucket(...$passwordLimit));
    commonAuthRunLimit(...[...$passwordLimit, 'failure']);
    deliveryCheck(deliverySnapshot(deliveryBucket(...$passwordLimit)) === $before, 'Password retries do not extend an active block.');
    echo "auth_delivery_rate_limit_test: OK\n";
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach ([...$magicLimits, ...$resetLimits, $passwordLimit] as $limit) {
        commonAuthClearLimit(...$limit);
    }
    foreach ([$router, $directory . '/server.log'] as $file) if (is_file($file)) unlink($file);
    rmdir($directory);
}
