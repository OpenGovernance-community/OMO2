<?php

require_once __DIR__ . '/avatar.php';

function commonResolveTopbarProfileData($organizationContext = null, array $profileOptions = [])
{
    $defaults = [
        'userId' => 0,
        'displayName' => '',
        'initials' => '',
        'email' => '',
        'username' => '',
        'photoUrl' => '',
        'phone' => '',
    ];

    $providedData = [];
    if (!empty($profileOptions['data']) && is_array($profileOptions['data'])) {
        $providedData = $profileOptions['data'];
    }

    $profileData = array_merge($defaults, $providedData);

    $hasEnoughProvidedData =
        $profileData['displayName'] !== ''
        && ($profileData['email'] !== '' || $profileData['username'] !== '' || $profileData['photoUrl'] !== '')
    ;

    if (!$hasEnoughProvidedData && function_exists('commonGetCurrentUserId')) {
        $currentUserId = (int)commonGetCurrentUserId();
        if ($currentUserId > 0) {
            $organizationId = 0;
            if (is_array($organizationContext) && !empty($organizationContext['id'])) {
                $organizationId = (int)$organizationContext['id'];
            }

            $user = new \dbObject\User();
            if ($user->load($currentUserId)) {
                if ((int)$profileData['userId'] <= 0) {
                    $profileData['userId'] = (int)$user->getId();
                }

                if ($profileData['displayName'] === '') {
                    $profileData['displayName'] = (string)$user->getScopedDisplayName($organizationId);
                }

                if ($profileData['email'] === '') {
                    $profileData['email'] = (string)$user->getScopedEmail($organizationId);
                }

                if ($profileData['username'] === '') {
                    $profileData['username'] = (string)$user->getScopedUsername($organizationId);
                }

                if ($profileData['photoUrl'] === '') {
                    $profileData['photoUrl'] = (string)$user->getScopedProfilePhotoUrl($organizationId);
                }

                if ($profileData['initials'] === '') {
                    $profileData['initials'] = (string)$user->getScopedInitials($organizationId);
                }
            }
        }
    }

    if ($profileData['initials'] === '') {
        $seedLabel = trim((string)$profileData['displayName']);
        if ($seedLabel === '') {
            $seedLabel = trim((string)$profileData['username']);
        }
        if ($seedLabel === '') {
            $seedLabel = trim((string)$profileData['email']);
        }

        $profileData['initials'] = \dbObject\User::buildInitials($seedLabel);
    }

    return $profileData;
}

function commonRenderTopbarJqueryAssets()
{
    static $jqueryLoaded = false;

    if ($jqueryLoaded) {
        return;
    }

    echo '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>' . PHP_EOL;
    $jqueryLoaded = true;
}

function commonRenderTopbarSearchPeriod(array $period, $idPrefix = 'commonTopbar')
{
    $minDate = trim((string)($period['minDate'] ?? ''));
    $maxDate = trim((string)($period['maxDate'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $minDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $maxDate) || $minDate > $maxDate) {
        return;
    }

    $startDate = trim((string)($period['startDate'] ?? $minDate));
    $endDate = trim((string)($period['endDate'] ?? $maxDate));
    if ($startDate < $minDate || $startDate > $maxDate) {
        $startDate = $minDate;
    }
    if ($endDate < $startDate || $endDate > $maxDate) {
        $endDate = $maxDate;
    }
    $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$idPrefix);
    ?>
    <div class="common-topbar__search-period" data-topbar-search-period>
        <div class="common-topbar__search-period-row">
            <label class="common-topbar__search-period-field">
                <span><?= htmlspecialchars((string)($period['startLabel'] ?? 'Du')) ?></span>
                <input type="date" id="<?= htmlspecialchars($prefix) ?>SearchPeriodStart" class="generic-form-control" min="<?= htmlspecialchars($minDate) ?>" max="<?= htmlspecialchars($maxDate) ?>" value="<?= htmlspecialchars($startDate) ?>" data-topbar-search-period-start>
            </label>
            <div class="common-topbar__search-period-sliders" data-topbar-search-period-sliders>
                <input type="range" min="0" max="1000" value="0" step="1" data-topbar-search-period-start-slider aria-label="<?= htmlspecialchars((string)($period['startLabel'] ?? 'Du')) ?>">
                <input type="range" min="0" max="1000" value="1000" step="1" data-topbar-search-period-end-slider aria-label="<?= htmlspecialchars((string)($period['endLabel'] ?? 'Au')) ?>">
                <div class="common-topbar__search-period-years" data-topbar-search-period-years aria-hidden="true"></div>
            </div>
            <label class="common-topbar__search-period-field">
                <span><?= htmlspecialchars((string)($period['endLabel'] ?? 'Au')) ?></span>
                <input type="date" id="<?= htmlspecialchars($prefix) ?>SearchPeriodEnd" class="generic-form-control" min="<?= htmlspecialchars($minDate) ?>" max="<?= htmlspecialchars($maxDate) ?>" value="<?= htmlspecialchars($endDate) ?>" data-topbar-search-period-end>
            </label>
        </div>
    </div>
    <?php
}

