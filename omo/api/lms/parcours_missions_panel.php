<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once __DIR__ . '/inc/parcours_editor.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_GET['pid'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0 || $parcoursId <= 0) {
    http_response_code(403);
    echo '<div class="lms-parcours-missions__empty">Acces refuse.</div>';
    exit;
}

if (($managementContext['link'] ?? null) === null || !(($managementContext['parcours'] ?? null) instanceof \dbObject\Parcours)) {
    http_response_code(404);
    echo '<div class="lms-parcours-missions__empty">Parcours introuvable.</div>';
    exit;
}

if (empty($managementContext['canEditContent'])) {
    http_response_code(403);
    echo '<div class="lms-parcours-missions__empty">Vous n avez pas le droit de modifier ce parcours.</div>';
    exit;
}

echo lmsRenderParcoursContentManager($organizationId, $parcoursId);
