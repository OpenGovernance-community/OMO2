<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 3) . '/common/stats_ethercalc_sync.php';
require_once dirname(__DIR__, 3) . '/common/stats_spreadsheet_sync.php';

use dbObject\StatIndicator;
use dbObject\Document;
use dbObject\StatIndicatorGroup;
use dbObject\StatIndicatorGroupItem;
use dbObject\StatIndicatorImport;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

// adminEdit expects a JSON string while fetch callers can still parse this body as JSON.
header('Content-Type: text/plain; charset=UTF-8');

function omoStatsActionRespond($success, $message = '', array $extra = [], $statusCode = 200)
{
    if (trim((string)($_POST['stats_action'] ?? $_POST['action'] ?? '')) === 'save_indicator') {
        $statusCode = 200;
    }
    http_response_code((int)$statusCode);
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'status' => (bool)$success,
        'message' => (string)$message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function omoStatsActionParseDecimal($rawValue)
{
    $normalized = str_replace([' ', ','], ['', '.'], trim((string)$rawValue));
    return $normalized !== '' && is_numeric($normalized) ? (float)$normalized : null;
}

function omoStatsActionParseDateTime($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return null;
    }

    foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'] as $format) {
        $date = \DateTime::createFromFormat($format, $rawValue);
        $errors = \DateTime::getLastErrors();
        if ($date instanceof \DateTime && ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0))) {
            return $date;
        }
    }

    return null;
}

function omoStatsActionParseReferencePoints($referenceType, $rawPoints)
{
    $referenceType = StatIndicator::normalizeReferenceType($referenceType);
    if ($referenceType === StatIndicator::REFERENCE_NONE) {
        return [];
    }

    if (!is_array($rawPoints)) {
        throw new \InvalidArgumentException(omoStatsT('stats.error.reference_points'));
    }

    $points = [];
    $positions = [];
    foreach ($rawPoints as $rawPoint) {
        if (!is_array($rawPoint)) {
            continue;
        }

        $position = omoStatsActionParseDecimal($rawPoint['position_percent'] ?? null);
        $value = omoStatsActionParseDecimal($rawPoint['value'] ?? null);
        if ($position === null || $value === null || $position < 0 || $position > 100) {
            throw new \InvalidArgumentException(omoStatsT('stats.error.reference_points'));
        }

        if (abs($position) < 0.00005) {
            $position = 0.0;
        } elseif (abs($position - 100.0) < 0.00005) {
            $position = 100.0;
        }

        $positionKey = number_format($position, 4, '.', '');
        if (isset($positions[$positionKey])) {
            throw new \InvalidArgumentException(omoStatsT('stats.error.reference_points'));
        }
        $positions[$positionKey] = true;

        $isEndpoint = $position === 0.0 || $position === 100.0;
        $pointAt = $isEndpoint ? omoStatsActionParseDateTime($rawPoint['point_at'] ?? null) : null;
        if ($isEndpoint && !($pointAt instanceof \DateTimeInterface)) {
            throw new \InvalidArgumentException(omoStatsT('stats.error.reference_endpoints'));
        }

        $points[] = [
            'position_percent' => $position,
            'value' => $value,
            'point_at' => $pointAt,
        ];
    }

    usort($points, static function (array $left, array $right) {
        return $left['position_percent'] <=> $right['position_percent'];
    });

    if (
        count($points) < 2
        || abs((float)$points[0]['position_percent']) > 0.00005
        || abs((float)$points[count($points) - 1]['position_percent'] - 100.0) > 0.00005
    ) {
        throw new \InvalidArgumentException(omoStatsT('stats.error.reference_endpoints'));
    }

    $startAt = $points[0]['point_at'];
    $endAt = $points[count($points) - 1]['point_at'];
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        throw new \InvalidArgumentException(omoStatsT('stats.error.reference_endpoints'));
    }
    if ($endAt->getTimestamp() <= $startAt->getTimestamp()) {
        throw new \InvalidArgumentException(omoStatsT('stats.error.reference_dates'));
    }

    $duration = $endAt->getTimestamp() - $startAt->getTimestamp();
    foreach ($points as &$point) {
        if ((float)$point['position_percent'] > 0.0 && (float)$point['position_percent'] < 100.0) {
            $pointAt = \DateTime::createFromInterface($startAt);
            $pointAt->setTimestamp((int)(round(($startAt->getTimestamp() + ($duration * ((float)$point['position_percent'] / 100.0))) / 60) * 60));
            $point['point_at'] = $pointAt;
        }
    }
    unset($point);

    if ($referenceType === StatIndicator::REFERENCE_CEILING) {
        $ceilingValue = (float)$points[0]['value'];
        foreach ($points as $point) {
            if (abs((float)$point['value'] - $ceilingValue) > 0.000001) {
                throw new \InvalidArgumentException(omoStatsT('stats.error.ceiling'));
            }
        }
    }

    return $points;
}

function omoStatsActionParseIndicatorReferencePoints($referenceType, $rawPoints, $rawCeilingValue)
{
    $referenceType = StatIndicator::normalizeReferenceType($referenceType);
    if ($referenceType !== StatIndicator::REFERENCE_CEILING) {
        return omoStatsActionParseReferencePoints($referenceType, $rawPoints);
    }

    $ceilingValue = omoStatsActionParseDecimal($rawCeilingValue);
    if ($ceilingValue === null) {
        throw new \InvalidArgumentException(omoStatsT('stats.error.ceiling_value'));
    }

    return [[
        'position_percent' => 0.0,
        'value' => $ceilingValue,
        'point_at' => null,
    ]];
}

function omoStatsActionParseGroupReferencePoints($referenceType, $rawPoints, $rawCeilingValue)
{
    return omoStatsActionParseIndicatorReferencePoints($referenceType, $rawPoints, $rawCeilingValue);
}

function omoStatsActionParseChartMinimumValue($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return null;
    }

    $value = omoStatsActionParseDecimal($rawValue);
    if ($value === null) {
        throw new \InvalidArgumentException(omoStatsT('stats.error.chart_min_value'));
    }

    return $value;
}

