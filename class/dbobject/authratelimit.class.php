<?php

namespace dbObject;

class AuthRateLimit extends DbObject
{
    public static function tableName()
    {
        return 'auth_rate_limit';
    }

    public static function rules()
    {
        return [
            [['id', 'attempt_count'], 'integer'],
            [['scope', 'key_hash'], 'string'],
            [['window_started_at', 'blocked_until', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'scope' => 'Périmètre',
            'key_hash' => 'Clé anonymisée',
            'window_started_at' => 'Début de fenêtre',
            'attempt_count' => 'Tentatives',
            'blocked_until' => 'Bloque jusqu au',
            'created_at' => 'Créé le',
            'updated_at' => 'Mis à jour le',
        ];
    }

    public static function attributeLength()
    {
        return [
            'scope' => 64,
            'key_hash' => 64,
        ];
    }

    public static function getOrder()
    {
        return 'updated_at DESC';
    }

    public static function inspect($scope, $keyHash, $windowSeconds)
    {
        return self::updateBucket($scope, $keyHash, 1, $windowSeconds, $windowSeconds, false, false);
    }

    public static function consume($scope, $keyHash, $maximum, $windowSeconds, $blockSeconds = null)
    {
        return self::updateBucket(
            $scope,
            $keyHash,
            $maximum,
            $windowSeconds,
            $blockSeconds,
            true,
            false
        );
    }

    // Check every delivery quota under the same locks before counting an accepted request.
    public static function consumeBuckets(array $buckets, $inspectOnly = false)
    {
        $pdo = self::getPdo();
        if (!$pdo instanceof \PDO) {
            return self::storageErrorResult();
        }
        $ownsTransaction = !$pdo->inTransaction();
        try {
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }
            $combined = ['available' => true, 'allowed' => true, 'retry_after' => 0];
            foreach ($buckets as $bucket) {
                $result = self::updateBucket(
                    $bucket['scope'], $bucket['key_hash'], $bucket['maximum'],
                    $bucket['window'], $bucket['block'], false, false, true
                );
                $combined['available'] = $combined['available'] && $result['available'];
                $combined['allowed'] = $combined['allowed'] && $result['allowed'];
                $combined['retry_after'] = max($combined['retry_after'], $result['retry_after']);
            }
            if (!$inspectOnly && $combined['allowed']) {
                foreach ($buckets as $bucket) {
                    $result = self::consume(
                        $bucket['scope'], $bucket['key_hash'], $bucket['maximum'],
                        $bucket['window'], $bucket['block']
                    );
                    if (!$result['available'] || !$result['allowed']) {
                        throw new \RuntimeException('Cannot consume checked authentication quota.');
                    }
                }
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $combined;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Authentication delivery rate limit storage error: ' . $exception->getMessage());
            return self::storageErrorResult();
        }
    }

    public static function recordFailure($scope, $keyHash, $maximum, $windowSeconds, $blockSeconds = null)
    {
        return self::updateBucket(
            $scope,
            $keyHash,
            $maximum,
            $windowSeconds,
            $blockSeconds,
            true,
            true
        );
    }

    public static function clearBucket($scope, $keyHash)
    {
        $scope = self::normalizeScope($scope);
        $keyHash = self::normalizeKeyHash($keyHash);
        if ($scope === '' || $keyHash === '') {
            return false;
        }

        return self::execute(
            'DELETE FROM auth_rate_limit WHERE scope = :scope AND key_hash = :key_hash',
            [
                'scope' => $scope,
                'key_hash' => $keyHash,
            ]
        );
    }

    public static function purgeStale($retentionDays = 30, $maximumRows = 1000)
    {
        $retentionDays = max(1, min(365, (int)$retentionDays));
        $maximumRows = max(1, min(10000, (int)$maximumRows));

        return self::execute(
            'DELETE FROM auth_rate_limit
             WHERE updated_at < DATE_SUB(NOW(), INTERVAL ' . $retentionDays . ' DAY)
             LIMIT ' . $maximumRows
        );
    }

    private static function updateBucket(
        $scope,
        $keyHash,
        $maximum,
        $windowSeconds,
        $blockSeconds,
        $increment,
        $blockAtLimit,
        $checkCapacity = false
    ) {
        $scope = self::normalizeScope($scope);
        $keyHash = self::normalizeKeyHash($keyHash);
        $maximum = max(1, (int)$maximum);
        $windowSeconds = max(1, (int)$windowSeconds);
        $blockSeconds = $blockSeconds === null ? $windowSeconds : max(1, (int)$blockSeconds);

        if ($scope === '' || $keyHash === '') {
            return self::storageErrorResult();
        }

        $pdo = self::getPdo();
        if (!$pdo instanceof \PDO) {
            return self::storageErrorResult();
        }

        $ownsTransaction = !$pdo->inTransaction();
        $now = new \DateTimeImmutable();

        try {
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }

            $statement = $pdo->prepare(
                'SELECT id, attempt_count, window_started_at, blocked_until
                 FROM auth_rate_limit
                 WHERE scope = :scope AND key_hash = :key_hash
                 LIMIT 1
                 FOR UPDATE'
            );
            $statement->execute([
                'scope' => $scope,
                'key_hash' => $keyHash,
            ]);
            $row = $statement->fetch(\PDO::FETCH_ASSOC);
            $statement->closeCursor();

            if ($row === false) {
                if (!$increment) {
                    if ($ownsTransaction) {
                        $pdo->commit();
                    }
                    return self::allowedResult(0, $maximum, 0);
                }

                $attemptCount = 1;
                $blockedUntil = $blockAtLimit && $attemptCount >= $maximum
                    ? $now->modify('+' . $blockSeconds . ' seconds')
                    : null;
                $insert = $pdo->prepare(
                    'INSERT INTO auth_rate_limit
                        (scope, key_hash, window_started_at, attempt_count, blocked_until, created_at, updated_at)
                     VALUES
                        (:scope, :key_hash, :window_started_at, :attempt_count, :blocked_until, :created_at, :updated_at)'
                );
                $insert->execute([
                    'scope' => $scope,
                    'key_hash' => $keyHash,
                    'window_started_at' => self::formatDateTime($now),
                    'attempt_count' => $attemptCount,
                    'blocked_until' => $blockedUntil ? self::formatDateTime($blockedUntil) : null,
                    'created_at' => self::formatDateTime($now),
                    'updated_at' => self::formatDateTime($now),
                ]);
                $insert->closeCursor();

                if ($ownsTransaction) {
                    $pdo->commit();
                }

                if ($blockedUntil) {
                    return self::blockedResult($attemptCount, $maximum, $blockedUntil->getTimestamp() - $now->getTimestamp());
                }

                return self::allowedResult($attemptCount, $maximum, 0);
            }

            $windowStartedAt = self::parseDateTime($row['window_started_at'] ?? null);
            $blockedUntil = self::parseDateTime($row['blocked_until'] ?? null);
            if ($blockedUntil && $blockedUntil > $now) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return self::blockedResult(
                    (int)($row['attempt_count'] ?? 0),
                    $maximum,
                    $blockedUntil->getTimestamp() - $now->getTimestamp()
                );
            }

            $windowExpired = !$windowStartedAt
                || ($now->getTimestamp() - $windowStartedAt->getTimestamp()) >= $windowSeconds;
            $attemptCount = $windowExpired ? 0 : max(0, (int)($row['attempt_count'] ?? 0));
            $windowStartedAt = $windowExpired ? $now : $windowStartedAt;
            $blockedUntil = null;

            // Delivery limits expire from the accepted request, never from a rejected click.
            if (!$blockAtLimit && ($increment || $checkCapacity) && $attemptCount >= $maximum) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return self::blockedResult(
                    $attemptCount, $maximum,
                    $windowSeconds - ($now->getTimestamp() - $windowStartedAt->getTimestamp())
                );
            }
            if (!$increment) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return self::allowedResult($attemptCount, $maximum, 0);
            }

