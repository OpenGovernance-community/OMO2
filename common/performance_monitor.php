<?php

/** Low-cost request counters are independent of detailed SQL logging. */
function commonPerformanceEnabled(): bool
{
    $value = function_exists('envBool') ? envBool('PERFORMANCE_MONITOR_ENABLED', true)
        : !in_array(strtolower((string)getenv('PERFORMANCE_MONITOR_ENABLED')), ['0', 'false', 'off'], true);
    return (bool)$value;
}

function commonPerformanceCpu(): ?float
{
    if (!function_exists('getrusage')) { return null; }
    $usage = getrusage();
    if (!is_array($usage) || !isset($usage['ru_utime.tv_sec'], $usage['ru_stime.tv_sec'])) { return null; }
    return 1000 * ($usage['ru_utime.tv_sec'] + $usage['ru_stime.tv_sec'])
        + ($usage['ru_utime.tv_usec'] + $usage['ru_stime.tv_usec']) / 1000;
}

function commonPerformanceStart(): array
{
    return ['id' => bin2hex(random_bytes(16)), 'time' => microtime(true), 'ns' => hrtime(true),
        'cpu' => commonPerformanceCpu(), 'sql' => class_exists('dbObject\\DbObject', false)
            ? \dbObject\DbObject::getPerformanceCounters() : ['queries' => 0, 'ms' => 0, 'errors' => 0]];
}

function commonPerformanceRecord(array $start, array $context): void
{
    if (!commonPerformanceEnabled()) { return; }
    try {
        $sql = \dbObject\DbObject::getPerformanceCounters();
        $cpu = commonPerformanceCpu();
        $context += [
            'request_id' => $start['id'], 'parent_request_id' => '',
            'started_at' => gmdate('Y-m-d H:i:s', (int)$start['time']),
            'duration_ms' => max(0, (hrtime(true) - $start['ns']) / 1000000),
            'cpu_ms' => $cpu !== null && $start['cpu'] !== null ? max(0, $cpu - $start['cpu']) : null,
            'sql_ms' => max(0, $sql['ms'] - $start['sql']['ms']),
            'query_count' => max(0, $sql['queries'] - $start['sql']['queries']),
            'sql_errors' => max(0, $sql['errors'] - $start['sql']['errors']),
            'peak_memory' => memory_get_peak_usage(true),
        ];
        \dbObject\PerformanceRequest::record($context);
    } catch (\Throwable $error) {
        // Monitoring must never break application responses, including during deployment.
        error_log('Performance collection failed: ' . get_class($error));
    }
}

function commonPerformanceInit(): void
{
    if (isset($GLOBALS['commonPerformanceRequest']) || PHP_SAPI === 'cli' || !commonPerformanceEnabled()) { return; }
    $GLOBALS['commonPerformanceRequest'] = commonPerformanceStart();
    $GLOBALS['commonPerformanceCron'] = ['cron_state' => 0, 'cron_calls' => 0, 'cron_ms' => 0];
    register_shutdown_function(static function (): void {
        $start = $GLOBALS['commonPerformanceRequest'];
        $lastError = error_get_last();
        $fatal = $lastError && in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
        $route = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/unknown'));
        // Use the script, never an URL containing tokens or object identifiers.
        $route = substr(explode('?', $route, 2)[0], 0, 190);
        commonPerformanceRecord($start, ($GLOBALS['commonPerformanceCron'] ?? []) + [
            'route' => $route, 'method' => substr((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'), 0, 8), 'kind' => 'web',
            'IDorganization' => (int)($_SESSION['currentOrganization'] ?? 0),
            'IDuser' => (int)($_SESSION['currentUser'] ?? 0),
            'status_code' => $fatal ? 500 : (http_response_code() ?: 200),
            // Include time before the shared bootstrap, notably session lock waiting.
            'duration_ms' => max(0, (microtime(true) - (float)($_SERVER['REQUEST_TIME_FLOAT'] ?? $start['time'])) * 1000),
        ]);
    });
}

/** Record nested work separately; page totals already include inline cron time. */
function commonPerformanceCron(string $kind, string $name, callable $callback)
{
    if (!commonPerformanceEnabled()) { return $callback(); }
    $start = commonPerformanceStart();
    $parent = $GLOBALS['commonPerformanceParent'] ?? ($GLOBALS['commonPerformanceRequest']['id'] ?? '');
    $GLOBALS['commonPerformanceParent'] = $start['id'];
    $status = 200;
    $state = 2;
    try {
        $result = $callback();
        if (is_array($result) && !empty($result['skipped'])) { $state = 1; }
        if (is_array($result) && !empty($result['performanceFailedTasks'])) { $status = 500; }
        return $result;
    } catch (\Throwable $error) {
        $status = 500;
        throw $error;
    } finally {
        $duration = max(0, (hrtime(true) - $start['ns']) / 1000000);
        $GLOBALS['commonPerformanceParent'] = $parent;
        if ($kind === 'cron') {
            $stats = $GLOBALS['commonPerformanceCron'] ?? ['cron_state' => 0, 'cron_calls' => 0, 'cron_ms' => 0];
            $stats['cron_state'] = max($stats['cron_state'], $state);
            $stats['cron_calls']++;
            $stats['cron_ms'] += $duration;
            $GLOBALS['commonPerformanceCron'] = $stats;
        }
        commonPerformanceRecord($start, ['parent_request_id' => $parent, 'route' => substr($name, 0, 190),
            'method' => 'RUN', 'kind' => $kind, 'status_code' => $status,
            'cron_state' => $state, 'duration_ms' => $duration]);
    }
}
