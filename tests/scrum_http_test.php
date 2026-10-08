<?php
declare(strict_types=1);
// Run in local Docker as www-data, so Apache can read the disposable fixture session.
$_SERVER['HTTP_HOST'] = 'localtest.me'; $_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ScrumSprint, Project, ArrayApplication, OrganizationApplication, UserHolon, ArrayPermission, HolonPermission};
function scrumHttp(string $page, array $query, ?array $post = null, bool $csrf = true): array {
    if ($post !== null && $csrf) { $post['_csrf'] = commonCsrfToken(); }
    $cookie = session_name() . '=' . session_id(); session_write_close();
    $curl = curl_init('https://localhost/omo/api/scrum/' . $page . '?' . http_build_query($query));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) { curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]); }
    $body = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE); session_start();
    mcpCheck(is_string($body), 'Local Scrum HTTP request failed');
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}
$before = $_SESSION; $items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $hid = (int)$items['role']->getId(); $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    foreach (['scrum', 'projects'] as $hash) {
        $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'value' => $hash]], 'limit' => 1]);
        $items['app_' . $hash] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    }
    $items['assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $uid, 'IDholon' => $hid, 'active' => 1, 'is_membership' => 1]);
    $permissions = new ArrayPermission(); $permissions->load(['where' => [['field' => 'permission_key', 'op' => 'in', 'value' => ['CAN_CREATE_PROJECT', 'CAN_EDIT_PROJECT']]]]);
    foreach ($permissions as $permission) { $items['permission_' . $permission->getId()] = mcpFixture(HolonPermission::class, ['IDholon' => $hid, 'IDpermission' => $permission->getId(), 'range' => 'self', 'member_type' => 'member']); }
    $query = ['oid' => $oid, 'cid' => $hid];
    $editor = scrumHttp('index.php', $query + ['edit' => 1]);
    mcpCheck($editor['status'] === 200 && str_contains($editor['body'], 'formulaire-edit'), 'Authorized editor must use adminEdit');
    $payload = ['action' => 'save', 'title' => 'HTTP Scrum <test>', 'objective' => 'Testing the complete flow', 'start_date' => ScrumSprint::now()->format('Y-m-d'), 'end_date' => ScrumSprint::now()->modify('+7 days')->format('Y-m-d')];
    $denied = scrumHttp('action.php', $query, $payload, false);
    mcpCheck($denied['status'] === 403, 'Missing CSRF must fail');
    $created = scrumHttp('action.php', $query, $payload);
    mcpCheck($created['status'] === 200 && !empty($created['json']['status']), 'Create sprint: ' . $created['body']);
    $id = (int)$created['json']['id']; $items['sprint'] = new ScrumSprint(); $items['sprint']->load($id, true);
    $today = scrumHttp('index.php', $query + ['id' => $id]);
    $items['sprint']->load($id, true);
    mcpCheck($items['sprint']->get('state') === 'running', 'A sprint dated today starts automatically');
    $prepare = scrumHttp('action.php', $query, ['action' => 'stop', 'id' => $id]);
    mcpCheck(!empty($prepare['json']['status']), 'Stop permits preparation after an automatic start');
    $projectBase = ['IDorganization' => $oid, 'IDholon' => (int)$items['root']->getId(), 'IDuser' => $uid, 'project_size' => 'M', 'status' => 'someday', 'active' => 1];
    $items['parent'] = mcpFixture(Project::class, ['title' => 'HTTP parent'] + $projectBase);
    $items['child'] = mcpFixture(Project::class, ['title' => 'HTTP child', 'IDproject_parent' => (int)$items['parent']->getId()] + $projectBase);
    $pid = (int)$items['parent']->getId(); $childId = (int)$items['child']->getId();
    $picker = scrumHttp('index.php', $query + ['id' => $id, 'import' => 1]);
    mcpCheck($picker['status'] === 200 && str_contains($picker['body'], 'data-scrum-picker'), 'Project picker must render');
    $import = scrumHttp('action.php', $query, ['action' => 'import', 'id' => $id, 'projects' => [$pid], 'sizes' => [$pid => 'S']]);
    mcpCheck(!empty($import['json']['status']), 'Cross-circle tree import: ' . $import['body']);
    $detail = scrumHttp('index.php', $query + ['id' => $id]);
    mcpCheck($detail['status'] === 200 && str_contains($detail['body'], 'data-omo-projects-scrum="1"'), 'The original kanban must render inside Scrum');
    $dom = new DOMDocument(); @$dom->loadHTML($detail['body']); $xpath = new DOMXPath($dom);
    mcpCheck($xpath->query('//*[@data-omo-project-card]')->length === 2, 'Leaf projects must be visible as cards');
    mcpCheck($xpath->query('//*[@data-omo-projects-column="someday"]')->length === 0, 'No someday column');
    mcpCheck(str_contains($detail['body'], 'HTTP Scrum &lt;test&gt;'), 'Sprint title must be escaped');
    $wrong = scrumHttp('action.php', ['oid' => $oid, 'cid' => (int)$items['root']->getId()], ['action' => 'stop', 'id' => $id]);
    mcpCheck($wrong['status'] >= 400, 'A sprint cannot be edited from the wrong space');
    $start = scrumHttp('action.php', $query, ['action' => 'resume', 'id' => $id]);
    mcpCheck(!empty($start['json']['status']), 'Resume must start the sprint');
    $locked = scrumHttp('action.php', $query, ['action' => 'import', 'id' => $id, 'projects' => [$pid]]);
    mcpCheck($locked['status'] === 422, 'Started sprint rejects imports');
    $stop = scrumHttp('action.php', $query, ['action' => 'stop', 'id' => $id]);
    mcpCheck(!empty($stop['json']['status']), 'Stop must reopen preparation');
    $partial = scrumHttp('action.php', $query, ['action' => 'remove', 'id' => $id, 'project_id' => $childId]);
    mcpCheck($partial['status'] === 422, 'Partial tree removal must fail');
    $remove = scrumHttp('action.php', $query, ['action' => 'remove', 'id' => $id, 'project_id' => $pid]);
    mcpCheck(!empty($remove['json']['status']), 'Root removal must include descendants');
    $reimport = scrumHttp('action.php', $query, ['action' => 'import', 'id' => $id, 'projects' => [$pid]]);
    mcpCheck(!empty($reimport['json']['status']), 'A removed tree can be imported again');
    $items['sprint']->load($id, true);
    ScrumSprint::locked($oid, fn () => $items['sprint']->sync($items['sprint']->endAt()->modify('+1 second')));
    $finished = scrumHttp('index.php', $query + ['id' => $id]);
    $snapshotDom = new DOMDocument(); @$snapshotDom->loadHTML($finished['body']); $snapshotXpath = new DOMXPath($snapshotDom);
    mcpCheck($snapshotXpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " omo-project-card ")]')->length === 2, 'Final snapshot retains all cards');
    mcpCheck($snapshotXpath->query('//*[@data-omo-project-status-select]')->length === 0, 'Final snapshot cannot edit live statuses');
    $archive = scrumHttp('action.php', $query, ['action' => 'archive', 'id' => $id]);
    mcpCheck(!empty($archive['json']['status']), 'Finished sprint can be archived');
    $archives = scrumHttp('index.php', $query + ['archives' => 1]);
    mcpCheck(str_contains($archives['body'], 'HTTP Scrum &lt;test&gt;'), 'Archives list must retain the sprint');
    $restore = scrumHttp('action.php', $query, ['action' => 'restore', 'id' => $id]);
    mcpCheck(!empty($restore['json']['status']), 'Archived sprint can be restored');
    $deleted = scrumHttp('action.php', $query, ['action' => 'delete', 'id' => $id]);
    mcpCheck(!empty($deleted['json']['status']), 'Draft sprint can be deleted');
    $project = new Project(); mcpCheck($project->load($pid, true), 'Sprint deletion must preserve projects');
    unset($items['sprint']);
    $fixture = ['editor' => $editor['body'], 'picker' => $picker['body'], 'detail' => $detail['body'], 'parent' => $pid, 'child' => $childId];
    file_put_contents(dirname(__DIR__) . '/tmp/scrum-browser-fixture.json', json_encode($fixture));
    echo "Scrum HTTP permissions, import, kanban and lifecycle tests passed.\n";
} finally { mcpCleanup($items); $_SESSION = $before; session_write_close(); }
