<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/project.class.php';
require_once dirname(__DIR__) . '/class/dbobject/projectimportancecalculator.class.php';

use dbObject\ProjectImportanceCalculator;

function assertClose($actual, $expected, $message): void
{
    if (abs((float)$actual - (float)$expected) > 0.000001) {
        throw new RuntimeException($message . ' (expected ' . $expected . ', got ' . $actual . ')');
    }
}

$config = ProjectImportanceCalculator::getDefaultConfig();
$rootHigh = ProjectImportanceCalculator::calculateScore(5, null, true, 0, $config);
$highUnderHigh = ProjectImportanceCalculator::calculateScore(5, $rootHigh, true, 5, $config);
assertClose($rootHigh, 1.0, 'A declared importance of 5 must normalize to 1.');
assertClose($highUnderHigh, 1.0, 'A high child under a high parent must stay high.');

$lowParent = ProjectImportanceCalculator::calculateScore(1, null, true, 0, $config);
$highUnderLow = ProjectImportanceCalculator::calculateScore(5, $lowParent, true, 0, $config);
assertClose($highUnderLow, 0.0, 'A branch cannot recover from a zero normalized parent score.');

$mediumChild = ProjectImportanceCalculator::calculateScore(2, $rootHigh, true, 0, $config);
$highGrandchild = ProjectImportanceCalculator::calculateScore(5, $mediumChild, true, 0, $config);
$highGreatGrandchild = ProjectImportanceCalculator::calculateScore(5, $highGrandchild, true, 0, $config);
if ($highGrandchild > $mediumChild || $highGreatGrandchild > $highGrandchild) {
    throw new RuntimeException('A descendant must not exceed its branch ceiling.');
}

$inherited = ProjectImportanceCalculator::calculateScore(0, $mediumChild, true, 0, $config);
assertClose($inherited, $mediumChild, 'An undefined local importance must inherit its parent score.');
assertClose(ProjectImportanceCalculator::calculateScore(0, null, true, 0, $config), 0.0, 'An undefined root must have no strategic importance.');
assertClose(ProjectImportanceCalculator::toBusinessScale(0), 0.0, 'A zero strategic score must remain visible as zero.');
$firstDefinedInBranch = ProjectImportanceCalculator::calculateScore(5, 0.0, true, 0, $config, false);
assertClose($firstDefinedInBranch, 1.0, 'The first declared importance in a branch must establish its score.');
$inheritedAfterUndefinedAncestors = ProjectImportanceCalculator::calculateScore(0, $firstDefinedInBranch, true, 0, $config, true);
assertClose($inheritedAfterUndefinedAncestors, $firstDefinedInBranch, 'An undefined descendant must inherit the first declared ancestor score.');

$anchored = ProjectImportanceCalculator::calculateScore(5, null, true, 5, $config);
$unanchored = ProjectImportanceCalculator::calculateScore(5, null, false, 5, $config);
assertClose($anchored, 1.0, 'A root-anchored project must not receive a depth penalty.');
assertClose($unanchored, exp(-0.15 * 5), 'An unanchored project must receive the configured depth penalty.');
$highDescendant = ProjectImportanceCalculator::calculateScore(5, $unanchored, false, 6, $config, true);
assertClose($highDescendant, $unanchored, 'A descendant must not receive the root depth penalty a second time.');

echo "ProjectImportanceCalculator tests passed.\n";
