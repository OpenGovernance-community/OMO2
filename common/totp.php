<?php

function commonTotpGetEncryptionKey()
{
    $configured = trim((string)commonAuthReadEnvironmentValue('AUTH_TOTP_ENCRYPTION_KEY', ''));
    if (strlen($configured) < 32 || !function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
        return null;
    }

    return hash('sha256', $configured, true);
}

function commonTotpBase32Encode($binary)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split((string)$binary) as $character) {
        $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
    }

    $encoded = '';
    foreach (str_split($bits, 5) as $chunk) {
        $encoded .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
    }

    return $encoded;
}

function commonTotpBase32Decode($value)
{
    $normalized = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string)$value));
    if ($normalized === '') {
        return null;
    }

    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($normalized) as $character) {
        $position = strpos($alphabet, $character);
        if ($position === false) {
            return null;
        }
        $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
    }

    $decoded = '';
    for ($offset = 0; $offset + 8 <= strlen($bits); $offset += 8) {
        $decoded .= chr(bindec(substr($bits, $offset, 8)));
    }

    return $decoded === '' ? null : $decoded;
}

function commonTotpGenerateSecret()
{
    return commonTotpBase32Encode(random_bytes(20));
}

function commonTotpEncryptSecret($secret)
{
    $key = commonTotpGetEncryptionKey();
    $secret = trim((string)$secret);
    if ($key === null || $secret === '') {
        return null;
    }

    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($secret, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if (!is_string($ciphertext) || strlen($tag) !== 16) {
        return null;
    }

    return 'v1:' . base64_encode($iv . $tag . $ciphertext);
}

function commonTotpDecryptSecret($encrypted)
{
    $key = commonTotpGetEncryptionKey();
    $encrypted = (string)$encrypted;
    if ($key === null || !str_starts_with($encrypted, 'v1:')) {
        return null;
    }

    $payload = base64_decode(substr($encrypted, 3), true);
    if (!is_string($payload) || strlen($payload) < 29) {
        return null;
    }

    $iv = substr($payload, 0, 12);
    $tag = substr($payload, 12, 16);
    $ciphertext = substr($payload, 28);
    $secret = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    return is_string($secret) && commonTotpBase32Decode($secret) !== null ? $secret : null;
}

function commonTotpGetCode($secret, $timestamp = null)
{
    $binarySecret = commonTotpBase32Decode($secret);
    if ($binarySecret === null) {
        return null;
    }

    $timeSlice = intdiv((int)($timestamp ?? time()), 30);
    $high = intdiv($timeSlice, 4294967296);
    $low = $timeSlice % 4294967296;
    $hash = hash_hmac('sha1', pack('N2', $high, $low), $binarySecret, true);
    $offset = ord($hash[19]) & 0x0f;
    $value = ((ord($hash[$offset]) & 0x7f) << 24)
        | (ord($hash[$offset + 1]) << 16)
        | (ord($hash[$offset + 2]) << 8)
        | ord($hash[$offset + 3]);

    return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function commonTotpVerifyCode($secret, $code, $timestamp = null)
{
    $code = preg_replace('/\s+/', '', (string)$code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }

    $timestamp = (int)($timestamp ?? time());
    foreach ([-1, 0, 1] as $offset) {
        $expected = commonTotpGetCode($secret, $timestamp + ($offset * 30));
        if ($expected !== null && hash_equals($expected, $code)) {
            return true;
        }
    }

    return false;
}

function commonUserHasTotpEnabled($user)
{
    return is_object($user)
        && method_exists($user, 'get')
        && (bool)$user->get('totp_enabled')
        && trim((string)$user->get('totp_secret')) !== '';
}

function commonUserGetTotpSecret($user)
{
    if (!commonUserHasTotpEnabled($user)) {
        return null;
    }

    return commonTotpDecryptSecret((string)$user->get('totp_secret'));
}

function commonTotpBuildProvisioningUri($secret, $email)
{
    $issuer = trim((string)($GLOBALS['siteTitle'] ?? 'OMO'));
    $issuer = $issuer === '' ? 'OMO' : $issuer;
    $account = trim((string)$email);
    $label = rawurlencode($issuer . ':' . $account);

    return 'otpauth://totp/' . $label . '?secret=' . rawurlencode((string)$secret)
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}

function commonTotpBuildQrDataUrl($provisioningUri)
{
    $libraryPath = dirname(__DIR__) . '/library/phpqrcode/qrlib.php';
    if (!is_file($libraryPath)) {
        return null;
    }

    require_once $libraryPath;
    ob_start();
    \QRcode::png((string)$provisioningUri, null, \QR_ECLEVEL_M, 5, 1);
    $png = ob_get_clean();

    return is_string($png) && $png !== '' ? 'data:image/png;base64,' . base64_encode($png) : null;
}
