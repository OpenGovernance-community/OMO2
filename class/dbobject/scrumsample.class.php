<?php
namespace dbObject;

class ScrumSample extends DbObject
{
    public static function tableName() { return 'scrum_sample'; }
    public static function rules() {
        return [[['id', 'remaining'], 'integer'], [['IDsprint'], 'fk'], [['sampled_at'], 'datetime'], [['source'], 'string'], [['id'], 'safe']];
    }
    public static function attributeLabels() {
        return ['IDsprint' => 'Sprint', 'sampled_at' => 'Mesure', 'remaining' => 'Charge restante', 'source' => 'Origine'];
    }
    public static function attributeLength() { return ['source' => 20]; }
    public static function forSprint(int $id): array {
        return self::fetchAll('SELECT sampled_at, remaining, source FROM scrum_sample WHERE IDsprint = :id ORDER BY sampled_at, id', ['id' => $id]) ?: [];
    }
    public static function hasDaily(int $id, \DateTimeImmutable $at): bool {
        return (bool)self::fetchRow("SELECT id FROM scrum_sample WHERE IDsprint=:id AND source='daily' AND sampled_at>=:start AND sampled_at<:end LIMIT 1",
            ['id' => $id, 'start' => $at->format('Y-m-d') . ' 00:00:00', 'end' => $at->modify('+1 day')->format('Y-m-d') . ' 00:00:00']);
    }
    public static function clearSprint(int $id): void {
        if (!self::execute('DELETE FROM scrum_sample WHERE IDsprint = :id', ['id' => $id])) { throw new \RuntimeException('save'); }
    }
}
