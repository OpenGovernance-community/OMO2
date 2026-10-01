<?php
declare(strict_types=1);

function assertPvEditorEventExtensionSync(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');

assertPvEditorEventExtensionSync(
    strpos($editorSource, '<?php if (!$isPublicParticipation && $hasAssociatedEvent): ?>') !== false,
    'The event extension menu must remain available for client-side stage updates.'
);
assertPvEditorEventExtensionSync(
    strpos($editorSource, 'function syncEventExtensionUi(documentPayload)') !== false,
    'The PV editor must synchronize the event extension menu with the document state.'
);
assertPvEditorEventExtensionSync(
    strpos($editorSource, "String(documentPayload.pvStage || '') === 'meeting'") !== false,
    'The event extension menu must be available only during the meeting stage.'
);
assertPvEditorEventExtensionSync(
    strpos($editorSource, 'syncEventExtensionUi(documentPayload);') !== false,
    'A document state refresh must also refresh the event extension menu.'
);

echo "pv_editor_event_extension_sync_test: OK\n";
