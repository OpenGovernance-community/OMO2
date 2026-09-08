<?php
declare(strict_types=1);

function assertPvEditorAttendanceSync(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');

assertPvEditorAttendanceSync(
    strpos($editorSource, 'function canManageAttendance()') !== false,
    'The PV editor must expose a shared attendance editability check.'
);
assertPvEditorAttendanceSync(
    strpos($editorSource, 'renderAttendancePayload(currentAttendancePayload);') !== false,
    'A document state refresh must also refresh the attendance area.'
);
assertPvEditorAttendanceSync(
    strpos($editorSource, 'canManage: canManageAttendance() ? 1 : 0') !== false,
    'The attendance render signature must include its current editability.'
);
assertPvEditorAttendanceSync(
    strpos($editorSource, 'checkbox.disabled = !canManageAttendance();') !== false,
    'Attendance checkboxes must use the shared editability check.'
);

echo "pv_editor_attendance_sync_test: OK\n";
