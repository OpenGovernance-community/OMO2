<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\DbObject;
use dbObject\Document;
use dbObject\DocumentShareLink;
use dbObject\Event;
use dbObject\EventPublicLink;
use dbObject\EventPublicRegistration;

$organizationId = (int)($argv[1] ?? 0);
$userId = (int)($argv[2] ?? 0);
if ($organizationId <= 0 || $userId <= 0) {
    fwrite(STDERR, "Usage: php tests/event_public_documents_test.php ORGANIZATION_ID USER_ID\n");
    exit(2);
}
function publicDocumentExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function savePublicDocumentFixture($object, array $values): void
{
    foreach ($values as $field => $value) { $object->set($field, $value); }
    $saved = $object->save();
    publicDocumentExpect(!empty($saved['status']), 'Fixture save failed: ' . get_class($object) . ' ' . json_encode(DbObject::getLastDbError()));
}
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $event = new Event();
    savePublicDocumentFixture($event, ['IDorganization' => $organizationId, 'IDuser' => $userId,
        'title' => 'Public document access test', 'active' => 1, 'status' => Event::STATUS_CONFIRMED,
        'start_at' => new DateTimeImmutable('2030-01-01 10:00'), 'end_at' => new DateTimeImmutable('2030-01-01 11:00')]);
    publicDocumentExpect(EventPublicLink::setForEvent((int)$event->getId(), true), 'Public link creation failed');
    $registration = new EventPublicRegistration();
    savePublicDocumentFixture($registration, ['IDevent' => $event->getId(), 'name' => 'Synthetic guest',
        'email' => 'guest-documents@example.invalid', 'token' => bin2hex(random_bytes(32))]);
    $document = new Document();
    savePublicDocumentFixture($document, ['title' => 'Public PV test', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_PREPARATION, 'IDorganization' => $organizationId,
        'IDevent' => $event->getId(), 'IDuser' => $userId, 'IDusercreation' => $userId, 'active' => 1]);
    $documentId = (int)$document->getId();
    publicDocumentExpect($registration->getAccessibleDocument($documentId) === null, 'Pending registration must not open documents');
    publicDocumentExpect($registration->getAccessibleDocuments() === [], 'Pending registration must not list documents');
    savePublicDocumentFixture($registration, ['confirmed_at' => new DateTimeImmutable()]);
    publicDocumentExpect($registration->getParticipantDisplayName() === 'Synthetic guest (guest-documents@example.invalid)', 'External identity must include confirmed email');
    publicDocumentExpect($document->getExternalParticipantDisplayName('guest-documents@example.invalid') === $registration->getParticipantDisplayName(), 'PV must use the same external identity');
    $point = new \dbObject\DocumentPvPoint();
    $point->set('IDdocument', $documentId);
    $point->set('author_email', 'guest-documents@example.invalid');
    publicDocumentExpect($point->getAuthorDisplayName($organizationId) === $registration->getParticipantDisplayName(), 'External PV author must include email');
    publicDocumentExpect($registration->getAccessibleDocument($documentId) instanceof Document, 'Confirmed guest must access linked document');
    publicDocumentExpect(count($registration->getAccessibleDocuments()) === 1, 'Confirmed guest must see linked document');
    $share = DocumentShareLink::getOrCreatePvParticipantLink($document, $userId, (string)$registration->get('email'));
    publicDocumentExpect($share instanceof DocumentShareLink && $share->allowsPvContribution()
        && $share->getRecipientEmail() === 'guest-documents@example.invalid', 'PV access must belong to the registrant');
    $sameShare = DocumentShareLink::getOrCreatePvParticipantLink($document, $userId, (string)$registration->get('email'));
    publicDocumentExpect($sameShare->getId() === $share->getId(), 'Repeated opening must reuse the participation link');
    savePublicDocumentFixture($document, ['active' => 0]);
    publicDocumentExpect($registration->getAccessibleDocument($documentId) === null && $registration->getAccessibleDocuments() === [], 'Archived document must be excluded');
    savePublicDocumentFixture($document, ['active' => 1, 'IDevent' => null]);
    publicDocumentExpect($registration->getAccessibleDocument($documentId) === null, 'Unrelated document must be excluded');
    savePublicDocumentFixture($document, ['IDevent' => $event->getId()]);
    publicDocumentExpect(EventPublicLink::setForEvent((int)$event->getId(), false), 'Disabling public link failed');
    publicDocumentExpect($registration->getAccessibleDocument($documentId) === null, 'Disabled public link must block access');
    EventPublicLink::setForEvent((int)$event->getId(), true);
    savePublicDocumentFixture($event, ['status' => Event::STATUS_CANCELLED]);
    publicDocumentExpect($registration->getAccessibleDocument($documentId) === null, 'Cancelled event must block access');
    savePublicDocumentFixture($event, ['status' => Event::STATUS_DRAFT]);
    publicDocumentExpect($registration->getAccessibleDocument($documentId) === null, 'Draft event must block access');
    $external = new Document();
    $external->set('documenttype', Document::TYPE_EXTERNAL_LINK);
    foreach (['https://mensuel.framapad.org/p/example' => true, 'https://framapad.org/p/example' => true,
        'https://framapad.org.attacker.invalid/p/example' => false, 'javascript:alert(1)' => false] as $url => $expected) {
        $external->set('externalurl', $url);
        publicDocumentExpect($external->isFramapadExternalLink() === $expected, 'Incorrect Framapad host classification');
    }
    echo "Public document access: confirmation, scope, disabled links, archived documents and individual PV links OK\n";
} finally {
    $pdo->rollBack();
}
