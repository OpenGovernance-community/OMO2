<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';
require_once BASE_PATH . '/common/account_merge.php';
require_once BASE_PATH . '/popup/profil_translation_helper.php';

header('Content-Type: application/json; charset=UTF-8');

function accountMergeRespond(array $payload, $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function accountMergeFail($error, $translationKey, $statusCode = 400, array $variables = array(), array $extra = array())
{
    accountMergeRespond(array_merge(array(
        'status' => false,
        'error' => (string)$error,
        'message' => profilPopupT($translationKey, $variables),
    ), $extra), $statusCode);
}

function accountMergeConfirmationPayload(array $state)
{
    return array(
        'status' => true,
        'phase' => 'confirm',
        'current_email' => (string)($state['current_email'] ?? ''),
        'other_email' => (string)($state['other_email'] ?? ''),
        'current_is_siteadmin' => !empty($state['current_is_siteadmin']),
        'other_is_siteadmin' => !empty($state['other_is_siteadmin']),
    );
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    accountMergeFail('method_not_allowed', 'profile.popup.merge.error.invalid_request', 405);
}

$connected = checklogin();
$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION['currentUser'] ?? 0);
if (!$connected || $currentUserId <= 0) {
    accountMergeFail('login_required', 'profile.popup.error.login_required', 401);
}

if (!commonAccountMergeVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
    accountMergeFail('csrf', 'profile.popup.merge.error.expired', 403);
}

$action = trim((string)($_POST['action'] ?? ''));
if ($action === 'cancel') {
    commonAccountMergeClearState();
    accountMergeRespond(array('status' => true, 'phase' => 'start'));
}

if ($action === 'start') {
    commonAccountMergeClearState();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        accountMergeFail('email', 'profile.popup.merge.error.invalid_email');
    }

    $requestLimit = commonAuthRunLimit('magic_request_ip', 'ip', commonGetRequestIp());
    if (empty($requestLimit['available']) || empty($requestLimit['allowed'])) {
        commonAuthSetLimitHttpResponse($requestLimit);
        accountMergeFail('rate_limited', 'profile.popup.merge.error.rate_limited', 429);
    }

    $otherUser = \dbObject\User::findByLoginIdentifier($email);
    if (!$otherUser || $otherUser->isHistoricalPlaceholder() || (int)$otherUser->getId() === $currentUserId) {
        $isSameAccount = $otherUser && (int)$otherUser->getId() === $currentUserId;
        commonAuthSecurityLog('account_merge_start', 'rejected', array(
            'current_user_id' => $currentUserId,
            'email' => $email,
            'reason' => $isSameAccount ? 'same_account' : 'account_not_found',
        ));
        accountMergeFail(
            $isSameAccount ? 'same_account' : 'account_not_found',
            $isSameAccount ? 'profile.popup.merge.error.same_account' : 'profile.popup.merge.error.account_not_found'
        );
    }

    $deliveryLimit = commonAuthEmailDeliveryLimits('magic', $email);
    if (empty($deliveryLimit['available']) || empty($deliveryLimit['allowed'])) {
        commonAuthSetLimitHttpResponse($deliveryLimit);
        accountMergeFail('rate_limited', 'profile.popup.merge.error.rate_limited', 429);
    }

    $organizationContext = commonResolveOrganizationContext(1);
    $returnTo = '/popup/profil.php?tab=tools';
    $loginRequest = commonSendLoginCode(
        (int)$otherUser->getId(),
        (string)$otherUser->get('email'),
        $organizationContext,
        0,
        $returnTo,
        '/popup/profil.php?tab=tools'
    );
    commonStorePendingLoginToken(null);
    if ($loginRequest === false) {
        commonAuthSecurityLog('account_merge_code', 'failed', array(
            'current_user_id' => $currentUserId,
            'other_user_id' => (int)$otherUser->getId(),
        ));
        accountMergeFail('send_failed', 'profile.popup.merge.error.send_failed', 503);
    }

    $state = array(
        'current_user_id' => $currentUserId,
        'other_user_id' => (int)$otherUser->getId(),
        'current_email' => '',
        'other_email' => (string)$otherUser->get('email'),
        'current_is_siteadmin' => false,
        'other_is_siteadmin' => $otherUser->isSiteAdmin(),
        'login_token' => (string)$loginRequest['request_token'],
        'request_ip' => commonGetRequestIp(),
        'expires_at' => time() + 600,
        'verified_at' => 0,
        'verified_method' => '',
        'phase' => 'code',
        'password_login_enabled' => commonUserAllowsPasswordLogin($otherUser),
    );
    $currentUser = new \dbObject\User();
    if ($currentUser->load($currentUserId)) {
        $state['current_email'] = (string)$currentUser->get('email');
        $state['current_is_siteadmin'] = $currentUser->isSiteAdmin();
    }
    commonAccountMergeStoreState($state);
    commonAuthSecurityLog('account_merge_code', 'sent', array(
        'current_user_id' => $currentUserId,
        'other_user_id' => (int)$otherUser->getId(),
        'delivery_uncertain' => !empty($loginRequest['delivery_failed']),
    ));

    accountMergeRespond(array(
        'status' => true,
        'phase' => 'code',
        'request_token' => (string)$loginRequest['request_token'],
        'password_login_enabled' => commonUserAllowsPasswordLogin($otherUser),
        'delivery_uncertain' => !empty($loginRequest['delivery_failed']),
        'message' => profilPopupT(
            !empty($loginRequest['delivery_failed'])
                ? 'profile.popup.merge.status.code_pending'
                : 'profile.popup.merge.status.code_sent'
        ),
    ));
}

