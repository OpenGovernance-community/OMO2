<?php
namespace dbObject;

class StatIndicatorGroup extends DbObject
{
    const DISPLAY_OVERLAY = 'overlay';
    const DISPLAY_SUM = 'sum';

    public static function tableName()
    {
        return 'stat_indicator_group';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'name', 'display_mode'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser'], 'fk'],
            [['name', 'display_mode'], 'string'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Contexte',
            'IDuser' => 'Createur',
            'name' => 'Nom',
            'display_mode' => 'Affichage',
            'active' => 'Actif',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function normalizeDisplayMode($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, [self::DISPLAY_OVERLAY, self::DISPLAY_SUM], true)
            ? $value
            : self::DISPLAY_OVERLAY;
    }

    public function save()
    {
        $this->set('display_mode', self::normalizeDisplayMode($this->get('display_mode')));
        return parent::save();
    }

    public function getItems()
    {
        $items = new ArrayStatIndicatorGroupItem();
        $items->loadForGroup((int)$this->getId());
        return $items;
    }

    public function canView()
    {
        $organization = new Organization();
        if (!$organization->load((int)$this->get('IDorganization')) || !$organization->canViewDetail()) {
            return false;
        }

        $holonId = (int)$this->get('IDholon');
        if ($holonId <= 0) {
            return true;
        }

        $holon = new Holon();
        return $holon->load($holonId) && $holon->canViewDetail();
    }
}
?>
