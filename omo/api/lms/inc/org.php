<?php
require_once dirname(__DIR__) . '/bootstrap.php';

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
    $normalizedPath = omoLmsBuildPath($path);
    $organizationId = lmsGetNavigationOrganizationId();

    if ($organizationId > 0 && !array_key_exists('oid', $params)) {
        $params['oid'] = $organizationId;
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }

    return omoLmsBuildPath($normalizedPath, $params);
}
?>
