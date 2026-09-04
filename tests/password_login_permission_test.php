<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/auth.php';

function assertPasswordLoginPermissionTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class PasswordLoginPermissionTestUser
{
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function get(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }
}

$enabledUser = new PasswordLoginPermissionTestUser([
    'password' => password_hash('test-password', PASSWORD_DEFAULT),
    'allow_password_login' => 1,
]);
$calDavOnlyUser = new PasswordLoginPermissionTestUser([
    'password' => password_hash('test-password', PASSWORD_DEFAULT),
    'allow_password_login' => 0,
]);
$passwordlessUser = new PasswordLoginPermissionTestUser([
    'password' => '',
    'allow_password_login' => 1,
]);

assertPasswordLoginPermissionTest(
    commonUserAllowsPasswordLogin($enabledUser),
    'An enabled password must allow web password login.'
);
assertPasswordLoginPermissionTest(
    !commonUserAllowsPasswordLogin($calDavOnlyUser),
    'A CalDAV/CardDAV-only password must not allow web password login.'
);
assertPasswordLoginPermissionTest(
    !commonUserAllowsPasswordLogin($passwordlessUser),
    'The permission must not enable a missing password.'
);

$root = dirname(__DIR__);
$authSource = file_get_contents($root . '/common/auth.php');
$cardDavSource = file_get_contents($root . '/common/carddav.php');
$calDavSource = file_get_contents($root . '/common/caldav.php');
$loginScriptSource = file_get_contents($root . '/common/assets/auth.js');
$profileSource = file_get_contents($root . '/popup/profil_scope.php');
$migrationSource = file_get_contents($root . '/sql/2026-09-04-02-user-password-login-permission.sql');

assertPasswordLoginPermissionTest(
    is_string($authSource)
        && str_contains($authSource, 'if (!commonUserAllowsPasswordLogin($user))')
        && str_contains($authSource, "'error' => 'password_login_disabled'"),
    'The password login handler must enforce the account permission.'
);
assertPasswordLoginPermissionTest(
    is_string($loginScriptSource)
        && str_contains($loginScriptSource, 'setPasswordLoginAvailable(data.password_login_enabled !== false)'),
    'The login screen must hide password login when the account does not allow it.'
);
assertPasswordLoginPermissionTest(
    is_string($profileSource)
        && str_contains($profileSource, 'name="allow_password_login"')
        && str_contains($profileSource, 'type="checkbox"'),
    'The profile form must expose the password login permission.'
);
assertPasswordLoginPermissionTest(
    is_string($cardDavSource)
        && str_contains($cardDavSource, 'commonVerifyUserPassword($password, $passwordHash)')
        && !str_contains($cardDavSource, 'allow_password_login'),
    'CardDAV authentication must remain independent from the web login permission.'
);
assertPasswordLoginPermissionTest(
    is_string($calDavSource) && str_contains($calDavSource, 'return commonCardDavAuthenticateRequest();'),
    'CalDAV must continue to delegate to the CardDAV authentication flow.'
);
assertPasswordLoginPermissionTest(
    is_string($migrationSource)
        && str_starts_with($migrationSource, '-- @migration')
        && str_contains($migrationSource, 'allow_password_login')
        && str_contains($migrationSource, 'DEFAULT 0'),
    'The web password login permission must default to disabled after migration.'
);

echo "password_login_permission_test: OK\n";
