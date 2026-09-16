<?php
declare(strict_types=1);

use dbObject\DbObject;

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';

final class ImageOptimizationProbe extends DbObject
{
    public static function tableName()
    {
        return 'imageoptimizationprobe';
    }

    public static function rules()
    {
        return array(
            array(array('image'), 'sizedimage'),
        );
    }

    public static function attributeLabels()
    {
        return array('image' => 'Image');
    }

    public static function attributeLength()
    {
        return array('image' => array(array(320, 320), array(160, 160)));
    }
}

function assertSizedImageOptimization(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omo-image-test-' . bin2hex(random_bytes(5));
$uploadDirectory = $temporaryRoot . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'imageoptimizationprobe';
if (!mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
    throw new RuntimeException('Unable to create the temporary upload directory.');
}

$sourcePath = $temporaryRoot . DIRECTORY_SEPARATOR . 'source.png';
$source = imagecreatetruecolor(900, 700);
for ($y = 0; $y < 700; $y += 10) {
    $color = imagecolorallocate($source, ($y * 3) % 255, ($y * 5) % 255, ($y * 7) % 255);
    imagefilledrectangle($source, 0, $y, 899, min(699, $y + 9), $color);
}
imagepng($source, $sourcePath);

$_SERVER['DOCUMENT_ROOT'] = $temporaryRoot;
$_FILES['image'] = array(
    'name' => 'profile.png',
    'type' => 'image/png',
    'tmp_name' => $sourcePath,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($sourcePath),
);

$object = new ImageOptimizationProbe();
$object->set('image', 'newimage');
$storedPath = (string)$object->get('image');
$storedFullPath = $temporaryRoot . str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
$storedSize = getimagesize($storedFullPath);

assertSizedImageOptimization(
    str_ends_with($storedPath, function_exists('imagewebp') ? '.webp' : '.png'),
    'Sized images must use the best format supported by GD.'
);
assertSizedImageOptimization(is_array($storedSize), 'The optimized image must be readable.');
assertSizedImageOptimization($storedSize[0] === 320 && $storedSize[1] === 320, 'The dbObject dimensions must be enforced.');
assertSizedImageOptimization(
    $storedSize['mime'] === (function_exists('imagewebp') ? 'image/webp' : 'image/png'),
    'The stored image format must match the GD capabilities.'
);

$jpegSourcePath = $temporaryRoot . DIRECTORY_SEPARATOR . 'source.jpg';
imagejpeg($source, $jpegSourcePath, 90);
$_FILES['image'] = array(
    'name' => 'profile.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => $jpegSourcePath,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($jpegSourcePath),
);

$jpegObject = new ImageOptimizationProbe();
$jpegObject->set('image', 'newimage');
$jpegStoredPath = (string)$jpegObject->get('image');
$jpegStoredFullPath = $temporaryRoot . str_replace('/', DIRECTORY_SEPARATOR, $jpegStoredPath);
$jpegStoredSize = getimagesize($jpegStoredFullPath);

assertSizedImageOptimization(
    str_ends_with($jpegStoredPath, function_exists('imagewebp') ? '.webp' : '.jpg'),
    'JPEG uploads must be stored using a format supported by GD.'
);
assertSizedImageOptimization(is_array($jpegStoredSize), 'The optimized JPEG must be readable.');
assertSizedImageOptimization($jpegStoredSize[0] === 320 && $jpegStoredSize[1] === 320, 'JPEG dimensions must be enforced.');

unlink($storedFullPath);
unlink($sourcePath);
unlink($jpegStoredFullPath);
unlink($jpegSourcePath);
rmdir($uploadDirectory);
rmdir(dirname($uploadDirectory));
rmdir(dirname(dirname($uploadDirectory)));
rmdir($temporaryRoot);
unset($_FILES['image']);

if (!function_exists('imagecreatefromwebp')) {
    $webpPath = dirname(__DIR__) . '/img/orange.webp';
    assertSizedImageOptimization(is_file($webpPath), 'The WebP fixture must be available.');

    $_SERVER['DOCUMENT_ROOT'] = $temporaryRoot;
    $_FILES['image'] = array(
        'name' => 'profile.webp',
        'type' => 'image/webp',
        'tmp_name' => $webpPath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($webpPath),
    );

    $unsupportedWebpObject = new ImageOptimizationProbe();
    $unsupportedWebpObject->set('image', '/img/upload/previous.png');
    $unsupportedWebpObject->set('image', 'newimage');
    assertSizedImageOptimization(
        $unsupportedWebpObject->get('image') === '/img/upload/previous.png',
        'An unsupported WebP upload must preserve the previous image without a fatal error.'
    );
    unset($_FILES['image']);

    $unsupportedUploadDirectory = $temporaryRoot . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'imageoptimizationprobe';
    rmdir($unsupportedUploadDirectory);
    rmdir(dirname($unsupportedUploadDirectory));
    rmdir(dirname(dirname($unsupportedUploadDirectory)));
    rmdir($temporaryRoot);
}

echo "dbobject_sized_image_optimization_test: OK\n";
