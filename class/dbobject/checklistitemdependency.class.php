<?php
namespace dbObject;

class ChecklistItemDependency extends DbObject
{
    public static function tableName()
    {
        return 'checklist_item_dependency';
    }

    public static function rules()
    {
        return [
            [['IDchecklistitem', 'IDchecklistitem_required'], 'required'],
            [['id', 'delay_value'], 'integer'],
            [['IDchecklistitem', 'IDchecklistitem_required'], 'fk'],
            [['delay_unit'], 'string'],
            [['created_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklistitem' => 'Etape',
            'IDchecklistitem_required' => 'Etape requise',
            'delay_value' => 'Delai',
            'delay_unit' => 'Unite du delai',
            'created_at' => 'Creation',
        ];
    }

    public static function attributeLength()
    {
        return ['delay_unit' => 20];
    }

    public static function getOrder()
    {
        return 'id ASC';
    }

    public function save()
    {
        $this->set('delay_value', max(0, (int)$this->get('delay_value')));
        $this->set('delay_unit', ChecklistItem::normalizeDelayUnit($this->get('delay_unit')));
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', new \DateTime());
        }
        return parent::save();
    }

    public function getItem()
    {
        $item = new ChecklistItem();
        return $item->load((int)$this->get('IDchecklistitem')) ? $item : null;
    }

    public function getRequiredItem()
    {
        $item = new ChecklistItem();
        return $item->load((int)$this->get('IDchecklistitem_required')) ? $item : null;
    }
}
?>
