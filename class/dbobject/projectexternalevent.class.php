<?php
namespace dbObject;

/** Shared project documentation, independent of the lifetime of the personal calendar cache. */
class ProjectExternalEvent extends DbObject
{
    public static function tableName() { return 'project_external_event'; }
    public static function isStorageAvailable(): bool { return self::tableExists(self::tableName()); }

    public static function rules()
    {
        return [
            [['IDproject', 'title', 'calendar_title', 'start_at', 'end_at'], 'required'],
            [['id'], 'integer'],
            [['IDproject', 'IDexternalcalendarevent'], 'fk'],
            [['title', 'calendar_title', 'location'], 'string'],
            [['description'], 'text'],
            [['is_all_day', 'source_missing'], 'boolean'],
            [['start_at', 'end_at', 'last_seen_at', 'datecreation'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return ['IDproject' => 'Projet', 'IDexternalcalendarevent' => 'Evenement externe',
            'title' => 'Titre', 'calendar_title' => 'Calendrier source', 'description' => 'Description',
            'location' => 'Lieu', 'start_at' => 'Debut', 'end_at' => 'Fin', 'is_all_day' => 'Journee entiere',
            'source_missing' => 'Source indisponible', 'last_seen_at' => 'Derniere presence dans la source', 'datecreation' => 'Date d ajout'];
    }

    public static function attributeLength() { return ['title' => 1000, 'calendar_title' => 190, 'location' => 1000]; }

    public static function attach(Project $project, ExternalCalendarEvent $event, int $userId): ?self
    {
        if ((int)$project->getId() <= 0 || !$project->get('active') || $project->isPendingProposal()
            || !$event->canEditTimeBuffers($userId)) { return null; }
        $link = new self();
        if ($link->load([['IDproject', (int)$project->getId()], ['IDexternalcalendarevent', (int)$event->getId()]])) { return $link; }
        $calendar = new ExternalCalendar();
        if (!$calendar->load((int)$event->get('IDexternalcalendar'))) { return null; }
        $link->set('IDproject', (int)$project->getId());
        $link->set('IDexternalcalendarevent', (int)$event->getId());
        $link->set('calendar_title', (string)$calendar->get('title'));
        foreach (['title', 'description', 'location', 'start_at', 'end_at', 'is_all_day'] as $field) {
            $link->set($field, $event->get($field));
        }
        $link->set('source_missing', 0);
        $link->set('last_seen_at', new \DateTimeImmutable());
        $link->set('datecreation', new \DateTimeImmutable());
        return !empty($link->save()['status']) ? $link : null;
    }

    /** Called inside the successful source sync transaction; failures never mark sources missing. */
    public static function synchronizeForCalendar(int $calendarId, \DateTimeInterface $start, \DateTimeInterface $end): void
    {
        if (!self::tableExists(self::tableName())) { return; }
        $updated = self::execute('UPDATE project_external_event p
            JOIN external_calendar_event e ON e.id = p.IDexternalcalendarevent
            JOIN external_calendar c ON c.id = e.IDexternalcalendar
            SET p.title = e.title, p.description = e.description, p.location = e.location,
                p.start_at = e.start_at, p.end_at = e.end_at, p.is_all_day = e.is_all_day,
                p.calendar_title = c.title, p.source_missing = 0, p.last_seen_at = :seen
            WHERE e.IDexternalcalendar = :calendar AND e.active = 1', ['calendar' => $calendarId, 'seen' => new \DateTimeImmutable()]);
        // Feeds can drop historical events, including inside the fetched window.
        $missing = self::execute('UPDATE project_external_event p
            JOIN external_calendar_event e ON e.id = p.IDexternalcalendarevent
            SET p.source_missing = 1
            WHERE e.IDexternalcalendar = :calendar AND e.active = 0
                AND e.end_at >= :now AND e.start_at <= :end AND e.end_at >= :start',
            ['calendar' => $calendarId, 'start' => $start, 'end' => $end, 'now' => new \DateTimeImmutable()]);
        if (!$updated || !$missing) { throw new \RuntimeException('Unable to synchronize project event documentation.'); }
    }

    public function isSourceMissing(): bool
    {
        if ($this->get('source_missing')) { return true; }
        $end = $this->get('end_at');
        if ($end instanceof \DateTimeInterface && $end < new \DateTimeImmutable()) { return false; }
        if ((int)$this->get('IDexternalcalendarevent') <= 0) { return true; }
        $event = new ExternalCalendarEvent();
        $calendar = new ExternalCalendar();
        return !$event->load((int)$this->get('IDexternalcalendarevent'), true)
            || !$calendar->load((int)$event->get('IDexternalcalendar'), true)
            || !$calendar->get('active') || $calendar->get('availability_only');
    }
}
