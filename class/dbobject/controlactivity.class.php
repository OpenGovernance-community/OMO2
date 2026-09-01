<?php
namespace dbObject;

class ControlActivity extends ControlTask
{
    public static function tableName()
    {
        return 'control_task';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'IDholon', 'title', 'frequency', 'schedule'], 'required'],
            [['id', 'display_lead_value', 'execution_duration_value', 'position'], 'integer'],
            [['IDorganization', 'IDholon'], 'fk'],
            [['title', 'frequency', 'schedule', 'display_lead_unit', 'execution_duration_unit'], 'string'],
            [['description'], 'text'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public function getHolon()
    {
        $holon = new Holon();
        return $holon->load((int)$this->get('IDholon')) ? $holon : null;
    }

    public function getOrganization()
    {
        $organization = new Organization();
        return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }

    public function getAttributionWindow(\DateTimeImmutable $occurrenceAt)
    {
        $nextOccurrenceAt = RecurrenceSchedule::getNextOccurrence(
            $this->get('frequency'),
            $this->get('schedule'),
            $occurrenceAt
        );
        if (!($nextOccurrenceAt instanceof \DateTimeImmutable)) {
            return null;
        }
        return [
            'occurrenceAt' => $occurrenceAt,
            'displayAt' => $this->getDisplayAt($occurrenceAt),
            'deadlineAt' => $this->getDeadlineAt($occurrenceAt),
            'nextOccurrenceAt' => $nextOccurrenceAt,
            'endAt' => $this->getDisplayAt($nextOccurrenceAt),
        ];
    }

    public function resolveOccurrenceForMoment(\DateTimeImmutable $moment)
    {
        $previousOccurrenceAt = RecurrenceSchedule::getPreviousOccurrence(
            $this->get('frequency'),
            $this->get('schedule'),
            $moment
        );
        $nextOccurrenceAt = RecurrenceSchedule::getNextOccurrence(
            $this->get('frequency'),
            $this->get('schedule'),
            $moment
        );
        if (!($previousOccurrenceAt instanceof \DateTimeImmutable) || !($nextOccurrenceAt instanceof \DateTimeImmutable)) {
            return null;
        }
        return $moment >= $this->getDisplayAt($nextOccurrenceAt) ? $nextOccurrenceAt : $previousOccurrenceAt;
    }

    protected function getChecksByCheckedAt($from = null, $to = null, $limit = 0)
    {
        $checks = new ArrayControlTaskCheck();
        $checks->loadForTaskByCheckedAt((int)$this->getId(), $from, $to, $limit);
        return $checks;
    }

    protected function findCheckForWindow(array $window)
    {
        $checks = $this->getChecksByCheckedAt($window['displayAt'], $window['endAt'], 1);
        return count($checks) > 0 && $checks[0] instanceof ControlTaskCheck ? $checks[0] : null;
    }

    protected function getCreatedAtImmutable()
    {
        $createdAt = $this->get('created_at');
        return $createdAt instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($createdAt) : null;
    }

    public function getOccurrenceState(\DateTimeImmutable $now)
    {
        $occurrenceAt = $this->resolveOccurrenceForMoment($now);
        if (!($occurrenceAt instanceof \DateTimeImmutable)) {
            return ['state' => 'invalid'];
        }

        $createdAt = $this->getCreatedAtImmutable();
        if ($createdAt instanceof \DateTimeImmutable && $createdAt > $occurrenceAt) {
            $nextOccurrenceAt = RecurrenceSchedule::getNextOccurrence(
                $this->get('frequency'),
                $this->get('schedule'),
                $occurrenceAt
            );
            if ($nextOccurrenceAt instanceof \DateTimeImmutable) {
                $occurrenceAt = $nextOccurrenceAt;
            }
        }

        $window = $this->getAttributionWindow($occurrenceAt);
        if (!is_array($window)) {
            return ['state' => 'invalid'];
        }
        $check = $this->findCheckForWindow($window);
        if ($check instanceof ControlTaskCheck && $check->get('checked_at') instanceof \DateTimeInterface) {
            $checkedAt = \DateTimeImmutable::createFromInterface($check->get('checked_at'));
            return array_merge($window, [
                'state' => $checkedAt <= $window['deadlineAt'] ? 'checked' : 'late',
                'check' => $check,
                'checkedAt' => $checkedAt,
                'missedOccurrenceAt' => null,
            ]);
        }

        $previousOccurrenceAt = RecurrenceSchedule::getPreviousOccurrence(
            $this->get('frequency'),
            $this->get('schedule'),
            $occurrenceAt->modify('-1 second')
        );
        $previousWindow = $previousOccurrenceAt instanceof \DateTimeImmutable
            ? $this->getAttributionWindow($previousOccurrenceAt)
            : null;
        $previousCheck = is_array($previousWindow) ? $this->findCheckForWindow($previousWindow) : null;
        $activityExistedForPreviousWindow = is_array($previousWindow)
            && (!($createdAt instanceof \DateTimeImmutable) || $createdAt <= $previousOccurrenceAt);
        $missedOccurrenceAt = $activityExistedForPreviousWindow
            && $now >= $previousWindow['endAt']
            && !($previousCheck instanceof ControlTaskCheck)
                ? $previousOccurrenceAt
                : null;

        return array_merge($window, [
            'state' => $missedOccurrenceAt instanceof \DateTimeImmutable
                ? 'missed'
                : ($now >= $window['displayAt'] ? 'due' : 'upcoming'),
            'check' => null,
            'checkedAt' => null,
            'missedOccurrenceAt' => $missedOccurrenceAt,
        ]);
    }

    public function getRegularity($limit = 12, $reference = null)
    {
        $now = $reference instanceof \DateTimeImmutable ? $reference : new \DateTimeImmutable('now');
        $limit = max(1, min(48, (int)$limit));
        $lastOccurrenceAt = $this->resolveOccurrenceForMoment($now);
        if (!($lastOccurrenceAt instanceof \DateTimeImmutable)) {
            return [];
        }
        $createdAt = $this->getCreatedAtImmutable();
        if ($createdAt instanceof \DateTimeImmutable && $createdAt > $lastOccurrenceAt) {
            $nextOccurrenceAt = RecurrenceSchedule::getNextOccurrence(
                $this->get('frequency'),
                $this->get('schedule'),
                $lastOccurrenceAt
            );
            if ($nextOccurrenceAt instanceof \DateTimeImmutable) {
                $lastOccurrenceAt = $nextOccurrenceAt;
            }
        }

        $nextOccurrenceAt = $lastOccurrenceAt <= $now
            ? RecurrenceSchedule::getNextOccurrence(
                $this->get('frequency'),
                $this->get('schedule'),
                $lastOccurrenceAt
            )
            : null;
        $includeNextOccurrence = $nextOccurrenceAt instanceof \DateTimeImmutable && $limit >= 2;
        $historyLimit = $includeNextOccurrence ? $limit - 1 : $limit;

        $occurrences = [];
        $cursor = $lastOccurrenceAt;
        for ($index = 0; $index < $historyLimit; $index++) {
            $key = $cursor->format('Y-m-d H:i:s');
            if (isset($occurrences[$key])) {
                break;
            }
            $occurrences[$key] = $cursor;
            $previous = RecurrenceSchedule::getPreviousOccurrence(
                $this->get('frequency'),
                $this->get('schedule'),
                $cursor->modify('-1 second')
            );
            if (!($previous instanceof \DateTimeImmutable)) {
                break;
            }
            $cursor = $previous;
        }
        $occurrences = array_values(array_filter(
            array_reverse(array_values($occurrences)),
            static function (\DateTimeImmutable $occurrenceAt) use ($createdAt) {
                return !($createdAt instanceof \DateTimeImmutable) || $occurrenceAt >= $createdAt;
            }
        ));
        if (count($occurrences) === 0) {
            return [];
        }
        if ($includeNextOccurrence) {
            $occurrences[] = $nextOccurrenceAt;
        }

        $firstWindow = $this->getAttributionWindow($occurrences[0]);
        $lastWindow = $this->getAttributionWindow($occurrences[count($occurrences) - 1]);
        if (!is_array($firstWindow) || !is_array($lastWindow)) {
            return [];
        }
        $checks = $this->getChecksByCheckedAt($firstWindow['displayAt'], $lastWindow['endAt']);
        $entries = [];

        foreach ($occurrences as $occurrenceAt) {
            $window = $this->getAttributionWindow($occurrenceAt);
            if (!is_array($window)) {
                continue;
            }
            $check = null;
            foreach ($checks as $candidate) {
                if (!($candidate instanceof ControlTaskCheck) || !($candidate->get('checked_at') instanceof \DateTimeInterface)) {
                    continue;
                }
                $checkedAt = \DateTimeImmutable::createFromInterface($candidate->get('checked_at'));
                if ($checkedAt >= $window['displayAt'] && $checkedAt < $window['endAt']) {
                    $check = $candidate;
                    break;
                }
            }
            if ($check instanceof ControlTaskCheck) {
                $checkedAt = \DateTimeImmutable::createFromInterface($check->get('checked_at'));
                $entryState = $checkedAt <= $window['deadlineAt'] ? 'checked' : 'late';
            } elseif ($now >= $window['endAt']) {
                $entryState = 'missed';
            } else {
                $entryState = $now >= $window['displayAt'] ? 'due' : 'upcoming';
            }
            $entries[] = [
                'occurrenceAt' => $occurrenceAt,
                'displayAt' => $window['displayAt'],
                'deadlineAt' => $window['deadlineAt'],
                'endAt' => $window['endAt'],
                'check' => $check,
                'state' => $entryState,
            ];
        }
        return $entries;
    }
}
?>
