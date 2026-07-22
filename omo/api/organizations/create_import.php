<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
@set_time_limit(0);

$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    http_response_code(401);
    echo json_encode(array('status' => false, 'message' => 'Connexion requise.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_FILES['omo1_export_file']) || !is_array($_FILES['omo1_export_file'])) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Aucun fichier JSON n a ete transmis.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$upload = $_FILES['omo1_export_file'];
if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Le fichier n a pas pu etre televerse.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rawPayload = @file_get_contents((string)($upload['tmp_name'] ?? ''));
if (!is_string($rawPayload) || trim($rawPayload) === '') {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Le fichier d import est vide.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Le fichier d import n est pas un JSON valide.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$availableModules = array('structure', 'members', 'documents', 'projects', 'tasks', 'indicators', 'calendar', 'pv');
$requestedModules = array();
$postedModules = isset($_POST['modules']) && is_array($_POST['modules']) ? $_POST['modules'] : array();
foreach ($availableModules as $module) {
    $requestedModules[$module] = in_array($module, $postedModules, true);
}

$organizationName = trim((string)($_POST['organization_name'] ?? ''));
$result = \dbObject\Organization::importOmo1ExportAsNewOrganization(
    $payload,
    $requestedModules,
    $currentUserId,
    $organizationName
);

if (empty($result['status']) || !($result['organization'] ?? null) instanceof \dbObject\Organization) {
    http_response_code(422);
    echo json_encode(array(
        'status' => false,
        'message' => (string)($result['message'] ?? 'L import de la nouvelle organisation a echoue.'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = $result['organization'];
echo json_encode(array(
    'status' => true,
    'message' => (string)($result['message'] ?? 'La nouvelle organisation a ete importee.'),
    'organizationId' => (int)$organization->getId(),
    'organizationName' => (string)$organization->get('name'),
    'redirect' => commonBuildOrganizationHomeUrl(
        (int)$organization->getId(),
        trim((string)$organization->get('shortname')),
        commonGetRootHost()
    ),
    'stats' => $result['stats'] ?? array(),
    'warnings' => $result['warnings'] ?? array(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
