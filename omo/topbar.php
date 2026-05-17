<?php

require_once dirname(__DIR__) . '/common/github_bug_report.php';
require_once __DIR__ . '/translations.php';

function omoGetTopbarSourceLang(): array
{
    return [
        'topbar.bug.button' => [
            'text' => 'Bug',
            'context' => 'Topbar bug report button label in OMO pages.',
        ],
        'topbar.bug.title' => [
            'text' => 'Signaler un bug',
            'context' => 'Topbar bug report modal title in OMO pages.',
        ],
        'topbar.bug.unavailable_html' => [
            'text' => '<p>Formulaire indisponible.</p>',
            'context' => 'Fallback HTML shown when the bug report form cannot be loaded from the OMO topbar.',
        ],
        'topbar.close' => [
            'text' => 'Fermer',
            'context' => 'Generic close button label for the OMO topbar modal and drawer.',
        ],
        'topbar.drawer.default_title' => [
            'text' => 'Panneau lateral',
            'context' => 'Fallback drawer title for the OMO topbar when no specific title is provided.',
        ],
        'topbar.help.button' => [
            'text' => 'Aide',
            'context' => 'Topbar help menu button label in OMO pages.',
        ],
        'topbar.help.fallback_label' => [
            'text' => 'Aide',
            'context' => 'Fallback label for a help item when no label is available in the OMO topbar.',
        ],
        'topbar.help.faq.description' => [
            'text' => 'Acces aux questions les plus courantes, avec moteur de recherche pour trouver facilement la reponse a ses questions.',
            'context' => 'Description of the FAQ help entry in the OMO topbar.',
        ],
        'topbar.help.faq.label' => [
            'text' => 'FAQ',
            'context' => 'Label of the FAQ help entry in the OMO topbar.',
        ],
        'topbar.help.faq.title' => [
            'text' => 'FAQ OMO',
            'context' => 'Title of the FAQ help entry in the OMO topbar.',
        ],
        'topbar.help.pending_html' => [
            'text' => '<p>Contenu a venir.</p>',
            'context' => 'Fallback HTML shown when a help item exists but does not yet have content in the OMO topbar.',
        ],
        'topbar.help.tour.description' => [
            'text' => 'Tour des fonctions visibles a l ecran avec explication pour chaque bouton et chaque possibilite.',
            'context' => 'Description of the guided tour help entry in the OMO topbar.',
        ],
        'topbar.help.tour.label' => [
            'text' => 'Visite guidee',
            'context' => 'Label of the guided tour help entry in the OMO topbar.',
        ],
        'topbar.help.tutorials.description' => [
            'text' => 'Des formations ciblees pour monter en competences dans l utilisation du logiciel.',
            'context' => 'Description of the tutorials help entry in the OMO topbar.',
        ],
        'topbar.help.tutorials.label' => [
            'text' => 'Tutoriels',
            'context' => 'Label of the tutorials help entry in the OMO topbar.',
        ],
        'topbar.help.tutorials.title' => [
            'text' => 'Tutoriels',
            'context' => 'Title of the tutorials help entry in the OMO topbar.',
        ],
        'topbar.help.unavailable_html' => [
            'text' => '<p>Contenu indisponible.</p>',
            'context' => 'Fallback HTML shown when a help item cannot be loaded from the OMO topbar.',
        ],
        'topbar.load_error' => [
            'text' => 'Erreur de chargement',
            'context' => 'Fallback message displayed inside the OMO topbar modal or drawer when remote content fails to load.',
        ],
        'topbar.loading' => [
            'text' => 'Chargement...',
            'context' => 'Temporary message displayed inside the OMO topbar modal or drawer while remote content is loading.',
        ],
        'topbar.modal.default_title' => [
            'text' => 'Panneau',
            'context' => 'Fallback modal title for the OMO topbar when no specific title is provided.',
        ],
        'topbar.profile.button' => [
            'text' => 'Profil',
            'context' => 'Topbar profile button label in OMO pages.',
        ],
        'topbar.profile.details.email' => [
            'text' => 'E-mail',
            'context' => 'Email field label in the OMO topbar profile panel.',
        ],
        'topbar.profile.details.empty_value' => [
            'text' => 'Non renseigne',
            'context' => 'Fallback value shown for missing profile information in the OMO topbar profile panel.',
        ],
        'topbar.profile.details.name' => [
            'text' => 'Nom',
            'context' => 'Name field label in the OMO topbar profile panel.',
        ],
        'topbar.profile.details.username' => [
            'text' => 'Identifiant',
            'context' => 'Username field label in the OMO topbar profile panel.',
        ],
        'topbar.profile.edit_label' => [
            'text' => 'Editer le profil',
            'context' => 'Button label used to open the profile editor from the OMO topbar.',
        ],
        'topbar.profile.edit_title' => [
            'text' => 'Votre profil',
            'context' => 'Modal title used when opening the profile editor from the OMO topbar.',
        ],
        'topbar.profile.preferences.language_label' => [
            'text' => 'Langue',
            'context' => 'Label of the compact language selector shown in the OMO topbar profile panel.',
        ],
        'topbar.profile.preferences.language_system' => [
            'text' => 'Systeme',
            'context' => 'System language option label shown in the compact language selector of the OMO topbar profile panel.',
        ],
        'topbar.profile.preferences.theme_dark' => [
            'text' => 'Sombre',
            'context' => 'Dark theme option label shown in the OMO topbar profile panel.',
        ],
        'topbar.profile.preferences.theme_label' => [
            'text' => 'Theme',
            'context' => 'Label of the compact theme selector shown in the OMO topbar profile panel.',
        ],
        'topbar.profile.preferences.theme_light' => [
            'text' => 'Clair',
            'context' => 'Light theme option label shown in the OMO topbar profile panel.',
        ],
        'topbar.profile.preferences.theme_system' => [
            'text' => 'Systeme',
            'context' => 'System theme option label shown in the OMO topbar profile panel.',
        ],
        'topbar.profile.summary_fallback' => [
            'text' => 'Resume du profil',
            'context' => 'Fallback summary text shown below the profile name in the OMO topbar when no email is available.',
        ],
        'topbar.search.advanced_hint' => [
            'text' => 'D autres filtres avances pourront s ajouter ici.',
            'context' => 'Fallback hint shown in the OMO topbar search panel when no scoped search options are available.',
        ],
        'topbar.search.button' => [
            'text' => 'Recherche',
            'context' => 'Topbar search menu button label in OMO pages.',
        ],
        'topbar.search.placeholder' => [
            'text' => 'Rechercher un cercle, un role ou un outil',
            'context' => 'Placeholder and label for the OMO topbar search field.',
        ],
        'topbar.search.scope' => [
            'text' => 'Chercher dans',
            'context' => 'Label shown above the scoped filters in the OMO topbar search panel.',
        ],
        'topbar.search.scope_hint' => [
            'text' => 'La recherche de la topbar n agit que sur les modules coches.',
            'context' => 'Hint shown below the scoped filters in the OMO topbar search panel.',
        ],
        'topbar.search.submit' => [
            'text' => 'Lancer',
            'context' => 'Submit button label for the OMO topbar search field.',
        ],
        'topbar.logout' => [
            'text' => 'Se deconnecter',
            'context' => 'Logout button label in the OMO topbar profile panel.',
        ],
    ];
}

