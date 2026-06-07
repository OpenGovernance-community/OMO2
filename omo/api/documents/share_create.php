<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\DocumentShareLink;

header('Content-Type: application/json; charset=UTF-8');

$documentId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$shareId = isset($_POST['share_id']) ? (int)$_POST['share_id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$label = trim((string)($_POST['label'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$clearPassword = !empty($_POST['clear_password']);
$allowLiveFollow = !empty($_POST['allow_live_follow']);
$dateExpirationRaw = trim((string)($_POST['dateexpiration'] ?? ''));

$document = new Document();
if (
    $documentId <= 0
    || $currentUserId <= 0
    || !$document->load($documentId)
    || $document->isFolder()
    || !$document->canEditInOrganizationContext((int)$document->get('IDorganization'))
) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => 'Acces refuse.',
    ));
    exit;
}

$dateExpiration = null;
if ($dateExpirationRaw !== '') {
    try {
        $dateExpiration = new DateTime($dateExpirationRaw);
    } catch (Exception $exception) {
        http_response_code(422);
        echo json_encode(array(
            'status' => false,
            'message' => 'Date d expiration invalide.',
        ));
        exit;
    }
}

$organizationId = (int)$document->get('IDorganization');

if ($shareId > 0) {
    $shareLink = DocumentShareLink::findByIdForContext($shareId, $organizationId, $documentId, true);
    if (!$shareLink) {
        http_response_code(404);
        echo json_encode(array(
            'status' => false,
            'message' => 'Lien introuvable.',
        ));
        exit;
    }

    $shareLink->set('label', $label);
    $shareLink->set('dateexpiration', $dateExpiration);
    $shareLink->set('allow_live_follow', $allowLiveFollow ? 1 : 0);
    $shareLink->set('active', 1);

    if ($clearPassword) {
        $shareLink->set('password_hash', null);
    } elseif ($password !== '') {
        $shareLink->set('password_hash', password_hash($password, PASSWORD_DEFAULT));
    }

    $result = $shareLink->save();
    $isSuccess = is_array($result) && !empty($result['status']);
} else {
    $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
    $shareLink = DocumentShareLink::createForDocument($document, $currentUserId, array(
        'label' => $label,
        'password_hash' => $passwordHash,
        'allow_live_follow' => $allowLiveFollow,
        'dateexpiration' => $dateExpiration,
    ));
    $isSuccess = $shareLink instanceof DocumentShareLink;
}

if (!$isSuccess) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => 'Impossible d enregistrer ce lien.',
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => $shareId > 0 ? 'Lien mis a jour.' : 'Lien cree.',
    'shareUrl' => $shareLink->buildShareUrl(),
));
