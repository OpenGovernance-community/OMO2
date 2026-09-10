<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/collabora.php';

use dbObject\Document;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$documentId = (int)($_POST['id'] ?? $_GET['id'] ?? $_POST['folder_id'] ?? $_GET['folder_id'] ?? 0);
$remotePath = \dbObject\Document::normalizeNextcloudFolderPath($_POST['path'] ?? $_GET['path'] ?? '');
$userId = (int)commonGetCurrentUserId();

if ($documentId <= 0 || $userId <= 0) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Demande invalide.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$document = new Document();
if (!$document->load($documentId) || (!$document->canOpenWithCollabora() && !$document->isNextcloudFolder())) {
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
if (!$organization->load($organizationId) || !$organization->hasDocumentStorage() || !omoCollaboraHasConfig($organization) || ($document->isNextcloudFolder() && !$organization->hasNextcloudDocumentStorage())) {
    http_response_code(503);
    echo json_encode(array('status' => false, 'message' => 'Collabora ou le stockage de documents n est pas disponible.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$isRemoteFile = $document->isNextcloudFolder();
if ($isRemoteFile && ($remotePath === '' || !$document->isNextcloudFolderRemotePathAllowed($organization, $remotePath))) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Chemin NextCloud invalide.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$expiresAt = time() + omoCollaboraGetWopiTokenLifetimeSeconds();
$accessToken = omoCollaboraBuildWopiToken($document, $userId, $expiresAt, $isRemoteFile ? $remotePath : '');
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
