<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/pv_participation.php';
require_once __DIR__ . '/translations.php';

$sourceLang = [
    'title' => ['text' => 'Document de la reunion', 'context' => 'Public meeting document reader title.'],
    'unavailable' => ['text' => 'Ce document est introuvable ou son acces est indisponible avec ce lien de reunion.', 'context' => 'Public meeting document access denied message.'],
    'read_only' => ['text' => 'Lecture seule via votre lien de participation.', 'context' => 'Public meeting document reader access explanation.'],
    'download' => ['text' => 'Telecharger le fichier', 'context' => 'Meeting attachment download action.'],
    'preview' => ['text' => 'Ouvrir le PDF', 'context' => 'Meeting attachment PDF preview action.'],
];
$lang = omoLoadTranslationBundle('omo_pv_document', $sourceLang);
$text = static fn(string $key): string => t($key, [], $lang, $sourceLang);
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
$document = commonResolvePvEmbeddedDocument();
$title = $document ? (string)$document->get('title') : $text('title');
$content = '';
$fileUrl = '';
if (!$document) {
    http_response_code(404);
} elseif ($document->isUploadedFile() && !$document->hasStoredFile()) {
    $content = '<p>' . $escape($text('unavailable')) . '</p>';
} elseif ($document->isUploadedFile()) {
    $fileUrl = $document->buildStoredFileDownloadUrl() . '&' . http_build_query([
        'pv_document_id' => (int)$_GET['pv_document_id'], 'pv_token' => (string)$_GET['pv_token'],
    ]);
} elseif ($document->supportsHtmlContent()) {
    $link = \dbObject\DocumentShareLink::findValidByToken((string)$_GET['pv_token']);
    $content = $document->renderResolvedHtmlForViewer((string)$document->get('content'), (int)$document->get('IDorganization'), [
        'compactEmbeds' => true, 'pvParticipationLink' => $link,
    ]);
} else {
    $content = (string)$document->buildLiveSharePayload()['content'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape($title) ?></title>
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <?= commonStylesheetTags('/omo/assets/css/styles.css') ?>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/document_share.css') ?>">
</head>
<body>
    <main class="omo-document-share">
        <section class="omo-document-share__hero generic-hero-panel">
            <h1 class="generic-card-title generic-card-title--large"><?= $escape($title) ?></h1>
            <p><?= $escape($document ? $text('read_only') : $text('unavailable')) ?></p>
            <?php if ($document && trim((string)$document->get('description')) !== ''): ?>
                <p><?= $escape($document->get('description')) ?></p>
            <?php endif; ?>
        </section>
        <?php if ($document): ?>
        <section class="omo-document-share__body generic-section generic-section--stack">
            <?php if ($fileUrl !== ''): ?>
                <a class="generic-action-button" href="<?= $escape($fileUrl) ?>"><?= $escape($text('download')) ?></a>
                <?php if ($document->isStoredPdfFile()): ?>
                    <a class="generic-action-button generic-action-button--secondary" href="<?= $escape($fileUrl . '&inline=1') ?>" target="_blank" rel="noopener noreferrer"><?= $escape($text('preview')) ?></a>
                <?php endif; ?>
            <?php endif; ?>
            <div class="omo-document-share__content prose"><?= $content ?></div>
        </section>
        <?php endif; ?>
    </main>
    <script>
    document.querySelectorAll('a[href^="#documents-d"]').forEach(function (link) {
        var match = /^#documents-d(\d+)$/.exec(link.getAttribute('href'));
        if (!match) return;
        var url = new URL(window.location.href);
        url.searchParams.set('id', match[1]);
        url.hash = '';
        link.href = url.href;
    });
    </script>
</body>
</html>
