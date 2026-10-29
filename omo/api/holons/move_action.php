<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoHolonsUiT('aucune_organisation_n_est_selectionnee'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoHolonsUiT('l_organisation_demandee_est_introuvable'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = array();
}

$holonId = (int)($_GET['hid'] ?? $_POST['hid'] ?? ($payload['id'] ?? 0));
$targetParentId = (int)($_GET['targetParentId'] ?? $_POST['targetParentId'] ?? ($payload['targetParentId'] ?? 0));

if ($holonId <= 0 || $targetParentId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoHolonsUiT('le_deplacement_demande_est_invalide'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$result = $organization->moveHolonDefinition($holonId, $targetParentId, (int)commonGetCurrentUserId());
if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
        'message' => (string)($result['message'] ?? omoHolonsUiT('l_espace_n_a_pas_pu_etre_deplace')),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['message'] ?? omoHolonsUiT('espace_deplace')),
        'holon' => $result['holon'] ?? null,
        'parent' => $result['parent'] ?? null,
        'previousParent' => $result['previousParent'] ?? null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
