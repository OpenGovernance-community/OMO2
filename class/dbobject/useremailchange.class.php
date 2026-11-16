<?php
namespace dbObject;

class UserEmailChange extends DbObject
{
    public static function tableName() { return 'user_email_change'; }

    public static function rules()
    {
        return [
            [['id', 'security_version', 'attempt_count'], 'integer'],
            [['IDuser'], 'fk'],
            [['old_email', 'new_email', 'request_hash', 'old_code_hash', 'new_code_hash'], 'string'],
            [['created_at', 'expires_at'], 'datetime'],
            [['ready'], 'boolean'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'IDuser' => 'Utilisateur', 'old_email' => 'Adresse actuelle', 'new_email' => 'Nouvelle adresse',
            'request_hash' => 'Empreinte de la demande', 'old_code_hash' => 'Empreinte du code actuel',
            'new_code_hash' => 'Empreinte du nouveau code', 'security_version' => 'Version de sécurité',
            'created_at' => 'Créée le', 'expires_at' => 'Expire le', 'attempt_count' => 'Tentatives',
            'ready' => 'Codes envoyés',
        ];
    }

    public static function attributeLength()
    {
        return ['old_email' => 150, 'new_email' => 150, 'request_hash' => 64,
            'old_code_hash' => 255, 'new_code_hash' => 255];
    }

    private static function emailIsAvailable(string $email, int $userId, bool $lock = false): bool
    {
        // Locking reads also protect the final claim against concurrent account creation.
        // Historical databases may contain duplicate addresses and have no unique email index.
        $suffix = $lock ? ' FOR UPDATE' : '';
        $users = self::fetchAll('SELECT id FROM `user` WHERE LOWER(TRIM(email)) = :email AND id <> :id' . $suffix,
            ['email' => $email, 'id' => $userId]);
        $memberships = self::fetchAll('SELECT id FROM user_organization WHERE LOWER(TRIM(email)) = :email AND IDuser <> :id' . $suffix,
            ['email' => $email, 'id' => $userId]);
        if ($users === false || $memberships === false) throw new \RuntimeException('Email availability lookup failed.');
        return $users === [] && $memberships === [];
    }

    /** Plain codes are returned only to the mail sender and never stored. */
    public static function issue(int $userId, string $newEmail, int $securityVersion): array
    {
        $newEmail = strtolower(trim($newEmail));
        if (strlen($newEmail) > 150 || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) return ['error' => 'invalid_email'];
        $pdo = self::getPdo();
        if ($pdo->inTransaction()) throw new \LogicException('Email changes require their own transaction.');
        $pdo->beginTransaction();
        try {
            self::fetchRow('SELECT id FROM `user` WHERE id = :id FOR UPDATE', ['id' => $userId]);
            $user = new User();
            if (!$user->load($userId, true) || (int)$user->get('security_version') !== $securityVersion
                || $user->isRoleplayCharacter() || $user->isHistoricalPlaceholder()
                || !filter_var($user->get('email'), FILTER_VALIDATE_EMAIL)) {
                $pdo->rollBack();
                return ['error' => 'unavailable'];
            }
            if (strtolower(trim((string)$user->get('email'))) === $newEmail) {
                $pdo->rollBack();
                return ['error' => 'same_email'];
            }
            if (!self::emailIsAvailable($newEmail, $userId)) {
                $pdo->rollBack();
                return ['error' => 'email_in_use'];
            }
            $pending = new self();
            if ($pending->load(['IDuser', $userId], true)
                && $pending->get('created_at') > new \DateTime('-60 seconds')) {
                $pdo->rollBack();
                return ['error' => 'rate_limited'];
            }
            $token = bin2hex(random_bytes(32));
            $oldCode = sprintf('%06d', random_int(0, 999999));
            do { $newCode = sprintf('%06d', random_int(0, 999999)); } while ($newCode === $oldCode);
            $pending->set('IDuser', $userId);
            $pending->set('old_email', (string)$user->get('email'));
            $pending->set('new_email', $newEmail);
            $pending->set('request_hash', hash('sha256', $token));
            $pending->set('old_code_hash', password_hash($oldCode, PASSWORD_DEFAULT));
            $pending->set('new_code_hash', password_hash($newCode, PASSWORD_DEFAULT));
            $pending->set('security_version', $securityVersion);
            $pending->set('created_at', new \DateTime());
            $pending->set('expires_at', new \DateTime('+10 minutes'));
            $pending->set('attempt_count', 0);
            $pending->set('ready', 0);
            if (empty($pending->save()['status'])) throw new \RuntimeException('Email change creation failed.');
            $pdo->commit();
            return ['token' => $token, 'old_code' => $oldCode, 'new_code' => $newCode,
                'old_email' => (string)$user->get('email'), 'new_email' => $newEmail];
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function markDelivered(int $userId, string $token): bool
    {
        if (!self::execute('UPDATE user_email_change SET ready = 1
            WHERE IDuser = :id AND request_hash = :hash AND ready = 0 AND expires_at > NOW()',
            ['id' => $userId, 'hash' => hash('sha256', $token)])) return false;
        return (bool)self::fetchValue('SELECT ready FROM user_email_change WHERE IDuser = :id AND request_hash = :hash',
            ['id' => $userId, 'hash' => hash('sha256', $token)]);
    }

    /** Verify both proofs and consume the request in the same transaction as the address update. */
    public static function confirm(int $userId, string $token, string $oldCode, string $newCode, int $securityVersion): array
    {
        $pdo = self::getPdo();
        if ($pdo->inTransaction()) throw new \LogicException('Email changes require their own transaction.');
        // Next-key locks also protect absent addresses on installations without a unique email index.
        if (!self::execute('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ')) {
            throw new \RuntimeException('Email change isolation setup failed.');
        }
        $pdo->beginTransaction();
        try {
            // Take address-claim locks before the account lock for consistent lock ordering.
            $pending = new self();
            if (!$pending->load(['IDuser', $userId], true)) {
                $pdo->rollBack();
                return ['error' => 'expired'];
            }
            $available = self::emailIsAvailable((string)$pending->get('new_email'), $userId, true);
            $userRow = self::fetchRow('SELECT * FROM `user` WHERE id = :id FOR UPDATE', ['id' => $userId]);
            $row = self::fetchRow('SELECT * FROM user_email_change WHERE IDuser = :id FOR UPDATE', ['id' => $userId]);
            $user = new User();
            if ($userRow) $user->hydrateFromDatabaseRow($userRow, true);
            if (!$row || !$userRow || !$row['ready']
                || !hash_equals($row['request_hash'], hash('sha256', $token))
                || $row['new_email'] !== $pending->get('new_email')
                || $row['old_email'] !== $user->get('email')
                || (int)$row['security_version'] !== (int)$user->get('security_version')
                || $securityVersion !== (int)$user->get('security_version')
                || new \DateTime($row['expires_at']) <= new \DateTime()
                || (int)$row['attempt_count'] >= 5) {
                $pdo->rollBack();
                return ['error' => 'expired'];
            }
            // Always check both hashes. Each submission consumes one of five attempts.
            $oldValid = preg_match('/^[0-9]{6}$/D', $oldCode) === 1 && password_verify($oldCode, $row['old_code_hash']);
            $newValid = preg_match('/^[0-9]{6}$/D', $newCode) === 1 && password_verify($newCode, $row['new_code_hash']);
            if (!$oldValid || !$newValid) {
                if (!self::execute('UPDATE user_email_change SET attempt_count = attempt_count + 1 WHERE id = :id', ['id' => $row['id']])) {
                    throw new \RuntimeException('Email change attempt update failed.');
                }
                $pdo->commit();
                return ['error' => (int)$row['attempt_count'] >= 4 ? 'expired' : 'invalid_codes'];
            }
            if (!$available) {
                $pdo->rollBack();
                return ['error' => 'email_in_use'];
            }
            $user->set('email', $row['new_email']);
            $user->set('code', null);
            $user->set('codeexpiration', null);
            if (empty($user->save()['status']) || !$user->revokeAuthentication()
                || !self::execute('DELETE FROM user_email_change WHERE id = :id', ['id' => $row['id']])) {
                throw new \RuntimeException('Email change confirmation failed.');
            }
            $pdo->commit();
            return ['email' => (string)$user->get('email'), 'security_version' => (int)$user->get('security_version')];
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
