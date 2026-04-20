<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/topbar.php';

$organizationContext = commonResolveOrganizationContext(1);
commonRestoreRememberedUser();
$isDemoGuest = commonCanAccessWithoutLogin($organizationContext);

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
        'title' => ($organizationContext['name'] ?: 'OMO') . ' - OMO',
        'appName' => 'OMO',
        'intro' => 'Connectez-vous pour accéder à la structure et aux outils de gouvernance.',
        'returnTo' => commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/omo/', '/omo/'),
        'headHtml' => $omoPwaHeadHtml,
        'bodyEndHtml' => $omoPwaBodyEndHtml,
    ]);
}

$currentUserName = $isDemoGuest ? 'Démo' : commonGetCurrentUserDisplayName();
$currentUserProfile = [
    'displayName' => $currentUserName,
    'email' => '',
    'username' => '',
    'phone' => '',
    'photoUrl' => '',
];

$currentUser = new \dbObject\User();
if ($currentUser->load(commonGetCurrentUserId())) {
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
