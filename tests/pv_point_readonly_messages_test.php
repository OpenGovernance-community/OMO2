<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/omo/api/documents/pv/helpers.php';

function assertPvPointReadonlyMessage(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$uiText = [
    'reviewReadonly' => 'review',
    'handledReadonly' => 'handled',
    'lockedOwnSession' => 'locked by own session',
    'lockedOther' => 'locked by {user}',
    'lockedOtherUnknown' => 'locked by another session',
    'takeoverYielded' => 'takeover requested',
    'notAuthorNotice' => 'not author',
];

assertPvPointReadonlyMessage(
    omoDocumentsPvEditorReadonlyNotice([], $uiText) === 'not author',
    'A point owned by another author must show the author-specific message.'
);
assertPvPointReadonlyMessage(
    omoDocumentsPvEditorReadonlyNotice([
        'lock' => ['isLockedByOther' => true, 'userLabel' => 'David D2'],
    ], $uiText) === 'locked by David D2',
    'A point locked by another user must name that user.'
);
assertPvPointReadonlyMessage(
    omoDocumentsPvEditorReadonlyNotice([
        'lock' => [
            'isLockedByOther' => true,
            'isOwnedByCurrentUser' => true,
            'userLabel' => 'David D2',
        ],
    ], $uiText) === 'locked by own session',
    'A point locked by the current account in another session must not look like another user owns the lock.'
);
assertPvPointReadonlyMessage(
    omoDocumentsPvEditorReadonlyNotice([
        'lock' => ['isLockedByOther' => true, 'userLabel' => ''],
    ], $uiText) === 'locked by another session',
    'A point locked by an unidentified session must use a neutral lock message.'
);
assertPvPointReadonlyMessage(
    omoDocumentsPvEditorReadonlyNotice([
        'takeover' => ['mustYield' => true],
        'lock' => ['isLockedByOther' => false],
    ], $uiText) === 'takeover requested',
    'A session asked to yield its lock must explain why editing was closed.'
);
assertPvPointReadonlyMessage(
    omoDocumentsPvEditorReadonlyNotice([
        'isHandled' => true,
        'takeover' => ['mustYield' => true],
        'lock' => ['isLockedByOther' => true, 'userLabel' => 'David D2'],
    ], $uiText) === 'handled',
    'The handled state must take precedence over a stale editing lock.'
);
assertPvPointReadonlyMessage(
    omoDocumentsPvEditorReadonlyNotice([
        'isReview' => true,
        'isHandled' => true,
    ], $uiText) === 'review',
    'The review stage message must take precedence over point-level states.'
);

$source = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/helpers.php');
assertPvPointReadonlyMessage(
    strpos($source, 'seul son auteur peut le modifier avant la réunion') === false,
    'The obsolete before-meeting restriction must not remain in the PV editor messages.'
);

echo "pv_point_readonly_messages_test: OK\n";
