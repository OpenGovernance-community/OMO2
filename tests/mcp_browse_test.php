<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{McpBrowse, McpContent, ArrayApplication, OrganizationApplication, User, UserOrganization, UserHolon,
    Document, ObjectVisibility, Project, ProjectUser};
$before = $_SESSION;
$items = mcpFixtures();
function mcpBrowseAll(array $grant, array $args): array
{
    $all = []; $cursor = 0;
    do {
        $page = McpBrowse::records($grant, $args + ['after_id' => $cursor]);
        array_push($all, ...$page['items']);
        mcpCheck($page['next_after_id'] === null || $page['next_after_id'] > $cursor, 'Cursor must advance');
        mcpCheck($page['complete'] === ($page['next_after_id'] === null), 'Completion is explicit');
        $cursor = $page['next_after_id'];
    } while ($cursor !== null);
    return $all;
}
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $grant = ['IDuser' => $uid, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['team', 'projects', 'documents', 'decision']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['colleague'] = mcpFixture(User::class, ['firstname' => 'McpBrowseColleague', 'active' => 1]);
    $otherUid = (int)$items['colleague']->getId();
    $items['colleague_membership'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $otherUid, 'active' => 1]);
    $items['outsider'] = mcpFixture(User::class, ['firstname' => 'OUTSIDE-ORGANIZATION', 'active' => 1]);
    $items['assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $otherUid, 'IDholon' => $items['role']->getId(), 'active' => 1, 'is_membership' => 1, 'focus' => 'Focus test']);
    $items['hidden_assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $otherUid, 'IDholon' => $items['hidden']->getId(), 'active' => 1, 'is_membership' => 1]);
    $items['foreign_assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $otherUid, 'IDholon' => $items['other_root']->getId(), 'active' => 1, 'is_membership' => 1]);
    $items['preference_only'] = mcpFixture(UserHolon::class, ['IDuser' => $otherUid, 'IDholon' => $hid, 'active' => 0, 'is_membership' => 0]);
    $catalog = McpBrowse::catalog($grant);
    mcpCheck(in_array('team', array_column($catalog['modules'], 'module'), true)
        && !in_array('calendar', array_column($catalog['modules'], 'module'), true), 'Catalog only includes enabled modules');
    $members = mcpBrowseAll($grant, ['module' => 'team', 'query' => 'McpBrowseColleague']);
    mcpCheck(array_column($members, 'record_id') === [$otherUid], 'Name lookup returns user_id');
    $assigned = McpBrowse::assignments($grant, ['user_id' => $otherUid]);
    mcpCheck(array_column($assigned['items'], 'assignment_id') === [(int)$items['assignment']->getId()], 'Hidden and foreign assignments excluded');
    mcpCheck($assigned['items'][0]['focus'] === 'Focus test', 'Safe assignment detail');
    $roles = mcpBrowseAll($grant, ['module' => 'structure', 'user_id' => $otherUid]);
    mcpCheck(array_column($roles, 'record_id') === [(int)$items['role']->getId()], 'Person-specific structure');
    mcpCheck(mcpBrowseAll($grant, ['module' => 'team', 'context_holon_id' => $hid]) === [], 'Preference-only rows are not memberships');
    $common = ['IDorganization' => $oid, 'IDholon' => $hid, 'active' => 1, 'project_kind' => 'standard', 'proposal_status' => 'normal'];
    $items['parent'] = mcpFixture(Project::class, $common + ['title' => 'Browse parent', 'status' => 'ready', 'IDuser' => $uid]);
    $items['child'] = mcpFixture(Project::class, $common + ['title' => 'Browse child', 'status' => 'in_progress',
        'IDproject_parent' => $items['parent']->getId(), 'IDuser' => $otherUid]);
    $items['assigned_project'] = mcpFixture(ProjectUser::class, ['IDproject' => $items['parent']->getId(), 'IDuser' => $otherUid, 'active' => 1]);
    $projects = mcpBrowseAll($grant, ['module' => 'projects', 'user_id' => $otherUid]);
    mcpCheck(count($projects) === 2, 'Responsible and explicit assignment both match');
    $responsible = mcpBrowseAll($grant, ['module' => 'projects', 'user_id' => $otherUid, 'user_relation' => 'responsible']);
    mcpCheck(array_column($responsible, 'record_id') === [(int)$items['child']->getId()], 'Responsible differs from assignee');
    mcpCheck(count(mcpBrowseAll($grant, ['module' => 'projects', 'parent_id' => 0])) === 1, 'Project roots');
    $children = mcpBrowseAll($grant, ['module' => 'projects', 'parent_id' => (int)$items['parent']->getId(), 'status' => 'in_progress']);
    mcpCheck(array_column($children, 'record_id') === [(int)$items['child']->getId()], 'Direct child/status filter');
    $documents = [];
    // More than the old search cap, each visible through the same OMO access checks.
    for ($index = 0; $index < 57; $index++) {
        $key = 'document_' . $index;
        $items[$key] = mcpFixture(Document::class, ['title' => 'Browse document ' . $index, 'IDorganization' => $oid,
            'IDuser' => $otherUid, 'IDusercreation' => $uid, 'active' => 1, 'documenttype' => Document::TYPE_HTML,
            'datecreation' => new DateTimeImmutable('2026-10-02 12:00:00'), 'codeview' => 'SECRET-SHARE-KEY']);
        $documents[] = (int)$items[$key]->getId();
        $items[$key . '_visibility'] = mcpFixture(ObjectVisibility::class, ['object_type' => 'document', 'object_id' => $items[$key]->getId(),
            'IDorganization' => $oid, 'visibility_type' => ObjectVisibility::TYPE_ORGANIZATION, 'active' => 1]);
    }
    $items['private'] = mcpFixture(Document::class, ['title' => 'PRIVATE-DOCUMENT', 'IDorganization' => $oid,
        'IDuser' => $otherUid, 'IDusercreation' => $otherUid, 'active' => 1, 'documenttype' => Document::TYPE_HTML]);
    $items['private_visibility'] = mcpFixture(ObjectVisibility::class, ['object_type' => 'document', 'object_id' => $items['private']->getId(),
        'IDorganization' => $oid, 'visibility_type' => ObjectVisibility::TYPE_SELF, 'active' => 1]);
    $items['foreign_document'] = mcpFixture(Document::class, ['title' => 'FOREIGN-DOCUMENT', 'IDorganization' => $items['other_org']->getId(),
        'IDuser' => $otherUid, 'IDusercreation' => $uid, 'active' => 1, 'documenttype' => Document::TYPE_HTML]);
    $listed = mcpBrowseAll($grant, ['module' => 'documents', 'user_id' => $otherUid, 'limit' => 7]);
    mcpCheck(array_column($listed, 'record_id') === $documents, 'Complete pagination beyond 50 without duplicates or private/foreign records');
    mcpCheck(!str_contains(json_encode($listed), 'SECRET-SHARE-KEY'), 'Safe projection excludes sharing secrets');
    mcpCheck(count(mcpBrowseAll($grant, ['module' => 'documents', 'date_from' => '2026-10-02', 'date_to' => '2026-10-02'])) === 57, 'Inclusive calendar day');
    mcpCheck(mcpBrowseAll($grant, ['module' => 'documents', 'date_from' => '2026-10-03']) === [], 'Date exclusion');
    mcpCheck(mcpBrowseAll($grant, ['module' => 'documents', 'query' => "' OR 1=1 --"]) === [], 'Query is literal bound text');
    $record = McpContent::read($grant, 'projects', (int)$items['child']->getId(), null, 0, 0, 1000);
    mcpCheck($record['record']['parent_id'] === (int)$items['parent']->getId(), 'Read result includes traversal identifiers');
    $items['pv'] = mcpFixture(Document::class, ['title' => 'Browse minutes', 'IDorganization' => $oid,
        'IDuser' => $uid, 'IDusercreation' => $uid, 'IDuser_pv_official_editor' => $otherUid,
        'documenttype' => Document::TYPE_PV, 'pvstage' => Document::PV_STAGE_VALIDATED, 'active' => 1]);
    $items['pv_visibility'] = mcpFixture(ObjectVisibility::class, ['object_type' => 'document', 'object_id' => $items['pv']->getId(),
        'IDorganization' => $oid, 'visibility_type' => ObjectVisibility::TYPE_ORGANIZATION, 'active' => 1]);
    $minutes = mcpBrowseAll($grant, ['module' => 'pv', 'user_id' => $otherUid, 'user_relation' => 'editor']);
    mcpCheck(array_column($minutes, 'record_id') === [(int)$items['pv']->getId()], 'Official minutes editor filter');
    mcpCheck(mcpBrowseAll($grant, ['module' => 'pv', 'user_id' => $otherUid, 'user_relation' => 'owner']) === [], 'Editor differs from minutes owner');
    foreach ([['module' => 'calendar'], ['module' => 'projects', 'user_id' => (int)$items['outsider']->getId()],
        ['module' => 'projects', 'user_id' => $uid, 'user_relation' => 'requester'],
        ['module' => 'projects', 'status' => 'unknown'], ['module' => 'faq', 'parent_id' => 0],
        ['module' => 'tutorials', 'user_id' => $uid],
        ['module' => 'documents', 'context_holon_id' => (int)$items['other_root']->getId()]] as $args) {
        $denied = false;
        try { McpBrowse::records($grant, $args); } catch (DomainException $error) { $denied = true; }
        mcpCheck($denied, 'Unavailable scope/filter rejected instead of silently ignored');
    }
    // Cursor must progress through an empty readable page, rather than claiming the list ended.
    for ($index = 0; $index < 501; $index++) {
        $key = 'draft_' . $index;
        $items[$key] = mcpFixture(\dbObject\DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid,
            'IDuser' => $otherUid, 'title' => 'Hidden draft', 'decision_type' => 'decision', 'status' => 'draft',
            'evaluation_method' => 'simple_vote', 'visibility_type' => 'organization']);
    }
    $items['visible_decision'] = mcpFixture(\dbObject\DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $uid, 'title' => 'Visible decision after hidden page', 'decision_type' => 'decision', 'status' => 'draft',
        'evaluation_method' => 'simple_vote', 'visibility_type' => 'organization']);
    $empty = McpBrowse::records($grant, ['module' => 'decision']);
    mcpCheck($empty['items'] === [] && !$empty['complete'] && $empty['next_after_id'] !== null, 'Empty page continues after permission filtering');
    $end = McpBrowse::records($grant, ['module' => 'decision', 'after_id' => $empty['next_after_id']]);
    mcpCheck(array_column($end['items'], 'record_id') === [(int)$items['visible_decision']->getId()] && $end['complete'], 'Later readable result is never skipped');
} finally { mcpCleanup($items); $_SESSION = $before; }
echo "mcp_browse_test: OK\n";
