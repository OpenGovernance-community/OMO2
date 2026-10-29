<?php
require_once __DIR__ . '/ui_translations.php';
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, 0, $currentUserId);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);
$canCreateParcours = !empty($managementContext['canCreate']);
$canManagePublicParcours = function_exists('commonCurrentUserIsAdminModeEnabled')
    ? commonCurrentUserIsAdminModeEnabled($organizationId)
    : false;
$canManageBasicParcours = function_exists('commonCurrentUserIsSiteAdminModeEnabled')
    ? commonCurrentUserIsSiteAdminModeEnabled()
    : false;
$parcoursId = (int)($_POST['id'] ?? ($_GET['pid'] ?? 0));

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => omoLmsUiT('acces_refuse'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$parcours = new \dbObject\Parcours();
$isEditMode = $parcoursId > 0;
$originalIsPublic = false;
$originalIsBasic = false;

if ($isEditMode) {
    $managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
    $link = $managementContext['link'] ?? null;
    $loadedParcours = $managementContext['parcours'] ?? null;
    if (!($loadedParcours instanceof \dbObject\Parcours) || ($link === null && empty($managementContext['isExposedViaPack']))) {
        http_response_code(404);
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => omoLmsUiT('parcours_introuvable'),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (empty($managementContext['canEditContent'])) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => omoLmsUiT('vous_n_avez_pas_le_droit_de_modifier_ce_parcours'),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $parcours = $loadedParcours;
    $originalIsPublic = (bool)$parcours->get('ispublic');
    $originalIsBasic = (bool)$parcours->get('isbasic');
} elseif (!$canCreateParcours) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => omoLmsUiT('vous_n_avez_pas_le_droit_de_creer_un_parcours_dans_ce_contexte'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = $_POST;
$parcours->loadFromArray($data);
$parcours->set('isarchived', false); // Retirement is handled only by the deletion action.

if (!\dbObject\Parcours::hasApplicationColumn()) {
    $parcours->set('IDapplication', null);
}

if (!\dbObject\Parcours::hasIsPackColumn()) {
    $parcours->set('ispack', false);
}

if (!$isEditMode) {
    $parcours->set('IDorganization', $organizationId);
}

if (!$canManagePublicParcours) {
    $parcours->set('ispublic', $isEditMode ? $originalIsPublic : false);
}

if (!$canManageBasicParcours) {
    $parcours->set('isbasic', $isEditMode ? $originalIsBasic : false);
}

if (trim((string)$parcours->get('title')) === '') {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => omoLmsUiT('le_titre_est_obligatoire'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$saveResult = $parcours->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => is_array($saveResult) && !empty($saveResult['text'])
            ? (string)$saveResult['text']
            : ($isEditMode ? 'Impossible de mettre a jour ce parcours.' : 'Impossible de creer ce parcours.'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$isEditMode) {
    $attachResult = \dbObject\OrganizationParcours::attachParcoursToOrganization(
        $organizationId,
        (int)$parcours->getId(),
        array(
            'everybody' => true,
            'anonymous' => false,
        )
    );

    if (!is_array($attachResult) || empty($attachResult['status'])) {
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => is_array($attachResult) && !empty($attachResult['message'])
                ? (string)$attachResult['message']
                : 'Le parcours a ete cree mais pas rattache a l organisation.',
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => $isEditMode ? 'Parcours mis a jour.' : 'Parcours cree.',
    'id' => (int)$parcours->getId(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
