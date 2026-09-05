<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/pv_participation.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';

$participationSourceLang = [
    'documents.pv_participation.invalid_title' => ['text' => 'Lien de réunion invalide', 'context' => 'Title of the public PV participation page when the link is unavailable.'],
    'documents.pv_participation.invalid_heading' => ['text' => 'Lien invalide', 'context' => 'Heading of the public PV participation page when the link is unavailable.'],
    'documents.pv_participation.invalid_body' => ['text' => 'Ce lien de participation est invalide, expiré ou le PV a déjà été validé.', 'context' => 'Explanation shown when a public PV participation link is unavailable.'],
    'documents.pv_participation.default_title' => ['text' => 'Réunion', 'context' => 'Fallback title of a public PV participation page.'],
    'documents.pv_participation.close' => ['text' => 'Fermer', 'context' => 'Accessible label for closing the public PV discussion dialog.'],
    'documents.pv_participation.discussion' => ['text' => 'Discussion', 'context' => 'Fallback title of a public PV discussion dialog.'],
];
$participationLang = omoLoadTranslationBundle('omo_pv_participation', $participationSourceLang);
$participationT = static function (string $key) use ($participationLang, $participationSourceLang): string {
    return t($key, [], $participationLang, $participationSourceLang);
};

$token = trim((string)($_GET['token'] ?? ''));
$shareLink = \dbObject\DocumentShareLink::findValidByToken($token);
$document = $shareLink instanceof \dbObject\DocumentShareLink ? $shareLink->getDocument() : null;
$canParticipate = $shareLink instanceof \dbObject\DocumentShareLink
    && $shareLink->allowsPvContribution()
    && $document instanceof \dbObject\Document
    && $document->isPvDocument()
    && !$document->isPvValidated();

if (!$canParticipate) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($participationT('documents.pv_participation.invalid_title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout"><div class="auth-state-card"><h1><?= htmlspecialchars($participationT('documents.pv_participation.invalid_heading'), ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars($participationT('documents.pv_participation.invalid_body'), ENT_QUOTES, 'UTF-8') ?></p></div></main>
</body>
</html>
    <?php
    exit;
}

if ($shareLink->requiresPassword() && !commonIsSharePasswordVerified($token)) {
    header('Location: ' . $shareLink->buildShareUrl());
    exit;
}

$_GET['id'] = (int)$document->getId();
$_GET['oid'] = (int)$document->get('IDorganization');
$_GET['pv_token'] = $token;
$_REQUEST['id'] = $_GET['id'];
$_REQUEST['oid'] = $_GET['oid'];
$_REQUEST['pv_token'] = $token;

// The PV editor bootstrap sets cache headers and the public organization context.
// It must run before this standalone page starts rendering HTML.
require_once __DIR__ . '/api/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(trim((string)$document->get('title')) ?: $participationT('documents.pv_participation.default_title'), ENT_QUOTES, 'UTF-8') ?></title>
    <script src="/shared_functions.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/common/assets/topbar.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css?v=20260904-pv-participation">
    <style>
    html, body { height: 100%; margin: 0; }
    body { background: var(--color-bg, #f8fafc); color: var(--color-text, #1f2937); }
    .omo-pv-participation { height: 100%; min-height: 100%; }
    </style>
</head>
<body>
    <main class="omo-pv-participation">
        <?php require __DIR__ . '/api/documents/pv/editor.php'; ?>
    </main>
    <div class="common-topbar-modal" id="commonTopbarModal" hidden>
        <div class="common-topbar-modal__backdrop" data-topbar-modal-close></div>
        <div class="common-topbar-modal__panel" role="dialog" aria-modal="true" aria-labelledby="commonTopbarModalTitle">
            <div class="common-topbar-modal__header">
                <h3 id="commonTopbarModalTitle"></h3>
                <button type="button" class="common-topbar-modal__close" data-topbar-modal-close aria-label="<?= htmlspecialchars($participationT('documents.pv_participation.close'), ENT_QUOTES, 'UTF-8') ?>">&times;</button>
            </div>
            <div class="common-topbar-modal__body" id="commonTopbarModalBody"></div>
        </div>
    </div>
    <script>
    (function () {
        if (typeof window.commonTopbarOpenModal === 'function') return;
        var modal = document.getElementById('commonTopbarModal');
        var body = document.getElementById('commonTopbarModalBody');
        var title = document.getElementById('commonTopbarModalTitle');
        if (!modal || !body || !title) return;
        function closeModal() {
            if (modal.hidden) return;
            modal.hidden = true;
            body.replaceChildren();
            document.body.classList.remove('common-topbar-modal-open');
            window.dispatchEvent(new CustomEvent('common-topbar-modal-close'));
        }
        window.commonTopbarOpenModal = function (modalTitle, content) {
            title.textContent = String(modalTitle || <?= json_encode($participationT('documents.pv_participation.discussion'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
            body.innerHTML = String(content || '');
            modal.hidden = false;
            document.body.classList.add('common-topbar-modal-open');
            window.dispatchEvent(new CustomEvent('common-topbar-modal-open'));
        };
        window.commonTopbarCloseModal = closeModal;
        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-topbar-modal-close]')) closeModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeModal();
        });
    }());
    </script>
</body>
</html>
