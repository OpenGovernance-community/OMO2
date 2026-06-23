<?php

if (!function_exists('memoRenderDocumentDetail')) {
    function memoRenderDocumentDetail(\dbObject\Document $document, array $options = array())
    {
        $escape = 'memoApiEscape';
        $contextLabel = trim((string)($options['contextLabel'] ?? $document->getOrganizationContextLabel()));
        $altTexts = $document->getAltText();
        $medias = $document->getMedias();
        $description = trim((string)$document->get('description'));
        $keywords = trim((string)$document->get('keywords'));
        $createdAt = $document->get('datecreation');
        $updatedAt = $document->get('datemodification');
        $author = $document->getCreatedByDisplayName();
        $updatedBy = $document->getUpdatedByDisplayName();
        $renderedContent = $document->getRenderedContentForCurrentViewer();

        $formatter = class_exists('IntlDateFormatter')
            ? new \IntlDateFormatter('fr_FR', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT)
            : null;

        $formatDateTime = static function ($value) use ($formatter) {
            if (!$value instanceof \DateTimeInterface) {
                return '';
            }

            if ($formatter instanceof \IntlDateFormatter) {
                $formatted = $formatter->format($value);
                if (is_string($formatted) && $formatted !== '') {
                    return $formatted;
                }
            }

            return $value->format('d.m.Y H:i');
        };
        ?>
<div class="memo-document-detail">
    <article class="memo-document-detail__article">
        <header class="memo-document-detail__intro">
            <div class="memo-document-detail__meta">
                <?php if ($createdAt instanceof \DateTimeInterface): ?>
                    <span class="omo-pill"><?= $escape($formatDateTime($createdAt)) ?></span>
                <?php endif; ?>

                <?php if ($updatedAt instanceof \DateTimeInterface && (!$createdAt instanceof \DateTimeInterface || $updatedAt != $createdAt)): ?>
                    <span class="omo-pill">Mise à jour : <?= $escape($formatDateTime($updatedAt)) ?></span>
                <?php endif; ?>

                <?php if ($author !== ''): ?>
                    <span class="omo-pill">Par <?= $escape($author) ?></span>
                <?php endif; ?>

                <?php if ($updatedBy !== '' && $updatedBy !== $author): ?>
                    <span class="omo-pill">Modifie par <?= $escape($updatedBy) ?></span>
                <?php endif; ?>

                <?php if ($contextLabel !== ''): ?>
                    <span class="omo-pill"><?= $escape($contextLabel) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($description !== ''): ?>
                <div class="memo-document-detail__summary omo-card">
                    <?= nl2br($escape($description)) ?>
                </div>
            <?php endif; ?>

            <?php if ($keywords !== ''): ?>
                <div class="memo-document-detail__keywords">
                    <?php foreach (preg_split('/\s*,\s*/', $keywords) as $keyword): ?>
                        <?php if (trim((string)$keyword) !== ''): ?>
                            <span class="omo-pill"><?= $escape(trim((string)$keyword)) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>

        <section class="memo-document-detail__section omo-card">
            <div class="memo-document-detail__content prose">
                <?= $renderedContent ?>
            </div>
        </section>

        <?php if (count($altTexts) > 0): ?>
            <section class="memo-document-detail__section">
                <h3 class="memo-document-detail__section-title">Versions texte</h3>
                <div class="memo-document-detail__stack">
                    <?php foreach ($altTexts as $altText): ?>
                        <?php
                        $prompt = $altText->get('aiprompt');
                        $promptTitle = is_object($prompt) && method_exists($prompt, 'get')
                            ? trim((string)$prompt->get('title'))
                            : '';
                        ?>
                        <article class="memo-document-detail__variant omo-card">
                            <div class="memo-document-detail__variant-head">
                                <strong><?= $escape($promptTitle !== '' ? $promptTitle : 'Version texte') ?></strong>
                            </div>
                            <div class="memo-document-detail__variant-body">
                                <?= nl2br($escape((string)$altText->get('text'))) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (count($medias) > 0): ?>
            <section class="memo-document-detail__section">
                <h3 class="memo-document-detail__section-title">Médias associés</h3>
                <div class="memo-document-detail__stack">
                    <?php foreach ($medias as $media): ?>
                        <article class="memo-document-detail__media omo-card">
                            <div class="memo-document-detail__media-head">
                                <strong><?= $escape((string)$media->get('title')) ?></strong>
                                <?php if (trim((string)$media->get('filename')) !== ''): ?>
                                    <span><?= $escape((string)$media->get('filename')) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ((int)$media->get('IDtype') === 1): ?>
                                <audio controls preload="none" class="memo-document-detail__audio">
                                    <source src="/shared/getfile.php?id=<?= $escape($media->getId()) ?>" type="<?= $escape((string)$media->get('contenttype')) ?>">
                                </audio>
                            <?php elseif ((int)$media->get('IDtype') === 2): ?>
                                <a href="/shared/getfile.php?id=<?= $escape($media->getId()) ?>" target="_blank" rel="noopener" class="memo-document-detail__image-link">
                                    <img
                                        src="/shared/getfile.php?id=<?= $escape($media->getId()) ?>"
                                        alt="<?= $escape((string)$media->get('title')) ?>"
                                        class="memo-document-detail__image"
                                        loading="lazy"
                                    >
                                </a>
                            <?php else: ?>
                                <a href="/shared/getfile.php?id=<?= $escape($media->getId()) ?>" target="_blank" rel="noopener" class="memo-document-detail__download">
                                    Ouvrir le média
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
.memo-document-detail {
    min-height: 100%;
    padding: 20px;
    background: var(--color-bg);
}

.memo-document-detail__article {
    display: flex;
    flex-direction: column;
    gap: 18px;
    max-width: 920px;
    margin: 0 auto;
}

.memo-document-detail__intro,
.memo-document-detail__section,
.memo-document-detail__stack,
.memo-document-detail__variant,
.memo-document-detail__media,
.memo-document-detail__variant-head,
.memo-document-detail__media-head {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.memo-document-detail__meta,
.memo-document-detail__keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.memo-document-detail__summary,
.memo-document-detail__variant-body {
    color: var(--color-text-light);
    line-height: 1.6;
}

.memo-document-detail__section-title {
    margin: 0;
    font-size: 1rem;
}

.memo-document-detail__content {
    line-height: 1.7;
    word-break: break-word;
}

.memo-document-detail__content > :first-child,
.memo-document-detail__content .omo-document-embed__body > :first-child {
    margin-top: 0;
}

.memo-document-detail__content > :last-child,
.memo-document-detail__content .omo-document-embed__body > :last-child {
    margin-bottom: 0;
}

.memo-document-detail__content .omo-document-external {
    display: grid;
    gap: 14px;
}

.memo-document-detail__content .omo-document-external__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.memo-document-detail__content .omo-document-external__hint,
.memo-document-detail__content .omo-document-external__fallback,
.memo-document-detail__variant-head span,
.memo-document-detail__media-head span {
    color: var(--color-text-light);
    line-height: 1.6;
    font-size: 0.92rem;
}

.memo-document-detail__content .omo-document-external__frame {
    width: 100%;
    min-height: 72vh;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: #fff;
}

.memo-document-detail__content .omo-document-file,
.memo-document-detail__content .omo-document-embed {
    display: grid;
    gap: 10px;
    padding: 16px 18px;
    border-radius: 16px;
    border: 1px solid color-mix(in srgb, var(--color-border) 85%, #2563eb 15%);
    background: color-mix(in srgb, var(--color-surface) 92%, #eff6ff 8%);
}

.memo-document-detail__content .omo-document-file--empty,
.memo-document-detail__content .omo-document-embed__description,
.memo-document-detail__content .omo-document-embed__message {
    color: var(--color-text-light);
}

.memo-document-detail__content .omo-document-file__title,
.memo-document-detail__content .omo-document-embed__title {
    font-weight: 700;
    color: var(--color-text);
    word-break: break-word;
}

.memo-document-detail__content .omo-document-file__download {
    justify-self: flex-start;
}

.memo-document-detail__audio {
    width: 100%;
}

.memo-document-detail__image-link {
    display: inline-flex;
    align-self: flex-start;
    max-width: 100%;
}

.memo-document-detail__image {
    display: block;
    max-width: 100%;
    max-height: 420px;
    border-radius: var(--radius-md);
    border: 1px solid var(--color-border);
    object-fit: contain;
    background: var(--color-surface-alt);
}

.memo-document-detail__download {
    color: var(--color-primary);
    text-decoration: none;
}

.memo-document-detail__download:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .memo-document-detail {
        padding: 14px;
    }
}
</style>
        <?php
    }
}
