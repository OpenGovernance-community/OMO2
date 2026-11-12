<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/ui_translations.php';
require_once __DIR__ . '/history_helpers.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => false, 'message' => omoDocumentsUiT('demande_invalide')]);
    exit;
}
$payload = json_decode((string)file_get_contents('php://input'), true);
$document = new \dbObject\Document();
$userId = (int)commonGetCurrentUserId();
$documentId = is_array($payload) ? (int)($payload['id'] ?? 0) : 0;
if ($userId <= 0 || $documentId <= 0 || !$document->load($documentId)
    || !commonCurrentUserHasOrganizationAccess((int)$document->get('IDorganization'))
    || !$document->canViewDirectlyInOrganization((int)$document->get('IDorganization'), $userId)
    || !$document->canEditInOrganizationContext((int)$document->get('IDorganization'), $userId, false)) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => omoDocumentsUiT('acces_refuse')]);
    exit;
}
$result = $document->archiveCurrentVersion($userId);
if (empty($result['status'])) {
    http_response_code(422);
    echo json_encode(['status' => false, 'message' => $result['text'] ?? omoDocumentHistoryT('error')]);
    exit;
}
echo json_encode([
    'status' => true, 'message' => omoDocumentHistoryT('success'),
    'html' => omoDocumentHistoryVersionHtml($result['version'], (int)$document->get('IDorganization')),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
