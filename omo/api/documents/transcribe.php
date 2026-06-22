<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/openai_audio.php';

header('Content-Type: application/json; charset=UTF-8');

$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Acces refuse.',
    ));
    exit;
}

if ($holonId > 0) {
    $holon = new \dbObject\Holon();
    if (!$holon->load($holonId) || !$holon->canView()) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'message' => 'Acces refuse a ce contexte.',
        ));
        exit;
    }
}

if (empty($_FILES['audio']) || !is_array($_FILES['audio'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => 'Aucun enregistrement audio n a ete recu.',
    ));
    exit;
}

$title = trim((string)($_POST['title'] ?? ''));
$prompt = 'Transcription en francais d une dictee pour un document. Rends la ponctuation naturelle et garde les retours a la ligne evidents.';
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
        'message' => trim((string)($result['message'] ?? 'Impossible de transcrire cet enregistrement.')),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'text' => trim((string)($result['text'] ?? '')),
    'model' => trim((string)($result['model'] ?? '')),
));
