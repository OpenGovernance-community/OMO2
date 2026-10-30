<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';

use dbObject\DbObject;

function sqlThresholdExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$path = tempnam(sys_get_temp_dir(), 'sql-threshold-');
if ($path === false) throw new RuntimeException('Unable to create the log.');
putenv('DB_QUERY_LOG_ENABLED=true');
putenv('DB_QUERY_LOG_MIN_MS=60000');
putenv('DB_QUERY_LOG_PATH=' . $path);
try {
    $profile = DbObject::beginSqlPerformanceProfile("SELECT 'fast-secret'", ['token' => 'parameter-secret']);
    DbObject::finishSqlPerformanceProfile($profile, 'fast', true, 1);
    sqlThresholdExpect(file_get_contents($path) === '', 'Fast successful queries do not create detailed log entries.');
    sqlThresholdExpect(DbObject::getPerformanceCounters()['queries'] === 1, 'Unlogged queries still update counters.');

    $callerLine = __LINE__ + 1;
    $profile = DbObject::beginSqlPerformanceProfile("SELECT 'error-secret' -- hidden-comment", ['token' => 'parameter-secret']);
    DbObject::finishSqlPerformanceProfile($profile, 'failed', false, 0, new RuntimeException('failure', 7));
    DbObject::writeSqlPerformanceRequestSummary();
    $contents = file_get_contents($path);
    $rows = array_map(static fn ($line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
        array_values(array_filter(explode("\n", trim($contents)))));
    sqlThresholdExpect(count($rows) === 2 && $rows[0]['event'] === 'query' && $rows[1]['event'] === 'request_summary',
        'Errors are logged even below the threshold, followed by a single request summary.');
    sqlThresholdExpect($rows[0]['success'] === false && $rows[0]['error_code'] === '7', 'Error diagnostics remain available.');
    sqlThresholdExpect($rows[0]['caller']['file'] === 'tests/dbobject_sql_performance_threshold_test.php'
        && $rows[0]['caller']['line'] === $callerLine, 'Deferred formatting retains the original call site.');
    foreach (['fast-secret', 'error-secret', 'parameter-secret', 'hidden-comment'] as $secret) {
        sqlThresholdExpect(!str_contains($contents, $secret), 'Logs never contain literal, comment or parameter values.');
    }
    sqlThresholdExpect($rows[1]['query_count'] === 2 && $rows[1]['logged_query_count'] === 1
        && $rows[1]['error_count'] === 1, 'Summary counters include both skipped and failed queries.');
    echo "dbobject_sql_performance_threshold_test: OK (threshold, errors, counters, original caller and redaction)\n";
} finally {
    unlink($path);
}
