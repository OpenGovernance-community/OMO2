<?php
namespace dbObject;

class HolonPermission extends DbObject
{
    const PERMISSION_CACHE_VERSION = 15;
    const RANGE_SELF = 'self';
    const RANGE_PARENT_CIRCLE = 'parent_circle';
    const RANGE_PARENT_CIRCLE_ELEMENTS = 'parent_circle_elements';
    const RANGE_PARENT_CIRCLE_DESCENDANTS = 'parent_circle_descendants';
    const RANGE_ORGANIZATION = 'organization';

    public static function tableName()
    {
        return 'holon_permission';
    }

    public static function rules()
    {
        return [
            [['IDholon', 'IDpermission', 'range'], 'required'],
            [['id'], 'integer'],
            [['IDholon', 'IDpermission'], 'fk'],
            [['range'], 'string'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDholon' => 'Holon',
            'IDpermission' => 'Droit',
            'range' => 'Portee',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDholon' => 'Holon auquel le droit est attribue.',
            'IDpermission' => 'Droit accorde a ce holon.',
            'range' => 'Zone sur laquelle le droit peut etre exerce.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'range' => 40,
        ];
    }

    public static function getOrder()
    {
        return 'IDholon ASC, IDpermission ASC';
    }

    public static function getRangeLabels()
    {
        return [
            self::RANGE_SELF => 'Element courant',
            self::RANGE_PARENT_CIRCLE => 'Cercle englobant seul',
            self::RANGE_PARENT_CIRCLE_ELEMENTS => 'Elements du cercle parent',
            self::RANGE_PARENT_CIRCLE_DESCENDANTS => 'Cercle englobant et descendants',
            self::RANGE_ORGANIZATION => 'Toute l organisation',
        ];
    }

    public static function getEditorRangeCatalog()
    {
        $catalog = [];
        foreach (self::getRangeLabels() as $key => $label) {
            $catalog[] = [
                'key' => (string)$key,
                'label' => (string)$label,
            ];
        }

        return $catalog;
    }

    public static function getEditorRangeCatalogForPermission($permissionKey = '', $isContextual = null)
    {
        $isContextual = $isContextual === null ? true : (bool)$isContextual;
        $rangeLabels = $isContextual
            ? self::getRangeLabels()
            : [self::RANGE_ORGANIZATION => self::getRangeLabels()[self::RANGE_ORGANIZATION]];

        $catalog = [];
        foreach ($rangeLabels as $key => $label) {
            $catalog[] = [
                'key' => (string)$key,
                'label' => (string)$label,
            ];
        }

        return $catalog;
    }

    protected static function normalizeAssignmentRangesForPermission($permissionKey, $ranges)
    {
        $isContextual = \dbObject\Permission::isPermissionContextual($permissionKey, true);
        if (!$isContextual) {
            if (!is_array($ranges)) {
                $ranges = trim((string)$ranges) !== '' ? [$ranges] : [];
            }

            return count($ranges) > 0 ? [self::RANGE_ORGANIZATION] : [];
        }

        return self::normalizeAssignmentRanges($ranges);
    }

    public static function isValidRange($range)
    {
        return array_key_exists((string)$range, self::getRangeLabels());
    }

    public static function normalizeRange($range)
    {
        $range = trim((string)$range);
        return self::isValidRange($range) ? $range : self::RANGE_SELF;
    }

    public static function findByHolonAndPermission($holonId, $permissionId, $range = null)
    {
        $params = [
            'holon_id' => (int)$holonId,
            'permission_id' => (int)$permissionId,
        ];
        $sql = 'SELECT * FROM `holon_permission` WHERE `IDholon` = :holon_id AND `IDpermission` = :permission_id';
        if ($range !== null && trim((string)$range) !== '') {
            $sql .= ' AND `range` = :range';
            $params['range'] = self::normalizeRange($range);
        }
        $sql .= ' ORDER BY `id` ASC LIMIT 1';

        $row = self::fetchRow($sql, $params);

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public static function getAssignmentKeyMapForHolon($holonId)
    {
        $rows = self::fetchAll(
            'SELECT hp.`range`, p.`permission_key`
             FROM `holon_permission` hp
             INNER JOIN `permission` p ON p.`id` = hp.`IDpermission`
             WHERE hp.`IDholon` = :holon_id',
            [
                'holon_id' => (int)$holonId,
            ]
        );

        if (!is_array($rows)) {
            return [];
        }

        $assignments = [];
        foreach ($rows as $row) {
            $permissionKey = trim((string)($row['permission_key'] ?? ''));
            if ($permissionKey === '') {
                continue;
            }

            if (!isset($assignments[$permissionKey])) {
                $assignments[$permissionKey] = [];
            }

            $normalizedRange = self::normalizeRange($row['range'] ?? '');
            $assignments[$permissionKey][$normalizedRange] = $normalizedRange;
        }

        foreach ($assignments as $permissionKey => $ranges) {
            $assignments[$permissionKey] = array_values($ranges);
        }

        return $assignments;
    }

    protected static function normalizeAssignmentRanges($ranges)
    {
        if (!is_array($ranges)) {
            $ranges = trim((string)$ranges) !== '' ? [$ranges] : [];
        }

        $normalizedRanges = [];
        foreach ($ranges as $range) {
            $range = trim((string)$range);
            if ($range === '' || !self::isValidRange($range)) {
                continue;
            }

            $normalizedRange = self::normalizeRange($range);
            $normalizedRanges[$normalizedRange] = $normalizedRange;
        }

        return array_values($normalizedRanges);
    }

    public static function syncAssignmentsForHolon($holonId, array $assignmentsByPermissionKey)
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return false;
        }

        $normalizedAssignments = [];
        foreach ($assignmentsByPermissionKey as $permissionKey => $ranges) {
            $permissionKey = trim((string)$permissionKey);
            if ($permissionKey === '') {
                continue;
            }

            $normalizedRanges = self::normalizeAssignmentRangesForPermission($permissionKey, $ranges);
            if (count($normalizedRanges) === 0) {
                continue;
            }

            $normalizedAssignments[$permissionKey] = $normalizedRanges;
        }

        $existingRows = self::fetchAll(
            'SELECT hp.`id`, hp.`IDpermission`, hp.`range`, p.`permission_key`
             FROM `holon_permission` hp
             INNER JOIN `permission` p ON p.`id` = hp.`IDpermission`
             WHERE hp.`IDholon` = :holon_id',
            [
                'holon_id' => $holonId,
            ]
        );

        $existingByKey = [];
        if (is_array($existingRows)) {
            foreach ($existingRows as $row) {
                $permissionKey = trim((string)($row['permission_key'] ?? ''));
                if ($permissionKey === '') {
                    continue;
                }

                $range = self::normalizeRange($row['range'] ?? '');
                if (!isset($existingByKey[$permissionKey])) {
                    $existingByKey[$permissionKey] = [];
                }

                $existingByKey[$permissionKey][$range] = [
                    'id' => (int)($row['id'] ?? 0),
                ];
            }
        }

        foreach ($existingByKey as $permissionKey => $existingRanges) {
            foreach ($existingRanges as $range => $existingRow) {
                if (
                    isset($normalizedAssignments[$permissionKey])
                    && in_array($range, $normalizedAssignments[$permissionKey], true)
                ) {
                    continue;
                }

                if ((int)$existingRow['id'] <= 0) {
                    continue;
                }

                $item = new self();
                $item->setId((int)$existingRow['id']);
                if ($item->delete() === false) {
                    return false;
                }
            }
        }

        foreach ($normalizedAssignments as $permissionKey => $ranges) {
            $permission = \dbObject\Permission::findByKey($permissionKey);
            if (!$permission || (int)$permission->getId() <= 0) {
                continue;
            }

            foreach ($ranges as $range) {
                $item = self::findByHolonAndPermission($holonId, (int)$permission->getId(), $range);
                if (!$item) {
                    $item = new self();
                }

                $item->set('IDholon', $holonId);
                $item->set('IDpermission', (int)$permission->getId());
                $item->set('range', self::normalizeRange($range));
                $saveResult = $item->save();
                if (empty($saveResult['status'])) {
                    return false;
                }
            }
        }

        return true;
    }

