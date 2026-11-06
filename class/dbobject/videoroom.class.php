<?php
namespace dbObject;

class VideoRoom extends DbObject
{
    public static function tableName() { return 'video_room'; }
    public static function rules()
    {
        return [[['IDuser', 'code', 'title'], 'required'], [['id'], 'integer'], [['id'], 'safe'],
            [['IDuser'], 'fk'], [['code', 'title'], 'string'], [['code'], 'unique'], [['created_at'], 'datetime']];
    }
    public static function attributeLength() { return ['code' => 32, 'title' => 190]; }
    public static function attributeLabels()
    {
        return ['IDuser' => 'Créateur', 'code' => 'Référence', 'title' => 'Nom de la réunion', 'created_at' => 'Création'];
    }
    public function isOwner(int $userId): bool { return $userId > 0 && (int)$this->get('IDuser') === $userId; }
    public function canView() { return $this->isOwner((int)($_SESSION['currentUser'] ?? 0)); }
    public function canViewDetail() { return $this->canView(); }
    public static function isStorageAvailable(): bool { return self::tableExists('video_room'); }

    /** The unguessable code is an invitation, not an ownership credential. */
    public static function findByInvitation(string $code): ?self
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $code)) return null;
        $room = new self();
        return $room->load(['code', $code], true) ? $room : null;
    }
    public static function createForCurrentUser(string $title): self
    {
        $userId = (int)($_SESSION['currentUser'] ?? 0);
        if ($userId <= 0) throw new \RuntimeException('login_required');
        $user = new User();
        if (!$user->load($userId) || !$user->get('active')) throw new \RuntimeException('login_required');
        $title = trim($title);
        if ($title === '' || mb_strlen($title, 'UTF-8') > 190) throw new \RuntimeException('invalid_title');
        $room = new self();
        $room->set('IDuser', $userId);
        $room->set('code', bin2hex(random_bytes(16)));
        $room->set('title', $title);
        $room->set('created_at', new \DateTimeImmutable());
        if (empty($room->save()['status'])) throw new \RuntimeException('storage_error');
        return $room;
    }
    public static function listForCurrentUser(): array
    {
        $userId = (int)($_SESSION['currentUser'] ?? 0);
        if ($userId <= 0) return [];
        $rows = self::fetchAll('SELECT code, title, created_at FROM video_room WHERE IDuser = :uid ORDER BY created_at DESC, id DESC LIMIT 20', ['uid' => $userId]);
        if (!is_array($rows)) throw new \RuntimeException('storage_error');
        return $rows;
    }
    public function jitsiRoomName(): string { return 'omo-' . $this->get('code'); }
    public function invitationPath(): string { return '/visio/' . rawurlencode((string)$this->get('code')); }
}
