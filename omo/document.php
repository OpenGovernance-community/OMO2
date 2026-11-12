<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once __DIR__ . '/translations.php';

$sourceLang = [
    'title' => ['text' => 'Document', 'context' => 'Standalone document reader title.'],
    'unavailable' => ['text' => 'Ce document est introuvable ou vous ne disposez pas des droits nécessaires pour le consulter.', 'context' => 'Document reader access denied message.'],
    'login' => ['text' => 'Connectez-vous pour consulter ce document. Vos droits d’accès seront vérifiés après la connexion.', 'context' => 'Private document reader login introduction.'],
    'read_only' => ['text' => 'Consultation en lecture seule', 'context' => 'Document reader mode label.'],
    'omo' => ['text' => 'Voir dans OMO', 'context' => 'Open the document in the full OMO application.'],
    'download' => ['text' => 'Télécharger le fichier', 'context' => 'Download the document original file.'],
    'no_file' => ['text' => 'Aucun fichier n’est disponible pour ce document.', 'context' => 'Reader missing uploaded file message.'],
    'fullscreen' => ['text' => 'Plein écran', 'context' => 'Expand the read-only document within the browser window.'],
];
$lang = omoLoadTranslationBundle('omo_document_reader', $sourceLang);
$text = static fn(string $key): string => t($key, [], $lang, $sourceLang);
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

$document = \dbObject\Document::findByReaderCode(trim((string)($_GET['code'] ?? '')));
$userId = (int)commonGetCurrentUserId();
$loaded = $document instanceof \dbObject\Document
    && !$document->isFolder() && !$document->isNextcloudFolder() && !$document->isArchived()
    && (int)$document->get('IDorganization') > 0;

if ($loaded && !$document->canReadFromLink($userId) && $userId <= 0) {
    commonRenderMagicLoginPage([
        'title' => $text('title'), 'appName' => 'OMO', 'intro' => $text('login'),
        'returnTo' => $document->buildReaderUrl(), 'layout' => 'public',
        'organization' => commonResolveOrganizationContext((int)$document->get('IDorganization')),
    ]);
    exit;
}

$allowed = $loaded && $document->canReadFromLink($userId);
$title = $allowed ? (string)$document->get('title') : $text('title');
$description = $allowed ? trim((string)$document->get('description')) : '';
$content = '';
$fileUrl = '';
$collaboraUrl = '';
if (!$allowed) {
    http_response_code(404);
} elseif ($document->isUploadedFile()) {
    if ($document->hasStoredFile()) {
        $readerQuery = http_build_query(['reader' => 1, 'code' => (string)$document->get('readercode')]);
        $fileUrl = '/omo/api/documents/upload/download.php?' . $readerQuery;
        if ($document->canOpenWithCollabora()) {
            require_once dirname(__DIR__) . '/common/collabora.php';
            $organization = new \dbObject\Organization();
            if ($organization->load((int)$document->get('IDorganization'))
                && $organization->hasDocumentStorage() && omoCollaboraHasConfig($organization)) {
                $collaboraUrl = '/omo/api/documents/collabora/open.php?' . $readerQuery;
            }
        }
    } else {
        $content = '<p>' . $escape($text('no_file')) . '</p>';
    }
} else {
    $content = $document->getReaderContent();
}
$hasMediaPreview = $collaboraUrl !== '' || ($fileUrl !== '' && ($document->isStoredPdfFile() || $document->getStoredFileKind() === 'image'));
$hasTextPreview = $allowed && trim($content) !== '' && ($document->supportsHtmlContent() || $document->isPvDocument() || $document->isEtherpadDocument());
$hasVisualPreview = $hasMediaPreview || $hasTextPreview;
?>
<!DOCTYPE html>
<html lang="<?= $escape(omoGetTranslationLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape($title) ?></title>
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <?= commonStylesheetTags('/omo/assets/css/styles.css') ?>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/document_share.css') ?>">
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/document.css') ?>">
    <?php if ($allowed && $document->isPvDocument()): ?>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/document/pv-report.css') ?>">
    <?php endif; ?>
</head>
<body class="generic-public-page generic-public-page--scrollable">
    <main class="generic-page-shell generic-stack generic-stack--roomy">
        <header class="generic-public-brand">
            <img class="generic-public-brand__logo" src="/img/omo2/logo-omo-dark.png" alt="OMO" width="1076" height="332">
            <span class="generic-public-brand__label"><?= $escape($text('read_only')) ?></span>
        </header>
        <section class="generic-hero-panel generic-stack">
            <h1 class="generic-card-title generic-card-title--display"><?= $escape($title) ?></h1>
            <?php if (!$allowed): ?>
                <p><?= $escape($text('unavailable')) ?></p>
            <?php else: ?>
                <?php if ($description !== ''): ?><p><?= nl2br($escape($description)) ?></p><?php endif; ?>
                <div class="generic-form-actions">
                    <a class="generic-action-button generic-action-button--main" href="<?= $escape($document->buildOmoDocumentUrl()) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($text('omo')) ?></a>
                    <?php if ($fileUrl !== ''): ?>
                    <a class="generic-action-button generic-action-button--secondary" href="<?= $escape($fileUrl) ?>"><?= $escape($text('download')) ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php if ($allowed): ?>
        <section
            class="generic-section generic-section--stack generic-panel-fullscreen-content--<?= $hasMediaPreview ? 'media' : 'scrollable' ?>"
            data-common-panel-view
            data-common-panel-fullscreen-isolate="main"
            data-common-panel-fullscreen-mode="window"
        >
            <?php if ($hasVisualPreview): ?>
            <div class="generic-form-actions" data-common-panel-media-toolbar>
                <button type="button" class="generic-action-button generic-action-button--secondary" data-common-panel-fullscreen aria-pressed="false"><?= $escape($text('fullscreen')) ?></button>
            </div>
            <?php endif; ?>
            <div data-common-panel-media-content>
            <?php if ($fileUrl !== ''): ?>
                <?php if ($collaboraUrl !== ''): ?>
                <iframe class="omo-document-reader__pdf" src="<?= $escape($collaboraUrl) ?>" title="<?= $escape($title) ?>" referrerpolicy="same-origin" allow="fullscreen" allowfullscreen></iframe>
                <?php elseif ($document->isStoredPdfFile()): ?>
                <iframe class="omo-document-reader__pdf" src="<?= $escape($fileUrl . '&inline=1') ?>" title="<?= $escape($title) ?>"></iframe>
                <?php elseif ($document->getStoredFileKind() === 'image'): ?>
                <img class="omo-document-reader__image" src="<?= $escape($fileUrl . '&inline=1') ?>" alt="<?= $escape($title) ?>">
                <?php elseif ($document->getStoredFileKind() === 'audio'): ?>
                <audio controls preload="metadata" src="<?= $escape($fileUrl . '&inline=1') ?>"></audio>
                <?php endif; ?>
            <?php endif; ?>
                <?php if ($content !== ''): ?>
                <div class="omo-document-share__content prose"><?= $content ?></div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>
    <?php if ($hasVisualPreview): ?>
    <script src="<?= commonAssetUrl('/common/panel-view/actions.js') ?>"></script>
    <script>window.commonPanelViewActions.mount(document);</script>
    <?php endif; ?>
</body>
</html>
