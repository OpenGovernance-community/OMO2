<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$sourceLang = [
    'documents.detail.error.invalid' => ['text' => 'Document invalide.', 'context' => 'Error shown when the document id is invalid.'],
    'documents.detail.error.not_found' => ['text' => 'Document introuvable ou inaccessible.', 'context' => 'Error shown when the document cannot be loaded or viewed.'],
    'documents.detail.meta.updated' => ['text' => 'Mise à jour : {date}', 'context' => 'Metadata pill showing the last update date of the document.'],
    'documents.detail.meta.author' => ['text' => 'Par {name}', 'context' => 'Metadata pill showing the author of the document.'],
    'documents.detail.meta.updated_by' => ['text' => 'Modifié par {name}', 'context' => 'Metadata pill showing who updated the document.'],
    'documents.detail.event.title' => ['text' => 'Rencontre associee', 'context' => 'Section title shown when a document is linked to an event.'],
    'documents.detail.event.schedule' => ['text' => 'Horaire', 'context' => 'Label for the event schedule in the document detail summary.'],
    'documents.detail.event.location' => ['text' => 'Lieu', 'context' => 'Label for the event location in the document detail summary.'],
    'documents.detail.event.context' => ['text' => 'Contexte', 'context' => 'Label for the event context in the document detail summary.'],
    'documents.detail.event.virtual_fallback' => ['text' => 'Visio', 'context' => 'Fallback location label when only a virtual meeting link exists.'],
    'documents.detail.action.more' => ['text' => 'Plus d actions', 'context' => 'Accessible label for the read-only document action menu.'],
    'documents.detail.action.export_pdf' => ['text' => 'Exporter en PDF', 'context' => 'Action used to download a PV document as a PDF file.'],
    'documents.detail.action.export_pdf_waiting' => ['text' => 'Veuillez patienter', 'context' => 'Temporary label shown while a PV PDF is being generated.'],
    'documents.detail.action.export_pdf_notice' => ['text' => 'Génération du PDF en préparation.', 'context' => 'Topbar notification shown when a PV PDF export starts.'],
    'documents.detail.action.export_pdf_error' => ['text' => 'Impossible de générer le PDF.', 'context' => 'Notification shown when a PV PDF export fails.'],
    'documents.detail.action.edit' => ['text' => 'Modifier', 'context' => 'Button opening the document editor from the document detail drawer.'],
    'documents.detail.action.fullscreen' => ['text' => 'Plein écran', 'context' => 'Button used to show a collaborative document iframe in fullscreen.'],
    'documents.detail.action.exit_fullscreen' => ['text' => 'Quitter le plein écran', 'context' => 'Button used to leave the collaborative document fullscreen mode.'],
    'documents.detail.alt_texts.title' => ['text' => 'Versions texte', 'context' => 'Section title listing alternate text versions.'],
    'documents.detail.alt_texts.fallback' => ['text' => 'Version texte', 'context' => 'Fallback title for an alternate text variant.'],
    'documents.detail.media.title' => ['text' => 'Médias associés', 'context' => 'Section title listing associated media.'],
    'documents.detail.media.open' => ['text' => 'Ouvrir le média', 'context' => 'Link label used to open a media item.'],
    'documents.detail.pv_discussion.title' => ['text' => 'Discussion de relecture', 'context' => 'Read-only title for the discussion attached to a validated PV point.'],
    'documents.detail.pv_discussion.link' => ['text' => 'Voir les corrections effectuées', 'context' => 'Subtle link opening a read-only validated PV point discussion.'],
    'documents.detail.pv_discussion.loading' => ['text' => 'Chargement de la discussion...', 'context' => 'Loading state in a validated PV point discussion.'],
    'documents.detail.pv_discussion.empty' => ['text' => 'Aucun message pour le moment.', 'context' => 'Empty state in a validated PV point discussion.'],
    'documents.detail.pv_discussion.readonly' => ['text' => 'Lecture seule', 'context' => 'Placeholder for the disabled composer in a validated PV discussion.'],
    'documents.detail.pv_discussion.send' => ['text' => 'Envoyer', 'context' => 'Send label shared by the chat popup.'],
    'documents.detail.pv_discussion.changes' => ['text' => 'Voir les modifications', 'context' => 'Label opening before and after changes in a PV discussion.'],
    'documents.detail.pv_discussion.content_excerpt' => ['text' => 'Contenu (extrait)', 'context' => 'Label for an excerpted PV point content change.'],
];

