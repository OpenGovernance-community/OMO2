<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{McpCalendar, McpOauthGrant, ArrayApplication, OrganizationApplication, User, UserOrganization, UserHolon,
    ArrayPermission, HolonPermission, Event, EventInvitation, ExternalCalendar, ExternalCalendarEvent, MeetingProfile};

function mcpCalendarDenied(callable $action, string $message): void
{
    $denied = false; try { $action(); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, $message);
}
function mcpCalendarHttp(string $token, string $name, array $args): array
{
    $endpoint = omoMcpPublicUrl();
    mcpCheck(parse_url($endpoint, PHP_URL_HOST) === 'localtest.me', 'HTTP fixture check only runs against local Docker');
    $curl = curl_init($endpoint);
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_POSTFIELDS => json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => (object)$args]], JSON_THROW_ON_ERROR)]);
    $raw = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); unset($curl);
    mcpCheck($status === 200 && is_string($raw), 'Calendar HTTP call failed');
    return json_decode($raw, true, 64, JSON_THROW_ON_ERROR)['result'];
}
$before = $_SESSION; $items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['team', 'calendar']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['member'] = mcpFixture(User::class, ['firstname' => 'Available member', 'active' => 1]); $mid = (int)$items['member']->getId();
    $items['member_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $mid, 'active' => 1]);
    foreach ([$uid, $mid] as $person) $items['assignment_' . $person] = mcpFixture(UserHolon::class,
        ['IDholon' => $hid, 'IDuser' => $person, 'active' => 1, 'is_membership' => 1]);
    $permissions = new ArrayPermission(); $permissions->load(['where' => [['field' => 'permission_key', 'value' => 'CAN_CREATE_EVENT']], 'limit' => 1]);
    mcpCheck(count($permissions) === 1, 'Event creation permission exists');
    $items['permission'] = mcpFixture(HolonPermission::class, ['IDholon' => $hid, 'IDpermission' => $permissions[0]->getId(), 'range' => 'self', 'member_type' => 'member']);
    $readGrant = ['IDuser' => $uid, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE];
    $request = mcpAuthorizationRequest($items['client']); $request['scope'] .= ' ' . OMO_MCP_EVENT_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $request);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    $spaces = McpCalendar::spaces($readGrant, ['limit' => 1]);
    mcpCheck(count($spaces['items']) === 1 && $spaces['items'][0]['holon_id'] === $hid && !$spaces['write_authorized'], 'Discovery honors holon rights, independently of write consent');
    $next = McpCalendar::spaces($grant, ['after_id' => $spaces['next_after_id'], 'limit' => 1]);
    mcpCheck($next['complete'] && $next['items'] === [], 'Filtered destination pagination completes');
    $day = new DateTimeImmutable('next monday 00:00', new DateTimeZone('Europe/Zurich'));
    $hours = MeetingProfile::defaultHours(); foreach ($hours as &$row) $row['pause'] = true; unset($row);
    foreach ([$uid, $mid] as $person) $items['profile_' . $person] = mcpFixture(MeetingProfile::class,
        ['IDuser' => $person, 'enabled' => 1, 'slug' => 'mcp-calendar-' . $person, 'weekly_hours' => json_encode($hours), 'timezone' => 'Europe/Zurich']);
    $items['busy'] = mcpFixture(Event::class, ['IDuser' => $mid, 'IDorganization' => $items['other_org']->getId(), 'IDholon' => $items['other_root']->getId(),
        'title' => 'PRIVATE OMO event title', 'description' => 'PRIVATE notes', 'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0), 'status' => 'confirmed', 'active' => 1]);
    $base = ['IDuser' => $mid, 'provider' => 'ics', 'title' => 'PRIVATE calendar name', 'username' => 'fixture',
        'password_encrypted' => 'unused-fixture', 'active' => 1, 'last_sync_at' => new DateTimeImmutable()];
    $items['external'] = mcpFixture(ExternalCalendar::class, $base + ['calendar_url' => 'ics:mcp-busy-' . $mid]);
    $items['external_busy'] = mcpFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $items['external']->getId(), 'source_key' => 'busy',
        'title' => 'PRIVATE external event', 'start_at' => $day->setTime(14, 0), 'end_at' => $day->setTime(15, 0), 'is_busy' => 1, 'active' => 1]);
    $items['opening'] = mcpFixture(ExternalCalendar::class, $base + ['calendar_url' => 'ics:mcp-opening-' . $mid, 'availability_only' => 1]);
    $items['opening_window'] = mcpFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $items['opening']->getId(), 'source_key' => 'opening',
        'title' => 'PRIVATE opening', 'start_at' => $day->setTime(9, 0), 'end_at' => $day->setTime(16, 0), 'is_busy' => 0, 'active' => 1]);
    $query = ['user_ids' => [$uid, $mid], 'date_from' => $day->format('Y-m-d'), 'date_to' => $day->format('Y-m-d'), 'duration_minutes' => 60];
    $available = McpCalendar::availability($readGrant, $query);
    $starts = array_map(static fn($range) => (new DateTimeImmutable($range['start_at']))->format('H:i'), $available['common_free_intervals']);
    mcpCheck($starts === ['09:00', '11:00', '13:00', '15:00'] && !$available['incomplete'], 'Common times combine OMO, imported busy/opening calendars, working hours and lunch');
    mcpCheck(!str_contains(json_encode($available), 'PRIVATE') && !str_contains(json_encode($available), 'ics:'), 'Availability contains no private calendar/event identifiers or titles');
    $long = McpCalendar::availability($readGrant, array_replace($query, ['duration_minutes' => 90]));
    mcpCheck($long['common_free_intervals'] === [], 'Duration filtering rejects short windows');
    mcpCalendarDenied(fn() => McpCalendar::availability($readGrant, array_replace($query, ['user_ids' => [(int)$items['other_root']->get('IDuser') ?: PHP_INT_MAX]])), 'Outsiders cannot be probed');
    $items['external']->set('last_sync_error', 'PRIVATE cached error'); $items['external']->save();
    mcpCheck(McpCalendar::availability($readGrant, $query)['incomplete'], 'Failed external calendars explicitly mark availability as unverified');
    $items['external']->set('last_sync_error', null); $items['external']->save();
    $args = ['title' => 'Created event', 'holon_id' => $hid, 'request_key' => 'calendar-default-once',
        'start_at' => $day->setTime(9, 0)->format(DateTimeInterface::ATOM), 'end_at' => $day->setTime(10, 0)->format(DateTimeInterface::ATOM)];
    mcpCalendarDenied(fn() => McpCalendar::create($readGrant, $args), 'Read grant never permits event creation');
    foreach (['other_root', 'hidden', 'inactive', 'root'] as $fixture) {
        mcpCalendarDenied(fn() => McpCalendar::create($grant, array_replace($args, ['holon_id' => (int)$items[$fixture]->getId()])), 'Unavailable or unauthorized holon rejected');
    }
    $conflicted = array_replace($args, ['start_at' => $day->setTime(10, 0)->format(DateTimeInterface::ATOM), 'end_at' => $day->setTime(11, 0)->format(DateTimeInterface::ATOM)]);
    $warning = McpCalendar::create($grant, $conflicted);
    mcpCheck(!$warning['created'] && $warning['requires_confirmation'] && !str_contains(json_encode($warning), 'Other MCP'), 'Conflict prevents creation and hides other organization labels');
    $created = McpCalendar::create($grant, $args);
    $items['created_default'] = new Event(); $items['created_default']->load($created['event_id'], true);
    mcpCheck($created['created'] && !$created['replayed'] && $created['invitation_mode'] === 'default' && $created['invitation_counts']['members'] === 2, 'Default holon invitations preserved');
    mcpCheck(McpCalendar::create($grant, $args)['event_id'] === $created['event_id'], 'Same event request never creates duplicates');
    mcpCalendarDenied(fn() => McpCalendar::create($grant, array_replace($args, ['title' => 'Different'])), 'Changed request payload cannot reuse a key');
    $explicit = array_replace($args, ['request_key' => 'calendar-explicit-once', 'invitation_user_ids' => [$mid],
        'start_at' => $day->setTime(11, 0)->format(DateTimeInterface::ATOM), 'end_at' => $day->setTime(12, 0)->format(DateTimeInterface::ATOM)]);
    $selected = McpCalendar::create($grant, $explicit);
    $items['created_explicit'] = new Event(); $items['created_explicit']->load($selected['event_id'], true);
    mcpCheck($selected['invitation_counts']['members'] === 1 && $selected['invitation_mode'] === 'explicit'
        && $items['created_explicit']->getEffectiveInvitationTargets($oid)['userIds'] === [$mid], 'Explicit audience excludes the host holon and unrequested organizer');
    $foreign = array_replace($explicit, ['request_key' => 'calendar-foreign', 'invitation_holon_ids' => [(int)$items['other_root']->getId()]]);
    mcpCalendarDenied(fn() => McpCalendar::create($grant, $foreign), 'Foreign invitation holons rejected');
    $email = array_replace($explicit, ['request_key' => 'calendar-email-once', 'invitation_user_ids' => [], 'invitation_emails' => ['guest@example.invalid']]);
    $emailWarning = McpCalendar::create($grant, $email);
    mcpCheck(!$emailWarning['created'] && $emailWarning['availability']['unverified'][0]['reason'] === 'email', 'External guest availability requires acknowledgement');
    $email['allow_conflicts'] = true; $emailed = McpCalendar::create($grant, $email);
    $items['created_email'] = new Event(); $items['created_email']->load($emailed['event_id'], true);
    mcpCheck($emailed['invitation_counts']['invitedEmails'] === 1 && !$emailed['emails_sent'], 'External guests are stored without sending emails');
    $utc = array_replace($args, ['request_key' => 'calendar-utc-once', 'start_at' => $day->setTime(13, 0)->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM),
        'end_at' => $day->setTime(14, 0)->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM)]);
    $utcCreated = McpCalendar::create($grant, $utc);
    $items['created_utc'] = new Event(); $items['created_utc']->load($utcCreated['event_id'], true);
    mcpCheck((new DateTimeImmutable($utcCreated['start_at']))->format('H:i') === '13:00', 'Timestamp offsets are preserved through storage and converted to the event timezone');
    $allDay = array_replace($args, ['request_key' => 'calendar-all-day-once', 'is_all_day' => true, 'allow_conflicts' => true,
        'start_at' => '2026-10-25T00:00:00+02:00', 'end_at' => '2026-10-25T23:00:00+01:00']);
    $allDayCreated = McpCalendar::create($grant, $allDay);
    $items['created_all_day'] = new Event(); $items['created_all_day']->load($allDayCreated['event_id'], true);
    mcpCheck($allDayCreated['is_all_day'] && $allDayCreated['start_at'] === '2026-10-25T00:00:00+02:00'
        && $allDayCreated['end_at'] === '2026-10-25T23:59:59+01:00', 'All-day events follow native inclusive ends through the daylight-saving change');
    $invalidLocation = array_replace($args, ['request_key' => 'calendar-location-retry', 'locationmode' => 'virtual', 'allow_conflicts' => true]);
    mcpCalendarDenied(fn() => McpCalendar::create($grant, $invalidLocation), 'Native location validation rolls back creation');
    $repaired = McpCalendar::create($grant, $invalidLocation + ['videomeetingurl' => 'https://example.org/meeting']);
    $items['created_repaired'] = new Event(); $items['created_repaired']->load($repaired['event_id'], true);
    mcpCheck($repaired['created'], 'Rolled-back key can be corrected and retried');
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    $http = mcpCalendarHttp($tokens['access_token'], 'omo_get_availability', $query);
    mcpCheck(!$http['isError'] && count($http['structuredContent']['members']) === 2, 'Availability works through bearer-authenticated HTTP');
    $http = mcpCalendarHttp($tokens['access_token'], 'omo_create_event', $explicit);
    mcpCheck(!$http['isError'] && $http['structuredContent']['replayed'] && $http['structuredContent']['event_id'] === $selected['event_id'], 'HTTP retry retrieves persisted event and invitations');
    $group = array_replace($explicit, ['request_key' => 'calendar-group-once', 'invitation_holon_ids' => [$hid], 'allow_conflicts' => true]);
    $groupCreated = McpCalendar::create($grant, $group);
    $items['created_group'] = new Event(); $items['created_group']->load($groupCreated['event_id'], true);
    mcpCheck($groupCreated['invitation_counts']['members'] === 2, 'Individual and holon invitations deduplicate overlapping memberships');
    $items['created_group']->delete();
    mcpCalendarDenied(fn() => McpCalendar::create($grant, $group), 'Deleted events cannot be recreated by replay');
    $items['assignment_' . $uid]->set('active', 0); $items['assignment_' . $uid]->save();
    mcpCalendarDenied(fn() => McpCalendar::create($grant, array_replace($args, ['request_key' => 'calendar-rights-removed'])), 'Current permission loss prevents creation');
    mcpCheck(McpCalendar::spaces($grant, [])['items'] === [], 'Discovery responds to permission removal');
    McpOauthGrant::revokeOwned((int)$grant['id'], $uid);
    mcpCalendarDenied(fn() => McpCalendar::create($grant, $args), 'Revoked grant cannot create or replay');
    echo "mcp_calendar_test: OK (availability, imported calendars, privacy, rights, defaults, explicit invites, conflicts, replay, rollback and HTTP)\n";
} finally { mcpCleanup($items); $_SESSION = $before; }
