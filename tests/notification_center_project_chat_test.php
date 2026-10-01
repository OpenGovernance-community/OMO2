<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';
require_once dirname(__DIR__) . '/class/dbobject/project.class.php';
require_once dirname(__DIR__) . '/common/notification_center.php';

function assertNotificationCenterProjectChat($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$eventCatalog = notificationCenterEventCatalog();
assertNotificationCenterProjectChat(
    array_key_exists('project_chat_owner', $eventCatalog),
    'The project owner chat event must be available in notification preferences.'
);
assertNotificationCenterProjectChat(
    array_key_exists('project_chat_participant', $eventCatalog),
    'The project participant chat event must be available in notification preferences.'
);

$groupCatalog = notificationCenterEventGroupCatalog();
$projectEventKeys = $groupCatalog['projects']['eventKeys'] ?? [];
assertNotificationCenterProjectChat(
    in_array('project_chat_owner', $projectEventKeys, true)
        && in_array('project_chat_participant', $projectEventKeys, true),
    'The project chat events must be listed in the Projects notification group.'
);
assertNotificationCenterProjectChat(
    notificationCenterBuildProjectUrl(12, 34) === '/omo/o/12#projects-d34',
    'A project chat notification must open its project detail.'
);

echo "Notification center project chat tests passed.\n";
