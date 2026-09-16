<?php
namespace dbObject;

class ArrayEvent extends ArrayDbObject
{
    public function loadBusyForUserDateRange(int $userId, \DateTimeInterface $start, \DateTimeInterface $end): void
    {
        $this->exchangeArray([]);
        $rows = Event::fetchAll('SELECT e.id FROM `event` e WHERE e.active = 1 AND e.status <> :cancelled
            AND e.start_at < :end AND (CASE WHEN e.is_all_day = 1
                THEN DATE_ADD(DATE(COALESCE(e.end_at, e.start_at)), INTERVAL 1 DAY)
                ELSE COALESCE(e.end_at, DATE_ADD(e.start_at, INTERVAL 1 HOUR)) END) > :start
            AND (e.IDuser = :owner OR EXISTS (SELECT 1 FROM user_organization uo
                WHERE uo.IDorganization = e.IDorganization AND uo.IDuser = :member AND uo.active = 1))',
            ['cancelled' => Event::STATUS_CANCELLED, 'start' => $start, 'end' => $end, 'owner' => $userId, 'member' => $userId]);
        if (!is_array($rows)) { throw new \RuntimeException('storage'); }
        foreach ($rows as $row) {
            $event = new Event();
            if ($event->load((int)$row['id']) && ((int)$event->get('IDuser') === $userId
                || $event->isVisibleToInvitationViewer($userId, (int)$event->get('IDorganization')))) {
                $this[] = $event;
            }
        }
    }

    public static function objectName()
    {
        return '\dbObject\Event';
    }

    /** Sanitized personal busy blocks: never expose another organization's event details. */
    public static function otherOrganizationBusyBlocks(int $userId, int $organizationId, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        if ($userId <= 0) { return []; }
        $events = new self();
        $events->loadBusyForUserDateRange($userId, $start, $end);
        $labels = [];
        $blocks = [];
        foreach ($events as $event) {
            $otherId = (int)$event->get('IDorganization');
            if ($otherId <= 0 || $otherId === $organizationId) { continue; }
            if (!array_key_exists($otherId, $labels)) {
                $organization = new Organization();
                $labels[$otherId] = $organization->load($otherId) ? (string)$organization->get('name') : '';
            }
            $interval = $event->getBusyInterval();
            if ($interval === null || $labels[$otherId] === '') { continue; }
            $blocks[] = ['title' => $labels[$otherId], 'start' => $interval[0], 'end' => $interval[1], 'allDay' => (bool)$event->get('is_all_day')];
        }
        return $blocks;
    }

    public function loadForOrganization($organizationId, $includeInactive = false, $hydrate = false)
    {
        $organizationId = (int)$organizationId;
        $this->exchangeArray([]);

        if ($organizationId <= 0) {
            return;
        }

        $params = [
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
            ],
            'orderBy' => [
                ['field' => 'start_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if (!$includeInactive) {
            $params['where'][] = ['field' => 'active', 'value' => 1];
        }

        if ($hydrate !== false) {
            $params['hydrate'] = $hydrate;
        }

        $this->load($params);
    }

    public function loadVisibleForOrganization($organizationId, $userId, $includeInactive = false)
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        $this->exchangeArray([]);

        if ($organizationId <= 0 || $userId <= 0) {
            return;
        }

        $allEvents = new self();
        $allEvents->loadForOrganization($organizationId, $includeInactive, true);

        foreach ($allEvents as $event) {
            if (!($event instanceof Event)) {
                continue;
            }

            if (Event::normalizeStatus($event->get('status')) === Event::STATUS_CANCELLED) {
                continue;
            }

            if ((int)$event->get('IDuser') === $userId || $event->isVisibleToInvitationViewer($userId, $organizationId)) {
                $this[] = $event;
            }
        }
    }

    public function loadForHolon($holonId, $includeInactive = false)
    {
        $holonId = (int)$holonId;
        $this->exchangeArray([]);

        if ($holonId <= 0) {
            return;
        }

        $query = "
            SELECT e.id
            FROM `event` e
            WHERE e.IDholon = :holon_id
        ";

        if (!$includeInactive) {
            $query .= "
              AND e.active = 1
            ";
        }

        $query .= "
            ORDER BY e.start_at ASC, e.id ASC
        ";

        $rows = \dbObject\DbObject::fetchAll($query, [
            'holon_id' => $holonId,
        ]);

        if ($rows === false) {
            return;
        }

        foreach ($rows as $row) {
            $item = new Event();
            $item->setId((int)($row['id'] ?? 0));
            $this[] = $item;
        }
    }

