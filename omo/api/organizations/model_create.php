<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$userId = (int)commonGetCurrentUserId();
$modelId = (int)($_POST['model_id'] ?? 0);
$organizationName = trim((string)($_POST['organization_name'] ?? ''));

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(array('status' => false, 'message' => 'Connexion requise.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($modelId <= 0) {
    http_response_code(422);
    echo json_encode(array('status' => false, 'message' => 'Choisissez un modele public.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$result = Organization::createFromPublicModel($modelId, $userId, $organizationName);
if (empty($result['status']) || !($result['organization'] ?? null) instanceof Organization) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => (string)($result['message'] ?? 'La creation depuis le modele a echoue.'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = $result['organization'];
echo json_encode(array(
    'status' => true,
    'message' => (string)($result['message'] ?? 'Organisation creee depuis le modele public.'),
    'redirect' => commonBuildOrganizationHomeUrl(
        (int)$organization->getId(),
        trim((string)$organization->get('shortname')),
        commonGetRootHost()
    ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
