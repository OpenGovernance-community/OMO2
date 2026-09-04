<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/omo_cron_log.php';

function assertOmoCronLogTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

putenv('OMO_CRON_LOG_PATH=');
unset($_ENV['OMO_CRON_LOG_PATH'], $_SERVER['OMO_CRON_LOG_PATH']);
$defaultLogPath = omoCronLogGetPath();
assertOmoCronLogTest(basename($defaultLogPath) === 'omo-cron.jsonl', 'The default log must use one stable file.');
assertOmoCronLogTest(
    str_starts_with($defaultLogPath, dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR),
    'The default cron log must be outside the public project root.'
);

$logPath = tempnam(sys_get_temp_dir(), 'omo-cron-');
if ($logPath === false) {
    throw new RuntimeException('Unable to create the temporary cron log file.');
}

file_put_contents($logPath, '');
putenv('OMO_CRON_LOG_ENABLED=true');
putenv('OMO_CRON_LOG_PATH=' . $logPath);

$context = omoCronStartMaintenanceLog('runtime_endpoint');
usleep(2000);
$written = omoCronFinishMaintenanceLog($context, array());
assertOmoCronLogTest($written, 'The maintenance event must be written.');

$lines = array_values(array_filter(explode("\n", trim((string)file_get_contents($logPath)))));
assertOmoCronLogTest(count($lines) === 1, 'The log must contain one maintenance event.');
$event = json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR);

assertOmoCronLogTest($event['event'] === 'omo_cron_invocation', 'The event type must identify cron invocations.');
assertOmoCronLogTest($event['source'] === 'runtime_endpoint', 'The invocation source must be logged.');
assertOmoCronLogTest($event['status'] === 'success', 'The invocation status must be logged.');
assertOmoCronLogTest($event['duration_ms'] > 0, 'The total duration must be positive.');
assertOmoCronLogTest(array_keys($event) === array('event', 'time', 'source', 'status', 'duration_ms'), 'Successful events must remain compact.');

$rejectedStartedAtNs = hrtime(true);
usleep(1000);
omoCronLogRejectedInvocation('server_cron_http', 'unauthorized', $rejectedStartedAtNs);
$lines = array_values(array_filter(explode("\n", trim((string)file_get_contents($logPath)))));
$rejectedEvent = json_decode($lines[1], true, 512, JSON_THROW_ON_ERROR);
assertOmoCronLogTest($rejectedEvent['status'] === 'rejected', 'Rejected invocations must be identifiable.');
assertOmoCronLogTest($rejectedEvent['reason'] === 'unauthorized', 'The rejection reason must be logged.');

putenv('OMO_CRON_LOG_ENABLED=false');
omoCronLogRejectedInvocation('server_cron_http', 'unauthorized', hrtime(true));
$disabledLines = array_values(array_filter(explode("\n", trim((string)file_get_contents($logPath)))));
assertOmoCronLogTest(count($disabledLines) === 2, 'Disabled logging must not append events.');

unlink($logPath);
putenv('OMO_CRON_LOG_ENABLED');
putenv('OMO_CRON_LOG_PATH');

echo "omo_cron_log_test: OK\n";
