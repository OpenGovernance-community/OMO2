<?php

if (!function_exists('webPushBase64UrlEncode')) {
    function webPushBase64UrlEncode($value)
    {
        return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '=');
    }
}

if (!function_exists('webPushBase64UrlDecode')) {
    function webPushBase64UrlDecode($value)
    {
        $value = trim((string)$value);
        if ($value === '' || preg_match('/[^A-Za-z0-9\-_]/', $value)) {
            return false;
        }

        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value, true);
    }
}

if (!function_exists('webPushGetEcPublicBytes')) {
    function webPushGetEcPublicBytes($key)
    {
        $details = openssl_pkey_get_details($key);
        $x = is_array($details) && isset($details['ec']['x']) ? (string)$details['ec']['x'] : '';
        $y = is_array($details) && isset($details['ec']['y']) ? (string)$details['ec']['y'] : '';
        if (strlen($x) !== 32 || strlen($y) !== 32) {
            return false;
        }

        return "\x04" . $x . $y;
    }
}

if (!function_exists('webPushGetVapidConfiguration')) {
    function webPushGetVapidConfiguration()
    {
        $privateValue = function_exists('envValue') ? trim((string)envValue('WEB_PUSH_VAPID_PRIVATE_KEY', '')) : '';
        $subject = function_exists('envValue') ? trim((string)envValue('WEB_PUSH_VAPID_SUBJECT', '')) : '';
        if ($privateValue === '' || $subject === '') {
            return null;
        }

        $privatePem = base64_decode($privateValue, true);
        if ($privatePem === false) {
            $privatePem = webPushBase64UrlDecode($privateValue);
        }
        if (!is_string($privatePem) || $privatePem === '') {
            return null;
        }

        $privateKey = openssl_pkey_get_private($privatePem);
        if ($privateKey === false) {
            return null;
        }

        $publicKey = webPushGetEcPublicBytes($privateKey);
        if ($publicKey === false) {
            return null;
        }

        $configuredPublicKey = function_exists('envValue') ? trim((string)envValue('WEB_PUSH_VAPID_PUBLIC_KEY', '')) : '';
        if ($configuredPublicKey !== '') {
            $configuredBytes = webPushBase64UrlDecode($configuredPublicKey);
            if (!is_string($configuredBytes) || !hash_equals($publicKey, $configuredBytes)) {
                return null;
            }
        }

        return [
            'privateKey' => $privateKey,
            'publicKey' => $publicKey,
            'publicKeyBase64Url' => webPushBase64UrlEncode($publicKey),
            'subject' => $subject,
        ];
    }
}

if (!function_exists('webPushGenerateVapidEnvironmentValues')) {
    function webPushGenerateVapidEnvironmentValues()
    {
        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if ($privateKey === false) {
            return null;
        }

        $privatePem = '';
        if (!openssl_pkey_export($privateKey, $privatePem) || $privatePem === '') {
            return null;
        }

        $publicKey = webPushGetEcPublicBytes($privateKey);
        if ($publicKey === false) {
            return null;
        }

        return [
            'WEB_PUSH_VAPID_PUBLIC_KEY' => webPushBase64UrlEncode($publicKey),
            'WEB_PUSH_VAPID_PRIVATE_KEY' => base64_encode($privatePem),
        ];
    }
}

if (!function_exists('webPushBuildPublicKeyPem')) {
    function webPushBuildPublicKeyPem($publicKey)
    {
        $publicKey = (string)$publicKey;
        if (strlen($publicKey) !== 65 || $publicKey[0] !== "\x04") {
            return false;
        }

        $der = "\x30\x59\x30\x13\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07\x03\x42\x00" . $publicKey;
        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }
}

