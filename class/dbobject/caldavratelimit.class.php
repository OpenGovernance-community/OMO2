<?php
namespace dbObject;

/** Cheap pre-authentication rejection records. These records never grant access. */
final class CalDavRateLimit
{
    public static function directory(): string
    {
        return CalDavCache::getRootPath() . '/read-limits';
    }

    private static function path(string $key): string
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) throw new \InvalidArgumentException('Invalid CalDAV limit key.');
        return self::directory() . '/' . $key . '.json';
    }

    private static function read($handle): ?array
    {
        rewind($handle);
        $data = json_decode((string)stream_get_contents($handle, 65536), true);
        if (!is_array($data) || ($data['version'] ?? null) !== 1 || !is_array($data['revisions'] ?? null)) return null;
        foreach (['user_id', 'organization_id', 'burst_until', 'expires_at'] as $field) {
            if (!is_int($data[$field] ?? null)) return null;
        }
        if ($data['user_id'] <= 0 || $data['organization_id'] < 0 || $data['burst_until'] <= 0
            || $data['expires_at'] <= $data['burst_until']) return null;
        return $data;
    }

    private static function revisionsMatch(array $revisions): bool
    {
        foreach ($revisions as $organizationId => $revision) {
            if ((int)$organizationId <= 0 || (int)$revision <= 0
                || CalDavCache::getOrganizationRevision((int)$organizationId) !== (int)$revision) return false;
        }
        return true;
    }

    public static function inspect(string $key, ?int $now = null): ?array
    {
        $now ??= time();
        $handle = @fopen(self::path($key), 'rb');
        if (!is_resource($handle)) return null;
        try {
            if (!flock($handle, LOCK_SH | LOCK_NB)) return null;
            $data = self::read($handle);
        } finally { fclose($handle); }
        if (!$data || $now < (int)$data['burst_until'] || $now >= (int)$data['expires_at']
            || !self::revisionsMatch($data['revisions'])) return null;
        return $data + ['retry_after' => max(1, (int)$data['expires_at'] - $now)];
    }

    /** Called only after authenticated, authorized, successful discovery. Never extend a live window. */
    public static function remember(string $key, int $userId, int $organizationId, array $revisions,
        int $window, int $burst, ?int $now = null): bool
    {
        if ($userId <= 0 || $burst < 1 || $window <= $burst) return false;
        $now ??= time();
        $directory = self::directory();
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) return false;
        $path = self::path($key);
        $handle = @fopen($path, 'c+');
        if (!is_resource($handle)) return false;
        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) return false;
            @chmod($path, 0600);
            $previous = self::read($handle);
            if ($previous && $now < (int)$previous['expires_at'] && (int)$previous['user_id'] === $userId
                && $previous['revisions'] === $revisions) return true;
            // A response built while data changes must not suppress its next refresh.
            if (!self::revisionsMatch($revisions)) return false;
            $payload = json_encode(['version' => 1, 'user_id' => $userId, 'organization_id' => $organizationId,
                'burst_until' => $now + $burst, 'expires_at' => $now + $window, 'revisions' => $revisions], JSON_THROW_ON_ERROR);
            rewind($handle);
            return ftruncate($handle, 0) && fwrite($handle, $payload) === strlen($payload) && fflush($handle);
        } finally { fclose($handle); }
    }

    public static function cleanup(): int
    {
        $deleted = 0;
        $directory = self::directory();
        if (!is_dir($directory)) return 0;
        foreach (new \DirectoryIterator($directory) as $file) {
            if (!$file->isFile() || !preg_match('/^[a-f0-9]{64}\.json$/D', $file->getFilename()) || $file->getMTime() >= time() - 86400) continue;
            $handle = @fopen($file->getPathname(), 'r+');
            if (!is_resource($handle)) continue;
            try {
                if (!flock($handle, LOCK_EX | LOCK_NB)) continue;
                $data = self::read($handle);
                if ((!$data || (int)$data['expires_at'] < time()) && @unlink($file->getPathname())) $deleted++;
            } finally { fclose($handle); }
            if ($deleted >= 1000) break;
        }
        return $deleted;
    }
}
