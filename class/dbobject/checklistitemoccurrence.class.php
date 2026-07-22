<?php
namespace dbObject;

class ChecklistItemOccurrence extends DbObject
{
    public static function tableName()
    {
        return 'checklist_item_occurrence';
    }

    public static function rules()
    {
        return [
            [['IDchecklistitem', 'scheduled_for'], 'required'],
            [['id'], 'integer'],
            [['IDchecklistitem', 'IDproject'], 'fk'],
            [['scheduled_for', 'created_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklistitem' => 'Element de checklist',
            'scheduled_for' => 'Occurrence prevue',
            'IDproject' => 'Projet genere',
            'created_at' => 'Creation',
        ];
    }

    public function save()
    {
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', new \DateTime());
        }
        return parent::save();
    }

    public function getProject()
    {
        $project = new Project();
        return $project->load((int)$this->get('IDproject')) ? $project : null;
    }
}
?>
