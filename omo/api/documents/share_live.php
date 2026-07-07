<?php
require_once dirname(__DIR__, 3) . '/shared_functions.php';
require_once dirname(__DIR__, 3) . '/common/auth.php';

use dbObject\DocumentShareLink;

header('Content-Type: application/json; charset=UTF-8');

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$knownUpdatedAt = trim((string)($_GET['known_updated_at'] ?? $_POST['known_updated_at'] ?? ''));
$knownContentHash = trim((string)($_GET['known_content_hash'] ?? $_POST['known_content_hash'] ?? ''));
$knownStateHash = trim((string)($_GET['known_state_hash'] ?? $_POST['known_state_hash'] ?? ''));
$shareLink = DocumentShareLink::findValidByToken($token);

if (!$shareLink) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => 'Lien invalide.',
    ));
    exit;
}

if ($shareLink->requiresPassword() && !commonIsSharePasswordVerified($token)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Mot de passe requis.',
    ));
    exit;
}

$document = $shareLink->getDocument();
if (!($document instanceof \dbObject\Document) || $document->isFolder()) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => 'Document introuvable.',
    ));
    exit;
}

$payload = $document->buildLiveSharePayload($shareLink->allowsLiveFollow());
$stateHash = trim((string)($payload['stateHash'] ?? ''));
$contentHash = trim((string)($payload['contentHash'] ?? ''));
$updatedAt = trim((string)($payload['updatedAt'] ?? ''));
$hasKnownStateHash = $knownStateHash !== '';
$hasKnownContentHash = $knownContentHash !== '';

$stateMatches = $hasKnownStateHash && hash_equals($stateHash, $knownStateHash);
$legacyMatches = !$hasKnownStateHash
    && $knownUpdatedAt !== ''
    && $knownUpdatedAt === $updatedAt
    && $hasKnownContentHash
    && hash_equals($contentHash, $knownContentHash);

if ($stateMatches || $legacyMatches) {
    echo json_encode(array(
        'status' => true,
        'changed' => false,
        'updatedAt' => $payload['updatedAt'],
        'contentHash' => $contentHash,
        'stateHash' => $stateHash,
    ));
    exit;
}

$contentChanged = !$hasKnownContentHash || !hash_equals($contentHash, $knownContentHash);

$response = array(
    'status' => true,
    'changed' => true,
    'contentChanged' => $contentChanged,
    'title' => $payload['title'],
    'description' => $payload['description'],
    'isDraft' => $payload['isDraft'],
    'editingUserName' => $payload['editingUserName'],
    'updatedAt' => $payload['updatedAt'],
    'contentHash' => $contentHash,
    'stateHash' => $stateHash,
);

if ($contentChanged) {
    $response['content'] = $payload['content'];
}

echo json_encode($response);
