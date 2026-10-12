<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonTipsT(string $key): string
{
    static $sourceLang = [
        'title' => ['text' => 'Trucs et astuces', 'context' => 'Daily software tip title and sidebar button label.'],
        'next' => ['text' => 'Suivant', 'context' => 'Show another software tip.'],
        'read' => ['text' => 'En savoir plus', 'context' => 'Open the help link attached to a tip.'],
        'close' => ['text' => 'Fermer', 'context' => 'Dismiss a software tip.'],
        'error' => ['text' => 'Impossible de charger cette astuce.', 'context' => 'Tip request failure.'],
        'manage' => ['text' => 'Gerer les trucs et astuces', 'context' => 'Global software tips administration entry.'],
        'description' => ['text' => 'Ces astuces sont communes a toutes les organisations. Une astuce publiee est proposee chaque jour aux membres.', 'context' => 'Global tip administration explanation.'],
        'new' => ['text' => 'Nouvelle astuce', 'context' => 'Create a software tip.'],
        'edit' => ['text' => 'Modifier', 'context' => 'Edit a software tip.'],
        'save' => ['text' => 'Enregistrer', 'context' => 'Save a software tip.'],
        'removeImage' => ['text' => 'Retirer l image', 'context' => 'Remove the optional tip illustration.'],
        'saved' => ['text' => 'Astuce enregistree.', 'context' => 'Tip saved successfully.'],
        'invalid' => ['text' => 'Indiquez un texte de 500 caracteres maximum et un lien interne ou HTTP(S) valide.', 'context' => 'Invalid tip form content.'],
        'forbidden' => ['text' => 'Acces reserve au mode super administrateur.', 'context' => 'Global tip administration access denied.'],
        'empty' => ['text' => 'Aucune astuce disponible.', 'context' => 'Empty tip catalog.'],
        'unavailable' => ['text' => 'Appliquez la migration SQL des trucs et astuces pour activer ce catalogue.', 'context' => 'Missing tips table.'],
        'published' => ['text' => 'Publiee', 'context' => 'Tip publication status.'],
        'draft' => ['text' => 'Masquee', 'context' => 'Unpublished tip status.'],
    ];
    static $bundle = null;
    if ($bundle === null) {
        $locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
        $bundle = loadTranslationBundle('common_tips', $locale, $sourceLang);
    }
    return t($key, [], $bundle, $sourceLang);
}
