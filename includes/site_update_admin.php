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

function siteUpdateAdminGetComposerCommand($phpBinary)
{
    $configured = function_exists('envValue') ? trim((string)envValue('SITE_UPDATE_COMPOSER_BINARY', '')) : '';
    $candidates = $configured !== '' ? array($configured) : array(
        'composer', '/usr/local/bin/composer', '/usr/bin/composer',
        '/usr/local/bin/composer.phar', '/usr/bin/composer.phar',
        siteUpdateAdminGetRepoRoot() . '/composer.phar',
    );
    foreach ($candidates as $candidate) {
        if (strpos($candidate, '/') === false && strpos($candidate, '\\') === false) {
            foreach (explode(PATH_SEPARATOR, (string)getenv('PATH')) as $directory) {
                $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $candidate;
                if ($directory !== '' && is_file($path)) {
                    $candidate = $path;
                    break;
                }
            }
        }
        $isPhp = strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) === 'phar';
        if (is_file($candidate) && is_readable($candidate)) {
            $prefix = file_get_contents($candidate, false, null, 0, 512);
            $isPhp = $isPhp || ($prefix !== false && strpos($prefix, '<?php') !== false);
        }
        $command = $isPhp ? array($phpBinary, $candidate) : array($candidate);
        $output = siteUpdateAdminRunCommand(array_merge($command, array('--version', '--no-ansi')), null, $exitCode);
        if ($exitCode === 0 && preg_match('/Composer(?: version)?\s+([2-9]|[1-9][0-9]+)\./i', $output)) {
            return $command;
        }
    }
    throw new RuntimeException('Composer 2 est introuvable ou inutilisable depuis PHP. Configurez SITE_UPDATE_COMPOSER_BINARY dans .env avec le chemin absolu de Composer ou de composer.phar (sans arguments). Les alias SSH ne sont pas disponibles depuis le site.');
}

function siteUpdateAdminGetPhpBinary()
{
    $candidates = array();
    $configuredBinary = '';
    if (function_exists('envValue')) {
        $configuredBinary = trim((string)envValue('SITE_UPDATE_PHP_BINARY', ''));
    }
    if (PHP_SAPI === 'cli' && defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
        $candidates[] = PHP_BINARY;
    }
    $candidates[] = '/opt/php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '/bin/php';
    if (defined('PHP_BINDIR') && is_string(PHP_BINDIR) && PHP_BINDIR !== '') {
        $candidates[] = rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php';
        $candidates[] = rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php.exe';
    }
    $candidates[] = 'php';
    foreach (array_unique($configuredBinary !== '' ? array($configuredBinary) : $candidates) as $candidate) {
        if (preg_match('/fpm|cgi/i', basename($candidate))) {
            continue;
        }
        if ((strpos($candidate, '/') !== false || strpos($candidate, '\\') !== false) && !is_file($candidate)) {
            continue;
        }
        $output = siteUpdateAdminRunCommand(array($candidate, '-r', 'echo PHP_SAPI . ":" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;'), null, $exitCode);
        if ($exitCode === 0 && trim($output) === 'cli:' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) {
            return $candidate;
        }
    }
    throw new RuntimeException('PHP CLI compatible introuvable. Configurez SITE_UPDATE_PHP_BINARY dans .env avec le chemin absolu du PHP CLI de meme version que le site (pas php-fpm ni php-cgi).');
}

function siteUpdateAdminHasGitRepository()
{
    return file_exists(siteUpdateAdminGetRepoRoot() . DIRECTORY_SEPARATOR . '.git');
}

function siteUpdateAdminBuildCommand(array $parts, $mergeErrorOutput = true)
{
    $escapedParts = array();
    foreach ($parts as $part) {
        $escapedParts[] = escapeshellarg((string)$part);
    }

    return implode(' ', $escapedParts) . ($mergeErrorOutput ? ' 2>&1' : '');
}

