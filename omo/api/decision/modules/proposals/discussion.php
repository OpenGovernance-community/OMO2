<?php
require_once __DIR__ . '/../../ui_translations.php';
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/context.php';
require_once dirname(__DIR__) . '/common.php';
require_once dirname(__DIR__, 5) . '/common/notification_center.php';
require_once dirname(__DIR__, 5) . '/common/chat/reactions.php';

use dbObject\ChatMessage;
use dbObject\ChatThread;
use dbObject\DecisionProposal;
use dbObject\User;

header('Content-Type: application/json; charset=UTF-8');

function omoDecisionProposalDiscussionRespond($statusCode, array $payload)
{
    omoDecisionModuleJsonResponse((int)$statusCode, $payload);
}

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($requestMethod, ['GET', 'POST'], true)) {
    omoDecisionProposalDiscussionRespond(405, [
        'status' => false,
        'message' => omoDecisionUiT('methode_non_autorisee'),
    ]);
}

$input = $requestMethod === 'POST' ? $_POST : $_GET;
$context = omoDecisionResolveEditorContext($input);
if (empty($context['status'])) {
    omoDecisionProposalDiscussionRespond((int)($context['code'] ?? 403), [
        'status' => false,
        'message' => omoDecisionUiT('acces_refuse'),
    ]);
}

$viewerUserId = omoDecisionGetContextAccountUserId($context);
$viewerParticipantId = omoDecisionGetContextParticipantId($context);
if ($viewerUserId <= 0 && $viewerParticipantId <= 0) {
    omoDecisionProposalDiscussionRespond(403, [
        'status' => false,
        'reason' => 'participant_required',
        'message' => omoDecisionUiT('un_participant_valide_est_necessaire_pour_acceder_a_cette_discussion'),
    ]);
}

$proposal = omoDecisionLoadProposalForContext((int)($input['proposal_id'] ?? 0), $context, true);
if (!$proposal instanceof DecisionProposal) {
    omoDecisionProposalDiscussionRespond(404, [
        'status' => false,
        'message' => omoDecisionUiT('proposition_introuvable'),
    ]);
}
if (!omoDecisionCanAccessProposalDiscussion($proposal, $context)) {
    omoDecisionProposalDiscussionRespond(403, [
        'status' => false,
        'message' => omoDecisionUiT('acces_refuse'),
    ]);
}

$thread = $proposal->getChatThread(false);
$reactionActor = \dbObject\ChatMessageReaction::actorKey($viewerUserId, $viewerParticipantId);
commonChatHandleReaction($thread, true, $reactionActor);
$decision = $proposal->getDecisionProcess();
$decisionAdministratorUserId = $decision instanceof \dbObject\DecisionProcess
    ? (int)$decision->get('IDuser')
    : 0;
$proposalAuthorUserId = (int)$proposal->getAuthorUserId();
$discussionIsAnonymous = $proposal->isAnonymous();
$anonymousPreferenceExpiresAt = '';
if ($decision instanceof \dbObject\DecisionProcess) {
    if ($decision->hasEvaluationStarted()) {
        $anonymousPreferenceExpiresAt = (new \DateTimeImmutable('now'))->format(DATE_ATOM);
    } else {
        $anonymousPreferenceExpiresAtDate = null;
        foreach (['consultation_end_at', 'evaluation_start_at'] as $dateField) {
            $dateValue = \dbObject\DecisionProcess::normalizeDateTimeValue($decision->get($dateField));
            if ($dateValue instanceof \DateTimeInterface && (
                !($anonymousPreferenceExpiresAtDate instanceof \DateTimeInterface)
                || $dateValue < $anonymousPreferenceExpiresAtDate
            )) {
                $anonymousPreferenceExpiresAtDate = $dateValue;
            }
        }
        if ($anonymousPreferenceExpiresAtDate instanceof \DateTimeInterface) {
            $anonymousPreferenceExpiresAt = $anonymousPreferenceExpiresAtDate->format(DATE_ATOM);
        }
    }
}
$afterMessageId = $requestMethod === 'GET' ? max(0, (int)($_GET['after_id'] ?? 0)) : 0;
if ($requestMethod === 'POST') {
    $content = trim((string)($_POST['content'] ?? ''));
    if ($content === '' || mb_strlen($content, 'UTF-8') > 4000) {
        omoDecisionProposalDiscussionRespond(422, [
            'status' => false,
            'message' => omoDecisionUiT('le_message_doit_contenir_entre_1_et_4000_caracteres'),
        ]);
    }

    $thread = $proposal->getChatThread(true, $viewerUserId);
    if (!$thread) {
        omoDecisionProposalDiscussionRespond(500, [
            'status' => false,
            'message' => omoDecisionUiT('la_discussion_ne_peut_pas_etre_creee_pour_le_moment'),
        ]);
    }

    $anonymousByAuthor = !$discussionIsAnonymous && !empty($_POST['is_anonymous']);
    $message = ChatMessage::createUserMessage(
        $thread,
        $viewerUserId,
        $content,
        $discussionIsAnonymous || $anonymousByAuthor,
        $anonymousByAuthor,
        $viewerParticipantId
    );
    if (!$message instanceof ChatMessage) {
        omoDecisionProposalDiscussionRespond(500, [
            'status' => false,
            'message' => omoDecisionUiT('le_message_ne_peut_pas_etre_envoye_pour_le_moment'),
        ]);
    }

    try {
        notificationCenterDispatchDecisionChatMessage($message);
    } catch (\Throwable $exception) {
        error_log('decision_chat_notification_failed: ' . $exception->getMessage());
    }
}

