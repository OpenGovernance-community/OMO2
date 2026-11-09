<?php
require_once __DIR__ . '/../omo/api/parameters/translation.php';

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
    for ($index = 0; $index + 3 < count($fields); $index += 4) {
        $commit = trim($fields[$index]);
        if (!preg_match('/^[a-f0-9]{40,64}$/', $commit)) { continue; }
        $title = trim($fields[$index + 1]);
        $number = 0;
        if (preg_match('/^Merge pull request #(\d+)\b|\(#(\d+)\)\s*$/', $title, $matches)) {
            $number = (int)($matches[1] !== '' ? $matches[1] : ($matches[2] ?? 0));
        }
        $parents = preg_split('/\s+/', trim($fields[$index + 3]), -1, PREG_SPLIT_NO_EMPTY);
        $entries[] = ['commit' => $commit, 'title' => $title, 'description' => trim($fields[$index + 2]),
            'number' => $number, 'parents' => $parents];
    }
    return $entries;
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

function siteUpdateChangelogContent(array $entries, string $journal): array
{
    $presentations = [];
    foreach ($entries as $entry) {
        // Prepared text is resolved separately from technical subjects and ordinary commit bodies.
        $prepared = trim((string)($entry['preparedDescription'] ?? ''));
        if (trim((string)preg_replace('/<!--.*?-->/s', '', $prepared)) !== '') {
            $presentations[hash('sha256', $prepared)] = $prepared;
        }
    }
    return $presentations
        ? ['description' => implode("\n\n---\n\n", $presentations), 'journal' => $journal, 'hasPreparedDescription' => true]
        : ['description' => $journal !== '' ? $journal : siteUpdateNewsT('empty'), 'journal' => '', 'hasPreparedDescription' => false];
}

function siteUpdateChangelogPreparedDescription(array $entry, ?array $pull = null): string
{
    $body = trim((string)($entry['description'] ?? ''));
    $pullTitle = trim((string)($pull['title'] ?? ''));
    $hasBody = trim((string)preg_replace('/<!--.*?-->/s', '', $body)) !== '';
    if (count($entry['parents'] ?? []) > 1 && $hasBody) {
        $automaticTitle = preg_match('/^Merge (?:pull request #\d+\b|branch |remote-tracking branch |tag )/', (string)$entry['title']);
        $sameAsPullTitle = $pullTitle !== '' && $body === $pullTitle;
        $structuredBody = str_contains($body, "\n")
            && preg_match('/^(?:#{1,6}\s|[-*+]\s|\d+[.)]\s|>\s)/m', $body);
        if (!$sameAsPullTitle && (!$automaticTitle || $pullTitle !== '' || $structuredBody)) {
            // A customized merge message is the final release copy, even without a PR number.
            return $body;
        }
    }
    return trim((string)($pull['body'] ?? ''));
}

/** Read immutable local history; no fetch and no GitHub request. */
function siteUpdateAdminReadChangelog(array $context, string $from, string $to): array
{
    if (!preg_match('/^[a-f0-9]{40,64}$/', $from) || !preg_match('/^[a-f0-9]{40,64}$/', $to)) {
        throw new InvalidArgumentException(omoSiteUpdateT('copy.versions_de_mise_a_jour_invalides'));
    }
    $output = siteUpdateAdminRunCommand(['git', 'log', '--first-parent', '-z', '--format=%H%x00%s%x00%b%x00%P', $from . '..' . $to, '--'],
        $context['repoRoot'], $exitCode, true);
    if ($exitCode !== 0) { throw new RuntimeException('Impossible de lire les modifications Git.'); }
    $journalDiff = siteUpdateAdminRunCommand(['git', 'diff', '--no-ext-diff', '--no-textconv', '--unified=0', $from, $to, '--', 'NOUVEAUTES.md'],
        $context['repoRoot'], $exitCode);
    return ['entries' => siteUpdateChangelogParseGit($output),
        'journal' => $exitCode === 0 ? siteUpdateChangelogParseJournal($journalDiff) : ''];
}

/** Repair the display of a previously saved journal-only snapshot, without new notifications. */
function siteUpdateAdminRecoverNewsContent(array $context, string $from, string $to, string $description, string $journal): array
{
    $stored = ['description' => $description, 'journal' => $journal];
    if ($journal !== '' || $from === $to || !siteUpdateAdminIsExecAvailable()
        || !file_exists($context['repoRoot'] . '/.git')) { return $stored; }
    try {
        $local = siteUpdateAdminReadChangelog($context, $from, $to);
    } catch (RuntimeException | InvalidArgumentException $error) {
        return $stored;
    }
    // Never replace an existing editorial snapshot: only the exact journal fallback is eligible.
    if (trim($description) !== trim($local['journal'])) { return $stored; }
    foreach ($local['entries'] as &$entry) {
        $entry['preparedDescription'] = siteUpdateChangelogPreparedDescription($entry);
    }
    unset($entry);
    $recovered = siteUpdateChangelogContent($local['entries'], $local['journal']);
    return $recovered['journal'] !== '' ? $recovered : $stored;
}

function siteUpdateAdminGetChangelog(array $context, string $from, string $to): array
{
    if ($from === $to) { return ['entries' => [], 'description' => '', 'fallback' => false]; }
    $url = siteUpdateAdminRunCommand(['git', 'remote', 'get-url', $context['remote']], $context['repoRoot'], $exitCode);
    $repository = $exitCode === 0 ? siteUpdateChangelogRepository($url) : null;
    $cachePath = siteUpdateAdminEnsureRuntimeDir() . '/changelog-' . hash('sha256', 'v6' . $from . $to . $repository) . '.json';
    if (is_file($cachePath) && filemtime($cachePath) > time() - 300) {
        $cached = json_decode((string)file_get_contents($cachePath), true);
        if (is_array($cached) && isset($cached['entries'], $cached['description'], $cached['fallback'])) { return $cached; }
    }
    $local = siteUpdateAdminReadChangelog($context, $from, $to);
    $entries = $local['entries'];
    $pulls = [];
    $fallback = false;
    $deadline = microtime(true) + 10;
    foreach ($entries as &$entry) {
        $number = $entry['number'];
        if ($number <= 0) {
            $entry['preparedDescription'] = siteUpdateChangelogPreparedDescription($entry);
            continue;
        }
        if (!array_key_exists($number, $pulls)) {
            $pulls[$number] = $repository !== null
                ? siteUpdateChangelogRequest($repository, $number, max(0, (int)(($deadline - microtime(true)) * 1000))) : null;
        }
        $pull = $pulls[$number];
        $entry['preparedDescription'] = siteUpdateChangelogPreparedDescription($entry, $pull);
        if ($entry['preparedDescription'] === '') { $fallback = true; }
    }
    unset($entry);
    $unique = [];
    foreach ($entries as $entry) {
        $key = $entry['number'] > 0 ? 'pr:' . $entry['number'] : $entry['commit'];
        if (!isset($unique[$key])) { $unique[$key] = $entry; }
    }
    $entries = array_values($unique);
    $result = array_merge(['entries' => $entries, 'fallback' => $fallback], siteUpdateChangelogContent($entries, $local['journal']));
    // Private repositories may contain private descriptions; keep the cache outside the web root.
    file_put_contents($cachePath, json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod($cachePath, 0600);
    return $result;
}

function siteUpdateAdminChangelogHtml(array $changelog): string
{
    return siteUpdateNewsRender($changelog['description'] ?: siteUpdateNewsT('empty'), (string)($changelog['journal'] ?? ''));
}
