<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/recurrenceschedule.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicator.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicatorvalue.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicatorgroup.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicatorgroupitem.class.php';
require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';

function omoStatsT($key, $params = []) {
    foreach ($params as $name => $value) {
        $key .= ' ' . $name . '=' . $value;
    }
    return $key;
}
function omoApiEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

require_once dirname(__DIR__) . '/omo/api/stats/shared.php';

final class AvailabilityGroup extends \dbObject\StatIndicatorGroup
{
    public array $items = [];
    public function getItems() { return $this->items; }
    public function getReferencePoints() { return []; }
}

final class AvailabilityItem extends \dbObject\StatIndicatorGroupItem
{
    public function __construct(private ?\dbObject\StatIndicator $source, int $sourceId)
    {
        parent::__construct();
        $this->set('IDstatindicator', $sourceId);
    }
    public function getIndicator() { return $this->source; }
}

final class AvailabilityIndicator extends \dbObject\StatIndicator
{
    private array $fixtureFields = [];
    public array $measurements = [];
    public bool $visible = true;
    public function get($field) { return $this->fixtureFields[$field] ?? null; }
    public function set($field, $value) { $this->fixtureFields[$field] = $value; return true; }
    public function canView() { return $this->visible; }
    public function getMeasurements() { return $this->measurements; }
}

function checkAvailability(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function makeAvailabilityIndicator(int $id, string $name): AvailabilityIndicator
{
    $indicator = new AvailabilityIndicator();
    $indicator->setId($id);
    $indicator->set('name', $name);
    $indicator->set('active', 1);
    foreach (['2026-01-01', '2026-01-04', '2026-01-08'] as $index => $date) {
        $value = new \dbObject\StatIndicatorValue();
        $value->set('value', ($index + 1) * $id);
        $value->set('measured_at', new DateTimeImmutable($date));
        $indicator->measurements[] = $value;
    }
    return $indicator;
}

$first = makeAvailabilityIndicator(1, 'First');
$second = makeAvailabilityIndicator(2, 'Second');
$group = new AvailabilityGroup();
$group->set('name', 'Combined');
$group->set('display_mode', \dbObject\StatIndicatorGroup::DISPLAY_OVERLAY);
$group->items = [new AvailabilityItem($first, 1), new AvailabilityItem($second, 2)];

$current = omoStatsGetGroupSourceAvailability($group);
checkAvailability($current['status'] === 'current' && $current['issues'] === [], 'Two active sources remain current.');
checkAvailability(count(omoStatsGetGroupSeries($group, $current)[0]['points']) === 3, 'Current groups retain new measurements.');

$second->set('active', 0);
$second->set('archived_at', new DateTimeImmutable('2026-01-05'));
$archived = omoStatsGetGroupSourceAvailability($group);
checkAvailability($archived['status'] === 'archived' && $archived['issues'][0]['status'] === 'archived', 'An archived source marks the whole group as archived.');
$archivedSeries = omoStatsGetGroupSeries($group, $archived);
checkAvailability(count($archivedSeries) === 2 && count($archivedSeries[0]['points']) === 2 && count($archivedSeries[1]['points']) === 2, 'An archived overlay stops at the archive date for every curve.');
checkAvailability(omoStatsGetGroupOverdueInfo($group, null, $archived)['severity'] === 'none', 'Archived groups are not overdue.');

$group->set('display_mode', \dbObject\StatIndicatorGroup::DISPLAY_SUM);
$first->measurements[1]->set('measured_at', new DateTimeImmutable('2026-01-04 13:20:00'));
$second->measurements[1]->set('measured_at', new DateTimeImmutable('2026-01-04 13:40:00'));
$sumSeries = omoStatsGetGroupSeries($group, $archived);
$sumPoints = $sumSeries[count($sumSeries) - 1]['points'];
checkAvailability(count($sumPoints) > 0 && max(array_column($sumPoints, 'timestamp')) <= (new DateTimeImmutable('2026-01-04 13:00:00'))->getTimestamp(), 'An archived sum cannot include later partial values.');
checkAvailability((float)$sumPoints[count($sumPoints) - 1]['value'] === 6.0, 'The last archived total includes every source after timestamp normalization.');

$archivedEmbed = '<span class="omo-indicator-embed omo-indicator-embed--overdue" data-omo-embed-type="indicator" data-omo-indicator-id="12" data-omo-indicator-kind="group" data-omo-indicator-source-status="archived" data-omo-indicator-overdue="1" data-omo-indicator-status="Source archivee"><strong>Combined</strong></span>';
$cleanEmbed = \dbObject\PropertyFormat::sanitizeHtml($archivedEmbed);
checkAvailability(str_contains($cleanEmbed, 'data-omo-indicator-source-status="archived"'), 'Saving a PV preserves the archived source state.');
checkAvailability(str_contains($cleanEmbed, 'omo-indicator-embed--unavailable') && !str_contains($cleanEmbed, 'omo-indicator-embed--overdue'), 'Saving a PV keeps archived groups gray instead of overdue.');

$second->set('archived_at', null);
$deleted = omoStatsGetGroupSourceAvailability($group);
checkAvailability($deleted['status'] === 'unavailable' && $deleted['issues'][0]['status'] === 'deleted', 'An inactive source without an archive date is deleted.');
checkAvailability(omoStatsGetGroupSeries($group, $deleted) === [], 'Deleted sources cannot produce a partial group total.');
checkAvailability(str_contains(omoStatsRenderGroupChart($group, [], 'compact', 'none', false, $deleted), 'stats.group.chart.unavailable'), 'Deleted source charts explain their unavailability.');

$group->items[1] = new AvailabilityItem(null, 2);
$missing = omoStatsGetGroupSourceAvailability($group);
checkAvailability($missing['status'] === 'unavailable' && $missing['issues'][0]['status'] === 'deleted', 'A missing source is reported as deleted.');

echo "stat_indicator_group_source_availability_test: OK\n";
