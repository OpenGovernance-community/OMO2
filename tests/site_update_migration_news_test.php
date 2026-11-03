<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/site_update_admin.php';

// Execute the real CLI entry point in an isolated tree; no SQL or real news writes.
$fixture = sys_get_temp_dir() . '/site-update-migrations-' . bin2hex(random_bytes(8));
$removeFixture = static function (string $path) use (&$removeFixture): void {
    if (!is_dir($path) || is_link($path)) { unlink($path); return; }
    foreach (scandir($path) as $name) {
        if ($name !== '.' && $name !== '..') { $removeFixture($path . '/' . $name); }
    }
    rmdir($path);
};
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
};
try {
    foreach (['scripts', 'db', 'vendor', 'includes', 'sql'] as $directory) { mkdir($fixture . '/' . $directory, 0700, true); }
    copy(dirname(__DIR__) . '/scripts/run-migrations.php', $fixture . '/scripts/run-migrations.php');
    file_put_contents($fixture . '/vendor/autoload.php', '<?php');
    file_put_contents($fixture . '/shared_functions.php', '<?php if (headers_sent()) { throw new RuntimeException("Late bootstrap"); }');
    file_put_contents($fixture . '/db/connection.php', <<<'PHP'
<?php
$GLOBALS['dbName'] = 'fixture';
function envValue($key, $fallback = '') { return $fallback; }
function createPDOConnection($name) { return new stdClass(); }
PHP);
    file_put_contents($fixture . '/db/migrations.php', <<<'PHP'
<?php
function getPendingSqlMigrations($pdo, $dir) { return [['filename' => 'fixture.sql']]; }
function runSqlMigrations($pdo, $dir) {
    if (is_file(dirname(__DIR__) . '/fail')) { throw new RuntimeException('Migration failed'); }
    file_put_contents(dirname(__DIR__) . '/migrated', 'yes');
}
PHP);
    file_put_contents($fixture . '/includes/site_update_admin.php', <<<'PHP'
<?php
function siteUpdateAdminReadState() {
    return json_decode(file_get_contents(dirname(__DIR__) . '/state.json'), true);
}
function siteUpdateAdminPublishInstalledNews($state) {
    if (!is_file(dirname(__DIR__) . '/migrated')) { throw new RuntimeException('Premature publication'); }
    if (empty($state['localCommit']) || empty($state['remoteCommit'])) { throw new RuntimeException('Lost old updater state'); }
    file_put_contents(dirname(__DIR__) . '/published', json_encode($state));
    return 42;
}
PHP);
    $oldState = ['status' => 'running', 'requiresCompletion' => true, 'localCommit' => str_repeat('a', 40), 'remoteCommit' => str_repeat('b', 40)];
    file_put_contents($fixture . '/state.json', json_encode($oldState));
    file_put_contents($fixture . '/fail', 'yes');
    siteUpdateAdminRunCommand([PHP_BINARY, $fixture . '/scripts/run-migrations.php'], $fixture, $exitCode);
    $check($exitCode !== 0 && !is_file($fixture . '/published'), 'Failed migrations must not publish');
    unlink($fixture . '/fail');
    $output = siteUpdateAdminRunCommand([PHP_BINARY, $fixture . '/scripts/run-migrations.php'], $fixture, $exitCode);
    $check($exitCode === 0 && str_contains($output, 'News de mise a jour : 42'), 'First deployment publishes through fresh worker');
    $check(json_decode(file_get_contents($fixture . '/published'), true) === $oldState, 'Preserve old updater revision range');
    unlink($fixture . '/published');
    $oldState['status'] = 'failed';
    file_put_contents($fixture . '/state.json', json_encode($oldState));
    siteUpdateAdminRunCommand([PHP_BINARY, $fixture . '/scripts/run-migrations.php'], $fixture, $exitCode);
    $check($exitCode === 0 && !is_file($fixture . '/published'), 'Ordinary migration execution must not publish pending failures');
    echo "site_update_migration_news_test: OK\n";
} finally { $removeFixture($fixture); }
