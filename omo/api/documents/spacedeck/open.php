<?php
require_once dirname(__DIR__, 4) . '/common/document/link_bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/spacedeck.php';
$document = $documentLinkAccess['document'];
if (!$document->isWhiteboardDocument()) {
    http_response_code(404);
    exit;
}
$url = omoSpacedeckBuildDocumentOpenUrl($document, (int)commonGetCurrentUserId(),
    $documentLinkAccess['shareLink'], $documentLinkAccess['shareLink'] === null);
if ($url === '') {
    http_response_code(503);
    exit;
}
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
header('Location: ' . $url, true, 302);
