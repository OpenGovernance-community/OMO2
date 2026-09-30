<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/holon.class.php';

final class MandatoryGroupDeleteTestHolon extends \dbObject\Holon
{
    public ?\dbObject\Holon $parent = null;
    public bool $editable = true;
    public int $siblingCount = 0;

    public function getParentHolon() { return $this->parent; }
    public function canEdit() { return $this->editable; }
    public function isMandatoryTemplateInstance() { return true; }
    public function countSiblingTemplateInstances() { return $this->siblingCount; }
}

function mandatoryDeleteAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$group = new \dbObject\Holon();
$group->set('IDtypeholon', 3);
$circle = new \dbObject\Holon();
$circle->set('IDtypeholon', 2);
$role = new MandatoryGroupDeleteTestHolon();
$role->parent = $group;
$role->set('visible', true);

mandatoryDeleteAssert($role->isLastMandatoryTemplateInstance(), 'The role is still the last instance under its direct parent.');
mandatoryDeleteAssert($role->canDelete(), 'A mandatory template instance in a group must be deletable.');
$role->parent = $circle;
mandatoryDeleteAssert(!$role->canDelete(), 'The last mandatory template instance in a circle must remain protected.');
$role->siblingCount = 1;
mandatoryDeleteAssert($role->canDelete(), 'Another instance in the circle must permit deletion.');
$role->parent = $group;
$role->siblingCount = 0;
$role->editable = false;
mandatoryDeleteAssert(!$role->canDelete(), 'A user without edit access must remain unable to delete the role.');
$role->editable = true;
$role->set('mandatory', true);
$role->set('templatename', 'Required template');
mandatoryDeleteAssert(!$role->canDelete(), 'A visible mandatory template definition must remain protected.');

echo "holon_mandatory_group_delete_test: OK\n";
