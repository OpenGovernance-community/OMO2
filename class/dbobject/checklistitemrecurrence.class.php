<?php
namespace dbObject;

class ChecklistItemRecurrence extends DbObject
{
    public static function tableName()
    {
        return 'checklist_item_recurrence';
    }

    public static function rules()
    {
        return [
            [['IDchecklistitem', 'frequency', 'schedule'], 'required'],
            [['id', 'display_lead_value', 'execution_duration_value'], 'integer'],
            [['IDchecklistitem'], 'fk'],
            [['frequency', 'schedule', 'display_lead_unit', 'execution_duration_unit'], 'string'],
            [['enabled'], 'boolean'],
            [['next_trigger_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklistitem' => 'Activite de processus',
            'frequency' => 'Frequence',
            'schedule' => 'Moment attendu',
            'display_lead_value' => 'Affichage en avance',
            'display_lead_unit' => 'Unite d anticipation',
            'execution_duration_value' => 'Delai de realisation',
            'execution_duration_unit' => 'Unite du delai de realisation',
            'next_trigger_at' => 'Prochain declenchement',
            'enabled' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'display_lead_value' => 'Nombre de jours, semaines ou mois avant la date attendue pour afficher le projet.',
            'display_lead_unit' => 'Unite utilisee pour l anticipation d affichage.',
            'execution_duration_value' => 'Duree accordee pour realiser le projet a partir de sa date attendue.',
            'execution_duration_unit' => 'Unite utilisee pour le delai de realisation.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'frequency' => 20,
            'schedule' => 20,
            'display_lead_unit' => 20,
            'execution_duration_unit' => 20,
        ];
    }

    public function save()
    {
        $frequency = RecurrenceSchedule::normalizeFrequency($this->get('frequency'));
        $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $this->get('schedule'));
        $this->set('frequency', $frequency);
        $this->set('schedule', $schedule);
        $displayLeadValue = max(0, min(3650, (int)$this->get('display_lead_value')));
        $displayLeadUnit = ChecklistItem::normalizeDelayUnit($this->get('display_lead_unit'));
        $durationValue = max(0, min(3650, (int)$this->get('execution_duration_value')));
        $durationUnit = ChecklistItem::normalizeDelayUnit($this->get('execution_duration_unit'));
        $this->set('display_lead_value', $displayLeadValue);
        $this->set('display_lead_unit', $displayLeadValue > 0 ? ($displayLeadUnit ?: ChecklistItem::DELAY_DAY) : null);
        $this->set('execution_duration_value', $durationValue);
        $this->set('execution_duration_unit', $durationValue > 0 ? ($durationUnit ?: ChecklistItem::DELAY_DAY) : null);
        if ($frequency === null || $schedule === null) {
            $this->set('enabled', 0);
            $this->set('next_trigger_at', null);
            $this->set('display_lead_value', 0);
            $this->set('display_lead_unit', null);
            $this->set('execution_duration_value', 0);
            $this->set('execution_duration_unit', null);
        }
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getItem()
    {
        $item = new ChecklistItem();
        return $item->load((int)$this->get('IDchecklistitem')) ? $item : null;
    }

    public function getDisplayLeadValue()
    {
        return max(0, (int)$this->get('display_lead_value'));
    }

    public function getDisplayLeadUnit()
    {
        return ChecklistItem::normalizeDelayUnit($this->get('display_lead_unit')) ?: ChecklistItem::DELAY_DAY;
    }

    public function getExecutionDurationValue()
    {
        return max(0, (int)$this->get('execution_duration_value'));
    }

    public function getExecutionDurationUnit()
    {
        return ChecklistItem::normalizeDelayUnit($this->get('execution_duration_unit')) ?: ChecklistItem::DELAY_DAY;
    }

    public function getDisplayTriggerAt(\DateTimeImmutable $occurrenceAt)
    {
        return ChecklistItem::shiftDate(
            $occurrenceAt,
            -$this->getDisplayLeadValue(),
            $this->getDisplayLeadUnit()
        );
    }

    public function getDeadlineAt(\DateTimeImmutable $occurrenceAt)
    {
        $duration = $this->getExecutionDurationValue();
        return $duration > 0
            ? ChecklistItem::shiftDate($occurrenceAt, $duration, $this->getExecutionDurationUnit())
            : null;
    }

    public static function formatOccurrenceLabel(\DateTimeInterface $occurrenceAt, $frequency, $schedule)
    {
        $frequency = RecurrenceSchedule::normalizeFrequency($frequency);
        $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $schedule);
        if ($frequency === null || $schedule === null) {
            return '';
        }

        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];
        $weekdays = [
            1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi',
            5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche',
        ];
        $month = $months[(int)$occurrenceAt->format('n')] ?? '';
        $year = $occurrenceAt->format('Y');

        if ($frequency === RecurrenceSchedule::FREQUENCY_DAILY) {
            $label = ($weekdays[(int)$occurrenceAt->format('N')] ?? '') . ' '
                . $occurrenceAt->format('j') . ' ' . $month . ' ' . $year;
            return $schedule !== '00:00' ? trim($label . ' ' . $schedule) : trim($label);
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_WEEKLY) {
            return 'Semaine ' . $occurrenceAt->format('W') . ' ' . $occurrenceAt->format('o')
                . ' - ' . ($weekdays[(int)$occurrenceAt->format('N')] ?? '');
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_MONTHLY) {
            return $occurrenceAt->format('j') . ' ' . $month . ' ' . $year;
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_QUARTERLY) {
            $quarter = (int)floor(((int)$occurrenceAt->format('n') - 1) / 3) + 1;
            return ($quarter === 1 ? '1er' : $quarter . 'e') . ' trimestre ' . $year;
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_SEMIANNUAL) {
            $semester = (int)floor(((int)$occurrenceAt->format('n') - 1) / 6) + 1;
            return ($semester === 1 ? '1er' : $semester . 'e') . ' semestre ' . $year;
        }
        return $month . ' ' . $year;
    }

