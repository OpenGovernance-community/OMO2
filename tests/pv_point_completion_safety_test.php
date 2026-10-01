<?php
declare(strict_types=1);

function assertPvPointCompletionSafety(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');
$actionSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/action.php');
$helpersSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/helpers.php');
$pointSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/documentpvpoint.class.php');

assertPvPointCompletionSafety(
    strpos($editorSource, 'const saveBeforeHandling = isHandled') !== false
        && strpos($editorSource, 'savePoint(pointId).then(function (wasSaved)') !== false,
    'Completing a dirty point must wait for its save request.'
);
assertPvPointCompletionSafety(
    strpos($helpersSource, "&& empty(\$pointData['lock']['isLockedByOther']);") !== false,
    'The handled control must be disabled when another session owns the lock.'
);

$toggleStart = strpos($actionSource, "if (\$action === 'toggle_handled')");
$toggleEnd = strpos($actionSource, "if (\$action === 'reorder_points')", $toggleStart);
$toggleSource = $toggleStart !== false && $toggleEnd !== false
    ? substr($actionSource, $toggleStart, $toggleEnd - $toggleStart)
    : '';
assertPvPointCompletionSafety(
    strpos($toggleSource, 'isLockedByOtherSession($currentUserId, $editorToken)') !== false
        && strpos($toggleSource, '], 423);') !== false,
    'The completion endpoint must reject a point locked by another session.'
);
assertPvPointCompletionSafety(
    strpos($pointSource, 'return true;') !== false
        && strpos($pointSource, "&& \$this->getEditingUserId() !== \$userId") === false,
    'A lock held by another browser session must remain exclusive, even for the same user.'
);

echo "pv_point_completion_safety_test: OK\n";
