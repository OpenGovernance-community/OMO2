<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/etherpad.php';

use dbObject\Document;
use dbObject\User;

$documentId = (int)($_GET['id'] ?? 0);
$userId = (int)commonGetCurrentUserId();

if ($documentId <= 0 || $userId <= 0) {
    http_response_code(400);
    echo 'Demande invalide.';
    exit;
}

$document = new Document();
if (!$document->load($documentId) || !$document->isEtherpadDocument()) {
    http_response_code(404);
    echo 'Document introuvable.';
    exit;
}

$documentOrganizationId = (int)$document->get('IDorganization');
$documentHolonId = (int)$document->get('IDholon');
$organizationId = $documentOrganizationId;
if (
    $organizationId <= 0
    || !commonCurrentUserHasOrganizationAccess($organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $documentHolonId > 0 ? $documentHolonId : null)
) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo 'Organisation introuvable.';
    exit;
}

$padId = $document->getEtherpadPadId();
if ($padId === '' || !omoEtherpadHasConfig($organization)) {
    http_response_code(503);
    echo 'Etherpad n’est pas disponible pour cette organisation.';
    exit;
}

$canEdit = $document->canEditInOrganizationContext($organizationId, $userId, false);
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

$user = new User();
if (!$user->load($userId)) {
    http_response_code(503);
    echo 'Identité OMO introuvable.';
    exit;
}
$userName = trim((string)$user->getScopedDisplayName($organizationId));
$access = omoEtherpadPrepareEditingAccess(
    $organization,
    $padId,
    'omo-organization-' . $organizationId . '-user-' . $userId,
    $userName !== '' ? $userName : ('Utilisateur ' . $userId)
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
