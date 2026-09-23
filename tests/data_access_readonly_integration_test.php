<?php
declare(strict_types=1);

// Explicit local integration test: reads existing data only, never saves fixtures.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$organizationId = (int)($argv[1] ?? 0);
if ($organizationId <= 0) { fwrite(STDERR, "Usage: php tests/data_access_readonly_integration_test.php ORGANIZATION_ID\n"); exit(2); }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
$_ENV['DB_QUERY_LOG_ENABLED'] = 'false';
$_SERVER['DB_QUERY_LOG_ENABLED'] = 'false';
putenv('DB_QUERY_LOG_ENABLED=false');

use dbObject\ArrayDocument;
use dbObject\ArrayEvent;
use dbObject\ArrayUserOrganization;
use dbObject\DbObject;
use dbObject\HolonPermission;
use dbObject\ObjectVisibility;
use dbObject\Organization;

$organization = new Organization();
if (!$organization->load($organizationId)) { throw new RuntimeException('Organization not found.'); }
$root = $organization->getStructuralRootHolon();
if (!$root) { throw new RuntimeException('A structural root is required for this test.'); }
$holonIds = $root->getVisibleDescendantIds(true);
$memberships = new ArrayUserOrganization();
$memberships->loadHydrated(['where' => [['field' => 'IDorganization', 'value' => $organizationId]], 'limit' => 5]);
$userIds = [0];
foreach ($memberships as $membership) { $userIds[] = (int)$membership->get('IDuser'); }
$userIds = array_values(array_unique($userIds));
$cases = 0;
foreach ($userIds as $userId) {
    foreach ([false, true] as $adminMode) {
        if ($userId === 0 && $adminMode) { continue; }
        $snapshots = [];
        foreach ([false, true] as $memoEnabled) {
            $_SESSION = ['currentUser' => $userId, 'currentOrganization' => $organizationId];
            DbObject::$preload = [];
            if ($adminMode) { $_SESSION['isAdminByOrganization'][$organizationId] = true; }
            DbObject::enableReadOnlyMemoization($memoEnabled);
            $snapshot = [];
            foreach ([$organizationId, $organizationId + 1000000000] as $scopeId) {
                foreach (['CAN_EDIT_DOCUMENT', 'CAN_DELETE_DOCUMENT', 'CAN_EDIT_EVENT', 'CAN_DELETE_EVENT'] as $key) {
                    $snapshot['permissions'][$scopeId][$key] = HolonPermission::buildUserPermissionSetForOrganization($userId, $scopeId, [$key]);
                    // A second evaluation exercises reuse as well as the initial fill.
                    if ($snapshot['permissions'][$scopeId][$key] !== HolonPermission::buildUserPermissionSetForOrganization($userId, $scopeId, [$key])) {
                        throw new RuntimeException('Repeated permission evaluation changed.');
                    }
                }
            }
            $documents = new ArrayDocument();
            $rules = $documents->loadVisibleForOrganizationContext($organizationId, (int)$root->getId(), 'descendants', $holonIds);
            foreach ($documents as $document) {
                $id = (int)$document->getId();
                $snapshot['documents'][$id] = [
                    $document->canUserOpenPvEditor($userId, $organizationId),
                    $document->canManageLifecycle($organizationId, $userId),
                    $document->canDeleteInOrganizationContext($organizationId, $userId),
                    $document->getVisibilityDisplayData($organizationId, $rules[$id] ?? null),
                    ObjectVisibility::loadActiveRuleRow('document', $id, $organizationId),
                ];
            }
            $events = new ArrayEvent();
            $events->loadForOrganizationDateRange($organizationId, new DateTimeImmutable('first day of this month 00:00:00'), null, false, true);
            foreach ($events as $event) {
                $snapshot['events'][(int)$event->getId()] = $event->isVisibleToInvitationViewer($userId, $organizationId);
            }
            $snapshots[] = $snapshot;
        }
        if ($snapshots[0] !== $snapshots[1]) { throw new RuntimeException('Memoized and fresh permissions/visibility differ.'); }
        $cases++;
    }
}
DbObject::enableReadOnlyMemoization(false);
echo "OK: identical permissions, document actions and event visibility for {$cases} viewer/admin scenarios; other-organization scope checked.\n";
