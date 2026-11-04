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
$mergeParents = str_repeat('0', 40) . ' ' . str_repeat('1', 40);
$records = str_repeat('a', 40) . "\0Merge pull request #529 from owner/Beta\0Feature description\0" . $mergeParents . "\0"
    . str_repeat('b', 40) . "\0Correct a bug (#530)\0Bug details\0" . str_repeat('2', 40) . "\0";
$entries = siteUpdateChangelogParseGit($records);
updateCheck(count($entries) === 2 && $entries[0]['number'] === 529 && $entries[1]['number'] === 530, 'Merge and squash parsing');
updateCheck($entries[0]['description'] === 'Feature description', 'Preserve descriptions');
updateCheck(count($entries[0]['parents']) === 2 && count($entries[1]['parents']) === 1, 'Identify real merges independently of their subject');
updateCheck(siteUpdateChangelogParseJournal("--- a/NOUVEAUTES.md\n+++ b/NOUVEAUTES.md\n@@ -1 +1 @@\n-old note\n+- New note\n unchanged") === '- New note', 'Journal contains only added lines');
$html = siteUpdateNewsRender("## Corrections\n\n- Fix **important**\n\n<script>alert(1)</script>\n\n[bad](javascript:alert(1))");
updateCheck(str_contains($html, '<strong>important</strong>') && !str_contains($html, '<script') && !str_contains($html, 'href="javascript:'), 'Safe Markdown rendering');
$tutorialNote = 'Tutoriels publics (2026-11-03) : styles des missions isoles.';
$syncNote = 'Synchronisation / nouveautes (2026-11-03) : nouvelles notifications.';
$journalNotes = "- $tutorialNote\n- $syncNote";
$preparedMarkdown = "## Une belle presentation\n\n**Bienvenue** dans cette version.\n\n"
    . "Un paragraphe volontairement repete.\n\nUn paragraphe volontairement repete.\n\n- Une nouveaute expliquee simplement.\n";
$customMerge = ['title' => 'Tutoriels : une navigation plus simple', 'description' => $preparedMarkdown,
    'parents' => explode(' ', $mergeParents), 'number' => 0];
