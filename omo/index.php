<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/topbar.php';
require_once dirname(__DIR__) . '/common/patreon.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once __DIR__ . '/topbar.php';

$sourceLang = [
    'app.access_denied.message' => [
        'text' => "Votre compte est bien connecte, mais il n'a pas encore acces a l'organisation {organizationName}.",
        'context' => 'Message shown on the forbidden access page when the user is logged in but has no access to the organization.',
    ],
    'app.access_denied.organization_fallback' => [
        'text' => 'demandee',
        'context' => 'Fallback organization name used on the forbidden access page when the organization name is missing.',
    ],
    'app.access_denied.page_description' => [
        'text' => "Pour le moment, l'acces a cet espace est reserve aux personnes presentes dans la liste des membres autorises.",
        'context' => 'Additional explanation shown on the forbidden access page.',
    ],
    'app.access_denied.page_heading' => [
        'text' => 'Acces interdit',
        'context' => 'Main heading shown on the forbidden access page.',
    ],
    'app.access_denied.page_title' => [
        'text' => 'Acces interdit - OMO',
        'context' => 'Browser title shown on the forbidden access page.',
    ],
    'app.directory.create.action' => [
        'text' => 'Ouvrir le formulaire',
        'context' => 'Action label displayed on the create organization card.',
    ],
    'app.directory.create.aria_label' => [
        'text' => 'Creer une nouvelle organisation',
        'context' => 'Aria label for the create organization card button.',
    ],
    'app.directory.create.badge' => [
        'text' => 'Nouveau',
        'context' => 'Badge shown on the create organization card.',
    ],
    'app.directory.create.description' => [
        'text' => 'Nom, domaine, logo, banniere, couleur',
        'context' => 'Subtitle shown on the create organization card.',
    ],
    'app.directory.create.modal_title' => [
        'text' => 'Creer une nouvelle organisation',
        'context' => 'Title shown in the create organization modal and iframe title.',
    ],
    'app.directory.create.title' => [
        'text' => 'Creer une nouvelle organisation',
        'context' => 'Title shown on the create organization card.',
    ],
    'app.directory.cta.connect' => [
        'text' => 'Se connecter',
        'context' => 'Action label shown on an organization card to enter its workspace.',
    ],
    'app.directory.cta.view_invitation' => [
        'text' => "Voir l'invitation",
        'context' => 'Action label shown on a pending invitation card to open the invitation instead of the organization workspace.',
    ],
    'app.directory.description.empty' => [
        'text' => "Votre compte est bien connecté, mais il n'est rattaché à aucune organisation pour le moment. Vous pouvez en créer une nouvelle ci-dessous.",
        'context' => 'Message shown on the organization directory when the user has no accessible organizations.',
    ],
    'app.directory.description.with_results' => [
        'text' => "Choisissez l'organisation que vous souhaitez ouvrir. Chaque carte vous redirige vers son espace dédié.",
        'context' => 'Message shown on the organization directory when organizations are available.',
    ],
    'app.directory.fallback_badge' => [
        'text' => 'Espace OMO',
        'context' => 'Fallback badge label on an organization card when no custom domain is available.',
    ],
    'app.directory.invitation.badge' => [
        'text' => 'Invitation en attente',
        'context' => 'Badge shown on an organization card when the user has a pending invitation for that organization.',
    ],
    'app.directory.invitation.pending_holons' => [
        'one' => '{count} holon en attente',
        'other' => '{count} holons en attente',
        'context' => 'Summary shown on a pending invitation organization card with the number of holons included in the invitation.',
    ],
    'app.directory.invitation.pending_organization' => [
        'text' => 'Acces a confirmer',
        'context' => 'Summary shown on a pending invitation organization card when there is no holon detail to display.',
    ],
    'app.directory.fallback_organization_name' => [
        'text' => 'Organisation',
        'context' => 'Fallback organization name used when an organization record has no visible name.',
    ],
    'app.directory.heading' => [
        'text' => 'Vos espaces OMO',
        'context' => 'Main heading shown on the organization directory page.',
    ],
    'app.directory.template.badge' => [
        'text' => "Template partage",
        'context' => 'Badge shown on a shared organization template card.',
    ],
    'app.directory.templates.heading' => [
        'text' => "Vos template d'organisation",
        'context' => 'Heading shown above the shared organization template cards on the directory page.',
    ],
    'app.directory.js.action_error' => [
        'text' => 'Action impossible.',
        'context' => 'Fallback error message shown in JavaScript when an organization card action fails.',
    ],
    'app.directory.js.default_organization_name' => [
        'text' => 'cette organisation',
        'context' => 'Fallback organization name used in JavaScript confirmation dialogs when the name is missing.',
    ],
    'app.directory.js.delete_confirm' => [
        'text' => "Supprimer {organizationName} ?\n\nLa structure, les membres, les cercles, les roles, les partages et les documents relies seront supprimes.",
        'context' => 'Confirmation dialog shown before deleting an organization from the directory page.',
    ],
    'app.directory.js.leave_confirm' => [
        'text' => "Quitter {organizationName} ?\n\nVos liens avec l'organisation, ses cercles et ses roles seront retires.",
        'context' => 'Confirmation dialog shown before leaving an organization from the directory page.',
    ],
    'app.directory.menu.actions_aria_label' => [
        'text' => 'Actions pour {organizationName}',
        'context' => 'Aria label for the actions menu button on an organization card.',
    ],
    'app.directory.menu.delete' => [
        'text' => 'Supprimer',
        'context' => 'Menu item label used to delete an organization from the directory page.',
    ],
    'app.directory.menu.leave' => [
        'text' => 'Quitter',
        'context' => 'Menu item label used to leave an organization from the directory page.',
    ],
    'app.directory.modal.close' => [
        'text' => 'Fermer',
        'context' => 'Button label used to close the create organization modal.',
    ],
    'app.directory.open_organization_aria_label' => [
        'text' => "Ouvrir l'espace {organizationName}",
        'context' => 'Aria label for the clickable overlay opening an organization workspace.',
    ],
    'app.directory.page_title' => [
        'text' => 'Vos espaces OMO',
        'context' => 'Browser title shown on the organization directory page.',
    ],
    'app.directory.status.none' => [
        'text' => 'Aucune organisation pour le moment',
        'context' => 'Status label shown on the organization directory page when the user has no accessible organizations.',
    ],
    'app.directory.status.available' => [
        'one' => '{count} organisation disponible',
        'other' => '{count} organisations disponibles',
        'context' => 'Status label shown on the organization directory page with the number of accessible organizations.',
    ],
    'app.login.intro' => [
        'text' => 'Connectez-vous pour acceder à la structure et aux outils de gouvernance.',
        'context' => 'Intro text shown on the login page before accessing OMO.',
    ],
    'app.login.page_title' => [
        'text' => '{organizationName} - OMO',
        'context' => 'Browser title shown on the login page.',
    ],
    'app.main.page_title' => [
        'text' => 'Gouvernance UI',
        'context' => 'Browser title shown on the main OMO application page.',
    ],
    'app.mobile.context' => [
        'text' => 'Contexte',
        'context' => 'Mobile navigation label for the context panel.',
    ],
    'app.mobile.menu' => [
        'text' => 'Outils',
        'context' => 'Mobile navigation label for the tools panel.',
    ],
    'app.mobile.structure' => [
        'text' => 'Structure',
        'context' => 'Mobile navigation label for the structure panel.',
    ],
    'app.not_found.message' => [
        'text' => "L'organisation demandée n'existe pas ou n'est plus disponible.",
        'context' => 'Main message shown on the organization not found page.',
    ],
    'app.not_found.page_description' => [
        'text' => "Vous pouvez revenir à l'accueil OMO et choisir un autre espace.",
        'context' => 'Additional explanation shown on the organization not found page.',
    ],
    'app.not_found.page_heading' => [
        'text' => 'Organisation introuvable',
        'context' => 'Main heading shown on the organization not found page.',
    ],
    'app.not_found.page_title' => [
        'text' => 'Organisation introuvable - OMO',
        'context' => 'Browser title shown on the organization not found page.',
    ],
    'app.patreon.prompt_title' => [
        'text' => 'Soutenir le projet',
        'context' => 'Title passed to the Patreon welcome popup configuration.',
    ],
    'app.user.demo' => [
        'text' => 'Demo',
        'context' => 'Display name shown for a demo guest user.',
    ],
    'common.back_to_home' => [
        'text' => "Revenir à l'accueil",
        'context' => 'Generic action label used to return to the OMO home page.',
    ],
    'common.logout' => [
        'text' => 'Se deconnecter',
        'context' => 'Generic action label used to log out from OMO.',
    ],
];

