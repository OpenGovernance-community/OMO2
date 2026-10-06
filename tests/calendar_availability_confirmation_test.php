<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\DbObject;
use dbObject\Event;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;

// Each request runs the real endpoint in a fresh process, with the same session context.
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session'];
    $_GET = $request['get'];
    $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/calendar/create.php';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    require dirname(__DIR__) . '/omo/api/calendar/create.php';
    exit;
}

function confirmationExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function confirmationRequest(array $request): array
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    confirmationExpect(is_resource($process), 'Could not start endpoint request.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exit = proc_close($process);
    $result = json_decode($output, true);
    confirmationExpect($exit === 0 && is_array($result), 'Invalid endpoint response: ' . $output . ' ' . $errors);
    return $result;
}

$fixtures = [];
$fixture = static function (string $class, array $fields) use (&$fixtures): DbObject {
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    confirmationExpect(!empty($object->save()['status']), 'Fixture save failed: ' . $class);
    $fixtures[] = $object;
    return $object;
};
try {
    $nonce = bin2hex(random_bytes(8));
    $user = $fixture(\dbObject\User::class, ['email' => 'confirmation-' . $nonce . '@example.invalid', 'firstname' => 'Organizer']);
    $guest = $fixture(\dbObject\User::class, ['email' => 'confirmation-guest-' . $nonce . '@example.invalid', 'firstname' => 'Guest']);
    $outsider = $fixture(\dbObject\User::class, ['email' => 'confirmation-outsider-' . $nonce . '@example.invalid']);
    $org = $fixture(\dbObject\Organization::class, ['name' => 'Confirmation fixture', 'shortname' => 'confirmation-' . $nonce]);
    foreach ([$user, $guest] as $member) {
        $fixture(\dbObject\UserOrganization::class, ['IDuser' => $member->getId(), 'IDorganization' => $org->getId(), 'active' => 1]);
    }
    $holon = $fixture(\dbObject\Holon::class, ['name' => 'Confirmation scope', 'IDorganization' => $org->getId(), 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $holon->set('IDholon_org', $holon->getId()); $holon->save();
    $fixture(\dbObject\UserHolon::class, ['IDholon' => $holon->getId(), 'IDuser' => $user->getId(), 'active' => 1, 'is_membership' => 1]);
    foreach (['calendar', 'structure'] as $hash) {
        $app = new \dbObject\Application();
        if ($app->load([['hash', $hash]])) {
            $fixture(\dbObject\OrganizationApplication::class, ['IDapplication' => $app->getId(), 'IDorganization' => $org->getId(), 'active' => 1]);
        }
    }
    $fixture(\dbObject\HolonPermission::class, ['IDholon' => $holon->getId(),
        'IDpermission' => \dbObject\Permission::findByKey('CAN_EDIT_EVENT')->getId(), 'member_type' => 'member', 'range' => 'self']);
    $day = new DateTimeImmutable('tomorrow');
    $event = $fixture(Event::class, ['IDorganization' => $org->getId(), 'IDholon' => $holon->getId(), 'IDuser' => $user->getId(),
        'title' => 'Original', 'status' => Event::STATUS_DRAFT, 'active' => 1, 'start_at' => $day->setTime(9, 0), 'end_at' => $day->setTime(10, 0)]);
    // Invalid credentials guarantee a provider failure without any real network call.
    $calendar = $fixture(ExternalCalendar::class, ['IDuser' => $guest->getId(), 'provider' => 'caldav', 'title' => 'PRIVATE calendar',
        'calendar_url' => 'https://calendar.example.invalid/dav', 'username' => 'PRIVATE user', 'password_encrypted' => 'unreadable',
        'active' => 1, 'last_sync_at' => new DateTimeImmutable('-3 hours')]);
    $busy = $fixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $calendar->getId(), 'source_key' => $nonce,
        'title' => 'PRIVATE appointment', 'active' => 1, 'is_busy' => 1, 'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0)]);
    $request = ['session' => ['currentUser' => (int)$user->getId(), 'currentOrganization' => (int)$org->getId(),
        'calendar_availability_secret' => bin2hex(random_bytes(32))],
        'get' => ['oid' => $org->getId(), 'cid' => $holon->getId(), 'id' => $event->getId()],
        'post' => ['title' => 'Changed', 'IDholon' => $holon->getId(), 'status' => Event::STATUS_DRAFT,
            'start_at' => $day->format('Y-m-d') . 'T10:30', 'end_at' => $day->format('Y-m-d') . 'T11:30',
            'invitation_user_ids' => [(int)$guest->getId()]]];
    $unchanged = $request;
    $unchanged['post']['start_at'] = $day->format('Y-m-d') . 'T09:00';
    $unchanged['post']['end_at'] = $day->format('Y-m-d') . 'T10:00';
    $busy->set('start_at', $day->setTime(9, 30)); $busy->save();
    $unchangedResult = confirmationRequest($unchanged);
    confirmationExpect($unchangedResult['status'] && empty($unchangedResult['warning']), 'Unchanged dates save without reviewing existing conflicts.');
    $calendar->load((int)$calendar->getId(), true);
    confirmationExpect($calendar->get('last_sync_at') < new DateTimeImmutable('-2 hours'), 'Metadata-only edits do not synchronize calendars.');
    $busy->set('start_at', $day->setTime(10, 0)); $busy->save();
    $allDay = $unchanged; $allDay['post']['is_all_day'] = '1';
    confirmationExpect(!confirmationRequest($allDay)['status'], 'All-day changes still check the occupied interval.');
    $buffersOnly = $unchanged;
    $buffersOnly['post']['time_buffers_enabled'] = '1'; $buffersOnly['post']['closing_minutes'] = '45';
    confirmationExpect(!confirmationRequest($buffersOnly)['status'], 'Changing only the closing time still checks conflicts.');

    $cached = $request;
    $cached['post']['start_at'] = $day->format('Y-m-d') . 'T14:00';
    $cached['post']['end_at'] = $day->format('Y-m-d') . 'T15:00';
    $cachedResult = confirmationRequest($cached);
    confirmationExpect($cachedResult['status'] && !empty($cachedResult['warning']), 'An unavailable provider must not prevent saving a conflict-free cached schedule.');
    confirmationExpect(!isset($cachedResult['availability']) && !str_contains(json_encode($cachedResult), 'PRIVATE'), 'Cached fallback is non-blocking and does not disclose private data.');
    $event->load((int)$event->getId(), true);
    confirmationExpect($event->get('start_at')->format('H:i') === '14:00', 'Cached fallback actually persists the proposed dates.');
    foreach (['title' => 'Original', 'start_at' => $day->setTime(9, 0), 'end_at' => $day->setTime(10, 0)] as $field => $value) { $event->set($field, $value); }
    $event->save();
    $fixture(\dbObject\HolonPermission::class, ['IDholon' => $holon->getId(),
        'IDpermission' => \dbObject\Permission::findByKey('CAN_CREATE_EVENT')->getId(), 'member_type' => 'member', 'range' => 'self']);
    $creation = $cached; unset($creation['get']['id']);
    $creationResult = confirmationRequest($creation);
    confirmationExpect($creationResult['status'] && !empty($creationResult['warning']), 'A new event also saves using an incomplete but conflict-free cache.');
    $createdEvent = new Event();
    confirmationExpect($createdEvent->load((int)$creationResult['eventId']), 'Reload the event created with cached availability.');
    $fixtures[] = $createdEvent;
    $createdEvent->set('active', 0); $createdEvent->save();
    $calendar->set('last_sync_at', new DateTimeImmutable('-3 hours')); $calendar->set('last_sync_error', null); $calendar->save();
    $first = confirmationRequest($request);
    confirmationExpect(!$first['status'] && !empty($first['availability']['acknowledgement']), 'Expected a warning before saving: ' . json_encode($first));
    $calendar->load((int)$calendar->getId(), true);
    confirmationExpect($calendar->get('last_sync_at') > new DateTimeImmutable('-1 minute') && $calendar->get('last_sync_error'), 'Another member calendar is refreshed before the warning.');
    confirmationExpect(count(array_filter($first['availability']['items'], static fn($item) => $item['kind'] === 'conflict')) === 1, 'Failed refresh must keep the cached conflict.');
    confirmationExpect(!str_contains(json_encode($first), 'PRIVATE'), 'A member must not receive private calendar or event details.');
    $event->load((int)$event->getId(), true);
    confirmationExpect($event->get('title') === 'Original', 'Warning must not save yet.');
    $request['post']['availability_ack'] = $first['availability']['acknowledgement'];
    $request['session']['calendar_availability_warnings'][$first['availability']['acknowledgement']] = true;

    $changed = $request;
    $changed['post']['end_at'] = $day->format('Y-m-d') . 'T12:30';
    $changedResult = confirmationRequest($changed);
    confirmationExpect(!$changedResult['status'] && $changedResult['availability']['acknowledgement'] !== $request['post']['availability_ack'], 'Changing the proposed schedule needs a new review.');
    $changedBuffers = $request;
    $changedBuffers['post']['time_buffers_enabled'] = '1';
    $changedBuffers['post']['preparation_minutes'] = '15';
    $changedBuffers['post']['closing_minutes'] = '20';
    $bufferResult = confirmationRequest($changedBuffers);
    confirmationExpect(!$bufferResult['status'] && $bufferResult['availability']['acknowledgement'] !== $request['post']['availability_ack'],
        'Changing attached time invalidates the reviewed conflicts.');
    $outside = $request;
    $outside['post']['invitation_user_ids'] = [(int)$outsider->getId()];
    $oldSync = new DateTimeImmutable('-3 hours');
    $calendar->set('last_sync_at', $oldSync); $calendar->set('last_sync_error', null); $calendar->save();
    confirmationExpect(!confirmationRequest($outside)['status'], 'Confirmation never bypasses membership validation.');
    $calendar->load((int)$calendar->getId(), true);
    confirmationExpect($calendar->get('last_sync_at')->format('c') === $oldSync->format('c'), 'An invalid participant selection must not trigger synchronization.');

    // The data changes after review. The valid confirmation must neither reread
    // conflicts nor refresh this now-stale calendar.
    $busy->set('end_at', $day->setTime(12, 0)); $busy->save();
    $guest->set('firstname', 'Updated guest'); $guest->save();
    $confirmed = confirmationRequest($request);
    confirmationExpect($confirmed['status'] === true, 'Confirmation must save despite changed conflicts: ' . json_encode($confirmed));
    confirmationExpect(!empty($confirmed['warning']), 'The stale-cache caution survives confirmation of a real conflict.');
    $event->load((int)$event->getId(), true);
    $calendar->load((int)$calendar->getId(), true);
    confirmationExpect($event->get('title') === 'Changed' && $event->get('start_at')->format('H:i') === '10:30', 'Event edit must be persisted.');
    confirmationExpect($calendar->get('last_sync_at')->format('c') === $oldSync->format('c') && !$calendar->get('last_sync_error'), 'Confirmation must not synchronize again.');
    echo "calendar_availability_confirmation_test: OK\n";
} finally {
    foreach (array_reverse($fixtures) as $object) { $object->delete(); }
}
