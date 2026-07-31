<?php

function commonLegalEnsureTranslationHelpers(): void
{
    if (!function_exists('loadTranslationBundle')) {
        require_once __DIR__ . '/translation_bundles.php';
    }
}

function commonGetLegalSharedSourceLang(): array
{
    return [
        'legal.shared.badge.temporary' => [
            'text' => 'Version provisoire',
            'context' => 'Badge displayed on temporary legal documents such as terms and privacy pages.',
        ],
        'legal.shared.intro.activation_notice' => [
            'text' => 'Elle est publiee afin de permettre l’activation technique de certaines integrations et sera completee, relue et validée ultérieurement.',
            'context' => 'Shared explanatory paragraph on temporary legal pages.',
        ],
    ];
}

function commonLegalResolveLocale(): string
{
    commonLegalEnsureTranslationHelpers();

    return (string)translationBundleResolveRequestLocale('lang');
}

function commonLegalLoadBundle(string $bundleKey, array $sourceLang, ?string $locale = null): array
{
    static $cache = [];

    commonLegalEnsureTranslationHelpers();

    $resolvedLocale = $locale !== null && $locale !== ''
        ? (string)$locale
        : commonLegalResolveLocale();
    $cacheKey = $bundleKey . ':' . $resolvedLocale . ':' . md5(serialize($sourceLang));

    if (!isset($cache[$cacheKey])) {
        $cache[$cacheKey] = function_exists('loadTranslationBundle')
            ? loadTranslationBundle($bundleKey, $resolvedLocale, $sourceLang)
            : $sourceLang;
    }

    return $cache[$cacheKey];
}

function commonLegalT(string $key, array $variables = [], ?array $bundle = null, ?array $sourceLang = null): string
{
    commonLegalEnsureTranslationHelpers();

    return translationBundleTranslate($key, $variables, $bundle, $sourceLang);
}

function commonLegalPageIsEmbedded(): bool
{
    if (!empty($_GET['embed'])) {
        return true;
    }

    $requestedWith = strtolower(trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));

    return $requestedWith === 'xmlhttprequest';
}

