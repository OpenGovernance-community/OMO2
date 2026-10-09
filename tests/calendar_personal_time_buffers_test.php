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
    personalBufferExpect(in_array($endpoint, ['create.php', 'index.php', 'detail.php'], true), 'Valid endpoint.');
    $_SERVER['REQUEST_URI'] = '/omo/api/calendar/' . $endpoint;
    $_SERVER['REQUEST_METHOD'] = $request['method'];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    require dirname(__DIR__) . '/omo/api/calendar/' . $endpoint;
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
    personalBufferExpect(str_contains(personalBufferRequest($full), 'name="title"'), 'Event permission restores the full editor.');
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
    echo "calendar_personal_time_buffers_test: OK\n";
} finally {
    foreach (array_reverse($fixtures) as $object) { $object->delete(); }
}
