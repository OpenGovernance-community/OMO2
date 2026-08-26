<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;

header('Content-Type: application/json; charset=UTF-8');

$payload = json_decode(file_get_contents('php://input'), true);
$payload = is_array($payload) ? $payload : [];
$documentId = (int)($_GET['id'] ?? $_POST['id'] ?? ($payload['id'] ?? 0));
$action = trim(strtolower((string)($_GET['action'] ?? $_POST['action'] ?? ($payload['action'] ?? ''))));
$userId = (int)commonGetCurrentUserId();
$documentIds = [];
foreach ((array)($_GET['ids'] ?? $_POST['ids'] ?? ($payload['ids'] ?? [])) as $requestedDocumentId) {
    $requestedDocumentId = (int)$requestedDocumentId;
    if ($requestedDocumentId > 0) {
        $documentIds[$requestedDocumentId] = true;
    }
}
if ($documentId > 0) {
    $documentIds[$documentId] = true;
}
$documentIds = array_keys($documentIds);

$error = static function (string $message, int $status): void {
    http_response_code($status);
    echo json_encode(['status' => false, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (count($documentIds) === 0 || !in_array($action, ['archive', 'delete'], true) || $userId <= 0) {
    $error('Demande invalide.', 400);
}

$documents = [];
$expectedOrganizationId = 0;
foreach ($documentIds as $requestedDocumentId) {
    $document = new Document();
    if (!$document->load($requestedDocumentId) || (int)$document->get('IDorganization') <= 0) {
        $error('Document introuvable.', 404);
    }

    $organizationId = (int)$document->get('IDorganization');
    if ($expectedOrganizationId > 0 && $organizationId !== $expectedOrganizationId) {
        $error('Les documents doivent appartenir a la meme organisation.', 422);
    }
    $expectedOrganizationId = $organizationId;
    if (!commonCurrentUserHasOrganizationAccess($organizationId) || !$document->canManageLifecycle($organizationId, $userId)) {
        $error('Accès refusé.', 403);
    }

    if ($action === 'archive' && $document->isArchived()) {
        $error('Ce document est deja archive.', 422);
    }

    if ($action === 'delete' && !$document->canDeleteDocument()) {
        $error('Ce document ne peut pas être supprimé car il est utilisé ailleurs ou contient encore des documents.', 422);
    }

    $documents[] = $document;
}

if ($action === 'archive') {
    foreach ($documents as $document) {
        $document->set('active', 0);
        $result = $document->save();
        if (!is_array($result) || empty($result['status'])) {
            $error('Impossible d archiver le document.', 422);
        }
    }

    echo json_encode(['status' => true, 'action' => 'archive', 'affectedCount' => count($documents)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

foreach ($documents as $document) {
    if (!$document->delete()) {
        $error('Impossible de supprimer le document.', 422);
    }
}

echo json_encode(['status' => true, 'action' => 'delete', 'affectedCount' => count($documents)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