    public function activateIfDue(\DateTimeImmutable $now)
    {
        if ((int)$this->get('enabled') !== 1) {
            return false;
        }
        $nextTriggerAt = $this->get('next_trigger_at');
        if (!($nextTriggerAt instanceof \DateTimeInterface) || $nextTriggerAt > $now) {
            return false;
        }
        $item = $this->getItem();
        $checklist = $item instanceof ChecklistItem ? $item->getChecklist() : null;
        $trigger = null;
        if ($checklist instanceof Checklist) {
            foreach ($checklist->getTriggers(false) as $candidateTrigger) {
                if ($candidateTrigger instanceof ChecklistTrigger) {
                    $trigger = $candidateTrigger;
                    break;
                }
            }
        }
        $template = $item instanceof ChecklistItem ? $item->getProjectTemplate() : null;
        if (
            !($item instanceof ChecklistItem)
            || (int)$item->get('active') !== 1
            || !($checklist instanceof Checklist)
            || Checklist::normalizeStatus($checklist->get('status')) !== Checklist::STATUS_PUBLISHED
            || !($trigger instanceof ChecklistTrigger)
            || ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type')) !== ChecklistTrigger::TYPE_CONTAINER
            || !($template instanceof Project)
        ) {
            return false;
        }

        $frequency = RecurrenceSchedule::normalizeFrequency($this->get('frequency'));
        $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $this->get('schedule'));
        $triggerAt = \DateTimeImmutable::createFromInterface($nextTriggerAt);
        $occurrenceAt = ChecklistItem::shiftDate(
            $triggerAt,
            $this->getDisplayLeadValue(),
            $this->getDisplayLeadUnit()
        );
        $nextOccurrenceAt = RecurrenceSchedule::getNextOccurrence($frequency, $schedule, $occurrenceAt);
        $nextTriggerAt = $nextOccurrenceAt instanceof \DateTimeImmutable
            ? $this->getDisplayTriggerAt($nextOccurrenceAt)
            : null;
        if (!($occurrenceAt instanceof \DateTimeImmutable) || !($nextOccurrenceAt instanceof \DateTimeImmutable) || !($nextTriggerAt instanceof \DateTimeImmutable)) {
            return false;
        }

        $pdo = DbObject::getPdo();
        $startedTransaction = false;
        try {
            if ($pdo && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }
            $occurrences = new ArrayChecklistItemOccurrence();
            $occurrences->loadForItemAndScheduledFor((int)$item->getId(), $occurrenceAt);
            if (count($occurrences) === 0) {
                $baseTitle = trim((string)$template->get('title'));
                $occurrenceLabel = self::formatOccurrenceLabel($occurrenceAt, $frequency, $schedule);
                $occurrenceTitle = $baseTitle;
                if ($occurrenceLabel !== '') {
                    $occurrenceTitle .= ' - ' . $occurrenceLabel;
                }
                $occurrenceTitle = function_exists('mb_substr')
                    ? mb_substr($occurrenceTitle, 0, 255, 'UTF-8')
                    : substr($occurrenceTitle, 0, 255);
                $project = Project::createFromChecklistTemplate(
                    $template,
                    0,
                    $occurrenceAt,
                    $occurrenceTitle,
                    $this->getDeadlineAt($occurrenceAt)
                );
                if (!($project instanceof Project)) {
                    throw new \RuntimeException('Unable to create recurring process activity project.');
                }
                $occurrence = new ChecklistItemOccurrence();
                $occurrence->set('IDchecklistitem', (int)$item->getId());
                $occurrence->set('scheduled_for', $occurrenceAt);
                $occurrence->set('IDproject', (int)$project->getId());
                $occurrenceResult = $occurrence->save();
                if (!is_array($occurrenceResult) || empty($occurrenceResult['status'])) {
                    throw new \RuntimeException('Unable to save recurring process activity occurrence.');
                }
            }
            $this->set('next_trigger_at', $nextTriggerAt);
            $result = $this->save();
            if (!is_array($result) || empty($result['status'])) {
                throw new \RuntimeException('Unable to update recurring process activity schedule.');
            }
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return count($occurrences) === 0;
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    public static function activateDueBatch($limit = 50, $referenceDateTime = null)
    {
        $now = $referenceDateTime instanceof \DateTimeImmutable
            ? $referenceDateTime
            : new \DateTimeImmutable($referenceDateTime === null ? 'now' : (string)$referenceDateTime);
        $pdo = DbObject::getPdo();
        if (!($pdo instanceof \PDO)) {
            return 0;
        }
        $lockAcquired = DbObject::fetchValue("SELECT GET_LOCK('omo_checklist_item_recurrences', 0)");
        if ((int)$lockAcquired !== 1) {
            return 0;
        }
        try {
            $recurrences = new ArrayChecklistItemRecurrence();
            $recurrences->loadDue($now, $limit);
            $createdCount = 0;
            foreach ($recurrences as $recurrence) {
                $freshRecurrence = new self();
                if (
                    $recurrence instanceof self
                    && $freshRecurrence->load((int)$recurrence->getId(), true)
                    && $freshRecurrence->activateIfDue($now)
                ) {
                    $createdCount++;
                }
            }
            return $createdCount;
        } finally {
            DbObject::fetchValue("SELECT RELEASE_LOCK('omo_checklist_item_recurrences')");
        }
    }
}
?>
