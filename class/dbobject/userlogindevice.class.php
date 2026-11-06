<?php
namespace dbObject;

// Recognition only: these tokens must never grant access or bypass authentication.
class UserLoginDevice extends DbObject
{
    public static function tableName() { return 'user_login_device'; }

    public static function rules()
    {
        return [
            [['IDuser', 'token_hash', 'expires_at'], 'required'],
            [['id'], 'integer'],
            [['IDuser'], 'fk'],
            [['token_hash'], 'string'],
            [['expires_at', 'created_at', 'last_login_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return ['IDuser' => 'Utilisateur', 'token_hash' => 'Empreinte du navigateur',
            'expires_at' => 'Expiration', 'created_at' => 'Creation', 'last_login_at' => 'Derniere connexion'];
    }

    public static function attributeLength() { return ['token_hash' => 64]; }
    public static function lifetimeSeconds(): int { return 365 * 24 * 60 * 60; }

    public static function recordLogin(int $userId, string $token): array
    {
        if ($userId <= 0 || !self::tableExists(self::tableName())) {
            throw new \RuntimeException('Login device storage unavailable.');
        }
        self::execute('DELETE FROM user_login_device WHERE IDuser = :uid AND expires_at <= :now',
            ['uid' => $userId, 'now' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')]);
        $item = new self();
        $known = false;
        if (preg_match('/^[a-f0-9]{64}$/D', $token)) {
            $known = $item->load([['IDuser', $userId], ['token_hash', hash('sha256', $token)]])
                && $item->get('expires_at') instanceof \DateTimeInterface
                && $item->get('expires_at')->getTimestamp() > time();
        }
        if (!$known) {
            $item = new self();
            $token = bin2hex(random_bytes(32));
            $item->set('IDuser', $userId);
            $item->set('token_hash', hash('sha256', $token));
            $item->set('created_at', new \DateTimeImmutable());
        }
        $item->set('expires_at', new \DateTimeImmutable('+' . self::lifetimeSeconds() . ' seconds'));
        $item->set('last_login_at', new \DateTimeImmutable());
        if (empty($item->save()['status'])) {
            throw new \RuntimeException('Unable to record login device.');
        }
        return ['known' => $known, 'token' => $token];
    }
}
