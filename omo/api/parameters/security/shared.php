<?php

function omoSecuritySourceLang(): array
{
    return [
        'indexing_title' => ['text' => 'Indexation dans les moteurs de recherche', 'context' => 'Server-wide search indexing section in security settings.'],
        'indexing_allowed' => ['text' => 'Indexation autorisée sur cette installation.', 'context' => 'Effective search indexing policy allows public application pages.'],
        'indexing_blocked' => ['text' => 'Indexation bloquée sur cette installation.', 'context' => 'Effective search indexing policy excludes application pages.'],
        'indexing_help' => ['text' => 'Ce réglage concerne toute cette installation et dépend uniquement de son fichier .env. Indexation autorisée par défaut, sans détection du domaine. Le retrait des résultats existants attend le prochain passage du moteur. Ce blocage ne rend pas le site privé.', 'context' => 'Instance scope, explicit environment setting and search removal delay.'],
        'indexing_configure' => ['text' => 'Configurer l’indexation du serveur', 'context' => 'Open existing protected server settings on the security tab.'],
        'mcp_title' => ['text' => 'IA et connexions externes', 'context' => 'Organization internal AI and external integration security section.'],
        'mcp_description' => ['text' => "Par défaut : IA interne autorisée, API REST et MCP bloques. Trois choix indépendants, configurables sans lien Patreon. Les blocages s'appliquent aussi aux connexions existantes.", 'context' => 'Three independent organization security policies, configurable without Patreon and binding on existing connections.'],
        'ai_blocked_label' => ['text' => 'Bloquer les fonctions IA', 'context' => 'Organization setting disabling all internal AI features.'],
        'ai_blocked_help' => ['text' => "Bloque le chat, les résumés, la reformulation et la transcription, y compris les mémos Telegram destinés à l'organisation, même avec un abonnement personnel.", 'context' => 'Scope of internal AI block, binding on individually subscribed members.'],
        'api_label' => ['text' => "Accès à l'API REST de cette organisation", 'context' => 'Independent organization REST API policy label.'],
        'mcp_label' => ['text' => 'Accès au serveur MCP de cette organisation', 'context' => 'Organization integration policy label.'],
        'help_for' => ['text' => 'Aide : {label}', 'context' => 'Accessible label of organization security help capsules.'],
        'api_help' => ['text' => "Autorise les applications externes a accéder aux données de cette organisation par l'API REST, selon les droits OMO et les consentements de chaque utilisateur. Un blocage refuse aussi les jetons déjà delivres. Ce choix est indépendant du serveur MCP et des fonctions IA internes.", 'context' => 'Organization REST API security help.'],
        'mcp_help' => ['text' => "Autorise les assistants externes a accéder aux données de cette organisation par MCP, selon les droits OMO et les consentements de chaque utilisateur. Ces assistants peuvent transmettre les données à leur propre fournisseur IA. Un blocage refuse aussi les connexions existantes. Ce choix est indépendant de l'API REST et des fonctions IA internes.", 'context' => 'Organization MCP security help and external AI data disclosure.'],
        'api_documentation' => ['text' => 'Documentation API REST', 'context' => 'REST documentation link shown below enabled API access.'],
        'mcp_documentation' => ['text' => 'Documentation MCP', 'context' => 'MCP connection guide shown below enabled MCP access.'],
        'api_endpoint' => ['text' => 'URL de base', 'context' => 'Configured REST API base URL.'],
        'mcp_endpoint' => ['text' => 'Endpoint MCP', 'context' => 'Configured canonical MCP connection URL.'],
        'endpoint_unconfigured' => ['text' => "Les endpoints externes ne sont pas encore configurés sur ce serveur. Contactez l'administration du serveur.", 'context' => 'Persistent information when external access is allowed before MCP_PUBLIC_URL is configured.'],
        'mcp_blocked' => ['text' => 'Bloque (par défaut)', 'context' => 'MCP blocked option.'],
        'mcp_allowed' => ['text' => 'Autorisé', 'context' => 'MCP allowed option.'],
        'mcp_invalid' => ['text' => 'Vérifiez le blocage IA et les autorisations API et MCP.', 'context' => 'Invalid organization security policy value.'],
        'title' => ['text' => 'Sécurité', 'context' => 'Organization security settings title.'],
        'backup_title' => ['text' => 'Sauvegardes de l’organisation', 'context' => 'Heading of the security drawer content.'],
        'automation' => ['text' => 'Sauvegarde automatique', 'context' => 'Automatic backup settings section.'],
        'delivery' => ['text' => 'Envoi par e-mail', 'context' => 'Backup recipients and frequency section.'],
        'tracking' => ['text' => 'Suivi des sauvegardes', 'context' => 'Backup delivery tracking section.'],
        'description' => ['text' => 'Recevez une sauvegarde JSON de toute la structure et de ses modules. Elle est envoyée aux administrateurs actifs et, si vous la renseignez, à une adresse e-mail complémentaire.', 'context' => 'Automatic backup explanation.'],
        'notice' => ['text' => 'La sauvegarde conserve les références des fichiers externes (Etherpad, Nextcloud, kDrive, etc.), mais pas leur contenu ni la configuration des serveurs. Vous devrez reconfigurer ces serveurs après la restauration.', 'context' => 'Backup scope and restoration notice.'],
        'schedule' => ['text' => 'Après activation, la première sauvegarde est envoyée au prochain passage de la tâche planifiée. Les suivantes sont envoyées à la fréquence choisie. En cas d’échec, une nouvelle tentative est possible au bout d’une heure.', 'context' => 'Backup delivery schedule.'],
        'save' => ['text' => 'Enregistrer', 'context' => 'Save all organization security preferences: AI, API, MCP and backups.'],
        'backup_now' => ['text' => 'Sauvegarder immédiatement', 'context' => 'Send an organization backup now.'],
        'sending' => ['text' => 'Envoi de la sauvegarde en cours…', 'context' => 'Immediate backup progress message.'],
        'sent' => ['text' => 'La sauvegarde a été envoyée par e-mail.', 'context' => 'Immediate backup success message.'],
        'send_error' => ['text' => 'La sauvegarde n’a pas pu être envoyée. Vérifiez les adresses des destinataires et la configuration du serveur de messagerie.', 'context' => 'Immediate backup delivery failed.'],
        'busy' => ['text' => 'Une sauvegarde ou une opération de maintenance est déjà en cours. Réessayez dans quelques instants.', 'context' => 'Immediate backup prevented by maintenance lock.'],
        'manual_help' => ['text' => 'Le bouton « Sauvegarder immédiatement » enregistre ces paramètres et envoie le fichier JSON, même si la sauvegarde automatique est désactivée.', 'context' => 'Immediate backup button behavior.'],
        'saved' => ['text' => 'Les préférences de sécurité ont été enregistrées.', 'context' => 'All organization security preferences saved.'],
        'error' => ['text' => "Impossible d'enregistrer les préférences de sécurité.", 'context' => 'Organization security preferences save error.'],
        'forbidden' => ['text' => 'Activez le mode Admin de cette organisation pour accéder aux paramètres de sécurité.', 'context' => 'Backup settings authorization error.'],
        'invalid' => ['text' => 'Vérifiez l’adresse e-mail et choisissez une fréquence valide.', 'context' => 'Invalid backup settings.'],
        'csrf' => ['text' => 'Rechargez cet écran, puis réessayez.', 'context' => 'Expired backup form token.'],
        'never' => ['text' => 'Aucune sauvegarde n’a encore été envoyée.', 'context' => 'No backup delivered yet.'],
        'last' => ['text' => 'Dernier envoi : {date}', 'context' => 'Last successful backup delivery.'],
        'next' => ['text' => 'Prochaine échéance : {date}', 'context' => 'Next scheduled backup.'],
        'pending' => ['text' => 'En attente du prochain passage de la tâche planifiée.', 'context' => 'First backup pending.'],
        'organization' => ['text' => 'Organisation introuvable.', 'context' => 'Backup organization lookup error.'],
    ];
}

function omoSecurityT(string $key, array $replace = []): string
{
    static $lang = null;
    $sourceLang = omoSecuritySourceLang();
    $lang ??= omoLoadTranslationBundle('omo_parameters_security', $sourceLang);
    return t($key, $replace, $lang, $sourceLang);
}

function omoSecurityEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function omoSecurityHelp(string $labelKey, array $textKeys, string $id = ''): string
{
    $content = '';
    foreach ($textKeys as $key) $content .= '<p>' . omoSecurityEscape(omoSecurityT($key)) . '</p>';
    return '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
        . '<summary aria-label="' . omoSecurityEscape(omoSecurityT('help_for', ['label' => omoSecurityT($labelKey)])) . '">?</summary>'
        . '<div class="generic-context-help__content"' . ($id !== '' ? ' id="' . omoSecurityEscape($id) . '"' : '') . '>'
        . $content . '</div></details>';
}

function omoSecurityCanManage(int $organizationId): bool
{
    return commonGetCurrentUserId() > 0 && (commonCurrentUserIsSiteAdminModeEnabled()
        || (commonCurrentUserCanUseAdminMode($organizationId) && commonCurrentUserIsAdminModeEnabled($organizationId)));
}
