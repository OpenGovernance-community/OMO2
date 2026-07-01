<?php

function omoParametersIndexSourceLang()
{
    static $sourceLang = null;

    if (is_array($sourceLang)) {
        return $sourceLang;
    }

    $sourceLang = [
        'parameters.index.title' => ['text' => 'Paramètres', 'context' => 'Main title of the OMO settings hub.'],
        'parameters.index.description' => ['text' => "Retrouvez ici vos réglages personnels ainsi que les écrans de configuration disponibles pour l'organisation.", 'context' => 'Intro text shown at the top of the OMO settings hub.'],
        'parameters.index.empty.login' => ['text' => 'Connectez-vous pour accéder à vos paramètres utilisateur.', 'context' => 'Empty state shown in the settings hub when no user is connected.'],
        'parameters.index.card.profile.title' => ['text' => 'Profil', 'context' => 'Card title used to open the current user profile editor from the settings hub.'],
        'parameters.index.card.profile.description' => ['text' => "Ouvrir l'édition de votre profil.", 'context' => 'Card description used to open the current user profile editor from the settings hub.'],
        'parameters.index.card.organization.title' => ['text' => 'Organisation', 'context' => 'Card title used to open the organization settings editor from the settings hub.'],
        'parameters.index.card.organization.fallback_name' => ['text' => 'cette organisation', 'context' => 'Fallback organization name used in the settings hub when the current organization has no display name.'],
        'parameters.index.card.organization.description' => ['text' => 'Modifier le nom, le nom court, la position géographique, les illustrations et la couleur de {organizationName}.', 'context' => 'Card description used when the current user can edit the organization settings.'],
        'parameters.index.card.organization.forbidden' => ['text' => "Vous devez être admin de l'organisation pour modifier ces paramètres.", 'context' => 'Card description shown when the current user cannot edit the organization settings.'],
        'parameters.index.card.holon_templates.title' => ['text' => 'Modèles de holons', 'context' => 'Card title used to open the holon template settings editor from the settings hub.'],
        'parameters.index.card.holon_templates.description' => ['text' => 'Configurer les types de nœuds et leurs propriétés pour votre organisation.', 'context' => 'Card description used to open the holon template settings editor from the settings hub.'],
        'parameters.index.card.server_admin.title' => ['text' => 'Admin du serveur', 'context' => 'Card title used to open the server administration settings popup from the settings hub.'],
        'parameters.index.card.server_admin.description' => ['text' => 'Ouvrir les réglages globaux sensibles du fichier .env, hors configuration de la base de données.', 'context' => 'Card description used to open the sensitive server environment settings popup from the settings hub.'],
        'parameters.index.drawer.loading' => ['text' => 'Chargement...', 'context' => 'Loading placeholder shown while a nested settings drawer is fetching its content.'],
        'parameters.index.drawer.error' => ['text' => 'Impossible de charger ce module.', 'context' => 'Error shown inside the nested settings drawer when the requested content cannot be loaded.'],
        'parameters.index.action.close' => ['text' => 'Fermer', 'context' => 'Close button label used in the nested settings drawer header.'],
    ];

    return $sourceLang;
}

function omoParametersIndexLang()
{
    static $lang = null;

    if ($lang === null) {
        $lang = omoLoadTranslationBundle('omo_parameters_index', omoParametersIndexSourceLang());
    }

    return $lang;
}

function omoParametersIndexT($key, array $replace = [])
{
    return t($key, $replace, omoParametersIndexLang(), omoParametersIndexSourceLang());
}

