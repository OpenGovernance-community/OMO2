<?php
declare(strict_types=1);

// Local MariaDB regression test. All fixtures and departures are rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, DeferredProposal, Organization, User, UserOrganization};

function departureExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function departureSave($object): void
{
    $result = $object->save();
    departureExpect(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}

$pdo = DbObject::getPdo();
departureExpect(!$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES), 'Use native prepared statements as in production.');
$pdo->beginTransaction();
try {
    $users = [];
    foreach (['departing', 'remaining'] as $name) {
        $user = new User();
        $user->set('email', $name . '-' . bin2hex(random_bytes(6)) . '@example.invalid');
        $user->set('active', true);
        departureSave($user);
        $users[] = $user;
    }
    [$departing, $remaining] = $users;
    $_SESSION['currentUser'] = (int)$departing->getId();
    $organizations = [];
    foreach (['Departure', 'Other'] as $name) {
        $organization = new Organization();
        $organization->set('name', $name . ' regression fixture');
        $organization->set('shortname', 'departure-' . bin2hex(random_bytes(6)));
        departureSave($organization);
        $organizations[] = $organization;
    }
    [$organization, $otherOrganization] = $organizations;
    $organizationId = (int)$organization->getId();
    $departingId = (int)$departing->getId();
    $remainingId = (int)$remaining->getId();
    foreach ($users as $user) {
        $membership = new UserOrganization();
        $membership->set('IDuser', $user->getId());
        $membership->set('IDorganization', $organizationId);
        $membership->set('active', true);
        $result = $membership->setOrganizationAdmin(true);
        departureExpect(!empty($result['status']), 'Both members must be administrators.');
    }
    departureExpect($organization->countActiveAdminMemberships($departingId) === 1, 'Another administrator must remain.');

    // Even an organization with no proposals must not fail during departure.
    departureExpect(
        DeferredProposal::handleUserDeparture((int)$otherOrganization->getId(), $departingId, $remainingId),
        'An empty proposal set must accept departure: ' . json_encode(DbObject::getLastDbError())
    );
    $fixtures = [
        [$organizationId, [$departingId, $departingId, $departingId]],
        [$organizationId, [$remainingId, $departingId, null]],
        [$organizationId, [$departingId, null, $remainingId]],
        [$organizationId, [$remainingId, $remainingId, $departingId]],
        [(int)$otherOrganization->getId(), [$departingId, $departingId, $departingId]],
    ];
    $fields = ['IDuser_author', 'IDuser_validated', 'IDuser_applied'];
    $proposals = [];
    foreach ($fixtures as [$fixtureOrganizationId, $references]) {
        $proposal = new DeferredProposal();
        foreach (['IDorganization' => $fixtureOrganizationId, 'target_type' => 'rule', 'operation' => 'create',
            'status' => 'pending', 'after_state' => ['title' => 'Departure fixture']] as $field => $value) {
            $proposal->set($field, $value);
        }
        foreach ($fields as $index => $field) $proposal->set($field, $references[$index]);
        departureSave($proposal);
        $proposals[] = $proposal;
    }

    // Exercise the shared departure operation inside the test transaction.
    $result = $organization->disconnectUserPreservingHistory($departingId);
    departureExpect(!empty($result['status']), 'Departure must succeed with another admin: ' . json_encode($result));
    $ghostUserId = (int)$result['ghostUserId'];
    departureExpect($ghostUserId > 0 && !in_array($ghostUserId, [$departingId, $remainingId], true), 'History must use a dedicated placeholder.');
    departureExpect(!UserOrganization::hasActiveMembership($departingId, $organizationId), 'Departing membership must be removed.');
    departureExpect(UserOrganization::hasActiveMembership($remainingId, $organizationId), 'Remaining membership must stay active.');
    departureExpect($organization->countActiveAdminMemberships($departingId) === 1, 'Remaining admin must retain their rights.');
    foreach ($proposals as $index => $proposal) {
        departureExpect($proposal->load($proposal->getId(), true), 'Proposal history must remain available.');
        [$fixtureOrganizationId, $references] = $fixtures[$index];
        foreach ($fields as $fieldIndex => $field) {
            $expected = $references[$fieldIndex];
            if ($fixtureOrganizationId === $organizationId && $expected === $departingId) $expected = $ghostUserId;
            $actual = $proposal->get($field);
            departureExpect(($expected === null ? $actual === null : (int)$actual === $expected), 'Only departing references in the target organization may change: ' . $field);
        }
    }
    $lastAdminResult = $organization->removeMember($remainingId, ['actorUserId' => $remainingId]);
    departureExpect(empty($lastAdminResult['status']) && str_contains($lastAdminResult['message'], 'Le dernier admin'), 'Last-admin protection must remain active.');
    echo "organization_departure_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
