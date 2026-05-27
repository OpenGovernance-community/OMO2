<?php

require_once dirname(__DIR__) . '/shared_functions.php';

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "Ce script doit etre execute en CLI.\n");
	exit(1);
}

$jobId = 0;

foreach (array_slice($argv, 1) as $argument) {
	if (strpos($argument, '--job=') === 0) {
		$jobId = (int)substr($argument, strlen('--job='));
		break;
	}
}

if ($jobId <= 0) {
	fwrite(STDERR, "Usage: php scripts/process-search-job.php --job=<id>\n");
	exit(1);
}

$success = \dbObject\SearchJob::processJobById($jobId);
exit($success ? 0 : 1);