    protected static function resolveOrganizationRootHolonId($organizationId)
    {
        return (int)self::fetchValue(
            'SELECT `id`
             FROM `holon`
             WHERE `IDorganization` = :organization_id
               AND `active` = 1
               AND `visible` = 1
               AND (`IDholon_parent` IS NULL OR `IDholon_parent` = 0)
             ORDER BY `id` ASC
             LIMIT 1',
            [
                'organization_id' => (int)$organizationId,
            ]
        );
    }

    protected static function loadOrganizationHolonRows($organizationRootHolonId)
    {
        $organizationRootHolonId = (int)$organizationRootHolonId;
        if ($organizationRootHolonId <= 0) {
            return [];
        }

        $rootRow = self::fetchRow(
            'SELECT `id`, `name`, `IDtypeholon`, `IDholon_parent`, `IDholon_template`, `IDorganization`, `IDholon_org`, `active`
             FROM `holon`
             WHERE `id` = :root_holon_id
               AND `active` = 1
             LIMIT 1',
            [
                'root_holon_id' => $organizationRootHolonId,
            ]
        );

        if (!is_array($rootRow) || (int)($rootRow['id'] ?? 0) <= 0) {
            return [];
        }

        $indexedRows = [];
        $rows = [$rootRow];

        while (count($rows) > 0) {
            $nextFrontierHolonIds = [];

            foreach ($rows as $row) {
                $holonId = (int)($row['id'] ?? 0);
                if ($holonId <= 0 || isset($indexedRows[$holonId])) {
                    continue;
                }

                $indexedRows[$holonId] = [
                    'id' => $holonId,
                    'name' => (string)($row['name'] ?? ''),
                    'IDtypeholon' => (int)($row['IDtypeholon'] ?? 0),
                    'IDholon_parent' => (int)($row['IDholon_parent'] ?? 0),
                    'IDholon_template' => (int)($row['IDholon_template'] ?? 0),
                    'IDorganization' => (int)($row['IDorganization'] ?? 0),
                    'IDholon_org' => (int)($row['IDholon_org'] ?? 0),
                    'active' => !empty($row['active']),
                ];

                $nextFrontierHolonIds[] = $holonId;
            }

            if (count($nextFrontierHolonIds) === 0) {
                break;
            }

            $params = [];
            $parentPlaceholders = self::buildIntegerInClauseSql($nextFrontierHolonIds, 'parent_holon_id', $params);
            if ($parentPlaceholders === '') {
                break;
            }

            $rows = self::fetchAll(
                'SELECT `id`, `name`, `IDtypeholon`, `IDholon_parent`, `IDholon_template`, `IDorganization`, `IDholon_org`, `active`
                 FROM `holon`
                 WHERE `active` = 1
                   AND `IDholon_parent` IN (' . $parentPlaceholders . ')',
                $params
            );

            if (!is_array($rows)) {
                break;
            }
        }

        $organizationRows = self::fetchAll(
            'SELECT `id`, `name`, `IDtypeholon`, `IDholon_parent`, `IDholon_template`, `IDorganization`, `IDholon_org`, `active`
             FROM `holon`
             WHERE `active` = 1
               AND (`id` = :root_holon_id_self OR `IDholon_org` = :root_holon_id_org)',
            [
                'root_holon_id_self' => $organizationRootHolonId,
                'root_holon_id_org' => $organizationRootHolonId,
            ]
        );

        if (is_array($organizationRows)) {
            foreach ($organizationRows as $row) {
                $holonId = (int)($row['id'] ?? 0);
                if ($holonId <= 0 || isset($indexedRows[$holonId])) {
                    continue;
                }

                $indexedRows[$holonId] = [
                    'id' => $holonId,
                    'name' => (string)($row['name'] ?? ''),
                    'IDtypeholon' => (int)($row['IDtypeholon'] ?? 0),
                    'IDholon_parent' => (int)($row['IDholon_parent'] ?? 0),
                    'IDholon_template' => (int)($row['IDholon_template'] ?? 0),
                    'IDorganization' => (int)($row['IDorganization'] ?? 0),
                    'IDholon_org' => (int)($row['IDholon_org'] ?? 0),
                    'active' => !empty($row['active']),
                ];
            }
        }

        return $indexedRows;
    }

