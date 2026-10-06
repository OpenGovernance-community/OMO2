<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\DbObject;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/calendar/external_event.php';
    $_SERVER['REQUEST_METHOD'] = $request['method'];
    require dirname(__DIR__) . '/omo/api/calendar/external_event.php';
    exit;
}

function externalBufferExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function externalBufferRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    externalBufferExpect(is_resource($process), 'Request process failed.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    externalBufferExpect(proc_close($process) === 0 && $errors === '', 'Endpoint failed: ' . $errors . $output);
    return $output;
}
$fixtures = [];
$fixture = static function (string $class, array $fields) use (&$fixtures): DbObject {
    $object = new $class();
    foreach ($fields as $key => $value) { $object->set($key, $value); }
    externalBufferExpect(!empty($object->save()['status']), 'Fixture failed: ' . $class);
    $fixtures[] = $object;
    return $object;
};
try {
    $nonce = bin2hex(random_bytes(8));
    $owner = $fixture(\dbObject\User::class, ['email' => 'external-buffer-' . $nonce . '@example.invalid']);
    $other = $fixture(\dbObject\User::class, ['email' => 'external-buffer-other-' . $nonce . '@example.invalid']);
    $org = $fixture(\dbObject\Organization::class, ['name' => 'External buffers', 'shortname' => $nonce]);
    foreach ([$owner, $other] as $member) {
        $fixture(\dbObject\UserOrganization::class, ['IDuser' => $member->getId(), 'IDorganization' => $org->getId(), 'active' => 1]);
    }
    foreach (['ics', 'caldav'] as $provider) {
        $calendar = $fixture(ExternalCalendar::class, ['IDuser' => $owner->getId(), 'provider' => $provider,
            'title' => 'Imported fixture', 'calendar_url' => 'https://example.invalid/' . $nonce . '/' . $provider,
            'username' => 'fixture', 'password_encrypted' => 'fixture', 'active' => 1]);
        $event = $fixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $calendar->getId(), 'source_key' => $nonce,
            'title' => 'Source title', 'description' => 'Source description', 'location' => 'Source location',
            'start_at' => new DateTimeImmutable('2030-01-07 10:00'), 'end_at' => new DateTimeImmutable('2030-01-07 11:00'),
            'active' => 1, 'is_busy' => 1, 'preparation_minutes' => 20, 'closing_minutes' => 15]);
        $request = ['session' => ['currentUser' => $owner->getId(), 'currentOrganization' => $org->getId(),
            'omo_external_event_csrf' => 'test-token'], 'get' => ['oid' => $org->getId(), 'id' => $event->getId()],
            'method' => 'GET', 'post' => []];
        $html = externalBufferRequest($request);
        externalBufferExpect(str_contains($html, 'data-omo-calendar-external-event-form')
            && str_contains($html, 'value="20" selected') && str_contains($html, ' checked'), 'Editable form must preserve custom durations.');
        externalBufferExpect(!str_contains($html, 'name="title"') && !str_contains($html, 'name="start_at"'), 'Imported content is read-only.');
        $request['method'] = 'POST';
        $request['post'] = ['csrf' => 'test-token', 'time_buffers_enabled' => '1', 'preparation_minutes' => '30', 'closing_minutes' => '45',
            'title' => 'Tampered', 'start_at' => '2030-01-01 00:00'];
        $foreign = $request; $foreign['session']['currentUser'] = $other->getId();
        externalBufferExpect(json_decode(externalBufferRequest($foreign), true)['status'] === false, 'Other users cannot annotate this event.');
        $csrf = $request; $csrf['post']['csrf'] = 'invalid';
        externalBufferExpect(json_decode(externalBufferRequest($csrf), true)['status'] === false, 'CSRF required.');
        foreach (['-1', '1441', '1.5', ['30']] as $invalid) {
            $bad = $request; $bad['post']['preparation_minutes'] = $invalid;
            externalBufferExpect(json_decode(externalBufferRequest($bad), true)['status'] === false, 'Reject invalid durations.');
        }
        externalBufferExpect(json_decode(externalBufferRequest($request), true)['status'] === true, 'Owner save failed.');
        $event->load((int)$event->getId(), true);
        externalBufferExpect((int)$event->get('preparation_minutes') === 30 && (int)$event->get('closing_minutes') === 45
            && $event->get('time_buffers_local'), 'Save local override.');
        externalBufferExpect($event->get('title') === 'Source title' && $event->get('start_at')->format('H:i') === '10:00', 'Tampering cannot modify source content.');
        [$start, $end] = $event->getBusyInterval();
        externalBufferExpect($start->format('H:i') === '09:30' && $end->format('H:i') === '11:45', 'Availability includes local durations.');
        $clear = $request; unset($clear['post']['time_buffers_enabled']);
        externalBufferExpect(json_decode(externalBufferRequest($clear), true)['status'] === true, 'Clear local durations.');
        $event->load((int)$event->getId(), true);
        externalBufferExpect((int)$event->get('preparation_minutes') === 0 && (int)$event->get('closing_minutes') === 0
            && $event->get('time_buffers_local'), 'Explicit zero must remain an override.');
        $calendar->set('availability_only', 1); $calendar->save();
        externalBufferExpect(json_decode(externalBufferRequest($request), true)['status'] === false, 'Opening windows cannot be edited as appointments.');
        $calendar->set('availability_only', 0); $calendar->save();
        $event->set('active', 0); $event->save();
        externalBufferExpect(json_decode(externalBufferRequest($request), true)['status'] === false, 'Retired events cannot be annotated.');
    }
    echo "external_calendar_event_time_buffers_test: OK\n";
} finally {
    foreach (array_reverse($fixtures) as $object) { $object->delete(); }
}