updateCheck(siteUpdateChangelogPreparedDescription($customMerge) === trim($preparedMarkdown), 'Read a customized merge body without a PR number or API access');
updateCheck(siteUpdateChangelogPreparedDescription($customMerge, ['title' => 'Beta', 'body' => null]) === trim($preparedMarkdown), 'PR 532 regression: empty PR description does not hide the prepared merge body');
updateCheck(siteUpdateChangelogPreparedDescription($customMerge, ['title' => 'Beta', 'body' => 'Older PR description']) === trim($preparedMarkdown), 'The final customized merge message takes priority');
$automaticMerge = array_merge($customMerge, ['title' => 'Merge pull request #532 from owner/Beta', 'description' => 'Beta']);
updateCheck(siteUpdateChangelogPreparedDescription($automaticMerge, ['title' => 'Beta', 'body' => null]) === '', 'An automatic PR-title-only merge is not a presentation');
updateCheck(siteUpdateChangelogPreparedDescription($automaticMerge, ['title' => 'Beta', 'body' => $preparedMarkdown]) === trim($preparedMarkdown), 'Read the PR description when the merge message is automatic');
updateCheck(siteUpdateChangelogPreparedDescription(array_merge($automaticMerge, ['description' => $preparedMarkdown])) === trim($preparedMarkdown), 'Recognize prepared Markdown with a standard merge title even offline');
updateCheck(siteUpdateChangelogPreparedDescription(array_merge($customMerge, ['parents' => [str_repeat('0', 40)]])) === '', 'Ordinary commit bodies stay excluded');
$preparedEntries = [['title' => 'Technical merge subject', 'description' => 'Technical Git body', 'preparedDescription' => $preparedMarkdown]];
$content = siteUpdateChangelogContent($preparedEntries, $journalNotes);
updateCheck($content['description'] === trim($preparedMarkdown) && $content['journal'] === $journalNotes, 'Keep prepared Markdown and journal separate');
updateCheck($content['hasPreparedDescription'] === true, 'Prepared text enables notifications');
$preparedHtml = siteUpdateAdminChangelogHtml($content);
updateCheck(str_contains($preparedHtml, '<strong>Bienvenue</strong>') && substr_count($preparedHtml, 'Un paragraphe volontairement repete.') === 2, 'Preserve authored formatting and intentional repetitions');
updateCheck(str_contains($preparedHtml, '<details class="generic-accordion generic-help-text">') && !str_contains($preparedHtml, '<details open'), 'Journal is compact and collapsed by default');
updateCheck(strpos($preparedHtml, 'Bienvenue') < strpos($preparedHtml, '<details'), 'Prepared introduction comes first');
updateCheck(!str_contains($preparedHtml, 'Technical merge') && !str_contains($preparedHtml, 'Technical Git body'), 'Do not display merge metadata');
$fallback = siteUpdateChangelogContent([['title' => 'Merge pull request #530', 'description' => 'Not editorial']], $journalNotes);
updateCheck($fallback === ['description' => $journalNotes, 'journal' => '', 'hasPreparedDescription' => false], 'Journal replaces a missing PR body without enabling notifications');
updateCheck(!str_contains(siteUpdateAdminChangelogHtml($fallback), '<details'), 'Fallback journal is expanded directly');
updateCheck(siteUpdateChangelogContent([['preparedDescription' => '<!-- template comment -->']], $journalNotes) === $fallback, 'Comment-only PR bodies are not prepared presentations');
$withoutJournal = siteUpdateChangelogContent($preparedEntries, '');
updateCheck($withoutJournal['journal'] === '' && $withoutJournal['hasPreparedDescription'] === true, 'Prepared text enables notifications even without a journal');
updateCheck(siteUpdateChangelogContent([], '')['description'] === siteUpdateNewsT('empty'), 'No technical fallback when no source has notes');
$legacyDescription = "## Journal des nouveautes\n\n$journalNotes\n\n"
    . "## Descriptions GitHub et historique Git\n\n## Update tutoriel public access and MAJ (#530)\n\n"
    . $preparedMarkdown . "\n\n## Update tutoriel public access and MAJ\n";
