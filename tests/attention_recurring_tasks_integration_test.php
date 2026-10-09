<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayOrganization, ArrayUser, ControlActivity, ControlTaskCheck, Holon};

function recurringAttentionCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function recurringAttentionSave(DbObject $object): void
{
    $reply = $object->save();
    recurringAttentionCheck(!empty($reply['status']), (string)($reply['text'] ?? 'Save failed'));
}
$organizations = new ArrayOrganization();
$organizations->load(['limit' => 2]);
$users = new ArrayUser();
$users->load(['limit' => 2]);
$organizationId = (int)$organizations[0]->getId();
$userId = (int)$users[0]->getId();
$_SESSION['currentUser'] = $userId;
$now = new DateTimeImmutable('2026-10-09 12:00:00');
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $fixtureIds = [];
    $make = static function (array $overrides = []) use ($organizationId, $userId, &$fixtureIds): ControlActivity {
        $activity = new ControlActivity();
        foreach (array_merge([
            'IDorganization' => $organizationId, 'IDuser_responsible' => $userId,
            'title' => 'Recurring attention fixture', 'frequency' => 'monthly', 'schedule' => '1',
            'created_at' => new DateTimeImmutable('2026-10-01 00:00:00'),
            'execution_duration_value' => 5, 'execution_duration_unit' => 'day', 'active' => 1,
        ], $overrides) as $field => $value) $activity->set($field, $value);
        recurringAttentionSave($activity);
        $fixtureIds[] = (int)$activity->getId();
        return $activity;
    };
    $pending = static function () use ($organizationId, $userId, $now, &$fixtureIds): array {
        return array_values(array_filter(ControlActivity::getOverdueForUser($organizationId, $userId, $now),
            static fn(array $row): bool => in_array((int)$row['activity']->getId(), $fixtureIds, true)));
    };
    $overdue = $make();
    recurringAttentionCheck($overdue->getOccurrenceState($now)['state'] === 'due', 'The fixture must be within its current attribution window.');
    recurringAttentionCheck($overdue->getOverdueAt($now) == new DateTimeImmutable('2026-10-06 00:00:00'), 'An outstanding task past its deadline must require attention before the next occurrence.');
    $missed = $make(['created_at' => new DateTimeImmutable('2026-08-01 00:00:00')]);
    recurringAttentionCheck($missed->getOccurrenceState($now)['state'] === 'missed', 'The fixture must also cover a missed prior occurrence.');
    recurringAttentionCheck($pending()[0]['activity']->getId() === $missed->getId(), 'The oldest overdue deadline must come first.');
    $make(['IDuser_responsible' => null]);
    $make(['IDuser_responsible' => $users[1]->getId()]);
    $make(['IDorganization' => $organizations[1]->getId()]);
    $make(['active' => 0]);
    $make(['archived_at' => new DateTimeImmutable('2026-10-08')]);
    recurringAttentionCheck(count($pending()) === 2, 'Only active, non-archived tasks explicitly assigned to this user in this organization may be signalled.');
    $future = $make(['execution_duration_value' => 20]);
    recurringAttentionCheck($future->getOverdueAt($now) === null, 'A future deadline must not require attention.');
    $longGrace = $make(['created_at' => new DateTimeImmutable('2026-08-01'), 'execution_duration_value' => 100]);
    recurringAttentionCheck($longGrace->getOverdueAt($now) === null, 'A missed attribution window must still respect a longer execution deadline.');
    $upcoming = $make(['schedule' => '20']);
    recurringAttentionCheck($upcoming->getOverdueAt($now) === null, 'A task created after the previous occurrence must not signal a missed period before its creation.');
    $_GET['cid'] = 999999;
    recurringAttentionCheck(count($pending()) === 2, 'The current navigation context must not filter assigned overdue tasks.');
    $check = new ControlTaskCheck();
    foreach (['IDcontroltask' => $overdue->getId(), 'IDuser' => $userId,
        'scheduled_for' => new DateTimeImmutable('2026-10-01'), 'checked_at' => $now] as $field => $value) $check->set($field, $value);
    recurringAttentionSave($check);
    recurringAttentionCheck($overdue->getOccurrenceState($now)['state'] === 'late' && count($pending()) === 1, 'A task completed late must clear its reminder.');
    $missed->set('IDuser_responsible', $users[1]->getId());
    recurringAttentionSave($missed);
    recurringAttentionCheck($pending() === [], 'Reassignment must clear the former responsible user reminder.');
    recurringAttentionCheck(ControlActivity::getOverdueForUser($organizationId, 0, $now) === [], 'Anonymous users must not receive reminders.');
    echo "attention_recurring_tasks_integration_test: OK\n";
} finally {
    $pdo->rollBack();
}
