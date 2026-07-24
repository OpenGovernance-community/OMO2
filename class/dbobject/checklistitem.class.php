<?php
namespace dbObject;

class ChecklistItem extends DbObject
{
    public const ACTIVATION_IMMEDIATE = 'immediate';
    public const ACTIVATION_AFTER_START = 'after_start';
    public const ACTIVATION_AFTER_COMPLETION = 'after_completion';
    public const ACTIVATION_MANUAL = 'manual';

    public const DELAY_DAY = 'day';
    public const DELAY_WEEK = 'week';
    public const DELAY_MONTH = 'month';

    public static function tableName()
    {
        return 'checklist_item';
    }

    public static function rules()
    {
        return [
            [['IDchecklist', 'IDproject_template', 'stable_key', 'activation_type'], 'required'],
            [['id', 'delay_value', 'display_lead_value', 'execution_duration_value', 'position'], 'integer'],
            [['IDchecklist', 'IDproject_template'], 'fk'],
            [['stable_key', 'activation_type', 'delay_unit', 'display_lead_unit', 'execution_duration_unit'], 'string'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklist' => 'Checklist',
            'IDproject_template' => 'Projet modele',
            'stable_key' => 'Cle stable',
            'activation_type' => 'Activation',
            'delay_value' => 'Delai',
            'delay_unit' => 'Unite du delai',
            'display_lead_value' => 'Affichage anticipe',
            'display_lead_unit' => 'Unite d affichage anticipe',
            'execution_duration_value' => 'Delai de realisation',
            'execution_duration_unit' => 'Unite du delai de realisation',
            'position' => 'Position',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeLength()
    {
        return [
            'stable_key' => 64,
            'activation_type' => 30,
            'delay_unit' => 20,
            'display_lead_unit' => 20,
            'execution_duration_unit' => 20,
        ];
    }

    public static function attributeValues()
    {
        return [
            'activation_type' => [
                [self::ACTIVATION_IMMEDIATE, 'Immediate'],
                [self::ACTIVATION_AFTER_START, 'Apres le demarrage'],
                [self::ACTIVATION_AFTER_COMPLETION, 'Apres une etape'],
                [self::ACTIVATION_MANUAL, 'Manuelle'],
            ],
            'delay_unit' => [
                ['', 'Aucune'],
                [self::DELAY_DAY, 'Jour'],
                [self::DELAY_WEEK, 'Semaine'],
                [self::DELAY_MONTH, 'Mois'],
            ],
        ];
    }

    public static function activationTypes()
    {
        return [
            self::ACTIVATION_IMMEDIATE,
            self::ACTIVATION_AFTER_START,
            self::ACTIVATION_AFTER_COMPLETION,
            self::ACTIVATION_MANUAL,
        ];
    }

    public static function delayUnits()
    {
        return [self::DELAY_DAY, self::DELAY_WEEK, self::DELAY_MONTH];
    }

    public static function normalizeActivationType($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::activationTypes(), true) ? $value : self::ACTIVATION_IMMEDIATE;
    }

    public static function normalizeDelayUnit($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::delayUnits(), true) ? $value : null;
    }

    public static function shiftDate(\DateTimeImmutable $reference, $value, $unit)
    {
        $value = (int)$value;
        if ($value === 0) {
            return $reference;
        }
        $unit = self::normalizeDelayUnit($unit) ?: self::DELAY_DAY;
        $modifierUnit = $unit === self::DELAY_WEEK
            ? 'weeks'
            : ($unit === self::DELAY_MONTH ? 'months' : 'days');
        $shifted = $reference->modify(($value > 0 ? '+' : '') . $value . ' ' . $modifierUnit);
        return $shifted instanceof \DateTimeImmutable ? $shifted : $reference;
    }

    public function calculateActivationAt(\DateTimeImmutable $reference, \DateTimeImmutable $createdAt)
    {
        $activationType = self::normalizeActivationType($this->get('activation_type'));
        $plannedStartAt = $this->calculatePlannedStartAt($reference);
        if (!($plannedStartAt instanceof \DateTimeImmutable)) {
            return null;
        }

        if ($this->getDisplayLeadValue() > 0) {
            return self::shiftDate($plannedStartAt, -$this->getDisplayLeadValue(), $this->getDisplayLeadUnit());
        }

        return $activationType === self::ACTIVATION_IMMEDIATE ? $createdAt : $plannedStartAt;
    }

    public function calculatePlannedStartAt(\DateTimeImmutable $reference)
    {
        $activationType = self::normalizeActivationType($this->get('activation_type'));
        if ($activationType === self::ACTIVATION_IMMEDIATE) {
            return $reference;
        }
        if ($activationType === self::ACTIVATION_AFTER_START) {
            return self::shiftDate($reference, $this->get('delay_value'), $this->get('delay_unit'));
        }
        return null;
    }

    public function getDisplayLeadValue()
    {
        return max(0, (int)$this->get('display_lead_value'));
    }

    public function getDisplayLeadUnit()
    {
        return self::normalizeDelayUnit($this->get('display_lead_unit')) ?: self::DELAY_DAY;
    }

    public function getExecutionDurationValue()
    {
        return max(0, (int)$this->get('execution_duration_value'));
    }

    public function getExecutionDurationUnit()
    {
        return self::normalizeDelayUnit($this->get('execution_duration_unit')) ?: self::DELAY_DAY;
    }

    public function getDeadlineAt(\DateTimeImmutable $plannedStartAt)
    {
        $duration = $this->getExecutionDurationValue();
        return $duration > 0
            ? self::shiftDate($plannedStartAt, $duration, $this->getExecutionDurationUnit())
            : null;
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public function save()
    {
        $this->set('stable_key', trim((string)$this->get('stable_key')));
        $this->set('activation_type', self::normalizeActivationType($this->get('activation_type')));
        $this->set('delay_value', max(-3650, min(3650, (int)$this->get('delay_value'))));
        $this->set('delay_unit', self::normalizeDelayUnit($this->get('delay_unit')));
        $displayLeadValue = max(0, min(3650, (int)$this->get('display_lead_value')));
        $executionDurationValue = max(0, min(3650, (int)$this->get('execution_duration_value')));
        $this->set('display_lead_value', $displayLeadValue);
        $this->set('display_lead_unit', $displayLeadValue > 0 ? (self::normalizeDelayUnit($this->get('display_lead_unit')) ?: self::DELAY_DAY) : null);
        $this->set('execution_duration_value', $executionDurationValue);
        $this->set('execution_duration_unit', $executionDurationValue > 0 ? (self::normalizeDelayUnit($this->get('execution_duration_unit')) ?: self::DELAY_DAY) : null);
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getProjectTemplate()
    {
        $project = new Project();
        return $project->load((int)$this->get('IDproject_template')) ? $project : null;
    }

    public function getChecklist()
    {
        $checklist = new Checklist();
        return $checklist->load((int)$this->get('IDchecklist')) ? $checklist : null;
    }

    public function getDependencies()
    {
        $items = new ArrayChecklistItemDependency();
        $items->loadForItem((int)$this->getId());
        return $items;
    }

    public function getRecurrence()
    {
        $recurrences = new ArrayChecklistItemRecurrence();
        $recurrences->loadForItem((int)$this->getId());
        foreach ($recurrences as $recurrence) {
            return $recurrence instanceof ChecklistItemRecurrence ? $recurrence : null;
        }
        return null;
    }
}
?>
