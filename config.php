<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auto_install.php';

$autoInstallEnvPath = __DIR__ . '/.env';
autoInstallBootstrap($autoInstallEnvPath);

loadEnv($autoInstallEnvPath);

// Identite du site
$GLOBALS['siteTitle'] = envValue('SITE_TITLE', 'EasyPV');
$GLOBALS['homeTitle'] = envValue('HOME_TITLE', 'Home');
$GLOBALS['lang'] = envValue('APP_LANG', 'FR');
$GLOBALS['organizationSubdomainRouting'] = envBool('ORGANIZATION_SUBDOMAIN_ROUTING', false);

// Config base de donnees
$GLOBALS['dbName'] = envValue('DB_NAME', '');
$GLOBALS['dbServer'] = envValue('DB_HOST', '');
$GLOBALS['dbUser'] = envValue('DB_USER', '');
$GLOBALS['dbPassword'] = envValue('DB_PASS', '');

// Config OpenAI
$GLOBALS['OpenAI'] = envValue('OPENAI_API_KEY', '');
$GLOBALS['openAiUploadApiKey'] = envValue('OPENAI_UPLOAD_API_KEY', $GLOBALS['OpenAI']);
$GLOBALS['openAiTranslationModel'] = envValue('OPENAI_TRANSLATION_MODEL', envValue('OPENAI_MODEL', 'gpt-4o'));

// Config du mail
$GLOBALS['mailHost'] = envValue('MAIL_HOST', 'mail.infomaniak.com');
$GLOBALS['mailPort'] = envInt('MAIL_PORT', 587);
$GLOBALS['mailSecure'] = envValue('MAIL_SECURE', 'SSL');
$GLOBALS['mailAuth'] = envBool('MAIL_AUTH', true);
$GLOBALS['mailCharset'] = envValue('MAIL_CHARSET', 'UTF-8');
$GLOBALS['mailUser'] = envValue('MAIL_USER', '');
$GLOBALS['mailPassword'] = envValue('MAIL_PASS', '');
$GLOBALS['mailTimeout'] = max(3, envInt('MAIL_TIMEOUT', 10));

// Autres acces / integrations
$GLOBALS['paypalClientId'] = envValue('PAYPAL_CLIENT_ID', '');
$GLOBALS['telegramToken'] = envValue('TELEGRAM_BOT_TOKEN', '');
$GLOBALS['patreonClientId'] = envValue('PATREON_CLIENT_ID', '');
$GLOBALS['patreonClientSecret'] = envValue('PATREON_CLIENT_SECRET', '');
$GLOBALS['patreonRedirectUri'] = envValue('PATREON_REDIRECT_URI', '');
$GLOBALS['patreonCreatorCampaignId'] = envValue('PATREON_CREATOR_CAMPAIGN_ID', '');
$GLOBALS['patreonUserAgent'] = envValue('PATREON_USER_AGENT', 'EasyPV Patreon Sync');
$GLOBALS['githubBugReportToken'] = envValue('GITHUB_BUGREPORT_TOKEN', '');
$GLOBALS['githubBugReportRepoOwner'] = envValue('GITHUB_BUGREPORT_REPO_OWNER', 'OpenGovernance-community');
$GLOBALS['githubBugReportRepoName'] = envValue('GITHUB_BUGREPORT_REPO_NAME', 'OMO2');
$GLOBALS['githubBugReportLabels'] = envValue('GITHUB_BUGREPORT_LABELS', '');
$GLOBALS['githubBugReportUserAgent'] = envValue('GITHUB_BUGREPORT_USER_AGENT', 'OMO Bug Reporter');

// Constantes historiques
if (!defined('TOKEN')) {
    define('TOKEN', $GLOBALS['telegramToken']);
}

if (!defined('MODEL')) {
    define('MODEL', envValue('OPENAI_MODEL', 'gpt-4o'));
}

if (!defined('OpenAI')) {
    define('OpenAI', $GLOBALS['OpenAI']);
}
