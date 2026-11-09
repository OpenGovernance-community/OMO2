<?php

function commonObjectMailT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.configurez_mail_from_avec_une_adresse_technique_du_domaine_d_envoi' => ['text' => 'Configurez MAIL_FROM avec une adresse technique du domaine d’envoi.', 'context' => 'Interface text in common/object_mail/delivery.php'],
        'copy.selection_de_membres_invalide' => ['text' => 'Sélection de membres invalide.', 'context' => 'Interface text in common/object_mail/validation.php'],
    ];
    require_once __DIR__ . '/../translation_bundles.php';
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= loadTranslationBundle('common_object_mail_translations', $locale, $sourceLang)) : $sourceLang;
    return t($key, $variables, $bundle, $sourceLang);
}
