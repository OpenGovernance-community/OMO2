<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\DocumentShareLink;

header('Content-Type: application/json; charset=UTF-8');

$documentId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$shareId = isset($_POST['share_id']) ? (int)$_POST['share_id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$document = new Document();

if (
    $documentId <= 0
    || $shareId <= 0
    || $currentUserId <= 0
    || !$document->load($documentId)
    || $document->isFolder()
    || !$document->supportsHtmlContent()
    || !$document->canViewInOrganizationContext(
        (int)$document->get('IDorganization'),
        (int)$document->get('IDholon') > 0 ? (int)$document->get('IDholon') : null
    )
) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Acces refuse.',
    ));
    exit;
}

$shareLink = DocumentShareLink::findByIdForContext($shareId, (int)$document->get('IDorganization'), $documentId, true);
if (!$shareLink) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => 'Lien introuvable.',
    ));
    exit;
}

$shareLink->set('active', 0);
$result = $shareLink->save();

if (!is_array($result) || empty($result['status'])) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => 'Suppression impossible.',
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => 'Lien supprime.',
));
