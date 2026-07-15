<?php
namespace dbObject;

class StatIndicatorGroupItem extends DbObject
{
    public static function tableName()
    {
        return 'stat_indicator_group_item';
    }

    public static function rules()
    {
        return [
            [['IDstatindicatorgroup', 'IDstatindicator'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDstatindicatorgroup', 'IDstatindicator'], 'fk'],
            [['created_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDstatindicatorgroup' => 'Groupe',
            'IDstatindicator' => 'Indicateur',
            'position' => 'Position',
            'created_at' => 'Creation',
        ];
    }

    public function getIndicator()
    {
        $indicator = new StatIndicator();
        return $indicator->load((int)$this->get('IDstatindicator')) ? $indicator : null;
    }
}
?>
