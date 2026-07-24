<?php
namespace dbObject;

class ArrayRule extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\Rule';
    }

    public function loadForAuthority($authorityId)
    {
        $this->exchangeArray([]);
        $authorityId = (int)$authorityId;
        if ($authorityId <= 0) {
            return;
        }

        $this->load([
            'where' => [['field' => 'IDauthority', 'value' => $authorityId]],
            'orderBy' => self::orderBy(),
        ]);
    }

    public function loadForHolon($holonId)
    {
        $this->exchangeArray([]);
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return;
        }

        $this->load([
            'where' => [['field' => 'IDholon', 'value' => $holonId]],
            'orderBy' => self::orderBy(),
        ]);
    }

    public function loadForPolicyContext($organizationId, Holon $contextHolon)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0 || (int)$contextHolon->getId() <= 0) {
            return;
        }

        $rows = Rule::fetchAll(
            'SELECT r.`id`
             FROM `rule` r
             LEFT JOIN `authority` a ON a.`id` = r.`IDauthority`
             INNER JOIN `holon` h ON h.`id` = COALESCE(r.`IDholon`, a.`IDholon`)
             LEFT JOIN `holon` root ON root.`id` = h.`IDholon_org`
             WHERE COALESCE(NULLIF(h.`IDorganization`, 0), root.`IDorganization`, 0) = :organization_id
             ORDER BY r.`expiration_date` ASC, r.`review_date` ASC, r.`id` ASC',
            ['organization_id' => $organizationId]
        );
        if (!is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $rule = new Rule();
            if (!$rule->load((int)($row['id'] ?? 0))) {
                continue;
            }
            $scope = Rule::normalizeScope($rule->get('scope'));
            $sourceHolon = $rule->getHolon();
            if (!($sourceHolon instanceof Holon)) {
                continue;
            }
            if ($scope === Rule::SCOPE_GLOBAL
                || ($scope === Rule::SCOPE_LOCAL && (int)$sourceHolon->getId() === (int)$contextHolon->getId())
                || ($scope === Rule::SCOPE_DESCENDANTS && $contextHolon->isDescendantOf((int)$sourceHolon->getId(), true))) {
                $this[] = $rule;
            }
        }
    }

    protected static function orderBy()
    {
        return [
            ['field' => 'expiration_date', 'dir' => 'ASC'],
            ['field' => 'review_date', 'dir' => 'ASC'],
            ['field' => 'id', 'dir' => 'ASC'],
        ];
    }
}

?>
