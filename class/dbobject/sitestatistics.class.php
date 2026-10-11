<?php
namespace dbObject;

final class SiteStatistics
{
    public static function growth(\DateTimeImmutable $chartStartDate, \DateTimeImmutable $chartEndDate): array
    {
        $chartStartSql = $chartStartDate->format('Y-m-d H:i:s');
        $chartEndSql = $chartEndDate->modify('+1 day')->format('Y-m-d H:i:s');
        $monthStartSql = $chartEndDate->modify('first day of this month')->format('Y-m-d 00:00:00');
        $totalOrganizations = (int)\dbObject\DbObject::fetchValue('SELECT COUNT(*) FROM organization');
        $newOrganizationsThisMonth = (int)\dbObject\DbObject::fetchValue(
            'SELECT COUNT(*) FROM organization WHERE datecreation >= :month_start AND datecreation < :period_end',
            ['month_start' => $monthStartSql, 'period_end' => $chartEndSql]
        );
        $totalUsers = (int)\dbObject\DbObject::fetchValue('SELECT COUNT(*) FROM `user`');
        $connectedUsers30 = (int)\dbObject\DbObject::fetchValue(
            'SELECT COUNT(*) FROM `user` WHERE dateconnexion >= :recent_start AND dateconnexion < :period_end',
            ['recent_start' => $chartEndDate->modify('-29 days')->format('Y-m-d 00:00:00'), 'period_end' => $chartEndSql]
        );
        $totalHolons = (int)\dbObject\DbObject::fetchValue('SELECT COUNT(*) FROM holon WHERE active = 1');
        $modifiedHolons30 = (int)\dbObject\DbObject::fetchValue(
            "SELECT COUNT(DISTINCT CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED))
             FROM history h
             WHERE h.active = 1
               AND h.action = 'holon_updated'
               AND h.datecreation >= :recent_start
               AND h.datecreation < :period_end
               AND JSON_VALID(h.parameters) = 1
               AND CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED) > 0",
            ['recent_start' => $chartEndDate->modify('-29 days')->format('Y-m-d 00:00:00'), 'period_end' => $chartEndSql]
        );
        $organizationRows = \dbObject\DbObject::fetchAll(
            'SELECT DATE(datecreation) AS period_date, COUNT(*) AS total
             FROM organization
             WHERE datecreation >= :period_start AND datecreation < :period_end
             GROUP BY DATE(datecreation)
             ORDER BY period_date ASC',
            ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
        );
        $userRows = \dbObject\DbObject::fetchAll(
            'SELECT DATE(dateconnexion) AS period_date, COUNT(*) AS total
             FROM `user`
             WHERE dateconnexion IS NOT NULL
               AND dateconnexion >= :period_start
               AND dateconnexion < :period_end
             GROUP BY DATE(dateconnexion)
             ORDER BY period_date ASC',
            ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
        );
        $holonCreationRows = \dbObject\DbObject::fetchAll(
            'SELECT DATE(datecreation) AS period_date, COUNT(*) AS total
             FROM holon
             WHERE datecreation >= :period_start AND datecreation < :period_end
             GROUP BY DATE(datecreation)
             ORDER BY period_date ASC',
            ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
        );
        $holonModificationRows = \dbObject\DbObject::fetchAll(
            "SELECT DATE(h.datecreation) AS period_date,
                    COUNT(DISTINCT CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED)) AS total
             FROM history h
             WHERE h.active = 1
               AND h.action = 'holon_updated'
               AND h.datecreation >= :period_start
               AND h.datecreation < :period_end
               AND JSON_VALID(h.parameters) = 1
               AND CAST(JSON_UNQUOTE(JSON_EXTRACT(h.parameters, '$.IDholon')) AS UNSIGNED) > 0
             GROUP BY DATE(h.datecreation)
             ORDER BY period_date ASC",
            ['period_start' => $chartStartSql, 'period_end' => $chartEndSql]
        );
        return compact('totalOrganizations', 'newOrganizationsThisMonth', 'totalUsers', 'connectedUsers30', 'totalHolons', 'modifiedHolons30', 'organizationRows', 'userRows', 'holonCreationRows', 'holonModificationRows');
    }
}
