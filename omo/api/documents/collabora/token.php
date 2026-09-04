<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/collabora.php';

use dbObject\Document;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$documentId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$userId = (int)commonGetCurrentUserId();

if ($documentId <= 0 || $userId <= 0) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Demande invalide.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$document = new Document();
if (!$document->load($documentId) || !$document->canOpenWithCollabora()) {
    http_response_code(404);
    echo json_encode(array('status' => false, 'message' => 'Document introuvable.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organizationId = (int)$document->get('IDorganization');
$holonId = (int)$document->get('IDholon');
if (
    $organizationId <= 0
    || !commonUserHasOrganizationAccess($userId, $organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null, $userId)
) {
    http_response_code(403);
    echo json_encode(array('status' => false, 'message' => 'Acces refuse.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId) || !$organization->hasDocumentStorage() || !omoCollaboraHasConfig($organization)) {
    http_response_code(503);
    echo json_encode(array('status' => false, 'message' => 'Collabora ou le stockage de documents n est pas disponible.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$expiresAt = time() + omoCollaboraGetWopiTokenLifetimeSeconds();
$accessToken = omoCollaboraBuildWopiToken($document, $userId, $expiresAt);
if ($accessToken === '') {
    http_response_code(500);
    echo json_encode(array('status' => false, 'message' => 'Impossible de renouveler le jeton Collabora.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array(
    'status' => true,
    'accessToken' => $accessToken,
    'expiresAt' => $expiresAt,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
