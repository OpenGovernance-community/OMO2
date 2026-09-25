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
        $this->loadForPolicyContexts($organizationId, [(int)$contextHolon->getId()]);
    }

    public static function normalizeViewScope($scope): string
    {
        return in_array($scope, ['local', 'contextual', 'global'], true) ? $scope : 'contextual';
    }

    /**
     * Contextual: applicable rules; local: rules defined in the selected holons;
     * global: all rules of this organization. Each rule is included once.
     */
    public function loadForPolicyContexts($organizationId, array $contextHolonIds, $includeOrganizationRules = false, $viewScope = 'contextual')
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        $viewScope = self::normalizeViewScope($viewScope);
        $contextHolons = [];
        foreach ($contextHolonIds as $contextHolonId) {
            $contextHolonId = (int)$contextHolonId;
            if ($contextHolonId <= 0 || isset($contextHolons[$contextHolonId])) {
                continue;
            }

            $contextHolon = new Holon();
            if ($contextHolon->load($contextHolonId)) {
                $contextHolons[$contextHolonId] = $contextHolon;
            }
        }
        if ($organizationId <= 0 || ($viewScope !== 'global' && count($contextHolons) === 0 && !$includeOrganizationRules)) {
            return;
        }

        $rows = Rule::fetchAll(
            'SELECT r.`id`
             FROM `rule` r
             LEFT JOIN `authority` a ON a.`id` = r.`IDauthority`
             LEFT JOIN `holon` h ON h.`id` = COALESCE(r.`IDholon`, a.`IDholon`)
             LEFT JOIN `holon` root ON root.`id` = h.`IDholon_org`
             WHERE COALESCE(r.`IDorganization`, NULLIF(h.`IDorganization`, 0), root.`IDorganization`, 0) = :organization_id
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
            if ($viewScope === 'global') {
                $this[] = $rule;
                continue;
            }
            $sourceHolon = $rule->getHolon();
            if (!($sourceHolon instanceof Holon)) {
                if ($includeOrganizationRules && (int)$rule->get('IDorganization') === $organizationId
                    && (int)$rule->get('IDauthority') === 0 && (int)$rule->get('IDholon') === 0) {
                    $this[] = $rule;
                    continue;
                }
            }

            if ($viewScope === 'local') {
                if ($sourceHolon instanceof Holon && isset($contextHolons[(int)$sourceHolon->getId()])) $this[] = $rule;
                continue;
            }

            foreach ($contextHolons as $contextHolon) {
                if ($rule->appliesToHolon($contextHolon)) {
                    $this[] = $rule;
                    break;
                }
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
