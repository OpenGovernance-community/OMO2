<?php
namespace dbObject;

class PerformanceRequest extends DbObject
{
    public static function tableName() { return 'performance_request'; }
    public static function rules() { return [
        [['IDorganization', 'IDuser'], 'fk'],
        [['id', 'status_code', 'query_count', 'sql_errors', 'peak_memory', 'cron_state', 'cron_calls', 'aggregated'], 'integer'],
        [['duration_ms', 'cpu_ms', 'sql_ms', 'cron_ms'], 'float'],
        [['request_id', 'parent_request_id', 'route', 'method', 'kind'], 'string'], [['started_at'], 'datetime'],
    ]; }
    public static function attributeLabels() { return ['id' => 'ID', 'route' => 'Page', 'started_at' => 'Date UTC']; }
    public static function attributeLength() { return ['route' => 190, 'method' => 8, 'kind' => 8, 'request_id' => 32, 'parent_request_id' => 32]; }
    public static function available(): bool { return self::tableExists(self::tableName()); }

    public static function record(array $data): bool
    {
        $fields = ['request_id', 'parent_request_id', 'started_at', 'route', 'method', 'kind', 'IDorganization', 'IDuser',
            'status_code', 'duration_ms', 'cpu_ms', 'sql_ms', 'query_count', 'sql_errors', 'peak_memory', 'cron_state', 'cron_calls', 'cron_ms'];
        $data += ['parent_request_id' => '', 'IDorganization' => 0, 'IDuser' => 0, 'status_code' => 200,
            'cpu_ms' => null, 'sql_ms' => 0, 'query_count' => 0, 'sql_errors' => 0, 'peak_memory' => 0,
            'cron_state' => 0, 'cron_calls' => 0, 'cron_ms' => 0];
        $pdo = self::getPdo();
        if (!$pdo || $pdo->inTransaction()) { return false; }
        // Dedicated insert: no object history, recursive profiling or application transaction changes.
        $statement = $pdo->prepare('INSERT INTO performance_request (`' . implode('`,`', $fields) . '`)
            VALUES (:' . implode(',:', $fields) . ')');
        return $statement->execute(array_intersect_key($data, array_flip($fields)));
    }

    /** Mark exactly the selected rows in the same transaction as their additive aggregates. */
    public static function aggregate(int $batchSize = 5000, int $batches = 10): int
    {
        if (!self::available() || !PerformanceHourly::available()) { return 0; }
        $pdo = self::getPdo();
        if (!$pdo || $pdo->inTransaction()) { return 0; }
        $lock = 'performance-' . substr(hash('sha256', (string)($GLOBALS['dbName'] ?? '')), 0, 40);
        if ((int)self::fetchValue('SELECT GET_LOCK(:name, 0)', ['name' => $lock]) !== 1) { return 0; }
        $processed = 0;
        try {
            for ($i = 0; $i < max(1, min(10, $batches)); $i++) {
                $pdo->beginTransaction();
                $rows = self::fetchAll('SELECT id FROM performance_request WHERE aggregated = 0 ORDER BY id LIMIT '
                    . max(1, min(5000, $batchSize)) . ' FOR UPDATE');
                if ($rows === false) { throw new \RuntimeException('Unable to select performance samples.'); }
                if (!$rows) { $pdo->commit(); break; }
                $ids = implode(',', array_map(static fn($row) => (int)$row['id'], $rows));
                PerformanceHourly::aggregateSelected($ids);
                if (!self::execute('UPDATE performance_request SET aggregated = 1 WHERE id IN (' . $ids . ')')) {
                    throw new \RuntimeException('Unable to mark performance samples.');
                }
                $pdo->commit();
                $processed += count($rows);
            }
            // Never delete samples which have not been aggregated, even after a cron outage.
            if (!self::execute('DELETE FROM performance_request WHERE aggregated = 1 AND started_at < UTC_TIMESTAMP() - INTERVAL 30 DAY LIMIT 10000')
                || !self::execute('DELETE FROM performance_hourly WHERE bucket < UTC_TIMESTAMP() - INTERVAL 13 MONTH LIMIT 10000')) {
                throw new \RuntimeException('Unable to prune performance history.');
            }
        } finally {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            self::fetchValue('SELECT RELEASE_LOCK(:name)', ['name' => $lock]);
        }
        return $processed;
    }
}
