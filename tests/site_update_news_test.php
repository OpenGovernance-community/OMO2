<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/includes/site_update_admin.php';
require_once dirname(__DIR__) . '/db/migrations.php';

use dbObject\{DbObject, SiteUpdateNews, Notification, User};

function updateCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
updateCheck(PHP_VERSION_ID >= 80500, 'Use PHP 8.5.');
updateCheck(siteUpdateChangelogRepository('https://github.com/OpenGovernance-community/OMO2.git') === 'OpenGovernance-community/OMO2', 'HTTPS remote');
updateCheck(siteUpdateChangelogRepository('git@github.com:owner/repo.git') === 'owner/repo', 'SSH remote');
updateCheck(siteUpdateChangelogRepository('https://github.com.evil.test/owner/repo') === null, 'Reject other API hosts');
$records = str_repeat('a', 40) . "\0Merge pull request #529 from owner/Beta\0Feature description\0"
    . str_repeat('b', 40) . "\0Correct a bug (#530)\0Bug details\0";
$entries = siteUpdateChangelogParseGit($records);
updateCheck(count($entries) === 2 && $entries[0]['number'] === 529 && $entries[1]['number'] === 530, 'Merge and squash parsing');
updateCheck($entries[0]['description'] === 'Feature description', 'Preserve descriptions');
updateCheck(siteUpdateChangelogParseJournal("--- a/NOUVEAUTES.md\n+++ b/NOUVEAUTES.md\n@@ -1 +1 @@\n-old note\n+- New note\n unchanged") === '- New note', 'Journal contains only added lines');
$html = siteUpdateNewsRender("## Corrections\n\n- Fix **important**\n\n<script>alert(1)</script>\n\n[bad](javascript:alert(1))");
updateCheck(str_contains($html, '<strong>important</strong>') && !str_contains($html, '<script') && !str_contains($html, 'href="javascript:'), 'Safe Markdown rendering');
$hostileTitle = siteUpdateNewsRender(siteUpdateChangelogMarkdown([['title' => '<img src=x onerror=alert(1)> [bad](javascript:alert(1))', 'description' => '', 'number' => 2]]));
updateCheck(!str_contains($hostileTitle, '<img') && !str_contains($hostileTitle, '<a'), 'Titles must remain literal text');

$tutorialNote = 'Tutoriels publics (2026-11-03) : styles des missions isoles.';
$syncNote = 'Synchronisation / nouveautes (2026-11-03) : nouvelles notifications.';
$repeatedNotes = "## Journal des nouveautes\n\n- $tutorialNote\n- $syncNote\n\n"
    . "## Descriptions GitHub et historique Git\n\n## Update tutoriel public access and MAJ (#530)\n\n"
    . "$tutorialNote\n\n- $syncNote\n\n## Update tutoriel public access and MAJ\n";
$cleanHtml = siteUpdateNewsRender($repeatedNotes);
updateCheck(substr_count($cleanHtml, $tutorialNote) === 1 && substr_count($cleanHtml, $syncNote) === 1, 'Journal and PR notes appear only once in saved news');
updateCheck(substr_count($cleanHtml, '<li>') === 2, 'Both notes retain their list markers');
updateCheck(!str_contains($cleanHtml, 'Update tutoriel') && !str_contains($cleanHtml, 'Descriptions GitHub'), 'Remove headings whose descriptions were redundant');
$additionalHtml = siteUpdateNewsRender($repeatedNotes . "\n\n## Other correction (#531)\n\nIndependent fix.\n");
updateCheck(str_contains($additionalHtml, 'Other correction') && str_contains($additionalHtml, 'Independent fix.'), 'Keep information absent from the journal');
$listHtml = siteUpdateNewsRender("## Release (#530)\n\n$tutorialNote\n\n- $syncNote\n\n## Release\n");
updateCheck(substr_count($listHtml, '<li>') === 2 && substr_count($listHtml, '<h2>') === 1, 'Repair a copied journal list and remove the bare commit title');
$linksHtml = siteUpdateNewsRender("- [Documentation](https://example.test/one)\n\n- [Documentation](https://example.test/two)");
updateCheck(substr_count($linksHtml, '<li>') === 2, 'Different link destinations are distinct information');
$nestedHtml = siteUpdateNewsRender("## Details\n\n- Main note\n  - Nested note\n\n```text\nPreserved code\n```");
updateCheck(substr_count($nestedHtml, '<li>') === 2 && str_contains($nestedHtml, 'Preserved code'), 'Preserve nested lists and code content');

