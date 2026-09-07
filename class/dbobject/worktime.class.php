<?php
namespace dbObject;

class WorkTime extends DbObject
{
    public const END_REASON_STOP = 'stop';
    public const END_REASON_SWITCH = 'switch';

    public static function tableName()
    {
        return 'work_time';
    }

    public static function rules()
    {
        return [
            [['IDuser', 'IDorganization', 'IDholon', 'started_at'], 'required'],
            [['id'], 'integer'],
            [['IDuser', 'IDorganization', 'IDholon', 'IDproject'], 'fk'],
            [['started_at', 'ended_at', 'last_heartbeat_at'], 'datetime'],
            [['end_reason'], 'string'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDuser' => 'Utilisateur',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Holon',
            'IDproject' => 'Projet',
            'started_at' => 'Debut',
            'ended_at' => 'Fin',
            'last_heartbeat_at' => 'Dernier signal',
            'end_reason' => 'Raison de fin',
        ];
    }

    public static function attributeLength()
    {
        return [
            'end_reason' => 20,
        ];
    }

    public static function getOrder()
    {
        return 'started_at DESC, id DESC';
    }

    public static function findOpenForUser($userId)
    {
        return self::loadOpenRow((int)$userId, 0, false);
    }

    public static function startOrSwitch($userId, $organizationId, $holonId, $projectId = 0)
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $projectId = (int)$projectId;

        if ($userId <= 0 || $organizationId <= 0 || $holonId <= 0) {
            return null;
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return null;
        }

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $current = self::loadOpenRow($userId, 0, true);
            $now = new \DateTimeImmutable('now');

            if ($current instanceof self && self::sameTarget($current, $organizationId, $holonId, $projectId)) {
                $current->set('ended_at', $now);
                $current->set('last_heartbeat_at', $now);
                $result = $current->save();
                if (!is_array($result) || empty($result['status'])) {
                    throw new \RuntimeException('Unable to refresh active work time.');
                }

                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return $current;
            }

            if ($current instanceof self) {
                $current->set('ended_at', $now);
                $current->set('last_heartbeat_at', $now);
                $current->set('end_reason', self::END_REASON_SWITCH);
                $result = $current->save();
                if (!is_array($result) || empty($result['status'])) {
                    throw new \RuntimeException('Unable to close previous work time.');
                }
            }

            $workTime = new self();
            $workTime->set('IDuser', $userId);
            $workTime->set('IDorganization', $organizationId);
            $workTime->set('IDholon', $holonId);
            $workTime->set('IDproject', $projectId > 0 ? $projectId : null);
            $workTime->set('started_at', $now);
            $workTime->set('ended_at', $now);
            $workTime->set('last_heartbeat_at', $now);
            $workTime->set('end_reason', null);
            $result = $workTime->save();
            if (!is_array($result) || empty($result['status'])) {
                throw new \RuntimeException('Unable to create work time.');
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $workTime;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return null;
        }
    }

    public static function touchOpenForUser($userId, $entryId = 0)
    {
        $workTime = self::loadOpenRow((int)$userId, (int)$entryId, false);
        if (!($workTime instanceof self)) {
            return null;
        }

        $now = new \DateTimeImmutable('now');
        $workTime->set('ended_at', $now);
        $workTime->set('last_heartbeat_at', $now);
        $result = $workTime->save();
        return is_array($result) && !empty($result['status']) ? $workTime : null;
    }

    public static function closeOpenForUser($userId, $entryId = 0, $reason = self::END_REASON_STOP)
    {
        $reason = in_array($reason, [self::END_REASON_STOP, self::END_REASON_SWITCH], true)
            ? $reason
            : self::END_REASON_STOP;
        $workTime = self::loadOpenRow((int)$userId, (int)$entryId, false);
        if (!($workTime instanceof self)) {
            return null;
        }

        $now = new \DateTimeImmutable('now');
        $workTime->set('ended_at', $now);
        $workTime->set('last_heartbeat_at', $now);
        $workTime->set('end_reason', $reason);
        $result = $workTime->save();
        return is_array($result) && !empty($result['status']) ? $workTime : null;
    }

