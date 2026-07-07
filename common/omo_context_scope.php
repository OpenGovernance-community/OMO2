<?php

use dbObject\Holon;

if (!function_exists('omoApiCanUseDescendantScope')) {
    function omoApiCanUseDescendantScope($currentHolon = null, $rootHolon = null)
    {
        if (!($currentHolon instanceof Holon) || (int)$currentHolon->getId() <= 0) {
            return false;
        }

        if ($rootHolon instanceof Holon && (int)$rootHolon->getId() === (int)$currentHolon->getId()) {
            return false;
        }

        return true;
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
            $scopes[] = 'descendants';
        }

        $scopes[] = 'global';
        return $scopes;
    }
}

if (!function_exists('omoApiNormalizeContextScope')) {
    function omoApiNormalizeContextScope($rawScope, array $allowedScopes = ['contextual', 'global'])
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

        return array_values(array_unique(array_filter(array_map('intval', $currentHolon->getVisibleDescendantIds(true)), static function ($holonId) {
            return $holonId > 0;
        })));
    }
}

if (!function_exists('omoApiGetDescendantHolonIdMap')) {
    function omoApiGetDescendantHolonIdMap($currentHolon = null)
    {
        $holonIds = omoApiGetDescendantHolonIds($currentHolon);
        return count($holonIds) > 0 ? array_fill_keys($holonIds, true) : [];
    }
}

if (!function_exists('omoApiResolveContextScopeIndex')) {
    function omoApiResolveContextScopeIndex($currentScope, array $allowedScopes = ['contextual', 'global'])
    {
        $normalizedScope = omoApiNormalizeContextScope($currentScope, $allowedScopes);
        $index = array_search($normalizedScope, $allowedScopes, true);

        return $index === false ? 0 : (int)$index;
    }
}
