<?php

function siteUpdateAdminGetRepoRoot()
{
    return dirname(__DIR__);
}

function siteUpdateAdminGetRuntimeDir()
{
    return rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'omo-site-update-' . md5(siteUpdateAdminGetRepoRoot());
}

function siteUpdateAdminEnsureRuntimeDir()
{
    $runtimeDir = siteUpdateAdminGetRuntimeDir();
    if (is_dir($runtimeDir)) {
        return $runtimeDir;
    }

    if (!@mkdir($runtimeDir, 0775, true) && !is_dir($runtimeDir)) {
        throw new RuntimeException('Impossible de preparer le dossier temporaire de mise a jour.');
    }

    return $runtimeDir;
}

function siteUpdateAdminGetLockPath()
{
    return siteUpdateAdminGetRuntimeDir() . DIRECTORY_SEPARATOR . 'site-update.lock';
}

function siteUpdateAdminGetStatePath()
{
    return siteUpdateAdminGetRuntimeDir() . DIRECTORY_SEPARATOR . 'site-update.json';
}

function siteUpdateAdminIsExecAvailable()
{
    if (!function_exists('exec')) {
        return false;
    }

    $disabledFunctions = array_map(
        'trim',
        explode(',', (string)ini_get('disable_functions'))
    );

    return !in_array('exec', $disabledFunctions, true);
}

function siteUpdateAdminGetGitBinary()
{
    return 'git';
}

function siteUpdateAdminGetPhpBinary()
{
    if (function_exists('envValue')) {
        $configuredBinary = trim((string)envValue('SITE_UPDATE_PHP_BINARY', ''));
        if ($configuredBinary !== '') {
            return $configuredBinary;
        }
    }

    if (PHP_SAPI === 'cli' && defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
        return PHP_BINARY;
    }

    if (defined('PHP_BINDIR') && is_string(PHP_BINDIR) && PHP_BINDIR !== '') {
        $candidates = array(
            rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php',
            rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php.exe',
        );

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
    }

    if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
        $binaryName = strtolower(basename(PHP_BINARY));
        if (strpos($binaryName, 'fpm') === false && strpos($binaryName, 'cgi') === false) {
            return PHP_BINARY;
        }
    }

    return 'php';
}

function siteUpdateAdminHasGitRepository()
{
    return file_exists(siteUpdateAdminGetRepoRoot() . DIRECTORY_SEPARATOR . '.git');
}

function siteUpdateAdminBuildCommand(array $parts)
{
    $escapedParts = array();
    foreach ($parts as $part) {
        $escapedParts[] = escapeshellarg((string)$part);
    }

    return implode(' ', $escapedParts) . ' 2>&1';
}

function siteUpdateAdminRunCommand(array $parts, $cwd = null, &$exitCode = null)
{
    if (!siteUpdateAdminIsExecAvailable()) {
        throw new RuntimeException('Les commandes systeme sont indisponibles sur ce serveur.');
    }

    $cwd = is_string($cwd) && $cwd !== '' ? $cwd : siteUpdateAdminGetRepoRoot();
    $originalDirectory = getcwd();

    if (!@chdir($cwd)) {
        throw new RuntimeException('Impossible d acceder au dossier du depot Git.');
    }

    try {
        $outputLines = array();
        $command = siteUpdateAdminBuildCommand($parts);
        exec($command, $outputLines, $resolvedExitCode);
        $exitCode = (int)$resolvedExitCode;

        return trim(implode("\n", $outputLines));
    } finally {
        if ($originalDirectory !== false) {
            @chdir($originalDirectory);
        }
    }
}

function siteUpdateAdminTryAcquireLock()
{
    siteUpdateAdminEnsureRuntimeDir();

    $handle = @fopen(siteUpdateAdminGetLockPath(), 'c+');
    if ($handle === false) {
        return false;
    }

    if (!@flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        return false;
    }

    return $handle;
}

function siteUpdateAdminReleaseLock($handle)
{
    if (!is_resource($handle)) {
        return;
    }

    @flock($handle, LOCK_UN);
    @fclose($handle);
}

function siteUpdateAdminReadState()
{
    $statePath = siteUpdateAdminGetStatePath();
    if (!is_file($statePath)) {
        return array();
    }

    $decoded = json_decode((string)file_get_contents($statePath), true);
    return is_array($decoded) ? $decoded : array();
}

