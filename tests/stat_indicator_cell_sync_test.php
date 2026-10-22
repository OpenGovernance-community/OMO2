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

    final class ArrayStatIndicator extends \ArrayObject
    {
        public static array $sources = [];

        public function load($params)
        {
            $types = $params['where'][1]['value'];
            $this->exchangeArray(array_values(array_filter(self::$sources, static fn ($source) =>
                (int)$source->get('active') === 1 && in_array($source->get('source_type'), $types, true)
            )));
        }
    }

    final class ArrayStatIndicatorValue extends \ArrayObject
    {
        public function load($params)
        {
            $indicatorId = $params['where'][0]['value'];
            $since = $params['where'][1]['value'];
            $this->exchangeArray(array_slice(array_values(array_filter(StatIndicatorValue::$saved, static fn ($value) =>
                (int)$value->get('IDstatindicator') === $indicatorId
                && $value->get('measured_at')->format('Y-m-d H:i:s') >= $since
            )), 0, $params['limit']));
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

        // Existing production state: the old cron read an unchanged cell in October
        // and advanced last_sync_at, but kept only the September measurement.
        StatIndicatorValue::$saved = [];
        $september = new DateTimeImmutable('2026-09-10 07:02:00');
        checkCellSync(!empty($synchronize($indicator, $september)['status']), $prefix . ': prepare the last real measurement.');
        $indicator->set($prefix . '_last_sync_at', new DateTimeImmutable('2026-10-10 07:02:00'));
        \dbObject\ArrayStatIndicator::$sources = [$indicator];
        $loadDue = $prefix === 'spreadsheet' ? 'loadDueSpreadsheetSources' : 'loadDueEthercalcSources';
        $today = new DateTimeImmutable('2026-10-22 07:02:00');
        checkCellSync(StatIndicator::$loadDue(20, $today) === [$indicator], $prefix . ': the actual cron source selector recovers a recent sync with no corresponding measurement.');
        $cellReadResult = ['status' => false, 'text' => 'Source unavailable'];
        checkCellSync(empty($synchronize($indicator, $today)['status']), $prefix . ': recovery reports a read failure.');
        checkCellSync(count(StatIndicatorValue::$saved) === 1 && StatIndicator::$loadDue(20, $today) === [$indicator], $prefix . ': failed recovery remains eligible for the next cron call.');
        $cellReadResult = ['status' => true, 'value' => 42.5];
        checkCellSync(!empty($synchronize($indicator, $today)['status']), $prefix . ': recover by reading the cell again.');
        checkCellSync(count(StatIndicatorValue::$saved) === 2 && StatIndicatorValue::$saved[1]->get('measured_at')->getTimestamp() === $today->getTimestamp(), $prefix . ': recovery creates a current measurement instead of fabricating a historical one.');
        checkCellSync(StatIndicator::$loadDue(20, $today->modify('+5 minutes')) === [], $prefix . ': later cron calls do not duplicate the recovered measurement.');

        // A table import may legitimately contain older dated rows after a recent sync.
        $indicator->set('source_type', $prefix === 'spreadsheet' ? StatIndicator::SOURCE_SPREADSHEET_TABLE : StatIndicator::SOURCE_ETHERCALC_TABLE);
        $indicator->set($prefix . '_last_sync_at', new DateTimeImmutable('2026-10-10 07:02:00'));
        StatIndicatorValue::$saved = [StatIndicatorValue::$saved[0]];
        checkCellSync(StatIndicator::$loadDue(20, $today) === [], $prefix . ': table imports keep their normal synchronization schedule.');

        $indicator->set('source_type', $sourceType);
        StatIndicatorValue::$saved = [];
        checkCellSync(StatIndicator::$loadDue(20, $today) === [$indicator], $prefix . ': a cell without any measurement is also recovered.');
        $indicator->set('active', 0);
        checkCellSync(StatIndicator::$loadDue(20, $today) === [], $prefix . ': archived cells are not recovered.');
    }

    echo "stat_indicator_cell_sync_test: OK\n";
}