$legacyContent = siteUpdateNewsLegacyContent($legacyDescription);
updateCheck($legacyContent['description'] === trim($preparedMarkdown) && $legacyContent['journal'] === $journalNotes, 'Existing saved news use prepared text first');
updateCheck(str_contains(siteUpdateNewsRender($legacyDescription), '<details class="generic-accordion generic-help-text">'), 'Legacy saved news also use the collapsed journal');
$legacyFallback = "## Journal des nouveautes\n\n$journalNotes\n\n## Descriptions GitHub et historique Git\n\n## Update (#530)\n\nUpdate\n\n## Update\n";
updateCheck(siteUpdateNewsLegacyContent($legacyFallback) === ['description' => $journalNotes, 'journal' => ''], 'Existing merge-title-only news show the journal');
$legacyGitFallback = "## Journal des nouveautes\n\n$journalNotes\n\n## Descriptions GitHub et historique Git\n\n## Merge pull request #530 from owner/Beta (#530)\n\nA technical merge description\n";
updateCheck(siteUpdateNewsLegacyContent($legacyGitFallback) === ['description' => $journalNotes, 'journal' => ''], 'Unresolved legacy merge messages are not prepared presentations');
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
    $deliveryBranch = $git(['symbolic-ref', '--short', 'HEAD']);
    $git(['checkout', '--quiet', '-b', 'internal-feature']);
    file_put_contents($fixture . '/NOUVEAUTES.md', "# Journal\n\n- New feature for users\n");
    $git(['add', 'NOUVEAUTES.md']);
    $git(['commit', '--quiet', '-m', 'Internal feature (#999)', '-m', 'Internal technical details']);
    $git(['checkout', '--quiet', $deliveryBranch]);
    $git(['merge', '--quiet', '--no-ff', 'internal-feature', '-m', 'Merge pull request #529 from owner/Beta', '-m', 'Automatic PR title']);
    $git(['commit', '--quiet', '--allow-empty', '-m', 'Direct correction', '-m', 'Preserved details']);
    $toGit = $git(['rev-parse', 'HEAD']);
    $context = ['repoRoot' => $fixture, 'remote' => 'origin'];
    $changelog = siteUpdateAdminGetChangelog($context, $fromGit, $toGit);
    updateCheck(count($changelog['entries']) === 2 && $changelog['fallback'], 'Full revision range and API fallback');
    updateCheck(!in_array(999, array_column($changelog['entries'], 'number'), true), 'Use delivery PRs rather than nested implementation PRs');
    updateCheck(!str_contains($changelog['description'], 'Bug fixes') && !str_contains($changelog['description'], 'Preserved details'), 'Git metadata never replaces the journal');
    updateCheck(!str_contains($changelog['description'], 'Already installed'), 'Exclude installed changes');
    updateCheck(str_contains($changelog['description'], '- New feature for users'), 'Include new release notes');
    updateCheck($changelog['hasPreparedDescription'] === false, 'A technical-only update stays silent');
    updateCheck(siteUpdateAdminGetChangelog($context, $fromGit, $toGit) === $changelog, 'Cached description');
    updateCheck(siteUpdateAdminGetChangelog($context, $toGit, $toGit)['entries'] === [], 'No changes for same revision');
    $git(['checkout', '--quiet', '-b', 'next-delivery']);
    file_put_contents($fixture . '/NOUVEAUTES.md', "# Journal\n\n- Latest technical note\n\n- New feature for users\n");
    $git(['add', 'NOUVEAUTES.md']);
    $git(['commit', '--quiet', '-m', 'Internal implementation detail']);
    $git(['checkout', '--quiet', $deliveryBranch]);
    $git(['merge', '--quiet', '--no-ff', 'next-delivery', '-m', 'Tutoriels : une navigation plus simple', '-m', $preparedMarkdown]);
    $customRevision = $git(['rev-parse', 'HEAD']);
    $customChangelog = siteUpdateAdminGetChangelog($context, $toGit, $customRevision);
    updateCheck($customChangelog['description'] === trim($preparedMarkdown) && $customChangelog['journal'] === '- Latest technical note', 'Real customized merge reads the prepared body without GitHub');
    updateCheck($customChangelog['hasPreparedDescription'] === true, 'A customized merge enables notifications');
    updateCheck($customChangelog['entries'][0]['number'] === 0 && count($customChangelog['entries']) === 1, 'No numbered subject or implementation commits are needed');
    $recovered = siteUpdateAdminRecoverNewsContent($context, $toGit, $customRevision, '- Latest technical note', '');
    updateCheck($recovered['description'] === trim($preparedMarkdown) && $recovered['journal'] === '- Latest technical note', 'Recover an already published journal-only news from immutable Git history');
    updateCheck(siteUpdateAdminRecoverNewsContent($context, $toGit, $customRevision, 'Existing editorial snapshot', '') ===
        ['description' => 'Existing editorial snapshot', 'journal' => ''], 'Never replace an existing editorial snapshot');
    updateCheck(siteUpdateAdminRecoverNewsContent($context, $toGit, $customRevision, 'Prepared snapshot', 'Archived journal') ===
        ['description' => 'Prepared snapshot', 'journal' => 'Archived journal'], 'Keep already separated snapshots untouched');
} finally { $removeFixture($fixture); }

// All writes below are confined to temporary tables on this PDO connection.
$pdo = DbObject::getPdo();
executeSqlMigrationStatements($pdo, file_get_contents(dirname(__DIR__) . '/sql/fixtures/site-update-news.sql'));
executeSqlMigrationStatements($pdo, str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE IF NOT EXISTS',
    file_get_contents(dirname(__DIR__) . '/sql/2026-11-03-01-site-update-news.sql')));
