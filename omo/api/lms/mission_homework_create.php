<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);
$parcoursId = (int)($_POST['pid'] ?? 0);
$missionId = (int)($_POST['mid'] ?? 0);
$homeworkId = (int)($_POST['id'] ?? 0);

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

$homework = new \dbObject\Homework();
$isEditMode = false;

if ($homeworkId > 0) {
    $missionHomework = new \dbObject\MissionHomework();
    if (!$missionHomework->load([
        ['IDmission', $missionId],
        ['IDhomework', $homeworkId],
    ]) || !$homework->load($homeworkId)) {
        http_response_code(404);
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => 'Devoir introuvable.',
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $isEditMode = true;
}

$homework->loadFromArray($_POST);
if (!$isEditMode) {
    $homework->set('position', \dbObject\Homework::getNextPosition());
}

if (trim((string)$homework->get('title')) === '') {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Le titre est obligatoire.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$saveResult = $homework->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
            'message' => is_array($saveResult) && !empty($saveResult['text'])
                ? (string)$saveResult['text']
            : ($isEditMode ? 'Impossible de mettre a jour ce devoir.' : 'Impossible de creer ce devoir.'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$isEditMode) {
    $attachResult = \dbObject\MissionHomework::attachHomeworkToMission($missionId, (int)$homework->getId());
    if (!is_array($attachResult) || empty($attachResult['status'])) {
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => is_array($attachResult) && !empty($attachResult['message'])
                ? (string)$attachResult['message']
                : 'Le devoir a ete cree mais pas lie a la mission.',
            'id' => (int)$homework->getId(),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => $isEditMode ? 'Devoir mis a jour.' : 'Devoir cree.',
    'id' => (int)$homework->getId(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
