<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/patreon.php';
require_once dirname(__DIR__, 4) . '/common/openai_text.php';

header('Content-Type: application/json; charset=UTF-8');

$sourceLang = [
    'documents.summarize.error.contributor_required' => [
        'text' => 'Les fonctions IA sont reservees aux contributeurs Patreon actifs.',
        'context' => 'Error returned when the current user does not have access to AI features.',
    ],
    'documents.summarize.error.access_denied' => [
        'text' => 'Accès refusé.',
        'context' => 'Error returned when the current user cannot access the summarization endpoint.',
    ],
    'documents.summarize.error.context_denied' => [
        'text' => 'Accès refusé à ce contexte.',
        'context' => 'Error returned when the current user cannot access the requested holon context.',
    ],
    'documents.summarize.error.invalid_text' => [
        'text' => 'Aucun texte valide n’a été reçu.',
        'context' => 'Error returned when the summarization request does not contain the required text payload.',
    ],
    'documents.summarize.error.failed' => [
        'text' => 'Impossible de résumer ce texte.',
        'context' => 'Fallback error returned when the OpenAI summarization request fails.',
    ],
];

$lang = omoLoadTranslationBundle('omo_documents_summarize', $sourceLang);

function omoDocumentsSummarizeT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_POST['cid']) ? (int)($_POST['cid']) : 0;
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsSummarizeT('documents.summarize.error.access_denied'),
    ));
    exit;
}

if (!patreonUserCanUseAi($currentUserId)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsSummarizeT('documents.summarize.error.contributor_required'),
    ));
    exit;
}

if ($holonId > 0) {
    $holon = new \dbObject\Holon();
    if (!$holon->load($holonId) || !$holon->canView()) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'message' => omoDocumentsSummarizeT('documents.summarize.error.context_denied'),
        ));
        exit;
    }
}

$selectedText = trim((string)($_POST['selected_text'] ?? ''));
$fullText = trim((string)($_POST['full_text'] ?? ''));
$title = trim((string)($_POST['title'] ?? ''));

if ($selectedText === '' || $fullText === '') {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsSummarizeT('documents.summarize.error.invalid_text'),
    ));
    exit;
}

$result = commonOpenAiSummarizeSelectedDocumentText($selectedText, $fullText, array(
    'title' => $title,
));

if (empty($result['status'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => trim((string)($result['message'] ?? omoDocumentsSummarizeT('documents.summarize.error.failed'))),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'text' => trim((string)($result['text'] ?? '')),
    'model' => trim((string)($result['model'] ?? '')),
));
