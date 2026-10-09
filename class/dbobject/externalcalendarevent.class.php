<?php
namespace dbObject;

class ExternalCalendarEvent extends DbObject
{
    use CalendarTimeBuffer;
    public static function tableName()
    {
        return 'external_calendar_event';
    }

    public static function rules()
    {
        return [
            [['IDexternalcalendar', 'source_key', 'title', 'start_at', 'end_at'], 'required'],
            [['id', 'preparation_minutes', 'closing_minutes'], 'integer'],
            [['IDexternalcalendar'], 'fk'],
            [['source_key', 'source_etag', 'title', 'location', 'timezone'], 'string'],
            [['description'], 'text'],
            [['is_all_day', 'is_busy', 'active', 'time_buffers_local'], 'boolean'],
            [['start_at', 'end_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'IDexternalcalendar' => 'Calendrier externe',
            'source_key' => 'Cle source',
            'source_etag' => 'ETag source',
            'title' => 'Titre',
            'description' => 'Description',
            'location' => 'Lieu',
            'timezone' => 'Fuseau horaire',
            'preparation_minutes' => 'Preparation (minutes)', 'closing_minutes' => 'Cloture (minutes)',
            'time_buffers_local' => 'Temps personnalises dans OMO',
            'start_at' => 'Debut',
            'end_at' => 'Fin',
            'is_all_day' => 'Journee entiere',
            'active' => 'Actif',
        ];
    }

    public static function attributeLength()
    {
        return [
            'source_key' => 512,
            'source_etag' => 255,
            'title' => 1000,
            'location' => 1000,
            'timezone' => 64,
        ];
    }

    public function getBusyInterval(): ?array
    {
        $start = $this->get('start_at');
        $end = $this->get('end_at');
        if (!$start instanceof \DateTimeInterface || !$end instanceof \DateTimeInterface) { return null; }
        $end = \DateTimeImmutable::createFromInterface($end);
        if ($this->get('is_all_day')) { $end = $end->modify('+1 second'); }
        return $end > $start ? $this->withTimeBuffers($start, $end) : null;
    }

    public static function findForCalendarSourceKey($calendarId, $sourceKey)
    {
        $calendarId = (int)$calendarId;
        $sourceKey = trim((string)$sourceKey);
        if ($calendarId <= 0 || $sourceKey === '') {
            return null;
        }

        $event = new self();
        return $event->load([
            ['IDexternalcalendar', $calendarId],
            ['source_key', $sourceKey],
        ]) ? $event : null;
    }

    /** Preserve local links and annotations when a standalone appointment changes its start time. */
    public static function findForImportedValues(int $calendarId, array $values): ?self
    {
        $event = self::findForCalendarSourceKey($calendarId, (string)$values['source_key']);
        $prefix = (string)($values['single_source_prefix'] ?? '');
        if ($event instanceof self || $prefix === '' || strlen($prefix) >= 512) { return $event; }
        // Ambiguous series or reused UIDs must never be merged into another occurrence.
        $rows = self::fetchAll('SELECT id FROM external_calendar_event
            WHERE IDexternalcalendar = :calendar AND LEFT(source_key, CHAR_LENGTH(:prefix_length)) = :prefix
            LIMIT 2', ['calendar' => $calendarId, 'prefix_length' => $prefix, 'prefix' => $prefix]);
        if (!is_array($rows) || count($rows) !== 1) { return null; }
        $event = new self();
        return $event->load((int)$rows[0]['id'], true) ? $event : null;
    }

    public function canEditTimeBuffers(int $userId): bool
    {
        $calendar = new ExternalCalendar();
        return $userId > 0 && $this->get('active') && $calendar->load((int)$this->get('IDexternalcalendar'), true)
            && $calendar->get('active') && !$calendar->get('availability_only') && (int)$calendar->get('IDuser') === $userId;
    }

    /** Only local annotations are writable; never overwrite imported event content or dates. */
    public function saveLocalTimeBuffers(int $userId, $preparation, $closing): bool
    {
        $preparation = self::validateBufferMinutes($preparation);
        $closing = self::validateBufferMinutes($closing);
        if (!$this->canEditTimeBuffers($userId)) { return false; }
        $saved = self::execute('UPDATE external_calendar_event e JOIN external_calendar c ON c.id = e.IDexternalcalendar
            SET e.preparation_minutes = :preparation, e.closing_minutes = :closing, e.time_buffers_local = 1, e.updated_at = NOW()
            WHERE e.id = :id AND e.active = 1 AND c.IDuser = :uid AND c.active = 1 AND c.availability_only = 0',
            ['preparation' => $preparation, 'closing' => $closing, 'id' => (int)$this->getId(), 'uid' => $userId]);
        return $saved && $this->load((int)$this->getId(), true) && $this->canEditTimeBuffers($userId)
            && $this->get('time_buffers_local') && (int)$this->get('preparation_minutes') === $preparation
            && (int)$this->get('closing_minutes') === $closing;
    }

    public function applyImportedValues(array $values): void
    {
        foreach (['source_key', 'source_etag', 'title', 'description', 'location', 'timezone', 'start_at', 'end_at',
            'is_all_day', 'is_busy', 'preparation_minutes', 'closing_minutes'] as $field) {
            if ($this->get('time_buffers_local') && in_array($field, ['preparation_minutes', 'closing_minutes'], true)) { continue; }
            $this->set($field, $values[$field] ?? null);
        }
    }

    /** Live booking checks must use the same annotations as the local cache. */
    public static function withLocalTimeBuffers(int $calendarId, array $events): array
    {
        $rows = self::fetchAll('SELECT source_key, preparation_minutes, closing_minutes FROM external_calendar_event
            WHERE IDexternalcalendar = :calendar AND time_buffers_local = 1 AND active = 1', ['calendar' => $calendarId]);
        if (!is_array($rows)) { throw new \RuntimeException('storage'); }
        $overrides = array_column($rows, null, 'source_key');
        foreach ($events as &$event) {
            if (!isset($overrides[$event['source_key']])) { continue; }
            $row = $overrides[$event['source_key']];
            $event['preparation_minutes'] = (int)$row['preparation_minutes'];
            $event['closing_minutes'] = (int)$row['closing_minutes'];
        }
        unset($event);
        return $events;
    }

    public static function deactivateForCalendar($calendarId)
    {
        return self::execute(
            'UPDATE `external_calendar_event` SET `active` = 0 WHERE `IDexternalcalendar` = :calendar_id',
            ['calendar_id' => (int)$calendarId]
        );
    }

    public static function deactivateInRange($calendarId, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd)
    {
        return self::execute(
            'UPDATE `external_calendar_event`
             SET `active` = 0
             WHERE `IDexternalcalendar` = :calendar_id
               AND `start_at` <= :range_end
               AND `end_at` >= :range_start',
            [
                'calendar_id' => (int)$calendarId,
                'range_start' => $rangeStart,
                'range_end' => $rangeEnd,
            ]
        );
    }
}
