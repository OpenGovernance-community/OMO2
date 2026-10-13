<?php

/** Requires the shared CalDAV bootstrap. No response cache or authentication shortcut is used. */
function commonCalDavReadLimitTicket(): ?array
{
    if (!envBool('CALDAV_READ_LIMIT_ENABLED', true)) return null;
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
    if (!in_array($method, ['OPTIONS', 'PROPFIND'], true)) return null;
    $credentials = commonCardDavGetBasicCredentials();
    if (!is_array($credentials) || trim($credentials[0]) === '' || $credentials[1] === '') return null;
    $window = max(60, min(86400, (int)envValue('CALDAV_READ_LIMIT_SECONDS', 900)));
    $burst = max(1, min($window - 1, (int)envValue('CALDAV_READ_LIMIT_BURST_SECONDS', 30)));
    $raw = $GLOBALS['commonCardDavRawInput'] ?? file_get_contents('php://input', false, null, 0, 131073);
    if (!is_string($raw)) return null;
    // Large bodies bypass duplicate detection and retain the endpoint's normal processing.
    if (strlen($raw) > 131072) return null;
    $GLOBALS['commonCardDavRawInput'] = $raw;
    $key = hash_hmac('sha256', serialize(['caldav-read-limit-v1', $credentials,
        (string)($_SERVER['HTTP_HOST'] ?? ''), $method, (string)($_SERVER['REQUEST_URI'] ?? ''),
        trim((string)($_SERVER['HTTP_DEPTH'] ?? '')), hash('sha256', $raw), $window, $burst]),
        commonGetAuthRateLimitSecret());
    return ['key' => $key, 'window' => $window, 'burst' => $burst];
}

function commonCalDavEnforceReadLimit(?array $ticket): void
{
    if ($ticket === null) return;
    $blocked = \dbObject\CalDavRateLimit::inspect($ticket['key']);
    if ($blocked === null) return;
    // Attribution to the previously verified credentials is for monitoring only.
    // Do not set a session user or authorize any resource from this record.
    $GLOBALS['commonPerformanceUserId'] = (int)$blocked['user_id'];
    $GLOBALS['commonPerformanceOrganizationId'] = (int)$blocked['organization_id'];
    if (isset($GLOBALS['commonCalDavDiagnostics'])) {
        $GLOBALS['commonCalDavDiagnostics']['user_id'] = (int)$blocked['user_id'];
        $GLOBALS['commonCalDavDiagnostics']['auth_ms'] = 0;
        $GLOBALS['commonCalDavDiagnostics']['rate_limit'] = 'blocked';
        $GLOBALS['commonCalDavDiagnostics']['retry_after'] = (int)$blocked['retry_after'];
    }
    header('Retry-After: ' . (int)$blocked['retry_after']);
    header('X-OMO-CalDAV-Rate-Limit: blocked');
    commonCalDavSendStatusText(429, 'Too Many Requests. Retry after the delay indicated by Retry-After.');
}

function commonCalDavRememberReadLimit(?array $ticket, \dbObject\User $viewer): void
{
    if ($ticket === null) return;
    $userId = (int)$viewer->getId();
    register_shutdown_function(static function () use ($ticket, $userId) {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;
        $status = http_response_code();
        if ($status < 200 || $status >= 300) return;
        \dbObject\CalDavRateLimit::remember($ticket['key'], $userId,
            (int)($GLOBALS['commonPerformanceOrganizationId'] ?? 0), $GLOBALS['commonCalDavRateLimitRevisions'] ?? [],
            $ticket['window'], $ticket['burst']);
    });
}
