<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/calendar/invitations_shared.php';

use dbObject\{DbObject, Document, DocumentInvitation, Event, Organization, User};

function namedInvitationExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function namedInvitationFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    $result = $object->save();
    namedInvitationExpect(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
    return $object;
}

$input = "Alice Martin <ALICE@example.invalid>; bare@example.invalid\r\n"
    . '"Dupont, Marie; equipe" <marie@example.invalid>, '
    . '"Jean \\"JJ\\" Martin" <jean@example.invalid>' . "\n"
    . "Zo\u{00EB} D'Amour <zoe@example.invalid>\n"
    . 'bare@example.invalid; Bob Martin <bare@example.invalid>; alice@example.invalid; invalid; Name <invalid>';
$expected = [
    ['email' => 'alice@example.invalid', 'display_name' => 'Alice Martin'],
    ['email' => 'bare@example.invalid', 'display_name' => 'Bob Martin'],
    ['email' => 'marie@example.invalid', 'display_name' => 'Dupont, Marie; equipe'],
    ['email' => 'jean@example.invalid', 'display_name' => 'Jean "JJ" Martin'],
    ['email' => 'zoe@example.invalid', 'display_name' => "Zo\u{00EB} D'Amour"],
];
namedInvitationExpect(omoCalendarInvitationParseEmailRecipients($input) === $expected,
    'Pasted names, mixed separators, quoted punctuation, Unicode and duplicates must be preserved.');
namedInvitationExpect(omoCalendarInvitationParseEmails($input) === array_column($expected, 'email'),
    'The email-only helper must still return normalized unique addresses.');
namedInvitationExpect(omoCalendarInvitationParseEmailRecipients(['one@example.invalid', 'Two <two@example.invalid>']) === [
    ['email' => 'one@example.invalid', 'display_name' => ''],
    ['email' => 'two@example.invalid', 'display_name' => 'Two'],
], 'Array inputs must accept bare and named addresses.');
namedInvitationExpect(omoCalendarInvitationParseEmailRecipients('') === [], 'An empty list must remain empty.');

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = 'named-invitations-' . bin2hex(random_bytes(5));
    $organization = namedInvitationFixture(Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $organizationId = (int)$organization->getId();
    $user = namedInvitationFixture(User::class, ['email' => $nonce . '@example.invalid', 'active' => 1]);
    $userId = (int)$user->getId();
    $event = namedInvitationFixture(Event::class, [
        'IDorganization' => $organizationId, 'IDuser' => $userId, 'title' => $nonce,
        'status' => Event::STATUS_CONFIRMED, 'active' => 1,
        'start_at' => new DateTimeImmutable('2030-01-01 10:00'),
        'end_at' => new DateTimeImmutable('2030-01-01 11:00'),
    ]);
    $document = namedInvitationFixture(Document::class, [
        'IDorganization' => $organizationId, 'IDuser' => $userId, 'IDusercreation' => $userId,
        'title' => $nonce, 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_PREPARATION, 'active' => 1,
    ]);
    // Exercise the same shared saving path used by meetings and standalone PVs.
    foreach ([$event, $document] as $resource) {
        $saveSelection = static function ($emails) use ($resource, $organization, $organizationId): void {
            $arguments = [$resource, $organization, $organizationId, [], [], $emails];
            if ($resource instanceof Document) {
                $arguments[] = DocumentInvitation::class;
                $arguments[] = 'IDdocument';
            }
            namedInvitationExpect(!empty(omoCalendarApplyInvitationSelections(...$arguments)['status']),
                'Named invitations must save through the shared editor.');
        };
        $saveSelection($input . "\nplain@example.invalid");
        $invitations = [];
        foreach ($resource->getInvitations(true) as $invitation) {
            $invitations[$invitation->get('email')] = $invitation;
        }
        namedInvitationExpect(count($invitations) === 6, 'Duplicate email addresses must create only one invitation.');
        foreach ($expected as $recipient) {
            namedInvitationExpect($invitations[$recipient['email']]->get('display_name') === $recipient['display_name'],
                'The supplied name must be persisted separately from the email address.');
        }
        $attendance = $resource instanceof Event
            ? $resource->getAttendanceEntries($organizationId)
            : $resource->getInvitationAttendanceEntries($organizationId);
        $attendance = array_column($attendance, null, 'email');
        namedInvitationExpect($attendance['marie@example.invalid']['displayLabel'] === 'Dupont, Marie; equipe'
            && $attendance['plain@example.invalid']['displayLabel'] === 'plain@example.invalid',
            'Attendance must show supplied names, with email fallback for unnamed guests.');

        $initialIds = array_map(static fn($invitation) => $invitation->getId(), $invitations);
        $reopened = omoCalendarExtractInvitationSelections($resource);
        $saveSelection(implode("\n", $reopened['emails']));
        foreach ($resource->getInvitations(true) as $invitation) {
            $email = $invitation->get('email');
            namedInvitationExpect($invitation->getId() === $initialIds[$email]
                && $invitation->get('display_name') === $invitations[$email]->get('display_name'),
                'Reopening and saving must preserve names, escaping and existing invitation identities.');
        }
        $saveSelection('Alice Updated <alice@example.invalid>');
        $remaining = iterator_to_array($resource->getInvitations(true));
        namedInvitationExpect(count($remaining) === 1, 'Removed addresses must still be revoked.');
        $alice = reset($remaining);
        namedInvitationExpect($alice->getId() === $initialIds['alice@example.invalid']
            && $alice->get('display_name') === 'Alice Updated', 'A name change must update the same invitation.');
    }
    echo "calendar_named_email_invitations_test: OK (parsing, persistence, attendance, round trips, updates and removals)\n";
} finally {
    $pdo->rollBack();
}
