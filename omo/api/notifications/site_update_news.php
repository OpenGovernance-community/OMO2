<?php
require_once dirname(__DIR__, 3) . '/shared_functions.php';
require_once dirname(__DIR__, 3) . '/common/auth.php';
require_once dirname(__DIR__, 3) . '/common/translation_bundles.php';
require_once dirname(__DIR__, 3) . '/common/site_update/news.php';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store');
commonRestoreRememberedUser();
if (commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    echo htmlspecialchars(siteUpdateNewsT('login'), ENT_QUOTES, 'UTF-8');
    exit;
}
$news = new \dbObject\SiteUpdateNews();
if ((int)($_GET['id'] ?? 0) <= 0 || !$news->load((int)$_GET['id'])) {
    http_response_code(404);
    echo htmlspecialchars(siteUpdateNewsT('missing'), ENT_QUOTES, 'UTF-8');
    exit;
}
commonReleaseReadOnlySession();
$content = ['description' => (string)$news->get('description'), 'journal' => (string)$news->get('journal')];
if ($content['journal'] === '') {
    require_once dirname(__DIR__, 3) . '/includes/site_update_admin.php';
    $content = siteUpdateAdminRecoverNewsContent(['repoRoot' => siteUpdateAdminGetRepoRoot()],
        (string)$news->get('previous_revision'), (string)$news->get('revision'), $content['description'], $content['journal']);
}
echo '<article class="generic-section" data-topbar-modal-title="'
    . htmlspecialchars((string)$news->get('title'), ENT_QUOTES, 'UTF-8') . '">'
    . siteUpdateNewsRender($content['description'], $content['journal']) . '</article>';