    public function loadForUser($userId, $organizationId = 0, $includeInactive = false)
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $this->exchangeArray([]);

        if ($userId <= 0) {
            return;
        }

        $query = "
            SELECT e.id
            FROM `event` e
            WHERE e.IDuser = :user_id
        ";

        $params = [
            'user_id' => $userId,
        ];

        if ($organizationId > 0) {
            $query .= "
              AND e.IDorganization = :organization_id
            ";
            $params['organization_id'] = $organizationId;
        }

        if (!$includeInactive) {
            $query .= "
              AND e.active = 1
            ";
        }

        $query .= "
            ORDER BY e.start_at ASC, e.id ASC
        ";

        $rows = \dbObject\DbObject::fetchAll($query, $params);

        if ($rows === false) {
            return;
        }

        foreach ($rows as $row) {
            $item = new Event();
            $item->setId((int)($row['id'] ?? 0));
            $this[] = $item;
        }
    }

    public function loadForProject($projectId, $includeInactive = false)
    {
        $projectId = (int)$projectId;
        $this->exchangeArray([]);

        if ($projectId <= 0) {
            return;
        }

        $params = [
            'where' => [
                ['field' => 'IDproject', 'value' => $projectId],
            ],
            'orderBy' => [
                ['field' => 'start_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if (!$includeInactive) {
            $params['where'][] = ['field' => 'active', 'value' => 1];
            $params['where'][] = ['field' => 'status', 'op' => '<>', 'value' => \dbObject\Event::STATUS_CANCELLED];
        }

        $this->load($params);
    }

    public function loadForCalendarMonth($organizationId, $rangeStart, $rangeEnd, $holonId = 0, $includeInactive = false)
    {
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $this->exchangeArray([]);

        if ($organizationId <= 0) {
            return;
        }

        if (!($rangeStart instanceof \DateTimeInterface) || !($rangeEnd instanceof \DateTimeInterface)) {
            return;
        }

        $query = "
            SELECT e.id
            FROM `event` e
            WHERE e.IDorganization = :organization_id
              AND e.start_at <= :range_end
              AND e.end_at >= :range_start
        ";

        $params = [
            'organization_id' => $organizationId,
            'range_start' => $rangeStart->format('Y-m-d H:i:s'),
            'range_end' => $rangeEnd->format('Y-m-d H:i:s'),
        ];

        if ($holonId > 0) {
            $query .= "
              AND (e.IDholon = :holon_id OR e.IDholon IS NULL)
            ";
            $params['holon_id'] = $holonId;
        }

        if (!$includeInactive) {
            $query .= "
              AND e.active = 1
              AND e.status <> :cancelled_status
            ";
            $params['cancelled_status'] = \dbObject\Event::STATUS_CANCELLED;
        }

        $query .= "
            ORDER BY e.start_at ASC, e.end_at ASC, e.id ASC
        ";

        $rows = \dbObject\DbObject::fetchAll($query, $params);
        if ($rows === false) {
            return;
        }

        foreach ($rows as $row) {
            $item = new Event();
            $item->setId((int)($row['id'] ?? 0));
            $this[] = $item;
        }
    }

    public function loadForCalendarContext($organizationId, $holonId = 0, $includeInactive = false)
    {
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $this->exchangeArray([]);

        if ($organizationId <= 0) {
            return;
        }

        $query = "
            SELECT e.id
            FROM `event` e
            WHERE e.IDorganization = :organization_id
        ";

        $params = [
            'organization_id' => $organizationId,
        ];

        if ($holonId > 0) {
            $query .= "
              AND (e.IDholon = :holon_id OR e.IDholon IS NULL)
            ";
            $params['holon_id'] = $holonId;
        }

        if (!$includeInactive) {
            $query .= "
              AND e.active = 1
              AND e.status <> :cancelled_status
            ";
            $params['cancelled_status'] = \dbObject\Event::STATUS_CANCELLED;
        }

        $query .= "
            ORDER BY e.start_at ASC, e.end_at ASC, e.id ASC
        ";

        $rows = \dbObject\DbObject::fetchAll($query, $params);
        if ($rows === false) {
            return;
        }

        foreach ($rows as $row) {
            $item = new Event();
            $item->setId((int)($row['id'] ?? 0));
            $this[] = $item;
        }
    }

    public function loadForOrganizationDateRange($organizationId, $rangeStart = null, $rangeEnd = null, $includeInactive = false, $hydrate = false)
    {
        $organizationId = (int)$organizationId;
        $this->exchangeArray([]);

        if ($organizationId <= 0) {
            return;
        }

        $params = [
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
            ],
            'orderBy' => [
                ['field' => 'start_at', 'dir' => 'ASC'],
                ['field' => 'end_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if ($rangeStart instanceof \DateTimeInterface) {
            $params['where'][] = [
                'field' => 'end_at',
                'op' => '>=',
                'value' => $rangeStart->format('Y-m-d H:i:s'),
            ];
        }

        if ($rangeEnd instanceof \DateTimeInterface) {
            $params['where'][] = [
                'field' => 'start_at',
                'op' => '<=',
                'value' => $rangeEnd->format('Y-m-d H:i:s'),
            ];
        }

        if (!$includeInactive) {
            $params['where'][] = ['field' => 'active', 'value' => 1];
            $params['where'][] = [
                'field' => 'status',
                'op' => '<>',
                'value' => \dbObject\Event::STATUS_CANCELLED,
            ];
        }

        if ($hydrate !== false) {
            $params['hydrate'] = $hydrate;
        }

        $this->load($params);
    }

    public function loadUpcomingForCalendarList($organizationId, $referenceStart, $holonId = 0, $includeInactive = false)
    {
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $this->exchangeArray([]);

        if ($organizationId <= 0 || !($referenceStart instanceof \DateTimeInterface)) {
            return;
        }

        $query = "
            SELECT e.id
            FROM `event` e
            WHERE e.IDorganization = :organization_id
              AND e.end_at >= :reference_start
        ";

        $params = [
            'organization_id' => $organizationId,
            'reference_start' => $referenceStart->format('Y-m-d H:i:s'),
        ];

        if ($holonId > 0) {
            $query .= "
              AND (e.IDholon = :holon_id OR e.IDholon IS NULL)
            ";
            $params['holon_id'] = $holonId;
        }

        if (!$includeInactive) {
            $query .= "
              AND e.active = 1
              AND e.status <> :cancelled_status
            ";
            $params['cancelled_status'] = \dbObject\Event::STATUS_CANCELLED;
        }

        $query .= "
            ORDER BY e.start_at ASC, e.end_at ASC, e.id ASC
        ";

        $rows = \dbObject\DbObject::fetchAll($query, $params);
        if ($rows === false) {
            return;
        }

        foreach ($rows as $row) {
            $item = new Event();
            $item->setId((int)($row['id'] ?? 0));
            $this[] = $item;
        }
    }

    protected function eventMatchesPersonalSpaceViewer(\dbObject\Event $event, $organizationId, $userId)
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;

        if ($organizationId <= 0 || $userId <= 0 || (int)$event->getId() <= 0) {
            return false;
        }

        return $event->isVisibleToInvitationViewer($userId, $organizationId);
    }

    public function loadUpcomingForPersonalSpace($organizationId, $userId, $limit = 5, $referenceStart = null)
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        $limit = max(1, (int)$limit);
        $this->exchangeArray([]);

        if ($organizationId <= 0 || $userId <= 0) {
            return;
        }

        if (!($referenceStart instanceof \DateTimeInterface)) {
            $referenceStart = new \DateTimeImmutable('now');
        }

        $upcomingEvents = new self();
        $upcomingEvents->loadUpcomingForCalendarList($organizationId, $referenceStart, 0, false);

        foreach ($upcomingEvents as $event) {
            if (!($event instanceof \dbObject\Event)) {
                continue;
            }

            if (!$this->eventMatchesPersonalSpaceViewer($event, $organizationId, $userId)) {
                continue;
            }

            $this[] = $event;
            if (count($this) >= $limit) {
                break;
            }
        }
    }
}

?>
