<?php
namespace dbObject;

class ArrayProjectDocument extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ProjectDocument';
    }

    public function loadForProject($projectId)
    {
        $this->exchangeArray([]);
        $projectId = (int)$projectId;
        if ($projectId <= 0) {
            return;
        }

        $this->load([
            'where' => [
                ['field' => 'IDproject', 'value' => $projectId],
            ],
            'orderBy' => [
                ['field' => 'datecreation', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }

    public function loadForDocument($documentId)
    {
        $this->exchangeArray([]);
        $documentId = (int)$documentId;
        if ($documentId <= 0) {
            return;
        }

        $this->load([
            'where' => [
                ['field' => 'IDdocument', 'value' => $documentId],
            ],
            'orderBy' => [
                ['field' => 'datecreation', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }
}
?>
