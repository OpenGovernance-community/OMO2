<?php

require_once dirname(__DIR__) . '/db/migrations.php';
require_once dirname(__DIR__) . '/common/auth.php';

function autoInstallBootstrap($envPath)
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $envPath = is_string($envPath) ? $envPath : '';
    if ($envPath === '' || file_exists($envPath)) {
        return;
    }

    $currentPath = autoInstallGetRequestPath();
    if ($currentPath !== '/install.php') {
        header('Location: /install.php');
        exit;
    }

    autoInstallHandleRequest($envPath);
    exit;
}

function autoInstallGetRequestPath()
{
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/install.php');
    $path = parse_url($requestUri, PHP_URL_PATH);

    if (!is_string($path) || $path === '') {
        return '/install.php';
    }

    return $path;
}

function autoInstallHandleRequest($envPath)
{
    $definitions = autoInstallGetFieldDefinitions();
    $values = autoInstallBuildInitialValues($definitions);
    $errors = [];

    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
        $values = autoInstallReadSubmittedValues($definitions);
        $errors = autoInstallValidateValues($definitions, $values, $envPath);

        if ($errors === []) {
            try {
                autoInstallPrepareDatabase($values);
                autoInstallWriteEnvFile($envPath, $definitions, $values);
                header('Location: /');
                exit;
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
    }

    http_response_code(503);
    autoInstallRenderPage($definitions, $values, $errors);
}

function autoInstallGetFieldDefinitions()
{
    static $definitions = null;

    if ($definitions !== null) {
        return $definitions;
    }

    $exampleDefaults = autoInstallReadEnvDefaults(dirname(__DIR__) . '/.env.example');

    $definitions = [
        'general' => [
            'title' => 'Parametres generaux',
            'intro' => 'Ces valeurs servent a initialiser le site. Les valeurs proposees viennent du fichier .env.example quand il existe.',
            'fields' => [
                [
                    'key' => 'SITE_TITLE',
                    'label' => 'Titre du site',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'HOME_TITLE',
                    'label' => 'Titre de la page d accueil',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'APP_LANG',
                    'label' => 'Langue par defaut',
                    'type' => 'select',
                    'required' => false,
                    'options' => [
                        'FR' => 'FR',
                        'EN' => 'EN',
                    ],
                ],
            ],
        ],
        'database' => [
            'title' => 'Base de donnees',
            'intro' => 'Ces champs sont obligatoires. L installation verifie la connexion avant d enregistrer le fichier .env.',
            'fields' => [
                [
                    'key' => 'DB_HOST',
                    'label' => 'Serveur MySQL',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'localhost ou db',
                ],
                [
                    'key' => 'DB_NAME',
                    'label' => 'Nom de la base',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'key' => 'DB_USER',
                    'label' => 'Utilisateur MySQL',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'key' => 'DB_PASS',
                    'label' => 'Mot de passe MySQL',
                    'type' => 'password',
                    'required' => true,
                    'placeholder' => 'Mot de passe MySQL',
                ],
            ],
        ],
        'admin' => [
            'title' => 'Compte administrateur',
            'intro' => 'Ce compte est cree pendant l installation. Il devient admin du site et peut se connecter des le premier demarrage.',
            'fields' => [
                [
                    'key' => 'INSTALL_ADMIN_FIRSTNAME',
                    'label' => 'Prenom',
                    'type' => 'text',
                    'required' => true,
                    'persist' => false,
                ],
                [
                    'key' => 'INSTALL_ADMIN_LASTNAME',
                    'label' => 'Nom',
                    'type' => 'text',
                    'required' => true,
                    'persist' => false,
                ],
                [
                    'key' => 'INSTALL_ADMIN_EMAIL',
                    'label' => 'E-mail',
                    'type' => 'email',
                    'required' => true,
                    'persist' => false,
                ],
                [
                    'key' => 'INSTALL_ADMIN_USERNAME',
                    'label' => 'Identifiant',
                    'type' => 'text',
                    'required' => false,
                    'persist' => false,
                    'placeholder' => 'Optionnel',
                    'help' => 'Si vide, l identifiant sera derive de l adresse e-mail.',
                ],
                [
                    'key' => 'INSTALL_ADMIN_PASSWORD',
                    'label' => 'Mot de passe',
                    'type' => 'password',
                    'required' => true,
                    'persist' => false,
                    'placeholder' => 'Choisissez un mot de passe solide',
                    'help' => 'Preferez une phrase de passe longue, unique et difficile a deviner.',
                ],
                [
                    'key' => 'INSTALL_ADMIN_PASSWORD_CONFIRM',
                    'label' => 'Confirmation du mot de passe',
                    'type' => 'password',
                    'required' => true,
                    'persist' => false,
                    'placeholder' => 'Retapez le mot de passe',
                ],
            ],
        ],
        'mail' => [
            'title' => 'E-mail',
            'intro' => 'Optionnel mais recommande si le serveur doit envoyer des liens ou des notifications.',
            'fields' => [
                [
                    'key' => 'MAIL_HOST',
                    'label' => 'Serveur SMTP',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'MAIL_PORT',
                    'label' => 'Port SMTP',
                    'type' => 'number',
                    'required' => false,
                ],
                [
                    'key' => 'MAIL_SECURE',
                    'label' => 'Securite SMTP',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'SSL, tls ou vide',
                ],
                [
                    'key' => 'MAIL_AUTH',
                    'label' => 'Authentification SMTP',
                    'type' => 'select',
                    'required' => false,
                    'options' => [
                        'true' => 'Oui',
                        'false' => 'Non',
                    ],
                ],
                [
                    'key' => 'MAIL_CHARSET',
                    'label' => 'Jeu de caracteres e-mail',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'MAIL_USER',
                    'label' => 'Utilisateur SMTP',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'MAIL_PASS',
                    'label' => 'Mot de passe SMTP',
                    'type' => 'password',
                    'required' => false,
                ],
            ],
        ],
        'ai' => [
            'title' => 'IA',
            'intro' => 'Optionnel. Sans ces cles, les fonctions liees a OpenAI resteront indisponibles.',
            'fields' => [
                [
                    'key' => 'OPENAI_API_KEY',
                    'label' => 'Cle OpenAI',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'OPENAI_UPLOAD_API_KEY',
                    'label' => 'Cle OpenAI upload',
                    'type' => 'text',
                    'required' => false,
                    'help' => 'Si vide, le site reutilisera OPENAI_API_KEY.',
                ],
                [
                    'key' => 'OPENAI_MODEL',
                    'label' => 'Modele OpenAI',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'OPENAI_TRANSLATION_MODEL',
                    'label' => 'Modele de traduction OpenAI',
                    'type' => 'text',
                    'required' => false,
                ],
            ],
        ],
        'integrations' => [
            'title' => 'Integrations optionnelles',
            'intro' => 'Vous pouvez laisser ces champs vides. Les fonctions associees seront simplement limitees tant que les cles ne sont pas renseignees.',
            'fields' => [
                [
                    'key' => 'PAYPAL_CLIENT_ID',
                    'label' => 'Client ID PayPal',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'TELEGRAM_BOT_TOKEN',
                    'label' => 'Token Telegram',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'PATREON_CLIENT_ID',
                    'label' => 'Client ID Patreon',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'PATREON_CLIENT_SECRET',
                    'label' => 'Client secret Patreon',
                    'type' => 'password',
                    'required' => false,
                ],
                [
                    'key' => 'PATREON_REDIRECT_URI',
                    'label' => 'Redirect URI Patreon',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'PATREON_CREATOR_CAMPAIGN_ID',
                    'label' => 'Campaign ID Patreon',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'PATREON_USER_AGENT',
                    'label' => 'User-Agent Patreon',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_TOKEN',
                    'label' => 'Token GitHub bug report',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_REPO_OWNER',
                    'label' => 'Repository owner GitHub',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_REPO_NAME',
                    'label' => 'Repository name GitHub',
                    'type' => 'text',
                    'required' => false,
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_LABELS',
                    'label' => 'Labels GitHub',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'bug,triage',
                ],
                [
                    'key' => 'GITHUB_BUGREPORT_USER_AGENT',
                    'label' => 'User-Agent GitHub',
                    'type' => 'text',
                    'required' => false,
                ],
            ],
        ],
    ];

    $definitions = autoInstallApplyExampleValues($definitions, $exampleDefaults);

    return $definitions;
}

function autoInstallApplyExampleValues(array $definitions, array $exampleDefaults)
{
    foreach ($definitions as $sectionKey => $section) {
        foreach ($section['fields'] as $fieldIndex => $field) {
            $key = (string)$field['key'];
            $fieldType = (string)($field['type'] ?? 'text');

            if (array_key_exists($key, $exampleDefaults)) {
                $definitions[$sectionKey]['fields'][$fieldIndex]['example'] = $exampleDefaults[$key];

                if (
                    !array_key_exists('placeholder', $field)
                    && !in_array($fieldType, ['password', 'select'], true)
                ) {
                    $definitions[$sectionKey]['fields'][$fieldIndex]['placeholder'] = $exampleDefaults[$key];
                }
            }

            if (!array_key_exists('default', $field)) {
                $definitions[$sectionKey]['fields'][$fieldIndex]['default'] = '';
            }
        }
    }

    return $definitions;
}

function autoInstallReadEnvDefaults($path)
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
        $line = trim((string)$line);

        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim((string)$key);
        $value = trim((string)$value);

        if ($key === '') {
            continue;
        }

        $defaults[$key] = autoInstallNormalizeStoredValue($value);
    }

    return $defaults;
}

