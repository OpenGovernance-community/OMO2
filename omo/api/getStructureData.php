<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

use dbObject\ArrayOrganization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Organisation invalide.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$organizations = new ArrayOrganization();
$organizations->load(
    array(
        'where' => array(
            array('field' => 'id', 'value' => $organizationId),
        ),
        'limit' => 1,
    )
);

$organization = $organizations->get($organizationId);
if ($organization === null) {
    http_response_code(404);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Organisation introuvable.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$root = $organization->getStructuralRootHolon();
if ($root === null) {
    http_response_code(404);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Aucun holon racine de type organisation n'a ete trouve pour cette organisation.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

echo $root->toRepresentationJson(array(
    'representation' => 'circle',
    'includeMemberUserIds' => true,
    'organizationId' => $organizationId,
));
