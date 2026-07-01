<?php

if (!function_exists('commonHolonScopeGetCacheVersion')) {
    function commonHolonScopeGetCacheVersion(): int
    {
        return 2;
    }
}

if (!function_exists('commonHolonScopeBuildCacheMetadata')) {
    function commonHolonScopeBuildCacheMetadata(int $organizationId, int $userId): array
    {
        return [
            'cacheVersion' => commonHolonScopeGetCacheVersion(),
            'organizationId' => $organizationId,
            'userId' => $userId,
            'cachedAtHistoryId' => $organizationId > 0
                ? (int)\dbObject\History::getLatestOrganizationEntryId($organizationId)
                : 0,
        ];
    }
}

if (!function_exists('commonHolonScopeIsCacheEntryFresh')) {
    function commonHolonScopeIsCacheEntryFresh(array $cacheEntry, int $organizationId, int $userId): bool
    {
        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        if ((int)($cacheEntry['cacheVersion'] ?? 0) !== commonHolonScopeGetCacheVersion()) {
            return false;
        }

        if ((int)($cacheEntry['organizationId'] ?? 0) !== $organizationId) {
            return false;
        }

        if ((int)($cacheEntry['userId'] ?? 0) !== $userId) {
            return false;
        }

        $cachedAtHistoryId = (int)($cacheEntry['cachedAtHistoryId'] ?? -1);
        if ($cachedAtHistoryId < 0) {
            return false;
        }

        return $cachedAtHistoryId >= (int)\dbObject\History::getLatestOrganizationEntryId($organizationId);
    }
}

if (!function_exists('commonHolonScopeGetSessionCacheBucket')) {
    function commonHolonScopeGetSessionCacheBucket(): array
    {
        if (!isset($_SESSION['holonScopeOptionsByOrganization']) || !is_array($_SESSION['holonScopeOptionsByOrganization'])) {
            $_SESSION['holonScopeOptionsByOrganization'] = [];
        }

        return $_SESSION['holonScopeOptionsByOrganization'];
    }
}

if (!function_exists('commonHolonScopeClearSessionCache')) {
    function commonHolonScopeClearSessionCache(): void
    {
        unset($_SESSION['holonScopeOptionsByOrganization']);
    }
}

if (!function_exists('commonHolonScopeBuildOptionLabel')) {
    function commonHolonScopeBuildOptionLabel(\dbObject\Holon $holon, int $depth = 0): string
    {
        return str_repeat(' - ', max(0, $depth)) . trim((string)$holon->getDisplayName());
    }
}

if (!function_exists('commonHolonScopeSortChildren')) {
    function commonHolonScopeSortChildren(array $children): array
    {
        usort($children, static function ($left, $right) {
            $leftLabel = $left instanceof \dbObject\Holon ? trim((string)$left->getDisplayName()) : '';
            $rightLabel = $right instanceof \dbObject\Holon ? trim((string)$right->getDisplayName()) : '';

            return strcasecmp($leftLabel, $rightLabel);
        });

        return $children;
    }
}

if (!function_exists('commonHolonScopeLoadMemberVisibilityMap')) {
    function commonHolonScopeLoadMemberVisibilityMap(int $organizationId, array $holonIds, int $userId): array
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        $holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), static function ($holonId) {
            return $holonId > 0;
        })));

        if ($organizationId <= 0 || $userId <= 0 || count($holonIds) === 0) {
            return [
                'selectableIds' => [],
                'visibleIds' => [],
            ];
        }

        $selectableIds = [];
        foreach (\dbObject\UserHolon::fetchEffectiveRowsForUserAndHolonIds($userId, $holonIds) as $row) {
            $holonId = (int)($row['IDholon'] ?? 0);
            if ($holonId > 0) {
                $selectableIds[$holonId] = true;
            }
        }

        $visibleIds = $selectableIds;
        foreach (array_keys($selectableIds) as $memberHolonId) {
            $memberHolon = new \dbObject\Holon();
            if (!$memberHolon->load((int)$memberHolonId) || (int)$memberHolon->get('IDorganization') !== $organizationId) {
                continue;
            }

            foreach ($memberHolon->getPathHolons(true) as $pathHolon) {
                if (!$pathHolon instanceof \dbObject\Holon) {
                    continue;
                }

                $pathHolonId = (int)$pathHolon->getId();
                if ($pathHolonId > 0) {
                    $visibleIds[$pathHolonId] = true;
                }
            }
        }

        return [
            'selectableIds' => $selectableIds,
            'visibleIds' => $visibleIds,
        ];
    }
}

