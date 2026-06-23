<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_POST['pid'] ?? 0);
$missionId = (int)($_POST['mid'] ?? 0);
$questionIds = $_POST['question_ids'] ?? array();
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

$parcoursLink = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
$parcoursMission = new \dbObject\ParcoursMission();
if ($parcoursLink === null || !$parcoursMission->load([
    ['IDparcours', $parcoursId],
    ['IDmission', $missionId],
])) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Mission introuvable.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!is_array($questionIds)) {
    $questionIds = array();
}

$result = \dbObject\MissionQuestion::reorderForMission($missionId, $questionIds);
if (!is_array($result) || empty($result['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => is_array($result) && !empty($result['message'])
            ? (string)$result['message']
            : 'Impossible de reordonner les questions.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => 'Ordre des questions mis a jour.',
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