if (!function_exists('webPushDerSignatureToRaw')) {
    function webPushDerSignatureToRaw($signature)
    {
        $signature = (string)$signature;
        if (strlen($signature) < 8 || ord($signature[0]) !== 0x30) {
            return false;
        }

        $offset = 1;
        $readLength = static function ($value, &$position) {
            if ($position >= strlen($value)) {
                return false;
            }
            $length = ord($value[$position++]);
            if (($length & 0x80) === 0) {
                return $length;
            }
            $byteCount = $length & 0x7f;
            if ($byteCount < 1 || $byteCount > 2 || ($position + $byteCount) > strlen($value)) {
                return false;
            }
            $length = 0;
            for ($index = 0; $index < $byteCount; $index++) {
                $length = ($length << 8) | ord($value[$position++]);
            }
            return $length;
        };

        $sequenceLength = $readLength($signature, $offset);
        if ($sequenceLength === false || ($offset + $sequenceLength) !== strlen($signature)) {
            return false;
        }

        $parts = [];
        for ($index = 0; $index < 2; $index++) {
            if ($offset >= strlen($signature) || ord($signature[$offset++]) !== 0x02) {
                return false;
            }
            $length = $readLength($signature, $offset);
            if ($length === false || $length < 1 || ($offset + $length) > strlen($signature)) {
                return false;
            }
            $part = substr($signature, $offset, $length);
            $offset += $length;
            $part = ltrim($part, "\x00");
            if (strlen($part) > 32) {
                return false;
            }
            $parts[] = str_pad($part, 32, "\x00", STR_PAD_LEFT);
        }

        return $offset === strlen($signature) ? $parts[0] . $parts[1] : false;
    }
}

if (!function_exists('webPushBuildAuthorizationHeader')) {
    function webPushBuildAuthorizationHeader($endpoint, array $vapid)
    {
        $parts = parse_url((string)$endpoint);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        $audience = $scheme . '://' . $host;
        if (isset($parts['port']) && (int)$parts['port'] > 0 && (int)$parts['port'] !== 443) {
            $audience .= ':' . (int)$parts['port'];
        }
        $header = webPushBase64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_UNESCAPED_SLASHES));
        $claim = webPushBase64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + (12 * 60 * 60),
            'sub' => (string)$vapid['subject'],
        ], JSON_UNESCAPED_SLASHES));
        $input = $header . '.' . $claim;
        $derSignature = '';
        if (!openssl_sign($input, $derSignature, $vapid['privateKey'], OPENSSL_ALGO_SHA256)) {
            return false;
        }
        $rawSignature = webPushDerSignatureToRaw($derSignature);
        if ($rawSignature === false) {
            return false;
        }

        return 'vapid t=' . $input . '.' . webPushBase64UrlEncode($rawSignature)
            . ', k=' . (string)$vapid['publicKeyBase64Url'];
    }
}

if (!function_exists('webPushEncryptPayload')) {
    function webPushEncryptPayload($payload, array $subscription)
    {
        $clientPublicKey = webPushBase64UrlDecode((string)($subscription['p256dh_key'] ?? ''));
        $authSecret = webPushBase64UrlDecode((string)($subscription['auth_key'] ?? ''));
        if (!is_string($clientPublicKey) || strlen($clientPublicKey) !== 65 || !is_string($authSecret) || strlen($authSecret) < 16) {
            return null;
        }

        $clientPublicPem = webPushBuildPublicKeyPem($clientPublicKey);
        $clientKey = $clientPublicPem !== false ? openssl_pkey_get_public($clientPublicPem) : false;
        $serverKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if ($clientKey === false || $serverKey === false) {
            return null;
        }

        $serverPublicKey = webPushGetEcPublicBytes($serverKey);
        $sharedSecret = $serverPublicKey !== false ? openssl_pkey_derive($clientKey, $serverKey) : false;
        if ($serverPublicKey === false || $sharedSecret === false || strlen($sharedSecret) !== 32) {
            return null;
        }

        $ikm = hash_hkdf('sha256', $sharedSecret, 32, "WebPush: info\x00" . $clientPublicKey . $serverPublicKey, $authSecret);
        $salt = random_bytes(16);
        $contentEncryptionKey = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);
        $plaintext = (string)$payload . "\x02";
        if (strlen($plaintext) > 4096) {
            return null;
        }

        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $contentEncryptionKey, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false || strlen($tag) !== 16) {
            return null;
        }

        return $salt . pack('N', 4096) . chr(strlen($serverPublicKey)) . $serverPublicKey . $ciphertext . $tag;
    }
}

