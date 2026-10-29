<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';

use dbObject\DecisionProcess;

header('Content-Type: application/json; charset=UTF-8');

function omoDecisionActionJsonResponse($statusCode, array $payload)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDecisionActionJsonResponse(405, [
        'status' => false,
        'message' => omoDecisionUiT('methode_non_autorisee'),
    ]);
}

$requestedAction = trim((string)($_POST['decision_action'] ?? 'close')) ?: 'close';
$request = $_POST;
$request['intent'] = 'view';
$context = omoDecisionResolveEditorContext($request);
if (empty($context['status'])) {
    omoDecisionActionJsonResponse((int)($context['code'] ?? 400), [
        'status' => false,
        'message' => omoDecisionUiT('contexte_de_prise_de_decision_invalide'),
    ]);
}

$decision = $context['decision'] ?? null;
if (!($decision instanceof DecisionProcess) || (int)$decision->getId() <= 0) {
    omoDecisionActionJsonResponse(404, [
        'status' => false,
        'message' => omoDecisionUiT('prise_de_decision_introuvable'),
    ]);
}

if (empty($context['canDelete'])) {
    omoDecisionActionJsonResponse(403, [
        'status' => false,
        'message' => omoDecisionUiT('acces_refuse_a_cette_prise_de_decision'),
    ]);
}

if ($requestedAction === 'archive') {
    $result = $decision->archiveForManager();
} elseif ($requestedAction === 'delete') {
    $result = $decision->deleteWithRelations();
} else {
    $result = $decision->closeForManager();
}
if (!is_array($result) || empty($result['status'])) {
    omoDecisionActionJsonResponse(422, [
        'status' => false,
        'message' => (string)($result['message'] ?? omoDecisionUiT('impossible_de_mettre_a_jour_cette_prise_de_decision')),
        'requestedAction' => $requestedAction,
        'resolvedAction' => is_array($result) ? (string)($result['action'] ?? '') : '',
    ]);
}

omoDecisionActionJsonResponse(200, [
    'status' => true,
    'message' => (string)($result['message'] ?? omoDecisionUiT('prise_de_decision_mise_a_jour')),
    'requestedAction' => $requestedAction,
    'resolvedAction' => (string)($result['action'] ?? ''),
    'decisionId' => (int)$decision->getId(),
]);
