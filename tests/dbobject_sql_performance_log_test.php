<?php
declare(strict_types=1);

use dbObject\DbObject;

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';

function assertSqlPerformanceTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$logPath = tempnam(sys_get_temp_dir(), 'sql-performance-');
if ($logPath === false) {
    throw new RuntimeException('Unable to create the temporary log file.');
}

file_put_contents($logPath, '');
$pathMode = $argv[1] ?? 'file';
$runtimeDirectory = $logPath . '-runtime';
$configuredPath = $logPath;
if ($pathMode !== 'file') {
    assertSqlPerformanceTest(in_array($pathMode, ['default', 'directory', 'missing-directory', 'backslash-directory', 'relative-directory'], true), 'Unknown path mode.');
    unlink($logPath);
    mkdir($runtimeDirectory, 0770);
    putenv('RUNTIME_LOG_DIR=' . $runtimeDirectory);
    $configuredPath = match ($pathMode) {
        'default' => '',
        'directory' => $runtimeDirectory,
        'missing-directory' => $runtimeDirectory . '/missing/',
        'backslash-directory' => $runtimeDirectory . '/missing\\',
        'relative-directory' => 'sql',
    };
    $logPath = commonRuntimeLogPath('sql-performance/sql-performance-' . date('Y-m-d') . '.jsonl');
}
putenv('DB_QUERY_LOG_ENABLED=true');
putenv('DB_QUERY_LOG_MIN_MS=0');
putenv('DB_QUERY_LOG_PATH=' . $configuredPath);

$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
$_SERVER['REQUEST_URI'] = '/tests/sql-performance?token=request-secret-value';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/tests/dbobject_sql_performance_log_test.php';
$statusCode = (int)($argv[2] ?? 404);
if ($statusCode > 0) {
    http_response_code($statusCode);
}

$literalSecret = 'literal-secret-value';
$doubleQuotedSecret = 'double-quoted-secret-value';
$commentSecret = 'comment-secret-value';
$parameterSecret = 'parameter-secret-value';
$query = "SELECT * FROM sample WHERE token = '{$literalSecret}' AND alias = \"{$doubleQuotedSecret}\" AND owner_id = :owner_id AND code = 12345 -- {$commentSecret}";
$profile = DbObject::beginSqlPerformanceProfile($query, array(
    'owner_id' => $parameterSecret,
));
usleep(2000);
DbObject::finishSqlPerformanceProfile($profile, 'test_query', true, 3);
DbObject::writeSqlPerformanceRequestSummary();

$contents = (string)file_get_contents($logPath);
$lines = array_values(array_filter(explode("\n", trim($contents))));
assertSqlPerformanceTest(count($lines) === 2, 'The log must contain one query and one request summary.');

$queryEvent = json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR);
$summaryEvent = json_decode($lines[1], true, 512, JSON_THROW_ON_ERROR);

assertSqlPerformanceTest($queryEvent['event'] === 'query', 'The first event must describe a query.');
assertSqlPerformanceTest($queryEvent['duration_ms'] > 0, 'The query duration must be positive.');
assertSqlPerformanceTest($queryEvent['row_count'] === 3, 'The row count must be logged.');
assertSqlPerformanceTest(strlen($queryEvent['fingerprint']) === 64, 'The fingerprint must be a SHA-256 hash.');
assertSqlPerformanceTest($queryEvent['parameter_count'] === 1, 'The parameter count must be logged.');
assertSqlPerformanceTest(strpos($contents, $literalSecret) === false, 'SQL literals must not be logged.');
assertSqlPerformanceTest(strpos($contents, $doubleQuotedSecret) === false, 'Double-quoted SQL literals must not be logged.');
assertSqlPerformanceTest(strpos($contents, $commentSecret) === false, 'SQL comments must not be logged.');
assertSqlPerformanceTest(strpos($contents, $parameterSecret) === false, 'SQL parameter values must not be logged.');
assertSqlPerformanceTest(strpos($contents, 'request-secret-value') === false, 'Query-string values must not be logged.');
assertSqlPerformanceTest(strpos($queryEvent['sql'], '12345') === false, 'Numeric literals must not be logged.');

assertSqlPerformanceTest($summaryEvent['event'] === 'request_summary', 'The second event must summarize the request.');
assertSqlPerformanceTest($summaryEvent['status_code'] === ($statusCode ?: 200), 'The summary must preserve the HTTP status independently of SQL errors.');
assertSqlPerformanceTest($summaryEvent['error_count'] === 0, 'An HTTP error is not an SQL error.');
assertSqlPerformanceTest($summaryEvent['query_count'] === 1, 'The summary must count the query.');
assertSqlPerformanceTest($summaryEvent['logged_query_count'] === 1, 'The summary must count the logged query.');
assertSqlPerformanceTest($summaryEvent['database_duration_ms'] > 0, 'The summary must include database time.');
assertSqlPerformanceTest($summaryEvent['request_id'] === $queryEvent['request_id'], 'Events must share a request identifier.');

unlink($logPath);
if ($pathMode !== 'file') {
    rmdir(dirname($logPath));
    rmdir($runtimeDirectory);
    putenv('RUNTIME_LOG_DIR');
}
putenv('DB_QUERY_LOG_ENABLED');
putenv('DB_QUERY_LOG_MIN_MS');
putenv('DB_QUERY_LOG_PATH');

echo "dbobject_sql_performance_log_test ($pathMode): OK\n";
