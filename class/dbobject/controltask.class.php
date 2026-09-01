<?php
namespace dbObject;

class ControlTask extends DbObject
{
    public const DELAY_DAY = 'day';
    public const DELAY_WEEK = 'week';
    public const DELAY_MONTH = 'month';

    public static function tableName()
    {
        return 'control_task';
    }

    public static function rules()
    {
        return [
            [['IDcontrollist', 'title', 'frequency', 'schedule'], 'required'],
            [['id', 'display_lead_value', 'execution_duration_value', 'position'], 'integer'],
            [['IDcontrollist'], 'fk'],
            [['title', 'frequency', 'schedule', 'display_lead_unit', 'execution_duration_unit'], 'string'],
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
            'IDcontrollist' => 'Liste de controle',
            'title' => 'Titre',
            'description' => 'Description',
            'frequency' => 'Frequence',
            'schedule' => 'Reference',
            'display_lead_value' => 'Affichage en avance',
            'display_lead_unit' => 'Unite d anticipation',
            'execution_duration_value' => 'Delai avant retard',
            'execution_duration_unit' => 'Unite du delai avant retard',
            'position' => 'Position',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 255,
            'frequency' => 20,
            'schedule' => 20,
            'display_lead_unit' => 20,
            'execution_duration_unit' => 20,
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public static function delayUnits()
    {
        return [self::DELAY_DAY, self::DELAY_WEEK, self::DELAY_MONTH];
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
        $modifier = $unit === self::DELAY_WEEK ? 'weeks' : ($unit === self::DELAY_MONTH ? 'months' : 'days');
        $shifted = $reference->modify(($value > 0 ? '+' : '') . $value . ' ' . $modifier);
        return $shifted instanceof \DateTimeImmutable ? $shifted : $reference;
    }

    public function save()
    {
        $frequency = RecurrenceSchedule::normalizeFrequency($this->get('frequency'));
        $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $this->get('schedule'));
        $this->set('title', trim((string)$this->get('title')));
        $this->set('frequency', $frequency);
        $this->set('schedule', $schedule);
        $lead = max(0, min(3650, (int)$this->get('display_lead_value')));
        $duration = max(1, min(3650, (int)$this->get('execution_duration_value')));
        $this->set('display_lead_value', $lead);
        $this->set('display_lead_unit', $lead > 0 ? (self::normalizeDelayUnit($this->get('display_lead_unit')) ?: self::DELAY_DAY) : null);
        $this->set('execution_duration_value', $duration);
        $this->set('execution_duration_unit', self::normalizeDelayUnit($this->get('execution_duration_unit')) ?: self::DELAY_DAY);
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getList()
    {
        $list = new ControlList();
        return $list->load((int)$this->get('IDcontrollist')) ? $list : null;
    }

    public function getChecks($from = null, $to = null)
    {
        $checks = new ArrayControlTaskCheck();
        $checks->loadForTask((int)$this->getId(), $from, $to);
        return $checks;
    }

    public function getOccurrenceAt(\DateTimeImmutable $reference)
    {
        return RecurrenceSchedule::getPreviousOccurrence($this->get('frequency'), $this->get('schedule'), $reference);
    }

    public function getDisplayAt(\DateTimeImmutable $occurrenceAt)
    {
        return self::shiftDate($occurrenceAt, -(int)$this->get('display_lead_value'), $this->get('display_lead_unit'));
    }

    public function getDeadlineAt(\DateTimeImmutable $occurrenceAt)
    {
        return self::shiftDate($occurrenceAt, max(1, (int)$this->get('execution_duration_value')), $this->get('execution_duration_unit'));
    }

    public function getOccurrenceState(\DateTimeImmutable $now)
    {
        $occurrence = $this->getOccurrenceAt($now);
        if (!($occurrence instanceof \DateTimeImmutable)) {
            return ['state' => 'invalid'];
        }
        $checks = new ArrayControlTaskCheck();
        $checks->loadForTaskAndScheduledFor((int)$this->getId(), $occurrence);
        $check = count($checks) > 0 ? $checks[0] : null;
        $deadline = $this->getDeadlineAt($occurrence);
        $state = $check instanceof ControlTaskCheck
            ? 'checked'
            : ($now < $occurrence ? 'upcoming' : ($now > $deadline ? 'overdue' : 'due'));
        return [
            'state' => $state,
            'occurrenceAt' => $occurrence,
            'displayAt' => $this->getDisplayAt($occurrence),
            'deadlineAt' => $deadline,
            'check' => $check,
        ];
    }

    public function getRegularity($limit = 12, $reference = null)
    {
        $now = $reference instanceof \DateTimeImmutable ? $reference : new \DateTimeImmutable('now');
        $limit = max(1, min(48, (int)$limit));
        $occurrences = [];
        $cursor = $now;
        for ($index = 0; $index < $limit; $index++) {
            $occurrence = $this->getOccurrenceAt($cursor);
            if (!($occurrence instanceof \DateTimeImmutable)) {
                break;
            }
            $key = $occurrence->format('Y-m-d H:i:s');
            if (isset($occurrences[$key])) {
                break;
            }
            $occurrences[$key] = $occurrence;
            $cursor = $occurrence->modify('-1 second');
        }
        if (count($occurrences) === 0) {
            return [];
        }
        $dates = array_values($occurrences);
        $checks = $this->getChecks(end($dates), reset($dates));
        $checkByOccurrence = [];
        foreach ($checks as $check) {
            if ($check instanceof ControlTaskCheck && $check->get('scheduled_for') instanceof \DateTimeInterface) {
                $checkByOccurrence[$check->get('scheduled_for')->format('Y-m-d H:i:s')] = $check;
            }
        }
        $entries = [];
        foreach (array_reverse($occurrences, true) as $key => $occurrence) {
            $check = $checkByOccurrence[$key] ?? null;
            $entries[] = [
                'occurrenceAt' => $occurrence,
                'check' => $check,
                'state' => $check instanceof ControlTaskCheck ? 'checked' : ($now > $this->getDeadlineAt($occurrence) ? 'missed' : 'pending'),
            ];
        }
        return $entries;
    }

    public function deleteWithRelatedData()
    {
        foreach ($this->getChecks() as $check) {
            if ($check instanceof ControlTaskCheck && !$check->delete()) {
                return false;
            }
        }
        return $this->delete();
    }
}
?>