$state = commonAccountMergeGetState($currentUserId);
if (!is_array($state)) {
    accountMergeFail('expired', 'profile.popup.merge.error.expired', 409);
}

$otherUser = new \dbObject\User();
if (!$otherUser->load((int)$state['other_user_id'])) {
    commonAccountMergeClearState();
    accountMergeFail('account_not_found', 'profile.popup.merge.error.account_not_found', 404);
}

if ($action === 'verify_code') {
    $token = (string)($_POST['token'] ?? '');
    $code = commonNormalizeLoginCode($_POST['code'] ?? '');
    if ($token === '' || $code === '' || !hash_equals((string)$state['login_token'], $token)) {
        accountMergeFail('missing_code', 'profile.popup.merge.error.missing_code');
    }

    $loginToken = \dbObject\UserLoginToken::findByToken($token);
    if (!$loginToken
        || (int)$loginToken->get('IDuser') !== (int)$state['other_user_id']
        || (int)$loginToken->get('used') > 0
        || !($loginToken->get('expires_at') instanceof \DateTimeInterface)
        || $loginToken->get('expires_at') <= new \DateTime()
        || (int)$loginToken->get('attempt_count') >= 5
        || (string)$loginToken->get('request_ip') !== commonGetRequestIp()
    ) {
        commonAccountMergeClearState();
        accountMergeFail('expired', 'profile.popup.merge.error.expired', 409);
    }

    if (!password_verify($code, (string)$loginToken->get('code_hash'))) {
        $loginToken->incrementAttemptCount();
        $limits = commonAuthRunLimits(array(
            array('policy' => 'otp_ip', 'kind' => 'ip', 'value' => commonGetRequestIp()),
            array('policy' => 'otp_account', 'kind' => 'user', 'value' => (string)$state['other_user_id']),
        ), 'failure');
        $remaining = max(0, 5 - (int)$loginToken->get('attempt_count'));
        commonAuthSecurityLog('account_merge_code_verify', 'failed', array(
            'current_user_id' => $currentUserId,
            'other_user_id' => (int)$state['other_user_id'],
            'remaining_attempts' => $remaining,
        ));
        if (empty($limits['available']) || empty($limits['allowed'])) {
            commonAuthSetLimitHttpResponse($limits);
            accountMergeFail('rate_limited', 'profile.popup.merge.error.rate_limited', 429);
        }
        accountMergeFail(
            $remaining > 0 ? 'wrong_code' : 'locked',
            $remaining > 0 ? 'profile.popup.merge.error.wrong_code' : 'profile.popup.merge.error.locked',
            $remaining > 0 ? 422 : 429,
            array('count' => $remaining),
            array('remaining_attempts' => $remaining)
        );
    }

    $mfaResult = commonBeginTotpLogin($otherUser, commonGetRequestIp(), 0, $loginToken);
    if (($mfaResult['error'] ?? '') === 'mfa_required') {
        $state['login_token'] = (string)$mfaResult['mfa_token'];
        $state['phase'] = 'totp';
        commonAccountMergeStoreState($state);
        accountMergeRespond(array(
            'status' => true,
            'phase' => 'totp',
            'mfa_token' => (string)$mfaResult['mfa_token'],
            'message' => profilPopupT('profile.popup.merge.status.mfa_required'),
        ));
    }
    if (empty($mfaResult['status'])) {
        $loginToken->markUsed();
        accountMergeFail('mfa_unavailable', 'profile.popup.merge.error.mfa_unavailable', 503);
    }

    $loginToken->markUsed();
    commonAuthClearLimit('otp_account', 'user', (string)$state['other_user_id']);
    $state = commonAccountMergeMarkVerified($state, 'code');
    commonAuthSecurityLog('account_merge_code_verify', 'success', array(
        'current_user_id' => $currentUserId,
        'other_user_id' => (int)$state['other_user_id'],
    ));
    accountMergeRespond(accountMergeConfirmationPayload($state));
}