$lang = omoLoadTranslationBundle('omo_documents_detail', $sourceLang);

function omoDocumentsDetailT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;

$escape = 'omoApiEscape';

$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT)
    : null;

$formatDateTime = static function ($value) use ($formatter): string {
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if ($formatter instanceof IntlDateFormatter) {
        $formatted = $formatter->format($value);

        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y H:i');
};

if ($documentId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-document-detail omo-document-detail--error">
        <div class="omo-empty-state"><?= $escape(omoDocumentsDetailT('documents.detail.error.invalid')) ?></div>
    </div>
    <?php
    exit;
}

$document = new \dbObject\Document();
$currentUserId = (int)commonGetCurrentUserId();
$canAccessDocument = false;
if ($documentId > 0 && $organizationId > 0 && $document->load($documentId)) {
	$hasPvInvitationAccess = $document->isPvDocument()
		&& !$document->isPvValidated()
		&& $document->canUserAccessPvBeforeValidation($currentUserId, $organizationId);
	$canAccessDocument = $document->canUserPassPvMeetingVisibilityGate($currentUserId, $organizationId)
		&& (
			$hasPvInvitationAccess
			|| $document->canViewInOrganizationContext($organizationId, $holonId)
			|| $document->canViewDirectlyInOrganization($organizationId)
		);
}

if (
    !$canAccessDocument
) {
    http_response_code(404);
    ?>
    <div class="omo-document-detail omo-document-detail--error">
        <div class="omo-empty-state"><?= $escape(omoDocumentsDetailT('documents.detail.error.not_found')) ?></div>
    </div>
    <?php
    exit;
}

$_SESSION['doc_' . $document->getId()] = true;

$altTexts = $document->getAltText();
$medias = $document->getMedias();
$description = trim((string)$document->get('description'));
$keywords = trim((string)$document->get('keywords'));
$createdAt = $document->get('datecreation');
$updatedAt = $document->get('datemodification');
$author = $document->getCreatedByDisplayName();
$updatedBy = $document->getUpdatedByDisplayName();
$visibility = $document->getVisibilityDisplayData($organizationId);
$includePvDiscussionLinks = $document->isPvDocument()
    && (
            $document->getPvStage() === \dbObject\Document::PV_STAGE_VALIDATED
        || (
            $document->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW
            && $document->canUserAccessPvReview($currentUserId, $organizationId)
        )
    );
$renderedContent = $document->getRenderedContentForCurrentViewer([
    'includePvDiscussionLinks' => $includePvDiscussionLinks,
    'pvDiscussionContextHolonId' => $holonId,
    'pvDiscussionLabels' => [
        'title' => omoDocumentsDetailT('documents.detail.pv_discussion.title'),
        'link' => omoDocumentsDetailT('documents.detail.pv_discussion.link'),
        'loading' => omoDocumentsDetailT('documents.detail.pv_discussion.loading'),
        'empty' => omoDocumentsDetailT('documents.detail.pv_discussion.empty'),
        'placeholder' => omoDocumentsDetailT('documents.detail.pv_discussion.readonly'),
        'send' => omoDocumentsDetailT('documents.detail.pv_discussion.send'),
        'changeDetails' => omoDocumentsDetailT('documents.detail.pv_discussion.changes'),
        'contentExcerpt' => omoDocumentsDetailT('documents.detail.pv_discussion.content_excerpt'),
    ],
]);
$hasCollaborativeFrame = $document->isEtherpadDocument() || $document->isEthercalcDocument() || $document->isCollaboraDocument();
$drawerTitle = trim((string)$document->get('title'));
$drawerDescription = $createdAt instanceof DateTimeInterface ? $formatDateTime($createdAt) : '';
$associatedEvent = $document->getAssociatedEvent();
$associatedEventSchedule = '';
$associatedEventLocation = '';
$associatedEventContext = '';
$pdfExportUrl = $document->isPvDocument()
    ? '/omo/api/documents/pv/export_pdf.php?id=' . rawurlencode((string)(int)$document->getId())
        . '&oid=' . rawurlencode((string)$organizationId)
    : '';
$showPvDiscussion = $includePvDiscussionLinks;
$canManageDocument = !$document->isPvDocument()
    && $document->canManageInOrganizationContext($organizationId, $currentUserId, false);
$canEditDocumentContent = !$document->isPvDocument()
    && $document->canEditInOrganizationContext($organizationId, $currentUserId, false);
$canEditDocument = !$document->isPvDocument()
    && ($canManageDocument || (!$document->isEtherpadDocument() && !$document->isEthercalcDocument() && !$document->isCollaboraDocument() && $canEditDocumentContent));
$editUrl = $canEditDocument
    ? '/omo/api/documents/create.php?oid=' . rawurlencode((string)$organizationId)
        . ($holonId > 0 ? '&cid=' . rawurlencode((string)$holonId) : '')
        . '&id=' . rawurlencode((string)(int)$document->getId())
    : '';

if ($associatedEvent instanceof \dbObject\Event) {
    $startAt = $associatedEvent->get('start_at');
    $endAt = $associatedEvent->get('end_at');
    if ($startAt instanceof DateTimeInterface && $endAt instanceof DateTimeInterface) {
        $associatedEventSchedule = $formatDateTime($startAt) . ' - ' . $formatDateTime($endAt);
    }

    $locationData = $associatedEvent->getLocationDisplayData();
    $locationParts = array();
    if (trim((string)($locationData['address'] ?? '')) !== '') {
        $locationParts[] = trim((string)$locationData['address']);
    }
    if (trim((string)($locationData['videoUrl'] ?? '')) !== '') {
        $locationParts[] = trim((string)$locationData['videoUrl']);
    } elseif (count($locationParts) === 0 && trim((string)($locationData['mode'] ?? '')) === \dbObject\Event::LOCATION_MODE_VIRTUAL) {
        $locationParts[] = omoDocumentsDetailT('documents.detail.event.virtual_fallback');
    }
    $associatedEventLocation = implode(' | ', $locationParts);

    $associatedEventHolonId = (int)$associatedEvent->get('IDholon');
    if ($associatedEventHolonId > 0) {
        $eventHolon = new \dbObject\Holon();
        if ($eventHolon->load($associatedEventHolonId)) {
            $associatedEventContext = trim((string)$eventHolon->getLabel());
        }
    }
}
?>
<?php if ($showPvDiscussion): ?>
<link rel="stylesheet" href="/common/chat/thread.css?v=20260821-pv-review-access-2">
<link rel="stylesheet" href="/common/choice/change-details.css?v=20260821-pv-review-access-2">
<?php endif; ?>
<div
    class="omo-document-detail"
    data-omo-document-drawer-title="<?= $escape($drawerTitle) ?>"
    data-omo-document-drawer-description="<?= $escape($drawerDescription) ?>"
>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= $escape($drawerTitle) ?>"
        data-omo-subdrawer-description="<?= $escape($drawerDescription) ?>"
    >
        <?php if ($editUrl !== ''): ?>
            <button
                type="button"
                class="generic-action-button generic-action-button--main"
                data-omo-subdrawer-action
                data-omo-document-open-editor-url="<?= $escape($editUrl) ?>"
            ><?= $escape(omoDocumentsDetailT('documents.detail.action.edit')) ?></button>
        <?php endif; ?>
    </div>

    <article class="omo-document-detail__article">
        <header class="omo-document-detail__intro">
            <?php if ($pdfExportUrl !== ''): ?>
                <div class="omo-document-detail__actions">
                    <details class="omo-document-detail__more-actions">
                        <summary
                            aria-label="<?= $escape(omoDocumentsDetailT('documents.detail.action.more')) ?>"
                            title="<?= $escape(omoDocumentsDetailT('documents.detail.action.more')) ?>"
                        >...</summary>
                        <div class="omo-document-detail__more-actions-menu">
                            <a
                                class="generic-action-button omo-document-detail__pdf-export"
                                href="<?= $escape($pdfExportUrl) ?>"
                                download
                                data-omo-pv-pdf-export
                                data-omo-pv-pdf-label="<?= $escape(omoDocumentsDetailT('documents.detail.action.export_pdf')) ?>"
                                data-omo-pv-pdf-waiting-label="<?= $escape(omoDocumentsDetailT('documents.detail.action.export_pdf_waiting')) ?>"
                                data-omo-pv-pdf-notice="<?= $escape(omoDocumentsDetailT('documents.detail.action.export_pdf_notice')) ?>"
                                data-omo-pv-pdf-error="<?= $escape(omoDocumentsDetailT('documents.detail.action.export_pdf_error')) ?>"
                            >
                                <?= $escape(omoDocumentsDetailT('documents.detail.action.export_pdf')) ?>
                            </a>
                        </div>
                    </details>
                </div>
            <?php endif; ?>
            <?php if ($description !== ''): ?>
                <div class="omo-document-detail__summary omo-card">
                    <?= nl2br($escape($description)) ?>
                </div>
            <?php endif; ?>

            <?php if ($keywords !== '' || $hasCollaborativeFrame): ?>
                <div class="omo-document-detail__keyword-actions">
                    <div class="omo-document-detail__keywords">
                        <?php if ($keywords !== ''): ?>
                    <?php foreach (preg_split('/\s*,\s*/', $keywords) as $keyword): ?>
                        <?php if (trim((string)$keyword) !== ''): ?>
                            <span class="omo-pill"><?= $escape(trim((string)$keyword)) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($hasCollaborativeFrame): ?>
                        <button
                            type="button"
                            class="generic-action-button generic-action-button--secondary omo-document-detail__fullscreen-button"
                            data-omo-document-fullscreen
                            data-omo-document-fullscreen-label="<?= $escape(omoDocumentsDetailT('documents.detail.action.fullscreen')) ?>"
                            data-omo-document-exit-fullscreen-label="<?= $escape(omoDocumentsDetailT('documents.detail.action.exit_fullscreen')) ?>"
                            aria-pressed="false"
                        ><?= $escape(omoDocumentsDetailT('documents.detail.action.fullscreen')) ?></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($associatedEvent instanceof \dbObject\Event): ?>
                <section class="omo-document-detail__event omo-card">
                    <h3 class="omo-document-detail__section-title"><?= $escape(omoDocumentsDetailT('documents.detail.event.title')) ?></h3>
                    <div class="omo-document-detail__event-title">
                        <?= $escape(trim((string)$associatedEvent->get('title')) !== '' ? trim((string)$associatedEvent->get('title')) : ('Evenement #' . (int)$associatedEvent->getId())) ?>
                    </div>
                    <div class="omo-document-detail__event-grid">
                        <?php if ($associatedEventSchedule !== ''): ?>
                            <div class="omo-document-detail__event-item">
                                <span class="omo-document-detail__event-label"><?= $escape(omoDocumentsDetailT('documents.detail.event.schedule')) ?></span>
                                <strong><?= $escape($associatedEventSchedule) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ($associatedEventLocation !== ''): ?>
                            <div class="omo-document-detail__event-item">
                                <span class="omo-document-detail__event-label"><?= $escape(omoDocumentsDetailT('documents.detail.event.location')) ?></span>
                                <strong><?= $escape($associatedEventLocation) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ($associatedEventContext !== ''): ?>
                            <div class="omo-document-detail__event-item">
                                <span class="omo-document-detail__event-label"><?= $escape(omoDocumentsDetailT('documents.detail.event.context')) ?></span>
                                <strong><?= $escape($associatedEventContext) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </header>

        <section class="omo-document-detail__section">
            <div class="omo-document-detail__content prose">
                <?= $renderedContent ?>
            </div>
        </section>

        <?php if (count($altTexts) > 0): ?>
            <section class="omo-document-detail__section">
                <h3 class="omo-document-detail__section-title"><?= $escape(omoDocumentsDetailT('documents.detail.alt_texts.title')) ?></h3>
                <div class="omo-document-detail__stack">
                    <?php foreach ($altTexts as $altText): ?>
                        <?php
                        $prompt = $altText->get('aiprompt');
                        $promptTitle = is_object($prompt) && method_exists($prompt, 'get')
                            ? trim((string)$prompt->get('title'))
                            : '';
                        ?>
                        <article class="omo-document-detail__variant omo-card">
                            <div class="omo-document-detail__variant-head">
                                <strong><?= $escape($promptTitle !== '' ? $promptTitle : omoDocumentsDetailT('documents.detail.alt_texts.fallback')) ?></strong>
                            </div>
                            <div class="omo-document-detail__variant-body">
                                <?= nl2br($escape((string)$altText->get('text'))) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (count($medias) > 0): ?>
            <section class="omo-document-detail__section">
                <h3 class="omo-document-detail__section-title"><?= $escape(omoDocumentsDetailT('documents.detail.media.title')) ?></h3>
                <div class="omo-document-detail__stack">
                    <?php foreach ($medias as $media): ?>
                        <article class="omo-document-detail__media omo-card">
                            <div class="omo-document-detail__media-head">
                                <strong><?= $escape((string)$media->get('title')) ?></strong>
                                <?php if (trim((string)$media->get('filename')) !== ''): ?>
                                    <span><?= $escape((string)$media->get('filename')) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ((int)$media->get('IDtype') === 1): ?>
                                <audio controls preload="none" class="omo-document-detail__audio">
                                    <source src="/shared/getfile.php?id=<?= $escape($media->getId()) ?>" type="<?= $escape((string)$media->get('contenttype')) ?>">
                                </audio>
                            <?php elseif ((int)$media->get('IDtype') === 2): ?>
                                <a href="/shared/getfile.php?id=<?= $escape($media->getId()) ?>" target="_blank" rel="noopener" class="omo-document-detail__image-link">
                                    <img
                                        src="/shared/getfile.php?id=<?= $escape($media->getId()) ?>"
                                        alt="<?= $escape((string)$media->get('title')) ?>"
                                        class="omo-document-detail__image"
                                        loading="lazy"
                                    >
                                </a>
                            <?php else: ?>
                                <a href="/shared/getfile.php?id=<?= $escape($media->getId()) ?>" target="_blank" rel="noopener" class="omo-document-detail__download">
                                    <?= $escape(omoDocumentsDetailT('documents.detail.media.open')) ?>
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <footer class="omo-document-detail__meta">
            <?php if ($createdAt instanceof DateTimeInterface): ?>
                <span class="omo-pill"><?= $escape($formatDateTime($createdAt)) ?></span>
            <?php endif; ?>

            <?php if ($updatedAt instanceof DateTimeInterface && (!$createdAt instanceof DateTimeInterface || $updatedAt != $createdAt)): ?>
                <span class="omo-pill"><?= $escape(omoDocumentsDetailT('documents.detail.meta.updated', ['date' => $formatDateTime($updatedAt)])) ?></span>
            <?php endif; ?>

            <?php if ($author !== ''): ?>
                <span class="omo-pill"><?= $escape(omoDocumentsDetailT('documents.detail.meta.author', ['name' => $author])) ?></span>
            <?php endif; ?>

            <?php if ($updatedBy !== '' && $updatedBy !== $author): ?>
                <span class="omo-pill"><?= $escape(omoDocumentsDetailT('documents.detail.meta.updated_by', ['name' => $updatedBy])) ?></span>
            <?php endif; ?>

            <?php if (trim((string)($visibility['badgeText'] ?? '')) !== ''): ?>
                <span class="omo-pill"><?= $escape((string)$visibility['badgeText']) ?></span>
            <?php endif; ?>
        </footer>
    </article>
</div>

<style>
.omo-document-detail {
    --omo-document-detail-article-max-width: 920px;
    --omo-document-detail-article-margin-inline: auto;
    min-height: 100%;
    padding: var(--generic-layout-gutter, 20px);
    background: var(--color-bg);
}

.omo-overlay-drawer__body .omo-document-detail {
    --omo-document-detail-article-max-width: none;
    --omo-document-detail-article-margin-inline: 0;
}

.omo-document-detail__article {
    display: flex;
    flex-direction: column;
    gap: 18px;
    max-width: var(--omo-document-detail-article-max-width);
    margin: 0;
    margin-left: var(--omo-document-detail-article-margin-inline);
    margin-right: var(--omo-document-detail-article-margin-inline);
}

.omo-document-detail__intro {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.omo-document-detail__actions {
    display: flex;
    justify-content: flex-end;
}

.omo-document-detail__more-actions {
    position: relative;
}

.omo-document-detail__more-actions > summary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 30px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    color: var(--color-text);
    cursor: pointer;
    font-weight: 800;
    list-style: none;
}

.omo-document-detail__more-actions > summary::-webkit-details-marker {
    display: none;
}

.omo-document-detail__more-actions-menu {
    position: absolute;
    z-index: 12;
    top: calc(100% + 5px);
    right: 0;
    width: max-content;
    min-width: 190px;
    padding: 6px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
}

.omo-document-detail__more-actions-menu .generic-action-button {
    width: 100%;
    justify-content: flex-start;
}

.omo-document-detail__meta,
.omo-document-detail__keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-document-detail__keyword-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.omo-document-detail__keywords {
    gap: 4px 6px;
    min-width: 0;
}

.omo-document-detail__keywords .omo-pill {
    min-height: 18px;
    padding: 2px 7px;
    font-size: 0.68rem;
    line-height: 1.15;
}

.omo-document-detail__fullscreen-button {
    flex: 0 0 auto;
    min-height: 30px;
    padding: 5px 10px;
    font-size: 0.76rem;
}

.omo-document-detail__meta {
    margin-top: 8px;
    padding-top: 14px;
    border-top: 1px solid var(--color-border);
    color: var(--color-text-light);
    font-size: 0.76rem;
}

.omo-document-detail__meta .omo-pill {
    min-height: 20px;
    padding: 3px 8px;
    font-size: inherit;
}

.omo-document-detail__summary {
    color: var(--color-text-light);
    line-height: 1.6;
}

.omo-document-detail__event {
    display: grid;
    gap: 12px;
}

.omo-document-detail__event-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--color-text);
}