function omoStatsActionEnsureAutoEthercalcGroup($organizationId, $holonId, $userId, $name, array $indicators)
{
    $indicators = array_values(array_filter($indicators, static function ($indicator) {
        return $indicator instanceof StatIndicator && (int)$indicator->getId() > 0;
    }));
    if (count($indicators) < 2) {
        return null;
    }

    $organizationId = (int)$organizationId;
    $holonId = (int)$holonId;
    $group = null;
    $memberships = new \dbObject\ArrayStatIndicatorGroupItem();
    $memberships->load([
        'where' => [['field' => 'IDstatindicator', 'value' => (int)$indicators[0]->getId()]],
        'orderBy' => [['field' => 'id', 'dir' => 'ASC']],
    ]);
    foreach ($memberships as $membership) {
        $candidate = new StatIndicatorGroup();
        if (
            !$candidate->load((int)$membership->get('IDstatindicatorgroup'))
            || (int)$candidate->get('IDorganization') !== $organizationId
            || (int)$candidate->get('IDholon') !== $holonId
            || (int)$candidate->get('active') !== 1
        ) {
            continue;
        }
        $group = $candidate;
        break;
    }

    if (!($group instanceof StatIndicatorGroup)) {
        $group = new StatIndicatorGroup();
        $group->set('IDorganization', $organizationId);
        $group->set('IDholon', $holonId > 0 ? $holonId : null);
        $group->set('IDuser', (int)$userId > 0 ? (int)$userId : null);
        $group->set('name', mb_substr(trim((string)$name), 0, 190, 'UTF-8'));
        $group->set('display_mode', StatIndicatorGroup::DISPLAY_OVERLAY);
        $group->set('reference_type', StatIndicator::REFERENCE_NONE);
        $group->set('hide_same_holon_sources', 1);
        $group->set('active', 1);
        $groupResult = $group->save();
        if (!is_array($groupResult) || empty($groupResult['status']) || (int)$group->getId() <= 0) {
            throw new \RuntimeException(omoStatsT('stats.error.save'));
        }
    } elseif ((int)$group->get('hide_same_holon_sources') !== 1) {
        $group->set('hide_same_holon_sources', 1);
        $groupResult = $group->save();
        if (!is_array($groupResult) || empty($groupResult['status'])) {
            throw new \RuntimeException(omoStatsT('stats.error.save'));
        }
    }

    $existingIndicatorIds = [];
    $nextPosition = 1;
    foreach ($group->getItems() as $existingItem) {
        if (!($existingItem instanceof StatIndicatorGroupItem)) {
            continue;
        }
        $existingIndicatorIds[(int)$existingItem->get('IDstatindicator')] = true;
        $nextPosition = max($nextPosition, (int)$existingItem->get('position') + 1);
    }
    foreach ($indicators as $indicator) {
        $indicatorId = (int)$indicator->getId();
        if (!isset($existingIndicatorIds[$indicatorId])) {
            $item = new StatIndicatorGroupItem();
            $item->set('IDstatindicatorgroup', (int)$group->getId());
            $item->set('IDstatindicator', $indicatorId);
            $item->set('position', $nextPosition);
            $itemResult = $item->save();
            if (!is_array($itemResult) || empty($itemResult['status'])) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
            $nextPosition += 1;
        }

    }

    return $group;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    omoStatsActionRespond(false, omoStatsT('stats.error.method'), [], 405);
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$currentHolonId = isset($_POST['cid']) && is_numeric($_POST['cid']) ? (int)$_POST['cid'] : 0;
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$context = omoStatsResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    omoStatsActionRespond(false, (string)($context['message'] ?? omoStatsT('stats.error.context')), [], 403);
}

$action = trim((string)($_POST['stats_action'] ?? $_POST['action'] ?? ''));