if (!function_exists('webPushSendToSubscription')) {
    function webPushSendToSubscription(\dbObject\NotificationPushSubscription $subscription, array $payload)
    {
        $vapid = webPushGetVapidConfiguration();
        if (!is_array($vapid)) {
            return ['status' => false, 'error' => 'Web Push VAPID is not configured.', 'disable' => false];
        }
        if (!function_exists('curl_init')) {
            return ['status' => false, 'error' => 'The PHP cURL extension is unavailable.', 'disable' => false];
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encryptedPayload = is_string($jsonPayload) ? webPushEncryptPayload($jsonPayload, [
            'p256dh_key' => $subscription->get('p256dh_key'),
            'auth_key' => $subscription->get('auth_key'),
        ]) : null;
        $authorization = webPushBuildAuthorizationHeader((string)$subscription->get('endpoint'), $vapid);
        if (!is_string($encryptedPayload) || $authorization === false) {
            return ['status' => false, 'error' => 'Unable to encrypt or sign the Web Push payload.', 'disable' => false];
        }

        $curl = curl_init((string)$subscription->get('endpoint'));
        if ($curl === false) {
            return ['status' => false, 'error' => 'Unable to initialize the Web Push request.', 'disable' => false];
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encryptedPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'Authorization: ' . $authorization,
                'TTL: 300',
                'Urgency: normal',
            ],
        ]);
        $response = curl_exec($curl);
        $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = $response === false ? trim((string)curl_error($curl)) : '';
        $success = $response !== false && $statusCode >= 200 && $statusCode < 300;

        return [
            'status' => $success,
            'statusCode' => $statusCode,
            'error' => $success ? '' : ($error !== '' ? $error : 'Web Push endpoint returned HTTP ' . $statusCode . '.'),
            'disable' => in_array($statusCode, [404, 410], true),
        ];
    }
}

if (!function_exists('notificationPushGetWorkerLogPath')) {
    function notificationPushGetWorkerLogPath()
    {
        $configuredPath = function_exists('envValue') ? trim((string)envValue('NOTIFICATION_PUSH_WORKER_LOG', '')) : '';
        return $configuredPath !== '' ? $configuredPath : dirname(__DIR__) . '/tmp/notification-push-worker.log';
    }
}

if (!function_exists('notificationPushWriteWorkerLog')) {
    function notificationPushWriteWorkerLog($message)
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . trim((string)$message) . PHP_EOL;
        $logPath = notificationPushGetWorkerLogPath();
        $logDirectory = dirname($logPath);
        if (!is_dir($logDirectory)) {
            @mkdir($logDirectory, 0777, true);
        }
        if (@file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log(trim($line));
        }
    }
}

if (!function_exists('notificationPushIsExecAvailable')) {
    function notificationPushIsExecAvailable()
    {
        if (!function_exists('exec')) {
            return false;
        }
        $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        return !in_array('exec', $disabledFunctions, true);
    }
}

if (!function_exists('notificationPushGetPhpBinary')) {
    function notificationPushGetPhpBinary()
    {
        $isCliCandidate = static function ($path) {
            $path = trim((string)$path);
            $basename = strtolower((string)pathinfo($path, PATHINFO_BASENAME));
            return $path !== ''
                && $basename !== ''
                && strpos($basename, 'php-fpm') === false
                && strpos($basename, 'php-cgi') === false
                && strpos($basename, 'php') !== false;
        };

        $configuredBinary = function_exists('envValue') ? trim((string)envValue('NOTIFICATION_WORKER_PHP_BINARY', '')) : '';
        if ($isCliCandidate($configuredBinary)) {
            return $configuredBinary;
        }
        if (defined('PHP_BINARY') && $isCliCandidate(PHP_BINARY)) {
            return PHP_BINARY;
        }
        foreach (['/usr/bin/php', '/usr/local/bin/php', '/bin/php'] as $candidate) {
            if (is_file($candidate) && $isCliCandidate($candidate)) {
                return $candidate;
            }
        }
        return '';
    }
}

