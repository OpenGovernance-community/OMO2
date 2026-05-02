<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

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

$contextHolonId = (int)($_GET['cid'] ?? $_POST['cid'] ?? 0);

$rawPayload = $_POST['payload'] ?? file_get_contents('php://input');
$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => 'La requête envoyée est invalide.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$result = $organization->saveHolonTemplateDefinition($payload, (int)commonGetCurrentUserId(), $contextHolonId);
if (!($result['status'] ?? false)) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => (string)($result['message'] ?? "Le modèle n'a pas pu être enregistré."),
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

echo json_encode(
    array(
        'status' => 'ok',
        'message' => (string)($result['message'] ?? 'Modèle enregistré.'),
        'template' => $result['template'] ?? null,
        'data' => $result['data'] ?? $organization->getHolonTemplateEditorData($contextHolonId),
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
