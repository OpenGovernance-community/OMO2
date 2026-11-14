<?php
require_once __DIR__ . '/../ui_translations.php';
$linkRequest = isset($_GET['reader']) || isset($_GET['share_token']);
require_once $linkRequest ? dirname(__DIR__, 4) . '/common/document/link_bootstrap.php' : dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/etherpad.php';

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
if ((!$linkRequest && !$document->load($documentId)) || !$document->isEtherpadDocument()) {
    http_response_code(404);
    echo omoDocumentsUiT('document_introuvable');
    exit;
}

$documentOrganizationId = (int)$document->get('IDorganization');
$documentHolonId = (int)$document->get('IDholon');
$organizationId = $documentOrganizationId;
if (!$linkRequest && (
    $organizationId <= 0
    || !commonCurrentUserHasOrganizationAccess($organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $documentHolonId > 0 ? $documentHolonId : null)
)) {
    http_response_code(403);
    echo omoDocumentsUiT('acces_refuse');
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo omoDocumentsUiT('organisation_introuvable');
    exit;
}

$padId = $document->getEtherpadPadId();
if ($padId === '' || !omoEtherpadHasConfig($organization)) {
    http_response_code(503);
    echo omoDocumentsUiT('etherpad_n_est_pas_disponible_pour_cette_organisation');
    exit;
}

$canEdit = $linkRequest ? $documentLinkAccess['canEdit'] : $document->canEditInOrganizationContext($organizationId, $userId, false);
if (!$canEdit || isset($_GET['content'])) {
    // Private group pads reject even their read-only ID without an API session.
    // Such a session also grants editing: serve the sanitized content through OMO.
    $content = omoEtherpadGetReadOnlyContent($organization, $padId);
    if (isset($_GET['content'])) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(!empty($content['status']) ? 200 : 503);
        echo json_encode($content, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    require __DIR__ . '/readonly.php';
    exit;
}

$shareLink = $linkRequest ? $documentLinkAccess['shareLink'] : null;
$user = new User();
if (!$shareLink && !$user->load($userId)) {
    http_response_code(503);
    echo omoDocumentsUiT('identite_omo_introuvable');
    exit;
}
$userName = $shareLink ? commonDocumentServiceT('collabora.guest') : trim((string)$user->getScopedDisplayName($organizationId));
$access = omoEtherpadPrepareEditingAccess(
    $organization,
    $padId,
    $shareLink ? 'omo-share-' . $shareLink->getId() . '-' . $shareLink->getAccessFingerprint()
        : 'omo-organization-' . $organizationId . '-user-' . $userId,
    $userName !== '' ? $userName : ('Utilisateur ' . $userId),
    $shareLink ? commonDocumentShareValidUntil($shareLink, 3600) : 0
);
if (empty($access['status'])) {
    http_response_code(503);
    echo htmlspecialchars((string)$access['text'], ENT_QUOTES, 'UTF-8');
    exit;
}
$padUrl = $access['url'];

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $padUrl, true, 302);
exit;
