<?php

require_once dirname(__DIR__) . '/includes/env.php';

foreach (envGetRuntimeEnvPaths() as $runtimeEnvPath) {
    loadEnv($runtimeEnvPath);
}

const GETIMG_MAX_SOURCE_BYTES = 10 * 1024 * 1024;
const GETIMG_MAX_SOURCE_PIXELS = 40000000;
const GETIMG_MAX_OUTPUT_DIMENSION = 4096;
const GETIMG_MAX_OUTPUT_PIXELS = 16000000;

function getImgFail(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $message;
    exit;
}

function getImgAllowedExtensions(): array
{
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
}

function getImgNormalizeExtension(string $extension): string
{
    $extension = strtolower(trim($extension));
    if ($extension === 'jpeg') {
        return 'jpg';
    }

    return in_array($extension, getImgAllowedExtensions(), true) ? $extension : '';
}

function getImgParseDimension(string $name): ?int
{
    if (!isset($_GET[$name]) || $_GET[$name] === '') {
        return null;
    }

    $value = filter_var(
        $_GET[$name],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => GETIMG_MAX_OUTPUT_DIMENSION]]
    );

    if ($value === false) {
        getImgFail(400, 'Invalid image dimensions.');
    }

    return (int)$value;
}

function getImgNormalizeAllowedHosts(string $configuredHosts): array
{
    $hosts = preg_split('/[\s,;]+/', strtolower(trim($configuredHosts))) ?: [];
    $normalized = [];

    foreach ($hosts as $host) {
        $host = rtrim(trim((string)$host), '.');
        if ($host === '' || preg_match('/^(?:\*\.)?[a-z0-9.-]+$/', $host) !== 1) {
            continue;
        }
        $normalized[$host] = true;
    }

    return array_keys($normalized);
}

function getImgHostIsAllowed(string $host, array $allowedHosts): bool
{
    $host = rtrim(strtolower(trim($host)), '.');
    if ($host === '') {
        return false;
    }

    foreach ($allowedHosts as $allowedHost) {
        $allowedHost = rtrim(strtolower(trim((string)$allowedHost)), '.');
        if ($allowedHost === $host) {
            return true;
        }

        if (str_starts_with($allowedHost, '*.')) {
            $suffix = substr($allowedHost, 1);
            if ($suffix !== '' && $host !== substr($suffix, 1) && str_ends_with($host, $suffix)) {
                return true;
            }
        }
    }

    return false;
}

function getImgIpIsPublic(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

function getImgResolvePublicIps(string $host): array
{
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return getImgIpIsPublic($host) ? [$host] : [];
    }

    $ips = [];
    if (function_exists('dns_get_record')) {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                $ip = trim((string)($record['ip'] ?? $record['ipv6'] ?? ''));
                if ($ip !== '') {
                    $ips[$ip] = true;
                }
            }
        }
    }

    if ($ips === [] && function_exists('gethostbynamel')) {
        $ipv4Addresses = @gethostbynamel($host);
        if (is_array($ipv4Addresses)) {
            foreach ($ipv4Addresses as $ip) {
                $ip = trim((string)$ip);
                if ($ip !== '') {
                    $ips[$ip] = true;
                }
            }
        }
    }

    $resolvedIps = array_keys($ips);
    if ($resolvedIps === []) {
        return [];
    }

    foreach ($resolvedIps as $ip) {
        if (!getImgIpIsPublic($ip)) {
            return [];
        }
    }

    return $resolvedIps;
}

function getImgLoadLocalSource(string $requestedUrl): string
{
    $path = parse_url($requestedUrl, PHP_URL_PATH);
    if (!is_string($path) || $path === '' || $path[0] !== '/') {
        getImgFail(400, 'Invalid local image path.');
    }

    $decodedPath = rawurldecode($path);
    if (str_contains($decodedPath, "\0")) {
        getImgFail(400, 'Invalid local image path.');
    }

    $sourceExtension = getImgNormalizeExtension((string)pathinfo($decodedPath, PATHINFO_EXTENSION));
    if ($sourceExtension === '') {
        getImgFail(415, 'Unsupported source image format.');
    }

    $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $sourcePath = $documentRoot !== false
        ? realpath($documentRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $decodedPath), DIRECTORY_SEPARATOR))
        : false;

    if ($documentRoot === false || $sourcePath === false || !is_file($sourcePath)) {
        getImgFail(404, 'Image not found.');
    }

    $rootPrefix = rtrim($documentRoot, '/\\') . DIRECTORY_SEPARATOR;
    $pathPrefix = substr($sourcePath, 0, strlen($rootPrefix));
    $insideDocumentRoot = DIRECTORY_SEPARATOR === '\\'
        ? strcasecmp($pathPrefix, $rootPrefix) === 0
        : $pathPrefix === $rootPrefix;
    if (!$insideDocumentRoot) {
        getImgFail(403, 'Local image path is not allowed.');
    }

    $sourceSize = @filesize($sourcePath);
    if (!is_int($sourceSize) || $sourceSize <= 0 || $sourceSize > GETIMG_MAX_SOURCE_BYTES) {
        getImgFail(413, 'Source image is too large.');
    }

    $content = @file_get_contents($sourcePath);
    if (!is_string($content) || $content === '') {
        getImgFail(502, 'Unable to load image.');
    }

    return $content;
}

