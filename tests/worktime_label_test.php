<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/worktime.class.php';

use dbObject\WorkTime;

function assertWorkTimeLabel(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$rules = WorkTime::rules();
$stringFields = array();
foreach ($rules as $rule) {
    if (($rule[1] ?? '') === 'string') {
        $stringFields = array_merge($stringFields, $rule[0] ?? array());
    }
}
assertWorkTimeLabel(in_array('label', $stringFields, true), 'The work time label must be declared as a dbObject string field.');
assertWorkTimeLabel((WorkTime::attributeLength()['label'] ?? 0) === 1000, 'The work time label must have a dedicated maximum length.');

$normalized = WorkTime::normalizeLabel("  Analyse\r\ndu projet  ");
assertWorkTimeLabel(!empty($normalized['status']), 'A regular work time label must be accepted.');
assertWorkTimeLabel(($normalized['value'] ?? null) === "Analyse\ndu projet", 'The work time label must preserve line breaks and trim outer whitespace.');

$empty = WorkTime::normalizeLabel(" \n ");
assertWorkTimeLabel(!empty($empty['status']) && array_key_exists('value', $empty) && $empty['value'] === null, 'An empty work time label must be stored as null.');

$tooLong = WorkTime::normalizeLabel(str_repeat('a', 1001));
assertWorkTimeLabel(empty($tooLong['status']), 'An overlong work time label must be rejected.');

$root = dirname(__DIR__);
$trackApi = (string)file_get_contents($root . '/timer/api/track.php');
$timerIndex = (string)file_get_contents($root . '/timer/index.php');
$timerScript = (string)file_get_contents($root . '/timer/assets/timer.js');
$migration = (string)file_get_contents($root . '/sql/2026-09-08-01-work-time-label.sql');
$seed = (string)file_get_contents($root . '/docker/db/init/00-base.seed.sql');

assertWorkTimeLabel(
    str_contains($trackApi, "if (\$action === 'label')")
        && str_contains($trackApi, 'updateOpenLabelForUser')
        && str_contains($trackApi, "startOrSwitch(\$userId, \$organizationId, \$holonId, \$projectId, \$label['value'])"),
    'The timer API must save changed labels and include the current label when starting or switching.'
);
assertWorkTimeLabel(
    str_contains($timerIndex, 'data-timer-work-label')
        && str_contains($timerIndex, 'generic-form-control--single-line')
        && str_contains($timerScript, "postAction('label'")
        && str_contains($timerScript, 'scheduleWorkLabelSave')
        && str_contains($timerScript, 'label: state.workLabel'),
    'The Timer UI must keep the label field and send it only through the dedicated change flow.'
);
assertWorkTimeLabel(
    str_contains($migration, 'ADD COLUMN IF NOT EXISTS `label` varchar(1000)')
        && str_contains($seed, '`label` varchar(1000) DEFAULT NULL'),
    'The work time label must be stored in the migration and Docker seed schema.'
);

echo "worktime_label_test: OK\n";
