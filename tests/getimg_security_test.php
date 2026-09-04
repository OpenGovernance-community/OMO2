<?php
declare(strict_types=1);

define('GETIMG_SKIP_MAIN', true);
require_once dirname(__DIR__) . '/shared/getImg.php';

function assertGetImgSecurity(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertGetImgSecurity(getImgNormalizeExtension('JPEG') === 'jpg', 'JPEG must normalize to jpg.');
assertGetImgSecurity(getImgNormalizeExtension('php') === '', 'Executable extensions must be rejected.');

$allowedHosts = getImgNormalizeAllowedHosts('images.example.org, *.static.example.org; INVALID/host');
assertGetImgSecurity(
    $allowedHosts === ['images.example.org', '*.static.example.org'],
    'Only valid configured hosts must be retained.'
);
assertGetImgSecurity(
    getImgHostIsAllowed('images.example.org', $allowedHosts),
    'An exact allowlisted host must be accepted.'
);
assertGetImgSecurity(
    getImgHostIsAllowed('cdn.static.example.org', $allowedHosts),
    'A subdomain covered by an allowlist wildcard must be accepted.'
);
assertGetImgSecurity(
    !getImgHostIsAllowed('static.example.org', $allowedHosts),
    'A wildcard must not include its parent domain.'
);
assertGetImgSecurity(
    !getImgHostIsAllowed('images.example.org.attacker.test', $allowedHosts),
    'A host suffix lookalike must be rejected.'
);
assertGetImgSecurity(!getImgHostIsAllowed('example.org', []), 'Remote loading must be disabled by default.');

assertGetImgSecurity(!getImgIpIsPublic('127.0.0.1'), 'Loopback IPv4 must be rejected.');
assertGetImgSecurity(!getImgIpIsPublic('10.0.0.1'), 'Private IPv4 must be rejected.');
assertGetImgSecurity(!getImgIpIsPublic('169.254.169.254'), 'Link-local IPv4 must be rejected.');
assertGetImgSecurity(!getImgIpIsPublic('::1'), 'Loopback IPv6 must be rejected.');
assertGetImgSecurity(getImgIpIsPublic('8.8.8.8'), 'A public IPv4 address must be accepted.');

[$width, $height] = getImgCalculateOutputDimensions(800, 400, 400, null);
assertGetImgSecurity($width === 400 && $height === 200, 'Aspect ratio must be preserved with one dimension.');

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omo-getimg-test-' . bin2hex(random_bytes(5));
assertGetImgSecurity(mkdir($temporaryRoot, 0775), 'The temporary image directory must be created.');
$sourcePath = $temporaryRoot . DIRECTORY_SEPARATOR . 'source.png';
$testImage = imagecreatetruecolor(80, 40);
assertGetImgSecurity($testImage instanceof GdImage, 'The source test image must be created.');
$testColor = imagecolorallocate($testImage, 12, 80, 140);
imagefilledrectangle($testImage, 0, 0, 79, 39, $testColor);
imagepng($testImage, $sourcePath);
imagedestroy($testImage);

$_SERVER['DOCUMENT_ROOT'] = $temporaryRoot;
$localContent = getImgLoadLocalSource('/source.png');
assertGetImgSecurity($localContent !== '', 'A valid local image must be readable.');
$localImage = imagecreatefromstring($localContent);
assertGetImgSecurity($localImage instanceof GdImage, 'A valid local image must be decoded.');
$encodedImage = getImgEncode($localImage, 80, 40, 40, 20, 'png');
imagedestroy($localImage);
$encodedInfo = getimagesizefromstring($encodedImage);
assertGetImgSecurity(
    is_array($encodedInfo) && $encodedInfo[0] === 40 && $encodedInfo[1] === 20,
    'The secured proxy must resize and encode a local image.'
);

unlink($sourcePath);
rmdir($temporaryRoot);

$source = file_get_contents(dirname(__DIR__) . '/shared/getImg.php');
assertGetImgSecurity(is_string($source), 'The endpoint source must be readable.');
assertGetImgSecurity(!str_contains($source, 'HTTP_COOKIE'), 'The endpoint must never forward visitor cookies.');
assertGetImgSecurity(
    str_contains($source, 'CURLOPT_FOLLOWLOCATION => false'),
    'Automatic redirects must remain disabled to prevent allowlist bypasses.'
);
assertGetImgSecurity(!is_file(dirname(__DIR__) . '/ai.php'), 'The obsolete AI endpoint must remain deleted.');

echo "getimg_security_test: OK\n";
