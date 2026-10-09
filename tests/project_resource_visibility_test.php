<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/' . $request['endpoint'];
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    require dirname(__DIR__) . '/omo/api/' . $request['endpoint'];
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Project, StatIndicator, ControlActivity, ArrayStatIndicator, ArrayControlActivity};

function resourceVisibilityRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start endpoint.');
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Endpoint failed: ' . $errors . $out);
    return $out;
}
function resourceVisibilityResult(array $request): array
{
    return json_decode(resourceVisibilityRequest($request), true, 512, JSON_THROW_ON_ERROR);
}
function resourceVisibilityIds(string $type, int $oid, int $hid, bool $descendants = false): array
{
    if ($type === 'indicator') {
        $collection = new ArrayStatIndicator();
        $collection->loadForContext($oid, $hid, $descendants ? 'descendants' : 'contextual', [$hid]);
    } else {
        $collection = new ArrayControlActivity();
        $collection->loadForContext($oid, [$hid]);
    }
    return array_map(static fn($item) => (int)$item->getId(), $collection->getArrayCopy());
}
$items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId(); $hid = (int)$items['role']->getId();
    foreach (['stats', 'activities', 'projects', 'documents'] as $hash) {
        $app = new \dbObject\Application(); mcpCheck($app->load([['hash', $hash]]), 'Missing app.');
        $items['app_' . $hash] = mcpFixture(\dbObject\OrganizationApplication::class,
            ['IDapplication' => $app->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $items['assignment'] = mcpFixture(\dbObject\UserHolon::class,
        ['IDholon' => $hid, 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    foreach (['CAN_EDIT_INDICATOR', 'CAN_CREATE_RECURRING_TASK', 'CAN_EDIT_RECURRING_TASK', 'CAN_EDIT_DOCUMENT'] as $key) {
        $items[$key] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid,
            'IDpermission' => \dbObject\Permission::findByKey($key)->getId(), 'member_type' => 'member', 'range' => 'self']);
    }
    foreach (['project', 'second_project'] as $key) {
        $items[$key] = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid,
            'IDuser' => $uid, 'title' => $key, 'status' => Project::STATUS_IN_PROGRESS, 'active' => 1]);
    }
    $pid = (int)$items['project']->getId();
    $session = $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    foreach (['indicator', 'recurring_task'] as $type) {
        $module = $type === 'indicator' ? 'stats' : 'activities';
        $class = $type === 'indicator' ? StatIndicator::class : ControlActivity::class;
        $base = ['oid' => $oid, 'cid' => $hid, 'IDuser_responsible' => $uid];
        $base += $type === 'indicator'
            ? ['stats_action' => 'save_indicator', 'name' => 'Visibility indicator', 'reference_type' => 'none', 'source_type' => 'manual']
            : ['activity_action' => 'save_activity', 'title' => 'Visibility task', 'frequency' => 'weekly', 'schedule' => '1'];
        $request = ['session' => $session, 'endpoint' => $module . '/edit.php',
            'get' => ['oid' => $oid, 'cid' => $hid, 'project_id' => $pid], 'post' => []];
        $editor = resourceVisibilityRequest($request);
        mcpCheck(preg_match('/name="project_visible_in_holon"[^>]*>/', $editor, $checkbox) === 1
            && !str_contains($checkbox[0], ' checked'), 'Project creation must default to project-only visibility.');
        $request['endpoint'] = $module . '/action.php'; $request['get'] = [];
        $request['post'] = $base + ['project_id' => $pid, 'project_visibility_present' => 1];
        $saved = resourceVisibilityResult($request);
        mcpCheck(!empty($saved['status']), 'Cannot create project resource: ' . json_encode($saved));
        $items[$type . '_project_resource'] = $resource = new $class();
        mcpCheck($resource->load((int)$saved['id']), 'Missing project resource.');
        mcpCheck(!in_array($resource->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Project-only resource must leave the space list.');
        mcpCheck(!in_array($resource->getId(), resourceVisibilityIds($type, $oid, $hid, true)), 'Descendant lists must also hide project-only resources.');
        $projectView = ['session' => $session, 'endpoint' => 'projects/resources.php',
            'get' => ['oid' => $oid, 'cid' => $hid, 'id' => $pid, 'type' => $type], 'post' => []];
        mcpCheck(str_contains(resourceVisibilityRequest($projectView), $base[$type === 'indicator' ? 'name' : 'title']), 'The project must retain its hidden resource.');
        if ($type === 'recurring_task') {
            foreach (['created_at' => new DateTimeImmutable('2026-10-01'), 'frequency' => 'monthly',
                'schedule' => '1', 'execution_duration_value' => 5, 'execution_duration_unit' => 'day'] as $field => $value) {
                $resource->set($field, $value);
            }
            mcpCheck(!empty($resource->save()['status']), 'Cannot prepare an overdue project task.');
            $overdue = ControlActivity::getOverdueForUser($oid, $uid, new DateTimeImmutable('2026-10-09 12:00:00'));
            mcpCheck(in_array($resource->getId(), array_map(static fn($row) => $row['activity']->getId(), $overdue)),
                'Project-only tasks must retain their overdue reminders.');
        }

        $request['post']['id'] = $resource->getId();
        $request['post']['project_visible_in_holon'] = 1;
        mcpCheck(!empty(resourceVisibilityResult($request)['status']), 'Cannot enable space visibility.');
        mcpCheck(in_array($resource->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Checked resources must appear in the space.');
        unset($request['post']['project_visibility_present'], $request['post']['project_visible_in_holon']);
        mcpCheck(!empty(resourceVisibilityResult($request)['status']), 'Legacy save failed.');
        mcpCheck(in_array($resource->getId(), resourceVisibilityIds($type, $oid, $hid)), 'An unrelated save must retain visibility.');

        $request['post'] = $base;
        $outside = resourceVisibilityResult($request);
        mcpCheck(!empty($outside['status']), 'Cannot create a standalone resource.');
        $items[$type . '_import_resource'] = $imported = new $class(); $imported->load((int)$outside['id']);
        mcpCheck(in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Unlinked resources remain visible regardless of the flag.');
        $attach = ['session' => $session, 'endpoint' => 'projects/action.php', 'get' => [],
            'post' => ['oid' => $oid, 'cid' => $hid, 'id' => $pid, 'project_action' => 'attach_resource',
                'resource_type' => $type, 'resource_id' => $imported->getId()]];
        mcpCheck(!empty(resourceVisibilityResult($attach)['success']), 'Cannot import resource.');
        mcpCheck(!in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Import must apply project-only visibility to an unchecked resource.');
        $edit = ['session' => $session, 'endpoint' => $module . '/edit.php',
            'get' => ['oid' => $oid, 'cid' => $hid, 'id' => $imported->getId()], 'post' => []];
        mcpCheck(str_contains(resourceVisibilityRequest($edit), 'name="project_visible_in_holon"'), 'The ordinary editor must expose visibility after import.');
        $request['post'] = $base + ['id' => $imported->getId(), 'project_visibility_present' => 1, 'project_visible_in_holon' => 1];
        mcpCheck(!empty(resourceVisibilityResult($request)['status']), 'Cannot enable visibility outside the project drawer.');
        mcpCheck(in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Imported resources can reappear in the space.');
        mcpCheck(preg_match('/name="project_visible_in_holon"[^>]* checked/', resourceVisibilityRequest($edit)) === 1,
            'The ordinary editor must retain the checked visibility setting.');
        unset($request['post']['project_visible_in_holon']);
        mcpCheck(!empty(resourceVisibilityResult($request)['status']), 'Cannot hide an imported resource again.');
        mcpCheck(!in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Unchecking must hide the resource again.');
        $attach['post']['id'] = $items['second_project']->getId();
        mcpCheck(!empty(resourceVisibilityResult($attach)['success']), 'Cannot link a second project.');
        mcpCheck(in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Sharing a hidden resource across projects must immediately restore space visibility.');
        mcpCheck(in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid, true)), 'Shared resources must also appear in descendant lists.');
        $sharedEditor = resourceVisibilityRequest($edit);
        preg_match('/name="project_visible_in_holon"[^>]*>/', $sharedEditor, $sharedCheckbox);
        mcpCheck(str_contains($sharedCheckbox[0] ?? '', ' checked') && str_contains($sharedCheckbox[0] ?? '', ' disabled'),
            'A shared ' . $type . ' must have a checked and locked visibility checkbox: ' . ($sharedCheckbox[0] ?? 'missing'));
        mcpCheck(!empty(resourceVisibilityResult($request)['status']), 'Cannot save a shared resource.');
        $imported->load($imported->getId(), true);
        mcpCheck((int)$imported->get('project_visible_in_holon') === 1, 'The API must reject attempts to hide a resource shared by several projects.');
        $imported->set('project_visible_in_holon', 0);
        mcpCheck(!empty($imported->save()['status']) && (int)$imported->get('project_visible_in_holon') === 1,
            'Direct model saves must retain the mandatory visibility.');
        $attach['post']['project_action'] = 'detach_resource'; $attach['post']['id'] = $pid;
        mcpCheck(!empty(resourceVisibilityResult($attach)['success']), 'Cannot detach first project.');
        mcpCheck(preg_match('/name="project_visible_in_holon"[^>]* disabled/', resourceVisibilityRequest($edit)) === 0,
            'Returning to one project must unlock the visibility setting.');
        mcpCheck(!empty(resourceVisibilityResult($request)['status']), 'Cannot hide a resource linked to only one project.');
        mcpCheck(!in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid)), 'One project allows project-only visibility again.');
        $attach['post']['id'] = $items['second_project']->getId();
        mcpCheck(!empty(resourceVisibilityResult($attach)['success']), 'Cannot detach final project.');
        mcpCheck(in_array($imported->getId(), resourceVisibilityIds($type, $oid, $hid)), 'Removing the final project must restore space visibility.');
    }
    $items['shared_document'] = $document = mcpFixture(\dbObject\Document::class, [
        'IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid, 'IDusercreation' => $uid,
        'title' => 'Shared visibility document', 'documenttype' => \dbObject\Document::TYPE_EXTERNAL_LINK,
        'externalurl' => 'https://example.invalid/', 'active' => 1, 'project_visible_in_holon' => 0]);
    $document->saveVisibilityRule('organization');
    $document->saveEditVisibilityRule('self');
    $documentVisible = static function () use ($oid, $hid, $document): bool {
        $documents = new \dbObject\ArrayDocument();
        $documents->loadVisibleForOrganizationContext($oid, $hid);
        return in_array($document->getId(), array_map(static fn($item) => $item->getId(), $documents->getArrayCopy()));
    };
    $items['document_link_one'] = mcpFixture(\dbObject\ProjectDocument::class, ['IDproject' => $pid, 'IDdocument' => $document->getId()]);
    mcpCheck(!$documentVisible(), 'A document linked to one project can remain hidden.');
    $items['document_link_two'] = mcpFixture(\dbObject\ProjectDocument::class, ['IDproject' => $items['second_project']->getId(), 'IDdocument' => $document->getId()]);
    mcpCheck($documentVisible() && $document->isVisibleInHolonWhenProjectDocument(), 'A document shared across projects must immediately remain visible.');
    $documentEdit = ['session' => $session, 'endpoint' => 'documents/create.php', 'post' => [],
        'get' => ['oid' => $oid, 'cid' => $hid, 'id' => $document->getId()]];
    mcpCheck(preg_match('/name="project_visible_in_holon"[^>]* checked disabled/', resourceVisibilityRequest($documentEdit)) === 1,
        'Shared documents must also have a checked and locked visibility setting.');
    $documentSave = ['session' => $session, 'endpoint' => 'documents/save.php', 'get' => [],
        'post' => ['oid' => $oid, 'cid' => $hid, 'id' => $document->getId(), 'title' => 'Shared visibility document',
            'document_type' => \dbObject\Document::TYPE_EXTERNAL_LINK, 'external_url' => 'https://example.invalid/']];
    mcpCheck(!empty(resourceVisibilityResult($documentSave)['status']), 'Cannot save a shared document.');
    $document->load($document->getId(), true);
    mcpCheck((int)$document->get('project_visible_in_holon') === 1, 'Document API saves cannot hide a shared document.');
    mcpCheck(!empty($document->saveProjectHolonVisibility(false)['status']) && (int)$document->get('project_visible_in_holon') === 1,
        'Document visibility saves must enforce the shared-project rule.');
    $items['document_link_two']->delete(); unset($items['document_link_two']);
    mcpCheck(!empty($document->saveProjectHolonVisibility(false)['status']) && !$documentVisible(),
        'Returning to a single project allows a document to be hidden again.');
    echo "project_resource_visibility_test: OK\n";
} finally { mcpCleanup($items); }
