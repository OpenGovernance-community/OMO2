<?php
namespace dbObject;

class ScrumProject extends DbObject
{
    public static function tableName() { return 'scrum_project'; }
    public static function rules() {
        return [
            [['id', 'project_key', 'parent_key', 'points', 'own_points'], 'integer'],
            [['IDsprint', 'IDproject'], 'fk'],
            [['snapshot'], 'text'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels() {
        return ['IDsprint' => 'Sprint', 'IDproject' => 'Projet', 'project_key' => 'Projet original',
            'parent_key' => 'Parent original', 'points' => 'Estimation', 'own_points' => 'Charge propre', 'snapshot' => 'Etat conserve'];
    }
    public static function forSprint(int $id): array {
        $items = [];
        foreach (self::fetchAll('SELECT id FROM scrum_project WHERE IDsprint = :id ORDER BY id', ['id' => $id]) ?: [] as $row) {
            $item = new self();
            if ($item->load((int)$row['id'], true)) { $items[] = $item; }
        }
        return $items;
    }
    public static function clearSprint(int $id): void {
        if (!self::execute('DELETE FROM scrum_project WHERE IDsprint = :id', ['id' => $id])) { throw new \RuntimeException('save'); }
    }
}
