<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

$omoManifestIconDebug = isset($_GET['debug']) && $_GET['debug'] === '1';
$omoManifestIconDiagnostics = [];

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
    global $omoManifestIconDebug, $omoManifestIconDiagnostics;

    $fallbackPath = $purpose === 'maskable'
        ? $_SERVER['DOCUMENT_ROOT'] . '/omo/icons/icon-maskable-512.png'
        : $_SERVER['DOCUMENT_ROOT'] . ($size <= 192 ? '/omo/icons/icon-192.png' : '/omo/icons/icon-512.png');

    $omoManifestIconDiagnostics['fallback_path'] = $fallbackPath;
    $omoManifestIconDiagnostics['fallback_exists'] = is_file($fallbackPath);

    if ($omoManifestIconDebug) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($omoManifestIconDiagnostics, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

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
    global $omoManifestIconDiagnostics;

    $omoManifestIconDiagnostics['gd_functions'] = [
        'getimagesize' => function_exists('getimagesize'),
        'imagecreatefrompng' => function_exists('imagecreatefrompng'),
        'imagecreatefromjpeg' => function_exists('imagecreatefromjpeg'),
        'imagecreatefromwebp' => function_exists('imagecreatefromwebp'),
    ];

    if (!function_exists('getimagesize')) {
        return false;
    }

    $imageInfo = @getimagesize($absolutePath);
    $omoManifestIconDiagnostics['image_info'] = is_array($imageInfo)
        ? [
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null,
            'mime' => $imageInfo['mime'] ?? null,
        ]
        : false;
    if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
        return false;
    }

    switch ($imageInfo['mime']) {
        case 'image/png':
            return function_exists('imagecreatefrompng') ? @imagecreatefrompng($absolutePath) : false;
        case 'image/jpeg':
            return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($absolutePath) : false;
        case 'image/webp':
            return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false;
    }

    return false;
}

function omoManifestIconCreateCanvas($size, $purpose, $backgroundColor)
{
    global $omoManifestIconDiagnostics;

    $omoManifestIconDiagnostics['canvas_functions'] = [
        'imagecreatetruecolor' => function_exists('imagecreatetruecolor'),
        'imagealphablending' => function_exists('imagealphablending'),
        'imagesavealpha' => function_exists('imagesavealpha'),
        'imagecolorallocate' => function_exists('imagecolorallocate'),
        'imagecolorallocatealpha' => function_exists('imagecolorallocatealpha'),
        'imagefilledrectangle' => function_exists('imagefilledrectangle'),
    ];

    if (!function_exists('imagecreatetruecolor') || !function_exists('imagealphablending') || !function_exists('imagesavealpha') || !function_exists('imagecolorallocate') || !function_exists('imagecolorallocatealpha') || !function_exists('imagefilledrectangle')) {
        return false;
    }

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

$omoManifestIconDiagnostics['request'] = [
    'size' => $size,
    'purpose' => $purpose,
    'debug' => $omoManifestIconDebug,
];
$omoManifestIconDiagnostics['context'] = $context;
$omoManifestIconDiagnostics['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? '';
$omoManifestIconDiagnostics['gd_extension_loaded'] = extension_loaded('gd');

if (!in_array($size, [192, 512], true)) {
    $size = 512;
}

$logoPath = trim((string)($context['logo'] ?? ''));
$absoluteLogoPath = ($logoPath !== '' && strpos($logoPath, '/') === 0)
    ? $_SERVER['DOCUMENT_ROOT'] . $logoPath
    : '';

$omoManifestIconDiagnostics['logo_path'] = $logoPath;
$omoManifestIconDiagnostics['absolute_logo_path'] = $absoluteLogoPath;
$omoManifestIconDiagnostics['absolute_logo_exists'] = $absoluteLogoPath !== '' && is_file($absoluteLogoPath);

if ($absoluteLogoPath === '' || !is_file($absoluteLogoPath)) {
    $omoManifestIconDiagnostics['result'] = 'fallback';
    $omoManifestIconDiagnostics['reason'] = 'logo_file_missing';
    omoManifestIconOutputFallback($size, $purpose);
}

$source = omoManifestIconLoadSourceImage($absoluteLogoPath);
if ($source === false) {
    $omoManifestIconDiagnostics['result'] = 'fallback';
    $omoManifestIconDiagnostics['reason'] = 'logo_image_load_failed';
    omoManifestIconOutputFallback($size, $purpose);
}

$backgroundColor = omoManifestIconHexToRgb($context['color'] ?? '');
$omoManifestIconDiagnostics['background_color_rgb'] = $backgroundColor;
$canvas = omoManifestIconCreateCanvas($size, $purpose, $backgroundColor);
$canRender = $canvas !== false
    && function_exists('imagesx')
    && function_exists('imagesy')
    && function_exists('imagecopyresampled')
    && function_exists('imagepng')
    && function_exists('imagedestroy');

$omoManifestIconDiagnostics['render_functions'] = [
    'imagesx' => function_exists('imagesx'),
    'imagesy' => function_exists('imagesy'),
    'imagecopyresampled' => function_exists('imagecopyresampled'),
    'imagepng' => function_exists('imagepng'),
    'imagedestroy' => function_exists('imagedestroy'),
];
$omoManifestIconDiagnostics['canvas_created'] = $canvas !== false;

if (!$canRender) {
    $isGdImageObject = is_object($source) && class_exists('GdImage', false) && ($source instanceof GdImage);
    if (is_resource($source) || $isGdImageObject) {
        imagedestroy($source);
    }
    $omoManifestIconDiagnostics['result'] = 'fallback';
    $omoManifestIconDiagnostics['reason'] = 'render_functions_unavailable';
    omoManifestIconOutputFallback($size, $purpose);
}

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);
$omoManifestIconDiagnostics['source_size'] = [
    'width' => $sourceWidth,
    'height' => $sourceHeight,
];

if ($sourceWidth <= 0 || $sourceHeight <= 0) {
    imagedestroy($source);
    imagedestroy($canvas);
    $omoManifestIconDiagnostics['result'] = 'fallback';
    $omoManifestIconDiagnostics['reason'] = 'invalid_source_dimensions';
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

$omoManifestIconDiagnostics['target_size'] = $targetSize;
$omoManifestIconDiagnostics['draw_box'] = [
    'width' => $drawWidth,
    'height' => $drawHeight,
    'offset_x' => $offsetX,
    'offset_y' => $offsetY,
];

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

if ($omoManifestIconDebug) {
    $omoManifestIconDiagnostics['result'] = 'generated';
    $omoManifestIconDiagnostics['reason'] = 'ok';
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($omoManifestIconDiagnostics, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    imagedestroy($canvas);
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
imagepng($canvas);
imagedestroy($canvas);
exit;
