<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

function omoManifestIconLoadContext()
{
    $organizationContext = commonResolveOrganizationContext(1);
    $requestedOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : 0;

    if ($requestedOrganizationId > 0) {
        $requestedOrganization = new \dbObject\Organization();
        if ($requestedOrganization->load($requestedOrganizationId)) {
            return [
                'id' => (int)$requestedOrganization->getId(),
                'logo' => (string)$requestedOrganization->get('logo'),
                'color' => trim((string)$requestedOrganization->get('color')),
            ];
        }
    }

    return [
        'id' => (int)($organizationContext['id'] ?? 0),
        'logo' => (string)($organizationContext['logo'] ?? ''),
        'color' => trim((string)($organizationContext['color'] ?? '')),
    ];
}

function omoManifestIconOutputFallback($size, $purpose)
{
    $fallbackPath = $purpose === 'maskable'
        ? $_SERVER['DOCUMENT_ROOT'] . '/omo/icons/icon-maskable-512.png'
        : $_SERVER['DOCUMENT_ROOT'] . ($size <= 192 ? '/omo/icons/icon-192.png' : '/omo/icons/icon-512.png');

    if (!is_file($fallbackPath)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    readfile($fallbackPath);
    exit;
}

function omoManifestIconLoadSourceImage($absolutePath)
{
    $imageInfo = @getimagesize($absolutePath);
    if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
        return false;
    }

    switch ($imageInfo['mime']) {
        case 'image/png':
            return @imagecreatefrompng($absolutePath);
        case 'image/jpeg':
            return @imagecreatefromjpeg($absolutePath);
        case 'image/webp':
            return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false;
    }

    return false;
}

function omoManifestIconCreateCanvas($size, $purpose, $backgroundColor)
{
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    if ($purpose === 'maskable') {
        $fillColor = imagecolorallocate($canvas, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $fillColor);
    } else {
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
    }

    return $canvas;
}

function omoManifestIconHexToRgb($value, $fallback = [0, 70, 99])
{
    $value = trim((string)$value);
    if (!preg_match('/^#?([a-f0-9]{6})$/i', $value, $matches)) {
        return $fallback;
    }

    $hex = $matches[1];
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

$context = omoManifestIconLoadContext();
$size = isset($_GET['size']) ? (int)$_GET['size'] : 512;
$purpose = isset($_GET['purpose']) && $_GET['purpose'] === 'maskable' ? 'maskable' : 'any';

if (!in_array($size, [192, 512], true)) {
    $size = 512;
}

$logoPath = trim((string)($context['logo'] ?? ''));
$absoluteLogoPath = $logoPath !== '' ? $_SERVER['DOCUMENT_ROOT'] . $logoPath : '';

if ($absoluteLogoPath === '' || !is_file($absoluteLogoPath)) {
    omoManifestIconOutputFallback($size, $purpose);
}

$source = omoManifestIconLoadSourceImage($absoluteLogoPath);
if ($source === false) {
    omoManifestIconOutputFallback($size, $purpose);
}

$backgroundColor = omoManifestIconHexToRgb($context['color'] ?? '');
$canvas = omoManifestIconCreateCanvas($size, $purpose, $backgroundColor);
$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);

if ($sourceWidth <= 0 || $sourceHeight <= 0) {
    imagedestroy($source);
    imagedestroy($canvas);
    omoManifestIconOutputFallback($size, $purpose);
}

$targetSize = $purpose === 'maskable'
    ? (int)round($size * 0.74)
    : $size;
$targetSize = max(1, min($size, $targetSize));

$ratio = min($targetSize / $sourceWidth, $targetSize / $sourceHeight);
$drawWidth = max(1, (int)round($sourceWidth * $ratio));
$drawHeight = max(1, (int)round($sourceHeight * $ratio));
$offsetX = (int)floor(($size - $drawWidth) / 2);
$offsetY = (int)floor(($size - $drawHeight) / 2);

imagecopyresampled(
    $canvas,
    $source,
    $offsetX,
    $offsetY,
    0,
    0,
    $drawWidth,
    $drawHeight,
    $sourceWidth,
    $sourceHeight
);

imagedestroy($source);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
imagepng($canvas);
imagedestroy($canvas);
exit;
