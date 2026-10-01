<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/web_push.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$values = webPushGenerateVapidEnvironmentValues();
if (!is_array($values)) {
    fwrite(STDERR, "Unable to generate Web Push VAPID keys.\n");
    exit(1);
}

foreach ($values as $name => $value) {
    fwrite(STDOUT, $name . '=' . $value . PHP_EOL);
}
fwrite(STDOUT, "WEB_PUSH_VAPID_SUBJECT=mailto:admin@example.org\n");
?>
