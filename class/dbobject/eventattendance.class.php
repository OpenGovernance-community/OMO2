<?php
namespace dbObject;

class EventAttendance extends ResourceAttendance
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