if ($action === 'save_indicator') {
    $indicatorId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
    $indicator = $indicatorId > 0 ? omoStatsLoadIndicator($indicatorId, $organizationId) : new StatIndicator();

    if ($indicatorId > 0 && !($indicator instanceof StatIndicator)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.not_found'), [], 404);
    }
    if ($indicatorId > 0 && !$indicator->canEdit()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    if ($indicatorId <= 0 && !omoStatsCanCreateContext($context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }

    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        omoStatsActionRespond(false, omoStatsT('stats.error.name'), [], 422);
    }
    $name = mb_substr($name, 0, 190, 'UTF-8');

    $ethercalcSourceUpdate = null;
    $ethercalcAdditionalValueColumns = [];
    $ethercalcAdditionalIndicators = [];
    $ethercalcAdditionalGroupIndicators = [];
    $spreadsheetSourceUpdate = null;
    $spreadsheetAdditionalValueColumns = [];
    $spreadsheetAdditionalIndicators = [];
    $spreadsheetAdditionalGroupIndicators = [];
    $sourceUrl = null;
    if ($indicatorId > 0 && $indicator->isEthercalcSource()) {
        $documentId = isset($_POST['ethercalc_document_id']) && is_numeric($_POST['ethercalc_document_id'])
            ? (int)$_POST['ethercalc_document_id']
            : 0;
        $document = omoStatsLoadVisibleEthercalcDocument($documentId, $organizationId);
        if (!($document instanceof Document)) {
            omoStatsActionRespond(false, omoStatsT('stats.error.document_ethercalc'), [], 422);
        }

        if ($indicator->isEthercalcCellSource()) {
            $cell = StatIndicator::normalizeEthercalcCell($_POST['ethercalc_cell'] ?? '');
            if ($cell === '') {
                omoStatsActionRespond(false, omoStatsT('stats.error.ethercalc_cell'), [], 422);
            }
            $ethercalcSourceUpdate = [
                'IDdocument' => (int)$document->getId(),
                'ethercalc_cell' => $cell,
                'ethercalc_frequency' => StatIndicator::normalizeEthercalcFrequency($_POST['ethercalc_frequency'] ?? ''),
                'ethercalc_last_sync_at' => null,
            ];
        } else {
            $range = StatIndicator::normalizeEthercalcRange($_POST['ethercalc_range'] ?? '');
            $dateColumn = StatIndicator::normalizeEthercalcColumn($_POST['ethercalc_date_column'] ?? '');
            $rawValueColumns = (string)($_POST['ethercalc_value_columns'] ?? $_POST['ethercalc_value_column'] ?? '');
            $valueColumns = [];
            foreach (preg_split('/[\s,;]+/', strtoupper(trim($rawValueColumns))) ?: [] as $rawValueColumn) {
                $valueColumn = StatIndicator::normalizeEthercalcColumn($rawValueColumn);
                if ($valueColumn !== '' && $valueColumn !== $dateColumn) {
                    $valueColumns[$valueColumn] = $valueColumn;
                }
            }
            if ($range === '' || $dateColumn === '' || count($valueColumns) === 0) {
                omoStatsActionRespond(false, omoStatsT('stats.error.columns_required'), [], 422);
            }
            preg_match('/^([A-Z]+)[0-9]+:([A-Z]+)[0-9]+$/', $range, $rangeMatches);
            $rangeStartColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[1] ?? '');
            $rangeEndColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[2] ?? '');
            foreach (array_merge([$dateColumn], array_values($valueColumns)) as $selectedColumn) {
                $selectedColumnIndex = StatIndicator::ethercalcColumnToIndex($selectedColumn);
                if ($selectedColumnIndex < $rangeStartColumn || $selectedColumnIndex > $rangeEndColumn) {
                    omoStatsActionRespond(false, omoStatsT('stats.error.columns_in_range'), [], 422);
                }
            }
            $currentValueColumn = StatIndicator::normalizeEthercalcColumn($indicator->get('ethercalc_value_column'));
            $primaryValueColumn = isset($valueColumns[$currentValueColumn])
                ? $currentValueColumn
                : reset($valueColumns);
            unset($valueColumns[$primaryValueColumn]);
            $ethercalcAdditionalValueColumns = array_values($valueColumns);
            if (count($ethercalcAdditionalValueColumns) > 0 && !omoStatsCanCreateContext($context)) {
                omoStatsActionRespond(false, omoStatsT('stats.error.create_permission'), [], 403);
            }
            $ethercalcSourceUpdate = [
                'IDdocument' => (int)$document->getId(),
                'ethercalc_range' => $range,
                'ethercalc_date_column' => $dateColumn,
                'ethercalc_value_column' => $primaryValueColumn,
                'ethercalc_frequency' => StatIndicator::normalizeEthercalcFrequency($_POST['ethercalc_frequency'] ?? ''),
                'ethercalc_last_sync_at' => null,
            ];
        }
    } elseif ($indicatorId > 0 && $indicator->isSpreadsheetSource()) {
        $documentId = isset($_POST['spreadsheet_document_id']) && is_numeric($_POST['spreadsheet_document_id'])
            ? (int)$_POST['spreadsheet_document_id']
            : 0;
        $document = omoStatsLoadVisibleSpreadsheetDocument($documentId, $organizationId);
        if (!($document instanceof Document)) {
            omoStatsActionRespond(false, omoStatsT('stats.error.document_spreadsheet'), [], 422);
        }

        $sheet = StatIndicator::normalizeSpreadsheetSheet($_POST['spreadsheet_sheet'] ?? '');
        $frequency = StatIndicator::normalizeSpreadsheetFrequency($_POST['spreadsheet_frequency'] ?? '');
        if ($indicator->isSpreadsheetCellSource()) {
            $cell = StatIndicator::normalizeEthercalcCell($_POST['spreadsheet_cell'] ?? '');
            if ($cell === '') {
                omoStatsActionRespond(false, omoStatsT('stats.error.spreadsheet_cell'), [], 422);
            }
            $spreadsheetSourceUpdate = [
                'IDdocument' => (int)$document->getId(),
                'spreadsheet_sheet' => $sheet,
                'spreadsheet_cell' => $cell,
                'spreadsheet_frequency' => $frequency,
                'spreadsheet_last_sync_at' => null,
            ];
        } else {
            $range = StatIndicator::normalizeEthercalcRange($_POST['spreadsheet_range'] ?? '');
            $dateColumn = StatIndicator::normalizeEthercalcColumn($_POST['spreadsheet_date_column'] ?? '');
            $rawValueColumns = (string)($_POST['spreadsheet_value_columns'] ?? $_POST['spreadsheet_value_column'] ?? '');
            $valueColumns = [];
            foreach (preg_split('/[\s,;]+/', strtoupper(trim($rawValueColumns))) ?: [] as $rawValueColumn) {
                $valueColumn = StatIndicator::normalizeEthercalcColumn($rawValueColumn);
                if ($valueColumn !== '' && $valueColumn !== $dateColumn) {
                    $valueColumns[$valueColumn] = $valueColumn;
                }
            }
            if ($range === '' || $dateColumn === '' || count($valueColumns) === 0) {
                omoStatsActionRespond(false, omoStatsT('stats.error.columns_required'), [], 422);
            }
            preg_match('/^([A-Z]+)[0-9]+:([A-Z]+)[0-9]+$/', $range, $rangeMatches);
            $rangeStartColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[1] ?? '');
            $rangeEndColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[2] ?? '');
            foreach (array_merge([$dateColumn], array_values($valueColumns)) as $selectedColumn) {
                $selectedColumnIndex = StatIndicator::ethercalcColumnToIndex($selectedColumn);
                if ($selectedColumnIndex < $rangeStartColumn || $selectedColumnIndex > $rangeEndColumn) {
                    omoStatsActionRespond(false, omoStatsT('stats.error.columns_in_range'), [], 422);
                }
            }
            $currentValueColumn = StatIndicator::normalizeEthercalcColumn($indicator->get('spreadsheet_value_column'));
            $primaryValueColumn = isset($valueColumns[$currentValueColumn]) ? $currentValueColumn : reset($valueColumns);
            unset($valueColumns[$primaryValueColumn]);
            $spreadsheetAdditionalValueColumns = array_values($valueColumns);
            if (count($spreadsheetAdditionalValueColumns) > 0 && !omoStatsCanCreateContext($context)) {
                omoStatsActionRespond(false, omoStatsT('stats.error.create_permission'), [], 403);
            }
            $spreadsheetSourceUpdate = [
                'IDdocument' => (int)$document->getId(),
                'spreadsheet_sheet' => $sheet,
                'spreadsheet_frequency' => $frequency,
                'spreadsheet_range' => $range,
                'spreadsheet_date_column' => $dateColumn,
                'spreadsheet_value_column' => $primaryValueColumn,
                'spreadsheet_last_sync_at' => null,
            ];
        }
    } else {
        $rawSourceUrl = trim((string)($_POST['source_url'] ?? ''));
        $sourceUrl = StatIndicator::sanitizeSourceUrl($rawSourceUrl);
        if ($rawSourceUrl !== '' && $sourceUrl === '') {
            omoStatsActionRespond(false, omoStatsT('stats.error.url'), [], 422);
        }
    }

    $referenceType = StatIndicator::normalizeReferenceType($_POST['reference_type'] ?? StatIndicator::REFERENCE_NONE);
    $rawMeasurementFrequency = trim((string)($_POST['measurement_frequency'] ?? ''));
    $measurementFrequency = StatIndicator::normalizeMeasurementFrequency($rawMeasurementFrequency);
    if ($rawMeasurementFrequency !== '' && $measurementFrequency === null) {
        omoStatsActionRespond(false, omoStatsT('stats.error.schedule'), [], 422);
    }
    $rawMeasurementSchedule = trim((string)($_POST['measurement_schedule'] ?? ''));
    $measurementSchedule = StatIndicator::normalizeMeasurementSchedule($measurementFrequency, $rawMeasurementSchedule);
    if ($rawMeasurementSchedule !== '' && $measurementSchedule === null) {
        omoStatsActionRespond(false, omoStatsT('stats.error.schedule'), [], 422);
    }
    try {
        $chartMinValue = omoStatsActionParseChartMinimumValue($_POST['chart_min_value'] ?? null);
        $referencePoints = omoStatsActionParseIndicatorReferencePoints(
            $referenceType,
            $_POST['reference_points'] ?? [],
            $_POST['ceiling_value'] ?? null
        );
    } catch (\InvalidArgumentException $exception) {
        omoStatsActionRespond(false, $exception->getMessage(), [], 422);
    }

    if ($indicatorId <= 0) {
        $indicator->set('IDorganization', $organizationId);
        $indicator->set('IDholon', ($context['currentHolon'] ?? null) instanceof \dbObject\Holon
            ? (int)$context['currentHolon']->getId()
            : null);
        $indicator->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
        $indicator->set('active', 1);
    }
    $indicator->set('name', $name);
    $indicator->set('description', trim((string)($_POST['description'] ?? '')));
    $indicator->set('source_url', $sourceUrl !== null && $sourceUrl !== '' ? $sourceUrl : null);
    if (is_array($ethercalcSourceUpdate)) {
        foreach ($ethercalcSourceUpdate as $field => $value) {
            $indicator->set($field, $value);
        }
    }
    if (is_array($spreadsheetSourceUpdate)) {
        foreach ($spreadsheetSourceUpdate as $field => $value) {
            $indicator->set($field, $value);
        }
        $indicator->set('source_type', $indicator->isSpreadsheetCellSource()
            ? StatIndicator::SOURCE_SPREADSHEET_CELL
            : StatIndicator::SOURCE_SPREADSHEET_TABLE);
    }
    $indicator->set('reference_type', $referenceType);
    $indicator->set('measurement_frequency', $measurementFrequency);
    $indicator->set('measurement_schedule', $measurementSchedule);
    $indicator->set('chart_min_value', $chartMinValue);
    $indicator->set('show_cumulative', !empty($_POST['show_cumulative']) ? 1 : 0);

    $pdo = \dbObject\DbObject::getPdo();
    $startedTransaction = false;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $saveResult = $indicator->save();
        if (!is_array($saveResult) || empty($saveResult['status']) || (int)$indicator->getId() <= 0) {
            throw new \RuntimeException(omoStatsT('stats.error.save'));
        }

        foreach ($indicator->getReferencePoints() as $existingPoint) {
            if ($existingPoint instanceof StatIndicatorReferencePoint && !$existingPoint->delete()) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }

        foreach ($referencePoints as $pointData) {
            $point = new StatIndicatorReferencePoint();
            $point->set('IDstatindicator', (int)$indicator->getId());
            $point->set('position_percent', (float)$pointData['position_percent']);
            $point->set('value', (float)$pointData['value']);
            $point->set('point_at', $pointData['point_at']);
            $pointResult = $point->save();
            if (!is_array($pointResult) || empty($pointResult['status'])) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }

        foreach ($ethercalcAdditionalValueColumns as $valueColumn) {
            $existingSource = StatIndicator::findActiveEthercalcTableSource(
                $organizationId,
                (int)$indicator->get('IDholon'),
                (int)$ethercalcSourceUpdate['IDdocument'],
                (string)$ethercalcSourceUpdate['ethercalc_range'],
                (string)$ethercalcSourceUpdate['ethercalc_date_column'],
                $valueColumn
            );
            if ($existingSource instanceof StatIndicator && (int)$existingSource->getId() !== (int)$indicator->getId()) {
                $ethercalcAdditionalGroupIndicators[] = $existingSource;
                continue;
            }
            $additionalIndicator = new StatIndicator();
            $additionalIndicator->set('IDorganization', $organizationId);
            $additionalIndicator->set('IDholon', $indicator->get('IDholon'));
            $additionalIndicator->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
            $additionalIndicator->set('IDdocument', $ethercalcSourceUpdate['IDdocument']);
            $additionalIndicator->set('name', mb_substr($name . ' - ' . $valueColumn, 0, 190, 'UTF-8'));
            $additionalIndicator->set('description', trim((string)($_POST['description'] ?? '')));
            $additionalIndicator->set('source_url', null);
            $additionalIndicator->set('source_type', StatIndicator::SOURCE_ETHERCALC_TABLE);
            $additionalIndicator->set('ethercalc_range', $ethercalcSourceUpdate['ethercalc_range']);
            $additionalIndicator->set('ethercalc_date_column', $ethercalcSourceUpdate['ethercalc_date_column']);
            $additionalIndicator->set('ethercalc_value_column', $valueColumn);
            $additionalIndicator->set('ethercalc_frequency', $ethercalcSourceUpdate['ethercalc_frequency']);
            $additionalIndicator->set('reference_type', $referenceType);
            $additionalIndicator->set('measurement_frequency', $measurementFrequency);
            $additionalIndicator->set('measurement_schedule', $measurementSchedule);
            $additionalIndicator->set('chart_min_value', $chartMinValue);
            $additionalIndicator->set('show_cumulative', !empty($_POST['show_cumulative']) ? 1 : 0);
            $additionalIndicator->set('active', 1);
            $additionalSaveResult = $additionalIndicator->save();
            if (!is_array($additionalSaveResult) || empty($additionalSaveResult['status']) || (int)$additionalIndicator->getId() <= 0) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
            $ethercalcAdditionalIndicators[] = $additionalIndicator;
            $ethercalcAdditionalGroupIndicators[] = $additionalIndicator;
        }
        foreach ($spreadsheetAdditionalValueColumns as $valueColumn) {
            $existingSource = StatIndicator::findActiveSpreadsheetTableSource(
                $organizationId,
                (int)$indicator->get('IDholon'),
                (int)$spreadsheetSourceUpdate['IDdocument'],
                (string)$spreadsheetSourceUpdate['spreadsheet_sheet'],
                (string)$spreadsheetSourceUpdate['spreadsheet_range'],
                (string)$spreadsheetSourceUpdate['spreadsheet_date_column'],
                $valueColumn
            );
            if ($existingSource instanceof StatIndicator && (int)$existingSource->getId() !== (int)$indicator->getId()) {
                $spreadsheetAdditionalGroupIndicators[] = $existingSource;
                continue;
            }
            $additionalIndicator = new StatIndicator();
            $additionalIndicator->set('IDorganization', $organizationId);
            $additionalIndicator->set('IDholon', $indicator->get('IDholon'));
            $additionalIndicator->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
            $additionalIndicator->set('IDdocument', $spreadsheetSourceUpdate['IDdocument']);
            $additionalIndicator->set('name', mb_substr($name . ' - ' . $valueColumn, 0, 190, 'UTF-8'));
            $additionalIndicator->set('description', trim((string)($_POST['description'] ?? '')));
            $additionalIndicator->set('source_url', null);
            $additionalIndicator->set('source_type', StatIndicator::SOURCE_SPREADSHEET_TABLE);
            $additionalIndicator->set('spreadsheet_sheet', $spreadsheetSourceUpdate['spreadsheet_sheet']);
            $additionalIndicator->set('spreadsheet_frequency', $spreadsheetSourceUpdate['spreadsheet_frequency']);
            $additionalIndicator->set('spreadsheet_range', $spreadsheetSourceUpdate['spreadsheet_range']);
            $additionalIndicator->set('spreadsheet_date_column', $spreadsheetSourceUpdate['spreadsheet_date_column']);
            $additionalIndicator->set('spreadsheet_value_column', $valueColumn);
            $additionalIndicator->set('reference_type', $referenceType);
            $additionalIndicator->set('measurement_frequency', $measurementFrequency);
            $additionalIndicator->set('measurement_schedule', $measurementSchedule);
            $additionalIndicator->set('chart_min_value', $chartMinValue);
            $additionalIndicator->set('show_cumulative', !empty($_POST['show_cumulative']) ? 1 : 0);
            $additionalIndicator->set('active', 1);
            $additionalSaveResult = $additionalIndicator->save();
            if (!is_array($additionalSaveResult) || empty($additionalSaveResult['status']) || (int)$additionalIndicator->getId() <= 0) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
            $spreadsheetAdditionalIndicators[] = $additionalIndicator;
            $spreadsheetAdditionalGroupIndicators[] = $additionalIndicator;
        }
        if (count($spreadsheetAdditionalGroupIndicators) > 0) {
            omoStatsActionEnsureAutoEthercalcGroup(
                $organizationId,
                (int)$indicator->get('IDholon'),
                $currentUserId,
                $name,
                array_merge([$indicator], $spreadsheetAdditionalGroupIndicators)
            );
        }
        if (count($ethercalcAdditionalGroupIndicators) > 0) {
            omoStatsActionEnsureAutoEthercalcGroup(
                $organizationId,
                (int)$indicator->get('IDholon'),
                $currentUserId,
                $name,
                array_merge([$indicator], $ethercalcAdditionalGroupIndicators)
            );
        }

        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoStatsActionRespond(false, $exception->getMessage() !== '' ? $exception->getMessage() : omoStatsT('stats.error.save'), [], 500);
    }

    if (is_array($ethercalcSourceUpdate)) {
        $syncResult = omoStatsSynchronizeEthercalcIndicator($indicator);
        if (empty($syncResult['status'])) {
            error_log('Updated EtherCalc indicator was not synchronized: ' . (string)($syncResult['text'] ?? 'unknown error'));
        }
    }
    foreach ($ethercalcAdditionalIndicators ?? [] as $additionalIndicator) {
        $syncResult = omoStatsSynchronizeEthercalcIndicator($additionalIndicator);
        if (empty($syncResult['status'])) {
            error_log('Additional EtherCalc indicator was not synchronized: ' . (string)($syncResult['text'] ?? 'unknown error'));
        }
    }
    if (is_array($spreadsheetSourceUpdate)) {
        $syncResult = omoStatsSynchronizeSpreadsheetIndicator($indicator);
        if (empty($syncResult['status'])) {
            error_log('Updated spreadsheet indicator was not synchronized: ' . (string)($syncResult['text'] ?? 'unknown error'));
        }
    }
    foreach ($spreadsheetAdditionalIndicators ?? [] as $additionalIndicator) {
        $syncResult = omoStatsSynchronizeSpreadsheetIndicator($additionalIndicator);
        if (empty($syncResult['status'])) {
            error_log('Additional spreadsheet indicator was not synchronized: ' . (string)($syncResult['text'] ?? 'unknown error'));
        }
    }

    omoStatsActionRespond(true, '', [
        'id' => (int)$indicator->getId(),
        'detailUrl' => '/omo/api/stats/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$indicator->getId()),
    ]);
}

