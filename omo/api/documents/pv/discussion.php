<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';

use dbObject\ChatMessage;
use dbObject\ChatThread;

header('Content-Type: application/json; charset=UTF-8');

function omoDocumentsPvDiscussionRespond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$sourceLang = omoDocumentsPvEditorSourceLang();
$lang = omoLoadTranslationBundle('omo_documents_pv_editor', $sourceLang);
$translate = static function (string $key) use (&$lang, &$sourceLang): string {
    return t($key, [], $lang, $sourceLang);
};
$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$input = $requestMethod === 'POST' ? $_POST : $_GET;
$organizationId = (int)($input['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$contextHolonId = max(0, (int)($input['cid'] ?? 0));
$documentId = (int)($input['document_id'] ?? 0);
$pointId = (int)($input['point_id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$publicParticipationLink = function_exists('commonGetPublicPvParticipationLink')
    ? commonGetPublicPvParticipationLink()
    : null;
$isPublicParticipation = $publicParticipationLink instanceof \dbObject\DocumentShareLink
    && $publicParticipationLink->allowsPvContribution();
$publicParticipationUserId = $isPublicParticipation
    ? commonPvParticipationRecipientOrganizationUserId($publicParticipationLink, $organizationId)
    : 0;
$viewerUserId = $isPublicParticipation ? $publicParticipationUserId : $userId;
$viewerDocumentShareLinkId = $isPublicParticipation ? (int)$publicParticipationLink->getId() : 0;
$document = new \dbObject\Document();
$point = new \dbObject\DocumentPvPoint();

if (
    $organizationId <= 0
    || $documentId <= 0
    || $pointId <= 0
    || (!$isPublicParticipation && $userId <= 0)
    || !$document->load($documentId)
    || !$document->isPvDocument()
    || !$point->load($pointId)
    || $point->isGroup()
    || (int)$point->get('IDdocument') !== $documentId
    || (int)$document->get('IDorganization') !== $organizationId
    || ($isPublicParticipation && (int)$publicParticipationLink->get('IDdocument') !== $documentId)
) {
    omoDocumentsPvDiscussionRespond(['status' => false, 'message' => $translate('documents.pv_editor.error.forbidden')], 403);
}

$canAccess = $isPublicParticipation
    ? $document->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW
    : ($document->isPvValidated()
    ? $document->canUserPassPvMeetingVisibilityGate($viewerUserId, $organizationId)
        && (
            $document->canViewInOrganizationContext($organizationId, $contextHolonId > 0 ? $contextHolonId : null)
            || $document->canViewDirectlyInOrganization($organizationId)
        )
    : $document->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW
        && $document->canUserAccessPvReview($viewerUserId, $organizationId));
if (!$canAccess) {
    omoDocumentsPvDiscussionRespond(['status' => false, 'message' => $translate('documents.pv_editor.error.forbidden')], 403);
}

if (!$document->canUserViewPvPoint($point, $viewerUserId)) {
    omoDocumentsPvDiscussionRespond(['status' => false, 'message' => $translate('documents.pv_editor.error.forbidden')], 403);
}

$thread = ChatThread::findBySubject($organizationId, ChatThread::SUBJECT_DOCUMENT_PV_POINT, $pointId);
$canPost = $document->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW
    && !$document->isPvValidated()
    && ($isPublicParticipation || $document->canUserAccessPvReview($viewerUserId, $organizationId));
if ($requestMethod === 'POST') {
    if (!$canPost) {
        omoDocumentsPvDiscussionRespond(['status' => false, 'message' => $translate('documents.pv_editor.chat.readonly')], 403);
    }
    $content = trim((string)($_POST['content'] ?? ''));
    if ($content === '' || mb_strlen($content, 'UTF-8') > 4000) {
        omoDocumentsPvDiscussionRespond(['status' => false, 'message' => $translate('documents.pv_editor.chat.invalid_message')], 422);
    }
    $thread = ChatThread::getOrCreateForSubject($organizationId, ChatThread::SUBJECT_DOCUMENT_PV_POINT, $pointId, $viewerUserId, trim((string)$point->get('title')));
    $message = $thread instanceof ChatThread
        ? ChatMessage::createUserMessage($thread, $viewerUserId, $content, false, false, 0, $viewerDocumentShareLinkId)
        : null;
    if (!$message instanceof ChatMessage) {
        omoDocumentsPvDiscussionRespond(['status' => false, 'message' => $translate('documents.pv_editor.chat.send_error')], 500);
    }
}

$afterId = $requestMethod === 'GET' ? max(0, (int)($_GET['after_id'] ?? 0)) : 0;
$messages = [];
if ($thread instanceof ChatThread) {
    foreach ($thread->getMessages(300, $afterId) as $message) {
        if ($message instanceof ChatMessage) {
            $messages[] = $message->toClientArray($viewerUserId, 0, $viewerDocumentShareLinkId);
        }
    }
}
$discussionSummary = ChatThread::getSubjectDiscussionSummaries(
    $organizationId,
    ChatThread::SUBJECT_DOCUMENT_PV_POINT,
    [$pointId],
    $viewerUserId,
    0,
    $viewerDocumentShareLinkId
);
$messageCount = max(0, (int)($discussionSummary[$pointId]['total_messages'] ?? 0));

omoDocumentsPvDiscussionRespond([
    'status' => true,
    'title' => trim((string)$point->get('title')),
    'canPost' => $canPost,
    'messages' => $messages,
    'messageCount' => $messageCount,
    'lastMessageId' => count($messages) > 0 ? (int)$messages[count($messages) - 1]['id'] : $afterId,
]);
