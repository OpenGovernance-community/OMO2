<?php
declare(strict_types=1);

$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
$dashboardDataSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/dashboard/modules/data/event.php');
$dashboardTemplateSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/dashboard/modules/event.php');
$sharedPermissionSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/permissions_shared.php');
$appSource = (string)file_get_contents(dirname(__DIR__) . '/omo/assets/js/app.js');

function assertCalendarEventDocument(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertCalendarEventDocument(
    strpos($calendarSource, 'new ArrayDocument()') !== false
        && strpos($calendarSource, "'field' => 'IDevent', 'op' => 'in'") !== false,
    'Calendar events must load associated documents in a batch.'
);
assertCalendarEventDocument(
    strpos($sharedPermissionSource, 'function omoCalendarBuildAssociatedDocumentOpenData') !== false
        && strpos($calendarSource, 'omoCalendarBuildAssociatedDocumentOpenData(') !== false,
    'Calendar document links must use the shared permission checks.'
);
assertCalendarEventDocument(
    substr_count($calendarSource, 'data-omo-calendar-open-url=') >= 4
        && strpos($calendarSource, 'data-omo-calendar-open-pv-editor-url=') !== false,
    'Calendar event representations must expose an openable document action.'
);
assertCalendarEventDocument(
    strpos($dashboardDataSource, "'documentUrl' =>") !== false
        && strpos($dashboardTemplateSource, 'data-omo-personal-space-document-url=') !== false
        && strpos($dashboardTemplateSource, 'data-omo-personal-space-document-pv-editor-url=') !== false,
    'Dashboard events must expose associated document metadata and an action button.'
);
assertCalendarEventDocument(
    strpos($appSource, 'function omoOpenAssociatedDocumentResult(') !== false
        && strpos($appSource, 'window.omoOpenAssociatedDocumentResult = omoOpenAssociatedDocumentResult;') !== false,
    'The shared document opener must be available to calendar and dashboard actions.'
);
assertCalendarEventDocument(
    strpos($calendarSource, '<strong class="omo-calendar__time-event-title"')
        < strpos($calendarSource, '<span class="omo-calendar__time-event-time-row"'),
    'Timed calendar events must render the title before the schedule block.'
);
assertCalendarEventDocument(
    strpos($calendarSource, 'display: flex;') !== false
        && strpos($calendarSource, 'flex-wrap: wrap;') !== false
        && strpos($calendarSource, 'min-height: 34px;') !== false,
    'Timed calendar events must wrap their blocks while keeping short titles visible.'
);

echo "calendar_event_document_test: OK\n";
