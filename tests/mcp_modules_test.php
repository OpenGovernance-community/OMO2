<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{McpContent, ArrayApplication, OrganizationApplication, Project, Event, Rule, StatIndicator,
    StatIndicatorValue, FAQ, Parcours, OrganizationParcours, Mission, ParcoursMission, ControlActivity,
    Checklist, DecisionProcess};
$before = $_SESSION;
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'op' => 'IN',
        'value' => ['team', 'calendar', 'policy', 'decision', 'projects', 'stats', 'processus', 'activities']]]]);
    mcpCheck(count($apps) === 8, 'Module apps in seed');
    foreach ($apps as $app) {
        $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
            ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $common = ['IDorganization' => $oid, 'IDholon' => $hid, 'active' => 1];
    $items['projects'] = mcpFixture(Project::class, $common + ['title' => 'MCP-modules project', 'description' => 'Project body',
        'status' => 'ready', 'project_kind' => 'standard', 'proposal_status' => 'normal']);
    $items['project_owner'] = mcpFixture(\dbObject\User::class, ['firstname' => 'Private proposer', 'active' => 1]);
    $items['private_project'] = mcpFixture(Project::class, $common + ['title' => 'MCP-modules private proposal',
        'description' => 'PRIVATE-PROPOSAL-CONTENT', 'status' => 'ready', 'project_kind' => 'standard', 'proposal_status' => 'refused',
        'IDuser' => $items['project_owner']->getId(), 'IDuser_proposed' => $items['project_owner']->getId()]);
    $permissions = new \dbObject\ArrayPermission();
    $permissions->load(['where' => [['field' => 'permission_key', 'value' => 'CAN_CREATE_PROJECT']], 'limit' => 1]);
    mcpCheck(count($permissions) === 1, 'Project permission in seed');
    // Configured permission is restricted to role members; this viewer has no role assignment.
    $items['project_permission'] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $items['role']->getId(),
        'IDpermission' => $permissions[0]->getId(), 'range' => 'organization', 'member_type' => 'member']);
    $items['calendar'] = mcpFixture(Event::class, $common + ['IDuser' => $uid, 'title' => 'MCP-modules event',
        'description' => 'Event body', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich',
        'start_at' => new DateTimeImmutable('2026-10-02 09:00'), 'end_at' => new DateTimeImmutable('2026-10-02 10:00')]);
    $items['rules'] = mcpFixture(Rule::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'title' => 'MCP-modules rule',
        'description' => 'Rule body', 'scope' => 'global', 'review_date' => new DateTimeImmutable('2027-01-01'),
        'expiration_date' => new DateTimeImmutable('2028-01-01')]);
    $items['stats'] = mcpFixture(StatIndicator::class, $common + ['name' => 'MCP-modules indicator', 'description' => 'Indicator body',
        'reference_type' => 'none', 'measurement_frequency' => 'monthly', 'source_type' => 'manual']);
    $items['measurement'] = mcpFixture(StatIndicatorValue::class, ['IDstatindicator' => $items['stats']->getId(),
        'IDuser' => $uid, 'value' => 42.5, 'measured_at' => new DateTimeImmutable('2026-10-01 12:00')]);
    $items['faq'] = mcpFixture(FAQ::class, ['IDorganization' => $oid, 'question' => 'MCP-modules FAQ',
        'answer' => 'FAQ body', 'isactive' => 1]);
    $items['tutorials'] = mcpFixture(Parcours::class, ['title' => 'MCP-modules tutorial', 'description' => 'Tutorial body',
        'ispublic' => 0, 'isbasic' => 0, 'ispack' => 0, 'isarchived' => 0]);
    $items['tutorial_link'] = mcpFixture(OrganizationParcours::class, ['IDorganization' => $oid,
        'IDparcours' => $items['tutorials']->getId(), 'everybody' => 1]);
    $items['mission'] = mcpFixture(Mission::class, ['title' => 'MCP-modules mission', 'resume' => 'Mission summary', 'html' => 'Mission body']);
    $items['mission_link'] = mcpFixture(ParcoursMission::class, ['IDparcours' => $items['tutorials']->getId(), 'IDmission' => $items['mission']->getId()]);
    $items['activities'] = mcpFixture(ControlActivity::class, $common + ['title' => 'MCP-modules activity', 'description' => 'Activity body',
        'frequency' => 'daily', 'schedule' => '09:00']);
    $items['process_template'] = mcpFixture(Project::class, $common + ['title' => 'MCP-modules process', 'description' => 'Process body',
        'status' => 'ready', 'project_kind' => 'checklist_template', 'proposal_status' => 'normal']);
    $items['processus'] = mcpFixture(Checklist::class, ['IDorganization' => $oid, 'IDproject_template_root' => $items['process_template']->getId(),
        'status' => 'published', 'active' => 1]);
    $items['decision'] = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'MCP-modules decision', 'description' => 'Decision body', 'decision_type' => 'decision',
        'status' => 'draft', 'evaluation_method' => 'simple_vote', 'visibility_type' => 'organization']);
    $grant = ['IDuser' => $uid, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE];
    $roleId = (int)$items['role']->getId();
    $items['local_rule'] = mcpFixture(Rule::class, ['IDorganization' => $oid, 'IDholon' => $roleId,
        'title' => 'MCP-local-context rule', 'description' => 'Local rule body', 'scope' => 'local',
        'review_date' => new DateTimeImmutable('2027-01-01'), 'expiration_date' => new DateTimeImmutable('2028-01-01')]);
    $items['local_faq'] = mcpFixture(FAQ::class, ['IDorganization' => $oid, 'IDholon' => $roleId,
        'question' => 'MCP-local-context FAQ', 'answer' => 'Local FAQ body', 'isactive' => 1]);
    foreach (['rules' => 'local_rule', 'faq' => 'local_faq'] as $module => $fixture) {
        $listed = \dbObject\McpBrowse::records($grant, ['module' => $module, 'query' => 'MCP-local-context']);
        mcpCheck(count($listed['items']) === 1, 'Contextual record enumerated: ' . $module);
        $item = $listed['items'][0];
        mcpCheck($item['context_holon_id'] === $roleId && $item['holon_id'] === $roleId, 'Context returned: ' . $module);
        $read = McpContent::read($grant, $module, $item['record_id'], $item['context_holon_id'], 0, 0, 12000);
        mcpCheck($read['record_id'] === (int)$items[$fixture]->getId(), 'Contextual read back: ' . $module);
        $rootList = \dbObject\McpBrowse::records($grant, ['module' => $module, 'query' => 'MCP-local-context', 'context_holon_id' => $hid]);
        mcpCheck($rootList['items'] === [], 'Explicit root excludes local child context: ' . $module);
    }
    $items['app_projects']->load((int)$items['app_projects']->getId(), true);
    $items['app_projects']->setParametersArray(['importanceCalculationVersion' => 0]);
    $items['app_projects']->save();
    foreach (['projects', 'calendar', 'rules', 'stats', 'faq', 'tutorials', 'activities', 'processus', 'decision'] as $module) {
        $record = McpContent::read($grant, $module, (int)$items[$module]->getId(), null, 0, 0, 12000);
        mcpCheck(str_contains($record['title'], 'MCP-modules'), 'Module read: ' . $module);
        if ($module === 'stats') mcpCheck(str_contains($record['text'], '42.5'), 'Numeric measurements available');
        $listed = \dbObject\McpBrowse::records($grant, ['module' => $module, 'query' => 'MCP-modules', 'limit' => 50]);
        mcpCheck(in_array((int)$items[$module]->getId(), array_column($listed['items'], 'record_id'), true), 'Module enumeration: ' . $module);
        mcpCheck(!str_contains(json_encode($listed), 'private proposal'), 'Private project is not listed');
    }
    foreach (\dbObject\McpBrowse::catalog($grant)['modules'] as $dataset) {
        foreach ($dataset['user_relations'] as $relation) {
            $filtered = \dbObject\McpBrowse::records($grant, ['module' => $dataset['module'],
                'user_id' => $uid, 'user_relation' => $relation, 'query' => 'MCP-modules']);
            mcpCheck(isset($filtered['items']), 'Declared user relation works: ' . $dataset['module'] . '/' . $relation);
        }
    }
    mcpCheck(McpContent::read($grant, 'team', $uid, null, 0, 0, 12000)['record_id'] === $uid, 'Own member profile available');
    $mission = McpContent::read($grant, 'tutorials', (int)$items['tutorials']->getId(), null, (int)$items['mission']->getId(), 0, 12000);
    mcpCheck(str_contains($mission['text'], 'Mission body'), 'Linked tutorial mission content');
    $denied = false;
    try { McpContent::read($grant, 'tutorials', (int)$items['tutorials']->getId(), null, (int)$items['mission']->getId() + 1000000, 0, 12000); }
    catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Foreign tutorial mission rejected');
    $search = McpContent::search($grant, 'MCP-modules', [], null, 0, 50);
    mcpCheck(count($search['items']) >= 9, 'Cross-module search returns enabled modules');
    mcpCheck(!str_contains(json_encode($search), 'private proposal') && !str_contains(json_encode($search), 'PRIVATE-PROPOSAL-CONTENT'), 'Private proposal title and excerpt excluded');
    $denied = false;
    try { McpContent::read($grant, 'projects', (int)$items['private_project']->getId(), null, 0, 0, 12000); }
    catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Private proposal direct read rejected');
    $items['app_projects']->load((int)$items['app_projects']->getId(), true);
    mcpCheck($items['app_projects']->getParametersArray()['importanceCalculationVersion'] === 0, 'Read/search never initializes project settings');
} finally { mcpCleanup($items); $_SESSION = $before; }
echo "mcp_modules_test: OK\n";
