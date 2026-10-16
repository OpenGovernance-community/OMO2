<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/helloasso.php';

try {
    if (in_array('--help', $argv, true)) {
        echo "Usage: php scripts/helloasso-sync.php [--user=ID]\nWithout --user, process the next due cron batch.\n";
        exit(0);
    }
    $userId = null;
    foreach (array_slice($argv, 1) as $argument) {
        if (!preg_match('/^--user=([1-9][0-9]*)$/D', $argument, $match)) throw new InvalidArgumentException('Unknown argument. Use --help.');
        $userId = (int)$match[1];
    }
    if (!helloAssoIsConfigured()) throw new RuntimeException('HelloAsso configuration incomplete.');
    $count = $userId === null ? helloAssoSyncBatch() : (int)helloAssoSyncUser($userId, true);
    echo "HelloAsso: {$count} account(s) synchronized.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
