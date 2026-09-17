<?php
declare(strict_types=1);

function assertPvPointLockTakeover(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$pointSource = (string)file_get_contents($root . '/class/dbobject/documentpvpoint.class.php');
$documentSource = (string)file_get_contents($root . '/class/dbobject/document.class.php');
$actionSource = (string)file_get_contents($root . '/omo/api/documents/pv/action.php');
$editorSource = (string)file_get_contents($root . '/omo/api/documents/pv/editor.php');
$helpersSource = (string)file_get_contents($root . '/omo/api/documents/pv/helpers.php');
$migrationSource = (string)file_get_contents($root . '/sql/2026-09-17-06-pv-point-lock-takeover.sql');

assertPvPointLockTakeover(
    str_starts_with($migrationSource, "-- @migration\n")
        && strpos($migrationSource, 'IDuser_edit_takeover_request') !== false
        && strpos($migrationSource, 'edit_takeover_request_token') !== false
        && strpos($migrationSource, 'edit_takeover_target_token') !== false
        && strpos($migrationSource, 'date_edit_takeover_request') !== false,
    'The takeover coordination fields must be delivered through a migration.'
);
assertPvPointLockTakeover(
    strpos($pointSource, 'EDIT_TAKEOVER_GRACE_SECONDS = 8') !== false
        && strpos($pointSource, 'takeOverEditLockAsPvEditor') !== false
        && strpos($pointSource, 'FOR UPDATE') !== false,
    'A takeover must wait briefly and serialize lock transfer in the database.'
);
assertPvPointLockTakeover(
    strpos($pointSource, 'saveForEditSession') !== false
        && strpos($pointSource, "'lockLost' => true") !== false
        && strpos($actionSource, 'saveForEditSession($currentUserId, $editorToken, $isPublicParticipation)') !== false,
    'Point saves must be fenced by the current session lock at write time.'
);
assertPvPointLockTakeover(
    strpos($pointSource, 'AND edit_lock_token = :lock_token') !== false
        && strpos($pointSource, 'dateedition < :expired_before') !== false,
    'Lock refresh and release queries must not overwrite a transferred lock.'
);
assertPvPointLockTakeover(
    strpos($actionSource, "if (!empty(\$lockResult['pending']))") !== false
        && strpos($actionSource, "'retryAfterMs'") !== false
        && strpos($editorSource, 'return new Promise(function (resolve)') !== false
        && strpos($editorSource, '}).then(requestTakeover);') !== false,
    'The requesting browser must wait and retry while the current editor saves.'
);
assertPvPointLockTakeover(
    strpos($documentSource, 'edit_takeover_request_token') !== false
        && strpos($helpersSource, "'mustYield' => !empty(\$takeoverData['mustYield'])") !== false
        && strpos($editorSource, 'processIncomingTakeoverRequests') !== false
        && strpos($editorSource, 'yieldPointLockForTakeover') !== false
        && strpos($editorSource, 'savePoint(pointId)') !== false,
    'Polling must signal the old browser to save and yield its lock.'
);
assertPvPointLockTakeover(
    strpos($editorSource, 'preserveDraftBeforeRemoteTakeover') !== false
        && strpos($editorSource, "'text/html': new window.Blob") !== false
        && strpos($editorSource, 'navigator.clipboard.writeText') !== false
        && strpos($editorSource, 'window.prompt(takeoverDraftCopyFailedMessage, draft.plainText)') !== false,
    'A draft rejected after forced takeover must be preserved in the clipboard or exposed for manual copy.'
);
assertPvPointLockTakeover(
    strpos($editorSource, 'const protectImmediatePointInput = function') !== false
        && strpos($editorSource, "root.addEventListener('beforeinput', protectImmediatePointInput, true)") !== false
        && strpos($editorSource, 'locallyEngagedPointIds.has') !== false,
    'The first trusted keystroke must protect the local draft even while the HTML editor is still mounting.'
);
assertPvPointLockTakeover(
    strpos($editorSource, 'const pendingTakeoverPointIds = new Set()') !== false
        && strpos($editorSource, 'function syncPendingTakeoverUi') !== false
        && strpos($editorSource, 'pendingTakeoverPointIds.add(pointId)') !== false
        && strpos($editorSource, 'button.textContent = isPending ? takeOverWaitingLabel : takeOverLockLabel') !== false,
    'The takeover button must keep its waiting state across remote point refreshes.'
);
assertPvPointLockTakeover(
    strpos($editorSource, 'const preMountEditorDrafts = new Map()') !== false
        && strpos($editorSource, 'preMountEditorFocusPointIds.add(pointId)') !== false
        && strpos($editorSource, 'const shouldRestoreInitialFocus = preMountEditorFocusPointIds.has(pointId)') !== false
        && strpos($editorSource, "typeof api.focusForInsertion === 'function'") !== false
        && strpos($editorSource, 'function restorePointFocusAfterLock') !== false
        && strpos($editorSource, 'return locallyEngagedPointIds.has(pointId)') !== false,
    'The asynchronous Summernote mount must preserve an initial click, focus, and any text entered before readiness.'
);

echo "pv_point_lock_takeover_test: OK\n";
