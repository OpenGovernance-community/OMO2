<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Opt-in: the fixed destination must be an isolated clone containing demo 828.
if (getenv('OMO_TRANSFER_ROUNDTRIP') !== '1') {
    echo "organization_archive_roundtrip_test: SKIP (isolated clone + OMO_TRANSFER_ROUNDTRIP=1 required)\n";
    exit;
}
// This test writes into the shared public upload directory, like the HTTP importer.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    throw new RuntimeException('Run this test as the web server user (docker exec --user www-data), never as root.');
}
chdir(dirname(__DIR__));
$_SERVER['DOCUMENT_ROOT'] = getcwd();
require 'config.php';
// This test imports complete demo organizations, so it requires an isolated DB clone.
$GLOBALS['dbServer'] = 'omo-transfer-test-db';
$GLOBALS['dbUser'] = 'root';
$GLOBALS['dbPassword'] = 'omo-transfer-fixture';
require 'shared_functions.php';

use dbObject\{Organization, OrganizationArchive, OrganizationExport, OrganizationTransferDates};

function transferCheck(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function transferCalendarValues($value) {
    if (is_array($value)) { return array_map('transferCalendarValues', $value); }
    // SQL DATETIME stores local clock time; its exported offset follows the date's DST.
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/D', $value) ? substr($value, 0, 19) : $value;
}
function transferEqual($expected, $actual, string $message): void {
    if (transferCalendarValues($expected) != transferCalendarValues($actual)) { throw new RuntimeException($message."\nExpected: ".json_encode($expected, JSON_UNESCAPED_UNICODE)."\nActual: ".json_encode($actual, JSON_UNESCAPED_UNICODE)); }
}
function transferNodes(array $nodes, array &$out): void {
    foreach ($nodes as $node) { $out[(int)$node['id']] = $node; transferNodes($node['children'] ?? [], $out); }
}
function transferDate($date) { return $date ? substr((string)$date, 0, 10) : null; }
function transferSorted(array $values): array { sort($values); return $values; }

$_SESSION['currentUser'] = 2135;
$_SESSION['currentOrganization'] = 828;
commonSetCurrentUserAdminMode(true, 828);
$source = new Organization();
transferCheck((bool)$source->load(828), 'Demo source unavailable in isolated clone');
$selected = array_fill_keys(OrganizationExport::MODULES, true);
$sourcePayload = OrganizationExport::build($source, $selected);
// A holon image fixture proves that this channel is preserved too, with deduplication.
$sourcePayload['holons'][0]['children'][0]['icon'] = $sourcePayload['organization']['logo'];
$zipPath = 'tmp/transfer-roundtrip-test.zip';
$archive = OrganizationArchive::create($sourcePayload, $zipPath);
transferEqual(23, $archive['images'], 'Demo images');
transferEqual([], $archive['warnings'], 'Image export warnings');
transferEqual($sourcePayload['exportedAt'], $archive['payload']['exportedAt'], 'Export date stays unchanged');
transferEqual($sourcePayload['modules']['projects'], $archive['payload']['modules']['projects'], 'Export project dates stay unchanged');
$preview = OrganizationArchive::read($zipPath);
transferEqual([], $preview['files'], 'Preview never writes files');

$reports = [];
foreach ([false, true] as $roleplay) {
    $read = OrganizationArchive::read($zipPath, true);
    try {
        $payload = $read['payload'];
        if ($roleplay) { $payload['exportedAt'] = (new DateTimeImmutable('today'))->modify('-30 days')->format('c'); }
        $expected = $roleplay ? OrganizationTransferDates::shift($payload) : $payload;
        $result = Organization::importOmo1ExportAsNewOrganization($payload, $selected, 2135, $sourcePayload['organization']['name'], [], ['sendMemberInvitationEmails' => false, 'roleplay' => $roleplay]);
        transferCheck(!empty($result['status']), 'Import failed: '.json_encode($result, JSON_UNESCAPED_UNICODE));
        $target = $result['organization'];
        commonSetCurrentUserAdminMode(true, (int)$target->getId());
        $actual = OrganizationExport::build($target, $selected);
        file_put_contents('tmp/transfer-roundtrip-'.($roleplay ? 'roleplay' : 'normal').'.json', json_encode(['expected' => $expected, 'actual' => $actual, 'result' => ['stats' => $result['stats'], 'warnings' => $result['warnings']]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $holonMap = $result['holonIdMap'];
        $sourceNodes = $targetNodes = [];
        transferNodes($expected['holons'], $sourceNodes);
        transferNodes($actual['holons'], $targetNodes);
        transferEqual(count($sourceNodes), count($targetNodes), 'Holon count');
        $sourceDefinitions = array_column($expected['propertyDefinitions'], null, 'id');
        $targetDefinitions = array_column($actual['propertyDefinitions'], null, 'id');
        foreach ($sourceNodes as $id => $node) {
            $copy = $targetNodes[$holonMap[$id]];
            foreach (['name', 'fullName', 'typeId', 'visible', 'templateName'] as $field) {
                // The root keeps the chosen organization name by design.
                if (($node['role'] ?? '') === 'organization_root' && $field === 'name') { continue; }
                transferEqual($node[$field] ?? null, $copy[$field] ?? null, 'Holon '.$id.' '.$field);
            }
            $semanticProperties = function ($properties, $definitions) {
                $values = [];
                foreach ($properties as $p) {
                    $key = $definitions[$p['propertyId']]['shortname'] ?? '';
                    if (in_array($key, ['purpose', 'accountabilities', 'raison_d_etre', 'attendus'], true)) { $values[$definitions[$p['propertyId']]['name']] = $p['value'] ?? null; }
                }
                ksort($values);
                return $values;
            };
            transferEqual($semanticProperties($node['properties'] ?? [], $sourceDefinitions), $semanticProperties($copy['properties'] ?? [], $targetDefinitions), 'Mission and outputs '.$id);
            $sourceTemplate = (int)($node['templateId'] ?? 0);
            transferEqual($sourceTemplate ? $holonMap[$sourceTemplate] : 0, (int)($copy['templateId'] ?? 0), 'Template link '.$id);
            if (!empty($node['icon'])) { transferEqual(hash_file('sha256', getcwd().$node['icon']), hash_file('sha256', getcwd().$copy['icon']), 'Holon image bytes'); }
        }
        $sourceProjects = array_merge($expected['modules']['projects']['records'], $expected['modules']['tasks']['records']);
        $targetProjects = array_merge($actual['modules']['projects']['records'], $actual['modules']['tasks']['records']);
        transferEqual(181, count($targetProjects), 'Project count');
        $byTitle = array_column($targetProjects, null, 'title');
        $projectMap = [];
        foreach ($sourceProjects as $project) { $projectMap[$project['sourceId']] = $byTitle[$project['title']]['sourceId'] ?? 0; }
        foreach ($sourceProjects as $project) {
            $copy = $byTitle[$project['title']] ?? [];
            foreach (['title','description','status','priority','importance','projectSize','active','blockedReason','blockedAutoReactivate','blockedReactivateStatus','sourceUserId','teamMembers','followerSourceUserIds'] as $field) { transferEqual($project[$field] ?? null, $copy[$field] ?? null, 'Project '.$project['sourceId'].' '.$field); }
            foreach (['plannedStartAt','plannedEndAt','blockedUntil','closedAt','archivedAt','createdAt'] as $field) { transferEqual(transferDate($project[$field] ?? null), transferDate($copy[$field] ?? null), 'Project date '.$project['sourceId'].' '.$field); }
            transferEqual($holonMap[$project['sourceHolonId']] ?? 0, $copy['sourceHolonId'] ?? 0, 'Project holon');
            transferEqual($projectMap[$project['sourceParentProjectId']] ?? 0, $copy['sourceParentProjectId'] ?? 0, 'Project parent');
        }
        $copyMembers = array_column($actual['modules']['members']['records'], null, 'email');
        foreach (['logo', 'banner'] as $field) { if (!empty($expected['organization'][$field])) { transferEqual(hash_file('sha256', getcwd().$expected['organization'][$field]), hash_file('sha256', getcwd().$actual['organization'][$field]), 'Organization '.$field.' image bytes'); } }
        $skillCount = 0;
        foreach ($expected['modules']['members']['records'] as $member) {
            $copy = $copyMembers[$member['email']] ?? [];
            foreach (['firstname','lastname','username','presentation','phone'] as $field) { transferEqual($member[$field] ?? null, $copy[$field] ?? null, 'Member '.$member['email'].' '.$field); }
            transferEqual($member['skills'] ?? [], $copy['skills'] ?? [], 'Skills '.$member['email']);
            $skillCount += count($copy['skills'] ?? []);
            if (!empty($member['image'])) { transferEqual(hash_file('sha256', getcwd().$member['image']), hash_file('sha256', getcwd().$copy['image']), 'Profile image bytes'); }
            transferEqual(count($member['roleAssignments']), count($copy['roleAssignments']), 'Role assignments');
        }
        transferEqual(69, $skillCount, 'Skill count');
        $copyRules = array_column($actual['rules'], null, 'title');
        $sourceAuthorities = array_column($expected['authorities'], null, 'id');
        $targetAuthorities = array_column($actual['authorities'], null, 'id');
        transferEqual(count($sourceAuthorities), count($targetAuthorities), 'Authority count');
        transferEqual(54, count($copyRules), 'Rule count');
        foreach ($expected['rules'] as $rule) {
            $copy = $copyRules[$rule['title']];
            foreach (['intention','description','scope','reviewDate','expirationDate'] as $field) { transferEqual($rule[$field] ?? null, $copy[$field] ?? null, 'Rule '.$rule['title'].' '.$field); }
            $sourceAuthority = $sourceAuthorities[$rule['authorityId']];
            $targetAuthority = $targetAuthorities[$copy['authorityId']];
            transferEqual($sourceAuthority['label'], $targetAuthority['label'], 'Rule authority label');
            transferEqual($holonMap[$sourceAuthority['holonId']], $targetAuthority['holonId'], 'Rule authority holon');
        }
        $copyTasks = array_column($actual['modules']['checklists']['records'], null, 'sourceId');
        $tasksByTitle = [];
        foreach ($copyTasks as $task) { $tasksByTitle[$task['items'][0]['title']] = $task; }
        transferEqual(64, count($copyTasks), 'Recurring tasks');
        $checkCount = 0;
        foreach ($expected['modules']['checklists']['records'] as $task) {
            $copy = $tasksByTitle[$task['items'][0]['title']];
            transferEqual($task['active'], $copy['active'], 'Task archive state');
            foreach (['title','description','recurrence','checks'] as $field) { transferEqual($task['items'][0][$field], $copy['items'][0][$field], 'Task '.$task['sourceId'].' '.$field); }
            $checkCount += count($copy['items'][0]['checks']);
        }
        $sourceCheckCount = array_sum(array_map(fn($task) => count($task['items'][0]['checks']), $expected['modules']['checklists']['records']));
        transferEqual($sourceCheckCount, $checkCount, 'Recurring task checks');
        $copyIndicators = array_column($actual['modules']['indicators']['records'], null, 'name');
        $indicatorMap = [];
        foreach ($expected['modules']['indicators']['records'] as $indicator) { $indicatorMap[$indicator['sourceId']] = $copyIndicators[$indicator['name']]['sourceId'] ?? 0; }
        $valueCount = 0;
        foreach ($expected['modules']['indicators']['records'] as $indicator) {
            $copy = $copyIndicators[$indicator['name']];
            foreach (['description','sourceUrl','referenceType','referenceScale','measurementFrequency','measurementSchedule','chartMinValue','showCumulative','active','sourceResponsibleUserId','referencePoints','values'] as $field) { transferEqual($indicator[$field] ?? null, $copy[$field] ?? null, 'Indicator '.$indicator['name'].' '.$field); }
            $valueCount += count($copy['values']);
        }
        transferEqual(390, $valueCount, 'Indicator measurements');
        $imports = function ($rows, $hMap, $iMap) {
            $out = [];
            foreach ($rows as $r) { $out[] = [$hMap[$r['sourceHolonId']] ?? $r['sourceHolonId'], $iMap[$r['sourceIndicatorId']] ?? $r['sourceIndicatorId'], $r['active']]; }
            return transferSorted($out);
        };
        transferEqual($imports($expected['modules']['indicators']['imports'], $holonMap, $indicatorMap), $imports($actual['modules']['indicators']['imports'], [], []), 'Indicator imports');
        $copyGroups = array_column($actual['modules']['indicators']['groups'], null, 'name');
        foreach ($expected['modules']['indicators']['groups'] as $group) {
            $copy = $copyGroups[$group['name']] ?? [];
            foreach (['displayMode','referenceType','chartMinValue','hideSameHolonSources','active','referencePoints'] as $field) { transferEqual($group[$field], $copy[$field] ?? null, 'Group '.$field); }
            $items = array_map(fn($item) => ['sourceIndicatorId' => $indicatorMap[$item['sourceIndicatorId']], 'position' => $item['position']], $group['items']);
            transferEqual($items, $copy['items'], 'Group indicator links');
        }
        $reports[] = ['mode' => $roleplay ? 'roleplay+30days' : 'normal', 'projects' => count($targetProjects), 'holons' => count($targetNodes), 'skills' => $skillCount, 'rules' => count($copyRules), 'checks' => $checkCount, 'measurements' => $valueCount, 'images' => count($read['files']), 'groups' => count($copyGroups), 'warnings' => $result['warnings']];
    } finally { OrganizationArchive::cleanup($read['files']); }
}
file_put_contents('tmp/transfer-roundtrip-report.json', json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo json_encode($reports, JSON_UNESCAPED_UNICODE)."\norganization_archive_roundtrip_test: OK\n";