if ($action === 'add_value') {
    $indicatorId = isset($_POST['indicator_id']) && is_numeric($_POST['indicator_id']) ? (int)$_POST['indicator_id'] : 0;
    $indicator = omoStatsLoadIndicator($indicatorId, $organizationId);
    if (!($indicator instanceof StatIndicator)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.not_found'), [], 404);
    }
    if (!$indicator->canEdit()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    if ($indicator->isEthercalcSource()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.ethercalc_synced'), [], 403);
    }
    if ($indicator->isSpreadsheetSource()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.spreadsheet_synced'), [], 403);
    }

    $valueNumber = omoStatsActionParseDecimal($_POST['value'] ?? null);
    if ($valueNumber === null) {
        omoStatsActionRespond(false, omoStatsT('stats.error.value'), [], 422);
    }

    $measuredAt = omoStatsActionParseDateTime($_POST['measured_at'] ?? '');
    if (!($measuredAt instanceof \DateTimeInterface)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.date'), [], 422);
    }

    $value = new StatIndicatorValue();
    $value->set('IDstatindicator', (int)$indicator->getId());
    $value->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
    $value->set('value', $valueNumber);
    $value->set('measured_at', $measuredAt);
    $saveResult = $value->save();
    if (!is_array($saveResult) || empty($saveResult['status'])) {
        omoStatsActionRespond(false, omoStatsT('stats.error.value_save'), [], 500);
    }

    omoStatsActionRespond(true, '', ['id' => (int)$value->getId()]);
}

if ($action === 'delete_value') {
    $valueId = isset($_POST['value_id']) && is_numeric($_POST['value_id']) ? (int)$_POST['value_id'] : 0;
    $value = new StatIndicatorValue();
    if (!$value->load($valueId)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.not_found'), [], 404);
    }

    $indicator = $value->getIndicator();
    if (
        !($indicator instanceof StatIndicator)
        || (int)$indicator->get('IDorganization') !== $organizationId
        || !$indicator->canView()
    ) {
        omoStatsActionRespond(false, omoStatsT('stats.error.not_found'), [], 404);
    }
    if (!$indicator->canEdit()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    if ($indicator->isEthercalcSource()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.ethercalc_synced'), [], 403);
    }

    if (!$value->delete()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.value_save'), [], 500);
    }

    omoStatsActionRespond(true);
}

if ($action === 'delete_indicator') {
    $indicatorId = isset($_POST['indicator_id']) && is_numeric($_POST['indicator_id']) ? (int)$_POST['indicator_id'] : 0;
    $indicator = omoStatsLoadIndicator($indicatorId, $organizationId);
    if (!($indicator instanceof StatIndicator)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.not_found'), [], 404);
    }
    if (!$indicator->canEdit()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    $indicator->set('active', 0);
    $result = $indicator->save();
    if (!is_array($result) || empty($result['status'])) {
        omoStatsActionRespond(false, omoStatsT('stats.error.save'), [], 500);
    }
    omoStatsActionRespond(true, '', ['id' => $indicatorId]);
}

if ($action === 'create_spreadsheet_indicator') {
    if (!omoStatsCanCreateContext($context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }

    $documentId = isset($_POST['spreadsheet_document_id']) && is_numeric($_POST['spreadsheet_document_id'])
        ? (int)$_POST['spreadsheet_document_id']
        : 0;
    $document = omoStatsLoadVisibleSpreadsheetDocument($documentId, $organizationId);
    if (!($document instanceof Document)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.document_spreadsheet'), [], 422);
    }

    $mode = trim((string)($_POST['spreadsheet_mode'] ?? ''));
    if (!in_array($mode, ['cell', 'table'], true)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.spreadsheet_mode'), [], 422);
    }

    $baseName = trim((string)($_POST['spreadsheet_name'] ?? ''));
    if ($baseName === '') {
        $baseName = trim((string)$document->get('title'));
    }
    $baseName = mb_substr($baseName, 0, 160, 'UTF-8');
    if ($baseName === '') {
        omoStatsActionRespond(false, omoStatsT('stats.error.name'), [], 422);
    }

    $sheet = StatIndicator::normalizeSpreadsheetSheet($_POST['spreadsheet_sheet'] ?? '');
    $frequency = StatIndicator::normalizeSpreadsheetFrequency($_POST['spreadsheet_frequency'] ?? '');
    $sourceDefinitions = [];
    if ($mode === 'cell') {
        $cell = StatIndicator::normalizeEthercalcCell($_POST['spreadsheet_cell'] ?? '');
        if ($cell === '') {
            omoStatsActionRespond(false, omoStatsT('stats.error.spreadsheet_cell'), [], 422);
        }
        $sourceDefinitions[] = [
            'name' => $baseName,
            'source_type' => StatIndicator::SOURCE_SPREADSHEET_CELL,
            'spreadsheet_sheet' => $sheet,
            'spreadsheet_cell' => $cell,
            'spreadsheet_frequency' => $frequency,
        ];
    } else {
        $range = StatIndicator::normalizeEthercalcRange($_POST['spreadsheet_range'] ?? '');
        $dateColumn = StatIndicator::normalizeEthercalcColumn($_POST['spreadsheet_date_column'] ?? '');
        $rawColumns = preg_split('/[\s,;]+/', strtoupper(trim((string)($_POST['spreadsheet_value_columns'] ?? ''))));
        $valueColumns = [];
        foreach (is_array($rawColumns) ? $rawColumns : [] as $rawColumn) {
            $column = StatIndicator::normalizeEthercalcColumn($rawColumn);
            if ($column !== '' && $column !== $dateColumn) {
                $valueColumns[$column] = $column;
            }
        }
        if ($range === '' || $dateColumn === '' || count($valueColumns) === 0) {
            omoStatsActionRespond(false, omoStatsT('stats.error.single_value_column_required'), [], 422);
        }
        preg_match('/^([A-Z]+)[0-9]+:([A-Z]+)[0-9]+$/', $range, $rangeMatches);
        $rangeStartColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[1] ?? '');
        $rangeEndColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[2] ?? '');
        foreach (array_merge([$dateColumn], array_values($valueColumns)) as $selectedColumn) {
            $selectedColumnIndex = StatIndicator::ethercalcColumnToIndex($selectedColumn);
            if ($selectedColumnIndex < $rangeStartColumn || $selectedColumnIndex > $rangeEndColumn) {
                omoStatsActionRespond(false, omoStatsT('stats.error.columns_in_range'), [], 422);
            }
        }
        foreach ($valueColumns as $column) {
            $sourceDefinitions[] = [
                'name' => mb_substr($baseName . ' - ' . $column, 0, 190, 'UTF-8'),
                'source_type' => StatIndicator::SOURCE_SPREADSHEET_TABLE,
                'spreadsheet_sheet' => $sheet,
                'spreadsheet_frequency' => $frequency,
                'spreadsheet_range' => $range,
                'spreadsheet_date_column' => $dateColumn,
                'spreadsheet_value_column' => $column,
            ];
        }
    }

    $pdo = \dbObject\DbObject::getPdo();
    $startedTransaction = false;
    $createdIndicators = [];
    $createdGroup = null;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }
        foreach ($sourceDefinitions as $definition) {
            $indicator = new StatIndicator();
            $indicator->set('IDorganization', $organizationId);
            $indicator->set('IDholon', ($context['currentHolon'] ?? null) instanceof \dbObject\Holon
                ? (int)$context['currentHolon']->getId()
                : null);
            $indicator->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
            $indicator->set('IDdocument', (int)$document->getId());
            $indicator->set('name', $definition['name']);
            $indicator->set('description', 'Synchronisé depuis le document tableur « ' . trim((string)$document->get('title')) . ' ».');
            $indicator->set('source_type', $definition['source_type']);
            foreach ($definition as $field => $value) {
                if ($field !== 'name' && $field !== 'source_type') {
                    $indicator->set($field, $value);
                }
            }
            $indicator->set('reference_type', StatIndicator::REFERENCE_NONE);
            $indicator->set('measurement_frequency', null);
            $indicator->set('measurement_schedule', null);
            $indicator->set('show_cumulative', 0);
            $indicator->set('active', 1);
            $saveResult = $indicator->save();
            if (!is_array($saveResult) || empty($saveResult['status']) || (int)$indicator->getId() <= 0) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
            $createdIndicators[] = $indicator;
        }
        if ($mode === 'table' && count($createdIndicators) > 1) {
            $createdGroup = omoStatsActionEnsureAutoEthercalcGroup(
                $organizationId,
                ($context['currentHolon'] ?? null) instanceof \dbObject\Holon
                    ? (int)$context['currentHolon']->getId()
                    : 0,
                $currentUserId,
                $baseName,
                $createdIndicators
            );
        }
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoStatsActionRespond(false, $exception->getMessage() !== '' ? $exception->getMessage() : omoStatsT('stats.error.save'), [], 500);
    }

    $synchronized = 0;
    foreach ($createdIndicators as $indicator) {
        $syncResult = omoStatsSynchronizeSpreadsheetIndicator($indicator);
        if (!empty($syncResult['status'])) {
            $synchronized += 1;
            $headerLabel = trim((string)($syncResult['header_label'] ?? ''));
            if ($mode === 'table' && $headerLabel !== '') {
                $indicator->set('name', mb_substr($headerLabel, 0, 190, 'UTF-8'));
                $indicator->save();
            }
        } else {
            error_log('Initial spreadsheet indicator synchronization failed: ' . (string)($syncResult['text'] ?? 'unknown error'));
        }
    }

    omoStatsActionRespond(true, '', [
        'ids' => array_map(static fn (StatIndicator $indicator) => (int)$indicator->getId(), $createdIndicators),
        'groupId' => $createdGroup instanceof StatIndicatorGroup ? (int)$createdGroup->getId() : 0,
        'synchronized' => $synchronized,
    ]);
}

