<?php

function commonDocumentServiceT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.impossible_de_charger_la_decouverte_collabora' => ['text' => 'Impossible de charger la découverte Collabora.', 'context' => 'Interface text in common/collabora.php'],
        'copy.la_requete_ethercalc_a_echoue' => ['text' => 'La requête EtherCalc a échoué.', 'context' => 'Interface text in common/ethercalc.php'],
        'copy.la_requete_etherpad_a_echoue' => ['text' => 'La requête Etherpad a échoué.', 'context' => 'Interface text in common/etherpad.php'],
        'copy.la_requete_spacedeck_a_echoue' => ['text' => 'La requête SpaceDeck a échoué.', 'context' => 'Interface text in common/spacedeck.php'],
    ];
    require_once __DIR__ . '/../translation_bundles.php';
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= loadTranslationBundle('common_document_service_translations', $locale, $sourceLang)) : $sourceLang;
    return t($key, $variables, $bundle, $sourceLang);
}
