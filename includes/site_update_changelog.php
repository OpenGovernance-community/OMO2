<?php
require_once dirname(__DIR__) . '/common/site_update/news.php';

function siteUpdateChangelogRepository(string $url): ?string
{
    if (preg_match('~^(?:https://github\.com/|git@github\.com:|ssh://git@github\.com/)([A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?)(?:\.git)?/?$~D', trim($url), $matches)) {
        return $matches[1];
    }
    return null;
}

/** No redirects: credentials are only ever sent to api.github.com. */
function siteUpdateChangelogRequest(string $repository, int $number, int $timeoutMs): ?array
{
    if (!function_exists('curl_init') || $timeoutMs <= 0) { return null; }
    $curl = curl_init('https://api.github.com/repos/' . $repository . '/pulls/' . $number);
    $headers = ['Accept: application/vnd.github+json', 'User-Agent: OMO Site Update', 'X-GitHub-Api-Version: 2022-11-28'];
    $token = function_exists('envValue') ? trim((string)envValue('SITE_UPDATE_GITHUB_TOKEN', envValue('GITHUB_BUGREPORT_TOKEN', ''))) : '';
    if ($token !== '') { $headers[] = 'Authorization: Bearer ' . $token; }
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT_MS => min(1500, $timeoutMs), CURLOPT_TIMEOUT_MS => $timeoutMs,
        CURLOPT_HTTPHEADER => $headers, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($status !== 200 || !is_string($body)) { return null; }
    $data = json_decode($body, true);
    return is_array($data) && !empty($data['merged_at']) && (int)($data['number'] ?? 0) === $number ? $data : null;
}

function siteUpdateChangelogParseGit(string $output): array
{
    $fields = explode("\0", $output);
    $entries = [];
    for ($index = 0; $index + 2 < count($fields); $index += 3) {
        $commit = trim($fields[$index]);
        if (!preg_match('/^[a-f0-9]{40,64}$/', $commit)) { continue; }
        $title = trim($fields[$index + 1]);
        $number = 0;
        if (preg_match('/^Merge pull request #(\d+)\b|\(#(\d+)\)\s*$/', $title, $matches)) {
            $number = (int)($matches[1] !== '' ? $matches[1] : ($matches[2] ?? 0));
        }
        $entries[] = ['commit' => $commit, 'title' => $title, 'description' => trim($fields[$index + 2]), 'number' => $number];
    }
    return $entries;
}

function siteUpdateChangelogMarkdown(array $entries): string
{
    $blocks = [];
    foreach ($entries as $entry) {
        $title = preg_replace('/[\r\n]+/', ' ', (string)$entry['title']);
        $title = preg_replace('/([\\\\`*_{}\[\]<>()#+.!|~-])/', '\\\\$1', $title);
        $blocks[] = '## ' . $title . (!empty($entry['number']) ? ' (#' . (int)$entry['number'] . ')' : '')
            . "\n\n" . trim((string)$entry['description']);
    }
    return trim(implode("\n\n", $blocks));
}

function siteUpdateChangelogParseJournal(string $diff): string
{
    $lines = [];
    foreach (explode("\n", $diff) as $line) {
        if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
            $added = substr($line, 1);
            if (!preg_match('/^#\s/', $added)) { $lines[] = $added; }
        }
    }
    return trim(implode("\n", $lines));
}

function siteUpdateAdminGetChangelog(array $context, string $from, string $to): array
{
    if ($from === $to) { return ['entries' => [], 'description' => '', 'fallback' => false]; }
    $url = siteUpdateAdminRunCommand(['git', 'remote', 'get-url', $context['remote']], $context['repoRoot'], $exitCode);
    $repository = $exitCode === 0 ? siteUpdateChangelogRepository($url) : null;
    $cachePath = siteUpdateAdminEnsureRuntimeDir() . '/changelog-' . hash('sha256', 'v2' . $from . $to . $repository) . '.json';
    if (is_file($cachePath) && filemtime($cachePath) > time() - 300) {
        $cached = json_decode((string)file_get_contents($cachePath), true);
        if (is_array($cached) && isset($cached['entries'], $cached['description'], $cached['fallback'])) { return $cached; }
    }
    $output = siteUpdateAdminRunCommand(['git', 'log', '-z', '--format=%H%x00%s%x00%b', $from . '..' . $to, '--'],
        $context['repoRoot'], $exitCode, true);
    if ($exitCode !== 0) { throw new RuntimeException('Impossible de lire les modifications Git.'); }
    $entries = siteUpdateChangelogParseGit($output);
    $pulls = [];
    $fallback = false;
    $deadline = microtime(true) + 10;
    foreach ($entries as &$entry) {
        $number = $entry['number'];
        if ($number <= 0) { continue; }
        if (!array_key_exists($number, $pulls)) {
            $pulls[$number] = $repository !== null
                ? siteUpdateChangelogRequest($repository, $number, max(0, (int)(($deadline - microtime(true)) * 1000))) : null;
        }
        $pull = $pulls[$number];
        if (is_array($pull)) {
            $entry['title'] = trim((string)$pull['title']);
            if (trim((string)($pull['body'] ?? '')) !== '') {
                $entry['description'] = trim((string)$pull['body']);
            } else { $fallback = true; }
        } else { $fallback = true; }
    }
    unset($entry);
    $unique = [];
    foreach ($entries as $entry) {
        $key = $entry['number'] > 0 ? 'pr:' . $entry['number'] : $entry['commit'];
        if (!isset($unique[$key])) { $unique[$key] = $entry; }
    }
    $entries = array_values($unique);
    $journalDiff = siteUpdateAdminRunCommand(['git', 'diff', '--no-ext-diff', '--no-textconv', '--unified=0', $from, $to, '--', 'NOUVEAUTES.md'],
        $context['repoRoot'], $exitCode);
    $journal = $exitCode === 0 ? siteUpdateChangelogParseJournal($journalDiff) : '';
    $description = siteUpdateChangelogMarkdown($entries);
    if ($journal !== '') {
        $description = '## ' . siteUpdateNewsT('journal') . "\n\n" . $journal
            . "\n\n## " . siteUpdateNewsT('history') . "\n\n" . $description;
    }
    $result = ['entries' => $entries, 'description' => $description, 'fallback' => $fallback];
    // Private repositories may contain private descriptions; keep the cache outside the web root.
    file_put_contents($cachePath, json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod($cachePath, 0600);
    return $result;
}

function siteUpdateAdminChangelogHtml(array $changelog): string
{
    $html = !empty($changelog['fallback'])
        ? '<p class="generic-help-text">' . htmlspecialchars(siteUpdateNewsT('fallback'), ENT_QUOTES, 'UTF-8') . '</p>' : '';
    return $html . siteUpdateNewsRender($changelog['description'] ?: siteUpdateNewsT('empty'));
}
