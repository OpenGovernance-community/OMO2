<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\DocumentShareLink;

$token = trim((string)($_GET['token'] ?? ''));
$shareLink = DocumentShareLink::findValidByToken($token);

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

$document = $shareLink->getDocument();
if (!($document instanceof \dbObject\Document) || $document->isFolder()) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(omoInterfaceUiT('document_introuvable'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1><?= htmlspecialchars(omoInterfaceUiT('document_introuvable'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(omoInterfaceUiT('le_document_partage_n_est_plus_disponible'), ENT_QUOTES, 'UTF-8') ?></p>
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
            header('Location: ' . $shareLink->buildShareUrl());
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
            <p><?= htmlspecialchars(omoInterfaceUiT('un_mot_de_passe_est_requis_pour_ouvrir_ce_document_partage'), ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post" class="auth-state-form">
                <label class="auth-state-label" for="omoDocumentSharePasswordInput"><?= htmlspecialchars(omoInterfaceUiT('mot_de_passe'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="auth-state-input" type="password" id="omoDocumentSharePasswordInput" name="share_password" autofocus>
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

if ($shareLink->allowsPvContribution()) {
    header('Location: ' . $shareLink->buildPvParticipationUrl());
    exit;
}

$livePayload = $document->buildLiveSharePayload($shareLink->allowsLiveFollow());
$updatedAtToken = trim((string)($livePayload['updatedAt'] ?? ''));
$updatedAt = $updatedAtToken !== '' ? date_create_immutable($updatedAtToken) : null;
$documentTitle = trim((string)($livePayload['title'] ?? ''));
$documentDescription = trim((string)($livePayload['description'] ?? ''));
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($documentTitle !== '' ? $documentTitle : 'Document partage', ENT_QUOTES, 'UTF-8') ?></title>
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <?= commonStylesheetTags('/omo/assets/css/styles.css') ?>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/document_share.css') ?>">
</head>
<body>
    <main class="omo-document-share">
        <section class="omo-document-share__hero generic-hero-panel">
            <h1 class="generic-card-title generic-card-title--large" id="omoDocumentShareTitle"><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <p id="omoDocumentShareDescription"<?= $documentDescription !== '' ? '' : ' hidden' ?>><?= htmlspecialchars($documentDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="omo-document-share__meta">
                <?php if ($shareLink->allowsLiveFollow()): ?>
                    <span class="omo-pill"><?= htmlspecialchars(omoInterfaceUiT('suivi_temps_reel'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($updatedAt instanceof DateTimeInterface): ?>
                    <span class="omo-pill" id="omoDocumentShareUpdatedAt"><?= htmlspecialchars(omoInterfaceUiT('mise_a_jour'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($updatedAt->format('d.m.Y H:i'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="omo-pill" id="omoDocumentShareDraftBadge"<?= !empty($livePayload['isDraft']) ? '' : ' hidden' ?>><?= htmlspecialchars(omoInterfaceUiT('brouillon_en_cours'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="omo-document-share__status" id="omoDocumentShareStatus">
                <?php if (!empty($livePayload['isDraft']) && trim((string)($livePayload['editingUserName'] ?? '')) !== ''): ?>
                    <?= htmlspecialchars(omoInterfaceUiT('editing_by_name', ['name' => (string)$livePayload['editingUserName']]), ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                    <?= htmlspecialchars(omoInterfaceUiT('shared_reading'), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="omo-document-share__body generic-section generic-section--stack">
            <div class="omo-document-share__content prose" id="omoDocumentShareContent"><?= (string)($livePayload['content'] ?? '') ?></div>
        </section>
    </main>

    <script>window.omoUiTranslations = <?= json_encode(omoUiJsPayload(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="<?= commonAssetUrl('/omo/assets/js/ui-translations.js') ?>"></script>
    <?php if ($shareLink->allowsLiveFollow()): ?>
    <?= commonPageScriptTags('/omo/document_share.js', [
    'token' => $token,
    'knownUpdatedAt' => $livePayload['updatedAt'] ?? null,
    'knownContentHash' => $livePayload['contentHash'] ?? '',
    'knownStateHash' => $livePayload['stateHash'] ?? '',
]) ?>
    <?php endif; ?>
</body>
</html>