$fixture = sys_get_temp_dir() . '/site-update-git-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);
$git = static function (array $arguments) use ($fixture): string {
    $output = siteUpdateAdminRunCommand(array_merge(['git'], $arguments), $fixture, $exitCode);
    updateCheck($exitCode === 0, 'Git fixture: ' . $output);
    return $output;
};
$removeFixture = static function (string $path) use (&$removeFixture): void {
    if (!is_dir($path) || is_link($path)) { unlink($path); return; }
    foreach (scandir($path) as $name) {
        if ($name !== '.' && $name !== '..') { $removeFixture($path . '/' . $name); }
    }
    rmdir($path);
};
try {
    $git(['init', '--quiet']);
    $git(['config', 'user.name', 'Update test']);
    $git(['config', 'user.email', 'test@example.invalid']);
    $git(['remote', 'add', 'origin', 'https://example.invalid/owner/repo.git']);
    $git(['commit', '--quiet', '--allow-empty', '-m', 'Already installed']);
    $fromGit = $git(['rev-parse', 'HEAD']);
    file_put_contents($fixture . '/NOUVEAUTES.md', "# Journal\n\n- New feature for users\n");
    $git(['add', 'NOUVEAUTES.md']);
    $git(['commit', '--quiet', '--allow-empty', '-m', 'Merge pull request #529 from owner/Beta', '-m', "Bug fixes\n\nNew features"]);
    $git(['commit', '--quiet', '--allow-empty', '-m', 'Direct correction', '-m', 'Preserved details']);
    $toGit = $git(['rev-parse', 'HEAD']);
    $context = ['repoRoot' => $fixture, 'remote' => 'origin'];
    $changelog = siteUpdateAdminGetChangelog($context, $fromGit, $toGit);
    updateCheck(count($changelog['entries']) === 2 && $changelog['fallback'], 'Full revision range and API fallback');
    updateCheck(str_contains($changelog['description'], "Bug fixes\n\nNew features") && str_contains($changelog['description'], 'Preserved details'), 'Preserve full Git bodies');
    updateCheck(!str_contains($changelog['description'], 'Already installed'), 'Exclude installed changes');
    updateCheck(str_contains($changelog['description'], '- New feature for users'), 'Include new release notes');
    updateCheck(siteUpdateAdminGetChangelog($context, $fromGit, $toGit) === $changelog, 'Cached description');
    updateCheck(siteUpdateAdminGetChangelog($context, $toGit, $toGit)['entries'] === [], 'No changes for same revision');
} finally { $removeFixture($fixture); }

// All writes below are confined to temporary tables on this PDO connection.
$pdo = DbObject::getPdo();
executeSqlMigrationStatements($pdo, file_get_contents(dirname(__DIR__) . '/sql/fixtures/site-update-news.sql'));
executeSqlMigrationStatements($pdo, str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE IF NOT EXISTS',
    file_get_contents(dirname(__DIR__) . '/sql/2026-11-03-01-site-update-news.sql')));
$users = [];
foreach (['real', 'inactive', 'roleplay', 'historical'] as $type) {
    $user = new User();
    $user->set('email', $type . '@example.invalid');
    $user->set('active', $type !== 'inactive');
    if ($type === 'roleplay') { $user->set('parameters', ['roleplay_character' => true]); }
    if ($type === 'historical') { $user->set('parameters', ['historical_placeholder' => true]); }
    updateCheck(!empty($user->save()['status']), 'Create test user');
    $users[$type] = (int)$user->getId();
}
updateCheck(User::getSiteUpdateRecipientIds() === [$users['real']], 'Only active real users receive news');
$from = str_repeat('a', 40);
$to = str_repeat('b', 40);
$news = SiteUpdateNews::publish($from, $to, 'New features', "## Changes\n\nBug fixed");
updateCheck((int)$news->getId() > 0, 'News published');
$inbox = Notification::getInboxForUser($users['real'], 123);
updateCheck(count($inbox) === 1 && $inbox[0]->get('event_key') === 'site_update_installed', 'Personal notification created');
updateCheck(count(Notification::getInboxForUser($users['real'], 456)) === 1, 'Visible across organizations');
updateCheck(count(Notification::getInboxForUser($users['roleplay'], 123)) === 0, 'No character notification');
$again = SiteUpdateNews::publish($from, $to, 'Retry', 'Different content');
updateCheck($again->getId() === $news->getId() && count(Notification::getInboxForUser($users['real'], 123)) === 1, 'No duplicate on retry');
updateCheck($again->get('description') === "## Changes\n\nBug fixed", 'Published snapshot remains immutable');

// Force a notification collision after the news insert: the whole publish must roll back.
$failureRevision = str_repeat('c', 40);
updateCheck(Notification::createForUser($users['real'], 0, 'site_update_installed', 'site-update:' . $failureRevision, 'Collision') !== null, 'Failure fixture');
try {
    SiteUpdateNews::publish($to, $failureRevision, 'Must roll back', 'Unpublished');
    throw new LogicException('Expected notification failure');
} catch (RuntimeException $error) {
    updateCheck(str_contains($error->getMessage(), 'notifications'), 'Expected notification error');
}
$missing = new SiteUpdateNews();
updateCheck(!$missing->load([['revision', $failureRevision]]), 'No published news after notification failure');

// Exercise the actual popup endpoint with a legacy, already stored description.
$displayNews = new SiteUpdateNews();
$displayNews->set('revision', str_repeat('d', 40));
$displayNews->set('previous_revision', $to);
$displayNews->set('title', 'Nouveautes du site - 03.11.2026');
$displayNews->set('description', $repeatedNotes);
updateCheck(!empty($displayNews->save()['status']), 'Create legacy display fixture');
$viewer = new User();
updateCheck($viewer->load($users['real']), 'Load popup viewer');
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
$previousSession = $_SESSION;
$previousGet = $_GET;
$_SESSION = ['currentUser' => $users['real'], 'auth_security_version' => (int)$viewer->get('security_version')];
$_GET = ['id' => $displayNews->getId()];
ob_start();
require dirname(__DIR__) . '/omo/api/notifications/site_update_news.php';
$popupHtml = ob_get_clean();
$_SESSION = $previousSession;
$_GET = $previousGet;
updateCheck(str_contains($popupHtml, 'data-topbar-modal-title="Nouveautes du site - 03.11.2026"'), 'Provide the title to the popup header');
updateCheck(!str_contains(strip_tags($popupHtml), 'Nouveautes du site - 03.11.2026'), 'No repeated news title inside the body');
updateCheck(substr_count($popupHtml, $tutorialNote) === 1 && substr_count($popupHtml, '<li>') === 2, 'Existing news endpoint uses the shared cleanup');
file_put_contents(dirname(__DIR__) . '/tmp/site-update-news-rendered.html', $popupHtml);
echo "site_update_news_test: OK\n";
