<?php
declare(strict_types=1);

$documentSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/document.class.php');
$documentCollectionSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/arraydocument.class.php');
$calendarCreateSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/create.php');
$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
$calendarSource .= (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/calendar.js');
$projectEditorSource = (string)file_get_contents(dirname(__DIR__) . '/common/calendar/event-editor.js');
$projectDocumentPickerSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/document_picker.php');
$projectSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/projects.js');
$templateActionSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/template_action.php');
$documentsSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/index.php');
$etherpadSource = (string)file_get_contents(dirname(__DIR__) . '/common/etherpad.php');
$ethercalcSource = (string)file_get_contents(dirname(__DIR__) . '/common/ethercalc.php');

function assertCalendarPvTemplateVisibility(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertCalendarPvTemplateVisibility(
    strpos($documentCollectionSource, 'function loadDocumentTemplatesForOrganization') !== false
        && strpos($calendarCreateSource, 'loadDocumentTemplatesForOrganization($organizationId)') !== false,
    'The event editor must load active document templates from its organization before applying the event context.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'function canUseAsPvTemplateInOrganizationContext') !== false
        && strpos($documentSource, 'TYPE_CIRCLE') !== false
        && strpos($documentSource, 'isDescendantOf($targetHolonId, true)') !== false
        && strpos($documentSource, 'TYPE_ROLE') !== false,
    'PV template use must accept circle descendants and restrict role templates to their exact role.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'function canUseAsDocumentTemplate(int $organizationId)') !== false
        && strpos($documentSource, '&& $this->canViewDirectlyInOrganization($organizationId);') === false,
    'Template availability must depend on the target scope rather than the current viewer access.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'createFromDocumentTemplateInOrganizationContext') !== false
        && strpos($documentSource, "'pv_template_id'] = (int)\$template->getId()") !== false,
    'The PV copy operation must keep using the validated template when a meeting document is created.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'TYPE_UPLOADED_FILE && $template->hasStoredFile()') !== false
        && strpos($documentSource, 'downloadDocumentFileFromStorage') !== false
        && strpos($documentSource, "'uploaded_file'") !== false,
    'Stored-file templates must be copied into a new uploaded document rather than reused by path.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, '($this->isFolder() && !$this->isNextcloudFolder())') !== false
        && strpos($documentSource, 'copyDocumentTemplateInOrganizationContext') !== false
        && strpos($documentSource, '$template->getDirectChildren()') !== false
        && strpos($documentSource, 'deleteDocumentTree') !== false,
    'A local folder template must reproduce its child tree and clean up a partial copy on failure.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'TYPE_ETHERPAD') !== false
        && strpos($documentSource, 'TYPE_ETHERCALC') !== false
        && strpos($etherpadSource, 'function omoEtherpadCopyDocumentPadContents') !== false
        && strpos($ethercalcSource, 'function omoEthercalcCopyDocumentSheetContents') !== false,
    'Collaborative-pad and spreadsheet templates must copy their content into independent resources.'
);
assertCalendarPvTemplateVisibility(
    strpos($projectDocumentPickerSource, "'templates' => \$templatePayload") !== false
        && strpos($projectSource, 'createDocumentFromTemplate') !== false
        && strpos($templateActionSource, "'project_id'") !== false
        && strpos($templateActionSource, 'ProjectDocument') !== false,
    'A project document picker must create a template copy and attach it to the current project.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentSource, 'function getTemplateGroupLabel') !== false
        && strpos($documentsSource, 'generic-menu-group-label') !== false
        && strpos($calendarCreateSource, '<optgroup label=') !== false
        && strpos($projectSource, 'templateGroups') !== false,
    'Document templates must remain grouped by their holon in document, meeting, and project pickers.'
);
assertCalendarPvTemplateVisibility(
    strpos($documentsSource, 'resolveDocumentTemplateIconUrl') !== false
        && strpos($documentsSource, 'omo-documents__template-menu-icon') !== false,
    'The document template menu must show the existing icon for each document type.'
);
assertCalendarPvTemplateVisibility(
    strpos($calendarCreateSource, 'data-omo-calendar-document-template-scope') !== false
        && strpos($calendarCreateSource, 'data-omo-calendar-context-path') !== false
        && strpos($calendarSource, 'function syncDocumentTemplateOptions') !== false
        && strpos($projectEditorSource, 'function syncDocumentTemplateOptions') !== false,
    'Calendar and project event forms must refresh template choices when their context changes.'
);

echo "calendar_pv_template_visibility_test: OK\n";