            if ($increment) {
                $attemptCount++;
                $mustBlock = $blockAtLimit ? $attemptCount >= $maximum : $attemptCount > $maximum;
                if ($mustBlock) {
                    $blockedUntil = $now->modify('+' . $blockSeconds . ' seconds');
                }
            }

            $update = $pdo->prepare(
                'UPDATE auth_rate_limit
                 SET window_started_at = :window_started_at,
                     attempt_count = :attempt_count,
                     blocked_until = :blocked_until,
                     updated_at = :updated_at
                 WHERE id = :id'
            );
            $update->execute([
                'window_started_at' => self::formatDateTime($windowStartedAt),
                'attempt_count' => $attemptCount,
                'blocked_until' => $blockedUntil ? self::formatDateTime($blockedUntil) : null,
                'updated_at' => self::formatDateTime($now),
                'id' => (int)$row['id'],
            ]);
            $update->closeCursor();

            if ($ownsTransaction) {
                $pdo->commit();
            }

            if ($blockedUntil) {
                return self::blockedResult($attemptCount, $maximum, $blockedUntil->getTimestamp() - $now->getTimestamp());
            }

            return self::allowedResult($attemptCount, $maximum, 0);
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Authentication rate limit storage error: ' . $exception->getMessage());
            return self::storageErrorResult();
        }
    }

    private static function normalizeScope($scope)
    {
        $scope = strtolower(trim((string)$scope));
        return preg_match('/^[a-z0-9:_-]{1,64}$/', $scope) === 1 ? $scope : '';
    }

    private static function normalizeKeyHash($keyHash)
    {
        $keyHash = strtolower(trim((string)$keyHash));
        return preg_match('/^[a-f0-9]{64}$/', $keyHash) === 1 ? $keyHash : '';
    }

    private static function parseDateTime($value)
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable((string)$value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private static function formatDateTime(\DateTimeInterface $value)
    {
        return $value->format('Y-m-d H:i:s');
    }

    private static function allowedResult($count, $maximum, $retryAfter)
    {
        return [
            'available' => true,
            'allowed' => true,
            'count' => max(0, (int)$count),
            'remaining' => max(0, (int)$maximum - (int)$count),
            'retry_after' => max(0, (int)$retryAfter),
        ];
    }

    private static function blockedResult($count, $maximum, $retryAfter)
    {
        return [
            'available' => true,
            'allowed' => false,
            'count' => max(0, (int)$count),
            'remaining' => 0,
            'retry_after' => max(1, (int)$retryAfter),
        ];
    }

    private static function storageErrorResult()
    {
        return [
            'available' => false,
            'allowed' => false,
            'count' => 0,
            'remaining' => 0,
            'retry_after' => 60,
        ];
    }
}
