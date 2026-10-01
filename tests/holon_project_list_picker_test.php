<?php
declare(strict_types=1);

function assertHolonProjectListPicker(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/holons/create.php');
$editorCss = (string)file_get_contents(dirname(__DIR__) . '/omo/api/holons/editor.css');
$organizationSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/organization.class.php');
$pvEditorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');
$sharedPickerSource = (string)file_get_contents(dirname(__DIR__) . '/common/project-picker/project-picker.js');
$topbarSource = (string)file_get_contents(dirname(__DIR__) . '/common/topbar.php');
$topbarJsSource = (string)file_get_contents(dirname(__DIR__) . '/common/assets/topbar.js');

assertHolonProjectListPicker(
    strpos($editorSource, 'data-project-selected-list') !== false
        && strpos($editorSource, 'data-project-picker-open') !== false
        && strpos($editorSource, 'data-project-remove') !== false,
    'Project list properties must show only selected projects and an add action.'
);
assertHolonProjectListPicker(
    strpos($editorSource, 'window.commonMountProjectPicker') !== false
        && strpos($editorSource, 'data-project-holon-scope') !== false
        && strpos($editorSource, 'data-project-choice-select') !== false
        && strpos($organizationSource, "'holonId' => \$holon ? (int)\$holon->getId() : 0") !== false,
    'The project selector must filter projects through the shared holon navigation.'
);
assertHolonProjectListPicker(
    strpos($sharedPickerSource, 'window.commonMountProjectPicker') !== false
        && strpos($sharedPickerSource, 'window.omoMountHolonScopePicker') !== false
        && strpos($pvEditorSource, 'window.commonMountProjectPicker') !== false
        && strpos($topbarSource, '/common/project-picker/project-picker.js') !== false,
    'The PV Summernote editor and holon properties must use the same shared project picker.'
);
assertHolonProjectListPicker(
    strpos($editorSource, "formatId === 7") !== false
        && strpos($editorSource, 'renderProjectPicker(property, selectedIds') !== false
        && strpos($editorSource, 'readProjectPickerSelectedIds(projectPicker)') !== false
        && strpos($editorSource, 'catalog.slice().sort(compareProjectsByTitle)') !== false
        && strpos($editorSource, "localeCompare(rightTitle, 'fr'") !== false,
    'Composite HTML and list properties must retain project selections and sort them alphabetically.'
);
assertHolonProjectListPicker(
    strpos($editorCss, '.omo-holon-create__project-picker-layer') === false
        && strpos($editorCss, '.omo-holon-create__project-selected-row') !== false
        && strpos($editorSource, 'window.commonTopbarPushModal') !== false
        && strpos($editorSource, 'showModes: true') !== false
        && strpos($topbarJsSource, 'window.commonTopbarPushModal = pushModal') !== false
        && strpos($topbarJsSource, 'window.commonTopbarPopModal = popModal') !== false
        && strpos($topbarJsSource, 'currentScrollTop = body.scrollTop') !== false
        && strpos($topbarJsSource, 'body.scrollTop = Number(state.scrollTop) || 0') !== false,
    'The compact project list must open the standard topbar modal, expose the full range selector, and restore the editor scroll position.'
);

echo "holon_project_list_picker_test: OK\n";
