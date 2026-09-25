<?php
declare(strict_types=1);

require_once __DIR__ . '/common/translation_bundles.php';

$sourceLang = [
    'migration.meta.title' => ['text' => 'Migration de OMO1 vers OMO2 - OpenMyOrganization', 'context' => 'Browser title for the public OMO migration page.'],
    'migration.meta.description' => ['text' => 'Decouvrez le passage de OMO1 a OMO2 et retrouvez ici les explications sur la migration.', 'context' => 'SEO description for the OMO migration page.'],
    'migration.skip' => ['text' => 'Aller au contenu', 'context' => 'Skip link.'],
    'migration.nav.label' => ['text' => 'Navigation principale', 'context' => 'Navigation label.'],
    'migration.nav.home' => ['text' => 'Accueil', 'context' => 'Link to the OMO2 homepage.'],
    'migration.nav.compare' => ['text' => 'Avant / apres', 'context' => 'Link to the image comparison.'],
    'migration.nav.video' => ['text' => 'Video explicative', 'context' => 'Link to the future explanatory video.'],
    'migration.nav.login' => ['text' => 'Se connecter', 'context' => 'Link to the OMO workspace.'],
    'migration.menu.open' => ['text' => 'Ouvrir le menu', 'context' => 'Accessible mobile menu action.'],
    'migration.menu.close' => ['text' => 'Fermer le menu', 'context' => 'Accessible mobile menu action.'],
    'migration.hero.title' => ['text' => 'Une nouvelle etape pour OMO.', 'context' => 'Migration page heading.'],
    'migration.compare.instruction' => ['text' => 'Faites glisser le curseur, puis choisissez un autre ecran sous les images.', 'context' => 'Instructions below the image comparison.'],
    'migration.compare.omo1' => ['text' => 'OMO1', 'context' => 'Label for the older screenshot.'],
    'migration.compare.omo2' => ['text' => 'OMO2', 'context' => 'Label for the newer screenshot.'],
    'migration.compare.alt1' => ['text' => 'Vue des cercles dans OMO1', 'context' => 'Alternative text for the OMO1 screenshot.'],
    'migration.compare.alt2' => ['text' => 'Vue des cercles dans OMO2', 'context' => 'Alternative text for the OMO2 screenshot.'],
    'migration.compare.projects.alt1' => ['text' => 'Vue des projets dans OMO1', 'context' => 'Alternative text for the OMO1 projects screenshot.'],
    'migration.compare.projects.alt2' => ['text' => 'Vue des projets dans OMO2', 'context' => 'Alternative text for the OMO2 projects screenshot.'],
    'migration.compare.meetings.alt1' => ['text' => 'Vue de la reunion dans OMO1', 'context' => 'Alternative text for the OMO1 meeting screenshot.'],
    'migration.compare.meetings.alt2' => ['text' => 'Vue de la reunion dans OMO2', 'context' => 'Alternative text for the OMO2 meeting screenshot.'],
    'migration.compare.documents.alt1' => ['text' => 'Vue des documents dans OMO1', 'context' => 'Alternative text for the OMO1 documents screenshot.'],
    'migration.compare.documents.alt2' => ['text' => 'Vue des documents dans OMO2', 'context' => 'Alternative text for the OMO2 documents screenshot.'],
    'migration.compare.indicators.alt1' => ['text' => 'Vue des indicateurs dans OMO1', 'context' => 'Alternative text for the OMO1 indicators screenshot.'],
    'migration.compare.indicators.alt2' => ['text' => 'Vue des indicateurs dans OMO2', 'context' => 'Alternative text for the OMO2 indicators screenshot.'],
    'migration.compare.screens.label' => ['text' => 'Choisir un ecran a comparer', 'context' => 'Accessible label for the screen selector.'],
    'migration.compare.screens.soon' => ['text' => 'Bientot', 'context' => 'Label for comparison screens that do not have images yet.'],
    'migration.compare.screens.structure' => ['text' => 'Structure', 'context' => 'Comparison button for circle structure.'],
    'migration.compare.screens.projects' => ['text' => 'Projets', 'context' => 'Comparison button for projects.'],
    'migration.compare.screens.meetings' => ['text' => 'Reunions', 'context' => 'Comparison button for meetings.'],
    'migration.compare.screens.decisions' => ['text' => 'Decisions', 'context' => 'Future comparison button for decisions.'],
    'migration.compare.screens.documents' => ['text' => 'Documents', 'context' => 'Comparison button for documents.'],
    'migration.compare.screens.indicators' => ['text' => 'Indicateurs', 'context' => 'Comparison button for indicators.'],
    'migration.compare.slider' => ['text' => 'Comparer OMO1 et OMO2 : fleche gauche pour voir OMO2, fleche droite pour voir OMO1', 'context' => 'Accessible label for the comparison range control.'],
    'migration.video.eyebrow' => ['text' => 'Comprendre la migration', 'context' => 'Video section eyebrow.'],
    'migration.video.title' => ['text' => 'Le passage a OMO2, explique en video.', 'context' => 'Video section heading.'],
    'migration.video.body' => ['text' => 'Un espace pour presenter le processus de migration et repondre aux questions courantes. La video sera ajoutee ici prochainement.', 'context' => 'Explanation of the planned video.'],
    'migration.video.placeholder' => ['text' => 'Video explicative a venir', 'context' => 'Visible label in the empty video area.'],
    'migration.footer.tagline' => ['text' => 'OpenMyOrganization, un commun porte par OpenGouvernance.', 'context' => 'Footer tagline.'],
    'migration.footer.home' => ['text' => 'Retour a l\'accueil OMO2', 'context' => 'Footer homepage link.'],
];

