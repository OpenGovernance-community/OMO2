<?php
declare(strict_types=1);

/** Separate purpose key: possession of a collector key never grants JWT signing rights. */
function visioTrafficSigningKey(string $jwtSecret): string
{
    return hash_hmac('sha256', 'omo-visio-traffic-v1', $jwtSecret);
}

function visioTrafficAuthenticate(string $body, string $timestamp, string $signature, string $jwtSecret, ?int $now = null): bool
{
    return strlen($jwtSecret) >= 32 && preg_match('/\A[0-9]{10}\z/D', $timestamp)
        && abs(($now ?? time()) - (int)$timestamp) <= 120
        && preg_match('/\A[a-f0-9]{64}\z/D', $signature)
        && hash_equals(hash_hmac('sha256', $timestamp . "\n" . $body, visioTrafficSigningKey($jwtSecret)), $signature);
}
