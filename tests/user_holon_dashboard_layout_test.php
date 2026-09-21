<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/holon.class.php';
require_once dirname(__DIR__) . '/class/dbobject/organization.class.php';
require_once dirname(__DIR__) . '/class/dbobject/videoembedhelper.class.php';
require_once dirname(__DIR__) . '/class/dbobject/userholon.class.php';
require_once dirname(__DIR__) . '/class/dbobject/applicationsetting.class.php';
require_once dirname(__DIR__) . '/omo/api/dashboard/modules/registry.php';
require_once dirname(__DIR__) . '/common/application_view_preferences.php';
require_once dirname(__DIR__) . '/common/dashboard_view_preferences.php';

use dbObject\UserHolon;
use dbObject\Holon;
use dbObject\ApplicationSetting;
use dbObject\Organization;

function assertDashboardLayout(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$layout = UserHolon::normalizeDashboardLayout(array(
    array('id' => 'wide', 'type' => 'projects', 'row' => 0, 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 2),
    array('id' => 'collision', 'type' => 'team', 'row' => 0, 'column' => 1, 'rowSpan' => 1, 'columnSpan' => 1),
    array('id' => 'vertical', 'type' => 'stats', 'row' => 1, 'column' => 1, 'rowSpan' => 2, 'columnSpan' => 1),
    array('id' => 'square', 'type' => 'rules', 'row' => 3, 'column' => 0, 'rowSpan' => 2, 'columnSpan' => 2),
    array('id' => 'unknown', 'type' => 'unknown', 'row' => 4, 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 1),
));

assertDashboardLayout(count($layout) === 2, 'Only valid, non-overlapping modules must remain.');
assertDashboardLayout($layout[0]['id'] === 'wide' && $layout[0]['columnSpan'] === 2, 'A horizontal double module must be preserved.');
assertDashboardLayout($layout[1]['id'] === 'vertical' && $layout[1]['rowSpan'] === 2, 'A vertical double module must be preserved.');
assertDashboardLayout($layout[0]['settings']['scope'] === 'contextual', 'Legacy dashboard modules must receive the local scope by default.');
assertDashboardLayout($layout[0]['settings']['audience'] === 'all', 'Legacy audience-aware modules must receive the all-items audience by default.');

$scopedLayout = UserHolon::normalizeDashboardLayout(array(
    array('id' => 'descendants', 'type' => 'projects', 'row' => 0, 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 1, 'settings' => array('scope' => 'descendants', 'audience' => 'mine')),
    array('id' => 'invalid-scope', 'type' => 'rules', 'row' => 0, 'column' => 1, 'rowSpan' => 1, 'columnSpan' => 1, 'settings' => array('scope' => 'unsupported')),
));
assertDashboardLayout($scopedLayout[0]['settings']['scope'] === 'descendants', 'A valid module scope must be stored in the layout.');
assertDashboardLayout($scopedLayout[0]['settings']['audience'] === 'mine', 'A valid module audience must be stored in the layout.');
assertDashboardLayout($scopedLayout[1]['settings']['scope'] === 'contextual', 'An invalid module scope must fall back to the local scope.');
assertDashboardLayout(UserHolon::normalizeDashboardModuleSettings('stats', array('audience' => 'roles'))['audience'] === 'roles', 'The indicators dashboard module must retain the roles audience.');
assertDashboardLayout(UserHolon::normalizeDashboardModuleSettings('activities', array('audience' => 'roles'))['audience'] === 'roles', 'The recurring tasks dashboard module must retain the spaces audience.');
assertDashboardLayout(UserHolon::normalizeDashboardModuleSettings('projects', array('audience' => 'roles'))['audience'] === 'roles', 'The projects dashboard module must retain the spaces audience.');
assertDashboardLayout(UserHolon::normalizeDashboardModuleSettings('checklist', array('audience' => 'roles'))['audience'] === 'roles', 'The checklists dashboard module must retain the spaces audience.');
assertDashboardLayout(UserHolon::normalizeDashboardModuleSettings('stats', array('audience' => 'unsupported'))['audience'] === 'all', 'An invalid module audience must fall back to all items.');
assertDashboardLayout(!isset(UserHolon::getDashboardModuleCatalog()['event']['settings']['audience']), 'The event module keeps its own filtering without a dashboard audience setting.');
assertDashboardLayout(omoDashboardMatchesResponsibleAudience('all', 0, null, 0, 0), 'The all audience must not exclude an item.');
assertDashboardLayout(omoDashboardMatchesResponsibleAudience('mine', 42, null, 42, 1), 'The mine audience must keep directly assigned items.');
assertDashboardLayout(!omoDashboardMatchesResponsibleAudience('roles', 42, null, 42, 1), 'The spaces audience must require an associated space.');
assertDashboardLayout(UserHolon::getDefaultDashboardLayout() === array(), 'The built-in dashboard fallback must be empty.');
assertDashboardLayout(method_exists(ApplicationSetting::class, 'getDashboardGlobalDefaultLayout'), 'The global dashboard default must be readable from application settings.');
assertDashboardLayout(method_exists(ApplicationSetting::class, 'saveDashboardGlobalDefaultLayout'), 'The global dashboard default must be writable through application settings.');
assertDashboardLayout(method_exists(ApplicationSetting::class, 'clearDashboardGlobalDefaultLayout'), 'The global dashboard default must be removable through application settings.');
assertDashboardLayout(array_keys(UserHolon::getDashboardModuleCatalog()) === array_keys(omoDashboardGetModuleDefinitions()), 'The persistence catalog and UI registry must expose the same module identifiers.');
assertDashboardLayout(
    UserHolon::normalizeDashboardModuleSettings('video', array('video' => 'https://youtu.be/dQw4w9WgXcQ'))['video'] === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    'A supported dashboard video URL must be converted to a safe embed URL.'
);
assertDashboardLayout(
    UserHolon::normalizeDashboardModuleSettings('video', array('video' => 'https://example.com/video'))['video'] === '',
    'An unsupported dashboard video URL must not be persisted.'
);

$layoutPriorityFixtures = array(
    'temporary' => array(array('id' => 'temporary')),
    'personal' => array(array('id' => 'personal')),
    'holon' => array(array('id' => 'holon')),
    'organizationTemplate' => array(array('id' => 'organization-template')),
    'organizationModel' => array(array('id' => 'organization-model')),
    'applicationType' => array(array('id' => 'application-type')),
    'global' => array(array('id' => 'global')),
);
assertDashboardLayout(
    omoDashboardViewPreferencesResolveLayout($layoutPriorityFixtures)[0]['id'] === 'temporary',
    'A temporary discovery layout must take priority for the current session.'
);
unset($layoutPriorityFixtures['temporary'], $layoutPriorityFixtures['personal'], $layoutPriorityFixtures['holon']);
assertDashboardLayout(
    omoDashboardViewPreferencesResolveLayout($layoutPriorityFixtures)[0]['id'] === 'organization-template',
    'An organization template layout must take priority over application defaults.'
);
unset($layoutPriorityFixtures['organizationTemplate']);
assertDashboardLayout(
    omoDashboardViewPreferencesResolveLayout($layoutPriorityFixtures)[0]['id'] === 'organization-model',
    'The organization model layout must take priority over application defaults for the organization holon.'
);
assertDashboardLayout(
    omoDashboardViewPreferencesResolveLayout(array('personal' => array(), 'global' => array(array('id' => 'global')))) === array(),
    'An explicitly empty closer layout must stop fallback resolution.'
);
assertDashboardLayout(
    omoDashboardViewPreferencesResolveLayout(array()) === array(),
    'A missing global dashboard layout must resolve to the empty built-in fallback.'
);
$discoveryMemberCapabilities = omoDashboardViewPreferencesResolveCapabilities(
    Organization::INTERFACE_LEVEL_DISCOVERY,
    12,
    '',
    'type:2',
    array('isMember' => true)
);
assertDashboardLayout(
    $discoveryMemberCapabilities['canSaveTemporary']
        && !$discoveryMemberCapabilities['canSavePersonal']
        && !$discoveryMemberCapabilities['canSaveHolon'],
    'A discovery member must only save a temporary session layout.'
);
$discoveryAdminCapabilities = omoDashboardViewPreferencesResolveCapabilities(
    Organization::INTERFACE_LEVEL_DISCOVERY,
    12,
    'template:2:0123456789abcdef01234567',
    'type:2',
    array('isMember' => true, 'isHolonAdmin' => true, 'isOrganizationAdmin' => true, 'isSiteAdmin' => true),
    true
);
assertDashboardLayout(
    $discoveryAdminCapabilities['canSaveTemporary']
        && !$discoveryAdminCapabilities['canSaveHolon']
        && $discoveryAdminCapabilities['canSaveOrganizationTemplate']
        && $discoveryAdminCapabilities['canSaveOrganizationModel']
        && $discoveryAdminCapabilities['canSaveApplicationType']
        && $discoveryAdminCapabilities['canSaveGlobal'],
    'Discovery mode must keep holon changes temporary while preserving higher administrator defaults.'
);
$siteWithoutStructureCapabilities = omoDashboardViewPreferencesResolveCapabilities(
    Organization::INTERFACE_LEVEL_DISCOVERY,
    0,
    '',
    '',
    array('isSiteAdmin' => true)
);
assertDashboardLayout(
    $siteWithoutStructureCapabilities['canSaveGlobal'] && $siteWithoutStructureCapabilities['canEdit'],
    'An active site administrator must edit the global dashboard even without a structure.'
);
$expertAllCapabilities = omoDashboardViewPreferencesResolveCapabilities(
    Organization::INTERFACE_LEVEL_EXPERT,
    12,
    'template:2:0123456789abcdef01234567',
    'type:2',
    array('isMember' => true, 'isHolonAdmin' => true, 'isOrganizationAdmin' => true, 'isSiteAdmin' => true)
);
assertDashboardLayout(
    omoDashboardViewPreferencesGetOrderedSaveScopes($expertAllCapabilities) === array('personal', 'holon', 'organization_template', 'application_type', 'global'),
    'Persistent dashboard save options must be ordered from the closest scope to the global scope.'
);
assertDashboardLayout(
    omoDashboardViewPreferencesGetOrderedSaveScopes(array(
        'canSaveTemporary' => true,
        'canSaveOrganizationTemplate' => true,
        'canSaveOrganizationModel' => true,
        'canSaveApplicationType' => true,
        'canSaveGlobal' => true,
    )) === array('temporary', 'organization_template', 'organization_model', 'application_type', 'global'),
    'Discovery mode must put the temporary session view before administrator defaults.'
);
$_SESSION = array();
omoDashboardViewPreferencesSaveTemporaryLayout(7, 11, 0, array());
assertDashboardLayout(
    omoDashboardViewPreferencesGetTemporaryLayout(7, 11, 0) === array(),
    'A temporary empty discovery layout must be preserved for the current session.'
);
omoDashboardViewPreferencesClearTemporaryLayout(7, 11, 0);
assertDashboardLayout(
    omoDashboardViewPreferencesGetTemporaryLayout(7, 11, 0) === null,
    'Clearing a temporary discovery layout must restore default resolution.'
);

$templateKey = UserHolon::makeDashboardTemplateKey(1, 'Facilitateur');
$templateLayouts = UserHolon::normalizeDashboardTemplateLayouts(array(
    $templateKey => array(),
    'invalid' => UserHolon::getDefaultDashboardLayout(),
));
assertDashboardLayout(UserHolon::normalizeDashboardTemplateKey($templateKey) === $templateKey, 'A template dashboard key must remain stable.');
assertDashboardLayout($templateLayouts === array($templateKey => array()), 'An explicit empty template layout must be preserved.');
assertDashboardLayout(UserHolon::makeDashboardBaseTypeKey(2) === 'type:2', 'A circle base type dashboard key must be stable.');
assertDashboardLayout(UserHolon::makeDashboardBaseTypeKey(9) === '', 'Unsupported holon types must not receive a dashboard base type key.');

$applicationViews = UserHolon::normalizeApplicationViewDefaults(array(
    'projects' => array('scope' => 'descendants', 'view' => 'kanban', 'sort' => 'importance'),
    'invalid' => array('scope' => 'children'),
));
assertDashboardLayout(isset($applicationViews['projects']), 'A known application view must be retained.');
assertDashboardLayout(!isset($applicationViews['invalid']), 'Unknown application views must not be persisted.');
$applicationViewsByType = UserHolon::normalizeApplicationViewBaseTypeDefaults(array(
    'type:2' => $applicationViews,
    'type:9' => $applicationViews,
));
assertDashboardLayout(isset($applicationViewsByType['type:2']['projects']), 'A base holon type application view must be retained.');
assertDashboardLayout(!isset($applicationViewsByType['type:9']), 'An unsupported base holon type application view must not be persisted.');

$preference = new UserHolon();
assertDashboardLayout($preference->getDashboardLayoutPreference() === null, 'An absent personal layout must leave room for default fallbacks.');
$holon = new Holon();
assertDashboardLayout($holon->getDashboardDefaultLayout() === null, 'A holon without a configured layout must not override fallbacks.');
$holon->setDashboardDefaultLayout(array());
assertDashboardLayout($holon->getDashboardDefaultLayout() === array(), 'An empty holon default layout must be preserved as an explicit preference.');
$holon->clearDashboardDefaultLayout();
assertDashboardLayout($holon->getDashboardDefaultLayout() === null, 'Clearing a holon dashboard default must restore fallback resolution.');
assertDashboardLayout($holon->getDashboardDirectTemplateLayoutKey() === '', 'A holon without a direct template must not receive a template layout key.');

$applicationViewContext = array(
    'personalView' => array('scope' => 'children'),
    'defaultView' => array('scope' => 'descendants', 'sort' => 'alpha'),
);
assertDashboardLayout(
    omoApplicationViewPreferencesGetEffectiveView($applicationViewContext) === array('scope' => 'children'),
    'A personal application view must take precedence over its default.'
);
$applicationViewContext['personalView'] = null;
assertDashboardLayout(
    omoApplicationViewPreferencesGetEffectiveView($applicationViewContext) === array('scope' => 'descendants', 'sort' => 'alpha'),
    'An application default must be used when no personal view exists.'
);

$applicationViewLayers = array(
    'temporary' => array('scope' => 'temporary'),
    'personal' => array('scope' => 'personal'),
    'holon' => array('scope' => 'holon'),
    'organizationTemplate' => array('scope' => 'organization-template'),
    'organization' => array('scope' => 'organization'),
    'applicationType' => array('scope' => 'application-type'),
    'global' => array('scope' => 'global'),
);
assertDashboardLayout(
    omoApplicationViewPreferencesResolveView($applicationViewLayers)['scope'] === 'temporary',
    'A temporary discovery filter view must take priority over every persistent scope.'
);
unset($applicationViewLayers['temporary'], $applicationViewLayers['personal'], $applicationViewLayers['holon']);
assertDashboardLayout(
    omoApplicationViewPreferencesResolveView($applicationViewLayers)['scope'] === 'organization-template',
    'An organization template filter view must take priority over the organization-wide view.'
);
assertDashboardLayout(
    omoApplicationViewPreferencesResolveView(array('holon' => array(), 'global' => array('scope' => 'global'))) === array(),
    'An explicitly empty closer filter view must stop fallback resolution.'
);

$discoveryFilterMemberCapabilities = omoApplicationViewPreferencesResolveCapabilities(
    Organization::INTERFACE_LEVEL_DISCOVERY,
    12,
    '',
    'type:2',
    array('isMember' => true, 'isHolonAdmin' => true)
);
assertDashboardLayout(
    $discoveryFilterMemberCapabilities['canSaveTemporary']
        && !$discoveryFilterMemberCapabilities['canSavePersonal']
        && !$discoveryFilterMemberCapabilities['canSaveHolon'],
    'Discovery members and holon administrators must only keep a temporary local filter view.'
);
$expertFilterCapabilities = omoApplicationViewPreferencesResolveCapabilities(
    Organization::INTERFACE_LEVEL_EXPERT,
    12,
    'template:2:0123456789abcdef01234567',
    'type:2',
    array('isMember' => true, 'isHolonAdmin' => true, 'isOrganizationAdmin' => true, 'isSiteAdmin' => true)
);
assertDashboardLayout(
    omoApplicationViewPreferencesGetOrderedSaveScopes($expertFilterCapabilities) === array(
        'personal',
        'holon',
        'organization_template',
        'organization',
        'application_type',
        'global',
    ),
    'Filter save options must be ordered from the member preference to the global default.'
);
$discoveryFilterAdminCapabilities = omoApplicationViewPreferencesResolveCapabilities(
    Organization::INTERFACE_LEVEL_DISCOVERY,
    12,
    'template:2:0123456789abcdef01234567',
    'type:2',
    array('isMember' => true, 'isHolonAdmin' => true, 'isOrganizationAdmin' => true, 'isSiteAdmin' => true)
);
assertDashboardLayout(
    omoApplicationViewPreferencesGetOrderedSaveScopes($discoveryFilterAdminCapabilities) === array(
        'temporary',
        'organization_template',
        'organization',
        'application_type',
        'global',
    ),
    'Discovery mode must keep temporary filters first while preserving broader administrator defaults.'
);

$_SESSION = array();
omoApplicationViewPreferencesSaveTemporaryView(7, 11, 12, 'projects', array('scope' => 'children'));
assertDashboardLayout(
    omoApplicationViewPreferencesGetTemporaryView(7, 11, 12, 'projects') === array('scope' => 'children'),
    'A temporary filter view must be retained for the current session and context.'
);
omoApplicationViewPreferencesClearTemporaryView(7, 11, 12, 'projects');
assertDashboardLayout(
    omoApplicationViewPreferencesGetTemporaryView(7, 11, 12, 'projects') === null,
    'Clearing a temporary filter view must restore persistent resolution.'
);

$holon->setApplicationViewDefault('projects', array('scope' => 'children'));
assertDashboardLayout(
    $holon->getApplicationViewDefault('projects') === array('scope' => 'children'),
    'A holon filter default must be stored in holon parameters.'
);
$holon->clearApplicationViewDefault('projects');
assertDashboardLayout($holon->getApplicationViewDefault('projects') === null, 'A holon filter default must be removable.');
$organization = new class extends Organization {
    public function canViewDetail()
    {
        return true;
    }
};
$organization->setApplicationViewDefault('projects', array('scope' => 'descendants'));
assertDashboardLayout(
    $organization->getApplicationViewDefault('projects') === array('scope' => 'descendants'),
    'An organization-wide filter default must be stored in organization parameters.'
);
$organization->clearApplicationViewDefault('projects');
assertDashboardLayout($organization->getApplicationViewDefault('projects') === null, 'An organization-wide filter default must be removable.');
$organization->setDashboardOrganizationDefaultLayout(array());
assertDashboardLayout(
    $organization->getDashboardOrganizationDefaultLayout() === array(),
    'An empty organization model dashboard layout must be preserved as an explicit preference.'
);
$organization->clearDashboardOrganizationDefaultLayout();
assertDashboardLayout(
    $organization->getDashboardOrganizationDefaultLayout() === null,
    'Clearing the organization model dashboard layout must restore fallback resolution.'
);
assertDashboardLayout(method_exists(ApplicationSetting::class, 'saveApplicationViewGlobalDefault'), 'A global filter default must be writable.');
assertDashboardLayout(method_exists(ApplicationSetting::class, 'clearApplicationViewGlobalDefault'), 'A global filter default must be removable.');

echo "user_holon_dashboard_layout_test: OK\n";
