<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/calendar/recurrence.php';

use dbObject\Event;
use dbObject\EventRecurrence;
use dbObject\Document;

function recurrenceExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function recurrenceFixture(string $class, array $fields)
{
    $item = new $class(); foreach ($fields as $field => $value) { $item->set($field, $value); }
    $result = $item->save(); recurrenceExpect(!empty($result['status']), $class . ': ' . json_encode($result));
    return $item;
}
function recurrenceEvents(EventRecurrence $series): array
{
    $items = new \dbObject\ArrayEvent(); $items->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $series->getId()]],
        'orderBy' => [['field' => 'recurrence_position', 'dir' => 'ASC']]]);
    return array_values($items->getArrayCopy());
}

// Only full numeric dates matching the source meeting are rewritten, retaining their format.
$titleFrom = new DateTimeImmutable('2026-10-12 09:00:00');
$titleTo = new DateTimeImmutable('2027-02-03 14:30:15');
foreach ([
    'PV Reunion OP du 12.10.2026 09:00' => 'PV Reunion OP du 03.02.2027 14:30',
    'PV du 12/10/26 a 9h' => 'PV du 03/02/27 a 14h30',
    'PV 12-10-2026, 09H00' => 'PV 03-02-2027, 14H30',
    'PV 2026-10-12T09:00:00' => 'PV 2027-02-03T14:30:15',
    'PV 12.10.2026 de 9:00' => 'PV 03.02.2027 de 14:30',
    "PV 12.10.2026 \u{00e0} 09:00" => "PV 03.02.2027 \u{00e0} 14:30",
    '12.10.2026 / annexe 01.10.2026 / 12/10/2026' => '03.02.2027 / annexe 01.10.2026 / 03/02/2027',
    '12.10.2026 08:00' => '03.02.2027 08:00',
    'Sans date / 10/12/2026 / 12 octobre 2026 / 12.10 / 09:00' => 'Sans date / 10/12/2026 / 12 octobre 2026 / 12.10 / 09:00',
    'REF12.10.2026 / 112.10.2026 / 12.10.20260 / 12.10.2026.1' => 'REF12.10.2026 / 112.10.2026 / 12.10.20260 / 12.10.2026.1',
] as $before => $after) {
    recurrenceExpect(Event::replaceMeetingDateInTitle($before, $titleFrom, $titleTo) === $after, 'Title format: ' . $before);
}
recurrenceExpect(Event::replaceMeetingDateInTitle('2/1/26 9h15 - 2026-1-2', new DateTimeImmutable('2026-01-02 09:15'), $titleTo)
    === '3/2/27 14h30 - 2027-2-3', 'Unpadded numeric dates.');
recurrenceExpect(Event::replaceMeetingDateInTitle('12.10.2026 09:00', $titleFrom, $titleFrom) === '12.10.2026 09:00', 'Unchanged schedule preserves title.');

// Rules are tested on real calendar boundaries, independently of today's date.
$rule = new EventRecurrence();
foreach (EventRecurrence::normalizeSettings(['frequency' => 'monthly_day', 'month_day' => 31]) as $field => $value) { $rule->set($field, $value); }
$rule->set('timezone', 'Europe/Zurich'); $rule->set('anchor_date', '2030-01-31'); $rule->set('anchor_position', 0);
recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-02-28', 'Clamp February without drifting the March date.');
recurrenceExpect($rule->occurrenceDate(2)->format('Y-m-d') === '2030-03-31', 'Restore the 31st in March.');
$rule->set('month_day', 1); $rule->set('anchor_date', '2030-05-01'); $rule->set('weekend_shift', 'previous');
recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-05-31', 'Saturday June 1 moves to Friday in May.');
$rule->set('weekend_shift', 'next');
recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-06-03', 'Saturday moves to Monday.');
$rule->set('frequency', 'monthly_weekday'); $rule->set('ordinal', 1); $rule->set('weekday', 1); $rule->set('anchor_date', '2030-01-07');
recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-02-04', 'First Monday of following month.');
$rule->set('ordinal', 5);
recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-01-28', 'Last weekday later in the same month.');
$rule->set('frequency', 'weekly'); $rule->set('weekday', 2);
recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-01-08', 'A different weekday does not skip the first week.');
recurrenceExpect($rule->occurrenceDate(-1)->format('Y-m-d') === '2030-01-01', 'Earlier future positions can be recalculated from a later reference.');
$rule->set('frequency', 'days'); $rule->set('interval_days', 10); $rule->set('weekend_shift', 'none');
recurrenceExpect($rule->occurrenceDate(2)->format('Y-m-d') === '2030-01-27', 'Day intervals remain anchored.');
$rule->set('anchor_date', '2030-01-04'); $rule->set('interval_days', 1);
foreach (['previous', 'next'] as $shift) {
    $rule->set('weekend_shift', $shift);
    recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-01-07', 'Weekend collisions create only one Monday after Friday.');
    recurrenceExpect($rule->occurrenceDate(2)->format('Y-m-d') === '2030-01-08', 'Next distinct date after a weekend.');
}
$rule->set('interval_days', 2); $rule->set('weekend_shift', 'next');
recurrenceExpect($rule->occurrenceDate(1)->format('Y-m-d') === '2030-01-07' && $rule->occurrenceDate(2)->format('Y-m-d') === '2030-01-08', 'Weekend shift keeps the nominal two-day cadence.');
foreach ([['frequency' => 'invalid'], ['horizon_months' => 0], ['interval_days' => '1.5'], ['weekday' => 8]] as $invalid) {
    try { EventRecurrence::normalizeSettings($invalid); throw new RuntimeException('Invalid settings accepted.'); }
    catch (InvalidArgumentException $expected) {}
}

