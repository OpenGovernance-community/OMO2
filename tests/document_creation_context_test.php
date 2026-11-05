<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{Application, DbObject, Document, Holon, Organization, OrganizationApplication, User, UserOrganization};

function documentContextExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function documentContextFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    $result = $object->save();
    documentContextExpect(!empty($result['status']), $class . ': ' . json_encode($result));
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = 'document-context-' . bin2hex(random_bytes(5));
    $user = documentContextFixture(User::class, ['email' => $nonce . '@example.invalid', 'firstname' => 'Admin', 'active' => 1]);
    $organization = documentContextFixture(Organization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $oid = (int)$organization->getId();
    $uid = (int)$user->getId();
    documentContextFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'active' => 1, 'parameters' => '{"isAdmin":true}']);
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid, 'isAdminByOrganization' => [$oid => true]];
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    foreach (['structure', 'documents'] as $hash) {
        $application = new Application();
        documentContextExpect((bool)$application->load([['hash', $hash]]), 'Application required: ' . $hash);
        documentContextFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $application->getId(), 'active' => 1]);
    }
    $root = documentContextFixture(Holon::class, ['IDorganization' => $oid, 'name' => 'Organization', 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId());
    documentContextExpect(!empty($root->save()['status']), 'Root attachment must save.');
    foreach ([null, (int)$root->getId()] as $holonId) {
        foreach ([true, false] as $useSessionCache) {
            documentContextExpect(Document::canCreateInOrganizationContext($oid, $holonId, $uid, 0, $useSessionCache), 'Admin must pass creation permissions.');
        }
        foreach ([[], ['edit_visibility_type' => 'role'], ['visibility_type' => 'circle', 'edit_visibility_type' => 'circle']] as $scopes) {
            $document = new Document();
            $result = $document->createInOrganizationContext($oid, $holonId, $uid, array_merge(['title' => 'Folder', 'document_type' => Document::TYPE_FOLDER], $scopes));
            documentContextExpect(!empty($result['status']), 'Folder creation failed: ' . json_encode($result));
            documentContextExpect($document->isFolder(), 'Created entry must be a folder.');
            documentContextExpect(($document->getPrimaryEditVisibilityRuleRow()['visibility_type'] ?? '') === 'self', 'Without a role or circle, editing must fall back to the owner.');
            $loaded = new Document();
            documentContextExpect($loaded->load($document->getId()) && (int)$loaded->get('IDorganization') === $oid
                && (int)$loaded->get('IDholon') === (int)$holonId, 'Folder must retain its organization and holon.');
            documentContextExpect(Document::canCreateInOrganizationContext($oid, null, $uid, (int)$document->getId(), false), 'Admin can create inside the folder.');
            $child = new Document();
            $result = $child->createInOrganizationContext($oid, null, $uid, ['title' => 'Child', 'document_type' => Document::TYPE_FOLDER, 'parent_document_id' => $document->getId()]);
            documentContextExpect(!empty($result['status']) && (int)$child->get('IDholon') === (int)$holonId
                && (int)$child->get('IDdocument_parent') === (int)$document->getId(), 'Child folder must inherit the destination: ' . json_encode($result));
        }
    }
    // Defaults must still follow the final destination when actual roles/circles exist.
    $circle = documentContextFixture(Holon::class, ['IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(), 'name' => 'Circle', 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $role = documentContextFixture(Holon::class, ['IDholon_org' => $root->getId(), 'IDholon_parent' => $circle->getId(), 'name' => 'Role', 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    foreach ([[$circle, 'circle'], [$role, 'role']] as [$holon, $expectedScope]) {
        $folder = new Document();
        $result = $folder->createInOrganizationContext($oid, (int)$holon->getId(), $uid, ['title' => 'Scoped folder', 'document_type' => Document::TYPE_FOLDER]);
        documentContextExpect(!empty($result['status']), 'Scoped folder creation: ' . json_encode($result));
        documentContextExpect(($folder->getPrimaryEditVisibilityRuleRow()['visibility_type'] ?? '') === $expectedScope, 'Default editing must match the final holon type.');
        $child = new Document();
        $result = $child->createInOrganizationContext($oid, (int)$root->getId(), $uid, ['title' => 'Scoped child', 'document_type' => Document::TYPE_HTML, 'parent_document_id' => $folder->getId()]);
        documentContextExpect(!empty($result['status']) && ($child->getPrimaryEditVisibilityRuleRow()['visibility_type'] ?? '') === $expectedScope, 'Defaults must use the parent folder rather than the requested holon.');
    }
    documentContextExpect(!Document::canCreateInOrganizationContext($oid, (int)$root->getId(), 0), 'Anonymous creation remains forbidden.');
    documentContextExpect(!Document::canCreateInOrganizationContext($oid, PHP_INT_MAX, $uid, 0, false), 'Admin creation still rejects invalid destinations.');
    echo "document_creation_context_test: OK (single organization holon, default/explicit scopes, persisted folders, nested folders, circle/role defaults, invalid destinations, rollback)\n";
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    DbObject::$preload = [];
}
