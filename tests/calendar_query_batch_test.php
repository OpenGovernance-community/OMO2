<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\DbObject;
use dbObject\Event;
use dbObject\EventInvitation;
use dbObject\HolonPermission;

function calendarBatchExpect(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
function calendarBatchFixture(string $class, array $values): DbObject
{
    $item = new $class();
    foreach ($values as $key => $value) { $item->set($key, $value); }
    calendarBatchExpect(!empty($item->save()['status']), 'Fixture: ' . $class);
    return $item;
}

// Count every query without writing one log line per query. All fixtures are rolled back.
$log = tempnam(sys_get_temp_dir(), 'calendar-batch-');
foreach (['DB_QUERY_LOG_ENABLED' => 'true', 'DB_QUERY_LOG_MIN_MS' => '100000', 'DB_QUERY_LOG_PATH' => $log] as $key => $value) {
    putenv($key . '=' . $value); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
$stats = new ReflectionProperty(DbObject::class, '_sqlPerformanceStats');
$countQueries = static function (callable $callback) use ($stats): array {
    $before = $stats->getValue()['queryCount'];
    $value = $callback();
    return [$value, $stats->getValue()['queryCount'] - $before];
};
$ids = static fn(array $events): array => array_map(static fn(Event $e): int => (int)$e->getId(), $events);
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
$session = $_SESSION;
try {
    $nonce = bin2hex(random_bytes(8));
    $org = calendarBatchFixture(\dbObject\Organization::class, ['name' => 'Batch fixture', 'shortname' => $nonce]);
    $oid = (int)$org->getId();
    $people = [];
    foreach (['owner', 'member', 'outsider'] as $name) {
        $people[$name] = calendarBatchFixture(\dbObject\User::class, ['email' => $name . '-' . $nonce . '@example.invalid', 'active' => 1]);
    }
    $uid = (int)$people['member']->getId();
    foreach (['owner', 'member'] as $name) {
        calendarBatchFixture(\dbObject\UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $people[$name]->getId(), 'active' => 1]);
    }
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $root = calendarBatchFixture(\dbObject\Holon::class, ['name' => 'Root', 'IDorganization' => $oid,
        'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $children = [];
    foreach (['A', 'B', 'C'] as $name) {
        $children[] = calendarBatchFixture(\dbObject\Holon::class, ['name' => $name, 'IDholon_org' => $root->getId(),
            'IDholon_parent' => $root->getId(), 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    }
    calendarBatchFixture(\dbObject\UserHolon::class, ['IDholon' => $root->getId(), 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    foreach (['CAN_CREATE_EVENT' => HolonPermission::RANGE_DESCENDANTS, 'CAN_EDIT_EVENT' => HolonPermission::RANGE_SELF] as $key => $range) {
        calendarBatchFixture(HolonPermission::class, ['IDholon' => $root->getId(), 'IDpermission' => \dbObject\Permission::findByKey($key)->getId(),
            'range' => $range, 'member_type' => 'member']);
    }
    $holonIds = array_map(static fn($h): int => (int)$h->getId(), [$root, ...$children]);
    calendarBatchExpect(HolonPermission::filterUserAllowedHolonIds($uid, $oid, 'CAN_EDIT_EVENT', $holonIds) === [(int)$root->getId()], 'Self scope only grants the root.');
    calendarBatchExpect(HolonPermission::filterUserAllowedHolonIds($uid, $oid, 'CAN_CREATE_EVENT', $holonIds) === array_slice($holonIds, 1), 'Descendants scope excludes its root.');
    foreach ([$uid, (int)$people['outsider']->getId(), 0] as $viewer) {
        foreach (['CAN_CREATE_EVENT', 'CAN_EDIT_EVENT', 'CAN_DELETE_EVENT', 'CAN_CREATE_HOLON'] as $key) {
            $expected = array_values(array_filter($holonIds, static fn(int $id): bool =>
                HolonPermission::userHasPermissionForHolonContext($viewer, $oid, $key, $id)));
            calendarBatchExpect(HolonPermission::filterUserAllowedHolonIds($viewer, $oid, $key, $holonIds) === $expected,
                'Batch preserves scoped grants, unconfigured permissions and denied viewers: ' . $key);
        }
    }
    [$scalar, $scalarQueries] = $countQueries(static fn() => array_values(array_filter($holonIds, static fn(int $id): bool =>
        HolonPermission::userHasPermissionForHolonContext($uid, $oid, 'CAN_CREATE_EVENT', $id))));
    [$batch, $batchQueries] = $countQueries(static fn() => HolonPermission::filterUserAllowedHolonIds($uid, $oid, 'CAN_CREATE_EVENT', $holonIds));
    calendarBatchExpect($scalar === $batch && $batchQueries < $scalarQueries / 2, 'Permission reads are shared across the picker.');

    $events = [];
    $direct = [];
    foreach (range(1, 12) as $i) {
        $event = calendarBatchFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $people['owner']->getId(),
            'title' => 'Fixture ' . $i, 'status' => 'confirmed', 'active' => 1,
            'start_at' => new DateTimeImmutable('2031-03-03 10:00'), 'end_at' => new DateTimeImmutable('2031-03-03 11:00')]);
        $events[] = $event;
        if ($i <= 8) {
            $direct[] = $event;
            calendarBatchFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'invitation_type' => 'user', 'IDuser' => $uid, 'active' => 1]);
        }
    }
    // An event with no invitation must remain absent even for its creator.
    calendarBatchFixture(EventInvitation::class, ['IDevent' => $events[9]->getId(), 'invitation_type' => 'user', 'IDuser' => $uid, 'status' => 'revoked', 'active' => 1]);
    calendarBatchFixture(EventInvitation::class, ['IDevent' => $events[10]->getId(), 'invitation_type' => 'email',
        'email' => $people['member']->get('email'), 'active' => 1]);
    calendarBatchFixture(\dbObject\EventPublicRegistration::class, ['IDevent' => $events[11]->getId(), 'name' => 'Member',
        'email' => $people['member']->get('email'), 'token' => bin2hex(random_bytes(32)), 'confirmed_at' => new DateTimeImmutable()]);
    foreach ([$uid, (int)$people['owner']->getId(), (int)$people['outsider']->getId()] as $viewer) {
        $expected = array_values(array_filter($events, static fn(Event $e): bool => $e->isInvitedToEvent($viewer)));
        calendarBatchExpect($ids(Event::filterInvitedForUser($events, $viewer)) === $ids($expected), 'Direct, revoked, email, registration and creator semantics.');
    }
    calendarBatchExpect(count(Event::filterInvitedForUser($events, $uid)) === 10, 'Only eight direct, one email and one confirmed registration.');
    [, $oneQueries] = $countQueries(static fn() => Event::filterInvitedForUser([$direct[0]], $uid));
    [, $manyQueries] = $countQueries(static fn() => Event::filterInvitedForUser($direct, $uid));
    calendarBatchExpect($oneQueries === 2 && $manyQueries === 2, 'Exactly two relation queries regardless of event count.');
    // Exercise chunk boundaries without creating hundreds of business records.
    $repeated = array_fill(0, 501, $direct[0]);
    [$chunked, $chunkQueries] = $countQueries(static fn() => Event::filterInvitedForUser($repeated, $uid));
    calendarBatchExpect(count($chunked) === 501 && $chunkQueries === 4, 'Chunked reads preserve order and do not drop the last event.');
    $direct[0]->set('status', 'draft');
    calendarBatchExpect(Event::filterInvitedForUser([$direct[0]], $uid) === [], 'An invitation does not expose another author draft.');
    $direct[0]->set('status', 'cancelled');
    calendarBatchExpect(Event::filterInvitedForUser([$direct[0]], $uid) === [], 'Cancelled events are excluded.');
    $direct[0]->set('status', 'confirmed');
    $direct[0]->set('parameters', json_encode(['meeting_participants' => ['targets' => ['hasExplicitInvitations' => true,
        'userIds' => [], 'emails' => [], 'registeredEmails' => []]]]));
    calendarBatchExpect(Event::filterInvitedForUser([$direct[0]], $uid) === [], 'Frozen participants override current invitations.');
    $events[8]->set('parameters', json_encode(['meeting_participants' => ['targets' => ['hasExplicitInvitations' => true,
        'userIds' => [$uid], 'emails' => [], 'registeredEmails' => []]]]));
    calendarBatchExpect(count(Event::filterInvitedForUser([$events[8]], $uid)) === 1, 'A frozen participant remains invited without a current invitation row.');
    // A second batch must observe writes, including revocations, in the same request.
    $invite = new EventInvitation();
    calendarBatchExpect($invite->load([['resource_type', 'event'], ['resource_id', $direct[1]->getId()], ['IDuser', $uid]]), 'Find direct invitation.');
    $invite->set('active', 0); $invite->save();
    calendarBatchExpect(Event::filterInvitedForUser([$direct[1]], $uid) === [], 'No relation cache survives writes.');
    echo "calendar_query_batch_test: OK; permission queries {$scalarQueries} -> {$batchQueries}; invitation queries {$oneQueries}/{$manyQueries}/{$chunkQueries}\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION = $session;
    DbObject::writeSqlPerformanceRequestSummary();
    if (is_file($log)) { unlink($log); }
}
