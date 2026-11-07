<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{Application, ControlActivity, DeferredProposal, DecisionGovernanceAction, Document,
    DocumentPvPoint, HolonPermission, OrganizationApplication, Permission, Project, StatIndicator};

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    mcpCheck(in_array($request['endpoint'], ['pv_project_editor.php', 'pv_project_save.php',
        'pv_object_editor.php', 'pv_object_save.php', 'pv_holon_editor.php', 'pv_holon_save.php'], true), 'Invalid endpoint.');
    $_SERVER['REQUEST_URI'] = '/omo/api/deferred_proposals/' . $request['endpoint'];
    require dirname(__DIR__) . $_SERVER['REQUEST_URI'];
    exit;
}

function snapshotRequest(string $endpoint, array $get, array $post = []): string
{
    $request = ['session' => $_SESSION, 'get' => $get, 'post' => $post, 'endpoint' => $endpoint];
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=stderr', __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start proposal endpoint.');
    fclose($pipes[0]);
    $body = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0, 'Proposal endpoint failed: ' . $errors);
    return $body;
}

function snapshotCheckFields(string $html, array $expected): void
{
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    foreach ($expected as $name => $value) {
        $field = $xpath->query('//*[@name="' . $name . '"]')->item(0);
        mcpCheck($field instanceof DOMElement, 'Missing field: ' . $name);
        $actual = match ($field->tagName) {
            'textarea' => $field->textContent,
            'select' => $field->hasAttribute('data-selected-value') ? $field->getAttribute('data-selected-value')
                : $xpath->query('option[@selected]', $field)->item(0)?->getAttribute('value'),
            default => $field->getAttribute('value'),
        };
        mcpCheck($actual === (string)$value, 'Proposal must retain ' . $name . ': expected ' . $value . ', got ' . $actual);
    }
}

function snapshotSave(string $endpoint, array $post, array &$items, string $key): DeferredProposal
{
    $result = json_decode(snapshotRequest($endpoint, [], $post), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($result['status']), 'Cannot save proposal: ' . json_encode($result));
    $proposal = new DeferredProposal();
    mcpCheck($proposal->load((int)$result['id']), 'Cannot reload proposal.');
    $items[$key] = $proposal;
    return $proposal;
}

