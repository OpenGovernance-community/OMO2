<?php
namespace dbObject;

/** Personal time surrounding an event, including invitations through a holon. */
class EventTimeBuffer extends DbObject
{
    use CalendarTimeBuffer;

    public static function tableName() { return 'event_time_buffer'; }
    public static function rules()
    {
        return [
            [['IDevent', 'IDuser'], 'required'],
            [['id', 'preparation_minutes', 'closing_minutes'], 'integer'],
            [['IDevent', 'IDuser'], 'fk'],
            [['updated_at'], 'datetime'], [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['IDevent' => 'Evenement', 'IDuser' => 'Personne',
            'preparation_minutes' => 'Preparation (minutes)', 'closing_minutes' => 'Cloture (minutes)',
            'updated_at' => 'Mise a jour'];
    }
    public static function forUser(int $eventId, int $userId): self
    {
        $item = new self();
        if ($eventId > 0 && $userId > 0) {
            $item->load([['IDevent', $eventId], ['IDuser', $userId]]);
        }
        $item->set('IDevent', $eventId);
        $item->set('IDuser', $userId);
        return $item;
    }
    public function save()
    {
        foreach (['preparation_minutes', 'closing_minutes'] as $field) {
            $this->set($field, self::validateBufferMinutes($this->get($field)));
        }
        $result = parent::save();
        if (is_array($result) && !empty($result['status'])) {
            $organizationId = Event::getOrganizationIdByEventId((int)$this->get('IDevent'));
            CalDavCache::invalidateOrganization($organizationId);
            CalDavSyncChange::recordEventChange($organizationId, (int)$this->get('IDevent'), 'updated');
        }
        return $result;
    }
}
