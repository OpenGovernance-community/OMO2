<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/web_push.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$messageId = 0;
foreach (array_slice($argv ?? [], 1) as $argument) {
    if (strpos($argument, '--message=') === 0) {
        $messageId = (int)substr($argument, strlen('--message='));
    }
}

if ($messageId <= 0) {
    fwrite(STDERR, "A --message identifier is required.\n");
    exit(1);
}

try {
    $result = notificationPushSendDecisionChatMessage($messageId);
    notificationPushWriteWorkerLog(
        'Decision chat push processed. message=' . $messageId
        . ' sent=' . (int)($result['sent'] ?? 0)
        . ' failed=' . (int)($result['failed'] ?? 0)
        . ' reason=' . (string)($result['reason'] ?? '')
    );
    fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(!empty($result['status']) ? 0 : 1);
} catch (\Throwable $exception) {
    notificationPushWriteWorkerLog('Decision chat push worker failed. message=' . $messageId . ' error=' . $exception->getMessage());
    fwrite(STDERR, "Decision chat push worker failed.\n");
    exit(1);
}
?>
