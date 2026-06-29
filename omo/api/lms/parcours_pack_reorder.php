<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_POST['pid'] ?? 0);
$childParcoursIds = $_POST['child_parcours_ids'] ?? array();
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0 || $parcoursId <= 0) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Acces refuse.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$pack = $managementContext['parcours'] ?? null;
if (
    ($managementContext['link'] ?? null) === null
    || !($pack instanceof \dbObject\Parcours)
    || !$pack->isOwnedByOrganization($organizationId)
    || !$pack->isPack()
) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Pack introuvable.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($managementContext['canEditContent'])) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Vous n avez pas le droit de modifier ce parcours.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!is_array($childParcoursIds)) {
    $childParcoursIds = array();
}

$result = \dbObject\ParcoursParcours::reorderForParent($parcoursId, $childParcoursIds);
if (!is_array($result) || empty($result['status'])) {
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => is_array($result) && !empty($result['message'])
            ? (string)$result['message']
            : 'Impossible de reordonner les parcours du pack.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'status' => true,
    'success' => true,
    'message' => 'Ordre mis a jour.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
