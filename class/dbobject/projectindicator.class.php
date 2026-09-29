<?php
namespace dbObject;

class ProjectIndicator extends DbObject
{
    public static function tableName() { return 'project_indicator'; }

    public static function rules()
    {
        return [
            [['IDproject', 'IDstatindicator'], 'required'],
            [['id'], 'integer'],
            [['IDproject', 'IDstatindicator'], 'fk'],
            [['datecreation'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDproject' => 'Projet', 'IDstatindicator' => 'Indicateur', 'datecreation' => 'Date d ajout'];
    }

    public function save()
    {
        if ((int)$this->getId() <= 0 && !($this->get('datecreation') instanceof \DateTimeInterface)) {
            $this->set('datecreation', new \DateTime());
        }
        return parent::save();
    }
}
