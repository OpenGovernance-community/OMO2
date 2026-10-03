<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ObjectAudience, ObjectMail, ObjectMailRecipient, User, UserOrganization, UserHolon, Event, EventInvitation, EventAttendance,
    ResourceInvitation, Project, ProjectUser, DecisionProcess, DecisionParticipant, McpOauthGrant, ArrayApplication, OrganizationApplication};
function objectMailDenied(callable $action, string $message): void
{
    $denied = false;
    try { $action(); } catch (DomainException | InvalidArgumentException $error) { $denied = true; }
    mcpCheck($denied, $message);
}
$before = $_SESSION; $items = mcpFixtures(); $smtp = null; $pipes = [];
$capture = tempnam(sys_get_temp_dir(), 'omo-mail-sink-');
try {
    $_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
    $_ENV['MAIL_USER'] = ''; $_ENV['MAIL_FROM'] = ''; $GLOBALS['mailUser'] = '';
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    mcpEnableDocumentCreation($items);
    $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['team', 'calendar', 'projects', 'decision']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    foreach (['CAN_EDIT_EVENT', 'CAN_EDIT_DECISION', 'CAN_ADD_MEMBER'] as $key) {
        $permissions = new \dbObject\ArrayPermission(); $permissions->load(['where' => [['field' => 'permission_key', 'value' => $key]], 'limit' => 1]);
        mcpCheck(count($permissions) === 1, 'Permission exists: ' . $key);
        $items[$key] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid, 'IDpermission' => $permissions[0]->getId(), 'range' => 'organization', 'member_type' => 'member']);
    }
    $items['member'] = mcpFixture(User::class, ['firstname' => 'Member', 'lastname' => 'Test', 'email' => 'private@example.invalid', 'active' => 1]);
    $memberId = (int)$items['member']->getId();
    $items['member_org'] = mcpFixture(UserOrganization::class, ['IDuser' => $memberId, 'IDorganization' => $oid, 'email' => 'work@example.invalid', 'phone' => '01234', 'active' => 1]);
    $items['member_role'] = mcpFixture(UserHolon::class, ['IDuser' => $memberId, 'IDholon' => $hid, 'active' => 1, 'is_membership' => 1]);
    $items['outsider'] = mcpFixture(User::class, ['firstname' => 'Outside', 'email' => 'outside@example.invalid', 'active' => 1]);
    $items['outsider_org'] = mcpFixture(UserOrganization::class, ['IDuser' => $items['outsider']->getId(), 'IDorganization' => $oid, 'active' => 1]);
    $items['inactive_user'] = mcpFixture(User::class, ['firstname' => 'Inactive', 'email' => 'inactive@example.invalid', 'active' => 0]);
    $items['inactive_org'] = mcpFixture(UserOrganization::class, ['IDuser' => $items['inactive_user']->getId(), 'IDorganization' => $oid, 'active' => 1]);
    $items['inactive_role'] = mcpFixture(UserHolon::class, ['IDuser' => $items['inactive_user']->getId(), 'IDholon' => $hid, 'active' => 1, 'is_membership' => 1]);
    for ($i = 0; $i < 51; $i++) {
        $items['bulk_user_' . $i] = mcpFixture(User::class, ['firstname' => 'Bulk ' . $i, 'email' => 'bulk-' . $i . '@example.invalid', 'active' => 1]);
        $items['bulk_org_' . $i] = mcpFixture(UserOrganization::class, ['IDuser' => $items['bulk_user_' . $i]->getId(), 'IDorganization' => $oid, 'active' => 1]);
        $items['bulk_role_' . $i] = mcpFixture(UserHolon::class, ['IDuser' => $items['bulk_user_' . $i]->getId(), 'IDholon' => $hid, 'active' => 1, 'is_membership' => 1]);
    }
    $page = ObjectAudience::page($oid, ['object_type' => 'holon', 'object_id' => $hid, 'limit' => 50]);
    mcpCheck(count($page['items']) === 50 && !$page['complete'] && $page['total'] === 53, 'Holon enumeration exceeds one page and excludes inactive users');
    $page2 = ObjectAudience::page($oid, ['object_type' => 'holon', 'object_id' => $hid, 'offset' => $page['next_offset'], 'limit' => 50]);
    mcpCheck(count($page2['items']) === 3 && $page2['complete'], 'All holon members available without search cap');
    $all = [...$page['items'], ...$page2['items']];
    $scoped = array_values(array_filter($all, static fn ($row) => $row['user_id'] === $memberId))[0];
    mcpCheck($scoped['email'] === 'work@example.invalid' && $scoped['phone'] === '01234' && !str_contains(json_encode($all), 'private@example.invalid'), 'Organization-scoped contact data');
    foreach (['hidden', 'other_root', 'inactive'] as $key) objectMailDenied(fn () => ObjectAudience::resolve($oid, 'holon', (int)$items[$key]->getId()), 'Unavailable holons denied');
    $items['event'] = mcpFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid, 'title' => 'Meeting test', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour'), 'active' => 1]);
    $eid = (int)$items['event']->getId();
    $defaultAudience = ObjectAudience::resolve($oid, 'event', $eid);
    mcpCheck(count($defaultAudience['members']) === 54 && count($defaultAudience['recipients']) === 53, 'Native invited contacts are listed separately from active delivery recipients');
    $nativeIds = $items['event']->getEffectiveInvitationTargets($oid)['userIds'];
    $freshIds = $items['event']->getEffectiveInvitationTargets($oid, null, true)['userIds'];
    sort($nativeIds); sort($freshIds);
    mcpCheck($nativeIds === $freshIds && count($nativeIds) === count($defaultAudience['members']), 'Refreshing native invitations must not silently change their membership scope');
    $listedInactive = array_column($defaultAudience['members'], null, 'user_id')[(int)$items['inactive_user']->getId()];
    mcpCheck(!$listedInactive['mail_eligible'], 'Inactive referenced contact remains listed, but cannot receive mail');
    foreach (['sender' => ['invitation_type' => 'user', 'IDuser' => $uid], 'member' => ['invitation_type' => 'user', 'IDuser' => $memberId], 'guest' => ['invitation_type' => 'email', 'email' => 'guest@example.invalid', 'display_name' => 'Guest']] as $key => $fields) {
        $items['invitation_' . $key] = mcpFixture(EventInvitation::class, ['IDevent' => $eid, 'status' => 'accepted', 'active' => 1] + $fields);
    }
    $preview = ObjectAudience::resolve($oid, 'event', $eid);
    mcpCheck(count($preview['members']) === 3 && $preview['can_send'], 'Explicit invitations replace default holon scope');
    $items['invitation_member']->set('accepted', false); $items['invitation_member']->save();
    $items['attendance_member'] = mcpFixture(EventAttendance::class, ['IDevent' => $eid, 'IDuser' => $memberId, 'is_present' => 0, 'active' => 1]);
    $absentAudience = ObjectAudience::page($oid, ['object_type' => 'event', 'object_id' => $eid]);
    mcpCheck(in_array($memberId, array_column($absentAudience['items'], 'user_id'), true) && $absentAudience['recipient_count'] === 3, 'An invited member remains in the complete MCP list when unchecked in attendance and invitation acceptance');
    $items['attendance_member']->set('is_present', true); $items['attendance_member']->save();
    mcpCheck(ObjectAudience::resolve($oid, 'event', $eid)['audience_token'] === $preview['audience_token'], 'Attendance never changes the email audience');
    $items['project'] = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid, 'title' => 'Project test', 'status' => 'ready', 'project_kind' => 'standard', 'proposal_status' => 'normal', 'active' => 1]);
    $items['project_member'] = mcpFixture(ProjectUser::class, ['IDproject' => $items['project']->getId(), 'IDuser' => $memberId, 'active' => 1]);
    mcpCheck(count(ObjectAudience::resolve($oid, 'project', (int)$items['project']->getId())['members']) === 2, 'Project audience is responsible plus active assignees');
    $items['decision'] = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid, 'title' => 'Decision test', 'decision_type' => 'decision', 'status' => 'draft', 'evaluation_method' => 'simple_vote', 'visibility_type' => 'organization']);
    $items['participant'] = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $items['decision']->getId(), 'IDuser' => $memberId, 'role' => 'participant', 'status' => 'active', 'active' => 1]);
    $decision = ObjectAudience::resolve($oid, 'decision', (int)$items['decision']->getId());
    mcpCheck(count($decision['members']) === 1 && !str_contains(json_encode($decision['members']), 'access_token'), 'Decision members projected without tokens or votes');
    $_SESSION['currentUser'] = (int)$items['outsider']->getId();
    objectMailDenied(fn () => ObjectAudience::resolve($oid, 'decision', (int)$items['decision']->getId()), 'Decision audience requires real management permission');
    $_SESSION = [];
    objectMailDenied(fn () => ObjectAudience::resolve($oid, 'event', $eid), 'Anonymous connection denied');
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $authorization = mcpAuthorizationRequest($items['client']); $authorization['scope'] = OMO_MCP_SCOPE . ' ' . OMO_MCP_CREATE_SCOPE . ' ' . OMO_MCP_MAIL_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $authorization);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    mcpCheck(McpOauthGrant::hasActiveCreationAuthorization($grant), 'Combined mail and document consent retains document creation');
    $args = ['object_type' => 'event', 'object_id' => $eid, 'subject' => 'Meeting message', 'message' => "Hello <script>test</script>\nSecond line", 'request_key' => 'test-mail-operation', 'audience_token' => $preview['audience_token']];
    objectMailDenied(fn () => ObjectMail::enqueue($oid, $args, ['IDuser' => $uid, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE]), 'Read-only grant cannot send');
    objectMailDenied(fn () => ObjectMail::enqueue($oid, $args + ['emails' => ['outside@example.invalid']], $grant), 'Free recipient input denied');
    $result = ObjectMail::enqueue($oid, $args, $grant);
    $items['mail'] = new ObjectMail(); $items['mail']->load($result['mail_id']);
    mcpCheck($result['delivery']['queued'] === 3 && !$result['complete'], 'Explicit queue result, not premature success');
    $retry = ObjectMail::enqueue($oid, $args, $grant);
    mcpCheck($retry['mail_id'] === $result['mail_id'] && $retry['replayed'], 'Identical retry does not duplicate deliveries');
    objectMailDenied(fn () => ObjectMail::enqueue($oid, array_replace($args, ['message' => 'Different']), $grant), 'Same key cannot send different content');
    $items['invitation_guest']->set('status', 'declined'); $items['invitation_guest']->save();
    $current = ObjectAudience::resolve($oid, 'event', $eid);
    mcpCheck(count($current['recipients']) === 2, 'Declined external guest excluded');
    objectMailDenied(fn () => ObjectMail::enqueue($oid, array_replace($args, ['request_key' => 'changed-audience']), $grant), 'Changed audience needs fresh preview');
    $smtp = proc_open([PHP_BINARY, __DIR__ . '/object_mail_smtp_fixture.php', $capture], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($smtp), 'SMTP sink starts');
    $address = trim(fgets($pipes[1]));
    mcpCheck(str_starts_with($address, '127.0.0.1:'), 'Test SMTP cannot forward to an external server');
    $GLOBALS['mailHost'] = '127.0.0.1'; $GLOBALS['mailPort'] = (int)substr(strrchr($address, ':'), 1);
    $GLOBALS['mailSecure'] = ''; $GLOBALS['mailAuth'] = false; $GLOBALS['mailTimeout'] = 3;
    ObjectMail::processBatch(20, $result['mail_id']);
    $status = ObjectMail::status($oid, $result['mail_id']);
    mcpCheck($status['delivery']['sent'] === 2 && $status['delivery']['skipped'] === 1 && $status['complete'], 'Delivery rechecks declined recipients');
    $captured = array_map(static fn ($line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR), file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    mcpCheck(count($captured) === 2, 'Exactly two SMTP messages');
    foreach ($captured as $message) {
        mcpCheck(count($message['recipients']) === 1 && !str_contains($message['data'], '\nBcc:') && !str_contains($message['data'], '\nCc:'), 'One recipient per delivery, no address leakage');
        mcpCheck(str_contains($message['data'], 'Reply-To:') && str_contains($message['data'], '&lt;script&gt;'), 'Authenticated reply-to and escaped plain text');
        mcpCheck(str_contains($message['data'], 'From:') && str_contains($message['data'], (string)$items['user']->get('email')), 'A valid profile works with no MAIL_USER address');
    }
    ObjectMail::enqueue($oid, $args, $grant); ObjectMail::processBatch(20, $result['mail_id']);
    mcpCheck(count(file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 2, 'Replay after SMTP success sends nothing');
    $args['audience_token'] = $current['audience_token']; $args['request_key'] = 'direct-profile-mail';
    $direct = ObjectMail::send($oid, $args, $grant);
    $items['direct_mail'] = new ObjectMail(); $items['direct_mail']->load($direct['mail_id']);
    mcpCheck($direct['delivery_mode'] === 'direct' && $direct['complete'] && $direct['delivery']['sent'] === 2, 'Small audiences deliver during the request without MAIL_USER');
    ObjectMail::send($oid, $args, $grant);
    mcpCheck(count(file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 4, 'Direct retry cannot duplicate successful email');
    $items['group_event'] = mcpFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid, 'title' => 'Group meeting', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour'), 'active' => 1]);
    $groupId = (int)$items['group_event']->getId();
    $groupPreview = ObjectAudience::resolve($oid, 'event', $groupId);
    $groupArgs = array_replace($args, ['object_id' => $groupId, 'request_key' => 'large-group-mail', 'audience_token' => $groupPreview['audience_token']]);
    $large = ObjectMail::send($oid, $groupArgs, $grant);
    $items['large_mail'] = new ObjectMail(); $items['large_mail']->load($large['mail_id']);
    mcpCheck($large['delivery_mode'] === 'queued' && $large['delivery']['queued'] === 53 && !$large['complete'], 'Large audiences are deferred, without SMTP on the request');
    $items['member_role']->set('active', false); $items['member_role']->save();
    $changedGroup = ObjectAudience::resolve($oid, 'event', $groupId);
    mcpCheck(count($changedGroup['members']) === 53 && count($changedGroup['recipients']) === 52, 'Fresh native scope drops removed role members and keeps inactive referenced contacts separate from delivery');
    $items['group_invitation'] = mcpFixture(EventInvitation::class, ['IDevent' => $groupId, 'invitation_type' => 'holon', 'IDholon' => $hid, 'status' => 'declined', 'active' => 1]);
    mcpCheck(ObjectAudience::resolve($oid, 'event', $groupId)['recipients'] === [], 'Declined holon invitation cannot produce recipients');
    $args['request_key'] = 'uncertain-smtp-mail';
    $uncertain = ObjectMail::enqueue($oid, $args, $grant);
    $items['uncertain_mail'] = new ObjectMail(); $items['uncertain_mail']->load($uncertain['mail_id']);
    $deliveries = new \dbObject\ArrayObjectMailRecipient(); $deliveries->load(['where' => [['field' => 'IDobject_mail', 'value' => $uncertain['mail_id']]]]);
    $deliveries[0]->set('status', 'sending'); $deliveries[0]->set('updated_at', time() - 601); $deliveries[0]->save();
    $deliveries[1]->set('status', 'failed'); $deliveries[1]->save();
    ObjectMail::processBatch(20, $uncertain['mail_id']);
    $uncertainStatus = ObjectMail::status($oid, $uncertain['mail_id']);
    mcpCheck($uncertainStatus['delivery']['unknown'] === 1 && $uncertainStatus['delivery']['failed'] === 1 && $uncertainStatus['complete'], 'Interrupted and failed SMTP deliveries are terminal');
    mcpCheck(count(file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 4, 'Uncertain SMTP outcomes are never resent automatically');
    $quotaFields = ['IDuser' => $uid, 'IDorganization' => $oid, 'IDmcp_oauth_grant' => $grant['id'], 'object_type' => 'event',
        'object_id' => $eid, 'request_hash' => hash('sha256', 'quota-fixture'), 'payload_hash' => hash('sha256', 'quota-fixture'),
        'subject' => 'Quota fixture only', 'message' => 'No delivery rows; never sent', 'recipient_count' => 1000, 'created_at' => time()];
    $items['quota_mail'] = mcpFixture(ObjectMail::class, $quotaFields);
    objectMailDenied(fn () => ObjectMail::enqueue($oid, array_replace($args, ['request_key' => 'daily-quota-denied']), $grant), 'Daily recipient quota is enforced across requests');
    $items['quota_mail']->delete(); unset($items['quota_mail']);
    $args['audience_token'] = $current['audience_token']; $args['request_key'] = 'revoked-grant-mail';
    $revoked = ObjectMail::enqueue($oid, $args, $grant);
    $items['revoked_mail'] = new ObjectMail(); $items['revoked_mail']->load($revoked['mail_id']);
    $deleted = ObjectMail::enqueue($oid, array_replace($args, ['request_key' => 'deleted-grant-mail']), $grant);
    $items['deleted_grant_mail'] = new ObjectMail(); $items['deleted_grant_mail']->load($deleted['mail_id']);
    McpOauthGrant::revokeOwned((int)$grant['id'], $uid);
    ObjectMail::processBatch(20, $revoked['mail_id']);
    mcpCheck(ObjectMail::status($oid, $revoked['mail_id'])['delivery']['skipped'] === 2, 'Revoked OAuth prevents queued SMTP delivery');
    ObjectMail::processBatch(100, $large['mail_id']);
    mcpCheck(ObjectMail::status($oid, $large['mail_id'])['delivery']['skipped'] === 53, 'Deferred group deliveries also honor OAuth revocation');
    $storedGrant = new McpOauthGrant(); $storedGrant->load((int)$grant['id']);
    mcpCheck($storedGrant->delete(), 'Outbox cannot block OAuth client/account deletion');
    ObjectMail::processBatch(20, $deleted['mail_id']);
    mcpCheck(ObjectMail::status($oid, $deleted['mail_id'])['delivery']['skipped'] === 2, 'Deleted grant never converts queued MCP mail into native mail');
    $_SESSION['currentUser'] = $memberId;
    objectMailDenied(fn () => ObjectMail::status($oid, $result['mail_id']), 'Another member cannot inspect sender mail jobs');
    mcpCheck(!$items['mail']->canViewDetail() && !$items['mail']->canEdit(), 'Outbox objects cannot be exposed or edited through generic object screens');
    echo "object_mail_test: OK\n";
} finally {
    if (is_resource($smtp)) { proc_terminate($smtp); foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_close($smtp); }
    @unlink($capture); mcpCleanup($items); $_SESSION = $before;
}
