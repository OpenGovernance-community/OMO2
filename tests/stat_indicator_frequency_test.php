<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/recurrenceschedule.class.php';
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicatorvalue.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicator.class.php';

use dbObject\StatIndicator;
use dbObject\StatIndicatorValue;

class StatIndicatorMeasurementTestDouble extends StatIndicator
{
    public array $measurements = [];

    public function getMeasurements()
    {
        return $this->measurements;
    }
}

function assertStatIndicatorFrequency(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$expectedFrequencies = [
    StatIndicator::SPREADSHEET_FREQUENCY_HOURLY,
    StatIndicator::SPREADSHEET_FREQUENCY_DAILY,
    StatIndicator::SPREADSHEET_FREQUENCY_WEEKLY,
    StatIndicator::SPREADSHEET_FREQUENCY_MONTHLY,
    StatIndicator::SPREADSHEET_FREQUENCY_QUARTERLY,
    StatIndicator::SPREADSHEET_FREQUENCY_SEMIANNUAL,
    StatIndicator::SPREADSHEET_FREQUENCY_YEARLY,
];
assertStatIndicatorFrequency(
    array_keys(StatIndicator::getSpreadsheetFrequencyCatalog()) === $expectedFrequencies,
    'Spreadsheet sources must offer hourly through yearly frequencies.'
);
assertStatIndicatorFrequency(
    array_keys(StatIndicator::getEthercalcFrequencyCatalog()) === $expectedFrequencies,
    'EtherCalc sources must offer the same frequencies as spreadsheet sources.'
);

$spreadsheetIndicator = new StatIndicator();
$spreadsheetIndicator->set('source_type', StatIndicator::SOURCE_SPREADSHEET_CELL);
$spreadsheetIndicator->set('spreadsheet_frequency', StatIndicator::SPREADSHEET_FREQUENCY_QUARTERLY);
$spreadsheetIndicator->set('measurement_frequency', null);
assertStatIndicatorFrequency(
    $spreadsheetIndicator->getEffectiveMeasurementFrequency() === StatIndicator::FREQUENCY_QUARTERLY,
    'A spreadsheet source must use its source frequency for indicator classification.'
);

$ethercalcIndicator = new StatIndicator();
$ethercalcIndicator->set('source_type', StatIndicator::SOURCE_ETHERCALC_TABLE);
$ethercalcIndicator->set('ethercalc_frequency', StatIndicator::ETHERCALC_FREQUENCY_YEARLY);
$ethercalcIndicator->set('measurement_frequency', null);
assertStatIndicatorFrequency(
    $ethercalcIndicator->getEffectiveMeasurementFrequency() === StatIndicator::FREQUENCY_YEARLY,
    'An EtherCalc source must use its source frequency for indicator classification.'
);

$monthlyIndicator = new StatIndicator();
$monthlyIndicator->set('source_type', StatIndicator::SOURCE_SPREADSHEET_CELL);
$monthlyIndicator->set('spreadsheet_frequency', StatIndicator::SPREADSHEET_FREQUENCY_MONTHLY);
$monthlyIndicator->set('active', 1);
$monthlyIndicator->set('spreadsheet_last_sync_at', new DateTimeImmutable('2026-01-31 12:00:00'));
assertStatIndicatorFrequency(
    !$monthlyIndicator->isSpreadsheetSyncDue(new DateTimeImmutable('2026-02-28 11:59:59')),
    'Monthly synchronization must wait until the matching calendar day or the last day of a shorter month.'
);
assertStatIndicatorFrequency(
    $monthlyIndicator->isSpreadsheetSyncDue(new DateTimeImmutable('2026-02-28 12:00:00')),
    'Monthly synchronization must be due on the last day of a shorter month.'
);

$ethercalcTableIndicator = new StatIndicator();
$ethercalcTableIndicator->set('source_type', StatIndicator::SOURCE_ETHERCALC_TABLE);
$ethercalcTableIndicator->set('ethercalc_frequency', StatIndicator::ETHERCALC_FREQUENCY_QUARTERLY);
$ethercalcTableIndicator->set('active', 1);
$ethercalcTableIndicator->set('ethercalc_last_sync_at', new DateTimeImmutable('2026-01-15 09:00:00'));
assertStatIndicatorFrequency(
    !$ethercalcTableIndicator->isEthercalcSyncDue(new DateTimeImmutable('2026-04-15 08:59:59')),
    'EtherCalc tables must honor their configured synchronization frequency.'
);
assertStatIndicatorFrequency(
    $ethercalcTableIndicator->isEthercalcSyncDue(new DateTimeImmutable('2026-04-15 09:00:00')),
    'EtherCalc tables must synchronize when their configured period is reached.'
);

$sameValueIndicator = new StatIndicatorMeasurementTestDouble();
$existingValue = new StatIndicatorValue();
$existingValue->set('value', 42.5);
$sameValueIndicator->measurements = [$existingValue];
assertStatIndicatorFrequency(
    $sameValueIndicator->hasLatestMeasurementValue(42.5),
    'A synchronization must not create a duplicate when the source value is unchanged.'
);
assertStatIndicatorFrequency(
    !$sameValueIndicator->hasLatestMeasurementValue(42.6),
    'A synchronization must record a changed source value.'
);

echo "stat_indicator_frequency_test: OK\n";
