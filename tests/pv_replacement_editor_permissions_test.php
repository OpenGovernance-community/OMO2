<?php
declare(strict_types=1);

function assertPvReplacementEditorPermissions(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$documentSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/document.class.php');
$methodStart = strpos($documentSource, 'public function canUserManagePvDocument(int $userId): bool');
$methodEnd = strpos($documentSource, 'public function isVisibleInDocumentsListForUser', $methodStart);
$methodSource = $methodStart !== false && $methodEnd !== false
    ? substr($documentSource, $methodStart, $methodEnd - $methodStart)
    : '';

$activeEditorCheck = strpos($methodSource, 'if ($this->isPvEditor($userId))');
$genericPermissionCheck = strpos($methodSource, "if (!\$this->hasObjectPermission('CAN_EDIT_DOCUMENT', \$userId))");
assertPvReplacementEditorPermissions(
    $activeEditorCheck !== false
        && $genericPermissionCheck !== false
        && $activeEditorCheck < $genericPermissionCheck,
    'The active replacement editor must be authorized before the generic document permission check.'
);

echo "pv_replacement_editor_permissions_test: OK\n";
