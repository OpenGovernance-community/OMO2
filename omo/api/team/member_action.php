<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Holon;
use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$holonId = (int)($_POST['hid'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));

if ($organizationId <= 0 || $holonId <= 0 || $userId <= 0 || $action === '') {
    http_response_code(400);
    echo json_encode(array(
        'status' => false,
        'message' => 'Action membre invalide.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = new Organization();
$holon = new Holon();

if (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => 'Contexte introuvable.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$holon->canEdit()) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'message' => "Vous n'avez pas le droit de modifier ce contexte.",
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

switch ($action) {
    case 'remove':
        $result = $holon->removeMember($userId, array(
            'organizationId' => $organizationId,
        ));
        break;

    case 'grant_admin':
        $result = $holon->setMemberContextAdmin($userId, true, $organizationId);
        break;

    case 'revoke_admin':
        $result = $holon->setMemberContextAdmin($userId, false, $organizationId);
        break;

    default:
        $result = array(
            'status' => false,
            'message' => 'Action inconnue.',
        );
        break;
}

if (!($result['status'] ?? false)) {
    http_response_code(422);
}

echo json_encode(array(
    'status' => (bool)($result['status'] ?? false),
    'message' => (string)($result['message'] ?? 'Action terminée.'),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
