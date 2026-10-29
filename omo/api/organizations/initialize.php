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
            'message' => omoOrganizationsUiT('aucune_organisation_n_est_selectionnee'),
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
            'message' => omoOrganizationsUiT('l_organisation_demandee_est_introuvable'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoOrganizationsUiT('la_requete_envoyee_est_invalide'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$templateId = (int)($payload['templateId'] ?? 0);
$result = $organization->initializeStructure((int)commonGetCurrentUserId(), $templateId);
if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => (string)($result['message'] ?? omoOrganizationsUiT('l_initialisation_de_l_organisation_a_echoue')),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$rootHolon = $result['rootHolon'] ?? null;

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['message'] ?? omoOrganizationsUiT('organisation_initialisee')),
        'rootHolon' => $rootHolon
            ? array(
                'id' => (int)$rootHolon->getId(),
                'name' => $rootHolon->getDisplayName(),
            )
            : null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
