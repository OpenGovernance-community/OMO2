<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;

header('Content-Type: application/json; charset=UTF-8');

$payload = json_decode(file_get_contents('php://input'), true);
$payload = is_array($payload) ? $payload : [];
$documentId = (int)($_GET['id'] ?? $_POST['id'] ?? ($payload['id'] ?? 0));
$action = trim(strtolower((string)($_GET['action'] ?? $_POST['action'] ?? ($payload['action'] ?? ''))));
$userId = (int)commonGetCurrentUserId();
$document = new Document();

$error = static function (string $message, int $status): void {
    http_response_code($status);
    echo json_encode(['status' => false, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if ($documentId <= 0 || !in_array($action, ['archive', 'delete'], true) || $userId <= 0) {
    $error('Demande invalide.', 400);
}

if (!$document->load($documentId) || (int)$document->get('IDorganization') <= 0) {
    $error('Document introuvable.', 404);
}

$organizationId = (int)$document->get('IDorganization');
if (!commonCurrentUserHasOrganizationAccess($organizationId) || !$document->canManageLifecycle($organizationId, $userId)) {
    $error('Acces refuse.', 403);
}

if ($action === 'delete' && !$document->canDeleteDocument()) {
    $error('Ce document doit etre archive car il est utilise ailleurs ou appartient au module PV.', 422);
}

if ($action === 'archive') {
    $document->set('active', 0);
    $result = $document->save();
    if (!is_array($result) || empty($result['status'])) {
        $error('Impossible d archiver le document.', 422);
    }

    echo json_encode(['status' => true, 'action' => 'archive'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$document->delete()) {
    $error('Impossible de supprimer le document.', 422);
}

echo json_encode(['status' => true, 'action' => 'delete'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
