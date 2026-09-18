<?php
declare(strict_types=1);

$documentSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/document.class.php');
$documentCollectionSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/arraydocument.class.php');
$calendarCreateSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/create.php');
$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
$projectEditorSource = (string)file_get_contents(dirname(__DIR__) . '/common/calendar/event-editor.js');

function assertCalendarPvTemplateVisibility(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertCalendarPvTemplateVisibility(
    strpos($documentCollectionSource, 'function loadPvTemplatesForOrganization') !== false
        && strpos($calendarCreateSource, 'loadPvTemplatesForOrganization($organizationId)') !== false,
    'The event editor must load all active PV templates from its organization before applying the event context.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'function canUseAsPvTemplateInOrganizationContext') !== false
        && strpos($documentSource, 'TYPE_CIRCLE') !== false
        && strpos($documentSource, 'isDescendantOf($targetHolonId, true)') !== false
        && strpos($documentSource, 'TYPE_ROLE') !== false,
    'PV template use must accept circle descendants and restrict role templates to their exact role.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'copyPvAgendaFromTemplate($pvTemplate, $organizationId, $userId, $resolvedHolonId)') !== false,
    'The PV copy operation must validate the actual event document context on the server.'
);
assertCalendarPvTemplateVisibility(
    strpos($calendarCreateSource, 'data-omo-calendar-pv-template-scope') !== false
        && strpos($calendarCreateSource, 'data-omo-calendar-context-path') !== false
        && strpos($calendarSource, 'function syncPvTemplateOptions') !== false
        && strpos($projectEditorSource, 'function syncPvTemplateOptions') !== false,
    'Calendar and project event forms must refresh template choices when their context changes.'
);

echo "calendar_pv_template_visibility_test: OK\n";