    protected static function buildIntegerInClauseSql(array $values, $paramPrefix, array &$params)
    {
        $values = array_values(array_unique(array_filter(array_map('intval', $values), static function ($value) {
            return $value > 0;
        })));

        if (count($values) === 0) {
            return '';
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $paramName = $paramPrefix . '_' . $index;
            $params[$paramName] = $value;
            $placeholders[] = ':' . $paramName;
        }

        return implode(', ', $placeholders);
    }

    protected static function buildPermissionKeyFilterSql(array $permissionKeys, array &$params)
    {
        $permissionKeys = array_values(array_filter(array_map('strval', $permissionKeys), static function ($value) {
            return trim($value) !== '';
        }));

        if (count($permissionKeys) === 0) {
            return '';
        }

        $placeholders = [];
        foreach ($permissionKeys as $index => $permissionKey) {
            $paramName = 'permission_key_' . $index;
            $params[$paramName] = trim($permissionKey);
            $placeholders[] = ':' . $paramName;
        }

        return ' AND p.`permission_key` IN (' . implode(', ', $placeholders) . ')';
    }

    protected static function hasActiveUserOrganizationMembership($userId, $organizationId)
    {
        return (bool)self::fetchValue(
            'SELECT 1
             FROM `user_organization`
             WHERE `IDuser` = :user_id
               AND `IDorganization` = :organization_id
               AND `active` = 1
             LIMIT 1',
            [
                'user_id' => (int)$userId,
                'organization_id' => (int)$organizationId,
            ]
        );
    }

    protected static function loadActiveUserHolonRowsForOrganization($userId, array $organizationHolonIds, $organizationId = 0, $organizationRootHolonId = 0)
    {
        $userId = (int)$userId;
        if ($userId <= 0 || count($organizationHolonIds) === 0) {
            return [];
        }

        $rows = \dbObject\UserHolon::fetchEffectiveRowsForUserAndHolonIds($userId, $organizationHolonIds);
        return is_array($rows) ? $rows : [];
    }