function commonRenderLegalPage(array $config): void
{
    $siteTitle = trim((string)($config['siteTitle'] ?? 'Le site'));
    $pageTitle = trim((string)($config['pageTitle'] ?? $siteTitle));
    $documentTitle = trim((string)($config['documentTitle'] ?? $pageTitle));
    $badge = trim((string)($config['badge'] ?? ''));
    $intro = array_values($config['intro'] ?? []);
    $sections = array_values($config['sections'] ?? []);
    $note = array_values($config['note'] ?? []);
    $embed = commonLegalPageIsEmbedded();

    $accent = trim((string)($config['accent'] ?? '#2563eb'));
    $accentSoft = trim((string)($config['accentSoft'] ?? '#dbeafe'));
    $backgroundStart = trim((string)($config['backgroundStart'] ?? '#eff6ff'));
    $pageBackground = trim((string)($config['pageBackground'] ?? '#f8fafc'));
    $noteBackground = trim((string)($config['noteBackground'] ?? '#f8fbff'));
    $borderColor = trim((string)($config['borderColor'] ?? '#dbe4ee'));
    $locale = trim((string)($config['locale'] ?? 'fr'));

    $style = <<<CSS
<style>
    :root {
        --legal-bg: var(--color-surface-alt, {$pageBackground});
        --legal-surface: var(--color-surface, #ffffff);
        --legal-text: var(--color-text, #0f172a);
        --legal-muted: var(--color-text-light, #475569);
        --legal-accent: {$accent};
        --legal-accent-soft: {$accentSoft};
        --legal-border: var(--color-border, {$borderColor});
        --legal-note-bg: {$noteBackground};
        --legal-bg-start: {$backgroundStart};
        color-scheme: light;
    }

    html[data-theme="dark"],
    :root[data-theme="dark"] {
        --legal-accent-soft: color-mix(in srgb, {$accent} 22%, var(--legal-surface));
        --legal-note-bg: color-mix(in srgb, {$accent} 14%, var(--legal-surface));
        color-scheme: dark;
    }

    @media (prefers-color-scheme: dark) {
        :root:not([data-theme]) {
            --legal-bg: #101923;
            --legal-surface: #16202b;
            --legal-text: #e5edf7;
            --legal-muted: #9fb0c3;
            --legal-border: #283548;
            --legal-accent-soft: color-mix(in srgb, {$accent} 22%, #16202b);
            --legal-note-bg: color-mix(in srgb, {$accent} 14%, #16202b);
            --legal-bg-start: #0b1220;
            color-scheme: dark;
        }
    }

    * {
        box-sizing: border-box;
    }

    .common-legal-page-shell {
        max-width: 920px;
        margin: 0 auto;
        padding: 32px 20px 48px;
    }

    .common-legal-page-card {
        background: var(--legal-surface);
        border: 1px solid var(--legal-border);
        border-radius: var(--radius-md);
        padding: 28px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }

    .common-legal-page-content h1 {
        margin: 0 0 10px;
        font-size: 32px;
        line-height: 1.15;
        color: var(--legal-text);
    }

    .common-legal-page-content h2 {
        margin: 28px 0 0;
        font-size: 20px;
        color: var(--legal-text);
    }

    .common-legal-page-content p,
    .common-legal-page-content li {
        line-height: 1.7;
        color: var(--legal-muted);
    }

    .common-legal-page-badge {
        display: inline-block;
        margin-bottom: 14px;
        padding: 6px 10px;
        border-radius: 999px;
        background: var(--legal-accent-soft);
        color: var(--legal-accent);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .common-legal-page-note {
        margin-top: 22px;
        padding: 14px 16px;
        border-left: 4px solid var(--legal-accent);
        background: var(--legal-note-bg);
        border-radius: var(--radius-md);
    }

    .common-legal-page-content a {
        color: var(--legal-accent);
    }

    .common-legal-page-content strong {
        color: var(--legal-text);
    }

    body.common-legal-page-body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: linear-gradient(180deg, var(--legal-bg-start) 0%, var(--legal-bg) 220px);
        color: var(--legal-text);
    }

    .common-legal-page-content--embed {
        padding: 24px;
        color: var(--legal-text);
    }

    .common-legal-page-content--embed .common-legal-page-note {
        margin-bottom: 0;
    }
</style>
CSS;

    $renderContent = static function () use ($badge, $documentTitle, $intro, $sections, $note, $siteTitle, $embed): void {
        ?>
        <article class="common-legal-page-content<?= $embed ? ' common-legal-page-content--embed' : '' ?>">
            <?php if ($badge !== ''): ?>
                <div class="common-legal-page-badge"><?= htmlspecialchars($badge) ?></div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($documentTitle) ?></h1>
            <?php foreach ($intro as $paragraph): ?>
                <p><?= str_replace('{siteTitle}', htmlspecialchars($siteTitle), (string)$paragraph) ?></p>
            <?php endforeach; ?>

            <?php foreach ($sections as $section): ?>
                <?php
                $sectionTitle = trim((string)($section['title'] ?? ''));
                $paragraphs = array_values($section['paragraphs'] ?? []);
                $listItems = array_values($section['list'] ?? []);
                ?>
                <?php if ($sectionTitle !== ''): ?>
                    <h2><?= htmlspecialchars($sectionTitle) ?></h2>
                <?php endif; ?>
                <?php foreach ($paragraphs as $paragraph): ?>
                    <p><?= str_replace('{siteTitle}', htmlspecialchars($siteTitle), (string)$paragraph) ?></p>
                <?php endforeach; ?>
                <?php if (!empty($listItems)): ?>
                    <ul>
                        <?php foreach ($listItems as $listItem): ?>
                            <li><?= str_replace('{siteTitle}', htmlspecialchars($siteTitle), (string)$listItem) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($note)): ?>
                <div class="common-legal-page-note">
                    <?php foreach ($note as $paragraph): ?>
                        <p><?= str_replace('{siteTitle}', htmlspecialchars($siteTitle), (string)$paragraph) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
        <?php
    };

    if ($embed) {
        echo $style;
        $renderContent();
        return;
    }
    ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="/shared_css.css" rel="stylesheet">
    <?= $style . PHP_EOL ?>
    <script src="/shared_functions.js"></script>
    <script>
    if (typeof window.sharedApplyDocumentTheme === 'function') {
        window.sharedApplyDocumentTheme(document);
    }
    </script>
</head>
<body class="common-legal-page-body" data-legal-locale="<?= htmlspecialchars($locale) ?>">
    <div class="common-legal-page-shell">
        <div class="common-legal-page-card">
            <?php $renderContent(); ?>
        </div>
    </div>
</body>
</html>
<?php
}
