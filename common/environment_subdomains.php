<?php

function commonLoadRuntimeEnvIfAvailable()
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $envLoaderPath = dirname(__DIR__) . '/includes/env.php';
    if (is_file($envLoaderPath)) {
        require_once $envLoaderPath;

        if (function_exists('loadEnv') && function_exists('envGetRuntimeEnvPaths')) {
            foreach (envGetRuntimeEnvPaths() as $runtimeEnvPath) {
                loadEnv($runtimeEnvPath);
            }
        }
    }

    $loaded = true;
}

function commonReadRuntimeEnvValue($key, $default = null)
{
    commonLoadRuntimeEnvIfAvailable();

    if (function_exists('envValue')) {
        return envValue($key, $default);
    }

    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }

    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    return $default;
}

function commonReadRuntimeEnvBool($key, $default = false)
{
    $value = commonReadRuntimeEnvValue($key, null);

    if ($value === null || $value === '') {
        return (bool)$default;
    }

    $normalized = strtolower(trim((string)$value));

    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return (bool)$default;
}

function commonGetConfiguredEnvironmentSubdomains()
{
    return ['dev', 'beta', 'deploy'];
}

function commonGetHostRootPartCount(array $parts)
{
    $partCount = count($parts);
    if ($partCount === 0) {
        return 0;
    }

    $rootPartCount = min(2, $partCount);
    if ($partCount === 2 && ($parts[1] ?? '') === 'localhost') {
        $rootPartCount = 1;
    }

    if ($partCount >= 3) {
        $environmentCandidate = strtolower((string)($parts[$partCount - 3] ?? ''));
        if (in_array($environmentCandidate, commonGetConfiguredEnvironmentSubdomains(), true)) {
            $rootPartCount = 3;
        }
    }

    $configuredRoot = strtolower(trim((string)commonReadRuntimeEnvValue('COOKIE_ROOT_HOST', '')));
    $configuredRoot = preg_replace('/:\d+$/', '', $configuredRoot);
    $rootParts = array_values(array_filter(explode('.', trim($configuredRoot, '.'))));
    if ($rootParts !== [] && $partCount >= count($rootParts)
        && array_map('strtolower', array_slice($parts, -count($rootParts))) === $rootParts) {
        // A parent cookie scope must not turn a reserved environment into an organization.
        $rootPartCount = max($rootPartCount, count($rootParts));
    }

    return $rootPartCount;
}

function commonUseOrganizationSubdomains()
{
    return commonReadRuntimeEnvBool('ORGANIZATION_SUBDOMAIN_ROUTING', false);
}
