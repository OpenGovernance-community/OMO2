<?php
namespace dbObject;

class MeetingBooking extends DbObject
{
    use CalendarTimeBuffer;
    public static function tableName() { return 'meeting_booking'; }
    public function canView() { return (int)$this->get('IDuser') > 0 && (int)$this->get('IDuser') === (int)($_SESSION['currentUser'] ?? 0); }
    public function canViewDetail() { return $this->canView(); }
    public static function rules()
    {
        return [[['IDuser', 'token', 'guest_name', 'guest_email', 'start_at', 'end_at'], 'required'],
            [['id', 'preparation_minutes', 'closing_minutes'], 'integer'], [['id'], 'safe'], [['IDuser', 'IDexternalcalendar'], 'fk'],
            [['token', 'status', 'guest_name', 'guest_email', 'resource_url'], 'string'],
            [['reason', 'calendar_data', 'meeting_method'], 'text'], [['start_at', 'end_at', 'created_at', 'email_sent_at'], 'datetime']];
    }
    public static function attributeLength()
    {
        return ['token' => 64, 'status' => 16, 'guest_name' => 190, 'guest_email' => 254, 'resource_url' => 2100];
    }
    public static function attributeLabels()
    {
        return ['IDuser' => 'Utilisateur', 'IDexternalcalendar' => 'Calendrier', 'token' => 'Reference',
            'status' => 'Etat', 'guest_name' => 'Nom', 'guest_email' => 'E-mail', 'reason' => 'Motif',
            'preparation_minutes' => 'Preparation (minutes)', 'closing_minutes' => 'Cloture (minutes)',
            'start_at' => 'Debut', 'end_at' => 'Fin', 'resource_url' => 'Ressource CalDAV',
            'calendar_data' => 'Evenement', 'created_at' => 'Creation', 'email_sent_at' => 'E-mail envoye', 'meeting_method' => 'Moyen de rencontre'];
    }
    public function meetingMethod(): ?array { return json_decode((string)$this->get('meeting_method'), true) ?: null; }
    public static function pendingIntervals(int $userId, \DateTimeInterface $start, \DateTimeInterface $end, string $except = ''): array
    {
        $params = ['uid' => $userId, 'status' => 'pending', 'start' => $start, 'end' => $end];
        if ($except !== '') { $params['token'] = $except; }
        $rows = self::fetchAll('SELECT start_at, end_at, preparation_minutes, closing_minutes FROM meeting_booking
            WHERE IDuser = :uid AND status = :status
            AND DATE_SUB(start_at, INTERVAL preparation_minutes MINUTE) < :end AND DATE_ADD(end_at, INTERVAL closing_minutes MINUTE) > :start'
            . ($except !== '' ? ' AND token <> :token' : ''), $params);
        if (!is_array($rows)) { throw new \RuntimeException('storage'); }
        return array_map(static fn($row) => [(new \DateTimeImmutable($row['start_at']))->modify('-' . (int)$row['preparation_minutes'] . ' minutes'),
            (new \DateTimeImmutable($row['end_at']))->modify('+' . (int)$row['closing_minutes'] . ' minutes')], $rows);
    }
}