function omoServerEnvSourceLang()
{
    static $sourceLang = null;

    if (is_array($sourceLang)) {
        return $sourceLang;
    }

    $sourceLang = [
        'parameters.server_env.auth.required_title' => ['text' => 'Connexion requise', 'context' => 'Title shown in the server environment popup when the user is not connected.'],
        'parameters.server_env.auth.required_message' => ['text' => 'Connectez-vous pour accéder à ce panneau.', 'context' => 'Message shown in the server environment popup when the user is not connected.'],
        'parameters.server_env.auth.forbidden_title' => ['text' => 'Accès refusé', 'context' => 'Title shown in the server environment popup when the current user is not a site admin.'],
        'parameters.server_env.auth.forbidden_message' => ['text' => "Ce panneau est réservé à l'admin du serveur.", 'context' => 'Message shown in the server environment popup when the current user is not a site admin.'],
        'parameters.server_env.hero.eyebrow' => ['text' => 'Configuration sensible', 'context' => 'Small eyebrow shown above the server environment popup title.'],
        'parameters.server_env.hero.title' => ['text' => 'Admin du serveur', 'context' => 'Main title of the server environment popup.'],
        'parameters.server_env.hero.description' => ['text' => "Ce panneau permet de compléter les variables globales du fichier d'environnement hors base de données, comme Telegram, Patreon, OpenAI, SMTP ou GitHub.", 'context' => 'Intro text shown in the server environment popup.'],
        'parameters.server_env.hero.target' => ['text' => 'Fichier cible : {target}', 'context' => 'Badge showing the target .env file edited by the server environment popup.'],
        'parameters.server_env.hero.unlock_ttl' => ['text' => 'Vérification valable {minutes} min', 'context' => 'Badge showing how long the password confirmation remains valid in the server environment popup.'],
        'parameters.server_env.password.unavailable_title' => ['text' => 'Mot de passe indisponible', 'context' => 'Title shown when the current account has no local password for server environment editing.'],
        'parameters.server_env.password.unavailable_message' => ['text' => "Ce compte n'a pas de mot de passe local vérifiable. L'édition de {target} via ce panneau est donc bloquée pour le moment.", 'context' => 'Message shown when the current account has no local password for server environment editing.'],
        'parameters.server_env.unlock.title' => ['text' => 'Vérifier votre identité', 'context' => 'Title shown before unlocking the server environment popup form.'],
        'parameters.server_env.unlock.description' => ['text' => "Avant d'afficher le formulaire, saisissez le mot de passe du compte connecté. Cela déverrouille temporairement l'édition de ce panneau.", 'context' => 'Intro text shown before unlocking the server environment popup form.'],
        'parameters.server_env.unlock.password_label' => ['text' => 'Mot de passe actuel', 'context' => 'Label of the password field used to unlock the server environment popup.'],
        'parameters.server_env.unlock.submit' => ['text' => 'Ouvrir le formulaire', 'context' => 'Submit button label used to unlock the server environment popup form.'],
        'parameters.server_env.edit.title' => ['text' => 'Modifier {target}', 'context' => 'Title shown above the editable .env form.'],
        'parameters.server_env.edit.secret_hint' => ['text' => 'Les champs secrets restent masqués. Si vous laissez un champ secret vide, la valeur actuelle est conservée.', 'context' => 'Hint shown above the editable .env form.'],
        'parameters.server_env.secret.configured' => ['text' => 'Déjà configuré', 'context' => 'Status label shown next to a configured secret field in the server environment popup.'],
        'parameters.server_env.secret.empty' => ['text' => 'Non renseigné', 'context' => 'Status label shown next to an empty secret field in the server environment popup.'],
        'parameters.server_env.action.close' => ['text' => 'Fermer', 'context' => 'Close button label used in the server environment popup.'],
        'parameters.server_env.action.save' => ['text' => 'Enregistrer {target}', 'context' => 'Submit button label used to save the editable .env form.'],
        'parameters.server_env.feedback.invalid_response' => ['text' => 'Réponse invalide.', 'context' => 'Error shown when the server environment popup receives an invalid JSON response.'],
        'parameters.server_env.feedback.unlock_failed' => ['text' => 'Vérification impossible.', 'context' => 'Error shown when the password verification failed unexpectedly in the server environment popup.'],
        'parameters.server_env.feedback.unlock_success' => ['text' => 'Vérification effectuée.', 'context' => 'Success message shown after unlocking the server environment popup form.'],
        'parameters.server_env.feedback.operation_done' => ['text' => 'Opération terminée.', 'context' => 'Generic fallback message shown after saving the server environment popup form.'],
        'parameters.server_env.feedback.save_failed' => ['text' => "Impossible d'enregistrer le fichier {target}.", 'context' => 'Error shown when saving the .env file failed unexpectedly in the server environment popup.'],
        'parameters.server_env.section.general.title' => ['text' => 'Paramètres généraux', 'context' => 'Title of the general section in the server environment editor.'],
        'parameters.server_env.section.general.intro' => ['text' => 'Réglages globaux du site visibles sur plusieurs pages.', 'context' => 'Intro of the general section in the server environment editor.'],
        'parameters.server_env.section.mail.title' => ['text' => 'E-mail', 'context' => 'Title of the mail section in the server environment editor.'],
        'parameters.server_env.section.mail.intro' => ['text' => 'Configuration SMTP générale du serveur.', 'context' => 'Intro of the mail section in the server environment editor.'],
        'parameters.server_env.section.ai.title' => ['text' => 'IA', 'context' => 'Title of the AI section in the server environment editor.'],
        'parameters.server_env.section.ai.intro' => ['text' => 'Clés et modèles utilisés par les fonctions OpenAI.', 'context' => 'Intro of the AI section in the server environment editor.'],
        'parameters.server_env.section.integrations.title' => ['text' => 'Intégrations', 'context' => 'Title of the integrations section in the server environment editor.'],
        'parameters.server_env.section.integrations.intro' => ['text' => 'Services externes optionnels du serveur.', 'context' => 'Intro of the integrations section in the server environment editor.'],
        'parameters.server_env.field.SITE_TITLE.label' => ['text' => 'Titre du site', 'context' => 'Label of the SITE_TITLE field in the server environment editor.'],
        'parameters.server_env.field.HOME_TITLE.label' => ['text' => "Titre de la page d'accueil", 'context' => 'Label of the HOME_TITLE field in the server environment editor.'],
        'parameters.server_env.field.APP_LANG.label' => ['text' => 'Langue par défaut', 'context' => 'Label of the APP_LANG field in the server environment editor.'],
        'parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.label' => ['text' => 'Sous-domaines par organisation', 'context' => 'Label of the ORGANIZATION_SUBDOMAIN_ROUTING field in the server environment editor.'],
        'parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.help' => ['text' => "Active les URL du type orgname.domaine.com. Cela demande une configuration spéciale de l'hébergement, avec DNS wildcard et serveur web capable d'accepter les sous-domaines.", 'context' => 'Help text of the ORGANIZATION_SUBDOMAIN_ROUTING field in the server environment editor.'],
        'parameters.server_env.field.COOKIE_SCOPE_MODE.label' => ['text' => 'Portée des cookies', 'context' => 'Label of the COOKIE_SCOPE_MODE field in the server environment editor.'],
        'parameters.server_env.field.COOKIE_SCOPE_MODE.help' => ['text' => 'Auto isole par défaut dev, beta et deploy en host-only. Environment partage dans *.dev.domaine.tld. Parent partage dans *.domaine.tld. Host force un cookie limité au host courant.', 'context' => 'Help text of the COOKIE_SCOPE_MODE field in the server environment editor.'],
        'parameters.server_env.field.COOKIE_ROOT_HOST.label' => ['text' => 'Racine cookies', 'context' => 'Label of the COOKIE_ROOT_HOST field in the server environment editor.'],
        'parameters.server_env.field.COOKIE_ROOT_HOST.help' => ['text' => 'Optionnel. Si renseigné, force le partage des cookies à cette racine exacte, par exemple dev.opengov.tools pour partager entre dev.opengov.tools et *.dev.opengov.tools sans toucher à la prod.', 'context' => 'Help text of the COOKIE_ROOT_HOST field in the server environment editor.'],
        'parameters.server_env.field.MAIL_HOST.label' => ['text' => 'Serveur SMTP', 'context' => 'Label of the MAIL_HOST field in the server environment editor.'],
        'parameters.server_env.field.MAIL_PORT.label' => ['text' => 'Port SMTP', 'context' => 'Label of the MAIL_PORT field in the server environment editor.'],
        'parameters.server_env.field.MAIL_SECURE.label' => ['text' => 'Sécurité SMTP', 'context' => 'Label of the MAIL_SECURE field in the server environment editor.'],
        'parameters.server_env.field.MAIL_SECURE.placeholder' => ['text' => 'SSL, tls ou vide', 'context' => 'Placeholder of the MAIL_SECURE field in the server environment editor.'],
        'parameters.server_env.field.MAIL_AUTH.label' => ['text' => 'Authentification SMTP', 'context' => 'Label of the MAIL_AUTH field in the server environment editor.'],
        'parameters.server_env.field.MAIL_CHARSET.label' => ['text' => 'Jeu de caractères e-mail', 'context' => 'Label of the MAIL_CHARSET field in the server environment editor.'],
        'parameters.server_env.field.MAIL_USER.label' => ['text' => 'Utilisateur SMTP', 'context' => 'Label of the MAIL_USER field in the server environment editor.'],
        'parameters.server_env.field.MAIL_PASS.label' => ['text' => 'Mot de passe SMTP', 'context' => 'Label of the MAIL_PASS field in the server environment editor.'],
        'parameters.server_env.field.OPENAI_API_KEY.label' => ['text' => 'Clé OpenAI', 'context' => 'Label of the OPENAI_API_KEY field in the server environment editor.'],
        'parameters.server_env.field.OPENAI_UPLOAD_API_KEY.label' => ['text' => 'Clé OpenAI upload', 'context' => 'Label of the OPENAI_UPLOAD_API_KEY field in the server environment editor.'],
        'parameters.server_env.field.OPENAI_MODEL.label' => ['text' => 'Modèle OpenAI', 'context' => 'Label of the OPENAI_MODEL field in the server environment editor.'],
        'parameters.server_env.field.OPENAI_TRANSLATION_MODEL.label' => ['text' => 'Modèle de traduction OpenAI', 'context' => 'Label of the OPENAI_TRANSLATION_MODEL field in the server environment editor.'],
        'parameters.server_env.field.STADIA_MAPS_API_KEY.label' => ['text' => 'Clé Stadia Maps', 'context' => 'Label of the STADIA_MAPS_API_KEY field in the server environment editor.'],
        'parameters.server_env.field.PAYPAL_CLIENT_ID.label' => ['text' => 'Client ID PayPal', 'context' => 'Label of the PAYPAL_CLIENT_ID field in the server environment editor.'],
        'parameters.server_env.field.TELEGRAM_BOT_TOKEN.label' => ['text' => 'Token Telegram', 'context' => 'Label of the TELEGRAM_BOT_TOKEN field in the server environment editor.'],
        'parameters.server_env.field.PATREON_CLIENT_ID.label' => ['text' => 'Client ID Patreon', 'context' => 'Label of the PATREON_CLIENT_ID field in the server environment editor.'],
        'parameters.server_env.field.PATREON_CLIENT_SECRET.label' => ['text' => 'Client secret Patreon', 'context' => 'Label of the PATREON_CLIENT_SECRET field in the server environment editor.'],
        'parameters.server_env.field.PATREON_REDIRECT_URI.label' => ['text' => 'Redirect URI Patreon', 'context' => 'Label of the PATREON_REDIRECT_URI field in the server environment editor.'],
        'parameters.server_env.field.PATREON_CREATOR_CAMPAIGN_ID.label' => ['text' => 'Campaign ID Patreon', 'context' => 'Label of the PATREON_CREATOR_CAMPAIGN_ID field in the server environment editor.'],
        'parameters.server_env.field.PATREON_USER_AGENT.label' => ['text' => 'User-Agent Patreon', 'context' => 'Label of the PATREON_USER_AGENT field in the server environment editor.'],
        'parameters.server_env.field.GITHUB_BUGREPORT_TOKEN.label' => ['text' => 'Token GitHub bug report', 'context' => 'Label of the GITHUB_BUGREPORT_TOKEN field in the server environment editor.'],
        'parameters.server_env.field.GITHUB_BUGREPORT_REPO_OWNER.label' => ['text' => 'Repository owner GitHub', 'context' => 'Label of the GITHUB_BUGREPORT_REPO_OWNER field in the server environment editor.'],
        'parameters.server_env.field.GITHUB_BUGREPORT_REPO_NAME.label' => ['text' => 'Repository name GitHub', 'context' => 'Label of the GITHUB_BUGREPORT_REPO_NAME field in the server environment editor.'],
        'parameters.server_env.field.GITHUB_BUGREPORT_LABELS.label' => ['text' => 'Labels GitHub', 'context' => 'Label of the GITHUB_BUGREPORT_LABELS field in the server environment editor.'],
        'parameters.server_env.field.GITHUB_BUGREPORT_USER_AGENT.label' => ['text' => 'User-Agent GitHub', 'context' => 'Label of the GITHUB_BUGREPORT_USER_AGENT field in the server environment editor.'],
        'parameters.server_env.field.secret_keep.help' => ['text' => 'Laissez vide pour conserver la valeur actuelle.', 'context' => 'Help text shown under secret fields in the server environment editor.'],
        'parameters.server_env.option.boolean.true' => ['text' => 'Oui', 'context' => 'Yes option label used in boolean selects in the server environment editor.'],
        'parameters.server_env.option.boolean.false' => ['text' => 'Non', 'context' => 'No option label used in boolean selects in the server environment editor.'],
        'parameters.server_env.error.required' => ['text' => 'Connexion requise.', 'context' => 'JSON error returned when the current user is not connected to use the server environment endpoints.'],
        'parameters.server_env.error.forbidden' => ['text' => "Accès réservé à l'admin du serveur.", 'context' => 'JSON error returned when the current user is not allowed to use the server environment endpoints.'],
        'parameters.server_env.error.password_required' => ['text' => 'Veuillez renseigner votre mot de passe.', 'context' => 'JSON error returned when no password was provided to unlock the server environment endpoints.'],
        'parameters.server_env.error.password_unavailable' => ['text' => "Ce compte ne dispose pas de mot de passe local vérifiable.", 'context' => 'JSON error returned when the current account has no local password for the server environment endpoints.'],
        'parameters.server_env.error.password_invalid' => ['text' => 'Mot de passe invalide.', 'context' => 'JSON error returned when the provided password is invalid for the server environment endpoints.'],
        'parameters.server_env.status.unlocked' => ['text' => 'Vérification effectuée.', 'context' => 'JSON success message returned after unlocking the server environment endpoints.'],
        'parameters.server_env.error.unlock_required' => ['text' => 'Confirmation du mot de passe requise.', 'context' => 'JSON error returned when the server environment save endpoint requires a password confirmation.'],
        'parameters.server_env.status.saved' => ['text' => 'Le fichier {target} a été mis à jour.', 'context' => 'JSON success message returned after saving the server environment file.'],
        'parameters.server_env.error.invalid_field_value' => ['text' => 'La valeur choisie pour {label} est invalide.', 'context' => 'Validation error returned when a field value is invalid in the server environment editor.'],
        'parameters.server_env.error.read_failed' => ['text' => 'Impossible de lire le fichier {target}.', 'context' => 'Error returned when the server environment target file cannot be read.'],
        'parameters.server_env.error.write_failed' => ['text' => "Impossible d'écrire le fichier {target}. Vérifiez les permissions ou un montage Docker en lecture seule.", 'context' => 'Error returned when the server environment target file cannot be written.'],
    ];

    return $sourceLang;
}

