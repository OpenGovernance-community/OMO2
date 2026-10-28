<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';

function extensionExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
// Capture only the real endpoint's permission guard; no writes or notifications.
function omoDocumentsPvEditorJsonResponse(array $payload, int $statusCode = 200): void
{
    throw new RuntimeException('response', $statusCode);
}
function omoDocumentsPvEditorActionT($key, array $replace = []) { return $key; }
function omoDocumentsPvEditorHasValidSessionToken($organizationId, $documentId, $actor, $token): bool
{
    return $token === 'valid-fixture-token';
}
$document = new class extends dbObject\Document {
    public function hasObjectPermission(string $permissionKey, int $userId, bool $useSessionCache = false): bool { return false; }
    public function getPvPermissionHolon(int $organizationId = 0) { return null; }
};
foreach (['id' => 123, 'documenttype' => 'pv', 'pvstage' => 'meeting', 'IDorganization' => 1,
    'IDholon' => 1, 'IDusercreation' => 11, 'IDuser_pv_editor' => 12, 'IDuser_pv_official_editor' => 11] as $field => $value) {
    $document->set($field, $value);
}
$organizationId = 1;
$documentId = 123;
$currentUserId = 12;
$editorToken = 'valid-fixture-token';
extensionExpect($document->canUserManagePvDocument(12), 'Active replacement can manage meeting content.');
extensionExpect(!$document->canUserManagePvStructure(1, 12), 'Replacement still cannot manage the PV structure.');
$source = file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/action.php');
$actionStart = strpos($source, "if (\$action === 'extend_associated_event') {");
$start = strpos($source, '    if (', $actionStart);
$end = strpos($source, '    $minutes =', $start);
extensionExpect($actionStart !== false && $start !== false && $end > $start, 'Extension guard boundary found.');
$guard = substr($source, $start, $end - $start);
$allowed = static function () use ($guard, $document, $organizationId, $documentId, &$currentUserId, &$editorToken): bool {
    try { eval($guard); return true; }
    catch (RuntimeException $exception) {
        if ($exception->getCode() !== 403) throw $exception;
        return false;
    }
};
extensionExpect($allowed(), 'Active replacement can extend the meeting after taking over.');
$editorToken = 'invalid';
extensionExpect(!$allowed(), 'A valid editor session remains required.');
$editorToken = 'valid-fixture-token';
$currentUserId = 11;
extensionExpect(!$allowed(), 'Former editor without another management permission cannot extend.');
$currentUserId = 12;
$document->set('pvstage', 'preparation');
extensionExpect($allowed(), 'Extension remains available during preparation.');
foreach (['review', 'validated'] as $stage) {
    $document->set('pvstage', $stage);
    extensionExpect(!$allowed(), 'Extension is refused in stage ' . $stage);
}
echo "pv_event_extension_permissions_test: OK\n";
