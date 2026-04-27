<?php

function isAutoSqlMigrationFile(string $path): bool
{
    $sql = file_get_contents($path);

    if ($sql === false) {
        throw new RuntimeException('Impossible de lire le fichier SQL : ' . $path);
    }

    return preg_match('/^\s*--\s*@migration\b/im', $sql) === 1;
}

function ensureSqlMigrationTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `sql_migration` (
            `filename` varchar(255) NOT NULL,
            `checksum` char(64) NOT NULL,
            `executed_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function listSqlMigrationFiles(string $sqlDir): array
{
    if (!is_dir($sqlDir)) {
        throw new RuntimeException('Le dossier SQL est introuvable : ' . $sqlDir);
    }

    $pattern = rtrim($sqlDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql';
    $files = glob($pattern);

    if ($files === false) {
        throw new RuntimeException('Impossible de lire les migrations SQL dans : ' . $sqlDir);
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    $migrations = [];

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        if (!isAutoSqlMigrationFile($file)) {
            continue;
        }

        $filename = basename($file);
        $checksum = hash_file('sha256', $file);

        if ($checksum === false) {
            throw new RuntimeException('Impossible de calculer le checksum de : ' . $filename);
        }

        $migrations[] = [
            'path' => $file,
            'filename' => $filename,
            'checksum' => $checksum,
        ];
    }

    return $migrations;
}

function getAppliedSqlMigrations(PDO $pdo): array
{
    ensureSqlMigrationTable($pdo);

    $statement = $pdo->query('SELECT filename, checksum FROM `sql_migration`');
    $applied = [];

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $applied[$row['filename']] = $row['checksum'];
    }

    return $applied;
}

function getPendingSqlMigrations(PDO $pdo, string $sqlDir): array
{
    $files = listSqlMigrationFiles($sqlDir);
    $applied = getAppliedSqlMigrations($pdo);
    $pending = [];

    foreach ($files as $migration) {
        $filename = $migration['filename'];
        $checksum = $migration['checksum'];

        if (isset($applied[$filename])) {
            if ($applied[$filename] !== $checksum) {
                throw new RuntimeException(
                    'La migration déjà exécutée a été modifiée : ' . $filename
                );
            }

            continue;
        }

        $pending[] = $migration;
    }

    return $pending;
}

function loadSqlMigrationContent(string $path): string
{
    $sql = file_get_contents($path);

    if ($sql === false) {
        throw new RuntimeException('Impossible de lire le fichier SQL : ' . $path);
    }

    if (strncmp($sql, "\xEF\xBB\xBF", 3) === 0) {
        $sql = substr($sql, 3);
    }

    $sql = trim($sql);

    if ($sql === '') {
        throw new RuntimeException('Le fichier SQL est vide : ' . basename($path));
    }

    return $sql;
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $quote = null;
    $inLineComment = false;
    $inBlockComment = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $nextChar = $index + 1 < $length ? $sql[$index + 1] : '';
        $previousChar = $index > 0 ? $sql[$index - 1] : '';

        if ($inLineComment) {
            $buffer .= $char;

            if ($char === "\n") {
                $inLineComment = false;
            }

            continue;
        }

        if ($inBlockComment) {
            $buffer .= $char;

            if ($previousChar === '*' && $char === '/') {
                $inBlockComment = false;
            }

            continue;
        }

        if ($quote !== null) {
            $buffer .= $char;

            if (
                ($quote === '\'' || $quote === '"')
                && $char === $quote
                && $nextChar === $quote
            ) {
                $buffer .= $nextChar;
                $index++;
                continue;
            }

            if ($char === $quote && $previousChar !== '\\') {
                $quote = null;
            }

            continue;
        }

        if ($char === '-' && $nextChar === '-') {
            $thirdChar = $index + 2 < $length ? $sql[$index + 2] : '';
            if ($thirdChar === '' || ctype_space($thirdChar)) {
                $buffer .= $char;
                $inLineComment = true;
                continue;
            }
        }

        if ($char === '#') {
            $buffer .= $char;
            $inLineComment = true;
            continue;
        }

        if ($char === '/' && $nextChar === '*') {
            $buffer .= $char;
            $inBlockComment = true;
            continue;
        }

        if ($char === '\'' || $char === '"' || $char === '`') {
            $buffer .= $char;
            $quote = $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim($buffer);

            if ($statement !== '') {
                $statements[] = $statement;
            }

            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);

    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function executeSqlMigrationStatements(PDO $pdo, string $sql): void
{
    foreach (splitSqlStatements($sql) as $statementSql) {
        $statement = $pdo->prepare($statementSql);
        $statement->execute();
        $statement->closeCursor();
    }
}

function markSqlMigrationAsExecuted(PDO $pdo, string $filename, string $checksum): void
{
    $statement = $pdo->prepare(
        'INSERT INTO `sql_migration` (`filename`, `checksum`) VALUES (:filename, :checksum)'
    );

    $statement->execute([
        'filename' => $filename,
        'checksum' => $checksum,
    ]);
}

function runSqlMigrations(PDO $pdo, string $sqlDir): void
{
    $pendingMigrations = getPendingSqlMigrations($pdo, $sqlDir);

    foreach ($pendingMigrations as $migration) {
        $sql = loadSqlMigrationContent($migration['path']);
        executeSqlMigrationStatements($pdo, $sql);
        markSqlMigrationAsExecuted($pdo, $migration['filename'], $migration['checksum']);
    }
}
