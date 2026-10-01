<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once dirname(__DIR__) . '/common.php';

use dbObject\DecisionProposal;

header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    omoDecisionModuleJsonResponse(405, [
        'status' => false,
        'message' => 'Méthode non autorisée.',
    ]);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    omoDecisionModuleJsonResponse((int)($context['code'] ?? 403), [
        'status' => false,
        'message' => 'Accès refusé.',
    ]);
}

$userId = omoDecisionGetContextAccountUserId($context);
$participantId = omoDecisionGetContextParticipantId($context);
if ($userId <= 0 && $participantId <= 0) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'reason' => 'participant_required',
        'message' => 'Un participant valide est nécessaire pour supprimer une proposition.',
    ]);
}

$proposal = omoDecisionLoadProposalForContext((int)($_POST['proposal_id'] ?? 0), $context, true);
if (!$proposal instanceof DecisionProposal) {
    omoDecisionModuleJsonResponse(404, [
        'status' => false,
        'message' => 'Proposition introuvable.',
    ]);
}

if (!omoDecisionCanEditProposalFromPublicInterface($proposal, $context)) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'message' => 'Vous ne pouvez supprimer que vos propres propositions avant le début du scrutin.',
    ]);
}

$result = $proposal->archiveByAuthor($userId, $participantId);
if (empty($result['status'])) {
    $statusCode = ($result['reason'] ?? '') === 'forbidden' ? 403 : 422;
    if (($result['reason'] ?? '') === 'not_found') {
        $statusCode = 404;
    } elseif (($result['reason'] ?? '') === 'save_failed') {
        $statusCode = 500;
    }
    omoDecisionModuleJsonResponse($statusCode, $result);
}

omoDecisionModuleJsonResponse(200, [
    'status' => true,
    'message' => (string)($result['message'] ?? 'Proposition supprimée.'),
    'proposalId' => (int)$proposal->getId(),
]);

?>
