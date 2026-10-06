<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/calendar/invitations_shared.php';
require_once dirname(__DIR__) . '/common/external_calendar.php';

use dbObject\ArrayEvent;
use dbObject\DbObject;
use dbObject\Event;
use dbObject\EventInvitation;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\Organization;
use dbObject\User;
use dbObject\UserOrganization;

function availabilityExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function availabilityFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $field => $value) { $object->set($field, $value); }
    $result = $object->save();
    availabilityExpect(is_array($result) && !empty($result['status']), 'Fixture save failed: ' . $class . ' ' . json_encode($result));
    return $object;
}

// Dedicated fixtures, rolled back together; no remote calendar calls or notifications.
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
});
try {
    $nonce = bin2hex(random_bytes(6));
    $organizer = availabilityFixture(User::class, ['email' => 'organizer-' . $nonce . '@example.invalid', 'firstname' => 'Organizer']);
    $guest = availabilityFixture(User::class, ['email' => 'guest-' . $nonce . '@example.invalid', 'firstname' => 'Guest']);
    $outsider = availabilityFixture(User::class, ['email' => 'outsider-' . $nonce . '@example.invalid', 'firstname' => 'Outsider']);
    $org = availabilityFixture(Organization::class, ['name' => 'Current ' . $nonce, 'shortname' => 'current-' . $nonce]);
    $other = availabilityFixture(Organization::class, ['name' => 'Other ' . $nonce, 'shortname' => 'other-' . $nonce]);
    $structureApps = new \dbObject\ArrayApplication();
    $structureApps->load(['where' => [['field' => 'hash', 'value' => 'structure']], 'limit' => 1]);
    availabilityExpect(count($structureApps) === 1, 'Structure app exists for holon invitation fixtures');
    foreach ([$org, $other] as $fixtureOrg) {
        availabilityFixture(\dbObject\OrganizationApplication::class, ['IDorganization' => $fixtureOrg->getId(),
            'IDapplication' => $structureApps[0]->getId(), 'active' => 1]);
    }
    $root = availabilityFixture(\dbObject\Holon::class, ['name' => 'Fixture root', 'IDorganization' => $org->getId(),
        'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    foreach ([$organizer, $guest] as $member) {
        availabilityFixture(UserOrganization::class, ['IDorganization' => $org->getId(), 'IDuser' => $member->getId(), 'active' => 1]);
    }
    $day = new DateTimeImmutable('tomorrow 00:00');
    $otherHolon = availabilityFixture(\dbObject\Holon::class, ['id' => 0, 'name' => 'Cercle Ancrage',
        'IDorganization' => $other->getId(), 'IDtypeholon' => 2, 'IDuser' => $guest->getId(), 'active' => 1, 'visible' => 1]);
    $busy = availabilityFixture(Event::class, ['IDuser' => $guest->getId(), 'IDorganization' => $other->getId(),
        'IDholon' => $otherHolon->getId(),
        'title' => 'SECRET title', 'description' => 'SECRET details', 'start_at' => $day->setTime(10, 0),
        'end_at' => $day->setTime(11, 0), 'active' => 1, 'status' => Event::STATUS_CONFIRMED]);
    $blocks = ArrayEvent::otherOrganizationBusyBlocks((int)$guest->getId(), (int)$org->getId(), $day, $day->modify('+1 day'));
    availabilityExpect(count($blocks) === 1 && $blocks[0]['title'] === $other->get('name'), 'Other organization shown by name');
    availabilityExpect(!str_contains(json_encode($blocks), 'SECRET') && !isset($blocks[0]['id']), 'No event details or source ID disclosed');
    availabilityExpect(ArrayEvent::otherOrganizationBusyBlocks((int)$guest->getId(), (int)$other->getId(), $day, $day->modify('+1 day')) === [], 'Current organization excluded');

    $selection = omoCalendarPrepareInvitationSelections($org, (int)$org->getId(), [], [(int)$guest->getId()], []);
    availabilityExpect($selection['status'], 'Active member invitation valid');
    availabilityExpect(!omoCalendarPrepareInvitationSelections($org, (int)$org->getId(), [], [(int)$outsider->getId()], [])['status'], 'Cannot probe unrelated users');
    $invite = new EventInvitation();
    foreach (current($selection['invitations']) as $field => $value) { $invite->set($field, $value); }
    $proposed = new Event();
    foreach (['IDorganization' => $org->getId(), 'IDuser' => $organizer->getId(), 'start_at' => $day->setTime(10, 30), 'end_at' => $day->setTime(11, 30)] as $field => $value) { $proposed->set($field, $value); }
    $report = $proposed->checkInvitationAvailability([$invite]);
    availabilityExpect(count($report['conflicts']) === 1, 'Invitee conflict across organizations');
    availabilityExpect($report['conflicts'][0]['start'] === $day->format('Y-m-d') . ' 10:00' && $report['conflicts'][0]['end'] === $day->format('Y-m-d') . ' 11:00', 'Full appointment interval shown, not only the overlap');
    availabilityExpect($report['conflicts'][0]['source'] === 'omo' && $report['conflicts'][0]['organization'] === $other->get('name')
        && $report['conflicts'][0]['holon'] === 'Cercle Ancrage', 'Organization and circle or role context shown');
    availabilityExpect(!str_contains(json_encode($report), 'SECRET'), 'Guest report contains no private titles or descriptions');
    $longBusy = availabilityFixture(Event::class, ['IDuser' => $guest->getId(), 'IDorganization' => $other->getId(),
        'title' => 'SECRET long meeting', 'start_at' => $day->setTime(9, 0), 'end_at' => $day->setTime(12, 0),
        'active' => 1, 'status' => Event::STATUS_CONFIRMED]);
    $separateReport = $proposed->checkInvitationAvailability([$invite]);
    availabilityExpect(count($separateReport['conflicts']) === 2, 'Overlapping appointments remain separate');
    availabilityExpect($separateReport['conflicts'][0]['start'] === $day->format('Y-m-d') . ' 09:00'
        && $separateReport['conflicts'][0]['end'] === $day->format('Y-m-d') . ' 12:00', 'Long appointment retains both bounds beyond the proposed event');
    availabilityExpect($separateReport['conflicts'][0]['holon'] === '', 'Appointment without circle keeps organization context only');
    $longBusy->set('active', 0); $longBusy->save();
    $holon = availabilityFixture(\dbObject\Holon::class, ['id' => 0, 'name' => 'Fixture team', 'IDorganization' => $org->getId(),
        'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(), 'IDtypeholon' => 1,
        'IDuser' => $organizer->getId(), 'active' => 1, 'visible' => 1]);
    availabilityFixture(\dbObject\UserHolon::class, ['IDholon' => $holon->getId(), 'IDuser' => $guest->getId(), 'active' => 1, 'is_membership' => 1]);
    $holonInvite = new EventInvitation();
    $holonInvite->set('invitation_type', 'holon'); $holonInvite->set('IDholon', $holon->getId());
    availabilityExpect(count($proposed->checkInvitationAvailability([$holonInvite])['conflicts']) === 1, 'Holon invite expands to its members');
    $proposed->set('IDholon', $holon->getId());
    availabilityExpect(count($proposed->checkInvitationAvailability([])['conflicts']) === 1, 'Default holon invitations use the same recipients');
    $proposed->set('IDholon', null);
    $proposed->set('start_at', $day->setTime(11, 0));
    availabilityExpect($proposed->checkInvitationAvailability([$invite])['conflicts'] === [], 'Touching end/start is not a conflict');
    $proposed->set('start_at', $day->setTime(10, 30));
    $proposed->setId($busy->getId());
    availabilityExpect($proposed->checkInvitationAvailability([$invite])['conflicts'] === [], 'Editing ignores the event itself');
    $proposed->setId(0);
    $busy->set('status', Event::STATUS_CANCELLED); $busy->save();
    availabilityExpect($proposed->checkInvitationAvailability([$invite])['conflicts'] === [], 'Cancelled events do not block');
    $busy->set('status', Event::STATUS_CONFIRMED); $busy->set('is_all_day', 1); $busy->save();
    $proposed->set('start_at', $day->setTime(23, 0)); $proposed->set('end_at', $day->modify('+1 day'));
    availabilityExpect(count($proposed->checkInvitationAvailability([$invite])['conflicts']) === 1, 'All-day event blocks through midnight');
    $proposed->set('start_at', $day->modify('+1 day')); $proposed->set('end_at', $day->modify('+1 day +1 hour'));
    availabilityExpect($proposed->checkInvitationAvailability([$invite])['conflicts'] === [], 'All-day exclusive end does not block next day');
    $busy->set('active', 0); $busy->save();

    $calendar = availabilityFixture(ExternalCalendar::class, ['IDuser' => $guest->getId(), 'provider' => 'caldav', 'title' => 'SECRET calendar',
        'calendar_url' => 'https://example.invalid/calendar', 'username' => 'fixture', 'password_encrypted' => 'unused',
        'active' => 1, 'last_sync_at' => new DateTimeImmutable()]);
    $external = availabilityFixture(ExternalCalendarEvent::class, ['IDexternalcalendar' => $calendar->getId(), 'source_key' => 'fixture-' . $nonce,
        'title' => 'SECRET external', 'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0), 'is_busy' => 1, 'active' => 1]);
    $proposed->set('start_at', $day->setTime(10, 30)); $proposed->set('end_at', $day->setTime(11, 30));
    $report = $proposed->checkInvitationAvailability([$invite]);
    availabilityExpect(count($report['conflicts']) === 1 && $report['externalCache'] && !$report['unverified'], 'Cached external busy event included');
    availabilityExpect($report['conflicts'][0]['source'] === 'external' && $report['conflicts'][0]['organization'] === ''
        && $report['conflicts'][0]['holon'] === '' && !str_contains(json_encode($report), 'SECRET'), 'External source remains generic');
    availabilityExpect($report['conflicts'][0]['start'] === $day->format('Y-m-d') . ' 10:00'
        && $report['conflicts'][0]['end'] === $day->format('Y-m-d') . ' 11:00', 'Full external appointment interval shown');
    $displayRefreshes = 0;
    $calendar->set('last_sync_at', new DateTimeImmutable('-3 hours')); $calendar->save();
    commonExternalCalendarRefreshForDisplay((int)$guest->getId(), microtime(true) + 2,
        static function () use (&$displayRefreshes): void { $displayRefreshes++; });
    availabilityExpect($displayRefreshes === 0, 'A displayed calendar may reuse a three-hour-old cache');
    $calendar->set('last_sync_at', new DateTimeImmutable('-6 hours')); $calendar->save();
    commonExternalCalendarRefreshForDisplay((int)$guest->getId(), microtime(true) + 2,
        static function (ExternalCalendar $calendar) use (&$displayRefreshes): void {
            $displayRefreshes++;
            $calendar->markSyncResult(true);
        });
    availabilityExpect($displayRefreshes === 1, 'A displayed calendar refreshes a six-hour-old cache');
    $external->set('is_busy', 0); $external->save();
    availabilityExpect($proposed->checkInvitationAvailability([$invite])['conflicts'] === [], 'Transparent external event ignored');
    $calendar->set('last_sync_at', new DateTimeImmutable('-3 hours')); $calendar->save();
    availabilityExpect($proposed->checkInvitationAvailability([$invite])['unverified'][0]['reason'] === 'cache', 'Stale cache does not imply free');
    $refreshCalls = 0;
    $refresh = static function (int $userId) use (&$refreshCalls, $external): void {
        commonExternalCalendarRefreshForAvailability($userId, microtime(true) + 2,
            static function (ExternalCalendar $calendar, $start, $end, $force, $deadline) use (&$refreshCalls, $external): array {
                $refreshCalls++;
                availabilityExpect(!$force && $deadline > microtime(true), 'Refresh keeps change detection and a time budget');
                $external->set('is_busy', 1); $external->save();
                $calendar->markSyncResult(true);
                return ['status' => true];
            });
    };
    $report = $proposed->checkInvitationAvailability([$invite], $refresh);
    availabilityExpect($refreshCalls === 1 && !$report['unverified'] && count($report['conflicts']) === 1, 'Refresh precedes conflict calculation and removes the stale warning');
    $proposed->checkInvitationAvailability([$invite], $refresh);
    availabilityExpect($refreshCalls === 1, 'Fresh calendars are not fetched again');
    $calendar->load((int)$calendar->getId(), true);
    $calendar->set('last_sync_at', new DateTimeImmutable('-3 hours')); $calendar->save();
    commonExternalCalendarRefreshForAvailability((int)$guest->getId(), microtime(true) - 1,
        static function (): void { throw new RuntimeException('Expired budget must not start a request'); });
    $failedRefresh = static function (int $userId) use (&$refreshCalls): void {
        commonExternalCalendarRefreshForAvailability($userId, microtime(true) + 2,
            static function (ExternalCalendar $calendar) use (&$refreshCalls): array {
                $refreshCalls++;
                $calendar->markSyncResult(false, 'Fixture server offline');
                return ['status' => false];
            });
    };
    $report = $proposed->checkInvitationAvailability([$invite], $failedRefresh);
    availabilityExpect($refreshCalls === 2 && $report['unverified'][0]['reason'] === 'cache', 'Failed refresh remains an advisory warning');
    $proposed->checkInvitationAvailability([$invite], $failedRefresh);
    availabilityExpect($refreshCalls === 2, 'Repeated failures have a short retry cooldown');
    availabilityExpect(\dbObject\MeetingProfile::lock((int)$guest->getId()), 'Refresh always releases the booking lock');
    \dbObject\MeetingProfile::unlock((int)$guest->getId());
    $calendar->load((int)$calendar->getId(), true);
    $calendar->set('last_sync_at', new DateTimeImmutable('-3 hours')); $calendar->save();
    $timeoutReport = $proposed->checkInvitationAvailability([$invite], static function (int $userId): void {
        commonExternalCalendarRefreshForAvailability($userId, microtime(true) + 2,
            static function (): array { throw new RuntimeException('Connection timed out after 10001 milliseconds'); });
    });
    availabilityExpect(count($timeoutReport['conflicts']) === 1 && $timeoutReport['unverified'][0]['reason'] === 'cache', 'Timeouts still show cached conflicts and a nonblocking stale warning.');
    $unexpectedReport = $proposed->checkInvitationAvailability([$invite], static function (): void {
        throw new RuntimeException('Unexpected refresh failure');
    });
    availabilityExpect(count($unexpectedReport['conflicts']) === 1, 'A throwing refresh callback must never bypass existing external data.');
    $retriesAfterTimeout = 0;
    commonExternalCalendarRefreshForAvailability((int)$guest->getId(), microtime(true) + 2,
        static function () use (&$retriesAfterTimeout): void { $retriesAfterTimeout++; });
    availabilityExpect($retriesAfterTimeout === 0, 'A timeout must receive the failure retry cooldown.');
    $calendar->load((int)$calendar->getId(), true);
    $calendar->set('last_sync_at', new DateTimeImmutable('-3 hours')); $calendar->save();
    $freeAfterRefresh = $proposed->checkInvitationAvailability([$invite], static function (int $userId) use ($external): void {
        commonExternalCalendarRefreshForAvailability($userId, microtime(true) + 2,
            static function (ExternalCalendar $calendar) use ($external): array {
                $external->set('is_busy', 0); $external->save();
                $calendar->markSyncResult(true);
                return ['status' => true];
            });
    });
    availabilityExpect(!$freeAfterRefresh['conflicts'] && !$freeAfterRefresh['unverified'], 'Successful refresh with no conflict allows normal save');
    $emailInvite = new EventInvitation(); $emailInvite->set('invitation_type', 'email'); $emailInvite->set('email', 'unknown@example.invalid');
    availabilityExpect($proposed->checkInvitationAvailability([$emailInvite])['unverified'][0]['reason'] === 'email', 'External email availability unknown');
    if (in_array($argv[1] ?? '', ['--render', '--form', '--post-warning', '--preview'], true)) {
        $busy->set('active', 1); $busy->set('is_all_day', 0); $busy->save();
        $_SESSION['currentUser'] = (int)$guest->getId();
        $_SESSION['currentOrganization'] = (int)$org->getId();
        $_SERVER['HTTP_HOST'] = 'localtest.me';
        $_SERVER['REQUEST_METHOD'] = in_array($argv[1], ['--render', '--form'], true) ? 'GET' : 'POST';
        $_SERVER['REQUEST_URI'] = '/omo/api/calendar/' . ($argv[1] === '--render' ? 'index.php' : 'create.php');
        $_GET = ['oid' => $org->getId(), 'view' => 'week', 'date' => $day->format('Y-m-d')];
        $_REQUEST = $_GET;
        if ($argv[1] === '--form') {
            ob_start();
            require dirname(__DIR__) . '/omo/api/calendar/create.php';
            $formHtml = (string)ob_get_clean();
            availabilityExpect(str_contains($formHtml, 'data-omo-calendar-preview-tab')
                && str_contains($formHtml, 'data-omo-calendar-preview-host')
                && !str_contains($formHtml, 'calendar-freebusy-calendar'), 'Editor renders a lazy availability tab without precomputing the calendar.');
            availabilityExpect(str_contains($formHtml, 'data-omo-calendar-buffers-toggle')
                && str_contains($formHtml, 'name="preparation_minutes"') && str_contains($formHtml, 'name="closing_minutes"'),
                'Date editor provides attached time controls.');
            echo "calendar_availability_test --form: OK\n";
        } elseif ($argv[1] === '--render') {
            $bufferedEvent = availabilityFixture(Event::class, ['IDorganization' => $org->getId(), 'IDuser' => $guest->getId(),
                'title' => 'Buffered fixture', 'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0),
                'active' => 1, 'status' => Event::STATUS_CONFIRMED, 'preparation_minutes' => 15, 'closing_minutes' => 20]);
            ob_start();
            (static function (): void {
                global $lang, $sourceLang;
                require dirname(__DIR__) . '/omo/api/calendar/index.php';
            })();
            $html = ob_get_clean();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $dataNode = $xpath->query('//script[@data-omo-calendar-data]')->item(0);
            $payload = json_decode($dataNode->textContent, true, 512, JSON_THROW_ON_ERROR);
            $importedItems = array_values(array_filter($payload['items'], static fn($item) => !empty($item['isExternal'])));
            availabilityExpect(count($importedItems) > 0, 'Own imported events are included.');
            foreach ($importedItems as $item) {
                availabilityExpect($item['externalDrawerData']['editUrl'] === '/omo/api/calendar/external_event.php?oid='
                    . $org->getId() . '&id=' . $external->getId(), 'Imported editor URL uses the real cache row, not its virtual event ID.');
            }
            $buffers = array_values(array_filter($payload['items'], static fn($item) => !empty($item['bufferKind'])
                && (int)$item['id'] === (int)$bufferedEvent->getId()));
            availabilityExpect(count($buffers) === 2 && $buffers[0]['startMinute'] === 585 && $buffers[0]['endMinute'] === 600
                && $buffers[1]['startMinute'] === 660 && $buffers[1]['endMinute'] === 680, 'Timeline serializes the exact attached intervals.');
            foreach (['week', 'day'] as $view) {
                $dayItems = array_merge(...array_column($payload['views']['contextual'][$view]['days'], 'timed'));
                $attached = array_filter($dayItems, static fn($id) => !empty($payload['items'][$id]['bufferKind'])
                    && (int)$payload['items'][$id]['id'] === (int)$bufferedEvent->getId());
                availabilityExpect(count($attached) === 2, 'Both week and day retain preparation and closing segments.');
                $parentSegments = array_values(array_filter(array_map(static fn($id) => $payload['items'][$id], $dayItems),
                    static fn($item) => (int)$item['id'] === (int)$bufferedEvent->getId()));
                availabilityExpect(count(array_unique(array_column($parentSegments, 'column'))) === 1
                    && count(array_unique(array_column($parentSegments, 'columnCount'))) === 1, 'Parent and attached bands keep the same width and position despite overlaps.');
            }
            $otherItems = array_filter($payload['items'], static fn($item) => !empty($item['isOtherOrganization']));
            availabilityExpect(count($otherItems) > 0, 'Week/day data contains other-organization blocks');
            foreach ($otherItems as $itemId => $item) {
                availabilityExpect($item['title'] === $other->get('name') && $item['documentUrl'] === '', 'Only organization label is exposed');
                foreach ($payload['views'] as $scopeViews) {
                    foreach ($scopeViews['month']['days'] as $dayData) {
                        availabilityExpect(!in_array($itemId, $dayData['items'], true), 'No other-organization block in month');
                    }
                    foreach ($scopeViews['list']['sections'] as $section) {
                        availabilityExpect(!in_array($itemId, $section['items'], true), 'No other-organization block in list');
                    }
                }
            }
            availabilityExpect(!str_contains($html, 'SECRET title') && !str_contains($html, 'SECRET details'), 'Source event never serialized into page');
            availabilityExpect($xpath->query('//*[@data-omo-calendar-view-panel]')->length === 0, 'No interfaces constructed server-side');
            $script = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/calendar.js');
            availabilityExpect(str_contains($script, "attr('now-indicator')"), 'Lazy timelines contain current-time indicators');
            availabilityExpect($xpath->query('//*[@data-omo-calendar-timezone and normalize-space(@data-omo-calendar-timezone) != ""]')->length === 1,
                'Calendar timezone is supplied to current-time positioning');
            availabilityExpect(str_contains($script, 'scrollTimelineToRelevantTime') && str_contains($script, 'availableHeight / 2')
                && str_contains($script, 'setInterval(function ()'), 'Current-time line updates and opens vertically centered');
            $week = $payload['views']['contextual']['week'];
            availabilityExpect(count($week['days']) === 7 && preg_match('/^(Lun|Mar|Mer|Jeu|Ven|Sam|Dim) [0-9]{1,2}$/', $week['days'][0]['label']) === 1,
                'Timeline day heading omits repeated month name');
            availabilityExpect(isset($week['count'], $week['days'][0]['count'], $week['days'][0]['countLabel']), 'Timeline badges retain counts and labels');
            $styles = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/calendar.css');
            availabilityExpect(substr_count($styles, '-webkit-line-clamp: 2') >= 2, 'Timed and all-day event titles are clamped to two lines');
        } elseif ($argv[1] === '--preview') {
            $calendar->markSyncResult(true);
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $_POST = [
                'availability_preview' => '1',
                'month' => $day->format('Y-m'),
                'date' => $day->format('Y-m-d'),
                'invitation_user_ids' => [(int)$organizer->getId()],
            ];
            ob_start();
            register_shutdown_function(static function () use ($day): void {
                $output = ob_get_clean();
                availabilityExpect(str_contains($output, 'data-omo-calendar-preview-target'), 'Preview endpoint returns navigable shared calendar.');
                availabilityExpect(str_contains($output, '2 personnes prises en compte') && str_contains($output, 'Guest') && str_contains($output, 'Organizer') && str_contains($output, 'Organisateur'), 'Preview identifies invited member and event owner separately.');
                availabilityExpect(!str_contains($output, 'SECRET title') && !str_contains($output, 'SECRET details'), 'Preview never exposes private event content.');
                availabilityExpect((bool)preg_match('/<script type="application\/json" data-omo-calendar-preview-data>(.*?)<\/script>/s', $output, $payload), 'Preview includes local filtering data.');
                $clientData = json_decode($payload[1], true, 512, JSON_THROW_ON_ERROR);
                availabilityExpect(count($clientData['people']) === 2, 'Month data contains each considered person once.');
                foreach ($clientData['people'] as $person) {
                    availabilityExpect($person['name'] !== '' && strlen($person['days'][$day->format('Y-m-d')]) === 48, 'Hover names and half-hour states are available locally.');
                    availabilityExpect((bool)preg_match('/^[0123]{48}$/', $person['days'][$day->format('Y-m-d')]), 'Client gets availability codes, not private appointment details.');
                }
                availabilityExpect(!str_contains(json_encode($clientData), 'SECRET'), 'Local cache discloses no private titles.');
                preg_match_all('/<input[^>]*data-omo-calendar-preview-person[^>]*>/', $output, $filters);
                availabilityExpect(count($filters[0]) === 2 && !str_contains(implode('', $filters[0]), ' name='), 'Search filters are not submitted as invitations.');
                if ((int)$day->format('N') <= 5) {
                    availabilityExpect(str_contains($output, 'data-state="busy"'), 'Invitee OMO appointment blocks a shared slot.');
                    availabilityExpect(str_contains($output, '<button type="button" class="calendar-freebusy-slot" data-state="free"')
                        && str_contains($output, '<div class="calendar-freebusy-slot" data-state="busy"'), 'Only free slots can set event times.');
                }
                echo "calendar_availability_test --preview: OK\n";
            });
            require dirname(__DIR__) . '/omo/api/calendar/create.php';
        } else {
            $_POST = ['title' => 'Availability test ' . $nonce, 'status' => Event::STATUS_DRAFT,
                'start_at' => $day->format('Y-m-d') . 'T10:30', 'end_at' => $day->format('Y-m-d') . 'T11:30',
                'invitation_emails' => 'unknown@example.invalid'];
            ob_start();
            register_shutdown_function(static function () use ($day, $other): void {
                $output = ob_get_clean();
                $payload = json_decode($output, true);
                availabilityExpect(is_array($payload) && $payload['status'] === false && !empty($payload['availability']['acknowledgement']), 'Event endpoint reports conflicts before saving: ' . $output);
                availabilityExpect(!str_contains($output, 'SECRET'), 'Endpoint does not disclose event details');
                availabilityExpect(!str_contains($output, '{name}') && !str_contains($output, '{start}'), 'Warning placeholders are translated and substituted');
                $conflicts = array_values(array_filter($payload['availability']['items'], static fn($item) => $item['kind'] === 'conflict'));
                availabilityExpect(count($conflicts) === 1 && $conflicts[0]['context'] === $other->get('name') . ' - Cercle Ancrage', 'Endpoint supplies organization and circle context');
                availabilityExpect($conflicts[0]['start'] === $day->format('Y-m-d') . ' 10:00' && $conflicts[0]['end'] === $day->format('Y-m-d') . ' 11:00', 'Endpoint preserves full times');
                echo "calendar_availability_test --post-warning: OK\n";
            });
            require dirname(__DIR__) . '/omo/api/calendar/create.php';
        }
    }
    echo "calendar_availability_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}
