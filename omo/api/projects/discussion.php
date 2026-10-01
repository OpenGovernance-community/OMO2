<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ChatMessage;
use dbObject\ChatThread;
use dbObject\Project;

header('Content-Type: application/json; charset=UTF-8');

function omoProjectsDiscussionRespond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($requestMethod, ['GET', 'POST'], true)) {
    omoProjectsDiscussionRespond([
        'status' => false,
        'message' => omoProjectsT('projects.error.method'),
    ], 405);
}

$input = $requestMethod === 'POST' ? $_POST : $_GET;
$organizationId = (int)($_SESSION['currentOrganization'] ?? ($input['oid'] ?? 0));
$contextHolonId = max(0, (int)($input['cid'] ?? 0));
$projectId = max(0, (int)($input['project_id'] ?? 0));
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$context = omoProjectsResolveContext($organizationId, $contextHolonId);
$project = new Project();

if (
    $organizationId <= 0
    || $projectId <= 0
    || $currentUserId <= 0
    || empty($context['status'])
    || !$project->load($projectId)
    || (int)$project->get('IDorganization') !== $organizationId
    || !omoProjectsCanViewProject($project, $context)
) {
    omoProjectsDiscussionRespond([
        'status' => false,
        'message' => omoProjectsT('projects.chat.error.forbidden'),
    ], 403);
}

$thread = ChatThread::findBySubject($organizationId, ChatThread::SUBJECT_PROJECT, $projectId);
$canPost = (int)$project->get('active') === 1;
if ($requestMethod === 'POST') {
    if (!$canPost) {
        omoProjectsDiscussionRespond([
            'status' => false,
            'message' => omoProjectsT('projects.chat.error.readonly'),
        ], 403);
    }

    $content = trim((string)($_POST['content'] ?? ''));
    if ($content === '' || mb_strlen($content, 'UTF-8') > 4000) {
        omoProjectsDiscussionRespond([
            'status' => false,
            'message' => omoProjectsT('projects.chat.error.invalid_message'),
        ], 422);
    }

    $thread = ChatThread::getOrCreateForSubject(
        $organizationId,
        ChatThread::SUBJECT_PROJECT,
        $projectId,
        $currentUserId,
        trim((string)$project->get('title'))
    );
    $message = $thread instanceof ChatThread
        ? ChatMessage::createUserMessage($thread, $currentUserId, $content)
        : null;
    if (!$message instanceof ChatMessage) {
        omoProjectsDiscussionRespond([
            'status' => false,
            'message' => omoProjectsT('projects.chat.error.send'),
        ], 500);
    }
    try {
        require_once dirname(__DIR__, 3) . '/common/notification_center.php';
        notificationCenterDispatchProjectChatMessage($message);
    } catch (\Throwable $exception) {
        error_log('project_chat_notification_failed: ' . $exception->getMessage());
    }
}

$afterId = $requestMethod === 'GET' ? max(0, (int)($_GET['after_id'] ?? 0)) : 0;
$messages = [];
if ($thread instanceof ChatThread) {
    foreach ($thread->getMessages(300, $afterId) as $message) {
        if ($message instanceof ChatMessage) {
            $messages[] = $message->toClientArray($currentUserId);
        }
    }
}
$discussionSummary = ChatThread::getSubjectDiscussionSummaries(
    $organizationId,
    ChatThread::SUBJECT_PROJECT,
    [$projectId],
    $currentUserId
);
$discussionSummary = $discussionSummary[$projectId] ?? [];
$messageCount = max(0, (int)($discussionSummary['total_messages'] ?? 0));
$messagesSinceViewer = (int)($discussionSummary['last_viewer_message_id'] ?? 0) > 0
    ? max(0, (int)($discussionSummary['messages_since_viewer'] ?? 0))
    : null;

omoProjectsDiscussionRespond([
    'status' => true,
    'title' => trim((string)$project->get('title')),
    'canPost' => $canPost,
    'messages' => $messages,
    'messageCount' => $messageCount,
    'messagesSinceViewer' => $messagesSinceViewer,
    'lastMessageId' => count($messages) > 0
        ? (int)$messages[count($messages) - 1]['id']
        : $afterId,
]);
