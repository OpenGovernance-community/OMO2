<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/holon_scope_helper.php';

header('Content-Type: application/json; charset=UTF-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_POST['oid'] ?? $_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$requestedHolonId = isset($_POST['IDholon']) && is_numeric($_POST['IDholon'])
    ? (int)$_POST['IDholon']
    : 0;
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if ($currentUserId <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'message' => 'Connexion requise.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($organizationId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => 'Organisation invalide.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => 'Organisation introuvable.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($requestedHolonId > 0) {
    $selectedHolon = commonHolonScopeResolveOrganizationHolon($organization, $requestedHolonId);
    if (!$selectedHolon instanceof \dbObject\Holon) {
        http_response_code(422);
        echo json_encode([
            'status' => false,
            'message' => 'Le holon selectionne est invalide.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$tension = new \dbObject\Tension();
$saveResult = $tension->createInOrganizationContext(
    $organizationId,
    $requestedHolonId > 0 ? $requestedHolonId : null,
    $currentUserId,
    [
        'title' => $title,
        'description' => $description,
    ]
);

if (!is_array($saveResult) || empty($saveResult['status'])) {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => (string)($saveResult['text'] ?? 'Impossible d enregistrer la tension.'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'status' => true,
    'message' => 'Tension enregistree.',
    'id' => (int)$tension->getId(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
