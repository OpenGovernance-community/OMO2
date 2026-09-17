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
if (!in_array($scope, array('temporary', 'temporary_reset', 'personal', 'personal_reset', 'holon', 'holon_reset', 'organization_template', 'organization_template_reset', 'application_type', 'application_type_reset', 'global', 'global_reset'), true)) {
    $respond(false, 'Portee d enregistrement invalide.', array(), 400);
}

$organization = new Organization();
$holon = null;
if (!$organization->load($organizationId)) {
    $respond(false, 'Contexte introuvable.', array(), 404);
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$isSiteAdmin = function_exists('commonUserHasSiteAdminOverride') && commonUserHasSiteAdminOverride($currentUserId);
if ($holonId > 0) {
    $candidateHolon = new Holon();
    if (
        !$candidateHolon->load($holonId)
        || !($rootHolon instanceof Holon)
        || !$candidateHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || (!$candidateHolon->canViewDetail() && !$isSiteAdmin)
    ) {
        $respond(false, 'Contexte inaccessible.', array(), 403);
    }
    $holon = $candidateHolon;
} elseif (!in_array($scope, array('temporary', 'temporary_reset', 'global', 'global_reset'), true)) {
    $respond(false, 'Contexte introuvable.', array(), 404);
}

$layout = UserHolon::normalizeDashboardLayout($payload['layout'] ?? null);
$availableModuleScopes = $holon instanceof Holon
    ? omoApiGetAvailableContextScopes(true, $holon, $rootHolon)
    : array('contextual');
foreach ($layout as &$module) {
    $moduleType = trim((string)($module['type'] ?? ''));
    $moduleCatalogItem = UserHolon::getDashboardModuleCatalog()[$moduleType] ?? array();
    if (empty($moduleCatalogItem['settings']['scope'])) {
        continue;
    }
    $module['settings']['scope'] = omoApiNormalizeContextScope(
        $module['settings']['scope'] ?? 'contextual',
        $availableModuleScopes
    );
}
unset($module);
$dashboardAccess = omoDashboardViewPreferencesGetAccess($currentUserId, $organization, $holon);
$interfaceLevel = (int)$dashboardAccess['interfaceLevel'];
$canEditDashboard = !empty($dashboardAccess['canEdit']);
$canSaveTemporary = !empty($dashboardAccess['canSaveTemporary']);
$canSavePersonalLayout = !empty($dashboardAccess['canSavePersonal']);
$canSaveHolonDefault = !empty($dashboardAccess['canSaveHolon']);
$canSaveOrganizationTemplateDefault = !empty($dashboardAccess['canSaveOrganizationTemplate']);
$canSaveApplicationBaseTypeDefault = !empty($dashboardAccess['canSaveApplicationType']);
$canSaveGlobalDefault = !empty($dashboardAccess['canSaveGlobal']);
$canResetTemporaryLayout = $canSaveTemporary;
$canResetPersonalLayout = $canSavePersonalLayout;
$canResetHolonDefault = $canSaveHolonDefault;
$canResetOrganizationTemplateDefault = $canSaveOrganizationTemplateDefault;
$canResetApplicationBaseTypeDefault = $canSaveApplicationBaseTypeDefault;
$directTemplateKey = $holon instanceof Holon ? $holon->getDashboardDirectTemplateLayoutKey() : '';
$baseTypeKey = $holon instanceof Holon ? $holon->getDashboardBaseTypeLayoutKey() : '';

if (
    !$canEditDashboard
    || ($scope === 'temporary' && ($interfaceLevel !== Organization::INTERFACE_LEVEL_DISCOVERY || !$canSaveTemporary))
    || ($scope === 'temporary_reset' && ($interfaceLevel !== Organization::INTERFACE_LEVEL_DISCOVERY || !$canResetTemporaryLayout))
    || ($scope === 'personal' && ($interfaceLevel === Organization::INTERFACE_LEVEL_DISCOVERY || !$canSavePersonalLayout))
    || ($scope === 'personal_reset' && !$canResetPersonalLayout)
    || ($scope === 'holon' && !$canSaveHolonDefault)
    || ($scope === 'holon_reset' && !$canResetHolonDefault)
    || ($scope === 'organization_template' && !$canSaveOrganizationTemplateDefault)
    || ($scope === 'organization_template_reset' && !$canResetOrganizationTemplateDefault)
    || ($scope === 'application_type' && !$canSaveApplicationBaseTypeDefault)
    || ($scope === 'application_type_reset' && !$canResetApplicationBaseTypeDefault)
    || ($scope === 'global' && !$canSaveGlobalDefault)
    || ($scope === 'global_reset' && !$canSaveGlobalDefault)
    || (in_array($scope, array('organization_template', 'organization_template_reset'), true) && $templateKey !== $directTemplateKey)
) {
    $respond(false, 'Droits administrateur requis.', array(), 403);
}

$saveResult = null;
if ($scope === 'temporary') {
    $saveResult = omoDashboardViewPreferencesSaveTemporaryLayout($currentUserId, $organizationId, $holonId, $layout);
} elseif ($scope === 'temporary_reset') {
    $saveResult = omoDashboardViewPreferencesClearTemporaryLayout($currentUserId, $organizationId, $holonId);
} elseif ($scope === 'personal') {
    $saveResult = UserHolon::saveDashboardLayoutForUser($currentUserId, $holonId, $layout);
} elseif ($scope === 'personal_reset') {
    $saveResult = UserHolon::clearDashboardLayoutForUser($currentUserId, $holonId);
} else {
    if ($scope === 'holon') {
        $holon->setDashboardDefaultLayout($layout);
        $saveResult = $holon->save();
    } elseif ($scope === 'holon_reset') {
        $holon->clearDashboardDefaultLayout();
        $saveResult = $holon->save();
    } elseif ($scope === 'organization_template') {
        $organization->setDashboardTemplateDefaultLayout($templateKey, $layout);
        $saveResult = $organization->save();
    } elseif ($scope === 'organization_template_reset') {
        $organization->clearDashboardTemplateDefaultLayout($templateKey);
        $saveResult = $organization->save();
    } elseif ($scope === 'application_type') {
        $saveResult = ApplicationSetting::saveDashboardBaseTypeDefaultLayout((int)$holon->get('IDtypeholon'), $layout);
    } elseif ($scope === 'application_type_reset') {
        $saveResult = ApplicationSetting::clearDashboardBaseTypeDefaultLayout((int)$holon->get('IDtypeholon'));
    } elseif ($scope === 'global') {
        $saveResult = ApplicationSetting::saveDashboardGlobalDefaultLayout($layout);
    } elseif ($scope === 'global_reset') {
        $saveResult = ApplicationSetting::clearDashboardGlobalDefaultLayout();
    }
}
if (!is_array($saveResult) || empty($saveResult['status'])) {
    $respond(false, 'Impossible d enregistrer le tableau de pilotage.', array(), 422);
}

$respond(true, '', array('layout' => $layout, 'scope' => $scope));
