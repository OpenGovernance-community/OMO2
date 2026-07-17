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
}
