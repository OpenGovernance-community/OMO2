<?php
namespace dbObject;

class NotificationPushSubscription extends DbObject
{
    public static function tableName()
    {
        return 'notification_push_subscription';
    }

    public static function rules()
    {
        return [
            [['IDuser', 'endpoint_hash', 'endpoint', 'p256dh_key', 'auth_key'], 'required'],
            [['id'], 'integer'],
            [['IDuser'], 'fk'],
            [['endpoint_hash', 'endpoint', 'p256dh_key', 'auth_key', 'user_agent'], 'string'],
            [['last_error'], 'text'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at', 'last_seen_at', 'last_sent_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDuser' => 'Utilisateur',
            'endpoint_hash' => 'Empreinte endpoint',
            'endpoint' => 'Endpoint push',
            'p256dh_key' => 'Cle P256DH',
            'auth_key' => 'Cle auth',
            'user_agent' => 'Navigateur',
            'active' => 'Actif',
            'last_error' => 'Derniere erreur',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
            'last_seen_at' => 'Derniere confirmation',
            'last_sent_at' => 'Dernier envoi',
        ];
    }

    public static function attributeLength()
    {
        return [
            'endpoint_hash' => 64,
            'endpoint' => 2000,
            'p256dh_key' => 200,
            'auth_key' => 100,
            'user_agent' => 1000,
        ];
    }

    public static function getOrder()
    {
        return 'updated_at DESC, id DESC';
    }

    public static function isStorageAvailable()
    {
        return self::tableExists(self::tableName());
    }

    public static function normalizeSubscription(array $subscription)
    {
        $endpoint = trim((string)($subscription['endpoint'] ?? ''));
        $keys = isset($subscription['keys']) && is_array($subscription['keys']) ? $subscription['keys'] : [];
        $p256dhKey = trim((string)($keys['p256dh'] ?? ''));
        $authKey = trim((string)($keys['auth'] ?? ''));
        $endpointScheme = strtolower((string)parse_url($endpoint, PHP_URL_SCHEME));

        if (
            $endpoint === ''
            || $endpointScheme !== 'https'
            || !filter_var($endpoint, FILTER_VALIDATE_URL)
            || $p256dhKey === ''
            || $authKey === ''
            || strlen($endpoint) > 2000
            || strlen($p256dhKey) > 200
            || strlen($authKey) > 100
        ) {
            return null;
        }

        return [
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'p256dh_key' => $p256dhKey,
            'auth_key' => $authKey,
        ];
    }

    public static function findByEndpointHash($endpointHash)
    {
        $endpointHash = trim((string)$endpointHash);
        if ($endpointHash === '' || !self::isStorageAvailable()) {
            return null;
        }

        $subscription = new self();
        return $subscription->load(['endpoint_hash', $endpointHash]) ? $subscription : null;
    }

    public static function upsertForUser($userId, array $subscription, $userAgent = '')
    {
        $userId = (int)$userId;
        $normalized = self::normalizeSubscription($subscription);
        if ($userId <= 0 || !is_array($normalized) || !self::isStorageAvailable()) {
            return null;
        }

        $item = self::findByEndpointHash($normalized['endpoint_hash']);
        if (!$item instanceof self) {
            $item = new self();
            $item->set('created_at', new \DateTime());
        }

        $item->set('IDuser', $userId);
        $item->set('endpoint_hash', $normalized['endpoint_hash']);
        $item->set('endpoint', $normalized['endpoint']);
        $item->set('p256dh_key', $normalized['p256dh_key']);
        $item->set('auth_key', $normalized['auth_key']);
        $item->set('user_agent', mb_substr(trim((string)$userAgent), 0, 1000, 'UTF-8'));
        $item->set('active', true);
        $item->set('last_error', null);
        $item->set('last_seen_at', new \DateTime());
        $item->set('updated_at', new \DateTime());

        $saveResult = $item->save();
        return is_array($saveResult) && !empty($saveResult['status']) ? $item : null;
    }

    public static function deactivateForUser($userId, array $subscription)
    {
        $userId = (int)$userId;
        $normalized = self::normalizeSubscription($subscription);
        if ($userId <= 0 || !is_array($normalized) || !self::isStorageAvailable()) {
            return false;
        }

        return self::execute(
            'UPDATE `notification_push_subscription`
             SET `active` = 0, `updated_at` = NOW()
             WHERE `IDuser` = :user_id AND `endpoint_hash` = :endpoint_hash',
            [
                'user_id' => $userId,
                'endpoint_hash' => $normalized['endpoint_hash'],
            ]
        );
    }

    public static function findActiveForUserIds(array $userIds)
    {
        if (!self::isStorageAvailable()) {
            return [];
        }

        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static function ($userId) {
            return $userId > 0;
        })));
        if (count($userIds) === 0) {
            return [];
        }

        $parameters = ['active' => 1];
        $placeholders = [];
        foreach ($userIds as $index => $userId) {
            $placeholder = 'user_id_' . $index;
            $placeholders[] = ':' . $placeholder;
            $parameters[$placeholder] = $userId;
        }

        $rows = self::fetchAll(
            'SELECT * FROM `notification_push_subscription`
             WHERE `active` = :active
               AND `IDuser` IN (' . implode(', ', $placeholders) . ')
             ORDER BY `id` ASC',
            $parameters
        );
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!isset($row['id'])) {
                continue;
            }
            $item = new self();
            $item->loadFromArray($row);
            $item->setId((int)$row['id']);
            $items[] = $item;
        }
        return $items;
    }

    public static function recordDeliveryResult($subscriptionId, $sent, $errorMessage = '', $disable = false)
    {
        $subscriptionId = (int)$subscriptionId;
        if ($subscriptionId <= 0 || !self::isStorageAvailable()) {
            return false;
        }

        return self::execute(
            'UPDATE `notification_push_subscription`
             SET `active` = :active,
                 `last_error` = :last_error,
                 `last_sent_at` = CASE WHEN :sent = 1 THEN NOW() ELSE `last_sent_at` END,
                 `updated_at` = NOW()
             WHERE `id` = :id',
            [
                'active' => $disable ? 0 : 1,
                'last_error' => $sent ? null : mb_substr(trim((string)$errorMessage), 0, 4000, 'UTF-8'),
                'sent' => $sent ? 1 : 0,
                'id' => $subscriptionId,
            ]
        );
    }
}
?>
