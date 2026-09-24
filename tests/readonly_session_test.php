<?php
declare(strict_types=1);

$testDirectory = sys_get_temp_dir() . '/omo-session-test-' . bin2hex(random_bytes(8));
putenv('RUNTIME_LOG_DIR=' . $testDirectory);
require_once dirname(__DIR__) . '/common/auth.php';
// Keep authentication/session behavior real and isolate only permission data access.
final class SessionTestPermissionSet
{
    public const PERMISSION_CACHE_VERSION = 1;
    public static int $builds = 0;
    public static function buildUserPermissionSetForOrganization($userId, $organizationId): array
    {
        self::$builds++;
        return ['cacheVersion' => 1, 'userId' => $userId, 'organizationId' => $organizationId, 'permissions' => []];
    }
}
final class SessionTestHistory
{
    public static function getLatestOrganizationEntryId($organizationId): int { return 123; }
}
class_alias(SessionTestPermissionSet::class, 'dbObject\\HolonPermission');
class_alias(SessionTestHistory::class, 'dbObject\\History');
$_SERVER['HTTP_HOST'] = 'org1.localtest.me';
session_save_path($testDirectory);
session_id('test-' . bin2hex(random_bytes(8)));

function assertReadOnlySession(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$sessionFile = $testDirectory . '/sess_' . session_id();
try {
    session_start();
    $_SESSION['currentUser'] = 0;
    $_SESSION['omo_dashboard_layout_csrf'] = 'dashboard-token';
    $_SESSION['omo_application_view_preferences_csrf'] = 'preferences-token';
    commonReleaseReadOnlySession();
    assertReadOnlySession(session_status() === PHP_SESSION_NONE, 'Session is closed.');
    $probe = fopen($sessionFile, 'r+');
    assertReadOnlySession(flock($probe, LOCK_EX | LOCK_NB), 'Another request can immediately acquire the session lock.');
    fclose($probe);
    assertReadOnlySession($_SESSION['omo_dashboard_layout_csrf'] === 'dashboard-token', 'Rendering can still read tokens.');
    commonReleaseReadOnlySession(); // Safe when already closed.
    $_SESSION = [];
    session_start();
    assertReadOnlySession($_SESSION['omo_dashboard_layout_csrf'] === 'dashboard-token', 'Dashboard token persisted.');
    assertReadOnlySession($_SESSION['omo_application_view_preferences_csrf'] === 'preferences-token', 'Preferences token persisted.');
    $_SESSION['currentUser'] = 7;
    $_SESSION['currentOrganization'] = 3;
    commonReleaseReadOnlySession();
    assertReadOnlySession(SessionTestPermissionSet::$builds === 1, 'Permission cache built before closing.');
    $_SESSION = [];
    session_start();
    assertReadOnlySession($_SESSION['permissionCacheByOrganization'][3]['cachedAtHistoryId'] === 123, 'Permission metadata persisted.');
    commonReleaseReadOnlySession();
    assertReadOnlySession(SessionTestPermissionSet::$builds === 1, 'Fresh permission cache reused.');
    echo "OK: session unlock, token and permission cache persistence, repeated release.\n";
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    unlink($sessionFile);
    rmdir($testDirectory);
}
