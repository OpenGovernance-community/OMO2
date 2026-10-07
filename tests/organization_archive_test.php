<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/shared_functions.php';
use dbObject\{OrganizationArchive, OrganizationExport, OrganizationTransferDates};
function archiveCheck(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function archiveReject(callable $action, string $message): void {
    try { $action(); } catch (Throwable $e) { return; }
    throw new RuntimeException($message);
}
$root = sys_get_temp_dir().'/omo-archive-test-'.bin2hex(random_bytes(8));
mkdir($root.'/img', 0755, true);
$_SERVER['DOCUMENT_ROOT'] = $root;
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1sAAAAASUVORK5CYII=');
file_put_contents($root.'/img/fixture.png', $png);
$payload = ['format' => OrganizationExport::FORMAT, 'version' => 4, 'exportedAt' => '2026-10-06T23:50:00+02:00',
    'organization' => ['logo' => '/img/fixture.png', 'banner' => '/img/fixture.png'],
    'holons' => [['icon' => '/img/fixture.png']], 'modules' => ['members' => ['records' => [['image' => '/img/fixture.png']]]],
    'source' => ['system' => 'omo2']];
$written = [];
try {
    $made = OrganizationArchive::create($payload, $root.'/valid.zip');
    archiveCheck($made['images'] === 1, 'Identical organization, holon and member images must be deduplicated');
    archiveCheck(OrganizationArchive::read($root.'/valid.zip')['files'] === [], 'Preview must never write images');
    $read = OrganizationArchive::read($root.'/valid.zip', true);
    $written = $read['files'];
    archiveCheck(count($written) === 1 && file_get_contents($written[0]) === $png, 'Image bytes must survive');
    archiveCheck($read['payload']['holons'][0]['icon'] === $read['payload']['organization']['logo'], 'Holon reference must be restored');
    $importRoot = $root.'/img/upload/import';
    chmod($importRoot, 0555);
    clearstatcache(true, $importRoot);
    try {
        // Root bypasses filesystem permissions; run this regression as the web user.
        if (!is_writable($importRoot)) {
            $permissionRejected = false;
            try { OrganizationArchive::read($root.'/valid.zip', true); }
            catch (RuntimeException $e) { $permissionRejected = str_contains($e->getMessage(), 'droits du serveur web'); }
            archiveCheck($permissionRejected, 'Unwritable image storage must explain the required permissions');
            archiveCheck(count(glob($importRoot.'/*', GLOB_ONLYDIR)) === 1, 'Rejected import must not create a directory');
        }
    } finally { chmod($importRoot, 0755); }
    file_put_contents($root.'/legacy.json', json_encode($payload));
    archiveCheck(OrganizationArchive::read($root.'/legacy.json')['payload'] === $payload, 'Legacy JSON must remain supported');
    foreach (['../escape.php', 'images/not-an-image.php'] as $name) {
        copy($root.'/valid.zip', $root.'/invalid.zip');
        $zip = new ZipArchive(); $zip->open($root.'/invalid.zip'); $zip->addFromString($name, '<?php'); $zip->close();
        archiveReject(fn() => OrganizationArchive::read($root.'/invalid.zip', true), 'Unexpected ZIP path must be rejected');
    }
    copy($root.'/valid.zip', $root.'/invalid.zip');
    $zip = new ZipArchive(); $zip->open($root.'/invalid.zip');
    $entry = array_key_first($made['payload']['media']); $zip->addFromString($entry, 'corrupted'); $zip->close();
    archiveReject(fn() => OrganizationArchive::read($root.'/invalid.zip', true), 'Corrupted image must be rejected');
    $dates = ['exportedAt' => '2026-03-28T22:30:00+01:00', 'plannedStartAt' => '2026-03-29', 'plannedEndAt' => '2026-04-04',
        'checkedAt' => '2026-03-28T16:00:00+01:00', 'description' => '2026-03-29', 'blockedUntil' => null,
        'propertyDefinitions' => [['id' => 7, 'formatId' => 4], ['id' => 8, 'formatId' => 1]],
        'holons' => [['properties' => [['propertyId' => 7, 'value' => '2026-03-29'], ['propertyId' => 8, 'value' => '2026-03-29']]]]];
    $shift = OrganizationTransferDates::shift($dates, new DateTimeImmutable('2026-03-30', new DateTimeZone('Europe/Zurich')));
    archiveCheck($shift['plannedStartAt'] === '2026-03-31' && $shift['plannedEndAt'] === '2026-04-06', 'Shift must use calendar days across DST');
    archiveCheck($shift['checkedAt'] === '2026-03-30T16:00:00+01:00', 'Local hours must be preserved');
    archiveCheck($shift['holons'][0]['properties'][0]['value'] === '2026-03-31', 'Typed property dates must be shifted');
    archiveCheck($shift['holons'][0]['properties'][1]['value'] === '2026-03-29' && $shift['description'] === $dates['description'], 'Free text must stay unchanged');
    archiveCheck($shift['exportedAt'] === $dates['exportedAt'] && $shift['blockedUntil'] === null, 'Metadata and empty dates must stay unchanged');
    $back = OrganizationTransferDates::shift($dates, new DateTimeImmutable('2026-03-27'));
    archiveCheck($back['plannedStartAt'] === '2026-03-28', 'Negative shift must work');
    archiveReject(fn() => OrganizationTransferDates::shift(['exportedAt' => '2026-02-30']), 'Invalid export date must be rejected');
    archiveReject(fn() => OrganizationTransferDates::shift([]), 'Missing export date must be rejected');
    echo "organization_archive_test: OK\n";
} finally {
    OrganizationArchive::cleanup($written);
    foreach (glob($root.'/*') as $file) { if (is_file($file)) { unlink($file); } }
    unlink($root.'/img/fixture.png');
    @rmdir($root.'/img/upload/import'); @rmdir($root.'/img/upload'); rmdir($root.'/img'); rmdir($root);
}
