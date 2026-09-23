<?php
declare(strict_types=1);

function assertOrganizationExportRoundtripContract(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$exportSource = (string)file_get_contents($root . '/class/dbobject/organizationexport.class.php');
$organizationSource = (string)file_get_contents($root . '/class/dbobject/organization.class.php');
$popupSource = (string)file_get_contents($root . '/omo/api/organizations/export_popup.php');

assertOrganizationExportRoundtripContract(
    str_contains($exportSource, "'followerSourceUserIds' => ProjectFollower::getActiveUserIdsForProject")
        && str_contains($organizationSource, 'function omo1ImportProjectFollowers'),
    'Project followers must be present in the export and recreated during import.'
);
assertOrganizationExportRoundtripContract(
    str_contains($exportSource, "'teamMembers' => \$teamMembers")
        && str_contains($organizationSource, 'function omo1ImportProjectUsers'),
    'Project teams must be present in the export and recreated during import.'
);
assertOrganizationExportRoundtripContract(
    str_contains($exportSource, "'checks' => \$checkRecords")
        && str_contains($organizationSource, 'new \\dbObject\\ControlTaskCheck()'),
    'Recurring task checks must be present in the export and recreated during import.'
);
assertOrganizationExportRoundtripContract(
    str_contains($exportSource, "'runs' => \$runRecords")
        && str_contains($organizationSource, 'function omo1ImportProcessRuns'),
    'Process executions must be present in the export and recreated during import.'
);
assertOrganizationExportRoundtripContract(
    str_contains($organizationSource, "isset(\$historyRecord['pointtype'])")
        && str_contains($organizationSource, '\\dbObject\\DocumentPvPoint::normalizePointType'),
    'Native PV point types must be restored by the importer.'
);
assertOrganizationExportRoundtripContract(
    str_contains($exportSource, "'documentType' => \$documentType")
        && str_contains($exportSource, "'sourceParentDocumentId' =>")
        && str_contains($popupSource, 'organization_export.documents_notice'),
    'Document eligibility and the intentional omission notice must remain explicit.'
);

echo "organization_export_roundtrip_contract_test: OK\n";
