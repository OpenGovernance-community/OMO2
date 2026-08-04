<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\StatIndicator;
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

    $rawSourceUrl = trim((string)($_POST['source_url'] ?? ''));
    $sourceUrl = StatIndicator::sanitizeSourceUrl($rawSourceUrl);
    if ($rawSourceUrl !== '' && $sourceUrl === '') {
        omoStatsActionRespond(false, omoStatsT('stats.error.url'), [], 422);
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
    $indicator->set('source_url', $sourceUrl !== '' ? $sourceUrl : null);
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

        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoStatsActionRespond(false, $exception->getMessage() !== '' ? $exception->getMessage() : omoStatsT('stats.error.save'), [], 500);
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
