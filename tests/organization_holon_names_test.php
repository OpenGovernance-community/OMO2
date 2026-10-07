<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization, Application, OrganizationApplication};

function organizationNamesCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function organizationNamesFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $field => $value) $object->set($field, $value);
    $result = $object->save();
    organizationNamesCheck(!empty($result['status']), (string)($result['text'] ?? 'Fixture save failed'));
    return $object;
}

// All fixtures and history changes are rolled back in the local test database.
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $organization = organizationNamesFixture(Organization::class, ['name' => 'Original name', 'shortname' => 'names-' . $nonce]);
    $application = new Application();
    organizationNamesCheck($application->load(['hash', 'structure']), 'The local structure application is required');
    organizationNamesFixture(OrganizationApplication::class, [
        'IDorganization' => $organization->getId(), 'IDapplication' => $application->getId(), 'active' => 1,
    ]);
    $user = organizationNamesFixture(User::class, ['firstname' => 'Names test', 'email' => 'names-' . $nonce . '@example.invalid', 'active' => 1]);
    organizationNamesFixture(UserOrganization::class, [
        'IDuser' => $user->getId(), 'IDorganization' => $organization->getId(),
        'active' => 1, 'parameters' => json_encode(['isAdmin' => true]),
    ]);
    $holon = organizationNamesFixture(Holon::class, [
        'name' => 'Original name', 'nomcomplet' => 'Original full name',
        'IDorganization' => $organization->getId(), 'IDtypeholon' => 4,
        'active' => 1, 'visible' => 1, 'IDholon_parent' => null,
    ]);
    $holon->set('IDholon_org', $holon->getId());
    organizationNamesCheck(!empty($holon->save()['status']), 'Root setup failed');
    $_SESSION['currentUser'] = $user->getId();
    $_SESSION['currentOrganization'] = $organization->getId();
    organizationNamesCheck(commonSetCurrentUserAdminMode(true, $organization->getId()), 'Admin fixture must be enabled');

    $data = $organization->getHolonDefinitionEditorData($holon->getId());
    organizationNamesCheck(($data['templates'][0]['fullName'] ?? '') === 'Original full name', 'Editor must load the full name');
    foreach ([['fullName' => '  New full name  '], [], ['fullName' => '']] as $index => $fields) {
        $result = $organization->saveHolonDefinitionEditor(['name' => 'Short name', 'properties' => []] + $fields, $user->getId(), $holon->getId());
        organizationNamesCheck(!empty($result['status']), (string)($result['message'] ?? 'Save failed'));
        $holon->load($holon->getId(), true);
        $expected = $index === 2 ? '' : 'New full name';
        organizationNamesCheck($holon->get('name') === 'Short name', 'Short name must persist');
        organizationNamesCheck((string)$holon->get('nomcomplet') === $expected, 'Full name must persist, survive omitted payloads, and clear when empty');
        organizationNamesCheck(($result['template']['fullName'] ?? null) === $expected, 'Save response must reload the full name');
        organizationNamesCheck($holon->getFullDisplayName() === ($expected ?: 'Short name'), 'Display must fall back to the short name');
        $organization->load($organization->getId(), true);
        organizationNamesCheck($organization->get('shortname') === 'names-' . $nonce, 'Holon names must not change the URL identifier');
    }
    $_SESSION['currentUser'] = 0;
    $denied = $organization->saveHolonDefinitionEditor(['name' => 'Denied', 'fullName' => 'Denied'], 0, $holon->getId());
    organizationNamesCheck(empty($denied['status']), 'An unauthenticated edit must be denied');
    $holon->load($holon->getId(), true);
    organizationNamesCheck($holon->get('name') === 'Short name' && (string)$holon->get('nomcomplet') === '', 'Denied edits must preserve both names');
    echo "organization_holon_names_test: OK\n";
} finally {
    $pdo->rollBack();
    DbObject::$preload = [];
}
