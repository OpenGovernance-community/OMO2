<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{Application, ArrayApplication, DbObject, Organization, OrganizationApplication};

function appCacheExpect(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
function appCacheFixture(string $class, array $fields): DbObject {
    $object = new $class();
    foreach ($fields as $key => $value) { $object->set($key, $value); }
    appCacheExpect(!empty($object->save()['status']), 'Fixture save failed: ' . $class);
    return $object;
}
$log = tempnam(sys_get_temp_dir(), 'app-cache-');
foreach (['DB_QUERY_LOG_ENABLED' => 'true', 'DB_QUERY_LOG_MIN_MS' => '100000', 'DB_QUERY_LOG_PATH' => $log] as $key => $value) {
    putenv($key . '=' . $value); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
$stats = new ReflectionProperty(DbObject::class, '_sqlPerformanceStats');
$queryCount = static fn(): int => (int)$stats->getValue()['queryCount'];
$requestCache = new ReflectionProperty(Application::class, 'organizationCatalogs');
$session = $_SESSION;
$pdo = DbObject::getPdo(); $pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $org = appCacheFixture(Organization::class, ['name' => 'App cache', 'shortname' => $nonce]);
    $other = appCacheFixture(Organization::class, ['name' => 'Other app cache', 'shortname' => 'other-' . $nonce]);
    $app = appCacheFixture(Application::class, ['label' => 'App cache fixture', 'hash' => 'cache-' . $nonce,
        'url' => 'https://example.invalid/', 'navigationmode' => 'drawer', 'requires_login' => 1, 'active' => 1]);
    $link = appCacheFixture(OrganizationApplication::class, ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
    $hash = (string)$app->get('hash');
    $before = $queryCount();
    appCacheExpect($org->isApplicationEnabled($hash, 1), 'Enabled application is available to a signed-in user.');
    appCacheExpect($queryCount() - $before === 2, 'Cold catalog uses one version check and one bulk load.');
    $before = $queryCount();
    appCacheExpect(!$org->isApplicationEnabled($hash, 0), 'Login requirement is evaluated for each caller.');
    appCacheExpect(Application::isEnabledForOrganization($app->getId(), $org->getId()), 'ID lookup shares the catalog.');
    $applications = $org->getApplications(1);
    appCacheExpect(count($applications) === 1, 'Only locally enabled applications are returned.');
    foreach ($applications as $loaded) { appCacheExpect($loaded->get('label') === 'App cache fixture', 'Catalog objects are hydrated.'); }
    appCacheExpect($queryCount() === $before, 'Repeated checks and navigation need no additional SQL.');
    $available = new ArrayApplication();
    $available->loadAvailableForOrganization($org->getId(), 1);
    $availableIds = [];
    foreach ($available as $loaded) { $availableIds[] = (int)$loaded->getId(); $loaded->get('label'); }
    appCacheExpect(in_array((int)$app->getId(), $availableIds, true), 'Available catalog includes the fixture.');
    appCacheExpect($queryCount() === $before, 'Available catalog and hydrated labels share the cache without extra SQL.');
    $available->loadAvailableForOrganization($org->getId(), 0);
    foreach ($available as $loaded) { appCacheExpect((int)$loaded->getId() !== (int)$app->getId(), 'Available catalog respects login requirements.'); }
    appCacheExpect(count($org->getApplications(0)) === 0 && count($org->getApplications(0, true)) === 1, 'Anonymous navigation and explicit login bypass stay isolated.');
    appCacheExpect(!$other->isApplicationEnabled($hash, 1), 'Organization caches are isolated.');
    $requestCache->setValue(null, []); // Simulate the next request with the same session.
    $before = $queryCount();
    appCacheExpect($org->isApplicationEnabled($hash, 1), 'Warm session catalog is reusable.');
    appCacheExpect($queryCount() - $before === 1, 'Warm session only checks configuration versions.');
    $staleSession = $_SESSION;
    $link->set('active', 0); $link->save();
    appCacheExpect(!$org->isApplicationEnabled($hash, 1), 'Local writes invalidate the cache immediately.');
    $available->loadAvailableForOrganization($org->getId(), 1);
    $availableIds = [];
    foreach ($available as $loaded) { $availableIds[] = (int)$loaded->getId(); }
    appCacheExpect(in_array((int)$app->getId(), $availableIds, true), 'Disabled organization applications remain available for activation.');
    $_SESSION = $staleSession; $requestCache->setValue(null, []);
    appCacheExpect(!$org->isApplicationEnabled($hash, 1), 'A different session detects the deactivation on its next request.');
    $link->set('active', 1); $link->save();
    appCacheExpect($org->isApplicationEnabled($hash, 1), 'Reactivation is immediately visible.');
    $staleSession = $_SESSION;
    $app->set('navigationmode', 'panel'); $app->save();
    $_SESSION = $staleSession; $requestCache->setValue(null, []);
    appCacheExpect(!$org->isApplicationEnabled($hash, 1) && count($org->getApplications(1)) === 0, 'Global catalog edits invalidate other sessions.');
    $app->set('navigationmode', 'drawer'); $app->save();
    appCacheExpect($org->isApplicationEnabled($hash, 1), 'Restored navigation mode is visible.');
    $staleSession = $_SESSION;
    $link->delete();
    $_SESSION = $staleSession; $requestCache->setValue(null, []);
    appCacheExpect(!$org->isApplicationEnabled($hash, 1), 'Deleted organization links invalidate other sessions.');
    $available->loadAvailableForOrganization($org->getId(), 1);
    $availableIds = [];
    foreach ($available as $loaded) { $availableIds[] = (int)$loaded->getId(); }
    appCacheExpect(in_array((int)$app->getId(), $availableIds, true), 'Unassigned applications remain available for activation.');
    appCacheExpect($org->isApplicationEnabled('unknown-' . $nonce, 1), 'Unknown module behavior is preserved.');
    echo "application_catalog_cache_test: OK (cold 2 queries, warm 1, repeated 0; isolation and cross-session invalidation)\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    Application::clearOrganizationCatalogCache(); $_SESSION = $session;
    DbObject::writeSqlPerformanceRequestSummary(); if (is_file($log)) { unlink($log); }
}
