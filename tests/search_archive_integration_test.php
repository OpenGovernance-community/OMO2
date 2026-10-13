<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/mcp/server.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization, Application, OrganizationApplication,
    Document, Project, ControlActivity, StatIndicator, SearchJob, ArrayDocument, ArrayProject, ArrayControlActivity, ObjectVisibility,
    DecisionProcess, Event, Rule, FAQ, Checklist, ArrayChecklist, ArrayEvent};

function searchArchiveAssert(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function searchArchiveFixture(string $class, array $values): DbObject {
    $object = new $class();
    foreach ($values as $field => $value) $object->set($field, $value);
    $result = $object->save();
    searchArchiveAssert(!empty($result['status']), 'Fixture failed: ' . $class . ' ' . json_encode($result) . ' ' . json_encode(DbObject::getLastDbError()));
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(5));
    $org = searchArchiveFixture(Organization::class, ['name' => 'Archive search', 'shortname' => 'sa' . $nonce]);
    $user = searchArchiveFixture(User::class, ['email' => 'sa' . $nonce . '@example.invalid', 'active' => 1]);
    searchArchiveFixture(UserOrganization::class, ['IDuser' => $user->getId(), 'IDorganization' => $org->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    foreach (['structure', 'documents', 'projects', 'activities', 'stats', 'calendar', 'decision', 'policy', 'processus'] as $hash) {
        $app = new Application();
        searchArchiveAssert((bool)$app->load(['hash', $hash]), 'Missing application: ' . $hash);
        searchArchiveFixture(OrganizationApplication::class, ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $root = searchArchiveFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $org->getId(), 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $circle = searchArchiveFixture(Holon::class, ['name' => 'Needle circle', 'IDholon_parent' => $root->getId(), 'IDholon_org' => $root->getId(), 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $role = searchArchiveFixture(Holon::class, ['name' => 'Needle role', 'IDholon_parent' => $circle->getId(), 'IDholon_org' => $root->getId(), 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $_SESSION['currentUser'] = $user->getId(); $_SESSION['currentOrganization'] = $org->getId();
    commonSetCurrentUserAdminMode(true, $org->getId());
    $viewer = SearchJob::buildViewerContextFromGlobals((int)$org->getId(), (int)$root->getId());
    $objects = [];
    foreach (['live' => $root, 'space' => $role, 'own' => $root] as $kind => $holon) {
        $base = ['IDorganization' => $org->getId(), 'IDholon' => $holon->getId(), 'active' => $kind === 'own' ? 0 : 1];
        $own = $kind === 'own' ? ['archived_at' => new DateTime()] : [];
        $objects[$kind] = [
            'documents' => searchArchiveFixture(Document::class, $base + ['title' => 'Needle doc ' . $kind, 'IDuser' => $user->getId(), 'documenttype' => Document::TYPE_HTML, 'content' => 'Preserved content']),
            'pv' => searchArchiveFixture(Document::class, $base + ['title' => 'Needle PV ' . $kind, 'IDuser' => $user->getId(), 'documenttype' => Document::TYPE_PV]),
            'projects' => searchArchiveFixture(Project::class, $base + $own + ['title' => 'Needle project ' . $kind, 'project_kind' => Project::KIND_STANDARD]),
            'activities' => searchArchiveFixture(ControlActivity::class, $base + $own + ['title' => 'Needle task ' . $kind, 'frequency' => 'monthly', 'schedule' => '1']),
            'stats' => searchArchiveFixture(StatIndicator::class, $base + $own + ['name' => 'Needle indicator ' . $kind, 'reference_type' => 'none']),
        ];
    }
    $extra = [];
    foreach (['live' => $root, 'space' => $role] as $kind => $holon) {
        $base = ['IDorganization' => $org->getId(), 'IDholon' => $holon->getId()];
        $template = searchArchiveFixture(Project::class, $base + ['title' => 'Needle process ' . $kind, 'active' => 1, 'project_kind' => Project::KIND_CHECKLIST_TEMPLATE]);
        $extra[$kind] = [
            'calendar' => searchArchiveFixture(Event::class, $base + ['IDuser' => $user->getId(), 'title' => 'Needle event ' . $kind, 'status' => 'scheduled', 'active' => 1, 'start_at' => new DateTime('+1 day'), 'end_at' => new DateTime('+2 days')]),
            'decision' => searchArchiveFixture(DecisionProcess::class, $base + ['IDuser' => $user->getId(), 'title' => 'Needle decision ' . $kind, 'decision_type' => 'choice', 'status' => 'draft', 'evaluation_method' => 'majority', 'visibility_type' => 'organization']),
            'rules' => searchArchiveFixture(Rule::class, $base + ['title' => 'Needle rule ' . $kind, 'description' => 'Preserved rule', 'review_date' => new DateTime('+1 year'), 'expiration_date' => new DateTime('+2 years')]),
            'faq' => searchArchiveFixture(FAQ::class, $base + ['question' => 'Needle question ' . $kind, 'answer' => 'Preserved answer', 'isactive' => 1]),
            'processus' => searchArchiveFixture(Checklist::class, ['IDorganization' => $org->getId(), 'IDproject_template_root' => $template->getId(), 'status' => 'published', 'active' => 1]),
        ];
    }
    searchArchiveAssert(!empty($circle->changeArchiveState((int)$org->getId(), false, 'reassign')['status']), 'Archive circle');
    $scopes = ['documents', 'pv', 'projects', 'activities', 'stats', 'structure'];
    $options = ['viewerContext' => $viewer, 'retainAllScopes' => true];
    $active = $org->searchTopbarResults('Needle', $scopes, $options);
    $archive = $org->searchTopbarResults('Needle', $scopes, $options + ['archived' => true]);
    foreach (['documents', 'pv', 'projects', 'activities', 'stats'] as $module) {
        searchArchiveAssert(($active['counts'][$module] ?? -1) === 1, 'Active count ' . $module . ': ' . json_encode($active));
        searchArchiveAssert(($archive['counts'][$module] ?? -1) === 2, 'Archive count ' . $module . ': ' . json_encode($archive));
        foreach (['live', 'space', 'own'] as $kind) {
            $id = (int)$objects[$kind][$module]->getId();
            searchArchiveAssert((bool)$org->loadTopbarSearchPreviewObject($module, $id, $viewer) === ($kind === 'live'), 'Normal preview ' . $kind . ' ' . $module);
            searchArchiveAssert((bool)$org->loadTopbarSearchPreviewObject($module, $id, $viewer + ['archived' => true]) === ($kind !== 'live'), 'Archive preview ' . $kind . ' ' . $module);
        }
    }
    searchArchiveAssert($active['counts']['structure'] === 0 && $archive['counts']['structure'] === 2, 'Structure partition');
    foreach (['calendar', 'decision', 'rules', 'faq', 'processus'] as $module) {
        $normal = $org->searchTopbarResults('Needle', [$module], $options);
        $archived = $org->searchTopbarResults('Needle', [$module], $options + ['archived' => true]);
        searchArchiveAssert(($normal['counts'][$module] ?? -1) === 1, 'Extra active ' . $module . ': ' . json_encode($normal));
        searchArchiveAssert(($archived['counts'][$module] ?? -1) === 1, 'Extra archive ' . $module . ': ' . json_encode($archived));
        $id = (int)$extra['space'][$module]->getId();
        searchArchiveAssert(!$org->loadTopbarSearchPreviewObject($module, $id, $viewer), 'Hidden normal preview: ' . $module);
        searchArchiveAssert((bool)$org->loadTopbarSearchPreviewObject($module, $id, $viewer + ['archived' => true]), 'Missing archive preview: ' . $module);
    }
    // Both transports use this operation, including the detail permission gates before pagination.
    $grant = ['IDorganization' => $org->getId(), 'IDuser' => $user->getId(), 'scope' => OMO_MCP_SCOPE];
    foreach (array_merge($scopes, ['calendar', 'decision', 'rules', 'faq', 'processus']) as $module) {
        $args = ['query' => 'Needle', 'modules' => [$module], 'archived' => true];
        $page = omoApiExecuteOperation('omo_search', omoMcpToolArguments('omo_search', (object)$args), $grant);
        searchArchiveAssert($page['archived'] && count($page['items']) === (in_array($module, $scopes, true) ? 2 : 1), 'API archive partition: ' . $module);
        foreach ($page['items'] as $item) {
            searchArchiveAssert($item['archived'] && str_contains($item['url'], 'archive=1'), 'Archive source URL');
            $readArgs = ['module' => $module, 'record_id' => $item['record_id'], 'context_holon_id' => $item['context_holon_id'], 'archived' => true];
            $record = omoApiExecuteOperation('omo_read_record', omoMcpToolArguments('omo_read_record', (object)$readArgs), $grant);
            searchArchiveAssert($record['archived'] && str_contains($record['record']['url'], 'archive=1'), 'API archive read: ' . $module);
        }
    }
    $public = $org->searchTopbarResults('Needle', $scopes, ['archived' => true, 'viewerContext' => ['type' => 'public', 'organizationId' => $org->getId()]]);
    searchArchiveAssert($public['results'] === [], 'Public archive search must be empty');

    // Catalogs must exclude resources from hidden spaces, even with broad organization scope.
    $docs = new ArrayDocument(); $docs->loadVisibleForOrganization($org->getId());
    searchArchiveAssert(count($docs) === 2, 'Document catalog leaked hidden space');
    $docs->loadOwnedByUser($user->getId());
    searchArchiveAssert(count($docs) === 2, 'Personal documents must exclude own and inherited archives');
    $projects = new ArrayProject(); $projects->loadForOrganization($org->getId());
    searchArchiveAssert(count($projects) === 1, 'Project catalog leaked hidden space');
    $tasks = new ArrayControlActivity(); $tasks->loadForContext($org->getId(), [$root->getId(), $role->getId()]);
    searchArchiveAssert(count($tasks) === 1, 'Task catalog leaked hidden space');
    $processes = new ArrayChecklist(); $processes->loadForOrganization($org->getId());
    searchArchiveAssert(count($processes) === 1, 'Process catalog leaked hidden space');
    $events = new ArrayEvent(); $events->loadForOrganization($org->getId());
    searchArchiveAssert(count($events) === 1, 'Event catalog leaked hidden space');
    $events->loadForUser($user->getId(), $org->getId());
    searchArchiveAssert(count($events) === 1, 'Personal events leaked hidden space');
    $decisions = DecisionProcess::fetchListRowsForOrganization($org->getId(), $user->getId());
    searchArchiveAssert(count($decisions) === 1, 'Decision catalog leaked hidden space');
    searchArchiveAssert(count(DecisionProcess::fetchRelevantRowsForUser($user->getId())) === 1, 'Personal decisions leaked hidden space');
    $extra['live']['decision']->set('status', DecisionProcess::STATUS_ARCHIVED); $extra['live']['decision']->save();
    searchArchiveAssert($org->searchTopbarResults('Needle', ['decision'], $options)['counts']['decision'] === 0, 'Individually archived decisions must leave the normal search');
    searchArchiveAssert($org->searchTopbarResults('Needle', ['decision'], $options + ['archived' => true])['counts']['decision'] === 2, 'Decision archives must include both own and context archives');

    // The worker persists the selected partition without relying on its PHP session.
    $job = SearchJob::createTopbarJob($org, 'Needle', ['documents'], $viewer + ['archived' => true]);
    $session = $_SESSION;
    $_SESSION = [];
    try {
        searchArchiveAssert((bool)$job && SearchJob::processJobById((int)$job->getId()), 'Archive worker failed');
    } finally { $_SESSION = $session; }
    $job->load($job->getId(), true);
    searchArchiveAssert(($job->getResultPayload()['counts']['documents'] ?? 0) === 2, 'Worker lost archive mode');

    $other = searchArchiveFixture(User::class, ['email' => 'other' . $nonce . '@example.invalid', 'active' => 1]);
    $private = $objects['own']['documents'];
    $private->set('IDuser', $other->getId()); $private->save();
    ObjectVisibility::saveSingleRule(Document::getVisibilityObjectType(), $private->getId(), $org->getId(), ObjectVisibility::TYPE_SELF);
    commonSetCurrentUserAdminMode(false, $org->getId());
    $restricted = $org->searchTopbarResults('Needle', ['documents'], $options + ['archived' => true]);
    searchArchiveAssert($restricted['counts']['documents'] === 1, 'Archive search must retain private document permissions');
    searchArchiveAssert(!$org->loadTopbarSearchPreviewObject('documents', (int)$private->getId(), $viewer + ['archived' => true]), 'Private archive preview must be denied');
    $outsider = ['type' => 'user', 'organizationId' => $org->getId(), 'userId' => $other->getId(), 'archived' => true];
    searchArchiveAssert($org->searchTopbarResults('Needle', $scopes, ['viewerContext' => $outsider])['results'] === [], 'Non-member archive search must be empty');
    searchArchiveAssert(!$org->loadTopbarSearchPreviewObject('structure', (int)$circle->getId(), $outsider), 'Non-member archive preview must be denied');
    commonSetCurrentUserAdminMode(true, $org->getId());

    searchArchiveAssert(!empty($circle->changeArchiveState((int)$org->getId(), true)['status']), 'Restore circle');
    $after = $org->searchTopbarResults('Needle', $scopes, $options);
    foreach (['documents', 'projects', 'activities', 'stats'] as $module) {
        searchArchiveAssert($after['counts'][$module] === 2, 'Restored content should reappear: ' . $module);
        $objects['own'][$module]->load($objects['own'][$module]->getId(), true);
        searchArchiveAssert(!(bool)$objects['own'][$module]->get('active'), 'Own archives must remain archived');
    }
    echo "search_archive_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Holon::clearPreloadedData();
}
