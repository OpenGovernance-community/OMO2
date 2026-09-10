<?php
declare(strict_types=1);

function assertProjectEventActions(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$projectEventsSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/events.php');
$projectScriptSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/projects.js');
$calendarCreateSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/create.php');

assertProjectEventActions(
    str_contains($projectEventsSource, 'data-omo-project-detail-event-menu')
        && str_contains($projectEventsSource, 'data-omo-project-detail-event-editor-url')
        && str_contains($projectEventsSource, 'data-omo-project-detail-event-delete-url'),
    'Project event cards must expose edit, duplicate and delete menu actions.'
);
assertProjectEventActions(
    str_contains($projectEventsSource, "'&duplicate_id='"),
    'Project event duplication must open the event editor with a duplication source.'
);
assertProjectEventActions(
    str_contains($projectScriptSource, 'function deleteProjectEvent(url)')
        && str_contains($projectScriptSource, "new CustomEvent('omo-project-event-saved'")
        && str_contains($projectScriptSource, 'data-omo-project-detail-event-menu-toggle'),
    'Project event menu actions must handle deletion and refresh the event list.'
);
assertProjectEventActions(
    str_contains($calendarCreateSource, '$isDuplicateMode = !$isEditMode && $duplicateEventId > 0;')
        && str_contains($calendarCreateSource, '$prefillEvent = $isEditMode ? $event : $duplicateEvent;')
        && str_contains($calendarCreateSource, '($isDuplicateMode ? null : ($initialStartDefault')
        && str_contains($calendarCreateSource, '($isDuplicateMode ? null : (clone $startDefault)->modify')
        && str_contains($calendarCreateSource, '$duplicateAssociatedDocument->get(\'title\')'),
    'Duplicated events must keep their editable data while starting with empty dates.'
);

echo "project_event_actions_test: OK\n";
