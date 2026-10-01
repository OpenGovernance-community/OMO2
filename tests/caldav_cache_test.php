<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/caldavcache.class.php';

use dbObject\CalDavCache;

function assertCalDavCacheTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$cacheRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omo-caldav-cache-test-' . bin2hex(random_bytes(5));
putenv('CALDAV_CACHE_PATH=' . $cacheRoot);

$organizationId = 42;
$revision = CalDavCache::getOrganizationRevision($organizationId);
assertCalDavCacheTest($revision === 1, 'A new organization cache must start at revision 1.');

$key = CalDavCache::buildResponseKey(array('route' => '/scoped/42/7/descendants/2563eb/editable', 'viewer' => 9));
$response = array(
    'status' => 207,
    'headers' => array(
        'Content-Type: application/xml; charset=UTF-8',
        'Set-Cookie: secret=not-cached',
        'Content-Length: 999',
    ),
    'body' => '<d:multistatus xmlns:d="DAV:"/>',
);

assertCalDavCacheTest(CalDavCache::writeResponse($organizationId, $revision, $key, $response), 'The response must be written.');
$cachedResponse = CalDavCache::readResponse($organizationId, $revision, $key);
assertCalDavCacheTest(is_array($cachedResponse), 'The response must be readable.');
assertCalDavCacheTest($cachedResponse['status'] === 207, 'The cached status must be preserved.');
assertCalDavCacheTest($cachedResponse['body'] === $response['body'], 'The cached body must be preserved.');
assertCalDavCacheTest(count($cachedResponse['headers']) === 1, 'Sensitive and computed headers must not be cached.');

$lock = CalDavCache::acquireBuildLock($organizationId, $revision, $key, 0);
assertCalDavCacheTest(is_resource($lock), 'The first builder must acquire the cache lock.');
$secondLock = CalDavCache::acquireBuildLock($organizationId, $revision, $key, 0);
assertCalDavCacheTest($secondLock === null, 'A second builder must not acquire the same lock.');
CalDavCache::releaseBuildLock($lock);

$newRevision = CalDavCache::invalidateOrganization($organizationId);
assertCalDavCacheTest($newRevision === 2, 'Invalidation must increment the organization revision.');
assertCalDavCacheTest(CalDavCache::getOrganizationRevision($organizationId) === 2, 'The new revision must be immediately visible.');
assertCalDavCacheTest(CalDavCache::readResponse($organizationId, $newRevision, $key) === null, 'The obsolete response must not be visible through the new revision.');
assertCalDavCacheTest(CalDavCache::writeResponse($organizationId, $newRevision, $key, $response), 'The current revision response must be written.');
$currentResponsePath = $cacheRoot . DIRECTORY_SEPARATOR . 'org-42' . DIRECTORY_SEPARATOR . 'revision-2' . DIRECTORY_SEPARATOR . $key . '.cache';
touch($currentResponsePath, time() - 86400);

$obsoletePath = $cacheRoot . DIRECTORY_SEPARATOR . 'org-42' . DIRECTORY_SEPARATOR . 'revision-1';
touch($obsoletePath, time() - 120);
$deleted = CalDavCache::cleanup(60);
assertCalDavCacheTest($deleted >= 1, 'Cleanup must remove files from obsolete revisions after the grace period.');
assertCalDavCacheTest(!is_dir($obsoletePath), 'The obsolete revision directory must be removed.');
assertCalDavCacheTest(is_array(CalDavCache::readResponse($organizationId, $newRevision, $key)), 'A valid current revision must not expire with time.');

$revisionFile = $cacheRoot . DIRECTORY_SEPARATOR . 'org-42' . DIRECTORY_SEPARATOR . 'current-revision.txt';
unlink($currentResponsePath);
rmdir(dirname($currentResponsePath));
unlink($revisionFile);
rmdir(dirname($revisionFile));
rmdir($cacheRoot);
putenv('CALDAV_CACHE_PATH');

echo "caldav_cache_test: OK\n";
