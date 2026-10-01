<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/calendarshare.class.php';

use dbObject\CalendarShare;

function assertCalendarConnectIcsTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$scopeKey = CalendarShare::buildScopedCalendarKey(12, 34, 'descendants');
assertCalendarConnectIcsTest(
    $scopeKey === 'omo:12:34:descendants',
    'A scoped ICS link must have a stable calendar scope key.'
);
assertCalendarConnectIcsTest(
    CalendarShare::parseScopedCalendarKey($scopeKey) === [
        'organizationId' => 12,
        'holonId' => 34,
        'range' => 'descendants',
    ],
    'A scoped ICS link must restore its organization, context and range.'
);
assertCalendarConnectIcsTest(
    CalendarShare::buildScopedCalendarKey(0, 34, 'descendants') === ''
        && CalendarShare::parseScopedCalendarKey('omo:12:34:invalid') === null,
    'Invalid scoped ICS link parameters must be rejected.'
);

$root = dirname(__DIR__);
$connectSource = (string)file_get_contents($root . '/omo/api/calendar/connect.php');
$calendarSource = (string)file_get_contents($root . '/omo/api/calendar/index.php');
$calendarSource .= (string)file_get_contents($root . '/omo/api/calendar/calendar.js');
$feedSource = (string)file_get_contents($root . '/common/calendar/share-feed.php');
$migrationSource = (string)file_get_contents($root . '/sql/2026-09-21-05-calendar-scoped-ics.sql');

assertCalendarConnectIcsTest(
    strpos($connectSource, "'calendar.connect.ics_url'") !== false
        && strpos($connectSource, 'data-omo-calendar-connect-ics-url') < strpos($connectSource, 'data-omo-calendar-connect-url'),
    'The ICS URL must be rendered before the CalDAV URL in the connection panel.'
);
assertCalendarConnectIcsTest(
    strpos($connectSource, 'data-omo-calendar-connect-color') < strpos($connectSource, 'id="omoCalendarConnectUrl"')
        && str_contains($connectSource, 'omo-calendar-connect__color-control')
        && str_contains($connectSource, 'generic-action-button generic-action-button--main" data-omo-calendar-connect-copy data-omo-calendar-connect-copy-target="caldav"'),
    'The CalDAV color control and copy action must be prominent beside its URL.'
);
assertCalendarConnectIcsTest(
    str_contains($connectSource, "data-omo-calendar-connect-copy-target=\"ics\"")
        && str_contains($calendarSource, "copyTarget === 'ics'"),
    'The connection panel copy control must support the ICS URL.'
);
assertCalendarConnectIcsTest(
    substr_count($connectSource, '<details class="generic-accordion">') >= 2
        && str_contains($connectSource, "'calendar.connect.ics_steps.title'")
        && str_contains($connectSource, "'calendar.connect.caldav_steps.title'"),
    'Both connection URLs must provide their own detailed accordion instructions.'
);
assertCalendarConnectIcsTest(
    str_contains($feedSource, 'calendarShareLoadScopedOmoEvents')
        && str_contains($feedSource, 'getScopedCalendarConfig'),
    'The ICS feed must limit scoped subscriptions to the selected OMO calendar context.'
);
assertCalendarConnectIcsTest(
    str_starts_with($migrationSource, '-- @migration')
        && str_contains($migrationSource, 'scope_key'),
    'Scoped ICS subscriptions must be deployed through a migration.'
);

echo "calendar_connect_ics_test: OK\n";
