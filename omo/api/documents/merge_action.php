<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;

header('Content-Type: application/json; charset=UTF-8');

$payload = json_decode(file_get_contents('php://input'), true);
$payload = is_array($payload) ? $payload : array();
$documentIds = is_array($payload['ids'] ?? null) ? $payload['ids'] : array();
$keywords = is_array($payload['keywords'] ?? null) ? $payload['keywords'] : array();
$userId = (int)commonGetCurrentUserId();

if ($userId <= 0) {
    http_response_code(403);
    echo json_encode(array('status' => false, 'message' => 'Acces refuse.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$result = Document::mergeHtmlDocumentsInOrganizationContext(
    $documentIds,
    $userId,
    (string)($payload['title'] ?? ''),
    $keywords,
    (int)($payload['contextDocumentId'] ?? 0),
    (string)($payload['visibilityType'] ?? ''),
    (string)($payload['editVisibilityType'] ?? ''),
    !array_key_exists('keepSources', $payload) || !empty($payload['keepSources'])
);

if (!is_array($result) || ($result['status'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => (string)($result['text'] ?? 'Impossible de fusionner les documents.'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$document = $result['document'] ?? null;
echo json_encode(array(
    'status' => true,
    'message' => 'Document fusionne.',
    'documentId' => $document instanceof Document ? (int)$document->getId() : 0,
    'sourcesDeleted' => !empty($result['sourcesDeleted']),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
