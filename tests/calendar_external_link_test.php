<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
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

$items = mcpFixtures();
$before = $_SESSION;
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
    $request = ['session' => ['currentUser' => $uid, 'currentOrganization' => $oid],
        'get' => ['oid' => $oid, 'cid' => $hid], 'post' => []];
    $formRequest = $request; $formRequest['get']['editor_host'] = 'project';
    $html = calendarLinkRequest($formRequest);
    mcpCheck(str_contains($html, 'name="document_external_url"') && str_contains($html, '/common/calendar/document-fields.js'),
        'Meeting form must render and initialize the URL field');
    file_put_contents(dirname(__DIR__) . '/tmp/calendar-external-link-form.html', $html);
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
    echo "calendar_external_link_test: OK\n";
} finally {
    $documents = new \dbObject\ArrayDocument();
    $documents->load(['where' => [['field' => 'IDorganization', 'value' => $items['org']->getId()]]]);
    foreach ($documents as $document) { $document->delete(); }
    $events = new \dbObject\ArrayEvent(); $events->loadForOrganization((int)$items['org']->getId(), true);
    foreach ($events as $event) { $event->delete(); }
    mcpCleanup($items); $_SESSION = $before;
}