function siteUpdateAdminRunCommand(array $parts, $cwd = null, &$exitCode = null, $preservePathOutput = false)
{
    if (!siteUpdateAdminIsExecAvailable()) {
        throw new RuntimeException('Les commandes systeme sont indisponibles sur ce serveur.');
    }

    $cwd = is_string($cwd) && $cwd !== '' ? $cwd : siteUpdateAdminGetRepoRoot();
    $originalDirectory = getcwd();

    if (!@chdir($cwd)) {
        throw new RuntimeException('Impossible d acceder au dossier du depot Git.');
    }

    $stdoutFile = null;
    $stderrFile = null;
    try {
        if ($preservePathOutput) {
            // exec() trims line endings; Git -z output must remain byte-for-byte.
            // Keep Git warnings out of the list of filenames as well.
            $stdoutFile = tmpfile();
            $stderrFile = tmpfile();
            if ($stdoutFile === false || $stderrFile === false) {
                throw new RuntimeException('Impossible de lire les chemins Git sans alteration.');
            }
            $stdoutPath = stream_get_meta_data($stdoutFile)['uri'];
            $stderrPath = stream_get_meta_data($stderrFile)['uri'];
            $command = siteUpdateAdminBuildCommand($parts, false)
                . ' > ' . escapeshellarg($stdoutPath) . ' 2> ' . escapeshellarg($stderrPath);
            $ignoredOutput = array();
            exec($command, $ignoredOutput, $resolvedExitCode);
            $exitCode = (int)$resolvedExitCode;
            rewind($stdoutFile);
            rewind($stderrFile);
            $output = stream_get_contents($stdoutFile);
            $errors = stream_get_contents($stderrFile);
            if ($output === false || $errors === false) {
                throw new RuntimeException('Lecture incomplete des chemins Git.');
            }
            if ($errors !== '') {
                error_log('Site update Git: ' . trim($errors));
            }
            return $exitCode === 0 ? $output : $errors;
        }
        $outputLines = array();
        $command = siteUpdateAdminBuildCommand($parts);
        exec($command, $outputLines, $resolvedExitCode);
        $exitCode = (int)$resolvedExitCode;

        return rtrim(implode("\n", $outputLines), "\r\n");
    } finally {
        if (is_resource($stdoutFile)) {
            fclose($stdoutFile);
        }
        if (is_resource($stderrFile)) {
            fclose($stderrFile);
        }
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

function siteUpdateAdminCollectChangedPaths(array &$changesByPath, $output, $state)
{
    $paths = preg_split('/\0/', (string)$output);
    foreach ($paths as $path) {
        $path = (string)$path;
        if ($state === 'untracked') {
            // Git can report an untracked directory with a terminal slash.
            $path = rtrim($path, '/');
        }
        if ($path === '') {
            continue;
        }

        if (!isset($changesByPath[$path])) {
            $changesByPath[$path] = array(
                'path' => $path,
                'states' => array(),
            );
        }

        if (!in_array($state, $changesByPath[$path]['states'], true)) {
            $changesByPath[$path]['states'][] = $state;
        }
    }
}

function siteUpdateAdminGetLocalChanges(array $context)
{
    $repoRoot = $context['repoRoot'];
    $changesByPath = array();
    $commands = array(
        'modified' => array(siteUpdateAdminGetGitBinary(), 'diff', '--no-ext-diff', '--name-only', '-z'),
        'indexed' => array(siteUpdateAdminGetGitBinary(), 'diff', '--no-ext-diff', '--cached', '--name-only', '-z'),
        'untracked' => array(siteUpdateAdminGetGitBinary(), 'ls-files', '--others', '--exclude-standard', '-z'),
    );

    foreach ($commands as $state => $parts) {
        $exitCode = 0;
        $output = siteUpdateAdminRunCommand($parts, $repoRoot, $exitCode, true);
        if ($exitCode !== 0) {
            throw new RuntimeException('Impossible de verifier les fichiers locaux avant la mise a jour.');
        }
        siteUpdateAdminCollectChangedPaths($changesByPath, $output, $state);
    }

    $changes = array_values($changesByPath);
    usort($changes, static function ($left, $right) {
        return strnatcasecmp((string)$left['path'], (string)$right['path']);
    });

    return $changes;
}

function siteUpdateAdminGetRemoteChangedPaths(array $context, $localCommit, $remoteCommit)
{
    if (trim((string)$localCommit) === '' || trim((string)$remoteCommit) === '' || $localCommit === $remoteCommit) {
        return array();
    }

    $exitCode = 0;
    $output = siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'diff', '--no-ext-diff', '--name-only', '-z', $localCommit . '..' . $remoteCommit),
        $context['repoRoot'],
        $exitCode,
        true
    );

    if ($exitCode !== 0) {
        return array();
    }

    return array_values(array_filter(preg_split('/\0/', (string)$output), static function ($path) {
        return $path !== '';
    }));
}

