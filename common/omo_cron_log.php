<?php

require_once __DIR__ . '/runtime_log.php';

if (!function_exists('omoCronLogReadEnvValue')) {
    function omoCronLogReadEnvValue($key, $default = null)
    {
        if (function_exists('commonReadRuntimeEnvValue')) {
            return commonReadRuntimeEnvValue($key, $default);
        }
        if (function_exists('envValue')) {
            return envValue($key, $default);
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        $value = getenv((string)$key);
        return $value !== false ? $value : $default;
    }
}

if (!function_exists('omoCronLogIsEnabled')) {
    function omoCronLogIsEnabled()
    {
        $value = omoCronLogReadEnvValue('OMO_CRON_LOG_ENABLED', true);
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string)$value)), array('1', 'true', 'yes', 'on'), true);
    }
}

if (!function_exists('omoCronLogIsAbsolutePath')) {
    function omoCronLogIsAbsolutePath($path)
    {
        $path = (string)$path;
        return $path !== '' && (
            $path[0] === '/'
            || $path[0] === '\\'
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
        );
    }
}

if (!function_exists('omoCronLogGetPath')) {
    function omoCronLogGetPath()
    {
        $path = trim((string)omoCronLogReadEnvValue('OMO_CRON_LOG_PATH', ''));
        if ($path === '') {
            return commonRuntimeLogPath('omo-cron/omo-cron.jsonl');
        }
        if (!omoCronLogIsAbsolutePath($path)) {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        }

        return $path;
    }
}

if (!function_exists('omoCronLogWrite')) {
    function omoCronLogWrite(array $payload)
    {
        if (!omoCronLogIsEnabled()) {
            return false;
        }

        $path = omoCronLogGetPath();
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
            error_log('Unable to create OMO cron log directory: ' . $directory);
            return false;
        }

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($line) || $line === '') {
            return false;
        }

        if (@file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('Unable to write OMO cron log: ' . $path);
            return false;
        }

        return true;
    }
}

if (!function_exists('omoCronStartMaintenanceLog')) {
    function omoCronStartMaintenanceLog($source)
    {
        if (!omoCronLogIsEnabled()) {
            return null;
        }

        return array(
            'started_at_ns' => hrtime(true),
            'source' => trim((string)$source) !== '' ? trim((string)$source) : 'unknown',
        );
    }
}

if (!function_exists('omoCronFinishMaintenanceLog')) {
    function omoCronFinishMaintenanceLog($context, array $failedTasks)
    {
        if (!is_array($context) || !isset($context['started_at_ns'])) {
            return false;
        }

        $durationMs = max(0.0, (hrtime(true) - (int)$context['started_at_ns']) / 1000000);
        $payload = array(
            'event' => 'omo_cron_invocation',
            'time' => date('c'),
            'source' => (string)$context['source'],
            'status' => $failedTasks === array() ? 'success' : 'partial_failure',
            'duration_ms' => round($durationMs, 3),
        );
        if ($failedTasks !== array()) {
            $payload['failed_tasks'] = array_values($failedTasks);
        }

        return omoCronLogWrite($payload);
    }
}

if (!function_exists('omoCronLogRejectedInvocation')) {
    function omoCronLogRejectedInvocation($source, $reason, $startedAtNs)
    {
        return omoCronLogWrite(array(
            'event' => 'omo_cron_invocation',
            'time' => date('c'),
            'source' => trim((string)$source) !== '' ? trim((string)$source) : 'unknown',
            'status' => 'rejected',
            'reason' => (string)$reason,
            'duration_ms' => round(max(0.0, (hrtime(true) - (int)$startedAtNs) / 1000000), 3),
        ));
    }
}
