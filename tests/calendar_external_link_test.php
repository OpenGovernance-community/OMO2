<?php
declare(strict_types=1);
// Run as www-data in local Docker so HTTP requests can read the test session.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_FILES = $request['files'] ?? [];
    $_REQUEST = array_merge($_GET, $_POST);
    $user = new \dbObject\User(); $user->load((int)$_SESSION['currentUser']);
    $_SESSION['auth_security_version'] = (int)$user->get('security_version');
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/calendar/create.php';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    require dirname(__DIR__) . '/omo/api/calendar/create.php';
    exit;
}
function calendarLinkRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start endpoint');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Endpoint failed: ' . $errors . substr($output, -1500));
    return $output;
}

function calendarAttachmentHttp(array $request, string $filePath): array
{
    $_SESSION = $request['session'];
    $user = new \dbObject\User(); $user->load((int)$_SESSION['currentUser']);
    $_SESSION['auth_security_version'] = (int)$user->get('security_version');
    $post = $request['post']; $post['_csrf'] = commonCsrfToken();
    foreach ($post as $key => $value) {
        if (is_array($value)) {
            unset($post[$key]);
            foreach ($value as $index => $entry) { $post[$key . '[' . $index . ']'] = $entry; }
        }
    }
    $post['document_uploaded_file'] = new CURLFile($filePath, 'text/plain', 'meeting-notes.txt');
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost/omo/api/calendar/create.php?' . http_build_query($request['get']));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie, 'X-Requested-With: XMLHttpRequest']]);
    $body = curl_exec($curl);
    session_start();
    mcpCheck(is_string($body), 'HTTP upload failed');
    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
}

