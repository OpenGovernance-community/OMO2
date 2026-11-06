<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{DeferredProposal, Document, DocumentPvPoint, HolonPermission, Permission, Rule};

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $endpoint = $_POST ? 'pv_rule_save.php' : 'pv_rule_editor.php';
    $_SERVER['REQUEST_URI'] = '/omo/api/deferred_proposals/' . $endpoint;
    require dirname(__DIR__) . $_SERVER['REQUEST_URI'];
    exit;
}

function ruleEditorRequest(array $get, array $post = []): string
{
    $request = ['session' => $_SESSION, 'get' => $get, 'post' => $post];
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start rule endpoint.');
    fclose($pipes[0]);
    $body = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0, 'Rule endpoint failed: ' . $errors);
    return $body;
}

function ruleEditorCheckFields(string $html, array $expected): void
{
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    foreach ($expected as $name => $value) {
        $field = $xpath->query('//*[@name="' . $name . '"]')->item(0);
        mcpCheck($field instanceof DOMElement, 'Missing rule field: ' . $name);
        $actual = match ($field->tagName) {
            'textarea' => $field->textContent,
            'select' => $xpath->query('option[@selected]', $field)->item(0)?->getAttribute('value'),
            default => $field->getAttribute('value'),
        };
        mcpCheck($actual === (string)$value, 'Reopened rule field must retain its snapshot: ' . $name);
    }
}

$before = $_SESSION;
$items = mcpFixtures(false);
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $hid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    mcpEnableDocumentCreation($items);
    foreach (['CAN_CREATE_RULE', 'CAN_EDIT_RULE', 'CAN_DELETE_RULE'] as $key) {
        $items[$key] = mcpFixture(HolonPermission::class, ['IDholon' => $hid,
            'IDpermission' => Permission::findByKey($key)->getId(), 'range' => 'self', 'member_type' => 'collective']);
    }
    $items['pv'] = mcpFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $uid, 'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid,
        'documenttype' => Document::TYPE_PV, 'pvstage' => Document::PV_STAGE_MEETING,
        'title' => 'Rule proposal editor fixture', 'active' => 1]);
    $items['point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items['pv']->getId(),
        'IDuser_author' => $uid, 'IDholon_concerned' => $hid, 'title' => 'Rule change', 'position' => 1]);
    $original = ['title' => 'Existing rule', 'description' => '<p>Existing content</p>',
        'intention' => '<p>Existing intention</p>', 'scope' => 'local',
        'review_date' => '2027-01-01', 'expiration_date' => '2028-01-01'];
    $items['rule'] = mcpFixture(Rule::class, $original + ['IDholon' => $hid]);
    $get = ['oid' => $oid, 'point_id' => $items['point']->getId(), 'holon_id' => $hid,
        'workflow' => 1, 'direct' => 1, 'operation' => 'update', 'rule_id' => $items['rule']->getId()];
    // A new update starts from the live rule; an existing proposal must use its saved snapshot.
    ruleEditorCheckFields(ruleEditorRequest($get), $original);
    $proposed = ['title' => 'Proposed rule', 'description' => '<p>Proposed content</p>',
        'intention' => '<p>Proposed intention</p>', 'scope' => 'global',
        'review_date' => '2027-06-01', 'expiration_date' => '2029-06-01'];
    foreach (['update', 'create', 'delete'] as $operation) {
        $post = array_replace($get, $proposed, ['operation' => $operation]);
        $result = json_decode(ruleEditorRequest([], $post), true, 512, JSON_THROW_ON_ERROR);
        mcpCheck(!empty($result['status']), 'Cannot save ' . $operation . ' proposal: ' . json_encode($result));
        $proposal = new DeferredProposal();
        mcpCheck($proposal->load((int)$result['id']), 'Cannot reload rule proposal.');
        $items['proposal_' . $operation] = $proposal;
        $edit = $get + ['proposal_id' => $proposal->getId()];
        $expected = $operation === 'delete' ? $original : $proposed;
        ruleEditorCheckFields(ruleEditorRequest($edit), $expected);
        if ($operation === 'update') {
            $post['proposal_id'] = $proposal->getId();
            $post['description'] = '<p>Adjusted proposal</p>';
            mcpCheck(!empty(json_decode(ruleEditorRequest([], $post), true)['status']), 'Cannot adjust rule proposal.');
            ruleEditorCheckFields(ruleEditorRequest($edit), array_replace($proposed, ['description' => $post['description']]));
        }
    }
    $items['rule']->load($items['rule']->getId(), true);
    mcpCheck($items['rule']->get('title') === $original['title'], 'Editing proposals must leave the live rule unchanged.');
    // A deletion must also keep the captured rule when its live version changes later.
    $items['rule']->set('title', 'Later live title');
    mcpCheck(!empty($items['rule']->save()['status']), 'Cannot change live fixture.');
    ruleEditorCheckFields(ruleEditorRequest($get + ['proposal_id' => $items['proposal_delete']->getId()]), $original);
    echo "pv_rule_proposal_editor_test: OK\n";
} finally {
    mcpCleanup($items); $_SESSION = $before;
}
