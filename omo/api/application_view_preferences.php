<?php
require_once __DIR__ . '/bootstrap.php';

use dbObject\ApplicationSetting;
use dbObject\Document;
use dbObject\DocumentApplicationTab;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;

header('Content-Type: application/json; charset=UTF-8');

$respond = static function ($status, $message = '', array $extra = array(), $statusCode = 200): void {
    http_response_code((int)$statusCode);
    echo json_encode(array_merge(array('status' => (bool)$status, 'message' => (string)$message), $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
$applicationKey = UserHolon::normalizeApplicationViewKey($payload['application'] ?? '');
$organizationId = (int)($payload['organizationId'] ?? 0);
$holonId = (int)($payload['holonId'] ?? 0);
$pvApplicationTabId = (int)($payload['pvApplicationTabId'] ?? 0);
$scope = trim((string)($payload['scope'] ?? ''));
$operation = trim((string)($payload['operation'] ?? 'save'));
$csrfToken = trim((string)($payload['csrfToken'] ?? ''));
$expectedCsrfToken = trim((string)($_SESSION['omo_application_view_preferences_csrf'] ?? ''));

if ($currentUserId <= 0) {
    $respond(false, 'Connexion requise.', array(), 401);
}
if ($applicationKey === '' || !in_array($scope, array('temporary', 'personal', 'holon', 'organization_template', 'organization', 'application_type', 'global'), true) || !in_array($operation, array('save', 'clear'), true)) {
    $respond(false, 'Configuration de vue invalide.', array(), 400);
}
if ($csrfToken === '' || $expectedCsrfToken === '' || !hash_equals($expectedCsrfToken, $csrfToken)) {
    $respond(false, 'Jeton de securite invalide.', array(), 403);
}

$organization = new Organization();
if ($organizationId <= 0 || !$organization->load($organizationId) || !$organization->canViewDetail()) {
    $respond(false, 'Organisation introuvable.', array(), 404);
}

$holon = null;
if ($holonId > 0) {
    $candidate = new Holon();
    $rootHolon = $organization->getEnabledStructuralRootHolon();
    if (
        !($rootHolon instanceof Holon)
        || !$candidate->load($holonId)
        || !$candidate->isDescendantOf((int)$rootHolon->getId(), true)
        || !$candidate->canViewDetail()
    ) {
        $respond(false, 'Holon introuvable.', array(), 404);
    }
    $holon = $candidate;
}

$typeId = $holon instanceof Holon ? (int)$holon->get('IDtypeholon') : 0;
$templateKey = $holon instanceof Holon ? $holon->getDashboardDirectTemplateLayoutKey() : '';
$view = UserHolon::normalizeApplicationView($payload['view'] ?? array());

if ($pvApplicationTabId > 0) {
    $tab = new DocumentApplicationTab();
    $document = new Document();
    if (
        $scope !== 'personal'
        || !$tab->load($pvApplicationTabId)
        || !$tab->matchesApplicationKey($applicationKey)
        || !$document->load((int)$tab->get('IDdocument'))
        || (int)$document->get('IDorganization') !== $organizationId
        || !$document->canUserManagePvDocument($currentUserId)
    ) {
        $respond(false, 'Configuration de reunion indisponible.', array(), 403);
    }

    $result = $tab->saveView($operation === 'clear' ? array() : $view);
    if (!is_array($result) || empty($result['status'])) {
        $respond(false, 'Impossible d enregistrer cette vue de reunion.', array(), 422);
    }

    $respond(true, '', array(
        'scope' => $scope,
        'operation' => $operation,
        'view' => $operation === 'clear' ? array() : $view,
        'pvApplicationTabId' => $pvApplicationTabId,
    ));
}

$access = omoApplicationViewPreferencesGetAccess($currentUserId, $organization, $holon);
$scopePermissions = array(
    'temporary' => 'canSaveTemporary',
    'personal' => 'canSavePersonal',
    'holon' => 'canSaveHolon',
    'organization_template' => 'canSaveOrganizationTemplate',
    'organization' => 'canSaveOrganization',
    'application_type' => 'canSaveApplicationType',
    'global' => 'canSaveGlobal',
);
if (empty($access[$scopePermissions[$scope]])) {
    $respond(false, 'Droits insuffisants pour cette portee.', array(), 403);
}

if ($scope === 'temporary') {
    $result = $operation === 'clear'
        ? omoApplicationViewPreferencesClearTemporaryView($currentUserId, $organizationId, $holonId, $applicationKey)
        : omoApplicationViewPreferencesSaveTemporaryView($currentUserId, $organizationId, $holonId, $applicationKey, $view);
} elseif ($scope === 'personal') {
    $result = $operation === 'clear'
        ? UserHolon::clearApplicationViewForUser($currentUserId, $holonId, $applicationKey)
        : UserHolon::saveApplicationViewForUser($currentUserId, $holonId, $applicationKey, $view);
} elseif ($scope === 'holon') {
    if ($operation === 'clear') {
        $holon->clearApplicationViewDefault($applicationKey);
    } else {
        $holon->setApplicationViewDefault($applicationKey, $view);
    }
    $result = $holon->save();
} elseif ($scope === 'organization_template') {
    if ($operation === 'clear') {
        $organization->clearApplicationViewTemplateDefault($applicationKey, $templateKey);
    } else {
        $organization->setApplicationViewTemplateDefault($applicationKey, $templateKey, $view);
    }
    $result = $organization->save();
} elseif ($scope === 'organization') {
    if ($operation === 'clear') {
        $organization->clearApplicationViewDefault($applicationKey);
    } else {
        $organization->setApplicationViewDefault($applicationKey, $view);
    }
    $result = $organization->save();
} elseif ($scope === 'application_type') {
    $result = $operation === 'clear'
        ? ApplicationSetting::clearApplicationViewBaseTypeDefault($applicationKey, $typeId)
        : ApplicationSetting::saveApplicationViewBaseTypeDefault($applicationKey, $typeId, $view);
} else {
    $result = $operation === 'clear'
        ? ApplicationSetting::clearApplicationViewGlobalDefault($applicationKey)
        : ApplicationSetting::saveApplicationViewGlobalDefault($applicationKey, $view);
}

if (!is_array($result) || empty($result['status'])) {
    $respond(false, 'Impossible d enregistrer cette vue par defaut.', array(), 422);
}

$respond(true, '', array('scope' => $scope, 'operation' => $operation, 'view' => $view));
