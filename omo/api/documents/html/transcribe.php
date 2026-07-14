<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/openai_audio.php';

header('Content-Type: application/json; charset=UTF-8');

$sourceLang = [
    'documents.transcribe.error.access_denied' => [
        'text' => 'Accès refusé.',
        'context' => 'Error returned when the current user cannot access the transcription endpoint.',
    ],
    'documents.transcribe.error.context_denied' => [
        'text' => 'Accès refusé à ce contexte.',
        'context' => 'Error returned when the current user cannot access the requested holon context.',
    ],
    'documents.transcribe.error.missing_audio' => [
        'text' => 'Aucun enregistrement audio n’a été reçu.',
        'context' => 'Error returned when the transcription request does not include an uploaded audio file.',
    ],
    'documents.transcribe.error.failed' => [
        'text' => 'Impossible de transcrire cet enregistrement.',
        'context' => 'Fallback error returned when the OpenAI transcription request fails.',
    ],
];

$lang = omoLoadTranslationBundle('omo_documents_transcribe', $sourceLang);

function omoDocumentsTranscribeT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsTranscribeT('documents.transcribe.error.access_denied'),
    ));
    exit;
}

if ($holonId > 0) {
    $holon = new \dbObject\Holon();
    if (!$holon->load($holonId) || !$holon->canView()) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'message' => omoDocumentsTranscribeT('documents.transcribe.error.context_denied'),
        ));
        exit;
    }
}

if (empty($_FILES['audio']) || !is_array($_FILES['audio'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsTranscribeT('documents.transcribe.error.missing_audio'),
    ));
    exit;
}

$title = trim((string)($_POST['title'] ?? ''));
$prompt = 'Transcription en français d’une dictée pour un document. Rends la ponctuation naturelle et garde les retours à la ligne évidents.';
if ($title !== '') {
    $prompt .= ' Le titre du document est : ' . $title . '.';
}

$result = commonOpenAiTranscribeUploadedAudio($_FILES['audio'], array(
    'prompt' => $prompt,
));

if (empty($result['status'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => trim((string)($result['message'] ?? omoDocumentsTranscribeT('documents.transcribe.error.failed'))),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'text' => trim((string)($result['text'] ?? '')),
    'model' => trim((string)($result['model'] ?? '')),
));