function siteUpdateAdminValidateRelativePath(string $path, string $context = 'sauvegarde locale'): void
{
    $segments = explode('/', $path);
    $reason = '';
    if ($path === '') {
        $reason = 'chemin vide';
    } elseif (preg_match('/[\x00-\x1f\x7f]/', $path)) {
        $reason = 'caractere de controle dans le nom';
    } elseif (array_intersect($segments, array('', '.', '..'))) {
        $reason = 'chemin absolu ou segment de chemin non autorise';
    } elseif (in_array('.git', array_map('strtolower', $segments), true)) {
        $reason = 'metadonnees Git a proteger ; le dossier en conflit contient peut-etre un depot imbrique';
    } elseif (DIRECTORY_SEPARATOR === '\\' && preg_match('/[\\\\:*?"<>|]/', $path)) {
        $reason = 'nom de fichier incompatible avec Windows';
    }
    if ($reason !== '') {
        $displayPath = json_encode($path, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        throw new RuntimeException('Chemin refuse (' . $context . ') : ' . $displayPath . '. Motif : ' . $reason . '. Aucune synchronisation lancee.');
    }
}

function siteUpdateAdminAssertUploadStorageExcluded(array $remoteFiles): void
{
    foreach (array_keys($remoteFiles) as $path) {
        if ($path === 'img/upload' || str_starts_with($path, 'img/upload/')) {
            throw new RuntimeException('Mise a jour refusee : le depot distant contient ' . $path
                . '. Le stockage img/upload doit rester hors de Git pour conserver les images et les liens vers le stockage partage. Le mode force ne peut pas contourner cette protection.');
        }
    }
}

function siteUpdateAdminGetRemoteFiles(array $context, string $commit): array
{
    $exitCode = 0;
    $output = siteUpdateAdminRunCommand(
        array(siteUpdateAdminGetGitBinary(), 'ls-tree', '-r', '-z', '--full-tree', $commit),
        $context['repoRoot'], $exitCode, true
    );
    if ($exitCode !== 0) {
        throw new RuntimeException('Impossible de lire la liste des fichiers distants.');
    }
    $files = array();
    foreach (explode("\0", $output) as $entry) {
        if ($entry === '') {
            continue;
        }
        if (!preg_match('/^(\d+) (blob|commit) ([a-f0-9]{40}|[a-f0-9]{64})\t(.+)$/D', $entry, $parts)) {
            throw new RuntimeException('Entree Git distante non prise en charge : ' . json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
        }
        siteUpdateAdminValidateRelativePath($parts[4], 'arbre distant');
        $files[$parts[4]] = array('mode' => $parts[1], 'hash' => $parts[3]);
    }
    siteUpdateAdminAssertUploadStorageExcluded($files);
    return $files;
}

function siteUpdateAdminFileMatchesRemote(string $path, ?array $remote): bool
{
    if ($remote !== null && $remote['mode'] === '120000' && is_link($path)) {
        $target = readlink($path);
        return is_string($target) && hash_equals($remote['hash'], hash(
            strlen($remote['hash']) === 64 ? 'sha256' : 'sha1',
            'blob ' . strlen($target) . "\0" . $target
        ));
    }
    if ($remote === null || !in_array($remote['mode'], array('100644', '100755'), true)
        || is_link($path) || !is_file($path)) {
        return false;
    }
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Impossible de comparer un fichier local.');
    }
    try {
        $stat = fstat($handle);
        if ($stat === false) {
            throw new RuntimeException('Impossible de lire la taille du fichier local.');
        }
        $hash = hash_init(strlen($remote['hash']) === 64 ? 'sha256' : 'sha1');
        hash_update($hash, 'blob ' . $stat['size'] . "\0");
        if (hash_update_stream($hash, $handle) !== $stat['size']) {
            throw new RuntimeException('Lecture incomplete pendant la comparaison locale.');
        }
        return hash_equals($remote['hash'], hash_final($hash));
    } finally {
        fclose($handle);
    }
}

function siteUpdateAdminBuildLocalChangesPayload(array $context, $localCommit = '', $remoteCommit = '', ?array $remoteFiles = null)
{
    $localChanges = siteUpdateAdminGetLocalChanges($context);
    $remoteFiles = $remoteFiles ?? siteUpdateAdminGetRemoteFiles($context, $remoteCommit);
    $remotePaths = array_flip(siteUpdateAdminGetRemoteChangedPaths($context, $localCommit, $remoteCommit));
    $exitCode = 0;
    $trackedOutput = siteUpdateAdminRunCommand(array(siteUpdateAdminGetGitBinary(), 'ls-files', '-z'), $context['repoRoot'], $exitCode, true);
    if ($exitCode !== 0) {
        throw new RuntimeException('Impossible de lire les fichiers suivis par Git.');
    }
    $trackedPaths = array_flip(explode("\0", $trackedOutput));
    $changesByPath = array_column($localChanges, null, 'path');
    // Also detect ignored files and file/directory obstructions that reset may replace.
    foreach ($remoteFiles as $path => $remoteFile) {
        $prefix = '';
        foreach (explode('/', $path) as $segment) {
            $prefix = $prefix === '' ? $segment : $prefix . '/' . $segment;
            $absolute = $context['repoRoot'] . '/' . $prefix;
            if (!file_exists($absolute) && !is_link($absolute)) {
                break;
            }
            if ($prefix === $path || is_link($absolute) || !is_dir($absolute)) {
                if (!isset($trackedPaths[$prefix]) || is_link($absolute) || ($prefix === $path && is_dir($absolute) && $remoteFile['mode'] !== '160000')) {
                    if (!isset($changesByPath[$prefix])) {
                        $changesByPath[$prefix] = array('path' => $prefix, 'states' => array(isset($trackedPaths[$prefix]) ? 'modified' : 'untracked'));
                    }
                    $remotePaths[$prefix] = true;
                }
                break;
            }
        }
    }
    $localChanges = array_values($changesByPath);
    usort($localChanges, static fn($a, $b) => strnatcasecmp($a['path'], $b['path']));
    $overlappingPaths = array();
    $untrackedOverlappingPaths = array();
    $trackedCount = 0;

    foreach ($localChanges as &$change) {
        $change['overlapsRemoteUpdate'] = isset($remotePaths[$change['path']]);
        if ($change['overlapsRemoteUpdate']) {
            $overlappingPaths[] = $change['path'];
            if (in_array('untracked', $change['states'], true)) {
                $untrackedOverlappingPaths[] = $change['path'];
            }
        }

        if (array_intersect($change['states'], array('modified', 'indexed'))) {
            $trackedCount++;
        }
    }
    unset($change);

    return array(
        'hasTrackedChanges' => $trackedCount > 0,
        'localChangeCount' => count($localChanges),
        'trackedLocalChangeCount' => $trackedCount,
        'overlappingLocalChangeCount' => count($overlappingPaths),
        'overlappingLocalChangePaths' => $overlappingPaths,
        'untrackedOverlappingLocalChangeCount' => count($untrackedOverlappingPaths),
        'untrackedOverlappingLocalChangePaths' => $untrackedOverlappingPaths,
        'localChanges' => $localChanges,
    );
}

function siteUpdateAdminHasTrackedLocalChanges(array $localChangesPayload)
{
    return !empty($localChangesPayload['hasTrackedChanges']);
}

function siteUpdateAdminBackupLocalChanges(array $context, array $payload, array $remoteFiles): array
{
    $backup = array('backupPath' => '', 'backedUpFileCount' => 0, 'identicalFileCount' => 0);
    $entries = array();
    $root = realpath($context['repoRoot']);
    if ($root === false) {
        throw new RuntimeException('Le dossier du site est introuvable.');
    }
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $changes = $payload['localChanges'];
    $visited = array();
    for ($changeIndex = 0; $changeIndex < count($changes); $changeIndex++) {
        $change = $changes[$changeIndex];
        if (!$change['overlapsRemoteUpdate'] && !array_intersect($change['states'], array('modified', 'indexed'))) {
            continue;
        }
        $relative = $change['path'];
        if (isset($visited[$relative])) {
            continue;
        }
        $visited[$relative] = true;
        siteUpdateAdminValidateRelativePath($relative);
        $path = $root . '/' . $relative;
        $parent = dirname($path);
        while ($parent !== $root) {
            if (is_link($parent)) {
                throw new RuntimeException('Un lien de dossier doit etre traite manuellement avant la mise a jour : ' . $relative);
            }
            $parent = dirname($parent);
        }
        if (!file_exists($path) && !is_link($path)) {
            if (isset($remoteFiles[$relative])) {
                $entries[$relative] = array('type' => 'missing');
            }
            continue;
        }
        if (is_dir($path) && !is_link($path)) {
            if (($remoteFiles[$relative]['mode'] ?? '') === '160000') {
                throw new RuntimeException('Un sous-module Git doit etre synchronise manuellement : ' . $relative);
            }
            $children = scandir($path);
            if ($children === false) {
                throw new RuntimeException('Impossible de lire le dossier en conflit : ' . $relative);
            }
            $children = array_values(array_diff($children, array('.', '..')));
            // Keep a directory inventory, even when empty, and never follow links.
            $entries[$relative] = array('type' => 'directory', 'mode' => fileperms($path) & 0777, 'children' => $children);
            foreach ($children as $child) {
                $changes[] = array(
                    'path' => $relative . '/' . $child,
                    'states' => array('untracked'),
                    'overlapsRemoteUpdate' => true,
                );
            }
            continue;
        }
        if (siteUpdateAdminFileMatchesRemote($path, $remoteFiles[$relative] ?? null)) {
            $backup['identicalFileCount']++;
            continue;
        }
        if (is_link($path)) {
            $target = readlink($path);
            if ($target === false) {
                throw new RuntimeException('Impossible de sauvegarder le lien : ' . $relative);
            }
            $entries[$relative] = array('type' => 'symlink', 'target' => $target);
        } elseif (is_file($path)) {
            $entries[$relative] = array('type' => 'file', 'mode' => fileperms($path) & 0777);
        } else {
            throw new RuntimeException('Type de fichier a traiter manuellement : ' . $relative);
        }
    }
    if ($entries === array()) {
        return $backup;
    }

    require_once __DIR__ . '/../common/runtime_log.php';
    $directory = commonRuntimeLogPath('site-update-backups');
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Impossible de creer le dossier de sauvegarde. La mise a jour est annulee.');
    }
    $directory = realpath($directory);
    $publicRoots = array($root, realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: $root);
    foreach ($publicRoots as $publicRoot) {
        $prefix = rtrim(str_replace('\\', '/', $publicRoot), '/') . '/';
        $resolved = rtrim(str_replace('\\', '/', (string)$directory), '/') . '/';
        if ($directory === false || strncasecmp($resolved, $prefix, strlen($prefix)) === 0) {
            throw new RuntimeException('Configurez RUNTIME_LOG_DIR hors de la racine web pour les sauvegardes de mise a jour.');
        }
    }
    $backup['backupPath'] = $directory . '/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(6));
    if (!mkdir($backup['backupPath'], 0700)) {
        throw new RuntimeException('Impossible de preparer la sauvegarde. La mise a jour est annulee.');
    }
    foreach ($entries as $relative => $entry) {
        if ($entry['type'] !== 'file') {
            continue;
        }
        $source = $root . '/' . $relative;
        $destination = $backup['backupPath'] . '/files/' . $relative;
        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0700, true)) {
            throw new RuntimeException('Impossible de preparer la copie de sauvegarde : ' . $relative);
        }
        if (!copy($source, $destination) || !chmod($destination, 0600)) {
            throw new RuntimeException('Impossible de sauvegarder : ' . $relative . '. Aucune synchronisation lancee.');
        }
        $sourceHash = hash_file('sha256', $source);
        if ($sourceHash === false || $sourceHash !== hash_file('sha256', $destination)) {
            throw new RuntimeException('La sauvegarde differe du fichier local : ' . $relative . '. Aucune synchronisation lancee.');
        }
        $entries[$relative]['sha256'] = $sourceHash;
        $backup['backedUpFileCount']++;
    }
    // Refuse replacement if an upload changed a conflicting directory meanwhile.
    foreach ($entries as $relative => $entry) {
        if ($entry['type'] !== 'directory') {
            continue;
        }
        $children = scandir($root . '/' . $relative);
        if ($children === false || array_values(array_diff($children, array('.', '..'))) !== $entry['children']) {
            throw new RuntimeException('Le dossier a change pendant la sauvegarde : ' . $relative . '. Aucune synchronisation lancee.');
        }
    }
    $manifest = array(
        'createdAt' => gmdate('c'),
        'repoRoot' => $root,
        'localCommit' => $context['localCommit'],
        'remoteCommit' => $payload['remoteCommit'],
        'files' => $entries,
    );
    $manifestPath = $backup['backupPath'] . '/manifest.json';
    if (file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX) === false
        || !chmod($manifestPath, 0600)) {
        throw new RuntimeException('Impossible de finaliser la sauvegarde. Aucune synchronisation lancee.');
    }
    error_log('Site update backup: ' . $backup['backupPath']);
    return $backup;
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

    $state = siteUpdateAdminReadState();
    $localSummary = siteUpdateAdminGetCommitSummary($context, $context['localCommit']);
    $remoteSummary = siteUpdateAdminGetCommitSummary($context, $remoteCommit);
    $behindCount = siteUpdateAdminGetBehindCount($context, $context['localCommit'], $remoteCommit);
    $localChangesPayload = siteUpdateAdminBuildLocalChangesPayload($context, $context['localCommit'], $remoteCommit);

    return array_merge(array(
        'status' => true,
        'supported' => true,
        'updating' => false,
        'available' => $remoteCommit !== $context['localCommit'],
        'requiresCompletion' => !empty($state['requiresCompletion']),
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
    ), $localChangesPayload);
}