function commonRenderTopbar(array $options = [])
{
    static $assetsLoaded = false;

    $translationOptions = !empty($options['translations']) && is_array($options['translations'])
        ? $options['translations']
        : [];

    if (!function_exists('translationBundleGetLanguageOptions') && is_file(__DIR__ . '/translation_bundles.php')) {
        require_once __DIR__ . '/translation_bundles.php';
    }

    $languageOptions = !empty($options['profile']['preferences']['languageOptions']) && is_array($options['profile']['preferences']['languageOptions'])
        ? array_values($options['profile']['preferences']['languageOptions'])
        : (
            function_exists('translationBundleGetLanguageOptions')
                ? translationBundleGetLanguageOptions()
                : [
                    ['locale' => 'fr', 'label' => 'Francais'],
                    ['locale' => 'en', 'label' => 'English'],
                    ['locale' => 'de', 'label' => 'Deutsch'],
                    ['locale' => 'es', 'label' => 'Espanol'],
                    ['locale' => 'it', 'label' => 'Italiano'],
                    ['locale' => 'pt', 'label' => 'Portugues'],
                    ['locale' => 'nl', 'label' => 'Nederlands'],
                    ['locale' => 'pl', 'label' => 'Polski'],
                ]
        );

    $organizationContext = $options['organization'] ?? null;
    $brandLogo = (string)($options['brandLogo'] ?? ($organizationContext['logo'] ?? '/img/logo-OGC.png'));
    if ($brandLogo === '') {
        $brandLogo = '/img/logo-OGC.png';
    }

    $brandRootHost = commonGetRootHost($organizationContext['host'] ?? null);
    if ($brandRootHost === '') {
        $brandRootHost = commonGetRootHost();
    }

    $brandHref = commonBuildUrl('/omo/', $brandRootHost);
    $brandLabel = trim((string)($options['brandLabel'] ?? ($organizationContext['name'] ?? '')));
    $currentLocale = function_exists('translationBundleResolveRequestLocale')
        ? translationBundleResolveRequestLocale('lang')
        : (string)($_COOKIE['lang'] ?? 'fr');
    $currentLocalePreference = function_exists('translationBundleGetRequestLocalePreference')
        ? translationBundleGetRequestLocalePreference('lang')
        : $currentLocale;
    $systemLocale = function_exists('translationBundleResolveBrowserLocale')
        ? translationBundleResolveBrowserLocale(
            function_exists('translationBundleGetSupportedLocales') ? translationBundleGetSupportedLocales() : [],
            'fr'
        )
        : $currentLocale;
    $helpItems = array_values($options['helpItems'] ?? []);
    $faqHelpIndex = null;
    foreach ($helpItems as $index => $item) {
        if (!is_array($item)) {
            continue;
        }

        $faqSignals = [
            (string)($item['label'] ?? ''),
            (string)($item['title'] ?? ''),
            (string)($item['url'] ?? ''),
            (string)($item['callback'] ?? ''),
        ];

        foreach ($faqSignals as $signal) {
            if (stripos($signal, 'faq') !== false) {
                $faqHelpIndex = $index;
                break 2;
            }
        }
    }

    if ($faqHelpIndex !== null) {
        $faqHelpItem = $helpItems[$faqHelpIndex];
        array_splice($helpItems, $faqHelpIndex, 1);
        $faqTargetIndex = min(2, count($helpItems));
        array_splice($helpItems, $faqTargetIndex, 0, [$faqHelpItem]);
    }

    $config = [
        'appKey' => (string)($options['appKey'] ?? 'app'),
        'appLabel' => (string)($options['appLabel'] ?? 'Application'),
        'userName' => (string)($options['userName'] ?? commonGetCurrentUserDisplayName() ?: 'Profil'),
        'brandAlt' => (string)($options['brandAlt'] ?? ($organizationContext['name'] ?? ($options['appLabel'] ?? 'Application'))),
        'brandHref' => (string)($options['brandHref'] ?? $brandHref),
        'brandLabel' => $brandLabel,
        'logoutPath' => (string)($options['logoutPath'] ?? '/common/logout.php'),
        'logoutReturnTo' => commonNormalizeLocalPath($options['logoutReturnTo'] ?? ($_SERVER['REQUEST_URI'] ?? '/'), '/'),
        'search' => [
            'enabled' => !empty($options['search']['enabled']),
            'placeholder' => (string)($options['search']['placeholder'] ?? 'Rechercher'),
            'callback' => (string)($options['search']['callback'] ?? ''),
            'buttonLabel' => (string)($options['search']['buttonLabel'] ?? 'Recherche'),
            'submitLabel' => (string)($options['search']['submitLabel'] ?? 'Lancer'),
            'scopeProvider' => (string)($options['search']['scopeProvider'] ?? ''),
            'scopeLabel' => (string)($options['search']['scopeLabel'] ?? 'Chercher dans'),
            'scopeHint' => (string)($options['search']['scopeHint'] ?? ''),
            'advancedHint' => (string)($options['search']['advancedHint'] ?? 'D autres filtres avances pourront s ajouter ici.'),
            'period' => [
                'minDate' => (string)($options['search']['periodMinDate'] ?? ''),
                'maxDate' => (string)($options['search']['periodMaxDate'] ?? ''),
                'label' => (string)($options['search']['periodLabel'] ?? 'Periode'),
                'startLabel' => (string)($options['search']['periodStartLabel'] ?? 'Du'),
                'endLabel' => (string)($options['search']['periodEndLabel'] ?? 'Au'),
            ],
        ],
        'bugReport' => [
            'enabled' => !empty($options['bugReport']['enabled']),
            'buttonLabel' => (string)($options['bugReport']['buttonLabel'] ?? 'Bug'),
            'title' => (string)($options['bugReport']['title'] ?? 'Signaler un bug'),
            'mode' => (string)($options['bugReport']['mode'] ?? 'fetch'),
            'url' => (string)($options['bugReport']['url'] ?? ''),
            'callback' => (string)($options['bugReport']['callback'] ?? ''),
        ],
        'tension' => [
            'enabled' => !empty($options['tension']['enabled']),
            'buttonLabel' => (string)($options['tension']['buttonLabel'] ?? 'Tension'),
            'title' => (string)($options['tension']['title'] ?? 'Declarer une tension'),
            'mode' => (string)($options['tension']['mode'] ?? 'fetch'),
            'url' => (string)($options['tension']['url'] ?? ''),
            'callback' => (string)($options['tension']['callback'] ?? ''),
            'iconUrl' => (string)($options['tension']['iconUrl'] ?? '/common/assets/icon-topbar-tension.png'),
            'appendCurrentRouteContext' => !empty($options['tension']['appendCurrentRouteContext']),
        ],
        'profile' => [
            'enabled' => array_key_exists('enabled', $options['profile'] ?? []) ? !empty($options['profile']['enabled']) : true,
            'editLabel' => (string)($options['profile']['editLabel'] ?? 'Editer le profil'),
            'editTitle' => (string)($options['profile']['editTitle'] ?? 'Profil'),
            'editMode' => (string)($options['profile']['editMode'] ?? 'fetch'),
            'editUrl' => (string)($options['profile']['editUrl'] ?? '/popup/profil.php'),
            'editCallback' => (string)($options['profile']['editCallback'] ?? ''),
            'buttonLabel' => (string)($options['profile']['buttonLabel'] ?? 'Profil'),
            'summaryFallback' => (string)($options['profile']['summaryFallback'] ?? 'Resume du profil'),
            'adminMode' => [
                'enabled' => !empty($options['profile']['adminMode']['enabled']),
                'active' => !empty($options['profile']['adminMode']['active']),
                'organizationId' => (int)($options['profile']['adminMode']['organizationId'] ?? 0),
                'enableLabel' => (string)($options['profile']['adminMode']['enableLabel'] ?? 'Activer le mode admin d organisation'),
                'disableLabel' => (string)($options['profile']['adminMode']['disableLabel'] ?? 'Quitter le mode admin d organisation'),
                'statusActiveLabel' => (string)($options['profile']['adminMode']['statusActiveLabel'] ?? 'Mode admin d organisation actif'),
                'statusInactiveLabel' => (string)($options['profile']['adminMode']['statusInactiveLabel'] ?? 'Mode admin d organisation inactif'),
                'toggleUrl' => (string)($options['profile']['adminMode']['toggleUrl'] ?? '/common/admin_mode.php'),
            ],
            'siteAdminMode' => [
                'enabled' => !empty($options['profile']['siteAdminMode']['enabled']),
                'active' => !empty($options['profile']['siteAdminMode']['active']),
                'enableLabel' => (string)($options['profile']['siteAdminMode']['enableLabel'] ?? 'Activer le mode super admin'),
                'disableLabel' => (string)($options['profile']['siteAdminMode']['disableLabel'] ?? 'Quitter le mode super admin'),
                'statusActiveLabel' => (string)($options['profile']['siteAdminMode']['statusActiveLabel'] ?? 'Mode super admin actif'),
                'statusInactiveLabel' => (string)($options['profile']['siteAdminMode']['statusInactiveLabel'] ?? 'Mode super admin inactif'),
                'toggleUrl' => (string)($options['profile']['siteAdminMode']['toggleUrl'] ?? '/common/site_admin_mode.php'),
            ],
            'preferences' => [
                'enabled' => array_key_exists('enabled', $options['profile']['preferences'] ?? []) ? !empty($options['profile']['preferences']['enabled']) : true,
                'languageLabel' => (string)($options['profile']['preferences']['languageLabel'] ?? 'Langue'),
                'languageOptions' => $languageOptions,
                'themeLabel' => (string)($options['profile']['preferences']['themeLabel'] ?? 'Theme'),
                'colorStyleLabel' => (string)($options['profile']['preferences']['colorStyleLabel'] ?? 'Couleur'),
                'colorStyleDefaultLabel' => (string)($options['profile']['preferences']['colorStyleDefaultLabel'] ?? 'Noir et blanc'),
                'colorStyleTurquoiseLabel' => (string)($options['profile']['preferences']['colorStyleTurquoiseLabel'] ?? 'Turquoise'),
                'colorStyleOceanBlueLabel' => (string)($options['profile']['preferences']['colorStyleOceanBlueLabel'] ?? 'Ocean Blue'),
                'currentLocale' => (string)($options['profile']['preferences']['currentLocale'] ?? $currentLocalePreference),
                'resolvedLocale' => (string)($options['profile']['preferences']['resolvedLocale'] ?? $currentLocale),
                'systemLocale' => (string)($options['profile']['preferences']['systemLocale'] ?? $systemLocale),
                'systemLabel' => (string)($options['profile']['preferences']['systemLabel'] ?? 'Systeme'),
                'themeSystemLabel' => (string)($options['profile']['preferences']['themeSystemLabel'] ?? 'Systeme'),
                'themeLightLabel' => (string)($options['profile']['preferences']['themeLightLabel'] ?? 'Clair'),
                'themeDarkLabel' => (string)($options['profile']['preferences']['themeDarkLabel'] ?? 'Sombre'),
                'themeLightIconUrl' => (string)($options['profile']['preferences']['themeLightIconUrl'] ?? '/common/assets/icon-theme-sun.png'),
                'themeDarkIconUrl' => (string)($options['profile']['preferences']['themeDarkIconUrl'] ?? '/common/assets/icon-theme-moon.png'),
                'colorStyleIconUrl' => (string)($options['profile']['preferences']['colorStyleIconUrl'] ?? '/common/assets/icon-theme-palette.png'),
            ],
            'details' => [
                'nameLabel' => (string)($options['profile']['details']['nameLabel'] ?? 'Nom'),
                'emailLabel' => (string)($options['profile']['details']['emailLabel'] ?? 'E-mail'),
            'usernameLabel' => (string)($options['profile']['details']['usernameLabel'] ?? "Nom d'utilisateur"),
                'emptyValueLabel' => (string)($options['profile']['details']['emptyValueLabel'] ?? 'Non renseigne'),
            ],
            'data' => commonResolveTopbarProfileData($organizationContext, $options['profile'] ?? []),
        ],
        'helpLabel' => (string)($options['helpLabel'] ?? 'Aide'),
        'helpItems' => $helpItems,
        'helpLinks' => array_values($options['helpLinks'] ?? []),
        'logoutLabel' => (string)($options['logoutLabel'] ?? 'Se deconnecter'),
        'modal' => [
            'defaultTitle' => (string)($options['modal']['defaultTitle'] ?? 'Panneau'),
            'closeLabel' => (string)($options['modal']['closeLabel'] ?? 'Fermer'),
        ],
        'drawer' => [
            'defaultTitle' => (string)($options['drawer']['defaultTitle'] ?? 'Panneau lateral'),
            'closeLabel' => (string)($options['drawer']['closeLabel'] ?? 'Fermer'),
        ],
        'translations' => [
            'loadingLabel' => (string)($translationOptions['loadingLabel'] ?? 'Chargement...'),
            'loadErrorLabel' => (string)($translationOptions['loadErrorLabel'] ?? 'Erreur de chargement'),
            'helpFallbackLabel' => (string)($translationOptions['helpFallbackLabel'] ?? 'Aide'),
            'helpUnavailableHtml' => (string)($translationOptions['helpUnavailableHtml'] ?? '<p>Contenu indisponible.</p>'),
            'helpPendingHtml' => (string)($translationOptions['helpPendingHtml'] ?? '<p>Contenu a venir.</p>'),
            'bugReportUnavailableHtml' => (string)($translationOptions['bugReportUnavailableHtml'] ?? '<p>Formulaire indisponible.</p>'),
            'tensionUnavailableHtml' => (string)($translationOptions['tensionUnavailableHtml'] ?? '<p>Formulaire indisponible.</p>'),
        ],
    ];

    $profileDisplayName = trim((string)($config['profile']['data']['displayName'] ?? ''));
    if ($profileDisplayName === '') {
        $profileDisplayName = trim((string)$config['userName']);
    }
    if ($profileDisplayName === '') {
        $profileDisplayName = (string)$config['profile']['buttonLabel'];
    }

    $profileInitials = trim((string)($config['profile']['data']['initials'] ?? ''));
    if ($profileInitials === '') {
        $profileInitials = \dbObject\User::buildInitials($profileDisplayName);
    }

    $profileAvatarPalette = commonBuildAvatarPalette(
        $profileInitials,
        (int)($config['profile']['data']['userId'] ?? 0),
        commonBuildAvatarSeedLabel(
            (string)($config['profile']['data']['displayName'] ?? ''),
            (string)($config['profile']['data']['email'] ?? '')
        )
    );
    $profileAvatarStyle = 'background-color: ' . $profileAvatarPalette['background'] . '; color: ' . $profileAvatarPalette['foreground'] . ';';
    $currentLocaleCode = trim((string)$config['profile']['preferences']['currentLocale']);
    if ($currentLocaleCode === '' || $currentLocaleCode === 'system') {
        $currentLocaleCode = trim((string)$config['profile']['preferences']['resolvedLocale']);
    }
    if ($currentLocaleCode === '') {
        $currentLocaleCode = 'fr';
    }
    $currentLanguageTileLabel = function_exists('translationBundleGetSimpleLocaleLabel')
        ? translationBundleGetSimpleLocaleLabel($currentLocaleCode, 'fr')
        : strtoupper(substr($currentLocaleCode, 0, 2));
    $systemLocaleCode = trim((string)$config['profile']['preferences']['systemLocale']);
    if ($systemLocaleCode === '') {
        $systemLocaleCode = trim((string)$config['profile']['preferences']['resolvedLocale']);
    }
    $systemLocaleLabel = function_exists('translationBundleGetSimpleLocaleLabel')
        ? translationBundleGetSimpleLocaleLabel($systemLocaleCode, 'fr')
        : strtoupper(substr($systemLocaleCode, 0, 2));

    if (!$assetsLoaded) {
        commonRenderTopbarJqueryAssets();
        echo '<link rel="stylesheet" href="/common/assets/components.css">' . PHP_EOL;
        echo '<script src="/common/assets/components.js" defer></script>' . PHP_EOL;
        echo '<link rel="stylesheet" href="/common/assets/topbar.css">' . PHP_EOL;
        echo '<script src="/common/assets/topbar.js" defer></script>' . PHP_EOL;
        $assetsLoaded = true;
    }
    ?>
<header class="topbar common-topbar" data-app-key="<?= htmlspecialchars($config['appKey']) ?>">
    <div class="common-topbar__left">
        <a
            href="<?= htmlspecialchars($config['brandHref']) ?>"
            class="common-topbar__brand-link"
            title="<?= htmlspecialchars($config['brandAlt']) ?>"
        >
            <span class="common-topbar__brand">
                <img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($config['brandAlt']) ?>" class="common-topbar__brand-logo">
            </span>
            <?php if ($config['brandLabel'] !== ''): ?>
                <span class="common-topbar__brand-name"><?= htmlspecialchars($config['brandLabel']) ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="common-topbar__actions">
        <?php if (!empty($config['bugReport']['enabled'])): ?>
        <div class="common-topbar__menu-wrap">
            <button type="button" class="common-topbar__action common-topbar__action--square" data-topbar-bug-report>
                <span class="common-topbar__action-icon" aria-hidden="true">
                    <img src="/img/punaise.png" alt="" class="common-topbar__icon-image black-icon">
                </span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['bugReport']['buttonLabel']) ?></span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (!empty($config['tension']['enabled'])): ?>
        <div class="common-topbar__menu-wrap">
            <button type="button" class="common-topbar__action common-topbar__action--square" data-topbar-tension-report>
                <span class="common-topbar__action-icon" aria-hidden="true">
                    <img src="<?= htmlspecialchars($config['tension']['iconUrl']) ?>" alt="" class="common-topbar__icon-image black-icon">
                </span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['tension']['buttonLabel']) ?></span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (!empty($config['search']['enabled'])): ?>
        <div class="common-topbar__menu-wrap common-topbar__menu-wrap--panel">
            <button type="button" class="common-topbar__action common-topbar__action--square" data-topbar-menu-trigger="search">
                <span class="common-topbar__action-icon" aria-hidden="true">
                    <img src="/common/assets/icon-topbar-search.png" alt="" class="common-topbar__icon-image black-icon">
                </span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['search']['buttonLabel']) ?></span>
            </button>
            <div class="common-topbar__menu common-topbar__menu--panel common-topbar__menu--right" data-topbar-menu="search">
                <form class="common-topbar__search-panel" data-topbar-search-form>
                    <label class="common-topbar__search-panel-label" for="commonTopbarSearchInput"><?= htmlspecialchars($config['search']['placeholder']) ?></label>
                    <div class="common-topbar__search-panel-row">
                        <input
                            type="search"
                            id="commonTopbarSearchInput"
                            class="common-topbar__search-input generic-form-control"
                            data-topbar-search-input
                            placeholder="<?= htmlspecialchars($config['search']['placeholder']) ?>"
                            aria-label="<?= htmlspecialchars($config['search']['placeholder']) ?>"
                        >
                        <button type="submit" class="common-topbar__search-button generic-action-button generic-action-button--main"><?= htmlspecialchars($config['search']['submitLabel']) ?></button>
                    </div>
                    <?php if ($config['search']['scopeProvider'] !== ''): ?>
                    <div class="common-topbar__search-scopes" data-topbar-search-scopes hidden>
                        <div class="common-topbar__search-panel-label"><?= htmlspecialchars($config['search']['scopeLabel']) ?></div>
                        <div class="common-topbar__search-scope-list" data-topbar-search-scope-list></div>
                        <?php if ($config['search']['scopeHint'] !== ''): ?>
                            <div class="common-topbar__search-panel-hint"><?= htmlspecialchars($config['search']['scopeHint']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="common-topbar__search-panel-hint"><?= htmlspecialchars($config['search']['advancedHint']) ?></div>
                    <?php endif; ?>
                    <?php commonRenderTopbarSearchPeriod($config['search']['period']); ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="common-topbar__menu-wrap">
            <button type="button" class="common-topbar__action common-topbar__action--square" data-topbar-menu-trigger="help">
                <span class="common-topbar__action-icon" aria-hidden="true">
                    <img src="/common/assets/icon-topbar-help.png" alt="" class="common-topbar__icon-image black-icon">
                </span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['helpLabel']) ?></span>
            </button>
            <div class="common-topbar__menu common-topbar__menu--help" data-topbar-menu="help">
                <?php foreach ($config['helpItems'] as $item): ?>
                    <button
                        type="button"
                        class="common-topbar__menu-item common-topbar__help-item"
                        data-topbar-help-item='<?= htmlspecialchars(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
                    >
                        <span class="common-topbar__help-title"><?= htmlspecialchars($item['label'] ?? $config['translations']['helpFallbackLabel']) ?></span>
                        <?php if (!empty($item['description'])): ?>
                            <span class="common-topbar__help-description"><?= htmlspecialchars($item['description']) ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
                <?php if (!empty($config['helpLinks'])): ?>
                    <div class="common-topbar__help-links">
                        <?php foreach ($config['helpLinks'] as $link): ?>
                            <?php
                            $href = trim((string)($link['href'] ?? ''));
                            if ($href === '') {
                                continue;
                            }
                            $label = trim((string)($link['label'] ?? $href));
                            $target = trim((string)($link['target'] ?? ''));
                            $rel = trim((string)($link['rel'] ?? ''));
                            $helpLinkItem = [];
                            if (!empty($link['callback']) || !empty($link['url']) || !empty($link['mode']) || !empty($link['html']) || !empty($link['title'])) {
                                $helpLinkItem = $link;
                                if (empty($helpLinkItem['url'])) {
                                    $helpLinkItem['url'] = $href;
                                }
                            }
                            ?>
                            <a
                                href="<?= htmlspecialchars($href) ?>"
                                class="common-topbar__help-link"
                                <?php if (!empty($helpLinkItem)): ?>data-topbar-help-link-item='<?= htmlspecialchars(json_encode($helpLinkItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'<?php endif; ?>
                                <?php if ($target !== ''): ?>target="<?= htmlspecialchars($target) ?>"<?php endif; ?>
                                <?php if ($rel !== ''): ?>rel="<?= htmlspecialchars($rel) ?>"<?php endif; ?>
                            ><?= htmlspecialchars($label) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($config['profile']['enabled'])): ?>
        <div class="common-topbar__menu-wrap">
            <button type="button" class="common-topbar__action common-topbar__action--square common-topbar__profile" data-topbar-menu-trigger="profile">
                <span class="common-topbar__avatar" data-common-topbar-avatar<?= empty($config['profile']['data']['photoUrl']) ? ' style="' . htmlspecialchars($profileAvatarStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                    <?php if (!empty($config['profile']['data']['photoUrl'])): ?>
                        <img
                            src="<?= htmlspecialchars((string)$config['profile']['data']['photoUrl']) ?>"
                            alt="<?= htmlspecialchars($profileDisplayName) ?>"
                            class="common-topbar__avatar-image"
                            data-common-topbar-avatar-image
                        >
                    <?php else: ?>
                        <span class="common-topbar__avatar-initial" data-common-topbar-avatar-initial aria-hidden="true"><?= htmlspecialchars($profileInitials) ?></span>
                    <?php endif; ?>
                </span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['profile']['buttonLabel']) ?></span>
            </button>
            <div class="common-topbar__menu common-topbar__menu--right" data-topbar-menu="profile">
                <div class="common-topbar-profile-panel" data-common-topbar-profile-panel>
                    <section class="common-topbar-profile-panel__section common-topbar-profile-panel__section--media">
                        <div class="common-topbar-profile-card generic-section" data-common-topbar-profile-media>
                            <?php if (!empty($config['profile']['data']['photoUrl'])): ?>
                                <img
                                    src="<?= htmlspecialchars((string)$config['profile']['data']['photoUrl']) ?>"
                                    alt="<?= htmlspecialchars($profileDisplayName) ?>"
                                    class="common-topbar-profile-card__photo"
                                    data-common-topbar-profile-photo
                                >
                            <?php else: ?>
                                <div class="common-topbar-profile-card__placeholder" data-common-topbar-profile-placeholder style="<?= htmlspecialchars($profileAvatarStyle, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"><?= htmlspecialchars($profileInitials) ?></div>
                            <?php endif; ?>
                            <div class="common-topbar-profile-card__identity">
                                <strong data-common-topbar-display-name><?= htmlspecialchars($profileDisplayName) ?></strong>
                                <span data-common-topbar-email><?= htmlspecialchars((string)($config['profile']['data']['email'] ?: $config['profile']['summaryFallback'])) ?></span>
                            </div>
                        </div>
                    </section>

                    <section class="common-topbar-profile-panel__section common-topbar-profile-panel__section--details">
                        <div class="common-topbar-profile-details generic-section">
                            <div class="common-topbar-profile-details__row">
                                <span class="common-topbar-profile-details__label"><?= htmlspecialchars($config['profile']['details']['nameLabel']) ?></span>
                                <span class="common-topbar-profile-details__value" data-common-topbar-detail-name><?= htmlspecialchars($profileDisplayName) ?></span>
                            </div>
                            <div class="common-topbar-profile-details__row">
                                <span class="common-topbar-profile-details__label"><?= htmlspecialchars($config['profile']['details']['emailLabel']) ?></span>
                                <span class="common-topbar-profile-details__value" data-common-topbar-detail-email><?= htmlspecialchars((string)($config['profile']['data']['email'] ?: $config['profile']['details']['emptyValueLabel'])) ?></span>
                            </div>
                            <div class="common-topbar-profile-details__row">
                                <span class="common-topbar-profile-details__label"><?= htmlspecialchars($config['profile']['details']['usernameLabel']) ?></span>
                                <span class="common-topbar-profile-details__value" data-common-topbar-detail-username><?= htmlspecialchars((string)($config['profile']['data']['username'] ?: $config['profile']['details']['emptyValueLabel'])) ?></span>
                            </div>
                        </div>
                    </section>

                    <section class="common-topbar-profile-panel__section common-topbar-profile-panel__section--actions">
                        <div class="common-topbar-profile-actions generic-section">
                            <?php if (!empty($config['profile']['preferences']['enabled'])): ?>
                                <div class="common-topbar-profile-preferences">
                                    <div class="common-topbar-profile-preferences__list">
                                        <div class="common-topbar-profile-preferences__field" data-topbar-preference-menu>
                                            <span class="common-topbar-profile-preferences__label"><?= htmlspecialchars($config['profile']['preferences']['languageLabel']) ?></span>
                                            <button
                                                type="button"
                                                class="common-topbar-profile-preference__trigger"
                                                data-topbar-preference-trigger="language"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="<?= htmlspecialchars($config['profile']['preferences']['languageLabel']) ?>"
                                            >
                                                <span class="common-topbar-profile-preference__tile common-topbar-profile-preference__tile--language"><?= htmlspecialchars($currentLanguageTileLabel) ?></span>
                                            </button>
                                            <div class="common-topbar-profile-preference__menu" data-topbar-preference-popup="language" hidden>
                                                <button
                                                    type="button"
                                                    class="common-topbar-profile-preference__option<?= $config['profile']['preferences']['currentLocale'] === 'system' ? ' is-active' : '' ?>"
                                                    data-topbar-language-option="system"
                                                ><?= htmlspecialchars($config['profile']['preferences']['systemLabel']) ?> (<?= htmlspecialchars($systemLocaleLabel) ?>)</button>
                                                <?php foreach ($config['profile']['preferences']['languageOptions'] as $languageOption): ?>
                                                    <?php $languageOptionLocale = (string)($languageOption['locale'] ?? ''); ?>
                                                    <button
                                                        type="button"
                                                        class="common-topbar-profile-preference__option<?= $config['profile']['preferences']['currentLocale'] === $languageOptionLocale ? ' is-active' : '' ?>"
                                                        data-topbar-language-option="<?= htmlspecialchars($languageOptionLocale) ?>"
                                                    ><?= htmlspecialchars((string)($languageOption['label'] ?? '')) ?></button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="common-topbar-profile-preferences__field" data-topbar-preference-menu>
                                            <span class="common-topbar-profile-preferences__label"><?= htmlspecialchars($config['profile']['preferences']['themeLabel']) ?></span>
                                            <button
                                                type="button"
                                                class="common-topbar-profile-preference__trigger"
                                                data-topbar-preference-trigger="theme"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="<?= htmlspecialchars($config['profile']['preferences']['themeLabel']) ?>"
                                            >
                                                <span
                                                    class="common-topbar-profile-preference__tile common-topbar-profile-preference__tile--icon common-topbar-profile-preference__tile--theme"
                                                    style="--topbar-theme-light-icon:url('<?= htmlspecialchars($config['profile']['preferences']['themeLightIconUrl'], ENT_QUOTES, 'UTF-8') ?>'); --topbar-theme-dark-icon:url('<?= htmlspecialchars($config['profile']['preferences']['themeDarkIconUrl'], ENT_QUOTES, 'UTF-8') ?>');"
                                                >
                                                    <span class="common-topbar-profile-preference__icon common-topbar-profile-preference__icon--theme-light" aria-hidden="true"></span>
                                                    <span class="common-topbar-profile-preference__icon common-topbar-profile-preference__icon--theme-dark" aria-hidden="true"></span>
                                                </span>
                                            </button>
                                            <div class="common-topbar-profile-preference__menu" data-topbar-preference-popup="theme" hidden>
                                                <button type="button" class="common-topbar-profile-preference__option" data-topbar-theme-option="system"><?= htmlspecialchars($config['profile']['preferences']['themeSystemLabel']) ?></button>
                                                <button type="button" class="common-topbar-profile-preference__option" data-topbar-theme-option="light"><?= htmlspecialchars($config['profile']['preferences']['themeLightLabel']) ?></button>
                                                <button type="button" class="common-topbar-profile-preference__option" data-topbar-theme-option="dark"><?= htmlspecialchars($config['profile']['preferences']['themeDarkLabel']) ?></button>
                                            </div>
                                        </div>

                                        <div class="common-topbar-profile-preferences__field common-topbar-profile-preferences__field--align-end" data-topbar-preference-menu>
                                            <span class="common-topbar-profile-preferences__label"><?= htmlspecialchars($config['profile']['preferences']['colorStyleLabel']) ?></span>
                                            <button
                                                type="button"
                                                class="common-topbar-profile-preference__trigger"
                                                data-topbar-preference-trigger="color-style"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="<?= htmlspecialchars($config['profile']['preferences']['colorStyleLabel']) ?>"
                                            >
                                                <span
                                                    class="common-topbar-profile-preference__tile common-topbar-profile-preference__tile--icon common-topbar-profile-preference__tile--color-style"
                                                    style="--topbar-palette-icon:url('<?= htmlspecialchars($config['profile']['preferences']['colorStyleIconUrl'], ENT_QUOTES, 'UTF-8') ?>');"
                                                >
                                                    <span class="common-topbar-profile-preference__icon common-topbar-profile-preference__icon--palette" aria-hidden="true"></span>
                                                </span>
                                            </button>
                                            <div class="common-topbar-profile-preference__menu" data-topbar-preference-popup="color-style" hidden>
                                                <button type="button" class="common-topbar-profile-preference__option is-active" data-topbar-color-style-option="mono"><?= htmlspecialchars($config['profile']['preferences']['colorStyleDefaultLabel']) ?></button>
                                                <button type="button" class="common-topbar-profile-preference__option" data-topbar-color-style-option="turquoise"><?= htmlspecialchars($config['profile']['preferences']['colorStyleTurquoiseLabel']) ?></button>
                                                <button type="button" class="common-topbar-profile-preference__option" data-topbar-color-style-option="ocean-blue"><?= htmlspecialchars($config['profile']['preferences']['colorStyleOceanBlueLabel']) ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="common-topbar__menu-item common-topbar-profile-actions__button" data-topbar-profile-edit><?= htmlspecialchars($config['profile']['editLabel']) ?></button>
                            <?php if (!empty($config['profile']['adminMode']['enabled'])): ?>
                                <button
                                    type="button"
                                    class="common-topbar__menu-item common-topbar-profile-actions__button common-topbar-profile-actions__button--mode <?= !empty($config['profile']['adminMode']['active']) ? 'common-topbar-profile-actions__button--active' : 'common-topbar-profile-actions__button--inactive' ?>"
                                    data-topbar-admin-mode-toggle
                                    data-admin-mode-url="<?= htmlspecialchars($config['profile']['adminMode']['toggleUrl']) ?>"
                                    data-admin-mode-organization-id="<?= (int)$config['profile']['adminMode']['organizationId'] ?>"
                                    data-admin-mode-enabled="<?= !empty($config['profile']['adminMode']['active']) ? '0' : '1' ?>"
                                ><?= htmlspecialchars(!empty($config['profile']['adminMode']['active']) ? $config['profile']['adminMode']['disableLabel'] : $config['profile']['adminMode']['enableLabel']) ?></button>
                            <?php endif; ?>
                            <?php if (!empty($config['profile']['siteAdminMode']['enabled'])): ?>
                                <button
                                    type="button"
                                    class="common-topbar__menu-item common-topbar-profile-actions__button common-topbar-profile-actions__button--mode <?= !empty($config['profile']['siteAdminMode']['active']) ? 'common-topbar-profile-actions__button--active' : 'common-topbar-profile-actions__button--inactive' ?>"
                                    data-topbar-admin-mode-toggle
                                    data-admin-mode-url="<?= htmlspecialchars($config['profile']['siteAdminMode']['toggleUrl']) ?>"
                                    data-admin-mode-enabled="<?= !empty($config['profile']['siteAdminMode']['active']) ? '0' : '1' ?>"
                                ><?= htmlspecialchars(!empty($config['profile']['siteAdminMode']['active']) ? $config['profile']['siteAdminMode']['disableLabel'] : $config['profile']['siteAdminMode']['enableLabel']) ?></button>
                            <?php endif; ?>
                            <button type="button" class="common-topbar__menu-item common-topbar__menu-item--danger common-topbar-profile-actions__button" data-topbar-logout><?= htmlspecialchars($config['logoutLabel']) ?></button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</header>