function omoTopbarTranslate(string $key, array $variables = []): string
{
    static $sourceLang = null;
    static $bundle = null;

    if ($sourceLang === null) {
        $sourceLang = omoGetTopbarSourceLang();
    }

    if ($bundle === null) {
        $bundle = omoLoadTranslationBundle('omo_topbar', $sourceLang);
    }

    return t($key, $variables, $bundle, $sourceLang);
}

function omoGetTopbarHelpItems(string $variant = 'app'): array
{
    $tutorialsUrl = commonBuildUrl('/lms/index.php?embed=1', commonGetRootHost());

    $faqItem = [
        'key' => 'faq',
        'label' => omoTopbarTranslate('topbar.help.faq.label'),
        'description' => omoTopbarTranslate('topbar.help.faq.description'),
        'title' => omoTopbarTranslate('topbar.help.faq.title'),
    ];

    $tutorialsItem = [
        'key' => 'tutorials',
        'label' => omoTopbarTranslate('topbar.help.tutorials.label'),
        'description' => omoTopbarTranslate('topbar.help.tutorials.description'),
        'title' => omoTopbarTranslate('topbar.help.tutorials.title'),
        'mode' => 'drawer',
        'url' => $tutorialsUrl,
    ];

    if ($variant === 'app') {
        $faqItem['callback'] = 'omoOpenFaqHelp';

        return [
            $faqItem,
            [
                'key' => 'tour',
                'label' => omoTopbarTranslate('topbar.help.tour.label'),
                'description' => omoTopbarTranslate('topbar.help.tour.description'),
                'callback' => 'omoStartGuidedTour',
            ],
            $tutorialsItem,
        ];
    }

    $faqItem['mode'] = 'fetch';
    $faqItem['url'] = '/popup/faq.php';

    return [
        $faqItem,
        $tutorialsItem,
    ];
}

