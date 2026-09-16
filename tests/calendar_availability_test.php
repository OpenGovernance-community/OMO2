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
    foreach ([$organizer, $guest] as $member) {
        availabilityFixture(UserOrganization::class, ['IDorganization' => $org->getId(), 'IDuser' => $member->getId(), 'active' => 1]);
    }
    $day = new DateTimeImmutable('tomorrow 00:00');
    $otherHolon = availabilityFixture(\dbObject\Holon::class, ['id' => 0, 'name' => 'Cercle Ancrage',
        'IDorganization' => $other->getId(), 'IDuser' => $guest->getId(), 'active' => 1, 'visible' => 1]);
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
    if (in_array($argv[1] ?? '', ['--render', '--post-warning'], true)) {
        $busy->set('active', 1); $busy->set('is_all_day', 0); $busy->save();
        $_SESSION['currentUser'] = (int)$guest->getId();
        $_SESSION['currentOrganization'] = (int)$org->getId();
        $_SERVER['HTTP_HOST'] = 'localtest.me';
        $_SERVER['REQUEST_METHOD'] = ($argv[1] === '--render') ? 'GET' : 'POST';
        $_SERVER['REQUEST_URI'] = '/omo/api/calendar/' . ($argv[1] === '--render' ? 'index.php' : 'create.php');
        $_GET = ['oid' => $org->getId(), 'view' => 'week', 'date' => $day->format('Y-m-d')];
        $_REQUEST = $_GET;
        if ($argv[1] === '--render') {
            ob_start();
            (static function (): void {
                global $lang, $sourceLang;
                require dirname(__DIR__) . '/omo/api/calendar/index.php';
            })();
            $html = ob_get_clean();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $nodes = $xpath->query('//*[@data-omo-calendar-other-organization]');
            availabilityExpect($nodes->length > 0, 'Rendered week/day contains other-organization blocks');
            foreach ($nodes as $node) {
                availabilityExpect(trim($node->textContent) === $other->get('name'), 'Only organization name rendered');
                $panel = $xpath->query('ancestor::*[@data-omo-calendar-view-panel][1]', $node)->item(0);
                availabilityExpect(in_array($panel->getAttribute('data-omo-calendar-view-panel'), ['week', 'day'], true), 'No other-organization blocks in month/list');
            }
            availabilityExpect(!str_contains($html, 'SECRET title') && !str_contains($html, 'SECRET details'), 'Source event never serialized into page');
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
