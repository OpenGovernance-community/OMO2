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
