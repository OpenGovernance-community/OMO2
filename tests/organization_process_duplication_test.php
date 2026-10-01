<?php
declare(strict_types=1);

function assertOrganizationProcessDuplication(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$exportSource = (string)file_get_contents($root . '/class/dbobject/organizationexport.class.php');
$organizationSource = (string)file_get_contents($root . '/class/dbobject/organization.class.php');

assertOrganizationProcessDuplication(
    str_contains($exportSource, "'recordType' => 'process'")
        && str_contains($exportSource, "'recordType' => 'recurring_activity'")
        && str_contains($exportSource, "'sourceRootProjectId'")
        && str_contains($exportSource, "'sourceParentProjectId'"),
    'Process and recurring activity exports must retain distinct record types.'
);
assertOrganizationProcessDuplication(
    str_contains($organizationSource, 'function omo1ImportProcesses')
        && str_contains($organizationSource, 'new \\dbObject\\ChecklistItemRecurrence()')
        && str_contains($organizationSource, 'new \\dbObject\\ChecklistItemDependency()'),
    'Process imports must recreate recurring activities and their dependencies.'
);
assertOrganizationProcessDuplication(
    str_contains($organizationSource, 'self::omo1ImportProcesses($organization, $processRecords, $userIdMap, $holonIdMap, $stats, $processImportMaps);')
        && str_contains($organizationSource, 'self::omo1ImportActivities($organization, $legacyActivityRecords, $userIdMap, $holonIdMap, $stats);'),
    'Process and recurring activity records must use separate import paths.'
);

echo "organization_process_duplication_test: OK\n";