function getImgLoadRemoteSource(string $requestedUrl): string
{
    if (!function_exists('curl_init')) {
        getImgFail(503, 'Remote image loading is unavailable.');
    }

    $parts = parse_url($requestedUrl);
    if (!is_array($parts)) {
        getImgFail(400, 'Invalid remote image URL.');
    }

    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = rtrim(strtolower((string)($parts['host'] ?? '')), '.');
    $port = (int)($parts['port'] ?? 443);
    if (
        $scheme !== 'https'
        || $host === ''
        || $port !== 443
        || isset($parts['user'])
        || isset($parts['pass'])
    ) {
        getImgFail(400, 'Only standard HTTPS image URLs are allowed.');
    }

    $allowedHosts = getImgNormalizeAllowedHosts((string)envValue('GETIMG_ALLOWED_HOSTS', ''));
    if (!getImgHostIsAllowed($host, $allowedHosts)) {
        getImgFail(403, 'Remote image host is not allowed.');
    }

    $resolvedIps = getImgResolvePublicIps($host);
    if ($resolvedIps === []) {
        getImgFail(403, 'Remote image host does not resolve to a public address.');
    }

    $pinnedIp = $resolvedIps[0];
    $resolveIp = str_contains($pinnedIp, ':') ? '[' . $pinnedIp . ']' : $pinnedIp;
    $body = '';
    $tooLarge = false;
    $responseContentType = '';

    $curl = curl_init($requestedUrl);
    if ($curl === false) {
        getImgFail(502, 'Unable to prepare remote image request.');
    }

    $options = [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_RESOLVE => [$host . ':443:' . $resolveIp],
        CURLOPT_HTTPHEADER => ['Accept: image/*'],
        CURLOPT_USERAGENT => 'OMO image proxy/1.0',
        CURLOPT_HEADERFUNCTION => static function ($handle, string $headerLine) use (&$responseContentType): int {
            if (stripos($headerLine, 'Content-Type:') === 0) {
                $responseContentType = strtolower(trim(substr($headerLine, strlen('Content-Type:'))));
            }
            return strlen($headerLine);
        },
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, &$tooLarge): int {
            if (strlen($body) + strlen($chunk) > GETIMG_MAX_SOURCE_BYTES) {
                $tooLarge = true;
                return 0;
            }
            $body .= $chunk;
            return strlen($chunk);
        },
    ];

    if (defined('CURLOPT_PROTOCOLS_STR')) {
        $options[CURLOPT_PROTOCOLS_STR] = 'https';
    } else {
        $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
    }

    curl_setopt_array($curl, $options);
    $success = curl_exec($curl);
    $statusCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($tooLarge) {
        getImgFail(413, 'Source image is too large.');
    }
    if ($success === false || $statusCode < 200 || $statusCode >= 300 || $body === '') {
        getImgFail(502, 'Unable to load remote image.');
    }
    if ($responseContentType !== '' && !str_starts_with($responseContentType, 'image/')) {
        getImgFail(415, 'Remote resource is not an image.');
    }

    return $body;
}

function getImgCalculateOutputDimensions(int $sourceWidth, int $sourceHeight, ?int $requestedWidth, ?int $requestedHeight): array
{
    if ($requestedWidth === null && $requestedHeight === null) {
        $width = $sourceWidth;
        $height = $sourceHeight;
    } elseif ($requestedWidth !== null && $requestedHeight !== null) {
        $width = $requestedWidth;
        $height = $requestedHeight;
    } elseif ($requestedWidth !== null) {
        $width = $requestedWidth;
        $height = max(1, (int)round($sourceHeight * ($requestedWidth / $sourceWidth)));
    } else {
        $height = (int)$requestedHeight;
        $width = max(1, (int)round($sourceWidth * ($height / $sourceHeight)));
    }

    if (
        $width > GETIMG_MAX_OUTPUT_DIMENSION
        || $height > GETIMG_MAX_OUTPUT_DIMENSION
        || ($width * $height) > GETIMG_MAX_OUTPUT_PIXELS
    ) {
        getImgFail(413, 'Requested image dimensions are too large.');
    }

    return [$width, $height];
}

