<?php
namespace dbObject;

class ArrayDocumentApplicationTab extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\DocumentApplicationTab';
    }

    public function loadForDocument(int $documentId, bool $hydrate = false): void
    {
        $this->exchangeArray([]);
        if ($documentId <= 0 || !DocumentApplicationTab::hasTable()) {
            return;
        }

        $params = [
            'where' => [
                ['field' => 'IDdocument', 'value' => $documentId],
            ],
            'orderBy' => [
                ['field' => 'position', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];
        if ($hydrate) {
            $params['hydrate'] = true;
        }

        $this->load($params);
    }
}

