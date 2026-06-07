<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/openai_text.php';

header('Content-Type: application/json; charset=UTF-8');

$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_POST['cid']) ? (int)($_POST['cid']) : 0;
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

$selectedText = trim((string)($_POST['selected_text'] ?? ''));
$fullText = trim((string)($_POST['full_text'] ?? ''));
$title = trim((string)($_POST['title'] ?? ''));

if ($selectedText === '' || $fullText === '') {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => 'Aucun texte valide n a ete recu.',
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
        'message' => trim((string)($result['message'] ?? 'Impossible de resumer ce texte.')),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'text' => trim((string)($result['text'] ?? '')),
    'model' => trim((string)($result['model'] ?? '')),
));
