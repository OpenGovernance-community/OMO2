<?php
declare(strict_types=1);

function assertPvProjectSelectionCreate(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');

assertPvProjectSelectionCreate(
    strpos($editorSource, 'function getPvProjectCreationContext(field, pointCard)') !== false,
    'The PV editor must derive project creation context from the current point.'
);
assertPvProjectSelectionCreate(
    strpos($editorSource, "const title = String(field.getSelectedText() || '').trim();") !== false,
    'The selected PV point text must be used as the project title.'
);
assertPvProjectSelectionCreate(
    strpos($editorSource, "data-omo-pv-point-author=\"' + pointId + '\"") !== false
        && strpos($editorSource, "data-omo-pv-point-concerned-holon=\"' + pointId + '\"") !== false,
    'The selected-text project creation must use the point author and concerned role.'
);
assertPvProjectSelectionCreate(
    strpos($editorSource, "openPvProjectEmbedPicker(api, null, getPvProjectCreationContext(api, card));") !== false,
    'The Project toolbar button must pass the selected text context to the project picker.'
);
assertPvProjectSelectionCreate(
    strpos($editorSource, "const startsInCreationMode = creationTitle !== '';") !== false
        && strpos($editorSource, "value=\"' + escapeDocumentEmbedHtml(creationTitle) + '\"") !== false
        && strpos($editorSource, 'field.createTemporaryCursorMarker({preserveSelection: startsInCreationMode})') !== false,
    'A selected PV point text must open the new project form with its title filled.'
);
assertPvProjectSelectionCreate(
    strpos($editorSource, "const projectCreateContext = Object.assign({}, projectEmbedCreateContext);") !== false
        && strpos($editorSource, "formData.set('cid', String(projectCreateContext.holonId || 0));") !== false
        && strpos($editorSource, "formData.set('IDuser', String(projectCreateContext.responsibleId || 0));") !== false,
    'The project save request must retain the selected point role and person.'
);

$htmlFieldSource = (string)file_get_contents(dirname(__DIR__) . '/omo/assets/js/simple-html-field.js');
assertPvProjectSelectionCreate(
    strpos($htmlFieldSource, 'function createTemporaryCursorMarker(options)') !== false
        && strpos($htmlFieldSource, 'const preservesSelectedContent = preserveSelection && !range.collapsed;') !== false
        && strpos($htmlFieldSource, 'markerNode.__omoSelectionEndMarker = endMarkerNode;') !== false,
    'The HTML editor must keep selected content until an insertion is confirmed.'
);
assertPvProjectSelectionCreate(
    strpos($htmlFieldSource, 'selectedRange.deleteContents();') !== false
        && strpos($htmlFieldSource, 'selectionEndMarker.remove();') !== false,
    'The HTML editor must replace preserved text only when its marker is replaced.'
);
assertPvProjectSelectionCreate(
    strpos($htmlFieldSource, 'markerNode.parentNode === editable') !== false
        && strpos($htmlFieldSource, 'markerParagraph = document.createElement(\'p\');') !== false,
    'The HTML editor must wrap an insertion marker that is directly inside raw editor text.'
);

echo "pv_project_selection_create_test: OK\n";
