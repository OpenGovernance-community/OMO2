<?php
declare(strict_types=1);

$eventSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/event.class.php');
$createSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/create.php');
$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
$detailSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/detail.php');
$dashboardSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/dashboard/modules/data/event.php');
$dashboardTemplateSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/dashboard/modules/event.php');
$projectEventsSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/events.php');
$calendarStyles = $calendarSource;

function assertCalendarEventStatus(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertCalendarEventStatus(
    strpos($eventSource, "const STATUS_OPTION = 'option';") !== false
        && strpos($eventSource, 'self::STATUS_OPTION =>') !== false
        && strpos($eventSource, 'function isDraftVisibleToViewer(') !== false,
    'Events must define the option status and creator-only draft visibility.'
);
assertCalendarEventStatus(
    strpos($createSource, 'name="status"') !== false
        && strpos($createSource, 'Event::STATUS_OPTION') !== false
        && strpos($createSource, '$event->set(\'status\', $status)') !== false,
    'The event editor must expose and persist the selected status.'
);
assertCalendarEventStatus(
    strpos($calendarSource, 'isDraftVisibleToViewer($currentUserId)') !== false
        && strpos($detailSource, 'isDraftVisibleToViewer($currentUserId)') !== false
        && strpos($dashboardSource, 'isDraftVisibleToViewer($currentUserId)') !== false,
    'Calendar, detail and dashboard views must enforce creator-only drafts.'
);
assertCalendarEventStatus(
    strpos($calendarStyles, '.omo-calendar__event-chip.is-status-option') !== false
        && strpos($calendarStyles, 'border-inline-start: 4px solid #eab308') !== false
        && strpos($calendarStyles, 'border-inline-start: 4px solid #16a34a') !== false
        && strpos($dashboardTemplateSource, 'is-status-') !== false
        && strpos($projectEventsSource, 'is-status-') !== false,
    'Option and confirmed events must expose their visual status classes.'
);

echo "calendar_event_status_test: OK\n";
