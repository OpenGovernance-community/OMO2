<?php
declare(strict_types=1);

function assertPvEventExtension(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');
assertPvEventExtension(
    strpos($editorSource, 'data-omo-pv-event-extension-minutes') !== false,
    'The PV editor must expose meeting extension menu choices.'
);
assertPvEventExtension(
    strpos($editorSource, "postPointAction('extend_associated_event'") !== false,
    'The PV editor must save a selected meeting extension through its action endpoint.'
);
assertPvEventExtension(
    strpos($editorSource, 'applyAssociatedEventSchedule') !== false,
    'The PV editor must refresh its timer from an updated associated event schedule.'
);

$actionSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/action.php');
assertPvEventExtension(
    strpos($actionSource, "if (\$action === 'extend_associated_event')") !== false,
    'The PV action endpoint must handle meeting extensions.'
);
assertPvEventExtension(
    strpos($actionSource, 'in_array($minutes, [5, 10, 15, 30], true)') !== false,
    'The PV action endpoint must only accept the supported extension durations.'
);
assertPvEventExtension(
    strpos($actionSource, '$document->canUserManagePvDocument($currentUserId)') !== false,
    'The PV action endpoint must verify that the current person manages the meeting PV.'
);
assertPvEventExtension(
    strpos($actionSource, "notificationCenterDispatchEventChange(\$event, 'schedule', \$currentUserId)") !== false,
    'Meeting extensions must use the standard schedule-change notification flow.'
);

$eventSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/event.class.php');
assertPvEventExtension(
    strpos($eventSource, "\$document->set('datemodification', new \\DateTimeImmutable());") !== false,
    'Saving an updated event schedule must refresh the associated PV revision.'
);

echo "pv_event_extension_test: OK\n";
