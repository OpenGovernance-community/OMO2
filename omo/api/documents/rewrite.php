<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/openai_text.php';

header('Content-Type: application/json; charset=UTF-8');

$sourceLang = [
    'documents.rewrite.error.access_denied' => [
        'text' => 'Accès refusé.',
        'context' => 'Error returned when the current user cannot access the rewrite endpoint.',
    ],
    'documents.rewrite.error.context_denied' => [
        'text' => 'Accès refusé à ce contexte.',
        'context' => 'Error returned when the current user cannot access the requested holon context.',
    ],
    'documents.rewrite.error.invalid_text' => [
        'text' => 'Aucun texte valide n’a été reçu.',
        'context' => 'Error returned when the rewrite request does not contain the required text payload.',
    ],
    'documents.rewrite.error.failed' => [
        'text' => 'Impossible de réécrire ce texte.',
        'context' => 'Fallback error returned when the OpenAI rewrite request fails.',
    ],
];

$lang = omoLoadTranslationBundle('omo_documents_rewrite', $sourceLang);

function omoDocumentsRewriteT($key, array $replace = [])
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
        'message' => omoDocumentsRewriteT('documents.rewrite.error.access_denied'),
    ));
    exit;
}

if ($holonId > 0) {
    $holon = new \dbObject\Holon();
    if (!$holon->load($holonId) || !$holon->canView()) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'message' => omoDocumentsRewriteT('documents.rewrite.error.context_denied'),
        ));
        exit;
    }
}

$selectedText = trim((string)($_POST['selected_text'] ?? ''));
$fullText = trim((string)($_POST['full_text'] ?? ''));
$title = trim((string)($_POST['title'] ?? ''));
$rewriteFullDocument = isset($_POST['rewrite_full_document']) && (int)$_POST['rewrite_full_document'] === 1;

if ($selectedText === '' || $fullText === '') {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsRewriteT('documents.rewrite.error.invalid_text'),
    ));
    exit;
}

$result = commonOpenAiRewriteSelectedDocumentText($selectedText, $fullText, array(
    'title' => $title,
    'rewrite_full_document' => $rewriteFullDocument,
));

if (empty($result['status'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => trim((string)($result['message'] ?? omoDocumentsRewriteT('documents.rewrite.error.failed'))),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'text' => trim((string)($result['text'] ?? '')),
    'model' => trim((string)($result['model'] ?? '')),
));
