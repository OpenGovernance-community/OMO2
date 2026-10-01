<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "The GD extension is required.\n");
    exit(1);
}

$apply = in_array('--apply', $argv, true);
$projectRoot = dirname(__DIR__);
$roots = array(
    $projectRoot . '/img',
    $projectRoot . '/omo/assets/images',
    $projectRoot . '/omo/images',
    $projectRoot . '/common/assets',
    $projectRoot . '/common/choice/assets',
);

function staticImageRelativePath(string $path, string $projectRoot): string
{
    return str_replace('\\', '/', substr($path, strlen($projectRoot) + 1));
}

function staticImageTargetSize(string $relativePath, int $width, int $height): array
{
    $maxWidth = $width;
    $maxHeight = $height;

    if (preg_match('#^img/ilu_[^/]+\.jpe?g$#i', $relativePath)) {
        $maxWidth = 1000;
        $maxHeight = 1000;
    } elseif ($relativePath === 'img/home.jpg') {
        $maxWidth = 1920;
        $maxHeight = 1080;
    } elseif (preg_match('#^omo/(?:assets/images/directory|images/organization-setup)/#', $relativePath)) {
        $maxWidth = 960;
        $maxHeight = 540;
    } elseif (preg_match('#^img/(?:logo-OGC|org[12]-logo)\.png$#', $relativePath)) {
        $maxWidth = 512;
        $maxHeight = 512;
    } elseif (
        preg_match('#^omo/(?:assets/images/(?:action|calendar-view|documents|projects|scope|sort|view)|images/tools)/#', $relativePath)
        || preg_match('#^common/(?:assets/icon-|choice/assets/)#', $relativePath)
        || ($width === 512 && $height === 512 && preg_match('#^img/#', $relativePath))
    ) {
        $maxWidth = 128;
        $maxHeight = 128;
    } elseif ($width > 1920 || $height > 1920) {
        $maxWidth = 1600;
        $maxHeight = 1600;
    }

    $scale = min(1.0, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
    return array(
        max(1, (int)round($width * $scale)),
        max(1, (int)round($height * $scale)),
    );
}

function staticImageCreateCanvas($source, int $width, int $height, bool $preserveAlpha)
{
    $canvas = imagecreatetruecolor($width, $height);
    if ($canvas === false) {
        return false;
    }

    if ($preserveAlpha) {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
    }

    imagecopyresampled(
        $canvas,
        $source,
        0,
        0,
        0,
        0,
        $width,
        $height,
        imagesx($source),
        imagesy($source)
    );

    return $canvas;
}

function staticImageEncode($image, string $extension, string $path): bool
{
    if ($extension === 'jpg' || $extension === 'jpeg') {
        imageinterlace($image, true);
        return imagejpeg($image, $path, 82);
    }
    if ($extension === 'png') {
        return imagepng($image, $path, 9, PNG_ALL_FILTERS);
    }
    if ($extension === 'webp' && function_exists('imagewebp')) {
        return imagewebp($image, $path, 82);
    }

    return false;
}

$files = array();
foreach ($roots as $root) {
    if (!is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $relativePath = staticImageRelativePath($path, $projectRoot);
        if (preg_match('#^img/uploads?/#', $relativePath)) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (!in_array($extension, array('jpg', 'jpeg', 'png', 'webp'), true)) {
            continue;
        }

        $files[] = $path;
    }
}

$originalBytes = 0;
$optimizedBytes = 0;
$optimizedCount = 0;

foreach ($files as $path) {
    $contents = @file_get_contents($path);
    $source = is_string($contents) ? @imagecreatefromstring($contents) : false;
    if ($source === false) {
        continue;
    }

    $relativePath = staticImageRelativePath($path, $projectRoot);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $width = imagesx($source);
    $height = imagesy($source);
    list($targetWidth, $targetHeight) = staticImageTargetSize($relativePath, $width, $height);
    if ($targetWidth === $width && $targetHeight === $height) {
        imagedestroy($source);
        continue;
    }
    $preserveAlpha = in_array($extension, array('png', 'webp'), true);
    $canvas = staticImageCreateCanvas($source, $targetWidth, $targetHeight, $preserveAlpha);
    imagedestroy($source);
    if ($canvas === false) {
        continue;
    }

    $temporaryPath = tempnam(sys_get_temp_dir(), 'omo-image-');
    if ($temporaryPath === false || !staticImageEncode($canvas, $extension, $temporaryPath)) {
        imagedestroy($canvas);
        continue;
    }
    imagedestroy($canvas);

    $before = filesize($path);
    $after = filesize($temporaryPath);
    $originalBytes += $before;
    $optimizedBytes += min($before, $after);

    if ($after < $before) {
        $optimizedCount++;
        printf(
            "%s: %dx%d -> %dx%d, %.1f KB -> %.1f KB%s\n",
            $relativePath,
            $width,
            $height,
            $targetWidth,
            $targetHeight,
            $before / 1024,
            $after / 1024,
            $apply ? '' : ' (dry run)'
        );

        if ($apply && !copy($temporaryPath, $path)) {
            unlink($temporaryPath);
            fwrite(STDERR, "Unable to replace {$relativePath}.\n");
            exit(1);
        }
    }

    unlink($temporaryPath);
}

printf(
    "%s: %d files, %.2f MB -> %.2f MB.\n",
    $apply ? 'Optimized' : 'Potential saving',
    $optimizedCount,
    $originalBytes / 1048576,
    $optimizedBytes / 1048576
);
