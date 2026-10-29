<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
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

if ((int)commonGetCurrentUserId() <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoOrganizationsUiT('vous_n_avez_pas_les_droits_pour_importer_cette_organisation'),
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

if (!isset($_FILES['structure_file']) || !is_array($_FILES['structure_file'])) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoOrganizationsUiT('aucun_fichier_n_a_ete_transmis'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$upload = $_FILES['structure_file'];
if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoOrganizationsUiT('le_fichier_n_a_pas_pu_etre_televerse_2'),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$archive = ['files' => []];
try {
    $archive = \dbObject\OrganizationArchive::read((string)$upload['tmp_name'], true);
    $payload = $archive['payload'];
    if (($_POST['roleplay'] ?? '') === '1') { $payload = \dbObject\OrganizationTransferDates::shift($payload); }
} catch (\Throwable $exception) {
    \dbObject\OrganizationArchive::cleanup($archive['files']);
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
$result = $organization->importStructure($payload, (int)commonGetCurrentUserId());
if (!($result['status'] ?? false)) {
    \dbObject\OrganizationArchive::cleanup($archive['files']);
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => (string)($result['message'] ?? omoOrganizationsUiT('l_import_de_l_organisation_a_echoue')),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$rootHolon = $result['rootHolon'] ?? null;

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['message'] ?? omoOrganizationsUiT('l_organisation_a_ete_importee')),
        'rootHolon' => $rootHolon
            ? array(
                'id' => (int)$rootHolon->getId(),
                'name' => $rootHolon->getDisplayName(),
            )
            : null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
