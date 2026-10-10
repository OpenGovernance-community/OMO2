<?php
// Isolated dispatch tests: no database writes or external notification delivery.
namespace dbObject {
    class NotificationActorFixture
    {
        public static array $rows = [];
        public array $values = [];
        public function __construct(array $values = []) { $this->values = $values; }
        public function get($key) { return $this->values[$key] ?? null; }
        public function getId() { return $this->get('id'); }
        public function load($id)
        {
            $row = self::$rows[static::class][(int)$id] ?? null;
            if ($row === null) { return false; }
            $this->values = $row;
            return true;
        }
    }
    class User extends NotificationActorFixture
    {
        public function getScopedDisplayName($organizationId) { return $this->get('names')[$organizationId] ?? ''; }
    }
    class Event extends NotificationActorFixture
    {
        public function getNotificationRecipientUserIds() { return [1, 2, 3]; }
        public function getCreatedByDisplayName() { return 'Alice'; }
        public function getResolvedLocationAddress() { return $this->get('locationaddress'); }
        public function getResolvedVideoMeetingUrl() { return $this->get('videomeetingurl'); }
    }
    class DecisionProcess extends NotificationActorFixture {}
    class DecisionParticipant extends NotificationActorFixture
    {
        public static function getActiveUserIdsForDecision($id) { return [1, 2, 3]; }
        public function getIdentityLabel($organizationId) { return $this->get('display_name'); }
    }
    class DecisionProposal extends NotificationActorFixture
    {
        public function getDecisionProcess() { return new DecisionProcess(['id' => 20, 'IDorganization' => 12, 'IDuser' => 1, 'title' => 'Budget']); }
        public function getAuthorUserId() { return (int)$this->get('IDuser_author'); }
        public function getAuthorParticipant() { return $this->get('participant'); }
        public function isAnonymous() { return (bool)$this->get('anonymous'); }
    }
    class ChatThread extends NotificationActorFixture
    {
        public const SUBJECT_DECISION_PROPOSAL = 'decision_proposal';
        public const SUBJECT_PROJECT = 'project';
    }
    class ChatMessage extends NotificationActorFixture
    {
        public function isAnonymous() { return (bool)$this->get('anonymous'); }
        public static function getParticipantUserIdsForThread($id) { return [1, 2, 3]; }
    }
    class Project extends NotificationActorFixture
    {
        public static function normalizeStatus($status) { return $status; }
        public static function getOrganizationStatusLabel($organizationId, $status) { return $status; }
    }
    class ProjectFollower
    {
        public static function getActiveUserIdsForProject($id) { return [1, 2, 3]; }
    }
}

namespace {
    $dispatches = [];
    $bundleLoads = 0;
    function notificationCenterCreateForUsers($organizationId, $eventKey, array $userIds, $sourceKey, $title, $body, $url, $dedupeKey = '', $excludedUserId = 0)
    {
        $GLOBALS['dispatches'][] = compact('eventKey', 'userIds', 'sourceKey', 'title', 'body', 'url', 'dedupeKey', 'excludedUserId');
    }
    function omoLoadTranslationBundle($key, array $sourceLang): array
    {
        $GLOBALS['bundleLoads']++;
        return $sourceLang;
    }
    require_once dirname(__DIR__) . '/common/translation_bundles.php';
    require_once dirname(__DIR__) . '/common/notification_center.php';

    function assertNotificationActor($condition, $message): void
    {
        if (!$condition) { throw new \RuntimeException($message); }
    }
    function lastNotificationActor(): array
    {
        return $GLOBALS['dispatches'][array_key_last($GLOBALS['dispatches'])];
    }

    \dbObject\NotificationActorFixture::$rows = [
        \dbObject\User::class => [
            1 => ['id' => 1, 'names' => [12 => 'Alice']],
            2 => ['id' => 2, 'names' => [12 => 'Bob', 13 => 'Other organization']],
            3 => ['id' => 3, 'names' => [12 => '']],
        ],
        \dbObject\ChatThread::class => [
            40 => ['id' => 40, 'IDorganization' => 12, 'subject_type' => 'decision_proposal', 'subject_id' => 30],
            41 => ['id' => 41, 'IDorganization' => 12, 'subject_type' => 'project', 'subject_id' => 50],
        ],
        \dbObject\DecisionProposal::class => [30 => ['id' => 30, 'title' => 'Materiel', 'IDuser_author' => 1]],
        \dbObject\Project::class => [50 => ['id' => 50, 'IDorganization' => 12, 'title' => 'Site', 'IDuser' => 1]],
    ];
    $event = new \dbObject\Event([
        'id' => 10, 'IDorganization' => 12, 'IDuser' => 1, 'title' => 'Reunion',
        'start_at' => new \DateTimeImmutable('2026-10-10 09:00'),
        'end_at' => new \DateTimeImmutable('2026-10-10 11:00'),
        'updated_at' => new \DateTimeImmutable('2026-10-08 15:00'),
        'locationaddress' => 'Salle A', 'videomeetingurl' => 'https://example.org/meeting',
    ]);
    notificationCenterDispatchEventInvitation($event, 2);
    $notification = lastNotificationActor();
    assertNotificationActor(str_contains($notification['body'], 'Bob vous invite'), 'The inviter must use the scoped actor name.');
    assertNotificationActor(str_contains($notification['body'], 'organisé par Alice'), 'A different organizer must also be named.');
    assertNotificationActor($notification['excludedUserId'] === 2 && $notification['url'] === '/omo/o/12#calendar-e10', 'Actor exclusion and event links must remain intact.');
    notificationCenterDispatchEventInvitation($event);
    assertNotificationActor(str_contains(lastNotificationActor()['body'], 'Alice vous invite'), 'Without an explicit inviter, use the event creator.');
    assertNotificationActor(!str_contains(lastNotificationActor()['body'], 'organisé par'), 'Do not repeat the organizer when they sent the invitation.');

