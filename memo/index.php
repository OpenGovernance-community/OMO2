<?php
require_once __DIR__ . '/api/bootstrap.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/topbar.php';

commonRestoreRememberedUser();

$currentUserId = (int)commonGetCurrentUserId();
$requestUri = commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/memo/', '/memo/');

if ($currentUserId <= 0) {
    commonRenderMagicLoginPage(array(
        'title' => 'Connexion EasyMEMO',
        'appName' => 'EasyMEMO',
        'intro' => 'Retrouvez tous vos documents personnels dans un espace unique.',
        'returnTo' => $requestUri,
        'organization' => commonResolveOrganizationContext(1),
        'headHtml' => implode(PHP_EOL, array(
            '<script src="/shared_functions.js"></script>',
            '<link rel="stylesheet" href="/shared_css.css">',
            '<script>sharedApplyDocumentTheme();</script>',
        )),
    ));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyMEMO</title>
    <script src="/shared_functions.js"></script>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css">
    <link rel="stylesheet" href="/omo/api/stats/stats.css">
    <script>sharedApplyDocumentTheme();</script>
    <style>
        html,
        body.memo-page {
            height: 100%;
            margin: 0;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--color-bg) 92%, #dbeafe 8%), var(--color-bg));
            color: var(--color-text);
        }

        body.memo-page {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .memo-page__main {
            flex: 1 1 auto;
            min-height: calc(100dvh - var(--topbar-height, 48px));
            padding: 24px;
            overflow: visible;
        }

        .memo-page__content {
            max-width: 1240px;
            margin: 0 auto;
            min-height: 100%;
        }

        @media (max-width: 768px) {
            .memo-page__main {
                padding: 14px;
            }
        }
    </style>
</head>
<body class="memo-page">
<?php
commonRenderTopbar(array(
    'appKey' => 'memo',
    'appLabel' => 'EasyMEMO',
    'brandHref' => '/memo/',
    'brandLabel' => 'EasyMEMO',
    'logoutReturnTo' => '/memo/',
    'search' => array(
        'enabled' => false,
    ),
    'helpItems' => array(),
));
?>
<main class="memo-page__main">
    <div class="memo-page__content">
        <?php require __DIR__ . '/api/documents/index.php'; ?>
    </div>
</main>
</body>
</html>