function siteUpdateAdminWriteState(array $state)
{
    siteUpdateAdminEnsureRuntimeDir();
    $state['updatedAt'] = gmdate('c');
    file_put_contents(siteUpdateAdminGetStatePath(), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function siteUpdateAdminClearState()
{
    $statePath = siteUpdateAdminGetStatePath();
    if (is_file($statePath)) {
        @unlink($statePath);
    }
}

function siteUpdateAdminBuildUpdatingPayload()
{
    $state = siteUpdateAdminReadState();

    return array(
        'status' => true,
        'supported' => true,
        'updating' => true,
        'available' => false,
        'message' => (string)($state['message'] ?? 'Une mise a jour est deja en cours.'),
        'branch' => (string)($state['branch'] ?? ''),
        'behindCount' => (int)($state['behindCount'] ?? 0),
        'localCommit' => (string)($state['localCommit'] ?? ''),
        'remoteCommit' => (string)($state['remoteCommit'] ?? ''),
        'localHeadline' => (string)($state['localHeadline'] ?? ''),
        'remoteHeadline' => (string)($state['remoteHeadline'] ?? ''),
        'localDate' => (string)($state['localDate'] ?? ''),
        'remoteDate' => (string)($state['remoteDate'] ?? ''),
        'startedAt' => (string)($state['startedAt'] ?? ''),
        'updatedAt' => (string)($state['updatedAt'] ?? ''),
    );
}

function siteUpdateAdminParseTrackingReference($trackingReference)
{
    $trackingReference = trim((string)$trackingReference);
    if ($trackingReference === '') {
        return null;
    }

    if (!preg_match('#^([^/]+)/(.+)$#', $trackingReference, $matches)) {
        return null;
    }

    return array(
        'remote' => $matches[1],
        'branch' => $matches[2],
        'tracking' => $trackingReference,
    );
}

function siteUpdateAdminGetGitContext()
{
    if (!siteUpdateAdminIsExecAvailable()) {
        return array(
            'supported' => false,
            'reason' => 'exec_unavailable',
        );
    }

    if (!siteUpdateAdminHasGitRepository()) {
        return array(
            'supported' => false,
            'reason' => 'git_repository_missing',
        );
    }

    $repoRoot = siteUpdateAdminGetRepoRoot();

    $exitCode = 0;
    siteUpdateAdminRunCommand(array(siteUpdateAdminGetGitBinary(), '--version'), $repoRoot, $exitCode);
    if ($exitCode !== 0) {
        return array(
            'supported' => false,
            'reason' => 'git_missing',
        );
    }

    $localCommit = siteUpdateAdminRunCommand(array(siteUpdateAdminGetGitBinary(), 'rev-parse', 'HEAD'), $repoRoot, $exitCode);
    if ($exitCode !== 0 || $localCommit === '') {
        return array(
            'supported' => false,
            'reason' => 'local_commit_unavailable',
        );
    }

    $branch = siteUpdateAdminRunCommand(array(siteUpdateAdminGetGitBinary(), 'rev-parse', '--abbrev-ref', 'HEAD'), $repoRoot, $exitCode);
    if ($exitCode !== 0) {
        $branch = '';
    }

    $trackingReference = siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{upstream}'),
        $repoRoot,
        $exitCode
    );

    $tracking = $exitCode === 0 ? siteUpdateAdminParseTrackingReference($trackingReference) : null;
    if ($tracking === null) {
        $branch = trim((string)$branch);
        if ($branch === '' || strtoupper($branch) === 'HEAD') {
            return array(
                'supported' => false,
                'reason' => 'tracking_branch_missing',
            );
        }

        $tracking = array(
            'remote' => 'origin',
            'branch' => $branch,
            'tracking' => 'origin/' . $branch,
        );
    }

    return array(
        'supported' => true,
        'repoRoot' => $repoRoot,
        'localCommit' => trim((string)$localCommit),
        'branch' => trim((string)$branch),
        'remote' => $tracking['remote'],
        'remoteBranch' => $tracking['branch'],
        'tracking' => $tracking['tracking'],
    );
}

function siteUpdateAdminFetchRemoteCommit(array $context)
{
    $repoRoot = $context['repoRoot'];
    $exitCode = 0;
    $output = siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'fetch', '--quiet', $context['remote'], $context['remoteBranch']),
        $repoRoot,
        $exitCode
    );

    if ($exitCode !== 0) {
        throw new RuntimeException($output !== '' ? $output : 'Impossible de contacter le depot distant.');
    }

    $remoteCommit = siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'rev-parse', 'FETCH_HEAD'),
        $repoRoot,
        $exitCode
    );

    if ($exitCode !== 0 || trim((string)$remoteCommit) === '') {
        throw new RuntimeException('Impossible de determiner le commit distant.');
    }

    return trim((string)$remoteCommit);
}

function siteUpdateAdminGetCommitSummary(array $context, $ref)
{
    $repoRoot = $context['repoRoot'];
    $exitCode = 0;
    $output = siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'log', '-1', '--pretty=format:%H%x1f%s%x1f%cI', (string)$ref),
        $repoRoot,
        $exitCode
    );

    if ($exitCode !== 0 || trim((string)$output) === '') {
        return array(
            'commit' => '',
            'headline' => '',
            'date' => '',
        );
    }

    $parts = explode("\x1f", $output);

    return array(
        'commit' => trim((string)($parts[0] ?? '')),
        'headline' => trim((string)($parts[1] ?? '')),
        'date' => trim((string)($parts[2] ?? '')),
    );
}

