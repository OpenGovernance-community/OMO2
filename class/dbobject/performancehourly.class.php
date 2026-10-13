<?php
namespace dbObject;

class PerformanceHourly extends DbObject
{
    public const BUCKETS = [50, 100, 250, 500, 1000, 2000, 5000, 10000];
    private const SUMS = ['requests', 'duration_ms', 'cpu_ms', 'cpu_samples', 'sql_ms', 'query_count', 'sql_errors',
        'errors', 'client_errors', 'cron_calls', 'cron_ms', 'b50', 'b100', 'b250', 'b500', 'b1000', 'b2000', 'b5000', 'b10000', 'bover'];
    public static function tableName() { return 'performance_hourly'; }
    public static function rules() { return [
        [['IDorganization', 'IDuser'], 'fk'], [['id', 'cron_state', 'requests', 'cpu_samples', 'query_count', 'sql_errors', 'errors', 'client_errors', 'peak_memory', 'cron_calls', 'b50', 'b100', 'b250', 'b500', 'b1000', 'b2000', 'b5000', 'b10000', 'bover'], 'integer'],
        [['duration_ms', 'max_ms', 'cpu_ms', 'sql_ms', 'cron_ms'], 'float'],
        [['dimension_key', 'route', 'method', 'kind'], 'string'], [['bucket'], 'datetime'],
    ]; }
    public static function attributeLabels() { return ['id' => 'ID', 'route' => 'Page', 'bucket' => 'Heure UTC']; }
    public static function attributeLength() { return ['dimension_key' => 64, 'route' => 190, 'method' => 8, 'kind' => 8]; }
    public static function available(): bool { return self::tableExists(self::tableName()); }

    private static function sampleProjection(): string
    {
        $sql = "DATE_FORMAT(started_at, '%Y-%m-%d %H:00:00') AS bucket, route, method, kind, IDorganization, IDuser, cron_state,
            1 AS requests, duration_ms, duration_ms AS max_ms, COALESCE(cpu_ms, 0) AS cpu_ms,
            (cpu_ms IS NOT NULL) AS cpu_samples, sql_ms, query_count, sql_errors,
            (status_code >= 500) AS errors, (status_code >= 400 AND status_code < 500) AS client_errors,
            peak_memory, cron_calls, cron_ms";
        $previous = -1;
        foreach (self::BUCKETS as $bound) {
            $sql .= ", (duration_ms > $previous AND duration_ms <= $bound) AS b$bound";
            $previous = $bound;
        }
        return $sql . ', (duration_ms > 10000) AS bover';
    }

    private static function totalsSql(): string
    {
        return implode(', ', array_map(static fn($field) => "COALESCE(SUM($field), 0) AS $field", self::SUMS))
            . ', COALESCE(MAX(max_ms), 0) AS max_ms, COALESCE(MAX(peak_memory), 0) AS peak_memory,
            COUNT(DISTINCT NULLIF(IDuser, 0)) AS active_users,
            COALESCE(SUM(CASE WHEN cron_state = 2 THEN requests ELSE 0 END), 0) AS cron_runs,
            COALESCE(SUM(CASE WHEN cron_state = 1 THEN requests ELSE 0 END), 0) AS cron_skips';
    }

    public static function aggregateSelected(string $ids): void
    {
        if (!preg_match('/^\d+(,\d+)*$/D', $ids)) { throw new \InvalidArgumentException('Invalid sample IDs.'); }
        $dimensions = 'route, method, kind, IDorganization, IDuser, cron_state';
        $fields = implode(', ', self::SUMS);
        $sums = implode(', ', array_map(static fn($field) => "SUM($field)", self::SUMS));
        $updates = implode(', ', array_map(static fn($field) => "$field = $field + VALUES($field)", self::SUMS));
        $sql = "INSERT INTO performance_hourly (bucket, dimension_key, $dimensions, $fields, max_ms, peak_memory)
            SELECT bucket, SHA2(JSON_ARRAY($dimensions), 256), $dimensions, $sums, MAX(max_ms), MAX(peak_memory)
            FROM (SELECT " . self::sampleProjection() . " FROM performance_request WHERE id IN ($ids)) samples
            GROUP BY bucket, $dimensions
            ON DUPLICATE KEY UPDATE $updates, max_ms = GREATEST(max_ms, VALUES(max_ms)), peak_memory = GREATEST(peak_memory, VALUES(peak_memory))";
        if (!self::execute($sql)) { throw new \RuntimeException('Unable to aggregate performance samples.'); }
    }

    /** Returns the upper bound of the histogram interval containing the percentile. */
    public static function percentile(array $row, float $fraction = 0.95): float
    {
        $target = (int)ceil((int)($row['requests'] ?? 0) * $fraction);
        if ($target <= 0) { return 0; }
        $count = 0;
        foreach (self::BUCKETS as $bound) {
            $count += (int)($row['b' . $bound] ?? 0);
            if ($count >= $target) { return $bound; }
        }
        return (float)($row['max_ms'] ?? 0);
    }

