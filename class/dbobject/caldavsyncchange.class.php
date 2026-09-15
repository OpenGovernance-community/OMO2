<?php
namespace dbObject;

class CalDavSyncChange extends DbObject
{
    public static function tableName()
    {
        return 'caldav_sync_change';
    }

    public static function rules()
    {
        return array(
            array(array('IDorganization', 'event_id', 'change_type'), 'required'),
            array(array('id', 'event_id'), 'integer'),
            array(array('IDorganization'), 'fk'),
            array(array('change_type'), 'string'),
            array(array('changed_at'), 'datetime'),
            array(array('id'), 'safe'),
        );
    }

    public static function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'event_id' => 'Evenement',
            'change_type' => 'Type de changement',
            'changed_at' => 'Date du changement',
        );
    }

    public static function getLatestChangeId($organizationId)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return 0;
        }

        return (int)self::fetchValue(
            'SELECT MAX(`id`) FROM `caldav_sync_change` WHERE `IDorganization` = :organization_id',
            array('organization_id' => $organizationId)
        );
    }

    public static function getLatestEventChangesSince($organizationId, $changeId)
    {
        $organizationId = (int)$organizationId;
        $changeId = max(0, (int)$changeId);
        if ($organizationId <= 0) {
            return array();
        }

        $rows = self::fetchAll(
            'SELECT `id`, `event_id`, `change_type`
             FROM `caldav_sync_change`
             WHERE `IDorganization` = :organization_id
               AND `id` > :change_id
             ORDER BY `id` ASC',
            array(
                'organization_id' => $organizationId,
                'change_id' => $changeId,
            )
        );
        if (!is_array($rows)) {
            return array();
        }

        $changes = array();
        foreach ($rows as $row) {
            $eventId = (int)($row['event_id'] ?? 0);
            if ($eventId <= 0) {
                continue;
            }

            $changes[$eventId] = array(
                'id' => (int)($row['id'] ?? 0),
                'changeType' => (string)($row['change_type'] ?? 'updated'),
            );
        }

        return $changes;
    }

    public static function recordEventChange($organizationId, $eventId, $changeType = 'updated')
    {
        $organizationId = (int)$organizationId;
        $eventId = (int)$eventId;
        $changeType = trim((string)$changeType);
        if (
            $organizationId <= 0
            || $eventId <= 0
            || !in_array($changeType, array('updated', 'deleted'), true)
        ) {
            return false;
        }

        $change = new self();
        $change->set('IDorganization', $organizationId);
        $change->set('event_id', $eventId);
        $change->set('change_type', $changeType);
        $change->set('changed_at', new \DateTimeImmutable());
        $result = $change->save();

        return is_array($result) && !empty($result['status']);
    }
}
