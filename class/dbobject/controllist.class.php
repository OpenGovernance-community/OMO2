<?php
namespace dbObject;

class ControlList extends DbObject
{
    public static function tableName()
    {
        return 'control_list';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'IDholon', 'title'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon'], 'fk'],
            [['title'], 'string'],
            [['description'], 'text'],
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
            'IDholon' => 'Holon',
            'title' => 'Titre',
            'description' => 'Description',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeLength()
    {
        return ['title' => 255];
    }

    public static function getOrder()
    {
        return 'title ASC, id ASC';
    }

    public function save()
    {
        $this->set('title', trim((string)$this->get('title')));
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getOrganization()
    {
        $organization = new Organization();
        return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }

    public function getHolon()
    {
        $holon = new Holon();
        return $holon->load((int)$this->get('IDholon')) ? $holon : null;
    }

    public function getTasks($activeOnly = true)
    {
        $tasks = new ArrayControlTask();
        $tasks->loadForList((int)$this->getId(), (bool)$activeOnly);
        return $tasks;
    }

    public function deleteWithRelatedData()
    {
        foreach ($this->getTasks(false) as $task) {
            if ($task instanceof ControlTask && !$task->deleteWithRelatedData()) {
                return false;
            }
        }
        return $this->delete();
    }
}
?>
