<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;

header('Content-Type: application/json; charset=UTF-8');

$sourceLang = [
    'documents.move.error.access_denied' => [
        'text' => 'Accès refusé.',
        'context' => 'Error returned when the current user cannot access the move endpoint.',
    ],
    'documents.move.error.invalid_request' => [
        'text' => 'Le déplacement demandé est invalide.',
        'context' => 'Error returned when the move request does not contain a valid target.',
    ],
    'documents.move.error.not_found' => [
        'text' => 'Le document demandé est introuvable.',
        'context' => 'Error returned when the source document cannot be found.',
    ],
    'documents.move.error.failed' => [
        'text' => 'Impossible de déplacer le document.',
        'context' => 'Fallback error returned when the move operation fails.',
    ],
    'documents.move.success' => [
        'text' => 'Document déplacé.',
        'context' => 'Fallback success message returned when the document is moved successfully.',
    ],
];

$lang = omoLoadTranslationBundle('omo_documents_move_action', $sourceLang);

function omoDocumentsMoveActionT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

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
            'message' => omoDocumentsMoveActionT('documents.move.error.access_denied'),
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
            'message' => omoDocumentsMoveActionT('documents.move.error.invalid_request'),
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
            'message' => omoDocumentsMoveActionT('documents.move.error.invalid_request'),
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
            'message' => omoDocumentsMoveActionT('documents.move.error.not_found'),
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
            'message' => omoDocumentsMoveActionT('documents.move.error.access_denied'),
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
            'message' => (string)($result['text'] ?? omoDocumentsMoveActionT('documents.move.error.failed')),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['text'] ?? omoDocumentsMoveActionT('documents.move.success')),
        'document' => $result['document'] ?? null,
        'previousHolonId' => $result['previousHolonId'] ?? null,
        'previousParentDocumentId' => $result['previousParentDocumentId'] ?? null,
        'targetHolonId' => $result['targetHolonId'] ?? null,
        'targetParentDocumentId' => $result['targetParentDocumentId'] ?? null,
        'visibilityType' => $result['visibilityType'] ?? null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
