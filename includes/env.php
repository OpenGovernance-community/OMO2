<?php

function loadEnv($path)
{
    static $loadedPaths = [];

    if (!is_string($path) || $path === '' || !file_exists($path) || isset($loadedPaths[$path])) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim((string)$line);

        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        $firstChar = substr($value, 0, 1);
        $lastChar = substr($value, -1);
        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    $loadedPaths[$path] = true;
}

function envGetProjectRootPath()
{
    return dirname(__DIR__);
}

function envGetPrimaryEnvPath()
{
    return envGetProjectRootPath() . '/.env';
}

function envGetLocalOverrideEnvPath()
{
    return envGetProjectRootPath() . '/docker/app/.env.private';
}

function envNormalizeRuntimeHost($host = null)
{
    if (!is_string($host) || $host === '') {
        if (!empty($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['SERVER_NAME']) && is_string($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        } else {
            return '';
        }
    }

    $host = strtolower(trim($host));
    if ($host === '') {
        return '';
    }

    if (substr($host, 0, 1) === '[') {
        $endBracketPosition = strpos($host, ']');
        if ($endBracketPosition !== false) {
            return substr($host, 1, $endBracketPosition - 1);
        }
    }

    if (substr_count($host, ':') === 1) {
        $parts = explode(':', $host, 2);
        $host = (string)$parts[0];
    }

    return trim($host);
}

function envIsLocalRuntimeHost($host = null)
{
    $host = envNormalizeRuntimeHost($host);
    if ($host === '') {
        return false;
    }

    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
        return true;
    }

    if (substr($host, -10) === '.localhost') {
        return true;
    }

    if ($host === 'localtest.me' || substr($host, -13) === '.localtest.me') {
        return true;
    }

    return false;
}

function envGetRuntimeEnvPaths($host = null)
{
    $paths = [];

    $primaryEnvPath = envGetPrimaryEnvPath();
    if ($primaryEnvPath !== '') {
        $paths[] = $primaryEnvPath;
    }

    if (envIsLocalRuntimeHost($host)) {
        $localOverrideEnvPath = envGetLocalOverrideEnvPath();
        if ($localOverrideEnvPath !== '') {
            $paths[] = $localOverrideEnvPath;
        }
    }

    return array_values(array_unique($paths));
}

function envValue($key, $default = null)
{
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

function envBool($key, $default = false)
{
    $value = envValue($key, null);

    if ($value === null) {
        return (bool)$default;
    }

    if (is_bool($value)) {
        return $value;
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

function envInt($key, $default = 0)
{
    $value = envValue($key, null);

    if ($value === null || $value === '') {
        return (int)$default;
    }

    return (int)$value;
}
