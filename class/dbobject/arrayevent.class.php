<?php
namespace dbObject;

class ArrayEvent extends ArrayDbObject
{
    public static function objectName()
    {
        return '\dbObject\Event';
    }

    public function loadForOrganization($organizationId, $includeInactive = false)
    {
        $organizationId = (int)$organizationId;
        $this->exchangeArray([]);

        if ($organizationId <= 0) {
            return;
        }

        $query = "
            SELECT e.id
            FROM `event` e
            WHERE e.IDorganization = :organization_id
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
            'organization_id' => $organizationId,
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

    public function loadForOrganizationDateRange($organizationId, $rangeStart = null, $rangeEnd = null, $includeInactive = false)
    {
        $organizationId = (int)$organizationId;
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

        if ($rangeStart instanceof \DateTimeInterface) {
            $query .= "
              AND e.end_at >= :range_start
            ";
            $params['range_start'] = $rangeStart->format('Y-m-d H:i:s');
        }

        if ($rangeEnd instanceof \DateTimeInterface) {
            $query .= "
              AND e.start_at <= :range_end
            ";
            $params['range_end'] = $rangeEnd->format('Y-m-d H:i:s');
        }

        if (!$includeInactive) {
            $query .= "
              AND e.active = 1
            ";
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
}

?>
