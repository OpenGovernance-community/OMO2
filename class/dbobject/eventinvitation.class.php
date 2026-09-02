<?php
namespace dbObject;

class EventInvitation extends ResourceInvitation
{
    public static function resourceType()
    {
        return 'event';
    }

    protected static function legacyResourceField()
    {
        return 'IDevent';
    }

    public function save()
    {
        $eventId = (int)$this->get('IDevent');
        $result = parent::save();
        if (is_array($result) && ($result['status'] ?? false) === true) {
            CalDavCache::invalidateOrganization(Event::getOrganizationIdByEventId($eventId));
        }

        return $result;
    }

    public function delete()
    {
        $eventId = (int)$this->get('IDevent');
        $deleted = parent::delete();
        if ($deleted) {
            CalDavCache::invalidateOrganization(Event::getOrganizationIdByEventId($eventId));
        }

        return $deleted;
    }
}
