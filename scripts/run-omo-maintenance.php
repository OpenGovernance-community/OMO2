<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/omo_fake_cron.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$result = omo_run_fake_cron_maintenance(200, true, 'server_cron_cli');
fwrite(STDOUT, json_encode(array_merge(['status' => true], $result), JSON_UNESCAPED_SLASHES) . PHP_EOL);
