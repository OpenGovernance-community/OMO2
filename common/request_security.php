<?php

/** Browser mutations require the same origin or a token bound to this session. */
function commonCsrfToken(): string
{
    if (is_string($_SESSION['common_csrf'] ?? null) && preg_match('/^[a-f0-9]{64}$/D', $_SESSION['common_csrf'])) return $_SESSION['common_csrf'];
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('An active session is required.');
    }
    if (!is_string($_SESSION['common_csrf'] ?? null) || strlen($_SESSION['common_csrf']) !== 64) {
        $_SESSION['common_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['common_csrf'];
}

function commonRequestOrigin(string $url): string
{
    $parts = parse_url($url);
    if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return '';
    $scheme = strtolower($parts['scheme']);
    $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
    return $scheme . '://' . strtolower($parts['host']) . ':' . $port;
}

function commonBrowserMutationIsAllowed(array $server, array $post, array $session): bool
{
    $origin = $server['HTTP_ORIGIN'] ?? null;
    $scheme = commonSecurityRequestScheme($server);
    $expected = commonRequestOrigin($scheme . '://' . ($server['HTTP_HOST'] ?? ''));
    if ($origin !== null) {
        return is_string($origin) && $expected !== ''
            && commonRequestOrigin($origin) === $expected;
    }
    if (in_array(strtolower((string)($server['HTTP_SEC_FETCH_SITE'] ?? '')), ['cross-site', 'same-site'], true)) return false;
    $token = $server['HTTP_X_OMO_CSRF_TOKEN'] ?? ($post['_csrf'] ?? null);
    $stored = $session['common_csrf'] ?? null;
    return is_string($token) && is_string($stored) && strlen($stored) === 64 && hash_equals($stored, $token);
}

function commonSecurityRequestScheme(array $server): string
{
    $https = strtolower((string)($server['HTTPS'] ?? ''));
    if (($https !== '' && $https !== 'off') || (string)($server['SERVER_PORT'] ?? '') === '443') return 'https';
    if (function_exists('commonGetTrustedProxyRanges') && function_exists('commonIpMatchesAnyRange')
        && commonIpMatchesAnyRange((string)($server['REMOTE_ADDR'] ?? ''), commonGetTrustedProxyRanges())
        && strtolower((string)($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') return 'https';
    return 'http';
}

function commonGuardBrowserMutation(): void
{
    if (PHP_SAPI === 'cli' || in_array(strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'HEAD', 'OPTIONS'], true)) return;
    // Stateless transports authenticate independently of the browser session.
    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (preg_match('~^/(?:api/v1(?:/|$)|mcp/(?:token|register|revoke|index)\.php$|mcp/?$|omo/api/(?:carddav|caldav)(?:/|$)|telegram/memo_hook\.php$)~', (string)$path)) return;
    if ((int)($_SESSION['currentUser'] ?? 0) <= 0) return;
    if (commonBrowserMutationIsAllowed($_SERVER, $_POST, $_SESSION)) return;
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['status' => false, 'success' => false, 'error' => 'csrf', 'message' => 'Requete refusee. Rechargez la page.']);
    exit;
}

/** Security links never inherit an arbitrary request Host. */
function commonSecurityBaseUrl(): string
{
    $configured = trim((string)envValue('AUTH_PUBLIC_URL', ''));
    if ($configured !== '') {
        $parts = parse_url($configured);
        if (!is_array($parts) || commonRequestOrigin($configured) === ''
            || isset($parts['user'], $parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !in_array($parts['path'] ?? '', ['', '/'], true)) throw new RuntimeException('Invalid AUTH_PUBLIC_URL.');
        if (($parts['scheme'] ?? '') !== 'https' && !in_array($parts['host'] ?? '', ['localhost', '127.0.0.1'], true)) {
            throw new RuntimeException('AUTH_PUBLIC_URL must use HTTPS.');
        }
        return rtrim($configured, '/');
    }
    // Local development remains usable without a production domain setting.
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if (preg_match('/^(?:localhost|127\.0\.0\.1|(?:[a-z0-9-]+\.)*localtest\.me)(?::[0-9]+)?$/D', $host)) {
        return (function_exists('appShouldUseSecureCookies') && appShouldUseSecureCookies() ? 'https' : 'http') . '://' . $host;
    }
    throw new RuntimeException('Configure AUTH_PUBLIC_URL before sending authentication links.');
}

function commonSecurityUrl(string $path): string
{
    return commonSecurityBaseUrl() . commonNormalizeLocalPath($path, '/');
}