executeSqlMigrationStatements($pdo, file_get_contents(dirname(__DIR__) . '/sql/2026-11-03-02-site-update-news-journal.sql'));
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
$news = SiteUpdateNews::publish($from, $to, 'New features', "## Changes\n\nBug fixed", $journalNotes);
updateCheck((int)$news->getId() > 0, 'News published');
updateCheck($news->get('journal') === $journalNotes, 'Store the optional journal separately');
$inbox = Notification::getInboxForUser($users['real'], 123);
updateCheck(count($inbox) === 1 && $inbox[0]->get('event_key') === 'site_update_installed', 'Personal notification created');
updateCheck(count(Notification::getInboxForUser($users['real'], 456)) === 1, 'Visible across organizations');
updateCheck(count(Notification::getInboxForUser($users['roleplay'], 123)) === 0, 'No character notification');
$again = SiteUpdateNews::publish($from, $to, 'Retry', 'Different content');
updateCheck($again->getId() === $news->getId() && count(Notification::getInboxForUser($users['real'], 123)) === 1, 'No duplicate on retry');
updateCheck($again->get('description') === "## Changes\n\nBug fixed", 'Published snapshot remains immutable');
updateCheck($again->get('journal') === $journalNotes, 'Retry preserves the stored journal');

$silentRevision = str_repeat('e', 40);
$silent = SiteUpdateNews::publish($to, $silentRevision, 'Minor technical update',
    $fallback['description'], $fallback['journal'], $fallback['hasPreparedDescription']);
updateCheck((int)$silent->getId() > 0 && $silent->get('description') === $journalNotes, 'Silent updates still publish their technical news');
updateCheck(count(Notification::getInboxForUser($users['real'], 123)) === 1, 'No notification for a journal-only update');
$silentRetry = SiteUpdateNews::publish($to, $silentRevision, 'Retry', 'Now with some text', '', true);
updateCheck($silentRetry->getId() === $silent->getId() && count(Notification::getInboxForUser($users['real'], 123)) === 1, 'Retry does not notify an already silent news');
$emptyContent = siteUpdateChangelogContent([], '');
SiteUpdateNews::publish($to, str_repeat('f', 40), 'No release notes',
    $emptyContent['description'], $emptyContent['journal'], $emptyContent['hasPreparedDescription']);
updateCheck(count(Notification::getInboxForUser($users['real'], 123)) === 1, 'No notification without any prepared text or journal');
SiteUpdateNews::publish($to, str_repeat('9', 40), 'Prepared release without journal',
    $withoutJournal['description'], $withoutJournal['journal'], $withoutJournal['hasPreparedDescription']);
updateCheck(count(Notification::getInboxForUser($users['real'], 123)) === 2, 'Prepared text alone still notifies');

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

// Exercise the actual popup endpoint with a separately stored presentation and journal.
$displayNews = new SiteUpdateNews();
$displayNews->set('revision', str_repeat('d', 40));
$displayNews->set('previous_revision', $to);
$displayNews->set('title', 'Nouveautes du site - 03.11.2026');
$displayNews->set('description', $preparedMarkdown);
$displayNews->set('journal', $journalNotes);
updateCheck(!empty($displayNews->save()['status']), 'Create display fixture');
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
updateCheck(str_contains($popupHtml, '<details') && str_contains($popupHtml, '<strong>Bienvenue</strong>'), 'Existing news endpoint uses prepared text and collapsed journal');
file_put_contents(dirname(__DIR__) . '/tmp/site-update-news-rendered.html', $popupHtml);
file_put_contents(dirname(__DIR__) . '/tmp/site-update-news-fallback.html', siteUpdateNewsRender($fallback['description'], $fallback['journal']));
echo "site_update_news_test: OK\n";
