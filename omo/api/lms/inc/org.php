<?php
require_once dirname(__DIR__) . '/bootstrap.php';

function lmsIsBasicCatalogMode(): bool
{
    return trim((string)($_REQUEST['catalog'] ?? '')) === 'basic';
}

function lmsGetScopeQueryParams(): array
{
    return lmsIsBasicCatalogMode()
        ? ['catalog' => 'basic']
        : [];
}

if (lmsIsBasicCatalogMode()) {
    $org = [
        'isValid' => true,
        'id' => 0,
        'name' => 'OMO',
        'shortname' => 'omo',
        'domain' => '',
        'logo' => '/img/logo-OGC.png',
        'banner' => '',
        'color' => '',
        'host' => commonGetRequestHost(),
        'error' => null,
        'isDemo' => false,
        'routeMode' => 'path',
    ];
} else {
    $defaultOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
    if ($defaultOrganizationId <= 0) {
        $defaultOrganizationId = 1;
    }

    $org = commonResolveOrganizationContext($defaultOrganizationId);
}

function lmsGetNavigationOrganizationId(): int
{
    if (lmsIsBasicCatalogMode()) {
        return 0;
    }

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

    foreach (lmsGetScopeQueryParams() as $key => $value) {
        if (!array_key_exists($key, $params)) {
            $params[$key] = $value;
        }
    }

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
