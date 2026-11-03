<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{Organization, OrganizationArchive, OrganizationExport};

function importMediaCheck(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}

$root = sys_get_temp_dir() . '/omo-import-media-' . bin2hex(random_bytes(8));
mkdir($root . '/source/img', 0755, true);
mkdir($root . '/target/img/upload', 0755, true);
mkdir($root . '/shared', 0755);
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=');
file_put_contents($root . '/source/img/fixture.png', $png);
file_put_contents($root . '/outside.png', $png);
$_SERVER['DOCUMENT_ROOT'] = $root . '/source';
$payload = [
    'format' => OrganizationExport::FORMAT, 'version' => 4, 'source' => ['system' => 'omo2'],
    'organization' => ['logo' => '/img/fixture.png', 'banner' => '/img/fixture.png'],
    'holons' => [['icon' => '/img/fixture.png', 'children' => [['icon' => '/img/fixture.png']]]],
    'modules' => ['members' => ['records' => [['image' => '/img/fixture.png']]]],
];
$sanitize = new ReflectionMethod(Organization::class, 'sanitizeOmo1ImportedMediaReferences');
$existingPath = new ReflectionMethod(Organization::class, 'omo1ImportExistingImagePath');
$files = [];
$links = [];
try {
    OrganizationArchive::create($payload, $root . '/source.zip');
    unlink($root . '/source/img/fixture.png');
    $_SERVER['DOCUMENT_ROOT'] = $root . '/target';
    $modes = ['normal'];
    // Windows may require privileges to create symbolic links; Linux hosts support them.
    if (@symlink($root . '/shared', $root . '/link-probe')) {
        unlink($root . '/link-probe');
        $modes = ['normal', 'linked-upload', 'linked-img'];
    }
    foreach ($modes as $mode) {
        if ($mode === 'linked-upload') {
            rmdir($root . '/target/img/upload');
            $links[] = $root . '/target/img/upload';
            importMediaCheck(symlink($root . '/shared', end($links)), 'Upload symlink fixture');
        } elseif ($mode === 'linked-img') {
            unlink(array_pop($links));
            rmdir($root . '/target/img');
            $links[] = $root . '/target/img';
            importMediaCheck(symlink($root . '/shared', end($links)), 'Image symlink fixture');
        }
        $read = OrganizationArchive::read($root . '/source.zip', true);
        $files = $read['files'];
        $warnings = [];
        $args = [$read['payload'], &$warnings];
        $restored = $sanitize->invokeArgs(null, $args);
        importMediaCheck($warnings === [], $mode . ': extracted images must not be discarded');
        $path = $read['payload']['organization']['logo'];
        importMediaCheck(str_starts_with($path, '/img/upload/import/'), 'Public upload path');
        foreach ([$restored['organization']['logo'], $restored['organization']['banner'], $restored['holons'][0]['icon'], $restored['holons'][0]['children'][0]['icon'], $restored['modules']['members']['records'][0]['image']] as $reference) {
            importMediaCheck($reference === $path, $mode . ': keep the public URL instead of the physical symlink target');
            importMediaCheck(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $reference) === $png, $mode . ': preserve image bytes');
        }
        $export = OrganizationArchive::create($restored, $root . '/roundtrip.zip');
        importMediaCheck($export['images'] === 1 && $export['warnings'] === [], $mode . ': imported images remain exportable');
        foreach (['https://example.invalid/image.png', '//example.invalid/image.png', '/outside.png', '/img/../../outside.png', '/img/upload/../../../outside.png', '/img/%2e%2e/outside.png', '/img/missing.png', "/img/bad\0.png"] as $unsafe) {
            importMediaCheck($existingPath->invoke(null, $unsafe) === null, 'Reject unsafe or unavailable image path');
        }
        if ($links !== []) {
            $escape = $_SERVER['DOCUMENT_ROOT'] . '/img/upload/escape.png';
            importMediaCheck(symlink($root . '/outside.png', $escape), 'Escaping symlink fixture');
            try {
                importMediaCheck($existingPath->invoke(null, '/img/upload/escape.png') === null, 'Reject individual symlinks escaping the image storage');
                $unsafePayload = $payload;
                $unsafePayload['organization']['logo'] = '/img/upload/escape.png';
                $export = OrganizationArchive::create($unsafePayload, $root . '/unsafe.zip');
                importMediaCheck($export['images'] === 0, 'Do not export files outside image storage');
            } finally { unlink($escape); }
        }
        OrganizationArchive::cleanup($files);
        $files = [];
        rmdir($_SERVER['DOCUMENT_ROOT'] . '/img/upload/import');
        if ($mode === 'linked-img') { rmdir($_SERVER['DOCUMENT_ROOT'] . '/img/upload'); }
    }
    echo 'organization_import_media_test: OK (' . implode(', ', $modes) . ")\n";
} finally {
    OrganizationArchive::cleanup($files);
    @rmdir($root . '/target/img/upload/import');
    // Remove only the links and temporary directories created by this test.
    foreach ($links as $link) { @unlink($link); }
    foreach (['source.zip', 'roundtrip.zip', 'unsafe.zip', 'outside.png', 'source/img/fixture.png'] as $file) { @unlink($root . '/' . $file); }
    foreach (['target/img/upload', 'target/img', 'target', 'source/img', 'source', 'shared/upload/import', 'shared/upload', 'shared/import', 'shared', ''] as $directory) { @rmdir($root . '/' . $directory); }
}
