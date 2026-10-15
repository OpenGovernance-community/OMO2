<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/user_availability.php';
require_once dirname(__DIR__) . '/common/caldav.php';
require_once dirname(__DIR__) . '/omo/api/calendar/permissions_shared.php';
require_once dirname(__DIR__) . '/common/calendar/share-feed.php';
require_once dirname(__DIR__) . '/meeting/service.php';

use dbObject\DbObject;
use dbObject\Event;
use dbObject\EventInvitation;
use dbObject\User;

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $endpoint = $request['endpoint'] ?? 'create.php';
    personalBufferExpect(in_array($endpoint, ['create.php', 'index.php', 'detail.php', 'invitations_popup.php'], true), 'Valid endpoint.');
    $_SERVER['REQUEST_URI'] = '/omo/api/calendar/' . $endpoint;
    $_SERVER['REQUEST_METHOD'] = $request['method'];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    if (!empty($request['verify_session_cache'])) { ob_start(); }
    require dirname(__DIR__) . '/omo/api/calendar/' . $endpoint;
    if (!empty($request['verify_session_cache'])) {
        $html = ob_get_clean();
        personalBufferExpect(session_status() !== PHP_SESSION_ACTIVE, 'Detail rendering releases its session lock.');
        $_SESSION = [];
        session_start();
        personalBufferExpect(!empty($_SESSION['applicationCatalogs']) && !empty($_SESSION['permissionCacheByOrganization'])
            && !empty($_SESSION['omo_event_recurrence_csrf']), 'Application/permission caches and action token are persisted before release.');
        session_write_close();
        echo $html;
    }
    exit;
}
function personalBufferExpect(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
function personalBufferRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    personalBufferExpect(is_resource($process), 'Start endpoint request.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    personalBufferExpect(proc_close($process) === 0 && $errors === '', 'Endpoint failed: ' . $output . $errors);
    return $output;
}
$fixtures = [];
$fixture = static function (string $class, array $fields) use (&$fixtures): DbObject {
    $object = new $class();
    foreach ($fields as $key => $value) { $object->set($key, $value); }
    personalBufferExpect(!empty($object->save()['status']), 'Fixture failed: ' . $class);
    $fixtures[] = $object;
    return $object;
};
try {
    $nonce = bin2hex(random_bytes(6));
    $people = [];
    foreach (['owner', 'first', 'second', 'outsider'] as $name) {
        $people[$name] = $fixture(User::class, ['email' => $name . '-' . $nonce . '@example.invalid', 'active' => 1]);
    }
    $ownerId = (int)$people['owner']->getId(); $firstId = (int)$people['first']->getId(); $secondId = (int)$people['second']->getId();
    $org = $fixture(\dbObject\Organization::class, ['name' => 'Personal buffers', 'shortname' => $nonce]);
    $organizationId = (int)$org->getId();
    foreach ($people as $person) {
        $fixture(\dbObject\UserOrganization::class, ['IDuser' => $person->getId(), 'IDorganization' => $organizationId, 'active' => 1]);
    }
    $holon = $fixture(\dbObject\Holon::class, ['name' => 'Invited group', 'IDorganization' => $organizationId, 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $holon->set('IDholon_org', $holon->getId()); $holon->save();
    $fixture(\dbObject\HolonPermission::class, ['IDholon' => $holon->getId(),
        'IDpermission' => \dbObject\Permission::findByKey('CAN_EDIT_EVENT')->getId(), 'member_type' => 'admin', 'range' => 'self']);
    foreach (['first', 'second'] as $name) {
        $fixture(\dbObject\UserHolon::class, ['IDholon' => $holon->getId(), 'IDuser' => $people[$name]->getId(), 'active' => 1, 'is_membership' => 1]);
    }
    foreach (['calendar', 'structure'] as $hash) {
        $app = new \dbObject\Application(); $app->load([['hash', $hash]]);
        $fixture(\dbObject\OrganizationApplication::class, ['IDapplication' => $app->getId(), 'IDorganization' => $organizationId, 'active' => 1]);
    }
    $day = new DateTimeImmutable('2030-01-07');
    $event = $fixture(Event::class, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser' => $ownerId,
        'title' => 'Original title', 'status' => Event::STATUS_CONFIRMED, 'active' => 1,
        'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0)]);
    $groupInvitation = $fixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDholon' => $holon->getId(),
        'invitation_type' => 'holon', 'status' => 'invited', 'active' => 1]);
    personalBufferExpect(!omoCalendarCanEditEvent($event, $organizationId, $firstId, $holon, false), 'Invitee has no event edit permission.');
    personalBufferExpect($event->canEditTimeBuffers($firstId) && $event->canEditTimeBuffers($secondId), 'Group members can edit personal times.');
    personalBufferExpect(!$event->canEditTimeBuffers((int)$people['outsider']->getId()), 'Membership alone does not grant personal event access.');
    personalBufferExpect(!$event->canEditTimeBuffers($ownerId), 'An uninvited creator has no personal attendance times.');
    $fixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDuser' => $ownerId, 'invitation_type' => 'user', 'active' => 1]);
    personalBufferExpect($event->saveTimeBuffers($ownerId, 30, 45) && $event->saveTimeBuffers($secondId, 15, 0), 'Independent owner/second settings.');
    $request = ['session' => ['currentUser' => $firstId, 'currentOrganization' => $organizationId,
        'auth_security_version' => (int)$people['first']->get('security_version'), 'omo_event_time_buffers_csrf' => 'test-token'],
        'get' => ['oid' => $organizationId, 'id' => $event->getId()], 'post' => [], 'method' => 'GET'];
    $html = personalBufferRequest($request);
    personalBufferExpect(str_contains($html, 'data-omo-calendar-personal-time-buffers-form') && str_contains($html, 'Original title')
        && str_contains($html, '07.01.2030 10:00'), 'Invitee sees summary and personal controls.');
    foreach (['title', 'start_at', 'end_at', 'description', 'invitation_user_ids', 'document_type', 'status'] as $field) {
        personalBufferExpect(!str_contains($html, 'name="' . $field . '"'), 'Shared fields are absent: ' . $field);
    }
    $request['method'] = 'POST';
    $request['post'] = ['csrf' => 'test-token', 'time_buffers_enabled' => 1, 'preparation_minutes' => 120, 'closing_minutes' => 20,
        'IDuser' => $ownerId, 'title' => 'Tampered', 'status' => 'cancelled', 'start_at' => '2030-01-01T00:00',
        'public_registration_present' => 1, 'public_registration' => 1,
        'invitation_user_ids' => [(int)$people['outsider']->getId()]];
    $outside = $request;
    $outside['session']['currentUser'] = (int)$people['outsider']->getId();
    $outside['session']['auth_security_version'] = (int)$people['outsider']->get('security_version');
    personalBufferExpect(json_decode(personalBufferRequest($outside), true)['status'] === false, 'An uninvited organization member cannot submit personal times.');
    $bad = $request; $bad['post']['csrf'] = 'invalid';
    personalBufferExpect(json_decode(personalBufferRequest($bad), true)['status'] === false, 'CSRF required.');
    foreach ([-1, '1.5', 1441, [30]] as $invalid) {
        $bad = $request; $bad['post']['preparation_minutes'] = $invalid;
        personalBufferExpect(json_decode(personalBufferRequest($bad), true)['status'] === false, 'Invalid durations rejected.');
    }
    personalBufferExpect(json_decode(personalBufferRequest($request), true)['status'] === true, 'Invited user saves without event edit rights.');
    $event->load((int)$event->getId(), true);
    personalBufferExpect($event->getTimeBuffers($firstId) === [120, 20] && $event->getTimeBuffers($secondId) === [15, 0]
        && $event->getTimeBuffers($ownerId) === [30, 45], 'Saving one participant does not alter another.');
    personalBufferExpect($event->get('title') === 'Original title' && $event->get('status') === Event::STATUS_CONFIRMED
        && $event->get('start_at')->format('H:i') === '10:00' && count($event->getInvitations(true)) === 2, 'Forged fields cannot modify the event or invitations.');
    personalBufferExpect(\dbObject\EventPublicLink::forEvent((int)$event->getId()) === null, 'Personal time access cannot enable public registration.');
    [$firstStart, $firstEnd] = $event->getBusyInterval($firstId);
    [$secondStart, $secondEnd] = $event->getBusyInterval($secondId);
    personalBufferExpect($firstStart->format('H:i') === '08:00' && $firstEnd->format('H:i') === '11:20'
        && $secondStart->format('H:i') === '09:45' && $secondEnd->format('H:i') === '11:00', 'Each person has their own occupied interval.');
    personalBufferExpect($event->getBusyInterval()[0]->format('H:i') === '10:00', 'No personal buffers without a viewer.');
    personalBufferExpect(count(commonUserAvailabilityLoadBusyIntervals($firstId, $day->setTime(8, 30), $day->setTime(9, 0))) === 1
        && commonUserAvailabilityLoadBusyIntervals($secondId, $day->setTime(8, 30), $day->setTime(9, 0)) === [], 'Busy query uses only the requested participant buffers.');
    $firstIcs = commonCalDavBuildEventCalendarData($org, $event, $firstId);
    $secondIcs = commonCalDavBuildEventCalendarData($org, $event, $secondId);
    personalBufferExpect(str_contains($firstIcs, 'X-OMO-PREPARATION-MINUTES:120')
        && str_contains($secondIcs, 'X-OMO-PREPARATION-MINUTES:15'), 'Personal CalDAV exports do not share buffers.');
    personalBufferExpect(str_contains($firstIcs, 'TRIGGER;RELATED=START:-PT120M')
        && str_contains($secondIcs, 'TRIGGER;RELATED=START:-PT15M')
        && str_contains($firstIcs, 'TRIGGER;RELATED=START:-PT5M')
        && str_contains($secondIcs, 'TRIGGER;RELATED=START:-PT5M'), 'Each participant receives their own preparation reminder plus five minutes.');
    foreach ([$firstId => 480, $secondId => 585] as $viewerId => $expectedStart) {
        $calendarRequest = $request;
        $calendarRequest['method'] = 'GET'; $calendarRequest['post'] = []; $calendarRequest['endpoint'] = 'index.php';
        $calendarRequest['session']['currentUser'] = $viewerId;
        $calendarRequest['get'] = ['oid' => $organizationId, 'view' => 'week', 'date' => $day->format('Y-m-d')];
        $calendarHtml = personalBufferRequest($calendarRequest);
        preg_match('/<script[^>]*data-omo-calendar-data[^>]*>(.*?)<\/script>/s', $calendarHtml, $matches);
        $data = json_decode($matches[1] ?? '', true);
        personalBufferExpect(is_array($data), 'Calendar payload renders for invited member: ' . substr($calendarHtml, 0, 200));
        $bands = array_values(array_filter($data['items'], static fn($item) => ($item['bufferKind'] ?? '') === 'before'
            && (int)$item['id'] === (int)$event->getId()));
        personalBufferExpect(count($bands) === 1 && $bands[0]['startMinute'] === $expectedStart, 'Calendar shows only the viewer personal time.');
        $parent = array_values(array_filter($data['items'], static fn($item) => empty($item['bufferKind'])
            && (int)$item['id'] === (int)$event->getId()));
        personalBufferExpect(count(array_filter($parent, static fn($item) => !empty($item['canEdit']))) > 0, 'Invited member can open edit from calendar.');
    }
    $request['post'] = ['csrf' => 'test-token'];
    personalBufferExpect(json_decode(personalBufferRequest($request), true)['status'] === true
        && $event->getTimeBuffers($firstId) === [0, 0] && $event->getTimeBuffers($secondId) === [15, 0], 'Disabling resets only own times.');
    $groupInvitation->set('status', 'revoked'); $groupInvitation->save();
    personalBufferExpect(json_decode(personalBufferRequest($request), true)['status'] === false, 'Revoked invitation loses edit access.');
    foreach (['user' => ['IDuser' => $firstId], 'email' => ['email' => $people['first']->get('email')]] as $type => $target) {
        $invite = $fixture(EventInvitation::class, $target + ['IDevent' => $event->getId(), 'invitation_type' => $type, 'active' => 1]);
        personalBufferExpect(json_decode(personalBufferRequest($request), true)['status'] === true, 'Direct invitation works: ' . $type);
        $invite->set('status', 'revoked'); $invite->save();
    }
    $groupInvitation->set('status', 'invited'); $groupInvitation->save();
    $fixture(\dbObject\HolonPermission::class, ['IDholon' => $holon->getId(),
        'IDpermission' => \dbObject\Permission::findByKey('CAN_EDIT_EVENT')->getId(), 'member_type' => 'member', 'range' => 'self']);
    $full = $request; $full['method'] = 'GET'; $full['post'] = [];
    $fullHtml = personalBufferRequest($full);
    personalBufferExpect(str_contains($fullHtml, 'name="title"'), 'Event permission restores the full editor.');
    personalBufferExpect(str_contains($fullHtml, 'data-omo-calendar-invitations-placeholder')
        && !str_contains($fullHtml, 'data-omo-calendar-holon-list'), 'The initial editor keeps selections without building invitation choices.');
    preg_match_all('/<input type="checkbox" name="invitation_(holon|user)_ids\[\]" value="(\d+)" checked>/', $fullHtml, $selectionFields, PREG_SET_ORDER);
    $initialSelections = ['holon' => [], 'user' => []];
    foreach ($selectionFields as $field) { $initialSelections[$field[1]][] = (int)$field[2]; }
    personalBufferExpect($initialSelections['holon'] === [(int)$holon->getId()] && $initialSelections['user'] === [$ownerId],
        'Unopened invitation tab submits the original group and individual selections.');
    $fragment = $full; $fragment['get']['invitation_editor'] = 1;
    $fragmentHtml = personalBufferRequest($fragment);
    personalBufferExpect(str_contains($fragmentHtml, 'data-omo-calendar-holon-list') && !str_contains($fragmentHtml, 'name="title"'),
        'The invitation fragment contains just the authorized editor choices.');
    $detail = $full; $detail['endpoint'] = 'detail.php'; $detail['verify_session_cache'] = true;
    $detailHtml = personalBufferRequest($detail);
    personalBufferExpect(str_contains($detailHtml, 'data-calendar-invitations-lazy') && !str_contains($detailHtml, 'omo-calendar-detail__summary-head'),
        'Event detail defers the invitation summary.');
    file_put_contents(dirname(__DIR__) . '/tmp/calendar-invitations-detail.html', $detailHtml);
    $detail['get']['invitation_summary'] = 1;
    unset($detail['verify_session_cache']);
    $summaryHtml = personalBufferRequest($detail);
    personalBufferExpect(str_contains($summaryHtml, 'omo-calendar-detail__summary-head') && str_contains($summaryHtml, 'Invited group'),
        'Opening the invitation section renders its invited group.');
    file_put_contents(dirname(__DIR__) . '/tmp/calendar-invitations-summary.html', $summaryHtml);
    $nonMember = $fixture(User::class, ['email' => 'non-member-' . $nonce . '@example.invalid', 'active' => 1]);
    $deniedFragment = $fragment; $deniedFragment['session']['currentUser'] = (int)$nonMember->getId();
    personalBufferExpect(!str_contains(personalBufferRequest($deniedFragment), 'data-omo-calendar-holon-list'), 'The fragment keeps the editor permission checks.');
    $full['method'] = 'POST';
    $full['post'] = ['title' => 'Edited with permission', 'status' => Event::STATUS_CONFIRMED, 'IDholon' => $holon->getId(),
        'start_at' => '2030-01-07T10:00', 'end_at' => '2030-01-07T11:00',
        'invitation_holon_ids' => [$holon->getId()], 'time_buffers_enabled' => 1, 'preparation_minutes' => 60, 'closing_minutes' => 30];
    personalBufferExpect(json_decode(personalBufferRequest($full), true)['status'] === true, 'Full editor saves current participant personal times.');
    $event->load((int)$event->getId(), true);
    personalBufferExpect($event->get('title') === 'Edited with permission' && $event->getTimeBuffers($firstId) === [60, 30]
        && $event->getTimeBuffers($ownerId) === [30, 45] && $event->getTimeBuffers($secondId) === [15, 0], 'Full event editor does not overwrite other participants times.');

    // A creator manages events in their context without becoming a participant.
    $managed = $fixture(Event::class, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser' => $firstId,
        'title' => 'UNINVITED managed appointment', 'status' => Event::STATUS_CONFIRMED, 'active' => 1,
        'start_at' => $day->setTime(14, 0), 'end_at' => $day->setTime(15, 0)]);
    $fixture(EventInvitation::class, ['IDevent' => $managed->getId(), 'invitation_type' => 'user', 'IDuser' => $secondId]);
    $fixture(\dbObject\EventTimeBuffer::class, ['IDevent' => $managed->getId(), 'IDuser' => $firstId, 'preparation_minutes' => 120]);
    $contextOnly = $fixture(Event::class, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser' => $firstId,
        'title' => 'UNINVITED context appointment', 'status' => Event::STATUS_CONFIRMED, 'active' => 1,
        'start_at' => $day->setTime(14, 0), 'end_at' => $day->setTime(15, 0)]);
    personalBufferExpect(!$managed->isInvitedToEvent($firstId) && !$contextOnly->isInvitedToEvent($firstId)
        && !$contextOnly->isInvitedToEvent($secondId), 'Creation and context membership alone are not invitations.');
    personalBufferExpect(!$managed->canEditTimeBuffers($firstId) && !$managed->saveTimeBuffers($firstId, 60, 0),
        'A stale personal buffer does not grant attendance access.');
    personalBufferExpect(commonUserAvailabilityLoadBusyIntervals($firstId, $day->setTime(14, 0), $day->setTime(15, 0)) === []
        && count(commonUserAvailabilityLoadBusyIntervals($secondId, $day->setTime(14, 0), $day->setTime(15, 0))) === 1,
        'Only the invited participant is busy.');
    $bookingCalendar = $fixture(\dbObject\ExternalCalendar::class, ['IDuser' => $firstId, 'provider' => 'caldav',
        'title' => 'Local regression cache', 'calendar_url' => 'https://example.invalid/calendar', 'username' => 'fixture',
        'password_encrypted' => 'unused', 'active' => 1, 'last_sync_at' => $day]);
    $profile = new \dbObject\MeetingProfile(); $profile->set('IDuser', $firstId); $profile->set('IDexternalcalendar', $bookingCalendar->getId());
    personalBufferExpect(meetingBusy($profile, $day->setTime(14, 0), $day->setTime(15, 0)) === [],
        'Uninvited events leave public booking availability free.');
    $proposed = new Event();
    foreach (['IDorganization' => $organizationId, 'IDuser' => $secondId, 'start_at' => $day->setTime(14, 0),
        'end_at' => $day->setTime(15, 0)] as $field => $value) { $proposed->set($field, $value); }
    $firstInvite = new EventInvitation(); $firstInvite->set('invitation_type', 'user'); $firstInvite->set('IDuser', $firstId);
    personalBufferExpect($proposed->checkInvitationAvailability([$firstInvite])['conflicts'] === [],
        'Availability checks exclude both uninvited existing events and the uninvited proposed creator.');
    $calendarRequest = $request;
    $calendarRequest['method'] = 'GET'; $calendarRequest['post'] = []; $calendarRequest['endpoint'] = 'index.php';
    $calendarRequest['get'] = ['oid' => $organizationId, 'cid' => $holon->getId(), 'view' => 'week', 'date' => $day->format('Y-m-d')];
    $calendarHtml = personalBufferRequest($calendarRequest);
    preg_match('/<script[^>]*data-omo-calendar-data[^>]*>(.*?)<\/script>/s', $calendarHtml, $matches);
    $data = json_decode($matches[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
    foreach ([$managed, $contextOnly] as $uninvited) {
        $items = array_values(array_filter($data['items'], static fn($item) => (int)$item['id'] === (int)$uninvited->getId()));
        personalBufferExpect($items !== [] && count(array_filter($items, static fn($item) => !empty($item['isFaded']))) === count($items)
            && count(array_filter($items, static fn($item) => !empty($item['canEdit']))) > 0
            && count(array_filter($items, static fn($item) => !empty($item['bufferKind']) || !empty($item['hasTimeBuffers']))) === 0,
            'Managed events remain faded and editable in their circle, without personal buffer bands.');
    }
    $manageRequest = $calendarRequest; $manageRequest['endpoint'] = 'create.php'; $manageRequest['get']['id'] = $managed->getId();
    $manageHtml = personalBufferRequest($manageRequest);
    personalBufferExpect(str_contains($manageHtml, 'name="title"') && !str_contains($manageHtml, 'name="preparation_minutes"'),
        'The uninvited creator can use event edit rights without personal time controls.');
    $otherOrg = $fixture(\dbObject\Organization::class, ['name' => 'Other invitation scope', 'shortname' => 'scope-' . $nonce]);
    $otherId = (int)$otherOrg->getId();
    $calendarApp = new \dbObject\Application(); $calendarApp->load([['hash', 'calendar']]);
    $fixture(\dbObject\OrganizationApplication::class, ['IDapplication' => $calendarApp->getId(), 'IDorganization' => $otherId, 'active' => 1]);
    foreach ([$firstId, $secondId] as $memberId) {
        $fixture(\dbObject\UserOrganization::class, ['IDuser' => $memberId, 'IDorganization' => $otherId, 'active' => 1]);
    }
    $cross = $fixture(Event::class, ['IDorganization' => $otherId, 'IDuser' => $firstId, 'title' => 'UNINVITED cross organization',
        'status' => Event::STATUS_CONFIRMED, 'active' => 1, 'start_at' => $day->setTime(14, 0), 'end_at' => $day->setTime(15, 0)]);
    $fixture(EventInvitation::class, ['IDevent' => $cross->getId(), 'invitation_type' => 'user', 'IDuser' => $secondId]);
    personalBufferExpect(\dbObject\ArrayEvent::otherOrganizationBusyBlocks($firstId, $organizationId, $day, $day->modify('+1 day')) === [],
        'Other organizations do not import uninvited creator appointments.');
    personalBufferExpect(count(\dbObject\ArrayEvent::otherOrganizationBusyBlocks($secondId, $organizationId, $day, $day->modify('+1 day'))) === 1,
        'Invited participants still receive other-organization busy blocks.');
    $calendarHtml = personalBufferRequest($calendarRequest);
    preg_match('/<script[^>]*data-omo-calendar-data[^>]*>(.*?)<\/script>/s', $calendarHtml, $matches);
    $data = json_decode($matches[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
    personalBufferExpect(count(array_filter($data['items'], static fn($item) => !empty($item['isOtherOrganization']))) === 0,
        'The rendered calendar imports no uninvited events from other organizations.');
    $group = $fixture(EventInvitation::class, ['IDevent' => $contextOnly->getId(), 'invitation_type' => 'holon', 'IDholon' => $holon->getId()]);
    personalBufferExpect($contextOnly->isInvitedToEvent($firstId), 'An explicit circle invitation counts as attendance.');
    $group->set('status', 'revoked'); $group->save();
    personalBufferExpect(!$contextOnly->isInvitedToEvent($firstId), 'Revoking the last invitation cannot fall back to circle membership.');
    $email = $fixture(EventInvitation::class, ['IDevent' => $contextOnly->getId(), 'invitation_type' => 'email', 'email' => $people['first']->get('email')]);
    personalBufferExpect($contextOnly->isInvitedToEvent($firstId), 'An explicit scoped email invitation counts as attendance.');
    $email->set('status', 'revoked'); $email->save();
    $calendars = commonCalDavLoadCalendarsForViewer($people['first']);
    personalBufferExpect(isset($calendars['organization-' . $otherId]) && $calendars['organization-' . $otherId]['events'] === [],
        'The other organization CalDAV calendar is empty for its uninvited creator.');
    $scoped = commonCalDavLoadScopedCalendarForViewer($people['first'], $organizationId, (int)$holon->getId(), 'contextual', 'blue');
    personalBufferExpect(is_array($scoped) && count($scoped['events']) === 1, 'Scoped CalDAV includes only the invited group event.');
    $calendarJson = json_encode($calendars) . json_encode($scoped);
    personalBufferExpect(!str_contains($calendarJson, 'UNINVITED') && str_contains($calendarJson, 'Edited with permission'),
        'Both organization and scoped CalDAV exclude uninvited management events.');
    $share = $fixture(\dbObject\CalendarShare::class, ['IDuser' => $firstId, 'label' => 'Invitation regression', 'months' => 3,
        'token' => bin2hex(random_bytes(32)), 'details' => 1, 'active' => 1]);
    $feed = calendarShareBuildFeed($share, $day);
    personalBufferExpect(!str_contains($feed, 'UNINVITED') && str_contains($feed, 'SUMMARY:Edited with permission'),
        'Personal ICS exports exclude all uninvited events.');
    $share->set('scope_key', \dbObject\CalendarShare::buildScopedCalendarKey($organizationId, (int)$holon->getId(), 'contextual'));
    $feed = calendarShareBuildFeed($share, $day);
    personalBufferExpect(!str_contains($feed, 'UNINVITED') && str_contains($feed, 'SUMMARY:Edited with permission'),
        'Scoped ICS exports also exclude uninvited circle events.');
    // Event details must render every accessible attachment, not just the first PV.
    $documentBase = ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(),
        'IDuser' => $firstId, 'IDevent' => $event->getId(), 'active' => 1];
    $pv = $fixture(\dbObject\Document::class, $documentBase + ['documenttype' => 'pv', 'title' => 'Meeting PV']);
    $miro = $fixture(\dbObject\Document::class, $documentBase + ['documenttype' => 'external_link',
        'title' => 'Miro workshop board', 'externalurl' => 'https://miro.com/app/board/test-meeting/']);
    $file = $fixture(\dbObject\Document::class, $documentBase + ['documenttype' => 'uploaded_file', 'title' => 'Meeting attachment']);
    $shared = $fixture(\dbObject\Document::class, array_replace($documentBase, ['IDevent' => null,
        'documenttype' => 'external_link', 'title' => 'Shared reference', 'externalurl' => 'https://example.invalid/reference']));
    foreach ([$miro, $file, $shared] as $document) {
        personalBufferExpect(!empty($document->saveVisibilityRule('organization')['status']), 'Visible attachment');
    }
    foreach ([$miro, $shared] as $document) {
        $fixture(\dbObject\EventSharedDocument::class, ['IDevent' => $event->getId(), 'IDdocument' => $document->getId()]);
    }
    $private = $fixture(\dbObject\Document::class, array_replace($documentBase, ['IDuser' => $ownerId,
        'documenttype' => 'external_link', 'title' => 'Private reference', 'externalurl' => 'https://example.invalid/private']));
    personalBufferExpect(!empty($private->saveVisibilityRule('self')['status']), 'Private attachment');
    $archived = $fixture(\dbObject\Document::class, $documentBase + ['documenttype' => 'external_link', 'title' => 'Archived reference']);
    $archived->set('active', 0); $archived->save();
    $documentDetail = ['session' => ['currentUser' => $firstId, 'currentOrganization' => $organizationId,
        'auth_security_version' => (int)$people['first']->get('security_version')],
        'get' => ['oid' => $organizationId, 'id' => $event->getId()], 'post' => [], 'method' => 'GET', 'endpoint' => 'detail.php'];
    $documentHtml = personalBufferRequest($documentDetail);
    foreach ([$pv, $miro, $file, $shared] as $document) {
        personalBufferExpect(substr_count($documentHtml, 'data-omo-calendar-associated-document="' . $document->getId() . '"') === 1,
            'Each associated document appears once: ' . $document->get('title'));
        personalBufferExpect(str_contains($documentHtml, '/omo/api/documents/detail.php?id=' . $document->getId() . '&amp;'),
            'Each open action targets its own document');
    }
    personalBufferExpect(!str_contains($documentHtml, 'Private reference') && !str_contains($documentHtml, 'Archived reference'),
        'Inaccessible and archived attachments remain hidden');
    file_put_contents(dirname(__DIR__) . '/tmp/calendar-event-documents.html', $documentHtml);

    // Public registration is the same event setting in the editor, detail and PV.
    $publicEdit = $full;
    $publicEdit['post']['public_registration_present'] = 1;
    $publicEdit['post']['public_registration'] = 1;
    personalBufferExpect(json_decode(personalBufferRequest($publicEdit), true)['status'] === true, 'Editor enables public registration.');
    $publicLink = \dbObject\EventPublicLink::forEvent((int)$event->getId());
    personalBufferExpect($publicLink && (int)$publicLink->get('enabled') === 1, 'Public link persisted.');
    $fixtures[] = $publicLink;
    $publicPath = $publicLink->publicPath();
    $fixture(\dbObject\EventPublicRegistration::class, ['IDevent' => $event->getId(), 'name' => 'Public test guest',
        'email' => 'public-test@example.invalid', 'token' => bin2hex(random_bytes(32)), 'confirmed_at' => new DateTimeImmutable()]);
    $pv->set('IDuser_pv_editor', $firstId); $pv->save();
    $popup = $fragment; $popup['endpoint'] = 'invitations_popup.php';
    unset($popup['get']['invitation_editor']);
    $pvPopup = $popup; $pvPopup['get']['pv_editor'] = 1;
    foreach ([$fragment, $popup, $pvPopup] as $publicRequest) {
        $publicHtml = personalBufferRequest($publicRequest);
        personalBufferExpect(str_contains($publicHtml, 'data-omo-public-registration-toggle checked')
            && str_contains($publicHtml, $publicPath) && str_contains($publicHtml, 'Public test guest'),
            'All three entry points show the same enabled setting, link and registrants.');
    }
    $unopened = $fragment; unset($unopened['get']['invitation_editor']);
    personalBufferExpect(str_contains(personalBufferRequest($unopened), 'name="public_registration" value="1" checked'),
        'Unopened invitation tab preserves the enabled public setting.');
    $failed = $publicEdit; $failed['post']['title'] = ''; unset($failed['post']['public_registration']);
    personalBufferExpect(json_decode(personalBufferRequest($failed), true)['status'] === false
        && (int)\dbObject\EventPublicLink::forEvent((int)$event->getId())->get('enabled') === 1,
        'Invalid event edits cannot disable public registration.');
    unset($publicEdit['post']['public_registration']);
    personalBufferExpect(json_decode(personalBufferRequest($publicEdit), true)['status'] === true
        && (int)\dbObject\EventPublicLink::forEvent((int)$event->getId())->get('enabled') === 0,
        'Editor disables public registration.');
    foreach ([$fragment, $popup, $pvPopup] as $publicRequest) {
        $publicHtml = personalBufferRequest($publicRequest);
        personalBufferExpect(!str_contains($publicHtml, 'data-omo-public-registration-toggle checked')
            && !str_contains($publicHtml, $publicPath) && str_contains($publicHtml, 'Public test guest'),
            'Disabling hides the public link but retains registrants in all three editors.');
    }
    foreach ([$popup, $pvPopup] as $publicRequest) {
        $publicRequest['method'] = 'POST';
        $publicRequest['session']['omo_calendar_invitations_csrf'] = 'public-test-token';
        $publicRequest['post'] = ['csrf' => 'public-test-token', 'holon_ids' => [$holon->getId()], 'public_registration' => 1];
        $result = json_decode(personalBufferRequest($publicRequest), true);
        personalBufferExpect($result['status'] === true && str_ends_with($result['publicUrl'], $publicPath),
            'Detail and PV reuse the same public link when enabling registration.');
    }
    personalBufferExpect(json_decode(personalBufferRequest($full), true)['status'] === true
        && (int)\dbObject\EventPublicLink::forEvent((int)$event->getId())->get('enabled') === 1,
        'Older event forms without a public setting preserve the link.');
    echo "calendar_personal_time_buffers_test: OK\n";
} finally {
    foreach (array_reverse($fixtures) as $object) { $object->delete(); }
}
