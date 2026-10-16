<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/omo/api/stats/shared.php';

use dbObject\{ArrayDocument, DbObject, Document, Organization, StatIndicator, User, UserOrganization};

function indicatorFolderCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function indicatorFolderSave($object): void
{
    $result = $object->save();
    indicatorFolderCheck(!empty($result['status']), 'Fixture save: ' . json_encode($result));
}

function indicatorFolderFixture(string $class, array $fields)
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    indicatorFolderSave($object);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = 'indicator-folders-' . bin2hex(random_bytes(5));
    $organization = indicatorFolderFixture(Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $organizationId = (int)$organization->getId();
    $owner = indicatorFolderFixture(User::class, ['email' => $nonce . '-owner@example.invalid', 'active' => 1]);
    $viewer = indicatorFolderFixture(User::class, ['email' => $nonce . '-viewer@example.invalid', 'active' => 1]);
    foreach ([$owner, $viewer] as $user) {
        indicatorFolderFixture(UserOrganization::class, [
            'IDorganization' => $organizationId, 'IDuser' => $user->getId(), 'active' => 1,
        ]);
    }
    $_SESSION = ['currentUser' => (int)$viewer->getId(), 'currentOrganization' => $organizationId];

    $makeDocument = static function (array $fields) use ($organizationId, $owner): Document {
        $document = indicatorFolderFixture(Document::class, $fields + [
            'title' => 'Indicator source', 'IDorganization' => $organizationId,
            'IDuser' => $owner->getId(), 'active' => 1,
        ]);
        indicatorFolderCheck(!empty($document->saveVisibilityRule('organization')['status']), 'Save document visibility');
        return $document;
    };
    $folder = $makeDocument(['title' => 'Folder', 'estDossier' => 1]);
    $nested = $makeDocument(['title' => 'Nested folder', 'estDossier' => 1, 'IDdocument_parent' => $folder->getId()]);
    $ethercalc = $makeDocument(['documenttype' => Document::TYPE_ETHERCALC, 'ethercalcroomid' => $nonce]);
    // Collabora spreadsheets are persisted as uploaded ODS files.
    $spreadsheet = $makeDocument([
        'documenttype' => Document::TYPE_UPLOADED_FILE, 'storedfilepath' => $nonce . '.ods',
        'storedfilename' => 'Source.ods', 'storedfilemime' => 'application/vnd.oasis.opendocument.spreadsheet',
    ]);
    $sources = [
        [$ethercalc, 'omoStatsLoadVisibleEthercalcDocument', StatIndicator::SOURCE_ETHERCALC_CELL],
        [$spreadsheet, 'omoStatsLoadVisibleSpreadsheetDocument', StatIndicator::SOURCE_SPREADSHEET_CELL],
    ];
    $assertSources = static function (bool $expected, string $label) use ($sources, $organizationId): void {
        $documents = new ArrayDocument();
        $documents->loadVisibleForOrganization($organizationId);
        $visibleIds = array_map(static fn(Document $document): int => (int)$document->getId(), $documents->getArrayCopy());
        foreach ($sources as [$document, $load]) {
            $id = (int)$document->getId();
            indicatorFolderCheck(in_array($id, $visibleIds, true) === $expected, $label . ': list ' . $load);
            $loaded = $load($id, $organizationId);
            indicatorFolderCheck(($loaded instanceof Document) === $expected, $label . ': access ' . $load);
            if ($expected) {
                indicatorFolderCheck((int)$loaded->getId() === $id, $label . ': preserve source identity');
            }
        }
    };

    $assertSources(true, 'Root documents');
    foreach ($sources as [$document, $load, $sourceType]) {
        $indicator = indicatorFolderFixture(StatIndicator::class, [
            'name' => $nonce, 'IDorganization' => $organizationId, 'IDdocument' => $document->getId(),
            'source_type' => $sourceType, 'active' => 1,
        ]);
        $document->set('IDdocument_parent', $nested->getId());
        indicatorFolderSave($document);
        $linked = $sourceType === StatIndicator::SOURCE_ETHERCALC_CELL
            ? $indicator->getEthercalcDocument() : $indicator->getSpreadsheetDocument();
        indicatorFolderCheck($linked instanceof Document && (int)$linked->getId() === (int)$document->getId(), 'Existing indicator retains moved source');
    }
    $assertSources(true, 'Moved into nested folders');

    indicatorFolderCheck(!empty($folder->saveVisibilityRule('self')['status']), 'Restrict ancestor');
    $assertSources(false, 'Private ancestor');
    indicatorFolderCheck(!empty($folder->saveVisibilityRule('organization')['status']), 'Restore ancestor');
    $folder->set('active', 0);
    indicatorFolderSave($folder);
    $assertSources(false, 'Archived ancestor');
    $folder->set('active', 1);
    indicatorFolderSave($folder);
    $assertSources(true, 'Restored ancestor');

    foreach ($sources as [$document, $load]) {
        indicatorFolderCheck(!empty($document->saveVisibilityRule('self')['status']), 'Restrict source');
    }
    $assertSources(false, 'Private sources');
    foreach ($sources as [$document, $load]) {
        indicatorFolderCheck(!empty($document->saveVisibilityRule('organization')['status']), 'Restore source');
        $document->set('active', 0);
        indicatorFolderSave($document);
    }
    $assertSources(false, 'Archived sources');
    foreach ($sources as [$document, $load]) {
        $document->set('active', 1);
        $document->set('IDdocument_parent', null);
        indicatorFolderSave($document);
    }
    $assertSources(true, 'Moved back to root');

    $otherOrganization = indicatorFolderFixture(Organization::class, ['name' => $nonce . '-other', 'shortname' => $nonce . '-other']);
    foreach ($sources as [$document, $load]) {
        indicatorFolderCheck($load($document->getId(), $otherOrganization->getId()) === null, 'Cross-organization access denied');
        indicatorFolderCheck($load(0, $organizationId) === null, 'Invalid source denied');
        indicatorFolderCheck($load($folder->getId(), $organizationId) === null, 'Folder is not a spreadsheet');
    }
    indicatorFolderCheck(omoStatsLoadVisibleEthercalcDocument($spreadsheet->getId(), $organizationId) === null, 'ODS is not EtherCalc');
    indicatorFolderCheck(omoStatsLoadVisibleSpreadsheetDocument($ethercalc->getId(), $organizationId) === null, 'EtherCalc is not an uploaded spreadsheet');
    echo "stat_indicator_document_folders_test: OK\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}
