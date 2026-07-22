<?php
namespace dbObject;

class ChecklistTrigger extends DbObject
{
    public const TYPE_MANUAL = 'manual';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_CONTAINER = 'container';

    public const OVERLAP_CREATE_NEW = 'create_new';
    public const OVERLAP_REUSE_OPEN = 'reuse_open';
    public const OVERLAP_SKIP = 'skip';
    public const OVERLAP_ASK = 'ask';

    public static function tableName()
    {
        return 'checklist_trigger';
    }

    public static function rules()
    {
        return [
            [['IDchecklist', 'stable_key', 'trigger_type', 'overlap_policy'], 'required'],
            [['id'], 'integer'],
            [['IDchecklist'], 'fk'],
            [['stable_key', 'trigger_type', 'frequency', 'schedule', 'overlap_policy'], 'string'],
            [['enabled'], 'boolean'],
            [['next_trigger_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklist' => 'Checklist',
            'stable_key' => 'Cle stable',
            'trigger_type' => 'Type de declenchement',
            'frequency' => 'Frequence',
            'schedule' => 'Moment attendu',
            'next_trigger_at' => 'Prochain declenchement',
            'overlap_policy' => 'Si une execution est ouverte',
            'enabled' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeLength()
    {
        return [
            'stable_key' => 64,
            'trigger_type' => 20,
            'frequency' => 20,
            'schedule' => 20,
            'overlap_policy' => 20,
        ];
    }

    public static function attributeValues()
    {
        $frequencyValues = [['', 'Aucune']];
        foreach (RecurrenceSchedule::getFrequencyCatalog() as $frequency) {
            $frequencyValues[] = [$frequency, $frequency];
        }

        return [
            'trigger_type' => [
                [self::TYPE_MANUAL, 'Manuel'],
                [self::TYPE_SCHEDULED, 'Planifie'],
                [self::TYPE_CONTAINER, 'Conteneur'],
            ],
            'frequency' => $frequencyValues,
            'overlap_policy' => [
                [self::OVERLAP_CREATE_NEW, 'Creer une nouvelle execution'],
                [self::OVERLAP_REUSE_OPEN, 'Reutiliser l execution ouverte'],
                [self::OVERLAP_SKIP, 'Ignorer la nouvelle occurrence'],
                [self::OVERLAP_ASK, 'Demander'],
            ],
        ];
    }

    public static function triggerTypes()
    {
        return [self::TYPE_MANUAL, self::TYPE_SCHEDULED, self::TYPE_CONTAINER];
    }

    public static function overlapPolicies()
    {
        return [
            self::OVERLAP_CREATE_NEW,
            self::OVERLAP_REUSE_OPEN,
            self::OVERLAP_SKIP,
            self::OVERLAP_ASK,
        ];
    }

    public static function normalizeTriggerType($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::triggerTypes(), true) ? $value : self::TYPE_MANUAL;
    }

    public static function normalizeOverlapPolicy($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::overlapPolicies(), true) ? $value : self::OVERLAP_CREATE_NEW;
    }

    public static function getOrder()
    {
        return 'created_at ASC, id ASC';
    }

    public function save()
    {
        $triggerType = self::normalizeTriggerType($this->get('trigger_type'));
        $this->set('stable_key', trim((string)$this->get('stable_key')));
        $this->set('trigger_type', $triggerType);
        $this->set('overlap_policy', self::normalizeOverlapPolicy($this->get('overlap_policy')));

        if ($triggerType === self::TYPE_SCHEDULED) {
            $frequency = RecurrenceSchedule::normalizeFrequency($this->get('frequency'));
            $this->set('frequency', $frequency);
            $this->set('schedule', RecurrenceSchedule::normalizeSchedule($frequency, $this->get('schedule')));
        } else {
            $this->set('frequency', null);
            $this->set('schedule', null);
            $this->set('next_trigger_at', null);
        }
        if ($triggerType === self::TYPE_CONTAINER) {
            $this->set('enabled', 0);
        }

        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getChecklist()
    {
        $checklist = new Checklist();
        return $checklist->load((int)$this->get('IDchecklist')) ? $checklist : null;
    }
}
?>
