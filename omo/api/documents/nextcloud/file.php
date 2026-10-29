<?php
require_once __DIR__ . '/../ui_translations.php';
require_once __DIR__ . '/shared.php';

$context = omoDocumentsNextcloudLoadFolder((int)($_GET['id'] ?? 0), (int)($_GET['oid'] ?? $_SESSION['currentOrganization'] ?? 0));
if (empty($context['status'])) {
    http_response_code((int)($context['httpCode'] ?? 404));
    header('Content-Type: text/plain; charset=UTF-8');
    echo (string)($context['text'] ?? omoDocumentsUiT('acces_refuse'));
    exit;
}

$remotePath = omoDocumentsNextcloudGetRemotePath($context['folder'], $context['organization'], $_GET['path'] ?? '');
if ($remotePath === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo omoDocumentsUiT('chemin_nextcloud_invalide');
    exit;
}

$downloadResult = $context['organization']->downloadDocumentFileFromStorage($remotePath);
if (empty($downloadResult['status'])) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=UTF-8');
    echo trim((string)($downloadResult['text'] ?? omoDocumentsUiT('impossible_de_recuperer_le_fichier_distant')));
    exit;
}

$downloadName = basename($remotePath);
$asciiFallbackName = trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '-', $downloadName), '-.');
if ($asciiFallbackName === '') {
    $asciiFallbackName = 'document';
}
$contentType = omoDocumentsNextcloudFileMimeType($downloadName, (string)($downloadResult['contentType'] ?? ''));
$body = (string)($downloadResult['body'] ?? '');
$contentLength = max(0, (int)($downloadResult['contentLength'] ?? strlen($body)));

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $contentType);
header('Content-Disposition: ' . (!empty($_GET['inline']) ? 'inline' : 'attachment') . '; filename="' . addcslashes($asciiFallbackName, "\\\"") . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
if ($contentLength > 0) {
    header('Content-Length: ' . $contentLength);
}
echo $body;
