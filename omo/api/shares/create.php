<?php
require_once __DIR__ . '/context.php';

header('Content-Type: application/json; charset=UTF-8');

use dbObject\HolonShareLink;

$context = omoShareResolveManageContext($_POST);
if (empty($context['status'])) {
    http_response_code((int)($context['code'] ?? 400));
    echo json_encode(array(
        'status' => false,
        'message' => (string)($context['message'] ?? 'Erreur.'),
    ));
    exit;
}

$currentUserId = (int)$context['currentUserId'];
$organizationId = (int)$context['organizationId'];
$currentHolon = $context['currentHolon'];

$shareLinkId = isset($_POST['share_id']) && is_numeric($_POST['share_id']) ? (int)$_POST['share_id'] : 0;
$label = trim((string)($_POST['label'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$clearPassword = !empty($_POST['clear_password']);
$allowStructure = !empty($_POST['allow_structure']);
$allowPeople = !empty($_POST['allow_people']);
$allowPeopleDetail = $allowPeople && !empty($_POST['allow_people_detail']);
$expirationRaw = trim((string)($_POST['dateexpiration'] ?? ''));

if (!$allowStructure && !$allowPeople && !$allowPeopleDetail) {
    http_response_code(400);
    echo json_encode(array(
        'status' => false,
        'message' => 'Choisissez au moins un droit d acces.',
    ));
    exit;
}

$dateExpiration = null;
if ($expirationRaw !== '') {
    try {
        $dateExpiration = new DateTime($expirationRaw);
    } catch (Exception $exception) {
        http_response_code(400);
        echo json_encode(array(
            'status' => false,
            'message' => 'Date d expiration invalide.',
        ));
        exit;
    }
}

$shareLink = null;
$isUpdate = $shareLinkId > 0;

if ($isUpdate) {
    $shareLink = HolonShareLink::findByIdForContext($shareLinkId, $organizationId, (int)$currentHolon->getId(), true);
    if (!$shareLink || !(bool)$shareLink->get('active')) {
        http_response_code(404);
        echo json_encode(array(
            'status' => false,
            'message' => 'Lien de partage introuvable.',
        ));
        exit;
    }

    $shareLink->set('label', $label);
    $shareLink->set('allow_structure', $allowStructure ? 1 : 0);
    $shareLink->set('allow_people', $allowPeople ? 1 : 0);
    $shareLink->set('allow_people_detail', $allowPeopleDetail ? 1 : 0);
    $shareLink->set('dateexpiration', $dateExpiration);

    if ($clearPassword) {
        $shareLink->set('password_hash', null);
    } elseif ($password !== '') {
        $shareLink->set('password_hash', password_hash($password, PASSWORD_DEFAULT));
    }

    $result = $shareLink->save();
    if (empty($result['status'])) {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'message' => 'Impossible de mettre a jour le lien de partage.',
        ));
        exit;
    }
} else {
    $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
    $shareLink = HolonShareLink::createForHolon($currentHolon, $currentUserId, array(
        'label' => $label,
        'password_hash' => $passwordHash,
        'allow_structure' => $allowStructure,
        'allow_people' => $allowPeople,
        'allow_people_detail' => $allowPeopleDetail,
        'dateexpiration' => $dateExpiration,
    ));

    if (!$shareLink) {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'message' => 'Impossible de creer le lien de partage.',
        ));
        exit;
    }
}

echo json_encode(array(
    'status' => true,
    'message' => $isUpdate ? 'Lien mis a jour.' : 'Lien cree.',
    'url' => $shareLink->buildShareUrl(),
    'shareId' => (int)$shareLink->getId(),
    'token' => (string)$shareLink->get('token'),
    'holonName' => $currentHolon->getDisplayName(),
    'permissions' => array(
        'structure' => $allowStructure,
        'people' => $allowPeople,
        'peopleDetail' => $allowPeopleDetail,
    ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