$lang = translationBundleInit('omo_index_page', omoGetTranslationLocale(), $sourceLang);

$organizationContext = commonResolveOrganizationContext(1);
commonRestoreRememberedUser();
$isDemoGuest = commonCanAccessWithoutLogin($organizationContext);
$omoRootUrl = commonBuildUrl('/omo/', commonGetRootHost());
$requestedOrganizationId = commonGetRequestedOrganizationId();
$isOrganizationHub = commonGetRequestSubdomain() === '' && !commonIsDemoHost() && $requestedOrganizationId <= 0;
$omoDefaultLogo = '/img/logo-OGC.png';
$omoDefaultBanner = '/img/home.jpg';
$omoLandingOrganization = [
    'name' => 'OpenMyOrganization',
    'shortname' => '',
    'domain' => '',
    'logo' => $omoDefaultLogo,
    'banner' => $omoDefaultBanner,
    'color' => '',
];

function omoResolvePwaIconUrl($iconUrl, $fallback = '/omo/icons/icon-192.png')
{
    $iconUrl = trim((string)$iconUrl);

    if ($iconUrl === '') {
        return $fallback;
    }

    return $iconUrl;
}

function omoBuildManifestUrlForContext(array $organizationContext, $fallback = '/omo/manifest.php')
{
    $manifestPath = (string)$fallback;
    $organizationId = (int)($organizationContext['id'] ?? 0);

    if ($organizationId <= 0) {
        return $manifestPath;
    }

    $separator = strpos($manifestPath, '?') === false ? '?' : '&';
    return $manifestPath . $separator . 'oid=' . $organizationId;
}

function omoBuildManifestIconUrlForContext(array $organizationContext, $size = 192, $purpose = 'any')
{
    $query = [
        'size' => (int)$size,
    ];

    $organizationId = (int)($organizationContext['id'] ?? 0);
    if ($organizationId > 0) {
        $query['oid'] = $organizationId;
    }

    if ($purpose === 'maskable') {
        $query['purpose'] = 'maskable';
    }

    return '/omo/manifest_icon.php?' . http_build_query($query);
}

