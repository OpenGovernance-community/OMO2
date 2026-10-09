<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/projects/' . $request['endpoint'];
    $_SERVER['REQUEST_METHOD'] = $request['method'];
    require dirname(__DIR__) . '/omo/api/projects/' . $request['endpoint'];
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/external_calendar.php';

use dbObject\{Event, Project, ExternalCalendar, ExternalCalendarEvent, ProjectExternalEvent, ArrayProjectExternalEvent};

function projectEventRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start endpoint.');
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Endpoint failed: ' . $errors . $out);
    return $out;
}
function projectEventResult(array $request): array
{
    return json_decode(projectEventRequest($request), true, 512, JSON_THROW_ON_ERROR);
}

$items = mcpFixtures();
$oldKey = getenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY');
putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=project-event-test-only');
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId(); $hid = (int)$items['role']->getId();
    foreach (['calendar', 'projects'] as $hash) {
        $app = new \dbObject\Application(); mcpCheck($app->load([['hash', $hash]]), 'Missing app.');
        $items['app_' . $hash] = mcpFixture(\dbObject\OrganizationApplication::class,
            ['IDapplication' => $app->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $items['assignment'] = mcpFixture(\dbObject\UserHolon::class,
        ['IDholon' => $hid, 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    foreach (['CAN_EDIT_EVENT', 'CAN_CREATE_EVENT'] as $key) {
        $items[$key] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid,
            'IDpermission' => \dbObject\Permission::findByKey($key)->getId(), 'member_type' => 'member', 'range' => 'self']);
    }
    $projectFields = ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Import project', 'status' => Project::STATUS_IN_PROGRESS, 'active' => 1];
    $items['project'] = $project = mcpFixture(Project::class, $projectFields);
    $items['other_project'] = $otherProject = mcpFixture(Project::class, $projectFields);
    $items['event'] = $event = mcpFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Existing meeting', 'start_at' => new DateTimeImmutable('-2 days 10:00'),
        'end_at' => new DateTimeImmutable('-2 days 11:00'), 'status' => Event::STATUS_CONFIRMED, 'active' => 1]);
    $request = ['endpoint' => 'event_import.php', 'session' => ['currentUser' => $uid, 'currentOrganization' => $oid,
        'common_csrf' => str_repeat('a', 64)], 'get' => ['oid' => $oid, 'cid' => $hid, 'id' => $project->getId()],
        'post' => [], 'method' => 'GET'];
    $catalog = projectEventResult($request);
    mcpCheck($catalog['success'] && in_array($event->getId(), array_column($catalog['items'], 'eventId')), 'Internal event must be selectable.');
    $post = $request; $post['method'] = 'POST';
    $post['post'] = ['id' => $project->getId(), 'cid' => $hid, '_csrf' => str_repeat('a', 64),
        'source' => 'internal', 'event_id' => $event->getId(), 'action' => 'attach'];
    $bad = $post; $bad['post']['_csrf'] = 'bad';
    mcpCheck(!projectEventResult($bad)['success'], 'CSRF required.');
    mcpCheck(projectEventResult($post)['success'], 'Attach existing event.');
    $event->load((int)$event->getId(), true);
    mcpCheck((int)$event->get('IDproject') === (int)$project->getId() && $event->get('title') === 'Existing meeting', 'Link without changing event content.');
    mcpCheck(projectEventResult($post)['success'], 'Repeated import is idempotent.');
    $bad = $post; $bad['post']['id'] = $otherProject->getId();
    mcpCheck(!projectEventResult($bad)['success'], 'Never steal an event from another project.');
    $items['foreign_project'] = $foreignProject = mcpFixture(Project::class, array_replace($projectFields,
        ['IDorganization' => $items['other_org']->getId(), 'IDholon' => $items['other_root']->getId()]));
    $bad = $post; $bad['post']['id'] = $foreignProject->getId();
    mcpCheck(!projectEventResult($bad)['success'], 'Reject cross-organization project.');
    $htmlRequest = $request; $htmlRequest['endpoint'] = 'events.php';
    $html = projectEventRequest($htmlRequest);
    mcpCheck(str_contains($html, 'Existing meeting') && str_contains($html, 'generic-menu--split'), 'History and split import button rendered.');

    $url = 'https://example.com/project-fixture.ics';
    $items['other_user'] = $otherUser = mcpFixture(\dbObject\User::class,
        ['email' => 'project-import-other-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
    $items['other_membership'] = mcpFixture(\dbObject\UserOrganization::class,
        ['IDuser' => $otherUser->getId(), 'IDorganization' => $oid, 'active' => 1]);
    $items['archived_project'] = $archivedProject = mcpFixture(Project::class,
        array_replace($projectFields, ['active' => 0]));
    $archivedRequest = $request; $archivedRequest['get']['id'] = $archivedProject->getId();
    mcpCheck(!projectEventResult($archivedRequest)['success'], 'An archived project cannot import events.');
    $items['calendar'] = $calendar = mcpFixture(ExternalCalendar::class, ['IDuser' => $uid, 'provider' => 'ics',
        'title' => 'Private source', 'calendar_url' => 'ics:' . hash('sha256', $url), 'username' => 'ics',
        'password_encrypted' => commonExternalCalendarEncryptPassword($url), 'active' => 1]);
    $start = new DateTimeImmutable('-20 days'); $end = new DateTimeImmutable('+20 days');
    $past = new DateTimeImmutable('-2 days 10:00', new DateTimeZone('UTC'));
    $future = new DateTimeImmutable('+2 days 10:00', new DateTimeZone('UTC'));
    $makeFeed = static function (bool $include, string $title = 'Source appointment') use ($past, &$future): string {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0'];
        if ($include) foreach (['past' => $past, 'future' => $future] as $uid => $date) {
            array_push($lines, 'BEGIN:VEVENT', 'UID:' . $uid, 'SUMMARY:' . $title,
                'DTSTART:' . $date->format('Ymd\THis\Z'), 'DTEND:' . $date->modify('+1 hour')->format('Ymd\THis\Z'),
                'DESCRIPTION:Shared description', 'LOCATION:Source room', 'END:VEVENT');
        }
        array_push($lines, 'END:VCALENDAR', ''); return implode("\r\n", $lines);
    };
    $feed = $makeFeed(true); $failed = false;
    $fetch = static function () use (&$feed, &$failed): array { return ['status' => !$failed, 'body' => $feed, 'message' => $failed ? 'Offline fixture' : '']; };
    mcpCheck(commonExternalCalendarSynchronize($calendar, $start, $end, true, null, $fetch)['status'], 'Source sync.');
    $links = [];
    foreach (['past', 'future'] as $sourceKey) {
        $date = $sourceKey === 'past' ? $past : $future;
        $external = ExternalCalendarEvent::findForCalendarSourceKey((int)$calendar->getId(), $sourceKey . '|' . $date->format('Ymd\THis\Z'));
        mcpCheck($external instanceof ExternalCalendarEvent, 'Imported source occurrence: ' . $sourceKey);
        $post['post']['source'] = 'external'; $post['post']['event_id'] = $external->getId();
        mcpCheck(projectEventResult($post)['success'], 'Import external event.');
        mcpCheck(projectEventResult($post)['success'], 'External import is idempotent.');
        $link = new ProjectExternalEvent();
        mcpCheck($link->load([['IDproject', $project->getId()], ['IDexternalcalendarevent', $external->getId()]]), 'Project snapshot exists.');
        $links[$sourceKey] = $link;
        mcpCheck(ProjectExternalEvent::attach($project, $external, (int)$otherUser->getId()) === null,
            'A foreign personal calendar must never be imported.');
    }
    $linkCollection = new ArrayProjectExternalEvent(); $linkCollection->loadForProject((int)$project->getId());
    mcpCheck(count($linkCollection) === 2, 'No duplicate links: ' . count($linkCollection));
    $future = $future->modify('+1 day');
    $feed = $makeFeed(true, 'Updated source appointment');
    mcpCheck(commonExternalCalendarSynchronize($calendar, $start, $end, true, null, $fetch)['status'], 'Update source sync.');
    foreach ($links as $link) {
        $link->load((int)$link->getId(), true);
        mcpCheck($link->get('title') === 'Updated source appointment' && !$link->isSourceMissing(), 'Project follows source changes.');
    }
    mcpCheck($links['future']->get('start_at')->format('Y-m-d H:i') === $future->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i'),
        'Moving the appointment preserves its project link and updates its dates.');
    $failed = true; $feed = $makeFeed(false);
    mcpCheck(!commonExternalCalendarSynchronize($calendar, $start, $end, true, null, $fetch)['status'], 'Simulated sync failure.');
    $links['future']->load((int)$links['future']->getId(), true);
    mcpCheck(!$links['future']->isSourceMissing(), 'Failure must retain upcoming events.');
    $failed = false;
    mcpCheck(commonExternalCalendarSynchronize($calendar, $start, $end, true, null, $fetch)['status'], 'Empty valid source sync.');
    foreach ($links as $link) { $link->load((int)$link->getId(), true); }
    mcpCheck(!$links['past']->isSourceMissing() && $links['future']->isSourceMissing(), 'Retain past, hide disappeared future.');
    $html = projectEventRequest($htmlRequest);
    mcpCheck(substr_count($html, 'Updated source appointment') === 1, 'Only historical external event remains rendered.');
    $feed = $makeFeed(true);
    mcpCheck(commonExternalCalendarSynchronize($calendar, $start, $end, true, null, $fetch)['status'], 'Reappearing source sync.');
    $links['future']->load((int)$links['future']->getId(), true);
    mcpCheck(!$links['future']->isSourceMissing(), 'Restored source reappears without importing twice.');
    $calendar->delete();
    $links['past']->load((int)$links['past']->getId(), true);
    mcpCheck(!$links['past']->isSourceMissing() && $links['past']->get('description') === 'Shared description', 'Historical documentation survives deleted calendar.');
    $detail = $request; $detail['get'] += ['action' => 'detail', 'link_id' => $links['past']->getId()];
    mcpCheck(str_contains(projectEventRequest($detail), 'Shared description'), 'Retained history can still be opened.');
    $detail['session']['currentUser'] = $otherUser->getId();
    mcpCheck(str_contains(projectEventRequest($detail), 'Shared description'), 'Project readers can open only the intentionally shared snapshot.');
    $post['post']['action'] = 'detach'; $post['post']['event_id'] = $links['past']->getId();
    mcpCheck(projectEventResult($post)['success'], 'Detach retained history.');
    $post['post']['source'] = 'internal'; $post['post']['event_id'] = $event->getId();
    mcpCheck(projectEventResult($post)['success'], 'Detach internal event.');
    $event->load((int)$event->getId(), true);
    mcpCheck((int)$event->get('IDproject') === 0 && $event->get('active'), 'Detaching preserves original event.');
    echo "project_event_import_test: OK\n";
} finally {
    mcpCleanup($items);
    if ($oldKey === false) { putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY'); } else { putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=' . $oldKey); }
}