    public static function dashboard(string $start, string $end, int $organizationId = 0, string $kind = 'web', string $cron = 'all', string $sort = 'slow'): array
    {
        if (!self::available() || !PerformanceRequest::available()) { return ['available' => false]; }
        $kind = in_array($kind, ['web', 'cron', 'task'], true) ? $kind : 'web';
        $params = ['start_raw' => $start, 'end_raw' => $end, 'start_hour' => $start, 'end_hour' => $end, 'kind' => $kind];
        $source = self::dashboardSource();
        $where = 'kind = :kind';
        if ($organizationId > 0) { $where .= ' AND IDorganization = :organization'; $params['organization'] = $organizationId; }
        $where .= match ($cron) { 'none' => ' AND cron_state = 0', 'called' => ' AND cron_state > 0',
            'ran' => ' AND cron_state = 2', 'skipped' => ' AND cron_state = 1', default => '' };
        $base = ' FROM ' . $source . ' WHERE ' . $where;
        $totals = self::totalsSql();
        $query = static function (string $sql, array $values): array {
            $rows = self::fetchAll($sql, $values);
            if ($rows === false) { throw new \RuntimeException('Unable to read performance dashboard.'); }
            return $rows;
        };
        $order = match ($sort) { 'cpu' => 'cpu_ms DESC', 'total' => 'duration_ms DESC', 'volume' => 'requests DESC', default => 'duration_ms / NULLIF(requests, 0) DESC' };
        return ['available' => true,
            'totals' => $query('SELECT ' . $totals . $base, $params)[0],
            'daily' => $query('SELECT DATE(bucket) AS label, ' . $totals . $base . ' GROUP BY DATE(bucket) ORDER BY label', $params),
            'hours' => $query('SELECT HOUR(bucket) AS label, ' . $totals . $base . ' GROUP BY HOUR(bucket) ORDER BY label', $params),
            'routes' => $query('SELECT route AS label, method, ' . $totals . $base . ' GROUP BY route, method ORDER BY ' . $order . ' LIMIT 25', $params),
            'organizations' => $query('SELECT IDorganization AS organization_id, ' . $totals . $base . ' GROUP BY IDorganization ORDER BY duration_ms DESC LIMIT 25', $params),
            'organizationNames' => $query('SELECT id, name FROM organization ORDER BY name', []),
            'collection' => $query('SELECT COUNT(*) AS pending, MIN(started_at) AS oldest FROM performance_request WHERE aggregated = 0', [])[0],
        ];
    }

    /** One statement snapshot includes pending samples without counting aggregated ones twice. */
    private static function dashboardSource(): string
    {
        $fields = 'bucket, route, method, kind, IDorganization, IDuser, cron_state, ' . implode(', ', self::SUMS) . ', max_ms, peak_memory';
        return '(SELECT ' . $fields . ' FROM performance_hourly WHERE bucket >= :start_hour AND bucket < :end_hour
            UNION ALL SELECT ' . $fields . ' FROM (SELECT ' . self::sampleProjection() . ' FROM performance_request
                WHERE aggregated = 0 AND started_at >= :start_raw AND started_at < :end_raw) live) p';
    }

    public static function calDavDashboard(string $start, string $end, int $organizationId = 0, string $sort = 'total', bool $hourly = false): array
    {
        if (!self::available() || !PerformanceRequest::available()) return ['available' => false];
        $params = ['start_raw' => $start, 'end_raw' => $end, 'start_hour' => $start, 'end_hour' => $end,
            'caldav_route' => '/omo/api/caldav/index.php'];
        $base = ' FROM ' . self::dashboardSource() . " WHERE kind = 'web' AND route = :caldav_route";
        if ($organizationId > 0) {
            $base .= ' AND IDorganization = :organization';
            $params['organization'] = $organizationId;
        }
        $totals = self::totalsSql()
            . ", COALESCE(SUM(CASE WHEN method = 'PROPFIND' THEN requests ELSE 0 END), 0) AS propfind"
            . ", COALESCE(SUM(CASE WHEN method IN ('PROPPATC', 'PROPPATCH') THEN requests ELSE 0 END), 0) AS proppatch";
        $query = static function (string $sql) use ($params): array {
            $rows = self::fetchAll($sql, $params);
            if ($rows === false) throw new \RuntimeException('Unable to read CalDAV monitoring.');
            return $rows;
        };
        $bucket = $hourly ? 'bucket' : 'DATE(bucket)';
        $order = match ($sort) { 'volume' => 'requests', 'cpu' => 'cpu_ms', default => 'duration_ms' };
        return ['available' => true,
            'totals' => $query('SELECT ' . $totals . $base)[0],
            'timeline' => $query("SELECT $bucket AS label, $totals $base GROUP BY $bucket ORDER BY label"),
            // Resolve current contact details after aggregation, without multiplying samples by memberships.
            'users' => $query("SELECT ranked.*, TRIM(CONCAT_WS(' ', NULLIF(u.firstname, ''), NULLIF(u.lastname, ''))) AS name,
                u.email FROM (SELECT IDuser AS user_id, $totals $base GROUP BY IDuser
                    ORDER BY $order DESC, requests DESC, IDuser ASC LIMIT 25) ranked
                LEFT JOIN `user` u ON u.id = ranked.user_id
                ORDER BY ranked.$order DESC, ranked.requests DESC, ranked.user_id ASC"),
        ];
    }
}