function omoServerEnvLang()
{
    static $lang = null;

    if ($lang === null) {
        $lang = omoLoadTranslationBundle('omo_parameters_server_env', omoServerEnvSourceLang());
    }

    return $lang;
}

function omoServerEnvT($key, array $replace = [])
{
    return t($key, $replace, omoServerEnvLang(), omoServerEnvSourceLang());
}

function omoSiteUpdateSourceLang()
{
    static $sourceLang = null;

    if (is_array($sourceLang)) {
        return $sourceLang;
    }

    $sourceLang = [
        'parameters.site_update.error.required' => ['text' => 'Connexion requise.', 'context' => 'JSON error returned when the current user is not connected to use the site update endpoints.'],
        'parameters.site_update.error.forbidden' => ['text' => "Accès réservé à l'admin du site.", 'context' => 'JSON error returned when the current user is not allowed to use the site update endpoints.'],
        'parameters.site_update.error.method_not_allowed' => ['text' => 'Méthode non autorisée.', 'context' => 'JSON error returned when the site update run endpoint is called with an unsupported HTTP method.'],
        'parameters.site_update.error.prepare_runtime' => ['text' => 'Impossible de préparer le dossier temporaire de mise à jour.', 'context' => 'Error returned when the site update runtime directory cannot be prepared.'],
        'parameters.site_update.error.exec_unavailable' => ['text' => 'Les commandes système sont indisponibles sur ce serveur.', 'context' => 'Error returned when the site update worker cannot use exec on the current server.'],
        'parameters.site_update.error.repo_access' => ['text' => "Impossible d'accéder au dossier du dépôt Git.", 'context' => 'Error returned when the site update worker cannot enter the Git repository.'],
        'parameters.site_update.status.already_running' => ['text' => 'Une mise à jour est déjà en cours.', 'context' => 'Message returned when a site update is already running.'],
        'parameters.site_update.error.remote_unreachable' => ['text' => 'Impossible de contacter le dépôt distant.', 'context' => 'Error returned when the site update worker cannot fetch the remote repository.'],
        'parameters.site_update.error.remote_commit' => ['text' => 'Impossible de déterminer le commit distant.', 'context' => 'Error returned when the site update worker cannot determine the remote commit.'],
        'parameters.site_update.status.available' => ['text' => 'Une mise à jour est disponible.', 'context' => 'Short message returned when a site update is available.'],
        'parameters.site_update.status.available_headline' => ['text' => 'Une mise à jour est disponible : {headline}', 'context' => 'Detailed message returned when a site update is available with a headline.'],
        'parameters.site_update.error.not_supported' => ['text' => "La mise à jour automatique n'est pas disponible sur ce serveur.", 'context' => 'Error returned when automatic site updates are not supported on the current server.'],
        'parameters.site_update.error.local_changes' => ['text' => 'Le dépôt contient des modifications locales. La mise à jour automatique est bloquée pour éviter un écrasement.', 'context' => 'Error returned when automatic site updates are blocked because the repository contains local changes.'],
        'parameters.site_update.status.running' => ['text' => 'Mise à jour en cours.', 'context' => 'Message returned when a site update has started.'],
        'parameters.site_update.status.already_current' => ['text' => 'Le site est déjà à jour.', 'context' => 'Message returned when the site is already up to date.'],
        'parameters.site_update.error.sync_failed' => ['text' => 'Impossible de synchroniser le code avec le dépôt distant.', 'context' => 'Error returned when Git reset failed during the site update.'],
        'parameters.site_update.status.migrations_running' => ['text' => 'Synchronisation du code terminée. Application des migrations en cours.', 'context' => 'Message returned after the code sync completed and SQL migrations are starting.'],
        'parameters.site_update.error.migrations_failed' => ['text' => 'Les migrations SQL ont échoué.', 'context' => 'Error returned when SQL migrations failed during the site update.'],
        'parameters.site_update.status.completed' => ['text' => 'La mise à jour du site est terminée.', 'context' => 'Message returned after the site update completed successfully.'],
        'parameters.site_update.banner.eyebrow' => ['text' => 'Admin du site', 'context' => 'Eyebrow shown in the site update banner.'],
        'parameters.site_update.banner.dismiss' => ['text' => 'Ignorer', 'context' => 'Dismiss button label used in the site update banner.'],
        'parameters.site_update.banner.install' => ['text' => 'Installer', 'context' => 'Primary action label used in the site update banner when an update is available.'],
        'parameters.site_update.banner.installing' => ['text' => 'Installation...', 'context' => 'Primary action label used in the site update banner while an update is being installed.'],
        'parameters.site_update.banner.retry' => ['text' => 'Réessayer', 'context' => 'Primary action label used in the site update banner after a failed installation attempt.'],
        'parameters.site_update.banner.running_title' => ['text' => 'Mise à jour en cours', 'context' => 'Banner title shown while a site update is running.'],
        'parameters.site_update.banner.running_message' => ['text' => 'Un autre admin est en train de synchroniser le site.', 'context' => 'Banner message shown when another admin is already running a site update.'],
        'parameters.site_update.banner.available_title_one' => ['text' => 'Nouvelle version disponible', 'context' => 'Banner title shown when a single site update is available.'],
        'parameters.site_update.banner.available_title_other' => ['text' => '{count} mises à jour disponibles', 'context' => 'Banner title shown when several site updates are available.'],
        'parameters.site_update.banner.available_message' => ['text' => 'Une version plus récente du site peut être installée maintenant.', 'context' => 'Fallback banner message shown when a site update is available.'],
        'parameters.site_update.banner.confirm' => ['text' => 'Installer la nouvelle version du site maintenant ?', 'context' => 'Confirmation message shown before launching the site update.'],
        'parameters.site_update.banner.install_message' => ['text' => 'Synchronisation du code et exécution des migrations SQL...', 'context' => 'Banner message shown while a site update is being installed.'],
        'parameters.site_update.banner.invalid_response' => ['text' => 'Réponse invalide.', 'context' => 'Error shown when the site update banner receives an invalid JSON response.'],
        'parameters.site_update.banner.failed' => ['text' => 'La mise à jour a échoué.', 'context' => 'Fallback error shown when launching the site update failed.'],
        'parameters.site_update.banner.completed_title' => ['text' => 'Mise à jour terminée', 'context' => 'Banner title shown after the site update completed successfully.'],
        'parameters.site_update.banner.already_current_title' => ['text' => 'Site déjà à jour', 'context' => 'Banner title shown when the site was already up to date.'],
        'parameters.site_update.banner.completed_message' => ['text' => 'La mise à jour du site est terminée.', 'context' => 'Fallback banner message shown after the site update completed successfully.'],
        'parameters.site_update.banner.failed_title' => ['text' => 'Mise à jour impossible', 'context' => 'Banner title shown after a failed site update attempt.'],
        'parameters.site_update.banner.failed_message' => ['text' => "La mise à jour du site a échoué.", 'context' => 'Fallback banner message shown after a failed site update attempt.'],
        'parameters.site_update.banner.meta.version_one' => ['text' => '1 version', 'context' => 'Meta pill shown in the site update banner when one remote version is pending.'],
        'parameters.site_update.banner.meta.version_other' => ['text' => '{count} versions', 'context' => 'Meta pill shown in the site update banner when several remote versions are pending.'],
        'parameters.site_update.banner.meta.remote_date' => ['text' => 'Dernière version : {date}', 'context' => 'Meta pill shown in the site update banner with the remote release date.'],
        'parameters.site_update.banner.meta.local_date' => ['text' => 'Version installée : {date}', 'context' => 'Meta pill shown in the site update banner with the installed local version date.'],
    ];

    return $sourceLang;
}