function omoBuildTopbarOptions(array $organizationContext, array $options = []): array
{
    $variant = (string)($options['variant'] ?? 'app');
    $isDemoGuest = !empty($options['isDemoGuest']);
    $translationOptions = !empty($options['translations']) && is_array($options['translations'])
        ? $options['translations']
        : [];

    $config = [
        'appKey' => 'omo',
        'appLabel' => 'OMO',
        'organization' => $organizationContext,
        'logoutReturnTo' => (string)($options['logoutReturnTo'] ?? '/omo/'),
        'helpLabel' => omoTopbarTranslate('topbar.help.button'),
        'helpItems' => omoGetTopbarHelpItems($variant),
        'profile' => [
            'enabled' => !$isDemoGuest,
            'buttonLabel' => omoTopbarTranslate('topbar.profile.button'),
            'editLabel' => omoTopbarTranslate('topbar.profile.edit_label'),
            'editTitle' => omoTopbarTranslate('topbar.profile.edit_title'),
            'editUrl' => '/popup/profil.php',
            'editMode' => 'fetch',
            'summaryFallback' => omoTopbarTranslate('topbar.profile.summary_fallback'),
            'preferences' => [
                'languageLabel' => omoTopbarTranslate('topbar.profile.preferences.language_label'),
                'systemLabel' => omoTopbarTranslate('topbar.profile.preferences.language_system'),
                'themeLabel' => omoTopbarTranslate('topbar.profile.preferences.theme_label'),
                'currentLocale' => translationBundleGetRequestLocalePreference('lang'),
                'resolvedLocale' => omoGetTranslationLocale(),
                'themeSystemLabel' => omoTopbarTranslate('topbar.profile.preferences.theme_system'),
                'themeLightLabel' => omoTopbarTranslate('topbar.profile.preferences.theme_light'),
                'themeDarkLabel' => omoTopbarTranslate('topbar.profile.preferences.theme_dark'),
            ],
            'details' => [
                'nameLabel' => omoTopbarTranslate('topbar.profile.details.name'),
                'emailLabel' => omoTopbarTranslate('topbar.profile.details.email'),
                'usernameLabel' => omoTopbarTranslate('topbar.profile.details.username'),
                'emptyValueLabel' => omoTopbarTranslate('topbar.profile.details.empty_value'),
            ],
        ],
        'search' => [
            'enabled' => false,
            'placeholder' => omoTopbarTranslate('topbar.search.placeholder'),
            'buttonLabel' => omoTopbarTranslate('topbar.search.button'),
            'submitLabel' => omoTopbarTranslate('topbar.search.submit'),
            'callback' => 'omoHandleTopbarSearch',
            'scopeProvider' => 'omoGetTopbarSearchScopes',
            'scopeLabel' => omoTopbarTranslate('topbar.search.scope'),
            'scopeHint' => omoTopbarTranslate('topbar.search.scope_hint'),
            'advancedHint' => omoTopbarTranslate('topbar.search.advanced_hint'),
        ],
        'bugReport' => [
            'enabled' => !$isDemoGuest && $variant === 'app' && githubBugReportUiIsEnabled(),
            'buttonLabel' => omoTopbarTranslate('topbar.bug.button'),
            'title' => omoTopbarTranslate('topbar.bug.title'),
            'url' => '/omo/api/bug_report_popup.php',
            'mode' => 'fetch',
        ],
        'logoutLabel' => omoTopbarTranslate('topbar.logout'),
        'modal' => [
            'defaultTitle' => omoTopbarTranslate('topbar.modal.default_title'),
            'closeLabel' => omoTopbarTranslate('topbar.close'),
        ],
        'drawer' => [
            'defaultTitle' => omoTopbarTranslate('topbar.drawer.default_title'),
            'closeLabel' => omoTopbarTranslate('topbar.close'),
        ],
        'translations' => [
            'loadingLabel' => omoTopbarTranslate('topbar.loading'),
            'loadErrorLabel' => omoTopbarTranslate('topbar.load_error'),
            'helpFallbackLabel' => omoTopbarTranslate('topbar.help.fallback_label'),
            'helpUnavailableHtml' => omoTopbarTranslate('topbar.help.unavailable_html'),
            'helpPendingHtml' => omoTopbarTranslate('topbar.help.pending_html'),
            'bugReportUnavailableHtml' => omoTopbarTranslate('topbar.bug.unavailable_html'),
        ],
    ];

    if ($variant === 'app') {
        $config['search']['enabled'] = true;
    }

    if ($variant === 'login') {
        $config['profile']['enabled'] = false;
    }

    if (!empty($options['search']) && is_array($options['search'])) {
        $config['search'] = array_replace($config['search'], $options['search']);
    }

    if (!empty($options['bugReport']) && is_array($options['bugReport'])) {
        $config['bugReport'] = array_replace($config['bugReport'], $options['bugReport']);
    }

    if (!empty($options['profile']) && is_array($options['profile'])) {
        $config['profile'] = array_replace_recursive($config['profile'], $options['profile']);
    }

    if (!empty($options['helpItems']) && is_array($options['helpItems'])) {
        $config['helpItems'] = array_values($options['helpItems']);
    }

    if (!empty($options['modal']) && is_array($options['modal'])) {
        $config['modal'] = array_replace($config['modal'], $options['modal']);
    }

    if (!empty($options['drawer']) && is_array($options['drawer'])) {
        $config['drawer'] = array_replace($config['drawer'], $options['drawer']);
    }

    if (!empty($translationOptions)) {
        $config['translations'] = array_replace($config['translations'], $translationOptions);
    }

    return $config;
}

?>
