<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/ethercalc.php';

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
if (!$document->load($documentId) || !$document->isEthercalcDocument()) {
    http_response_code(404);
    echo 'Document introuvable.';
    exit;
}

$organizationId = (int)$document->get('IDorganization');
$holonId = (int)$document->get('IDholon');
if (
    $organizationId <= 0
    || !commonCurrentUserHasOrganizationAccess($organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null)
) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$roomId = $document->getEthercalcRoomId();
if ($roomId === '' || !omoEthercalcHasConfig()) {
    http_response_code(503);
    echo 'EtherCalc n’est pas disponible.';
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
if ($sheetUrl === '') {
    http_response_code(503);
    echo 'URL EtherCalc invalide.';
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $sheetUrl, true, 302);
exit;
