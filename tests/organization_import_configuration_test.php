<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/application_view_preferences.php';

use dbObject\{DbObject, PdoDbhCompat, Organization, OrganizationExport, OrganizationApplication, OrganizationTransferConfiguration, Holon, UserHolon, UserOrganization, ArrayUser, ApplicationSetting};

function configCheck(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
function configSave(DbObject $object): void
{
    configCheck(!empty($object->save()['status']), 'Fixture save: ' . get_class($object));
}

// Run the real import transactions inside one rollback-only outer transaction.
// No fixture (including memberships, histories and application settings) is committed.
class ConfigurationRollbackConnection
{
    public function __construct(private PDO $pdo) {}
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function __call(string $name, array $args) { return $this->pdo->$name(...$args); }
}

function configFixture(string $name, int $actor): array
{
    $organization = new Organization();
    $organization->set('name', $name);
    $organization->set('isModel', true);
    configSave($organization);
    $member = new UserOrganization();
    $member->set('IDorganization', $organization->getId());
    $member->set('IDuser', $actor);
    $member->set('active', true);
    $member->set('parameters', ['isAdmin' => true]);
    configSave($member);
    $organization->load($organization->getId(), true);
    $root = new Holon();
    foreach (['name' => $name, 'IDtypeholon' => 4, 'IDorganization' => $organization->getId(), 'active' => true, 'visible' => true] as $key => $value) { $root->set($key, $value); }
    configSave($root);
    $template = new Holon();
    foreach (['name' => $name . ' role', 'templatename' => $name . ' template', 'IDtypeholon' => 1, 'IDholon_parent' => $root->getId(), 'IDholon_org' => $root->getId(), 'active' => true, 'visible' => false] as $key => $value) { $template->set($key, $value); }
    configSave($template);
    $instance = new Holon();
    foreach (['name' => 'Instance', 'IDtypeholon' => 1, 'IDholon_parent' => $root->getId(), 'IDholon_org' => $root->getId(), 'IDholon_template' => $template->getId(), 'active' => true, 'visible' => true] as $key => $value) { $instance->set($key, $value); }
    configSave($instance);
    return [$organization, $root, $template, $instance];
}

$pdo = DbObject::getPdo();
$originalDbh = DbObject::$_dbh;
$pdo->beginTransaction();
DbObject::$_dbh = new PdoDbhCompat(new ConfigurationRollbackConnection($pdo));
try {
    $users = new ArrayUser();
    $users->load(['limit' => 1]);
    $actor = (int)iterator_to_array($users, false)[0]->getId();
    $_SESSION['currentUser'] = $actor;
    [$source, $root, $template, $instance] = configFixture('Archive config fixture', $actor);
    [$model, $modelRoot, $modelTemplate] = configFixture('Mapped config fixture', $actor);
    $view = ['scope' => 'descendants', 'view' => 'list'];
    $modelView = ['scope' => 'contextual', 'view' => 'cards'];
    $layout = [['id' => 'projects', 'type' => 'projects', 'row' => 0, 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 2]];
    foreach ([[$source, $template, 'Archive role', $view, $layout, false], [$model, $modelTemplate, 'Model role', $modelView, [], true]] as [$org, $tpl, $label, $appView, $dashboard, $active]) {
        $lexicon = $org->getLexicon();
        $lexicon['role']['label'] = $label;
        $lexicon['type3'] = ['label' => $label . ' property', 'enabled' => true];
        $org->setLexicon($lexicon);
        $templateKey = UserHolon::makeDashboardTemplateKey(1, $tpl->get('templatename'));
        $org->setApplicationViewTemplateDefault('projects', $templateKey, $appView);
        $org->setDashboardTemplateDefaultLayout($templateKey, $dashboard);
        $org->setApplicationViewDefault('documents', $appView);
        $org->setDashboardOrganizationDefaultLayout($dashboard);
        $parameters = $org->getParametersArray();
        $parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER] = ['type:2' => ['projects' => $appView]];
        $parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER] = ['type:2' => $dashboard];
        $org->setParametersArray($parameters);
        configSave($org);
        $tpl->setApplicationViewDefault('projects', $appView);
        $tpl->setDashboardDefaultLayout($dashboard);
        configSave($tpl);
        $link = OrganizationApplication::ensureByOrganizationAndDirectory((int)$org->getId(), 'projects');
        configCheck($link !== null, 'Projects catalog fixture');
        $link->set('active', $active);
        $link->set('position', $active ? 17 : 29);
        $link->setParametersArray(['fixture' => ['enabled' => $active, 'label' => $label]]);
        configSave($link);
    }
    $instance->setApplicationViewDefault('projects', ['view' => 'archive-local']);
    $instance->setDashboardDefaultLayout($layout);
    configSave($instance);
    $root->setDashboardDefaultLayout($layout);
    configSave($root);
    $modelRoot->setDashboardDefaultLayout([]);
    configSave($modelRoot);
    $disabledModelApp = OrganizationApplication::ensureByOrganizationAndDirectory((int)$model->getId(), 'documents');
    $disabledModelApp->set('active', false);
    $disabledModelApp->setParametersArray(['fixture' => 'disabled-model-app']);
    configSave($disabledModelApp);
    configCheck(!empty(ApplicationSetting::saveApplicationViewBaseTypeDefault('projects', 3, $view)['status']), 'Global type view fixture');
    configCheck(!empty(ApplicationSetting::saveDashboardBaseTypeDefaultLayout(3, [])['status']), 'Global type dashboard fixture');
    $selected = array_fill_keys(OrganizationExport::MODULES, false);
    $selected['structure'] = true;
    $payload = json_decode(json_encode(OrganizationExport::build($source, $selected)), true);
    configCheck(isset($payload['configuration']), 'Export includes configuration');
    $sourceConfig = $payload['configuration'];
    $modelConfig = OrganizationTransferConfiguration::capture($model);

    foreach (['omo1', 'omo2', 'future-adapter'] as $system) {
        foreach ([false, true] as $mapped) {
            $payload['source']['system'] = $system;
            $calibration = $mapped ? ['templateRootHolonId' => $modelRoot->getId(), 'mappings' => [$template->getId() => $modelTemplate->getId()]] : [];
            $result = Organization::importOmo1ExportAsNewOrganization($payload, $selected, $actor, 'Configuration import fixture', $calibration, ['sendMemberInvitationEmails' => false]);
            configCheck(!empty($result['status']), 'Import ' . $system . ': ' . ($result['message'] ?? 'failed'));
            $target = $result['organization'];
            $target->load($target->getId(), true);
            $expected = $mapped ? $modelConfig : $sourceConfig;
            configCheck(OrganizationTransferConfiguration::capture($target) == $expected, 'Configuration priority ' . $system . ' mapped=' . (int)$mapped);
            configCheck($target->getApplicationViewBaseTypeDefault('projects', 2) === ($mapped ? $modelView : $view), 'Type app default');
            configCheck($target->getDashboardBaseTypeDefaultLayout(2) === ($mapped ? [] : UserHolon::normalizeDashboardLayout($layout)), 'Type dashboard including explicit empty layout');
            configCheck($target->getApplicationViewBaseTypeDefault('projects', 3) === $view, 'Global source type view is snapshotted locally');
            configCheck($target->getDashboardBaseTypeDefaultLayout(3) === [], 'Global source empty dashboard is snapshotted locally');
            $typeHolon = new Holon();
            $typeHolon->set('IDtypeholon', 2);
            configCheck(omoApplicationViewPreferencesGetDefaultViews('projects', $target, $typeHolon)['applicationType'] === ($mapped ? $modelView : $view), 'UI resolves the imported type default');
            $imported = new Holon();
            $imported->load($result['holonIdMap'][$instance->getId()]);
            $defaults = omoApplicationViewPreferencesGetDefaultViews('projects', $target, $imported);
            configCheck($defaults['organizationTemplate'] === ($mapped ? $modelView : $view), 'Resolved template application default');
            configCheck($target->getDashboardTemplateDefaultLayoutForHolon($imported) === ($mapped ? [] : UserHolon::normalizeDashboardLayout($layout)), 'Resolved template dashboard');
            configCheck($imported->getApplicationViewDefault('projects') === ($mapped ? null : ['view' => 'archive-local']), 'Old local view must not override mapped model');
            $importedTemplate = new Holon();
            $importedTemplate->load($result['holonIdMap'][$template->getId()]);
            configCheck($importedTemplate->getApplicationViewDefault('projects') === ($mapped ? $modelView : $view), 'Cloned template local defaults');
            $importedRoot = $target->getStructuralRootHolon();
            configCheck($importedRoot->getDashboardDefaultLayout() === ($mapped ? [] : UserHolon::normalizeDashboardLayout($layout)), 'Root local dashboard');
        }
    }
    unset($payload['configuration']);
    $legacy = Organization::importOmo1ExportAsNewOrganization($payload, $selected, $actor, 'Legacy config fixture', [], ['sendMemberInvitationEmails' => false]);
    configCheck(!empty($legacy['status']), 'Legacy archive without configuration');
    configCheck(!(bool)OrganizationApplication::loadByOrganizationAndDirectory((int)$legacy['organization']->getId(), 'projects')->get('active'), 'Legacy module activation fallback');
    $legacyMapped = Organization::importOmo1ExportAsNewOrganization($payload, $selected, $actor, 'Legacy mapped config fixture', $calibration, ['sendMemberInvitationEmails' => false]);
    configCheck(!empty($legacyMapped['status']), 'Legacy archive with mapping');
    configCheck(OrganizationTransferConfiguration::capture($legacyMapped['organization']) == $modelConfig, 'Legacy archive receives the model configuration');
    configCheck(ApplicationSetting::getApplicationViewDefaultsForType('projects', 3)['baseType'] === $view, 'Imports never rewrite server type defaults');
    configCheck(OrganizationTransferConfiguration::capture($source) == $sourceConfig, 'Source remains unchanged');
    configCheck(OrganizationTransferConfiguration::capture($model) == $modelConfig, 'Model remains unchanged');
} finally {
    DbObject::$_dbh = $originalDbh;
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}
echo "organization_import_configuration_test: OK\n";
