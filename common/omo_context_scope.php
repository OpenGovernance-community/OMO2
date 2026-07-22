<?php

use dbObject\Holon;

if (!function_exists('omoApiIsStructuralScopeHolon')) {
    function omoApiIsStructuralScopeHolon($candidateHolon = null, $contextHolon = null)
    {
        if (!($candidateHolon instanceof Holon) || (int)$candidateHolon->getId() <= 0) {
            return false;
        }

        $rootHolonId = $contextHolon instanceof Holon ? (int)$contextHolon->get('IDholon_org') : 0;
        if ($rootHolonId <= 0 && $contextHolon instanceof Holon) {
            $rootHolonId = (int)$contextHolon->getId();
        }

        return !$candidateHolon->isTemplateNode($rootHolonId);
    }
}

if (!function_exists('omoApiCanUseDescendantScope')) {
    function omoApiCanUseDescendantScope($currentHolon = null, $rootHolon = null)
    {
        if (!($currentHolon instanceof Holon) || (int)$currentHolon->getId() <= 0) {
            return false;
        }

        return count(omoApiGetDirectChildHolonIds($currentHolon)) > 0;
    }
}

if (!function_exists('omoApiGetAvailableContextScopes')) {
    function omoApiGetAvailableContextScopes($canToggleScope, $currentHolon = null, $rootHolon = null)
    {
        $scopes = ['contextual'];

        if (!$canToggleScope) {
            return $scopes;
        }

        if (omoApiCanUseDescendantScope($currentHolon, $rootHolon)) {
            $scopes[] = 'children';
            $scopes[] = 'descendants';
        }

        return $scopes;
    }
}

if (!function_exists('omoApiNormalizeContextScope')) {
    function omoApiNormalizeContextScope($rawScope, array $allowedScopes = ['contextual', 'children', 'descendants'])
    {
        $allowedScopes = array_values(array_unique(array_filter(array_map(static function ($scope) {
            return trim(mb_strtolower((string)$scope, 'UTF-8'));
        }, $allowedScopes), static function ($scope) {
            return $scope !== '';
        })));

        if (count($allowedScopes) === 0) {
            $allowedScopes = ['contextual'];
        }

        $scope = trim(mb_strtolower((string)$rawScope, 'UTF-8'));
        if ($scope === 'global') {
            $scope = 'descendants';
        }
        if (in_array($scope, $allowedScopes, true)) {
            return $scope;
        }

        return in_array('contextual', $allowedScopes, true)
            ? 'contextual'
            : (string)$allowedScopes[0];
    }
}

if (!function_exists('omoApiGetDescendantHolonIds')) {
    function omoApiGetDescendantHolonIds($currentHolon = null)
    {
        if (!($currentHolon instanceof Holon) || (int)$currentHolon->getId() <= 0) {
            return [];
        }

        $holonIds = [];
        $collectHolonIds = static function (Holon $holon) use (&$collectHolonIds, &$holonIds, $currentHolon) {
            $holonId = (int)$holon->getId();
            if ($holonId <= 0 || isset($holonIds[$holonId])) {
                return;
            }

            $holonIds[$holonId] = $holonId;
            foreach ($holon->getChildren() as $childHolon) {
                if ($childHolon instanceof Holon && omoApiIsStructuralScopeHolon($childHolon, $currentHolon)) {
                    $collectHolonIds($childHolon);
                }
            }
        };
        $collectHolonIds($currentHolon);

        return array_values($holonIds);
    }
}

if (!function_exists('omoApiGetDirectChildHolonIds')) {
    function omoApiGetDirectChildHolonIds($currentHolon = null)
    {
        if (!($currentHolon instanceof Holon) || (int)$currentHolon->getId() <= 0) {
            return [];
        }

        $holonIds = [];
        $visitedGroupIds = [];
        $appendChildren = static function (Holon $parentHolon) use (&$appendChildren, &$holonIds, &$visitedGroupIds, $currentHolon) {
            foreach ($parentHolon->getChildren() as $childHolon) {
                if (!omoApiIsStructuralScopeHolon($childHolon, $currentHolon)) {
                    continue;
                }

                $childHolonId = (int)$childHolon->getId();
                if ($childHolonId <= 0) {
                    continue;
                }

                if ((int)$childHolon->get('IDtypeholon') === 3) {
                    if (isset($visitedGroupIds[$childHolonId])) {
                        continue;
                    }
                    $visitedGroupIds[$childHolonId] = true;
                    $appendChildren($childHolon);
                    continue;
                }

                $holonIds[$childHolonId] = $childHolonId;
            }
        };
        $appendChildren($currentHolon);

        return array_values($holonIds);
    }
}

if (!function_exists('omoApiGetDescendantHolonIdMap')) {
    function omoApiGetDescendantHolonIdMap($currentHolon = null)
    {
        $holonIds = omoApiGetDescendantHolonIds($currentHolon);
        return count($holonIds) > 0 ? array_fill_keys($holonIds, true) : [];
    }
}

if (!function_exists('omoApiGetDirectChildHolonIdMap')) {
    function omoApiGetDirectChildHolonIdMap($currentHolon = null)
    {
        $holonIds = omoApiGetDirectChildHolonIds($currentHolon);
        return count($holonIds) > 0 ? array_fill_keys($holonIds, true) : [];
    }
}

if (!function_exists('omoApiGetDirectChildScopeHolonIds')) {
    function omoApiGetDirectChildScopeHolonIds($currentHolon = null)
    {
        if (!($currentHolon instanceof Holon) || (int)$currentHolon->getId() <= 0) {
            return [];
        }

        return array_values(array_unique(array_merge(
            [(int)$currentHolon->getId()],
            omoApiGetDirectChildHolonIds($currentHolon)
        )));
    }
}

if (!function_exists('omoApiGetDirectChildScopeHolonIdMap')) {
    function omoApiGetDirectChildScopeHolonIdMap($currentHolon = null)
    {
        $holonIds = omoApiGetDirectChildScopeHolonIds($currentHolon);
        return count($holonIds) > 0 ? array_fill_keys($holonIds, true) : [];
    }
}

if (!function_exists('omoApiResolveContextScopeIndex')) {
    function omoApiResolveContextScopeIndex($currentScope, array $allowedScopes = ['contextual', 'children', 'descendants'])
    {
        $normalizedScope = omoApiNormalizeContextScope($currentScope, $allowedScopes);
        $index = array_search($normalizedScope, $allowedScopes, true);

        return $index === false ? 0 : (int)$index;
    }
}
