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
$directories = array(
    $projectRoot . '/img/upload/user',
    $projectRoot . '/img/upload/user_organization',
);
$optimizedCount = 0;
$originalBytes = 0;
$optimizedBytes = 0;

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        continue;
    }

    $files = new DirectoryIterator($directory);
    foreach ($files as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (!in_array($extension, array('jpg', 'jpeg', 'png', 'webp'), true)) {
            continue;
        }

        $path = $file->getPathname();
        $contents = @file_get_contents($path);
        $source = is_string($contents) ? @imagecreatefromstring($contents) : false;
        if ($source === false) {
            continue;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1.0, 320 / max(1, $width), 320 / max(1, $height));
        $targetWidth = max(1, (int)round($width * $scale));
        $targetHeight = max(1, (int)round($height * $scale));
        if ($targetWidth === $width && $targetHeight === $height && $extension !== 'png') {
            imagedestroy($source);
            continue;
        }

        $destination = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($destination === false) {
            imagedestroy($source);
            continue;
        }

        if (in_array($extension, array('png', 'webp'), true)) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
            imagefilledrectangle($destination, 0, 0, $targetWidth, $targetHeight, $transparent);
        }
        imagecopyresampled(
            $destination,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );
        imagedestroy($source);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'omo-profile-');
        if ($temporaryPath === false) {
            imagedestroy($destination);
            continue;
        }

        if ($extension === 'jpg' || $extension === 'jpeg') {
            imageinterlace($destination, true);
            $encoded = imagejpeg($destination, $temporaryPath, 82);
        } elseif ($extension === 'webp') {
            $encoded = imagewebp($destination, $temporaryPath, 82);
        } else {
            $encoded = imagepng($destination, $temporaryPath, 9, PNG_ALL_FILTERS);
        }
        imagedestroy($destination);
        if (!$encoded) {
            unlink($temporaryPath);
            continue;
        }

        $before = filesize($path);
        $after = filesize($temporaryPath);
        $originalBytes += $before;
        $optimizedBytes += min($before, $after);
        if ($after < $before) {
            $optimizedCount++;
            printf(
                "%s: %dx%d -> %dx%d, %.1f KB -> %.1f KB%s\n",
                str_replace('\\', '/', substr($path, strlen($projectRoot) + 1)),
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
                fwrite(STDERR, "Unable to replace {$path}.\n");
                exit(1);
            }
        }

        unlink($temporaryPath);
    }
}

printf(
    "%s: %d files, %.2f MB -> %.2f MB.\n",
    $apply ? 'Optimized' : 'Potential saving',
    $optimizedCount,
    $originalBytes / 1048576,
    $optimizedBytes / 1048576
);
