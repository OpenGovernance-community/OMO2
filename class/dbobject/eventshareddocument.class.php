<?php
namespace dbObject;

/** Reusable non-PV documents keep their own identity, title and dates. */
class EventSharedDocument extends DbObject
{
    public static function tableName() { return 'event_shared_document'; }
    public static function rules()
    {
        return [[['IDevent', 'IDdocument'], 'required'], [['IDevent', 'IDdocument'], 'fk'], [['id'], 'integer'], [['id'], 'safe']];
    }
    public static function attributeLabels() { return ['IDevent' => 'Reunion', 'IDdocument' => 'Document partage']; }

    public function save()
    {
        $event = new Event(); $document = new Document();
        if (!$event->load((int)$this->get('IDevent')) || !$document->load((int)$this->get('IDdocument'))
            || $document->isPvDocument() || $document->isArchived()
            || (int)$event->get('IDorganization') !== (int)$document->get('IDorganization')) {
            return ['status' => false, 'text' => 'Document partage incompatible.'];
        }
        return parent::save();
    }

    public static function attach(Event $event, Document $document): bool
    {
        $link = new self();
        if ($link->load([['IDevent', (int)$event->getId()], ['IDdocument', (int)$document->getId()]])) { return true; }
        $link->set('IDevent', $event->getId()); $link->set('IDdocument', $document->getId());
        return !empty($link->save()['status']);
    }

    /** Batch lookup also used by the calendar, avoiding one query per meeting. */
    public static function documentsByEvent(array $eventIds, int $organizationId): array
    {
        $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds), static fn($id) => $id > 0)));
        if (!$eventIds || !self::tableExists(self::tableName())) { return []; }
        $result = [];
        foreach (self::fetchAll('SELECT d.*, link.IDevent AS shared_event_id FROM event_shared_document link
            JOIN document d ON d.id = link.IDdocument JOIN event e ON e.id = link.IDevent
            WHERE link.IDevent IN (' . implode(',', $eventIds) . ') AND d.IDorganization = :organization
            AND e.IDorganization = d.IDorganization AND COALESCE(d.documenttype, \'\') <> :pv ORDER BY d.id',
            ['organization' => $organizationId, 'pv' => Document::TYPE_PV]) ?: [] as $row) {
            $eventId = (int)$row['shared_event_id']; unset($row['shared_event_id']);
            $document = new Document(); $document->hydrateFromDatabaseRow($row, true);
            $result[$eventId][] = $document;
        }
        return $result;
    }
}
