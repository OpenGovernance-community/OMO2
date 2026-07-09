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
    'documents.detail.alt_texts.title' => ['text' => 'Versions texte', 'context' => 'Section title listing alternate text versions.'],
    'documents.detail.alt_texts.fallback' => ['text' => 'Version texte', 'context' => 'Fallback title for an alternate text variant.'],
    'documents.detail.media.title' => ['text' => 'Médias associés', 'context' => 'Section title listing associated media.'],
    'documents.detail.media.open' => ['text' => 'Ouvrir le média', 'context' => 'Link label used to open a media item.'],
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

if (
    !$document->load($documentId)
    || !$document->canViewInOrganizationContext($organizationId, $holonId)
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
$renderedContent = $document->getRenderedContentForCurrentViewer();
$associatedEvent = $document->getAssociatedEvent();
$associatedEventSchedule = '';
$associatedEventLocation = '';
$associatedEventContext = '';

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
<div class="omo-document-detail">
    <article class="omo-document-detail__article">
        <header class="omo-document-detail__intro">
            <div class="omo-document-detail__meta">
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
            </div>

            <?php if ($description !== ''): ?>
                <div class="omo-document-detail__summary omo-card">
                    <?= nl2br($escape($description)) ?>
                </div>
            <?php endif; ?>

            <?php if ($keywords !== ''): ?>
                <div class="omo-document-detail__keywords">
                    <?php foreach (preg_split('/\s*,\s*/', $keywords) as $keyword): ?>
                        <?php if (trim((string)$keyword) !== ''): ?>
                            <span class="omo-pill"><?= $escape(trim((string)$keyword)) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
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

        <section class="omo-document-detail__section omo-card">
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
    </article>
</div>

<style>
.omo-document-detail {
    min-height: 100%;
    padding: 20px;
    background: var(--color-bg);
}

.omo-document-detail__article {
    display: flex;
    flex-direction: column;
    gap: 18px;
    max-width: 920px;
    margin: 0 auto;
}

.omo-document-detail__intro {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.omo-document-detail__meta,
.omo-document-detail__keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
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
    border-radius: 16px;
    background: #fff;
}

.omo-document-detail__content .omo-document-file {
    display: grid;
    gap: 10px;
    padding: 16px 18px;
    border-radius: 16px;
    border: 1px solid color-mix(in srgb, var(--color-border) 85%, #2563eb 15%);
    background: color-mix(in srgb, var(--color-surface) 92%, #eff6ff 8%);
}

.omo-document-detail__content .omo-document-file--empty {
    color: var(--color-text-light);
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

.omo-document-detail__content .omo-document-embed {
    display: grid;
    gap: 10px;
    margin: 0 0 1em;
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid color-mix(in srgb, var(--color-border) 85%, #2563eb 15%);
    background: color-mix(in srgb, var(--color-surface) 90%, #eff6ff 10%);
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

@media (max-width: 768px) {
    .omo-document-detail {
        padding: 14px;
    }

    .omo-document-detail__event-grid {
        grid-template-columns: 1fr;
    }
}
</style>
