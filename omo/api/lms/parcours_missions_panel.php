<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once __DIR__ . '/inc/parcours_editor.php';

$sourceLang = [
    'lms.parcours_missions_panel.error.access_denied' => ['text' => 'Acces refuse.', 'context' => 'Error shown when the user cannot access the parcours content manager panel.'],
    'lms.parcours_missions_panel.error.not_found' => ['text' => 'Parcours introuvable.', 'context' => 'Error shown when the parcours content manager cannot find the parcours.'],
    'lms.parcours_missions_panel.error.cannot_edit' => ['text' => 'Vous n avez pas le droit de modifier ce parcours.', 'context' => 'Error shown when the user cannot edit the parcours content manager.'],
];

$lang = omoLoadTranslationBundle('omo_lms_parcours_missions_panel', $sourceLang);

function lmsParcoursMissionsPanelT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_GET['pid'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0 || $parcoursId <= 0) {
    http_response_code(403);
    echo '<div class="lms-parcours-missions__empty">' . htmlspecialchars(lmsParcoursMissionsPanelT('lms.parcours_missions_panel.error.access_denied')) . '</div>';
    exit;
}

if (($managementContext['link'] ?? null) === null || !(($managementContext['parcours'] ?? null) instanceof \dbObject\Parcours)) {
    http_response_code(404);
    echo '<div class="lms-parcours-missions__empty">' . htmlspecialchars(lmsParcoursMissionsPanelT('lms.parcours_missions_panel.error.not_found')) . '</div>';
    exit;
}

if (empty($managementContext['canEditContent'])) {
    http_response_code(403);
    echo '<div class="lms-parcours-missions__empty">' . htmlspecialchars(lmsParcoursMissionsPanelT('lms.parcours_missions_panel.error.cannot_edit')) . '</div>';
    exit;
}

echo lmsRenderParcoursContentManager($organizationId, $parcoursId);