function siteUpdateAdminRunUpdate($actorUserId, $force = false, $completeOnly = false)
{
    $actorUserId = (int)$actorUserId;
    $requiresCompletion = false;
    $completed = false;
    $backup = array('backupPath' => '', 'backedUpFileCount' => 0, 'identicalFileCount' => 0);
    $lockHandle = siteUpdateAdminTryAcquireLock();
    if ($lockHandle === false) {
        throw new RuntimeException('Une mise a jour est deja en cours.');
    }

    try {
        $context = siteUpdateAdminGetGitContext();
        if (empty($context['supported'])) {
            throw new RuntimeException('La mise a jour automatique n est pas disponible sur ce serveur.');
        }

        $localCommit = (string)$context['localCommit'];
        $previousState = siteUpdateAdminReadState();
        if (!empty($previousState['requiresCompletion'])) {
            $completeOnly = true;
        }
        // Finalization uses the installed code, even if a newer remote version exists.
        $remoteCommit = $completeOnly ? $localCommit : siteUpdateAdminFetchRemoteCommit($context);
        if ($completeOnly) {
            $backup = array_merge($backup, (array)($previousState['backup'] ?? array()));
        }
        $localSummary = siteUpdateAdminGetCommitSummary($context, $localCommit);
        $remoteSummary = siteUpdateAdminGetCommitSummary($context, $remoteCommit);
        $behindCount = siteUpdateAdminGetBehindCount($context, $localCommit, $remoteCommit);
        $remoteFiles = $completeOnly ? array() : siteUpdateAdminGetRemoteFiles($context, $remoteCommit);
        $localChangesPayload = $completeOnly ? array() : siteUpdateAdminBuildLocalChangesPayload($context, $localCommit, $remoteCommit, $remoteFiles);

        if (!empty($localChangesPayload['untrackedOverlappingLocalChangeCount']) && !$force && !$completeOnly) {
            return array_merge(array(
                'status' => false,
                'requiresForce' => true,
                'message' => 'Des fichiers non suivis seraient remplaces. Vous pouvez forcer la mise a jour : seuls les contenus differents de la version distante seront sauvegardes avant remplacement.',
                'behindCount' => $behindCount,
                'localCommit' => $localCommit,
                'remoteCommit' => $remoteCommit,
                'localHeadline' => (string)($localSummary['headline'] ?? ''),
                'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
                'localDate' => (string)($localSummary['date'] ?? ''),
                'remoteDate' => (string)($remoteSummary['date'] ?? ''),
                'branch' => (string)$context['tracking'],
            ), $localChangesPayload);
        }

        if (siteUpdateAdminHasTrackedLocalChanges($localChangesPayload) && !$force && !$completeOnly) {
            return array_merge(array(
                'status' => false,
                'requiresForce' => true,
                'message' => 'Le depot contient des modifications locales. La mise a jour automatique est bloquee pour eviter un ecrasement.',
                'behindCount' => $behindCount,
                'localCommit' => $localCommit,
                'remoteCommit' => $remoteCommit,
                'localHeadline' => (string)($localSummary['headline'] ?? ''),
                'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
                'localDate' => (string)($localSummary['date'] ?? ''),
                'remoteDate' => (string)($remoteSummary['date'] ?? ''),
                'branch' => (string)$context['tracking'],
            ), $localChangesPayload);
        }

        // Resolve and check both commands before changing files or Git HEAD.
        $phpBinary = siteUpdateAdminGetPhpBinary();
        $composerCommand = siteUpdateAdminGetComposerCommand($phpBinary);

        siteUpdateAdminWriteState(array(
            'status' => 'running',
            'message' => 'Mise a jour en cours.',
            'requiresCompletion' => (bool)$completeOnly,
            'backup' => $backup,
            'startedAt' => gmdate('c'),
            'userId' => $actorUserId,
            'forced' => (bool)$force,
            'branch' => (string)$context['tracking'],
            'behindCount' => $behindCount,
            'localCommit' => $localCommit,
            'remoteCommit' => $remoteCommit,
            'localHeadline' => (string)($localSummary['headline'] ?? ''),
            'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
            'localDate' => (string)($localSummary['date'] ?? ''),
            'remoteDate' => (string)($remoteSummary['date'] ?? ''),
        ));

        if ($remoteCommit === $localCommit && !$force && !$completeOnly) {
            $completed = true;
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
                'forced' => (bool)$force,
            );
        }

        $repoRoot = $context['repoRoot'];
        if ($force && !$completeOnly) {
            $backup = siteUpdateAdminBackupLocalChanges($context, array_merge($localChangesPayload, array('remoteCommit' => $remoteCommit)), $remoteFiles);
        }
        $exitCode = 0;
        $resetOutput = $completeOnly ? '' : siteUpdateAdminRunCommand(
            array(siteUpdateAdminGetGitBinary(), 'reset', '--hard', $remoteCommit),
            $repoRoot,
            $exitCode
        );
        if ($exitCode !== 0) {
            throw new RuntimeException(siteUpdateAdminFormatCommandOutput($resetOutput) ?: 'Impossible de synchroniser le code avec le depot distant.');
        }
        $requiresCompletion = true;

        siteUpdateAdminWriteState(array(
            'status' => 'running',
            'message' => 'Synchronisation du code terminee. Installation des dependances en cours.',
            'requiresCompletion' => true,
            'backup' => $backup,
            'startedAt' => gmdate('c'),
            'userId' => $actorUserId,
            'forced' => (bool)$force,
            'branch' => (string)$context['tracking'],
            'behindCount' => $behindCount,
            'localCommit' => $localCommit,
            'remoteCommit' => $remoteCommit,
            'localHeadline' => (string)($localSummary['headline'] ?? ''),
            'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
            'localDate' => (string)($localSummary['date'] ?? ''),
            'remoteDate' => (string)($remoteSummary['date'] ?? ''),
        ));

        $composerOutput = siteUpdateAdminRunCommand(
            array_merge($composerCommand, array('install', '--no-dev', '--prefer-dist', '--no-interaction', '--optimize-autoloader')),
            $repoRoot,
            $exitCode
        );
        if ($exitCode !== 0) {
            throw new RuntimeException(siteUpdateAdminFormatCommandOutput($composerOutput) ?: 'L installation des dependances PHP a echoue.');
        }

        siteUpdateAdminWriteState(array(
            'status' => 'running',
            'message' => 'Installation des dependances terminee. Application des migrations en cours.',
            'requiresCompletion' => true,
            'backup' => $backup,
            'startedAt' => gmdate('c'),
            'userId' => $actorUserId,
            'forced' => (bool)$force,
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
            array($phpBinary, $repoRoot . '/scripts/run-migrations.php'),
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
        $completed = true;

        return array(
            'status' => true,
            'updated' => true,
            'message' => 'La mise a jour du site est terminee.'
                . ($backup['backupPath'] !== '' ? ' Sauvegarde locale : ' . $backup['backupPath'] . '.' : '')
                . ($backup['identicalFileCount'] > 0 ? ' ' . $backup['identicalFileCount'] . ' fichiers identiques non sauvegardes.' : ''),
            'backupPath' => $backup['backupPath'],
            'backedUpFileCount' => $backup['backedUpFileCount'],
            'identicalFileCount' => $backup['identicalFileCount'],
            'behindCount' => 0,
            'localCommit' => trim((string)$updatedLocalCommit),
            'remoteCommit' => $remoteCommit,
            'localHeadline' => (string)($updatedSummary['headline'] ?? ''),
            'remoteHeadline' => (string)($remoteSummary['headline'] ?? ''),
            'localDate' => (string)($updatedSummary['date'] ?? ''),
            'remoteDate' => (string)($remoteSummary['date'] ?? ''),
            'branch' => (string)$context['tracking'],
            'forced' => (bool)$force,
            'migrationOutput' => siteUpdateAdminFormatCommandOutput($migrationOutput),
            'composerOutput' => siteUpdateAdminFormatCommandOutput($composerOutput),
            'resetOutput' => siteUpdateAdminFormatCommandOutput($resetOutput),
        );
    } catch (Throwable $exception) {
        if ($requiresCompletion) {
            siteUpdateAdminWriteState(array_merge(siteUpdateAdminReadState(), array(
                'status' => 'failed',
                'requiresCompletion' => true,
                'message' => $exception->getMessage(),
                'backup' => $backup,
            )));
        }
        if ($backup['backupPath'] !== '') {
            throw new RuntimeException($exception->getMessage() . ' Sauvegarde locale disponible : ' . $backup['backupPath'], 0, $exception);
        }
        throw $exception;
    } finally {
        if ($completed) {
            siteUpdateAdminClearState();
        }
        siteUpdateAdminReleaseLock($lockHandle);
    }
}
