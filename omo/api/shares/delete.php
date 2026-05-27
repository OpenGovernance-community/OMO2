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

$organizationId = (int)$context['organizationId'];
$currentHolon = $context['currentHolon'];
$shareLinkId = isset($_POST['share_id']) && is_numeric($_POST['share_id']) ? (int)$_POST['share_id'] : 0;

if ($shareLinkId <= 0) {
    http_response_code(400);
    echo json_encode(array(
        'status' => false,
        'message' => 'Lien de partage invalide.',
    ));
    exit;
}

$shareLink = HolonShareLink::findByIdForContext($shareLinkId, $organizationId, (int)$currentHolon->getId(), true);
if (!$shareLink || !(bool)$shareLink->get('active')) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => 'Lien de partage introuvable.',
    ));
    exit;
}

$shareLink->set('active', 0);
$result = $shareLink->save();
if (empty($result['status'])) {
    http_response_code(500);
    echo json_encode(array(
        'status' => false,
        'message' => 'Impossible de supprimer le lien de partage.',
    ));
    exit;
}

echo json_encode(array(
    'status' => true,
    'message' => 'Lien supprime.',
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
