<?php
namespace dbObject;

/** Common lifecycle for resource-specific share links. Queries stay in each concrete dbObject. */
abstract class ShareLink extends DbObject
{
    protected static function generateToken(int $length = 48): string
    {
        return substr(rtrim(strtr(base64_encode(random_bytes(max(24, $length))), '+/', '-_'), '='), 0, 80);
    }

    public static function generateUniqueToken(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $token = static::generateToken();
            if (!static::findByToken($token)) return $token;
        }
        throw new \RuntimeException('share_token_generation_failed');
    }

    public function requiresPassword(): bool
    {
        return trim((string)$this->get('password_hash')) !== '';
    }

    public function verifyPassword($password): bool
    {
        return !$this->requiresPassword() || ((string)$password !== ''
            && password_verify((string)$password, (string)$this->get('password_hash')));
    }

    public function isExpired(): bool
    {
        $expiration = $this->get('dateexpiration');
        if (!$expiration) return false;
        try {
            $expiration = $expiration instanceof \DateTimeInterface ? $expiration : new \DateTimeImmutable((string)$expiration);
            return $expiration <= new \DateTimeImmutable();
        } catch (\Exception) {
            return true;
        }
    }
}
