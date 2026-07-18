<?php
namespace dbObject;

class StatIndicatorReferencePoint extends DbObject
{
    public static function tableName()
    {
        return 'stat_indicator_reference_point';
    }

    public static function rules()
    {
        return [
            [['position_percent', 'value'], 'required'],
            [['id'], 'integer'],
            [['IDstatindicator', 'IDstatindicatorgroup'], 'fk'],
            [['position_percent', 'value'], 'float'],
            [['point_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDstatindicator' => 'Indicateur',
            'IDstatindicatorgroup' => 'Groupe',
            'position_percent' => 'Position',
            'value' => 'Valeur de référence',
            'point_at' => 'Date du point',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'position_percent' => 'Les extrémités utilisent 0 et 100 %. Les points intermédiaires sont placés proportionnellement entre leurs dates.',
            'point_at' => 'Obligatoire uniquement pour les points à 0 et 100 %.',
        ];
    }

    public static function getOrder()
    {
        return 'position_percent ASC, id ASC';
    }

    public function save()
    {
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getIndicator()
    {
        $indicator = new \dbObject\StatIndicator();
        return $indicator->load((int)$this->get('IDstatindicator')) ? $indicator : null;
    }

    public function getGroup()
    {
        $group = new \dbObject\StatIndicatorGroup();
        return $group->load((int)$this->get('IDstatindicatorgroup')) ? $group : null;
    }

    public function canView()
    {
        $indicator = $this->getIndicator();
        if ($indicator instanceof \dbObject\StatIndicator) {
            return $indicator->canView();
        }
        $group = $this->getGroup();
        return $group instanceof \dbObject\StatIndicatorGroup && $group->canView();
    }

    public function canEdit()
    {
        $indicator = $this->getIndicator();
        if ($indicator instanceof \dbObject\StatIndicator) {
            return $indicator->canEdit();
        }
        $group = $this->getGroup();
        return $group instanceof \dbObject\StatIndicatorGroup && $group->canEdit();
    }
}

?>
