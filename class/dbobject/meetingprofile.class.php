<?php
namespace dbObject;

class MeetingProfile extends DbObject
{
    public static function tableName() { return 'meeting_profile'; }
    public static function rules()
    {
        return [
            [['IDuser', 'slug', 'weekly_hours'], 'required'],
            [['id'], 'integer'], [['id'], 'safe'],
            [['IDuser', 'IDexternalcalendar'], 'fk'],
            [['slug', 'timezone'], 'string'], [['weekly_hours'], 'text'], [['enabled'], 'boolean'],
        ];
    }
    public static function attributeLabels()
    {
        return ['IDuser' => 'Utilisateur', 'slug' => 'Nom public', 'enabled' => 'Activer la prise de rendez-vous',
            'IDexternalcalendar' => 'Calendrier de destination', 'timezone' => 'Fuseau horaire', 'weekly_hours' => 'Horaires'];
    }
    public static function attributeLength() { return ['slug' => 48, 'timezone' => 64]; }
    public function canView() { return (int)($this->get('IDuser')) === (int)($_SESSION['currentUser'] ?? 0) && (int)$this->get('IDuser') > 0; }
    public function canViewDetail() { return $this->canView(); }
    public static function isStorageAvailable() { return self::tableExists('meeting_profile') && self::tableExists('meeting_booking'); }
    public static function forUser(int $id): self
    {
        $profile = new self();
        if (!$profile->load(['IDuser', $id])) {
            $profile->set('IDuser', $id);
            $profile->set('enabled', 0);
            $profile->set('timezone', 'Europe/Zurich');
            $profile->set('weekly_hours', json_encode(self::defaultHours()));
        }
        return $profile;
    }
    public static function defaultHours(): array
    {
        $days = [];
        for ($i = 1; $i <= 7; $i++) {
            $days[$i] = ['open' => $i <= 5, 'start' => '09:00', 'end' => '17:00',
                'pause' => false, 'pause_start' => '12:00', 'pause_end' => '13:00'];
        }
        return $days;
    }
    public function hours(): array { return json_decode((string)$this->get('weekly_hours'), true) ?: self::defaultHours(); }
    public static function slugAvailable(string $slug, int $userId): bool
    {
        return (int)self::fetchValue('SELECT COUNT(*) FROM meeting_profile WHERE slug = :slug AND IDuser <> :uid',
            ['slug' => $slug, 'uid' => $userId]) === 0;
    }
    // The same owner lock guards settings, refresh and booking on every PHP worker.
    public static function lock(int $userId): bool
    {
        return (int)self::fetchValue('SELECT GET_LOCK(:lock_name, 0)', ['lock_name' => 'omo-meeting-' . $userId]) === 1;
    }
    public static function unlock(int $userId): void
    {
        self::fetchValue('SELECT RELEASE_LOCK(:lock_name)', ['lock_name' => 'omo-meeting-' . $userId]);
    }
}
