<?php
declare(strict_types=1);

$testDirectory = sys_get_temp_dir() . '/omo-maintenance-test-' . bin2hex(random_bytes(8));
putenv('RUNTIME_LOG_DIR=' . $testDirectory);
putenv('OMO_CRON_LOG_ENABLED=false');
require_once dirname(__DIR__) . '/common/omo_maintenance_lock.php';

function assertMaintenance(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$GLOBALS['dbServer'] = 'test';
$GLOBALS['dbName'] = 'first';
$runs = 0;
$run = static function () use (&$runs): array {
    $runs++;
    return ['runs' => $runs];
};
try {
    assertMaintenance(omoRunMaintenanceLocked($run, 'test') === ['runs' => 1], 'First run executes.');
    assertMaintenance(omoRunMaintenanceLocked($run, 'test')['skipped'] === 'recently_completed', 'Browser cooldown.');
    assertMaintenance(omoRunMaintenanceLocked($run, 'cli', true) === ['runs' => 2], 'CLI can bypass cooldown.');
    omoRunMaintenanceLocked(static function () use ($run): array {
        $nested = omoRunMaintenanceLocked($run, 'nested-cli', true);
        assertMaintenance($nested['skipped'] === 'already_running', 'Force must not bypass the lock.');
        return [];
    }, 'outer', true);
    assertMaintenance($runs === 2, 'Concurrent callback was not executed.');
    $GLOBALS['dbName'] = 'second';
    try {
        omoRunMaintenanceLocked(static function (): array { throw new LogicException('test failure'); }, 'test');
        throw new RuntimeException('Callback exception was swallowed.');
    } catch (LogicException $expected) {
        assertMaintenance($expected->getMessage() === 'test failure', 'Original exception preserved.');
    }
    assertMaintenance(omoRunMaintenanceLocked($run, 'retry') === ['runs' => 3], 'Failure releases lock without cooldown.');
    $GLOBALS['dbName'] = 'third';
    assertMaintenance(omoRunMaintenanceLocked($run, 'other-db') === ['runs' => 4], 'Databases have independent locks.');
    echo "OK: maintenance serialization, cooldown, force, exception and database scope.\n";
} finally {
    foreach (glob($testDirectory . '/omo-cron/*.lock') ?: [] as $testFile) {
        unlink($testFile);
    }
    rmdir($testDirectory . '/omo-cron');
    rmdir($testDirectory);
}