if (!function_exists('commonHolonScopeAppendOptionsFromTree')) {
    function commonHolonScopeAppendOptionsFromTree(\dbObject\Holon $holon, int $organizationId, array &$options, array $visibilityMap, int $depth = 0, array &$visited = [])
    {
        $holonId = (int)$holon->getId();
        if ($holonId <= 0 || $organizationId <= 0 || isset($visited[$holonId])) {
            return;
        }

        $visited[$holonId] = true;
        $visibleIds = is_array($visibilityMap['visibleIds'] ?? null) ? $visibilityMap['visibleIds'] : [];
        $selectableIds = is_array($visibilityMap['selectableIds'] ?? null) ? $visibilityMap['selectableIds'] : [];

        if (isset($visibleIds[$holonId])) {
            $options[] = [
                'id' => $holonId,
                'organizationId' => $organizationId,
                'label' => commonHolonScopeBuildOptionLabel($holon, $depth),
                'selectable' => isset($selectableIds[$holonId]),
                'disabled' => !isset($selectableIds[$holonId]),
            ];
        }

        $children = [];
        foreach ($holon->getChildren() as $child) {
            if (!$child instanceof \dbObject\Holon) {
                continue;
            }

            $children[] = $child;
        }

        foreach (commonHolonScopeSortChildren($children) as $child) {
            commonHolonScopeAppendOptionsFromTree($child, $organizationId, $options, $visibilityMap, $depth + 1, $visited);
        }
    }
}

if (!function_exists('commonHolonScopeLoadOptionsForOrganization')) {
    function commonHolonScopeLoadOptionsForOrganization(\dbObject\Organization $organization, bool $forceRefresh = false): array
    {
        $organizationId = (int)$organization->getId();
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : (int)($_SESSION['currentUser'] ?? 0);
        if ($organizationId <= 0) {
            return [];
        }

        $cacheBucket = commonHolonScopeGetSessionCacheBucket();
        if (
            !$forceRefresh
            && isset($cacheBucket[$organizationId])
            && commonHolonScopeIsCacheEntryFresh((array)$cacheBucket[$organizationId], $organizationId, $currentUserId)
        ) {
            return array_values((array)($cacheBucket[$organizationId]['options'] ?? []));
        }

        $rootHolon = $organization->getStructuralRootHolon();
        if (!$rootHolon instanceof \dbObject\Holon || (int)$rootHolon->getId() <= 0) {
            return [];
        }

        $allHolonIds = array_values(array_unique(array_filter(array_map('intval', $rootHolon->getVisibleDescendantIds(true)), static function ($holonId) {
            return $holonId > 0;
        })));
        $visibilityMap = commonHolonScopeLoadMemberVisibilityMap($organizationId, $allHolonIds, $currentUserId);

        $options = [];
        $visited = [];
        commonHolonScopeAppendOptionsFromTree($rootHolon, $organizationId, $options, $visibilityMap, 0, $visited);

        $cacheEntry = commonHolonScopeBuildCacheMetadata($organizationId, $currentUserId);
        $cacheEntry['options'] = array_values($options);
        $_SESSION['holonScopeOptionsByOrganization'][$organizationId] = $cacheEntry;

        return $options;
    }
}

if (!function_exists('commonHolonScopeResolveOrganizationHolon')) {
    function commonHolonScopeResolveOrganizationHolon(\dbObject\Organization $organization, int $holonId): ?\dbObject\Holon
    {
        $organizationId = (int)$organization->getId();
        $holonId = (int)$holonId;

        if ($organizationId <= 0 || $holonId <= 0) {
            return null;
        }

        $holon = new \dbObject\Holon();
        if (!$holon->load($holonId) || (int)$holon->get('IDorganization') !== $organizationId) {
            return null;
        }

        return $holon;
    }
}
