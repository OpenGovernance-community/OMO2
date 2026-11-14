<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/document/share.php';

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
    || !$document->canManageShareLinks($currentUserId)
) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsUiT('acces_refuse'),
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
            'message' => omoDocumentsUiT('date_d_expiration_invalide'),
        ));
        exit;
    }
}

$organizationId = (int)$document->get('IDorganization');
$allowLiveFollow = $document->supportsHtmlContent() && $allowLiveFollow;

if ($shareId > 0) {
    $shareLink = DocumentShareLink::findByIdForContext($shareId, $organizationId, $documentId, true);
    if (!$shareLink) {
        http_response_code(404);
        echo json_encode(array(
            'status' => false,
            'message' => omoDocumentsUiT('lien_introuvable'),
        ));
        exit;
    }

    if ($shareLink->allowsPvContribution()) {
        http_response_code(403);
        exit;
    }
    if (!commonDocumentShareRevokeEditingAccess($shareLink, $document)) {
        http_response_code(503);
        echo json_encode(['status' => false, 'message' => omoDocumentsUiT('impossible_d_enregistrer_ce_lien')]);
        exit;
    }

    $shareLink->set('label', $label);
    $shareLink->set('dateexpiration', $dateExpiration);
    $shareLink->set('allow_live_follow', $allowLiveFollow ? 1 : 0);
    $shareLink->set('allow_edit', $document->supportsSharedEditing() && !empty($_POST['allow_edit']) ? 1 : 0);
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
        'allow_edit' => !empty($_POST['allow_edit']),
        'dateexpiration' => $dateExpiration,
    ));
    $isSuccess = $shareLink instanceof DocumentShareLink;
}

if (!$isSuccess) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => omoDocumentsUiT('impossible_d_enregistrer_ce_lien'),
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => omoDocumentsUiT($shareId > 0 ? 'share.link_updated' : 'share.link_created'),
    'shareUrl' => $shareLink->buildShareUrl(),
));
