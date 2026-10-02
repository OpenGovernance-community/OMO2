<?php

function omoSecuritySourceLang(): array
{
    return [
        'title' => ['text' => 'Sécurité', 'context' => 'Organization security settings title.'],
        'backup_title' => ['text' => 'Sauvegardes de l’organisation', 'context' => 'Heading of the security drawer content.'],
        'automation' => ['text' => 'Sauvegarde automatique', 'context' => 'Automatic backup settings section.'],
        'delivery' => ['text' => 'Envoi par e-mail', 'context' => 'Backup recipients and frequency section.'],
        'tracking' => ['text' => 'Suivi des sauvegardes', 'context' => 'Backup delivery tracking section.'],
        'scope' => ['text' => 'Contenu de la sauvegarde', 'context' => 'Backup scope accordion title.'],
        'description' => ['text' => 'Recevez une sauvegarde JSON de toute la structure et de ses modules. Elle est envoyée aux administrateurs actifs et, si vous la renseignez, à une adresse e-mail complémentaire.', 'context' => 'Automatic backup explanation.'],
        'notice' => ['text' => 'La sauvegarde conserve les références des fichiers externes (Etherpad, Nextcloud, kDrive, etc.), mais pas leur contenu ni la configuration des serveurs. Vous devrez reconfigurer ces serveurs après la restauration.', 'context' => 'Backup scope and restoration notice.'],
        'schedule' => ['text' => 'Après activation, la première sauvegarde est envoyée au prochain passage de la tâche planifiée. Les suivantes sont envoyées à la fréquence choisie. En cas d’échec, une nouvelle tentative est possible au bout d’une heure.', 'context' => 'Backup delivery schedule.'],
        'save' => ['text' => 'Enregistrer', 'context' => 'Save backup settings button.'],
        'backup_now' => ['text' => 'Sauvegarder immédiatement', 'context' => 'Send an organization backup now.'],
        'sending' => ['text' => 'Envoi de la sauvegarde en cours…', 'context' => 'Immediate backup progress message.'],
        'sent' => ['text' => 'La sauvegarde a été envoyée par e-mail.', 'context' => 'Immediate backup success message.'],
        'send_error' => ['text' => 'La sauvegarde n’a pas pu être envoyée. Vérifiez les adresses des destinataires et la configuration du serveur de messagerie.', 'context' => 'Immediate backup delivery failed.'],
        'busy' => ['text' => 'Une sauvegarde ou une opération de maintenance est déjà en cours. Réessayez dans quelques instants.', 'context' => 'Immediate backup prevented by maintenance lock.'],
        'manual_help' => ['text' => 'Le bouton « Sauvegarder immédiatement » enregistre ces paramètres et envoie le fichier JSON, même si la sauvegarde automatique est désactivée.', 'context' => 'Immediate backup button behavior.'],
        'saved' => ['text' => 'Les paramètres de sauvegarde ont été enregistrés.', 'context' => 'Backup settings saved message.'],
        'error' => ['text' => 'Impossible d’enregistrer les paramètres de sauvegarde.', 'context' => 'Backup settings save error.'],
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

function omoSecurityCanManage(int $organizationId): bool
{
    return commonGetCurrentUserId() > 0 && (commonCurrentUserIsSiteAdminModeEnabled()
        || (commonCurrentUserCanUseAdminMode($organizationId) && commonCurrentUserIsAdminModeEnabled($organizationId)));
}
