<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once dirname(__DIR__) . '/common.php';

use dbObject\DecisionProposal;

header('Content-Type: application/json; charset=UTF-8');

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($requestMethod, ['GET', 'POST'], true)) {
    omoDecisionModuleJsonResponse(405, [
        'status' => false,
        'message' => 'Méthode non autorisée.',
    ]);
}

$input = $requestMethod === 'POST' ? $_POST : $_GET;
$context = omoDecisionResolveEditorContext($input);
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
        'message' => 'Un participant valide est nécessaire pour modifier une proposition.',
    ]);
}

$proposal = omoDecisionLoadProposalForContext((int)($input['proposal_id'] ?? 0), $context, true);
if (!$proposal instanceof DecisionProposal) {
    omoDecisionModuleJsonResponse(404, [
        'status' => false,
        'message' => 'Proposition introuvable.',
    ]);
}
if (!omoDecisionCanEditProposalFromPublicInterface($proposal, $context)) {
    omoDecisionModuleJsonResponse(403, [
        'status' => false,
        'message' => 'Vous ne pouvez pas modifier cette proposition.',
    ]);
}

if ($requestMethod === 'GET') {
    omoDecisionModuleJsonResponse(200, [
        'status' => true,
        'proposal' => [
            'id' => (int)$proposal->getId(),
            'title' => trim((string)$proposal->get('title')),
            'description' => trim((string)$proposal->get('description')),
            'infoUrl' => trim((string)$proposal->get('info_url')),
            'updatedLabel' => omoDecisionFormatProposalDateLabel($proposal->get('updated_at')),
        ],
    ]);
}

$result = $proposal->updateContentByAuthor(
    $userId,
    $_POST['title'] ?? '',
    $_POST['description'] ?? '',
    $_POST['info_url'] ?? '',
    $participantId
);
if (empty($result['status'])) {
    $statusCode = ($result['reason'] ?? '') === 'forbidden' ? 403 : 422;
    if (($result['reason'] ?? '') === 'save_failed') {
        $statusCode = 500;
    }
    omoDecisionModuleJsonResponse($statusCode, $result);
}

$discussionSummary = omoDecisionGetProposalDiscussionSummary($proposal, $context);
$discussionMessageCount = (int)($discussionSummary['total_messages'] ?? 0);

omoDecisionModuleJsonResponse(200, [
    'status' => true,
    'changed' => !empty($result['changed']),
    'message' => (string)($result['message'] ?? 'Proposition modifiée.'),
    'proposal' => [
        'id' => (int)$proposal->getId(),
        'title' => trim((string)$proposal->get('title')),
        'description' => trim((string)$proposal->get('description')),
        'infoUrl' => trim((string)$proposal->get('info_url')),
        'updatedLabel' => omoDecisionFormatProposalDateLabel($proposal->get('updated_at')),
        'discussionMessageCount' => $discussionMessageCount,
    ],
]);

?>
