<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;
$documentId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Acces refuse.',
    ));
    exit;
}

$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$content = (string)($_POST['content'] ?? '');
$visibilityType = trim((string)($_POST['visibility_type'] ?? 'organization'));

if ($title === '') {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => 'Le titre est obligatoire.',
    ));
    exit;
}

$document = new \dbObject\Document();
$payload = array(
    'title' => $title,
    'description' => $description,
    'content' => $content,
    'visibility_type' => $visibilityType,
);

if ($documentId > 0) {
    if (!$document->load($documentId) || !$document->canEditInOrganizationContext($organizationId)) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'message' => 'Acces refuse.',
        ));
        exit;
    }

    $result = $document->updateInOrganizationContext(
        $organizationId,
        $currentUserId,
        $payload
    );
} else {
    $result = $document->createInOrganizationContext(
        $organizationId,
        $holonId > 0 ? $holonId : null,
        $currentUserId,
        $payload
    );
}

if (!is_array($result) || ($result['status'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => trim((string)($result['text'] ?? 'Impossible de creer ce document.')),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => $documentId > 0 ? 'Document mis a jour.' : 'Document cree.',
    'id' => (int)$document->getId(),
));
