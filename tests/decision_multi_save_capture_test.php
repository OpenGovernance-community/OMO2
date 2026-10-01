<?php
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';

function assertDecisionMultiSaveCapture(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$GLOBALS['omoDecisionCaptureModuleResponse'] = true;
$captured = null;
try {
    omoDecisionModuleJsonResponse(422, [
        'status' => false,
        'message' => 'Question invalide.',
    ]);
} catch (OmoDecisionModuleCapturedResponse $response) {
    $captured = $response;
}
unset($GLOBALS['omoDecisionCaptureModuleResponse']);

assertDecisionMultiSaveCapture(
    $captured instanceof OmoDecisionModuleCapturedResponse,
    'The module response must be captured without exiting the batch save.'
);
assertDecisionMultiSaveCapture($captured->statusCode === 422, 'The captured HTTP status must be preserved.');
assertDecisionMultiSaveCapture(empty($captured->payload['status']), 'The captured response payload must be preserved.');

echo "Decision multi-save capture tests passed.\n";
