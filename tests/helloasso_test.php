<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/support.php';
require_once dirname(__DIR__) . '/includes/server_env_admin.php';

use dbObject\{User, UserHelloAsso, HelloAssoConnection, UserPatreon};

$nonce = bin2hex(random_bytes(6));
$items = [];
$GLOBALS['helloAssoClientId'] = 'fixture-' . $nonce;
$GLOBALS['helloAssoClientSecret'] = 'never-sent';
$GLOBALS['helloAssoOrganizationSlug'] = 'fixture-association';
$GLOBALS['helloAssoForms'] = '';
$GLOBALS['helloAssoSandbox'] = true;
foreach (['patreonClientId', 'patreonClientSecret', 'patreonCreatorCampaignId', 'patreonCreatorUserId', 'patreonConnectUrl', 'patreonRedirectUri', 'patreonConnectAllowedOrigins'] as $key) $GLOBALS[$key] = '';

function haPayment(string $email, string $type = 'MonthlyDonation', int $amount = 4000): array
{
    return ['id' => 100, 'payer' => ['email' => $email], 'order' => ['organizationSlug' => 'fixture-association', 'formType' => 'Donation', 'formSlug' => 'support'],
        'state' => 'Authorized', 'amount' => $amount, 'date' => (new DateTimeImmutable('-1 day'))->format(DATE_ATOM),
        'items' => [['id' => 200, 'type' => $type, 'shareAmount' => $amount]], 'refundOperations' => []];
}

