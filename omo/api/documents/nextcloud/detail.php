<?php
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 4) . '/common/collabora.php';

$sourceLang = array(
    'documents.nextcloud.detail.invalid_path' => array('text' => 'Chemin du dossier distant invalide.', 'context' => 'Error shown when a remote file path is invalid.'),
    'documents.nextcloud.detail.rights' => array('text' => 'Les droits du dossier distant s appliquent a ce fichier.', 'context' => 'Hint shown for an editable remote file.'),
    'documents.nextcloud.detail.download' => array('text' => 'Telecharger', 'context' => 'Download action for a remote file.'),
    'documents.nextcloud.detail.fullscreen' => array('text' => 'Plein ecran', 'context' => 'Fullscreen action for a remote preview.'),
    'documents.nextcloud.detail.exit_fullscreen' => array('text' => 'Quitter le plein ecran', 'context' => 'Label shown after entering fullscreen.'),
    'documents.nextcloud.detail.download_only' => array('text' => 'Ce fichier peut etre telecharge. Son format nest pas previsualisable.', 'context' => 'Fallback for unsupported remote file previews.'),
);
$lang = omoLoadTranslationBundle('omo_documents_nextcloud_detail', $sourceLang);
$t = static function (string $key) use ($lang, $sourceLang): string { return t($key, array(), $lang, $sourceLang); };

$context = omoDocumentsNextcloudLoadFolder((int)($_GET['id'] ?? 0), (int)($_GET['oid'] ?? $_SESSION['currentOrganization'] ?? 0));
$escape = 'omoApiEscape';
if (empty($context['status'])) {
    http_response_code((int)($context['httpCode'] ?? 404));
    ?><div class="omo-document-detail omo-document-detail--error"><div class="omo-empty-state"><?= $escape((string)($context['text'] ?? 'Acces refuse.')) ?></div></div><?php
    exit;
}

$remotePath = omoDocumentsNextcloudGetRemotePath($context['folder'], $context['organization'], $_GET['path'] ?? '');
if ($remotePath === '') {
    http_response_code(400);
    ?><div class="omo-document-detail omo-document-detail--error"><div class="omo-empty-state"><?= $escape($t('documents.nextcloud.detail.invalid_path')) ?></div></div><?php
    exit;
}

$filename = basename($remotePath);
$mimeType = omoDocumentsNextcloudFileMimeType($filename, (string)($_GET['mime'] ?? ''));
$isPdf = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf' || in_array(strtolower($mimeType), array('application/pdf', 'application/x-pdf'), true);
$canUseCollabora = !$isPdf && omoCollaboraHasConfig($context['organization']) && omoCollaboraSupportsFilename($filename);
$canEdit = $context['folder']->canEditInOrganizationContext($context['organizationId'], $context['userId'], false);
$storageLabel = $context['organization']->isKdriveDocumentStorage() ? 'kDrive' : 'NextCloud';
$baseQuery = 'id=' . rawurlencode((string)(int)$context['folder']->getId()) . '&oid=' . rawurlencode((string)$context['organizationId']) . '&path=' . rawurlencode($remotePath);
$downloadUrl = '/omo/api/documents/nextcloud/file.php?' . $baseQuery;
$inlineUrl = $downloadUrl . '&inline=1';
$collaboraUrl = $canUseCollabora
    ? '/omo/api/documents/collabora/open.php?folder_id=' . rawurlencode((string)(int)$context['folder']->getId()) . '&path=' . rawurlencode($remotePath)
    : '';
$collaboraOrigin = $canUseCollabora ? omoCollaboraBuildPostMessageOrigin((string)(omoCollaboraGetConfig($context['organization'])['baseUrl'] ?? '')) : '';
?>
<div class="omo-document-detail" data-omo-document-drawer-title="<?= $escape($filename) ?>" data-omo-collabora-origin="<?= $escape($collaboraOrigin) ?>">
    <div hidden data-omo-subdrawer-header data-omo-subdrawer-title="<?= $escape($filename) ?>">
        <?php if ($canEdit): ?><span class="generic-help-text"><?= $escape($t('documents.nextcloud.detail.rights')) ?></span><?php endif; ?>
    </div>
    <article class="omo-document-detail__article generic-stack generic-stack--roomy">
        <div class="omo-document-detail__keyword-actions">
            <div class="omo-document-detail__keywords"><span class="omo-pill"><?= $escape($storageLabel) ?></span></div>
            <div class="omo-document-detail__preview-actions">
                <a class="generic-action-button generic-action-button--secondary" href="<?= $escape($downloadUrl) ?>" download="<?= $escape($filename) ?>"><?= $escape($t('documents.nextcloud.detail.download')) ?></a>
                <?php if ($isPdf || $collaboraUrl !== ''): ?><button type="button" class="generic-action-button generic-action-button--secondary omo-document-detail__fullscreen-button" data-omo-document-fullscreen data-omo-document-fullscreen-label="<?= $escape($t('documents.nextcloud.detail.fullscreen')) ?>" data-omo-document-exit-fullscreen-label="<?= $escape($t('documents.nextcloud.detail.exit_fullscreen')) ?>"><?= $escape($t('documents.nextcloud.detail.fullscreen')) ?></button><?php endif; ?>
            </div>
        </div>
        <?php if ($isPdf): ?>
            <iframe class="omo-document-file__pdf-frame" src="<?= $escape($inlineUrl) ?>" title="<?= $escape($filename) ?>"></iframe>
        <?php elseif ($collaboraUrl !== ''): ?>
            <iframe class="omo-document-collabora__frame" src="<?= $escape($collaboraUrl) ?>" title="<?= $escape($filename) ?>" data-omo-collabora-document-id="<?= (int)$context['folder']->getId() ?>" data-omo-collabora-remote-path="<?= $escape($remotePath) ?>"></iframe>
        <?php else: ?>
            <div class="omo-empty-state"><?= $escape($t('documents.nextcloud.detail.download_only')) ?></div>
        <?php endif; ?>
    </article>
</div>
