<?php
namespace dbObject;

class ProjectRecurringTask extends DbObject
{
    public static function tableName() { return 'project_recurring_task'; }

    public static function rules()
    {
        return [
            [['IDproject', 'IDrecurringtask'], 'required'],
            [['id'], 'integer'],
            [['IDproject', 'IDrecurringtask'], 'fk'],
            [['datecreation'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDproject' => 'Projet', 'IDrecurringtask' => 'Tache recurrente', 'datecreation' => 'Date d ajout'];
    }

    public function save()
    {
        if ((int)$this->getId() <= 0 && !($this->get('datecreation') instanceof \DateTimeInterface)) {
            $this->set('datecreation', new \DateTime());
        }
        return parent::save();
    }
}
