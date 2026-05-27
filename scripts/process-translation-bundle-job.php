<?php

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';

function translationWorkerWriteError($message)
{
    $message = (string)$message;

    if (function_exists('translationBundleWriteWorkerDebug')) {
        translationBundleWriteWorkerDebug('[worker-stderr] ' . trim($message), 0, false);
    }

    if (PHP_SAPI !== 'cli') {
        if (!headers_sent()) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
        }

        echo $message;
        return;
    }

    if (defined('STDERR')) {
        fwrite(STDERR, $message);
        return;
    }

    $stderr = @fopen('php://stderr', 'wb');
    if (is_resource($stderr)) {
        fwrite($stderr, $message);
        fclose($stderr);
        return;
    }

    error_log(trim($message));
}

function translationWorkerWriteInfo($message)
{
    $message = (string)$message;

    if (function_exists('translationBundleWriteWorkerDebug')) {
        translationBundleWriteWorkerDebug('[worker-stdout] ' . trim($message), 0, false);
    }

    if (defined('STDOUT')) {
        fwrite(STDOUT, $message);
        return;
    }

    echo $message;
}

function translationWorkerFormatJobLabel(array $jobData)
{
    return 'job=' . (int)($jobData['id'] ?? 0)
        . ' bundle=' . (string)($jobData['bundle_key'] ?? '')
        . ' locale=' . (string)($jobData['locale'] ?? '');
}

function translationWorkerBuildExceptionReport(array $jobData, \Throwable $exception)
{
    $lines = [
        '[translation-worker] Failed ' . translationWorkerFormatJobLabel($jobData),
        'Message: ' . $exception->getMessage(),
        'Type: ' . get_class($exception),
        'File: ' . $exception->getFile() . ':' . $exception->getLine(),
    ];

    $trace = trim((string)$exception->getTraceAsString());
    if ($trace !== '') {
        $lines[] = 'Trace:';
        $lines[] = $trace;
    }

    return implode(PHP_EOL, $lines);
}

function translationWorkerGetConfiguredHttpToken()
{
    return function_exists('envValue')
        ? trim((string)envValue('TRANSLATION_WORKER_HTTP_TOKEN', ''))
        : '';
}

function translationWorkerGetHttpRequestToken()
{
    $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
    if ($token !== '') {
        return $token;
    }

    $headerToken = trim((string)($_SERVER['HTTP_X_TRANSLATION_WORKER_TOKEN'] ?? ''));
    if ($headerToken !== '') {
        return $headerToken;
    }

    $authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        return trim((string)$matches[1]);
    }

    return '';
}

function translationWorkerParseBoolFlag($value)
{
    $normalized = strtolower(trim((string)$value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

function translationWorkerProcessJob(\dbObject\TranslationBundleRefreshJob $job, $verbose = false)
{
    $jobData = [
        'id' => (int)$job->getId(),
        'bundle_key' => (string)$job->get('bundle_key'),
        'locale' => (string)$job->get('locale'),
        'source_hash' => (string)$job->get('source_hash'),
        'source_json' => (string)$job->get('source_json'),
    ];

    if ($verbose) {
        translationWorkerWriteInfo('[translation-worker] Processing ' . translationWorkerFormatJobLabel($jobData) . PHP_EOL);
    }

    try {
        translationBundleProcessRefreshJob($jobData);
        \dbObject\TranslationBundleRefreshJob::markCompleted((int)$job->getId());

        if ($verbose) {
            translationWorkerWriteInfo('[translation-worker] Completed ' . translationWorkerFormatJobLabel($jobData) . PHP_EOL);
        }

        return [
            'status' => true,
            'jobData' => $jobData,
            'error' => '',
        ];
    } catch (\Throwable $exception) {
        $errorReport = translationWorkerBuildExceptionReport($jobData, $exception);
        \dbObject\TranslationBundleRefreshJob::markFailed((int)$job->getId(), $errorReport);
        translationWorkerWriteError($errorReport . PHP_EOL);

        return [
            'status' => false,
            'jobData' => $jobData,
            'error' => $errorReport,
        ];
    }
}

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=UTF-8');
    }

    if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if (function_exists('ignore_user_abort')) {
        @ignore_user_abort(true);
    }

    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }

    $configuredHttpToken = translationWorkerGetConfiguredHttpToken();
    if ($configuredHttpToken === '') {
        if (!headers_sent()) {
            http_response_code(503);
        }
        translationWorkerWriteError(
            "HTTP access is disabled for this worker. Configure TRANSLATION_WORKER_HTTP_TOKEN.\n"
        );
        exit(1);
    }

    $providedHttpToken = translationWorkerGetHttpRequestToken();
    if ($providedHttpToken === '' || !hash_equals($configuredHttpToken, $providedHttpToken)) {
        if (!headers_sent()) {
            http_response_code(403);
        }
        translationWorkerWriteError("Forbidden.\n");
        exit(1);
    }
}

$jobId = 0;
$maxJobs = 1;
$verbose = false;

if ($isCli) {
    $arguments = isset($argv) && is_array($argv) ? array_slice($argv, 1) : [];

    foreach ($arguments as $argument) {
        if (strpos($argument, '--job=') === 0) {
            $jobId = (int)substr($argument, strlen('--job='));
            continue;
        }

        if (strpos($argument, '--max=') === 0) {
            $maxJobs = max(1, (int)substr($argument, strlen('--max=')));
            continue;
        }

        if ($argument === '--verbose') {
            $verbose = true;
        }
    }
} else {
    $jobId = isset($_GET['job']) ? (int)$_GET['job'] : (isset($_POST['job']) ? (int)$_POST['job'] : 0);
    $maxJobs = isset($_GET['max']) ? max(1, (int)$_GET['max']) : (isset($_POST['max']) ? max(1, (int)$_POST['max']) : 1);
    $verbose = translationWorkerParseBoolFlag($_GET['verbose'] ?? ($_POST['verbose'] ?? '0'));
}

if ($jobId > 0) {
    $job = \dbObject\TranslationBundleRefreshJob::claimPendingById($jobId);

    if (!$job instanceof \dbObject\TranslationBundleRefreshJob) {
        if ($verbose) {
            translationWorkerWriteInfo('[translation-worker] No pending job claimed for job=' . $jobId . PHP_EOL);
        }

        exit(0);
    }

    $result = translationWorkerProcessJob($job, $verbose);
    exit(!empty($result['status']) ? 0 : 1);
}

$processedCount = 0;
$failedCount = 0;

while ($processedCount < $maxJobs) {
    $job = \dbObject\TranslationBundleRefreshJob::claimNextPending();
    if (!$job instanceof \dbObject\TranslationBundleRefreshJob) {
        break;
    }

    $processedCount++;
    $result = translationWorkerProcessJob($job, $verbose);
    if (empty($result['status'])) {
        $failedCount++;
    }
}

if ($verbose) {
    if ($processedCount === 0) {
        translationWorkerWriteInfo('[translation-worker] No pending translation job found' . PHP_EOL);
    } else {
        translationWorkerWriteInfo(
            '[translation-worker] Run summary processed=' . $processedCount
            . ' failed=' . $failedCount
            . ' max=' . $maxJobs
            . PHP_EOL
        );
    }
}

if ($failedCount > 0) {
    exit(1);
}

exit(0);

?>
