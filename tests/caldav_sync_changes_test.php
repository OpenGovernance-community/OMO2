<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/caldav.php';

function assertCalDavSyncChangesTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$organizationId = 42;
$calendarSlug = 'organization-42';
$token = 'data:,omo-caldav-sync-v1-'
    . $organizationId
    . '-'
    . sha1('caldav-sync:' . $calendarSlug)
    . '-17';

assertCalDavSyncChangesTest(
    commonCalDavParseSyncCollectionToken($token, $organizationId, $calendarSlug) === 17,
    'A current CalDAV synchronization token must preserve its journal position.'
);
assertCalDavSyncChangesTest(
    commonCalDavParseSyncCollectionToken($token, $organizationId, 'organization-43') === false,
    'A synchronization token must not be reusable for another calendar.'
);
assertCalDavSyncChangesTest(
    commonCalDavParseSyncCollectionToken('data:,legacy-token', $organizationId, $calendarSlug) === false,
    'A legacy snapshot token must trigger a full resynchronization.'
);

$root = dirname(__DIR__);
$calDavSource = (string)file_get_contents($root . '/common/caldav.php');
$endpointSource = (string)file_get_contents($root . '/omo/api/caldav/index.php');
$permissionsSource = (string)file_get_contents($root . '/omo/api/calendar/permissions_shared.php');
$eventSource = (string)file_get_contents($root . '/class/dbobject/event.class.php');
$migrationSource = (string)file_get_contents($root . '/sql/2026-09-15-01-caldav-sync-changes.sql');

assertCalDavSyncChangesTest(
    str_contains($calDavSource, "'statusOnly' => 'HTTP/1.1 404 Not Found'")
        && str_contains($calDavSource, 'CalDavSyncChange::getLatestEventChangesSince'),
    'CalDAV synchronization must return tombstones for deleted or no-longer-visible events.'
);
assertCalDavSyncChangesTest(
    str_contains($calDavSource, 'commonCalDavHandleEventDelete')
        && str_contains($endpointSource, "\$allowedMethods[] = 'DELETE';"),
    'CalDAV must expose and dispatch DELETE for events when authorized.'
);
assertCalDavSyncChangesTest(
    str_contains($permissionsSource, "isAllowed('CAN_DELETE_EVENT'")
        && str_contains($permissionsSource, 'omoCalendarCanDeleteEvent')
        && str_contains($calDavSource, '$calendarCanDeleteEvents'),
    'CalDAV event deletion must use CAN_DELETE_EVENT independently from edit permission.'
);
assertCalDavSyncChangesTest(
    str_contains($eventSource, "CalDavSyncChange::recordEventChange(\$organizationId, \$eventId, 'deleted')"),
    'Deleting an event must add a CalDAV deletion tombstone.'
);
assertCalDavSyncChangesTest(
    str_starts_with($migrationSource, '-- @migration')
        && str_contains($migrationSource, 'CREATE TABLE IF NOT EXISTS `caldav_sync_change`'),
    'The CalDAV change journal must be deployed through a migration.'
);

echo "caldav_sync_changes_test: OK\n";
