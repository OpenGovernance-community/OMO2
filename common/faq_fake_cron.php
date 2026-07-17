<?php

if (!function_exists('faqFakeCronGetTmpDirectory')) {
	function faqFakeCronGetTmpDirectory()
	{
		return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';
	}
}

if (!function_exists('faqFakeCronEnsureTmpDirectory')) {
	function faqFakeCronEnsureTmpDirectory()
	{
		$tmpDirectory = faqFakeCronGetTmpDirectory();
		if (!is_dir($tmpDirectory)) {
			@mkdir($tmpDirectory, 0777, true);
		}

		return $tmpDirectory;
	}
}

if (!function_exists('faqFakeCronGetStatePath')) {
	function faqFakeCronGetStatePath()
	{
		return faqFakeCronEnsureTmpDirectory() . DIRECTORY_SEPARATOR . 'faq-fake-cron-last-run.txt';
	}
}

if (!function_exists('faqFakeCronGetLockPath')) {
	function faqFakeCronGetLockPath()
	{
		return faqFakeCronEnsureTmpDirectory() . DIRECTORY_SEPARATOR . 'faq-fake-cron.lock';
	}
}

if (!function_exists('faqFakeCronParseTimestamp')) {
	function faqFakeCronParseTimestamp($value)
	{
		$value = trim((string)$value);
		if ($value === '') {
			return 0;
		}

		$timestamp = strtotime($value);
		return $timestamp !== false ? (int)$timestamp : 0;
	}
}

if (!function_exists('faqFakeCronGetLastRunTimestamp')) {
	function faqFakeCronGetLastRunTimestamp()
	{
		$statePath = faqFakeCronGetStatePath();
		if (!is_file($statePath)) {
			return 0;
		}

		$contents = @file_get_contents($statePath);
		if ($contents === false) {
			return 0;
		}

		return faqFakeCronParseTimestamp($contents);
	}
}

if (!function_exists('faqFakeCronMarkLastRun')) {
	function faqFakeCronMarkLastRun($dateTimeString)
	{
		$statePath = faqFakeCronGetStatePath();
		return @file_put_contents($statePath, trim((string)$dateTimeString), LOCK_EX) !== false;
	}
}

if (!function_exists('faqFakeCronAcquireLock')) {
	function faqFakeCronAcquireLock()
	{
		$lockPath = faqFakeCronGetLockPath();
		$now = time();
		$lockTtl = \dbObject\FAQ::getFakeCronLockTtlSeconds();
		if (is_file($lockPath)) {
			$lockAge = $now - (int)@filemtime($lockPath);
			if ($lockAge < $lockTtl) {
				return false;
			}

			@unlink($lockPath);
		}

		$handle = @fopen($lockPath, 'x');
		if ($handle === false) {
			return false;
		}

		fwrite($handle, (string)$now);
		fclose($handle);
		$GLOBALS['faqFakeCronLockPath'] = $lockPath;
		return true;
	}
}

if (!function_exists('faqFakeCronReleaseLock')) {
	function faqFakeCronReleaseLock()
	{
		$lockPath = (string)($GLOBALS['faqFakeCronLockPath'] ?? faqFakeCronGetLockPath());
		if ($lockPath !== '' && is_file($lockPath)) {
			@unlink($lockPath);
		}

		unset($GLOBALS['faqFakeCronLockPath']);
	}
}

if (!function_exists('faq_run_fake_cron_batch')) {
	function faq_run_fake_cron_batch($limit = null, $referenceDateTime = null)
	{
		return \dbObject\FAQ::runReliabilityRefreshBatch($limit, $referenceDateTime);
	}
}

if (!function_exists('faq_maybe_run_fake_cron')) {
	function faq_maybe_run_fake_cron()
	{
		static $alreadyChecked = false;
		if ($alreadyChecked) {
			return 0;
		}
		$alreadyChecked = true;

		if (!\dbObject\FAQ::hasVoteColumns() || !\dbObject\FAQ::hasScoreAnalyticsColumns()) {
			return 0;
		}

		$now = time();
		$intervalSeconds = \dbObject\FAQ::getFakeCronIntervalSeconds();
		$lastRunTimestamp = faqFakeCronGetLastRunTimestamp();
		if ($lastRunTimestamp > 0 && ($now - $lastRunTimestamp) < $intervalSeconds) {
			return 0;
		}

		if (!faqFakeCronAcquireLock()) {
			return 0;
		}

		try {
			$lastRunTimestamp = faqFakeCronGetLastRunTimestamp();
			if ($lastRunTimestamp > 0 && ($now - $lastRunTimestamp) < $intervalSeconds) {
				return 0;
			}

			$runDateTime = date('Y-m-d H:i:s', $now);
			$processedCount = faq_run_fake_cron_batch(\dbObject\FAQ::getFakeCronBatchSize(), $runDateTime);
			faqFakeCronMarkLastRun($runDateTime);
			return $processedCount;
		} finally {
			faqFakeCronReleaseLock();
		}
	}
}

?>
