<?php
namespace dbObject;

class DocumentPvPointTension extends DbObject
{
    public static function tableName()
    {
        return 'document_pv_point_tension';
    }

    public static function rules()
    {
        return [
            [['IDdocument_pv_point', 'IDtension'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDdocument_pv_point', 'IDtension'], 'fk'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdocument_pv_point' => 'Point PV',
            'IDtension' => 'Tension adressee',
            'position' => 'Ordre',
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public function save()
    {
        if ((int)$this->get('IDdocument_pv_point') <= 0 || (int)$this->get('IDtension') <= 0) {
            return [
                'status' => false,
                'text' => 'Le lien point/tension est incomplet.',
            ];
        }

        if ((int)$this->get('position') < 0) {
            $this->set('position', 0);
        }

        return parent::save();
    }
}

?>
