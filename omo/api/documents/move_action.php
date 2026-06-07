<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;

header('Content-Type: application/json; charset=UTF-8');

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = array();
}

$documentId = (int)($_GET['id'] ?? $_POST['id'] ?? ($payload['id'] ?? 0));
$targetHolonIdProvided = isset($_GET['targetHolonId'])
    || isset($_POST['targetHolonId'])
    || array_key_exists('targetHolonId', $payload);
$targetParentDocumentIdProvided = isset($_GET['targetParentDocumentId'])
    || isset($_POST['targetParentDocumentId'])
    || array_key_exists('targetParentDocumentId', $payload);
$targetHolonId = $targetHolonIdProvided
    ? (int)($_GET['targetHolonId'] ?? $_POST['targetHolonId'] ?? $payload['targetHolonId'])
    : null;
$targetParentDocumentId = $targetParentDocumentIdProvided
    ? (int)($_GET['targetParentDocumentId'] ?? $_POST['targetParentDocumentId'] ?? $payload['targetParentDocumentId'])
    : null;
$currentUserId = (int)commonGetCurrentUserId();
$document = new Document();

if ($currentUserId <= 0) {
    http_response_code(403);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => 'Acces refuse.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($documentId <= 0 || (!$targetHolonIdProvided && !$targetParentDocumentIdProvided)) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => 'Le deplacement demande est invalide.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if (
    ($targetHolonId !== null && $targetHolonId < 0)
    || ($targetParentDocumentId !== null && $targetParentDocumentId < 0)
) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => 'Le deplacement demande est invalide.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if (!$document->load($documentId) || (int)$document->get('IDorganization') <= 0) {
    http_response_code(404);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => 'Le document demande est introuvable.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$organizationId = (int)$document->get('IDorganization');
if (!commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => 'Acces refuse.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$result = $document->moveToOrganizationContext(
    $organizationId,
    $targetHolonId !== null && $targetHolonId > 0 ? $targetHolonId : null,
    $targetParentDocumentId !== null && $targetParentDocumentId > 0 ? $targetParentDocumentId : null,
    $currentUserId
);

if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => (string)($result['text'] ?? 'Impossible de deplacer le document.'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['text'] ?? 'Document deplace.'),
        'document' => $result['document'] ?? null,
        'previousHolonId' => $result['previousHolonId'] ?? null,
        'previousParentDocumentId' => $result['previousParentDocumentId'] ?? null,
        'targetHolonId' => $result['targetHolonId'] ?? null,
        'targetParentDocumentId' => $result['targetParentDocumentId'] ?? null,
        'visibilityType' => $result['visibilityType'] ?? null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
