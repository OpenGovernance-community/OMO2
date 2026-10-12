<?php
declare(strict_types=1);

// Exercise real preferences/storage while keeping every external delivery local to this test.
$deliveries = [];
function notificationCenterSendPush($userId, array $payload) { $GLOBALS['deliveries'][] = ['push', (int)$userId, $payload]; }
function notificationCenterSendTelegram($user, $title, $body, $url) { $GLOBALS['deliveries'][] = ['telegram', (int)$user->getId(), $body]; }
function notificationCenterSendEmail($user, $organization, $title, $body, $url) { $GLOBALS['deliveries'][] = ['email', (int)$user->getId(), $body]; }
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/notification_center.php';

use dbObject\{Event, EventInvitation, EventRecurrence, Notification, NotificationPreference};

$before = $_SESSION; $items = mcpFixtures(); $pdo = \dbObject\DbObject::getPdo();
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['root']->getId();
    $_SESSION = ['currentOrganization' => $oid, 'currentUser' => $uid];
    $items['root']->set('IDholon_org', $hid); $items['root']->save();
    foreach (['email', 'silent'] as $name) {
        $items[$name] = mcpFixture(\dbObject\User::class, ['firstname' => $name, 'email' => bin2hex(random_bytes(8)) . '@example.invalid', 'active' => 1]);
        $items[$name . '_organization'] = mcpFixture(\dbObject\UserOrganization::class, ['IDuser' => $items[$name]->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    foreach (['user', 'email', 'silent'] as $name) {
        $items[$name . '_holon'] = mcpFixture(\dbObject\UserHolon::class, ['IDuser' => $items[$name]->getId(), 'IDholon' => $hid, 'is_membership' => 1, 'active' => 1]);
    }
    $key = 'calendar_recurring_occurrence_created';
    mcpCheck(in_array($key, notificationCenterEventGroupCatalog()['calendar']['eventKeys'], true), 'The setting belongs to the calendar preferences.');
    mcpCheck(!NotificationPreference::getChannelsFor($uid, $oid, $key)['in_app'], 'Recurring occurrence notifications default to disabled.');
    mcpCheck(NotificationPreference::getChannelsFor($uid, $oid, 'calendar_event_invited')['in_app'], 'Ordinary invitations retain their defaults.');
    mcpCheck(NotificationPreference::saveChannels($uid, $oid, $key, ['in_app' => 1, 'push' => 1, 'telegram' => 1, 'email' => 1]), 'Save all recurrence channels.');
    $emailUser = (int)$items['email']->getId();
    mcpCheck(NotificationPreference::saveChannels($emailUser, $oid, $key, ['email' => 1]), 'Save email-only recurrence preference.');
    $date = new DateTimeImmutable('tomorrow 09:00');
    $event = mcpFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Recurring notification test', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'active' => 1,
        'start_at' => $date, 'end_at' => $date->modify('+1 hour')]);
    mcpFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDholon' => $hid, 'invitation_type' => 'holon', 'status' => 'invited', 'active' => 1]);
    $pdo->beginTransaction();
    EventRecurrence::configure($event, ['frequency' => 'weekly', 'weekday' => (int)$date->format('N'), 'horizon_months' => 1], false, true);
    $pdo->commit(); $seriesId = (int)$event->get('IDeventrecurrence');
    $pdo->beginTransaction();
    mcpCheck(EventRecurrence::generateDueBatch(2, null, $seriesId) === 2, 'Generate inside the editor transaction.');
    mcpCheck(notificationCenterProcessRecurringInvitations(20, $seriesId) === 0 && !$deliveries, 'Nothing is delivered before commit.');
    $pdo->rollBack();
    mcpCheck(EventRecurrence::getPendingInvitationEvents(20, $seriesId) === [], 'Rollback also removes pending invitations.');

    $_SESSION = []; // The same generation/dispatch steps as cron, without a logged-in actor.
    mcpCheck(EventRecurrence::generateDueBatch(2, null, $seriesId) === 2, 'Cron generates two occurrences.');
    $pending = EventRecurrence::getPendingInvitationEvents(20, $seriesId);
    mcpCheck(count($pending) === 2, 'Both committed creations have pending invitations.');
    mcpCheck(notificationCenterProcessRecurringInvitations(20, $seriesId) === 2, 'Cron processes pending invitations.');
    $ownerInbox = Notification::getInboxForUser($uid, $oid);
    $emailInbox = Notification::getInboxForUser($emailUser, $oid);
    mcpCheck(count($ownerInbox) === 2 && count($emailInbox) === 2, 'Current holon members receive one notification per occurrence.');
    mcpCheck(Notification::getInboxForUser((int)$items['silent']->getId(), $oid) === [], 'Normal invitation defaults do not enable recurring occurrence notifications.');
    mcpCheck(count($deliveries) === 8, 'Selected push, Telegram and email channels are used for each occurrence.');
    foreach ($ownerInbox as $notification) {
        mcpCheck($notification->get('event_key') === $key && str_contains($notification->get('body'), 'série récurrente'), 'Messages use the dedicated category and disclose recurrence.');
        mcpCheck(str_contains($notification->get('url'), '#calendar-e') && !$notification->get('read_at'), 'An in-app notification opens the occurrence and remains unread.');
    }
    foreach ($emailInbox as $notification) { mcpCheck((bool)$notification->get('read_at'), 'Email-only delivery does not create an unread in-app alert.'); }
    mcpCheck(notificationCenterProcessRecurringInvitations(20, $seriesId) === 0 && count($deliveries) === 8, 'Repeated cron does not resend completed invitations.');
    // Simulate an interruption after notification creation, before the pending marker was cleared.
    $retry = $pending[0]; $retry->load((int)$retry->getId(), true);
    $parameters = json_decode((string)$retry->get('parameters'), true) ?: [];
    $parameters['recurrence_invitation_pending'] = 1; $retry->set('parameters', $parameters); $retry->save();
    mcpCheck(notificationCenterProcessRecurringInvitations(20, $seriesId) === 1 && count($deliveries) === 8, 'Retry uses the existing per-user occurrence deduplication.');
    NotificationPreference::saveChannels($uid, $oid, $key, []);
    NotificationPreference::saveChannels($emailUser, $oid, $key, []);
    EventRecurrence::generateDueBatch(1, null, $seriesId);
    mcpCheck(notificationCenterProcessRecurringInvitations(20, $seriesId) === 1 && count($deliveries) === 8, 'All channels disabled means no new alerts or external deliveries.');
    echo "notification_center_recurrence_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    mcpCleanup($items); $_SESSION = $before;
}
