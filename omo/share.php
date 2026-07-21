<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/omo_public_pages.php';
require_once dirname(__DIR__) . '/common/topbar.php';
require_once __DIR__ . '/translations.php';

$token = commonGetCurrentShareToken();
$shareLink = commonGetCurrentShareLink(false);

if (!$shareLink) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien de partage invalide</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1>Lien invalide</h1>
            <p>Ce lien de partage est invalide, inactif ou expire.</p>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load((int)$shareLink->get('IDorganization'))) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organisation introuvable</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1>Organisation introuvable</h1>
            <p>Le contexte de partage ne peut pas etre resolu.</p>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

$organizationRootHolon = $organization->getStructuralRootHolon();
$organizationRootHolonId = $organizationRootHolon ? (int)$organizationRootHolon->getId() : 0;
$isStructureApplicationEnabled = $organization->isStructureApplicationEnabled();

$scopeHolon = $shareLink->getScopeHolon();
if (!$scopeHolon) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holon introuvable</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1>Holon introuvable</h1>
            <p>Le contexte partage n est plus disponible.</p>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

$passwordError = '';
if ($shareLink->requiresPassword() && !commonIsSharePasswordVerified($token)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = (string)($_POST['share_password'] ?? '');
        if ($shareLink->verifyPassword($password)) {
            commonRememberSharePasswordVerified($token);
            header('Location: ' . $shareLink->buildShareUrl(isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : null));
            exit;
        }

        $passwordError = 'Mot de passe invalide.';
    }

    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe requis</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1>Acces protege</h1>
            <p>Un mot de passe est requis pour ouvrir ce lien de partage.</p>
            <form method="post" class="auth-state-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <label class="auth-state-label" for="omoSharePasswordInput">Mot de passe</label>
                <input class="auth-state-input" type="password" id="omoSharePasswordInput" name="share_password" autofocus>
                <?php if ($passwordError !== ''): ?>
                    <p class="auth-state-error"><?= htmlspecialchars($passwordError, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="auth-state-actions">
                    <button class="auth-state-btn auth-state-btn--primary" type="submit">Ouvrir le lien</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

$_SESSION['currentOrganization'] = (int)$organization->getId();

$requestedCid = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$initialCid = (int)$scopeHolon->getId();
if ($requestedCid > 0) {
    $candidate = new \dbObject\Holon();
    if ($candidate->load($requestedCid) && $shareLink->containsHolon($candidate)) {
        $initialCid = $requestedCid;
    }
}

$organizationContext = commonBuildOmoPublicOrganizationContext($organization);
$accentColor = trim((string)$organization->get('color'));
$helpItems = commonBuildOmoPublicHelpItems('share', (string)$organization->get('name'));
$brandHref = $shareLink->buildShareUrl($initialCid);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars((string)$organization->get('name'), ENT_QUOTES, 'UTF-8') ?> - Partage OMO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <link rel="stylesheet" href="/common/assets/omo_public_pages.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css?v=20260718-drawer-header-gap">
    <base href="/omo/">
    <style>
    :root {
        --omo-public-accent: <?= htmlspecialchars($accentColor !== '' ? $accentColor : '#2563eb', ENT_QUOTES, 'UTF-8') ?>;
    }

    body.omo-share-body {
        overflow: hidden;
    }

    .omo-share-page {
        width: 100%;
    }

    .omo-share-page .main {
        width: 100%;
    }

    .omo-share-banner {
        margin: 0;
        border: 0;
        border-radius: 0;
    }

    .omo-share-placeholder {
        height: 100%;
        display: grid;
        place-items: center;
        padding: 24px;
        text-align: center;
        color: var(--color-text-light, #6b7280);
    }

    .omo-share-placeholder__card {
        max-width: 520px;
        padding: 24px;
        border: 1px solid var(--color-border, #e5e7eb);
        border-radius: var(--radius-md);
        background: var(--color-surface, #ffffff);
        box-shadow: var(--shadow-md, 0 12px 24px rgba(0,0,0,0.12));
    }

    .omo-share-placeholder__card h2 {
        margin: 0 0 10px;
        color: var(--color-text, #1f2937);
    }

    .omo-share-placeholder__card p {
        margin: 0;
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .omo-share-page .main {
            height: 100dvh;
            max-height: 100dvh;
        }
    }
    </style>
</head>
<body class="view-left omo-share-body omo-public-body">
<div class="app omo-share-page">
    <div class="main">
        <?php
        commonRenderTopbar(array(
            'appKey' => 'omo-share',
            'appLabel' => 'OMO',
            'organization' => $organizationContext,
            'brandHref' => $brandHref,
            'brandLabel' => (string)$organization->get('name'),
            'profile' => array(
                'enabled' => false,
            ),
            'search' => array(
                'enabled' => false,
            ),
            'helpItems' => $helpItems,
            'helpLabel' => 'Aide',
        ));
        ?>
        <div class="omo-public-banner omo-share-banner">
            Lien partage public pour <?= htmlspecialchars($scopeHolon->getDisplayName(), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="content">
            <div class="panel panel-left" id="panel-left">
                <div class="omo-left-panel-shell" id="omoLeftPanelShell">
                    <div class="omo-left-panel-shell__context" id="panel-left-context"></div>
                    <div
                        class="omo-left-panel-shell__resizer"
                        id="panel-left-structure-resizer"
                        role="separator"
                        aria-orientation="horizontal"
                        aria-label="Redimensionner la mini structure"
                    ></div>
                    <div class="omo-left-panel-shell__structure" id="panel-left-structure">
                        <div class="omo-left-panel-shell__structure-host" id="omo-left-structure-map"></div>
                    </div>
                </div>
            </div>
            <div class="resizer" id="resizer"></div>
            <div class="panel panel-right" id="panel-right"></div>
        </div>
        <nav class="mobile-nav" id="omo-mobile-nav" aria-label="Navigation du partage">
            <button type="button" data-view="left">Infos</button>
            <button type="button" data-view="right">Structure</button>
        </nav>
    </div>
</div>

<script>
window.omoConfig = <?= json_encode(array(
    'mode' => 'share',
    'shareToken' => (string)$token,
    'oid' => (int)$organization->getId(),
    'shortname' => (string)$organization->get('shortname'),
    'name' => (string)$organization->get('name'),
    'host' => commonGetRequestHost(),
    'routeMode' => 'share',
    'translationLocale' => omoGetTranslationLocale(),
    'rootHolonId' => $organizationRootHolonId,
    'structureEnabled' => $isStructureApplicationEnabled,
    'orgLookupError' => null,
    'isDemo' => false,
    'currentUserName' => 'Invite',
    'userProfile' => array(
        'displayName' => '',
        'email' => '',
        'username' => '',
        'phone' => '',
        'photoUrl' => '',
    ),
    'initialCid' => $initialCid,
    'shareAllowsStructure' => $shareLink->allowsStructure(),
    'shareAllowsPeople' => $shareLink->allowsPeople(),
    'shareAllowsPeopleDetail' => $shareLink->allowsPeopleDetail(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="/omo/assets/js/app.js?v=20260720-projects-drawer-refresh"></script>
<script src="/omo/assets/js/structure-mini-map.js"></script>
<script>
$(document).ready(function () {
    if (window.omoConfig && !window.omoConfig.shareAllowsStructure) {
        const message = [
            '<div class="omo-share-placeholder">',
            '<div class="omo-share-placeholder__card">',
            '<h2>Structure non partagee</h2>',
            '<p>Ce lien n autorise pas l affichage de la structure.</p>',
            '</div>',
            '</div>'
        ].join('');

        if (typeof omoSetLeftPanelHtml === 'function') {
            omoSetLeftPanelHtml(message);
        } else {
            $('#panel-left').html(message);
        }
        $('#panel-right').html(message);

        if (window.omoConfig.shareAllowsPeople && typeof openDrawer === 'function') {
            const drawerUrl = 'api/team/index.php?oid=' + encodeURIComponent(String(window.omoConfig.oid || 0)) + '&cid=' + encodeURIComponent(String(window.omoConfig.initialCid || 0));
            openDrawer('drawer_team', drawerUrl);
        }

        return;
    }

    handleRoute();
});
</script>
</body>
</html>
