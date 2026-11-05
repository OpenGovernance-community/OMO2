<?php

namespace dbObject;

class ArrayTension extends ArrayDbObject
{
    public static function objectName()
    {
        return '\dbObject\Tension';
    }

    public function loadForOrganization($organizationId, $includeInactive = false, ?int $authorId = null)
    {
        $organizationId = (int)$organizationId;
        $this->exchangeArray([]);

        if ($organizationId <= 0 || ($authorId !== null && $authorId <= 0)) {
            return;
        }

        $query = '
            SELECT t.id
            FROM `tension` t
            WHERE t.IDorganization = :organization_id
        ';
        $params = ['organization_id' => $organizationId];

        if ($authorId !== null) {
            $query .= ' AND t.IDuser = :author_id';
            $params['author_id'] = $authorId;
        }

        if (!$includeInactive) {
            $query .= '
              AND t.active = 1
            ';
        }

        $query .= '
            ORDER BY t.datecreation DESC, t.id DESC
        ';

        $rows = \dbObject\DbObject::fetchAll($query, $params);

        if ($rows === false) {
            return;
        }

        foreach ($rows as $row) {
            $item = new Tension();
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

        $query = '
            SELECT t.id
            FROM `tension` t
            WHERE t.IDholon = :holon_id
        ';

        if (!$includeInactive) {
            $query .= '
              AND t.active = 1
            ';
        }

        $query .= '
            ORDER BY t.datecreation DESC, t.id DESC
        ';

        $rows = \dbObject\DbObject::fetchAll($query, [
            'holon_id' => $holonId,
        ]);

        if ($rows === false) {
            return;
        }

        foreach ($rows as $row) {
            $item = new Tension();
            $item->setId((int)($row['id'] ?? 0));
            $this[] = $item;
        }
    }
}

?>
