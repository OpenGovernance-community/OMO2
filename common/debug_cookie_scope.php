<?php

require_once dirname(__DIR__) . '/common/environment_subdomains.php';

function debugCookieNormalizeHost($host = null)
{
    $host = is_string($host) && $host !== ''
        ? strtolower($host)
        : strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

    $host = trim((string)$host);
    return preg_replace('/:\d+$/', '', $host);
}

function debugCookieConfiguredRootHost()
{
    $host = strtolower(trim((string)commonReadRuntimeEnvValue('COOKIE_ROOT_HOST', '')));
    $host = preg_replace('/:\d+$/', '', $host);

    if ($host === '' || $host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
        return '';
    }

    return $host;
}

function debugCookieHostMatchesRoot($host, $rootHost)
{
    $host = debugCookieNormalizeHost($host);
    $rootHost = debugCookieNormalizeHost($rootHost);

    if ($host === '' || $rootHost === '') {
        return false;
    }

    if ($host === $rootHost) {
        return true;
    }

    return substr($host, -strlen('.' . $rootHost)) === '.' . $rootHost;
}

function debugCookieEnvironmentSubdomain($host = null)
{
    $host = debugCookieNormalizeHost($host);

    if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    $parts = array_values(array_filter(explode('.', $host)));
    if (count($parts) < 3) {
        return '';
    }

    $environmentCandidate = strtolower((string)($parts[count($parts) - 3] ?? ''));
    if (in_array($environmentCandidate, commonGetConfiguredEnvironmentSubdomains(), true)) {
        return $environmentCandidate;
    }

    return '';
}

function debugCookieScopeMode($host = null)
{
    $mode = strtolower(trim((string)commonReadRuntimeEnvValue('COOKIE_SCOPE_MODE', 'auto')));
    if (in_array($mode, ['host', 'environment', 'parent'], true)) {
        return $mode;
    }

    $configuredRootHost = debugCookieConfiguredRootHost();
    if ($configuredRootHost !== '' && debugCookieHostMatchesRoot($host, $configuredRootHost)) {
        return 'environment';
    }

    if (debugCookieEnvironmentSubdomain($host) !== '') {
        return 'host';
    }

    return 'parent';
}

function debugCookieParentDomain($host = null)
{
    $host = debugCookieNormalizeHost($host);
    $configuredRootHost = debugCookieConfiguredRootHost();

    if ($configuredRootHost !== '' && debugCookieHostMatchesRoot($host, $configuredRootHost)) {
        return '.' . $configuredRootHost;
    }

    if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    if ($host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
        return '';
    }

    $parts = array_values(array_filter(explode('.', $host)));
    if (count($parts) < 2) {
        return '';
    }

    return '.' . implode('.', array_slice($parts, -2));
}

function debugCookieEnvironmentDomain($host = null)
{
    $host = debugCookieNormalizeHost($host);
    $configuredRootHost = debugCookieConfiguredRootHost();

    if ($configuredRootHost !== '' && debugCookieHostMatchesRoot($host, $configuredRootHost)) {
        return '.' . $configuredRootHost;
    }

    if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    if ($host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
        return '';
    }

    $parts = array_values(array_filter(explode('.', $host)));
    if (count($parts) < 3) {
        return '';
    }

    $environmentCandidate = strtolower((string)($parts[count($parts) - 3] ?? ''));
    if (!in_array($environmentCandidate, commonGetConfiguredEnvironmentSubdomains(), true)) {
        return '';
    }

    return '.' . implode('.', array_slice($parts, -3));
}

function debugCookieDomain($host = null)
{
    $host = debugCookieNormalizeHost($host);

    if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    if ($host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
        return '';
    }

    $scopeMode = debugCookieScopeMode($host);
    if ($scopeMode === 'host') {
        return '';
    }

    if ($scopeMode === 'environment') {
        $environmentDomain = debugCookieEnvironmentDomain($host);
        if ($environmentDomain !== '') {
            return $environmentDomain;
        }
    }

    return debugCookieParentDomain($host);
}

function debugCookieScopeKey($host = null)
{
    $domain = ltrim((string)debugCookieDomain($host), '.');
    if ($domain !== '') {
        return $domain;
    }

    return debugCookieNormalizeHost($host);
}

function debugCookieScopedName($baseName, $host = null)
{
    $baseName = trim((string)$baseName);
    if ($baseName === '') {
        return '';
    }

    $scopeMode = debugCookieScopeMode($host);
    $environmentSubdomain = debugCookieEnvironmentSubdomain($host);
    if ($scopeMode !== 'host' && $environmentSubdomain === '') {
        return $baseName;
    }

    $suffix = preg_replace('/[^a-z0-9]+/i', '_', strtolower((string)debugCookieScopeKey($host)));
    $suffix = trim((string)$suffix, '_');
    if ($suffix === '') {
        return $baseName;
    }

    return $baseName . '_' . $suffix;
}

$requestedHost = trim((string)($_GET['host'] ?? ''));
$host = debugCookieNormalizeHost($requestedHost !== '' ? $requestedHost : null);

$response = [
    'request' => [
        'http_host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
        'requested_host_param' => $requestedHost,
        'effective_host' => $host,
    ],
    'environment' => [
        'cookie_scope_mode_env' => (string)commonReadRuntimeEnvValue('COOKIE_SCOPE_MODE', ''),
        'cookie_root_host_env' => (string)commonReadRuntimeEnvValue('COOKIE_ROOT_HOST', ''),
        'organization_subdomain_routing_env' => (string)commonReadRuntimeEnvValue('ORGANIZATION_SUBDOMAIN_ROUTING', ''),
        'reserved_environment_subdomains' => commonGetConfiguredEnvironmentSubdomains(),
    ],
    'computed' => [
        'environment_subdomain' => debugCookieEnvironmentSubdomain($host),
        'scope_mode' => debugCookieScopeMode($host),
        'parent_domain' => debugCookieParentDomain($host),
        'environment_domain' => debugCookieEnvironmentDomain($host),
        'cookie_domain' => debugCookieDomain($host),
        'scope_key' => debugCookieScopeKey($host),
        'session_cookie_name' => debugCookieScopedName('PHPSESSID', $host),
        'current_user_cookie_name' => debugCookieScopedName('currentUser', $host),
        'current_code_cookie_name' => debugCookieScopedName('currentCode', $host),
        'remember_cookie_name' => debugCookieScopedName('remember_token', $host),
    ],
    'cookies_seen_by_php' => [
        'cookie_names' => array_values(array_keys($_COOKIE)),
    ],
];

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
