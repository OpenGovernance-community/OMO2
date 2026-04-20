<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/topbar.php';

$organizationContext = commonResolveOrganizationContext(1);
commonRestoreRememberedUser();
$isDemoGuest = commonCanAccessWithoutLogin($organizationContext);
$omoRootUrl = commonBuildUrl('/omo/', commonGetRootHost());
$isOrganizationHub = commonGetRequestSubdomain() === '' && !commonIsDemoHost();
$omoLandingOrganization = [
    'name' => 'OMO',
    'shortname' => '',
    'domain' => '',
    'logo' => '',
    'banner' => '',
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
    $accessibleOrganizations = commonGetAccessibleOrganizations($currentUserId);
    $organizationCount = count($accessibleOrganizations);

    if ($organizationCount === 0) {
        http_response_code(403);
    }
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos espaces OMO</title>
    <?= $omoPwaHeadHtml . PHP_EOL ?>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-card">
        <span class="auth-state-status">
            <?= htmlspecialchars($organizationCount === 0 ? 'Aucune organisation disponible' : ($organizationCount === 1 ? '1 organisation disponible' : $organizationCount . ' organisations disponibles')) ?>
        </span>
        <h1>Vos espaces OMO</h1>
        <?php if ($organizationCount > 0) { ?>
            <p>Choisissez l'organisation que vous souhaitez ouvrir. Chaque lien vous redirige vers son espace dedie.</p>
            <div class="auth-org-list">
                <?php foreach ($accessibleOrganizations as $accessibleOrganization) {
                    $organizationName = trim((string)($accessibleOrganization['name'] ?? 'Organisation'));
                    $organizationShortname = trim((string)($accessibleOrganization['shortname'] ?? ''));
                    $organizationUrl = commonBuildUrl('/omo/', commonBuildOrganizationHost($organizationShortname, commonGetRootHost()));
                    $organizationLogo = trim((string)($accessibleOrganization['logo'] ?? ''));
                    $organizationDomain = trim((string)($accessibleOrganization['domain'] ?? ''));
                    ?>
                <a class="auth-org-card" href="<?= htmlspecialchars($organizationUrl) ?>">
                    <?php if ($organizationLogo !== '') { ?>
                    <img class="auth-org-logo" src="<?= htmlspecialchars($organizationLogo) ?>" alt="<?= htmlspecialchars($organizationName) ?>">
                    <?php } else { ?>
                    <div class="auth-org-logo-placeholder" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($organizationName, 0, 1))) ?></div>
                    <?php } ?>
                    <div class="auth-org-info">
                        <strong class="auth-org-title"><?= htmlspecialchars($organizationName) ?></strong>
                        <span class="auth-org-meta"><?= htmlspecialchars($organizationShortname !== '' ? $organizationShortname . '.' . commonGetRootHost() : ($organizationDomain !== '' ? $organizationDomain : 'Organisation')) ?></span>
                    </div>
                </a>
                <?php } ?>
            </div>
        <?php } else { ?>
            <p>Votre compte est bien connecte, mais il n'est rattache a aucune organisation pour le moment.</p>
            <p>Pour l'instant, l'acces aux espaces OMO est reserve aux personnes presentes dans la liste des membres autorises.</p>
        <?php } ?>
        <div class="auth-state-actions">
            <a class="auth-state-btn auth-state-btn--primary" href="<?= htmlspecialchars($logoutUrl) ?>">Se deconnecter</a>
        </div>
    </main>
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
    $currentUserProfile['email'] = (string)$currentUser->get('email');
    $currentUserProfile['username'] = (string)$currentUser->get('username');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gouvernance UI</title>
    <script>
    (function () {
        var storageKey = 'omo-theme-preference';
        var preference = 'system';

        try {
            var storedPreference = window.localStorage.getItem(storageKey);
            if (storedPreference === 'light' || storedPreference === 'dark' || storedPreference === 'system') {
                preference = storedPreference;
            }
        } catch (error) {
        }

        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var resolvedTheme = preference === 'system' ? (prefersDark ? 'dark' : 'light') : preference;
        var root = document.documentElement;

        root.dataset.themePreference = preference;
        root.dataset.theme = resolvedTheme;
        root.style.colorScheme = resolvedTheme;
    })();
    </script>
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
                    'editMode' => 'iframe',
                ],
            'helpLabel' => 'Aide',
            'helpItems' => [
                [
                    'key' => 'faq',
                    'label' => 'FAQ',
                    'description' => 'Accès aux questions les plus courantes, avec moteur de recherche pour trouver facilement la réponse à ses questions.',
                    'title' => 'FAQ OMO',
                    'html' => '<div class="common-help-list"><div class="common-help-card"><h4>FAQ</h4><p>Cette entrée pourra regrouper les réponses fréquentes sur la gouvernance, les cercles et les outils.</p></div></div>',
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

    loadContent('#menu_sidebar', 'api/getSidebar.php', 'sidebar', function () {
        handleRoute(); // 👉 tout passe par là
    });

});
</script>

</body>
</html>
