<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$mailId = 0;
foreach (array_slice($argv, 1) as $argument) if (preg_match('/^--mail=([1-9][0-9]*)$/D', $argument, $match)) $mailId = (int)$match[1];
if ($mailId <= 0) { fwrite(STDERR, "Usage: php scripts/process-object-mail.php --mail=<id>\n"); exit(1); }
$count = 0; $deadline = microtime(true) + 300;
try {
    do {
        $batch = \dbObject\ObjectMail::processBatch(20, $mailId);
        $count += $batch;
    } while ($batch > 0 && $count < \dbObject\ObjectMail::MAX_RECIPIENTS && microtime(true) < $deadline);
    fwrite(STDOUT, json_encode(['mail_id' => $mailId, 'processed' => $count], JSON_THROW_ON_ERROR) . "\n");
} catch (Throwable $error) { error_log('OMO object mail worker failed for mail ' . $mailId); exit(1); }
