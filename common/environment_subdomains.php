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

        if (function_exists('loadEnv')) {
            loadEnv(dirname(__DIR__) . '/.env');
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

function commonUseOrganizationSubdomains()
{
    return commonReadRuntimeEnvBool('ORGANIZATION_SUBDOMAIN_ROUTING', false);
}
