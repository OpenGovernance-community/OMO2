<?php
declare(strict_types=1);

// Run against a local database with the registration migration applied. All fixtures are rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$organizationId = (int)($argv[1] ?? 0);
$userId = (int)($argv[2] ?? 0);
if ($organizationId <= 0 || $userId <= 0) {
    fwrite(STDERR, "Usage: php tests/event_public_participants_integration_test.php ORGANIZATION_ID USER_ID\n");
    exit(2);
}
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\ArrayEventInvitation;
use dbObject\DbObject;
use dbObject\Event;
use dbObject\EventInvitation;
use dbObject\EventPublicRegistration;

class PublicParticipantsFixture extends Event
{
    public array $invitations = [];
    public bool $hasStructure = true;
    public function getInvitations($activeOnly = false)
    {
        $items = new ArrayEventInvitation();
        $items->exchangeArray($this->invitations);
        return $items;
    }
    protected function getInvitationMembershipUserIds($holonId, $organizationId, bool $fresh = false, bool $activeOnly = false): array { return [101, 102]; }
    protected function getOrganizationMemberUserIds($organizationId): array { return [101, 102]; }
    protected function organizationHasStructureApplication($organizationId): bool { return $this->hasStructure; }
    protected function getViewerScopedEmail($userId, $organizationId): string { return 'member' . $userId . '@example.test'; }
    protected function getViewerDisplayName($userId, $organizationId): string { return 'Member ' . $userId; }
}
function expectPublicParticipants(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $event = new PublicParticipantsFixture();
    foreach (['IDorganization' => $organizationId, 'IDuser' => $userId, 'title' => 'Public participants test',
        'status' => Event::STATUS_CONFIRMED, 'timezone' => 'Europe/Zurich', 'active' => 1,
        'start_at' => new DateTimeImmutable('2030-01-01 10:00'), 'end_at' => new DateTimeImmutable('2030-01-01 11:00')] as $key => $value) {
        $event->set($key, $value);
    }
    expectPublicParticipants(!empty($event->save()['status']), 'Event fixture could not be saved.');
    foreach ([['user', 'IDuser', 101], ['holon', 'IDholon', 10], ['email', 'email', 'shared@example.test']] as [$type, $key, $value]) {
        $invitation = new EventInvitation();
        $invitation->set('invitation_type', $type); $invitation->set($key, $value);
        $invitation->set('active', 1); $invitation->set('status', 'invited');
        $event->invitations[] = $invitation;
    }
    $pending = null;
    foreach (['member101@example.test' => 'Public member name', 'shared@example.test' => 'Shared Guest',
        'public@example.test' => 'Public Guest', 'pending@example.test' => 'Pending Guest'] as $email => $name) {
        $registration = new EventPublicRegistration();
        foreach (['IDevent' => $event->getId(), 'name' => $name, 'email' => $email,
            'token' => bin2hex(random_bytes(32)), 'created_at' => new DateTimeImmutable()] as $key => $value) { $registration->set($key, $value); }
        if ($email !== 'pending@example.test') { $registration->set('confirmed_at', new DateTimeImmutable()); }
        else { $pending = $registration; }
        expectPublicParticipants(!empty($registration->save()['status']), 'Registration fixture could not be saved.');
    }
    $recipients = array_column($event->getInvitationEmailRecipients($organizationId), null, 'email');
    expectPublicParticipants(count($recipients) === 4 && !isset($recipients['pending@example.test']), 'Invitations must include confirmed registrants once and exclude pending ones.');
    expectPublicParticipants($recipients['member101@example.test']['user_id'] === 101 && $recipients['public@example.test']['display_name'] === 'Public Guest', 'Existing member identities and public guest names must be preserved.');
    $attendance = array_column($event->getAttendanceEntries($organizationId), null, 'identityKey');
    expectPublicParticipants(count($attendance) === 4 && !isset($attendance['email:member101@example.test']), 'Attendance must deduplicate registrations against invited members and external emails.');
    expectPublicParticipants($attendance['email:shared@example.test']['displayLabel'] === 'Shared Guest'
        && $attendance['email:public@example.test']['displayLabel'] === 'Public Guest', 'Attendance must use confirmed registrant names.');
    expectPublicParticipants(!$attendance['email:public@example.test']['isPresent'], 'An email confirmation must not mark physical attendance automatically.');
    foreach ([true, false] as $present) {
        expectPublicParticipants(!empty($event->setAttendancePresence($organizationId, $userId, 'email:public@example.test', $present)['status']), 'Public guest attendance must be editable.');
        $attendance = array_column($event->getAttendanceEntries($organizationId), null, 'identityKey');
        expectPublicParticipants($attendance['email:public@example.test']['isPresent'] === $present, 'Public guest attendance must persist.');
    }
    $counts = $event->getInvitationCounts();
    expectPublicParticipants($counts['total'] === 4 && $counts['invitedEmails'] === 1 && $counts['confirmedRegistrations'] === 3, 'Invitation summary must keep manual and public email counts distinct.');
    $event->invitations = [];
    expectPublicParticipants(count($event->getInvitationEmailRecipients($organizationId)) === 3, 'Public-only meetings must have recipients.');
    expectPublicParticipants(count($event->getAttendanceEntries($organizationId)) === 3, 'Public-only meetings must have attendance entries.');
    $event->set('IDholon', 10);
    expectPublicParticipants(count($event->getInvitationEmailRecipients($organizationId)) === 4, 'Registrations must supplement the default event space.');
    $event->set('IDholon', 0); $event->hasStructure = false;
    expectPublicParticipants(count($event->getAttendanceEntries($organizationId)) === 4, 'Registrations must supplement the default organization membership.');
    $pending->set('confirmed_at', new DateTimeImmutable());
    expectPublicParticipants(!empty($pending->save()['status']), 'Pending registration confirmation failed.');
    expectPublicParticipants(count($event->getInvitationEmailRecipients($organizationId)) === 5
        && count($event->getAttendanceEntries($organizationId)) === 5, 'A newly confirmed registration must appear immediately.');
    $otherEvent = new PublicParticipantsFixture();
    $otherEvent->set('IDorganization', $organizationId);
    expectPublicParticipants($otherEvent->getInvitationEmailRecipients($organizationId) === [], 'Registrations must remain scoped to their event.');
    echo "Public registrants: recipients, attendance, deduplication, pending status and defaults OK\n";
} finally {
    $pdo->rollBack();
}
