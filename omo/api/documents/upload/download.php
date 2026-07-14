<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use dbObject\Document;
use dbObject\Organization;

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($documentId <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Document invalide.';
    exit;
}

$document = new Document();
if (!$document->load($documentId) || !$document->isUploadedFile() || !$document->hasStoredFile()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Document introuvable ou inaccessible.';
    exit;
}

if ($organizationId <= 0) {
    $organizationId = (int)$document->get('IDorganization');
}

if ($holonId <= 0) {
    $holonId = (int)$document->get('IDholon');
}

if (!$document->canViewInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Document introuvable ou inaccessible.';
    exit;
}

$organization = new Organization();
if (
    !$organization->load((int)$document->get('IDorganization'))
    || !$organization->hasNextcloudDocumentStorage()
) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Stockage distant indisponible.';
    exit;
}

$downloadResult = $organization->downloadDocumentFileFromNextcloud((string)$document->get('storedfilepath'));
if (!is_array($downloadResult) || empty($downloadResult['status'])) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=UTF-8');
    echo trim((string)($downloadResult['text'] ?? 'Impossible de récupérer le fichier distant.'));
    exit;
}

$downloadName = $document->getStoredFileDownloadName();
$asciiFallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $downloadName);
$asciiFallbackName = trim((string)$asciiFallbackName, '-.');
if ($asciiFallbackName === '') {
    $asciiFallbackName = 'document-' . (int)$document->getId();
}

$contentType = trim((string)($downloadResult['contentType'] ?? 'application/octet-stream'));
$contentLength = max(0, (int)($downloadResult['contentLength'] ?? 0));
$body = (string)($downloadResult['body'] ?? '');

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . addcslashes($asciiFallbackName, "\\\"") . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
if ($contentLength > 0) {
    header('Content-Length: ' . $contentLength);
}

echo $body;
