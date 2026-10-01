<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/account_deletion.php';

function assertAccountDeletionTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_SESSION = [];
$csrfToken = commonAccountDeletionGetCsrfToken();
assertAccountDeletionTest(
    preg_match('/^[a-f0-9]{64}$/', $csrfToken) === 1
        && commonAccountDeletionVerifyCsrfToken($csrfToken)
        && !commonAccountDeletionVerifyCsrfToken(str_repeat('0', 64)),
    'Account deletion must use a dedicated session-bound CSRF token.'
);

$root = dirname(__DIR__);
$userSource = file_get_contents($root . '/class/dbobject/user.class.php');
$organizationSource = file_get_contents($root . '/class/dbobject/organization.class.php');
$organizationActionSource = file_get_contents($root . '/omo/api/organizations/card_action.php');
$directorySource = file_get_contents($root . '/omo/index.php');
$endpointSource = file_get_contents($root . '/ajax/user_account_delete.php');
$toolsSource = file_get_contents($root . '/popup/profil_tools.php');

assertAccountDeletionTest(
    is_string($userSource)
        && str_contains($userSource, 'public static function getAccountDeletionPlan')
        && str_contains($userSource, 'public static function deleteOwnAccount')
        && str_contains($userSource, 'if ($user->isSiteAdmin())')
        && str_contains($userSource, 'countOtherActiveSiteAdminsInBaseOrganization($userId) === 0')
        && str_contains($userSource, 'base_membership.active = 1')
        && str_contains($userSource, 'other_user.active = 1')
        && str_contains($userSource, 'other_user.siteadmin = 1')
        && str_contains($userSource, '$isActive && $isSystemOrganization && $activeMemberCount === 1')
        && !str_contains($userSource, 'if ($isActive && $isSystemOrganization) {')
        && str_contains($userSource, 'countActiveAdminMemberships($userId) === 0')
        && str_contains($userSource, 'deleted_organizations')
        && str_contains($userSource, 'disconnectUserPreservingHistory($userId)')
        && str_contains($userSource, 'IDorganization = :organization_id FOR UPDATE')
        && str_contains($userSource, "'account_deleted'")
        && str_contains($userSource, "DELETE FROM `user` WHERE id = :user_id"),
    'Account deletion must plan administrator constraints, retain historical references, and delete the final account transactionally.'
);
assertAccountDeletionTest(
    is_string($organizationSource)
        && str_contains($organizationSource, 'deleteForAccountDeletion')
        && str_contains($organizationSource, 'deleteInternal(false)')
        && str_contains($organizationSource, '$isSelfRemoval && $this->isSystemOrganization()')
        && str_contains($organizationSource, 'User::countOtherActiveSiteAdminsInBaseOrganization($userId) === 0')
        && !str_contains($organizationSource, '$isSelfRemoval && $this->isSystemOrganization() && $membership->isOrganizationAdmin()')
        && str_contains($organizationSource, "'DecisionProcess'")
        && str_contains($organizationSource, "'ChatMessage'")
        && str_contains($organizationSource, "'DeferredProposal'"),
    'Sole-member organizations must be deleted through dbObject, while preserved organizations transfer all supported historical references.'
);
assertAccountDeletionTest(
    is_string($organizationActionSource)
        && !str_contains($organizationActionSource, 'Un admin ne peut pas quitter l organisation de base.')
        && str_contains($organizationActionSource, '$organization->removeMember($currentUserId')
        && is_string($directorySource)
        && !str_contains($directorySource, 'isSystemOrganizationAdmin')
        && str_contains($directorySource, 'data-omo-org-action="leave"'),
    'The organization leave action must delegate administrator and superadmin safeguards to Organization.'
);
assertAccountDeletionTest(
    is_string($endpointSource)
        && str_contains($endpointSource, 'commonAccountDeletionVerifyCsrfToken')
        && str_contains($endpointSource, '\\dbObject\\User::getAccountDeletionPlan')
        && str_contains($endpointSource, '\\dbObject\\User::deleteOwnAccount')
        && preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE)\b\s+(?:FROM|INTO|`?[A-Za-z_])/i', $endpointSource) !== 1,
    'The deletion endpoint must validate CSRF and keep SQL in dbObject classes.'
);
assertAccountDeletionTest(
    is_string($toolsSource)
        && str_contains($toolsSource, 'data-profile-delete-root')
        && str_contains($toolsSource, 'generic-accordion--collapsible')
        && str_contains($toolsSource, 'data-delete-open')
        && str_contains($toolsSource, 'data-delete-confirmation-input')
        && str_contains($toolsSource, 'data-delete-complete')
        && str_contains($toolsSource, 'postDeletion("plan"')
        && str_contains($toolsSource, 'postDeletion("delete"'),
    'The Tools screen must expose an expandable deletion action with a server-derived confirmation.'
);

echo "account_deletion_test: OK\n";
