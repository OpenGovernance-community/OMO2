<?php
declare(strict_types=1);

// Isolated from bootstrap: no real database or authentication state is used.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'dbObject\\')) {
        require_once dirname(__DIR__) . '/class/dbobject/' . strtolower(substr($class, 9)) . '.class.php';
    }
});

final class TeamBatchPdo extends PDO
{
    public array $queries = [];
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries[] = $query;
        return new TeamBatchStatement($query);
    }
}

final class TeamBatchStatement extends PDOStatement
{
    private array $params = [];
    public function __construct(private string $query) {}
    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $this->params[ltrim((string)$param, ':')] = (int)$value;
        return true;
    }
    public function execute(?array $params = null): bool { return true; }
    public function closeCursor(): bool { return true; }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        $isMembership = str_contains($this->query, 'from `user_organization`');
        assertTeamBatch($isMembership || str_contains($this->query, 'from `user`'), 'Only expected tables loaded.');
        $ids = array_values($this->params);
        $organizationId = $isMembership ? array_shift($ids) : 0;
        if ($isMembership) {
            assertTeamBatch(str_contains($this->query, '`IDorganization` = :w_0'), 'Membership query is organization-scoped.');
        }
        return array_map(static fn ($id) => $isMembership
            ? ['id' => $id + 1000, 'IDuser' => $id, 'IDorganization' => $organizationId, 'active' => 1, 'username' => 'member-' . $id]
            : ['id' => $id, 'username' => 'user-' . $id, 'firstname' => 'First-' . $id], $ids);
    }
}

function assertTeamBatch(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach ([1, 3, 100] as $size) {
    $db = new TeamBatchPdo();
    // Loading DbObject also defines PdoDbhCompat.
    class_exists(\dbObject\DbObject::class);
    \dbObject\DbObject::$_dbh = new \dbObject\PdoDbhCompat($db);
    \dbObject\DbObject::$preload = [];
    $ids = range(1, $size);
    $context = \dbObject\UserOrganization::loadTeamMemberContext(7, array_merge($ids, [1, 0, -1]));
    assertTeamBatch(count($context['users']) === $size && count($context['memberships']) === $size, 'IDs normalized.');
    foreach ($ids as $id) {
        assertTeamBatch($context['users'][$id]->get('firstname') === 'First-' . $id, 'User fields hydrated.');
        assertTeamBatch((int)$context['memberships'][$id]->get('IDorganization') === 7, 'Membership scope preserved.');
    }
    assertTeamBatch(count($db->queries) === 2, 'Exactly two queries regardless of team size, including field reads.');
    \dbObject\UserOrganization::loadTeamMemberContext(7, $ids, $context['memberships']);
    assertTeamBatch(count($db->queries) === 2, 'Already loaded users and memberships are reused.');
    $other = \dbObject\UserOrganization::loadTeamMemberContext(8, $ids, $context['memberships']);
    assertTeamBatch(count($db->queries) === 3, 'Cross-organization memberships are reloaded in one batch.');
    assertTeamBatch((int)$other['memberships'][1]->get('IDorganization') === 8, 'No cross-organization membership reuse.');
    \dbObject\UserOrganization::loadTeamMemberContext(0, $ids);
    \dbObject\UserOrganization::loadTeamMemberContext(7, []);
    assertTeamBatch(count($db->queries) === 3, 'Invalid or empty input produces no query.');
}
echo "OK: team batching for 1, 3 and 100 members, hydration, cache reuse and organization scope.\n";