    public static function getDailyMeasuredSecondsForHolons($organizationId, array $holonIds, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd)
    {
        $organizationId = (int)$organizationId;
        $holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), static function ($holonId) {
            return $holonId > 0;
        })));
        $start = \DateTimeImmutable::createFromInterface($rangeStart);
        $end = \DateTimeImmutable::createFromInterface($rangeEnd);

        if ($organizationId <= 0 || count($holonIds) === 0 || $end <= $start) {
            return self::aggregateMeasuredIntervalsByDay([], $start, $end);
        }

        $params = [
            'organization_id' => $organizationId,
            'range_start' => $start->format('Y-m-d H:i:s'),
            'range_end' => $end->format('Y-m-d H:i:s'),
        ];
        $holonPlaceholders = [];
        foreach ($holonIds as $index => $holonId) {
            $parameterName = 'holon_' . $index;
            $holonPlaceholders[] = ':' . $parameterName;
            $params[$parameterName] = $holonId;
        }

        $rows = self::fetchAll(
            'SELECT `started_at`, `ended_at`
             FROM `work_time`
             WHERE `IDorganization` = :organization_id
               AND `IDholon` IN (' . implode(', ', $holonPlaceholders) . ')
               AND `ended_at` IS NOT NULL
               AND `ended_at` > `started_at`
               AND `started_at` < :range_end
               AND `ended_at` > :range_start
             ORDER BY `started_at` ASC, `id` ASC',
            $params
        );

        return self::aggregateMeasuredIntervalsByDay(is_array($rows) ? $rows : [], $start, $end);
    }

    public static function aggregateMeasuredIntervalsByDay(array $intervals, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd)
    {
        $start = \DateTimeImmutable::createFromInterface($rangeStart);
        $end = \DateTimeImmutable::createFromInterface($rangeEnd);
        $timezone = $start->getTimezone();
        $dailySeconds = [];

        if ($end <= $start) {
            return $dailySeconds;
        }

        $day = $start->setTime(0, 0, 0);
        while ($day < $end) {
            $dailySeconds[$day->format('Y-m-d')] = 0;
            $day = $day->modify('+1 day');
        }

        foreach ($intervals as $interval) {
            if (!is_array($interval)) {
                continue;
            }

            $intervalStart = self::normalizeDateTime($interval['started_at'] ?? null, $timezone);
            $intervalEnd = self::normalizeDateTime($interval['ended_at'] ?? null, $timezone);
            if (!($intervalStart instanceof \DateTimeImmutable) || !($intervalEnd instanceof \DateTimeImmutable)) {
                continue;
            }

            if ($intervalStart < $start) {
                $intervalStart = $start;
            }
            if ($intervalEnd > $end) {
                $intervalEnd = $end;
            }
            if ($intervalEnd <= $intervalStart) {
                continue;
            }

            $cursor = $intervalStart;
            while ($cursor < $intervalEnd) {
                $nextDay = $cursor->setTime(0, 0, 0)->modify('+1 day');
                $segmentEnd = $intervalEnd < $nextDay ? $intervalEnd : $nextDay;
                $dateKey = $cursor->format('Y-m-d');
                if (array_key_exists($dateKey, $dailySeconds)) {
                    $dailySeconds[$dateKey] += max(0, $segmentEnd->getTimestamp() - $cursor->getTimestamp());
                }
                $cursor = $segmentEnd;
            }
        }

        return $dailySeconds;
    }

    public function isOpen()
    {
        return trim((string)$this->get('end_reason')) === '';
    }

    public function hasTarget($organizationId, $holonId, $projectId = 0)
    {
        return self::sameTarget($this, (int)$organizationId, (int)$holonId, (int)$projectId);
    }

    public function toTimerArray()
    {
        $startedAt = $this->get('started_at');
        $endedAt = $this->get('ended_at');
        $lastHeartbeatAt = $this->get('last_heartbeat_at');

        return [
            'id' => (int)$this->getId(),
            'organizationId' => (int)$this->get('IDorganization'),
            'holonId' => (int)$this->get('IDholon'),
            'projectId' => (int)$this->get('IDproject'),
            'startedAt' => self::formatDate($startedAt),
            'startedAtUnix' => self::dateToUnix($startedAt),
            'endedAt' => self::formatDate($endedAt),
            'endedAtUnix' => self::dateToUnix($endedAt),
            'lastHeartbeatAt' => self::formatDate($lastHeartbeatAt),
            'lastHeartbeatAtUnix' => self::dateToUnix($lastHeartbeatAt),
            'endReason' => trim((string)$this->get('end_reason')),
            'isOpen' => $this->isOpen(),
        ];
    }

    private static function sameTarget(self $workTime, $organizationId, $holonId, $projectId)
    {
        return (int)$workTime->get('IDorganization') === (int)$organizationId
            && (int)$workTime->get('IDholon') === (int)$holonId
            && (int)$workTime->get('IDproject') === (int)$projectId;
    }

    private static function loadOpenRow($userId, $entryId = 0, $forUpdate = false)
    {
        $userId = (int)$userId;
        $entryId = (int)$entryId;
        if ($userId <= 0) {
            return null;
        }

        $query = 'SELECT * FROM `work_time` WHERE `IDuser` = :user_id AND `end_reason` IS NULL';
        $params = ['user_id' => $userId];
        if ($entryId > 0) {
            $query .= ' AND `id` = :entry_id';
            $params['entry_id'] = $entryId;
        }
        $query .= ' ORDER BY `id` DESC LIMIT 1';
        if ($forUpdate) {
            $query .= ' FOR UPDATE';
        }

        $row = self::fetchRow($query, $params);
        if (!is_array($row) || (int)($row['id'] ?? 0) <= 0) {
            return null;
        }

        $workTime = new self();
        return $workTime->hydrateFromDatabaseRow($row, true) ? $workTime : null;
    }

    private static function formatDate($value)
    {
        return $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : ((string)$value !== '' ? (string)$value : null);
    }

    private static function dateToUnix($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $timestamp = strtotime((string)$value);
        return $timestamp === false ? 0 : $timestamp;
    }

    private static function normalizeDateTime($value, \DateTimeZone $timezone)
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value)->setTimezone($timezone);
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value, $timezone);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
