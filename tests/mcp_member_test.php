<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{McpBrowse, McpContent, ArrayApplication, OrganizationApplication, User, UserOrganization, UserHolon,
    Event, EventInvitation, Document, ObjectVisibility, DecisionProcess, DecisionParticipant};
$before = $_SESSION; $items = mcpFixtures();
function mcpMemberRecords(array $grant, array $args): array
{
    $all = []; $cursor = 0;
    do {
        $page = McpBrowse::records($grant, $args + ['after_id' => $cursor, 'limit' => 2]);
        array_push($all, ...$page['items']);
        mcpCheck($page['next_after_id'] === null || $page['next_after_id'] > $cursor, 'Member list cursor advances');
        $cursor = $page['next_after_id'];
    } while ($cursor !== null);
    return $all;
}
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $grant = ['IDuser' => $uid, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['team', 'calendar', 'documents', 'decision', 'projects']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['member'] = mcpFixture(User::class, ['firstname' => 'Camille', 'lastname' => 'McpMember', 'email' => 'private-global@example.invalid', 'active' => 1]);
    $mid = (int)$items['member']->getId();
    $items['member_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $mid, 'active' => 1,
        'email' => 'camille-scoped@example.invalid', 'username' => 'camille-omo']);
    $items['outsider'] = mcpFixture(User::class, ['firstname' => 'Outside member', 'active' => 1]);
    $lookup = McpBrowse::records($grant, ['module' => 'team', 'query' => 'McpMember   Camille']);
    mcpCheck(array_column($lookup['items'], 'user_id') === [$mid], 'Name lookup works in reverse order with extra spaces');
    mcpCheck($lookup['items'][0]['member_details']['arguments'] === ['user_id' => $mid], 'Name results expose member navigation');
    for ($i = 0; $i < 22; $i++) {
        $items['role_' . $i] = mcpFixture(McpTestHolon::class, ['IDorganization' => $oid, 'IDholon_org' => $hid,
            'IDholon_parent' => $hid, 'IDtypeholon' => 1, 'name' => 'Member role ' . $i, 'active' => 1, 'visible' => 1]);
        $items['assignment_' . $i] = mcpFixture(UserHolon::class, ['IDholon' => $items['role_' . $i]->getId(), 'IDuser' => $mid,
            'active' => 1, 'is_membership' => 1, 'focus' => 'Visible focus']);
    }
    $items['hidden_assignment'] = mcpFixture(UserHolon::class, ['IDholon' => $items['hidden']->getId(), 'IDuser' => $mid, 'active' => 1, 'is_membership' => 1]);
    $member = McpBrowse::member($grant, $mid);
    mcpCheck($member['member']['email'] === 'camille-scoped@example.invalid' && !str_contains(json_encode($member), 'private-global'), 'Profile uses organization-scoped identity');
    mcpCheck(count($member['assignments']['items']) === 20 && $member['assignments']['next_after_id'] !== null, 'Member profile exposes paginated roles');
    $next = McpBrowse::assignments($grant, ['user_id' => $mid, 'after_id' => $member['assignments']['next_after_id']]);
    mcpCheck(count($next['items']) === 2 && $next['complete'] && $next['items'][0]['holon_type_id'] === 1, 'Remaining roles and their type are reachable');
    $navigation = array_column($member['related_records'], null, 'module');
    mcpCheck($navigation['projects']['arguments'] === ['module' => 'projects', 'user_id' => $mid]
        && in_array('invited', $navigation['calendar']['user_relations'], true), 'Profile links to projects and invitations');
    $effective = mcpMemberRecords($grant, ['module' => 'structure', 'user_id' => $mid, 'user_relation' => 'effective_member']);
    mcpCheck(count($effective) === 23 && in_array($hid, array_column($effective, 'record_id'), true), 'Effective membership includes the circle and its descendant roles, without hidden roles');
    $common = ['IDorganization' => $oid, 'IDholon' => (int)$items['role_0']->getId(), 'IDuser' => $uid,
        'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'start_at' => new DateTimeImmutable('2026-11-01 10:00:00'),
        'end_at' => new DateTimeImmutable('2026-11-01 11:00:00'), 'active' => 1];
    foreach (['explicit', 'group', 'default', 'declined', 'revoked', 'past', 'cancelled', 'email', 'private', 'authored'] as $key) {
        $changes = match ($key) {
            'past' => ['start_at' => new DateTimeImmutable('2026-09-01 10:00:00'), 'end_at' => new DateTimeImmutable('2026-09-01 11:00:00')],
            'cancelled' => ['status' => 'cancelled'], 'private' => ['status' => 'draft', 'IDuser' => $mid],
            'authored' => ['IDuser' => $mid], default => [],
        };
        $items['event_' . $key] = mcpFixture(Event::class, array_replace($common, $changes, ['title' => 'Member event ' . $key]));
        if ($key === 'default') continue;
        $fields = match ($key) {
            'group', 'declined' => ['invitation_type' => 'holon', 'IDholon' => $items['role_0']->getId()],
            'email' => ['invitation_type' => 'email', 'email' => 'camille-scoped@example.invalid'],
            'revoked', 'authored' => ['invitation_type' => 'user', 'IDuser' => $uid],
            default => ['invitation_type' => 'user', 'IDuser' => $mid],
        };
        $items['invite_' . $key] = mcpFixture(EventInvitation::class, ['IDevent' => $items['event_' . $key]->getId(), 'active' => 1, 'status' => 'accepted'] + $fields);
        if (in_array($key, ['declined', 'revoked'], true)) $items['override_' . $key] = mcpFixture(EventInvitation::class,
            ['IDevent' => $items['event_' . $key]->getId(), 'active' => 1, 'status' => $key, 'invitation_type' => 'user', 'IDuser' => $mid]);
    }
    $upcoming = mcpMemberRecords($grant, ['module' => 'calendar', 'user_id' => $mid, 'user_relation' => 'invited', 'date_from' => '2026-10-03']);
    $expected = array_map(fn ($key) => (int)$items['event_' . $key]->getId(), ['explicit', 'group', 'default', 'email']);
    mcpCheck(array_column($upcoming, 'record_id') === $expected, 'Unexpected upcoming meetings: ' . json_encode(array_column($upcoming, 'title')));
    mcpCheck(array_column(mcpMemberRecords($grant, ['module' => 'calendar', 'user_id' => $mid, 'date_from' => '2026-10-03']), 'record_id') === [...$expected, (int)$items['event_authored']->getId()], 'Default user filter combines authorship and invitations');
    $denied = false;
    try { McpContent::read($grant, 'calendar', (int)$items['event_private']->getId(), null, 0, 0, 12000); }
    catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Private draft cannot be read directly');
    mcpCheck(array_column(mcpMemberRecords($grant, ['module' => 'calendar', 'user_id' => $mid, 'user_relation' => 'author']), 'record_id') === [(int)$items['event_authored']->getId()], 'Invitation is distinct from event authorship');
    $items['pv'] = mcpFixture(Document::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'IDevent' => $items['event_explicit']->getId(),
        'title' => 'Member meeting minutes', 'documenttype' => Document::TYPE_PV, 'active' => 1]);
    $items['pv_visibility'] = mcpFixture(ObjectVisibility::class, ['object_type' => 'document', 'object_id' => $items['pv']->getId(),
        'IDorganization' => $oid, 'visibility_type' => 'organization', 'active' => 1]);
    mcpCheck(array_column(mcpMemberRecords($grant, ['module' => 'pv', 'user_id' => $mid, 'user_relation' => 'invited']), 'record_id') === [(int)$items['pv']->getId()], 'Member reaches readable minutes through event invitation');
    $items['decision'] = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Member decision', 'decision_type' => 'decision', 'status' => 'draft', 'evaluation_method' => 'simple_vote', 'visibility_type' => 'organization']);
    $items['participant'] = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $items['decision']->getId(), 'IDuser' => $mid, 'role' => 'participant', 'status' => 'active', 'active' => 1]);
    mcpCheck(array_column(mcpMemberRecords($grant, ['module' => 'decision', 'user_id' => $mid, 'user_relation' => 'participant']), 'record_id') === [(int)$items['decision']->getId()], 'Manager can traverse decision participation');
    foreach ([(int)$items['outsider']->getId(), -1] as $unavailable) {
        $denied = false;
        try { McpBrowse::member($grant, $unavailable); } catch (DomainException $error) { $denied = true; }
        mcpCheck($denied, 'Foreign/missing members unavailable');
    }
    $items['member_org']->set('active', 0); $items['member_org']->save();
    $denied = false;
    try { McpBrowse::member($grant, $mid); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Former organization member cannot be traversed');
} finally { mcpCleanup($items); $_SESSION = $before; }
echo "mcp_member_test: OK\n";
