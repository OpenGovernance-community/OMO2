<?php
declare(strict_types=1);

namespace dbObject {
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';

    // Capture writes without a database; exercise the actual source synchronizers below.
    final class StatIndicatorValue extends DbObject
    {
        public static array $saved = [];
        public static bool $failSave = false;

        public static function rules()
        {
            return [
                [['IDstatindicator', 'IDuser'], 'fk'],
                [['value'], 'float'],
                [['measured_at'], 'datetime'],
            ];
        }

        public static function attributeLabels()
        {
            return [];
        }

        public function save()
        {
            if (self::$failSave) {
                return ['status' => false];
            }
            self::$saved[] = clone $this;
            return ['status' => true];
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/class/dbobject/recurrenceschedule.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/statindicator.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/objectvisibility.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/document.class.php';

    use dbObject\Document;
    use dbObject\StatIndicator;
    use dbObject\StatIndicatorValue;

    $cellReadResult = ['status' => true, 'value' => 42.5];

    function omoStatsSpreadsheetReadCell(StatIndicator $indicator, string $cell): array
    {
        return $GLOBALS['cellReadResult'];
    }

    function omoStatsEthercalcReadCell(Document $document, string $cell): array
    {
        return $GLOBALS['cellReadResult'];
    }

    require_once dirname(__DIR__) . '/common/stats_spreadsheet_sync.php';
    require_once dirname(__DIR__) . '/common/stats_ethercalc_sync.php';
    require_once dirname(__DIR__) . '/omo/api/stats/shared.php';

    final class CellSyncIndicator extends StatIndicator
    {
        public function getMeasurements()
        {
            return StatIndicatorValue::$saved;
        }

        public function getEthercalcDocument()
        {
            $document = new Document();
            $document->set('IDorganization', $this->get('IDorganization'));
            return $document;
        }

        public function save()
        {
            return ['status' => true];
        }
    }

    function checkCellSync(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    foreach ([
        StatIndicator::SOURCE_SPREADSHEET_CELL => 'spreadsheet',
        StatIndicator::SOURCE_ETHERCALC_CELL => 'ethercalc',
    ] as $sourceType => $prefix) {
        StatIndicatorValue::$saved = [];
        $indicator = new CellSyncIndicator();
        $indicator->hydrateFromDatabaseRow(['id' => 123], true);
        $indicator->set('IDorganization', 456);
        $indicator->set('active', 1);
        $indicator->set('source_type', $sourceType);
        $indicator->set($prefix . '_cell', 'A1');
        $indicator->set($prefix . '_frequency', 'monthly');
        $synchronize = $prefix === 'spreadsheet' ? 'omoStatsSynchronizeSpreadsheetIndicator' : 'omoStatsSynchronizeEthercalcIndicator';
        $isDue = $prefix === 'spreadsheet' ? 'isSpreadsheetSyncDue' : 'isEthercalcSyncDue';

        foreach (['2026-08-15 12:00:00', '2026-09-15 12:00:00', '2026-10-15 12:00:00'] as $index => $date) {
            $measuredAt = new DateTimeImmutable($date);
            checkCellSync($indicator->$isDue($measuredAt), $prefix . ': the next monthly measurement is due.');
            checkCellSync(!empty($synchronize($indicator, $measuredAt)['status']), $prefix . ': synchronization succeeds.');
            checkCellSync(count(StatIndicatorValue::$saved) === $index + 1, $prefix . ': each month creates a measurement even when the value stays identical.');
            $measurement = StatIndicatorValue::$saved[$index];
            checkCellSync((float)$measurement->get('value') === 42.5, $prefix . ': the unchanged numeric value is preserved.');
            checkCellSync($measurement->get('measured_at')->format('Y-m-d H:i:s') === $date, $prefix . ': each measurement has its own synchronization date.');
            checkCellSync((int)$measurement->get('IDstatindicator') === 123 && $measurement->get('IDuser') === null, $prefix . ': the automatic measurement belongs to the correct indicator.');
            checkCellSync(!$indicator->$isDue($measuredAt->modify('+5 minutes')), $prefix . ': the cron waits for the next period after success.');
        }

        checkCellSync(!omoStatsGetIndicatorOverdueInfo($indicator, new DateTimeImmutable('2026-10-21 12:00:00'))['is_overdue'], $prefix . ': the actual new measurement clears the overdue state.');

        $nextDate = new DateTimeImmutable('2026-11-15 12:00:00');
        $lastSync = $indicator->get($prefix . '_last_sync_at')->getTimestamp();
        $cellReadResult = ['status' => false, 'text' => 'Source unavailable'];
        checkCellSync(empty($synchronize($indicator, $nextDate)['status']), $prefix . ': a read failure is reported.');
        checkCellSync(count(StatIndicatorValue::$saved) === 3 && $indicator->get($prefix . '_last_sync_at')->getTimestamp() === $lastSync, $prefix . ': a read failure creates no measurement and does not advance the sync date.');

        $cellReadResult = ['status' => true, 'value' => 42.5];
        StatIndicatorValue::$failSave = true;
        checkCellSync(empty($synchronize($indicator, $nextDate)['status']), $prefix . ': a measurement save failure is reported.');
        checkCellSync(count(StatIndicatorValue::$saved) === 3 && $indicator->get($prefix . '_last_sync_at')->getTimestamp() === $lastSync, $prefix . ': a save failure does not advance the sync date.');
        checkCellSync($indicator->$isDue($nextDate), $prefix . ': a failed measurement remains due for retry.');
        StatIndicatorValue::$failSave = false;
    }

    echo "stat_indicator_cell_sync_test: OK\n";
}
