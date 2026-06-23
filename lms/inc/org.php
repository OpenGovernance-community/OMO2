<?php
require_once dirname(__DIR__, 2) . '/common/auth.php';

$defaultOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($defaultOrganizationId <= 0) {
    $defaultOrganizationId = 1;
}

$org = commonResolveOrganizationContext($defaultOrganizationId);

function lmsGetNavigationOrganizationId(): int
{
    $requestedOrganizationId = commonGetRequestedOrganizationId();
    if ($requestedOrganizationId > 0) {
        return $requestedOrganizationId;
    }

    $currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
    return $currentOrganizationId > 0 ? $currentOrganizationId : 0;
}

function lmsBuildLocalPath(string $path, array $params = []): string
{
    $normalizedPath = commonNormalizeLocalPath($path, '/lms/');
    $organizationId = lmsGetNavigationOrganizationId();

    if ($organizationId > 0 && !array_key_exists('oid', $params)) {
        $params['oid'] = $organizationId;
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }

    if ($params === []) {
        return $normalizedPath;
    }

    return $normalizedPath
        . (strpos($normalizedPath, '?') === false ? '?' : '&')
        . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}
?>
