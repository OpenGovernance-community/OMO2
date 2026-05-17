<?php

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';

function translationWorkerWriteError($message)
{
    $message = (string)$message;

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

if (PHP_SAPI !== 'cli') {
    translationWorkerWriteError("This script must run in CLI. Current SAPI: " . PHP_SAPI . PHP_EOL);
    exit(1);
}

$jobId = 0;
$verbose = false;
$arguments = isset($argv) && is_array($argv) ? array_slice($argv, 1) : [];

foreach ($arguments as $argument) {
    if (strpos($argument, '--job=') === 0) {
        $jobId = (int)substr($argument, strlen('--job='));
        continue;
    }

    if ($argument === '--verbose') {
        $verbose = true;
    }
}

$job = $jobId > 0
    ? \dbObject\TranslationBundleRefreshJob::claimPendingById($jobId)
    : \dbObject\TranslationBundleRefreshJob::claimNextPending();

if (!$job instanceof \dbObject\TranslationBundleRefreshJob) {
    if ($verbose) {
        $message = $jobId > 0
            ? '[translation-worker] No pending job claimed for job=' . $jobId
            : '[translation-worker] No pending translation job found';
        translationWorkerWriteInfo($message . PHP_EOL);
    }

    exit(0);
}

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

    exit(0);
} catch (\Throwable $exception) {
    $errorReport = translationWorkerBuildExceptionReport($jobData, $exception);
    \dbObject\TranslationBundleRefreshJob::markFailed((int)$job->getId(), $errorReport);
    translationWorkerWriteError($errorReport . PHP_EOL);
    exit(1);
}

?>
