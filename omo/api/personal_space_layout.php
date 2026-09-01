<?php
require_once __DIR__ . '/bootstrap.php';

use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;
use dbObject\ApplicationSetting;

header('Content-Type: application/json; charset=UTF-8');

$respond = static function ($status, $message = '', array $extra = array(), $statusCode = 200): void {
    http_response_code((int)$statusCode);
    echo json_encode(array_merge(array(
        'status' => (bool)$status,
        'success' => (bool)$status,
        'message' => (string)$message,
    ), $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    $respond(false, 'Cette action doit etre envoyee en POST.', array(), 405);
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $respond(false, 'Contenu invalide.', array(), 400);
}

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($payload['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$holonId = (int)($payload['holon_id'] ?? 0);
$scope = trim((string)($payload['scope'] ?? 'personal'));
$templateKey = UserHolon::normalizeDashboardTemplateKey($payload['template_key'] ?? '');
$csrfToken = trim((string)($payload['csrf_token'] ?? ''));
$expectedCsrfToken = trim((string)($_SESSION['omo_dashboard_layout_csrf'] ?? ''));

if ($currentUserId <= 0) {
    $respond(false, 'Connexion requise.', array(), 401);
}
if ($csrfToken === '' || $expectedCsrfToken === '' || !hash_equals($expectedCsrfToken, $csrfToken)) {
    $respond(false, 'Jeton de securite invalide.', array(), 403);
}
if (!in_array($scope, array('personal', 'personal_reset', 'holon', 'organization', 'application', 'organization_template', 'application_template'), true)) {
    $respond(false, 'Portee d enregistrement invalide.', array(), 400);
}

$organization = new Organization();
$holon = new Holon();
if (!$organization->load($organizationId) || !$holon->load($holonId)) {
    $respond(false, 'Contexte introuvable.', array(), 404);
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$isSiteAdmin = function_exists('commonUserHasSiteAdminOverride') && commonUserHasSiteAdminOverride($currentUserId);
if (
    !($rootHolon instanceof Holon)
    || !$holon->isDescendantOf((int)$rootHolon->getId(), true)
    || (!$holon->canViewDetail() && !$isSiteAdmin)
) {
    $respond(false, 'Contexte inaccessible.', array(), 403);
}

$layout = UserHolon::normalizeDashboardLayout($payload['layout'] ?? null);
$interfaceLevel = $organization->getInterfaceLevel();
$organizationMembership = $organization->getMembership($currentUserId, true);
$isDashboardMember = $organizationMembership !== null;
$isOrganizationAdmin = $isDashboardMember
    && $organizationMembership->isOrganizationAdmin()
    && function_exists('commonCurrentUserIsAdminModeEnabled')
    && commonCurrentUserIsAdminModeEnabled($organizationId);
$isHolonAdmin = UserHolon::isUserHolonAdmin($currentUserId, $holonId);
$canEditDashboard = false;
$canSaveHolonDefault = false;
$canSaveOrganizationDefault = false;
$canSaveApplicationDefault = false;
$canSaveOrganizationTemplateDefault = false;
$canSaveApplicationTemplateDefault = false;
$canResetPersonalLayout = false;

if ($interfaceLevel === Organization::INTERFACE_LEVEL_DISCOVERY) {
    $canEditDashboard = $isOrganizationAdmin || $isSiteAdmin;
    $canSaveOrganizationDefault = $canEditDashboard;
    $canSaveApplicationDefault = $isSiteAdmin;
} elseif ($interfaceLevel === Organization::INTERFACE_LEVEL_AUTONOMOUS) {
    $canSaveHolonDefault = $isHolonAdmin || $isSiteAdmin;
    $canSaveOrganizationTemplateDefault = ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '';
    $canSaveApplicationTemplateDefault = $isSiteAdmin && $templateKey !== '';
    $canEditDashboard = $canSaveHolonDefault || $canSaveOrganizationTemplateDefault;
} else {
    $canEditDashboard = $isDashboardMember || $isSiteAdmin;
    $canResetPersonalLayout = $canEditDashboard;
    $canSaveHolonDefault = $isHolonAdmin || $isOrganizationAdmin || $isSiteAdmin;
    $canSaveOrganizationDefault = $isOrganizationAdmin || $isSiteAdmin;
    $canSaveApplicationDefault = $isSiteAdmin;
    $canSaveOrganizationTemplateDefault = ($isOrganizationAdmin || $isSiteAdmin) && $templateKey !== '';
    $canSaveApplicationTemplateDefault = $isSiteAdmin && $templateKey !== '';
}

if (
    !$canEditDashboard
    || ($scope === 'personal' && $interfaceLevel !== Organization::INTERFACE_LEVEL_EXPERT)
    || ($scope === 'personal_reset' && !$canResetPersonalLayout)
    || ($scope === 'holon' && !$canSaveHolonDefault)
    || ($scope === 'organization' && !$canSaveOrganizationDefault)
    || ($scope === 'application' && !$canSaveApplicationDefault)
    || ($scope === 'organization_template' && !$canSaveOrganizationTemplateDefault)
    || ($scope === 'application_template' && !$canSaveApplicationTemplateDefault)
    || (in_array($scope, array('organization_template', 'application_template'), true)
        && !in_array($templateKey, $holon->getDashboardTemplateLayoutKeys(), true))
) {
    $respond(false, 'Droits administrateur requis.', array(), 403);
}

$saveResult = null;
if ($scope === 'personal') {
    $saveResult = UserHolon::saveDashboardLayoutForUser($currentUserId, $holonId, $layout);
} elseif ($scope === 'personal_reset') {
    $saveResult = UserHolon::clearDashboardLayoutForUser($currentUserId, $holonId);
} else {
    if ($scope === 'holon') {
        $holon->setDashboardDefaultLayout($layout);
        $saveResult = $holon->save();
    } elseif ($scope === 'organization') {
        $organization->setDashboardDefaultLayout($layout);
        $saveResult = $organization->save();
    } elseif ($scope === 'organization_template') {
        $organization->setDashboardTemplateDefaultLayout($templateKey, $layout);
        $saveResult = $organization->save();
    } elseif ($scope === 'application_template') {
        $saveResult = ApplicationSetting::saveDashboardTemplateDefaultLayout($templateKey, $layout);
    } else {
        $saveResult = ApplicationSetting::saveDashboardDefaultLayout($layout);
    }
}
if (!is_array($saveResult) || empty($saveResult['status'])) {
    $respond(false, 'Impossible d enregistrer le tableau de pilotage.', array(), 422);
}

$respond(true, '', array('layout' => $layout, 'scope' => $scope));
