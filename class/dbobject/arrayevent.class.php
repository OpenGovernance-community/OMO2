<?php
namespace dbObject;

class ArrayEvent extends ArrayDbObject
{
    public static function objectName()
    {
        return '\dbObject\Event';
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
