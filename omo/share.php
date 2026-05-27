<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/topbar.php';

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

$organizationContext = array(
    'id' => (int)$organization->getId(),
    'name' => (string)$organization->get('name'),
    'shortname' => (string)$organization->get('shortname'),
    'domain' => (string)$organization->get('domain'),
    'logo' => (string)$organization->get('logo'),
    'banner' => (string)$organization->get('banner'),
    'color' => trim((string)$organization->get('color')),
    'host' => commonGetRequestHost(),
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars((string)$organization->get('name'), ENT_QUOTES, 'UTF-8') ?> - Partage OMO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <link rel="stylesheet" href="/omo/assets/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <base href="/omo/">
    <style>
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
        padding: 10px 16px;
        border-bottom: 1px solid var(--color-border, #e2e8f0);
        background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
        color: var(--color-text, #1f2937);
        font-size: 13px;
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
        border-radius: 18px;
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
    </style>
</head>
<body class="view-left omo-share-body">
<div class="app omo-share-page">
    <div class="main">
        <?php
        commonRenderTopbar(array(
            'appKey' => 'omo-share',
            'appLabel' => 'OMO',
            'organization' => $organizationContext,
            'brandLabel' => (string)$organization->get('name'),
            'profile' => array(
                'enabled' => false,
            ),
            'search' => array(
                'enabled' => false,
            ),
            'helpItems' => array(),
            'helpLabel' => 'Infos',
        ));
        ?>
        <div class="omo-share-banner">
            Lien partage public pour <?= htmlspecialchars($scopeHolon->getDisplayName(), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="content">
            <div class="panel panel-left" id="panel-left"></div>
            <div class="resizer" id="resizer"></div>
            <div class="panel panel-right" id="panel-right"></div>
        </div>
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
<script src="/omo/assets/js/app.js"></script>
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

        $('#panel-left').html(message);
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
