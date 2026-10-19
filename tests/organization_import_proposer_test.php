<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Organization, Project, ProjectFollower, User, UserOrganization};

function importProposerCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function importProposerFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    importProposerCheck(!empty($object->save()['status']), 'Fixture failed: ' . $class);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $organization = importProposerFixture(Organization::class, ['name' => 'Import proposer ' . $nonce]);
    $otherOrganization = importProposerFixture(Organization::class, ['name' => 'Other proposer ' . $nonce]);
    $actor = importProposerFixture(User::class, ['firstname' => 'Importer', 'email' => 'importer-' . $nonce . '@example.invalid', 'active' => true]);
    $outsider = importProposerFixture(User::class, ['firstname' => 'Outsider', 'email' => 'outsider-' . $nonce . '@example.invalid', 'active' => true]);
    importProposerFixture(UserOrganization::class, ['IDorganization' => $otherOrganization->getId(), 'IDuser' => $outsider->getId(), 'active' => true]);
    $_SESSION['currentUser'] = (int)$actor->getId();
    $_SESSION['currentOrganization'] = (int)$organization->getId();
    $members = [
        ['sourceId' => 10, 'email' => $actor->get('email'), 'active' => true],
        ['sourceId' => 20, 'email' => 'pending-' . $nonce . '@example.invalid', 'active' => true],
        ['sourceId' => 30, 'email' => 'inactive-' . $nonce . '@example.invalid', 'active' => false],
    ];
    $userMap = $pending = $invitations = $warnings = [];
    $stats = ['members' => 0, 'projects' => 0, 'tasks' => 0, 'projectFollowers' => 0];
    (new ReflectionMethod(Organization::class, 'omo1ImportMembers'))->invokeArgs(null, [
        $organization, $members, (int)$actor->getId(), &$userMap, &$pending, &$invitations, &$stats, &$warnings, false,
    ]);
    importProposerCheck(count($userMap) === 3 && !$invitations, 'All members must map without sending invitations.');
    $cases = [
        ['sourceProposerUserId' => 20],
        ['sourceProposerUserId' => 30],
        ['sourceProposerUserId' => null, 'proposalStatus' => Project::PROPOSAL_PENDING],
        ['proposalStatus' => Project::PROPOSAL_PENDING],
        ['sourceProposerUserId' => 999, 'proposalStatus' => Project::PROPOSAL_PENDING],
        ['sourceProposerUserId' => 10],
        ['sourceProposerUserId' => 0],
    ];
    $records = [];
    foreach ($cases as $index => $fields) {
        $records[] = $fields + ['sourceId' => 100 + $index, 'title' => 'Imported case ' . $index, 'status' => Project::STATUS_READY, 'active' => true];
    }
    $projectMap = $taskMap = [];
    (new ReflectionMethod(Organization::class, 'omo1ImportProjects'))->invokeArgs(null, [
        $organization, $records, (int)$actor->getId(), $userMap, [], [], [], &$projectMap, &$stats,
    ]);
    (new ReflectionMethod(Organization::class, 'omo1ImportTasks'))->invokeArgs(null, [
        $organization, $records, (int)$actor->getId(), $userMap, [], [], &$taskMap, &$stats, &$warnings,
    ]);
    importProposerCheck($stats['projects'] === count($cases) && $stats['tasks'] === count($cases), 'All project and task cases must import.');
    foreach ([$projectMap, $taskMap] as $map) {
        foreach ($records as $record) {
            $project = new Project();
            importProposerCheck((bool)$project->load($map[$record['sourceId']], true), 'Imported record must exist.');
            $expected = $userMap[(int)($record['sourceProposerUserId'] ?? 0)] ?? null;
            importProposerCheck(($project->get('IDuser_proposed') ?: null) === $expected, 'Historical proposer must be mapped or remain null.');
            importProposerCheck(ProjectFollower::getActiveUserIdsForProject((int)$project->getId()) === [], 'Import must not invent followers.');
            $project->set('title', 'Edited imported record');
            importProposerCheck(!empty($project->save()['status']), 'Imported records must remain editable with unchanged historical attribution.');
        }
    }
    foreach ([20, 30] as $sourceId) {
        $membership = new UserOrganization();
        importProposerCheck((bool)$membership->load([['IDorganization', (int)$organization->getId()], ['IDuser', $userMap[$sourceId]]]), 'Imported membership must exist.');
        importProposerCheck(!(bool)$membership->get('active'), 'Import must not grant active membership.');
    }
    // Explicit source followers still use the regular import path.
    $records[0]['followerSourceUserIds'] = [20];
    (new ReflectionMethod(Organization::class, 'omo1ImportProjectFollowers'))->invokeArgs(null, [
        $records, $projectMap, $userMap, &$stats, &$warnings,
    ]);
    importProposerCheck(ProjectFollower::getActiveUserIdsForProject($projectMap[100]) === [$userMap[20]], 'Explicit historical followers must be preserved.');

    $defaults = ['IDorganization' => (int)$organization->getId(), 'title' => 'Validation', 'active' => true];
    $invalid = new Project();
    foreach ($defaults + ['IDuser_proposed' => (int)$outsider->getId()] as $field => $value) $invalid->set($field, $value);
    importProposerCheck(empty($invalid->saveForImport()['status']) && !(int)$invalid->getId(), 'Import must reject proposers without membership in the target organization.');
    $invalid->set('IDuser_proposed', $userMap[20]);
    importProposerCheck(empty($invalid->save()['status']), 'A failed import save must restore ordinary active-member validation.');
    importProposerCheck(!empty($invalid->saveForImport()['status']), 'Import must allow an existing inactive membership.');
    $invalid->set('IDuser_proposed', $userMap[30]);
    importProposerCheck(empty($invalid->save()['status']), 'A successful import save must restore ordinary active-member validation.');
    $invalid->set('IDuser_proposed', null);
    importProposerCheck(!empty($invalid->save()['status']), 'An ordinary project may also have a null proposer.');
    importProposerCheck(!$warnings, 'Valid proposer cases must not produce warnings.');
    echo "organization_import_proposer_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
