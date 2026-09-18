<?php
namespace dbObject;

class ProjectFollower extends DbObject
{
    public static function tableName()
    {
        return 'project_follower';
    }

    public static function rules()
    {
        return [
            [['IDproject', 'IDuser'], 'required'],
            [['id'], 'integer'],
            [['IDproject', 'IDuser'], 'fk'],
            [['datecreation'], 'datetime'],
            [['active'], 'boolean'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDproject' => 'Projet',
            'IDuser' => 'Personne',
            'datecreation' => 'Date de suivi',
            'active' => 'Actif',
        ];
    }

    public static function getOrder()
    {
        return 'datecreation ASC, id ASC';
    }

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        $params = array('organization_id' => (int)$organizationId, 'user_id' => (int)$userId);
        if (!self::execute("DELETE pf FROM project_follower pf INNER JOIN project p ON p.id = pf.IDproject WHERE p.IDorganization = :organization_id AND pf.IDuser = :user_id AND p.active = 1 AND COALESCE(p.status, '') != 'done'", $params)) {
            return false;
        }
        return self::execute("UPDATE project_follower pf INNER JOIN project p ON p.id = pf.IDproject SET pf.IDuser = :ghost_user_id WHERE p.IDorganization = :organization_id AND pf.IDuser = :user_id", array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId));
    }

    public function save()
    {
        if ((int)$this->getId() <= 0 && !($this->get('datecreation') instanceof \DateTimeInterface)) {
            $this->set('datecreation', new \DateTime());
        }

        return parent::save();
    }

    public static function setFollowing(int $projectId, int $userId, bool $following): bool
    {
        if ($projectId <= 0 || $userId <= 0) {
            return false;
        }

        $rows = self::fetchAll(
            'SELECT id FROM project_follower WHERE IDproject = :project_id AND IDuser = :user_id ORDER BY id ASC LIMIT 1',
            ['project_id' => $projectId, 'user_id' => $userId]
        );
        $followerId = is_array($rows) && isset($rows[0]['id']) ? (int)$rows[0]['id'] : 0;

        if ($followerId <= 0) {
            if (!$following) {
                return true;
            }

            $follower = new self();
            $follower->set('IDproject', $projectId);
            $follower->set('IDuser', $userId);
            $follower->set('active', 1);
        } else {
            $follower = new self();
            if (!$follower->load($followerId)) {
                return false;
            }
            $follower->set('active', $following ? 1 : 0);
        }

        $result = $follower->save();
        return is_array($result) && !empty($result['status']);
    }

    public static function getActiveUserIdsForProject(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT IDuser FROM project_follower WHERE IDproject = :project_id AND active = 1 ORDER BY id ASC',
            ['project_id' => $projectId]
        );
        $userIds = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $userId = (int)($row['IDuser'] ?? 0);
            if ($userId > 0) {
                $userIds[$userId] = $userId;
            }
        }

        return array_values($userIds);
    }

    public static function getActiveProjectIds(array $projectIds): array
    {
        $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), static function (int $projectId): bool {
            return $projectId > 0;
        })));
        if (count($projectIds) === 0) {
            return [];
        }

        $params = [];
        $placeholders = [];
        foreach ($projectIds as $index => $projectId) {
            $parameterName = 'project_' . $index;
            $placeholders[] = ':' . $parameterName;
            $params[$parameterName] = $projectId;
        }

        $rows = self::fetchAll(
            'SELECT DISTINCT IDproject FROM project_follower WHERE active = 1 AND IDproject IN (' . implode(', ', $placeholders) . ')',
            $params
        );
        $activeProjectIds = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $projectId = (int)($row['IDproject'] ?? 0);
            if ($projectId > 0) {
                $activeProjectIds[$projectId] = $projectId;
            }
        }

        return $activeProjectIds;
    }

    public static function deleteForProjectIds(array $projectIds): bool
    {
        $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), static function (int $projectId): bool {
            return $projectId > 0;
        })));
        if (count($projectIds) === 0) {
            return true;
        }

        $params = [];
        $placeholders = [];
        foreach ($projectIds as $index => $projectId) {
            $parameterName = 'project_' . $index;
            $placeholders[] = ':' . $parameterName;
            $params[$parameterName] = $projectId;
        }

        return self::execute(
            'DELETE FROM project_follower WHERE IDproject IN (' . implode(', ', $placeholders) . ')',
            $params
        );
    }

    public static function getFollowerCardsByProjectIds(array $projectIds): array
    {
        $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), static function (int $projectId): bool {
            return $projectId > 0;
        })));
        if (count($projectIds) === 0) {
            return [];
        }

        $params = [];
        $placeholders = [];
        foreach ($projectIds as $index => $projectId) {
            $parameterName = 'project_' . $index;
            $placeholders[] = ':' . $parameterName;
            $params[$parameterName] = $projectId;
        }

        $rows = self::fetchAll(
            'SELECT pf.IDproject AS project_id, pf.IDuser AS user_id, u.firstname, u.lastname, u.username, u.email
             FROM project_follower pf
             INNER JOIN user u ON u.id = pf.IDuser
             WHERE pf.active = 1
               AND pf.IDproject IN (' . implode(', ', $placeholders) . ')
             ORDER BY pf.IDproject ASC, pf.datecreation ASC, pf.id ASC',
            $params
        );
        if (!is_array($rows)) {
            return [];
        }

        $followersByProjectId = [];
        foreach ($rows as $row) {
            $projectId = (int)($row['project_id'] ?? 0);
            $userId = (int)($row['user_id'] ?? 0);
            if ($projectId <= 0 || $userId <= 0) {
                continue;
            }

            $label = trim(trim((string)($row['firstname'] ?? '')) . ' ' . trim((string)($row['lastname'] ?? '')));
            if ($label === '') {
                $label = trim((string)($row['username'] ?? ''));
            }
            if ($label === '') {
                $label = trim((string)($row['email'] ?? ''));
            }
            if ($label === '') {
                continue;
            }

            $followersByProjectId[$projectId][] = [
                'userId' => $userId,
                'label' => $label,
            ];
        }

        return $followersByProjectId;
    }
}
