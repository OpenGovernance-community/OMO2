<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'POST';

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/holon.class.php';
require_once dirname(__DIR__) . '/class/dbobject/recurrenceschedule.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicator.class.php';

function commonGetCurrentUserId(): int { return 7; }
function omoStatsT($key, $params = []) { return (string)$key; }

require_once dirname(__DIR__) . '/omo/api/stats/shared.php';

final class MovePermissionHolon extends \dbObject\Holon
{
    public bool $canCreate = false;
    public bool $canDelete = false;
    public bool $inOrganization = true;
    public bool $viewable = true;
    public bool $template = false;
    public array $fields = ['active' => 1, 'visible' => 1];

    public function get($field) { return $this->fields[$field] ?? null; }
    public function isAllowed($permissionKey, $useSessionCache = true, $userId = 0)
    {
        return $permissionKey === 'CAN_CREATE_INDICATOR' ? $this->canCreate : $this->canDelete;
    }
    public function isDescendantOf($ancestor, $includeSelf = true) { return $this->inOrganization; }
    public function canViewDetail() { return $this->viewable; }
    public function isTemplateNode($rootHolonId = 0) { return $this->template; }
}

final class MovePermissionIndicator extends \dbObject\StatIndicator
{
    public function __construct(private MovePermissionHolon $source)
    {
        parent::__construct();
    }
    public function get($field) { return $field === 'IDholon' ? $this->source->getId() : null; }
    public function getHolon() { return $this->source; }
}

function checkMovePermission(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$root = new MovePermissionHolon();
$root->setId(1);
$source = new MovePermissionHolon();
$source->setId(2);
$source->canDelete = true;
$target = new MovePermissionHolon();
$target->setId(3);
$target->canCreate = true;
$indicator = new MovePermissionIndicator($source);
$context = ['rootHolon' => $root];

checkMovePermission(omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Delete at source and create at target allow the move.');
$source->canDelete = false;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Delete permission at source is required.');
$source->canDelete = true;
$target->canCreate = false;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Create permission at target is required.');
$target->canCreate = true;
$target->inOrganization = false;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Target must be in the same organization.');
$target->inOrganization = true;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $source, $context), 'The current space is not a move destination.');
$target->fields['active'] = 0;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Inactive target is forbidden.');
$target->fields['active'] = 1;
$target->viewable = false;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Inaccessible target is forbidden.');
$target->viewable = true;
$target->fields['visible'] = 0;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Hidden target is forbidden.');
$target->fields['visible'] = 1;
$target->template = true;
checkMovePermission(!omoStatsCanMoveIndicatorToHolon($indicator, $target, $context), 'Template target is forbidden.');

echo "stat_indicator_move_permissions_test: OK\n";
