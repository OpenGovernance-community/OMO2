<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/property.class.php';
require_once dirname(__DIR__) . '/class/dbobject/holon.class.php';

function assertBulkStructure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$holonRows = array(
    array('id' => 1, 'name' => 'Organization', 'nomcomplet' => '', 'color' => '#111111', 'IDtypeholon' => 4, 'IDholon_parent' => null, 'IDholon_template' => null, 'active' => 1, 'visible' => 1, 'templatename' => '', 'link' => 0),
    array('id' => 2, 'name' => 'Circle', 'nomcomplet' => 'Main circle', 'color' => '', 'IDtypeholon' => 2, 'IDholon_parent' => 1, 'IDholon_template' => null, 'active' => 1, 'visible' => 1, 'templatename' => '', 'link' => 0),
    array('id' => 3, 'name' => 'Role B', 'nomcomplet' => '', 'color' => '', 'IDtypeholon' => 1, 'IDholon_parent' => 2, 'IDholon_template' => 10, 'active' => 1, 'visible' => 1, 'templatename' => '', 'link' => 0),
    array('id' => 4, 'name' => 'Role A', 'nomcomplet' => '', 'color' => '', 'IDtypeholon' => 1, 'IDholon_parent' => 2, 'IDholon_template' => 11, 'active' => 1, 'visible' => 1, 'templatename' => '', 'link' => 0),
    array('id' => 5, 'name' => 'Nested circle', 'nomcomplet' => '', 'color' => '', 'IDtypeholon' => 2, 'IDholon_parent' => 2, 'IDholon_template' => null, 'active' => 1, 'visible' => 1, 'templatename' => '', 'link' => 0),
    array('id' => 6, 'name' => 'Link role', 'nomcomplet' => '', 'color' => '', 'IDtypeholon' => 1, 'IDholon_parent' => 5, 'IDholon_template' => 10, 'active' => 1, 'visible' => 1, 'templatename' => '', 'link' => 0),
    array('id' => 7, 'name' => 'Hidden role', 'nomcomplet' => '', 'color' => '', 'IDtypeholon' => 1, 'IDholon_parent' => 2, 'IDholon_template' => null, 'active' => 1, 'visible' => 0, 'templatename' => '', 'link' => 0),
    array('id' => 10, 'name' => 'Role template', 'nomcomplet' => '', 'color' => '#abcdef', 'IDtypeholon' => 1, 'IDholon_parent' => 1, 'IDholon_template' => null, 'active' => 1, 'visible' => 0, 'templatename' => 'role-template', 'link' => 1),
    array('id' => 11, 'name' => 'Visible template', 'nomcomplet' => '', 'color' => '#fedcba', 'IDtypeholon' => 1, 'IDholon_parent' => 1, 'IDholon_template' => null, 'active' => 1, 'visible' => 1, 'templatename' => 'visible-template', 'link' => 0),
);

$propertyRowsByHolonId = array(
    3 => array(
        array(
            'IDproperty' => 20,
            'name' => 'Purpose',
            'shortname' => 'purpose',
            'effective_position' => 1,
            'value' => '',
            'value_parents' => 'Inherited purpose',
            'IDpropertyformat' => 1,
            'propertyformat_name' => 'Text',
            'listitemtype' => '',
            'listholontypeids' => '',
            'mandatory' => 1,
            'locked' => 0,
        ),
    ),
);

$memberRows = array(
    array('IDholon' => 3, 'IDuser' => 5, 'active' => 1, 'parameters' => '{}'),
    array('IDholon' => 6, 'IDuser' => 7, 'active' => 1, 'parameters' => '{"isAdmin":true}'),
);

$representation = \dbObject\Holon::buildBulkStructureRepresentationFromRows(
    $holonRows,
    1,
    $propertyRowsByHolonId,
    $memberRows,
    array(1, 2),
    array('includeMemberUserIds' => true)
);

assertBulkStructure($representation['ID'] === '1', 'The requested root must be returned.');
assertBulkStructure($representation['userIds'] === array(1, 2), 'Organization members must be attached to the root.');
assertBulkStructure(count($representation['children']) === 2, 'Visible root children must be returned.');

$circle = $representation['children'][0];
assertBulkStructure($circle['ID'] === '2', 'The main circle must be the first child.');
assertBulkStructure(($circle['fullName'] ?? '') === 'Main circle', 'The full name must be preserved.');
assertBulkStructure(($circle['userIds'] ?? array()) === array(7), 'Link-role admins must be calculated on the englobing circle.');
assertBulkStructure(count($circle['children']) === 3, 'Hidden children and template definitions must not enter the tree.');

$childrenById = array();
foreach ($circle['children'] as $child) {
    $childrenById[(int)$child['ID']] = $child;
}

$roleA = $childrenById[4];
assertBulkStructure(($roleA['mycolor'] ?? '') === '#fedcba', 'Template colors must be inherited.');
assertBulkStructure(($roleA['visibleTemplateAncestorId'] ?? '') === '11', 'Visible template ancestry must be preserved.');

$roleB = $childrenById[3];
assertBulkStructure(($roleB['mycolor'] ?? '') === '#abcdef', 'Hidden template colors must be inherited.');
assertBulkStructure(($roleB['userIds'] ?? array()) === array(5), 'Direct members must be attached to their role.');
assertBulkStructure(($roleB['data']['d20']['effectiveValue'] ?? '') === 'Inherited purpose', 'Inherited property values must be preserved.');

echo "holon_bulk_structure_representation_test: OK\n";
