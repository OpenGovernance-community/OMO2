<?php
namespace dbObject;

/** Sampled JVB packet counters. Attribution is copied at collection time, without cascading deletion. */
class VideoTraffic extends DbObject
{
    public static function tableName() { return 'video_traffic'; }
    public static function rules() { return [[['endpoint_key', 'room_code'], 'string'], [['IDuser', 'IDorganization'], 'fk'], [['sampled_at'], 'datetime'], [['received_total', 'sent_total', 'received_bytes', 'sent_bytes'], 'integer']]; }
    public static function attributeLabels() { return ['received_bytes' => 'Octets recus', 'sent_bytes' => 'Octets envoyes']; }

    // Sender branches share one counter. Taking the maximum avoids counting it several times.
    private static function packetBytes(array $tree): ?int
    {
        $values = [];
        foreach ($tree as $key => $value) {
            if (!is_array($value)) continue;
            if (str_contains((string)$key, 'PacketStreamStats')) {
                $bytes = $value['bytes_sent'] ?? $value['bytes'] ?? null;
                if (is_int($bytes) && $bytes >= 0) $values[] = $bytes;
            }
            $nested = self::packetBytes($value);
            if ($nested !== null) $values[] = $nested;
        }
        return $values ? max($values) : null;
    }
    public static function observations(array $payload, string $bridge): array
    {
        if (!isset($payload['conferences']) || !is_array($payload['conferences'])) throw new \RuntimeException('Unsupported JVB response');
        $result = [];
        foreach ($payload['conferences'] as $conferenceId => $conference) {
            if (!is_array($conference) || !preg_match('/\Aomo-([a-f0-9]{32})(?:@|\z)/D', (string)($conference['name'] ?? ''), $match)) continue;
            foreach ($conference['endpoints'] ?? [] as $endpointId => $endpoint) {
                if (!is_array($endpoint)) throw new \RuntimeException('JVB full counters unavailable');
                $received = self::packetBytes($endpoint['transceiver']['receiver'] ?? []);
                $sent = self::packetBytes($endpoint['transceiver']['sender'] ?? []);
                if ($received === null || $sent === null) throw new \RuntimeException('Unsupported JVB packet counter format');
                $result[] = ['key' => hash('sha256', $bridge . ':' . $conferenceId . ':' . ($conference['creation_time'] ?? '') . ':' . $endpointId),
                    'code' => $match[1], 'received' => $received, 'sent' => $sent];
            }
        }
        return $result;
    }
    public static function ingest(array $observations): array
    {
        $pdo = self::getPdo();
        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) $pdo->beginTransaction();
        try {
            // One collector lock also protects counter checkpoints from concurrent/repeated polls.
            self::fetchRow('SELECT id FROM video_traffic_collector WHERE id = 1 FOR UPDATE');
            $count = 0; $unmatched = 0;
            foreach ($observations as $observation) {
                $room = self::fetchRow('SELECT IDuser, IDorganization FROM video_room WHERE code = :code', ['code' => $observation['code']]);
                if (!$room) { $unmatched++; continue; }
                $previous = self::fetchRow('SELECT received_total, sent_total FROM video_traffic WHERE endpoint_key = :key ORDER BY id DESC LIMIT 1', ['key' => $observation['key']]);
                $received = max((int)($previous['received_total'] ?? 0), $observation['received']);
                $sent = max((int)($previous['sent_total'] ?? 0), $observation['sent']);
                $deltaIn = $received - (int)($previous['received_total'] ?? 0);
                $deltaOut = $sent - (int)($previous['sent_total'] ?? 0);
                if ($deltaIn === 0 && $deltaOut === 0) continue;
                if (!self::execute('INSERT INTO video_traffic (endpoint_key, room_code, IDuser, IDorganization, sampled_at, received_total, sent_total, received_bytes, sent_bytes)
                    VALUES (:key, :code, :user, :org, UTC_TIMESTAMP(), :received, :sent, :deltaIn, :deltaOut)',
                    ['key' => $observation['key'], 'code' => $observation['code'], 'user' => $room['IDuser'], 'org' => $room['IDorganization'],
                    'received' => $received, 'sent' => $sent, 'deltaIn' => $deltaIn, 'deltaOut' => $deltaOut])) throw new \RuntimeException('Traffic storage failed');
                $count++;
            }
            VideoTrafficCollector::success(count($observations), $unmatched);
            if ($ownTransaction) $pdo->commit();
            return ['samples' => $count, 'endpoints' => count($observations), 'unmatched' => $unmatched];
        } catch (\Throwable $e) { if ($ownTransaction && $pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    public static function dashboard(string $start, string $end, string $group, int $organizationId): array
    {
        if (!self::tableExists(self::tableName())) return ['available' => false, 'rows' => [], 'status' => []];
        $byOrg = $group === 'organization';
        $field = $byOrg ? 't.IDorganization' : 't.IDuser';
        $name = $byOrg ? 'o.name' : "CONCAT_WS(' ', u.firstname, u.lastname)";
        $where = 't.sampled_at >= :start AND t.sampled_at < :end';
        $params = ['start' => $start, 'end' => $end];
        if ($organizationId > 0) { $where .= ' AND t.IDorganization = :org'; $params['org'] = $organizationId; }
        $rows = self::fetchAll("SELECT $field AS owner_id, $name AS name, SUM(t.received_bytes) AS received_bytes,
            SUM(t.sent_bytes) AS sent_bytes, COUNT(DISTINCT t.room_code) AS rooms FROM video_traffic t
            LEFT JOIN user u ON u.id = t.IDuser LEFT JOIN organization o ON o.id = t.IDorganization
            WHERE $where GROUP BY $field, $name ORDER BY sent_bytes DESC", $params);
        return ['available' => true, 'rows' => $rows ?: [], 'status' => VideoTrafficCollector::status()];
    }
}
