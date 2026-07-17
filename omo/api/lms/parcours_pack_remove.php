<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_POST['pid'] ?? 0);
$childParcoursId = (int)($_POST['child_parcours_id'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0 || $parcoursId <= 0 || $childParcoursId <= 0) {
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

$result = \dbObject\ParcoursParcours::detachChildFromParent($parcoursId, $childParcoursId);
if (!is_array($result) || empty($result['status'])) {
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => is_array($result) && !empty($result['message'])
            ? (string)$result['message']
            : 'Impossible de retirer ce parcours du pack.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'status' => true,
    'success' => true,
    'message' => 'Parcours retire du pack.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
