<?php
declare(strict_types=1);

namespace dbObject {
    // Only document permissions are stubbed; point locking and DbObject caching are real.
    class Document
    {
        public function load($id): bool { return (int)$id === 10; }
        public function get($field): int { return $field === 'IDorganization' ? 1 : 0; }
        public function canUserOpenPvEditor(int $userId, int $organizationId): bool
        {
            return $userId === 7 && $organizationId === 1;
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/documentpvpoint.class.php';

    final class LockCachePoint extends \dbObject\DocumentPvPoint
    {
        public function getEditingUserDisplayName(int $organizationId = 0): string
        {
            return '';
        }
    }

    // In-memory PDO boundary: keep database rows separate from DbObject::$preload.
    final class LockCachePdo extends PDO
    {
        public array $row = [
            'id' => 1, 'IDdocument' => 10, 'IDuser_editing' => null,
            'edit_lock_token' => null, 'dateedition' => null,
        ];
        public bool $loseNextAcquisition = false;

        public function __construct() {}
        public function prepare(string $query, array $options = []): PDOStatement|false
        {
            return new LockCacheStatement($this, $query);
        }
        public function lastInsertId(?string $name = null): string|false { return '0'; }
    }

    final class LockCacheStatement extends PDOStatement
    {
        private array $params = [];
        private int $affected = 0;

        public function __construct(private LockCachePdo $db, private string $query) {}
        public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
        {
            $this->params[ltrim((string)$param, ':')] = $value;
            return true;
        }
        public function execute(?array $params = null): bool
        {
            if (str_starts_with(strtolower(ltrim($this->query)), 'select')) {
                return true;
            }
            if (!str_starts_with(ltrim($this->query), 'UPDATE document_pv_point')) {
                throw new RuntimeException('Unexpected database operation in lock test.');
            }
            $token = $this->db->row['edit_lock_token'];
            if (isset($this->params['editing_date'])) {
                if ($this->db->loseNextAcquisition) {
                    $this->db->loseNextAcquisition = false;
                    $this->db->row['IDuser_editing'] = 8;
                    $this->db->row['edit_lock_token'] = 'other-session';
                    $this->db->row['dateedition'] = date('Y-m-d H:i:s');
                    return true; // Successful statement, zero affected rows after a race.
                }
                if (!$token || $token === $this->params['current_lock_token']
                    || !$this->db->row['dateedition']
                    || $this->db->row['dateedition'] < $this->params['expired_before']) {
                    $this->db->row['IDuser_editing'] = $this->params['user_id'] ?? null;
                    $this->db->row['edit_lock_token'] = $this->params['lock_token'];
                    $this->db->row['dateedition'] = $this->params['editing_date'];
                    $this->affected = 1;
                }
            } elseif ($token === $this->params['lock_token']) {
                $this->db->row['IDuser_editing'] = null;
                $this->db->row['edit_lock_token'] = null;
                $this->db->row['dateedition'] = null;
                $this->affected = 1;
            }
            return true;
        }
        public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
        {
            return [$this->db->row];
        }
        public function rowCount(): int { return $this->affected; }
        public function closeCursor(): bool { return true; }
    }

    function assertLockCache(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    foreach ([false, true] as $public) {
        $db = new LockCachePdo();
        \dbObject\DbObject::$_dbh = new \dbObject\PdoDbhCompat($db);
        \dbObject\DbObject::$preload = [];
        $point = new LockCachePoint();
        assertLockCache($point->load(1), 'The initial point must load into the real object cache.');
        $acquire = static fn() => $public
            ? $point->touchPublicEditLock(1, 'editor-session')
            : $point->touchEditLock(1, 7, 'editor-session');
        $label = $public ? 'public' : 'authenticated';

        for ($cycle = 0; $cycle < 3; $cycle++) {
            $result = $acquire();
            assertLockCache($result['status'] === true, "$label: the FIRST acquisition after release must succeed.");
            assertLockCache($point->getEditingLockToken() === 'editor-session', 'The object must reflect the database lock.');
            assertLockCache($acquire()['status'] === true, 'Renewing an owned lock must succeed.');
            $release = $public
                ? $point->releasePublicEditLock('editor-session')
                : $point->releaseEditLock(7, 'editor-session');
            assertLockCache($release['status'] === true && !$point->isEditLockActive(), 'Release must clear the lock.');
        }

        $db->loseNextAcquisition = true;
        assertLockCache($acquire()['status'] === false, 'A concurrent acquisition must still be rejected.');
        assertLockCache($point->getEditingLockToken() === 'other-session', 'Conflict responses must reflect the winning session.');
        assertLockCache($acquire()['status'] === false, 'An existing foreign lock must stay protected.');
    }

    echo "pv_point_lock_cache_test: OK\n";
}
