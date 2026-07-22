<?php
namespace dbObject;

class ChecklistRun extends DbObject
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR = 'error';

    public static function tableName()
    {
        return 'checklist_run';
    }

    public static function rules()
    {
        return [
            [['IDchecklist', 'IDorganization', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDchecklist', 'IDchecklisttrigger', 'IDorganization', 'IDholon', 'IDproject_root', 'IDuser_created'], 'fk'],
            [['status'], 'string'],
            [['scheduled_for', 'created_at', 'updated_at', 'completed_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklist' => 'Checklist',
            'IDchecklisttrigger' => 'Declencheur',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Contexte',
            'IDproject_root' => 'Projet racine',
            'IDuser_created' => 'Declenchee par',
            'scheduled_for' => 'Date de reference',
            'status' => 'Statut',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
            'completed_at' => 'Terminee le',
        ];
    }

    public static function attributeLength()
    {
        return ['status' => 20];
    }

    public static function attributeValues()
    {
        return [
            'status' => [
                [self::STATUS_RUNNING, 'En cours'],
                [self::STATUS_COMPLETED, 'Terminee'],
                [self::STATUS_CANCELLED, 'Annulee'],
                [self::STATUS_ERROR, 'Erreur'],
            ],
        ];
    }

    public static function statuses()
    {
        return [self::STATUS_RUNNING, self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_ERROR];
    }

    public static function normalizeStatus($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::statuses(), true) ? $value : self::STATUS_RUNNING;
    }

    public static function getOrder()
    {
        return 'created_at DESC, id DESC';
    }

    public function save()
    {
        $status = self::normalizeStatus($this->get('status'));
        $this->set('status', $status);
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        if ($status === self::STATUS_COMPLETED && !($this->get('completed_at') instanceof \DateTimeInterface)) {
            $this->set('completed_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getItems()
    {
        $items = new ArrayChecklistRunItem();
        $items->loadForRun((int)$this->getId());
        return $items;
    }

    public function getChecklist()
    {
        $checklist = new Checklist();
        return $checklist->load((int)$this->get('IDchecklist')) ? $checklist : null;
    }

    public function getTrigger()
    {
        $triggerId = (int)$this->get('IDchecklisttrigger');
        $trigger = new ChecklistTrigger();
        return $triggerId > 0 && $trigger->load($triggerId) ? $trigger : null;
    }

    public function getRootProject()
    {
        $projectId = (int)$this->get('IDproject_root');
        $project = new Project();
        return $projectId > 0 && $project->load($projectId) ? $project : null;
    }

    public function getReferenceAt()
    {
        return $this->get('scheduled_for');
    }

    public function syncStatusFromRootProject()
    {
        if (self::normalizeStatus($this->get('status')) !== self::STATUS_RUNNING) {
            return false;
        }
        $rootProject = $this->getRootProject();
        if (!($rootProject instanceof Project) || Project::normalizeStatus($rootProject->get('status')) !== Project::STATUS_DONE) {
            return false;
        }
        $this->set('status', self::STATUS_COMPLETED);
        $result = $this->save();
        return is_array($result) && !empty($result['status']);
    }

    public static function syncRunningBatch($limit = 100)
    {
        $runs = new ArrayChecklistRun();
        $runs->loadRunning($limit);
        $updatedCount = 0;
        foreach ($runs as $run) {
            if ($run instanceof self && $run->syncStatusFromRootProject()) {
                $updatedCount++;
            }
        }
        return $updatedCount;
    }
}
?>
