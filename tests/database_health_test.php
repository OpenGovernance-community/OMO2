<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';

final class HealthProbeStatement extends PDOStatement
{
    public mixed $value = 1;
    public bool $closed = false;
    public function fetchColumn(int $column = 0): mixed { return $this->value; }
    public function closeCursor(): bool { $this->closed = true; return true; }
}

final class HealthProbePdo extends PDO
{
    public array $queries = [];
    public bool $fail = false;
    public bool $throw = false;
    public HealthProbeStatement $statement;
    public function __construct() { $this->statement = new HealthProbeStatement(); }
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->queries[] = $query;
        if ($this->throw) { throw new PDOException('Private SQL diagnostic.'); }
        return $this->fail ? false : $this->statement;
    }
}

final class HealthProbeObject extends \dbObject\DbObject
{
    public static HealthProbePdo $connection;
    public static array $timeouts = [];
    public static bool $failConnection = false;
    protected static function createPdoConnection(?int $timeoutSeconds = null): PDO
    {
        self::$timeouts[] = $timeoutSeconds;
        if (self::$failConnection) { throw new PDOException('Private connection diagnostic.'); }
        return self::$connection;
    }
    public static function rules() { return []; }
    public static function attributeLabels() { return []; }
}

function assertDatabaseHealth(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$existing = new \dbObject\PdoDbhCompat(new HealthProbePdo());
\dbObject\DbObject::$_dbh = $existing;
HealthProbeObject::$connection = new HealthProbePdo();
assertDatabaseHealth(HealthProbeObject::checkDatabaseHealth(), 'Numeric one must be healthy.');
assertDatabaseHealth(HealthProbeObject::$connection->statement->closed, 'The query cursor must be closed.');
HealthProbeObject::$connection->statement->value = '1';
assertDatabaseHealth(HealthProbeObject::checkDatabaseHealth(), 'String one must be healthy.');
assertDatabaseHealth(HealthProbeObject::$timeouts === [3, 3], 'Each probe must request a fresh, bounded connection.');
assertDatabaseHealth(HealthProbeObject::$connection->queries === ['SELECT 1', 'SELECT 1'], 'The probe must only issue a constant read.');
assertDatabaseHealth(\dbObject\DbObject::$_dbh === $existing, 'The regular application connection must not be replaced.');
HealthProbeObject::$connection->statement->value = false;
assertDatabaseHealth(!HealthProbeObject::checkDatabaseHealth(), 'Unexpected query results must fail.');
HealthProbeObject::$connection->fail = true;
assertDatabaseHealth(!HealthProbeObject::checkDatabaseHealth(), 'Failed queries must fail.');

foreach (['query', 'connection'] as $failure) {
    HealthProbeObject::$failConnection = $failure === 'connection';
    HealthProbeObject::$connection->throw = $failure === 'query';
    ob_start();
    try {
        HealthProbeObject::checkDatabaseHealth();
        throw new RuntimeException('Expected a PDOException.');
    } catch (PDOException $exception) {
        assertDatabaseHealth(ob_get_contents() === '', 'Database failures must not echo private diagnostics.');
    } finally {
        ob_end_clean();
    }
}
echo "database_health_test: OK\n";