try {
    $fields = serverEnvAdminGetFieldMap();
    foreach (['HELLOASSO_CLIENT_ID', 'HELLOASSO_CLIENT_SECRET', 'HELLOASSO_ORGANIZATION_SLUG', 'HELLOASSO_SANDBOX', 'HELLOASSO_FORMS', 'HELLOASSO_SYNC_INTERVAL', 'HELLOASSO_BATCH_SIZE'] as $key) {
        mcpCheck(isset($fields[$key]) && $fields[$key]['label'] !== $key, 'Server setting is editable: ' . $key);
    }
    mcpCheck(!empty($fields['HELLOASSO_CLIENT_SECRET']['secret']), 'Secret is masked in server settings');
    $merged = serverEnvAdminMergeSubmittedValues(['HELLOASSO_CLIENT_SECRET' => ''], ['HELLOASSO_CLIENT_SECRET' => 'fixture']);
    mcpCheck($merged['HELLOASSO_CLIENT_SECRET'] === 'fixture', 'Empty secret edit preserves stored secret');
    mcpCheck(serverEnvAdminValidateValues(['HELLOASSO_BATCH_SIZE' => '0']) !== [], 'Invalid batch size rejected');
    $user = $items['user'] = mcpFixture(User::class, ['email' => 'ha-' . $nonce . '@example.invalid', 'active' => 1]);
    $uid = (int)$user->getId(); $email = (string)$user->get('email');
    $now = new DateTimeImmutable();
    $monthly = haPayment($email);
    $annual = haPayment($email, 'Donation', 40000); $annual['id'] = 101;
    foreach ([$monthly, $annual] as $payment) {
        mcpCheck(UserHelloAsso::selectContribution(helloAssoNormalizePayment($payment, $email))['amount_cents'] === 4000, '40 monthly equals 400 annual');
    }
    mcpCheck(helloAssoPeriodEnd(new DateTimeImmutable('2026-01-31T10:30:00+01:00'), true)->format('Y-m-d H:i') === '2026-02-28 10:30', 'Month end clamp');
    mcpCheck(helloAssoPeriodEnd(new DateTimeImmutable('2024-02-29T10:30:00+01:00'), false)->format('Y-m-d') === '2025-02-28', 'Leap year clamp');
    $expired = $monthly; $expired['date'] = '2026-01-31T10:30:00+01:00';
    $period = helloAssoNormalizePayment($expired, $email);
    mcpCheck(UserHelloAsso::selectContribution($period, new DateTimeImmutable('2026-02-28T10:30:00+01:00'))['amount_cents'] === 0, 'End boundary exclusive');
    mcpCheck(UserHelloAsso::selectContribution($period, new DateTimeImmutable('2026-02-28T10:29:59+01:00'))['amount_cents'] === 4000, 'Valid until anniversary');
    foreach (['Pending', 'Refused', 'Refunded', 'Refunding', 'Contested', 'WaitingBankValidation', 'Registered'] as $state) {
        $payment = $monthly; $payment['state'] = $state;
        mcpCheck(UserHelloAsso::selectContribution(helloAssoNormalizePayment($payment, $email))['amount_cents'] === 0, 'Reject state ' . $state);
    }
    $payment = $annual; $payment['refundOperations'] = [['status' => 'Processed', 'amount' => 10000, 'amountTip' => 1000]];
    mcpCheck(UserHelloAsso::selectContribution(helloAssoNormalizePayment($payment, $email))['amount_cents'] === 3000, 'Partial refund excludes tip and reduces contribution');
    $payment = $monthly; $payment['date'] = (new DateTimeImmutable('+1 day'))->format(DATE_ATOM);
    mcpCheck(UserHelloAsso::selectContribution(helloAssoNormalizePayment($payment, $email))['amount_cents'] === 0, 'Future payments grant no rights');
    $payment = $monthly; $payment['order']['formType'] = 'Event';
    mcpCheck(helloAssoNormalizePayment($payment, $email) === [], 'Tickets excluded');
    $payment = $monthly; $payment['order']['organizationSlug'] = 'other';
    mcpCheck(helloAssoNormalizePayment($payment, $email) === [], 'Other association excluded');
    mcpCheck(helloAssoNormalizePayment($monthly, 'other@example.invalid') === [], 'Exact payer email required');
    $payment = $monthly; $payment['payer']['email'] = strtoupper($email);
    mcpCheck(count(helloAssoNormalizePayment($payment, $email)) === 1, 'Case-insensitive exact email');
    $GLOBALS['helloAssoForms'] = 'Donation/other';
    mcpCheck(helloAssoNormalizePayment($monthly, $email) === [], 'Optional form filter');
    $GLOBALS['helloAssoForms'] = '';
    mcpCheck(!supportUserCanUseAi($uid), 'HelloAsso-only installation does not bypass paywall');

    $requests = []; $mode = 'success'; $tokenCalls = 0;
    $transport = static function ($method, $path, $parameters, $token) use (&$requests, &$mode, &$tokenCalls, $monthly, $annual): array {
        $requests[] = [$method, $path, $parameters];
        if ($method === 'POST') {
            $tokenCalls++;
            return ['access_token' => 'fixture-access', 'refresh_token' => 'fixture-refresh', 'expires_in' => 1800];
        }
        mcpCheck($token === 'fixture-access', 'Authenticated request');
        if ($mode === 'empty') return ['data' => [], 'meta' => ['pagination' => ['totalPages' => 0]]];
        if (!isset($parameters['continuationToken'])) return ['data' => [$monthly], 'meta' => ['pagination' => ['continuationToken' => 'next']]];
        if ($mode === 'failure') throw new RuntimeException('HelloAsso HTTP 503.', 503);
        if ($mode === 'loop') return ['data' => [$annual], 'meta' => ['pagination' => ['continuationToken' => 'next']]];
        return ['data' => [$monthly, $annual], 'meta' => ['pagination' => ['continuationToken' => null]]];
    };
    mcpCheck(helloAssoSyncUser($uid, true, $transport), 'Complete paginated sync succeeds');
    $items['snapshot'] = UserHelloAsso::findByUserId($uid);
    $items['tokens'] = HelloAssoConnection::forConfiguration(helloAssoConfigurationKey(false));
    mcpCheck(count(json_decode($items['snapshot']->get('payments'), true)) === 2, 'Pagination duplicates deduplicated');
    mcpCheck(supportGetUserContributionAmountCents($uid) === 4000 && supportUserCanUseAi($uid), 'Common amount and AI accept HelloAsso');
    mcpCheck(patreonUserCanUseAi($uid) && patreonCanManageOrganizationRouting($uid), 'Legacy wrappers accept HelloAsso');
    mcpCheck(!supportCanManageOrganizationRouting($uid, 4000), 'Routing threshold remains strictly greater');
    mcpCheck(!helloAssoSyncUser($uid, false, $transport), 'Recent successful sync is throttled');
    mcpCheck(count($requests) === 3, 'No extra request during throttled sync');
    foreach (['failure', 'loop'] as $failureMode) {
        $mode = $failureMode; $failed = false;
        try { helloAssoSyncUser($uid, true, $transport); } catch (RuntimeException) { $failed = true; }
        mcpCheck($failed && supportGetUserContributionAmountCents($uid) === 4000, 'Incomplete sync preserves confirmed snapshot');
    }
    mcpCheck($tokenCalls === 1, 'Access token reused across synchronizations');
    $items['tokens']->set('token_expires_at', new DateTime('@1')); $items['tokens']->save();
    $mode = 'success'; helloAssoSyncUser($uid, true, $transport);
    mcpCheck($tokenCalls === 2, 'Expired token is renewed');
    $tokenRequests = array_values(array_filter($requests, static fn ($request) => $request[0] === 'POST'));
    mcpCheck($tokenRequests[1][2]['grant_type'] === 'refresh_token', 'Refresh token used after initial authentication');

    $items['patreon'] = mcpFixture(UserPatreon::class, ['IDuser' => $uid, 'is_connected' => 1, 'refresh_token' => 'fixture', 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 5000]);
    mcpCheck(supportGetUserContributionAmountCents($uid) === 5000 && supportGetUserContribution($uid)['source'] === 'patreon', 'Higher provider wins without summing');
    $items['patreon']->set('currently_entitled_amount_cents', 100); $items['patreon']->save();
    mcpCheck(supportGetUserContributionAmountCents($uid) === 4000 && supportGetUserContribution($uid)['source'] === 'helloasso', 'HelloAsso wins when higher');
    $items['patreon']->set('is_connected', 0); $items['patreon']->save();
    $duplicate = $items['duplicate'] = mcpFixture(User::class, ['email' => $email, 'active' => 1]);
    mcpCheck(supportGetUserContributionAmountCents($uid) === 0, 'Ambiguous email cannot claim donation');
    $duplicate->delete(); unset($items['duplicate']);
    $user->set('email', 'changed-' . $email); $user->save();
    mcpCheck(supportGetUserContributionAmountCents($uid) === 0, 'Changed email immediately revokes cached rights');
    $user->set('email', $email); $user->set('activation_pending', 1); $user->save();
    mcpCheck(supportGetUserContributionAmountCents($uid) === 0, 'Pending account cannot claim donation');
    $user->set('activation_pending', 0); $user->save();
    $mode = 'empty'; helloAssoSyncUser($uid, true, $transport);
    mcpCheck(supportGetUserContributionAmountCents($uid) === 0 && !supportUserCanUseAi($uid), 'Successful empty snapshot removes previous rights');
    $GLOBALS['helloAssoClientSecret'] = '';
    mcpCheck(!supportUserCanUseAi($uid), 'Partial HelloAsso configuration fails closed');
    $user->set('activation_pending', 1); $user->set('active', 0); $user->save();
    $code = $user->issuePasswordResetCode();
    mcpCheck($code !== '' && $user->consumePasswordResetCode($code, commonHashUserPassword('HelloAsso-test-password!82')), 'Account activation succeeds even when HelloAsso is unavailable');
    mcpCheck((int)$user->get('active') === 1 && !(int)$user->get('activation_pending'), 'Account is activated independently of provider');
    $GLOBALS['helloAssoClientSecret'] = 'never-sent';
    $snapshot = UserHelloAsso::findByUserId($uid);
    $snapshot->set('last_sync_at', new DateTime('@1'));
    $snapshot->set('last_attempt_at', new DateTime('now', new DateTimeZone('UTC'))); $snapshot->save();
    mcpCheck(!in_array($uid, UserHelloAsso::dueUserIds(helloAssoConfigurationKey(), 21600, 100000), true), 'Cron backs off recent failed attempts');
    $snapshot->set('last_attempt_at', new DateTime('@1')); $snapshot->save();
    mcpCheck(in_array($uid, UserHelloAsso::dueUserIds(helloAssoConfigurationKey(), 21600, 100000), true), 'Cron selects due accounts');
    echo "PASS HelloAsso periods, amounts, refunds, email ownership, pagination, token rotation, failure recovery and shared support access\n";
} finally {
    // The snapshot and token may exist even if the first assertions failed.
    if (isset($user)) { $snapshot = UserHelloAsso::findByUserId((int)$user->getId()); if ($snapshot) $snapshot->delete(); }
    foreach (array_reverse($items) as $item) $item->delete();
}
