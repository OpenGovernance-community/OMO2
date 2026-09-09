<?php
declare(strict_types=1);

function assertLoginSecondaryEmailGuardTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$userSource = file_get_contents($root . '/class/dbobject/user.class.php');
$authSource = file_get_contents($root . '/common/auth.php');
$authJavaScript = file_get_contents($root . '/common/assets/auth.js');

assertLoginSecondaryEmailGuardTest(
    is_string($userSource)
        && str_contains($userSource, 'public static function isOrganizationEmailInUse')
        && str_contains($userSource, 'FROM user_organization membership')
        && str_contains($userSource, 'INNER JOIN `user` owner')
        && str_contains($userSource, "LOWER(TRIM(membership.email)) = :identity"),
    'Secondary organization emails must be checked through the User dbObject.'
);

$secondaryGuardPosition = strpos((string)$authSource, 'User::isOrganizationEmailInUse($email)');
$challengePosition = strpos((string)$authSource, 'if ($answer === null)', $secondaryGuardPosition === false ? 0 : $secondaryGuardPosition);
$creationPosition = strpos((string)$authSource, '$user->set(\'email\', $email)', $secondaryGuardPosition === false ? 0 : $secondaryGuardPosition);
assertLoginSecondaryEmailGuardTest(
    $secondaryGuardPosition !== false
        && $challengePosition !== false
        && $creationPosition !== false
        && $secondaryGuardPosition < $challengePosition
        && $secondaryGuardPosition < $creationPosition
        && str_contains((string)$authSource, "'error' => 'secondary_email_in_use'"),
    'Magic login must reject a secondary email before proposing or creating a new account.'
);

assertLoginSecondaryEmailGuardTest(
    is_string($authJavaScript)
        && str_contains($authJavaScript, "data.error === 'secondary_email_in_use'")
        && str_contains($authJavaScript, "t('auth.error.secondary_email_in_use')"),
    'The login screen must explain why automatic account creation was blocked.'
);

echo "login_secondary_email_guard_test: OK\n";
