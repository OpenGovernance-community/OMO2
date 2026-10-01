<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/detail_renderer.php';

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$accessCode = trim((string)($_GET['access_code'] ?? ''));
$currentUserId = (int)commonGetCurrentUserId();

if ($documentId <= 0) {
    http_response_code(400);
    ?>
    <div class="memo-document-detail memo-document-detail--error">
        <div class="omo-empty-state">Document invalide.</div>
    </div>
    <?php
    exit;
}

$document = new \dbObject\Document();
if (
    !$document->load($documentId)
    || !$document->canViewInMemoContext($currentUserId, $accessCode)
) {
    http_response_code($currentUserId > 0 ? 403 : 401);
    ?>
    <div class="memo-document-detail memo-document-detail--error">
        <div class="omo-empty-state">Document introuvable ou inaccessible.</div>
    </div>
    <?php
    exit;
}

$_SESSION['doc_' . $document->getId()] = true;
$document->markConsulted();
memoRenderDocumentDetail($document);
