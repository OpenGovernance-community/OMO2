<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;
$documentId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();

if ($currentUserId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Acces refuse.',
    ));
    exit;
}

$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$keywords = trim((string)($_POST['keywords'] ?? ''));
$content = (string)($_POST['content'] ?? '');
$documentType = trim((string)($_POST['document_type'] ?? ''));
$externalUrl = trim((string)($_POST['external_url'] ?? ''));
$openInNewWindow = !empty($_POST['open_in_new_window']);
$visibilityType = trim((string)($_POST['visibility_type'] ?? 'organization'));
$editVisibilityType = trim((string)($_POST['edit_visibility_type'] ?? 'self'));
$isFolder = !empty($_POST['is_folder']) || trim(mb_strtolower($documentType, 'UTF-8')) === \dbObject\Document::TYPE_FOLDER;
$parentDocumentId = isset($_POST['parent_document_id']) ? (int)$_POST['parent_document_id'] : 0;

if (
    $documentId <= 0
    && !\dbObject\Document::canCreateInOrganizationContext(
        $organizationId,
        $holonId > 0 ? $holonId : null,
        $currentUserId,
        $parentDocumentId,
        false
    )
) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Acces refuse.',
    ));
    exit;
}

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
    'keywords' => $keywords,
    'content' => $content,
    'document_type' => $documentType,
    'external_url' => $externalUrl,
    'open_in_new_window' => $openInNewWindow,
    'uploaded_file' => $_FILES['uploaded_file'] ?? null,
    'remove_uploaded_file' => !empty($_POST['remove_uploaded_file']),
    'is_folder' => $isFolder,
    'parent_document_id' => $parentDocumentId,
);

if (array_key_exists('visibility_type', $_POST)) {
    $payload['visibility_type'] = $visibilityType;
}

if (array_key_exists('edit_visibility_type', $_POST)) {
    $payload['edit_visibility_type'] = $editVisibilityType;
}

if ($documentId > 0) {
    if (!$document->load($documentId)) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'message' => 'Acces refuse.',
        ));
        exit;
    }

    $organizationId = (int)$document->get('IDorganization');

    if (
        ($organizationId > 0 && !commonCurrentUserHasOrganizationAccess($organizationId))
        || !$document->canEditInOrganizationContext($organizationId, $currentUserId, false)
    ) {
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
    if ($organizationId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'message' => 'Acces refuse.',
        ));
        exit;
    }

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
        'message' => trim((string)($result['text'] ?? 'Impossible de créer ce document.')),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => $documentId > 0 ? 'Document mis à jour.' : 'Document créé.',
    'id' => (int)$document->getId(),
));
