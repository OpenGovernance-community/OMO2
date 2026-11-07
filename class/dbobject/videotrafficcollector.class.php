<?php
namespace dbObject;
class VideoTrafficCollector extends DbObject
{
    public static function attributeLabels() { return ['last_success' => 'Dernier releve', 'last_error' => 'Erreur']; }
    public static function tableName() { return 'video_traffic_collector'; }
    public static function rules() { return [[['id', 'observed_endpoints', 'unmatched_rooms'], 'integer'], [['last_success', 'last_attempt'], 'datetime'], [['last_error'], 'string']]; }
    public static function status(): array { return self::fetchRow('SELECT * FROM video_traffic_collector WHERE id = 1') ?: []; }
    public static function success(int $endpoints, int $unmatched): void
    {
        if (!self::execute('UPDATE video_traffic_collector SET last_attempt = UTC_TIMESTAMP(), last_success = UTC_TIMESTAMP(), last_error = NULL, observed_endpoints = :endpoints, unmatched_rooms = :unmatched WHERE id = 1', ['endpoints' => $endpoints, 'unmatched' => $unmatched])) throw new \RuntimeException('Collector status storage failed');
    }
    public static function failure(): void { self::execute("UPDATE video_traffic_collector SET last_attempt = UTC_TIMESTAMP(), last_error = 'Collection failed; see collector logs' WHERE id = 1"); }
}
