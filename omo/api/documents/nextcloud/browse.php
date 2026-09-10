<?php
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$folderId = (int)($_GET['id'] ?? 0);
$organizationId = (int)($_GET['oid'] ?? $_SESSION['currentOrganization'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? 0);
$rawPath = (string)($_GET['path'] ?? '');

if ($folderId > 0) {
    $context = omoDocumentsNextcloudLoadFolder($folderId, $organizationId);
    if (empty($context['status'])) {
        http_response_code((int)($context['httpCode'] ?? 404));
        echo json_encode(array('status' => false, 'message' => (string)($context['text'] ?? 'Acces refuse.')));
        exit;
    }

    $folder = $context['folder'];
    $organization = $context['organization'];
    $remotePath = omoDocumentsNextcloudGetRemotePath($folder, $organization, $rawPath, true);
    if ($remotePath === '') {
        http_response_code(400);
        echo json_encode(array('status' => false, 'message' => 'Chemin NextCloud invalide.'));
        exit;
    }
} else {
    $userId = (int)commonGetCurrentUserId();
    $organization = new \dbObject\Organization();
    if (
        $organizationId <= 0
        || $userId <= 0
        || !commonUserHasOrganizationAccess($userId, $organizationId)
        || !\dbObject\Document::canCreateInOrganizationContext($organizationId, $contextHolonId > 0 ? $contextHolonId : null, $userId, 0, false)
        || !$organization->load($organizationId)
        || !$organization->hasDocumentStorage()
    ) {
        http_response_code(403);
        echo json_encode(array('status' => false, 'message' => 'Acces refuse.'));
        exit;
    }

    $relativePath = \dbObject\Document::normalizeNextcloudFolderPath($rawPath);
    $configFolder = $organization->isKdriveDocumentStorage()
        ? ''
        : \dbObject\Document::normalizeNextcloudFolderPath(($organization->getNextcloudDocumentsConfig()['folder'] ?? ''));
    $remotePath = implode('/', array_filter(array($configFolder, $relativePath)));
}

$result = $organization->listDocumentStorageDirectory($remotePath);
if (empty($result['status'])) {
    http_response_code(502);
    echo json_encode(array('status' => false, 'message' => (string)($result['text'] ?? 'Impossible de lire le dossier NextCloud.')));
    exit;
}

$entries = array();
$configFolder = $folderId > 0 || $organization->isKdriveDocumentStorage()
    ? ''
    : \dbObject\Document::normalizeNextcloudFolderPath(($organization->getNextcloudDocumentsConfig()['folder'] ?? ''));
foreach ((array)($result['entries'] ?? array()) as $entry) {
    $path = \dbObject\Document::normalizeNextcloudFolderPath($entry['path'] ?? '');
    if ($path === '') {
        continue;
    }

    $name = trim((string)($entry['name'] ?? ''));
    $clientPath = $path;
    if ($folderId <= 0 && $configFolder !== '') {
        $clientPath = $path === $configFolder
            ? ''
            : (str_starts_with($path, $configFolder . '/') ? substr($path, strlen($configFolder) + 1) : '');
    }
    if ($clientPath === '') {
        continue;
    }
    $entries[] = array(
        'path' => $clientPath,
        'name' => $name !== '' ? $name : basename($path),
        'isFolder' => !empty($entry['isFolder']),
        'mimeType' => trim((string)($entry['mimeType'] ?? '')),
        'size' => max(0, (int)($entry['size'] ?? 0)),
        'modifiedAt' => trim((string)($entry['modifiedAt'] ?? '')),
    );
}

$clientCurrentPath = $remotePath;
if ($folderId <= 0 && $configFolder !== '') {
    $clientCurrentPath = $remotePath === $configFolder
        ? ''
        : (str_starts_with($remotePath, $configFolder . '/') ? substr($remotePath, strlen($configFolder) + 1) : '');
}

echo json_encode(array('status' => true, 'path' => $clientCurrentPath, 'entries' => $entries), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
