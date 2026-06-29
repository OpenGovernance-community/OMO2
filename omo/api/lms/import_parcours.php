<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, 0, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);
$canCreateParcours = !empty($managementContext['canCreate']);
$parcoursId = (int)($_POST['parcours_id'] ?? 0);

if ($currentUserId <= 0 || !$hasOrganizationAccess || !$canCreateParcours || $organizationId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Vous n avez pas le droit d importer un parcours dans ce contexte.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($parcoursId <= 0) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Parcours invalide.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$existingLink = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
if ($existingLink !== null) {
    echo json_encode(array(
        'status' => true,
        'success' => true,
        'message' => 'Parcours deja present dans cette organisation.',
        'id' => $parcoursId,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$importableParcours = \dbObject\Parcours::loadImportableForOrganization($organizationId, $parcoursId);
if (!is_array($importableParcours) || empty($importableParcours['id'])) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Ce parcours n est pas disponible a l import.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$attachResult = \dbObject\OrganizationParcours::attachParcoursToOrganization(
    $organizationId,
    $parcoursId,
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
            : 'Impossible d importer ce parcours.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => 'Parcours importe.',
    'id' => $parcoursId,
    'created' => !empty($attachResult['created']),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
