<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => "Aucune organisation n'est sélectionnée.",
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
            'message' => "L'organisation demandée est introuvable.",
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
if ($holonId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => 'Le holon à supprimer est invalide.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$result = $organization->deleteHolonDefinition($holonId, (int)commonGetCurrentUserId());
if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => (string)($result['message'] ?? "Le holon n'a pas pu être supprimé."),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['message'] ?? 'Holon supprimé.'),
        'holon' => $result['holon'] ?? null,
        'parent' => $result['parent'] ?? null,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
