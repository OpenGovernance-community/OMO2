<?php

require_once __DIR__ . '/../../translations.php';

function omoNotificationsUiSourceLang(): array
{
    static $sourceLang = [
        'connexion_ou_organisation_requise' => ['text' => 'Connexion ou organisation requise.', 'context' => 'Validation or action feedback in api/notifications/inbox.php, api/notifications/mark_read.php'],
        'methode_non_autorisee' => ['text' => 'Méthode non autorisée.', 'context' => 'Validation or action feedback in api/notifications/mark_read.php, api/notifications/push_subscription.php'],
        'verification_de_securite_invalide' => ['text' => 'Vérification de sécurité invalide.', 'context' => 'Validation or action feedback in api/notifications/mark_read.php, api/notifications/push_subscription.php'],
        'connexion_requise' => ['text' => 'Connexion requise.', 'context' => 'Validation or action feedback in api/notifications/push_subscription.php'],
        'donnees_de_notification_invalides' => ['text' => 'Données de notification invalides.', 'context' => 'Validation or action feedback in api/notifications/push_subscription.php'],
        'souscription_navigateur_invalide' => ['text' => 'Souscription navigateur invalide.', 'context' => 'Validation or action feedback in api/notifications/push_subscription.php'],
    ];
    return $sourceLang;
}

function omoNotificationsUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoNotificationsUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_notifications_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
