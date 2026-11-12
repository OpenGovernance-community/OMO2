<?php
require_once __DIR__ . '/../ui_translations.php';
if (isset($_GET['pv_document_id'])) {
    require_once dirname(__DIR__, 4) . '/shared_functions.php';
    require_once dirname(__DIR__, 4) . '/common/auth.php';
    require_once dirname(__DIR__, 4) . '/common/pv_participation.php';
    $pvDocument = commonResolvePvEmbeddedDocument();
    header('Cache-Control: private, no-store');
    header('Referrer-Policy: no-referrer');
    if (!$pvDocument) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo omoDocumentsUiT('document_introuvable_ou_inaccessible_avec_ce_lien_de_reunion');
        exit;
    }
} elseif (isset($_GET['reader'])) {
    require_once dirname(__DIR__, 4) . '/shared_functions.php';
    require_once dirname(__DIR__, 4) . '/common/auth.php';
    $readerDocument = new \dbObject\Document();
    if (!$readerDocument->load((int)($_GET['id'] ?? 0))
        || !$readerDocument->canReadFromLink((int)commonGetCurrentUserId())
        || array_key_exists('version_id', $_GET)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo omoDocumentsUiT('document_introuvable_ou_inaccessible');
        exit;
    }
} else {
    require_once dirname(__DIR__, 2) . '/bootstrap.php';
}

use dbObject\Document;
use dbObject\Organization;

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$versionId = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;
$isVersionRequest = array_key_exists('version_id', $_GET);

if ($documentId <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo omoDocumentsUiT('document_invalide');
    exit;
}

$document = new Document();
if (!$document->load($documentId) || !$document->isUploadedFile() || (!$isVersionRequest && !$document->hasStoredFile())) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo omoDocumentsUiT('document_introuvable_ou_inaccessible');
    exit;
}

if ($organizationId <= 0) {
    $organizationId = (int)$document->get('IDorganization');
}

if ($holonId <= 0) {
    $holonId = (int)$document->get('IDholon');
}

if (($isVersionRequest && (isset($pvDocument) || isset($readerDocument))) || (!isset($pvDocument) && !isset($readerDocument)
    && !$document->canViewInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null)
    && !$document->canViewDirectlyInOrganization($organizationId))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo omoDocumentsUiT('document_introuvable_ou_inaccessible');
    exit;
}

$version = null;
if ($isVersionRequest) {
    $version = new \dbObject\DocumentVersion();
    if ($versionId <= 0 || !$version->load($versionId) || !$version->belongsToDocument($document)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo omoDocumentsUiT('document_introuvable_ou_inaccessible');
        exit;
    }
}

$organization = new Organization();
if (
    !$organization->load((int)$document->get('IDorganization'))
    || !$organization->hasDocumentStorage()
) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo omoDocumentsUiT('stockage_distant_indisponible');
    exit;
}

$downloadResult = $organization->downloadDocumentFileFromStorage((string)($version ?? $document)->get('storedfilepath'));
if (!is_array($downloadResult) || empty($downloadResult['status'])) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=UTF-8');
    echo trim((string)($downloadResult['text'] ?? omoDocumentsUiT('impossible_de_recuperer_le_fichier_distant')));
    exit;
}

$downloadName = $version ? (string)$version->get('storedfilename') : $document->getStoredFileDownloadName();
$asciiFallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $downloadName);
$asciiFallbackName = trim((string)$asciiFallbackName, '-.');
if ($asciiFallbackName === '') {
    $asciiFallbackName = 'document-' . (int)$document->getId();
}

$contentType = trim((string)($downloadResult['contentType'] ?? 'application/octet-stream'));
if ($contentType === '' || $contentType === 'application/octet-stream') {
    $storedMimeType = trim($version ? (string)$version->get('storedfilemime') : $document->getStoredFileMimeType());
    if ($storedMimeType !== '') {
        $contentType = $storedMimeType;
    }
}
$contentLength = max(0, (int)($downloadResult['contentLength'] ?? 0));
$body = (string)($downloadResult['body'] ?? '');
$inline = !$isVersionRequest && !empty($_GET['inline'])
    && ((!isset($pvDocument) && !isset($readerDocument)) || $document->isStoredPdfFile()
        || (isset($readerDocument) && in_array($document->getStoredFileKind(), ['image', 'audio'], true)));

header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $contentType);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addcslashes($asciiFallbackName, "\\\"") . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
if ($contentLength > 0) {
    header('Content-Length: ' . $contentLength);
}

echo $body;
