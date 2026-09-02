<?php
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/api/bootstrap.php';
require_once __DIR__ . '/api/documents/detail_renderer.php';

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$accessCode = trim((string)($_GET['access_code'] ?? ($_GET['pwd'] ?? '')));
$currentUserId = (int)commonGetCurrentUserId();
$requestUri = commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/memo/', '/memo/');

$document = new \dbObject\Document();
$hasAccess = $documentId > 0
    && $document->load($documentId)
    && $document->canViewInMemoContext($currentUserId, $accessCode);

if (!$hasAccess && $currentUserId <= 0) {
    commonRenderMagicLoginPage(array(
        'title' => 'Connexion EasyMEMO',
        'appName' => 'EasyMEMO',
        'intro' => 'Connectez-vous pour retrouver vos documents ou ouvrir ce memo.',
        'returnTo' => $requestUri,
        'organization' => commonResolveOrganizationContext(1),
        'headHtml' => implode(PHP_EOL, array(
            '<script src="/shared_functions.js"></script>',
            '<link rel="stylesheet" href="/shared_css.css">',
            '<script>sharedApplyDocumentTheme();</script>',
        )),
    ));
}

if (!$hasAccess) {
    http_response_code(404);
}

if ($hasAccess) {
    $_SESSION['doc_' . $document->getId()] = true;
    $document->markConsulted();
}
?>
<!DOCTYPE html>
<html lang="fr" class="memo-view-page-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $hasAccess ? memoApiEscape((string)$document->get('title')) : 'EasyMEMO' ?></title>
    <script src="/shared_functions.js"></script>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css">
    <link rel="stylesheet" href="/omo/api/stats/stats.css?v=20260807-range-handles">
    <script>sharedApplyDocumentTheme();</script>
    <style>
        html.memo-view-page-html,
        body.memo-view-page {
            height: auto;
            min-height: 100%;
            max-height: none;
            overflow-x: hidden;
            overflow-y: auto;
        }

        body.memo-view-page {
            margin: 0;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 30%),
                var(--color-bg);
            color: var(--color-text);
        }

        .memo-view-page__main {
            max-width: 1120px;
            margin: 0 auto;
            padding: 28px 16px 40px;
        }

        .memo-view-page__title {
            max-width: 920px;
            margin: 0 auto 18px;
            color: var(--color-text);
            font-size: clamp(1.55rem, 3vw, 2.2rem);
            line-height: 1.2;
        }

        .memo-view-page__error {
            max-width: 760px;
            margin: 64px auto 0;
            padding: 32px;
        }
    </style>
</head>
<body class="memo-view-page">
    <main class="memo-view-page__main">
        <?php if ($hasAccess): ?>
            <h1 class="memo-view-page__title"><?= memoApiEscape((string)$document->get('title')) ?></h1>
            <?php memoRenderDocumentDetail($document); ?>
        <?php else: ?>
            <div class="memo-view-page__error omo-card">
                <h1>Document introuvable</h1>
                <p>Ce document n'est pas accessible avec ce lien.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
