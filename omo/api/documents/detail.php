<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

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
        <div class="omo-empty-state">Document invalide.</div>
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
        <div class="omo-empty-state">Document introuvable ou inaccessible.</div>
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
$authorObject = $document->get('user');
$author = is_object($authorObject) && method_exists($authorObject, 'get')
    ? trim((string)$authorObject->get('username'))
    : '';
?>
<div class="omo-document-detail">
    <article class="omo-document-detail__article">
        <header class="omo-document-detail__intro">
            <div class="omo-document-detail__meta">
                <?php if ($createdAt instanceof DateTimeInterface): ?>
                    <span class="omo-pill"><?= $escape($formatDateTime($createdAt)) ?></span>
                <?php endif; ?>

                <?php if ($updatedAt instanceof DateTimeInterface && (!$createdAt instanceof DateTimeInterface || $updatedAt != $createdAt)): ?>
                    <span class="omo-pill">Mise à jour : <?= $escape($formatDateTime($updatedAt)) ?></span>
                <?php endif; ?>

                <?php if ($author !== ''): ?>
                    <span class="omo-pill">Par <?= $escape($author) ?></span>
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
        </header>

        <section class="omo-document-detail__section omo-card">
            <div class="omo-document-detail__content prose">
                <?= (string)$document->get('content') ?>
            </div>
        </section>

        <?php if (count($altTexts) > 0): ?>
            <section class="omo-document-detail__section">
                <h3 class="omo-document-detail__section-title">Versions texte</h3>
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
                                <strong><?= $escape($promptTitle !== '' ? $promptTitle : 'Version texte') ?></strong>
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
                <h3 class="omo-document-detail__section-title">Médias associés</h3>
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
}
</style>
