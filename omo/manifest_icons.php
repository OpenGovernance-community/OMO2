<?php

/** Shared by the page head, manifest and image endpoint. Bump v2 if rendering changes. */
function omoManifestBuildIconVersionToken(array $organizationContext): string
{
    $logo = trim((string)($organizationContext['logo'] ?? ''));
    $path = str_starts_with($logo, '/') ? ($_SERVER['DOCUMENT_ROOT'] ?? '') . $logo : '';
    $fingerprint = $path !== '' && is_file($path) ? @hash_file('sha256', $path) : false;
    return substr(hash('sha256', json_encode([
        'v2', (int)($organizationContext['id'] ?? 0), $logo,
        (string)($organizationContext['color'] ?? ''), $fingerprint,
    ], JSON_INVALID_UTF8_SUBSTITUTE)), 0, 20);
}

function omoManifestBuildIconUrl(array $organizationContext, $size, $purpose = 'any'): string
{
    $query = ['size' => (int)$size, 'v' => omoManifestBuildIconVersionToken($organizationContext)];
    $organizationId = (int)($organizationContext['id'] ?? 0);
    if ($organizationId > 0) {
        $query['oid'] = $organizationId;
    }
    if ($purpose === 'maskable') {
        $query['purpose'] = 'maskable';
    }
    return '/omo/manifest_icon.php?' . http_build_query($query);
}