.omo-document-detail__event-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.omo-document-detail__event-item {
    display: grid;
    gap: 6px;
}

.omo-document-detail__event-label {
    color: var(--color-text-light);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.omo-document-detail__section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.omo-document-detail__section-title {
    margin: 0;
    font-size: 1rem;
}

.omo-document-detail__content {
    line-height: 1.7;
    word-break: break-word;
}

.omo-document-detail__content > :first-child {
    margin-top: 0;
}

.omo-document-detail__content > :last-child {
    margin-bottom: 0;
}

.omo-document-detail__content .omo-document-external {
    display: grid;
    gap: 14px;
}

.omo-document-detail__content .omo-document-external--iframe {
    gap: 12px;
}

.omo-document-detail__content .omo-document-external__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.omo-document-detail__content .omo-document-external__hint,
.omo-document-detail__content .omo-document-external__fallback {
    color: var(--color-text-light);
    line-height: 1.6;
}

.omo-document-detail__content .omo-document-external__frame {
    width: 100%;
    min-height: 72vh;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: #fff;
}

.omo-document-detail__content .omo-document-file {
    display: grid;
    gap: 10px;
    padding: 16px 18px;
    border-radius: var(--radius-md);
    border: 1px solid color-mix(in srgb, var(--color-border) 85%, #2563eb 15%);
    background: color-mix(in srgb, var(--color-surface) 92%, #eff6ff 8%);
}

.omo-document-detail__content .omo-document-file--empty {
    color: #b91c1c;
    border-color: rgba(220, 38, 38, 0.42);
    background: #fef2f2;
}

.omo-document-detail__content .omo-document-file__title {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--color-text);
    word-break: break-word;
}

.omo-document-detail__content .omo-document-file__meta {
    color: var(--color-text-light);
    font-size: 0.9rem;
}

.omo-document-detail__content .omo-document-file__download {
    justify-self: flex-start;
}

.omo-document-detail__content .omo-document-etherpad,
.omo-document-detail__content .omo-document-ethercalc,
.omo-document-detail__content .omo-document-collabora {
    width: 100%;
    min-height: 70vh;
}

.omo-document-detail__content .omo-document-etherpad__frame,
.omo-document-detail__content .omo-document-ethercalc__frame,
.omo-document-detail__content .omo-document-collabora__frame {
    display: block;
    width: 100%;
    min-height: 70vh;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
}

.omo-document-detail__content .omo-document-etherpad__frame:fullscreen,
.omo-document-detail__content .omo-document-ethercalc__frame:fullscreen,
.omo-document-detail__content .omo-document-collabora__frame:fullscreen {
    width: 100vw;
    height: 100vh;
    min-height: 100vh;
    border: 0;
    border-radius: 0;
}

.omo-document-detail__content .omo-document-embed {
    display: grid;
    gap: 10px;
    margin: 0 0 1em;
    padding: 14px 16px;
    border-radius: var(--radius-md);
    border: 1px solid color-mix(in srgb, var(--color-border) 85%, #2563eb 15%);
    background: color-mix(in srgb, var(--color-surface) 90%, #eff6ff 10%);
}

.omo-document-detail__content .omo-document-embed--resolved {
    display: block;
    margin: 0;
    padding: 0;
    border: 0;
    border-radius: var(--radius-md);
    background: transparent;
    transition: background-color 140ms ease;
}

.omo-document-detail__content .omo-document-embed--resolved:hover:not(:has(.omo-document-embed--resolved:hover)) {
    background: color-mix(in srgb, var(--color-surface) 94%, var(--color-text) 6%);
}

.omo-document-detail__content .omo-document-embed:last-child {
    margin-bottom: 0;
}

.omo-document-detail__content .omo-document-embed__label {
    color: var(--color-text-light);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.omo-document-detail__content .omo-document-embed__title {
    font-weight: 700;
    color: var(--color-text);
}

.omo-document-detail__content .omo-document-embed__description,
.omo-document-detail__content .omo-document-embed__message {
    color: var(--color-text-light);
    line-height: 1.6;
}

.omo-document-detail__content .omo-document-embed__body {
    display: grid;
    gap: 0.9em;
    padding-top: 2px;
}

.omo-document-detail__content .omo-document-embed__body > :first-child {
    margin-top: 0;
}

.omo-document-detail__content .omo-document-embed__body > :last-child {
    margin-bottom: 0;
}

.omo-document-detail__stack {
    display: grid;
    gap: 12px;
}

.omo-document-detail__variant,
.omo-document-detail__media {
    display: grid;
    gap: 10px;
}

.omo-document-detail__variant-head,
.omo-document-detail__media-head {
    display: grid;
    gap: 4px;
}

.omo-document-detail__variant-head span,
.omo-document-detail__media-head span {
    color: var(--color-text-light);
    font-size: 0.92rem;
}

.omo-document-detail__variant-body {
    color: var(--color-text-light);
    line-height: 1.6;
    white-space: normal;
}

.omo-document-detail__audio {
    width: 100%;
}

.omo-document-detail__image-link {
    display: inline-flex;
    align-self: flex-start;
    max-width: 100%;
}

.omo-document-detail__image {
    display: block;
    max-width: 100%;
    max-height: 420px;
    border-radius: var(--radius-md);
    border: 1px solid var(--color-border);
    object-fit: contain;
    background: var(--color-surface-alt);
}

.omo-document-detail__download {
    color: var(--color-primary);
    text-decoration: none;
}

.omo-document-detail__download:hover {
    text-decoration: underline;
}

.omo-document-detail__pdf-export[aria-disabled="true"] {
    cursor: wait;
    opacity: 0.58;
    pointer-events: none;
}

@media (max-width: 768px) {
    .omo-document-detail {
        padding: 14px;
    }

    .omo-document-detail__event-grid {
        grid-template-columns: 1fr;
    }

    .omo-document-detail__keyword-actions {
        align-items: flex-start;
    }
}
</style>
<?php if ($pdfExportUrl !== ''): ?>
<script>
(function () {
    'use strict';

    function extractFilename(response) {
        var disposition = response.headers.get('Content-Disposition') || '';
        var encodedMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        var plainMatch = disposition.match(/filename="([^"]+)"/i);
        if (encodedMatch && encodedMatch[1]) {
            try {
                return decodeURIComponent(encodedMatch[1]);
            } catch (error) {
                return encodedMatch[1];
            }
        }
        return plainMatch && plainMatch[1] ? plainMatch[1] : 'export.pdf';
    }

    document.querySelectorAll('[data-omo-pv-pdf-export]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (link.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            var originalLabel = link.getAttribute('data-omo-pv-pdf-label') || link.textContent;
            var waitingLabel = link.getAttribute('data-omo-pv-pdf-waiting-label') || 'Veuillez patienter';
            var notice = link.getAttribute('data-omo-pv-pdf-notice') || '';
            var errorLabel = link.getAttribute('data-omo-pv-pdf-error') || 'Impossible de generer le PDF.';
            link.setAttribute('aria-disabled', 'true');
            link.setAttribute('aria-busy', 'true');
            link.textContent = waitingLabel;
            if (typeof window.commonNotify === 'function' && notice !== '') {
                window.commonNotify(notice, 'warning', {duration: 7000});
            }

            window.fetch(link.href, {credentials: 'same-origin', headers: {Accept: 'application/pdf'}})
                .then(function (response) {
                    if (!response.ok) {
                        return response.text().then(function (message) {
                            throw new Error(message || errorLabel);
                        });
                    }
                    var contentType = (response.headers.get('Content-Type') || '').toLowerCase();
                    return response.blob().then(function (blob) {
                        if (contentType.indexOf('text/plain') !== -1) {
                            return blob.text().then(function (message) {
                                throw new Error(message || errorLabel);
                            });
                        }
                        return {blob: blob, filename: extractFilename(response)};
                    });
                })
                .then(function (result) {
                    var blobUrl = window.URL.createObjectURL(result.blob);
                    var downloadLink = document.createElement('a');
                    downloadLink.href = blobUrl;
                    downloadLink.download = result.filename;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();
                    var exportMenu = link.closest('.omo-document-detail__more-actions');
                    if (exportMenu) exportMenu.removeAttribute('open');
                    window.setTimeout(function () { window.URL.revokeObjectURL(blobUrl); }, 1000);
                })
                .catch(function (error) {
                    if (typeof window.commonNotify === 'function') {
                        window.commonNotify(error.message || errorLabel, 'error');
                    }
                })
                .finally(function () {
                    link.removeAttribute('aria-disabled');
                    link.removeAttribute('aria-busy');
                    link.textContent = originalLabel;
                });
        });
    });
}());
</script>
<?php endif; ?>
<?php if ($showPvDiscussion): ?>
<script src="/common/choice/word-diff.js?v=20260821-pv-review-access-2"></script>
<script src="/common/choice/change-details.js?v=20260821-pv-review-access-2"></script>
<script src="/common/chat/thread.js?v=20260821-pv-review-access-2"></script>
<?php endif; ?>
