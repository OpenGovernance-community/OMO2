<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/account_merge.php';

function assertAccountMergeTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_SESSION = [];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$csrfToken = commonAccountMergeGetCsrfToken();
assertAccountMergeTest(
    preg_match('/^[a-f0-9]{64}$/', $csrfToken) === 1
        && commonAccountMergeVerifyCsrfToken($csrfToken)
        && !commonAccountMergeVerifyCsrfToken(str_repeat('0', 64)),
    'The account merge flow must use a session-bound CSRF token.'
);

$state = commonAccountMergeStoreState([
    'current_user_id' => 10,
    'other_user_id' => 20,
    'request_ip' => '127.0.0.1',
    'expires_at' => time() + 600,
    'verified_at' => 0,
]);
assertAccountMergeTest(
    is_array(commonAccountMergeGetState(10))
        && commonAccountMergeGetState(10, true) === null,
    'The second account must not be mergeable before ownership verification.'
);
$state = commonAccountMergeMarkVerified($state, 'code');
assertAccountMergeTest(
    is_array(commonAccountMergeGetState(10, true))
        && ($state['phase'] ?? '') === 'confirm',
    'A verified ownership proof must unlock only the confirmation phase.'
);

$root = dirname(__DIR__);
$userSource = file_get_contents($root . '/class/dbobject/user.class.php');
$endpointSource = file_get_contents($root . '/ajax/user_account_merge.php');
$profileSource = file_get_contents($root . '/popup/profil.php');
$toolsSource = file_get_contents($root . '/popup/profil_tools.php');
$authSource = file_get_contents($root . '/common/auth.php');
$historySource = file_get_contents($root . '/class/dbobject/history.class.php');
$structureSource = file_get_contents($root . '/omo/api/getStructureData.php');

assertAccountMergeTest(
    is_string($userSource)
        && str_contains($userSource, 'public static function mergeAccounts')
        && str_contains($userSource, '$pdo->beginTransaction()')
        && str_contains($userSource, '$pdo->rollBack()')
        && str_contains($userSource, 'accountMergeOwnershipColumns')
        && str_contains($userSource, '$superAdminForced = true')
        && str_contains($userSource, '$removedEmail')
        && str_contains($userSource, 'SET email = :removed_email')
        && str_contains($userSource, "key_usage.REFERENCED_TABLE_NAME = 'user'")
        && str_contains($userSource, 'IDvalidator_user')
        && str_contains($userSource, "DELETE FROM `user` WHERE id = :removed_user_id"),
    'The merge must be transactional and must transfer declared and conventional user references before deletion.'
);
assertAccountMergeTest(
    str_contains((string)$userSource, 'accountMergeDecisionRows')
        && str_contains((string)$userSource, 'account_merge_response_archive')
        && str_contains((string)$userSource, 'accountMergeMembershipRows')
        && str_contains((string)$userSource, 'accountMergeOrganizationIds')
        && str_contains((string)$userSource, 'accountMergeLearningRows')
        && str_contains((string)$userSource, 'accountMergeActivityRows'),
    'Duplicate memberships, learning progress, attendance, project assignments, and decision responses must have explicit merge strategies.'
);
assertAccountMergeTest(
    str_contains((string)$userSource, "'account_merge'")
        && str_contains((string)$userSource, "'organization'")
        && str_contains((string)$userSource, 'organization_history_entries')
        && str_contains((string)$userSource, 'History::createEntry')
        && str_contains((string)$historySource, 'getLatestStructureEntryId')
        && str_contains((string)$historySource, "'account_merge'")
        && str_contains((string)$structureSource, 'getLatestStructureEntryId'),
    'Each organization concerned by a merge must receive a history entry to invalidate its cached structure.'
);
assertAccountMergeTest(
    is_string($endpointSource)
        && str_contains($endpointSource, 'commonAccountMergeVerifyCsrfToken')
        && str_contains($endpointSource, 'commonAccountMergeClearState();')
        && str_contains($endpointSource, 'isHistoricalPlaceholder()')
        && str_contains($endpointSource, 'commonBeginTotpLogin')
        && str_contains($endpointSource, 'commonTotpVerifyCode')
        && str_contains($endpointSource, "in_array(\$keep, array('current', 'other'), true)")
        && str_contains($endpointSource, "\$result['kept_user_id']")
        && str_contains($endpointSource, '\\dbObject\\User::mergeAccounts'),
    'The endpoint must verify CSRF, support TOTP, allow the kept account choice, and delegate data access to User.'
);
assertAccountMergeTest(
    preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE)\b\s+(?:FROM|INTO|`?[A-Za-z_])/i', (string)$endpointSource) !== 1,
    'SQL must remain in dbObject classes rather than the account merge endpoint.'
);
assertAccountMergeTest(
    is_string($profileSource)
        && str_contains($profileSource, 'profile.popup.tabs.tools')
        && str_contains($profileSource, '/popup/profil_tools.php'),
    'The profile popup must expose the Tools tab and its lazy-loaded merge panel.'
);
assertAccountMergeTest(
    is_string($toolsSource)
        && str_contains($toolsSource, 'data-merge-use-password')
        && str_contains($toolsSource, 'data-merge-totp-step')
        && str_contains($toolsSource, 'data-merge-confirm-check')
        && str_contains($toolsSource, 'data-merge-superadmin-note')
        && str_contains($toolsSource, 'enforceSuperAdminChoice')
        && str_contains($toolsSource, 'window.top.location.reload()'),
    'The merge UI must support password fallback, TOTP, explicit confirmation, and page refresh.'
);
assertAccountMergeTest(
    is_string($authSource)
        && str_contains($authSource, "\$verifyPath = '/common/login_verify.php'")
        && str_contains($authSource, '$verifyPath . $querySeparator'),
    'Login-code delivery must support a merge-specific verification return path without changing normal login defaults.'
);

echo "account_merge_test: OK\n";
