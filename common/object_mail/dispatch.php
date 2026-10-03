<?php
/** Best-effort immediate dispatch; the existing OMO cron remains the recovery path. */
function omoObjectMailDispatch(int $mailId): bool
{
    if ($mailId <= 0) return false;
    try {
        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('exec')
            || in_array('exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))), true)) {
            throw new RuntimeException('Async execution unavailable; OMO maintenance will process the queue.');
        }
        // Reuse the CLI resolver that checks the SAPI and PHP version, rejecting FPM/CGI.
        require_once dirname(__DIR__, 2) . '/includes/site_update_admin.php';
        $binary = siteUpdateAdminGetPhpBinary();
        $script = dirname(__DIR__, 2) . '/scripts/process-object-mail.php';
        $log = commonRuntimeLogPath('object-mail/worker.log');
        $directory = dirname($log);
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Cannot create worker log directory.');
        $command = escapeshellarg($binary) . ' ' . escapeshellarg($script) . ' --mail=' . $mailId
            . ' >>' . escapeshellarg($log) . ' 2>&1 < /dev/null & echo $!';
        $output = []; $exitCode = 1; exec($command, $output, $exitCode);
        if ($exitCode !== 0 || !preg_match('/^[0-9]+$/D', trim((string)end($output)))) throw new RuntimeException('Worker launch failed.');
        error_log('OMO object mail worker launched for mail ' . $mailId);
        return true;
    } catch (Throwable $error) {
        error_log('OMO object mail dispatch for mail ' . $mailId . ': ' . $error->getMessage());
        return false;
    }
}
