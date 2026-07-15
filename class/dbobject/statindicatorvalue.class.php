<?php
namespace dbObject;

class StatIndicatorValue extends DbObject
{
    public static function tableName()
    {
        return 'stat_indicator_value';
    }

    public static function rules()
    {
        return [
            [['IDstatindicator', 'value', 'measured_at'], 'required'],
            [['id'], 'integer'],
            [['IDstatindicator', 'IDuser'], 'fk'],
            [['value'], 'float'],
            [['measured_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDstatindicator' => 'Indicateur',
            'IDuser' => 'Auteur',
            'value' => 'Valeur',
            'measured_at' => 'Date de la mesure',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'measured_at' => 'Date et heure auxquelles cette valeur a été constatée.',
        ];
    }

    public static function getOrder()
    {
        return 'measured_at ASC, id ASC';
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
