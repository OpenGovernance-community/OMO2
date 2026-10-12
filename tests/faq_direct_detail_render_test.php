<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{DbObject, FAQ};

function faqDirectRender(array $query): array
{
    $previousQuery = $_GET;
    $previousDirectory = getcwd();
    $_GET = $query;
    chdir(dirname(__DIR__) . '/popup');
    $before = DbObject::getPerformanceCounters();
    ob_start();
    try {
        include dirname(__DIR__) . '/popup/faq.php';
        $html = ob_get_contents();
        $after = DbObject::getPerformanceCounters();
        return ['html' => $html, 'queries' => $after['queries'] - $before['queries']];
    } finally {
        ob_end_clean();
        chdir($previousDirectory);
        $_GET = $previousQuery;
    }
}

$previousSession = $_SESSION;
$items = mcpFixtures();
try {
    $items['faq'] = mcpFixture(FAQ::class, ['question' => 'Direct FAQ ' . bin2hex(random_bytes(5)),
        'answer' => 'Direct answer', 'isactive' => 1]);
    $_SESSION = ['currentUser' => (int)$items['user']->getId(), 'currentOrganization' => (int)$items['org']->getId(),
        'auth_security_version' => (int)$items['user']->get('security_version')];
    $query = ['oid' => (int)$items['org']->getId()];
    $direct = faqDirectRender($query + ['id' => (int)$items['faq']->getId()]);
    mcpCheck(str_contains($direct['html'], 'data-faq-list-loaded="0"'), 'Direct links render a detail-only shell.');
    mcpCheck(str_contains($direct['html'], 'data-faq-initial-id="' . $items['faq']->getId() . '"'), 'The shell preserves the question ID.');
    mcpCheck(!str_contains($direct['html'], 'data-faq-item') && !str_contains($direct['html'], 'data-faq-editor-view'),
        'Direct links do not render the collection or creation form.');
    mcpCheck(!str_contains($direct['html'], 'Direct answer'), 'Question data is loaded only through the existing access-controlled detail endpoint.');

    $list = faqDirectRender($query);
    mcpCheck(str_contains($list['html'], 'data-faq-list') && str_contains($list['html'], 'data-faq-search-input'), 'The regular list is still rendered.');
    mcpCheck(str_contains($list['html'], htmlspecialchars((string)$items['faq']->get('question'))), 'The regular list contains the visible fixture.');
    mcpCheck($direct['queries'] < $list['queries'], 'Direct links must execute fewer queries than the collection.');
    echo 'faq_direct_detail_render_test: OK (direct=' . $direct['queries'] . ', list=' . $list['queries'] . " SQL queries)\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $previousSession;
}
