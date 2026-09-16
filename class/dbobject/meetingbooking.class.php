<?php
namespace dbObject;

class MeetingBooking extends DbObject
{
    public static function tableName() { return 'meeting_booking'; }
    public function canView() { return (int)$this->get('IDuser') > 0 && (int)$this->get('IDuser') === (int)($_SESSION['currentUser'] ?? 0); }
    public function canViewDetail() { return $this->canView(); }
    public static function rules()
    {
        return [[['IDuser', 'token', 'guest_name', 'guest_email', 'start_at', 'end_at'], 'required'],
            [['id'], 'integer'], [['id'], 'safe'], [['IDuser', 'IDexternalcalendar'], 'fk'],
            [['token', 'status', 'guest_name', 'guest_email', 'resource_url'], 'string'],
            [['reason', 'calendar_data'], 'text'], [['start_at', 'end_at', 'created_at', 'email_sent_at'], 'datetime']];
    }
    public static function attributeLength()
    {
        return ['token' => 64, 'status' => 16, 'guest_name' => 190, 'guest_email' => 254, 'resource_url' => 2100];
    }
    public static function attributeLabels()
    {
        return ['IDuser' => 'Utilisateur', 'IDexternalcalendar' => 'Calendrier', 'token' => 'Reference',
            'status' => 'Etat', 'guest_name' => 'Nom', 'guest_email' => 'E-mail', 'reason' => 'Motif',
            'start_at' => 'Debut', 'end_at' => 'Fin', 'resource_url' => 'Ressource CalDAV',
            'calendar_data' => 'Evenement', 'created_at' => 'Creation', 'email_sent_at' => 'E-mail envoye'];
    }
    public static function pendingIntervals(int $userId, \DateTimeInterface $start, \DateTimeInterface $end, string $except = ''): array
    {
        $rows = self::fetchAll('SELECT start_at, end_at FROM meeting_booking
            WHERE IDuser = :uid AND status = :status AND token <> :token AND start_at < :end AND end_at > :start',
            ['uid' => $userId, 'status' => 'pending', 'token' => $except, 'start' => $start, 'end' => $end]);
        if (!is_array($rows)) { throw new \RuntimeException('storage'); }
        return array_map(static fn($row) => [new \DateTimeImmutable($row['start_at']), new \DateTimeImmutable($row['end_at'])], $rows);
    }
}
