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
imagedestroy($source);

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

assertSizedImageOptimization(str_ends_with($storedPath, '.webp'), 'Sized images must be stored as WebP when supported.');
assertSizedImageOptimization(is_array($storedSize), 'The optimized image must be readable.');
assertSizedImageOptimization($storedSize[0] === 320 && $storedSize[1] === 320, 'The dbObject dimensions must be enforced.');
assertSizedImageOptimization($storedSize['mime'] === 'image/webp', 'The stored image content must be WebP.');

unlink($storedFullPath);
unlink($sourcePath);
rmdir($uploadDirectory);
rmdir(dirname($uploadDirectory));
rmdir(dirname(dirname($uploadDirectory)));
rmdir($temporaryRoot);
unset($_FILES['image']);

echo "dbobject_sized_image_optimization_test: OK\n";
