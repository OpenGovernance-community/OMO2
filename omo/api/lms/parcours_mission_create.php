<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);
$parcoursId = (int)($_POST['pid'] ?? 0);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Acces refuse.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($parcoursId <= 0) {
    http_response_code(400);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Parcours invalide.',
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
$mission->loadFromArray($_POST);
$mission->set('position', \dbObject\Mission::getNextPosition());

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
            : 'Impossible de creer cette mission.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$attachResult = \dbObject\ParcoursMission::attachMissionToParcours($parcoursId, (int)$mission->getId());
if (!is_array($attachResult) || empty($attachResult['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => is_array($attachResult) && !empty($attachResult['message'])
            ? (string)$attachResult['message']
            : 'La mission a ete creee mais pas ajoutee au parcours.',
        'id' => (int)$mission->getId(),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => 'Mission creee et ajoutee.',
    'id' => (int)$mission->getId(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

