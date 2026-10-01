<?php
namespace dbObject;

class ArrayStatIndicatorValue extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\StatIndicatorValue';
    }

    public function loadForIndicator($indicatorId)
    {
        $this->exchangeArray([]);
        $indicatorId = (int)$indicatorId;
        if ($indicatorId <= 0) {
            return;
        }

        $this->load([
            'where' => [
                ['field' => 'IDstatindicator', 'value' => $indicatorId],
            ],
            'orderBy' => [
                ['field' => 'measured_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }
}

?>
