<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/documentpvpoint.class.php';

function assertPvLockHeartbeatBatch(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertPvLockHeartbeatBatch(
    method_exists(\dbObject\DocumentPvPoint::class, 'refreshEditLocksForSession'),
    'The PV point object must expose the batch heartbeat operation.'
);
assertPvLockHeartbeatBatch(
    method_exists(\dbObject\DocumentPvPoint::class, 'releaseEditLocksForSession'),
    'The PV point object must expose the batch release operation.'
);
assertPvLockHeartbeatBatch(
    \dbObject\DocumentPvPoint::refreshEditLocksForSession(1, 1, 1, 'token', []) === false,
    'An empty heartbeat must not execute a database update.'
);

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');
$heartbeatStart = strpos($editorSource, 'function startLockHeartbeat()');
$heartbeatEnd = strpos($editorSource, "window.addEventListener('beforeunload'", $heartbeatStart);
assertPvLockHeartbeatBatch($heartbeatStart !== false && $heartbeatEnd !== false, 'The heartbeat implementation must be present.');
$heartbeatSource = substr($editorSource, $heartbeatStart, $heartbeatEnd - $heartbeatStart);
assertPvLockHeartbeatBatch(
    substr_count($heartbeatSource, "postPointAction('heartbeat_locks'") === 1,
    'One heartbeat request must refresh all active point locks.'
);
assertPvLockHeartbeatBatch(
    strpos($heartbeatSource, "postPointAction('lock_point'") === false,
    'The heartbeat must not rebuild each point through the acquisition endpoint.'
);

$releaseStart = strpos($editorSource, 'function releaseActiveLocksWithBeacon()');
$releaseEnd = strpos($editorSource, 'function confirmCloseWithUnsavedChanges()', $releaseStart);
assertPvLockHeartbeatBatch($releaseStart !== false && $releaseEnd !== false, 'The batch release implementation must be present.');
$releaseSource = substr($editorSource, $releaseStart, $releaseEnd - $releaseStart);
assertPvLockHeartbeatBatch(
    substr_count($releaseSource, "formData.append('action', 'release_locks')") === 1,
    'Closing the editor must release all locks in one request.'
);

$actionSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/action.php');
$batchActionPosition = strpos($actionSource, "if (\$action === 'heartbeat_locks' || \$action === 'release_locks')");
$fullDocumentLoadPosition = strpos($actionSource, '$document = omoDocumentsPvEditorLoadDocumentOrFail');
assertPvLockHeartbeatBatch(
    $batchActionPosition !== false
        && $fullDocumentLoadPosition !== false
        && $batchActionPosition < $fullDocumentLoadPosition,
    'Heartbeat and release requests must bypass the full PV document payload loading path.'
);

echo "pv_lock_heartbeat_batch_test: OK\n";

