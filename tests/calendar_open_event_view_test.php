<?php
declare(strict_types=1);

$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
$appSource = (string)file_get_contents(dirname(__DIR__) . '/omo/assets/js/app.js');

function assertCalendarOpenEventView(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$preferencesContextPosition = strpos($calendarSource, '$applicationViewPreferences = omoApplicationViewPreferencesGetContext(');
$viewPreferencePosition = strpos($calendarSource, '$viewMode = omoCalendarParseView(', $preferencesContextPosition);
$scopePreferencePosition = strpos($calendarSource, '$requestedScopeRaw = trim((string)omoApplicationViewPreferencesGetInitialValue(', $preferencesContextPosition);

assertCalendarOpenEventView(
    $preferencesContextPosition !== false
        && $viewPreferencePosition > $preferencesContextPosition
        && $scopePreferencePosition > $preferencesContextPosition,
    'Calendar view and scope preferences must be resolved after the application context, including for an opened event.'
);
assertCalendarOpenEventView(
    strpos($calendarSource, 'if ($openEventId <= 0) {') === false,
    'Opening an event must not bypass the saved calendar preferences.'
);
assertCalendarOpenEventView(
    strpos($calendarSource, 'if ($requestedViewRaw === \'\')') === false
        && strpos($calendarSource, '$viewMode = \'day\';') === false,
    'Opening an event must not force the calendar into the day view.'
);
assertCalendarOpenEventView(
    strpos($appSource, 'function omoReopenCurrentDrawerRoute(') !== false
        && strpos($appSource, 'omoReopenCurrentDrawerRoute(calendarEventRouteToken, route.oid, targetCid, route)') !== false,
    'Reopening the same dashboard event must restore its cached drawer without navigating the background panels again.'
);
assertCalendarOpenEventView(
    strpos($appSource, 'const openAfterRefresh = options.open === true;') !== false
        && strpos($appSource, "routeToken: routeToken,\n                open: true") !== false,
    'Refreshing an existing drawer after a context change must make the parent drawer visible again.'
);

echo "calendar_open_event_view_test: OK\n";
