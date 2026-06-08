<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$documentId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = trim(mb_strtolower((string)($_POST['action'] ?? ''), 'UTF-8'));
$clearDraft = !empty($_POST['clear_draft']);
$draftContent = array_key_exists('draft_content', $_POST) ? (string)$_POST['draft_content'] : null;
$currentUserId = (int)commonGetCurrentUserId();
$document = new \dbObject\Document();

if ($documentId <= 0 || $currentUserId <= 0) {
    http_response_code(400);
    echo json_encode(array(
        'status' => false,
        'message' => 'Requete invalide.',
    ));
    exit;
}

if (!$document->load($documentId)) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => 'Document introuvable.',
    ));
    exit;
}

$organizationId = (int)$document->get('IDorganization');
if ($organizationId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Acces refuse.',
    ));
    exit;
}

if ($action === 'release') {
    $result = $document->releaseEditLock($currentUserId, $clearDraft);
} else {
    $result = $document->touchEditLock($organizationId, $currentUserId, $draftContent);
}

if (!is_array($result) || ($result['status'] ?? false) !== true) {
    http_response_code(423);
    echo json_encode(array(
        'status' => false,
        'message' => trim((string)($result['text'] ?? 'Document verrouille.')),
        'lock' => is_array($result['lock'] ?? null) ? $result['lock'] : null,
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => trim((string)($result['text'] ?? 'OK')),
    'lock' => is_array($result['lock'] ?? null) ? $result['lock'] : null,
));
