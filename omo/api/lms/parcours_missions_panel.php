<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/parcours_editor.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_GET['pid'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0 || $parcoursId <= 0) {
    http_response_code(403);
    echo '<div class="lms-parcours-missions__empty">Acces refuse.</div>';
    exit;
}

$link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
if ($link === null) {
    http_response_code(404);
    echo '<div class="lms-parcours-missions__empty">Parcours introuvable.</div>';
    exit;
}

echo lmsRenderParcoursMissionManager($organizationId, $parcoursId);
