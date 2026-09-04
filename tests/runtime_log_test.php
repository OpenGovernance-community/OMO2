<?php
declare(strict_types=1);

putenv('RUNTIME_LOG_DIR=');
unset($_ENV['RUNTIME_LOG_DIR'], $_SERVER['RUNTIME_LOG_DIR']);

require_once dirname(__DIR__) . '/common/runtime_log.php';

function assertRuntimeLogTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$projectRoot = realpath(dirname(__DIR__));
$expectedDirectory = dirname((string)$projectRoot) . DIRECTORY_SEPARATOR . 'log';
$defaultDirectory = commonRuntimeLogDirectory();

assertRuntimeLogTest($projectRoot !== false, 'The project root must be resolvable.');
assertRuntimeLogTest($defaultDirectory === $expectedDirectory, 'The default log directory must be next to the public root.');
assertRuntimeLogTest(
    !str_starts_with($defaultDirectory . DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR),
    'The default log directory must not be inside the public root.'
);
assertRuntimeLogTest(
    commonRuntimeLogPath('sql-performance/example.jsonl')
        === $expectedDirectory . DIRECTORY_SEPARATOR . 'sql-performance' . DIRECTORY_SEPARATOR . 'example.jsonl',
    'Nested log paths must remain below the runtime log directory.'
);
assertRuntimeLogTest(
    ini_get('error_log') === commonRuntimeLogPath('php-errors.log'),
    'PHP errors must use the private runtime log directory after bootstrap.'
);

$invalidPathRejected = false;
try {
    commonRuntimeLogPath('../public.log');
} catch (InvalidArgumentException $exception) {
    $invalidPathRejected = true;
}
assertRuntimeLogTest($invalidPathRejected, 'Parent path traversal must be rejected.');

echo "runtime_log_test: OK\n";
