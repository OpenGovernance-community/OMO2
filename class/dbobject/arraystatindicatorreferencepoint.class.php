<?php
namespace dbObject;

class ArrayStatIndicatorReferencePoint extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\StatIndicatorReferencePoint';
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
                ['field' => 'position_percent', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }
}

?>
