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

if (PHP_SAPI !== 'cli') {
    translationWorkerWriteError("This script must run in CLI.\n");
    exit(1);
}

$jobId = 0;
$arguments = isset($argv) && is_array($argv) ? array_slice($argv, 1) : [];

foreach ($arguments as $argument) {
    if (strpos($argument, '--job=') === 0) {
        $jobId = (int)substr($argument, strlen('--job='));
        break;
    }
}

$job = $jobId > 0
    ? \dbObject\TranslationBundleRefreshJob::claimPendingById($jobId)
    : \dbObject\TranslationBundleRefreshJob::claimNextPending();

if (!$job instanceof \dbObject\TranslationBundleRefreshJob) {
    exit(0);
}

$jobData = [
    'id' => (int)$job->getId(),
    'bundle_key' => (string)$job->get('bundle_key'),
    'locale' => (string)$job->get('locale'),
    'source_hash' => (string)$job->get('source_hash'),
    'source_json' => (string)$job->get('source_json'),
];

try {
    translationBundleProcessRefreshJob($jobData);
    \dbObject\TranslationBundleRefreshJob::markCompleted((int)$job->getId());
    exit(0);
} catch (\Throwable $exception) {
    \dbObject\TranslationBundleRefreshJob::markFailed((int)$job->getId(), $exception->getMessage());
    translationWorkerWriteError($exception->getMessage() . "\n");
    exit(1);
}

?>
