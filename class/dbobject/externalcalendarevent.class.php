<?php
namespace dbObject;

class ExternalCalendarEvent extends DbObject
{
    public static function tableName()
    {
        return 'external_calendar_event';
    }

    public static function rules()
    {
        return [
            [['IDexternalcalendar', 'source_key', 'title', 'start_at', 'end_at'], 'required'],
            [['id'], 'integer'],
            [['IDexternalcalendar'], 'fk'],
            [['source_key', 'source_etag', 'title', 'location', 'timezone'], 'string'],
            [['description'], 'text'],
            [['is_all_day', 'is_busy', 'active'], 'boolean'],
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
