<?php
namespace dbObject;

class ArrayProjectIndicator extends ArrayDbObject
{
    public static function objectName() { return '\\dbObject\\ProjectIndicator'; }

    public function loadForProject(int $projectId): void
    {
        $this->exchangeArray([]);
        if ($projectId > 0) {
            $this->load(['where' => [['field' => 'IDproject', 'value' => $projectId]], 'orderBy' => [['field' => 'datecreation', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']]]);
        }
    }
}
