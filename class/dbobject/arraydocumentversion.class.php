<?php
namespace dbObject;

class ArrayDocumentVersion extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\DocumentVersion';
    }

    public function loadForDocument(int $documentId): void
    {
        $this->exchangeArray([]);
        if ($documentId > 0) {
            $this->load([
                'where' => [['field' => 'IDdocument', 'value' => $documentId]],
                'orderBy' => [['field' => 'datecreation', 'dir' => 'DESC'], ['field' => 'id', 'dir' => 'DESC']],
            ]);
        }
    }
}
