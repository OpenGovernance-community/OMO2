<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/topbar.php';

$organizationContext = commonResolveOrganizationContext(1);
commonRestoreRememberedUser();
$isDemoGuest = commonCanAccessWithoutLogin($organizationContext);
$omoRootUrl = commonBuildUrl('/omo/', commonGetRootHost());
$requestedOrganizationId = commonGetRequestedOrganizationId();
$isOrganizationHub = commonGetRequestSubdomain() === '' && !commonIsDemoHost() && $requestedOrganizationId <= 0;
$omoDefaultLogo = '/img/logo-OGC.png';
$omoDefaultBanner = '/img/home.jpg';
$omoLandingOrganization = [
    'name' => 'OMO',
    'shortname' => '',
    'domain' => '',
    'logo' => $omoDefaultLogo,
    'banner' => $omoDefaultBanner,
    'color' => '#4f46e5',
];

$omoPwaHeadHtml = implode(PHP_EOL, [
    '<link rel="manifest" href="/omo/manifest.json">',
    '<meta name="theme-color" content="#4f46e5">',
    '<meta name="mobile-web-app-capable" content="yes">',
    '<meta name="apple-mobile-web-app-capable" content="yes">',
    '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
    '<meta name="apple-mobile-web-app-title" content="OMO">',
    '<link rel="icon" href="/omo/icons/icon-192.png" type="image/png" sizes="192x192">',
    '<link rel="apple-touch-icon" href="/omo/icons/icon-192.png">',
    '<link rel="stylesheet" href="/omo/assets/css/install.css">',
]);

$omoPwaBodyEndHtml = '<script src="/omo/assets/js/install.js" defer></script>';
$omoThemeBootstrapHtml = implode(PHP_EOL, [
    '<script src="/shared_functions.js"></script>',
    '<script>sharedApplyDocumentTheme();</script>',
]);

if (!commonGetCurrentUserId() && !$isDemoGuest) {
    commonRenderMagicLoginPage([
        'title' => (($isOrganizationHub ? 'OMO' : $organizationContext['name']) ?: 'OMO') . ' - OMO',
        'appName' => 'OMO',
        'intro' => 'Connectez-vous pour accéder à la structure et aux outils de gouvernance.',
        'returnTo' => commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/omo/', '/omo/'),
        'organization' => $isOrganizationHub ? $omoLandingOrganization : $organizationContext,
        'headHtml' => $omoPwaHeadHtml,
    ]);
}

