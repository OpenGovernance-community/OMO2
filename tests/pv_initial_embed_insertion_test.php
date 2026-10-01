<?php
declare(strict_types=1);

function assertPvInitialEmbedInsertion(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');

assertPvInitialEmbedInsertion(
    strpos($editorSource, 'function insertPvEmbedIntoField(field, targetNode, markerNode, embedHtml)') !== false,
    'The PV editor must centralize application block insertion.'
);
assertPvInitialEmbedInsertion(
    strpos($editorSource, 'field.replaceMarkerWithHtml(markerNode || null, embedHtml)') !== false,
    'Application block insertion must fall back to the current cursor when the initial marker is missing.'
);
assertPvInitialEmbedInsertion(
    substr_count($editorSource, 'insertPvEmbedIntoField(field, targetNode, marker,') >= 6,
    'Every PV application block picker must use the safe insertion helper.'
);
assertPvInitialEmbedInsertion(
    strpos($editorSource, "else if (embedHtml !== '' && marker)") === false,
    'PV application block insertion must not depend on a surviving marker.'
);

echo "pv_initial_embed_insertion_test: OK\n";