function omoSiteUpdateLang()
{
    static $lang = null;

    if ($lang === null) {
        $lang = omoLoadTranslationBundle('omo_parameters_site_update', omoSiteUpdateSourceLang());
    }

    return $lang;
}

function omoSiteUpdateT($key, array $replace = [])
{
    return t($key, $replace, omoSiteUpdateLang(), omoSiteUpdateSourceLang());
}

function omoHolonTemplateSourceLang()
{
    static $sourceLang = null;

    if (is_array($sourceLang)) {
        return $sourceLang;
    }

    $sourceLang = [
        'parameters.holon_templates.error.no_organization' => ['text' => "Aucune organisation n'est actuellement sélectionnée.", 'context' => 'Error shown when no organization is selected in the holon template editor.'],
        'parameters.holon_templates.error.organization_not_found' => ['text' => "L'organisation demandée est introuvable.", 'context' => 'Error shown when the selected organization cannot be loaded in the holon template editor.'],
        'parameters.holon_templates.error.structure_required' => ['text' => "Les modèles de holons sont disponibles uniquement lorsqu'une structure existe et que l'app Structure est active.", 'context' => 'Error shown when the structure app is not available for the holon template editor.'],
        'parameters.holon_templates.header.organization_title' => ['text' => "Propriétés de l'organisation", 'context' => 'Header title shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.header.organization_description' => ['text' => "Modifiez ici les propriétés, illustrations et réglages locaux du holon d'organisation, même lorsqu'il ne s'agit pas d'un template.", 'context' => 'Header description shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.scope.aria' => ['text' => 'Portée des modèles', 'context' => 'ARIA label of the scope switch in the holon template editor.'],
        'parameters.holon_templates.scope.contextual' => ['text' => 'Contextuel', 'context' => 'Contextual scope label in the holon template editor.'],
        'parameters.holon_templates.scope.global' => ['text' => 'Global', 'context' => 'Global scope label in the holon template editor.'],
        'parameters.holon_templates.scope.note_global_prefix' => ['text' => 'Mode global :', 'context' => 'Prefix shown before the global scope note in the holon template editor.'],
        'parameters.holon_templates.scope.note_global' => ['text' => "tous les modèles de l'organisation sont affichés ici, quel que soit leur holon de définition.", 'context' => 'Global scope note shown in the holon template editor.'],
        'parameters.holon_templates.scope.note_contextual_prefix' => ['text' => 'Mode contextuel :', 'context' => 'Prefix shown before the contextual scope note in the holon template editor.'],
        'parameters.holon_templates.scope.note_contextual' => ['text' => 'seuls les modèles utiles au contexte {contextName} sont affichés ici.', 'context' => 'Contextual scope note shown in the holon template editor.'],
        'parameters.holon_templates.scope.current_context_fallback' => ['text' => 'courant', 'context' => 'Fallback context name used in the contextual scope note of the holon template editor.'],
        'parameters.holon_templates.hero.current_context' => ['text' => 'Contexte actuel', 'context' => 'Eyebrow shown in the sidebar hero of the holon template editor.'],
        'parameters.holon_templates.hero.definition_mode' => ['text' => 'Ce panneau agit comme un éditeur de définitions locales pour ce holon réel.', 'context' => 'Sidebar description shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.hero.template_mode' => ['text' => 'Créez une bibliothèque de modèles réutilisables pour vos cercles, rôles, projets et autres structures.', 'context' => 'Sidebar description shown in template mode for the holon template editor.'],
        'parameters.holon_templates.action.new_model' => ['text' => 'Nouveau modèle', 'context' => 'Primary button label used to create a new root holon template.'],
        'parameters.holon_templates.action.new_submodel' => ['text' => 'Sous-modèle', 'context' => 'Secondary button label used to create a child holon template.'],
        'parameters.holon_templates.tree.current_holon' => ['text' => 'Holon édité', 'context' => 'Sidebar tree title shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.tree.models' => ['text' => 'Arborescence des modèles', 'context' => 'Sidebar tree title shown in template mode for the holon template editor.'],
        'parameters.holon_templates.form.eyebrow' => ['text' => 'Édition', 'context' => 'Eyebrow shown above the form header in the holon template editor.'],
        'parameters.holon_templates.form.organization' => ['text' => 'Organisation', 'context' => 'Form title used in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.form.new_model' => ['text' => 'Nouveau modèle', 'context' => 'Form title used when creating a new holon template.'],
        'parameters.holon_templates.form.organization_description' => ['text' => "Ajustez ici les propriétés locales de cette organisation.", 'context' => 'Form description shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.form.new_model_description' => ['text' => "Choisissez un type de base, sa place dans l'arborescence et les propriétés qu'il transmettra.", 'context' => 'Form description shown when creating a new holon template.'],
        'parameters.holon_templates.section.holon' => ['text' => 'Holon', 'context' => 'Section title shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.section.structure' => ['text' => 'Structure du modèle', 'context' => 'Section title shown in template mode for the holon template editor.'],
        'parameters.holon_templates.field.base_type' => ['text' => 'Type de base', 'context' => 'Label of the base type select in the holon template editor.'],
        'parameters.holon_templates.field.inherits_from' => ['text' => 'Hérite de', 'context' => 'Label of the parent template select in the holon template editor.'],
        'parameters.holon_templates.field.name' => ['text' => 'Nom', 'context' => 'Generic name label used in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.field.model_name' => ['text' => 'Nom du modèle', 'context' => 'Name label used in template mode for the holon template editor.'],
        'parameters.holon_templates.flag.visible' => ['text' => 'Visible', 'context' => 'Visibility checkbox label in the holon template editor.'],
        'parameters.holon_templates.flag.visible_help' => ['text' => 'Afficher ce template dans le cercle où il est défini.', 'context' => 'Visibility checkbox help in the holon template editor.'],
        'parameters.holon_templates.flag.mandatory' => ['text' => 'Obligatoire', 'context' => 'Mandatory checkbox label in the holon template editor.'],
        'parameters.holon_templates.flag.mandatory_help' => ['text' => 'Indique que les sous-cercles devront implémenter ce template.', 'context' => 'Mandatory checkbox help in the holon template editor.'],
        'parameters.holon_templates.flag.locked_name' => ['text' => 'Nom verrouillé', 'context' => 'Locked name checkbox label in the holon template editor.'],
        'parameters.holon_templates.flag.locked_name_help' => ['text' => 'Impose le même nom à toutes les instances de ce template.', 'context' => 'Locked name checkbox help in the holon template editor.'],
        'parameters.holon_templates.flag.unique' => ['text' => 'Unique', 'context' => 'Unique checkbox label in the holon template editor.'],
        'parameters.holon_templates.flag.unique_help' => ['text' => 'Limite à une seule implémentation par cercle, groupes compris.', 'context' => 'Unique checkbox help in the holon template editor.'],
        'parameters.holon_templates.flag.link' => ['text' => 'Lien', 'context' => 'Link checkbox label in the holon template editor.'],
        'parameters.holon_templates.flag.link_help' => ['text' => 'Indique que le rôle appartient aussi au cercle englobant.', 'context' => 'Link checkbox help in the holon template editor.'],
        'parameters.holon_templates.section.properties' => ['text' => 'Propriétés', 'context' => 'Properties section title in the holon template editor.'],
        'parameters.holon_templates.section.properties_description_organization' => ['text' => 'Ajoutez ici les propriétés directement portées par cette organisation.', 'context' => 'Properties section description shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.section.properties_description_model' => ['text' => 'Ajoutez les propriétés visibles sur les nœuds dérivés de ce modèle.', 'context' => 'Properties section description shown in template mode for the holon template editor.'],
        'parameters.holon_templates.action.add_property' => ['text' => 'Ajouter une propriété', 'context' => 'Button label used to add a property in the holon template editor.'],
        'parameters.holon_templates.section.permissions' => ['text' => 'Droits', 'context' => 'Permissions section title in the holon template editor.'],
        'parameters.holon_templates.section.permissions_description' => ['text' => 'Activez ici les droits portés par ce template et choisissez leurs portées.', 'context' => 'Permissions section description in the holon template editor.'],
        'parameters.holon_templates.section.appearance' => ['text' => 'Apparence', 'context' => 'Appearance section title in the holon template editor.'],
        'parameters.holon_templates.section.appearance_description' => ['text' => "La couleur, l'icône et la bannière viennent après la définition des propriétés.", 'context' => 'Appearance section description in the holon template editor.'],
        'parameters.holon_templates.field.color' => ['text' => 'Couleur', 'context' => 'Color field label in the holon template editor.'],
        'parameters.holon_templates.field.override' => ['text' => 'Redéfinir', 'context' => 'Checkbox label used to enable a local override in the holon template editor.'],
        'parameters.holon_templates.field.color_empty_help' => ['text' => 'Sinon la couleur reste vide.', 'context' => 'Help text shown under the color field in the holon template editor.'],
        'parameters.holon_templates.field.shared_media' => ['text' => 'Illustrations transmises', 'context' => 'Title shown above the transmitted media cards in the holon template editor.'],
        'parameters.holon_templates.field.icon' => ['text' => 'Icône', 'context' => 'Icon card title in the holon template editor.'],
        'parameters.holon_templates.field.locked_icon' => ['text' => 'Icône verrouillée', 'context' => 'Locked icon checkbox label in the holon template editor.'],
        'parameters.holon_templates.field.banner' => ['text' => 'Bannière', 'context' => 'Banner card title in the holon template editor.'],
        'parameters.holon_templates.field.locked_banner' => ['text' => 'Bannière verrouillée', 'context' => 'Locked banner checkbox label in the holon template editor.'],
        'parameters.holon_templates.section.public_share' => ['text' => 'Partage public', 'context' => 'Public sharing section title shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.section.public_share_description' => ['text' => "Ces champs servent uniquement quand l'organisation est partagée comme modèle réutilisable.", 'context' => 'Public sharing section description shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.field.share_public' => ['text' => "Partager publiquement ce modèle d'organisation", 'context' => 'Public sharing checkbox label shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.field.share_public_help' => ['text' => "Active un modèle d'organisation récupérable par d'autres personnes.", 'context' => 'Public sharing checkbox help shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.field.public_model_name' => ['text' => 'Nom public du modèle', 'context' => 'Label of the public template name field in the holon template editor.'],
        'parameters.holon_templates.field.shared_model_media' => ['text' => 'Illustrations du modèle partagé', 'context' => 'Title shown above the public shared media cards in the holon template editor.'],
        'parameters.holon_templates.field.logo_icon' => ['text' => 'Logo / Icône', 'context' => 'Title of the public icon card in the holon template editor.'],
        'parameters.holon_templates.action.close' => ['text' => 'Fermer', 'context' => 'Close button label used in compact mode for the holon template editor.'],
        'parameters.holon_templates.action.save_organization' => ['text' => "Enregistrer l'organisation", 'context' => 'Submit button label used in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.action.save_model' => ['text' => 'Enregistrer le modèle', 'context' => 'Submit button label used in template mode for the holon template editor.'],
        'parameters.holon_templates.media.icon_label' => ['text' => 'Icône', 'context' => 'Media label passed to the sized image field for the icon in the holon template editor.'],
        'parameters.holon_templates.media.banner_label' => ['text' => 'Bannière', 'context' => 'Media label passed to the sized image field for the banner in the holon template editor.'],
        'parameters.holon_templates.permission.self' => ['text' => 'Élément courant', 'context' => 'Permission scope label used for the current holon in the holon template editor.'],
        'parameters.holon_templates.permission.children' => ['text' => 'Sous-éléments', 'context' => 'Permission scope label used for child holons in the holon template editor.'],
        'parameters.holon_templates.permission.parent_circle_elements' => ['text' => 'Éléments du cercle parent', 'context' => 'Permission scope label used for the parent circle elements in the holon template editor.'],
        'parameters.holon_templates.permission.organization' => ['text' => "Toute l'organisation", 'context' => 'Permission scope label used for organization-wide permissions in the holon template editor.'],
        'parameters.holon_templates.permission.none_available' => ['text' => "Aucun droit n'est disponible.", 'context' => 'Empty state shown when no permission can be configured in the holon template editor.'],
        'parameters.holon_templates.permission.add_range' => ['text' => 'Ajouter une portée...', 'context' => 'Placeholder option shown in the permission scope selector of the holon template editor.'],
        'parameters.holon_templates.permission.none_selected' => ['text' => 'Aucune portée sélectionnée.', 'context' => 'Empty state shown when no permission scope is selected in the holon template editor.'],
        'parameters.holon_templates.permission.remove_range' => ['text' => 'Retirer cette portée', 'context' => 'ARIA label used to remove a permission scope token in the holon template editor.'],
        'parameters.holon_templates.confirm.inheritance_change' => ['text' => "Changer l'héritage de ce modèle peut modifier ou masquer des propriétés et des valeurs sur ce modèle, ainsi que sur les modèles et holons qui en héritent.\n\nConfirmer cette opération ?", 'context' => 'Confirmation shown before changing the inheritance of a holon template.'],
        'parameters.holon_templates.summary.model_one' => ['text' => 'modèle', 'context' => 'Singular summary label for the number of models in the holon template editor.'],
        'parameters.holon_templates.summary.model_other' => ['text' => 'modèles', 'context' => 'Plural summary label for the number of models in the holon template editor.'],
        'parameters.holon_templates.summary.property_one' => ['text' => 'propriété', 'context' => 'Singular summary label for the number of properties in the holon template editor.'],
        'parameters.holon_templates.summary.property_other' => ['text' => 'propriétés', 'context' => 'Plural summary label for the number of properties in the holon template editor.'],
        'parameters.holon_templates.summary.submodel_one' => ['text' => 'sous-modèle', 'context' => 'Singular summary label for the number of child templates in the holon template editor tree.'],
        'parameters.holon_templates.summary.submodel_other' => ['text' => 'sous-modèles', 'context' => 'Plural summary label for the number of child templates in the holon template editor tree.'],
        'parameters.holon_templates.tree.empty' => ['text' => "Aucun modèle n'est encore défini.", 'context' => 'Empty state shown when the holon template tree has no templates yet.'],
        'parameters.holon_templates.tree.root' => ['text' => 'Racine des modèles', 'context' => 'Label used for the root option of the parent template selector in the holon template editor.'],
        'parameters.holon_templates.property.name' => ['text' => 'Nom', 'context' => 'Property field label used in dynamically rendered property rows of the holon template editor.'],
        'parameters.holon_templates.property.format' => ['text' => 'Format', 'context' => 'Property format field label used in dynamically rendered property rows of the holon template editor.'],
        'parameters.holon_templates.property.value_default' => ['text' => 'Valeur héritée par défaut', 'context' => 'Title of the default inherited value editor in the holon template editor.'],
        'parameters.holon_templates.property.value_local_added' => ['text' => 'Valeur locale ajoutée', 'context' => 'Title of the local override value editor in the holon template editor.'],
        'parameters.holon_templates.property.value_inherited' => ['text' => 'Valeur héritée', 'context' => 'Label shown above the inherited value preview in the holon template editor.'],
        'parameters.holon_templates.property.origin_inherited' => ['text' => 'Héritée', 'context' => 'Badge shown on inherited properties in the holon template editor.'],
        'parameters.holon_templates.property.origin_local' => ['text' => 'Locale', 'context' => 'Badge shown on local properties in the holon template editor.'],
        'parameters.holon_templates.property.toggle_mandatory' => ['text' => 'Obligatoire', 'context' => 'Toggle label shown on property rows for mandatory inheritance in the holon template editor.'],
        'parameters.holon_templates.property.toggle_locked' => ['text' => 'Verrouillée', 'context' => 'Toggle label shown on property rows for locked inheritance in the holon template editor.'],
        'parameters.holon_templates.property.action.move_up' => ['text' => 'Monter', 'context' => 'Action label used to move a property or list item up in the holon template editor.'],
        'parameters.holon_templates.property.action.move_down' => ['text' => 'Descendre', 'context' => 'Action label used to move a property or list item down in the holon template editor.'],
        'parameters.holon_templates.property.action.remove' => ['text' => 'Retirer', 'context' => 'Action label used to remove a local property or list item in the holon template editor.'],
        'parameters.holon_templates.property.action.exclude' => ['text' => 'Exclure', 'context' => 'Action label used to exclude an inherited property in the holon template editor.'],
        'parameters.holon_templates.property.placeholder.reason' => ['text' => "Ex. : Raison d'être", 'context' => 'Placeholder used for the property name field in the first simple property editor.'],
        'parameters.holon_templates.property.placeholder.generic' => ['text' => 'Ex. : Propriété', 'context' => 'Generic placeholder used for property name fields in the holon template editor.'],
        'parameters.holon_templates.property.placeholder.title' => ['text' => 'Titre', 'context' => 'Placeholder used for the detailed list item title in the holon template editor.'],
        'parameters.holon_templates.property.placeholder.description' => ['text' => 'Description', 'context' => 'Placeholder used for the detailed list item description in the holon template editor.'],
        'parameters.holon_templates.property.placeholder.number' => ['text' => 'Ex. : 42', 'context' => 'Placeholder used for numeric property values in the holon template editor.'],
        'parameters.holon_templates.property.placeholder.empty' => ['text' => 'Laissez vide pour ne rien imposer.', 'context' => 'Placeholder used for free text property values in the holon template editor.'],
        'parameters.holon_templates.property.list_item_type' => ['text' => 'Type des éléments de liste', 'context' => 'Field label used to choose the item type of a list property in the holon template editor.'],
        'parameters.holon_templates.property.allowed_holon_types' => ['text' => 'Types de holons autorisés', 'context' => 'Field label used to restrict allowed holon types in a list property.'],
        'parameters.holon_templates.property.no_template_for_types' => ['text' => 'Aucun template disponible pour les types choisis.', 'context' => 'Empty state shown when no template matches the selected holon types in a list property.'],
        'parameters.holon_templates.property.empty' => ['text' => 'Aucune propriété pour ce modèle. Vous pouvez commencer par en ajouter une.', 'context' => 'Empty state shown when no property is defined yet in the holon template editor.'],
        'parameters.holon_templates.property.detail_fallback' => ['text' => 'Élément', 'context' => 'Fallback title used for a detailed list item preview in the holon template editor.'],
        'parameters.holon_templates.property.help.default' => ['text' => 'Si cette valeur reste vide, chaque holon dérivé pourra définir librement son contenu.', 'context' => 'Default help text shown under property value editors in the holon template editor.'],
        'parameters.holon_templates.property.help.number' => ['text' => 'Laissez vide pour ne rien imposer. Utilisez un nombre entier ou décimal.', 'context' => 'Help text shown under numeric property value editors in the holon template editor.'],
        'parameters.holon_templates.property.help.date' => ['text' => 'Laissez vide pour ne rien imposer. La date sera héritée au format AAAA-MM-JJ.', 'context' => 'Help text shown under date property value editors in the holon template editor.'],
        'parameters.holon_templates.property.help.html' => ['text' => 'Format HTML simple : gras, italic, listes et liens.', 'context' => 'Help text shown under HTML property value editors in the holon template editor.'],
        'parameters.holon_templates.property.help.list_text' => ['text' => 'Une ligne par élément. Laissez vide pour ne rien imposer.', 'context' => 'Help text shown for text list properties in the holon template editor.'],
        'parameters.holon_templates.property.help.list_number' => ['text' => 'Une ligne par nombre. Laissez vide pour ne rien imposer.', 'context' => 'Help text shown for number list properties in the holon template editor.'],
        'parameters.holon_templates.property.help.list_date' => ['text' => 'Une ligne par date au format AAAA-MM-JJ. Laissez vide pour ne rien imposer.', 'context' => 'Help text shown for date list properties in the holon template editor.'],
        'parameters.holon_templates.property.help.list_detail' => ['text' => 'Chaque ligne contient un titre et une description. Laissez vide pour ne rien imposer.', 'context' => 'Help text shown for detailed list properties in the holon template editor.'],
        'parameters.holon_templates.property.help.list_holon' => ['text' => 'Cochez les holons de base à inclure dans ce template. Les instances pourront ensuite en ajouter.', 'context' => 'Help text shown for holon list properties in the holon template editor.'],
        'parameters.holon_templates.status.close_message' => ['text' => 'Fermer le message', 'context' => 'ARIA label of the dismiss button shown on status messages in the holon template editor.'],
        'parameters.holon_templates.badge.active_inheritance' => ['text' => 'Héritage actif', 'context' => 'Badge shown when a template inherits from another one in the holon template editor.'],
        'parameters.holon_templates.form.selection_hint_definition' => ['text' => "Modification des propriétés locales de cette organisation.", 'context' => 'Selection hint shown in organization definition mode for the holon template editor.'],
        'parameters.holon_templates.form.selection_hint_existing' => ['text' => 'Modification du modèle sélectionné.', 'context' => 'Selection hint shown when editing an existing holon template.'],
        'parameters.holon_templates.form.selection_hint_new' => ['text' => 'Nouveau modèle non encore enregistré.', 'context' => 'Selection hint shown when editing a new unsaved holon template.'],
        'parameters.holon_templates.form.model_title' => ['text' => 'Modèle', 'context' => 'Fallback form title used when editing an existing holon template without a name yet.'],
        'parameters.holon_templates.form.existing_model_description' => ['text' => 'Ajustez ce modèle et ses propriétés héritables.', 'context' => 'Form description shown when editing an existing holon template.'],
        'parameters.holon_templates.form.new_model_description_short' => ['text' => 'Choisissez son type de base puis ajoutez les propriétés à transmettre.', 'context' => 'Short form description shown when editing a new holon template after selection changes.'],
        'parameters.holon_templates.status.saved_organization' => ['text' => 'Organisation enregistrée.', 'context' => 'Fallback success message returned when saving the organization definition succeeded.'],
        'parameters.holon_templates.status.saved_model' => ['text' => 'Modèle enregistré.', 'context' => 'Fallback success message returned when saving a holon template succeeded.'],
        'parameters.holon_templates.error.invalid_request' => ['text' => 'La requête envoyée est invalide.', 'context' => 'Error returned when the holon template save payload is invalid.'],
        'parameters.holon_templates.error.save_organization' => ['text' => "L'organisation n'a pas pu être enregistrée.", 'context' => 'Fallback error returned when saving the organization definition failed.'],
        'parameters.holon_templates.error.save_model' => ['text' => "Le modèle n'a pas pu être enregistré.", 'context' => 'Fallback error returned when saving a holon template failed.'],
    ];

    return $sourceLang;
}

function omoHolonTemplateLang()
{
    static $lang = null;

    if ($lang === null) {
        $lang = omoLoadTranslationBundle('omo_parameters_holon_templates', omoHolonTemplateSourceLang());
    }

    return $lang;
}

function omoHolonTemplateT($key, array $replace = [])
{
    return t($key, $replace, omoHolonTemplateLang(), omoHolonTemplateSourceLang());
}
