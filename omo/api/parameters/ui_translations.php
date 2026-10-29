<?php

require_once __DIR__ . '/../../translations.php';

function omoParametersUiSourceLang(): array
{
    static $sourceLang = [
        'editer' => ['text' => 'éditer', 'context' => 'Interface label in api/parameters/index.php'],
        'exporter' => ['text' => 'exporter', 'context' => 'Interface label in api/parameters/index.php'],
        'contexte_utilisateur_ou_organisation_indisponible' => ['text' => 'Contexte utilisateur ou organisation indisponible.', 'context' => 'Validation or action feedback in api/parameters/notifications/preferences.php'],
        'methode_non_autorisee' => ['text' => 'Méthode non autorisée.', 'context' => 'Validation or action feedback in api/parameters/notifications/preferences.php'],
        'verification_de_securite_invalide' => ['text' => 'Vérification de sécurité invalide.', 'context' => 'Validation or action feedback in api/parameters/notifications/preferences.php'],
        'impossible_d_enregistrer_les_reglages_de_notifications' => ['text' => 'Impossible d’enregistrer les réglages de notifications.', 'context' => 'Validation or action feedback in api/parameters/notifications/preferences.php'],
        'connexion_requise' => ['text' => 'Connexion requise.', 'context' => 'Interface text in api/parameters/notifications/index.php'],
    ];
    return $sourceLang;
}

function omoParametersUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoParametersUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_parameters_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
