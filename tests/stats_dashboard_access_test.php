<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') === '--render') {
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    require_once dirname(__DIR__) . '/shared_functions.php';
    $_SESSION = [];
    $role = $argv[2] ?? 'guest';
    if ($role !== 'guest') {
        $users = new \dbObject\ArrayUser();
        $users->loadHydrated(['where' => [['field' => 'siteadmin', 'value' => $role === 'admin' ? 1 : 0], ['field' => 'active', 'value' => 1]], 'limit' => 1]);
        if (!count($users)) { throw new RuntimeException('Local active users with and without site admin role are required.'); }
        $_SESSION['currentUser'] = (int)$users[0]->getId();
        $_SESSION['auth_security_version'] = (int)$users[0]->get('security_version');
        $_SESSION['currentOrganization'] = 1;
    }
    // No isSiteAdminModeEnabled flag: role alone must suffice.
    $_GET = ['range' => (int)($argv[3] ?? 7), 'organization' => (int)($argv[4] ?? 0)];
    ob_start();
    require dirname(__DIR__) . '/common/admin/stats.php';
    $html = ob_get_clean();
    echo json_encode(['status' => http_response_code() ?: 200, 'html' => $html], JSON_THROW_ON_ERROR);
    exit;
}
foreach (['guest', 'member', 'admin'] as $role) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --render ' . escapeshellarg($role);
    exec($command, $output, $code);
    if ($code !== 0) { throw new RuntimeException('Rendering failed for ' . $role); }
    $result = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
    $output = [];
    if ($result['status'] !== ($role === 'admin' ? 200 : 403)) { throw new RuntimeException('Incorrect access for ' . $role); }
    $hasDashboard = str_contains($result['html'], 'name="kind"');
    if ($hasDashboard !== ($role === 'admin')) { throw new RuntimeException('Dashboard data exposed to incorrect role.'); }
    if (str_contains($result['html'], 'id="caldav-monitoring"') !== ($role === 'admin')) {
        throw new RuntimeException('CalDAV contact monitoring exposed to incorrect role.');
    }
    if (preg_match('/Fatal error|Warning:|Deprecated:/', $result['html'])) { throw new RuntimeException('PHP diagnostics in page.'); }
}
echo "stats_dashboard_access_test: OK (guest/member denied; site administrator accepted without elevated mode)\n";