$items = mcpFixtures();
$before = $_SESSION;
$storageProcess = null;
$storageDirectory = sys_get_temp_dir() . '/omo-calendar-upload-' . bin2hex(random_bytes(8));
mkdir($storageDirectory, 0700);
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['root']->getId();
    $items['root']->set('IDholon_org', $hid); $items['root']->save();
    $items['assignment'] = mcpFixture(\dbObject\UserHolon::class,
        ['IDuser' => $uid, 'IDholon' => $hid, 'is_membership' => 1, 'active' => 1]);
    foreach (['CAN_CREATE_EVENT', 'CAN_EDIT_EVENT', 'CAN_CREATE_DOCUMENT'] as $key) {
        $items[$key] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid,
            'IDpermission' => \dbObject\Permission::findByKey($key)->getId(), 'member_type' => 'member', 'range' => 'self']);
    }
    foreach (['calendar', 'documents'] as $hash) {
        $app = new \dbObject\Application(); $app->load([['hash', $hash]]);
        $items['app_' . $hash] = mcpFixture(\dbObject\OrganizationApplication::class,
            ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    mcpCheck(is_resource($socket), 'Local storage port');
    $address = stream_socket_get_name($socket, false); fclose($socket);
    $secret = bin2hex(random_bytes(24));
    $storageProcess = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/mcp_storage_fixture.php'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null,
        ['MCP_TEST_STORAGE_SECRET' => $secret, 'MCP_TEST_STORAGE_DIR' => $storageDirectory]);
    mcpCheck(is_resource($storageProcess), 'Local storage fixture starts');
    $port = (int)substr($address, strrpos($address, ':') + 1); $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
        if ($probe) { fclose($probe); $ready = true; break; }
        usleep(100000);
    }
    mcpCheck($ready, 'Local storage fixture is ready');
    $items['app_documents']->setParametersArray(['storage' => ['type' => 'nextcloud', 'baseUrl' => 'http://' . $address,
        'username' => 'fixture', 'appPassword' => $secret, 'folder' => '']]);
    mcpCheck(!empty($items['app_documents']->save()['status']), 'Configure isolated document storage');
    $request = ['session' => ['currentUser' => $uid, 'currentOrganization' => $oid],
        'get' => ['oid' => $oid, 'cid' => $hid], 'post' => []];
    $formRequest = $request; $formRequest['get']['editor_host'] = 'project';
    $html = calendarLinkRequest($formRequest);
    mcpCheck(str_contains($html, 'name="document_external_url"') && str_contains($html, '/common/calendar/document-fields.js'),
        'Meeting form must render and initialize the URL field');
    file_put_contents(dirname(__DIR__) . '/tmp/calendar-external-link-form.html', $html);
    file_put_contents(dirname(__DIR__) . '/tmp/calendar-attachment-form.html', calendarLinkRequest($request));
    $request['post'] = ['title' => 'External link meeting', 'status' => 'confirmed', 'IDholon' => $hid,
        'start_at' => '2031-05-05T09:00', 'end_at' => '2031-05-05T10:00', 'document_type' => 'external_link',
        'document_title' => 'Reference link', 'invitation_holon_ids' => [$hid]];
    foreach (['', 'not-a-url', 'javascript:alert(1)', 'ftp://example.invalid/file'] as $invalidUrl) {
        $request['post']['document_external_url'] = $invalidUrl;
        $result = json_decode(calendarLinkRequest($request), true, 512, JSON_THROW_ON_ERROR);
        mcpCheck(empty($result['status']) && str_contains($result['message'], 'https://'), 'Reject missing/unsafe URL with useful feedback');
    }
    $events = new \dbObject\ArrayEvent(); $events->loadForOrganization($oid);
    mcpCheck(count($events) === 0, 'Invalid links must not create partial meetings');
    $url = 'https://example.invalid/meeting?first=1&second=two#reference';
    $request['post']['document_external_url'] = $url;
    $result = json_decode(calendarLinkRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($result['status']) && (int)$result['documentId'] > 0, 'Create event and external link: ' . json_encode($result));
    $document = new \dbObject\Document(); $document->load((int)$result['documentId'], true);
    mcpCheck($document->getExternalUrl() === $url && $document->get('title') === 'Reference link'
        && (int)$document->get('IDevent') === (int)$result['eventId'], 'Save exact URL, name and event association');
    $eventId = (int)$result['eventId'];
    $duplicate = $request; $duplicate['get']['duplicate_id'] = $eventId; $duplicate['post'] = [];
    $duplicateHtml = calendarLinkRequest($duplicate);
    mcpCheck(str_contains($duplicateHtml, htmlspecialchars($url, ENT_QUOTES, 'UTF-8')), 'Duplicating an event must prefill its link URL');

    // Adding a link while editing an existing event must also save it.
    $event = mcpFixture(\dbObject\Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Existing meeting', 'status' => 'confirmed', 'active' => 1,
        'start_at' => '2031-05-06 09:00:00', 'end_at' => '2031-05-06 10:00:00']);
    $edit = $request; $edit['get']['id'] = $event->getId(); $edit['post']['event_id'] = $event->getId();
    $edit['post']['start_at'] = '2031-05-06T09:00'; $edit['post']['end_at'] = '2031-05-06T10:00';
    $result = json_decode(calendarLinkRequest($edit), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($result['status']) && (int)$result['eventId'] === (int)$event->getId(), 'Add link to existing event: ' . json_encode($result));
    $document->load((int)$result['documentId'], true);
    mcpCheck($document->getExternalUrl() === $url, 'Existing meeting link URL persists');

    $templateUrl = 'https://example.invalid/template';
    $template = mcpFixture(\dbObject\Document::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Link template', 'documenttype' => 'external_link', 'externalurl' => $templateUrl, 'is_template' => 1, 'active' => 1]);
    $template->saveVisibilityRule('organization');
    $fromTemplate = $request; unset($fromTemplate['post']['document_external_url']);
    $fromTemplate['post']['document_template_id'] = $template->getId();
    $fromTemplate['post']['start_at'] = '2031-05-07T09:00'; $fromTemplate['post']['end_at'] = '2031-05-07T10:00';
    $result = json_decode(calendarLinkRequest($fromTemplate), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($result['status']), 'Template provides the URL: ' . json_encode($result));
    $document->load((int)$result['documentId'], true);
    mcpCheck($document->getExternalUrl() === $templateUrl, 'Copy template link without requiring a second URL');

    $upload = $request; $upload['post']['document_type'] = 'uploaded_file';
    $upload['post']['start_at'] = '2031-05-08T09:00'; $upload['post']['end_at'] = '2031-05-08T10:00';
    $result = json_decode(calendarLinkRequest($upload), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(empty($result['status']) && str_contains($result['message'], 'fichier'), 'Missing file rejected');
    foreach ([UPLOAD_ERR_PARTIAL, UPLOAD_ERR_INI_SIZE] as $uploadError) {
        $failed = $upload; $failed['files'] = ['document_uploaded_file' => ['error' => $uploadError]];
        $result = json_decode(calendarLinkRequest($failed), true, 512, JSON_THROW_ON_ERROR);
        mcpCheck(empty($result['status']) && str_contains($result['message'], 'transfert'), 'Failed upload rejected');
    }
    $content = "Meeting attachment\nOriginal file bytes.\n";
    $uploadPath = $storageDirectory . '/upload.txt'; file_put_contents($uploadPath, $content);
    $result = calendarAttachmentHttp($upload, $uploadPath);
    mcpCheck(!empty($result['status']) && (int)$result['documentId'] > 0, 'Real multipart upload saves: ' . json_encode($result));
    $document->load((int)$result['documentId'], true);
    mcpCheck($document->hasStoredFile() && $document->get('storedfilename') === 'meeting-notes.txt'
        && (int)$document->get('storedfilesize') === strlen($content)
        && (int)$document->get('IDevent') === (int)$result['eventId'], 'File metadata and event association persist');
    $storedFiles = glob($storageDirectory . '/*.bin');
    mcpCheck(count($storedFiles) === 1 && file_get_contents($storedFiles[0]) === $content, 'Uploaded bytes reach document storage');
    $events->loadForOrganization($oid); $eventCount = count($events);
    $documents = new \dbObject\ArrayDocument();
    $documents->load(['where' => [['field' => 'IDorganization', 'value' => $oid]]]); $documentCount = count($documents);
    file_put_contents($storageDirectory . '/fail-put', '1');
    $upload['post']['start_at'] = '2031-05-09T09:00'; $upload['post']['end_at'] = '2031-05-09T10:00';
    $result = calendarAttachmentHttp($upload, $uploadPath);
    mcpCheck(empty($result['status']), 'Storage failure is returned to the editor');
    $events->loadForOrganization($oid);
    $documents = new \dbObject\ArrayDocument();
    $documents->load(['where' => [['field' => 'IDorganization', 'value' => $oid]]]);
    mcpCheck(count($events) === $eventCount && count($documents) === $documentCount,
        'Failed storage leaves no partial event or document: ' . json_encode([$eventCount, count($events), $documentCount, count($documents), $result]));
    echo "calendar_external_link_test: OK (links and file uploads)\n";
} finally {
    $documents = new \dbObject\ArrayDocument();
    $documents->load(['where' => [['field' => 'IDorganization', 'value' => $items['org']->getId()]]]);
    foreach ($documents as $document) { $document->delete(); }
    $events = new \dbObject\ArrayEvent(); $events->loadForOrganization((int)$items['org']->getId(), true);
    foreach ($events as $event) { $event->delete(); }
    mcpCleanup($items); $_SESSION = $before;
    if (is_resource($storageProcess)) { proc_terminate($storageProcess); proc_close($storageProcess); }
    foreach (glob($storageDirectory . '/*') as $file) { unlink($file); }
    rmdir($storageDirectory);
}
