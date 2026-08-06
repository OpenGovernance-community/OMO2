<?php
namespace dbObject;

class Notification extends DbObject
{
    public static function tableName()
    {
        return 'notification';
    }

    public static function rules()
    {
        return [
            [['IDuser', 'IDorganization', 'event_key', 'source_key', 'title'], 'required'],
            [['id'], 'integer'],
            [['IDuser', 'IDorganization'], 'fk'],
            [['event_key', 'source_key', 'dedupe_key', 'title', 'url', 'open_token'], 'string'],
            [['body'], 'text'],
            [['read_at', 'created_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'IDuser' => 'Utilisateur',
            'IDorganization' => 'Organisation',
            'event_key' => 'Evenement',
            'source_key' => 'Source',
            'dedupe_key' => 'Code de regroupement',
            'title' => 'Titre',
            'body' => 'Message',
            'url' => 'Lien',
            'open_token' => 'Jeton de lien',
            'read_at' => 'Lu le',
            'created_at' => 'Cree le',
        ];
    }

    public static function attributeLength()
    {
        return ['event_key' => 80, 'source_key' => 190, 'dedupe_key' => 190, 'title' => 250, 'url' => 1000, 'open_token' => 64];
    }

    public static function getOrder()
    {
        return 'created_at DESC, id DESC';
    }

    public static function isStorageAvailable()
    {
        return self::tableExists(self::tableName());
    }

    public static function createForUser($userId, $organizationId, $eventKey, $sourceKey, $title, $body = '', $url = '', $dedupeKey = '')
    {
        if (!self::isStorageAvailable()) {
            return null;
        }
        $dedupeKey = mb_substr(trim((string)$dedupeKey), 0, 190, 'UTF-8');
        $item = new self();
        if ($item->load([
            ['IDuser', (int)$userId],
            ['source_key', mb_substr(trim((string)$sourceKey), 0, 190, 'UTF-8')],
        ])) {
            return null;
        }
        if ($dedupeKey !== '') {
            $existingUnreadId = (int)self::fetchValue(
                'SELECT `id` FROM `notification` WHERE `IDuser` = :user_id AND `IDorganization` = :organization_id AND `dedupe_key` = :dedupe_key AND `read_at` IS NULL LIMIT 1',
                [
                    'user_id' => (int)$userId,
                    'organization_id' => (int)$organizationId,
                    'dedupe_key' => $dedupeKey,
                ]
            );
            if ($existingUnreadId > 0) {
                return null;
            }
        }
        $item->set('IDuser', (int)$userId);
        $item->set('IDorganization', (int)$organizationId);
        $item->set('event_key', mb_substr(trim((string)$eventKey), 0, 80, 'UTF-8'));
        $item->set('source_key', mb_substr(trim((string)$sourceKey), 0, 190, 'UTF-8'));
        $item->set('dedupe_key', $dedupeKey !== '' ? $dedupeKey : null);
        $item->set('title', mb_substr(trim((string)$title), 0, 250, 'UTF-8'));
        $item->set('body', trim((string)$body) !== '' ? trim((string)$body) : null);
        $item->set('url', trim((string)$url) !== '' ? mb_substr(trim((string)$url), 0, 1000, 'UTF-8') : null);
        try {
            $item->set('open_token', bin2hex(random_bytes(32)));
        } catch (\Throwable $exception) {
            return null;
        }
        $item->set('created_at', new \DateTimeImmutable('now'));
        $result = $item->save();
        return is_array($result) && !empty($result['status']) ? $item : null;
    }

    public function getOpenUrl()
    {
        $token = trim((string)$this->get('open_token'));
        if ($token === '') {
            return trim((string)$this->get('url'));
        }

        return '/omo/notifications/open.php?token=' . rawurlencode($token);
    }

    public static function findByOpenToken($token)
    {
        $token = trim((string)$token);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = self::fetchRow('SELECT * FROM `notification` WHERE `open_token` = :open_token LIMIT 1', ['open_token' => $token]);
        if (!is_array($row)) {
            return null;
        }
        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)($row['id'] ?? 0));
        return $item;
    }

    public static function markReadByOpenToken($token)
    {
        $token = trim((string)$token);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }
        return self::execute(
            'UPDATE `notification` SET `read_at` = COALESCE(`read_at`, NOW()) WHERE `open_token` = :open_token',
            ['open_token' => $token]
        );
    }

    public static function getInboxForUser($userId, $organizationId, $limit = 30)
    {
        $limit = max(1, min(100, (int)$limit));
        $rows = self::fetchAll(
            'SELECT * FROM `notification` WHERE `IDuser` = :user_id AND `IDorganization` = :organization_id ORDER BY `read_at` IS NULL DESC, `created_at` DESC, `id` DESC LIMIT ' . $limit,
            ['user_id' => (int)$userId, 'organization_id' => (int)$organizationId]
        );
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $item = new self();
            $item->loadFromArray($row);
            $item->setId((int)$row['id']);
            $items[] = $item;
        }
        return $items;
    }

    public static function countUnreadForUser($userId, $organizationId)
    {
        $row = self::fetchRow(
            'SELECT COUNT(*) AS `count` FROM `notification` WHERE `IDuser` = :user_id AND `IDorganization` = :organization_id AND `read_at` IS NULL',
            ['user_id' => (int)$userId, 'organization_id' => (int)$organizationId]
        );
        return is_array($row) ? (int)($row['count'] ?? 0) : 0;
    }

    public static function markReadForUser($userId, $organizationId, $notificationId = 0, $url = '')
    {
        $where = '`IDuser` = :user_id AND `IDorganization` = :organization_id AND `read_at` IS NULL';
        $parameters = ['user_id' => (int)$userId, 'organization_id' => (int)$organizationId];
        if ((int)$notificationId > 0) {
            $where .= ' AND `id` = :notification_id';
            $parameters['notification_id'] = (int)$notificationId;
        } elseif (trim((string)$url) !== '') {
            $where .= ' AND `url` = :url';
            $parameters['url'] = trim((string)$url);
        } else {
            return false;
        }
        return self::execute('UPDATE `notification` SET `read_at` = NOW() WHERE ' . $where, $parameters);
    }
}
