<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once __DIR__ . '/translations.php';
require_once dirname(__DIR__) . '/common/topbar.php';
require_once dirname(__DIR__) . '/common/omo_public_pages.php';

$sourceLang = [
    'title' => ['text' => 'Document', 'context' => 'Standalone document reader title.'],
    'unavailable' => ['text' => 'Ce document est introuvable ou vous ne disposez pas des droits nécessaires pour le consulter.', 'context' => 'Document reader access denied message.'],
    'login' => ['text' => 'Connectez-vous pour consulter ce document. Vos droits d’accès seront vérifiés après la connexion.', 'context' => 'Private document reader login introduction.'],
    'omo' => ['text' => 'Voir dans OMO', 'context' => 'Open the document in the full OMO application.'],
    'download' => ['text' => 'Télécharger le fichier', 'context' => 'Download the document original file.'],
    'no_file' => ['text' => 'Aucun fichier n’est disponible pour ce document.', 'context' => 'Reader missing uploaded file message.'],
    'info' => ['text' => 'Info', 'context' => 'Document information and actions menu.'],
    'description' => ['text' => 'Description', 'context' => 'Document description in the information menu.'],
    'created' => ['text' => 'Création', 'context' => 'Document creation date.'],
    'modified' => ['text' => 'Modification', 'context' => 'Document modification date.'],
    'author' => ['text' => 'Créé par', 'context' => 'Document author.'],
    'updated_by' => ['text' => 'Modifié par', 'context' => 'Last document editor.'],
    'context' => ['text' => 'Contexte', 'context' => 'Organization, space and folder containing the document.'],
    'filename' => ['text' => 'Fichier', 'context' => 'Original filename.'],
    'format' => ['text' => 'Type de fichier', 'context' => 'Document MIME type.'],
    'download_only' => ['text' => 'Ce fichier peut être téléchargé depuis le menu Info.', 'context' => 'File without an inline preview.'],
];
$lang = omoLoadTranslationBundle('omo_document_reader', $sourceLang);
$text = static fn(string $key): string => t($key, [], $lang, $sourceLang);
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

$document = \dbObject\Document::findByReaderCode(trim((string)($_GET['code'] ?? '')), (int)($_GET['id'] ?? 0));
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
$organization = new \dbObject\Organization();
$organizationLoaded = $allowed && $organization->load((int)$document->get('IDorganization'));
if (!$allowed) {
    http_response_code(404);
} elseif ($document->isUploadedFile()) {
    if ($document->hasStoredFile()) {
        $readerQuery = http_build_query(['reader' => 1, 'id' => (int)$document->getId(), 'code' => $document->getReaderAccessCode()]);
        $fileUrl = '/omo/api/documents/upload/download.php?' . $readerQuery;
        if ($document->canOpenWithCollabora()) {
            require_once dirname(__DIR__) . '/common/collabora.php';
            if ($organizationLoaded && $organization->hasDocumentStorage() && omoCollaboraHasConfig($organization)) {
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
$metadata = [];
if ($allowed) {
    $createdAt = $document->get('datecreation');
    $updatedAt = $document->get('datemodification');
    $metadata = [
        'description' => $description,
        'created' => $createdAt instanceof DateTimeInterface ? $createdAt->format('d.m.Y H:i') : '',
        'author' => $document->getCreatedByDisplayName(),
        'modified' => $updatedAt instanceof DateTimeInterface ? $updatedAt->format('d.m.Y H:i') : '',
        'updated_by' => $document->getUpdatedByDisplayName(),
        'context' => $document->getOrganizationContextLabel(),
        'filename' => $document->isUploadedFile() ? $document->getStoredFileDownloadName() : '',
        'format' => $document->isUploadedFile() ? $document->getStoredFileMimeType() : '',
    ];
}
ob_start();
if ($allowed): ?>
<div class="generic-stack">
    <?php foreach ($metadata as $key => $value): ?>
        <?php if (trim((string)$value) !== ''): ?>
        <div class="generic-form-field">
            <span class="generic-form-label"><?= $escape($text($key)) ?></span>
            <span><?= nl2br($escape($value)) ?></span>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="generic-form-actions generic-form-actions--stack-mobile">
        <?php if ($fileUrl !== ''): ?>
        <a class="generic-action-button generic-action-button--secondary" href="<?= $escape($fileUrl) ?>"><?= $escape($text('download')) ?></a>
        <?php endif; ?>
        <a class="generic-action-button generic-action-button--main" href="<?= $escape($document->buildOmoDocumentUrl()) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($text('omo')) ?></a>
    </div>
</div>
<?php endif;
$infoHtml = (string)ob_get_clean();
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
<body class="omo-document-reader">
    <?php
    $organizationContext = commonBuildOmoPublicOrganizationContext($organizationLoaded ? $organization : null);
    commonRenderTopbar([
        'appKey' => 'omo-document-reader',
        'appLabel' => 'OMO',
        'organization' => $organizationContext,
        'brandLogo' => $organizationContext['logo'] ?: '/img/omo2/logo-omo-dark.png',
        'brandLabel' => $title,
        'brandHref' => $allowed ? $document->buildReaderUrl() : '/omo/',
        'showBrandLabelOnMobile' => true,
        'profile' => ['enabled' => false],
        'search' => ['enabled' => false],
        'helpEnabled' => false,
        'contextMenu' => ['label' => $text('info'), 'contentHtml' => $infoHtml],
    ]);
    ?>
    <main id="omoDocumentReaderPreview" class="omo-document-reader__viewport<?= $hasMediaPreview ? ' omo-document-reader__viewport--media' : '' ?>" aria-label="<?= $escape($title) ?>">
        <?php if (!$allowed): ?>
            <div class="generic-page-shell"><p><?= $escape($text('unavailable')) ?></p></div>
        <?php else: ?>
            <?php if ($fileUrl !== ''): ?>
                <?php if ($collaboraUrl !== ''): ?>
                <iframe class="omo-document-reader__frame" src="<?= $escape($collaboraUrl) ?>" title="<?= $escape($title) ?>" referrerpolicy="same-origin"></iframe>
                <?php elseif ($document->isStoredPdfFile()): ?>
                <iframe class="omo-document-reader__frame" src="<?= $escape($fileUrl . '&inline=1') ?>" title="<?= $escape($title) ?>"></iframe>
                <?php elseif ($document->getStoredFileKind() === 'image'): ?>
                <img class="omo-document-reader__image" src="<?= $escape($fileUrl . '&inline=1') ?>" alt="<?= $escape($title) ?>">
                <?php elseif ($document->getStoredFileKind() === 'audio'): ?>
                <audio controls preload="metadata" src="<?= $escape($fileUrl . '&inline=1') ?>"></audio>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($content !== ''): ?>
            <div class="generic-page-shell omo-document-share__content prose"><?= $content ?></div>
            <?php elseif (!$hasMediaPreview && $fileUrl !== '' && $document->getStoredFileKind() !== 'audio'): ?>
            <div class="generic-page-shell"><p><?= $escape($text('download_only')) ?></p></div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
