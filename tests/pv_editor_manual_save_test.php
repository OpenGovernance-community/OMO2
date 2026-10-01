<?php
declare(strict_types=1);

function assertPvEditorManualSave(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');
$helpersSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/helpers.php');

assertPvEditorManualSave(
    strpos($editorSource, 'data-omo-pv-auto-save') === false
        && strpos($editorSource, 'schedulePointAutoSave') === false
        && strpos($editorSource, 'scheduleDocumentMetadataAutoSave') === false,
    'The PV editor must not schedule automatic saves.'
);
assertPvEditorManualSave(
    strpos($helpersSource, 'data-omo-pv-group-title-save') !== false
        && strpos($editorSource, "event.target.closest('[data-omo-pv-group-title-save]')") !== false,
    'Editable group titles must expose an explicit save action.'
);

echo "pv_editor_manual_save_test: OK\n";
