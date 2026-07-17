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
            [['IDstatindicator', 'position_percent', 'value'], 'required'],
            [['id'], 'integer'],
            [['IDstatindicator'], 'fk'],
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

    public function canView()
    {
        $indicator = $this->getIndicator();
        return $indicator instanceof \dbObject\StatIndicator && $indicator->canView();
    }

    public function canEdit()
    {
        $indicator = $this->getIndicator();
        return $indicator instanceof \dbObject\StatIndicator && $indicator->canEdit();
    }
}

?>
