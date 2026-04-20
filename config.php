<?php
require_once __DIR__ . '/includes/env.php';

loadEnv(__DIR__ . '/.env');

// Identite du site
$GLOBALS['siteTitle'] = envValue('SITE_TITLE', 'EasyPV');
$GLOBALS['homeTitle'] = envValue('HOME_TITLE', 'Home');
$GLOBALS['lang'] = envValue('APP_LANG', 'FR');

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

// Autres acces / integrations
$GLOBALS['paypalClientId'] = envValue('PAYPAL_CLIENT_ID', '');
$GLOBALS['telegramToken'] = envValue('TELEGRAM_BOT_TOKEN', '');

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
