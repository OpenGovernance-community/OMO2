<?php
namespace dbObject;

class CalendarShare extends DbObject
{
    public static function tableName() { return 'calendar_share'; }
    public static function rules()
    {
        return [
            [['IDuser', 'label', 'token', 'months'], 'required'],
            [['id', 'months'], 'integer'], [['id'], 'safe'], [['IDuser'], 'fk'],
            [['label', 'token', 'scope_key'], 'string'], [['details', 'active'], 'boolean'],
            [['expires_at', 'created_at'], 'datetime'],
        ];
    }
    public static function attributeLabels()
    {
        return ['IDuser' => 'Utilisateur', 'label' => 'Nom du partage', 'months' => 'Mois visibles',
            'details' => 'Afficher les details', 'expires_at' => 'Expiration', 'active' => 'Actif',
            'token' => 'Jeton secret', 'scope_key' => 'Portee du calendrier', 'created_at' => 'Creation'];
    }
    public static function attributeLength() { return ['label' => 100, 'token' => 64, 'scope_key' => 100]; }
    public function canView() { return (int)$this->get('IDuser') > 0 && (int)$this->get('IDuser') === (int)($_SESSION['currentUser'] ?? 0); }
    public function canViewDetail() { return $this->canView(); }
    public static function isStorageAvailable() { return self::tableExists(self::tableName()); }

    public static function forUser(int $userId): array
    {
        $rows = self::fetchAll('SELECT id FROM calendar_share WHERE IDuser = :uid ORDER BY id DESC', ['uid' => $userId]);
        if (!is_array($rows)) { throw new \RuntimeException('storage'); }
        $shares = [];
        foreach ($rows as $row) {
            $share = new self();
            if ($share->load((int)$row['id'])) { $shares[] = $share; }
        }
        return $shares;
    }

    public static function createForUser(int $userId, string $label, int $months, bool $details, string $expiration): self
    {
        $label = trim($label);
        if ($userId <= 0 || $label === '' || mb_strlen($label) > 100 || $months < 1 || $months > 12) {
            throw new \InvalidArgumentException('invalid');
        }
        $expires = null;
        if ($expiration !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $expiration);
            if (!$date || $date->format('Y-m-d') !== $expiration || $date < new \DateTimeImmutable('today')) {
                throw new \InvalidArgumentException('expiration_invalid');
            }
            // The selected date remains usable through the end of that day.
            $expires = $date->modify('+1 day');
        }
        $share = new self();
        foreach (['IDuser' => $userId, 'label' => $label, 'token' => bin2hex(random_bytes(32)), 'months' => $months,
            'details' => $details, 'active' => 1, 'expires_at' => $expires, 'created_at' => new \DateTimeImmutable()] as $key => $value) {
            $share->set($key, $value);
        }
        $saved = $share->save();
        if (!is_array($saved) || empty($saved['status'])) { throw new \RuntimeException('storage'); }
        return $share;
    }

    public static function buildScopedCalendarKey(int $organizationId, int $holonId, string $range): string
    {
        $range = strtolower(trim($range));
        if (
            $organizationId <= 0
            || $holonId <= 0
            || !in_array($range, ['contextual', 'children', 'descendants'], true)
        ) {
            return '';
        }

        return 'omo:' . $organizationId . ':' . $holonId . ':' . $range;
    }

    public static function parseScopedCalendarKey($scopeKey): ?array
    {
        $scopeKey = trim((string)$scopeKey);
        if (!preg_match('#^omo:(\d+):(\d+):(contextual|children|descendants)$#', $scopeKey, $matches)) {
            return null;
        }

        return [
            'organizationId' => (int)$matches[1],
            'holonId' => (int)$matches[2],
            'range' => (string)$matches[3],
        ];
    }

    public static function findOrCreateScopedCalendarForUser(
        int $userId,
        int $organizationId,
        int $holonId,
        string $range,
        string $label
    ): ?self {
        $scopeKey = self::buildScopedCalendarKey($organizationId, $holonId, $range);
        $label = trim($label);
        if ($userId <= 0 || $scopeKey === '' || $label === '' || mb_strlen($label) > 100) {
            return null;
        }

        $share = new self();
        $existingShare = $share->load([['IDuser', $userId], ['scope_key', $scopeKey]]);
        if (!$existingShare) {
            $share->set('IDuser', $userId);
            $share->set('scope_key', $scopeKey);
            $share->set('token', bin2hex(random_bytes(32)));
            $share->set('created_at', new \DateTimeImmutable());
        } elseif (!(bool)$share->get('active')) {
            // A revoked capability must stay revoked. Reconnecting creates a new capability.
            $share->set('token', bin2hex(random_bytes(32)));
        }

        $share->set('label', $label);
        $share->set('months', 12);
        $share->set('details', 1);
        $share->set('active', 1);
        $share->set('expires_at', null);
        $saved = $share->save();

        return is_array($saved) && !empty($saved['status']) ? $share : null;
    }

    public static function revokeForUser(int $id, int $userId): void
    {
        $share = new self();
        if ($userId <= 0 || !$share->load([['id', $id], ['IDuser', $userId]])) { throw new \RuntimeException('missing'); }
        $share->set('active', 0);
        $saved = $share->save();
        if (!is_array($saved) || empty($saved['status'])) { throw new \RuntimeException('storage'); }
    }

    public function isUsable(?\DateTimeImmutable $now = null): bool
    {
        $expires = $this->get('expires_at');
        return (bool)$this->get('active') && (!$expires || $expires > ($now ?? new \DateTimeImmutable()));
    }

    public static function resolveToken(string $token): ?self
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) { return null; }
        $share = new self();
        if (!$share->load(['token', $token]) || !$share->isUsable()) { return null; }
        $user = new User();
        return $user->load((int)$share->get('IDuser')) && $user->get('active') ? $share : null;
    }

    public function visibilityRange(?\DateTimeImmutable $now = null): array
    {
        $start = ($now ?? new \DateTimeImmutable())->setTime(0, 0);
        $month = $start->modify('first day of this month')->modify('+' . max(1, min(12, (int)$this->get('months'))) . ' months');
        $end = $month->setDate((int)$month->format('Y'), (int)$month->format('m'), min((int)$start->format('d'), (int)$month->format('t')));
        return [$start, $end];
    }

    public function getScopedCalendarConfig(): ?array
    {
        return self::parseScopedCalendarKey($this->get('scope_key'));
    }
}
