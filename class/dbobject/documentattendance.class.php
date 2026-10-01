<?php
namespace dbObject;

class DocumentAttendance extends ResourceAttendance
{
    public static function resourceType()
    {
        return 'document';
    }

    protected static function legacyResourceField()
    {
        return 'IDdocument';
    }
}

