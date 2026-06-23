<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);
$parcoursId = (int)($_GET['pid'] ?? 0);
$missionId = (int)($_POST['id'] ?? ($_GET['mid'] ?? 0));

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
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
$mission = new \dbObject\Mission();

if ($parcoursLink === null || !$parcoursMission->load([
    ['IDparcours', $parcoursId],
    ['IDmission', $missionId],
]) || !$mission->load($missionId)) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Mission introuvable.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$mission->loadFromArray($_POST);

if (trim((string)$mission->get('title')) === '') {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Le titre est obligatoire.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (trim((string)$mission->get('resume')) === '') {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Le resume est obligatoire.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$saveResult = $mission->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => is_array($saveResult) && !empty($saveResult['text'])
            ? (string)$saveResult['text']
            : 'Impossible de mettre a jour cette mission.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => 'Mission mise a jour.',
    'id' => (int)$mission->getId(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

