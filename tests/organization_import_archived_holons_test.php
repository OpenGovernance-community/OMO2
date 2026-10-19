<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, PdoDbhCompat, Organization, Holon, Document, ArrayDocument, User};

function archivedImportCheck(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

// The real importer commits twice; both commits stay inside this rollback-only fixture.
class ArchivedImportRollbackConnection
{
    public function __construct(private PDO $pdo) {}
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function __call(string $method, array $arguments) { return $this->pdo->$method(...$arguments); }
}

$pdo = DbObject::getPdo();
$originalDbh = DbObject::$_dbh;
$pdo->beginTransaction();
DbObject::$_dbh = new PdoDbhCompat(new ArchivedImportRollbackConnection($pdo));
try {
    $actor = new User();
    $actor->set('email', 'archived-import-' . bin2hex(random_bytes(6)) . '@example.invalid');
    $actor->set('active', true);
    archivedImportCheck(!empty($actor->save()['status']), 'Actor fixture');
    $_SESSION['currentUser'] = (int)$actor->getId();
    $payload = [
        'format' => 'openmyorganization-structure-export', 'version' => 4,
        'exportedAt' => '2026-10-19T08:12:04+00:00', 'source' => ['system' => 'omo1'],
        'organization' => ['name' => 'Archived import fixture'],
        'scope' => ['organizationRootHolonId' => 1],
        'holons' => [['id' => 1, 'typeId' => 4, 'name' => 'Root', 'role' => 'organization_root', 'children' => [
            ['id' => 2, 'typeId' => 2, 'name' => 'Old circle', 'sourceRoleId' => 13490, 'active' => false, 'archived' => true, 'children' => [
                ['id' => 3, 'typeId' => 1, 'name' => 'Old role', 'sourceRoleId' => 13491, 'active' => true],
            ]],
        ]]],
        'modules' => [
            'structure' => ['selected' => true, 'records' => []],
            'rules' => ['selected' => true, 'records' => [
                ['sourceId' => 1143, 'title' => 'Domain rule', 'sourceScopeRoleId' => 18083, 'sourceScopeId' => 2726],
            ], 'domains' => [
                ['sourceId' => 2726, 'label' => 'Domain', 'sourceRoleId' => 18083, 'sourceHolonId' => 2],
            ]],
            'documents' => ['selected' => true, 'records' => [
                ['sourceId' => 11, 'title' => 'Archived project document', 'sourceHolonId' => 3, 'sourceProjectId' => 999, 'legacyVisibility' => 3],
                ['sourceId' => 12, 'title' => 'Missing role A', 'sourceRoleId' => 999],
                ['sourceId' => 13, 'title' => 'Missing role B', 'sourceHolonId' => 998],
            ]],
            'calendar' => ['selected' => true, 'records' => [
                ['sourceId' => 21, 'title' => 'Old meeting', 'sourceHolonId' => 2, 'scheduledAt' => '2023-01-29 10:00:00'],
                ['sourceId' => 22, 'title' => 'Missing meeting role', 'sourceHolonId' => 999, 'scheduledAt' => '2023-01-30 10:00:00'],
            ]],
            'pv' => ['selected' => true, 'records' => [
                // Older exports can have the role reference without a compact holon ID.
                ['sourceId' => 21, 'sourceMeetingId' => 21, 'sourceRoleId' => 13490, 'sourceHolonId' => null, 'history' => []],
                ['sourceId' => 22, 'sourceMeetingId' => 22, 'sourceRoleId' => 999, 'history' => []],
            ]],
        ],
    ];
    $before = $payload;
    $selected = array_fill_keys(['structure', 'rules', 'documents', 'calendar', 'pv'], true);
    $result = Organization::importOmo1ExportAsNewOrganization($payload, $selected, (int)$actor->getId(), '', [], ['sendMemberInvitationEmails' => false]);
    archivedImportCheck(!empty($result['status']), 'Public import failed: ' . json_encode($result));
    archivedImportCheck($payload === $before, 'Skipped contents must remain intact in the source archive');
    archivedImportCheck($result['stats']['documents'] === 1 && $result['stats']['pv'] === 1, 'Only documents with an exported space must import');
    archivedImportCheck($result['stats']['archivedHolons'] === 2, 'The deleted circle and its entire branch must be archived');
    archivedImportCheck($result['stats']['rules'] === 1, 'Rule space references must also resolve through the exported domain inventory');
    archivedImportCheck(count($result['warnings']) === 1 && str_starts_with($result['warnings'][0], '3 documents n ont pas pu etre importes'), 'Missing document and PV spaces must produce one combined warning');
    $circle = new Holon(); $role = new Holon();
    $circle->load($result['holonIdMap'][2], true); $role->load($result['holonIdMap'][3], true);
    foreach ([$circle, $role] as $holon) {
        archivedImportCheck(!$holon->get('active') && $holon->get('archived_at') instanceof DateTimeInterface, 'Archived nodes must stay inactive');
        archivedImportCheck((int)$holon->getParametersArray()['holon_archive']['root_id'] === (int)$circle->getId(), 'Archive metadata must identify its root');
    }
    archivedImportCheck($circle->getParametersArray()['holon_archive']['batch'] === $role->getParametersArray()['holon_archive']['batch'], 'The branch must restore as one archive batch');
    $docs = new ArrayDocument();
    $docs->load(['where' => [['field' => 'IDorganization', 'value' => $result['organization']->getId()]]]);
    archivedImportCheck(count($docs) === 2, 'Missing contents must not become private orphan documents');
    foreach ($docs as $doc) {
        $read = $doc->getPrimaryVisibilityRuleRow(); $edit = $doc->getPrimaryEditVisibilityRuleRow();
        archivedImportCheck($read['visibility_type'] === 'circle' && (int)$read['IDholon'] === (int)$circle->getId(), 'Archived read scopes must be preserved');
        $expectedEdit = $doc->get('documenttype') === Document::TYPE_PV ? $circle : $role;
        archivedImportCheck((int)$edit['IDholon'] === (int)$expectedEdit->getId(), 'Archived edit scopes must be preserved');
    }
    $_SESSION['currentOrganization'] = (int)$result['organization']->getId();
    archivedImportCheck(commonSetCurrentUserAdminMode(true, $_SESSION['currentOrganization']), 'Admin mode fixture');
    archivedImportCheck($circle->canViewArchive(), 'Imported archive must be visible through the standard archive UI');
    $restored = $circle->changeArchiveState($_SESSION['currentOrganization'], true);
    archivedImportCheck(!empty($restored['status']), 'Imported archive must support normal restoration: ' . json_encode($restored));
    foreach ([$circle, $role] as $holon) {
        $holon->load($holon->getId(), true);
        archivedImportCheck((bool)$holon->get('active') && !$holon->get('archived_at'), 'Restoration must reactivate the whole branch');
    }
    echo "organization_import_archived_holons_test: OK\n";
} finally {
    DbObject::$_dbh = $originalDbh;
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
