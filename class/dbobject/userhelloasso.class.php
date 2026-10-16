<?php
namespace dbObject;

/** One atomic, minimal snapshot of API payments per local account. No payer profile is retained. */
class UserHelloAsso extends DbObject
{
    public static function tableName() { return 'user_helloasso'; }
    public static function rules()
    {
        return [[['id'], 'integer'], [['IDuser'], 'fk'], [['email', 'configuration_key', 'last_sync_error'], 'string'],
            [['payments'], 'text'], [['last_attempt_at', 'last_sync_at'], 'datetime'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDuser' => 'Utilisateur', 'email' => 'Email rapproche', 'configuration_key' => 'Configuration',
            'payments' => 'Versements', 'last_attempt_at' => 'Dernier controle', 'last_sync_at' => 'Dernier succes',
            'last_sync_error' => 'Erreur de synchronisation'];
    }
    public static function attributeLength() { return ['email' => 255, 'configuration_key' => 64, 'last_sync_error' => 255]; }
    public function canView() { return false; }
    public function canEdit() { return false; }
    public static function isStorageAvailable(): bool { return self::tableExists(static::tableName()); }
    public static function findByUserId(int $id): ?self
    {
        if (!self::isStorageAvailable()) return null;
        $item = new self();
        return $item->load(['IDuser', $id]) ? $item : null;
    }

    /** Use the account email only, never an editable organization contact address. */
    public static function eligibleEmail(int $id): string
    {
        $row = self::fetchRow('SELECT email FROM `user` WHERE id = :id AND active = 1 AND COALESCE(activation_pending, 0) = 0', ['id' => $id]);
        $email = strtolower(trim((string)($row['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return '';
        $count = self::fetchValue('SELECT COUNT(*) FROM `user` WHERE LOWER(TRIM(email)) = :email', ['email' => $email]);
        return (int)$count === 1 ? $email : '';
    }

    public static function dueUserIds(string $key, int $interval, int $limit): array
    {
        $rows = self::fetchAll('SELECT u.id FROM `user` u LEFT JOIN user_helloasso h ON h.IDuser = u.id
            WHERE u.active = 1 AND COALESCE(u.activation_pending, 0) = 0
            AND (h.id IS NULL OR h.last_attempt_at IS NULL OR h.last_attempt_at <= :retry)
            AND (h.id IS NULL OR h.configuration_key <> :config OR h.email <> LOWER(TRIM(u.email))
                OR h.last_sync_at IS NULL OR h.last_sync_at <= :due)
            ORDER BY COALESCE(h.last_attempt_at, :oldest), u.id LIMIT :lim',
            ['retry' => gmdate('Y-m-d H:i:s', time() - 900), 'config' => $key,
                'due' => gmdate('Y-m-d H:i:s', time() - $interval), 'oldest' => '1970-01-01 00:00:00', 'lim' => $limit]);
        if ($rows === false) throw new \RuntimeException('Cannot load HelloAsso synchronization queue.');
        return array_map(static fn ($row) => (int)$row['id'], $rows);
    }

    public static function contribution(int $id, string $key, ?\DateTimeImmutable $now = null): array
    {
        $empty = ['amount_cents' => 0, 'valid_until' => null];
        $item = self::findByUserId($id);
        if (!$item || $item->get('configuration_key') !== $key || $item->get('email') !== self::eligibleEmail($id)) return $empty;
        $rows = json_decode((string)$item->get('payments'), true);
        return self::selectContribution(is_array($rows) ? $rows : [], $now);
    }

    /** Do not stack two monthly charges near the renewal boundary or two providers. */
    public static function selectContribution(array $payments, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $result = ['amount_cents' => 0, 'valid_until' => null];
        foreach ($payments as $payment) {
            if (($payment['state'] ?? '') !== 'Authorized' || empty($payment['paid_at']) || empty($payment['valid_until'])) continue;
            if (new \DateTimeImmutable($payment['paid_at']) > $now || new \DateTimeImmutable($payment['valid_until']) <= $now) continue;
            $amount = max(0, (int)($payment['monthly_amount_cents'] ?? 0));
            if ($amount > $result['amount_cents'] || ($amount > 0 && $amount === $result['amount_cents'] && $payment['valid_until'] > $result['valid_until'])) {
                $result = ['amount_cents' => $amount, 'valid_until' => $payment['valid_until']];
            }
        }
        return $result;
    }
}