if (!function_exists('notificationPushDispatchDecisionMessage')) {
    function notificationPushDispatchDecisionMessage($messageId)
    {
        $messageId = (int)$messageId;
        if ($messageId <= 0 || !is_array(webPushGetVapidConfiguration())) {
            return false;
        }
        if (!notificationPushIsExecAvailable()) {
            notificationPushWriteWorkerLog('Unable to dispatch push worker: exec is unavailable. message=' . $messageId);
            return false;
        }

        $phpBinary = notificationPushGetPhpBinary();
        $scriptPath = dirname(__DIR__) . '/scripts/send-decision-chat-push.php';
        if ($phpBinary === '' || !is_file($scriptPath)) {
            notificationPushWriteWorkerLog('Unable to dispatch push worker: no CLI binary or worker script. message=' . $messageId);
            return false;
        }

        $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath) . ' --message=' . $messageId;
        if (DIRECTORY_SEPARATOR === '\\') {
            @exec('start /B "" ' . $command . ' >NUL 2>&1');
            notificationPushWriteWorkerLog('Dispatched push worker on Windows. message=' . $messageId);
            return true;
        }

        $output = [];
        $exitCode = 0;
        @exec($command . ' >/dev/null 2>&1 & echo $!', $output, $exitCode);
        $pid = trim((string)(count($output) > 0 ? $output[count($output) - 1] : ''));
        notificationPushWriteWorkerLog('Push worker dispatch exit=' . $exitCode . ' pid=' . ($pid !== '' ? $pid : 'n/a') . ' message=' . $messageId);
        return $exitCode === 0;
    }
}

if (!function_exists('notificationPushSendDecisionChatMessage')) {
    function notificationPushSendDecisionChatMessage($messageId)
    {
        $message = new \dbObject\ChatMessage();
        if (!$message->load((int)$messageId) || (string)$message->get('message_type') !== \dbObject\ChatMessage::TYPE_USER) {
            return ['status' => false, 'reason' => 'message_not_found', 'sent' => 0];
        }

        $thread = new \dbObject\ChatThread();
        if (
            !$thread->load((int)$message->get('IDchat_thread'))
            || (string)$thread->get('subject_type') !== \dbObject\ChatThread::SUBJECT_DECISION_PROPOSAL
        ) {
            return ['status' => false, 'reason' => 'not_decision_discussion', 'sent' => 0];
        }

        $proposal = new \dbObject\DecisionProposal();
        if (!$proposal->load((int)$thread->get('subject_id'))) {
            return ['status' => false, 'reason' => 'proposal_not_found', 'sent' => 0];
        }
        $decision = $proposal->getDecisionProcess();
        if (!$decision instanceof \dbObject\DecisionProcess) {
            return ['status' => false, 'reason' => 'decision_not_found', 'sent' => 0];
        }

        $proposalAuthorUserId = (int)$proposal->getAuthorUserId();
        $subscriptions = \dbObject\NotificationPushSubscription::findActiveForUserIds(
            $proposalAuthorUserId > 0 ? [$proposalAuthorUserId] : []
        );
        $organizationId = (int)$decision->get('IDorganization');
        $decisionId = (int)$decision->getId();
        $payload = [
            'title' => 'Nouveau message dans une discussion',
            'body' => 'Une nouvelle intervention concerne la proposition ' . mb_substr(trim((string)$proposal->get('title')), 0, 120, 'UTF-8') . '.',
            'url' => '/omo/o/' . $organizationId . '#decision-d' . $decisionId,
            'tag' => 'decision-chat-message-' . (int)$message->getId(),
        ];

        $sent = 0;
        $failed = 0;
        foreach ($subscriptions as $subscription) {
            if (!($subscription instanceof \dbObject\NotificationPushSubscription)) {
                continue;
            }
            $result = webPushSendToSubscription($subscription, $payload);
            $wasSent = !empty($result['status']);
            \dbObject\NotificationPushSubscription::recordDeliveryResult(
                (int)$subscription->getId(),
                $wasSent,
                (string)($result['error'] ?? ''),
                !empty($result['disable'])
            );
            if ($wasSent) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['status' => $failed === 0, 'sent' => $sent, 'failed' => $failed, 'subscriptions' => count($subscriptions)];
    }
}
?>