$pdo = \dbObject\DbObject::getPdo(); $pdo->beginTransaction();
try {
    $nonce = 'recurrence-' . bin2hex(random_bytes(5));
    $user = recurrenceFixture(\dbObject\User::class, ['email' => $nonce . '@example.test', 'firstname' => 'Recurrence', 'lastname' => 'Owner', 'active' => 1]);
    $member = recurrenceFixture(\dbObject\User::class, ['email' => $nonce . '-member@example.test', 'firstname' => 'Member', 'active' => 1]);
    $organization = recurrenceFixture(\dbObject\Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $oid = (int)$organization->getId(); $uid = (int)$user->getId();
    $_SESSION['currentOrganization'] = $oid; $_SESSION['currentUser'] = $uid;
    foreach ([$user, $member] as $person) {
        recurrenceFixture(\dbObject\UserOrganization::class, ['IDuser' => $person->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    foreach (['structure', 'calendar', 'documents'] as $hash) {
        $application = new \dbObject\Application(); $application->load([['hash', $hash]]);
        recurrenceFixture(\dbObject\OrganizationApplication::class, ['IDapplication' => $application->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    // A future series gets a full horizon from its anchor, including weekend dates.
    foreach ([
        'none' => ['2030-01-04', '2030-01-08', '2030-01-12', '2030-01-16', '2030-01-20', '2030-01-24', '2030-01-28', '2030-02-01'],
        'previous' => ['2030-01-04', '2030-01-08', '2030-01-11', '2030-01-16', '2030-01-18', '2030-01-24', '2030-01-28', '2030-02-01'],
        'next' => ['2030-01-04', '2030-01-08', '2030-01-14', '2030-01-16', '2030-01-21', '2030-01-24', '2030-01-28', '2030-02-01'],
    ] as $shift => $expectedDates) {
        $future = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Future weekend horizon',
            'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'active' => 1, 'start_at' => '2030-01-04 09:00:00', 'end_at' => '2030-01-04 10:00:00']);
        EventRecurrence::configure($future, ['frequency' => 'days', 'interval_days' => 4, 'weekend_shift' => $shift, 'horizon_months' => 1], false, true);
        $futureSeries = $future->getRecurrence();
        $earlyNow = new DateTimeImmutable('2029-12-10 08:00:00', new DateTimeZone('Europe/Zurich'));
        recurrenceExpect(EventRecurrence::generateDueBatch(100, $earlyNow, (int)$futureSeries->getId()) === 7, 'Full month from the future start, across weekends: ' . $shift);
        recurrenceExpect(array_map(static fn($item) => $item->get('start_at')->format('Y-m-d'), recurrenceEvents($futureSeries)) === $expectedDates, 'Weekend rules preserve the four-day cadence: ' . $shift);
        recurrenceExpect(EventRecurrence::generateDueBatch(100, $earlyNow, (int)$futureSeries->getId()) === 0, 'Future horizon does not extend itself or duplicate meetings: ' . $shift);
        $rollingNow = new DateTimeImmutable('2030-01-15 08:00:00', new DateTimeZone('Europe/Zurich'));
        recurrenceExpect(EventRecurrence::generateDueBatch(100, $rollingNow, (int)$futureSeries->getId()) === ($shift === 'previous' ? 4 : 3), 'Cron extends to February 15, including Sunday 17 shifted back to Friday 15: ' . $shift);
        recurrenceExpect(EventRecurrence::generateDueBatch(100, $rollingNow, (int)$futureSeries->getId()) === 0, 'Extended horizon remains idempotent: ' . $shift);
    }
    $future = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Future leap February',
        'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'active' => 1, 'start_at' => '2032-01-31 09:00:00', 'end_at' => '2032-01-31 10:00:00']);
    EventRecurrence::configure($future, ['frequency' => 'days', 'interval_days' => 1, 'horizon_months' => 1], false, true);
    $futureSeries = $future->getRecurrence();
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $earlyNow, (int)$futureSeries->getId()) === 29, 'Far-future horizon clamps January 31 to leap February 29, including weekends.');
    $futureEvents = recurrenceEvents($futureSeries);
    recurrenceExpect(end($futureEvents)->get('start_at')->format('Y-m-d') === '2032-02-29', 'No overflow into March at the future horizon boundary.');
    $holon = recurrenceFixture(\dbObject\Holon::class, ['name' => 'Recurring group', 'IDorganization' => $oid, 'IDtypeholon' => 2, 'visible' => 1, 'active' => 1]);
    $holon->set('IDholon_org', $holon->getId()); $holon->save();
    $membership = recurrenceFixture(\dbObject\UserHolon::class, ['IDuser' => $member->getId(), 'IDholon' => $holon->getId(), 'is_membership' => 1, 'active' => 1]);
    $event = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $holon->getId(), 'IDuser' => $uid,
        'title' => 'Operational meeting', 'description' => '<p>General framework</p>', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'active' => 1,
        'start_at' => '2030-03-19 09:00:00', 'end_at' => '2030-03-19 10:00:00']);
    recurrenceFixture(\dbObject\EventInvitation::class, ['IDevent' => $event->getId(), 'IDholon' => $holon->getId(), 'invitation_type' => 'holon', 'active' => 1, 'status' => 'invited']);
    $pv = new Document();
    $pvResult = $pv->createInOrganizationContext($oid, (int)$holon->getId(), $uid,
        ['title' => 'Operational PV', 'document_type' => 'pv', 'event_id' => $event->getId()]);
    recurrenceExpect(!empty($pvResult['status']), 'Create base PV: ' . json_encode($pvResult));
    $automaticPv = new Document();
    recurrenceExpect(!empty($automaticPv->createInOrganizationContext($oid, (int)$holon->getId(), $uid,
        ['title' => 'PV Operational meeting du 19.03.2030 09:00', 'document_type' => 'pv', 'event_id' => $event->getId()])['status']), 'Create default-named PV.');
    recurrenceExpect($event->registerDefaultDocumentTitle($automaticPv, ['before' => 'PV Operational meeting du ', 'after' => '']), 'Remember that only the date portion is automatic.');
    $group = recurrenceFixture(\dbObject\DocumentPvPoint::class, ['IDdocument' => $pv->getId(), 'item_type' => 'group', 'title' => 'Operations', 'position' => 1, 'active' => 1]);
    recurrenceFixture(\dbObject\DocumentPvPoint::class, ['IDdocument' => $pv->getId(), 'IDparent' => $group->getId(), 'item_type' => 'point',
        'title' => 'Review indicators', 'content' => '<p>Review framework</p>', 'position' => 1, 'active' => 1, 'is_handled' => 1, 'IDuser_author' => $uid]);
    $app = new \dbObject\Application(); $app->load([['hash', 'calendar']]);
    recurrenceFixture(\dbObject\DocumentApplicationTab::class, ['IDdocument' => $pv->getId(), 'IDapplication' => $app->getId(), 'position' => 1]);
    EventRecurrence::configure($event, ['frequency' => 'weekly', 'weekday' => 2, 'horizon_months' => 1], false, true);
    $series = $event->getRecurrence();
    recurrenceExpect($series instanceof EventRecurrence, 'Base event linked to series.');
    $now = new DateTimeImmutable('2030-03-19 08:00:00', new DateTimeZone('Europe/Zurich'));
    $session = $_SESSION; $_SESSION = [];
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $now, (int)$series->getId()) === 4, 'Generate four meetings to April 19 without a logged-in cron user.');
    $_SESSION = $session;
    $events = recurrenceEvents($series); recurrenceExpect(count($events) === 5, 'Base plus four independent events.');
    $next = $events[1];
    $neighbours = $series->getAdjacentEvents($next, static fn($candidate) => true);
    recurrenceExpect((int)$neighbours['previous']->getId() === (int)$event->getId()
        && (int)$neighbours['next']->getId() === (int)$events[2]->getId(), 'Navigate to both adjacent dates in the series.');
    recurrenceExpect($series->getAdjacentEvents($event, static fn($candidate) => true)['previous'] === null
        && $series->getAdjacentEvents($events[4], static fn($candidate) => true)['next'] === null, 'Navigation stops at both series boundaries.');
    $neighbours = $series->getAdjacentEvents($next, static fn($candidate) => (int)$candidate->getId() !== (int)$events[2]->getId());
    recurrenceExpect((int)$neighbours['next']->getId() === (int)$events[3]->getId(), 'Skip dates the viewer cannot access.');
    recurrenceExpect($next->get('description') === $event->get('description') && $next->get('start_at')->format('H:i') === '09:00', 'General framework and local time copied.');
    recurrenceExpect($events[2]->get('start_at')->format('H:i') === '09:00', 'Local meeting time survives DST.');
    $nextPv = $next->getAssociatedDocument();
    recurrenceExpect($nextPv instanceof Document && $nextPv->getId() !== $pv->getId(), 'Each meeting owns a new PV.');
    $nextTitles = array_map(static fn($document) => $document->get('title'), $next->getAssociatedDocuments());
    recurrenceExpect(in_array('PV Operational meeting du 26.03.2030 09:00', $nextTitles, true)
        && in_array('Operational PV', $nextTitles, true), 'Automatic PV date changes for the occurrence while custom names are copied intact.');
    $points = array_values($nextPv->getPvPoints(true)->getArrayCopy());
    recurrenceExpect(count($points) === 2 && !$points[1]->get('is_handled') && !$points[1]->get('IDuser_author'), 'Agenda copied with fresh state and no previous author.');
    recurrenceExpect(count($nextPv->getPvApplicationTabs()) === 1, 'Operational application tabs copied.');
    recurrenceExpect(count($next->getInvitations(true)) === 1 && $next->getInvitations(true)->getArrayCopy()[0]->get('invitation_type') === 'holon', 'Copy holon identity rather than individual membership snapshot.');
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $now, (int)$series->getId()) === 0, 'Repeated cron is idempotent.');
    $deletedId = (int)$events[2]->getId(); recurrenceExpect($events[2]->delete(), 'Delete individual future occurrence.');
    recurrenceExpect((int)$series->getAdjacentEvents($next, static fn($candidate) => true)['next']->getId() === (int)$events[3]->getId(), 'Deleted dates are skipped.');
    $next->set('start_at', '2030-04-11 14:00:00'); $next->set('end_at', '2030-04-11 15:00:00'); $next->save();
    $neighbours = $series->getAdjacentEvents($next, static fn($candidate) => true);
    recurrenceExpect((int)$neighbours['previous']->getId() === (int)$events[3]->getId()
        && (int)$neighbours['next']->getId() === (int)$events[4]->getId(), 'Moved dates navigate chronologically rather than by original position.');
    $next->set('start_at', '2030-03-27 14:00:00'); $next->set('end_at', '2030-03-27 15:00:00'); $next->save();
    recurrenceExpect(in_array('PV Operational meeting du 27.03.2030 14:00', array_map(static fn($document) => $document->get('title'), $next->getAssociatedDocuments()), true), 'Moving a meeting updates its automatic document date.');
    EventRecurrence::configure($next, ['frequency' => 'weekly'], false, true);
    recurrenceExpect((int)$series->getAdjacentEvents($event, static fn($candidate) => true)['next']->getId() === (int)$next->getId(), 'Personalized meetings remain in series navigation.');
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $now, (int)$series->getId()) === 0 && count(recurrenceEvents($series)) === 4, 'Deleted/moved dates never regenerate.');
    $event->set('title', 'Updated framework'); $event->set('start_at', '2030-03-19 11:00:00'); $event->set('end_at', '2030-03-19 12:00:00'); $event->save();
    EventRecurrence::configure($event, ['frequency' => 'weekly', 'weekday' => 2, 'horizon_months' => 1], true, true);
    $events = recurrenceEvents($series);
    recurrenceExpect($events[1]->get('title') === 'Operational meeting' && $events[1]->get('start_at')->format('H:i') === '14:00', 'Personalized occurrence protected from collective edits.');
    recurrenceExpect($events[2]->get('title') === 'Updated framework' && $events[2]->get('start_at')->format('H:i') === '11:00', 'Following ordinary occurrences updated.');
    recurrenceExpect((int)$events[2]->getAssociatedDocument()->getId() > 0, 'Prepared PV kept through collective changes.');
    recurrenceExpect(in_array('PV Operational meeting du ' . $events[2]->get('start_at')->format('d.m.Y H:i'),
        array_map(static fn($document) => $document->get('title'), $events[2]->getAssociatedDocuments()), true), 'Collective schedule changes update automatic document dates.');
    $series->load((int)$series->getId(), true);
    recurrenceExpect($event->delete(), 'Delete reference meeting.');
    $series->load((int)$series->getId(), true);
    recurrenceExpect((int)$series->get('IDreference_event') > 0 && (int)$series->get('IDreference_event') !== (int)$event->getId(), 'Reference transferred to next ordinary meeting.');
    $stable = $events[2];
    recurrenceExpect(count($stable->getAttendanceEntries($oid)) === 1, 'Current holon member invited.');
    $joining = recurrenceFixture(\dbObject\UserHolon::class, ['IDuser' => $uid, 'IDholon' => $holon->getId(), 'is_membership' => 1, 'active' => 1]);
    recurrenceExpect(count($stable->getEffectiveInvitationTargets($oid, fresh: true)['userIds']) === 2
        && count($stable->getAttendanceEntries($oid)) === 2, 'A new member appears in an already-generated event and its PV attendance.');
    $joining->set('active', 0); $joining->save();
    recurrenceExpect(count($stable->getEffectiveInvitationTargets($oid, fresh: true)['userIds']) === 1
        && count($stable->getAttendanceEntries($oid)) === 1, 'A departed member disappears from future invitations and PV attendance.');
    recurrenceExpect($stable->freezeInvitationParticipants(), 'Freeze historical participant identities.');
    $membership->set('active', 0); $membership->save();
    recurrenceExpect(count($stable->getAttendanceEntries($oid)) === 1, 'Participant remains in historical meeting after departure.');

    $laterReference = $events[3];
    $laterReference->set('title', 'Framework from a later occurrence'); $laterReference->save();
    EventRecurrence::configure($laterReference, ['frequency' => 'weekly', 'weekday' => 2, 'horizon_months' => 1], true, true);
    $stable->load((int)$stable->getId(), true);
    recurrenceExpect($stable->get('title') === 'Updated framework', 'Following scope preserves earlier meetings even when they are still in the future.');
    $unchangedState = EventRecurrence::captureMeetingEditState($stable);
    $stable->save();
    EventRecurrence::configure($stable, [], false, true, 'content', $unchangedState);
    recurrenceExpect(!$stable->get('recurrence_exception'), 'Unchanged earlier occurrence stays ordinary even when the series blueprint has since changed.');
    $invitationState = EventRecurrence::captureMeetingEditState($stable);
    recurrenceFixture(\dbObject\EventInvitation::class, ['IDevent' => $stable->getId(), 'IDuser' => $uid, 'invitation_type' => 'user', 'active' => 1, 'status' => 'invited']);
    recurrenceExpect($invitationState !== EventRecurrence::captureMeetingEditState($stable), 'Adding an invitation counts as a meeting change.');
    EventRecurrence::configure($next, ['frequency' => 'days', 'interval_days' => 10, 'horizon_months' => 1], false, true);
    recurrenceExpect((int)$next->get('IDeventrecurrence') === (int)$series->getId() && $next->get('recurrence_exception'),
        'Saving only a personalized meeting never starts a new series or changes following dates.');
    $independentSource = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Independent series', 'status' => 'confirmed',
        'timezone' => 'Europe/Zurich', 'start_at' => $next->get('start_at'), 'end_at' => $next->get('end_at'), 'active' => 1]);
    EventRecurrence::configure($independentSource, ['frequency' => 'days', 'interval_days' => 10, 'horizon_months' => 1], false, true);
    $independent = $independentSource->getRecurrence(); $independentSource->delete();
    $independent->load((int)$independent->getId(), true);
    recurrenceExpect(!$independent->get('IDreference_event'), 'Deleting the only reference preserves a source-free blueprint.');
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $now, (int)$independent->getId()) === 3, 'A series preserves a full month from its future anchor even after deletion of its first meeting.');
    $independent->load((int)$independent->getId(), true);
    recurrenceExpect((int)$independent->get('IDreference_event') > 0, 'The first generated meeting becomes the replacement reference.');
    $remainingCount = count(recurrenceEvents($series));
    EventRecurrence::configure($laterReference, [], true, false);
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $now->modify('+3 months'), (int)$series->getId()) === 0
        && count(recurrenceEvents($series)) === $remainingCount, 'Stopping a series halts generation while keeping its scheduled meetings.');

    $cadence = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Changing cadence', 'status' => 'confirmed',
        'timezone' => 'Europe/Zurich', 'start_at' => '2030-01-02 09:00:00', 'end_at' => '2030-01-02 10:00:00', 'active' => 1]);
    EventRecurrence::configure($cadence, ['frequency' => 'weekly', 'weekday' => 3, 'horizon_months' => 1], false, true);
    $cadenceSeries = $cadence->getRecurrence(); $cadenceNow = new DateTimeImmutable('2030-01-01 08:00:00');
    EventRecurrence::generateDueBatch(100, $cadenceNow, (int)$cadenceSeries->getId());
    $cadenceEvents = recurrenceEvents($cadenceSeries);
    $cadenceReference = $cadenceEvents[2];
    EventRecurrence::configure($cadenceReference, ['frequency' => 'days', 'interval_days' => 2, 'horizon_months' => 1], true, true);
    $cadenceEvents[1]->load((int)$cadenceEvents[1]->getId(), true); $cadenceEvents[3]->load((int)$cadenceEvents[3]->getId(), true);
    recurrenceExpect($cadenceEvents[1]->get('start_at')->format('Y-m-d') === '2030-01-09', 'Changing cadence leaves preceding dates intact.');
    recurrenceExpect($cadenceEvents[3]->get('start_at')->format('Y-m-d') === '2030-01-18', 'Existing following dates are replanned immediately with the new cadence.');
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $cadenceNow, (int)$cadenceSeries->getId()) > 0, 'New cadence fills its horizon without waiting for the old last date.');
    $cadenceEvents = recurrenceEvents($cadenceSeries);
    recurrenceExpect($cadenceEvents[5]->get('start_at')->format('Y-m-d') === '2030-01-22', 'Generation continues directly after the replanned dates.');
    $cadenceCount = count($cadenceEvents);
    EventRecurrence::configure($cadenceReference, [], true, false);
    recurrenceExpect(count(recurrenceEvents($cadenceSeries)) === $cadenceCount
        && EventRecurrence::generateDueBatch(100, $cadenceNow->modify('+3 months'), (int)$cadenceSeries->getId()) === 0,
        'Unchecking recurrence collectively stops generation and keeps every scheduled appointment.');

    // A future-only rule keeps planned dates and never rewinds the consumed cursor.
    $beforeDates = array_map(static fn($item) => $item->get('start_at')->format('Y-m-d H:i:s'), recurrenceEvents($cadenceSeries));
    $cadenceSeries->load($cadenceSeries->getId(), true);
    $cursor = (int)$cadenceSeries->get('next_position');
    $lastDate = max($beforeDates);
    EventRecurrence::configure($cadenceEvents[0], ['frequency' => 'days', 'interval_days' => 10, 'horizon_months' => 1], true, true, 'after_last');
    $cadenceSeries = $cadenceEvents[0]->getRecurrence();
    recurrenceExpect($beforeDates === array_map(static fn($item) => $item->get('start_at')->format('Y-m-d H:i:s'), recurrenceEvents($cadenceSeries)), 'Future-only strategy preserves all planned dates.');
    recurrenceExpect((int)$cadenceSeries->get('next_position') === $cursor && $cadenceSeries->occurrenceDate($cursor)->format('Y-m-d') === (new DateTimeImmutable($lastDate))->modify('+10 days')->format('Y-m-d'), 'New rule begins after the last actual planned date without rewinding.');
    $anchor = $cadenceSeries->get('anchor_date')->format('Y-m-d');
    $cadenceEvents[0]->set('title', 'Updated content'); $cadenceEvents[0]->save();
    EventRecurrence::configure($cadenceEvents[0], [], true, true, 'content');
    $cadenceSeries = $cadenceEvents[0]->getRecurrence();
    recurrenceExpect($cadenceSeries->get('frequency') === 'days' && $cadenceSeries->get('anchor_date')->format('Y-m-d') === $anchor, 'Content edits retain the future-only rule and anchor.');
    recurrenceExpect($beforeDates === array_map(static fn($item) => $item->get('start_at')->format('Y-m-d H:i:s'), recurrenceEvents($cadenceSeries)), 'Content edits do not replan existing dates.');
    EventRecurrence::configure($cadenceEvents[0], [], true, true, 'stop_keep');
    recurrenceExpect(!$cadenceEvents[0]->getRecurrence()->get('active') && count(recurrenceEvents($cadenceSeries)) === count($beforeDates), 'Stop keeps every planned meeting.');
    EventRecurrence::configure($cadenceEvents[0], [], true, true, 'content');
    recurrenceExpect(!$cadenceEvents[0]->getRecurrence()->get('active'), 'An ordinary collective edit cannot restart a stopped series.');
    recurrenceExpect($cadenceSeries->hasFollowing($cadenceEvents[0]) && !$cadenceSeries->hasFollowing(end($cadenceEvents)), 'Only the last planned position has no following meetings.');

    $manual = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Adaptive cadence', 'status' => 'confirmed',
        'timezone' => 'Europe/Zurich', 'start_at' => '2030-04-02 09:00:00', 'end_at' => '2030-04-02 10:00:00', 'active' => 1]);
    try { EventRecurrence::configure($manual, ['frequency' => 'on_close'], false, true); throw new RuntimeException('On-close without PV accepted.'); }
    catch (InvalidArgumentException $expected) { recurrenceExpect($expected->getMessage() === 'calendar.recurrence.requires_pv', 'Explain the required PV.'); }
    $manualPv = new Document();
    recurrenceExpect(!empty($manualPv->createInOrganizationContext($oid, null, $uid,
        ['title' => 'Adaptive PV', 'document_type' => 'pv', 'event_id' => $manual->getId()])['status']), 'Create the PV needed at closing.');
    try { EventRecurrence::configure($manual, ['frequency' => 'weekly', 'document_mode' => 'reuse'], false, true); throw new RuntimeException('Shared PV accepted.'); }
    catch (InvalidArgumentException $expected) { recurrenceExpect($expected->getMessage() === 'calendar.recurrence.shared_pv', 'Explain why a PV cannot be shared.'); }
    EventRecurrence::configure($manual, ['frequency' => 'on_close'], false, true); $manualSeries = $manual->getRecurrence();
    recurrenceExpect(commonMeetingNextDateConfig($manual)['suggested'] === '2030-04-09T09:00', 'Existing on-close series keep a seven-day suggestion by default.');
    foreach ([1, 14, 30, 366] as $approximateDays) {
        $manualSeries->set('interval_days', $approximateDays); $manualSeries->save();
        $suggestion = commonMeetingNextDateConfig($manual);
        recurrenceExpect($suggestion['suggested'] === (new DateTimeImmutable('2030-04-02 09:00:00'))->modify('+' . $approximateDays . ' days')->format('Y-m-d\TH:i'), 'On-close suggestion uses the persisted approximate interval: ' . $approximateDays);
        recurrenceExpect($suggestion['duration'] === 3600, 'Approximate interval never changes the model duration.');
    }
    recurrenceExpect(EventRecurrence::generateDueBatch(100, $now, (int)$manualSeries->getId()) === 0, 'On-close mode never generates via cron.');
    $nextId = $manualSeries->scheduleNext($manual, new DateTimeImmutable('2030-04-18 15:30:00', new DateTimeZone('Europe/Zurich')));
    recurrenceExpect($nextId === $manualSeries->scheduleNext($manual, new DateTimeImmutable('2030-04-19')), 'Repeated close never duplicates successor.');
    $manualNext = new Event(); $manualNext->load($nextId, true);
    recurrenceExpect($manualNext->get('start_at')->format('Y-m-d H:i') === '2030-04-18 15:30' && $manualNext->get('end_at')->format('H:i') === '16:30', 'Chosen next date retains meeting duration.');
    recurrenceExpect(commonMeetingNextDateConfig($manualNext)['suggested'] === '2031-04-19T09:00', 'Following suggestion starts from the actual chosen date and keeps model hours.');

    // Shared documents keep a stable identity and survive deletion of the reference meeting.
    $sharedEvent = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Shared notebook', 'status' => 'confirmed',
        'timezone' => 'Europe/Zurich', 'start_at' => '2030-10-12 09:00:00', 'end_at' => '2030-10-12 10:00:00', 'active' => 1]);
    $sharedDocument = new Document();
    recurrenceExpect(!empty($sharedDocument->createInOrganizationContext($oid, null, $uid,
        ['title' => 'Notebook 12.10.2030 09:00', 'document_type' => 'html', 'content' => '<p>Shared notes</p>', 'event_id' => $sharedEvent->getId()])['status']), 'Create shared notes.');
    EventRecurrence::configure($sharedEvent, ['frequency' => 'days', 'interval_days' => 7, 'horizon_months' => 1, 'document_mode' => 'reuse'], false, true);
    $sharedSeries = $sharedEvent->getRecurrence(); $sharedNow = new DateTimeImmutable('2030-10-12 08:00:00');
    EventRecurrence::generateDueBatch(100, $sharedNow, (int)$sharedSeries->getId());
    $sharedEvents = recurrenceEvents($sharedSeries);
    foreach ($sharedEvents as $sharedOccurrence) {
        recurrenceExpect((int)$sharedOccurrence->getAssociatedDocument()->getId() === (int)$sharedDocument->getId(), 'Every occurrence opens the very same document.');
    }
    recurrenceExpect(count(\dbObject\EventSharedDocument::documentsByEvent(array_map(static fn($item) => (int)$item->getId(), $sharedEvents), $oid)) === count($sharedEvents), 'Batch lookup covers the original and generated events.');
    $sharedDocument->load((int)$sharedDocument->getId(), true); $sharedDocumentDate = $sharedDocument->get('datecreation')->format('c');
    $sharedEvents[1]->set('start_at', '2030-10-20 14:00:00'); $sharedEvents[1]->set('end_at', '2030-10-20 15:00:00'); $sharedEvents[1]->save();
    $sharedDocument->load((int)$sharedDocument->getId(), true);
    recurrenceExpect($sharedDocument->get('title') === 'Notebook 12.10.2030 09:00' && $sharedDocument->get('datecreation')->format('c') === $sharedDocumentDate, 'Moving an occurrence never renames or redates the shared document.');
    recurrenceExpect($sharedEvent->delete(), 'Delete original shared meeting.');
    EventRecurrence::generateDueBatch(100, $sharedNow->modify('+1 month'), (int)$sharedSeries->getId());
    $sharedEvents = recurrenceEvents($sharedSeries); $sharedLast = end($sharedEvents);
    recurrenceExpect((int)$sharedLast->getAssociatedDocument()->getId() === (int)$sharedDocument->getId(), 'Reference transfer and later generation retain the shared document.');
    EventRecurrence::configure($sharedLast, ['frequency' => 'days', 'interval_days' => 7, 'horizon_months' => 1, 'document_mode' => 'copy'], true, true, 'after_last');
    EventRecurrence::generateDueBatch(100, $sharedNow->modify('+2 months'), (int)$sharedSeries->getId());
    $sharedEvents = recurrenceEvents($sharedSeries); $copiedLast = end($sharedEvents);
    recurrenceExpect((int)$copiedLast->getAssociatedDocument()->getId() !== (int)$sharedDocument->getId()
        && (int)$sharedLast->getAssociatedDocument()->getId() === (int)$sharedDocument->getId(), 'Switching to copies affects new creations only.');

    // Date-bearing names follow generation, individual moves and later reference changes.
    $dated = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'OP 12.10.2030 09:00', 'status' => 'confirmed',
        'timezone' => 'Europe/Zurich', 'start_at' => '2030-10-12 09:00:00', 'end_at' => '2030-10-12 10:00:00', 'active' => 1]);
    $datedPv = new Document();
    recurrenceExpect(!empty($datedPv->createInOrganizationContext($oid, null, $uid,
        ['title' => 'PV 12/10/2030 a 9h - bilan 01/10/2030', 'document_type' => 'pv', 'event_id' => $dated->getId()])['status']), 'Create a manually dated PV.');
    $datedDefault = new Document();
    recurrenceExpect(!empty($datedDefault->createInOrganizationContext($oid, null, $uid,
        ['title' => 'PV OP 12.10.2030 09:00 du 12.10.2030 09:00', 'document_type' => 'pv', 'event_id' => $dated->getId()])['status']), 'Create a default PV whose meeting name also contains a date.');
    recurrenceExpect($dated->registerDefaultDocumentTitle($datedDefault, ['before' => 'PV OP 12.10.2030 09:00 du ', 'after' => '']), 'Track default date independently of the meeting name.');
    EventRecurrence::configure($dated, ['frequency' => 'days', 'interval_days' => 7, 'horizon_months' => 1], false, true);
    $datedSeries = $dated->getRecurrence(); $datedNow = new DateTimeImmutable('2030-10-12 08:00:00');
    EventRecurrence::generateDueBatch(100, $datedNow, (int)$datedSeries->getId());
    $datedEvents = recurrenceEvents($datedSeries); $datedNext = $datedEvents[1];
    recurrenceExpect($datedNext->get('title') === 'OP 19.10.2030 09:00', 'Generated event gets its own date in the title.');
    $datedTitles = array_map(static fn($document) => $document->get('title'), $datedNext->getAssociatedDocuments());
    recurrenceExpect(in_array('PV 19/10/2030 a 9h - bilan 01/10/2030', $datedTitles, true)
        && in_array('PV OP 19.10.2030 09:00 du 19.10.2030 09:00', $datedTitles, true), 'Both manual and default names follow the occurrence, preserving unrelated dates.');
    $datedNext->set('start_at', '2030-10-20 14:30:00'); $datedNext->set('end_at', '2030-10-20 15:30:00');
    recurrenceExpect(!empty($datedNext->save()['status']), 'Move dated occurrence.');
    recurrenceExpect($datedNext->get('title') === 'OP 20.10.2030 14:30', 'Individual move updates unchanged meeting title.');
    $datedTitles = array_map(static fn($document) => $document->get('title'), $datedNext->getAssociatedDocuments());
    recurrenceExpect(in_array('PV 20/10/2030 a 14h30 - bilan 01/10/2030', $datedTitles, true)
        && in_array('PV OP 20.10.2030 14:30 du 20.10.2030 14:30', $datedTitles, true), 'Moving updates both date mentions in default names and the date in manual names.');
    EventRecurrence::configure($datedNext, ['frequency' => 'days', 'interval_days' => 5, 'horizon_months' => 1], true, true, 'replan');
    $datedEvents = recurrenceEvents($datedSeries); $datedFollowing = $datedEvents[2];
    recurrenceExpect($datedFollowing->get('title') === 'OP 25.10.2030 14:30', 'Replanning uses the later reference date for event titles.');
    recurrenceExpect(in_array('PV 25/10/2030 a 14h30 - bilan 01/10/2030',
        array_map(static fn($document) => $document->get('title'), $datedFollowing->getAssociatedDocuments()), true), 'Replanning updates existing documents from their previous occurrence date.');
    EventRecurrence::configure($datedNext, ['frequency' => 'days', 'interval_days' => 10, 'horizon_months' => 1], true, true, 'after_last');
    EventRecurrence::generateDueBatch(100, $datedNow->modify('+1 month'), (int)$datedSeries->getId());
    $datedEvents = recurrenceEvents($datedSeries); $datedLatest = end($datedEvents); $datedStart = $datedLatest->get('start_at');
    recurrenceExpect($datedLatest->get('title') === 'OP ' . $datedStart->format('d.m.Y H:i'), 'Future-only rules retain the source date independently of the last planned anchor.');
    recurrenceExpect(in_array('PV ' . $datedStart->format('d/m/Y') . ' a 14h30 - bilan 01/10/2030',
        array_map(static fn($document) => $document->get('title'), $datedLatest->getAssociatedDocuments()), true), 'Documents use the same source snapshot after a future-only rule change.');
    $datedNext->set('title', 'Archive 20.10.2030 14:30');
    $datedNext->set('start_at', '2030-10-21 16:00:00'); $datedNext->set('end_at', '2030-10-21 17:00:00'); $datedNext->save();
    recurrenceExpect($datedNext->get('title') === 'Archive 20.10.2030 14:30', 'An explicitly edited title takes priority over automatic replacement.');
    EventRecurrence::configure($datedLatest, ['frequency' => 'on_close'], true, true);
    $datedNextId = $datedLatest->getRecurrence()->scheduleNext($datedLatest, new DateTimeImmutable('2031-01-03 11:00:00'));
    $datedOnClose = new Event(); $datedOnClose->load($datedNextId, true);
    recurrenceExpect($datedOnClose->get('title') === 'OP 03.01.2031 11:00', 'The date chosen at closing also updates titles.');
    recurrenceExpect(in_array('PV 03/01/2031 a 11h00 - bilan 01/10/2030',
        array_map(static fn($document) => $document->get('title'), $datedOnClose->getAssociatedDocuments()), true), 'On-close documents get the chosen date and time.');

    // Repair the exact legacy default names, preserving manual renames and content.
    $legacy = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Legacy meeting', 'status' => 'confirmed',
        'timezone' => 'Europe/Zurich', 'start_at' => '2031-01-01 09:00:00', 'end_at' => '2031-01-01 10:00:00', 'active' => 1]);
    $legacyPv = new Document();
    recurrenceExpect(!empty($legacyPv->createInOrganizationContext($oid, null, $uid,
        ['title' => 'PV Legacy meeting du 01.01.2031 09:00', 'document_type' => 'pv', 'event_id' => $legacy->getId()])['status']), 'Create legacy default title.');
    EventRecurrence::configure($legacy, ['frequency' => 'weekly', 'weekday' => 3, 'horizon_months' => 1], false, true);
    $legacySeries = $legacy->getRecurrence();
    $legacyNow = new DateTimeImmutable('2031-01-01 08:00:00', new DateTimeZone('Europe/Zurich'));
    EventRecurrence::generateDueBatch(100, $legacyNow, (int)$legacySeries->getId());
    $legacyEvents = recurrenceEvents($legacySeries); $renamed = $legacyEvents[1]->getAssociatedDocument();
    // Simulate names frozen by the old generator, before any date-aware copying existed.
    foreach (array_slice($legacyEvents, 1) as $legacyCopy) {
        $legacyDocument = $legacyCopy->getAssociatedDocument();
        $legacyDocument->set('title', 'PV Legacy meeting du 01.01.2031 09:00'); $legacyDocument->save();
    }
    $renamed->set('title', 'Personalized PV du 01.01.2031 09:00'); $renamed->save();
    $preparedPoint = recurrenceFixture(\dbObject\DocumentPvPoint::class, ['IDdocument' => $legacyEvents[2]->getAssociatedDocument()->getId(),
        'item_type' => 'point', 'title' => 'Prepared decision', 'content' => '<p>Approved content</p>', 'position' => 1,
        'is_handled' => 1, 'IDuser_author' => $uid, 'active' => 1]);
    $legacySeries->load((int)$legacySeries->getId(), true);
    $legacySeries->set('parameters', ['blueprint' => $legacySeries->getParameter('blueprint')]); $legacySeries->save();
    recurrenceExpect(EventRecurrence::repairLegacyDocumentTitlesBatch(20, (int)$legacySeries->getId()) === 3, 'Repair the three untouched legacy copies.');
    $renamed->load((int)$renamed->getId(), true);
    recurrenceExpect($renamed->get('title') === 'Personalized PV du 01.01.2031 09:00', 'Legacy repair preserves a custom name containing a date.');
    $repaired = $legacyEvents[2]->getAssociatedDocument(); $repaired->load((int)$repaired->getId(), true);
    recurrenceExpect($repaired->get('title') === 'PV Legacy meeting du 15.01.2031 09:00', 'Legacy copy now shows its own event date.');
    $preparedPoint->load((int)$preparedPoint->getId(), true);
    recurrenceExpect($preparedPoint->get('content') === '<p>Approved content</p>' && $preparedPoint->get('is_handled')
        && (int)$preparedPoint->get('IDuser_author') === $uid, 'Title repair preserves existing PV content, authors and handled state.');
    $repaired->set('title', 'Manually renamed after generation'); $repaired->save();
    $legacyEvents[2]->load((int)$legacyEvents[2]->getId(), true);
    $legacyEvents[2]->set('start_at', '2031-01-16 14:00:00'); $legacyEvents[2]->set('end_at', '2031-01-16 15:00:00'); $legacyEvents[2]->save();
    $repaired->load((int)$repaired->getId(), true);
    recurrenceExpect($repaired->get('title') === 'Manually renamed after generation', 'Moving an event preserves a manually renamed automatic document.');
    recurrenceExpect(EventRecurrence::repairLegacyDocumentTitlesBatch(20, (int)$legacySeries->getId()) === 0, 'Legacy repair is idempotent.');
    EventRecurrence::generateDueBatch(100, $legacyNow->modify('+1 month'), (int)$legacySeries->getId());
    $legacyEvents = recurrenceEvents($legacySeries); $latest = end($legacyEvents);
    recurrenceExpect($latest->getAssociatedDocument()->get('title') === 'PV Legacy meeting du ' . $latest->get('start_at')->format('d.m.Y H:i'), 'Repaired legacy series generates future titles with the correct date.');

    foreach (['individual', 'following', 'stop'] as $correctionMode) {
        $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
        $tomorrow = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
        $correction = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Incorrect original end', 'status' => 'confirmed',
            'timezone' => 'Europe/Zurich', 'start_at' => $yesterday . ' 08:00:00', 'end_at' => $tomorrow . ' 10:00:00', 'active' => 1]);
        $settings = ['frequency' => 'weekly', 'weekday' => (int)$correction->get('start_at')->format('N')];
        EventRecurrence::configure($correction, $settings, false, true);
        $correction->set('end_at', $yesterday . ' 09:00:00');
        recurrenceExpect(!empty($correction->save()['status']), 'The first save permits correcting a previously editable schedule.');
        EventRecurrence::configure($correction, $settings, $correctionMode !== 'individual', $correctionMode !== 'stop');
        $correction->load((int)$correction->getId(), true);
        recurrenceExpect($correction->get('end_at')->format('Y-m-d H:i:s') === $yesterday . ' 09:00:00', 'Recurrence metadata must not reject the corrected past end: ' . $correctionMode);
        $correction->set('end_at', $tomorrow . ' 11:00:00');
        recurrenceExpect(empty($correction->save()['status']), 'A subsequent edit cannot reopen the historical meeting.');
    }

    $past = recurrenceFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'title' => 'Historical meeting', 'status' => 'confirmed',
        'timezone' => 'Europe/Zurich', 'start_at' => '2020-04-02 09:00:00', 'end_at' => '2020-04-02 10:00:00', 'active' => 1]);
    $past->set('IDeventrecurrence', (int)$manualSeries->getId()); $past->set('recurrence_position', 20);
    recurrenceExpect(empty($past->save()['status']), 'A past meeting cannot be promoted into an editable series.');
    // The in-memory guard also protects deletion before any linked documents are touched.
    recurrenceExpect(!$past->delete(), 'A past recurring meeting cannot be deleted.');
    echo "calendar_recurrence_test: OK\n";
} finally {
    $pdo->rollBack();
}