function omoBuildPwaHeadHtml($themeColor = '#004663', $iconUrl = '/omo/icons/icon-192.png', $appTitle = 'OMO', $manifestUrl = '/omo/manifest.php')
{
    $resolvedIconUrl = omoResolvePwaIconUrl($iconUrl);
    $resolvedAppTitle = trim((string)$appTitle) !== '' ? (string)$appTitle : 'OMO';

    return implode(PHP_EOL, [
        '<link rel="manifest" href="' . htmlspecialchars((string)$manifestUrl, ENT_QUOTES, 'UTF-8') . '">',
        '<meta name="theme-color" content="' . htmlspecialchars((string)$themeColor, ENT_QUOTES, 'UTF-8') . '">',
        '<meta name="mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
        '<meta name="apple-mobile-web-app-title" content="' . htmlspecialchars($resolvedAppTitle, ENT_QUOTES, 'UTF-8') . '">',
        '<link rel="icon" href="' . htmlspecialchars($resolvedIconUrl, ENT_QUOTES, 'UTF-8') . '">',
        '<link rel="apple-touch-icon" href="' . htmlspecialchars($resolvedIconUrl, ENT_QUOTES, 'UTF-8') . '">',
        '<link rel="stylesheet" href="/omo/assets/css/install.css">',
    ]);
}

function omoBuildDirectoryCardData(array $directoryEntry, $currentUserId)
{
    $accessibleOrganization = $directoryEntry['organization'] ?? null;
    if (!($accessibleOrganization instanceof \dbObject\Organization)) {
        return null;
    }

    $pendingInvitation = ($directoryEntry['pendingInvitation'] ?? null) instanceof \dbObject\Invitation
        ? $directoryEntry['pendingInvitation']
        : null;
    $organizationName = trim((string)$accessibleOrganization->get('name'));
    if ($organizationName === '') {
        $organizationName = t('app.directory.fallback_organization_name');
    }

    $organizationMembership = $pendingInvitation ? null : $accessibleOrganization->getMembership($currentUserId, true);
    $organizationShortname = trim((string)$accessibleOrganization->get('shortname'));
    $organizationUrl = $pendingInvitation
        ? $pendingInvitation->getInvitationUrl()
        : commonBuildOrganizationHomeUrl((int)$accessibleOrganization->getId(), $organizationShortname, commonGetRootHost());
    $organizationDomain = trim((string)$accessibleOrganization->get('domain'));
    $organizationInitial = function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($organizationName, 0, 1))
        : strtoupper(substr($organizationName, 0, 1));
    $organizationHostLabel = commonBuildOrganizationAccessLabel((int)$accessibleOrganization->getId(), $organizationShortname, commonGetRootHost());
    $invitationPendingHolons = $pendingInvitation ? $pendingInvitation->getPendingHolons() : [];
    $isTemplateOrganization = $accessibleOrganization->isSharedAsTemplate();

    return [
        'organization' => $accessibleOrganization,
        'pendingInvitation' => $pendingInvitation,
        'organizationMembership' => $organizationMembership,
        'canDeleteOrganization' => $accessibleOrganization->canDelete(),
        'organizationName' => $organizationName,
        'organizationUrl' => $organizationUrl,
        'organizationLogo' => trim((string)$accessibleOrganization->get('logo')),
        'organizationBanner' => trim((string)$accessibleOrganization->get('banner')),
        'organizationColor' => trim((string)$accessibleOrganization->get('color')) ?: '#4f46e5',
        'organizationInitial' => $organizationInitial,
        'organizationCardMeta' => $pendingInvitation
            ? (
                count($invitationPendingHolons) > 0
                    ? t('app.directory.invitation.pending_holons', ['count' => count($invitationPendingHolons)])
                    : t('app.directory.invitation.pending_organization')
            )
            : $organizationHostLabel,
        'organizationCardBadge' => $pendingInvitation
            ? t('app.directory.invitation.badge')
            : ($isTemplateOrganization
                ? t('app.directory.template.badge')
                : ($organizationDomain !== '' ? $organizationDomain : t('app.directory.fallback_badge'))),
        'organizationCardAction' => $pendingInvitation
            ? t('app.directory.cta.view_invitation')
            : t('app.directory.cta.connect'),
        'organizationAriaLabel' => $pendingInvitation
            ? t('app.directory.cta.view_invitation') . ' - ' . $organizationName
            : t('app.directory.open_organization_aria_label', ['organizationName' => $organizationName]),
        'isTemplateOrganization' => $isTemplateOrganization,
    ];
}

