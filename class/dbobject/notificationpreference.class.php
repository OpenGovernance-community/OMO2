<?php
namespace dbObject;

class NotificationPreference extends DbObject
{
    public static function tableName()
    {
        return 'notification_preference';
    }

    public static function rules()
    {
        return [
            [['IDuser', 'event_key'], 'required'],
            [['id'], 'integer'],
            [['IDuser', 'IDorganization'], 'fk'],
            [['event_key'], 'string'],
            [['channel_push', 'channel_telegram', 'channel_email'], 'boolean'],
            [['parameters'], 'parameters'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'IDuser' => 'Utilisateur',
            'IDorganization' => 'Organisation',
            'event_key' => 'Événement',
            'channel_push' => 'Notification',
            'channel_telegram' => 'Telegram',
            'channel_email' => 'E-mail',
        ];
    }

    public static function isStorageAvailable()
    {
        return self::tableExists(self::tableName());
    }

    public static function getChannelsFor($userId, $organizationId, $eventKey)
    {
        $defaults = ['in_app' => true, 'push' => false, 'telegram' => false, 'email' => false, 'days' => [], 'lead_time' => ''];
        if (self::isPersonalEvent($eventKey)) { $defaults['email'] = true; }
        if (trim((string)$eventKey) === 'calendar_recurring_occurrence_created') { $defaults['in_app'] = false; }
        if (!self::isStorageAvailable()) {
            return $defaults;
        }

        $item = self::findForScope($userId, $organizationId, $eventKey);
        if (!$item) {
            return $defaults;
        }

        $parameters = $item->get('parameters');
        if (!is_array($parameters)) {
            $parameters = json_decode((string)$parameters, true);
        }
        $days = is_array($parameters['days'] ?? null) ? $parameters['days'] : [];
        $days = array_values(array_unique(array_filter(array_map('intval', $days), static function ($day) {
            return in_array($day, [1, 2, 3, 5], true);
        })));
        $leadTime = trim((string)($parameters['lead_time'] ?? ''));
        if (!in_array($leadTime, self::getReminderLeadTimes(), true)) {
            $leadTime = '';
        }

        return [
            // Existing preferences predate this channel and must remain visible in OMO.
            'in_app' => !array_key_exists('in_app', $parameters) || !empty($parameters['in_app']),
            'push' => !empty($item->get('channel_push')),
            'telegram' => !empty($item->get('channel_telegram')),
            'email' => !empty($item->get('channel_email')),
            'days' => $days,
            'lead_time' => $leadTime,
        ];
    }

    public static function getAllForUserOrganization($userId, $organizationId, array $eventKeys)
    {
        $settings = [];
        foreach ($eventKeys as $eventKey) {
            $settings[(string)$eventKey] = self::getChannelsFor($userId, $organizationId, $eventKey);
        }
        return $settings;
    }

    public static function saveChannels($userId, $organizationId, $eventKey, array $channels)
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $eventKey = trim((string)$eventKey);
        if ($userId <= 0 || ($organizationId <= 0 && !self::isPersonalEvent($eventKey)) || $eventKey === '' || !self::isStorageAvailable()) {
            return false;
        }

        $item = self::findForScope($userId, $organizationId, $eventKey) ?? new self();
        $item->set('IDuser', $userId);
        $item->set('IDorganization', self::isPersonalEvent($eventKey) ? null : $organizationId);
        $item->set('event_key', $eventKey);
        $item->set('channel_push', !empty($channels['push']) ? 1 : 0);
        $item->set('channel_telegram', !empty($channels['telegram']) ? 1 : 0);
        $item->set('channel_email', !empty($channels['email']) ? 1 : 0);
        $parameters = $item->get('parameters');
        if (!is_array($parameters)) {
            $parameters = json_decode((string)$parameters, true);
        }
        $parameters = is_array($parameters) ? $parameters : [];
        $parameters['in_app'] = !empty($channels['in_app']) ? 1 : 0;
        $days = is_array($channels['days'] ?? null) ? $channels['days'] : [];
        $parameters['days'] = array_values(array_unique(array_filter(array_map('intval', $days), static function ($day) {
            return in_array($day, [1, 2, 3, 5], true);
        })));
        $leadTime = trim((string)($channels['lead_time'] ?? ''));
        $parameters['lead_time'] = in_array($leadTime, self::getReminderLeadTimes(), true) ? $leadTime : '';
        $item->set('parameters', $parameters);
        if (!(int)$item->getId()) {
            $item->set('created_at', new \DateTimeImmutable('now'));
        }
        $item->set('updated_at', new \DateTimeImmutable('now'));
        $result = $item->save();
        return is_array($result) && !empty($result['status']);
    }

    public static function isPersonalEvent($eventKey): bool
    {
        return $eventKey === 'meeting_booking_created';
    }

    private static function findForScope($userId, $organizationId, $eventKey): ?self
    {
        $id = self::fetchValue('SELECT id FROM notification_preference
            WHERE IDuser = :uid AND IDorganization <=> :oid AND event_key = :event',
            ['uid' => (int)$userId, 'oid' => self::isPersonalEvent($eventKey) ? null : (int)$organizationId,
                'event' => trim((string)$eventKey)]);
        $item = new self();
        return $id && $item->load((int)$id, true) ? $item : null;
    }

    public static function getReminderLeadTimes()
    {
        return ['1h', '2h', '3h', '5h', '8h', '12h', '1d', '2d', '3d', '5d'];
    }
}
