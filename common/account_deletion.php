<?php

function commonAccountDeletionGetCsrfToken(): string
{
    $token = (string)($_SESSION['account_deletion_csrf'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['account_deletion_csrf'] = $token;
    }

    return $token;
}

function commonAccountDeletionVerifyCsrfToken($token): bool
{
    $expected = commonAccountDeletionGetCsrfToken();
    return is_string($token) && hash_equals($expected, $token);
}