$currentUserName = $isDemoGuest ? 'Démo' : commonGetCurrentUserDisplayName();
$currentUserId = commonGetCurrentUserId();
if ($isOrganizationHub && !$isDemoGuest) {
    $logoutUrl = '/common/logout.php?return_to=' . urlencode('/omo/');
    $organizationCreateUrl = '/popup/organization_create.php';
    $hubUser = new \dbObject\User();
    $accessibleOrganizations = $hubUser->load($currentUserId)
        ? $hubUser->getAccessibleOrganizations()
        : [];
    $organizationCount = count($accessibleOrganizations);

    ?>
<!DOCTYPE html>
<html lang="fr" class="auth-theme-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $omoThemeBootstrapHtml . PHP_EOL ?>
    <title>Vos espaces OMO</title>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/omo/assets/css/styles.css">
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page auth-state-page--scrollable auth-state-page--themed">
    <main class="auth-state-card auth-state-card--directory">
        <span class="auth-state-status auth-state-status--directory">
            <?= htmlspecialchars($organizationCount === 0 ? 'Aucune organisation pour le moment' : ($organizationCount === 1 ? '1 organisation disponible' : $organizationCount . ' organisations disponibles')) ?>
        </span>
        <h1>Vos espaces OMO</h1>
        <?php if ($organizationCount > 0) { ?>
            <p>Choisissez l'organisation que vous souhaitez ouvrir. Chaque carte vous redirige vers son espace dédié.</p>
        <?php } else { ?>
            <p>Votre compte est bien connecté, mais il n'est rattaché à aucune organisation pour le moment. Vous pouvez en créer une nouvelle ci-dessous.</p>
        <?php } ?>
        <div class="auth-org-list auth-org-list--directory">
            <?php foreach ($accessibleOrganizations as $accessibleOrganization) {
                $organizationName = trim((string)$accessibleOrganization->get('name'));
                if ($organizationName === '') {
                    $organizationName = 'Organisation';
                }
                $organizationShortname = trim((string)$accessibleOrganization->get('shortname'));
                $organizationUrl = $organizationShortname !== ''
                    ? commonBuildUrl('/omo/', commonBuildOrganizationHost($organizationShortname, commonGetRootHost()))
                    : commonBuildUrl('/omo/o/' . (int)$accessibleOrganization->getId(), commonGetRootHost());
                $organizationLogo = trim((string)$accessibleOrganization->get('logo'));
                $organizationBanner = trim((string)$accessibleOrganization->get('banner'));
                $organizationColor = trim((string)$accessibleOrganization->get('color')) ?: '#4f46e5';
                $organizationDomain = trim((string)$accessibleOrganization->get('domain'));
                $organizationInitial = function_exists('mb_substr')
                    ? mb_strtoupper(mb_substr($organizationName, 0, 1))
                    : strtoupper(substr($organizationName, 0, 1));
                $organizationHostLabel = $organizationShortname !== ''
                    ? $organizationShortname . '.' . commonGetRootHost()
                    : ($organizationDomain !== '' ? $organizationDomain : 'Organisation');
                ?>
            <a
                class="auth-org-card auth-org-card--directory"
                href="<?= htmlspecialchars($organizationUrl) ?>"
                style="--auth-org-accent: <?= htmlspecialchars($organizationColor) ?>;"
                aria-label="Ouvrir l'espace <?= htmlspecialchars($organizationName) ?>"
            >
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
                            <span class="auth-org-meta auth-org-meta--directory"><?= htmlspecialchars($organizationHostLabel) ?></span>
                        </div>
                    </div>
                    <div class="auth-org-card__footer">
                        <span class="auth-org-badge"><?= htmlspecialchars($organizationDomain !== '' ? $organizationDomain : 'Espace OMO') ?></span>
                        <span class="auth-org-action">Se connecter</span>
                    </div>
                </div>
            </a>
            <?php } ?>
            <button
                type="button"
                class="auth-org-card auth-org-card--directory auth-org-card--create"
                id="omoCreateOrganizationCard"
                aria-label="Créer une nouvelle organisation"
            >
                <div class="auth-org-card__banner auth-org-card__banner--create"></div>
                <div class="auth-org-card__body">
                    <div class="auth-org-card__header">
                        <div class="auth-org-logo-placeholder auth-org-logo-placeholder--directory auth-org-logo-placeholder--create" aria-hidden="true">+</div>
                        <div class="auth-org-info auth-org-info--directory">
                            <strong class="auth-org-title auth-org-title--directory">Créer une nouvelle organisation</strong>
                            <span class="auth-org-meta auth-org-meta--directory">Nom, domaine, logo, bannière, couleur</span>
                        </div>
                    </div>
                    <div class="auth-org-card__footer">
                        <span class="auth-org-badge">Nouveau</span>
                        <span class="auth-org-action">Ouvrir le formulaire</span>
                    </div>
                </div>
            </button>
        </div>
        <div class="auth-state-actions">
            <a class="auth-state-btn auth-state-btn--primary" href="<?= htmlspecialchars($logoutUrl) ?>">Se déconnecter</a>
        </div>
    </main>

    <div class="omo-directory-modal" id="omoDirectoryModal" hidden>
        <div class="omo-directory-modal__backdrop" data-omo-directory-close></div>
        <div class="omo-directory-modal__panel" role="dialog" aria-modal="true" aria-labelledby="omoDirectoryModalTitle">
            <div class="omo-directory-modal__header">
                <h2 id="omoDirectoryModalTitle">Créer une nouvelle organisation</h2>
                <button type="button" class="omo-directory-modal__close" data-omo-directory-close>Fermer</button>
            </div>
            <div class="omo-directory-modal__body">
                <iframe
                    class="omo-directory-modal__iframe"
                    src="<?= htmlspecialchars($organizationCreateUrl) ?>"
                    loading="lazy"
                    title="Créer une nouvelle organisation"
                ></iframe>
            </div>
        </div>
    </div>

    <style>
        .auth-org-card--create {
            border: 1px dashed rgba(37, 99, 235, 0.28);
            background: linear-gradient(180deg, #ffffff, #f8fbff);
            cursor: pointer;
            text-align: left;
        }

        .auth-org-card__banner--create {
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 32%),
                linear-gradient(135deg, #0f172a, #1e3a8a 58%, #2563eb);
        }

        .auth-org-logo-placeholder--create {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
        }

        .omo-directory-modal[hidden] {
            display: none;
        }

        .omo-directory-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
        }

        .omo-directory-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.56);
            backdrop-filter: blur(4px);
        }

        .omo-directory-modal__panel {
            position: relative;
            width: min(1120px, calc(100vw - 32px));
            height: min(880px, calc(100vh - 32px));
            margin: 16px auto;
            display: flex;
            flex-direction: column;
            border-radius: 22px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.28);
        }

        .omo-directory-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        .omo-directory-modal__header h2 {
            margin: 0;
            font-size: 20px;
            color: #0f172a;
        }

        .omo-directory-modal__close {
            min-height: 40px;
            padding: 8px 14px;
            border: 0;
            border-radius: 10px;
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            cursor: pointer;
        }

        .omo-directory-modal__body {
            flex: 1 1 auto;
            min-height: 0;
            background: #f8fafc;
        }

        .omo-directory-modal__iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #f8fafc;
        }
    </style>

    <script>
        (function () {
            var modal = document.getElementById('omoDirectoryModal');
            var openButton = document.getElementById('omoCreateOrganizationCard');

            if (!modal || !openButton) {
                return;
            }

            function closeModal() {
                modal.hidden = true;
                document.body.classList.remove('omo-directory-modal-open');
            }

            function openModal() {
                modal.hidden = false;
                document.body.classList.add('omo-directory-modal-open');
            }

            openButton.addEventListener('click', openModal);

            modal.addEventListener('click', function (event) {
                if (event.target.closest('[data-omo-directory-close]')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });
        })();
    </script>
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
    <title>Acces interdit - OMO</title>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-card">
        <h1>Acces interdit</h1>
        <p>Votre compte est bien connecte, mais il n'a pas encore acces a l'organisation <?= htmlspecialchars($organizationContext['name'] ?: 'demandee') ?>.</p>
        <p>Pour le moment, l'acces a cet espace est reserve aux personnes presentes dans la liste des membres autorises.</p>
        <div class="auth-state-actions">
            <a class="auth-state-btn auth-state-btn--secondary" href="<?= htmlspecialchars($omoRootUrl) ?>">Revenir a l'accueil</a>
            <a class="auth-state-btn auth-state-btn--primary" href="<?= htmlspecialchars($logoutUrl) ?>">Se deconnecter</a>
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

$currentUser = new \dbObject\User();
if ($currentUser->load($currentUserId)) {
    $currentUserProfile['displayName'] = (string)$currentUser->getScopedDisplayName((int)$organizationContext['id']);
    $currentUserProfile['email'] = (string)$currentUser->getScopedEmail((int)$organizationContext['id']);
    $currentUserProfile['username'] = (string)$currentUser->getScopedUsername((int)$organizationContext['id']);
    $currentUserProfile['photoUrl'] = (string)$currentUser->getScopedProfilePhotoUrl((int)$organizationContext['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gouvernance UI</title>
    <?= $omoThemeBootstrapHtml . PHP_EOL ?>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/omo/assets/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
                    'url' => '/lms/parcours.php?idp=1&embed=1',
                ],
            ],
        ]);
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

    <button data-view="menu" class="nav-btn">Outils</button>
    <button data-view="left" class="nav-btn">Contexte</button>
    <button data-view="right" class="nav-btn">Structure</button>

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
            'currentUserName' => $currentUserName,
            'userProfile' => $currentUserProfile,
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
?>;
</script>
<script src="/omo/assets/js/install.js" defer></script>
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
