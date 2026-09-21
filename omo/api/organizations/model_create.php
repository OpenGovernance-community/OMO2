<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/patreon.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$userId = (int)commonGetCurrentUserId();
$modelId = (int)($_POST['model_id'] ?? 0);
$organizationName = trim((string)($_POST['name'] ?? ($_POST['organization_name'] ?? '')));
$definitionOverrides = array_intersect_key($_POST, array_flip(array(
    'name',
    'shortname',
    'domain',
    'color',
    'latlong',
    'interface_level',
    'logo',
    'banner',
)));

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'status' => false, 'message' => 'Connexion requise.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($modelId <= 0) {
    http_response_code(422);
    echo json_encode(array('success' => false, 'status' => false, 'message' => 'Choisissez un modèle public.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!patreonCanManageOrganizationRouting($userId)) {
    unset($definitionOverrides['shortname'], $definitionOverrides['domain']);
}

$result = Organization::createFromPublicModel($modelId, $userId, $organizationName, $definitionOverrides);
if (empty($result['status']) || !($result['organization'] ?? null) instanceof Organization) {
    http_response_code(422);
    echo json_encode(array(
        'success' => false,
        'status' => false,
        'message' => (string)($result['message'] ?? 'La création depuis le modèle a échoué.'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = $result['organization'];
echo json_encode(array(
    'success' => true,
    'status' => true,
    'id' => (int)$organization->getId(),
    'organizationId' => (int)$organization->getId(),
    'mode' => 'create-from-model',
    'message' => (string)($result['message'] ?? 'Organisation créée depuis le modèle public.'),
    'redirect' => commonBuildOrganizationHomeUrl(
        (int)$organization->getId(),
        trim((string)$organization->get('shortname')),
        commonGetRootHost()
    ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
