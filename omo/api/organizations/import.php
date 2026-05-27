<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => "Aucune organisation n'est selectionnee.",
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
            'message' => "Vous n'avez pas les droits pour importer cette organisation.",
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
            'message' => "L'organisation demandee est introuvable.",
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
            'message' => "Aucun fichier n'a ete transmis.",
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
            'message' => "Le fichier n'a pas pu etre televerse.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$rawPayload = @file_get_contents((string)($upload['tmp_name'] ?? ''));
if (!is_string($rawPayload) || trim($rawPayload) === '') {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => "Le fichier d'import est vide.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => "Le fichier d'import n'est pas un JSON valide.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$result = $organization->importStructure($payload, (int)commonGetCurrentUserId());
if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => (string)($result['message'] ?? "L'import de l'organisation a echoue."),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$rootHolon = $result['rootHolon'] ?? null;

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['message'] ?? "L'organisation a ete importee."),
        'rootHolon' => $rootHolon
            ? array(
                'id' => (int)$rootHolon->getId(),
                'name' => $rootHolon->getDisplayName(),
            )
            : null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
