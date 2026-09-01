<?php
namespace dbObject;

class ControlTaskCheck extends DbObject
{
    public static function tableName()
    {
        return 'control_task_check';
    }

    public static function rules()
    {
        return [
            [['IDcontroltask', 'scheduled_for', 'checked_at', 'IDuser'], 'required'],
            [['id'], 'integer'],
            [['IDcontroltask', 'IDuser'], 'fk'],
            [['scheduled_for', 'checked_at', 'created_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDcontroltask' => 'Activite',
            'IDuser' => 'Personne',
            'scheduled_for' => 'Occurrence prevue',
            'checked_at' => 'Validee le',
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

    public function getUser()
    {
        $user = new User();
        return $user->load((int)$this->get('IDuser')) ? $user : null;
    }
}
?>
