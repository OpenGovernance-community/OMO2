<?php
namespace dbObject;

class EventPublicRegistration extends DbObject
{
    public static function tableName() { return 'event_public_registration'; }
    public static function rules()
    {
        return [[['IDevent', 'name', 'email', 'token'], 'required'], [['id'], 'integer'],
            [['IDevent'], 'fk'], [['name', 'token'], 'string'], [['email'], 'mail'],
            [['created_at', 'confirmed_at'], 'datetime'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDevent' => 'Evenement', 'name' => 'Nom',
            'email' => 'E-mail', 'token' => 'Confirmation', 'created_at' => 'Creation',
            'confirmed_at' => 'Confirmation'];
    }
    public static function attributeLength() { return ['name' => 190, 'email' => 254, 'token' => 64]; }

    public function getParticipantDisplayName(): string
    {
        $name = trim((string)preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', (string)$this->get('name')));
        $email = trim(mb_strtolower((string)$this->get('email'), 'UTF-8'));
        return $name !== '' && mb_strtolower($name, 'UTF-8') !== $email ? $name . ' (' . $email . ')' : $email;
    }

    public static function forEvent(int $eventId, bool $confirmedOnly = false): array
    {
        if ($eventId <= 0) { return []; }
        $items = new ArrayEventPublicRegistration();
        $where = [['field' => 'IDevent', 'value' => $eventId]];
        if ($confirmedOnly) { $where[] = ['field' => 'confirmed_at', 'op' => 'is not null']; }
        $items->load(['where' => $where, 'hydrate' => true, 'orderBy' => [
            ['field' => 'name', 'dir' => 'ASC'], ['field' => 'email', 'dir' => 'ASC'],
        ]]);
        return array_values($items->getArrayCopy());
    }

    public static function forEmail(int $eventId, string $email): ?self
    {
        $item = new self();
        return $item->load([['IDevent', $eventId], ['email', $email]]) ? $item : null;
    }

    public static function forToken(string $token): ?self
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) { return null; }
        $item = new self();
        return $item->load(['token', $token]) ? $item : null;
    }

    public static function confirmedCount(int $eventId): int
    {
        $row = self::fetchRow('SELECT COUNT(*) AS total FROM event_public_registration WHERE IDevent = :event_id AND confirmed_at IS NOT NULL',
            ['event_id' => $eventId]);
        return (int)($row['total'] ?? 0);
    }

    public function getAccessibleEvent(): ?Event
    {
        if (!$this->get('confirmed_at')) { return null; }
        $link = EventPublicLink::forEvent((int)$this->get('IDevent'));
        if (!$link || !(int)$link->get('enabled')) { return null; }
        $event = new Event();
        return $event->load((int)$this->get('IDevent')) && (int)$event->get('active') === 1
            && !in_array($event->get('status'), [Event::STATUS_DRAFT, Event::STATUS_CANCELLED], true)
            ? $event : null;
    }

    public function getAccessibleDocument(int $documentId): ?Document
    {
        $event = $this->getAccessibleEvent();
        $document = new Document();
        return $event && $documentId > 0 && $document->load($documentId)
            && (int)$document->get('IDevent') === (int)$event->getId()
            && (int)$document->get('IDorganization') === (int)$event->get('IDorganization')
            && !$document->isArchived() && !$document->isFolder()
            ? $document : null;
    }

    public function getAccessibleDocuments(): array
    {
        $event = $this->getAccessibleEvent();
        if (!$event) { return []; }
        return array_values(array_filter($event->getAssociatedDocuments(), static fn(Document $document): bool =>
            (int)$document->get('IDorganization') === (int)$event->get('IDorganization')
            && !$document->isArchived() && !$document->isFolder()));
    }

    public static function presentCount(int $eventId): int
    {
        $row = self::fetchRow('SELECT COUNT(*) AS total FROM resource_attendance WHERE resource_type = :type AND resource_id = :event_id AND active = 1 AND is_present = 1',
            ['type' => 'event', 'event_id' => $eventId]);
        return (int)($row['total'] ?? 0);
    }
}
