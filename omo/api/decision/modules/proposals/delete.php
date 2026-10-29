<?php
require_once __DIR__ . '/../../ui_translations.php';
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once dirname(__DIR__) . '/common.php';

use dbObject\DecisionProposal;

header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    omoDecisionModuleJsonResponse(405, [
        'status' => false,
        'message' => omoDecisionUiT('methode_non_autorisee'),
    ]);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    omoDecisionModuleJsonResponse((int)($context['code'] ?? 403), [
        'status' => false,
        'message' => omoDecisionUiT('acces_refuse'),
    ]);
}

$userId = omoDecisionGetContextAccountUserId($context);
$participantId = omoDecisionGetContextParticipantId($context);
if ($userId <= 0 && $participantId <= 0) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'reason' => 'participant_required',
        'message' => omoDecisionUiT('un_participant_valide_est_necessaire_pour_supprimer_une_proposition'),
    ]);
}

$proposal = omoDecisionLoadProposalForContext((int)($_POST['proposal_id'] ?? 0), $context, true);
if (!$proposal instanceof DecisionProposal) {
    omoDecisionModuleJsonResponse(404, [
        'status' => false,
        'message' => omoDecisionUiT('proposition_introuvable'),
    ]);
}

if (!omoDecisionCanEditProposalFromPublicInterface($proposal, $context)) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'message' => omoDecisionUiT('vous_ne_pouvez_supprimer_que_vos_propres_propositions_avant_le_debut_du_scrutin'),
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
    'message' => (string)($result['message'] ?? omoDecisionUiT('proposition_supprimee')),
    'proposalId' => (int)$proposal->getId(),
]);

?>
