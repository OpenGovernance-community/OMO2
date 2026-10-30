<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Event, EventInvitation, EventRecurrence, EventTimeBuffer, Organization, User, UserOrganization};

function calendarBatchExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function calendarBatchFixture(string $class, array $fields): DbObject
{
    $item = new $class();
    foreach ($fields as $field => $value) $item->set($field, $value);
    calendarBatchExpect(!empty($item->save()['status']), 'Fixture save failed: ' . $class);
    return $item;
}
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
$session = $_SESSION;
try {
    $nonce = bin2hex(random_bytes(6));
    $organization = calendarBatchFixture(Organization::class, ['name' => 'Calendar batch', 'shortname' => $nonce]);
    $owner = calendarBatchFixture(User::class, ['email' => 'owner-' . $nonce . '@example.invalid', 'active' => 1]);
    $guest = calendarBatchFixture(User::class, ['email' => 'guest-' . $nonce . '@example.invalid', 'active' => 1]);
    foreach ([$owner, $guest] as $user) calendarBatchFixture(UserOrganization::class,
        ['IDorganization' => $organization->getId(), 'IDuser' => $user->getId(), 'active' => 1]);
    $_SESSION = ['currentUser' => $owner->getId(), 'currentOrganization' => $organization->getId()];
    $series = calendarBatchFixture(EventRecurrence::class, ['IDorganization' => $organization->getId(),
        'frequency' => 'weekly', 'anchor_date' => '2030-01-01', 'timezone' => 'Europe/Zurich', 'active' => 1]);
    $events = [];
    $future = new DateTimeImmutable('+10 days');
    foreach ([[0, 1, 'confirmed', false], [1, 1, 'cancelled', false], [2, 0, 'confirmed', false],
        [3, 1, 'confirmed', true], [4, 1, 'confirmed', false], [5, 0, 'confirmed', false],
        [6, 1, 'confirmed', true]] as [$position, $active, $status, $past]) {
        $start = $past ? new DateTimeImmutable('-10 days') : $future->modify('+' . $position . ' days');
        $events[] = calendarBatchFixture(Event::class, ['title' => 'Occurrence ' . $position,
            'IDorganization' => $organization->getId(), 'IDuser' => $owner->getId(),
            'IDeventrecurrence' => $series->getId(), 'recurrence_position' => $position,
            'start_at' => $start, 'end_at' => $start->modify('+1 hour'), 'status' => $status, 'active' => $active]);
    }
    $standalone = calendarBatchFixture(Event::class, ['title' => 'Standalone', 'IDorganization' => $organization->getId(),
        'IDuser' => $owner->getId(), 'start_at' => $future, 'end_at' => $future->modify('+1 hour'), 'active' => 1]);
    $events[] = $standalone;
    calendarBatchFixture(EventInvitation::class, ['IDevent' => $events[0]->getId(), 'invitation_type' => EventInvitation::TYPE_USER,
        'IDuser' => $guest->getId(), 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $buffer = calendarBatchFixture(EventTimeBuffer::class, ['IDevent' => $events[0]->getId(), 'IDuser' => $owner->getId(),
        'preparation_minutes' => 15, 'closing_minutes' => 25]);
    calendarBatchFixture(EventTimeBuffer::class, ['IDevent' => $events[0]->getId(), 'IDuser' => $guest->getId(),
        'preparation_minutes' => 45, 'closing_minutes' => 5]);

    $before = DbObject::getPerformanceCounters()['queries'];
    $following = EventRecurrence::followingByEvent($events);
    calendarBatchExpect(DbObject::getPerformanceCounters()['queries'] - $before === 1, 'Following occurrences use one grouped lookup.');
    foreach ($events as $event) {
        $expected = (int)$event->get('IDeventrecurrence') > 0 && $series->hasFollowing($event);
        calendarBatchExpect($following[(int)$event->getId()] === $expected, 'Batch recurrence semantics match individual lookups.');
    }
    calendarBatchExpect($following[(int)$events[0]->getId()] && !$following[(int)$events[4]->getId()], 'First and last occurrences.');
    calendarBatchExpect(EventRecurrence::followingByEvent([]) === [], 'Empty calendar needs no series lookup.');

    foreach ([$owner, $guest] as $user) {
        $expected = [];
        foreach ($events as $event) if ($event->isPersonallyRelevantToViewer((int)$user->getId(), (int)$organization->getId())) $expected[] = (int)$event->getId();
        $actual = array_map(static fn (Event $event) => (int)$event->getId(), Event::filterInvitedForUser($events, (int)$user->getId()));
        calendarBatchExpect($actual === $expected, 'Batch relevance preserves owner, guest, cancellation and inactive-event checks.');
    }

    DbObject::enableReadOnlyMemoization();
    $before = DbObject::getPerformanceCounters()['queries'];
    EventTimeBuffer::preloadForUser(array_map(static fn (Event $event) => (int)$event->getId(), $events), (int)$owner->getId());
    calendarBatchExpect(DbObject::getPerformanceCounters()['queries'] - $before === 1, 'Personal buffers use one batch read.');
    $before = DbObject::getPerformanceCounters()['queries'];
    foreach ($events as $index => $event) {
        calendarBatchExpect($event->getTimeBuffers((int)$owner->getId()) === ($index === 0 ? [15, 25] : [0, 0]), 'Missing buffers remain zero.');
        $event->getBusyInterval((int)$owner->getId());
    }
    calendarBatchExpect(DbObject::getPerformanceCounters()['queries'] === $before, 'Repeated reads reuse existing and missing buffer rows.');
    $copy = EventTimeBuffer::forUser((int)$events[0]->getId(), (int)$owner->getId());
    $copy->set('preparation_minutes', 99);
    calendarBatchExpect($events[0]->getTimeBuffers((int)$owner->getId()) === [15, 25], 'Unsaved edits do not mutate cached rows.');
    calendarBatchExpect($events[0]->getTimeBuffers((int)$guest->getId()) === [45, 5], 'Personal caches are isolated by user.');
    $buffer->set('preparation_minutes', 30);
    calendarBatchExpect(!empty($buffer->save()['status']), 'Buffer update succeeds.');
    calendarBatchExpect(!DbObject::isReadOnlyMemoizationEnabled(), 'A write disables the read snapshot.');
    calendarBatchExpect($events[0]->getTimeBuffers((int)$owner->getId()) === [30, 25], 'A subsequent read sees the saved buffer.');
    $events[0]->setTimeBuffers((int)$owner->getId(), 5, 10);
    calendarBatchExpect($events[0]->getTimeBuffers((int)$owner->getId()) === [5, 10], 'Proposed personal times keep precedence.');
    echo "calendar_read_batch_test: OK (recurrences, invitation relevance, personal buffer batching, isolation and write invalidation)\n";
} finally {
    DbObject::enableReadOnlyMemoization(false);
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION = $session;
}
