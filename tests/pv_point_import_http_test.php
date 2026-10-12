<?php
declare(strict_types=1);

// Run as the web server user to share the session with HTTP requests.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{Document, DocumentPvPoint};

function pvImportHttpRequest(string $path, array $query = [], ?array $post = null): array
{
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost' . $path . '?' . http_build_query($query));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'PV import HTTP request failed.');
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}

$before = $_SESSION;
$items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId();
    $oid = (int)$items['org']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version')];
    foreach (['source' => ['validated', '2026-09-01 12:00:00'],
        'source_review' => ['review', '2026-09-02 12:00:00'],
        'source_open' => ['meeting', '2026-09-03 12:00:00'],
        'destination' => ['meeting', '2026-10-01 12:00:00']] as $name => [$stage, $date]) {
        $items[$name] = mcpFixture(Document::class, ['title' => 'HTTP import ' . $name, 'documenttype' => 'pv',
            'pvstage' => $stage, 'datecreation' => $date, 'IDorganization' => $oid, 'IDuser' => $uid,
            'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid, 'active' => 1]);
    }
    foreach (['source', 'source_review', 'source_open'] as $sourceName) {
        $items['point_' . $sourceName] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items[$sourceName]->getId(),
            'IDuser_author' => $uid, 'title' => 'Old unhandled point', 'content' => '<p>Retained draft</p>',
            'pointtype' => 'information', 'active' => 1]);
    }
    $response = pvImportHttpRequest('/omo/api/documents/pv/editor.php', ['id' => $items['destination']->getId(), 'oid' => $oid]);
    mcpCheck($response['status'] === 200, 'Destination editor opens: ' . $response['status']);
    mcpCheck(preg_match('/data-omo-pv-editor-token="([^"]+)"/', $response['body'], $match) === 1, 'Editor token is rendered.');
    $post = ['document_id' => $items['destination']->getId(), 'oid' => $oid, 'editor_token' => $match[1],
        'scope' => 'local', 'members' => 'mine', 'period' => 'before', 'action' => 'list_importable_points'];
    $api = '/omo/api/documents/pv/action.php';
    $response = pvImportHttpRequest($api, [], $post);
    mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'List old points over HTTP: ' . $response['body']);
    mcpCheck(array_column($response['json']['items'], 'id') === array_map(static fn($name) => (int)$items[$name]->getId(),
        ['point_source_open', 'point_source_review', 'point_source']), 'Open, review and validated source points are listed.');
    $post['point_id'] = $items['point_source']->getId();
    $post['action'] = 'save_point';
    mcpCheck(pvImportHttpRequest($api, [], $post)['status'] === 403, 'Ordinary editing still refuses a point from another PV.');
    $post['action'] = 'abandon_importable_point';
    $invalidToken = $post;
    $invalidToken['editor_token'] = 'invalid';
    mcpCheck(pvImportHttpRequest($api, [], $invalidToken)['status'] === 403, 'Follow-up closure still checks the session token.');
    $items['foreign_author'] = mcpFixture(dbObject\User::class, ['firstname' => 'Other author',
        'email' => 'pv-import-other-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
    $items['foreign_point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items['source']->getId(),
        'IDuser_author' => $items['foreign_author']->getId(), 'title' => 'Another author point', 'pointtype' => 'information', 'active' => 1]);
    $forgedPoint = $post;
    $forgedPoint['point_id'] = $items['foreign_point']->getId();
    mcpCheck(pvImportHttpRequest($api, [], $forgedPoint)['status'] === 409, 'A forged source ID cannot bypass the author filter.');
    foreach (['source', 'source_review', 'source_open'] as $sourceName) {
        $point = $items['point_' . $sourceName];
        $post['point_id'] = $point->getId();
        $response = pvImportHttpRequest($api, [], $post);
        mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'Close follow-up over HTTP (' . $sourceName . '): ' . $response['body']);
        $point->load($point->getId(), true);
        mcpCheck($point->isAbandoned() && !$point->isHandled()
            && $point->get('content') === '<p>Retained draft</p>', 'HTTP closure retains the unhandled source content.');
    }
    unset($post['point_id']);
    $post['action'] = 'list_importable_points';
    $response = pvImportHttpRequest($api, [], $post);
    mcpCheck($response['status'] === 200 && $response['json']['items'] === [], 'Closed points disappear from the actual import endpoint.');
    echo "pv_point_import_http_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
