<?php
declare(strict_types=1);

function processPermissionAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$shared = (string)file_get_contents($root . '/omo/api/checklist/shared.php');
$detail = (string)file_get_contents($root . '/omo/api/checklist/detail.php');
$action = (string)file_get_contents($root . '/omo/api/checklist/action.php');

processPermissionAssert(
    str_contains($shared, '$holon->isAllowed((string)$permissionKey, false, $currentUserId)'),
    'Process permissions must bypass the session cache.'
);
foreach ([$shared, $detail, $action] as $source) {
    processPermissionAssert(
        !str_contains($source, 'CAN_CREATE_CHECKLIST')
            && !str_contains($source, 'CAN_EDIT_CHECKLIST')
            && !str_contains($source, 'CAN_DELETE_CHECKLIST'),
        'Process code must not use legacy checklist permissions.'
    );
}
processPermissionAssert(
    str_contains($shared, 'CAN_CREATE_PROCESS')
        && str_contains($shared, 'CAN_EDIT_PROCESS')
        && str_contains($shared, 'CAN_DELETE_PROCESS')
        && str_contains($shared, 'CAN_CREATE_RECURRING_TASK'),
    'Process actions must use the renamed permissions.'
);

echo "process_permission_test: OK\n";