if ($action === 'verify_password') {
    $password = (string)($_POST['password'] ?? '');
    if ($password === '') {
        accountMergeFail('missing_password', 'profile.popup.merge.error.missing_password');
    }

    $email = mb_strtolower(trim((string)$state['other_email']), 'UTF-8');
    $limits = array(
        array('policy' => 'password_account', 'kind' => 'email', 'value' => $email),
        array('policy' => 'password_ip', 'kind' => 'ip', 'value' => commonGetRequestIp()),
    );
    $currentLimit = commonAuthRunLimits($limits, 'inspect');
    if (empty($currentLimit['available']) || empty($currentLimit['allowed'])) {
        commonAuthSetLimitHttpResponse($currentLimit);
        accountMergeFail('rate_limited', 'profile.popup.merge.error.rate_limited', 429);
    }

    if (!commonVerifyUserPassword($password, (string)$otherUser->get('password'))) {
        $failedLimit = commonAuthRunLimits($limits, 'failure');
        commonAuthSecurityLog('account_merge_password_verify', 'failed', array(
            'current_user_id' => $currentUserId,
            'other_user_id' => (int)$state['other_user_id'],
        ));
        if (empty($failedLimit['available']) || empty($failedLimit['allowed'])) {
            commonAuthSetLimitHttpResponse($failedLimit);
            accountMergeFail('rate_limited', 'profile.popup.merge.error.rate_limited', 429);
        }
        accountMergeFail('invalid_credentials', 'profile.popup.merge.error.invalid_password', 422);
    }
    if (!commonUserAllowsPasswordLogin($otherUser)) {
        accountMergeFail('password_login_disabled', 'profile.popup.merge.error.password_disabled', 409);
    }

    commonAuthClearLimit('password_account', 'email', $email);
    $mfaResult = commonBeginTotpLogin($otherUser, commonGetRequestIp(), 0);
    if (($mfaResult['error'] ?? '') === 'mfa_required') {
        $state['login_token'] = (string)$mfaResult['mfa_token'];
        $state['phase'] = 'totp';
        commonAccountMergeStoreState($state);
        accountMergeRespond(array(
            'status' => true,
            'phase' => 'totp',
            'mfa_token' => (string)$mfaResult['mfa_token'],
            'message' => profilPopupT('profile.popup.merge.status.mfa_required'),
        ));
    }
    if (empty($mfaResult['status'])) {
        accountMergeFail('mfa_unavailable', 'profile.popup.merge.error.mfa_unavailable', 503);
    }

    $state = commonAccountMergeMarkVerified($state, 'password');
    commonAuthSecurityLog('account_merge_password_verify', 'success', array(
        'current_user_id' => $currentUserId,
        'other_user_id' => (int)$state['other_user_id'],
    ));
    accountMergeRespond(accountMergeConfirmationPayload($state));
}

