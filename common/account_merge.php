<?php

function commonAccountMergeGetCsrfToken()
{
    $token = (string)($_SESSION['account_merge_csrf'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['account_merge_csrf'] = $token;
    }

    return $token;
}

function commonAccountMergeVerifyCsrfToken($token)
{
    $expected = commonAccountMergeGetCsrfToken();

    return is_string($token) && hash_equals($expected, $token);
}

function commonAccountMergeClearState()
{
    unset($_SESSION['account_merge_state']);
}

function commonAccountMergeStoreState(array $state)
{
    $_SESSION['account_merge_state'] = $state;

    return $state;
}

function commonAccountMergeGetState($currentUserId, $requireVerified = false)
{
    $state = $_SESSION['account_merge_state'] ?? null;
    if (!is_array($state)
        || (int)($state['current_user_id'] ?? 0) !== (int)$currentUserId
        || (int)($state['other_user_id'] ?? 0) <= 0
        || (int)($state['expires_at'] ?? 0) < time()
        || !hash_equals((string)($state['request_ip'] ?? ''), (string)commonGetRequestIp())
    ) {
        commonAccountMergeClearState();
        return null;
    }

    if ($requireVerified && (int)($state['verified_at'] ?? 0) <= 0) {
        return null;
    }

    return $state;
}

function commonAccountMergeMarkVerified(array $state, $method)
{
    $state['verified_at'] = time();
    $state['verified_method'] = trim((string)$method);
    $state['phase'] = 'confirm';
    $state['expires_at'] = time() + 600;

    return commonAccountMergeStoreState($state);
}

?>
