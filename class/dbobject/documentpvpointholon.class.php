<?php
namespace dbObject;

class DocumentPvPointHolon extends DbObject
{
    public static function tableName()
    {
        return 'document_pv_point_holon';
    }

    public static function rules()
    {
        return [
            [['IDdocument_pv_point', 'IDholon'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDdocument_pv_point', 'IDholon'], 'fk'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdocument_pv_point' => 'Point PV',
            'IDholon' => 'Holon adresse',
            'position' => 'Ordre',
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public function save()
    {
        if ((int)$this->get('IDdocument_pv_point') <= 0 || (int)$this->get('IDholon') <= 0) {
            return [
                'status' => false,
                'text' => 'Le lien point/holon est incomplet.',
            ];
        }

        if ((int)$this->get('position') < 0) {
            $this->set('position', 0);
        }

        return parent::save();
    }
}

?>