    protected static function loadPermissionAssignmentsForOrganization(array $organizationHolonIds, array $permissionKeys = [])
    {
        if (count($organizationHolonIds) === 0) {
            return [];
        }

        $params = [];
        $holonPlaceholders = self::buildIntegerInClauseSql($organizationHolonIds, 'permission_holon_id', $params);
        if ($holonPlaceholders === '') {
            return [];
        }

        $permissionFilterSql = self::buildPermissionKeyFilterSql($permissionKeys, $params);

        $rows = self::fetchAll(
            'SELECT hp.`IDholon`, hp.`range`, p.`permission_key`
             FROM `holon_permission` hp
             INNER JOIN `permission` p ON p.`id` = hp.`IDpermission`
             INNER JOIN `holon` h ON h.`id` = hp.`IDholon`
             WHERE h.`active` = 1
               AND h.`id` IN (' . $holonPlaceholders . ')' . $permissionFilterSql,
            $params
        );

        return is_array($rows) ? $rows : [];
    }

    protected static function collectTemplateChainHolonIds($holonId, array $holonsById)
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0 || !isset($holonsById[$holonId])) {
            return [];
        }

        $collected = [];
        $visited = [];
        $currentHolonId = $holonId;
        $guard = 0;

        while ($currentHolonId > 0 && isset($holonsById[$currentHolonId]) && $guard < 50) {
            if (isset($visited[$currentHolonId])) {
                break;
            }

            $visited[$currentHolonId] = true;
            $collected[$currentHolonId] = $currentHolonId;
            $currentHolonId = (int)($holonsById[$currentHolonId]['IDholon_template'] ?? 0);
            $guard += 1;
        }

        return array_values($collected);
    }

    protected static function collectAncestorChainHolonIds($holonId, array $holonsById)
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0 || !isset($holonsById[$holonId])) {
            return [];
        }

        $collected = [];
        $visited = [];
        $currentHolonId = $holonId;
        $guard = 0;

        while ($currentHolonId > 0 && isset($holonsById[$currentHolonId]) && $guard < 100) {
            if (isset($visited[$currentHolonId])) {
                break;
            }

            $visited[$currentHolonId] = true;
            $collected[$currentHolonId] = $currentHolonId;
            $currentHolonId = (int)($holonsById[$currentHolonId]['IDholon_parent'] ?? 0);
            $guard += 1;
        }

        return array_values($collected);
    }

    protected static function collectPermissionSourceHolonIds($assignedHolonId, array $holonsById)
    {
        $collected = [];
        $assignedHolonId = (int)$assignedHolonId;
        if ($assignedHolonId > 0) {
            $collected[$assignedHolonId] = $assignedHolonId;
        }

        foreach (self::collectTemplateChainHolonIds($assignedHolonId, $holonsById) as $holonId) {
            $collected[(int)$holonId] = (int)$holonId;
        }

        return array_values($collected);
    }

    protected static function resolveContainingCircleIdFromRows($holonId, array $holonsById, $includeSelf = false)
    {
        $currentHolonId = $includeSelf ? (int)$holonId : (int)($holonsById[(int)$holonId]['IDholon_parent'] ?? 0);
        $guard = 0;

        while ($currentHolonId > 0 && isset($holonsById[$currentHolonId]) && $guard < 100) {
            if ((int)($holonsById[$currentHolonId]['IDtypeholon'] ?? 0) === 2) {
                return $currentHolonId;
            }

            $currentHolonId = (int)($holonsById[$currentHolonId]['IDholon_parent'] ?? 0);
            $guard += 1;
        }

        return 0;
    }

    protected static function collectCircleElementHolonIdsFromRows($circleHolonId, array $holonsById)
    {
        $circleHolonId = (int)$circleHolonId;
        if ($circleHolonId <= 0 || !isset($holonsById[$circleHolonId])) {
            return [];
        }

        $collectedHolonIds = [];
        $appendGroupElements = function ($parentHolonId) use (&$appendGroupElements, &$collectedHolonIds, $holonsById) {
            foreach ($holonsById as $holonId => $row) {
                if ((int)($row['IDholon_parent'] ?? 0) !== (int)$parentHolonId) {
                    continue;
                }

                $holonId = (int)$holonId;
                if ($holonId <= 0) {
                    continue;
                }

                $collectedHolonIds[$holonId] = $holonId;

                if ((int)($row['IDtypeholon'] ?? 0) === 3) {
                    $appendGroupElements($holonId);
                }
            }
        };

        $appendGroupElements($circleHolonId);

        return array_values($collectedHolonIds);
    }

    protected static function applyResolvedScopeToPermissionSet(array &$permissionSet, $permissionKey, array $resolvedScope)
    {
        $permissionKey = trim((string)$permissionKey);
        if ($permissionKey === '') {
            return;
        }

        if (!isset($permissionSet['permissions'][$permissionKey])) {
            $permissionSet['permissions'][$permissionKey] = [
                'exact' => [],
                'subtree' => [],
                'organization' => false,
            ];
        }

        $scopeType = (string)($resolvedScope['type'] ?? '');
        $targetHolonId = (int)($resolvedScope['holonId'] ?? 0);
        $targetHolonIds = [];
        foreach (($resolvedScope['holonIds'] ?? []) as $holonId) {
            $holonId = (int)$holonId;
            if ($holonId > 0) {
                $targetHolonIds[$holonId] = $holonId;
            }
        }

        if ($scopeType === 'organization') {
            $permissionSet['permissions'][$permissionKey]['organization'] = true;
            return;
        }

        if (count($targetHolonIds) === 0 && $targetHolonId > 0) {
            $targetHolonIds[$targetHolonId] = $targetHolonId;
        }

        if (count($targetHolonIds) === 0) {
            return;
        }

        if ($scopeType === 'subtree') {
            foreach ($targetHolonIds as $holonId) {
                $permissionSet['permissions'][$permissionKey]['subtree'][$holonId] = true;
            }
            return;
        }

        if ($scopeType === 'exact') {
            foreach ($targetHolonIds as $holonId) {
                $permissionSet['permissions'][$permissionKey]['exact'][$holonId] = true;
            }
        }
    }

    protected static function resolveRangeScopeForAssignedHolon($assignedHolonId, $range, array $holonsById, $organizationRootHolonId)
    {
        $assignedHolonId = (int)$assignedHolonId;
        $organizationRootHolonId = (int)$organizationRootHolonId;
        $range = self::normalizeRange($range);

        if ($assignedHolonId <= 0 || !isset($holonsById[$assignedHolonId])) {
            return ['type' => 'none', 'holonId' => 0];
        }

        switch ($range) {
            case self::RANGE_ORGANIZATION:
                return ['type' => 'organization', 'holonId' => 0];

            case self::RANGE_PARENT_CIRCLE_DESCENDANTS:
                $circleId = self::resolveContainingCircleIdFromRows($assignedHolonId, $holonsById, true);
                if ($circleId > 0) {
                    return ['type' => 'subtree', 'holonId' => $circleId];
                }
                return ['type' => 'organization', 'holonId' => 0];

            case self::RANGE_PARENT_CIRCLE_ELEMENTS:
                $circleId = self::resolveContainingCircleIdFromRows($assignedHolonId, $holonsById, true);
                if ($circleId > 0) {
                    return [
                        'type' => 'exact',
                        'holonIds' => self::collectCircleElementHolonIdsFromRows($circleId, $holonsById),
                    ];
                }
                return ['type' => 'none', 'holonId' => 0];

            case self::RANGE_PARENT_CIRCLE:
                $circleId = self::resolveContainingCircleIdFromRows($assignedHolonId, $holonsById, true);
                if ($circleId > 0) {
                    return ['type' => 'exact', 'holonId' => $circleId];
                }
                return ['type' => 'exact', 'holonId' => $organizationRootHolonId];

            case self::RANGE_SELF:
            default:
                return ['type' => 'exact', 'holonId' => $assignedHolonId];
        }
    }

    public static function buildPermissionDebugForOrganization($userId, $organizationId, array $permissionKeys = [])
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $debug = [
            'userId' => $userId,
            'organizationId' => $organizationId,
            'organizationRootHolonId' => 0,
            'organizationHolonIds' => [],
            'hasActiveOrganizationMembership' => false,
            'rawUserHolonRows' => [],
            'activeUserHolonRows' => [],
            'permissionAssignments' => [],
            'permissionSourceHolonIdsByAssignedHolonId' => [],
            'permissionSet' => [
                'cacheVersion' => self::PERMISSION_CACHE_VERSION,
                'userId' => $userId,
                'organizationId' => $organizationId,
                'definedPermissionKeys' => [],
                'permissions' => [],
            ],
        ];

        if ($userId <= 0 || $organizationId <= 0) {
            return $debug;
        }

        $organizationRootHolonId = self::resolveOrganizationRootHolonId($organizationId);
        $debug['organizationRootHolonId'] = $organizationRootHolonId;
        if ($organizationRootHolonId <= 0) {
            return $debug;
        }

        $holonsById = self::loadOrganizationHolonRows($organizationRootHolonId);
        $organizationHolonIds = array_keys($holonsById);
        $debug['organizationHolonIds'] = $organizationHolonIds;
        $debug['hasActiveOrganizationMembership'] = self::hasActiveUserOrganizationMembership($userId, $organizationId);

        $debug['rawUserHolonRows'] = \dbObject\UserHolon::fetchRawRowsForUserAndHolonIds($userId, $organizationHolonIds);
        $activeUserHolonRows = self::loadActiveUserHolonRowsForOrganization($userId, $organizationHolonIds, $organizationId, $organizationRootHolonId);
        $debug['activeUserHolonRows'] = $activeUserHolonRows;

        $permissionAssignments = self::loadPermissionAssignmentsForOrganization($organizationHolonIds, $permissionKeys);
        $debug['permissionAssignments'] = $permissionAssignments;

        foreach ($activeUserHolonRows as $membershipRow) {
            $assignedHolonId = (int)($membershipRow['IDholon'] ?? 0);
            if ($assignedHolonId <= 0) {
                continue;
            }

            $debug['permissionSourceHolonIdsByAssignedHolonId'][$assignedHolonId] = self::collectPermissionSourceHolonIds($assignedHolonId, $holonsById);
        }

        $debug['permissionSet'] = self::buildUserPermissionSetForOrganization($userId, $organizationId, $permissionKeys);
        return $debug;
    }

    public static function buildEffectivePermissionDetailsForOrganization($userId, $organizationId, array $permissionKeys = [])
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $details = [
            'cacheVersion' => self::PERMISSION_CACHE_VERSION,
            'userId' => $userId,
            'organizationId' => $organizationId,
            'adminOverrideActive' => false,
            'rows' => [],
        ];

        if ($userId <= 0 || $organizationId <= 0) {
            return $details;
        }

        if (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($userId, $organizationId)) {
            $details['adminOverrideActive'] = true;
            foreach (\dbObject\Permission::getEditorCatalog() as $permissionEntry) {
                $permissionKey = trim((string)($permissionEntry['key'] ?? ''));
                if ($permissionKey === '') {
                    continue;
                }

                if (count($permissionKeys) > 0 && !in_array($permissionKey, $permissionKeys, true)) {
                    continue;
                }

                $dedupeKey = implode('|', [$permissionKey, 'organization', '0', '0', '0', self::RANGE_ORGANIZATION]);
                $details['rows'][$dedupeKey] = [
                    'permissionKey' => $permissionKey,
                    'isContextual' => (bool)($permissionEntry['isContextual'] ?? true),
                    'scopeType' => 'organization',
                    'scopeHolonId' => 0,
                    'assignedHolonId' => 0,
                    'sourceHolonId' => 0,
                    'range' => self::RANGE_ORGANIZATION,
                ];
            }

            $details['rows'] = array_values($details['rows']);
            return $details;
        }

        $organizationRootHolonId = self::resolveOrganizationRootHolonId($organizationId);
        if ($organizationRootHolonId <= 0) {
            return $details;
        }

        $holonsById = self::loadOrganizationHolonRows($organizationRootHolonId);
        if (count($holonsById) === 0) {
            return $details;
        }

        $organizationHolonIds = array_keys($holonsById);
        $permissionAssignments = self::loadPermissionAssignmentsForOrganization($organizationHolonIds, $permissionKeys);
        if (count($permissionAssignments) === 0) {
            return $details;
        }

        $permissionContextualMap = \dbObject\Permission::getContextualMap(array_values(array_unique(array_map(static function ($assignmentRow) {
            return trim((string)($assignmentRow['permission_key'] ?? ''));
        }, $permissionAssignments))));
        $assignmentsByHolonId = [];
        foreach ($permissionAssignments as $assignmentRow) {
            $permissionHolonId = (int)($assignmentRow['IDholon'] ?? 0);
            $permissionKey = trim((string)($assignmentRow['permission_key'] ?? ''));
            if ($permissionHolonId <= 0 || $permissionKey === '') {
                continue;
            }

            if (!isset($assignmentsByHolonId[$permissionHolonId])) {
                $assignmentsByHolonId[$permissionHolonId] = [];
            }

            $assignmentsByHolonId[$permissionHolonId][] = [
                'permission_key' => $permissionKey,
                'range' => self::normalizeRange($assignmentRow['range'] ?? ''),
                'is_contextual' => (bool)($permissionContextualMap[$permissionKey] ?? true),
            ];
        }

        if (count($assignmentsByHolonId) === 0) {
            return $details;
        }

        $activeUserHolonRows = self::loadActiveUserHolonRowsForOrganization($userId, $organizationHolonIds, $organizationId, $organizationRootHolonId);
        foreach ($activeUserHolonRows as $membershipRow) {
            $assignedHolonId = (int)($membershipRow['IDholon'] ?? 0);
            if ($assignedHolonId <= 0 || !isset($holonsById[$assignedHolonId])) {
                continue;
            }

            $permissionSourceHolonIds = self::collectPermissionSourceHolonIds($assignedHolonId, $holonsById);
            foreach ($permissionSourceHolonIds as $permissionSourceHolonId) {
                if (empty($assignmentsByHolonId[$permissionSourceHolonId])) {
                    continue;
                }

                foreach ($assignmentsByHolonId[$permissionSourceHolonId] as $assignment) {
                    $permissionKey = trim((string)($assignment['permission_key'] ?? ''));
                    if ($permissionKey === '') {
                        continue;
                    }

                    if (!empty($permissionKeys) && !in_array($permissionKey, $permissionKeys, true)) {
                        continue;
                    }

                    if (!empty($assignment['is_contextual'])) {
                        $resolvedScope = self::resolveRangeScopeForAssignedHolon(
                            $assignedHolonId,
                            $assignment['range'] ?? self::RANGE_SELF,
                            $holonsById,
                            $organizationRootHolonId
                        );
                    } else {
                        $resolvedScope = [
                            'type' => 'organization',
                            'holonId' => 0,
                        ];
                    }

                    $scopeType = (string)($resolvedScope['type'] ?? 'none');
                    $scopeHolonId = (int)($resolvedScope['holonId'] ?? 0);
                    if ($scopeType === 'none') {
                        continue;
                    }

                    $dedupeKey = implode('|', [
                        $permissionKey,
                        $scopeType,
                        (string)$scopeHolonId,
                        (string)$assignedHolonId,
                        (string)$permissionSourceHolonId,
                        (string)($assignment['range'] ?? self::RANGE_SELF),
                    ]);
                    $details['rows'][$dedupeKey] = [
                        'permissionKey' => $permissionKey,
                        'isContextual' => !empty($assignment['is_contextual']),
                        'scopeType' => $scopeType,
                        'scopeHolonId' => $scopeHolonId,
                        'assignedHolonId' => $assignedHolonId,
                        'sourceHolonId' => (int)$permissionSourceHolonId,
                        'range' => (string)($assignment['range'] ?? self::RANGE_SELF),
                    ];
                }
            }
        }

        $details['rows'] = array_values($details['rows']);
        return $details;
    }

    public static function buildUserPermissionSetForOrganization($userId, $organizationId, array $permissionKeys = [])
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $permissionSet = [
            'cacheVersion' => self::PERMISSION_CACHE_VERSION,
            'userId' => $userId,
            'organizationId' => $organizationId,
            'definedPermissionKeys' => [],
            'permissions' => [],
        ];

        if ($userId <= 0 || $organizationId <= 0) {
            return $permissionSet;
        }

        if (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($userId, $organizationId)) {
            foreach ($permissionKeys as $permissionKey) {
                $permissionKey = trim((string)$permissionKey);
                if ($permissionKey === '') {
                    continue;
                }
                $permissionSet['definedPermissionKeys'][$permissionKey] = true;
                $permissionSet['permissions'][$permissionKey] = [
                    'exact' => [],
                    'subtree' => [],
                    'organization' => true,
                ];
            }
            return $permissionSet;
        }

        $organizationRootHolonId = self::resolveOrganizationRootHolonId($organizationId);
        if ($organizationRootHolonId <= 0) {
            return $permissionSet;
        }

        $holonsById = self::loadOrganizationHolonRows($organizationRootHolonId);
        if (count($holonsById) === 0) {
            return $permissionSet;
        }

        $organizationHolonIds = array_keys($holonsById);

        $permissionAssignments = self::loadPermissionAssignmentsForOrganization($organizationHolonIds, $permissionKeys);
        if (count($permissionAssignments) === 0) {
            return $permissionSet;
        }

        $assignmentsByHolonId = [];
        $definedPermissionKeys = [];
        $permissionContextualMap = \dbObject\Permission::getContextualMap(array_values(array_unique(array_map(static function ($assignmentRow) {
            return trim((string)($assignmentRow['permission_key'] ?? ''));
        }, $permissionAssignments))));
        foreach ($permissionAssignments as $assignmentRow) {
            $permissionHolonId = (int)($assignmentRow['IDholon'] ?? 0);
            $permissionKey = trim((string)($assignmentRow['permission_key'] ?? ''));
            if ($permissionHolonId <= 0 || $permissionKey === '') {
                continue;
            }

            $definedPermissionKeys[$permissionKey] = true;

            if (!isset($assignmentsByHolonId[$permissionHolonId])) {
                $assignmentsByHolonId[$permissionHolonId] = [];
            }

            $assignmentsByHolonId[$permissionHolonId][] = [
                'permission_key' => $permissionKey,
                'range' => self::normalizeRange($assignmentRow['range'] ?? ''),
                'is_contextual' => (bool)($permissionContextualMap[$permissionKey] ?? true),
            ];
        }

        if (count($assignmentsByHolonId) === 0) {
            return $permissionSet;
        }

        $permissionSet['definedPermissionKeys'] = $definedPermissionKeys;

        $activeUserHolonRows = self::loadActiveUserHolonRowsForOrganization($userId, $organizationHolonIds, $organizationId, $organizationRootHolonId);
        foreach ($activeUserHolonRows as $membershipRow) {
            $assignedHolonId = (int)($membershipRow['IDholon'] ?? 0);
            if ($assignedHolonId <= 0 || !isset($holonsById[$assignedHolonId])) {
                continue;
            }

            $permissionSourceHolonIds = self::collectPermissionSourceHolonIds($assignedHolonId, $holonsById);
            foreach ($permissionSourceHolonIds as $permissionSourceHolonId) {
                if (empty($assignmentsByHolonId[$permissionSourceHolonId])) {
                    continue;
                }

                foreach ($assignmentsByHolonId[$permissionSourceHolonId] as $assignment) {
                    $permissionKey = trim((string)($assignment['permission_key'] ?? ''));
                    if ($permissionKey === '') {
                        continue;
                    }

                    if (empty($assignment['is_contextual'])) {
                        if (!isset($permissionSet['permissions'][$permissionKey])) {
                            $permissionSet['permissions'][$permissionKey] = [
                                'exact' => [],
                                'subtree' => [],
                                'organization' => false,
                            ];
                        }

                        $permissionSet['permissions'][$permissionKey]['organization'] = true;
                        continue;
                    }

                    $resolvedScope = self::resolveRangeScopeForAssignedHolon(
                        $assignedHolonId,
                        $assignment['range'] ?? self::RANGE_SELF,
                        $holonsById,
                        $organizationRootHolonId
                    );
                    self::applyResolvedScopeToPermissionSet(
                        $permissionSet,
                        $permissionKey,
                        $resolvedScope
                    );
                }
            }
        }

        return $permissionSet;
    }

    public static function userHasPermissionForHolonContext($userId, $organizationId, $permissionKey, $contextHolonId)
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $permissionKey = trim((string)$permissionKey);
        $contextHolonId = (int)$contextHolonId;

        if ($userId <= 0 || $organizationId <= 0 || $permissionKey === '') {
            return false;
        }

        $permissionSet = self::buildUserPermissionSetForOrganization($userId, $organizationId, [$permissionKey]);
        if (empty($permissionSet['definedPermissionKeys'][$permissionKey])) {
            return false;
        }

        $scope = $permissionSet['permissions'][$permissionKey] ?? null;
        if (!is_array($scope)) {
            return false;
        }

        if (!empty($scope['organization'])) {
            return true;
        }

        if ($contextHolonId <= 0) {
            return false;
        }

        if (!empty($scope['exact'][$contextHolonId])) {
            return true;
        }

        if (empty($scope['subtree'])) {
            return false;
        }

        $contextHolon = new \dbObject\Holon();
        if (!$contextHolon->load($contextHolonId)) {
            return false;
        }

        $contextOrganizationId = (int)$contextHolon->get('IDorganization');
        if ($contextOrganizationId <= 0) {
            $rootHolonId = (int)$contextHolon->get('IDholon_org');
            if ($rootHolonId > 0) {
                $rootHolon = new \dbObject\Holon();
                if ($rootHolon->load($rootHolonId)) {
                    $contextOrganizationId = (int)$rootHolon->get('IDorganization');
                }
            }
        }

        if ($contextOrganizationId !== $organizationId) {
            return false;
        }

        foreach (array_keys($scope['subtree']) as $rootHolonId) {
            if ($contextHolon->isDescendantOf((int)$rootHolonId, true)) {
                return true;
            }
        }

        return false;
    }

    public static function saveAssignment($holonId, $permissionId, $range)
    {
        $holonId = (int)$holonId;
        $permissionId = (int)$permissionId;

        if ($holonId <= 0 || $permissionId <= 0) {
            return false;
        }

        $permission = new \dbObject\Permission();
        if ($permission->load($permissionId) && !$permission->isContextual()) {
            $range = self::RANGE_ORGANIZATION;
        } else {
            $range = self::normalizeRange($range);
        }

        $existing = self::findByHolonAndPermission($holonId, $permissionId, $range);
        $item = $existing instanceof self ? $existing : new self();
        $item->set('IDholon', $holonId);
        $item->set('IDpermission', $permissionId);
        $item->set('range', $range);
        $result = $item->save();

        return !empty($result['status']) ? $item : false;
    }

    public function getNormalizedRange()
    {
        return self::normalizeRange($this->get('range'));
    }

    public function getRangeLabel()
    {
        $labels = self::getRangeLabels();
        $range = $this->getNormalizedRange();
        return $labels[$range] ?? $labels[self::RANGE_SELF];
    }
}

?>
