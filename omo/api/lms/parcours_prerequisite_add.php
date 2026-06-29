<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_POST['pid'] ?? 0);
$requiredParcoursId = (int)($_POST['required_parcours_id'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0 || $parcoursId <= 0 || $requiredParcoursId <= 0) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Acces refuse.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$parcours = $managementContext['parcours'] ?? null;
$requiredParcours = new \dbObject\Parcours();
$requiredParcoursLink = null;
if (
    ($managementContext['link'] ?? null) === null
    || !($parcours instanceof \dbObject\Parcours)
    || !$parcours->isOwnedByOrganization($organizationId)
) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Parcours introuvable.',
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

if (
    !$requiredParcours->load($requiredParcoursId)
    || !($requiredParcoursLink = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $requiredParcoursId))
    || $requiredParcours->isPack()
) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Parcours prerequis introuvable ou non autorise.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$result = \dbObject\ParcoursPrerequisite::attachPrerequisite($parcoursId, $requiredParcoursId);
if (!is_array($result) || empty($result['status'])) {
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => is_array($result) && !empty($result['message'])
            ? (string)$result['message']
            : 'Impossible d ajouter ce prerequis.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'status' => true,
    'success' => true,
    'message' => !empty($result['created']) ? 'Prerequis ajoute.' : 'Prerequis deja present.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
