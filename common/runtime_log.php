<?php

if (!function_exists('commonRuntimeLogIsAbsolutePath')) {
    function commonRuntimeLogIsAbsolutePath($path)
    {
        $path = (string)$path;
        return $path !== '' && (
            $path[0] === '/'
            || $path[0] === '\\'
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
        );
    }
}

if (!function_exists('commonRuntimeLogDirectory')) {
    function commonRuntimeLogDirectory()
    {
        $configuredDirectory = function_exists('envValue')
            ? trim((string)envValue('RUNTIME_LOG_DIR', ''))
            : trim((string)(getenv('RUNTIME_LOG_DIR') ?: ''));
        $projectRoot = dirname(__DIR__);

        if ($configuredDirectory === '') {
            return dirname($projectRoot) . DIRECTORY_SEPARATOR . 'log';
        }

        if (!commonRuntimeLogIsAbsolutePath($configuredDirectory)) {
            $configuredDirectory = $projectRoot . DIRECTORY_SEPARATOR . $configuredDirectory;
        }

        return rtrim($configuredDirectory, "/\\");
    }
}

if (!function_exists('commonRuntimeLogPath')) {
    function commonRuntimeLogPath($relativePath)
    {
        $relativePath = str_replace('\\', '/', trim((string)$relativePath));
        $segments = array_values(array_filter(explode('/', $relativePath), static function ($segment) {
            return $segment !== '';
        }));

        if ($segments === array() || in_array('..', $segments, true) || in_array('.', $segments, true)) {
            throw new InvalidArgumentException('Invalid runtime log path.');
        }

        return commonRuntimeLogDirectory() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
    }
}

if (!function_exists('commonConfigurePhpErrorLog')) {
    function commonConfigurePhpErrorLog()
    {
        $logDirectory = commonRuntimeLogDirectory();
        if (!is_dir($logDirectory) && !@mkdir($logDirectory, 0770, true) && !is_dir($logDirectory)) {
            return false;
        }

        return ini_set('error_log', commonRuntimeLogPath('php-errors.log')) !== false;
    }
}

commonConfigurePhpErrorLog();