    $event->values['IDeventrecurrence'] = 7;
    $event->values['recurrence_position'] = 0;
    notificationCenterDispatchEventInvitation($event, 2);
    assertNotificationActor(lastNotificationActor()['eventKey'] === 'calendar_event_invited' && str_contains(lastNotificationActor()['body'], 'série récurrente'), 'First invitation identifies the recurring series while keeping the normal invitation preference.');
    $event->values['recurrence_position'] = 1;
    notificationCenterDispatchEventInvitation($event);
    assertNotificationActor(lastNotificationActor()['eventKey'] === 'calendar_recurring_occurrence_created' && lastNotificationActor()['excludedUserId'] === 0, 'Automatic occurrences use their own preference and can notify their invited organizer.');
    notificationCenterDispatchEventInvitation($event, 1);
    assertNotificationActor(lastNotificationActor()['excludedUserId'] === 0, 'A recurring occurrence also notifies its invited creator when an explicit actor is supplied.');
    unset($event->values['IDeventrecurrence'], $event->values['recurrence_position']);

    notificationCenterDispatchEventChange($event, 'schedule', 2);
    $body = lastNotificationActor()['body'];
    assertNotificationActor(str_contains($body, 'Bob a modifié l’horaire'), 'The editor, rather than the creator, must be named.');
    assertNotificationActor(str_contains($body, '10.10.2026 09:00') && str_contains($body, '10.10.2026 11:00'), 'Show both new dates, including changes to the end alone.');
    notificationCenterDispatchEventChange($event, 'location', 2);
    $body = lastNotificationActor()['body'];
    assertNotificationActor(str_contains($body, 'Bob a modifié le lieu') && str_contains($body, 'Salle A') && str_contains($body, 'https://example.org/meeting'), 'A location change must include the editor and new location details.');
    notificationCenterDispatchEventChange($event, 'schedule', 999);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Un membre a modifié'), 'Never attribute an unknown editor to the event creator.');
    assertNotificationActor(notificationCenterActorName(3, 12) === 'Un membre', 'Empty display names need a readable fallback.');

    $proposal = new \dbObject\DecisionProposal(['id' => 30, 'title' => 'Materiel', 'IDuser_author' => 2]);
    notificationCenterDispatchDecisionProposal($proposal);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Bob a ajoute la proposition "Materiel"'), 'A new proposal must name its author.');
    assertNotificationActor(lastNotificationActor()['dedupeKey'] === 'PROPOSAL_12_30' && lastNotificationActor()['excludedUserId'] === 2, 'Proposal deduplication and actor exclusion must be preserved.');
    $proposal->values['participant'] = new \dbObject\DecisionParticipant(['display_name' => 'External guest']);
    $proposal->values['IDuser_author'] = 0;
    notificationCenterDispatchDecisionProposal($proposal);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'External guest a ajoute'), 'External participants must retain their visible identity.');
    $proposal->values['anonymous'] = true;
    notificationCenterDispatchDecisionProposal($proposal);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Un participant a ajoute') && !str_contains(lastNotificationActor()['body'], 'External guest'), 'Anonymous proposals must never reveal their participant identity.');
    $proposal->values['title'] = '';
    notificationCenterDispatchDecisionProposal($proposal);
    assertNotificationActor(str_contains(lastNotificationActor()['body'], 'une nouvelle proposition'), 'Untitled proposals need a readable anonymous notification.');

    $message = new \dbObject\ChatMessage(['id' => 60, 'IDchat_thread' => 40, 'IDuser' => 2, 'author_name' => '']);
    notificationCenterDispatchDecisionChatMessage($message);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Bob a commente'), 'Comments with missing snapshots must resolve the author account.');
    $message->values['anonymous'] = true;
    $message->values['author_name'] = 'Secret name';
    notificationCenterDispatchDecisionChatMessage($message);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Un participant a commente') && !str_contains(lastNotificationActor()['body'], 'Secret name'), 'Anonymous comments must hide even a populated author snapshot.');
    $message->values['IDchat_thread'] = 41;
    notificationCenterDispatchProjectChatMessage($message);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Un participant a ecrit'), 'Project messages must respect the anonymous flag too.');
    $message->values['anonymous'] = false;
    $message->values['author_name'] = '';
    notificationCenterDispatchProjectChatMessage($message);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Bob a ecrit'), 'Project messages with a missing snapshot must resolve their author.');
    $message->values['author_name'] = 'Name at publication';
    notificationCenterDispatchProjectChatMessage($message);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Name at publication a ecrit'), 'A stored author snapshot must remain preferred.');

    $project = new \dbObject\Project(['id' => 50, 'IDorganization' => 12, 'title' => 'Site', 'status' => 'active']);
    notificationCenterDispatchProjectStatusChange($project, 'blocked', 2);
    assertNotificationActor(str_starts_with(lastNotificationActor()['body'], 'Bob a change le statut') && str_contains(lastNotificationActor()['body'], '"blocked" a "active"'), 'A manual status change must show the editor and transition.');
    notificationCenterDispatchProjectStatusChange($project, 'blocked');
    assertNotificationActor(str_contains(lastNotificationActor()['body'], 'automatiquement') && !str_contains(lastNotificationActor()['body'], 'Alice'), 'Automatic reactivation must not be attributed to the project owner.');
    assertNotificationActor($bundleLoads === 1, 'The shared notification translation bundle must load only once.');

    echo "Notification center actor tests passed.\n";
}
