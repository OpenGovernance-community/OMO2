<?php
declare(strict_types=1);

// Real dbObjects and local HTTP storage; SQL fixtures roll back, remote data stays in a temporary directory.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/omo/api/documents/params/shared.php';

use dbObject\{DbObject, Organization, OrganizationApplication, User, UserOrganization, Document};

function historyCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function historySave($object): void
{
    $result = $object->save();
    historyCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}
function historyRender(string $page, array $query): string
{
    global $lang, $sourceLang;
    $_GET = $query;
    $_REQUEST = $query;
    $_SERVER['REQUEST_URI'] = '/omo/api/documents/' . $page;
    ob_start();
    require dirname(__DIR__) . '/omo/api/documents/' . $page;
    return (string)ob_get_clean();
}

$directory = sys_get_temp_dir() . '/omo-history-test-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
historyCheck(is_resource($socket), 'Reserve fixture port');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$secret = bin2hex(random_bytes(16));
$null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
$process = null;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $process = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/webdav_storage_fixture.php'],
        [0 => ['file', $null, 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $directory . '/server.log', 'w']], $pipes, null,
        array_merge(getenv(), ['WEBDAV_TEST_SECRET' => $secret, 'WEBDAV_TEST_DIR' => $directory]));
    historyCheck(is_resource($process), 'Start local storage fixture');
    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($probe) { fclose($probe); $ready = true; break; }
        usleep(100000);
    }
    historyCheck($ready, 'Storage fixture ready');
    $organization = new Organization();
    $organization->set('name', 'History integration test');
    $organization->set('shortname', 'history-' . bin2hex(random_bytes(6)));
    historySave($organization);
    $organizationId = (int)$organization->getId();
    $link = OrganizationApplication::ensureByOrganizationAndDirectory($organizationId, 'documents');
    historyCheck($link instanceof OrganizationApplication, 'Documents app available');
    $link->set('active', true);
    $link->setParametersArray(['storage' => [
        'type' => 'webdav', 'baseUrl' => 'http://' . $address . '/dav/Compte%20test',
        'username' => 'fixture', 'appPassword' => $secret, 'folder' => 'Documents partagés',
    ]]);
    historySave($link);
    $owner = new User();
    $owner->set('email', 'history-' . bin2hex(random_bytes(6)) . '@example.invalid');
    $owner->set('active', true);
    historySave($owner);
    $membership = new UserOrganization();
    $membership->set('IDorganization', $organizationId);
    $membership->set('IDuser', $owner->getId());
    $membership->set('active', true);
    historySave($membership);
    $ownerId = (int)$owner->getId();
    $_SERVER['HTTP_HOST'] = $organization->get('shortname') . '.localtest.me';
    $_SESSION['currentUser'] = $ownerId;
    $_SESSION['currentOrganization'] = $organizationId;
    $document = new Document();
    foreach (['title' => 'Conditions générales', 'documenttype' => Document::TYPE_UPLOADED_FILE,
        'IDorganization' => $organizationId, 'IDuser' => $ownerId, 'active' => 1, 'history_enabled' => 1] as $field => $value) {
        $document->set($field, $value);
    }
    historySave($document);
    historyCheck(!empty($document->saveVisibilityRule('organization')['status']), 'Read scope');
    historyCheck(!empty($document->saveEditVisibilityRule('self')['status']), 'Edit scope');
    $file = $directory . '/upload.txt';
    $upload = static function (string $contents) use ($file): array {
        file_put_contents($file, $contents);
        return ['error' => UPLOAD_ERR_OK, 'tmp_name' => $file, 'name' => 'conditions.odt',
            'type' => 'application/vnd.oasis.opendocument.text', 'size' => strlen($contents)];
    };
    $initial = $organization->uploadDocumentFileToStorage((int)$document->getId(), $upload('version one'));
    historyCheck(!empty($initial['status']), 'Initial upload');
    foreach (['storedfilepath' => $initial['relativePath'], 'storedfilename' => 'conditions.odt',
        'storedfilemime' => 'application/vnd.oasis.opendocument.text', 'storedfilesize' => 11] as $field => $value) {
        $document->set($field, $value);
    }
    historySave($document);
    $read = static fn(string $path): string => (string)($organization->downloadDocumentFileFromStorage($path)['body'] ?? '');
    $createdDocument = new Document();
    $creation = $createdDocument->createInOrganizationContext($organizationId, null, $ownerId, [
        'title' => 'New file with history', 'document_type' => Document::TYPE_UPLOADED_FILE,
        'uploaded_file' => $upload('created with history'), 'history_enabled' => 1,
        'visibility_type' => 'organization', 'edit_visibility_type' => 'self',
    ]);
    historyCheck(!empty($creation['status']) && $createdDocument->isVersionHistoryEnabled(), 'History can be enabled at creation: ' . json_encode($creation));
    historyCheck(count($createdDocument->getArchivedVersions()) === 0 && $createdDocument->delete(), 'Initial upload is not a previous version');
    $change = static fn(array $values): array => $document->updateInOrganizationContext($organizationId, $ownerId,
        array_merge(['title' => 'Conditions générales', 'visibility_type' => 'organization', 'edit_visibility_type' => 'self'], $values));
    historyCheck($document->canOpenWithCollabora(), 'Office files support the same history capability');
    $manual = $document->archiveCurrentVersion($ownerId);
    historyCheck(!empty($manual['status']), 'Manual snapshot: ' . json_encode($manual));
    $manualVersion = $manual['version'];
    $manualPath = (string)$manualVersion->get('storedfilepath');
    historyCheck($manualPath !== $initial['relativePath'] && $read($manualPath) === 'version one', 'Snapshot is an independent exact copy');
    historyCheck($manualVersion->get('storedfilename') === 'conditions.odt', 'Snapshot preserves original download name');
    historyCheck(empty($document->archiveCurrentVersion(0)['status']), 'Anonymous archive rejected');
    $otherUser = new User();
    $otherUser->set('email', 'history-outsider-' . bin2hex(random_bytes(6)) . '@example.invalid');
    $otherUser->set('active', true);
    historySave($otherUser);
    historyCheck(empty($document->archiveCurrentVersion((int)$otherUser->getId())['status']), 'Outsiders cannot archive a document');
    $update = $change(['uploaded_file' => $upload('version two'), 'history_enabled' => 1]);
    historyCheck(!empty($update['status']), 'Same-name replacement succeeds: ' . json_encode($update));
    $versions = $document->getArchivedVersions();
    historyCheck(count($versions) === 2, 'Replacement archives previous version');
    historyCheck($read((string)$versions[0]->get('storedfilepath')) === 'version one', 'Automatic snapshot contains old bytes');
    historyCheck($read((string)$document->get('storedfilepath')) === 'version two', 'Current file contains new bytes');
    $collabora = $document->saveCollaboraFileContents($organization, $ownerId, 'collaborative edit', 'application/vnd.oasis.opendocument.text');
    historyCheck(!empty($collabora['status']), 'Collabora saves succeed');
    historyCheck(count($document->getArchivedVersions()) === 2 && $read($manualPath) === 'version one', 'Autosave leaves snapshots immutable and does not add versions');
    $manualTwo = $document->archiveCurrentVersion($ownerId);
    historyCheck(!empty($manualTwo['status']) && $read((string)$manualTwo['version']->get('storedfilepath')) === 'collaborative edit', 'Manual archive captures latest collaborative bytes');
    $query = ['id' => $document->getId(), 'oid' => $organizationId];
    $detailHtml = historyRender('detail.php', $query);
    historyCheck(str_contains($detailHtml, 'data-omo-document-archive-id') && substr_count($detailHtml, '&amp;version_id=') === 3, 'Detail renders archive action and all protected version links');
    $editorHtml = historyRender('create.php', $query);
    historyCheck((bool)preg_match('/name="history_enabled"[^>]* checked/', $editorHtml), 'Editor renders enabled history checkbox');
    $renderDirectory = getenv('DOCUMENT_HISTORY_RENDER_DIR');
    if ($renderDirectory && is_dir($renderDirectory)) {
        file_put_contents($renderDirectory . '/document-history-detail.html', $detailHtml);
        file_put_contents($renderDirectory . '/document-history-editor.html', $editorHtml);
    }
    file_put_contents($directory . '/fail-put', '1');
    $currentPath = (string)$document->get('storedfilepath');
    $failed = $change(['uploaded_file' => $upload('must not replace'), 'history_enabled' => 1]);
    historyCheck(empty($failed['status']) && $read($currentPath) === 'collaborative edit', 'Failed archive prevents replacement');
    historyCheck(count($document->getArchivedVersions()) === 3, 'Failed archive creates no version');
    unlink($directory . '/fail-put');
    $disabled = $change(['history_enabled' => 0]);
    historyCheck(!empty($disabled['status']) && !$document->isVersionHistoryEnabled(), 'Disable history');
    historyCheck(count($document->getArchivedVersions()) === 3 && $read($manualPath) === 'version one', 'Disabling keeps archived versions');
    historyCheck(empty($document->archiveCurrentVersion($ownerId)['status']), 'Disabled history refuses manual archive');
    $replaceDisabled = $change(['uploaded_file' => $upload('without history')]);
    historyCheck(!empty($replaceDisabled['status']) && count($document->getArchivedVersions()) === 3, 'Disabled replacements do not add versions');
    $removed = $change(['remove_uploaded_file' => 1, 'history_enabled' => 1]);
    historyCheck(!empty($removed['status']) && !$document->hasStoredFile(), 'Remove current file');
    historyCheck(count($document->getArchivedVersions()) === 4 && $read($manualPath) === 'version one', 'Removal preserves history without a current file');
    $other = new Document();
    $other->setId((int)$document->getId() + 1);
    historyCheck(!$manualVersion->belongsToDocument($other) && $manualVersion->belongsToDocument($document), 'Version downloads bind to their parent document');
    historyCheck(Document::organizationHasStoredDocumentFiles($organizationId), 'Storage change detects archives even without a current file');
    historyCheck($document->delete(), 'Permanent document deletion cleans up versions');
    historyCheck(count($document->getArchivedVersions()) === 0 && empty($organization->downloadDocumentFileFromStorage($manualPath)['status']), 'Deleted document leaves no downloadable snapshots');
    echo "document_history_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach (glob($directory . '/*') as $temporaryFile) unlink($temporaryFile);
    rmdir($directory);
}
