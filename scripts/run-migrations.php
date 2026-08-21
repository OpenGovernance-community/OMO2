<?php

require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/db/migrations.php';

function ensureMigrationComposerDependencies(): void
{
    $projectRoot = dirname(__DIR__);
    $autoloadPath = $projectRoot . '/vendor/autoload.php';
    if (is_file($autoloadPath)) {
        return;
    }

    if (!function_exists('exec')) {
        throw new RuntimeException('Les dependances PHP sont absentes et Composer ne peut pas etre lance sur ce serveur.');
    }

    $composerBinary = trim((string)envValue('SITE_UPDATE_COMPOSER_BINARY', 'composer'));
    if ($composerBinary === '') {
        $composerBinary = 'composer';
    }

    $command = escapeshellarg($composerBinary)
        . ' install --no-dev --prefer-dist --no-interaction --optimize-autoloader 2>&1';
    $originalDirectory = getcwd();
    if (!@chdir($projectRoot)) {
        throw new RuntimeException('Impossible d acceder au dossier du projet pour installer les dependances PHP.');
    }

    try {
        echo "Dependances PHP absentes. Installation avec Composer.\n";
        $outputLines = array();
        exec($command, $outputLines, $exitCode);
        if ($outputLines !== array()) {
            echo implode("\n", $outputLines) . "\n";
        }
    } finally {
        if ($originalDirectory !== false) {
            @chdir($originalDirectory);
        }
    }

    if ((int)$exitCode !== 0 || !is_file($autoloadPath)) {
        throw new RuntimeException('L installation des dependances PHP a echoue.');
    }
}

function displayMigrationHelp(): void
{
    echo "Usage : php scripts/run-migrations.php [--sql-dir=CHEMIN] [--database=BASE] [--databases=BASE1,BASE2]\n";
    echo "Sans option, la base définie par DB_NAME est utilisée.\n";
    echo "La variable d'environnement DB_MIGRATION_DATABASES peut aussi contenir une liste séparée par des virgules.\n";
    echo "Seuls les fichiers SQL contenant le marqueur '-- @migration' sont exécutés automatiquement.\n";
}

function parseMigrationCliOptions(array $argv): array
{
    $sqlDir = dirname(__DIR__) . '/sql';
    $databaseNames = [];

    for ($index = 1; $index < count($argv); $index++) {
        $argument = $argv[$index];

        if ($argument === '--help' || $argument === '-h') {
            displayMigrationHelp();
            exit(0);
        }

        if (strpos($argument, '--sql-dir=') === 0) {
            $sqlDir = substr($argument, strlen('--sql-dir='));
            continue;
        }

        if ($argument === '--sql-dir' && isset($argv[$index + 1])) {
            $sqlDir = $argv[++$index];
            continue;
        }

        if (strpos($argument, '--database=') === 0) {
            $databaseNames[] = substr($argument, strlen('--database='));
            continue;
        }

        if ($argument === '--database' && isset($argv[$index + 1])) {
            $databaseNames[] = $argv[++$index];
            continue;
        }

        if (strpos($argument, '--databases=') === 0) {
            $databaseNames = array_merge(
                $databaseNames,
                explode(',', substr($argument, strlen('--databases=')))
            );
            continue;
        }

        throw new InvalidArgumentException('Option inconnue : ' . $argument);
    }

    if ($databaseNames === []) {
        $databaseNames = explode(',', (string)envValue('DB_MIGRATION_DATABASES', (string)$GLOBALS['dbName']));
    }

    $databaseNames = array_values(array_unique(array_filter(array_map(
        static function ($databaseName) {
            return trim((string)$databaseName);
        },
        $databaseNames
    ))));

    if ($databaseNames === []) {
        throw new InvalidArgumentException('Aucune base de données n\'a été fournie pour les migrations.');
    }

    return [
        'sqlDir' => $sqlDir,
        'databaseNames' => $databaseNames,
    ];
}

try {
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Ce script doit être exécuté en ligne de commande.');
    }

    ensureMigrationComposerDependencies();

    $options = parseMigrationCliOptions($argv);
    $sqlDir = $options['sqlDir'];
    $databaseNames = $options['databaseNames'];

    foreach ($databaseNames as $databaseName) {
        echo "Base : {$databaseName}\n";

        $pdo = createPDOConnection($databaseName);
        $pendingMigrations = getPendingSqlMigrations($pdo, $sqlDir);

        if ($pendingMigrations === []) {
            echo "  - Aucune migration à appliquer.\n";
            continue;
        }

        foreach ($pendingMigrations as $migration) {
            echo "  - Application de {$migration['filename']}\n";
        }

        runSqlMigrations($pdo, $sqlDir);
        echo "  - Terminé.\n";
    }

    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, '[migrations] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
