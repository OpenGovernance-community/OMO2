<?php
namespace dbObject;

class VideoRoom extends DbObject
{
    public static function tableName() { return 'video_room'; }
    public static function rules()
    {
        return [[['IDuser', 'code', 'title'], 'required'], [['id'], 'integer'], [['id'], 'safe'],
            [['IDuser', 'IDorganization'], 'fk'], [['code', 'title'], 'string'], [['code'], 'unique'], [['created_at'], 'datetime'],
            [array_keys(self::settingDefaults()), 'boolean'], [['settings_version'], 'integer'],
            [['lobby_password_encrypted'], 'string'], [['settings_version', 'lobby_password_encrypted'], 'safe']];
    }
    public static function attributeLength() { return ['code' => 32, 'title' => 190]; }
    public static function attributeLabels()
    {
        $labels = ['IDuser' => 'Créateur', 'code' => 'Référence', 'title' => 'Nom de la réunion', 'created_at' => 'Création',
            'IDorganization' => 'Organisation associée (facultatif)'];
        foreach (['member', 'guest'] as $group) {
            foreach (['audio_muted' => 'Commencer avec le micro coupé', 'video_muted' => 'Commencer avec la caméra éteinte',
                'camera_restricted' => 'Bloquer l’activation de la caméra', 'screen_restricted' => 'Partage d’écran sur autorisation individuelle',
                'lobby' => 'Passer par la salle d’attente'] as $key => $label) $labels[$group . '_' . $key] = $label;
        }
        return $labels;
    }
    public static function settingDefaults(): array
    {
        return ['member_audio_muted' => 0, 'member_video_muted' => 0, 'member_camera_restricted' => 0,
            'member_screen_restricted' => 0, 'member_lobby' => 0, 'guest_audio_muted' => 0, 'guest_video_muted' => 0,
            'guest_camera_restricted' => 0, 'guest_screen_restricted' => 0, 'guest_lobby' => 1];
    }
    public static function getValues($field)
    {
        if ($field !== 'IDorganization') return parent::getValues($field);
        $user = new User();
        if (!$user->load((int)($_SESSION['currentUser'] ?? 0))) return [];
        return array_values(array_filter(iterator_to_array($user->getAccessibleOrganizations()),
            static fn($org) => $org->getMembership($user->getId(), true) !== null));
    }
    public function accessRole(int $userId): string
    {
        if ($this->isOwner($userId)) return 'owner';
        if ($userId <= 0) return 'guest';
        $organizationId = (int)$this->get('IDorganization');
        if (!$organizationId) return 'member';
        $organization = new Organization();
        return $organization->load($organizationId) && $organization->getMembership($userId, true) ? 'member' : 'guest';
    }
    public function policy(): array
    {
        $result = ['version' => max(1, (int)$this->get('settings_version'))];
        foreach (self::settingDefaults() as $field => $default) {
            [$group, $key] = explode('_', $field, 2);
            $value = $this->get($field);
            $result[$group][$key] = (bool)($value === '' || $value === null ? $default : $value);
        }
        $result['native_password'] = (string)$this->get('lobby_password_encrypted');
        return $result;
    }
    public function hasLobbyPassword(): bool { return (string)$this->get('lobby_password_encrypted') !== ''; }
    private function encryptLobbyPassword(string $password): string
    {
        $secret = (string)\envValue('JITSI_APP_SECRET', '');
        if (strlen($secret) < 32) throw new \RuntimeException('not_configured');
        $key = hash('sha256', 'omo-jitsi-room-password-v1:' . $secret . ':' . $this->jitsiRoomName(), true);
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($password, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($encrypted) || strlen($tag) !== 16) throw new \RuntimeException('storage_error');
        return 'v1:' . base64_encode($iv . $encrypted . $tag);
    }
    public function updateForCurrentUser(array $values): void
    {
        $pdo = self::getPdo();
        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) $pdo->beginTransaction();
        try {
            self::fetchRow('SELECT id FROM video_room WHERE id = :id FOR UPDATE', ['id' => $this->getId()]);
            if (!$this->load($this->getId(), true) || !$this->isOwner((int)($_SESSION['currentUser'] ?? 0))) throw new \RuntimeException('forbidden');
            $clean = [];
            $password = $values['room_password'] ?? '';
            $removePassword = $values['remove_room_password'] ?? '0';
            if (!is_string($password) || strlen($password) > 72 || !mb_check_encoding($password, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $password)
                || !in_array($removePassword, [0, 1, '0', '1'], true) || ($password !== '' && (int)$removePassword === 1)) throw new \RuntimeException('invalid_room_password');
            if ($password !== '' || (int)$removePassword === 1) {
                $clean['lobby_password_encrypted'] = $password !== '' ? $this->encryptLobbyPassword($password) : null;
            }
            if (isset($values['title'])) {
                if (!is_string($values['title']) || trim($values['title']) === '' || mb_strlen(trim($values['title']), 'UTF-8') > 190) throw new \RuntimeException('invalid_title');
                $clean['title'] = trim($values['title']);
            }
            foreach (self::settingDefaults() as $field => $default) {
                if (!array_key_exists($field, $values)) continue;
                if (!in_array($values[$field], [0, 1, '0', '1'], true)) throw new \RuntimeException('invalid_settings');
                $clean[$field] = (int)$values[$field];
            }
            if (array_key_exists('IDorganization', $values)) {
                $id = $values['IDorganization'];
                if ($id !== '' && $id !== null && (!is_scalar($id) || !preg_match('/\A[1-9][0-9]*\z/D', (string)$id))) throw new \RuntimeException('invalid_organization');
                $id = (int)$id;
                if ($id && $id !== (int)$this->get('IDorganization')) {
                    $organization = new Organization();
                    if (!$organization->load($id) || !$organization->getMembership((int)$_SESSION['currentUser'], true)) throw new \RuntimeException('invalid_organization');
                }
                $clean['IDorganization'] = $id ?: null;
            }
            $this->loadFromArray($clean);
            $this->set('settings_version', (int)$this->get('settings_version') + 1);
            if (empty($this->save()['status'])) throw new \RuntimeException('storage_error');
            if ($ownTransaction) $pdo->commit();
        } catch (\Throwable $error) {
            if ($ownTransaction && $pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
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
