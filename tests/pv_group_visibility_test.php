<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/arraydbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/documentpvpoint.class.php';
require_once dirname(__DIR__) . '/class/dbobject/arraydocumentpvpoint.class.php';
require_once dirname(__DIR__) . '/class/dbobject/objectvisibility.class.php';
require_once dirname(__DIR__) . '/class/dbobject/document.class.php';

function assertPvGroupVisibility(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class PvGroupVisibilityTestDocument extends \dbObject\Document
{
    private bool $manager = false;
    private \dbObject\ArrayDocumentPvPoint $testPoints;

    public function setTestContext(\dbObject\ArrayDocumentPvPoint $points, bool $manager): void
    {
        $this->testPoints = $points;
        $this->manager = $manager;
    }

    public function isPvDocument(): bool
    {
        return true;
    }

    public function canUserManagePvDocument(int $userId): bool
    {
        return $this->manager;
    }

    public function isUserPresentAtPvMeeting(int $userId, int $organizationId = 0): bool
    {
        return false;
    }

    public function getPvPoints(bool $activeOnly = true)
    {
        return $this->testPoints;
    }
}

final class PvGroupVisibilityTestItem extends \dbObject\DocumentPvPoint
{
    public function __construct(private int $testId, private array $testFields)
    {
    }

    public function getId()
    {
        return $this->testId;
    }

    public function get($field)
    {
        return $this->testFields[(string)$field] ?? null;
    }
}

function buildPvGroupVisibilityItem(int $id, string $itemType, int $parentId = 0, bool $confidential = false): \dbObject\DocumentPvPoint
{
    return new PvGroupVisibilityTestItem($id, [
        'item_type' => $itemType,
        'IDparent' => $parentId > 0 ? $parentId : null,
        'is_confidential' => $confidential ? 1 : 0,
    ]);
}

function buildPvGroupVisibilityCollection(array $items): \dbObject\ArrayDocumentPvPoint
{
    $collection = new \dbObject\ArrayDocumentPvPoint();
    $collection->exchangeArray($items);
    return $collection;
}

$emptyGroup = buildPvGroupVisibilityItem(10, \dbObject\DocumentPvPoint::ITEM_TYPE_GROUP);
$managerDocument = new PvGroupVisibilityTestDocument();
$managerDocument->setTestContext(buildPvGroupVisibilityCollection([$emptyGroup]), true);
$managerItems = $managerDocument->getVisiblePvPointsForUser(1, true);
assertPvGroupVisibility(count($managerItems) === 1, 'A PV manager must keep an empty group visible during synchronization.');
assertPvGroupVisibility((int)$managerItems[0]->getId() === 10, 'The synchronized group must retain its identifier.');

$visibleGroup = buildPvGroupVisibilityItem(20, \dbObject\DocumentPvPoint::ITEM_TYPE_GROUP);
$visiblePoint = buildPvGroupVisibilityItem(21, \dbObject\DocumentPvPoint::ITEM_TYPE_POINT, 20);
$emptyRestrictedGroup = buildPvGroupVisibilityItem(30, \dbObject\DocumentPvPoint::ITEM_TYPE_GROUP);
$viewerDocument = new PvGroupVisibilityTestDocument();
$viewerDocument->setTestContext(buildPvGroupVisibilityCollection([$visibleGroup, $visiblePoint, $emptyRestrictedGroup]), false);
$viewerItems = $viewerDocument->getVisiblePvPointsForUser(2, true);
$viewerIds = array_map(static fn($item): int => (int)$item->getId(), $viewerItems->getArrayCopy());
assertPvGroupVisibility($viewerIds === [20, 21, 30], 'Other viewers must receive empty groups so they can classify their points.');

$confidentialGroup = buildPvGroupVisibilityItem(40, \dbObject\DocumentPvPoint::ITEM_TYPE_GROUP);
$confidentialPoint = buildPvGroupVisibilityItem(41, \dbObject\DocumentPvPoint::ITEM_TYPE_POINT, 40, true);
$confidentialViewerDocument = new PvGroupVisibilityTestDocument();
$confidentialViewerDocument->setTestContext(buildPvGroupVisibilityCollection([$confidentialGroup, $confidentialPoint]), false);
$confidentialViewerItems = $confidentialViewerDocument->getVisiblePvPointsForUser(2, true);
$confidentialViewerIds = array_map(static fn($item): int => (int)$item->getId(), $confidentialViewerItems->getArrayCopy());
assertPvGroupVisibility($confidentialViewerIds === [40], 'Shared groups must not expose their confidential points.');

echo "pv_group_visibility_test: OK\n";
