<?php
require_once __DIR__ . '/env.php';

function serverEnvAdminT($key, $fallback, array $replace = [])
{
    if (function_exists('omoServerEnvT')) {
        return omoServerEnvT($key, $replace);
    }

    return strtr((string)$fallback, array_map(function ($value) {
        return (string)$value;
    }, array_combine(
        array_map(function ($name) {
            return '{' . $name . '}';
        }, array_keys($replace)),
        array_values($replace)
    ) ?: []));
}

function serverEnvAdminGetEnvPath()
{
    if (function_exists('envIsLocalRuntimeHost') && envIsLocalRuntimeHost()) {
        return envGetLocalOverrideEnvPath();
    }

    return envGetPrimaryEnvPath();
}

function serverEnvAdminGetExampleEnvPath()
{
    return dirname(__DIR__) . '/.env.example';
}

function serverEnvAdminGetEnvTargetLabel()
{
    $projectRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $envPath = str_replace('\\', '/', serverEnvAdminGetEnvPath());

    if ($projectRoot !== '' && strpos($envPath, $projectRoot . '/') === 0) {
        return substr($envPath, strlen($projectRoot) + 1);
    }

    return basename($envPath);
}

function serverEnvAdminGetEditableSections()
{
    static $sections = null;

    if ($sections !== null) {
        return $sections;
    }

    $sections = [
        'general' => [
            'title' => serverEnvAdminT('parameters.server_env.section.general.title', 'Parametres generaux'),
            'intro' => serverEnvAdminT('parameters.server_env.section.general.intro', 'Reglages globaux du site visibles sur plusieurs pages.'),
            'fields' => [
                [
                    'key' => 'SITE_TITLE',
                    'label' => serverEnvAdminT('parameters.server_env.field.SITE_TITLE.label', 'Titre du site'),
                    'type' => 'text',
                ],
                [
                    'key' => 'HOME_TITLE',
                    'label' => serverEnvAdminT('parameters.server_env.field.HOME_TITLE.label', 'Titre de la page d accueil'),
                    'type' => 'text',
                ],
                [
                    'key' => 'APP_LANG',
                    'label' => serverEnvAdminT('parameters.server_env.field.APP_LANG.label', 'Langue par defaut'),
                    'type' => 'select',
                    'options' => [
                        'FR' => 'FR',
                        'EN' => 'EN',
                    ],
                ],
                [
                    'key' => 'ORGANIZATION_SUBDOMAIN_ROUTING',
                    'label' => serverEnvAdminT('parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.label', 'Sous-domaines par organisation'),
                    'type' => 'select',
                    'options' => [
                        'true' => serverEnvAdminT('parameters.server_env.option.boolean.true', 'Oui'),
                        'false' => serverEnvAdminT('parameters.server_env.option.boolean.false', 'Non'),
                    ],
                    'help' => serverEnvAdminT('parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.help', 'Active les URL du type orgname.domaine.com. Cela demande une configuration speciale de l hebergement, avec DNS wildcard et serveur web capable d accepter les sous-domaines.'),
                ],
                [
                    'key' => 'COOKIE_SCOPE_MODE',
                    'label' => serverEnvAdminT('parameters.server_env.field.COOKIE_SCOPE_MODE.label', 'Portee des cookies'),
                    'type' => 'select',
                    'options' => [
                        'auto' => 'Auto',
                        'host' => 'Host',
                        'environment' => 'Environment',
                        'parent' => 'Parent',
                    ],
                    'help' => serverEnvAdminT('parameters.server_env.field.COOKIE_SCOPE_MODE.help', 'Auto isole par defaut dev, beta et deploy en host-only. Environment partage dans *.dev.domaine.tld. Parent partage dans *.domaine.tld. Host force un cookie limite au host courant.'),
                ],
                [
                    'key' => 'COOKIE_ROOT_HOST',
                    'label' => serverEnvAdminT('parameters.server_env.field.COOKIE_ROOT_HOST.label', 'Racine cookies'),
                    'type' => 'text',
                    'placeholder' => 'dev.opengov.tools',
                    'help' => serverEnvAdminT('parameters.server_env.field.COOKIE_ROOT_HOST.help', 'Optionnel. Si renseigne, force le partage des cookies a cette racine exacte, par exemple dev.opengov.tools pour partager entre dev.opengov.tools et *.dev.opengov.tools sans toucher a la prod.'),
                ],
            ],
        ],
        'etherpad' => [
            'title' => serverEnvAdminT('parameters.server_env.section.etherpad.title', 'Etherpad'),
            'intro' => serverEnvAdminT('parameters.server_env.section.etherpad.intro', 'Connexion globale au serveur Etherpad utilise par les documents collaboratifs.'),
            'fields' => [
                [
                    'key' => 'ETHERPAD_URL',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_URL.label', 'URL du serveur Etherpad'),
                    'type' => 'url',
                    'placeholder' => 'https://doc.opengov.tools',
                    'help' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_URL.help', 'Adresse de base du serveur Etherpad, sans /p/ ni /api/.'),
                ],
                [
                    'key' => 'ETHERPAD_API_KEY',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_API_KEY.label', 'Cle API Etherpad'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
                [
                    'key' => 'ETHERPAD_API_VERSION',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_API_VERSION.label', 'Version de l API Etherpad'),
                    'type' => 'text',
                    'placeholder' => '1.3.1',
                    'help' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_API_VERSION.help', 'Version renvoyee par le point d entree /api du serveur Etherpad.'),
                ],
                [
                    'key' => 'ETHERPAD_COOKIE_DOMAIN',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_COOKIE_DOMAIN.label', 'Domaine de partage des cookies'),
                    'type' => 'text',
                    'placeholder' => '.opengov.tools',
                    'help' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_COOKIE_DOMAIN.help', 'Domaine commun a OMO et Etherpad, par exemple .opengov.tools. Laissez vide seulement si les deux utilisent exactement le meme host.'),
                ],
            ],
        ],
        'mail' => [
            'title' => serverEnvAdminT('parameters.server_env.section.mail.title', 'E-mail'),
            'intro' => serverEnvAdminT('parameters.server_env.section.mail.intro', 'Configuration SMTP generale du serveur.'),
            'fields' => [
                [
                    'key' => 'MAIL_HOST',
                    'label' => serverEnvAdminT('parameters.server_env.field.MAIL_HOST.label', 'Serveur SMTP'),
                    'type' => 'text',
                ],
                [
                    'key' => 'MAIL_PORT',
                    'label' => serverEnvAdminT('parameters.server_env.field.MAIL_PORT.label', 'Port SMTP'),
                    'type' => 'number',
                ],
                [
                    'key' => 'MAIL_SECURE',
                    'label' => serverEnvAdminT('parameters.server_env.field.MAIL_SECURE.label', 'Securite SMTP'),
                    'type' => 'text',
                    'placeholder' => serverEnvAdminT('parameters.server_env.field.MAIL_SECURE.placeholder', 'SSL, tls ou vide'),
                ],
                [
                    'key' => 'MAIL_AUTH',
                    'label' => serverEnvAdminT('parameters.server_env.field.MAIL_AUTH.label', 'Authentification SMTP'),
                    'type' => 'select',
                    'options' => [
                        'true' => serverEnvAdminT('parameters.server_env.option.boolean.true', 'Oui'),
                        'false' => serverEnvAdminT('parameters.server_env.option.boolean.false', 'Non'),
                    ],
                ],
                [
                    'key' => 'MAIL_CHARSET',
                    'label' => serverEnvAdminT('parameters.server_env.field.MAIL_CHARSET.label', 'Jeu de caracteres e-mail'),
                    'type' => 'text',
                ],
                [
                    'key' => 'MAIL_USER',
                    'label' => serverEnvAdminT('parameters.server_env.field.MAIL_USER.label', 'Utilisateur SMTP'),
                    'type' => 'text',
                ],
                [
                    'key' => 'MAIL_PASS',
                    'label' => serverEnvAdminT('parameters.server_env.field.MAIL_PASS.label', 'Mot de passe SMTP'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
            ],
        ],
        'ai' => [
            'title' => serverEnvAdminT('parameters.server_env.section.ai.title', 'IA'),
            'intro' => serverEnvAdminT('parameters.server_env.section.ai.intro', 'Cles et modeles utilises par les fonctions OpenAI.'),
            'fields' => [
                [
                    'key' => 'OPENAI_API_KEY',
                    'label' => serverEnvAdminT('parameters.server_env.field.OPENAI_API_KEY.label', 'Cle OpenAI'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
                [
                    'key' => 'OPENAI_UPLOAD_API_KEY',
                    'label' => serverEnvAdminT('parameters.server_env.field.OPENAI_UPLOAD_API_KEY.label', 'Cle OpenAI upload'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
                [
                    'key' => 'OPENAI_MODEL',
                    'label' => serverEnvAdminT('parameters.server_env.field.OPENAI_MODEL.label', 'Modele OpenAI'),
                    'type' => 'text',
                ],
                [
                    'key' => 'OPENAI_TRANSLATION_MODEL',
                    'label' => serverEnvAdminT('parameters.server_env.field.OPENAI_TRANSLATION_MODEL.label', 'Modele de traduction OpenAI'),
                    'type' => 'text',
                ],
                [
                    'key' => 'STADIA_MAPS_API_KEY',
                    'label' => serverEnvAdminT('parameters.server_env.field.STADIA_MAPS_API_KEY.label', 'Cle Stadia Maps'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
            ],
        ],
        'integrations' => [
            'title' => serverEnvAdminT('parameters.server_env.section.integrations.title', 'Integrations'),
            'intro' => serverEnvAdminT('parameters.server_env.section.integrations.intro', 'Services externes optionnels du serveur.'),
            'fields' => [
                [
                    'key' => 'PAYPAL_CLIENT_ID',
                    'label' => serverEnvAdminT('parameters.server_env.field.PAYPAL_CLIENT_ID.label', 'Client ID PayPal'),
                    'type' => 'text',
                ],
                [
                    'key' => 'TELEGRAM_BOT_TOKEN',
                    'label' => serverEnvAdminT('parameters.server_env.field.TELEGRAM_BOT_TOKEN.label', 'Token Telegram'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
                [
                    'key' => 'PATREON_CLIENT_ID',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CLIENT_ID.label', 'Client ID Patreon'),
                    'type' => 'text',
                ],
                [
                    'key' => 'PATREON_CLIENT_SECRET',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CLIENT_SECRET.label', 'Client secret Patreon'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
                [
                    'key' => 'PATREON_REDIRECT_URI',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_REDIRECT_URI.label', 'Redirect URI Patreon'),
                    'type' => 'text',
                ],
                [
                    'key' => 'PATREON_CREATOR_CAMPAIGN_ID',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CREATOR_CAMPAIGN_ID.label', 'Campaign ID Patreon'),
                    'type' => 'text',
                ],
                [
                    'key' => 'PATREON_USER_AGENT',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_USER_AGENT.label', 'User-Agent Patreon'),
                    'type' => 'text',
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_TOKEN',
                    'label' => serverEnvAdminT('parameters.server_env.field.GITHUB_BUGREPORT_TOKEN.label', 'Token GitHub bug report'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_REPO_OWNER',
                    'label' => serverEnvAdminT('parameters.server_env.field.GITHUB_BUGREPORT_REPO_OWNER.label', 'Repository owner GitHub'),
                    'type' => 'text',
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_REPO_NAME',
                    'label' => serverEnvAdminT('parameters.server_env.field.GITHUB_BUGREPORT_REPO_NAME.label', 'Repository name GitHub'),
                    'type' => 'text',
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_LABELS',
                    'label' => serverEnvAdminT('parameters.server_env.field.GITHUB_BUGREPORT_LABELS.label', 'Labels GitHub'),
                    'type' => 'text',
                    'placeholder' => 'bug,triage',
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_USER_AGENT',
                    'label' => serverEnvAdminT('parameters.server_env.field.GITHUB_BUGREPORT_USER_AGENT.label', 'User-Agent GitHub'),
                    'type' => 'text',
                ],
            ],
        ],
    ];

    $defaults = serverEnvAdminReadEnvDefaults(serverEnvAdminGetExampleEnvPath());
    foreach ($sections as $sectionKey => $section) {
        foreach ($section['fields'] as $fieldIndex => $field) {
            $key = (string)$field['key'];
            $sections[$sectionKey]['fields'][$fieldIndex]['default'] = (string)($defaults[$key] ?? '');
        }
    }

    return $sections;
}

function serverEnvAdminGetFieldMap()
{
    static $fieldMap = null;

    if ($fieldMap !== null) {
        return $fieldMap;
    }

    $fieldMap = [];
    foreach (serverEnvAdminGetEditableSections() as $section) {
        foreach ($section['fields'] as $field) {
            $fieldMap[(string)$field['key']] = $field;
        }
    }

    return $fieldMap;
}

function serverEnvAdminReadEnvDefaults($path)
{
    $defaults = [];

    if (!is_string($path) || $path === '' || !is_file($path)) {
        return $defaults;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return $defaults;
    }

    foreach ($lines as $line) {
        $parsed = serverEnvAdminParseAssignmentLine($line);
        if ($parsed === null) {
            continue;
        }

        $defaults[$parsed['key']] = $parsed['value'];
    }

    return $defaults;
}

function serverEnvAdminParseAssignmentLine($line)
{
    $line = trim((string)$line);
    if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
        return null;
    }

    list($key, $value) = explode('=', $line, 2);
    $key = trim((string)$key);
    $value = trim((string)$value);

    if ($key === '') {
        return null;
    }

    return [
        'key' => $key,
        'value' => serverEnvAdminNormalizeStoredValue($value),
    ];
}

function serverEnvAdminNormalizeStoredValue($value)
{
    $value = trim((string)$value);
    $firstChar = substr($value, 0, 1);
    $lastChar = substr($value, -1);

    if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
        return substr($value, 1, -1);
    }

    return $value;
}

function serverEnvAdminBuildCurrentValues()
{
    $values = [];

    foreach (serverEnvAdminGetFieldMap() as $key => $field) {
        $default = (string)($field['default'] ?? '');
        $values[$key] = (string)envValue($key, $default);
    }

    return $values;
}

function serverEnvAdminBuildDisplayValues(array $actualValues)
{
    $displayValues = [];

    foreach (serverEnvAdminGetFieldMap() as $key => $field) {
        if (!empty($field['secret'])) {
            $displayValues[$key] = '';
            continue;
        }

        $displayValues[$key] = (string)($actualValues[$key] ?? '');
    }

    return $displayValues;
}

function serverEnvAdminBuildSecretStateMap(array $actualValues)
{
    $states = [];

    foreach (serverEnvAdminGetFieldMap() as $key => $field) {
        if (empty($field['secret'])) {
            continue;
        }

        $states[$key] = trim((string)($actualValues[$key] ?? '')) !== '';
    }

    return $states;
}

function serverEnvAdminReadSubmittedValues(array $source)
{
    $values = [];

    foreach (serverEnvAdminGetFieldMap() as $key => $field) {
        $value = array_key_exists($key, $source) ? (string)$source[$key] : '';
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = trim($value);
        $values[$key] = str_replace("\n", ' ', $value);
    }

    return $values;
}

function serverEnvAdminMergeSubmittedValues(array $submittedValues, array $currentValues)
{
    $mergedValues = [];

    foreach (serverEnvAdminGetFieldMap() as $key => $field) {
        $submittedValue = (string)($submittedValues[$key] ?? '');
        $currentValue = (string)($currentValues[$key] ?? '');

        if (!empty($field['secret']) && $submittedValue === '') {
            $mergedValues[$key] = $currentValue;
            continue;
        }

        $mergedValues[$key] = $submittedValue;
    }

    return $mergedValues;
}

function serverEnvAdminValidateValues(array $values)
{
    $errors = [];

    $fieldMap = serverEnvAdminGetFieldMap();

    foreach (['APP_LANG', 'MAIL_AUTH'] as $key) {
        if (!isset($fieldMap[$key]['options'])) {
            continue;
        }

        $value = (string)($values[$key] ?? '');
        if ($value === '') {
            continue;
        }

        if (!array_key_exists($value, $fieldMap[$key]['options'])) {
            $errors[] = serverEnvAdminT(
                'parameters.server_env.error.invalid_field_value',
                'La valeur choisie pour {label} est invalide.',
                ['label' => $fieldMap[$key]['label']]
            );
        }
    }

    if (isset($values['MAIL_PORT']) && $values['MAIL_PORT'] !== '' && !ctype_digit((string)$values['MAIL_PORT'])) {
        $errors[] = 'Le port SMTP doit etre numerique.';
    }

    foreach (array('ETHERPAD_URL') as $etherpadUrlKey) {
        $etherpadUrl = trim((string)($values[$etherpadUrlKey] ?? ''));
        if ($etherpadUrl === '') {
            continue;
        }

        $parsedEtherpadUrl = parse_url($etherpadUrl);
        $etherpadScheme = is_array($parsedEtherpadUrl) ? strtolower((string)($parsedEtherpadUrl['scheme'] ?? '')) : '';
        $etherpadHost = is_array($parsedEtherpadUrl) ? trim((string)($parsedEtherpadUrl['host'] ?? '')) : '';
        if (
            !is_array($parsedEtherpadUrl)
            || !in_array($etherpadScheme, ['http', 'https'], true)
            || $etherpadHost === ''
            || isset($parsedEtherpadUrl['user'])
            || isset($parsedEtherpadUrl['pass'])
            || isset($parsedEtherpadUrl['query'])
            || isset($parsedEtherpadUrl['fragment'])
        ) {
            $errors[] = serverEnvAdminT(
                'parameters.server_env.error.invalid_etherpad_url',
                'L URL Etherpad doit etre une adresse http ou https valide.'
            );
        }
    }

    $etherpadApiVersion = trim((string)($values['ETHERPAD_API_VERSION'] ?? ''));
    if ($etherpadApiVersion !== '' && preg_match('/^[0-9]+(?:\.[0-9]+)*$/', $etherpadApiVersion) !== 1) {
        $errors[] = serverEnvAdminT(
            'parameters.server_env.error.invalid_etherpad_api_version',
            'La version de l API Etherpad doit etre numerique, par exemple 1.3.1.'
        );
    }

    $patreonRedirect = trim((string)($values['PATREON_REDIRECT_URI'] ?? ''));
    if ($patreonRedirect !== '' && preg_match('#^https?://#i', $patreonRedirect) !== 1) {
        $errors[] = 'La Redirect URI Patreon doit etre une URL absolue.';
    }

    $envPath = serverEnvAdminGetEnvPath();
    $targetLabel = serverEnvAdminGetEnvTargetLabel();
    $directory = dirname($envPath);
    if (!is_dir($directory) || !is_writable($directory)) {
        $errors[] = 'Le dossier contenant le fichier ' . $targetLabel . ' n est pas accessible en ecriture.';
    }

    return $errors;
}

function serverEnvAdminEncodeEnvValue($value)
{
    $value = str_replace(["\r", "\n"], ' ', (string)$value);

    if ($value === '') {
        return '';
    }

    if (preg_match('/\s|#|=|"|\'/', $value) === 1) {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    return $value;
}

function serverEnvAdminWriteValues(array $values)
{
    $envPath = serverEnvAdminGetEnvPath();
    $targetLabel = serverEnvAdminGetEnvTargetLabel();
    $lines = is_file($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];
    if ($lines === false) {
        throw new RuntimeException(serverEnvAdminT(
            'parameters.server_env.error.read_failed',
            'Impossible de lire le fichier {target}.',
            ['target' => $targetLabel]
        ));
    }

    $fieldMap = serverEnvAdminGetFieldMap();
    $lineIndexesByKey = [];

    foreach ($lines as $lineIndex => $line) {
        $parsed = serverEnvAdminParseAssignmentLine($line);
        if ($parsed === null) {
            continue;
        }

        $key = $parsed['key'];
        if (!isset($fieldMap[$key])) {
            continue;
        }

        if (!isset($lineIndexesByKey[$key])) {
            $lineIndexesByKey[$key] = [];
        }

        $lineIndexesByKey[$key][] = $lineIndex;
    }

    foreach ($fieldMap as $key => $field) {
        if (!isset($lineIndexesByKey[$key])) {
            continue;
        }

        foreach ($lineIndexesByKey[$key] as $lineIndex) {
            $lines[$lineIndex] = $key . '=' . serverEnvAdminEncodeEnvValue((string)($values[$key] ?? ''));
        }
    }

    $sections = serverEnvAdminGetEditableSections();
    foreach ($sections as $section) {
        $missingFields = [];

        foreach ($section['fields'] as $field) {
            $key = (string)$field['key'];
            if (!isset($lineIndexesByKey[$key])) {
                $missingFields[] = $field;
            }
        }

        if ($missingFields === []) {
            continue;
        }

        if ($lines !== [] && trim((string)end($lines)) !== '') {
            $lines[] = '';
        }

        $lines[] = '# ' . $section['title'];
        foreach ($missingFields as $field) {
            $key = (string)$field['key'];
            $lines[] = $key . '=' . serverEnvAdminEncodeEnvValue((string)($values[$key] ?? ''));
        }
    }

    $content = rtrim(implode("\n", $lines)) . "\n";
    if (@file_put_contents($envPath, $content, LOCK_EX) === false) {
        throw new RuntimeException(serverEnvAdminT(
            'parameters.server_env.error.write_failed',
            'Impossible d ecrire le fichier {target}. Verifiez les permissions ou un montage Docker en lecture seule.',
            ['target' => $targetLabel]
        ));
    }
}

function serverEnvAdminGetUnlockTtlSeconds()
{
    return 900;
}

function serverEnvAdminNormalizeUnlockUserId($userId = null)
{
    if ($userId === null && function_exists('commonGetCurrentUserId')) {
        $userId = commonGetCurrentUserId();
    }

    return (int)$userId;
}

function serverEnvAdminHasLocalPassword($userId = null)
{
    $userId = serverEnvAdminNormalizeUnlockUserId($userId);
    if ($userId <= 0) {
        return false;
    }

    $user = new \dbObject\User();
    if (!$user->load($userId)) {
        return false;
    }

    return trim((string)$user->get('password')) !== '';
}

function serverEnvAdminIsUnlocked($userId = null)
{
    $userId = serverEnvAdminNormalizeUnlockUserId($userId);
    if ($userId <= 0) {
        return false;
    }

    if (
        !isset($_SESSION['omo_server_env_admin_unlock'])
        || !is_array($_SESSION['omo_server_env_admin_unlock'])
        || !isset($_SESSION['omo_server_env_admin_unlock'][$userId])
    ) {
        return false;
    }

    $verifiedAt = (int)$_SESSION['omo_server_env_admin_unlock'][$userId];
    if ($verifiedAt <= 0 || ($verifiedAt + serverEnvAdminGetUnlockTtlSeconds()) < time()) {
        serverEnvAdminForgetUnlocked($userId);
        return false;
    }

    return true;
}

function serverEnvAdminRememberUnlocked($userId = null)
{
    $userId = serverEnvAdminNormalizeUnlockUserId($userId);
    if ($userId <= 0) {
        return false;
    }

    if (!isset($_SESSION['omo_server_env_admin_unlock']) || !is_array($_SESSION['omo_server_env_admin_unlock'])) {
        $_SESSION['omo_server_env_admin_unlock'] = array();
    }

    $_SESSION['omo_server_env_admin_unlock'][$userId] = time();
    return true;
}

function serverEnvAdminForgetUnlocked($userId = null)
{
    if ($userId === null) {
        unset($_SESSION['omo_server_env_admin_unlock']);
        return;
    }

    $userId = serverEnvAdminNormalizeUnlockUserId($userId);
    if (
        $userId <= 0
        || !isset($_SESSION['omo_server_env_admin_unlock'])
        || !is_array($_SESSION['omo_server_env_admin_unlock'])
    ) {
        return;
    }

    unset($_SESSION['omo_server_env_admin_unlock'][$userId]);
    if ($_SESSION['omo_server_env_admin_unlock'] === array()) {
        unset($_SESSION['omo_server_env_admin_unlock']);
    }
}

function serverEnvAdminVerifyCurrentUserPassword($password, $userId = null)
{
    $userId = serverEnvAdminNormalizeUnlockUserId($userId);
    if ($userId <= 0 || !is_string($password) || $password === '') {
        return false;
    }

    $user = new \dbObject\User();
    if (!$user->load($userId)) {
        return false;
    }

    $storedPassword = (string)$user->get('password');
    if ($storedPassword === '') {
        return false;
    }

    return commonVerifyUserPassword($password, $storedPassword);
}
