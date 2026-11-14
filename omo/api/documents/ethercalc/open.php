<?php
require_once __DIR__ . '/../ui_translations.php';
$linkRequest = isset($_GET['reader']) || isset($_GET['share_token']);
require_once $linkRequest ? dirname(__DIR__, 4) . '/common/document/link_bootstrap.php' : dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/ethercalc.php';

use dbObject\Document;
use dbObject\User;

$documentId = (int)($_GET['id'] ?? 0);
$userId = (int)commonGetCurrentUserId();

if (!$linkRequest && ($documentId <= 0 || $userId <= 0)) {
    http_response_code(400);
    echo omoDocumentsUiT('demande_invalide');
    exit;
}

$document = $linkRequest ? $documentLinkAccess['document'] : new Document();
if ((!$linkRequest && !$document->load($documentId)) || !$document->isEthercalcDocument()) {
    http_response_code(404);
    echo omoDocumentsUiT('document_introuvable');
    exit;
}

$organizationId = (int)$document->get('IDorganization');
$holonId = (int)$document->get('IDholon');
if (!$linkRequest && (
    $organizationId <= 0
    || !commonCurrentUserHasOrganizationAccess($organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null)
)) {
    http_response_code(403);
    echo omoDocumentsUiT('acces_refuse');
    exit;
}

$roomId = $document->getEthercalcRoomId();
if ($roomId === '' || !omoEthercalcHasConfig()) {
    http_response_code(503);
    echo omoDocumentsUiT('ethercalc_n_est_pas_disponible');
    exit;
}

$canEdit = $linkRequest ? $documentLinkAccess['canEdit'] : $document->canEditInOrganizationContext($organizationId, $userId, false);
if (!$canEdit) {
    $result = omoEthercalcRequest('GET', '/_/' . rawurlencode($roomId) . '.html');
    $content = ['status' => !empty($result['status']), 'html' => \dbObject\PropertyFormat::sanitizeHtml((string)($result['body'] ?? ''))];
    if (isset($_GET['content'])) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($content['status'] ? 200 : 503);
        echo json_encode($content, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    require dirname(__DIR__) . '/etherpad/readonly.php';
    exit;
}

$user = new User();
$displayName = '';
if ($user->load($userId)) {
    $displayName = trim((string)$user->getScopedDisplayName($organizationId));
}
if ($displayName === '') {
    $displayName = 'Utilisateur ' . $userId;
}

$sheetUrl = omoEthercalcBuildSheetUrl(
    $roomId,
    $document->canEditInOrganizationContext($organizationId, $userId, false),
    $displayName
);
if ($linkRequest && $documentLinkAccess['shareLink']) {
    $sheetUrl = omoEthercalcBuildSharedSheetUrl($document, $documentLinkAccess['shareLink']);
}
if ($sheetUrl === '') {
    http_response_code(503);
    echo omoDocumentsUiT('url_ethercalc_invalide');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $sheetUrl, true, 302);
exit;
