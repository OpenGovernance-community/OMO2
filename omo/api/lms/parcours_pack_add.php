<?php
require_once __DIR__ . '/ui_translations.php';
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
        'message' => omoLmsUiT('acces_refuse'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$link = $managementContext['link'] ?? null;
$pack = $managementContext['parcours'] ?? null;
$childParcours = new \dbObject\Parcours();

if ($link === null || !($pack instanceof \dbObject\Parcours) || !$pack->isOwnedByOrganization($organizationId) || !$pack->isPack()) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => omoLmsUiT('pack_introuvable'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($managementContext['canEditContent'])) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => omoLmsUiT('vous_n_avez_pas_le_droit_de_modifier_ce_parcours'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$childParcours->load($childParcoursId) || !$childParcours->isOwnedByOrganization($organizationId) || $childParcours->isPack()) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => omoLmsUiT('parcours_enfant_introuvable_ou_non_autorise'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$result = \dbObject\ParcoursParcours::attachChildToParent($parcoursId, $childParcoursId);
if (!is_array($result) || empty($result['status'])) {
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => is_array($result) && !empty($result['message'])
            ? (string)$result['message']
            : 'Impossible d ajouter ce parcours au pack.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'status' => true,
    'success' => true,
    'message' => !empty($result['created']) ? 'Parcours ajoute au pack.' : 'Parcours deja present dans ce pack.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
