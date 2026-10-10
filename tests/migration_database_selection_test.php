<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Run the real CLI with isolated configuration and database stubs: no SQL is executed.
$fixture = sys_get_temp_dir() . '/omo-migration-test-' . bin2hex(random_bytes(8));
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
};

try {
    foreach (['scripts', 'db', 'includes', 'vendor'] as $directory) {
        mkdir($fixture . '/' . $directory, 0700, true);
    }
    copy(dirname(__DIR__) . '/scripts/run-migrations.php', $fixture . '/scripts/run-migrations.php');
    copy(dirname(__DIR__) . '/includes/env.php', $fixture . '/includes/env.php');
    file_put_contents($fixture . '/vendor/autoload.php', "<?php\n");
    file_put_contents($fixture . '/db/connection.php', <<<'PHP'
<?php
require_once dirname(__DIR__) . '/includes/env.php';
unset($_ENV['DB_MIGRATION_DATABASES'], $_SERVER['DB_MIGRATION_DATABASES']);
putenv('DB_MIGRATION_DATABASES');
loadEnv(dirname(__DIR__) . '/.env');
$GLOBALS['dbName'] = envValue('DB_NAME', '');
function createPDOConnection(?string $databaseName = null): PDO {
    file_put_contents(dirname(__DIR__) . '/selected.jsonl', json_encode($databaseName) . "\n", FILE_APPEND);
    return new class extends PDO { public function __construct() {} };
}
PHP);
    file_put_contents($fixture . '/db/migrations.php', <<<'PHP'
<?php
function getPendingSqlMigrations(PDO $pdo, string $sqlDir): array { return []; }
PHP);

    $cases = [
        ['absent list', "DB_NAME=main\n", [], ['main']],
        ['empty list', "DB_NAME=main\nDB_MIGRATION_DATABASES=\n", [], ['main']],
        ['blank list', "DB_NAME=main\nDB_MIGRATION_DATABASES=\"  \t \"\n", [], ['main']],
        ['multiple databases', "DB_NAME=main\nDB_MIGRATION_DATABASES= first, second,first, \n", [], ['first', 'second']],
        ['CLI database', "DB_NAME=main\nDB_MIGRATION_DATABASES=first,second\n", ['--database=chosen'], ['chosen']],
        ['CLI list', "DB_NAME=main\nDB_MIGRATION_DATABASES=first,second\n", ['--databases=chosen,other,chosen'], ['chosen', 'other']],
        ['CLI separate argument', "DB_NAME=main\nDB_MIGRATION_DATABASES=\n", ['--database', 'chosen'], ['chosen']],
        ['missing database', "DB_NAME=\nDB_MIGRATION_DATABASES=\n", [], []],
        ['explicit empty database', "DB_NAME=main\n", ['--database='], []],
        ['invalid list', "DB_NAME=main\nDB_MIGRATION_DATABASES= , , \n", [], []],
    ];
    foreach ($cases as [$label, $configuration, $arguments, $expected]) {
        file_put_contents($fixture . '/.env', $configuration);
        file_put_contents($fixture . '/selected.jsonl', '');
        $process = proc_open(
            array_merge([PHP_BINARY, $fixture . '/scripts/run-migrations.php'], $arguments),
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $fixture
        );
        $check(is_resource($process), 'Cannot start migration CLI.');
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        $selected = array_map(
            static fn(string $line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
            file($fixture . '/selected.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        );
        $check($selected === $expected, $label . ': unexpected database selection.');
        $check($exitCode === ($expected === [] ? 1 : 0), $label . ': unexpected exit status. ' . $stdout . $stderr);
        $check($expected === [] ? str_starts_with($stderr, '[migrations] ') : $stderr === '', $label . ': unexpected diagnostics.');
    }
    echo "OK: migration database fallback, list normalization, CLI precedence and missing configuration.\n";
} finally {
    foreach (['scripts/run-migrations.php', 'includes/env.php', 'vendor/autoload.php', 'db/connection.php', 'db/migrations.php', '.env', 'selected.jsonl'] as $relative) {
        if (is_file($fixture . '/' . $relative)) { unlink($fixture . '/' . $relative); }
    }
    foreach (['scripts', 'includes', 'vendor', 'db', ''] as $relative) {
        if (is_dir($fixture . '/' . $relative)) { rmdir($fixture . '/' . $relative); }
    }
}
