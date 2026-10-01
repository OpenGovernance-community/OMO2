<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/objectvisibility.class.php';
require_once dirname(__DIR__) . '/class/dbobject/event.class.php';
require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';

final class ReadMemoPdo extends PDO
{
    public int $queries = 0;
    public function __construct() {}
    public function lastInsertId(?string $name = null): string|false { return '0'; }
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries++;
        return new ReadMemoStatement();
    }
}
final class ReadMemoStatement extends PDOStatement
{
    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool { return true; }
    public function execute(?array $params = null): bool { return true; }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return [['id' => 10, 'object_id' => 1, 'IDorganization' => 7, 'visibility_type' => 'organization']];
    }
    public function closeCursor(): bool { return true; }
    public function rowCount(): int { return 1; }
}
final class ReadMemoProbe extends \dbObject\DbObject
{
    public static function rules() { return []; }
    public static function attributeLabels() { return []; }
    public static function read(array $key, callable $load) { return self::memoizeRead($key, $load); }
}
final class CalendarPreviewProbe extends \dbObject\Event
{
    public int $loads = 0;
    public function load($id, $forced = false) { $this->loads++; return false; }
}
function assertReadMemo(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$pdo = new ReadMemoPdo();
\dbObject\DbObject::$_dbh = new \dbObject\PdoDbhCompat($pdo);
$calls = 0;
$load = static function () use (&$calls): bool { $calls++; return false; };
ReadMemoProbe::read(['user', 1, 7], $load);
ReadMemoProbe::read(['user', 1, 7], $load);
assertReadMemo($calls === 2, 'Default mode must always read fresh data.');
ReadMemoProbe::enableReadOnlyMemoization();
ReadMemoProbe::read(['user', 1, 7], $load);
ReadMemoProbe::read(['user', 1, 7], $load);
assertReadMemo($calls === 3, 'Denied results are memoized too.');
ReadMemoProbe::read(['user', 2, 7], $load);
ReadMemoProbe::read(['user', 1, 8], $load);
assertReadMemo($calls === 5, 'Different user and organization keys must remain isolated.');
ReadMemoProbe::invalidateReadMemoForSql('SELECT id FROM probe');
ReadMemoProbe::read(['user', 1, 7], $load);
assertReadMemo($calls === 5, 'Reads must retain the memo.');
$saved = new ReadMemoProbe();
$saved->hydrateFromDatabaseRow(['id' => 1], true);
$saved->set('active', 0);
$saved->save(); // The real DbObject write path must disable the read memo.
ReadMemoProbe::read(['user', 1, 7], $load);
ReadMemoProbe::read(['user', 1, 7], $load);
assertReadMemo($calls === 7, 'Writes clear and disable memoization.');

ReadMemoProbe::enableReadOnlyMemoization();
$pdo->queries = 0;
\dbObject\ObjectVisibility::loadActiveRuleRows('document', [1, 2], 7);
assertReadMemo($pdo->queries === 1, 'Visibility rules loaded in one batch.');
assertReadMemo(\dbObject\ObjectVisibility::loadActiveRuleRow('document', 1, 7)['id'] === 10, 'Batch result reused.');
assertReadMemo(\dbObject\ObjectVisibility::loadActiveRuleRow('document', 2, 7) === null, 'Missing rule reused without changing fallback behavior.');
assertReadMemo($pdo->queries === 1, 'No per-object query after the batch.');
\dbObject\ObjectVisibility::loadActiveRuleRow('document_edit', 1, 7);
\dbObject\ObjectVisibility::loadActiveRuleRow('document', 1, 8);
assertReadMemo($pdo->queries === 3, 'Visibility type and organization remain isolated.');
ReadMemoProbe::enableReadOnlyMemoization(false);
\dbObject\ObjectVisibility::loadActiveRuleRow('document', 1, 7);
assertReadMemo($pdo->queries === 4, 'No visibility memo outside opted-in rendering.');
$preview = new CalendarPreviewProbe();
$preview->hydrateFromDatabaseRow(['id' => 1000000000], true);
unset(\dbObject\DbObject::$preload['event_1000000000']);
$preview->set('title', 'External event');
$preview->set('start_at', new DateTimeImmutable('2026-09-23 09:00:00'));
assertReadMemo($preview->get('title') === 'External event', 'Preview fields remain available.');
$preview->get('IDholon');
assertReadMemo($preview->loads === 0, 'Synthetic events must not query a nonexistent database row.');
assertReadMemo(!isset(\dbObject\DbObject::$preload['event_1000000000']), 'Preview is not in the persistent-object preload map.');
echo "OK: opt-in memo, denied/missing results, isolation, write invalidation and batch visibility reuse.\n";