function siteUpdateAdminGetBehindCount(array $context, $localCommit, $remoteCommit)
{
    if (trim((string)$localCommit) === '' || trim((string)$remoteCommit) === '' || $localCommit === $remoteCommit) {
        return 0;
    }

    $repoRoot = $context['repoRoot'];
    $exitCode = 0;
    $output = siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'rev-list', '--count', trim((string)$localCommit) . '..' . trim((string)$remoteCommit)),
        $repoRoot,
        $exitCode
    );

    if ($exitCode !== 0) {
        return 0;
    }

    return max(0, (int)trim((string)$output));
}

function siteUpdateAdminBuildAvailableMessage($behindCount, array $remoteSummary)
{
    $behindCount = max(0, (int)$behindCount);
    $headline = trim((string)($remoteSummary['headline'] ?? ''));

    if ($behindCount <= 0 && $headline === '') {
        return 'Une nouvelle version est disponible.';
    }

    if ($headline === '') {
        return $behindCount <= 1
            ? 'Une mise a jour est disponible.'
            : $behindCount . ' mises a jour sont disponibles.';
    }

    if ($behindCount <= 1) {
        return 'Une mise a jour est disponible : ' . $headline;
    }

    return $behindCount . ' mises a jour sont disponibles. Derniere version : ' . $headline;
}

function siteUpdateAdminHasTrackedLocalChanges(array $context)
{
    $repoRoot = $context['repoRoot'];
    $exitCode = 0;

    siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'diff', '--no-ext-diff', '--quiet', '--exit-code'),
        $repoRoot,
        $exitCode
    );
    if ($exitCode !== 0) {
        return true;
    }

    siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'diff', '--no-ext-diff', '--cached', '--quiet', '--exit-code'),
        $repoRoot,
        $exitCode
    );

    return $exitCode !== 0;
}

function siteUpdateAdminFormatCommandOutput($output, $maxLines = 12)
{
    $output = trim((string)$output);
    if ($output === '') {
        return '';
    }

    $lines = preg_split('/\r\n|\r|\n/', $output);
    $lines = array_values(array_filter(array_map('trim', $lines), static function ($line) {
        return $line !== '';
    }));

    if (count($lines) > $maxLines) {
        $lines = array_slice($lines, -$maxLines);
    }

    return implode("\n", $lines);
}

function siteUpdateAdminCheckVersionStatus()
{
    $lockHandle = siteUpdateAdminTryAcquireLock();
    if ($lockHandle === false) {
        return siteUpdateAdminBuildUpdatingPayload();
    }

    siteUpdateAdminReleaseLock($lockHandle);

    $context = siteUpdateAdminGetGitContext();
    if (empty($context['supported'])) {
        return array(
            'status' => true,
            'supported' => false,
            'updating' => false,
            'available' => false,
            'reason' => (string)($context['reason'] ?? 'unsupported'),
        );
    }

    try {
        $remoteCommit = siteUpdateAdminFetchRemoteCommit($context);
    } catch (Throwable $exception) {
        return array(
            'status' => true,
            'supported' => false,
            'updating' => false,
            'available' => false,
            'reason' => 'remote_check_failed',
        );
    }

    $localSummary = siteUpdateAdminGetCommitSummary($context, $context['localCommit']);
    $remoteSummary = siteUpdateAdminGetCommitSummary($context, $remoteCommit);
    $behindCount = siteUpdateAdminGetBehindCount($context, $context['localCommit'], $remoteCommit);

    return array(
        'status' => true,
        'supported' => true,
        'updating' => false,
        'available' => $remoteCommit !== $context['localCommit'],
        'branch' => (string)$context['branch'],
        'tracking' => (string)$context['tracking'],
        'behindCount' => $behindCount,
        'localCommit' => (string)$context['localCommit'],
        'remoteCommit' => (string)$remoteCommit,
        'localHeadline' => (string)($localSummary['headline'] ?? ''),
        'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
        'localDate' => (string)($localSummary['date'] ?? ''),
        'remoteDate' => (string)($remoteSummary['date'] ?? ''),
        'message' => $remoteCommit !== $context['localCommit']
            ? siteUpdateAdminBuildAvailableMessage($behindCount, $remoteSummary)
            : 'Le site est deja a jour.',
    );
}

