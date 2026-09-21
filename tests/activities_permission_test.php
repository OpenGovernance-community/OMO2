<?php
declare(strict_types=1);

function activityPermissionAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$shared = (string)file_get_contents($root . '/omo/api/activities/shared.php');
$index = (string)file_get_contents($root . '/omo/api/activities/index.php');
$edit = (string)file_get_contents($root . '/omo/api/activities/edit.php');
$action = (string)file_get_contents($root . '/omo/api/activities/action.php');
$script = (string)file_get_contents($root . '/omo/api/activities/activities.js');
$migration = (string)file_get_contents($root . '/sql/2026-09-21-04-recurring-task-responsible.sql');

activityPermissionAssert(
    str_contains($shared, '$holon->isAllowed((string)$permissionKey, false, $userId)'),
    'Activity permissions must bypass the session cache.'
);
activityPermissionAssert(
    !str_contains($shared, <<<'PHP'
strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST'
PHP),
    'Activity permission behavior must not differ between GET and POST.'
);
foreach ([$index, $edit, $action] as $source) {
    activityPermissionAssert(
        str_contains($source, 'CAN_CREATE_RECURRING_TASK'),
        'The create permission must guard every activity creation entry point.'
    );
}
activityPermissionAssert(
    str_contains($script, 'window.commonNotify(String(message || \'\'), type || \'error\')'),
    'Activity errors must be relayed to the topbar notification.'
);
activityPermissionAssert(
    str_contains($migration, 'ADD COLUMN IF NOT EXISTS `IDuser_responsible`'),
    'The recurring task schema must include the responsible user column.'
);

echo "activities_permission_test: OK\n";
