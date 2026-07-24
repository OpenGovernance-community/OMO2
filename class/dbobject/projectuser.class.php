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

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        $params = array('organization_id' => (int)$organizationId, 'user_id' => (int)$userId);
        if (!self::execute("DELETE pu FROM project_user pu INNER JOIN project p ON p.id = pu.IDproject WHERE p.IDorganization = :organization_id AND pu.IDuser = :user_id AND p.active = 1 AND COALESCE(p.status, '') != 'done'", $params)) {
            return false;
        }
        return self::execute("UPDATE project_user pu INNER JOIN project p ON p.id = pu.IDproject SET pu.IDuser = :ghost_user_id WHERE p.IDorganization = :organization_id AND pu.IDuser = :user_id", array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId));
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