function siteUpdateAdminRunUpdate($actorUserId)
{
    $actorUserId = (int)$actorUserId;
    $lockHandle = siteUpdateAdminTryAcquireLock();
    if ($lockHandle === false) {
        throw new RuntimeException('Une mise a jour est deja en cours.');
    }

    try {
        $context = siteUpdateAdminGetGitContext();
        if (empty($context['supported'])) {
            throw new RuntimeException('La mise a jour automatique n est pas disponible sur ce serveur.');
        }

        if (siteUpdateAdminHasTrackedLocalChanges($context)) {
            throw new RuntimeException('Le depot contient des modifications locales. La mise a jour automatique est bloquee pour eviter un ecrasement.');
        }

        $remoteCommit = siteUpdateAdminFetchRemoteCommit($context);
        $localCommit = (string)$context['localCommit'];
        $localSummary = siteUpdateAdminGetCommitSummary($context, $localCommit);
        $remoteSummary = siteUpdateAdminGetCommitSummary($context, $remoteCommit);
        $behindCount = siteUpdateAdminGetBehindCount($context, $localCommit, $remoteCommit);

        siteUpdateAdminWriteState(array(
            'status' => 'running',
            'message' => 'Mise a jour en cours.',
            'startedAt' => gmdate('c'),
            'userId' => $actorUserId,
            'branch' => (string)$context['tracking'],
            'behindCount' => $behindCount,
            'localCommit' => $localCommit,
            'remoteCommit' => $remoteCommit,
            'localHeadline' => (string)($localSummary['headline'] ?? ''),
            'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
            'localDate' => (string)($localSummary['date'] ?? ''),
            'remoteDate' => (string)($remoteSummary['date'] ?? ''),
        ));

        if ($remoteCommit === $localCommit) {
            return array(
                'status' => true,
                'updated' => false,
                'message' => 'Le site est deja a jour.',
                'behindCount' => 0,
                'localCommit' => $localCommit,
                'remoteCommit' => $remoteCommit,
                'localHeadline' => (string)($localSummary['headline'] ?? ''),
                'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
                'localDate' => (string)($localSummary['date'] ?? ''),
                'remoteDate' => (string)($remoteSummary['date'] ?? ''),
                'branch' => (string)$context['tracking'],
            );
        }

        $repoRoot = $context['repoRoot'];
        $exitCode = 0;
        $resetOutput = siteUpdateAdminRunCommand(
            array(siteUpdateAdminGetGitBinary(), 'reset', '--hard', 'FETCH_HEAD'),
            $repoRoot,
            $exitCode
        );
        if ($exitCode !== 0) {
            throw new RuntimeException(siteUpdateAdminFormatCommandOutput($resetOutput) ?: 'Impossible de synchroniser le code avec le depot distant.');
        }

        siteUpdateAdminWriteState(array(
            'status' => 'running',
            'message' => 'Synchronisation du code terminee. Application des migrations en cours.',
            'startedAt' => gmdate('c'),
            'userId' => $actorUserId,
            'branch' => (string)$context['tracking'],
            'behindCount' => $behindCount,
            'localCommit' => $localCommit,
            'remoteCommit' => $remoteCommit,
            'localHeadline' => (string)($localSummary['headline'] ?? ''),
            'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
            'localDate' => (string)($localSummary['date'] ?? ''),
            'remoteDate' => (string)($remoteSummary['date'] ?? ''),
        ));

        $migrationOutput = siteUpdateAdminRunCommand(
            array(siteUpdateAdminGetPhpBinary(), $repoRoot . '/scripts/run-migrations.php'),
            $repoRoot,
            $exitCode
        );
        if ($exitCode !== 0) {
            throw new RuntimeException(siteUpdateAdminFormatCommandOutput($migrationOutput) ?: 'Les migrations SQL ont echoue.');
        }

        $updatedLocalCommit = siteUpdateAdminRunCommand(
            array(siteUpdateAdminGetGitBinary(), 'rev-parse', 'HEAD'),
            $repoRoot,
            $exitCode
        );
        if ($exitCode !== 0 || trim((string)$updatedLocalCommit) === '') {
            $updatedLocalCommit = $remoteCommit;
        }

        $updatedSummary = siteUpdateAdminGetCommitSummary($context, trim((string)$updatedLocalCommit));

        return array(
            'status' => true,
            'updated' => true,
            'message' => 'La mise a jour du site est terminee.',
            'behindCount' => 0,
            'localCommit' => trim((string)$updatedLocalCommit),
            'remoteCommit' => $remoteCommit,
            'localHeadline' => (string)($updatedSummary['headline'] ?? ''),
            'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
            'localDate' => (string)($updatedSummary['date'] ?? ''),
            'remoteDate' => (string)($remoteSummary['date'] ?? ''),
            'branch' => (string)$context['tracking'],
            'migrationOutput' => siteUpdateAdminFormatCommandOutput($migrationOutput),
            'resetOutput' => siteUpdateAdminFormatCommandOutput($resetOutput),
        );
    } finally {
        siteUpdateAdminClearState();
        siteUpdateAdminReleaseLock($lockHandle);
    }
}