function omoRenderDirectoryCard(array $directoryCardData)
{
    $accessibleOrganization = $directoryCardData['organization'];
    $pendingInvitation = $directoryCardData['pendingInvitation'];
    $organizationMembership = $directoryCardData['organizationMembership'];
    $canDeleteOrganization = !empty($directoryCardData['canDeleteOrganization']);
    $organizationName = (string)$directoryCardData['organizationName'];
    $organizationUrl = (string)$directoryCardData['organizationUrl'];
    $organizationLogo = (string)$directoryCardData['organizationLogo'];
    $organizationBanner = (string)$directoryCardData['organizationBanner'];
    $organizationColor = (string)$directoryCardData['organizationColor'];
    $organizationInitial = (string)$directoryCardData['organizationInitial'];
    $organizationCardMeta = (string)$directoryCardData['organizationCardMeta'];
    $organizationCardBadge = (string)$directoryCardData['organizationCardBadge'];
    $organizationCardAction = (string)$directoryCardData['organizationCardAction'];
    $organizationAriaLabel = (string)$directoryCardData['organizationAriaLabel'];
    ?>
            <article
                class="auth-org-card auth-org-card--directory auth-org-card--directory-managed<?= $pendingInvitation ? ' auth-org-card--directory-pending' : '' ?>"
                style="--auth-org-accent: <?= htmlspecialchars($organizationColor) ?>;"
                data-organization-id="<?= (int)$accessibleOrganization->getId() ?>"
                data-organization-name="<?= htmlspecialchars($organizationName, ENT_QUOTES, 'UTF-8') ?>"
            >
                <a
                    class="auth-org-card__overlay-link"
                    href="<?= htmlspecialchars($organizationUrl) ?>"
                    aria-label="<?= htmlspecialchars($organizationAriaLabel) ?>"
                ></a>
                <?php if ($organizationMembership) { ?>
                <div class="omo-org-card-menu" data-omo-org-card-menu>
                    <button
                        type="button"
                        class="omo-org-card-menu__trigger"
                        data-omo-org-menu-trigger
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-label="<?= htmlspecialchars(t('app.directory.menu.actions_aria_label', ['organizationName' => $organizationName])) ?>"
                    >...</button>
                    <div class="omo-org-card-menu__panel" data-omo-org-menu-panel>
                        <button
                            type="button"
                            class="omo-org-card-menu__item"
                            data-omo-org-action="leave"
                        ><?= htmlspecialchars(t('app.directory.menu.leave')) ?></button>
                        <?php if ($canDeleteOrganization) { ?>
                        <button
                            type="button"
                            class="omo-org-card-menu__item omo-org-card-menu__item--danger"
                            data-omo-org-action="delete"
                        ><?= htmlspecialchars(t('app.directory.menu.delete')) ?></button>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
                <div class="auth-org-card__banner">
                    <?php if ($organizationBanner !== '') { ?>
                    <img src="<?= htmlspecialchars($organizationBanner) ?>" alt="" loading="lazy">
                    <?php } ?>
                </div>
                <div class="auth-org-card__body">
                    <div class="auth-org-card__header">
                        <?php if ($organizationLogo !== '') { ?>
                        <img class="auth-org-logo auth-org-logo--directory" src="<?= htmlspecialchars($organizationLogo) ?>" alt="<?= htmlspecialchars($organizationName) ?>" loading="lazy">
                        <?php } else { ?>
                        <div class="auth-org-logo-placeholder auth-org-logo-placeholder--directory" aria-hidden="true"><?= htmlspecialchars($organizationInitial) ?></div>
                        <?php } ?>
                        <div class="auth-org-info auth-org-info--directory">
                            <strong class="auth-org-title auth-org-title--directory"><?= htmlspecialchars($organizationName) ?></strong>
                            <span class="auth-org-meta auth-org-meta--directory"><?= htmlspecialchars($organizationCardMeta) ?></span>
                        </div>
                    </div>
                    <div class="auth-org-card__footer">
                        <span class="auth-org-badge<?= $pendingInvitation ? ' auth-org-badge--pending' : '' ?>"><?= htmlspecialchars($organizationCardBadge) ?></span>
                        <span class="auth-org-action"><?= htmlspecialchars($organizationCardAction) ?></span>
                    </div>
                </div>
            </article>
    <?php
}

$omoPwaBodyEndHtml = '<script src="/omo/assets/js/install.js" defer></script>';
$omoThemeBootstrapHtml = implode(PHP_EOL, [
    '<script src="/shared_functions.js"></script>',
    '<script>sharedApplyDocumentTheme();</script>',
]);

if (!commonGetCurrentUserId() && !$isDemoGuest) {
    $loginOrganizationContext = $isOrganizationHub ? $omoLandingOrganization : $organizationContext;
    $omoPwaHeadHtml = omoBuildPwaHeadHtml(
        commonGetOrganizationAccentColor($loginOrganizationContext, '#004663'),
        omoBuildManifestIconUrlForContext($loginOrganizationContext, 192),
        ($loginOrganizationContext['name'] ?? 'OMO') ?: 'OMO',
        omoBuildManifestUrlForContext($loginOrganizationContext)
    );

    commonRenderMagicLoginPage([
        'title' => t('app.login.page_title', [
            'organizationName' => (($isOrganizationHub ? 'OMO' : $organizationContext['name']) ?: 'OMO'),
        ]),
        'appName' => 'OMO',
        'intro' => t('app.login.intro'),
        'returnTo' => commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/omo/', '/omo/'),
        'organization' => $loginOrganizationContext,
        'headHtml' => $omoThemeBootstrapHtml . PHP_EOL . $omoPwaHeadHtml,
        'bodyEndHtml' => $omoPwaBodyEndHtml,
        'topbar' => omoBuildTopbarOptions($loginOrganizationContext, [
            'variant' => 'login',
            'isDemoGuest' => $isDemoGuest,
            'logoutReturnTo' => '/omo/',
        ]),
    ]);
}

$currentUserName = $isDemoGuest ? t('app.user.demo') : commonGetCurrentUserDisplayName();
$currentUserId = commonGetCurrentUserId();
$isSiteAdmin = !$isDemoGuest && commonCurrentUserIsSiteAdminModeEnabled();
$omoPwaHeadHtml = omoBuildPwaHeadHtml(
    commonGetOrganizationAccentColor($organizationContext, '#004663'),
    omoBuildManifestIconUrlForContext($organizationContext, 192),
    ($organizationContext['name'] ?? 'OMO') ?: 'OMO',
    omoBuildManifestUrlForContext($organizationContext)
);
if (empty($organizationContext['isValid'])) {
    http_response_code(404);
    $logoutUrl = '/common/logout.php?return_to=' . urlencode('/omo/');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('app.not_found.page_title')) ?></title>
    <?= $omoThemeBootstrapHtml . PHP_EOL ?>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page auth-state-page--with-topbar">
    <?php
    commonRenderTopbar(omoBuildTopbarOptions($organizationContext, [
        'variant' => 'hub',
        'isDemoGuest' => $isDemoGuest,
        'logoutReturnTo' => '/omo/',
    ]));
    ?>
    <main class="auth-state-layout">
    <div class="auth-state-card">
        <h1><?= htmlspecialchars(t('app.not_found.page_heading')) ?></h1>
        <p><?= htmlspecialchars(t('app.not_found.message')) ?></p>
        <p><?= htmlspecialchars(t('app.not_found.page_description')) ?></p>
        <div class="auth-state-actions">
            <a class="auth-state-btn auth-state-btn--secondary" href="<?= htmlspecialchars($omoRootUrl) ?>"><?= htmlspecialchars(t('common.back_to_home')) ?></a>
            <?php if (!$isDemoGuest) { ?>
            <a class="auth-state-btn auth-state-btn--primary" href="<?= htmlspecialchars($logoutUrl) ?>"><?= htmlspecialchars(t('common.logout')) ?></a>
            <?php } ?>
        </div>
    </div>
    </main>
</body>
</html>
<?php
    exit;
}

if ($isOrganizationHub && !$isDemoGuest) {
    $logoutUrl = '/common/logout.php?return_to=' . urlencode('/omo/');
    $organizationCreateUrl = '/popup/organization_create.php';
    $hubUser = new \dbObject\User();
    $accessibleOrganizations = $hubUser->load($currentUserId)
        ? $hubUser->getAccessibleOrganizations()
        : [];
    $pendingInvitations = $hubUser->getId() > 0
        ? $hubUser->getPendingOrganizationInvitations()
        : [];
    $directoryEntries = [];
    $directoryOrganizationIds = [];
    foreach ($accessibleOrganizations as $accessibleOrganization) {
        $organizationId = (int)$accessibleOrganization->getId();
        if ($organizationId <= 0 || isset($directoryOrganizationIds[$organizationId])) {
            continue;
        }

        $directoryEntries[] = [
            'organization' => $accessibleOrganization,
            'pendingInvitation' => null,
        ];
        $directoryOrganizationIds[$organizationId] = true;
    }

    foreach ($pendingInvitations as $pendingInvitation) {
        if (!($pendingInvitation instanceof \dbObject\Invitation)) {
            continue;
        }

        $organization = new \dbObject\Organization();
        $organizationId = (int)$pendingInvitation->get('IDorganization');
        if ($organizationId <= 0 || isset($directoryOrganizationIds[$organizationId]) || !$organization->load($organizationId)) {
            continue;
        }

        $directoryEntries[] = [
            'organization' => $organization,
            'pendingInvitation' => $pendingInvitation,
        ];
        $directoryOrganizationIds[$organizationId] = true;
    }

    $organizationDirectoryCards = [];
    $templateDirectoryCards = [];
    foreach ($directoryEntries as $directoryEntry) {
        $directoryCardData = omoBuildDirectoryCardData($directoryEntry, $currentUserId);
        if ($directoryCardData === null) {
            continue;
        }

        if (!empty($directoryCardData['isTemplateOrganization'])) {
            $templateDirectoryCards[] = $directoryCardData;
            continue;
        }

        $organizationDirectoryCards[] = $directoryCardData;
    }

    $organizationCount = count($organizationDirectoryCards) + count($templateDirectoryCards);
    $organizationStatusLabel = $organizationCount === 0
        ? t('app.directory.status.none')
        : t('app.directory.status.available', ['count' => $organizationCount]);
    $directoryJsTranslations = [
        'actionError' => t('app.directory.js.action_error'),
        'defaultOrganizationName' => t('app.directory.js.default_organization_name'),
        'deleteConfirm' => t('app.directory.js.delete_confirm', ['organizationName' => '{organizationName}']),
        'leaveConfirm' => t('app.directory.js.leave_confirm', ['organizationName' => '{organizationName}']),
    ];

    ?>
<!DOCTYPE html>
<html lang="fr" class="auth-theme-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $omoThemeBootstrapHtml . PHP_EOL ?>
    <title><?= htmlspecialchars(t('app.directory.page_title')) ?></title>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css">
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page auth-state-page--scrollable auth-state-page--themed auth-state-page--with-topbar">
    <?php
    commonRenderTopbar(omoBuildTopbarOptions($omoLandingOrganization, [
        'variant' => 'hub',
        'isDemoGuest' => $isDemoGuest,
        'logoutReturnTo' => '/omo/',
    ]));
    ?>
    <main class="auth-state-layout auth-state-layout--scrollable">
    <div class="auth-state-card auth-state-card--directory">
        <span class="auth-state-status auth-state-status--directory">
            <?= htmlspecialchars($organizationStatusLabel) ?>
        </span>
        <div class="omo-directory-section">
        <h1><?= htmlspecialchars(t('app.directory.heading')) ?></h1>
        <?php if ($organizationCount > 0) { ?>
            <p><?= htmlspecialchars(t('app.directory.description.with_results')) ?></p>
        <?php } else { ?>
            <p><?= htmlspecialchars(t('app.directory.description.empty')) ?></p>
        <?php } ?>
        <div class="auth-org-list auth-org-list--directory">
            <?php foreach ($organizationDirectoryCards as $directoryCardData) {
                omoRenderDirectoryCard($directoryCardData);
            } ?>
            <button
                type="button"
                class="auth-org-card auth-org-card--directory auth-org-card--create"
                id="omoCreateOrganizationCard"
                aria-label="<?= htmlspecialchars(t('app.directory.create.aria_label')) ?>"
            >
                <div class="auth-org-card__banner auth-org-card__banner--create"></div>
                <div class="auth-org-card__body">
                    <div class="auth-org-card__header">
                        <div class="auth-org-logo-placeholder auth-org-logo-placeholder--directory auth-org-logo-placeholder--create" aria-hidden="true">+</div>
                        <div class="auth-org-info auth-org-info--directory">
                            <strong class="auth-org-title auth-org-title--directory"><?= htmlspecialchars(t('app.directory.create.title')) ?></strong>
                            <span class="auth-org-meta auth-org-meta--directory"><?= htmlspecialchars(t('app.directory.create.description')) ?></span>
                        </div>
                    </div>
                    <div class="auth-org-card__footer">
                        <span class="auth-org-badge"><?= htmlspecialchars(t('app.directory.create.badge')) ?></span>
                        <span class="auth-org-action"><?= htmlspecialchars(t('app.directory.create.action')) ?></span>
                    </div>
                </div>
            </button>
        </div>
        </div>
        <?php if (count($templateDirectoryCards) > 0) { ?>
        <section class="omo-directory-section omo-directory-section--templates" aria-labelledby="omoDirectoryTemplatesHeading">
            <h2 id="omoDirectoryTemplatesHeading" class="omo-directory-section__title generic-card-title generic-card-title--big"><?= htmlspecialchars(t('app.directory.templates.heading')) ?></h2>
            <div class="auth-org-list auth-org-list--directory">
                <?php foreach ($templateDirectoryCards as $directoryCardData) {
                    omoRenderDirectoryCard($directoryCardData);
                } ?>
            </div>
        </section>
        <?php } ?>
        <div class="auth-state-actions">
            <a class="auth-state-btn auth-state-btn--primary" href="<?= htmlspecialchars($logoutUrl) ?>"><?= htmlspecialchars(t('common.logout')) ?></a>
        </div>
    </div>
    </main>

    <script>
        window.omoDirectoryTranslations = <?= json_encode($directoryJsTranslations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <style>
        body.auth-state-page--with-topbar.auth-state-page--scrollable {
            padding-top: var(--topbar-height, 48px);
        }

        body.auth-state-page--with-topbar.auth-state-page--scrollable > .common-topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
        }

        body.auth-state-page--with-topbar.auth-state-page--scrollable > .auth-state-layout {
            min-height: calc(100vh - var(--topbar-height, 48px));
        }

        .omo-directory-section + .omo-directory-section {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--color-border, #dbe4ee);
        }

        .omo-directory-section__title {
            margin: 0 0 14px;
        }

        .auth-org-card--directory-managed {
            position: relative;
        }

        .auth-org-card--directory-pending {
            outline: 2px dashed color-mix(in srgb, var(--auth-org-accent) 48%, white);
            outline-offset: 2px;
        }

        .auth-org-card__overlay-link {
            position: absolute;
            inset: 0;
            z-index: 1;
            border-radius: inherit;
        }

        .auth-org-card--directory-managed .auth-org-card__banner,
        .auth-org-card--directory-managed .auth-org-card__body {
            position: relative;
            z-index: 0;
        }

        .auth-org-badge--pending {
            background: color-mix(in srgb, var(--auth-org-accent) 16%, var(--color-surface, #ffffff));
            color: color-mix(in srgb, var(--auth-org-accent) 72%, var(--color-text, #111827));
        }

        .omo-org-card-menu {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 3;
        }

        .omo-org-card-menu__trigger {
            min-width: 40px;
            min-height: 40px;
            padding: 0 10px 4px;
            border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 86%, transparent);
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-surface, #ffffff) 90%, transparent);
            color: var(--color-text, #0f172a);
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 10px 24px color-mix(in srgb, var(--color-text, #0f172a) 12%, transparent);
        }

        .omo-org-card-menu__panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 164px;
            padding: 8px;
            display: none;
            flex-direction: column;
            gap: 6px;
            border-radius: 16px;
            border: 1px solid var(--color-border, #dbe4ee);
            background: var(--color-surface, #ffffff);
            box-shadow: 0 18px 42px color-mix(in srgb, var(--color-text, #0f172a) 16%, transparent);
        }

        .omo-org-card-menu.is-open .omo-org-card-menu__panel {
            display: flex;
        }

        .omo-org-card-menu__item {
            width: 100%;
            min-height: 40px;
            padding: 10px 12px;
            border: 0;
            border-radius: 12px;
            background: var(--color-surface-alt, #f8fafc);
            color: var(--color-text, #0f172a);
            text-align: left;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .omo-org-card-menu__item:hover {
            background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
        }

        .omo-org-card-menu__item--danger {
            color: #b91c1c;
            background: color-mix(in srgb, #dc2626 10%, var(--color-surface, #ffffff));
        }

        .omo-org-card-menu__item--danger:hover {
            background: color-mix(in srgb, #dc2626 18%, var(--color-surface, #ffffff));
        }

        .auth-org-card--create {
            border: 1px dashed color-mix(in srgb, var(--color-primary, #2563eb) 28%, var(--color-border, #d1d5db));
            background:
                linear-gradient(
                    180deg,
                    color-mix(in srgb, var(--color-surface, #ffffff) 96%, var(--color-primary, #2563eb) 4%),
                    color-mix(in srgb, var(--color-surface-alt, #f8fbff) 92%, var(--color-primary, #2563eb) 8%)
                );
            cursor: pointer;
            text-align: left;
        }

        .auth-org-card__banner--create {
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 32%),
                linear-gradient(135deg, #0f172a, #1e3a8a 58%, #2563eb);
        }

        .auth-org-logo-placeholder--create {
            background: color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-surface, #ffffff));
            color: var(--color-primary, #2563eb);
        }

    </style>

    <script>
        (function () {
            var openButton = document.getElementById('omoCreateOrganizationCard');
            var organizationActionUrl = '/omo/api/organizations/card_action.php';
            var organizationCreateUrl = <?= json_encode($organizationCreateUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            var organizationCreateModalTitle = <?= json_encode(t('app.directory.create.modal_title'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

            if (!openButton) {
                return;
            }

            function interpolateTemplate(template, variables) {
                return String(template || '').replace(/\{(\w+)\}/g, function (match, key) {
                    return Object.prototype.hasOwnProperty.call(variables, key) ? String(variables[key]) : match;
                });
            }

            function closeMenus() {
                document.querySelectorAll('[data-omo-org-card-menu].is-open').forEach(function (menu) {
                    var trigger = menu.querySelector('[data-omo-org-menu-trigger]');
                    menu.classList.remove('is-open');
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            function openModal() {
                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(organizationCreateModalTitle, organizationCreateUrl, 'fetch');
                    return;
                }

                window.location.href = organizationCreateUrl;
            }

            openButton.addEventListener('click', openModal);

            document.addEventListener('click', function (event) {
                var trigger = event.target.closest('[data-omo-org-menu-trigger]');
                if (trigger) {
                    event.preventDefault();
                    event.stopPropagation();

                    var menu = trigger.closest('[data-omo-org-card-menu]');
                    var shouldOpen = !menu.classList.contains('is-open');
                    closeMenus();

                    if (shouldOpen) {
                        menu.classList.add('is-open');
                        trigger.setAttribute('aria-expanded', 'true');
                    }

                    return;
                }

                var actionButton = event.target.closest('[data-omo-org-action]');
                if (actionButton) {
                    event.preventDefault();
                    event.stopPropagation();

                    var card = actionButton.closest('[data-organization-id]');
                    if (!card) {
                        return;
                    }

                    var action = actionButton.getAttribute('data-omo-org-action') || '';
                    var organizationId = card.getAttribute('data-organization-id') || '';
                    var organizationName = card.getAttribute('data-organization-name') || window.omoDirectoryTranslations.defaultOrganizationName;
                    var confirmMessage = '';

                    if (action === 'leave') {
                        confirmMessage = interpolateTemplate(window.omoDirectoryTranslations.leaveConfirm, {
                            organizationName: organizationName
                        });
                    } else if (action === 'delete') {
                        confirmMessage = interpolateTemplate(window.omoDirectoryTranslations.deleteConfirm, {
                            organizationName: organizationName
                        });
                    }

                    if (confirmMessage === '' || !window.confirm(confirmMessage)) {
                        closeMenus();
                        return;
                    }

                    actionButton.disabled = true;

                    var payload = new FormData();
                    payload.append('oid', organizationId);
                    payload.append('action', action);

                    fetch(organizationActionUrl, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            return response.text().then(function (text) {
                                var data = null;

                                try {
                                    data = JSON.parse(text);
                                } catch (error) {
                                    data = null;
                                }

                                return {
                                    ok: response.ok,
                                    data: data
                                };
                            });
                        })
                        .then(function (result) {
                            if (!result.ok || !result.data || result.data.status !== true) {
                                throw new Error(result.data && result.data.message ? result.data.message : window.omoDirectoryTranslations.actionError);
                            }

                            closeMenus();

                            if (result.data.redirect) {
                                window.location.href = result.data.redirect;
                                return;
                            }

                            window.location.reload();
                        })
                        .catch(function (error) {
                            actionButton.disabled = false;
                            closeMenus();
                            window.alert(error && error.message ? error.message : window.omoDirectoryTranslations.actionError);
                        });

                    return;
                }

                if (!event.target.closest('[data-omo-org-card-menu]')) {
                    closeMenus();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenus();
                }
            });
        })();
    </script>
    <?php if ($isSiteAdmin) { ?>
    <script>
        window.omoSiteUpdateConfig = {
            enabled: true,
            statusUrl: '/omo/api/parameters/site_update_status.php',
            runUrl: '/omo/api/parameters/site_update_run.php'
        };
    </script>
    <script src="/omo/assets/js/site-update.js"></script>
    <script>
        window.omoInitSiteUpdateCheck(window.omoSiteUpdateConfig);
    </script>
    <?php } ?>
</body>
</html>
<?php
    exit;
}

if (!$isDemoGuest && !commonUserHasOrganizationAccess($currentUserId, (int)$organizationContext['id'])) {
    http_response_code(403);
    $logoutUrl = '/common/logout.php?return_to=' . urlencode('/omo/');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('app.access_denied.page_title')) ?></title>
    <?= $omoThemeBootstrapHtml . PHP_EOL ?>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page auth-state-page--with-topbar">
    <?php
    commonRenderTopbar(omoBuildTopbarOptions($organizationContext, [
        'variant' => 'hub',
        'isDemoGuest' => $isDemoGuest,
        'logoutReturnTo' => '/omo/',
    ]));
    ?>
    <main class="auth-state-layout">
    <div class="auth-state-card">
        <h1><?= htmlspecialchars(t('app.access_denied.page_heading')) ?></h1>
        <p><?= htmlspecialchars(t('app.access_denied.message', ['organizationName' => ($organizationContext['name'] ?: t('app.access_denied.organization_fallback'))])) ?></p>
        <p><?= htmlspecialchars(t('app.access_denied.page_description')) ?></p>
        <div class="auth-state-actions">
            <a class="auth-state-btn auth-state-btn--secondary" href="<?= htmlspecialchars($omoRootUrl) ?>"><?= htmlspecialchars(t('common.back_to_home')) ?></a>
            <a class="auth-state-btn auth-state-btn--primary" href="<?= htmlspecialchars($logoutUrl) ?>"><?= htmlspecialchars(t('common.logout')) ?></a>
        </div>
    </div>
    </main>
</body>
</html>
<?php
    exit;
}

$currentUserProfile = [
    'displayName' => $currentUserName,
    'email' => '',
    'username' => '',
    'phone' => '',
    'photoUrl' => '',
];
$patreonPromptShouldShow = false;

$currentUser = new \dbObject\User();
if ($currentUser->load($currentUserId)) {
    $currentUserProfile['displayName'] = (string)$currentUser->getScopedDisplayName((int)$organizationContext['id']);
    $currentUserProfile['email'] = (string)$currentUser->getScopedEmail((int)$organizationContext['id']);
    $currentUserProfile['username'] = (string)$currentUser->getScopedUsername((int)$organizationContext['id']);
    $currentUserProfile['photoUrl'] = (string)$currentUser->getScopedProfilePhotoUrl((int)$organizationContext['id']);
}

if (!$isDemoGuest && $currentUserId > 0 && patreonSupportUiIsEnabled()) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
    $patreonPromptShouldShow = !($patreonConnection !== false && $patreonConnection->isConnected());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(t('app.main.page_title')) ?></title>
    <?= $omoThemeBootstrapHtml . PHP_EOL ?>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/omo/">
</head>
<body class="view-left" style='overflow:hidden'> <!-- ou view-menu / view-right -->

<div class="app">

    <!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-toggle" id="sidebar-toggle">☰</div>

    <div class="menu" id="menu_sidebar">
        
    </div>

</aside>

    <!-- Main area -->
    <div class="main">

        <?php
        commonRenderTopbar(omoBuildTopbarOptions($organizationContext, [
            'variant' => 'app',
            'isDemoGuest' => $isDemoGuest,
            'logoutReturnTo' => '/omo/',
        ]));
        /*
        commonRenderTopbar([
            'appKey' => 'omo',
            'appLabel' => 'OMO',
            'organization' => $organizationContext,
            'logoutReturnTo' => '/omo/',
            'search' => [
                'enabled' => true,
                'placeholder' => 'Rechercher un cercle, un role ou un outil',
                'buttonLabel' => 'Recherche',
                'callback' => 'omoHandleTopbarSearch',
            ],
            'profile' => $isDemoGuest
                ? [
                    'enabled' => false,
                ]
                : [
                    'buttonLabel' => 'Profil',
                    'editTitle' => 'Votre profil',
                    'editUrl' => '/popup/profil.php',
                    'editMode' => 'fetch',
                ],
            'helpLabel' => 'Aide',
            'helpItems' => [
                [
                    'key' => 'faq',
                    'label' => 'FAQ',
                    'description' => 'Accès aux questions les plus courantes, avec moteur de recherche pour trouver facilement la réponse à ses questions.',
                    'title' => 'FAQ OMO',
                    'callback' => 'omoOpenFaqHelp',
                ],
                [
                    'key' => 'tour',
                    'label' => 'Visite guidée',
                    'description' => 'Tour des fonctions visibles à l’écran avec explication pour chaque bouton et chaque possibilité.',
                    'callback' => 'omoStartGuidedTour',
                ],
                [
                    'key' => 'tutorials',
                    'label' => 'Tutoriels',
                    'description' => 'Des formations ciblées pour monter en compétences dans l’utilisation du logiciel.',
                    'title' => 'Tutoriels',
                    'mode' => 'drawer',
                    'url' => commonBuildUrl('/lms/index.php?embed=1', commonGetRootHost()),
                ],
            ],
        ]);
        */
        ?>

        <!-- Content -->
        <div class="content">

            <!-- Left panel (1/3) -->
            <div class="panel panel-left" id="panel-left">
                <!-- Détails cercle / rôle -->
            </div>

            <!-- Resizer -->
            <div class="resizer" id="resizer"></div>

            <!-- Right panel (2/3) -->
            <div class="panel panel-right" id="panel-right">
                <!-- Structure cercles -->
            </div>

        </div>

    </div>

    <div class="mobile-nav" id="omo-mobile-nav">

    <button data-view="menu" class="nav-btn"><?= htmlspecialchars(t('app.mobile.menu')) ?></button>
    <button data-view="left" class="nav-btn"><?= htmlspecialchars(t('app.mobile.context')) ?></button>
    <button data-view="right" class="nav-btn"><?= htmlspecialchars(t('app.mobile.structure')) ?></button>

</div>

</div>

<div id="tooltip"></div>
<script>
window.omoConfig = <?=
    json_encode(
        [
            'oid' => $organizationContext['id'],
            'shortname' => $organizationContext['shortname'],
            'name' => $organizationContext['name'],
            'host' => $organizationContext['host'],
            'routeMode' => $organizationContext['routeMode'] ?? 'host',
            'orgLookupError' => $organizationContext['error'],
            'isDemo' => $isDemoGuest,
            'currentUserId' => $currentUserId,
            'currentUserName' => $currentUserName,
            'userProfile' => $currentUserProfile,
            'siteUpdate' => $isSiteAdmin ? [
                'enabled' => true,
                'statusUrl' => '/omo/api/parameters/site_update_status.php',
                'runUrl' => '/omo/api/parameters/site_update_run.php',
            ] : [
                'enabled' => false,
            ],
            'patreonPrompt' => [
                'shouldShow' => $patreonPromptShouldShow,
                'title' => t('app.patreon.prompt_title'),
                'url' => '/omo/api/patreon_welcome_popup.php',
                'mode' => 'fetch',
            ],
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
?>;
</script>
<script src="/omo/assets/js/install.js" defer></script>
<?php if ($isSiteAdmin) { ?>
<script src="/omo/assets/js/site-update.js"></script>
<?php } ?>
<script src="assets/js/app.js"></script>

<script>
$(document).ready(function () {

    omoRefreshSidebar(function () {
        handleRoute(); // 👉 tout passe par là
    });

});
</script>

</body>
</html>
