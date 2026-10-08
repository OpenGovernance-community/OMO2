<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/site_update_admin.php';

siteUpdateAdminAssertUploadStorageExcluded([]);
siteUpdateAdminAssertUploadStorageExcluded(['.htaccess' => [], 'img/logo.png' => [], 'img/uploads/example.png' => []]);
foreach (['img/upload', 'img/upload/.htaccess', 'img/upload/user/photo.jpg'] as $path) {
    try {
        siteUpdateAdminAssertUploadStorageExcluded([$path => []]);
        throw new LogicException('Deployment must reject a tracked runtime upload path: ' . $path);
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), $path)) throw $error;
    }
}
// Exercise the transition on a real symlink, without reading shared storage
// into the backup. Run this filesystem regression on Linux, like production.
if (PHP_OS_FAMILY === 'Windows') {
    throw new RuntimeException('Run this test in the Linux PHP container.');
}
$fixture = sys_get_temp_dir() . '/site-update-upload-' . bin2hex(random_bytes(8));
$previousLogDir = getenv('RUNTIME_LOG_DIR');
$previousErrorLog = ini_get('error_log');
$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new LogicException($message);
};
$removeFixture = static function (string $path) use (&$removeFixture): void {
    if (is_link($path) || !is_dir($path)) {
        unlink($path);
        return;
    }
    foreach (scandir($path) as $name) {
        if ($name !== '.' && $name !== '..') $removeFixture($path . '/' . $name);
    }
    rmdir($path);
};
try {
    mkdir($fixture . '/site/img', 0700, true);
    mkdir($fixture . '/common/upload/user', 0700, true);
    file_put_contents($fixture . '/common/upload/.htaccess', 'shared protection');
    file_put_contents($fixture . '/common/upload/user/photo.jpg', 'original image');
    symlink('../../common/upload', $fixture . '/site/img/upload');
    putenv('RUNTIME_LOG_DIR=' . $fixture . '/log');
    $context = ['repoRoot' => $fixture . '/site', 'localCommit' => 'old-release'];
    $payload = ['remoteCommit' => 'fixed-release', 'localChanges' => [
        ['path' => 'img/upload', 'states' => ['untracked'], 'overlapsRemoteUpdate' => false],
        ['path' => 'img/upload/.htaccess', 'states' => ['modified'], 'overlapsRemoteUpdate' => true],
        ['path' => 'img/upload/missing', 'states' => ['indexed'], 'overlapsRemoteUpdate' => true],
        ['path' => 'unrelated-local.php', 'states' => ['untracked'], 'overlapsRemoteUpdate' => false],
    ]];
    $backup = siteUpdateAdminBackupLocalChanges($context, $payload, []);
    $manifest = json_decode(file_get_contents($backup['backupPath'] . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $check($manifest['files'] === ['img/upload' => ['type' => 'symlink', 'target' => '../../common/upload']], 'Only the storage link must be backed up, once.');
    $check($backup['backedUpFileCount'] === 0, 'No shared files should be copied.');
    $check(is_link($fixture . '/site/img/upload'), 'Storage link must remain intact.');
    $check(file_get_contents($fixture . '/common/upload/.htaccess') === 'shared protection', 'Shared protection must remain intact.');
    $check(file_get_contents($fixture . '/common/upload/user/photo.jpg') === 'original image', 'Shared images must remain intact.');

    $payload['localChanges'] = [['path' => 'unrelated-local.php', 'states' => ['untracked'], 'overlapsRemoteUpdate' => false]];
    $check(siteUpdateAdminBackupLocalChanges($context, $payload, [])['backupPath'] === '', 'Non-conflicting untracked files must be left alone.');

    symlink('../common/upload', $fixture . '/site/other');
    $payload['localChanges'] = [['path' => 'other/.htaccess', 'states' => ['modified'], 'overlapsRemoteUpdate' => true]];
    try {
        siteUpdateAdminBackupLocalChanges($context, $payload, []);
        throw new LogicException('Other directory links must still be rejected.');
    } catch (RuntimeException $error) {
        $check(str_contains($error->getMessage(), 'other/.htaccess'), 'Expected unsafe parent link rejection.');
    }
    try {
        siteUpdateAdminBackupLocalChanges($context, $payload, ['img/upload/.htaccess' => []]);
        throw new LogicException('Backup must reject remote upload entries before proceeding.');
    } catch (RuntimeException $error) {
        $check(str_contains($error->getMessage(), 'img/upload/.htaccess'), 'Expected tracked upload rejection.');
    }
} finally {
    putenv($previousLogDir === false ? 'RUNTIME_LOG_DIR' : 'RUNTIME_LOG_DIR=' . $previousLogDir);
    ini_set('error_log', $previousErrorLog);
    if (is_dir($fixture)) $removeFixture($fixture);
}

echo "site_update_upload_storage_test: OK\n";
