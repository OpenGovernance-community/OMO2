<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DecisionProcess;

header('Content-Type: application/json; charset=UTF-8');

$sourceLang = [
    'decisions.move_action.error.access_denied' => ['text' => 'Accès refusé.', 'context' => 'Error returned when the user cannot move a decision.'],
    'decisions.move_action.error.invalid_request' => ['text' => 'Le déplacement demandé est invalide.', 'context' => 'Error returned when the target destination is invalid.'],
    'decisions.move_action.error.not_found' => ['text' => 'La prise de décision demandée est introuvable.', 'context' => 'Error returned when the source decision cannot be found.'],
    'decisions.move_action.error.failed' => ['text' => 'Impossible de déplacer cette prise de décision.', 'context' => 'Fallback error returned when moving fails.'],
    'decisions.move_action.success' => ['text' => 'Prise de décision déplacée.', 'context' => 'Success message after moving a decision.'],
];
$lang = omoLoadTranslationBundle('omo_decisions_move_action', $sourceLang);
function omoDecisionsMoveActionT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}
function omoDecisionsMoveActionResponse($statusCode, array $payload)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDecisionsMoveActionResponse(405, ['status' => false, 'message' => omoDecisionsMoveActionT('decisions.move_action.error.invalid_request')]);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) $payload = [];
$decisionId = (int)($_GET['id'] ?? $_POST['id'] ?? ($payload['id'] ?? 0));
$targetHolonId = (int)($_POST['targetHolonId'] ?? ($payload['targetHolonId'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();

if ($currentUserId <= 0) {
    omoDecisionsMoveActionResponse(403, ['status' => false, 'message' => omoDecisionsMoveActionT('decisions.move_action.error.access_denied')]);
}
if ($decisionId <= 0 || $targetHolonId <= 0) {
    omoDecisionsMoveActionResponse(400, ['status' => false, 'message' => omoDecisionsMoveActionT('decisions.move_action.error.invalid_request')]);
}

$decision = new DecisionProcess();
if (!$decision->load($decisionId) || (int)$decision->get('IDorganization') <= 0) {
    omoDecisionsMoveActionResponse(404, ['status' => false, 'message' => omoDecisionsMoveActionT('decisions.move_action.error.not_found')]);
}

$organizationId = (int)$decision->get('IDorganization');
if (!commonCurrentUserHasOrganizationAccess($organizationId)) {
    omoDecisionsMoveActionResponse(403, ['status' => false, 'message' => omoDecisionsMoveActionT('decisions.move_action.error.access_denied')]);
}

$result = $decision->moveToHolonContext($organizationId, $targetHolonId, $currentUserId);
if (!is_array($result) || empty($result['status'])) {
    omoDecisionsMoveActionResponse(422, [
        'status' => false,
        'message' => (string)($result['text'] ?? omoDecisionsMoveActionT('decisions.move_action.error.failed')),
    ]);
}

omoDecisionsMoveActionResponse(200, [
    'status' => true,
    'message' => (string)($result['text'] ?? omoDecisionsMoveActionT('decisions.move_action.success')),
    'decisionId' => $decisionId,
    'previousHolonId' => (int)($result['previousHolonId'] ?? 0),
    'targetHolonId' => (int)($result['targetHolonId'] ?? 0),
]);
