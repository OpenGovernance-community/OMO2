<?php
namespace dbObject;

class ArrayDocumentPvPoint extends ArrayDbObject
{
    public static function objectName()
    {
        return '\dbObject\DocumentPvPoint';
    }

    public function loadForDocument($documentId, $activeOnly = true)
    {
        $documentId = (int)$documentId;
        $activeOnly = (bool)$activeOnly;

        $this->exchangeArray([]);

        if ($documentId <= 0 || !\dbObject\DocumentPvPoint::hasPointTable()) {
            return;
        }

        $where = [
            ['field' => 'IDdocument', 'value' => $documentId],
        ];

        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'position', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }
}

?>