function getImgEncode(
    GdImage $source,
    int $sourceWidth,
    int $sourceHeight,
    int $outputWidth,
    int $outputHeight,
    string $extension
): string {
    $destination = imagecreatetruecolor($outputWidth, $outputHeight);
    if (!$destination instanceof GdImage) {
        getImgFail(500, 'Unable to prepare image.');
    }

    if (in_array($extension, ['png', 'gif', 'webp'], true)) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
        imagefilledrectangle($destination, 0, 0, $outputWidth, $outputHeight, $transparent);
    } else {
        $white = imagecolorallocate($destination, 255, 255, 255);
        imagefilledrectangle($destination, 0, 0, $outputWidth, $outputHeight, $white);
    }

    imagecopyresampled(
        $destination,
        $source,
        0,
        0,
        0,
        0,
        $outputWidth,
        $outputHeight,
        $sourceWidth,
        $sourceHeight
    );

    ob_start();
    $encoded = match ($extension) {
        'jpg' => imagejpeg($destination, null, 82),
        'png' => imagepng($destination, null, 8),
        'gif' => imagegif($destination),
        'webp' => function_exists('imagewebp') ? imagewebp($destination, null, 82) : false,
        default => false,
    };
    $content = (string)ob_get_clean();
    imagedestroy($destination);

    if (!$encoded || $content === '') {
        getImgFail(500, 'Unable to encode image.');
    }

    return $content;
}

function getImgHandleRequest(): never
{
    if (!isset($_GET['url']) || !is_string($_GET['url']) || trim($_GET['url']) === '') {
        getImgFail(400, 'Missing image URL.');
    }

    $requestedUrl = trim($_GET['url']);
    $urlPath = parse_url($requestedUrl, PHP_URL_PATH);
    $extension = getImgNormalizeExtension((string)($_GET['ext'] ?? pathinfo((string)$urlPath, PATHINFO_EXTENSION)));
    if ($extension === '') {
        getImgFail(415, 'Unsupported output image format.');
    }

    $resizeMode = strtolower(trim((string)($_GET['resize'] ?? '')));
    if (!in_array($resizeMode, ['', 'fit'], true)) {
        getImgFail(400, 'Unsupported resize mode.');
    }

    $requestedWidth = getImgParseDimension('x');
    $requestedHeight = getImgParseDimension('y');
    $cacheDirectory = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/img/small';
    if (!is_dir($cacheDirectory) && !@mkdir($cacheDirectory, 0755, true) && !is_dir($cacheDirectory)) {
        getImgFail(500, 'Image cache is unavailable.');
    }

    $cacheKey = hash('sha256', $requestedUrl);
    $cacheName = $cacheKey
        . ($requestedWidth !== null ? '_x' . $requestedWidth : '')
        . ($requestedHeight !== null ? '_y' . $requestedHeight : '')
        . ($resizeMode !== '' ? '_' . $resizeMode : '')
        . '.' . $extension;
    $cachePath = $cacheDirectory . DIRECTORY_SEPARATOR . $cacheName;

    $refresh = filter_var($_GET['refresh'] ?? false, FILTER_VALIDATE_BOOLEAN);
    if (!$refresh && is_file($cachePath) && (int)filesize($cachePath) > 0) {
        $contentType = $extension === 'jpg' ? 'image/jpeg' : 'image/' . $extension;
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . (int)filesize($cachePath));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($cachePath);
        exit;
    }

    $scheme = strtolower((string)parse_url($requestedUrl, PHP_URL_SCHEME));
    $sourceContent = $scheme === ''
        ? getImgLoadLocalSource($requestedUrl)
        : getImgLoadRemoteSource($requestedUrl);

    $imageInfo = @getimagesizefromstring($sourceContent);
    if (!is_array($imageInfo)) {
        getImgFail(415, 'Source resource is not a valid image.');
    }

    $sourceWidth = (int)($imageInfo[0] ?? 0);
    $sourceHeight = (int)($imageInfo[1] ?? 0);
    if (
        $sourceWidth <= 0
        || $sourceHeight <= 0
        || ($sourceWidth * $sourceHeight) > GETIMG_MAX_SOURCE_PIXELS
    ) {
        getImgFail(413, 'Source image dimensions are too large.');
    }

    $sourceImage = @imagecreatefromstring($sourceContent);
    unset($sourceContent);
    if (!$sourceImage instanceof GdImage) {
        getImgFail(415, 'Source resource is not a supported image.');
    }

    [$outputWidth, $outputHeight] = getImgCalculateOutputDimensions(
        $sourceWidth,
        $sourceHeight,
        $requestedWidth,
        $requestedHeight
    );
    $outputContent = getImgEncode(
        $sourceImage,
        $sourceWidth,
        $sourceHeight,
        $outputWidth,
        $outputHeight,
        $extension
    );
    imagedestroy($sourceImage);

    @file_put_contents($cachePath, $outputContent, LOCK_EX);

    $contentType = $extension === 'jpg' ? 'image/jpeg' : 'image/' . $extension;
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . strlen($outputContent));
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    echo $outputContent;
    exit;
}

if (!defined('GETIMG_SKIP_MAIN')) {
    getImgHandleRequest();
}
