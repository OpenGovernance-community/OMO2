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
    'documents.move.success_bulk' => [
        'text' => '{count} documents déplacés.',
        'context' => 'Success message returned when several documents are moved together.',
    ],
    'documents.move.success_none' => [
        'text' => 'Aucun document n’a été déplacé.',
        'context' => 'Success message returned when all selected documents were already at the destination.',
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

$documentIds = [];
$rawDocumentIds = $_GET['ids'] ?? $_POST['ids'] ?? ($payload['ids'] ?? []);
if (!is_array($rawDocumentIds)) {
    $rawDocumentIds = preg_split('/[,\s]+/', (string)$rawDocumentIds, -1, PREG_SPLIT_NO_EMPTY);
}
foreach ($rawDocumentIds as $rawDocumentId) {
    $normalizedDocumentId = (int)$rawDocumentId;
    if ($normalizedDocumentId > 0 && !in_array($normalizedDocumentId, $documentIds, true)) {
        $documentIds[] = $normalizedDocumentId;
    }
}
$documentId = (int)($_GET['id'] ?? $_POST['id'] ?? ($payload['id'] ?? 0));
if ($documentId > 0 && !in_array($documentId, $documentIds, true)) {
    array_unshift($documentIds, $documentId);
}
$documentId = (int)($documentIds[0] ?? 0);
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

if (count($documentIds) === 0 || (!$targetHolonIdProvided && !$targetParentDocumentIdProvided)) {
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

$documents = array($document);
foreach (array_slice($documentIds, 1) as $selectedDocumentId) {
    $selectedDocument = new Document();
    if (
        !$selectedDocument->load($selectedDocumentId)
        || (int)$selectedDocument->get('IDorganization') !== $organizationId
    ) {
        http_response_code(404);
        echo json_encode(array(
            'status' => 'error',
            'message' => omoDocumentsMoveActionT('documents.move.error.not_found'),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $documents[] = $selectedDocument;
}

$pdo = Document::getPdo();
$startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();
if ($startedTransaction) {
    $pdo->beginTransaction();
}

$movedCount = 0;
$firstResult = array('status' => true);
foreach ($documents as $selectedDocument) {
    $currentParentDocumentId = (int)$selectedDocument->get('IDdocument_parent');
    $currentHolonId = (int)$selectedDocument->get('IDholon');
    $targetParentId = $targetParentDocumentId !== null && $targetParentDocumentId > 0
        ? $targetParentDocumentId
        : 0;
    $targetHolon = $targetHolonId !== null && $targetHolonId > 0 ? $targetHolonId : 0;
    $alreadyAtDestination = $targetParentId > 0
        ? $currentParentDocumentId === $targetParentId
        : $currentParentDocumentId === 0 && $currentHolonId === $targetHolon;
    if ($alreadyAtDestination) {
        continue;
    }

    $result = $selectedDocument->moveToOrganizationContext(
        $organizationId,
        $targetHolonId !== null && $targetHolonId > 0 ? $targetHolonId : null,
        $targetParentDocumentId !== null && $targetParentDocumentId > 0 ? $targetParentDocumentId : null,
        $currentUserId
    );
    if (!($result['status'] ?? false)) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(422);
        echo json_encode(array(
            'status' => 'error',
            'message' => (string)($result['text'] ?? omoDocumentsMoveActionT('documents.move.error.failed')),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($movedCount === 0) {
        $firstResult = $result;
    }
    $movedCount += 1;
}

if ($startedTransaction && $pdo->inTransaction()) {
    $pdo->commit();
}

echo json_encode(
    array(
        'status' => 'ok',
        'message' => count($documents) > 1
            ? ($movedCount > 0
                ? omoDocumentsMoveActionT('documents.move.success_bulk', ['count' => $movedCount])
                : omoDocumentsMoveActionT('documents.move.success_none'))
            : (string)($firstResult['text'] ?? omoDocumentsMoveActionT('documents.move.success')),
        'movedCount' => $movedCount,
        'document' => $firstResult['document'] ?? null,
        'previousHolonId' => $firstResult['previousHolonId'] ?? null,
        'previousParentDocumentId' => $firstResult['previousParentDocumentId'] ?? null,
        'targetHolonId' => $firstResult['targetHolonId'] ?? null,
        'targetParentDocumentId' => $firstResult['targetParentDocumentId'] ?? null,
        'visibilityType' => $firstResult['visibilityType'] ?? null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
