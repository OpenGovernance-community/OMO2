<?php
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
        'message' => 'Methode non autorisee.',
    ]);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    omoDecisionActionJsonResponse((int)($context['code'] ?? 400), [
        'status' => false,
        'message' => 'Contexte de prise de decision invalide.',
    ]);
}

$decision = $context['decision'] ?? null;
if (!($decision instanceof DecisionProcess) || (int)$decision->getId() <= 0) {
    omoDecisionActionJsonResponse(404, [
        'status' => false,
        'message' => 'Prise de decision introuvable.',
    ]);
}

if (empty($context['canManage'])) {
    omoDecisionActionJsonResponse(403, [
        'status' => false,
        'message' => 'Acces refuse a cette prise de decision.',
    ]);
}

$requestedAction = trim((string)($_POST['decision_action'] ?? ''));
if ($requestedAction === '') {
    $requestedAction = 'close';
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
        'message' => (string)($result['message'] ?? 'Impossible de mettre a jour cette prise de decision.'),
        'requestedAction' => $requestedAction,
        'resolvedAction' => is_array($result) ? (string)($result['action'] ?? '') : '',
    ]);
}

omoDecisionActionJsonResponse(200, [
    'status' => true,
    'message' => (string)($result['message'] ?? 'Prise de decision mise a jour.'),
    'requestedAction' => $requestedAction,
    'resolvedAction' => (string)($result['action'] ?? ''),
    'decisionId' => (int)$decision->getId(),
]);