<div class="common-topbar-modal" id="commonTopbarModal" hidden>
    <div class="common-topbar-modal__backdrop" data-topbar-modal-close></div>
    <div class="common-topbar-modal__panel" role="dialog" aria-modal="true" aria-labelledby="commonTopbarModalTitle">
        <div class="common-topbar-modal__header">
            <h3 id="commonTopbarModalTitle"><?= htmlspecialchars($config['modal']['defaultTitle']) ?></h3>
            <button type="button" class="common-topbar-modal__close" data-topbar-modal-close aria-label="<?= htmlspecialchars($config['modal']['closeLabel']) ?>">
                <span aria-hidden="true">&times;</span>
                <span class="common-topbar__visually-hidden"><?= htmlspecialchars($config['modal']['closeLabel']) ?></span>
            </button>
        </div>
        <div class="common-topbar-modal__body" id="commonTopbarModalBody"></div>
    </div>
</div>

<div class="common-topbar-drawer" id="commonTopbarDrawer" hidden>
    <div class="common-topbar-drawer__backdrop" data-topbar-drawer-close></div>
    <div class="common-topbar-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="commonTopbarDrawerTitle">
        <div class="common-topbar-drawer__header generic-drawer-header">
            <div class="generic-drawer-header__copy">
                <h3 id="commonTopbarDrawerTitle"><?= htmlspecialchars($config['drawer']['defaultTitle']) ?></h3>
            </div>
            <div class="generic-drawer-header__actions">
                <button type="button" class="common-topbar-drawer__close" data-topbar-drawer-close aria-label="<?= htmlspecialchars($config['drawer']['closeLabel']) ?>">
                    <span aria-hidden="true">&times;</span>
                    <span class="common-topbar__visually-hidden"><?= htmlspecialchars($config['drawer']['closeLabel']) ?></span>
                </button>
            </div>
        </div>
        <div class="common-topbar-drawer__body" id="commonTopbarDrawerBody"></div>
    </div>
</div>

<script>
window.commonTopbarConfig = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php
}

?>