function autoInstallNormalizeStoredValue($value)
{
    $value = trim((string)$value);
    $firstChar = substr($value, 0, 1);
    $lastChar = substr($value, -1);

    if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
        return substr($value, 1, -1);
    }

    return $value;
}

function autoInstallBuildInitialValues(array $definitions)
{
    $values = [];

    foreach ($definitions as $section) {
        foreach ($section['fields'] as $field) {
            $defaultValue = array_key_exists('initial', $field) ? (string)$field['initial'] : '';
            $values[$field['key']] = $defaultValue;
        }
    }

    return $values;
}

function autoInstallReadSubmittedValues(array $definitions)
{
    $values = [];

    foreach ($definitions as $section) {
        foreach ($section['fields'] as $field) {
            $key = (string)$field['key'];
            $rawValue = isset($_POST[$key]) ? (string)$_POST[$key] : '';
            $values[$key] = autoInstallSanitizeSubmittedValue($rawValue);
        }
    }

    return $values;
}

function autoInstallSanitizeSubmittedValue($value)
{
    $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
    $value = trim($value);
    return str_replace("\n", ' ', $value);
}

function autoInstallValidateValues(array $definitions, array $values, $envPath)
{
    $errors = [];

    foreach ($definitions as $section) {
        foreach ($section['fields'] as $field) {
            if (empty($field['required'])) {
                continue;
            }

            $key = (string)$field['key'];
            if (trim((string)($values[$key] ?? '')) === '') {
                $errors[] = 'Le champ ' . $field['label'] . ' est obligatoire.';
            }
        }
    }

    $directory = dirname((string)$envPath);
    if (!is_dir($directory) || !is_writable($directory)) {
        $errors[] = 'Le dossier cible n est pas accessible en ecriture pour creer le fichier .env.';
    }

    if (isset($values['MAIL_PORT']) && $values['MAIL_PORT'] !== '' && !ctype_digit((string)$values['MAIL_PORT'])) {
        $errors[] = 'Le port SMTP doit etre numerique.';
    }

    if (!filter_var((string)($values['INSTALL_ADMIN_EMAIL'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L adresse e-mail de l administrateur est invalide.';
    }

    $passwordCheck = autoInstallEvaluateAdminPassword(
        (string)($values['INSTALL_ADMIN_PASSWORD'] ?? ''),
        (string)($values['INSTALL_ADMIN_EMAIL'] ?? '')
    );
    if (!$passwordCheck['valid']) {
        $errors[] = 'Le mot de passe administrateur doit faire au moins 12 caracteres et contenir une minuscule, une majuscule, un chiffre et un caractere special.';
    }

    if ((string)($values['INSTALL_ADMIN_PASSWORD'] ?? '') !== (string)($values['INSTALL_ADMIN_PASSWORD_CONFIRM'] ?? '')) {
        $errors[] = 'La confirmation du mot de passe administrateur ne correspond pas.';
    }

    if (!is_file(autoInstallGetSeedPath())) {
        $errors[] = 'Le fichier seed Docker est introuvable.';
    }

    return $errors;
}

function autoInstallGetSeedPath()
{
    return dirname(__DIR__) . '/docker/db/init/00-base.seed.sql';
}

function autoInstallEvaluateAdminPassword($password, $email = '')
{
    $password = (string)$password;
    $email = trim(strtolower((string)$email));
    $emailLocalPart = '';

    if ($email !== '' && strpos($email, '@') !== false) {
        $emailParts = explode('@', $email, 2);
        $emailLocalPart = trim((string)($emailParts[0] ?? ''));
    }

    $rules = [
        'length' => strlen($password) >= 12,
        'lower' => preg_match('/[a-z]/', $password) === 1,
        'upper' => preg_match('/[A-Z]/', $password) === 1,
        'digit' => preg_match('/\d/', $password) === 1,
        'special' => preg_match('/[^a-zA-Z0-9]/', $password) === 1,
        'email' => $emailLocalPart === '' || strlen($emailLocalPart) < 4 || stripos($password, $emailLocalPart) === false,
    ];

    return [
        'rules' => $rules,
        'valid' => $rules['length'] && $rules['lower'] && $rules['upper'] && $rules['digit'] && $rules['special'],
    ];
}

function autoInstallPrepareDatabase(array $values)
{
    $serverPdo = autoInstallCreateServerPdo($values);
    autoInstallCreateDatabaseIfNeeded($serverPdo, (string)$values['DB_NAME']);

    $databasePdo = autoInstallCreateDatabasePdo($values);

    if (autoInstallDatabaseLooksEmpty($databasePdo)) {
        autoInstallImportSeed($databasePdo);
    } elseif (!autoInstallDatabaseLooksSeeded($databasePdo)) {
        throw new RuntimeException(
            'La base cible contient deja des tables qui ne correspondent pas au seed de demo. '
            . 'Utilisez une base vide ou nettoyez-la avant de relancer l installation.'
        );
    }

    autoInstallCreateOrUpdateAdminAccount($values);
}

function autoInstallCreateServerPdo(array $values)
{
    return new PDO(
        'mysql:host=' . (string)$values['DB_HOST'] . ';charset=utf8mb4',
        (string)$values['DB_USER'],
        (string)$values['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function autoInstallCreateDatabasePdo(array $values)
{
    return new PDO(
        'mysql:host=' . (string)$values['DB_HOST']
            . ';dbname=' . (string)$values['DB_NAME']
            . ';charset=utf8mb4',
        (string)$values['DB_USER'],
        (string)$values['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function autoInstallCreateDatabaseIfNeeded(PDO $pdo, $databaseName)
{
    $databaseName = trim((string)$databaseName);
    if ($databaseName === '') {
        throw new RuntimeException('Le nom de base ne peut pas etre vide.');
    }

    $pdo->exec(
        'CREATE DATABASE IF NOT EXISTS '
        . autoInstallQuoteMysqlIdentifier($databaseName)
        . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
}

function autoInstallQuoteMysqlIdentifier($value)
{
    return '`' . str_replace('`', '``', (string)$value) . '`';
}

function autoInstallDatabaseLooksEmpty(PDO $pdo)
{
    $statement = $pdo->query('SHOW TABLES');
    $tables = $statement->fetchAll(PDO::FETCH_NUM);
    $statement->closeCursor();

    return count($tables) === 0;
}

function autoInstallDatabaseLooksSeeded(PDO $pdo)
{
    if (!autoInstallTableExists($pdo, 'organization')) {
        return false;
    }

    if (!autoInstallTableExists($pdo, 'application')) {
        return false;
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM `organization` WHERE `shortname` IN (:org1, :org2)'
    );
    $statement->execute([
        'org1' => 'org1',
        'org2' => 'org2',
    ]);
    $count = (int)$statement->fetchColumn();
    $statement->closeCursor();

    return $count >= 2;
}

function autoInstallTableExists(PDO $pdo, $tableName)
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)'
        . ' FROM information_schema.tables'
        . ' WHERE table_schema = DATABASE()'
        . ' AND table_name = :table_name'
    );
    $statement->execute([
        'table_name' => (string)$tableName,
    ]);
    $exists = (int)$statement->fetchColumn() > 0;
    $statement->closeCursor();

    return $exists;
}

function autoInstallImportSeed(PDO $pdo)
{
    executeSqlFile($pdo, autoInstallGetSeedPath());
}

function autoInstallRegisterAutoloader()
{
    static $registered = false;

    if ($registered) {
        return;
    }

    spl_autoload_register(function ($class) {
        $path = dirname(__DIR__) . '/class/' . str_replace('\\', '/', strtolower((string)$class)) . '.class.php';
        if (is_file($path)) {
            require_once $path;
        }
    });

    $registered = true;
}

function autoInstallApplyTemporaryDbGlobals(array $values)
{
    $GLOBALS['dbName'] = (string)($values['DB_NAME'] ?? '');
    $GLOBALS['dbServer'] = (string)($values['DB_HOST'] ?? '');
    $GLOBALS['dbUser'] = (string)($values['DB_USER'] ?? '');
    $GLOBALS['dbPassword'] = (string)($values['DB_PASS'] ?? '');
}

function autoInstallCreateOrUpdateAdminAccount(array $values)
{
    autoInstallRegisterAutoloader();
    autoInstallApplyTemporaryDbGlobals($values);

    \dbObject\DbObject::refreshDbh();

    $email = trim((string)($values['INSTALL_ADMIN_EMAIL'] ?? ''));
    if ($email === '') {
        throw new RuntimeException('Impossible de creer le compte admin sans adresse e-mail.');
    }

    $user = new \dbObject\User();
    if (!$user->load(['email', $email])) {
        $user = new \dbObject\User();
    }

    $user->set('email', $email);
    $user->set('firstname', trim((string)($values['INSTALL_ADMIN_FIRSTNAME'] ?? '')));
    $user->set('lastname', trim((string)($values['INSTALL_ADMIN_LASTNAME'] ?? '')));
    $user->set('username', autoInstallResolveAdminUsername($values));
    $user->set('password', commonHashUserPassword((string)($values['INSTALL_ADMIN_PASSWORD'] ?? '')));
    $user->set('active', 1);
    $user->set('code', null);
    $user->set('codeexpiration', null);

    $saveResult = $user->save();
    if (empty($saveResult['status']) || (int)$user->getId() <= 0) {
        throw new RuntimeException('Impossible de creer le compte administrateur.');
    }

    $siteAdminResult = $user->setSiteAdmin(true);
    if (empty($siteAdminResult['status'])) {
        throw new RuntimeException('Impossible d attribuer le statut admin du site au compte cree.');
    }

    autoInstallEnsureAdminMemberships((int)$user->getId(), $email);
}

function autoInstallResolveAdminUsername(array $values)
{
    $username = trim((string)($values['INSTALL_ADMIN_USERNAME'] ?? ''));
    if ($username !== '') {
        return $username;
    }

    $email = trim((string)($values['INSTALL_ADMIN_EMAIL'] ?? ''));
    if ($email === '' || strpos($email, '@') === false) {
        return null;
    }

    $parts = explode('@', $email, 2);
    $candidate = trim((string)($parts[0] ?? ''));
    return $candidate !== '' ? $candidate : null;
}

function autoInstallEnsureAdminMemberships($userId, $email)
{
    $organizations = new \dbObject\ArrayOrganization();
    $organizations->load();

    foreach ($organizations as $organization) {
        $organizationId = (int)$organization->getId();
        if ($organizationId <= 0) {
            continue;
        }

        $membership = new \dbObject\UserOrganization();
        $loaded = $membership->load([
            ['IDuser', (int)$userId],
            ['IDorganization', $organizationId],
        ]);

        if (!$loaded) {
            $membership = new \dbObject\UserOrganization();
            $membership->set('IDuser', (int)$userId);
            $membership->set('IDorganization', $organizationId);
        }

        $membership->set('email', $email);
        $membership->set('active', 1);

        $saveResult = $membership->save();
        if (empty($saveResult['status'])) {
            throw new RuntimeException('Impossible de lier le compte admin aux organisations de demonstration.');
        }

        $adminResult = $membership->setOrganizationAdmin(true);
        if (empty($adminResult['status'])) {
            throw new RuntimeException('Impossible de definir le compte admin comme admin d organisation.');
        }
    }
}

function autoInstallWriteEnvFile($envPath, array $definitions, array $values)
{
    $lines = [];

    foreach ($definitions as $section) {
        $persistedFields = [];

        foreach ($section['fields'] as $field) {
            if (array_key_exists('persist', $field) && !$field['persist']) {
                continue;
            }

            $key = (string)$field['key'];
            $value = (string)($values[$key] ?? '');
            $isRequired = !empty($field['required']);

            if (!$isRequired && $value === '') {
                continue;
            }

            $persistedFields[] = $field;
        }

        if ($persistedFields === []) {
            continue;
        }

        $lines[] = '# ' . $section['title'];

        foreach ($persistedFields as $field) {
            $key = (string)$field['key'];
            $lines[] = $key . '=' . autoInstallEncodeEnvValue((string)($values[$key] ?? ''));
        }

        if ($lines[count($lines) - 1] !== '') {
            $lines[] = '';
        }
    }

    $content = rtrim(implode("\n", $lines)) . "\n";

    $bytes = @file_put_contents($envPath, $content, LOCK_EX);
    if ($bytes === false) {
        throw new RuntimeException('Impossible d ecrire le fichier .env.');
    }
}

function autoInstallEncodeEnvValue($value)
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

function autoInstallRenderPage(array $definitions, array $values, array $errors)
{
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Installation initiale</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="stylesheet" href="/common/assets/components.css">
    <style>
        :root {
            --color-primary: #0f766e;
            --color-primary-deep: #134e4a;
            --color-surface: #ffffff;
            --color-surface-alt: #f5f7f4;
            --color-border: #d6ddd6;
            --color-text: #16302b;
            --color-text-light: #52706a;
            --color-text-inverse: #ffffff;
            --shadow-sm: 0 14px 36px rgba(14, 36, 33, 0.08);
            --shadow-md: 0 18px 42px rgba(15, 118, 110, 0.18);
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Helvetica Neue", sans-serif;
            color: var(--color-text);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.12), transparent 34%),
                radial-gradient(circle at bottom right, rgba(20, 83, 45, 0.12), transparent 32%),
                linear-gradient(180deg, #eef6f2 0%, #f8fbf8 100%);
        }

        .auto-install-page {
            width: min(1080px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 44px;
        }

        .auto-install-hero {
            margin-bottom: 18px;
        }

        .auto-install-eyebrow {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--color-primary-deep);
            font-weight: 700;
        }

        .auto-install-title {
            margin: 6px 0 0;
            font-size: clamp(30px, 5vw, 46px);
            line-height: 1.04;
            color: #123530;
        }

        .auto-install-intro {
            margin: 10px 0 0;
            max-width: 720px;
            font-size: 16px;
            line-height: 1.6;
            color: var(--color-text-light);
        }

        .auto-install-layout {
            display: grid;
            grid-template-columns: minmax(0, 2.2fr) minmax(260px, 0.95fr);
            gap: 18px;
            align-items: start;
        }

        .auto-install-form {
            display: grid;
            gap: 16px;
        }

        .auto-install-section {
            --generic-section-gap: 14px;
            --generic-section-radius: 20px;
            --generic-section-padding-block: 20px;
            --generic-section-padding-inline: 20px;
            --generic-section-shadow: var(--shadow-sm);
        }

        .auto-install-sidebar {
            position: sticky;
            top: 20px;
            display: grid;
            gap: 16px;
        }

        .auto-install-stack {
            display: grid;
            gap: 12px;
        }

        .auto-install-section-header {
            display: grid;
            gap: 6px;
        }

        .auto-install-section-intro,
        .auto-install-note,
        .auto-install-meta {
            margin: 0;
            line-height: 1.55;
            color: var(--color-text-light);
        }

        .auto-install-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .auto-install-field,
        .auto-install-actions {
            display: grid;
            gap: 8px;
        }

        .auto-install-field--full {
            grid-column: 1 / -1;
        }

        .auto-install-label {
            font-size: 14px;
            font-weight: 700;
            color: #123530;
        }

        .auto-install-required {
            color: #b42318;
        }

        .auto-install-help {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
            color: var(--color-text-light);
        }

        .auto-install-errors {
            --generic-soft-panel-border: #f3b7b2;
            --generic-soft-panel-background: #fff1f0;
            color: #8a1c14;
        }

        .auto-install-password-panel {
            display: grid;
            gap: 10px;
            padding: 14px 16px;
            border: 1px solid var(--color-border);
            border-radius: 16px;
            background: rgba(15, 118, 110, 0.04);
        }

        .auto-install-password-status,
        .auto-install-password-match {
            font-size: 13px;
            line-height: 1.5;
            color: var(--color-text-light);
        }

        .auto-install-password-rules {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 6px;
            font-size: 13px;
            color: var(--color-text-light);
        }

        .auto-install-password-rule.is-valid,
        .auto-install-password-match.is-valid,
        .auto-install-password-status.is-valid {
            color: #0f6b43;
        }

        .auto-install-password-rule.is-invalid,
        .auto-install-password-match.is-invalid,
        .auto-install-password-status.is-invalid {
            color: #8a1c14;
        }

        .auto-install-error-list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 6px;
        }

        .auto-install-actions {
            justify-items: start;
        }

        .auto-install-actions .generic-action-button {
            min-width: 220px;
        }

        .auto-install-code {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.08);
            color: var(--color-primary-deep);
            font-family: Consolas, monospace;
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .auto-install-layout {
                grid-template-columns: 1fr;
            }

            .auto-install-sidebar {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .auto-install-page {
                width: min(100% - 20px, 1080px);
                padding-top: 18px;
            }

            .auto-install-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="auto-install-page">
        <section class="auto-install-hero generic-hero-panel generic-hero-panel--accent">
            <p class="auto-install-eyebrow">Premier demarrage</p>
            <h1 class="auto-install-title">Configuration initiale du site</h1>
            <p class="auto-install-intro">
                Aucun fichier <span class="auto-install-code">.env</span> n a ete detecte.
                Renseignez les parametres obligatoires pour demarrer le site. Les champs laisses vides utilisent les valeurs par defaut du projet quand elles existent.
            </p>
        </section>

        <div class="auto-install-layout">
            <form method="post" action="/install.php" class="auto-install-form">
                <?php if ($errors !== []): ?>
                    <div class="auto-install-errors generic-soft-panel generic-soft-panel--stack">
                        <h2 class="generic-card-title generic-card-title--medium">Configuration incomplete</h2>
                        <ul class="auto-install-error-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php foreach ($definitions as $sectionKey => $section): ?>
                    <section class="auto-install-section generic-section generic-section--stack">
                        <div class="auto-install-section-header">
                            <p class="generic-card-title generic-card-title--small"><?= htmlspecialchars((string)$section['title'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="auto-install-section-intro"><?= htmlspecialchars((string)$section['intro'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="auto-install-grid">
                            <?php foreach ($section['fields'] as $field): ?>
                                <?php
                                $key = (string)$field['key'];
                                $fieldType = (string)($field['type'] ?? 'text');
                                $fieldValue = (string)($values[$key] ?? '');
                                $fieldPlaceholder = (string)($field['placeholder'] ?? '');
                                $fieldClass = 'auto-install-field';
                                if ($fieldType === 'password' || $fieldType === 'text' || strlen($fieldValue) > 42 || !empty($field['help'])) {
                                    $fieldClass .= ' auto-install-field--full';
                                }
                                ?>
                                <label class="<?= $fieldClass ?>" for="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="auto-install-label">
                                        <?= htmlspecialchars((string)$field['label'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($field['required'])): ?>
                                            <span class="auto-install-required">*</span>
                                        <?php endif; ?>
                                    </span>

                                    <?php if ($fieldType === 'select'): ?>
                                        <select
                                            class="generic-form-control"
                                            name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                            id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <?php if (empty($field['required'])): ?>
                                                <option value=""<?= $fieldValue === '' ? ' selected' : '' ?>>Choisir si necessaire</option>
                                            <?php endif; ?>
                                            <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                                                <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>"<?= $fieldValue === (string)$optionValue ? ' selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input
                                            class="generic-form-control"
                                            type="<?= htmlspecialchars($fieldType, ENT_QUOTES, 'UTF-8') ?>"
                                            name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                            id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                            value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>"
                                            placeholder="<?= htmlspecialchars($fieldPlaceholder, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= !empty($field['required']) ? 'required' : '' ?>
                                            <?= $key === 'INSTALL_ADMIN_PASSWORD' ? 'minlength="12"' : '' ?>
                                            autocomplete="off"
                                        >
                                    <?php endif; ?>

                                    <?php if (!empty($field['help'])): ?>
                                        <span class="auto-install-help"><?= htmlspecialchars((string)$field['help'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>

                                    <?php if ($key === 'INSTALL_ADMIN_PASSWORD'): ?>
                                        <div class="auto-install-password-panel" data-password-panel>
                                            <span class="auto-install-password-status" data-password-status aria-live="polite">Le mot de passe doit respecter les criteres ci-dessous.</span>
                                            <ul class="auto-install-password-rules">
                                                <li class="auto-install-password-rule" data-password-rule="length">Au moins 12 caracteres</li>
                                                <li class="auto-install-password-rule" data-password-rule="lower">Au moins une minuscule</li>
                                                <li class="auto-install-password-rule" data-password-rule="upper">Au moins une majuscule</li>
                                                <li class="auto-install-password-rule" data-password-rule="digit">Au moins un chiffre</li>
                                                <li class="auto-install-password-rule" data-password-rule="special">Au moins un caractere special ou un espace</li>
                                                <li class="auto-install-password-rule" data-password-rule="email">Evitez de reprendre votre e-mail ou votre identifiant</li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($key === 'INSTALL_ADMIN_PASSWORD_CONFIRM'): ?>
                                        <span class="auto-install-password-match" data-password-match aria-live="polite">Retapez le meme mot de passe pour confirmation.</span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <section class="auto-install-section generic-section generic-section--stack">
                    <div class="auto-install-actions">
                        <button type="submit" class="generic-action-button generic-action-button--main">Creer le fichier .env</button>
                        <p class="auto-install-meta">
                            La connexion MySQL est testee avant l ecriture. Les services optionnels pourront etre completes plus tard en modifiant le fichier <span class="auto-install-code">.env</span>.
                        </p>
                    </div>
                </section>
            </form>

            <aside class="auto-install-sidebar">
                <section class="generic-section generic-section--stack">
                    <p class="generic-card-title generic-card-title--small">Ce qui se passe</p>
                    <div class="auto-install-stack">
                            <p class="auto-install-note">1. Vous renseignez les parametres de base.</p>
                        <p class="auto-install-note">2. Le site cree la base si besoin et importe le seed Docker de demo.</p>
                        <p class="auto-install-note">3. Le fichier <span class="auto-install-code">.env</span> est cree a la racine du projet.</p>
                        <p class="auto-install-note">4. Le chargement normal du site reprend avec l organisation exemple.</p>
                    </div>
                </section>

                <section class="generic-section generic-section--stack">
                    <p class="generic-card-title generic-card-title--small">A savoir</p>
                    <p class="auto-install-note">Les champs Patreon, Telegram, PayPal, OpenAI ou GitHub sont facultatifs. Les fonctions associees resteront simplement inactives tant que les cles ne sont pas definies.</p>
                    <p class="auto-install-note">La base cible doit etre vide ou deja correspondre au seed de demo. Sinon, l installation s arrete pour eviter d ecraser des donnees existantes.</p>
                </section>
            </aside>
        </div>
    </main>
    <script>
        (function () {
            var passwordInput = document.getElementById('INSTALL_ADMIN_PASSWORD');
            var emailInput = document.getElementById('INSTALL_ADMIN_EMAIL');
            var confirmInput = document.getElementById('INSTALL_ADMIN_PASSWORD_CONFIRM');
            var statusNode = document.querySelector('[data-password-status]');
            var matchNode = document.querySelector('[data-password-match]');
            var ruleNodes = document.querySelectorAll('[data-password-rule]');

            if (!passwordInput || !statusNode || ruleNodes.length === 0) {
                return;
            }

            function evaluatePassword(password, email) {
                var emailLocalPart = '';
                var atIndex = email.indexOf('@');

                if (atIndex > 0) {
                    emailLocalPart = email.slice(0, atIndex).toLowerCase();
                }

                return {
                    length: password.length >= 12,
                    lower: /[a-z]/.test(password),
                    upper: /[A-Z]/.test(password),
                    digit: /\d/.test(password),
                    special: /[^a-zA-Z0-9]/.test(password),
                    email: emailLocalPart.length < 4 || password.toLowerCase().indexOf(emailLocalPart) === -1
                };
            }

            function setNodeState(node, isValid, isInvalid) {
                node.classList.toggle('is-valid', isValid);
                node.classList.toggle('is-invalid', isInvalid);
            }

            function updatePasswordUi() {
                var password = passwordInput.value || '';
                var email = emailInput ? (emailInput.value || '') : '';
                var checks = evaluatePassword(password, email);
                var requiredKeys = ['length', 'lower', 'upper', 'digit', 'special'];
                var isEmpty = password.length === 0;
                var isValid = requiredKeys.every(function (key) {
                    return checks[key];
                });

                ruleNodes.forEach(function (node) {
                    var ruleName = node.getAttribute('data-password-rule');
                    var passed = !!checks[ruleName];
                    setNodeState(node, !isEmpty && passed, !isEmpty && !passed);
                });

                if (isEmpty) {
                    statusNode.textContent = 'Le mot de passe doit respecter les criteres ci-dessous.';
                    setNodeState(statusNode, false, false);
                } else if (isValid) {
                    statusNode.textContent = 'Mot de passe OK.';
                    setNodeState(statusNode, true, false);
                } else {
                    statusNode.textContent = 'Mot de passe encore incomplet.';
                    setNodeState(statusNode, false, true);
                }

                if (matchNode && confirmInput) {
                    var confirmation = confirmInput.value || '';

                    if (confirmation === '') {
                        matchNode.textContent = 'Retapez le meme mot de passe pour confirmation.';
                        setNodeState(matchNode, false, false);
                    } else if (confirmation === password) {
                        matchNode.textContent = 'Confirmation OK.';
                        setNodeState(matchNode, true, false);
                    } else {
                        matchNode.textContent = 'La confirmation ne correspond pas encore.';
                        setNodeState(matchNode, false, true);
                    }
                }
            }

            passwordInput.addEventListener('input', updatePasswordUi);
            if (emailInput) {
                emailInput.addEventListener('input', updatePasswordUi);
            }
            if (confirmInput) {
                confirmInput.addEventListener('input', updatePasswordUi);
            }

            updatePasswordUi();
        })();
    </script>
</body>
</html>
    <?php
}
