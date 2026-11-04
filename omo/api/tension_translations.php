<?php
require_once __DIR__ . '/../ui_translations.php';

function omoTensionSourceLang(): array
{
    return [
        'space_hint' => ['text' => 'Choisissez un espace dans lequel vous avez le droit de creer des tensions.', 'context' => 'Tension space assignment picker help.'],
        'no_target' => ['text' => 'Vous ne disposez d aucun espace autorise pour creer une tension.', 'context' => 'Empty state when no tension creation destination is allowed.'],
        'existing' => ['text' => 'Tensions existantes', 'context' => 'Tab listing all active tensions in the current organization.'],
        'tabs' => ['text' => 'Gestion des tensions', 'context' => 'Accessible label for tension popup tabs.'],
        'empty' => ['text' => 'Aucune tension pour le moment dans cette organisation.', 'context' => 'Empty organization tension list.'],
        'loading' => ['text' => 'Chargement des tensions...', 'context' => 'Tension list progress.'],
        'load_failed' => ['text' => 'Impossible de charger les tensions.', 'context' => 'Tension list loading error.'],
        'refresh' => ['text' => 'Actualiser', 'context' => 'Reload the organization tension list.'],
        'actions' => ['text' => 'Actions pour {title}', 'context' => 'Accessible label for a tension row action menu.'],
        'delete' => ['text' => 'Supprimer', 'context' => 'Remove an active tension.'],
        'deleting' => ['text' => 'Suppression...', 'context' => 'Delete button progress.'],
        'confirm_delete' => ['text' => 'Supprimer la tension "{title}" ?', 'context' => 'Confirmation before removing a tension.'],
        'deleted' => ['text' => 'Tension supprimee.', 'context' => 'Successful tension removal.'],
        'delete_failed' => ['text' => 'Impossible de supprimer cette tension.', 'context' => 'Tension removal error.'],
        'forbidden' => ['text' => 'Vous ne pouvez pas supprimer cette tension.', 'context' => 'Tension removal permission denied.'],
        'not_found' => ['text' => 'Tension introuvable.', 'context' => 'Missing tension in the requested organization.'],
    ];
}

function omoTensionT(string $key, array $params = []): string
{
    static $sourceLang = null;
    static $lang = null;
    if ($sourceLang === null) {
        $sourceLang = omoTensionSourceLang();
        $locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
        $lang = loadTranslationBundle('tension_popup', $locale, $sourceLang);
    }
    return t($key, $params, $lang, $sourceLang);
}
