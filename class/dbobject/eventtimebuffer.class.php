<?php
namespace dbObject;

/** Personal time surrounding an event, including invitations through a holon. */
class EventTimeBuffer extends DbObject
{
    use CalendarTimeBuffer;

    public static function tableName() { return 'event_time_buffer'; }
    public static function rules()
    {
        return [
            [['IDevent', 'IDuser'], 'required'],
            [['id', 'preparation_minutes', 'closing_minutes'], 'integer'],
            [['IDevent', 'IDuser'], 'fk'],
            [['updated_at'], 'datetime'], [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['IDevent' => 'Evenement', 'IDuser' => 'Personne',
            'preparation_minutes' => 'Preparation (minutes)', 'closing_minutes' => 'Cloture (minutes)',
            'updated_at' => 'Mise a jour'];
    }
    public static function forUser(int $eventId, int $userId): self
    {
        $item = new self();
        if ($eventId > 0 && $userId > 0) {
            $row = self::memoizeRead(['forUser', $eventId, $userId], static fn () => self::fetchRow(
                'SELECT * FROM event_time_buffer WHERE IDevent = :event AND IDuser = :user',
                ['event' => $eventId, 'user' => $userId]
            ));
            if (is_array($row)) $item->hydrateFromDatabaseRow($row, true);
        }
        $item->set('IDevent', $eventId);
        $item->set('IDuser', $userId);
        return $item;
    }

    /** Prime the request-only read cache, including events without personal buffers. */
    public static function preloadForUser(array $eventIds, int $userId): void
    {
        if ($userId <= 0 || !self::isReadOnlyMemoizationEnabled()) return;
        $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds), static fn ($id) => $id > 0)));
        foreach (array_chunk($eventIds, 500) as $chunk) {
            $rows = self::fetchAll('SELECT * FROM event_time_buffer WHERE IDuser = :user AND IDevent IN ('
                . implode(',', $chunk) . ')', ['user' => $userId]);
            if (!is_array($rows)) continue;
            $byEvent = [];
            foreach ($rows as $row) $byEvent[(int)$row['IDevent']] = $row;
            foreach ($chunk as $eventId) {
                self::memoizeRead(['forUser', $eventId, $userId], static fn () => $byEvent[$eventId] ?? null);
            }
        }
    }
    public function save()
    {
        foreach (['preparation_minutes', 'closing_minutes'] as $field) {
            $this->set($field, self::validateBufferMinutes($this->get($field)));
        }
        $result = parent::save();
        if (is_array($result) && !empty($result['status'])) {
            $organizationId = Event::getOrganizationIdByEventId((int)$this->get('IDevent'));
            CalDavCache::invalidateOrganization($organizationId);
            CalDavSyncChange::recordEventChange($organizationId, (int)$this->get('IDevent'), 'updated');
        }
        return $result;
    }
}
