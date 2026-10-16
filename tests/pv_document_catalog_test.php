<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/omo/api/documents/pv/resource_catalog.php';

use dbObject\{DbObject, Document, Organization, User, UserOrganization};

function pvCatalogCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function pvCatalogSave($object): void
{
    $result = $object->save();
    pvCatalogCheck(!empty($result['status']), 'Fixture save: ' . json_encode($result));
}
function pvCatalogFixture(string $class, array $fields)
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    pvCatalogSave($object);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = 'pv-catalog-' . bin2hex(random_bytes(5));
    $organization = pvCatalogFixture(Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $oid = (int)$organization->getId();
    $owner = pvCatalogFixture(User::class, ['email' => $nonce . '-owner@example.invalid', 'active' => 1]);
    $viewer = pvCatalogFixture(User::class, ['email' => $nonce . '-viewer@example.invalid', 'active' => 1]);
    $uid = (int)$viewer->getId();
    foreach ([$owner, $viewer] as $user) {
        pvCatalogFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $user->getId(), 'active' => 1]);
    }
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $makeDocument = static function (array $fields) use ($oid, $owner): Document {
        $document = pvCatalogFixture(Document::class, $fields + [
            'title' => 'Source', 'IDorganization' => $oid, 'IDuser' => $owner->getId(), 'active' => 1,
        ]);
        pvCatalogCheck(!empty($document->saveVisibilityRule('organization')['status']), 'Save visibility');
        return $document;
    };
    $pv = $makeDocument(['documenttype' => Document::TYPE_PV, 'pvstage' => Document::PV_STAGE_VALIDATED]);
    $folder = $makeDocument(['title' => 'Folder', 'estDossier' => 1]);
    $nested = $makeDocument(['title' => 'Nested folder', 'estDossier' => 1, 'IDdocument_parent' => $folder->getId()]);
    $sources = [];
    foreach (array_keys(Document::getDocumentTypeCatalog()) as $type) {
        $sources[$type] = $makeDocument([
            'documenttype' => $type, 'title' => 'Source ' . $type, 'IDdocument_parent' => $nested->getId(),
            'estDossier' => in_array($type, [Document::TYPE_FOLDER, Document::TYPE_NEXTCLOUD_FOLDER], true),
            'ethercalcroomid' => $nonce, 'etherpadpadid' => $nonce,
            'storedfilepath' => $nonce . '.ods', 'storedfilename' => 'Source.ods',
            'pvstage' => Document::PV_STAGE_VALIDATED,
        ]);
    }
    $catalog = static fn(): array => omoDocumentsPvEditorLoadResourceCatalog($pv, $organization, $uid, 'documents', static fn($key): string => $key);
    $byId = array_column($catalog(), null, 'id');
    pvCatalogCheck(!isset($byId[$pv->getId()]), 'Current PV is excluded');
    pvCatalogCheck(isset($byId[$folder->getId()], $byId[$nested->getId()]), 'Ancestor folders are selectable');
    foreach ($sources as $type => $source) {
        $id = (int)$source->getId();
        pvCatalogCheck(isset($byId[$id]), 'Visible document type: ' . $type);
        pvCatalogCheck($byId[$id]['parentDocumentId'] === (int)$nested->getId(), 'Hierarchy retained: ' . $type);
        pvCatalogCheck($byId[$id]['documentType'] === $type, 'Type retained: ' . $type);
        pvCatalogCheck($byId[$id]['isFolder'] === $source->isFolder(), 'Folder metadata: ' . $type);
        $reference = '<span data-omo-embed-type="document" data-omo-document-id="' . $id . '"></span>';
        $rendered = $pv->renderResolvedHtmlForViewer($reference, $oid, ['compactEmbeds' => true]);
        pvCatalogCheck(str_contains($rendered, '#documents-d' . $id), 'PV link rendered: ' . $type);
        if (!$source->supportsHtmlContent()) {
            $rendered = $pv->renderResolvedHtmlForViewer($reference, $oid);
            pvCatalogCheck(str_contains($rendered, '#documents-d' . $id), 'Non-HTML reference stays a link: ' . $type);
        }
    }
    $sources[Document::TYPE_ETHERCALC]->set('IDdocument_parent', $folder->getId());
    pvCatalogSave($sources[Document::TYPE_ETHERCALC]);
    $byId = array_column($catalog(), null, 'id');
    pvCatalogCheck($byId[$sources[Document::TYPE_ETHERCALC]->getId()]['parentDocumentId'] === (int)$folder->getId(), 'Moves refresh the hierarchy');

    pvCatalogCheck(!empty($folder->saveVisibilityRule('self')['status']), 'Restrict ancestor');
    pvCatalogCheck($catalog() === [], 'Private ancestor and its descendants are excluded');
    pvCatalogCheck(!empty($folder->saveVisibilityRule('organization')['status']), 'Restore ancestor');
    $nested->set('active', 0);
    pvCatalogSave($nested);
    $ids = array_column($catalog(), 'id');
    pvCatalogCheck($ids === [(int)$folder->getId(), (int)$sources[Document::TYPE_ETHERCALC]->getId()], 'Archived subtree excluded, moved source remains visible');

    $other = pvCatalogFixture(Organization::class, ['name' => $nonce . '-other', 'shortname' => $nonce . '-other']);
    $foreign = $makeDocument(['IDorganization' => $other->getId(), 'documenttype' => Document::TYPE_ETHERCALC]);
    pvCatalogCheck(!in_array((int)$foreign->getId(), array_column($catalog(), 'id'), true), 'Other organization excluded');
    echo "pv_document_catalog_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
