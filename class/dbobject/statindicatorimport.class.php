<?php
namespace dbObject;

class StatIndicatorImport extends DbObject
{
    public static function tableName()
    {
        return 'stat_indicator_import';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'IDstatindicator'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDstatindicator'], 'fk'],
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
            'IDstatindicator' => 'Indicateur',
            'active' => 'Actif',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public function getIndicator()
    {
        $indicator = new StatIndicator();
        return $indicator->load((int)$this->get('IDstatindicator')) ? $indicator : null;
    }

    public function canView()
    {
        $indicator = $this->getIndicator();
        return $indicator instanceof StatIndicator && $indicator->canView();
    }
}
?>
