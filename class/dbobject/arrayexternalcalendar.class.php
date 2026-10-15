<?php
namespace dbObject;

class ArrayExternalCalendar extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ExternalCalendar';
    }

    public function loadForUser($userId, $activeOnly = false)
    {
        $userId = (int)$userId;
        $this->exchangeArray([]);
        if ($userId <= 0 || !ExternalCalendar::isStorageAvailable()) {
            return;
        }

        $params = [
            'where' => [
                ['field' => 'IDuser', 'value' => $userId],
            ],
            'orderBy' => [
                ['field' => 'title', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];
        if ($activeOnly) {
            $params['where'][] = ['field' => 'active', 'value' => 1];
        }

        $this->load($params);
    }

    public function loadMeetingDestinationsForUser(int $userId): void
    {
        $this->loadForUser($userId, true);
        $this->exchangeArray(array_values(array_filter($this->getArrayCopy(),
            static fn(ExternalCalendar $calendar): bool => $calendar->isMeetingDestinationForUser($userId))));
    }
}