if ($action === 'create_ethercalc_indicator') {
    if (!omoStatsCanCreateContext($context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    if (!omoEthercalcHasConfig()) {
        omoStatsActionRespond(false, omoStatsT('stats.error.ethercalc_config'), [], 422);
    }

    $documentId = isset($_POST['ethercalc_document_id']) && is_numeric($_POST['ethercalc_document_id'])
        ? (int)$_POST['ethercalc_document_id']
        : 0;
    $document = omoStatsLoadVisibleEthercalcDocument($documentId, $organizationId);
    if (!($document instanceof Document)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.document_ethercalc'), [], 422);
    }

    $mode = trim((string)($_POST['ethercalc_mode'] ?? ''));
    if (!in_array($mode, ['cell', 'table'], true)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.ethercalc_mode'), [], 422);
    }

    $baseName = trim((string)($_POST['ethercalc_name'] ?? ''));
    if ($baseName === '') {
        $baseName = trim((string)$document->get('title'));
    }
    $baseName = mb_substr($baseName, 0, 160, 'UTF-8');
    if ($baseName === '') {
        omoStatsActionRespond(false, omoStatsT('stats.error.name'), [], 422);
    }

    $sourceDefinitions = [];
    if ($mode === 'cell') {
        $cell = StatIndicator::normalizeEthercalcCell($_POST['ethercalc_cell'] ?? '');
        if ($cell === '') {
            omoStatsActionRespond(false, omoStatsT('stats.error.ethercalc_cell'), [], 422);
        }
        $sourceDefinitions[] = [
            'name' => $baseName,
            'source_type' => StatIndicator::SOURCE_ETHERCALC_CELL,
            'ethercalc_cell' => $cell,
            'ethercalc_frequency' => StatIndicator::normalizeEthercalcFrequency($_POST['ethercalc_frequency'] ?? ''),
        ];
    } else {
        $range = StatIndicator::normalizeEthercalcRange($_POST['ethercalc_range'] ?? '');
        $dateColumn = StatIndicator::normalizeEthercalcColumn($_POST['ethercalc_date_column'] ?? '');
        $rawColumns = preg_split('/[\s,;]+/', strtoupper(trim((string)($_POST['ethercalc_value_columns'] ?? ''))));
        $valueColumns = [];
        foreach (is_array($rawColumns) ? $rawColumns : [] as $rawColumn) {
            $column = StatIndicator::normalizeEthercalcColumn($rawColumn);
            if ($column !== '' && $column !== $dateColumn) {
                $valueColumns[$column] = $column;
            }
        }
        if ($range === '' || $dateColumn === '' || count($valueColumns) === 0) {
            omoStatsActionRespond(false, omoStatsT('stats.error.single_value_column_required'), [], 422);
        }
        preg_match('/^([A-Z]+)[0-9]+:([A-Z]+)[0-9]+$/', $range, $rangeMatches);
        $rangeStartColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[1] ?? '');
        $rangeEndColumn = StatIndicator::ethercalcColumnToIndex($rangeMatches[2] ?? '');
        $selectedColumns = array_merge([$dateColumn], array_values($valueColumns));
        foreach ($selectedColumns as $selectedColumn) {
            $selectedColumnIndex = StatIndicator::ethercalcColumnToIndex($selectedColumn);
            if ($selectedColumnIndex < $rangeStartColumn || $selectedColumnIndex > $rangeEndColumn) {
                omoStatsActionRespond(false, omoStatsT('stats.error.columns_in_range'), [], 422);
            }
        }
        foreach ($valueColumns as $column) {
            $sourceDefinitions[] = [
                'name' => mb_substr($baseName . ' - ' . $column, 0, 190, 'UTF-8'),
                'source_type' => StatIndicator::SOURCE_ETHERCALC_TABLE,
                'ethercalc_frequency' => StatIndicator::normalizeEthercalcFrequency($_POST['ethercalc_frequency'] ?? ''),
                'ethercalc_range' => $range,
                'ethercalc_date_column' => $dateColumn,
                'ethercalc_value_column' => $column,
            ];
        }
    }

    $pdo = \dbObject\DbObject::getPdo();
    $startedTransaction = false;
    $createdIndicators = [];
    $createdGroup = null;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }
        foreach ($sourceDefinitions as $definition) {
            $indicator = new StatIndicator();
            $indicator->set('IDorganization', $organizationId);
            $indicator->set('IDholon', ($context['currentHolon'] ?? null) instanceof \dbObject\Holon
                ? (int)$context['currentHolon']->getId()
                : null);
            $indicator->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
            $indicator->set('IDdocument', (int)$document->getId());
            $indicator->set('name', $definition['name']);
            $indicator->set('description', 'Synchronisé depuis le tableur collaboratif « ' . trim((string)$document->get('title')) . ' ».');
            $indicator->set('source_url', null);
            $indicator->set('source_type', $definition['source_type']);
            $indicator->set('ethercalc_cell', $definition['ethercalc_cell'] ?? null);
            $indicator->set('ethercalc_frequency', $definition['ethercalc_frequency'] ?? null);
            $indicator->set('ethercalc_range', $definition['ethercalc_range'] ?? null);
            $indicator->set('ethercalc_date_column', $definition['ethercalc_date_column'] ?? null);
            $indicator->set('ethercalc_value_column', $definition['ethercalc_value_column'] ?? null);
            $indicator->set('reference_type', StatIndicator::REFERENCE_NONE);
            $indicator->set('measurement_frequency', null);
            $indicator->set('measurement_schedule', null);
            $indicator->set('show_cumulative', 0);
            $indicator->set('active', 1);
            $saveResult = $indicator->save();
            if (!is_array($saveResult) || empty($saveResult['status']) || (int)$indicator->getId() <= 0) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
            $createdIndicators[] = $indicator;
        }
        if ($mode === 'table' && count($createdIndicators) > 1) {
            $createdGroup = omoStatsActionEnsureAutoEthercalcGroup(
                $organizationId,
                ($context['currentHolon'] ?? null) instanceof \dbObject\Holon
                    ? (int)$context['currentHolon']->getId()
                    : 0,
                $currentUserId,
                $baseName,
                $createdIndicators
            );
        }
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoStatsActionRespond(false, $exception->getMessage() !== '' ? $exception->getMessage() : omoStatsT('stats.error.save'), [], 500);
    }

    $synchronized = 0;
    foreach ($createdIndicators as $indicator) {
        $syncResult = omoStatsSynchronizeEthercalcIndicator($indicator);
        if (!empty($syncResult['status'])) {
            $synchronized += 1;
            $headerLabel = trim((string)($syncResult['header_label'] ?? ''));
            if ($mode === 'table' && $headerLabel !== '') {
                $indicator->set('name', mb_substr($headerLabel, 0, 190, 'UTF-8'));
                $indicator->save();
            }
        } else {
            error_log('Initial EtherCalc indicator synchronization failed: ' . (string)($syncResult['text'] ?? 'unknown error'));
        }
    }

    omoStatsActionRespond(true, '', [
        'ids' => array_map(static fn (StatIndicator $indicator) => (int)$indicator->getId(), $createdIndicators),
        'groupId' => $createdGroup instanceof StatIndicatorGroup ? (int)$createdGroup->getId() : 0,
        'synchronized' => $synchronized,
    ]);
}

