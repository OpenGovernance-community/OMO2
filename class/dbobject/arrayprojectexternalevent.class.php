<?php
namespace dbObject;

class ArrayProjectExternalEvent extends ArrayDbObject
{
    public static function objectName() { return '\\dbObject\\ProjectExternalEvent'; }

    public function loadForProject(int $projectId): void
    {
        $this->exchangeArray([]);
        if ($projectId > 0 && ProjectExternalEvent::isStorageAvailable()) {
            $this->load(['where' => [['field' => 'IDproject', 'value' => $projectId]],
                'orderBy' => [['field' => 'start_at', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']]]);
        }
    }
}
