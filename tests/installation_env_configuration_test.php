<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/auto_install.php';

function installationEnvExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$definitions = autoInstallGetFieldDefinitions();
$example = autoInstallReadEnvDefaults(dirname(__DIR__) . '/.env.example');
$fields = serverEnvAdminGetFieldMap();
$advancedEnvOnlyKeys = ['MCP_ALLOW_LOCAL_HTTP', 'MCP_ALLOWED_ORIGINS', 'NOTIFICATION_WORKER_PHP_BINARY'];
foreach ($advancedEnvOnlyKeys as $key) {
    installationEnvExpect(array_key_exists($key, $example) && !isset($fields[$key]), 'Advanced settings must stay in .env.example only: ' . $key);
}
foreach ($example as $key => $unused) {
    if (in_array($key, $advancedEnvOnlyKeys, true)) continue;
    installationEnvExpect(isset($fields[$key]), 'Missing editable setting: ' . $key);
    installationEnvExpect($fields[$key]['label'] !== $key, 'Missing metadata for a standard setting: ' . $key);
}
installationEnvExpect(!isset($fields['PAYPAL_CLIENT_ID']), 'Retired integration must not return');
$allKeys = [];
foreach (serverEnvAdminGetEditableSections() as $section) {
    foreach ($section['fields'] as $field) {
        $allKeys[] = $field['key'];
        if (!empty($section['technical'])) installationEnvExpect(!empty($field['help']), 'A technical setting needs contextual help: ' . $field['key']);
    }
}
installationEnvExpect(count($allKeys) === count(array_unique($allKeys)), 'A setting appears in several sections');
foreach (serverEnvAdminGetRetiredKeys() as $key) installationEnvExpect(!isset($fields[$key]), 'Obsolete setting remains editable: ' . $key);
installationEnvExpect(!isset(serverEnvAdminGetEditableSections()['extra']), 'Unknown hosting variables must not be imported into the form');
installationEnvExpect(array_keys($definitions) === ['general', 'database', 'admin', 'mail'], 'Installation must only request essential setup sections');
$setupKeys = [];
foreach ($definitions as $section) foreach ($section['fields'] as $field) $setupKeys[] = $field['key'];
installationEnvExpect($setupKeys === ['SITE_TITLE', 'APP_LANG', 'AUTH_PUBLIC_URL', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'INSTALL_ADMIN_FIRSTNAME', 'INSTALL_ADMIN_LASTNAME', 'INSTALL_ADMIN_EMAIL', 'INSTALL_ADMIN_PASSWORD', 'INSTALL_ADMIN_PASSWORD_CONFIRM', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_SECURE', 'MAIL_AUTH', 'MAIL_USER', 'MAIL_PASS'], 'An optional integration was added to the installer');

$values = array_merge(autoInstallBuildInitialValues($definitions), [
    'AUTH_PUBLIC_URL' => 'https://install.example.org',
    'DB_HOST' => 'mysql.example.org', 'DB_NAME' => 'installation_test', 'DB_USER' => 'installation_user',
    'DB_PASS' => 'database password#with=characters',
    'INSTALL_ADMIN_FIRSTNAME' => 'Install', 'INSTALL_ADMIN_LASTNAME' => 'Test',
    'INSTALL_ADMIN_EMAIL' => 'installation@example.org',
    'INSTALL_ADMIN_PASSWORD' => 'Long-Safe-Password!4962', 'INSTALL_ADMIN_PASSWORD_CONFIRM' => 'Long-Safe-Password!4962',
    'MAIL_HOST' => 'smtp.example.org', 'MAIL_AUTH' => 'false', 'MAIL_USER' => '', 'MAIL_PASS' => '',
]);
$envPath = tempnam(sys_get_temp_dir(), 'omo-install-env-');
installationEnvExpect(is_string($envPath), 'Cannot create isolated environment fixture');
try {
    installationEnvExpect(autoInstallValidateValues($definitions, $values, $envPath) === [], 'Minimum valid setup was rejected');
    installationEnvExpect(autoInstallValidateMailConfiguration($values) === [], 'SMTP without authentication should not require credentials');
    installationEnvExpect(autoInstallValidateMailConfiguration(array_merge($values, ['MAIL_AUTH' => 'true'])) !== [], 'Authenticated SMTP must still require credentials');
    installationEnvExpect(autoInstallValidateMailConfiguration(array_merge($values, ['MAIL_HOST' => ''])) !== [], 'Email verification must remain mandatory');
    installationEnvExpect(empty(autoInstallVerifyOrReuseMailCode($values, '')['status']), 'Installation must still require a verified email code');
    installationEnvExpect(autoInstallValidateValues($definitions, array_merge($values, ['AUTH_PUBLIC_URL' => 'http://install.example.org']), $envPath) !== [], 'A public installation must require HTTPS security links');
    autoInstallWriteEnvFile($envPath, $definitions, $values);
    $installed = autoInstallReadEnvDefaults($envPath);
    installationEnvExpect(array_diff_key($example, $installed) === [], 'Initial .env must include every standard setting');
    installationEnvExpect(array_diff_key($installed, $example) === [], 'Administrator credentials must not be written into .env');
    foreach (['AUTH_PUBLIC_URL', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'MAIL_HOST', 'MAIL_AUTH'] as $key) {
        installationEnvExpect($installed[$key] === $values[$key], 'Installation changed an essential setting: ' . $key);
    }
    foreach (['AI_API_KEY', 'TRANSCRIPTION_API_KEY', 'TELEGRAM_BOT_TOKEN', 'PATREON_CLIENT_ID', 'GITHUB_BUGREPORT_TOKEN', 'ETHERPAD_URL', 'WEB_PUSH_VAPID_SUBJECT'] as $key) {
        installationEnvExpect($installed[$key] === '', 'An unconfigured integration became active: ' . $key);
    }
    $anotherInstallation = autoInstallBuildEnvValues($definitions, $values);
    foreach (['AUTH_RATE_LIMIT_SECRET', 'AUTH_TOTP_ENCRYPTION_KEY', 'EXTERNAL_CALENDAR_ENCRYPTION_KEY'] as $key) {
        installationEnvExpect(preg_match('/^[a-f0-9]{64}$/D', $installed[$key]) === 1, 'Missing generated security secret');
        installationEnvExpect($installed[$key] !== $anotherInstallation[$key], 'Installations must not share generated secrets');
    }
    installationEnvExpect(serverEnvAdminValidateValues($installed) === [], 'New installation must be accepted by the configuration page');
    $current = array_merge(array_fill_keys(array_keys($fields), ''), array_intersect_key($installed, $fields));
    $merged = serverEnvAdminMergeSubmittedValues(serverEnvAdminReadSubmittedValues(['SITE_TITLE' => 'Updated title']), $current);
    foreach ($current as $key => $value) {
        if ($key !== 'SITE_TITLE') installationEnvExpect($merged[$key] === $value, 'Partial settings save lost a value: ' . $key);
    }
    $blankAccess = serverEnvAdminMergeSubmittedValues(serverEnvAdminReadSubmittedValues(['DB_HOST' => '', 'DB_NAME' => '', 'DB_USER' => '', 'DB_PASS' => '', 'AUTH_PUBLIC_URL' => '', 'AUTH_TOTP_ENCRYPTION_KEY' => '']), $current);
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'AUTH_PUBLIC_URL', 'AUTH_TOTP_ENCRYPTION_KEY'] as $key) {
        installationEnvExpect($blankAccess[$key] === $current[$key], 'A blank critical setting must retain its value: ' . $key);
    }
    installationEnvExpect(serverEnvAdminValidateValues([]) === [], 'Optional settings must not be required');
    $disabledMcp = serverEnvAdminMergeSubmittedValues(serverEnvAdminReadSubmittedValues(['MCP_PUBLIC_URL' => '']), array_merge($current, ['MCP_PUBLIC_URL' => 'https://example.org/mcp/']));
    installationEnvExpect($disabledMcp['MCP_PUBLIC_URL'] === '' && serverEnvAdminValidateValues($disabledMcp) === [], 'An empty MCP address must be saved to disable the integration');
    $blankSelect = serverEnvAdminMergeSubmittedValues(['MAIL_AUTH' => ''], $current);
    installationEnvExpect($blankSelect['MAIL_AUTH'] === '', 'An explicitly empty optional choice must remain empty');
    $displayFlags = serverEnvAdminBuildDisplayValues(['MAIL_AUTH' => '0', 'AUTH_RATE_LIMITS_ENABLED' => '1']);
    installationEnvExpect($displayFlags['MAIL_AUTH'] === 'false' && $displayFlags['AUTH_RATE_LIMITS_ENABLED'] === 'true', 'Numeric boolean settings must retain their meaning in selects');
    installationEnvExpect(serverEnvAdminValidateValues(['MAIL_TIMEOUT' => '0']) !== [], 'Invalid timeout must be rejected');
    installationEnvExpect(serverEnvAdminValidateValues(['MAIL_PORT' => '65536']) !== [], 'Invalid SMTP port must be rejected');
    installationEnvExpect(serverEnvAdminValidateValues(['AUTH_SECURITY_ALERT_EMAIL' => 'invalid']) !== [], 'Invalid alert email must be rejected');
    $bytes = file_get_contents($envPath);
    installationEnvExpect(!str_contains($bytes, "\r") && !str_starts_with($bytes, "\xEF\xBB\xBF"), 'Environment output must use LF and no BOM');

    // Exercise the production writer against an isolated file, never the installed .env.
    $writerSource = file_get_contents(dirname(__DIR__) . '/includes/server_env_admin.php');
    $writerStart = strpos($writerSource, 'function serverEnvAdminWriteValues(');
    $writerEnd = strpos($writerSource, 'function serverEnvAdminGetUnlockTtlSeconds()', $writerStart);
    $writerFunction = substr($writerSource, $writerStart, $writerEnd - $writerStart);
    eval(str_replace(['serverEnvAdminWriteValues(', 'serverEnvAdminGetEnvPath()', 'serverEnvAdminGetEnvTargetLabel()'], ['installationEnvWriteFixture(', 'installationEnvFixturePath()', 'installationEnvFixtureLabel()'], $writerFunction));
    $GLOBALS['installationEnvFixturePath'] = $envPath;
    file_put_contents($envPath, "CUSTOM_HOSTING_SETTING=keep\nMCP_ALLOW_LOCAL_HTTP=true\nMCP_ALLOWED_ORIGINS=http://localhost:6274\nNOTIFICATION_WORKER_PHP_BINARY=/usr/local/bin/php\nGITHUB_BUGREPORT_PROJECT_OWNER=obsolete\nexport GITHUB_BUGREPORT_PROJECT_NUMBER=1\nPAYPAL_CLIENT_ID=obsolete\n");
    installationEnvWriteFixture($current);
    $saved = serverEnvAdminReadEnvDefaults($envPath);
    installationEnvExpect($saved['CUSTOM_HOSTING_SETTING'] === 'keep', 'Unregistered hosting variables must still be preserved');
    installationEnvExpect($saved['MCP_ALLOW_LOCAL_HTTP'] === 'true' && $saved['MCP_ALLOWED_ORIGINS'] === 'http://localhost:6274', 'Saving the form must preserve advanced MCP settings in .env');
    installationEnvExpect($saved['NOTIFICATION_WORKER_PHP_BINARY'] === '/usr/local/bin/php', 'Saving the form must preserve the advanced notification PHP executable in .env');
    foreach (serverEnvAdminGetRetiredKeys() as $key) installationEnvExpect(!isset($saved[$key]), 'Retired setting must be removed from existing files');
    foreach ($current as $key => $value) installationEnvExpect($saved[$key] === $value, 'Settings writer lost a registered value: ' . $key);
} finally {
    unlink($envPath);
}

foreach (['https://example.org', 'https://example.org/', 'http://localhost:8080', 'http://127.0.0.1'] as $origin) {
    installationEnvExpect(serverEnvAdminIsValidPublicOrigin($origin), 'Valid security origin rejected');
}
foreach (['', 'http://example.org', 'https://example.org/path', 'https://example.org?x=1', 'https://example.org#fragment', 'https://user@example.org', 'https://bad host.example.org', 'ftp://example.org'] as $origin) {
    installationEnvExpect(!serverEnvAdminIsValidPublicOrigin($origin), 'Unsafe security origin accepted');
}
echo 'PASS minimal installation, editable and advanced environment settings, email verification, per-installation secrets and preservation of critical settings' . PHP_EOL;

function installationEnvFixturePath(): string { return $GLOBALS['installationEnvFixturePath']; }
function installationEnvFixtureLabel(): string { return 'isolated fixture.env'; }
