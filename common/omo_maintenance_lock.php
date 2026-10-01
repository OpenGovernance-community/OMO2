<?php

require_once __DIR__ . '/omo_cron_log.php';

/** Serialize maintenance across CLI, HTTP cron and browser fallback for this database. */
function omoRunMaintenanceLocked(callable $run, string $source, bool $force = false): array
{
    $databaseKey = hash('sha256', (string)($GLOBALS['dbServer'] ?? '') . ':' . (string)($GLOBALS['dbName'] ?? ''));
    $path = commonRuntimeLogPath('omo-cron/maintenance-' . $databaseKey . '.lock');
    $directory = dirname($path);
    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create maintenance lock directory.');
    }
    $lock = @fopen($path, 'c+');
    if ($lock === false) {
        throw new RuntimeException('Unable to open maintenance lock.');
    }

    $startedAt = hrtime(true);
    try {
        $skipReason = '';
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $skipReason = 'already_running';
        } else {
            $lastCompletedAt = (int)stream_get_contents($lock);
            if (!$force && $lastCompletedAt > 0 && time() - $lastCompletedAt < 60) {
                $skipReason = 'recently_completed';
            }
        }
        if ($skipReason !== '') {
            omoCronLogWrite([
                'event' => 'omo_cron_invocation',
                'time' => date('c'),
                'source' => $source,
                'status' => 'skipped',
                'reason' => $skipReason,
                'duration_ms' => round((hrtime(true) - $startedAt) / 1000000, 3),
            ]);
            return ['skipped' => $skipReason];
        }

        $result = $run();
        rewind($lock);
        if (!ftruncate($lock, 0) || fwrite($lock, (string)time()) === false || !fflush($lock)) {
            error_log('Unable to record maintenance completion time.');
        }
        return $result;
    } finally {
        // Closing releases our lock even after a task throws; never unlink the lock file.
        fclose($lock);
    }
}
