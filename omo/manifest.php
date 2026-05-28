<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

function omoManifestBuildIconVersionToken(array $organizationContext)
{
    $payload = implode('|', [
        (string)($organizationContext['id'] ?? 0),
        (string)($organizationContext['logo'] ?? ''),
        (string)($organizationContext['color'] ?? ''),
    ]);

    return substr(sha1($payload), 0, 12);
}

function omoManifestBuildIconUrl(array $organizationContext, $size, $purpose = 'any')
{
    $query = [
        'size' => (int)$size,
        'v' => omoManifestBuildIconVersionToken($organizationContext),
    ];

    $organizationId = (int)($organizationContext['id'] ?? 0);
    if ($organizationId > 0) {
        $query['oid'] = $organizationId;
    }

    if ($purpose === 'maskable') {
        $query['purpose'] = 'maskable';
    }

    return '/omo/manifest_icon.php?' . http_build_query($query);
}

$organizationContext = commonResolveOrganizationContext(1);
$requestedOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : 0;

if ($requestedOrganizationId > 0) {
    $requestedOrganization = new \dbObject\Organization();
    if ($requestedOrganization->load($requestedOrganizationId)) {
        $organizationContext = [
            'isValid' => true,
            'id' => (int)$requestedOrganization->getId(),
            'name' => (string)$requestedOrganization->get('name'),
            'shortname' => (string)$requestedOrganization->get('shortname'),
            'domain' => (string)$requestedOrganization->get('domain'),
            'logo' => (string)$requestedOrganization->get('logo'),
            'banner' => (string)$requestedOrganization->get('banner'),
            'color' => trim((string)$requestedOrganization->get('color')),
            'host' => commonGetRequestHost(),
            'error' => null,
            'isDemo' => commonIsDemoHost(),
            'routeMode' => 'path',
        ];
    }
}

$defaultName = 'OpenMyOrganization';
$defaultShortName = 'OMO';
$defaultIcon = '/omo/icons/icon-512.png';
$defaultIcon192 = '/omo/icons/icon-192.png';
$defaultMaskableIcon = '/omo/icons/icon-maskable-512.png';
$defaultThemeColor = '#004663';

$appName = trim((string)($organizationContext['name'] ?? ''));
if ($appName === '' || !($organizationContext['isValid'] ?? false)) {
    $appName = $defaultName;
}

$shortName = trim((string)($organizationContext['shortname'] ?? ''));
if ($shortName === '') {
    $shortName = $appName;
}
if (function_exists('mb_substr')) {
    $shortName = mb_substr($shortName, 0, 12);
} else {
    $shortName = substr($shortName, 0, 12);
}
if (trim($shortName) === '') {
    $shortName = $defaultShortName;
}

$themeColor = commonGetOrganizationAccentColor($organizationContext, $defaultThemeColor);
$startUrl = '/omo/';
$scope = '/omo/';

if (!empty($organizationContext['routeMode']) && $organizationContext['routeMode'] === 'path' && !empty($organizationContext['id'])) {
    $startUrl = '/omo/o/' . (int)$organizationContext['id'];
    $scope = '/omo/';
}

$hasOrganizationSpecificIcon = !empty($organizationContext['isValid']) && trim((string)($organizationContext['logo'] ?? '')) !== '';
$manifestIcons = [];

if ($hasOrganizationSpecificIcon) {
    $manifestIcons[] = [
        'src' => omoManifestBuildIconUrl($organizationContext, 192),
        'type' => 'image/png',
        'sizes' => '192x192',
        'purpose' => 'any'
    ];
    $manifestIcons[] = [
        'src' => omoManifestBuildIconUrl($organizationContext, 512),
        'type' => 'image/png',
        'sizes' => '512x512',
        'purpose' => 'any'
    ];
    $manifestIcons[] = [
        'src' => omoManifestBuildIconUrl($organizationContext, 512, 'maskable'),
        'type' => 'image/png',
        'sizes' => '512x512',
        'purpose' => 'maskable'
    ];
} else {
    $manifestIcons[] = [
        'src' => $defaultIcon192,
        'type' => 'image/png',
        'sizes' => '192x192',
        'purpose' => 'any'
    ];
    $manifestIcons[] = [
        'src' => $defaultIcon,
        'type' => 'image/png',
        'sizes' => '512x512',
        'purpose' => 'any'
    ];
    $manifestIcons[] = [
        'src' => $defaultMaskableIcon,
        'type' => 'image/png',
        'sizes' => '512x512',
        'purpose' => 'maskable'
    ];
}

$manifest = [
    'id' => $startUrl,
    'name' => $appName,
    'short_name' => $shortName,
    'description' => 'Application de gouvernance OMO pour consulter la structure, les cercles et les outils.',
    'lang' => 'fr-CH',
    'dir' => 'ltr',
    'start_url' => $startUrl,
    'scope' => $scope,
    'display' => 'standalone',
    'orientation' => 'any',
    'background_color' => '#f7f8fa',
    'theme_color' => $themeColor,
    'icons' => $manifestIcons,
];

header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
