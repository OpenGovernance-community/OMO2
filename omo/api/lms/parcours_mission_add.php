<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_POST['pid'] ?? 0);
$missionId = (int)($_POST['mission_id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0 || $parcoursId <= 0 || $missionId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Acces refuse.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
if ($link === null) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Parcours introuvable.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$mission = new \dbObject\Mission();
if (!$mission->load($missionId)) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Mission introuvable.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$result = \dbObject\ParcoursMission::attachMissionToParcours($parcoursId, $missionId);
if (!is_array($result) || empty($result['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => is_array($result) && !empty($result['message'])
            ? (string)$result['message']
            : 'Impossible d ajouter cette mission.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => !empty($result['created']) ? 'Mission ajoutee.' : 'Mission deja presente.',
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