if ($action === 'import_indicator') {
    if (!omoStatsCanManageContext($context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    $indicatorId = isset($_POST['indicator_id']) && is_numeric($_POST['indicator_id']) ? (int)$_POST['indicator_id'] : 0;
    $indicator = omoStatsLoadIndicator($indicatorId, $organizationId);
    if (!($indicator instanceof StatIndicator)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
    }

    $targetHolonId = ($context['currentHolon'] ?? null) instanceof \dbObject\Holon
        ? (int)$context['currentHolon']->getId()
        : 0;
    if ((int)$indicator->get('IDholon') === $targetHolonId) {
        omoStatsActionRespond(true, '', ['id' => (int)$indicator->getId(), 'existing' => true]);
    }
    $existingImports = new \dbObject\ArrayStatIndicatorImport();
    $existingImports->loadForContext($organizationId, $targetHolonId);
    foreach ($existingImports as $existingImport) {
        if ((int)$existingImport->get('IDstatindicator') === (int)$indicator->getId()) {
            omoStatsActionRespond(true, '', ['id' => (int)$existingImport->getId(), 'existing' => true]);
        }
    }

    $import = new StatIndicatorImport();
    $import->set('IDorganization', $organizationId);
    $import->set('IDholon', $targetHolonId > 0 ? $targetHolonId : null);
    $import->set('IDstatindicator', (int)$indicator->getId());
    $import->set('active', 1);
    $result = $import->save();
    if (!is_array($result) || empty($result['status'])) {
        omoStatsActionRespond(false, omoStatsT('stats.error.save'), [], 500);
    }
    omoStatsActionRespond(true, '', ['id' => (int)$import->getId()]);
}

if ($action === 'update_import') {
    $importId = isset($_POST['import_id']) && is_numeric($_POST['import_id']) ? (int)$_POST['import_id'] : 0;
    $import = omoStatsLoadImport($importId, $organizationId);
    if (!($import instanceof StatIndicatorImport) || !omoStatsCanEditContextResource($import, $context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    $indicatorId = isset($_POST['indicator_id']) && is_numeric($_POST['indicator_id']) ? (int)$_POST['indicator_id'] : 0;
    $indicator = omoStatsLoadIndicator($indicatorId, $organizationId);
    if (!($indicator instanceof StatIndicator)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
    }
    if ((int)$indicator->get('IDholon') === (int)$import->get('IDholon')) {
        omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
    }
    $existingImports = new \dbObject\ArrayStatIndicatorImport();
    $existingImports->loadForContext($organizationId, (int)$import->get('IDholon'));
    foreach ($existingImports as $existingImport) {
        if ((int)$existingImport->getId() !== $importId && (int)$existingImport->get('IDstatindicator') === $indicatorId) {
            omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
        }
    }
    $import->set('IDstatindicator', $indicatorId);
    $result = $import->save();
    if (!is_array($result) || empty($result['status'])) {
        omoStatsActionRespond(false, omoStatsT('stats.error.save'), [], 500);
    }
    omoStatsActionRespond(true, '', ['id' => $importId]);
}

if ($action === 'delete_import') {
    $importId = isset($_POST['import_id']) && is_numeric($_POST['import_id']) ? (int)$_POST['import_id'] : 0;
    $import = omoStatsLoadImport($importId, $organizationId);
    if (!($import instanceof StatIndicatorImport) || !omoStatsCanEditContextResource($import, $context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    $import->set('active', 0);
    $result = $import->save();
    if (!is_array($result) || empty($result['status'])) {
        omoStatsActionRespond(false, omoStatsT('stats.error.save'), [], 500);
    }
    omoStatsActionRespond(true, '', ['id' => $importId]);
}

if ($action === 'update_group') {
    $groupId = isset($_POST['group_id']) && is_numeric($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $group = omoStatsLoadGroup($groupId, $organizationId);
    if (!($group instanceof StatIndicatorGroup) || !omoStatsCanEditContextResource($group, $context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        omoStatsActionRespond(false, omoStatsT('stats.error.group_name'), [], 422);
    }
    $rawIndicatorIds = $_POST['indicator_ids'] ?? [];
    $indicatorIds = is_array($rawIndicatorIds) ? $rawIndicatorIds : [];
    $indicatorIds = array_values(array_unique(array_filter(array_map('intval', $indicatorIds), static function ($id) {
        return $id > 0;
    })));
    if (count($indicatorIds) === 0) {
        omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
    }
    foreach ($indicatorIds as $indicatorId) {
        if (!omoStatsLoadIndicator($indicatorId, $organizationId)) {
            omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
        }
    }
    $referenceType = StatIndicator::normalizeReferenceType($_POST['reference_type'] ?? StatIndicator::REFERENCE_NONE);
    try {
        $chartMinValue = omoStatsActionParseChartMinimumValue($_POST['chart_min_value'] ?? null);
        $referencePoints = omoStatsActionParseGroupReferencePoints(
            $referenceType,
            $_POST['reference_points'] ?? [],
            $_POST['ceiling_value'] ?? null
        );
    } catch (\InvalidArgumentException $exception) {
        omoStatsActionRespond(false, $exception->getMessage(), [], 422);
    }

    $pdo = \dbObject\DbObject::getPdo();
    $startedTransaction = false;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }
        $group->set('name', mb_substr($name, 0, 190, 'UTF-8'));
        $group->set('display_mode', StatIndicatorGroup::normalizeDisplayMode($_POST['display_mode'] ?? null));
        $group->set('reference_type', $referenceType);
        $group->set('chart_min_value', $chartMinValue);
        $group->set('hide_same_holon_sources', !empty($_POST['hide_same_holon_sources']) ? 1 : 0);
        $groupResult = $group->save();
        if (!is_array($groupResult) || empty($groupResult['status'])) {
            throw new \RuntimeException(omoStatsT('stats.error.save'));
        }
        foreach ($group->getItems() as $existingItem) {
            if ($existingItem instanceof StatIndicatorGroupItem && !$existingItem->delete()) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }
        foreach ($indicatorIds as $position => $indicatorId) {
            $item = new StatIndicatorGroupItem();
            $item->set('IDstatindicatorgroup', $groupId);
            $item->set('IDstatindicator', $indicatorId);
            $item->set('position', $position + 1);
            $itemResult = $item->save();
            if (!is_array($itemResult) || empty($itemResult['status'])) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }
        foreach ($group->getReferencePoints() as $existingPoint) {
            if ($existingPoint instanceof StatIndicatorReferencePoint && !$existingPoint->delete()) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }
        foreach ($referencePoints as $pointData) {
            $point = new StatIndicatorReferencePoint();
            $point->set('IDstatindicatorgroup', (int)$group->getId());
            $point->set('position_percent', (float)$pointData['position_percent']);
            $point->set('value', (float)$pointData['value']);
            $point->set('point_at', $pointData['point_at']);
            $pointResult = $point->save();
            if (!is_array($pointResult) || empty($pointResult['status'])) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoStatsActionRespond(false, $exception->getMessage() ?: omoStatsT('stats.error.save'), [], 500);
    }
    omoStatsActionRespond(true, '', ['id' => $groupId]);
}

if ($action === 'delete_group') {
    $groupId = isset($_POST['group_id']) && is_numeric($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $group = omoStatsLoadGroup($groupId, $organizationId);
    if (!($group instanceof StatIndicatorGroup) || !omoStatsCanEditContextResource($group, $context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    $group->set('active', 0);
    $result = $group->save();
    if (!is_array($result) || empty($result['status'])) {
        omoStatsActionRespond(false, omoStatsT('stats.error.save'), [], 500);
    }
    omoStatsActionRespond(true, '', ['id' => $groupId]);
}

if ($action === 'create_group') {
    if (!omoStatsCanManageContext($context)) {
        omoStatsActionRespond(false, omoStatsT('stats.error.forbidden'), [], 403);
    }
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        omoStatsActionRespond(false, omoStatsT('stats.error.group_name'), [], 422);
    }
    $rawIndicatorIds = $_POST['indicator_ids'] ?? [];
    $indicatorIds = is_array($rawIndicatorIds) ? $rawIndicatorIds : [];
    $indicatorIds = array_values(array_unique(array_filter(array_map('intval', $indicatorIds), static function ($id) {
        return $id > 0;
    })));
    if (count($indicatorIds) === 0) {
        omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
    }
    $indicators = [];
    foreach ($indicatorIds as $indicatorId) {
        $indicator = omoStatsLoadIndicator($indicatorId, $organizationId);
        if (!($indicator instanceof StatIndicator)) {
            omoStatsActionRespond(false, omoStatsT('stats.error.selection'), [], 422);
        }
        $indicators[] = $indicator;
    }
    $referenceType = StatIndicator::normalizeReferenceType($_POST['reference_type'] ?? StatIndicator::REFERENCE_NONE);
    try {
        $chartMinValue = omoStatsActionParseChartMinimumValue($_POST['chart_min_value'] ?? null);
        $referencePoints = omoStatsActionParseGroupReferencePoints(
            $referenceType,
            $_POST['reference_points'] ?? [],
            $_POST['ceiling_value'] ?? null
        );
    } catch (\InvalidArgumentException $exception) {
        omoStatsActionRespond(false, $exception->getMessage(), [], 422);
    }

    $pdo = \dbObject\DbObject::getPdo();
    $startedTransaction = false;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }
        $group = new StatIndicatorGroup();
        $group->set('IDorganization', $organizationId);
        $group->set('IDholon', ($context['currentHolon'] ?? null) instanceof \dbObject\Holon
            ? (int)$context['currentHolon']->getId()
            : null);
        $group->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
        $group->set('name', mb_substr($name, 0, 190, 'UTF-8'));
        $group->set('display_mode', StatIndicatorGroup::normalizeDisplayMode($_POST['display_mode'] ?? null));
        $group->set('reference_type', $referenceType);
        $group->set('chart_min_value', $chartMinValue);
        $group->set('hide_same_holon_sources', !empty($_POST['hide_same_holon_sources']) ? 1 : 0);
        $group->set('active', 1);
        $groupResult = $group->save();
        if (!is_array($groupResult) || empty($groupResult['status']) || (int)$group->getId() <= 0) {
            throw new \RuntimeException(omoStatsT('stats.error.save'));
        }
        foreach ($indicators as $position => $indicator) {
            $item = new StatIndicatorGroupItem();
            $item->set('IDstatindicatorgroup', (int)$group->getId());
            $item->set('IDstatindicator', (int)$indicator->getId());
            $item->set('position', $position + 1);
            $itemResult = $item->save();
            if (!is_array($itemResult) || empty($itemResult['status'])) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }
        foreach ($referencePoints as $pointData) {
            $point = new StatIndicatorReferencePoint();
            $point->set('IDstatindicatorgroup', (int)$group->getId());
            $point->set('position_percent', (float)$pointData['position_percent']);
            $point->set('value', (float)$pointData['value']);
            $point->set('point_at', $pointData['point_at']);
            $pointResult = $point->save();
            if (!is_array($pointResult) || empty($pointResult['status'])) {
                throw new \RuntimeException(omoStatsT('stats.error.save'));
            }
        }
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoStatsActionRespond(false, $exception->getMessage() ?: omoStatsT('stats.error.save'), [], 500);
    }
    omoStatsActionRespond(true, '', ['id' => (int)$group->getId()]);
}

omoStatsActionRespond(false, omoStatsT('stats.error.action'), [], 400);

?>
