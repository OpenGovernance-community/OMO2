<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);
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
        'message' => 'Acces refuse.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$parcours = new \dbObject\Parcours();
$isEditMode = $parcoursId > 0;
$originalIsPublic = false;
$originalIsBasic = false;

if ($isEditMode) {
    $link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
    if ($link === null || !$parcours->load($parcoursId)) {
        http_response_code(404);
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => 'Parcours introuvable.',
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ((int)$parcours->get('IDorganization') !== $organizationId) {
        http_response_code(403);
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => 'Seuls les parcours proprietaires de cette organisation peuvent etre modifies.',
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $originalIsPublic = (bool)$parcours->get('ispublic');
    $originalIsBasic = (bool)$parcours->get('isbasic');
}

$data = $_POST;
$parcours->loadFromArray($data);

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
        'message' => 'Le titre est obligatoire.',
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
