<?php
require_once __DIR__ . '/env.php';
require_once dirname(__DIR__) . '/common/ai_config.php';
require_once dirname(__DIR__) . '/omo/api/parameters/server_env_fields.php';
require_once dirname(__DIR__) . '/common/etherpad.php';

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
        'github' => [
            'title' => serverEnvAdminT('parameters.server_env.section.github.title', 'GitHub'),
            'intro' => serverEnvAdminT('parameters.server_env.section.github.intro', 'Depot et identite technique utilises pour envoyer les signalements de bugs.'),
            'fields' => [
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
        'patreon' => [
            'title' => serverEnvAdminT('parameters.server_env.section.patreon.title', 'Patreon'),
            'intro' => serverEnvAdminT('parameters.server_env.section.patreon.intro', 'Connexion des comptes Patreon, campagne soutenue et acces du createur.'),
            'fields' => [
                [
                    'key' => 'PATREON_CLIENT_ID',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CLIENT_ID.label', 'Client ID Patreon'),
                    'type' => 'text',
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_CLIENT_ID.help', 'Dans Patreon, ouvrez la page Clients & API Keys et selectionnez votre application OAuth v2. Copiez son Client ID. Il identifie l application, pas votre compte ni votre campagne. Configurez ces acces sur le serveur central de connexion.'),
                    'help_url' => 'https://docs.patreon.com/#clients-and-api-keys',
                ],
                [
                    'key' => 'PATREON_CLIENT_SECRET',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CLIENT_SECRET.label', 'Client secret Patreon'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_CLIENT_SECRET.help', 'Sur la page Patreon Clients & API Keys, ouvrez la meme application que pour le Client ID et copiez son Client Secret. Gardez ce secret prive. Laissez vide pour conserver la valeur actuelle.'),
                    'help_url' => 'https://docs.patreon.com/#clients-and-api-keys',
                ],
                [
                    'key' => 'PATREON_CONNECT_URL',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CONNECT_URL.label', 'URL centrale de connexion Patreon'),
                    'type' => 'text',
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_CONNECT_URL.help', 'Adresse OMO du serveur qui gere les connexions Patreon, par exemple https://omo2.org/common/patreon_connect.php. Utilisez la meme URL sur les sites qui partagent cette connexion. Ce n est pas l adresse de votre page Patreon.'),
                ],
                [
                    'key' => 'PATREON_CONNECT_ALLOWED_ORIGINS',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CONNECT_ALLOWED_ORIGINS.label', 'Domaines de retour Patreon autorisés'),
                    'type' => 'text',
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_CONNECT_ALLOWED_ORIGINS.help', 'Liste d origines HTTPS séparées par des virgules. https://*.dev.opengov.tools autorise ses sous-domaines ; ajoutez aussi https://dev.opengov.tools pour le domaine principal.'),
                ],
                [
                    'key' => 'PATREON_REDIRECT_URI',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_REDIRECT_URI.label', 'Redirect URI Patreon'),
                    'type' => 'text',
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_REDIRECT_URI.help', 'Adresse de retour a declarer aussi dans votre application Patreon, par exemple https://omo2.org/common/patreon_callback.php. Si l URL centrale de connexion est renseignee, OMO utilise automatiquement /common/patreon_callback.php sur ce domaine. Les deux adresses doivent correspondre.'),
                    'help_url' => 'https://docs.patreon.com/#clients-and-api-keys',
                ],
                [
                    'key' => 'PATREON_CREATOR_CAMPAIGN_ID',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CREATOR_CAMPAIGN_ID.label', 'Campaign ID Patreon'),
                    'type' => 'text',
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_CREATOR_CAMPAIGN_ID.help', 'Code numerique de la campagne soutenue. Il est renvoye comme id par l API Patreon /api/oauth2/v2/campaigns avec le jeton du createur. Ce n est ni le nom de la page Patreon, ni l ID utilisateur du createur. Conservez la valeur existante si la campagne ne change pas.'),
                    'help_url' => 'https://docs.patreon.com/#get-api-oauth2-v2-campaigns',
                ],
                [
                    'key' => 'PATREON_CREATOR_USER_ID',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_CREATOR_USER_ID.label', 'ID utilisateur Patreon du createur'),
                    'type' => 'text',
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_CREATOR_USER_ID.help', 'Dans OMO, ouvrez Mon profil > Patreon, connectez votre compte puis cliquez sur Synchroniser si necessaire. Copiez le code affiche sous ID utilisateur Patreon. Ce n est ni votre nom public, ni votre e-mail, ni l ID de campagne ou l ID OMO. Ce compte relie aura acces a l IA sans contribution. Vide : aucune exception.'),
                ],
                [
                    'key' => 'PATREON_USER_AGENT',
                    'label' => serverEnvAdminT('parameters.server_env.field.PATREON_USER_AGENT.label', 'User-Agent Patreon'),
                    'type' => 'text',
                    'help' => serverEnvAdminT('parameters.server_env.field.PATREON_USER_AGENT.help', 'Nom technique transmis a Patreon pour identifier les appels de votre site, par exemple OMO Patreon Sync. Vous choisissez ce texte ; aucune cle ni aucun identifiant Patreon n est necessaire ici.'),
                ],
            ],
        ],
        'telegram' => [
            'title' => serverEnvAdminT('parameters.server_env.section.telegram.title', 'Telegram'),
            'intro' => serverEnvAdminT('parameters.server_env.section.telegram.intro', 'Identite du bot et protection des appels entrants du webhook.'),
            'fields' => [
                [
                    'key' => 'TELEGRAM_BOT_TOKEN',
                    'label' => serverEnvAdminT('parameters.server_env.field.TELEGRAM_BOT_TOKEN.label', 'Token Telegram'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
                [
                    'key' => 'TELEGRAM_WEBHOOK_SECRET',
                    'label' => serverEnvAdminT('parameters.server_env.field.TELEGRAM_WEBHOOK_SECRET.label', 'Secret du webhook Telegram'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.TELEGRAM_WEBHOOK_SECRET.help', 'Secret choisi pour proteger les appels du bot : 32 a 256 caracteres, lettres, chiffres, tirets ou underscores. La meme valeur doit etre enregistree aupres de Telegram avec setWebhook et son parametre secret_token. Modifier ce champ ne reenregistre pas le webhook. Laissez vide pour conserver le secret actuel.'),
                ],
            ],
        ],
        'ethercalc' => [
            'title' => serverEnvAdminT('parameters.server_env.section.ethercalc.title', 'EtherCalc'),
            'intro' => serverEnvAdminT('parameters.server_env.section.ethercalc.intro', 'Connexion globale au serveur EtherCalc utilise par les tableurs collaboratifs.'),
            'fields' => [
                [
                    'key' => 'ETHERCALC_URL',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERCALC_URL.label', 'URL publique EtherCalc'),
                    'type' => 'url',
                    'placeholder' => 'https://calc.opengov.tools',
                    'help' => serverEnvAdminT('parameters.server_env.field.ETHERCALC_URL.help', 'Adresse de base du serveur EtherCalc, sans le nom de la feuille.'),
                ],
                [
                    'key' => 'ETHERCALC_INTERNAL_URL',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERCALC_INTERNAL_URL.label', 'URL interne EtherCalc'),
                    'type' => 'url',
                    'placeholder' => 'http://ethercalc:8000',
                    'help' => serverEnvAdminT('parameters.server_env.field.ETHERCALC_INTERNAL_URL.help', 'Optionnel. Utilisee uniquement par le serveur OMO pour joindre EtherCalc dans le meme reseau. Laissez vide dans les autres cas.'),
                ],
                [
                    'key' => 'ETHERCALC_KEY',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERCALC_KEY.label', 'Cle EtherCalc'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
            ],
        ],
        'etherpad' => [
            'title' => serverEnvAdminT('parameters.server_env.section.etherpad.title', 'Etherpad'),
            'intro' => serverEnvAdminT('parameters.server_env.section.etherpad.intro', 'Connexion globale au serveur Etherpad utilise par les documents collaboratifs.'),
            'fields' => [
                [
                    'key' => 'ETHERPAD_URL',
                    'label' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_URL.label', 'Adresses Etherpad'),
                    'type' => 'text',
                    'placeholder' => 'https://pad.example.org,https://pad.example.net',
                    'help' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_URL.help', 'Une ou plusieurs adresses de base séparées par des virgules. OMO choisit celle qui correspond au domaine du site.'),
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
                    'help' => serverEnvAdminT('parameters.server_env.field.ETHERPAD_COOKIE_DOMAIN.help', 'Optionnel. OMO déduit normalement le domaine du cookie de l’adresse Etherpad choisie.'),
                ],
            ],
        ],
        'spacedeck' => [
            'title' => serverEnvAdminT('parameters.server_env.section.spacedeck.title', 'SpaceDeck'),
            'intro' => serverEnvAdminT('parameters.server_env.section.spacedeck.intro', 'Connexion globale au serveur de tableaux blancs collaboratifs.'),
            'fields' => [
                [
                    'key' => 'SPACEDECK_URL',
                    'label' => serverEnvAdminT('parameters.server_env.field.SPACEDECK_URL.label', 'URL publique SpaceDeck'),
                    'type' => 'url',
                    'placeholder' => 'https://board.opengov.tools',
                    'help' => serverEnvAdminT('parameters.server_env.field.SPACEDECK_URL.help', 'Adresse HTTPS ouverte dans les iframes OMO, sans le nom du tableau.'),
                ],
                [
                    'key' => 'SPACEDECK_INTERNAL_URL',
                    'label' => serverEnvAdminT('parameters.server_env.field.SPACEDECK_INTERNAL_URL.label', 'URL interne SpaceDeck'),
                    'type' => 'url',
                    'placeholder' => 'https://board.opengov.tools',
                    'help' => serverEnvAdminT('parameters.server_env.field.SPACEDECK_INTERNAL_URL.help', 'Optionnel. Utilisee par OMO pour creer et supprimer les tableaux. Laissez vide pour utiliser l URL publique.'),
                ],
                [
                    'key' => 'SPACEDECK_PROVISIONING_TOKEN',
                    'label' => serverEnvAdminT('parameters.server_env.field.SPACEDECK_PROVISIONING_TOKEN.label', 'Jeton de provisioning SpaceDeck'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.SPACEDECK_PROVISIONING_TOKEN.help', 'Jeton prive configure sur le VPS. Il permet a OMO de creer et supprimer les tableaux.'),
                ],
                [
                    'key' => 'SPACEDOCK_EXTERNAL_ACCESS_SECRET',
                    'label' => serverEnvAdminT('parameters.server_env.field.SPACEDOCK_EXTERNAL_ACCESS_SECRET.label', 'Cle de signature des acces'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.SPACEDOCK_EXTERNAL_ACCESS_SECRET.help', 'Cle privee longue et aleatoire, utilisee par OMO pour signer les acces de lecture et d edition.'),
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
                    'min' => 1,
                    'max' => 65535,
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
        'maps' => [
            'title' => serverEnvAdminT('parameters.server_env.section.maps.title', 'Cartographie'),
            'intro' => serverEnvAdminT('parameters.server_env.section.maps.intro', 'Cle utilisee pour afficher les fonds de carte Stadia Maps.'),
            'fields' => [
                [
                    'key' => 'STADIA_MAPS_API_KEY',
                    'label' => serverEnvAdminT('parameters.server_env.field.STADIA_MAPS_API_KEY.label', 'Cle Stadia Maps'),
                    'type' => 'password',
                    'secret' => true,
                    'help' => serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.'),
                ],
            ],
        ],
    ];

    foreach (omoServerEnvAdditionalSections() as $sectionKey => $section) {
        $sections[$sectionKey] = [
            'title' => serverEnvAdminT('parameters.server_env.section.' . $sectionKey . '.title', $section['title']),
            'intro' => serverEnvAdminT('parameters.server_env.section.' . $sectionKey . '.intro', $section['intro']),
            'fields' => [],
            'technical' => !empty($section['technical']),
        ];
    }
    $additionalFields = omoServerEnvAdditionalServiceFields();
    foreach (omoServerEnvAdditionalSections() as $sectionKey => $section) {
        $additionalFields[$sectionKey] = $section['fields'];
    }
    foreach ($additionalFields as $sectionKey => $fields) {
        foreach ($fields as $key => $field) {
            $field['key'] = $key;
            $field['label'] = serverEnvAdminT('parameters.server_env.field.' . $key . '.label', $field['label']);
            $field['type'] = $field['type'] ?? (!empty($field['secret']) ? 'password' : 'text');
            if ($field['type'] === 'boolean') {
                $field['type'] = 'select';
                $field['options'] = [
                    'true' => serverEnvAdminT('parameters.server_env.option.boolean.true', 'Oui'),
                    'false' => serverEnvAdminT('parameters.server_env.option.boolean.false', 'Non'),
                ];
            }
            if (isset($field['options']) && ($fields[$key]['type'] ?? '') === 'select') {
                foreach ($field['options'] as $optionValue => $label) {
                    $field['options'][$optionValue] = serverEnvAdminT('parameters.server_env.field.' . $key . '.option.' . $optionValue, $label);
                }
            }
            if (!empty($field['help'])) {
                $field['help'] = serverEnvAdminT('parameters.server_env.field.' . $key . '.help', $field['help']);
            } elseif (!empty($field['secret'])) {
                $field['help'] = serverEnvAdminT('parameters.server_env.field.secret_keep.help', 'Laissez vide pour conserver la valeur actuelle.');
            }
            $sections[$sectionKey]['fields'][] = $field;
        }
    }

    $defaults = serverEnvAdminReadEnvDefaults(serverEnvAdminGetExampleEnvPath());
    foreach ($sections as $sectionKey => $section) {
        foreach ($section['fields'] as $fieldIndex => $field) {
            $key = (string)$field['key'];
            $sections[$sectionKey]['fields'][$fieldIndex]['default'] = (string)($defaults[$key] ?? '');
        }
    }

    return $sections;
}

function serverEnvAdminGetRetiredKeys()
{
    return ['PAYPAL_CLIENT_ID', 'GITHUB_BUGREPORT_PROJECT_OWNER', 'GITHUB_BUGREPORT_PROJECT_NUMBER'];
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

    $values['AI_PROVIDER'] = commonAiGetProvider();
    $values['AI_API_KEY'] = commonAiGetApiKey();
    $values['AI_MODEL'] = commonAiGetModel();
    $values['AI_TRANSLATION_MODEL'] = commonAiGetModel(true);
    $values['TRANSCRIPTION_PROVIDER'] = commonAiGetTranscriptionProvider();
    $values['TRANSCRIPTION_API_KEY'] = commonAiGetTranscriptionApiKey();
    $values['TRANSCRIPTION_MODEL'] = commonAiGetTranscriptionModel();
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
        if ($displayValues[$key] !== '' && isset($field['options']['true'], $field['options']['false'])) {
            $booleanValue = filter_var($displayValues[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($booleanValue !== null) {
                $displayValues[$key] = $booleanValue ? 'true' : 'false';
            }
        }
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
        if (!array_key_exists($key, $source)) {
            continue;
        }
        $value = (string)$source[$key];
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

        if (!array_key_exists($key, $submittedValues) || ((!empty($field['secret']) || !empty($field['preserve_empty'])) && $submittedValue === '')) {
            $mergedValues[$key] = $currentValue;
            continue;
        }

        $mergedValues[$key] = $submittedValue;
    }

    return $mergedValues;
}

function serverEnvAdminIsHttpUrl($value)
{
    $parsedServerUrl = parse_url(trim((string)$value));
    $serverScheme = is_array($parsedServerUrl) ? strtolower((string)($parsedServerUrl['scheme'] ?? '')) : '';
    $serverHost = is_array($parsedServerUrl) ? trim((string)($parsedServerUrl['host'] ?? '')) : '';

    return is_array($parsedServerUrl)
        && in_array($serverScheme, ['http', 'https'], true)
        && $serverHost !== ''
        && !isset($parsedServerUrl['user'])
        && !isset($parsedServerUrl['pass'])
        && !isset($parsedServerUrl['query'])
        && !isset($parsedServerUrl['fragment']);
}

function serverEnvAdminIsValidPublicOrigin($value)
{
    if (!serverEnvAdminIsHttpUrl($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $parts = parse_url($value);
    return in_array($parts['path'] ?? '', ['', '/'], true)
        && (strtolower($parts['scheme']) === 'https' || in_array(strtolower($parts['host']), ['localhost', '127.0.0.1'], true));
}

function serverEnvAdminParseEtherpadUrls($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $baseUrls = [];
    foreach (explode(',', $value) as $part) {
        $baseUrl = omoEtherpadNormalizeBaseUrl($part);
        if ($baseUrl === '' || !serverEnvAdminIsHttpUrl($baseUrl)) {
            return null;
        }

        $baseUrls[$baseUrl] = $baseUrl;
    }

    return array_values($baseUrls);
}

function serverEnvAdminValidateValues(array $values, ?array $currentValues = null, ?array $submittedValues = null)
{
    $errors = [];

    $provider = ($values['AI_PROVIDER'] ?? '') ?: 'openai';
    $destinationChanged = $currentValues !== null && $provider !== (($currentValues['AI_PROVIDER'] ?? '') ?: 'openai');
    if ($provider === 'openai_compatible') {
        try {
            $baseUrl = commonAiGetCompatibleBaseUrl((string)($values['AI_BASE_URL'] ?? ''));
            if ($currentValues !== null) {
                $currentBaseUrl = rtrim(trim((string)($currentValues['AI_BASE_URL'] ?? '')), '/') ?: 'https://openrouter.ai/api/v1';
                $destinationChanged = $destinationChanged || $baseUrl !== $currentBaseUrl;
            }
        } catch (InvalidArgumentException $error) {
            $errors[] = serverEnvAdminT('parameters.server_env.error.ai_base_url', 'Indiquez une adresse de base HTTPS valide, sans identifiants, parametres ni suffixe /chat/completions.');
        }
    }
    if ($currentValues !== null
        && $provider !== 'disabled'
        && $destinationChanged
        && trim((string)($currentValues['AI_API_KEY'] ?? '')) !== ''
        && trim((string)($submittedValues['AI_API_KEY'] ?? '')) === ''
        && ($values['AI_API_KEY'] ?? '') === ($currentValues['AI_API_KEY'] ?? '')) {
        $errors[] = serverEnvAdminT('parameters.server_env.error.ai_provider_key', 'Saisissez la cle API du fournisseur choisi lorsque vous changez de fournisseur de texte ou d adresse API.');
    }
    $audioProvider = ($values['TRANSCRIPTION_PROVIDER'] ?? '') ?: 'openai';
    if ($currentValues !== null && $audioProvider !== 'disabled'
        && $audioProvider !== (($currentValues['TRANSCRIPTION_PROVIDER'] ?? '') ?: 'openai')
        && trim((string)($currentValues['TRANSCRIPTION_API_KEY'] ?? '')) !== ''
        && trim((string)($submittedValues['TRANSCRIPTION_API_KEY'] ?? '')) === ''
        && ($values['TRANSCRIPTION_API_KEY'] ?? '') === ($currentValues['TRANSCRIPTION_API_KEY'] ?? '')) {
        $errors[] = serverEnvAdminT('parameters.server_env.error.transcription_provider_key', 'Saisissez la cle API du service choisi lorsque vous changez de fournisseur de transcription.');
    }
    if (in_array($provider, ['anthropic', 'mistral', 'openai_compatible'], true)
        && trim((string)($values['AI_API_KEY'] ?? '')) !== ''
        && trim((string)($values['AI_MODEL'] ?? '')) === '') {
        $errors[] = serverEnvAdminT('parameters.server_env.error.ai_model_required', 'Indiquez le modele principal du fournisseur de texte choisi.');
    }

    $fieldMap = serverEnvAdminGetFieldMap();

    foreach ($fieldMap as $key => $field) {
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

    foreach ($fieldMap as $key => $field) {
        $value = (string)($values[$key] ?? '');
        if ($value === '') {
            continue;
        }
        if (($field['type'] ?? '') === 'number' && (preg_match('/^[0-9]+$/D', $value) !== 1
            || (isset($field['min']) && (float)$value < $field['min'])
            || (isset($field['max']) && (float)$value > $field['max']))) {
            $errors[] = serverEnvAdminT('parameters.server_env.error.invalid_numeric_field', 'La valeur numerique de {label} est invalide.', ['label' => $field['label']]);
        }
        if (($field['type'] ?? '') === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = serverEnvAdminT('parameters.server_env.error.invalid_email_field', 'L adresse e-mail de {label} est invalide.', ['label' => $field['label']]);
        }
    }

    $publicUrl = (string)($values['AUTH_PUBLIC_URL'] ?? '');
    if ($publicUrl !== '' && !serverEnvAdminIsValidPublicOrigin($publicUrl)) {
        $errors[] = serverEnvAdminT('parameters.server_env.error.invalid_public_url', 'L adresse publique doit etre une origine HTTPS sans chemin ni parametre. HTTP est reserve a localhost et 127.0.0.1.');
    }
    foreach (['NOMINATIM_SEARCH_URL', 'MCP_PUBLIC_URL'] as $key) {
        $url = (string)($values[$key] ?? '');
        if ($url !== '' && !serverEnvAdminIsHttpUrl($url)) {
            $errors[] = serverEnvAdminT('parameters.server_env.error.invalid_url_field', 'L adresse de {label} est invalide.', ['label' => $fieldMap[$key]['label']]);
        }
    }

    $telegramWebhookSecret = (string)($values['TELEGRAM_WEBHOOK_SECRET'] ?? '');
    if ($telegramWebhookSecret !== '' && preg_match('/^[A-Za-z0-9_-]{32,256}$/D', $telegramWebhookSecret) !== 1) {
        $errors[] = serverEnvAdminT(
            'parameters.server_env.error.invalid_telegram_webhook_secret',
            'Le secret du webhook Telegram doit contenir 32 a 256 lettres, chiffres, tirets ou underscores.'
        );
    }

    $patreonCreatorUserId = trim((string)($values['PATREON_CREATOR_USER_ID'] ?? ''));
    if ($patreonCreatorUserId !== '' && preg_match('/^[1-9][0-9]*$/D', $patreonCreatorUserId) !== 1) {
        $errors[] = serverEnvAdminT(
            'parameters.server_env.error.invalid_patreon_creator_user_id',
            'L ID utilisateur Patreon du createur doit etre un identifiant numerique strictement positif.'
        );
    }

    $serverUrlFields = array(
        'ETHERPAD_URL' => array(
            'translationKey' => 'parameters.server_env.error.invalid_etherpad_url',
            'fallback' => 'L URL Etherpad doit etre une adresse http ou https valide.',
        ),
        'ETHERCALC_URL' => array(
            'translationKey' => 'parameters.server_env.error.invalid_ethercalc_url',
            'fallback' => 'L URL EtherCalc doit etre une adresse http ou https valide.',
        ),
        'ETHERCALC_INTERNAL_URL' => array(
            'translationKey' => 'parameters.server_env.error.invalid_ethercalc_url',
            'fallback' => 'L URL EtherCalc doit etre une adresse http ou https valide.',
        ),
        'SPACEDECK_URL' => array(
            'translationKey' => 'parameters.server_env.error.invalid_spacedeck_url',
            'fallback' => 'L URL SpaceDeck doit etre une adresse http ou https valide.',
        ),
        'SPACEDECK_INTERNAL_URL' => array(
            'translationKey' => 'parameters.server_env.error.invalid_spacedeck_url',
            'fallback' => 'L URL SpaceDeck doit etre une adresse http ou https valide.',
        ),
    );

    foreach ($serverUrlFields as $serverUrlKey => $serverUrlError) {
        $serverUrl = trim((string)($values[$serverUrlKey] ?? ''));
        if ($serverUrl === '') {
            continue;
        }

        if (
            $serverUrlKey === 'ETHERPAD_URL'
                ? serverEnvAdminParseEtherpadUrls($serverUrl) === null
                : !serverEnvAdminIsHttpUrl($serverUrl)
        ) {
            $errors[] = serverEnvAdminT(
                $serverUrlError['translationKey'],
                $serverUrlError['fallback']
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

    $patreonConnectUrl = trim((string)($values['PATREON_CONNECT_URL'] ?? ''));
    if ($patreonConnectUrl !== '' && preg_match('~^https://[^/?#]+/common/patreon_connect\.php$~i', $patreonConnectUrl) !== 1) {
        $errors[] = 'L URL centrale Patreon doit se terminer par /common/patreon_connect.php et utiliser HTTPS.';
    }

    $envPath = serverEnvAdminGetEnvPath();
    $targetLabel = serverEnvAdminGetEnvTargetLabel();
    $directory = dirname($envPath);
    if (!is_dir($directory) || !is_writable($directory)) {
        $errors[] = 'Le dossier contenant le fichier ' . $targetLabel . ' n est pas accessible en ecriture.';
    }

    return $errors;
}

function serverEnvAdminConnectionTestError($service, $reason)
{
    $serviceLabels = array(
        'etherpad' => 'Etherpad',
        'ethercalc' => 'EtherCalc',
        'spacedeck' => 'SpaceDeck',
    );
    $serviceLabel = $serviceLabels[$service] ?? 'Service';
    $reason = trim((string)$reason);

    return serverEnvAdminT(
        'parameters.server_env.error.connection_test_failed',
        'La connexion avec {service} a échoué{reason}.',
        [
            'service' => $serviceLabel,
            'reason' => $reason === '' ? '' : ' : ' . $reason,
        ]
    );
}

function serverEnvAdminRequest($method, $url, array $options = array())
{
    if (!function_exists('curl_init')) {
        return [
            'status' => false,
            'text' => serverEnvAdminT(
                'parameters.server_env.error.curl_required',
                'cURL est requis pour tester cette connexion.'
            ),
        ];
    }

    $curl = curl_init((string)$url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper(trim((string)$method)));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    $headers = array_merge(array('Accept: application/json'), (array)($options['headers'] ?? array()));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    if (array_key_exists('body', $options)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, (string)$options['body']);
    }

    $host = strtolower(trim((string)parse_url((string)$url, PHP_URL_HOST)));
    $localDevelopmentCertificate = '/etc/apache2/ssl/dev-localhost.crt';
    if (
        $host !== ''
        && (hash_equals($host, 'localtest.me') || str_ends_with($host, '.localtest.me'))
        && is_file($localDevelopmentCertificate)
    ) {
        curl_setopt($curl, CURLOPT_CAINFO, $localDevelopmentCertificate);
    }

    $response = curl_exec($curl);
    $curlError = trim((string)curl_error($curl));
    $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if ($response === false) {
        return [
            'status' => false,
            'httpCode' => $httpCode,
            'text' => $curlError !== '' ? $curlError : serverEnvAdminT(
                'parameters.server_env.error.connection_request_failed',
                'La requete a echoue.'
            ),
        ];
    }

    return [
        'status' => $httpCode >= 200 && $httpCode < 300,
        'httpCode' => $httpCode,
        'body' => (string)$response,
    ];
}

function serverEnvAdminTestEtherpadConnection(array $values)
{
    $baseUrls = serverEnvAdminParseEtherpadUrls($values['ETHERPAD_URL'] ?? '');
    $apiKey = trim((string)($values['ETHERPAD_API_KEY'] ?? ''));
    $apiVersion = trim((string)($values['ETHERPAD_API_VERSION'] ?? ''));

    if ($baseUrls === null) {
        return [
            'status' => false,
            'message' => serverEnvAdminT('parameters.server_env.error.invalid_etherpad_url', 'Chaque adresse Etherpad doit être une URL HTTP ou HTTPS valide.'),
        ];
    }
    if ($apiKey === '' || $apiVersion === '') {
        return [
            'status' => false,
            'message' => serverEnvAdminT(
                'parameters.server_env.error.etherpad_connection_incomplete',
                'Renseignez les adresses, la clé API et la version de l’API Etherpad avant le test.'
            ),
        ];
    }
    if (preg_match('/^[0-9]+(?:\.[0-9]+)*$/', $apiVersion) !== 1) {
        return [
            'status' => false,
            'message' => serverEnvAdminT('parameters.server_env.error.invalid_etherpad_api_version', 'La version de l API Etherpad doit etre numerique, par exemple 1.3.1.'),
        ];
    }

    foreach ($baseUrls as $baseUrl) {
        $url = $baseUrl . '/api/' . rawurlencode($apiVersion) . '/listAllPads?'
            . http_build_query(['apikey' => $apiKey], '', '&', PHP_QUERY_RFC3986);
        $result = serverEnvAdminRequest('GET', $url);
        if (!($result['status'] ?? false)) {
            $reason = isset($result['httpCode']) && (int)$result['httpCode'] > 0
                ? 'HTTP ' . (int)$result['httpCode']
                : (string)($result['text'] ?? '');
            return [
                'status' => false,
                'message' => serverEnvAdminConnectionTestError('etherpad', $baseUrl . ' : ' . $reason),
            ];
        }

        $payload = json_decode((string)($result['body'] ?? ''), true);
        if (!is_array($payload) || (int)($payload['code'] ?? -1) !== 0) {
            $reason = is_array($payload) ? trim((string)($payload['message'] ?? '')) : '';
            return [
                'status' => false,
                'message' => serverEnvAdminConnectionTestError('etherpad', $baseUrl . ' : ' . $reason),
            ];
        }
    }

    return [
        'status' => true,
        'message' => serverEnvAdminT(
            'parameters.server_env.status.etherpad_connection_ok',
            'Connexion Etherpad vérifiée pour toutes les adresses : version de l’API et clé valides.'
        ),
    ];
}

function serverEnvAdminTestEthercalcConnection(array $values)
{
    $publicBaseUrl = rtrim(trim((string)($values['ETHERCALC_URL'] ?? '')), '/');
    $internalBaseUrl = rtrim(trim((string)($values['ETHERCALC_INTERNAL_URL'] ?? '')), '/');
    $apiBaseUrl = $internalBaseUrl !== '' ? $internalBaseUrl : $publicBaseUrl;
    $key = trim((string)($values['ETHERCALC_KEY'] ?? ''));

    if (!serverEnvAdminIsHttpUrl($publicBaseUrl) || ($internalBaseUrl !== '' && !serverEnvAdminIsHttpUrl($internalBaseUrl))) {
        return [
            'status' => false,
            'message' => serverEnvAdminT('parameters.server_env.error.invalid_ethercalc_url', 'L URL EtherCalc doit etre une adresse http ou https valide.'),
        ];
    }
    if ($key === '') {
        return [
            'status' => false,
            'message' => serverEnvAdminT(
                'parameters.server_env.error.ethercalc_connection_incomplete',
                'Renseignez l URL publique et la cle EtherCalc avant le test.'
            ),
        ];
    }

    $healthResult = serverEnvAdminRequest('GET', $publicBaseUrl . '/_health');
    if (!($healthResult['status'] ?? false)) {
        $reason = isset($healthResult['httpCode']) && (int)$healthResult['httpCode'] > 0
            ? 'HTTP ' . (int)$healthResult['httpCode']
            : (string)($healthResult['text'] ?? '');
        return [
            'status' => false,
            'message' => serverEnvAdminConnectionTestError('ethercalc', $reason),
        ];
    }

    try {
        $testRoom = 'omo-connection-test-' . bin2hex(random_bytes(12));
    } catch (Throwable $exception) {
        return [
            'status' => false,
            'message' => serverEnvAdminConnectionTestError('ethercalc', ''),
        ];
    }

    $token = hash_hmac('sha256', $testRoom, $key);
    $requestUrl = $apiBaseUrl . '/_/' . rawurlencode($testRoom) . '?'
        . http_build_query(['auth' => $token], '', '&', PHP_QUERY_RFC3986);
    $authResult = serverEnvAdminRequest('DELETE', $requestUrl);
    if (!($authResult['status'] ?? false)) {
        $reason = isset($authResult['httpCode']) && (int)$authResult['httpCode'] > 0
            ? 'HTTP ' . (int)$authResult['httpCode']
            : (string)($authResult['text'] ?? '');
        return [
            'status' => false,
            'message' => serverEnvAdminConnectionTestError('ethercalc', $reason),
        ];
    }

    return [
        'status' => true,
        'message' => serverEnvAdminT(
            'parameters.server_env.status.ethercalc_connection_ok',
            'Connexion EtherCalc verifiee : URL publique, URL interne et cle sont valides.'
        ),
    ];
}

function serverEnvAdminTestSpacedeckConnection(array $values)
{
    $publicBaseUrl = rtrim(trim((string)($values['SPACEDECK_URL'] ?? '')), '/');
    $internalBaseUrl = rtrim(trim((string)($values['SPACEDECK_INTERNAL_URL'] ?? '')), '/');
    $apiBaseUrl = $internalBaseUrl !== '' ? $internalBaseUrl : $publicBaseUrl;
    $provisioningToken = trim((string)($values['SPACEDECK_PROVISIONING_TOKEN'] ?? ''));

    if (!serverEnvAdminIsHttpUrl($publicBaseUrl) || ($internalBaseUrl !== '' && !serverEnvAdminIsHttpUrl($internalBaseUrl))) {
        return array(
            'status' => false,
            'message' => serverEnvAdminT('parameters.server_env.error.invalid_spacedeck_url', 'L URL SpaceDeck doit etre une adresse http ou https valide.'),
        );
    }
    if ($provisioningToken === '') {
        return array(
            'status' => false,
            'message' => serverEnvAdminT(
                'parameters.server_env.error.spacedeck_connection_incomplete',
                'Renseignez l URL publique et le jeton de provisioning SpaceDeck avant le test.'
            ),
        );
    }

    $testName = 'OMO connection test ' . bin2hex(random_bytes(8));
    $headers = array(
        'Content-Type: application/json',
        'X-Spacedeck-Provisioning-Token: ' . $provisioningToken,
    );
    $createResult = serverEnvAdminRequest('POST', $apiBaseUrl . '/api/external/spaces', array(
        'headers' => $headers,
        'body' => json_encode(array('name' => $testName), JSON_UNESCAPED_SLASHES),
    ));
    if (!($createResult['status'] ?? false)) {
        $reason = isset($createResult['httpCode']) && (int)$createResult['httpCode'] > 0
            ? 'HTTP ' . (int)$createResult['httpCode']
            : (string)($createResult['text'] ?? '');
        return array(
            'status' => false,
            'message' => serverEnvAdminConnectionTestError('spacedeck', $reason),
        );
    }

    $payload = json_decode((string)($createResult['body'] ?? ''), true);
    $spaceId = is_array($payload) ? trim((string)($payload['id'] ?? '')) : '';
    if ($spaceId === '') {
        return array(
            'status' => false,
            'message' => serverEnvAdminConnectionTestError('spacedeck', 'reponse de creation invalide'),
        );
    }

    $deleteResult = serverEnvAdminRequest('DELETE', $apiBaseUrl . '/api/external/spaces/' . rawurlencode($spaceId), array(
        'headers' => $headers,
    ));
    if (!($deleteResult['status'] ?? false)) {
        $reason = isset($deleteResult['httpCode']) && (int)$deleteResult['httpCode'] > 0
            ? 'HTTP ' . (int)$deleteResult['httpCode']
            : (string)($deleteResult['text'] ?? '');
        return array(
            'status' => false,
            'message' => serverEnvAdminConnectionTestError('spacedeck', $reason),
        );
    }

    return array(
        'status' => true,
        'message' => serverEnvAdminT(
            'parameters.server_env.status.spacedeck_connection_ok',
            'Connexion SpaceDeck verifiee : URL et jeton de provisioning sont valides.'
        ),
    );
}

function serverEnvAdminTestConnection($service, array $values)
{
    if ($service === 'etherpad') {
        return serverEnvAdminTestEtherpadConnection($values);
    }
    if ($service === 'ethercalc') {
        return serverEnvAdminTestEthercalcConnection($values);
    }
    if ($service === 'spacedeck') {
        return serverEnvAdminTestSpacedeckConnection($values);
    }

    return [
        'status' => false,
        'message' => serverEnvAdminT(
            'parameters.server_env.error.invalid_connection_service',
            'Service de connexion invalide.'
        ),
    ];
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

    // Remove retired application settings while preserving unrelated hosting variables.
    $lines = array_values(array_filter($lines, static function ($line) {
        if (preg_match('/^\s*(?:export\s+)?([A-Z][A-Z0-9_]*)\s*=/', (string)$line, $matches) !== 1) {
            return true;
        }
        return !in_array($matches[1], serverEnvAdminGetRetiredKeys(), true);
    }));

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