$messages = [];
$discussionMessageCount = 0;
if ($thread) {
    foreach ($thread->getMessages(300, $afterMessageId) as $message) {
        if ($message instanceof ChatMessage) {
            $messageData = $message->toClientArray($viewerUserId, $viewerParticipantId);
            $messageAuthorUserId = (int)($messageData['authorUserId'] ?? 0);
            $messageAuthorParticipantId = (int)($messageData['authorParticipantId'] ?? 0);
            $messageIsAnonymous = (string)($messageData['type'] ?? '') === ChatMessage::TYPE_USER
                && $message->isAnonymous();
            $hideMessageAuthor = $messageIsAnonymous
                && ($messageAuthorUserId !== $decisionAdministratorUserId || $message->isAnonymousByAuthor());
            if ($hideMessageAuthor && $decision instanceof \dbObject\DecisionProcess) {
                $anonymousName = $messageAuthorParticipantId > 0
                    ? $decision->getAnonymousPseudonymForParticipant($messageAuthorParticipantId)
                    : $decision->getAnonymousPseudonymForUser($messageAuthorUserId);
                $messageData['authorUserId'] = 0;
                $messageData['authorParticipantId'] = 0;
                $messageData['authorName'] = $anonymousName;
                $messageData['photoUrl'] = '';
                $messageData['initials'] = User::buildInitials($anonymousName);
            }
            $messageData['authorRoles'] = [];
            if (!$hideMessageAuthor && $messageAuthorUserId > 0 && $messageAuthorUserId === $decisionAdministratorUserId) {
                $messageData['authorRoles'][] = [
                    'key' => 'decision-administrator',
                    'label' => omoDecisionUiT('administrateur_du_scrutin'),
                ];
            }
            if (
                !$hideMessageAuthor
                && (
                    ($messageAuthorUserId > 0 && $messageAuthorUserId === $proposalAuthorUserId)
                    || ($messageAuthorParticipantId > 0 && $messageAuthorParticipantId === $proposal->getAuthorParticipantId())
                )
            ) {
                $messageData['authorRoles'][] = [
                    'key' => 'proposal-author',
                    'label' => omoDecisionUiT('auteur_de_la_proposition'),
                ];
            }
            $messages[] = $messageData;
        }
    }
    $discussionSummary = ChatThread::getSubjectDiscussionSummaries(
        (int)$thread->get('IDorganization'),
        ChatThread::SUBJECT_DECISION_PROPOSAL,
        [(int)$proposal->getId()],
        $viewerUserId,
        $viewerParticipantId
    );
    $discussionMessageCount = (int)($discussionSummary[(int)$proposal->getId()]['total_messages'] ?? 0);
}

omoDecisionProposalDiscussionRespond(200, array_merge(commonChatReactionPayload($thread, $messages, $input, $reactionActor, true), [
    'status' => true,
    'proposal' => [
        'id' => (int)$proposal->getId(),
        'title' => trim((string)$proposal->get('title')),
        'description' => trim((string)$proposal->get('description')),
        'infoUrl' => trim((string)$proposal->get('info_url')),
    ],
    'discussionIsAnonymous' => $discussionIsAnonymous,
    'viewerUserId' => $viewerUserId,
    'viewerParticipantId' => $viewerParticipantId,
    'anonymousPreferenceExpiresAt' => $anonymousPreferenceExpiresAt,
    'discussionMessageCount' => $discussionMessageCount,
    'messages' => $messages,
    'lastMessageId' => count($messages) > 0
        ? (int)$messages[count($messages) - 1]['id']
        : $afterMessageId,
]));

?>