if ($action === 'verify_totp') {
    $token = (string)($_POST['token'] ?? '');
    $code = preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''));
    if ($token === '' || !preg_match('/^\d{6}$/', $code) || !hash_equals((string)$state['login_token'], $token)) {
        accountMergeFail('missing_mfa_code', 'profile.popup.merge.error.missing_mfa_code');
    }

    $loginToken = \dbObject\UserLoginToken::findByToken($token);
    if (!$loginToken
        || (int)$loginToken->get('IDuser') !== (int)$state['other_user_id']
        || (int)$loginToken->get('used') > 0
        || !(bool)$loginToken->get('mfa_pending')
        || !($loginToken->get('expires_at') instanceof \DateTimeInterface)
        || $loginToken->get('expires_at') <= new \DateTime()
        || (string)$loginToken->get('request_ip') !== commonGetRequestIp()
    ) {
        commonAccountMergeClearState();
        accountMergeFail('expired', 'profile.popup.merge.error.expired', 409);
    }

    $secret = commonUserGetTotpSecret($otherUser);
    if ($secret === null) {
        $loginToken->markUsed();
        accountMergeFail('mfa_unavailable', 'profile.popup.merge.error.mfa_unavailable', 503);
    }
    if ((int)$loginToken->get('mfa_attempt_count') >= 5 || !commonTotpVerifyCode($secret, $code)) {
        $loginToken->incrementMfaAttemptCount();
        $limits = commonAuthRunLimits(array(
            array('policy' => 'otp_ip', 'kind' => 'ip', 'value' => commonGetRequestIp()),
            array('policy' => 'otp_account', 'kind' => 'user', 'value' => (string)$state['other_user_id']),
        ), 'failure');
        $remaining = max(0, 5 - (int)$loginToken->get('mfa_attempt_count'));
        if (empty($limits['available']) || empty($limits['allowed'])) {
            commonAuthSetLimitHttpResponse($limits);
            accountMergeFail('rate_limited', 'profile.popup.merge.error.rate_limited', 429);
        }
        if ($remaining <= 0) {
            $loginToken->markUsed();
        }
        accountMergeFail(
            $remaining > 0 ? 'wrong_mfa_code' : 'locked',
            $remaining > 0 ? 'profile.popup.merge.error.wrong_mfa_code' : 'profile.popup.merge.error.locked',
            $remaining > 0 ? 422 : 429,
            array('count' => $remaining),
            array('remaining_attempts' => $remaining)
        );
    }

    $loginToken->markUsed();
    commonAuthClearLimit('otp_account', 'user', (string)$state['other_user_id']);
    $state = commonAccountMergeMarkVerified($state, 'totp');
    commonAuthSecurityLog('account_merge_totp_verify', 'success', array(
        'current_user_id' => $currentUserId,
        'other_user_id' => (int)$state['other_user_id'],
    ));
    accountMergeRespond(accountMergeConfirmationPayload($state));
}

if ($action === 'complete') {
    $state = commonAccountMergeGetState($currentUserId, true);
    if (!is_array($state)) {
        accountMergeFail('expired', 'profile.popup.merge.error.expired', 409);
    }
    if ((string)($_POST['confirm'] ?? '') !== '1') {
        accountMergeFail('confirmation_required', 'profile.popup.merge.error.confirmation_required');
    }

    $keep = (string)($_POST['keep'] ?? 'current');
    if (!in_array($keep, array('current', 'other'), true)) {
        accountMergeFail('invalid_choice', 'profile.popup.merge.error.invalid_choice');
    }

    $keptUserId = $keep === 'other' ? (int)$state['other_user_id'] : $currentUserId;
    $removedUserId = $keep === 'other' ? $currentUserId : (int)$state['other_user_id'];
    $result = \dbObject\User::mergeAccounts($keptUserId, $removedUserId);
    if (empty($result['status'])) {
        commonAuthSecurityLog('account_merge_complete', 'failed', array(
            'kept_user_id' => $keptUserId,
            'removed_user_id' => $removedUserId,
            'reason' => (string)($result['message'] ?? ''),
        ));
        accountMergeFail('merge_failed', 'profile.popup.merge.error.merge_failed', 500);
    }

    $keptUserId = (int)($result['kept_user_id'] ?? $keptUserId);
    $removedUserId = (int)($result['removed_user_id'] ?? $removedUserId);

    if ($removedUserId === $currentUserId) {
        commonExpireCookieValue(commonGetRememberCookieName(), true);
    }
    commonCompleteInteractiveLogin($keptUserId, 0, commonGetRequestIp());
    unset($_SESSION['userRef']);
    commonAccountMergeClearState();
    commonAuthSecurityLog('account_merge_complete', 'success', array(
        'kept_user_id' => $keptUserId,
        'removed_user_id' => $removedUserId,
        'summary' => $result['summary'] ?? array(),
    ));
    accountMergeRespond(array(
        'status' => true,
        'phase' => 'complete',
        'reload' => true,
        'message' => profilPopupT('profile.popup.merge.status.complete'),
    ));
}

accountMergeFail('invalid_action', 'profile.popup.merge.error.invalid_request', 400);

?>
