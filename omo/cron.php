<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/omo_fake_cron.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$cronRequestStartedAtNs = hrtime(true);

$configuredToken = trim((string)commonReadRuntimeEnvValue('OMO_CRON_TOKEN', ''));
$providedToken = trim((string)($_GET['token'] ?? ($_SERVER['HTTP_X_OMO_CRON_TOKEN'] ?? '')));
if ($configuredToken === '' || $providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
    omoCronLogRejectedInvocation(
        'server_cron_http',
        $configuredToken === '' ? 'not_configured' : 'unauthorized',
        $cronRequestStartedAtNs
    );
    http_response_code($configuredToken === '' ? 503 : 403);
    echo json_encode(['status' => false, 'message' => 'Cron access unavailable.'], JSON_UNESCAPED_SLASHES);
    exit;
}

if (function_exists('ignore_user_abort')) {
    @ignore_user_abort(true);
}
if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}

echo json_encode(array_merge(['status' => true], omo_run_fake_cron_maintenance(200, true, 'server_cron_http')), JSON_UNESCAPED_SLASHES);
