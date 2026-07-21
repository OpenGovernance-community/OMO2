<?php
namespace dbObject;

class ProjectUser extends DbObject
{
    public static function tableName()
    {
        return 'project_user';
    }

    public static function rules()
    {
        return [
            [['IDproject', 'IDuser'], 'required'],
            [['id'], 'integer'],
            [['IDproject', 'IDuser'], 'fk'],
            [['datecreation'], 'datetime'],
            [['active'], 'boolean'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDproject' => 'Projet',
            'IDuser' => 'Personne',
            'datecreation' => 'Date d ajout',
            'active' => 'Actif',
        ];
    }

    public static function getOrder()
    {
        return 'datecreation ASC, id ASC';
    }

    public function save()
    {
        if ((int)$this->getId() <= 0 && !($this->get('datecreation') instanceof \DateTimeInterface)) {
            $this->set('datecreation', new \DateTime());
        }

        return parent::save();
    }

    public function getProject()
    {
        $project = new Project();
        return $project->load((int)$this->get('IDproject')) ? $project : null;
    }

    public function getUser()
    {
        $user = new User();
        return $user->load((int)$this->get('IDuser')) ? $user : null;
    }
}
?>
