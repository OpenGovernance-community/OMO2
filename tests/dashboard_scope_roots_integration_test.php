<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/omo_context_scope.php';

use dbObject\{DbObject, Organization, Holon, UserHolon, ArrayUser, ArrayApplication, OrganizationApplication, Document, History};

function dashboardRootCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function dashboardRootFixture(string $class, array $fields): DbObject {
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    dashboardRootCheck(!empty($result['status']), 'Cannot save ' . $class . ': ' . json_encode($result));
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $admins = new ArrayUser();
    $admins->load(['where' => [['field' => 'siteadmin', 'value' => 1], ['field' => 'active', 'value' => 1]], 'limit' => 1]);
    dashboardRootCheck(count($admins) === 1, 'A local site administrator is required.');
    $admin = $admins[0];
    $_SESSION = ['currentUser' => (int)$admin->getId(), 'auth_security_version' => (int)$admin->get('security_version'), 'isSiteAdminModeEnabled' => true];
    $fixtureOrganization = dashboardRootFixture(Organization::class, ['name' => 'Dashboard roots', 'shortname' => 'dashboard-roots-' . bin2hex(random_bytes(5)), 'interface_level' => Organization::INTERFACE_LEVEL_EXPERT]);
    $oid = (int)$fixtureOrganization->getId();
    $_SESSION['currentOrganization'] = $oid;
    $apps = new ArrayApplication();
    $apps->load();
    foreach ($apps as $app) {
        if (in_array($app->get('hash'), ['structure', 'documents', 'decision', 'projects', 'team', 'calendar', 'policy', 'stats', 'processus', 'activities'], true)) {
            dashboardRootFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => true]);
        }
    }
    $fixtureRoot = dashboardRootFixture(Holon::class, ['IDorganization' => $oid, 'name' => 'Root', 'IDtypeholon' => 4, 'visible' => true, 'active' => true]);
    $fixtureRoot->set('IDholon_org', $fixtureRoot->getId());
    dashboardRootCheck(!empty($fixtureRoot->save()['status']), 'Cannot set organization root.');
    $makeHolon = static fn(string $name, int $type, Holon $parent) => dashboardRootFixture(Holon::class, [
        'name' => $name, 'IDtypeholon' => $type, 'IDholon_org' => $fixtureRoot->getId(),
        'IDholon_parent' => $parent->getId(), 'visible' => true, 'active' => true,
    ]);
    $circle = $makeHolon('Circle', 2, $fixtureRoot);
    $group = $makeHolon('Display group', 3, $circle);
    $role = $makeHolon('Memory', 1, $group);
    $child = $makeHolon('Child', 2, $role);
    $deep = $makeHolon('Grandchild', 1, $child);
    $peer = $makeHolon('Peer', 1, $circle);
    $outside = $makeHolon('Other branch', 2, $fixtureRoot);
    $holons = ['root' => $fixtureRoot, 'circle' => $circle, 'role' => $role, 'child' => $child, 'deep' => $deep, 'peer' => $peer, 'outside' => $outside];
    if (in_array($argv[1] ?? '', ['--save-leaf', '--save-leaf-local'], true)) {
        $leafRoot = ($argv[1] ?? '') === '--save-leaf-local' ? 'holon' : 'parent';
        // Supply the real JSON endpoint input without committing fixtures or starting an HTTP server.
        $leafLayout = [['id' => 'parent-children', 'type' => 'documents', 'row' => 0, 'column' => 0,
            'rowSpan' => 1, 'columnSpan' => 1, 'settings' => ['scopeRoot' => $leafRoot, 'scope' => 'children']]];
        $_SESSION['omo_dashboard_layout_csrf'] = 'dashboard-leaf-test';
        $_SERVER['HTTP_HOST'] = 'localtest.me';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/omo/api/personal_space_layout.php';
        class DashboardJsonInput {
            public $context;
            public static string $body;
            private int $offset = 0;
            public function stream_open($path, $mode, $options, &$openedPath): bool { return $path === 'php://input'; }
            public function stream_read($length): string { $part = substr(self::$body, $this->offset, $length); $this->offset += strlen($part); return $part; }
            public function stream_eof(): bool { return $this->offset >= strlen(self::$body); }
            public function stream_stat(): array { return []; }
            public function stream_close(): void { stream_wrapper_restore('php'); }
        }
        DashboardJsonInput::$body = json_encode(['oid' => $oid, 'holon_id' => (int)$peer->getId(), 'scope' => 'holon',
            'csrf_token' => $_SESSION['omo_dashboard_layout_csrf'], 'layout' => $leafLayout], JSON_THROW_ON_ERROR);
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', DashboardJsonInput::class);
        ob_start();
        register_shutdown_function(static function () use ($pdo, $peer, $leafRoot): void {
            $response = ob_get_clean();
            try {
                $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
                dashboardRootCheck(!empty($payload['status']), 'Saving a leaf dashboard must succeed: ' . $response);
                $saved = new Holon(); $saved->load($peer->getId(), true);
                $settings = $saved->getDashboardDefaultLayout()[0]['settings'];
                $expectedScope = $leafRoot === 'holon' ? 'contextual' : 'children';
                dashboardRootCheck($settings['scopeRoot'] === $leafRoot && $settings['scope'] === $expectedScope, 'Scope fallback must depend on the selected root.');
                echo "dashboard_scope_roots_integration_test: OK (save endpoint: $leafRoot/$expectedScope on a leaf)\n";
            } finally {
                if ($pdo->inTransaction()) $pdo->rollBack();
            }
        });
        require dirname(__DIR__) . '/omo/api/personal_space_layout.php';
        exit;
    }
    if (($argv[1] ?? '') === '--policy-range') {
        $ruleIds = [];
        foreach ($holons as $key => $holon) {
            $rule = dashboardRootFixture(\dbObject\Rule::class, ['IDholon' => $holon->getId(),
                'title' => 'Range fixture ' . $key, 'description' => '<p>Range test</p>', 'scope' => 'local',
                'review_date' => '2026-10-01', 'expiration_date' => '2027-10-01']);
            $ruleIds[$key] = (int)$rule->getId();
        }
        $_SERVER['HTTP_HOST'] = 'localtest.me';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        foreach (['holon', 'parent', 'organization'] as $rootKey) {
            $selectedRoot = $role->getDashboardScopeRoot($rootKey, $fixtureRoot);
            foreach (['contextual', 'children', 'descendants'] as $range) {
                $_GET = ['oid' => $oid, 'cid' => $selectedRoot->getId(), 'policy_range' => $range,
                    'policy_scope' => 'contextual', 'dashboard_context' => '1'];
                $html = (static function (): string {
                    ob_start(); require dirname(__DIR__) . '/omo/api/policy/index.php'; return ob_get_clean();
                })();
                dashboardRootCheck(!preg_match('/Warning:|Fatal error:|Deprecated:/', $html), 'Policy rendering diagnostics.');
                $scopeIds = $range === 'children' ? omoApiGetDirectChildScopeHolonIds($selectedRoot)
                    : ($range === 'descendants' ? omoApiGetDescendantHolonIds($selectedRoot) : [(int)$selectedRoot->getId()]);
                foreach ($ruleIds as $key => $id) {
                    dashboardRootCheck(str_contains($html, 'data-policy-rule-id="' . $id . '"') === in_array((int)$holons[$key]->getId(), $scopeIds, true),
                        'Policy must reproduce dashboard range: ' . $rootKey . '/' . $range . '/' . $key);
                }
                dashboardRootCheck(str_contains($html, '&quot;isDashboardNavigation&quot;:true'), 'Explicit dashboard navigation must reach the browser.');
            }
        }
        echo "dashboard_scope_roots_integration_test: OK (policy renders all nine root/range combinations)\n";
        return;
    }
    $documentsByHolon = [];
    foreach ($holons + ['organization' => null] as $key => $holon) {
        $document = dashboardRootFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $holon?->getId(),
            'IDuser' => $admin->getId(), 'title' => 'Root fixture ' . $key, 'documenttype' => 'html', 'active' => 1]);
        $documentsByHolon[$key] = (int)$document->getId();
        dashboardRootFixture(History::class, ['IDorganization' => $oid, 'IDuser' => $admin->getId(),
            'target_type' => $holon ? 'holon' : 'organization', 'target_id' => $holon?->getId() ?? $oid,
            'action' => 'update', 'content' => 'Root fixture ' . $key, 'active' => 1]);
    }
    $expected = [
        'holon' => ['contextual' => ['role'], 'children' => ['role', 'child'], 'descendants' => ['role', 'child', 'deep']],
        'parent' => ['contextual' => ['circle'], 'children' => ['circle', 'role', 'peer'], 'descendants' => ['circle', 'role', 'child', 'deep', 'peer']],
        'organization' => ['contextual' => ['root', 'organization'], 'children' => ['root', 'circle', 'outside', 'organization'], 'descendants' => ['root', 'circle', 'role', 'child', 'deep', 'peer', 'outside', 'organization']],
    ];
    $layouts = [];
    foreach ($expected as $rootKey => $scopes) {
        $resolved = $role->getDashboardScopeRoot($rootKey, $fixtureRoot);
        foreach ($scopes as $scope => $keys) {
            $ids = match ($scope) {
                'children' => omoApiGetDirectChildScopeHolonIds($resolved),
                'descendants' => omoApiGetDescendantHolonIds($resolved),
                default => [(int)$resolved->getId()],
            };
            $expectedIds = array_map(static fn($key) => (int)$holons[$key]->getId(), array_values(array_diff($keys, ['organization'])));
            sort($ids); sort($expectedIds);
            dashboardRootCheck($ids === $expectedIds, 'Wrong holon set: ' . $rootKey . '/' . $scope);
            $history = History::fetchHolonFeedPage($oid, $resolved->getId(), 100, 0, $rootKey === 'organization', $ids);
            foreach ($history['items'] as $item) {
                dashboardRootCheck(in_array((int)$item['targetId'], $ids, true) || ($rootKey === 'organization' && $item['targetType'] === 'organization'), 'History leaked beyond the selected scope.');
            }
            $layouts[] = ['id' => $rootKey . '-' . $scope, 'type' => 'documents', 'row' => count($layouts), 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 1,
                'settings' => ['scopeRoot' => $rootKey, 'scope' => $scope]];
        }
    }
    dashboardRootCheck($fixtureRoot->getDashboardScopeRoot('parent', $fixtureRoot) === $fixtureRoot, 'The organization parent must remain the organization.');
    $foreignRoot = new Holon();
    $foreignRoot->hydrateFromDatabaseRow(['id' => 999999999], true);
    dashboardRootCheck($role->getDashboardScopeRoot('organization', $foreignRoot) === null, 'A foreign organization root must be rejected.');
    foreach (UserHolon::getDashboardModuleCatalog() as $type => $definition) {
        if ($type === 'documents' || empty($definition['settings']['scope'])) continue;
        $layouts[] = ['id' => 'smoke-' . $type, 'type' => $type, 'row' => count($layouts), 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 1,
            'settings' => ['scopeRoot' => 'parent', 'scope' => 'descendants']];
    }
    $role->setDashboardDefaultLayout($layouts);
    dashboardRootCheck(!empty($role->save()['status']), 'Cannot save dashboard layout.');
    $reloadedRole = new Holon(); $reloadedRole->load($role->getId());
    dashboardRootCheck($reloadedRole->getDashboardDefaultLayout()[3]['settings']['scopeRoot'] === 'parent', 'Root selection must survive storage.');
    // Render an instance whose direct template inherits its dashboard from a parent template.
    $simpleTemplate = $makeHolon('Simple role template', 1, $fixtureRoot);
    $simpleTemplate->set('visible', false);
    $simpleTemplate->set('templatename', 'Simple role');
    dashboardRootCheck(!empty($simpleTemplate->save()['status']), 'Cannot save base template.');
    $memoryTemplate = $makeHolon('Memory role template', 1, $fixtureRoot);
    $memoryTemplate->set('visible', false);
    $memoryTemplate->set('templatename', 'Memory role');
    $memoryTemplate->set('IDholon_template', $simpleTemplate->getId());
    dashboardRootCheck(!empty($memoryTemplate->save()['status']), 'Cannot save child template.');
    $role->clearDashboardDefaultLayout();
    $role->set('IDholon_template', $memoryTemplate->getId());
    dashboardRootCheck(!empty($role->save()['status']), 'Cannot attach the inherited template.');
    $fixtureOrganization->setDashboardTemplateDefaultLayout($memoryTemplate->getDashboardDirectTemplateLayoutKey(), $layouts);
    dashboardRootCheck(!empty($fixtureOrganization->save()['status']), 'Cannot save parent template dashboard.');
    $_GET = ['oid' => $oid, 'cid' => (int)$role->getId()];
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/omo/api/personal_space.php';
    ob_start();
    require dirname(__DIR__) . '/omo/api/personal_space.php';
    $html = ob_get_clean();
    dashboardRootCheck(!preg_match('/Warning:|Fatal error:|Deprecated:/', $html), 'PHP diagnostics during dashboard rendering.');
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    $dashboardScript = $xpath->query('//script[contains(@src, "/omo/assets/js/personal-space-dashboard.js")]')->item(0);
    dashboardRootCheck($dashboardScript !== null && $dashboardScript->getAttribute('src') === commonAssetUrl('/omo/assets/js/personal-space-dashboard.js'), 'The configurator script URL must invalidate cached older versions.');
    $cards = $xpath->query('//section[@data-omo-dashboard-module="documents"]');
    dashboardRootCheck($cards->length === 9, 'The dashboard must render all nine document configurations.');
    $index = 0;
    foreach ($expected as $rootKey => $scopes) foreach ($scopes as $scope => $keys) {
        $card = $cards->item($index++);
        foreach ($documentsByHolon as $key => $id) {
            dashboardRootCheck(str_contains($card->textContent, 'Root fixture ' . $key) === in_array($key, $keys, true), 'Wrong document content: ' . $rootKey . '/' . $scope . '/' . $key);
        }
        $link = $xpath->query('.//*[@data-omo-personal-space-route-token]', $card)->item(0);
        dashboardRootCheck((int)$link->getAttribute('data-omo-personal-space-context-holon-id') === (int)$role->getDashboardScopeRoot($rootKey, $fixtureRoot)->getId(), 'Open link must use the selected root.');
        dashboardRootCheck($link->getAttribute('data-omo-personal-space-forced-scope') === $scope, 'Open link must retain the selected scope.');
    }
    dashboardRootCheck($xpath->query('//section[@data-omo-dashboard-module]')->length === count($layouts), 'Every scoped module must render.');
    dashboardRootCheck($xpath->query('//section[@data-omo-dashboard-module]//*[@data-omo-personal-space-route-token]')->length === count($layouts), 'Every scoped module must be enabled during the test.');
    dashboardRootCheck($xpath->query('//*[@data-omo-dashboard-editor-reset="organization_template_reset"]')->length === 0, 'An inherited parent dashboard must not offer a reset of the unconfigured direct template.');
    if (($argv[1] ?? '') === '--html') file_put_contents(dirname(__DIR__) . '/tmp/dashboard-scope-roots.html', $html);
    echo "dashboard_scope_roots_integration_test: OK (nine combinations, persisted settings, inherited template rendering, history and links)\n";
} finally {
    $pdo->rollBack();
}