$lang = loadTranslationBundle('migration_omo', 'fr', $sourceLang);
$t = static function (string $key) use ($lang, $sourceLang): string {
    return t($key, [], $lang, $sourceLang);
};
$e = static function (string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
};
$comparisonScreens = [
    ['key' => 'structure', 'before' => '/img/omo2/migration-omo1.png', 'after' => '/img/omo2/migration-omo2.png', 'altBefore' => $t('migration.compare.alt1'), 'altAfter' => $t('migration.compare.alt2')],
    ['key' => 'projects', 'before' => '/img/omo2/migration-omo1-projects.png', 'after' => '/img/omo2/migration-omo2-projects.png', 'altBefore' => $t('migration.compare.projects.alt1'), 'altAfter' => $t('migration.compare.projects.alt2')],
    ['key' => 'meetings', 'before' => '/img/omo2/migration-omo1-meetings.png', 'after' => '/img/omo2/migration-omo2-meetings.png', 'altBefore' => $t('migration.compare.meetings.alt1'), 'altAfter' => $t('migration.compare.meetings.alt2')],
    ['key' => 'decisions', 'before' => null, 'after' => null],
    ['key' => 'documents', 'before' => '/img/omo2/migration-omo1-documents.png', 'after' => '/img/omo2/migration-omo2-documents.png', 'altBefore' => $t('migration.compare.documents.alt1'), 'altAfter' => $t('migration.compare.documents.alt2')],
    ['key' => 'indicators', 'before' => '/img/omo2/migration-omo1-indicators.png', 'after' => '/img/omo2/migration-omo2-indicators.png', 'altBefore' => $t('migration.compare.indicators.alt1'), 'altAfter' => $t('migration.compare.indicators.alt2')],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $e($t('migration.meta.title')) ?></title>
    <meta name="description" content="<?= $e($t('migration.meta.description')) ?>">
    <link rel="canonical" href="/migration-omo.php">
    <meta name="theme-color" content="#05263c">
    <link rel="icon" type="image/jpeg" href="/img/omo-iceberg.jpg?v=20260914" sizes="1254x1254">
    <link rel="stylesheet" href="/assets/omo2-home.css?v=20260914-organizations-16">
    <link rel="stylesheet" href="/assets/omo2-migration.css?v=3">
</head>
<body>
    <a class="omo2-skip-link" href="#contenu"><?= $e($t('migration.skip')) ?></a>
    <header class="omo2-header" data-omo2-header>
        <div class="omo2-shell omo2-header__inner">
            <a class="omo2-brand" href="/index2.php" aria-label="OpenMyOrganization">
                <img src="/img/omo2/logo-omo.png" width="1081" height="441" alt="OpenMyOrganization">
            </a>
            <button class="omo2-menu-button" type="button" aria-expanded="false" aria-controls="omo2-navigation" data-omo2-menu-button data-label-open="<?= $e($t('migration.menu.open')) ?>" data-label-close="<?= $e($t('migration.menu.close')) ?>">
                <span class="omo2-menu-button__bars" aria-hidden="true"><i></i><i></i></span>
                <span class="omo2-sr-only" data-omo2-menu-label><?= $e($t('migration.menu.open')) ?></span>
            </button>
            <nav class="omo2-navigation" id="omo2-navigation" aria-label="<?= $e($t('migration.nav.label')) ?>" data-omo2-navigation>
                <a href="/index2.php"><?= $e($t('migration.nav.home')) ?></a>
                <a href="#comparaison" aria-current="page"><?= $e($t('migration.nav.compare')) ?></a>
                <a href="#video"><?= $e($t('migration.nav.video')) ?></a>
                <a class="omo2-button omo2-button--small" href="/omo/"><?= $e($t('migration.nav.login')) ?></a>
            </nav>
        </div>
    </header>

    <main id="contenu">
        <section class="omo2-hero omo2-migration-hero" id="comparaison" aria-labelledby="migration-title">
            <div class="omo2-hero__orb omo2-hero__orb--one" aria-hidden="true"></div>
            <div class="omo2-hero__orb omo2-hero__orb--two" aria-hidden="true"></div>
            <div class="omo2-shell">
                <div class="omo2-migration-hero__intro">
                    <h1 id="migration-title"><?= $e($t('migration.hero.title')) ?></h1>
                </div>
                <figure class="omo2-migration-comparison" data-omo-comparison>
                    <div class="omo2-migration-comparison__frame">
                        <img class="omo2-migration-comparison__image" data-compare-after-image src="<?= $e($comparisonScreens[0]['after']) ?>" width="2860" height="1532" alt="<?= $e($comparisonScreens[0]['altAfter']) ?>" fetchpriority="high" decoding="async">
                        <img class="omo2-migration-comparison__image omo2-migration-comparison__before" data-compare-before-image src="<?= $e($comparisonScreens[0]['before']) ?>" width="2860" height="1532" alt="<?= $e($comparisonScreens[0]['altBefore']) ?>" fetchpriority="high" decoding="async">
                        <span class="omo2-migration-comparison__label omo2-migration-comparison__label--before" aria-hidden="true"><?= $e($t('migration.compare.omo1')) ?></span>
                        <span class="omo2-migration-comparison__label omo2-migration-comparison__label--after" aria-hidden="true"><?= $e($t('migration.compare.omo2')) ?></span>
                        <span class="omo2-migration-comparison__divider" aria-hidden="true"><span class="omo2-migration-comparison__handle">‹&nbsp;›</span></span>
                        <input class="omo2-migration-comparison__range" type="range" min="0" max="100" value="50" aria-label="<?= $e($t('migration.compare.slider')) ?>">
                    </div>
                    <figcaption class="omo2-migration-comparison__instruction"><?= $e($t('migration.compare.instruction')) ?></figcaption>
                    <div class="omo2-migration-comparison__screens" role="group" aria-label="<?= $e($t('migration.compare.screens.label')) ?>">
                        <?php foreach ($comparisonScreens as $index => $screen): ?>
                        <button class="omo2-migration-screen-button" type="button" data-compare-screen<?php if ($screen['before'] !== null): ?> data-compare-before="<?= $e($screen['before']) ?>" data-compare-after="<?= $e($screen['after']) ?>" data-compare-alt-before="<?= $e($screen['altBefore']) ?>" data-compare-alt-after="<?= $e($screen['altAfter']) ?>"<?php else: ?> disabled<?php endif; ?> aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                            <span><?= $e($t('migration.compare.screens.' . $screen['key'])) ?></span>
                            <?php if ($screen['before'] === null): ?><small><?= $e($t('migration.compare.screens.soon')) ?></small><?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </figure>
            </div>
        </section>

        <section class="omo2-section omo2-migration-video" id="video" aria-labelledby="migration-video-title">
            <div class="omo2-shell omo2-migration-video__grid">
                <div class="omo2-migration-video__copy">
                    <p class="omo2-eyebrow"><?= $e($t('migration.video.eyebrow')) ?></p>
                    <h2 id="migration-video-title"><?= $e($t('migration.video.title')) ?></h2>
                    <p><?= $e($t('migration.video.body')) ?></p>
                </div>
                <div class="omo2-migration-video__placeholder" aria-label="<?= $e($t('migration.video.placeholder')) ?>">
                    <span class="omo2-migration-video__play" aria-hidden="true">▶</span>
                    <strong><?= $e($t('migration.video.placeholder')) ?></strong>
                </div>
            </div>
        </section>
    </main>

    <footer class="omo2-footer omo2-migration-footer">
        <div class="omo2-shell omo2-footer__top">
            <a class="omo2-brand omo2-brand--footer" href="/index2.php"><img src="/img/omo2/logo-omo.png" width="1081" height="441" alt="OpenMyOrganization"></a>
            <p><?= $e($t('migration.footer.tagline')) ?></p>
        </div>
        <div class="omo2-shell omo2-migration-footer__bottom"><a href="/index2.php"><?= $e($t('migration.footer.home')) ?> <span aria-hidden="true">↗</span></a><span>© <?= date('Y') ?> OpenMyOrganization</span></div>
    </footer>
    <script src="/assets/omo2-home.js?v=20260914-organizations-16"></script>
    <script src="/assets/omo2-migration.js?v=2"></script>
</body>
</html>