$before = $_SESSION; $items = mcpFixtures(false);
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    mcpEnableDocumentCreation($items);
    foreach (['projects', 'stats', 'activities'] as $hash) {
        $app = new Application(); mcpCheck($app->load([['hash', $hash]]), 'Missing app: ' . $hash);
        $items['app_' . $hash] = mcpFixture(OrganizationApplication::class,
            ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    foreach (['PROJECT', 'INDICATOR', 'RECURRING_TASK', 'HOLON'] as $type) {
        foreach (['CREATE', 'EDIT'] as $operation) {
            $key = $type === 'HOLON' && $operation === 'CREATE' ? 'CAN_ADD_HOLON' : 'CAN_' . $operation . '_' . $type;
            $items[$key] = mcpFixture(HolonPermission::class, ['IDholon' => $hid,
                'IDpermission' => Permission::findByKey($key)->getId(), 'range' => 'organization', 'member_type' => 'collective']);
        }
    }
    $items['pv'] = mcpFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid, 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_MEETING, 'title' => 'Proposal snapshot fixture', 'active' => 1]);
    $items['point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items['pv']->getId(),
        'IDuser_author' => $uid, 'IDholon_concerned' => $hid, 'title' => 'Snapshot checks', 'position' => 1]);
    $base = ['oid' => $oid, 'point_id' => $items['point']->getId(), 'holon_id' => $hid];
    $cases = [
        'project' => [Project::class, ['title' => 'Existing project', 'description' => '<p>Existing project content</p>'],
            ['title' => 'Proposed project', 'description' => '<p>Proposed project content</p>',
                'project_size' => 'L', 'planned_start_date' => '2027-01-01', 'planned_end_date' => '2027-06-01']],
        'recurring_task' => [ControlActivity::class, ['title' => 'Existing task', 'description' => '<p>Existing task content</p>',
            'frequency' => 'daily', 'schedule' => '09:00'],
            ['title' => 'Proposed task', 'description' => '<p>Proposed task content</p>', 'frequency' => 'weekly',
                'schedule' => '3', 'display_lead_value' => 4, 'execution_duration_value' => 2]],
        'indicator' => [StatIndicator::class, ['name' => 'Existing indicator', 'description' => 'Existing indicator content', 'source_type' => 'manual'],
            ['name' => 'Proposed indicator', 'description' => 'Proposed indicator content', 'source_type' => 'manual',
                'source_url' => 'https://example.invalid/proposed', 'chart_min_value' => 12,
                'reference_type' => 'ceiling', 'ceiling_value' => 150]],
    ];
    foreach ($cases as $type => [$class, $original, $proposed]) {
        $object = $items[$type] = mcpFixture($class, $original + ['IDorganization' => $oid, 'IDholon' => $hid, 'active' => 1]);
        $prefix = $type === 'project' ? 'pv_project' : 'pv_object';
        $target = ['target_type' => $type, $type === 'project' ? 'project_id' : 'object_id' => $object->getId()];
        snapshotCheckFields(snapshotRequest($prefix . '_editor.php', $base + $target + ['operation' => 'update']), $original);
        foreach (['update', 'create'] as $operation) {
            $post = array_replace($base, $target, $proposed, ['operation' => $operation]);
            if ($operation === 'create') $post[$type === 'project' ? 'project_id' : 'object_id'] = 0;
            $proposal = snapshotSave($prefix . '_save.php', $post, $items, $type . '_' . $operation);
            // Match the Edit link: only the proposal identifies the saved operation and target.
            $edit = ['oid' => $oid, 'point_id' => $base['point_id'], 'target_type' => $type,
                'proposal_id' => $proposal->getId(), 'stage' => 'capture', 'direct' => 1];
            snapshotCheckFields(snapshotRequest($prefix . '_editor.php', $edit), $proposed);
            $post['proposal_id'] = $proposal->getId(); $post['description'] = 'Adjusted proposal content';
            snapshotSave($prefix . '_save.php', $post, $items, $type . '_' . $operation);
            snapshotCheckFields(snapshotRequest($prefix . '_editor.php', $edit), array_replace($proposed, ['description' => $post['description']]));
        }
        $object->load($object->getId(), true);
        mcpCheck($object->get('description') === $original['description'], 'Proposals must not change the live ' . $type);
        echo $type . ": create/update snapshots OK\n";
    }
    foreach (['role' => 1, 'circle' => 2, 'group' => 3] as $type => $typeId) {
        $fields = ['IDorganization' => $oid, 'IDholon_org' => $hid, 'IDholon_parent' => $hid,
            'IDtypeholon' => $typeId, 'active' => 1];
        $template = $items[$type . '_template'] = mcpFixture(McpTestHolon::class,
            $fields + ['name' => 'Template', 'templatename' => 'Snapshot ' . $type, 'visible' => 0]);
        $object = $items['live_' . $type] = mcpFixture(McpTestHolon::class,
            $fields + ['IDholon_template' => $template->getId(), 'name' => 'Existing ' . $type, 'visible' => 1]);
        $state = DecisionGovernanceAction::captureHolonEditorState($object, $items['org']);
        $payload = array_replace($state['editor_payload'], ['name' => 'Proposed ' . $type, 'fullName' => 'Proposed full name']);
        foreach (['update', 'create'] as $operation) {
            $post = array_replace($base, ['operation' => $operation, 'holon_id' => $operation === 'create' ? $hid : $object->getId(),
                'payload' => json_encode($payload)]);
            $proposal = snapshotSave('pv_holon_save.php', $post, $items, $type . '_' . $operation);
            $edit = ['oid' => $oid, 'point_id' => $base['point_id'], 'proposal_id' => $proposal->getId(), 'stage' => 'capture', 'direct' => 1];
            $html = snapshotRequest('pv_holon_editor.php', $edit);
            mcpCheck(str_contains($html, 'id="omo-holon-create-editor"'), 'Holon form must render.');
            mcpCheck(preg_match('/window\.omoHolonGovernanceInitialPayload = (.*?);<\/script>/', $html, $matches) === 1, 'Missing initial holon payload.');
            $initial = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
            mcpCheck($initial['name'] === $payload['name'] && $initial['fullName'] === $payload['fullName'], 'Holon editor must receive proposed names.');
        }
        $object->load($object->getId(), true);
        mcpCheck($object->get('name') === 'Existing ' . $type, 'Holon proposals must leave live names unchanged.');
        echo $type . ": create/update snapshots OK\n";
    }
    echo "pv_proposal_editor_snapshots_test: OK\n";
} finally {
    mcpCleanup($items); $_SESSION = $before;
}
