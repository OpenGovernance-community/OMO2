<?php
declare(strict_types=1);

$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
$calendarScript = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/calendar.js');
$dashboardDataSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/dashboard/modules/data/event.php');
$dashboardTemplateSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/dashboard/modules/event.php');
$sharedPermissionSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/permissions_shared.php');
$calendarDetailSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/detail.php');
$documentLifecycleSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/lifecycle_action.php');
$calendarStyles = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/calendar.css');
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
    strpos($calendarScript, "attr('open-url', event.documentUrl)") !== false
        && strpos($calendarScript, "attr('open-pv-editor-url', event.documentPvEditorUrl)") !== false,
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
    strpos($calendarDetailSource, 'data-omo-calendar-document-delete-id=') !== false
        && strpos($calendarDetailSource, 'canDeleteDocument(true)') !== false
        && strpos($documentLifecycleSource, 'canDeleteDocument($allowEventDocument)') !== false
        && strpos($calendarScript, 'function deleteAssociatedDocument(') !== false,
    'The event detail must expose a confirmed deletion action for removable linked documents.'
);
assertCalendarEventDocument(
    strpos($calendarScript, "tag('strong', 'omo-calendar__time-event-title'")
        < strpos($calendarScript, "timeRow(event, 'omo-calendar__time-event-time')"),
    'Timed calendar events must render the title before the schedule block.'
);
assertCalendarEventDocument(
    strpos($calendarStyles, 'display: flex;') !== false
        && strpos($calendarStyles, 'flex-wrap: wrap;') !== false
        && strpos($calendarStyles, 'min-height: 34px;') !== false,
    'Timed calendar events must wrap their blocks while keeping short titles visible.'
);

echo "calendar_event_document_test: OK\n";
