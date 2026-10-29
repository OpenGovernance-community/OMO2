<?php
require_once __DIR__ . '/ui_translations.php';
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
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(omoInterfaceUiT('lien_de_partage_invalide'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1><?= htmlspecialchars(omoInterfaceUiT('lien_invalide'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(omoInterfaceUiT('ce_lien_de_partage_est_invalide_inactif_ou_expire'), ENT_QUOTES, 'UTF-8') ?></p>
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
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(omoInterfaceUiT('organisation_introuvable_2'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1><?= htmlspecialchars(omoInterfaceUiT('organisation_introuvable_2'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(omoInterfaceUiT('le_contexte_de_partage_ne_peut_pas_etre_resolu'), ENT_QUOTES, 'UTF-8') ?></p>
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
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(\dbObject\Organization::formatLexiconText('Holon introuvable', $organization->getLexicon()), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1><?= htmlspecialchars(\dbObject\Organization::formatLexiconText('Holon introuvable', $organization->getLexicon()), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(omoInterfaceUiT('le_contexte_partage_n_est_plus_disponible'), ENT_QUOTES, 'UTF-8') ?></p>
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

        $passwordError = omoInterfaceUiT('mot_de_passe_invalide');
    }

    ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(omoInterfaceUiT('mot_de_passe_requis'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1><?= htmlspecialchars(omoInterfaceUiT('acces_protege'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(omoInterfaceUiT('un_mot_de_passe_est_requis_pour_ouvrir_ce_lien_de_partage'), ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post" class="auth-state-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <label class="auth-state-label" for="omoSharePasswordInput"><?= htmlspecialchars(omoInterfaceUiT('mot_de_passe'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="auth-state-input" type="password" id="omoSharePasswordInput" name="share_password" autofocus>
                <?php if ($passwordError !== ''): ?>
                    <p class="auth-state-error"><?= htmlspecialchars($passwordError, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="auth-state-actions">
                    <button class="auth-state-btn auth-state-btn--primary" type="submit"><?= htmlspecialchars(omoInterfaceUiT('ouvrir_le_lien'), ENT_QUOTES, 'UTF-8') ?></button>
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
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars((string)$organization->get('name'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(omoInterfaceUiT('partage_omo'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <link rel="stylesheet" href="/omo/assets/css/public-pages.css">
    <?= commonStylesheetTags('/omo/assets/css/styles.css') ?>
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
            'helpLabel' => omoInterfaceUiT('aide'),
        ));
        ?>
        <div class="omo-public-banner omo-share-banner">
            <?= htmlspecialchars(omoInterfaceUiT('lien_partage_public_pour'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($scopeHolon->getDisplayName(), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="content">
            <div class="panel panel-left" id="panel-left">
                <div class="omo-left-panel-shell" id="omoLeftPanelShell">
                    <div class="omo-left-panel-shell__context" id="panel-left-context"></div>
                </div>
            </div>
            <div class="resizer" id="resizer"></div>
            <div class="panel panel-right" id="panel-right"></div>
        </div>
        <nav class="mobile-nav" id="omo-mobile-nav" aria-label="<?= htmlspecialchars(omoInterfaceUiT('navigation_du_partage'), ENT_QUOTES, 'UTF-8') ?>">
            <button type="button" data-view="left"><?= htmlspecialchars(omoInterfaceUiT('infos'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" data-view="right"><?= htmlspecialchars(omoInterfaceUiT('structure'), ENT_QUOTES, 'UTF-8') ?></button>
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
    'uiTexts' => omoUiJsPayload(),
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
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= commonAssetUrl('/omo/assets/js/ui-translations.js') ?>"></script>
<script src="<?= commonAssetUrl('/omo/assets/js/app.js') ?>"></script>
<script>
$(document).ready(function () {
    if (window.omoConfig && !window.omoConfig.shareAllowsStructure) {
        const message = [
            '<div class="omo-share-placeholder">',
            '<div class="omo-share-placeholder__card">',
            '<h2>' + <?= json_encode(htmlspecialchars(omoInterfaceUiT('structure_not_shared'), ENT_QUOTES, 'UTF-8'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '</h2>',
            '<p>' + <?= json_encode(htmlspecialchars(omoInterfaceUiT('structure_share_denied'), ENT_QUOTES, 'UTF-8'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '</p>',
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
