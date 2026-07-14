<?php
namespace dbObject;

class DocumentInvitation extends ResourceInvitation
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

