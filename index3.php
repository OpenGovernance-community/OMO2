<?php

require_once __DIR__ . '/config.php';

spl_autoload_register(static function ($class) {
    $path = __DIR__ . '/class/' . str_replace('\\', '/', strtolower((string)$class)) . '.class.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/common/translation_bundles.php';

$sourceLang = [
    'meta.title' => ['text' => 'OMO - Le cadre de votre autonomie collective', 'context' => 'SEO title of the OMO mockup landing page.'],
    'meta.description' => ['text' => 'OMO donne aux collectifs un cap, un cadre et un tableau de bord pour cooperer avec autonomie.', 'context' => 'SEO description of the OMO mockup landing page.'],
    'skip.content' => ['text' => 'Aller au contenu principal', 'context' => 'Accessibility skip link.'],
    'nav.label' => ['text' => 'Navigation principale', 'context' => 'Accessible label for the primary navigation.'],
    'nav.menu' => ['text' => 'Ouvrir le menu', 'context' => 'Accessible label for the mobile navigation button.'],
    'nav.promise' => ['text' => 'Notre promesse', 'context' => 'Header link to the promise section.'],
    'nav.tools' => ['text' => 'Les outils', 'context' => 'Header link to the tools section.'],
    'nav.foundations' => ['text' => 'Les fondations', 'context' => 'Header link to the foundations section.'],
    'action.discover' => ['text' => 'Decouvrir OMO', 'context' => 'Primary call to action.'],
    'action.tools' => ['text' => 'Voir les outils', 'context' => 'Secondary call to action.'],
    'action.demo' => ['text' => 'Entrer dans la demo', 'context' => 'Call to action leading to the OMO app.'],
    'brand.tagline' => ['text' => 'Open My Organization', 'context' => 'Brand subtitle in the header.'],
    'hero.kicker' => ['text' => 'Maturite organisationnelle', 'context' => 'Hero eyebrow.'],
    'hero.title' => ['text' => 'L autonomie est plus forte quand elle a un cadre.', 'context' => 'Main hero heading.'],
    'hero.lead' => ['text' => 'OMO aide les collectifs a partager le cap, clarifier les responsabilites et voir l essentiel - pour que chacun puisse agir avec confiance.', 'context' => 'Hero supporting text.'],
    'hero.caption' => ['text' => 'Un outil au service des organisations vivantes.', 'context' => 'Caption over the hero artwork.'],
    'hero.hosting' => ['text' => 'Heberge en Suisse sur une infrastructure Infomaniak', 'context' => 'Hosting statement in hero.'],
    'promise.label' => ['text' => 'Ce qui compte vraiment', 'context' => 'Promise section eyebrow.'],
    'promise.title' => ['text' => 'Les outils ne remplacent pas une organisation. Ils la rendent lisible.', 'context' => 'Promise section title.'],
    'promise.text' => ['text' => 'Indicateurs, processus, projets et reunions sont utiles lorsqu ils sont relies a une intention commune. OMO ne dicte pas une methode : il donne une maison claire a ce que votre collectif choisit de faire vivre.', 'context' => 'Promise section text.'],
    'map.cap.title' => ['text' => 'Un cap partage', 'context' => 'Organization map foundation title.'],
    'map.cap.text' => ['text' => 'Vision, mission et priorites restent visibles dans le quotidien.', 'context' => 'Organization map foundation description.'],
    'map.frame.title' => ['text' => 'Un cadre explicite', 'context' => 'Organization map foundation title.'],
    'map.frame.text' => ['text' => 'Roles, autorites et regles posent les reperes pour agir sans attendre.', 'context' => 'Organization map foundation description.'],
    'map.board.title' => ['text' => 'Un tableau de bord', 'context' => 'Organization map foundation title.'],
    'map.board.text' => ['text' => 'Projets, decisions et indicateurs rendent les informations utiles accessibles.', 'context' => 'Organization map foundation description.'],
    'tools.label' => ['text' => 'Un meme espace, plusieurs appuis', 'context' => 'Tools section eyebrow.'],
    'tools.title' => ['text' => 'Tout ce dont une equipe a besoin pour se mettre en mouvement.', 'context' => 'Tools section title.'],
    'tools.intro' => ['text' => 'Activez les modules utiles aujourd hui, puis faites grandir votre espace avec vos pratiques.', 'context' => 'Tools section introduction.'],
    'tools.structure.title' => ['text' => 'Structure', 'context' => 'Tool card title.'],
    'tools.structure.text' => ['text' => 'Cercles, roles, attentes et autorites pour savoir qui decide quoi.', 'context' => 'Tool card description.'],
    'tools.decisions.title' => ['text' => 'Decisions', 'context' => 'Tool card title.'],
    'tools.decisions.text' => ['text' => 'Consultations, propositions et votes pour choisir ensemble avec tracabilite.', 'context' => 'Tool card description.'],
    'tools.projects.title' => ['text' => 'Projets', 'context' => 'Tool card title.'],
    'tools.projects.text' => ['text' => 'Projets, evenements et documents relies pour garder le fil de l action.', 'context' => 'Tool card description.'],
    'tools.pilotage.title' => ['text' => 'Pilotage', 'context' => 'Tool card title.'],
    'tools.pilotage.text' => ['text' => 'Indicateurs, processus et activités pour apprendre de ce qui se passe.', 'context' => 'Tool card description.'],
    'values.label' => ['text' => 'Les conditions de la cooperation', 'context' => 'Values section eyebrow.'],
    'values.title' => ['text' => 'La maturite ne se decrete pas. Elle se pratique.', 'context' => 'Values section title.'],
    'values.trust.title' => ['text' => 'Confiance', 'context' => 'Value card title.'],
    'values.trust.text' => ['text' => 'Des informations partagees pour ne pas avoir a tout controler.', 'context' => 'Value card description.'],
    'values.sovereignty.title' => ['text' => 'Souverainete', 'context' => 'Value card title.'],
    'values.sovereignty.text' => ['text' => 'Un pouvoir d action clair, avec un outil ouvert qui vous appartient.', 'context' => 'Value card description.'],
    'values.responsibility.title' => ['text' => 'Responsabilite', 'context' => 'Value card title.'],
    'values.responsibility.text' => ['text' => 'Des engagements visibles, des decisions situees et des apprentissages concrets.', 'context' => 'Value card description.'],
    'values.cooperation.title' => ['text' => 'Cooperation', 'context' => 'Value card title.'],
    'values.cooperation.text' => ['text' => 'Une memoire commune pour faire circuler la contribution de chacun.', 'context' => 'Value card description.'],
    'foundations.label' => ['text' => 'Un numerique a votre service', 'context' => 'Technical foundations eyebrow.'],
    'foundations.title' => ['text' => 'Ouvert, accueillant et fait pour durer.', 'context' => 'Technical foundations title.'],
    'foundation.open.title' => ['text' => 'Open source', 'context' => 'Technical foundation title.'],
    'foundation.open.text' => ['text' => 'Le code peut etre examine, enrichi et heberge par votre organisation.', 'context' => 'Technical foundation description.'],
    'foundation.language.title' => ['text' => 'Multilingue', 'context' => 'Technical foundation title.'],
    'foundation.language.text' => ['text' => 'L interface et son vocabulaire se rapprochent de la culture de votre collectif.', 'context' => 'Technical foundation description.'],
    'foundation.modular.title' => ['text' => 'Modulaire', 'context' => 'Technical foundation title.'],
    'foundation.modular.text' => ['text' => 'Commencez simplement et ajoutez les espaces dont vous avez besoin.', 'context' => 'Technical foundation description.'],
    'foundation.learning.title' => ['text' => 'Progressif', 'context' => 'Technical foundation title.'],
    'foundation.learning.text' => ['text' => 'Des outils accessibles qui accompagnent l apprentissage, sans le surcharger.', 'context' => 'Technical foundation description.'],
    'cta.title' => ['text' => 'Faire mieux ensemble demande plus qu une bonne intention.', 'context' => 'Closing call to action title.'],
    'cta.text' => ['text' => 'Donnez a votre collectif un espace commun pour se diriger, decider et apprendre.', 'context' => 'Closing call to action text.'],
    'footer.text' => ['text' => 'OMO est un outil au service des collectifs qui oeuvrent a l emergence d un monde enviable.', 'context' => 'Footer statement.'],
    'footer.source' => ['text' => 'Voir le code source', 'context' => 'Footer GitHub link.'],
    'footer.privacy' => ['text' => 'Politique de confidentialite', 'context' => 'Footer privacy link.'],
];

$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
$lang = translationBundleInit('omo_landing_index3', $locale, $sourceLang);

function omo3Escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$demoUrl = '/omo/';
$sourceUrl = 'https://github.com/DavidDrayer/OMO2';
$privacyUrl = '/common/politique-confidentialite.php';
$tools = ['structure', 'decisions', 'projects', 'pilotage'];
$values = ['trust', 'sovereignty', 'responsibility', 'cooperation'];
$foundations = ['open', 'language', 'modular', 'learning'];
?>
<!DOCTYPE html>
<html lang="<?= omo3Escape($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="<?= omo3Escape(t('meta.description', [], $lang, $sourceLang)) ?>">
    <meta name="theme-color" content="#082535">
    <title><?= omo3Escape(t('meta.title', [], $lang, $sourceLang)) ?></title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/assets/css/index3.css">
</head>
<body class="omo3-page">
    <a class="omo3-skip" href="#content"><?= omo3Escape(t('skip.content', [], $lang, $sourceLang)) ?></a>

    <header class="omo3-header">
        <div class="omo3-container omo3-header__inner">
            <a class="omo3-brand" href="#top" aria-label="OMO">
                <span class="omo3-brand__letters">omo</span>
                <span><?= omo3Escape(t('brand.tagline', [], $lang, $sourceLang)) ?></span>
            </a>
            <button class="omo3-menu" type="button" aria-expanded="false" aria-controls="omo3-navigation">
                <span></span><span></span>
                <span class="omo3-visually-hidden"><?= omo3Escape(t('nav.menu', [], $lang, $sourceLang)) ?></span>
            </button>
            <nav class="omo3-nav" id="omo3-navigation" aria-label="<?= omo3Escape(t('nav.label', [], $lang, $sourceLang)) ?>">
                <a href="#promesse"><?= omo3Escape(t('nav.promise', [], $lang, $sourceLang)) ?></a>
                <a href="#outils"><?= omo3Escape(t('nav.tools', [], $lang, $sourceLang)) ?></a>
                <a href="#fondations"><?= omo3Escape(t('nav.foundations', [], $lang, $sourceLang)) ?></a>
                <a class="generic-action-button generic-action-button--main omo3-nav__cta" href="<?= omo3Escape($demoUrl) ?>"><?= omo3Escape(t('action.demo', [], $lang, $sourceLang)) ?></a>
            </nav>
        </div>
    </header>

    <main id="content">
        <section class="omo3-hero" id="top">
            <div class="omo3-container omo3-hero__grid">
                <div class="omo3-hero__copy">
                    <p class="omo3-kicker"><span></span><?= omo3Escape(t('hero.kicker', [], $lang, $sourceLang)) ?></p>
                    <h1><?= omo3Escape(t('hero.title', [], $lang, $sourceLang)) ?></h1>
                    <p class="omo3-hero__lead"><?= omo3Escape(t('hero.lead', [], $lang, $sourceLang)) ?></p>
                    <div class="omo3-actions">
                        <a class="generic-action-button generic-action-button--main omo3-button" href="#promesse"><?= omo3Escape(t('action.discover', [], $lang, $sourceLang)) ?> <span aria-hidden="true">↓</span></a>
                        <a class="omo3-text-link" href="#outils"><?= omo3Escape(t('action.tools', [], $lang, $sourceLang)) ?> <span aria-hidden="true">→</span></a>
                    </div>
                    <p class="omo3-hosting"><span aria-hidden="true">CH</span><?= omo3Escape(t('hero.hosting', [], $lang, $sourceLang)) ?></p>
                </div>
                <figure class="omo3-iceberg">
                    <img src="/img/omo-iceberg.jpg" alt="" fetchpriority="high">
                    <figcaption><?= omo3Escape(t('hero.caption', [], $lang, $sourceLang)) ?></figcaption>
                </figure>
            </div>
        </section>

        <section class="omo3-promise" id="promesse">
            <div class="omo3-container omo3-promise__grid">
                <div class="omo3-intro">
                    <p class="omo3-kicker omo3-kicker--dark"><span></span><?= omo3Escape(t('promise.label', [], $lang, $sourceLang)) ?></p>
                    <h2><?= omo3Escape(t('promise.title', [], $lang, $sourceLang)) ?></h2>
                </div>
                <div class="omo3-promise__text">
                    <p><?= omo3Escape(t('promise.text', [], $lang, $sourceLang)) ?></p>
                </div>
            </div>
            <div class="omo3-container omo3-map">
                <?php foreach (['cap', 'frame', 'board'] as $index => $point): ?>
                    <article class="omo3-map__item omo3-map__item--<?= omo3Escape($point) ?>">
                        <span class="omo3-map__number">0<?= $index + 1 ?></span>
                        <h3><?= omo3Escape(t('map.' . $point . '.title', [], $lang, $sourceLang)) ?></h3>
                        <p><?= omo3Escape(t('map.' . $point . '.text', [], $lang, $sourceLang)) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="omo3-tools" id="outils">
            <div class="omo3-container">
                <div class="omo3-section-heading">
                    <p class="omo3-kicker"><span></span><?= omo3Escape(t('tools.label', [], $lang, $sourceLang)) ?></p>
                    <h2><?= omo3Escape(t('tools.title', [], $lang, $sourceLang)) ?></h2>
                    <p><?= omo3Escape(t('tools.intro', [], $lang, $sourceLang)) ?></p>
                </div>
                <div class="omo3-tools__layout">
                    <div class="omo3-dashboard" aria-hidden="true">
                        <div class="omo3-dashboard__bar"><span></span><span></span><span></span><b>Tableau de bord</b></div>
                        <div class="omo3-dashboard__body">
                            <div class="omo3-dashboard__circle">EQUIPE</div>
                            <div class="omo3-dashboard__metric"><span>Projets actifs</span><b>12</b><i></i></div>
                            <div class="omo3-dashboard__metric omo3-dashboard__metric--long"><span>Cap partage</span><b>76%</b><i></i></div>
                            <div class="omo3-dashboard__task"><span></span><span></span><span></span></div>
                            <div class="omo3-dashboard__line"></div>
                        </div>
                    </div>
                    <div class="omo3-tool-grid">
                        <?php foreach ($tools as $index => $tool): ?>
                            <article class="omo3-tool-card">
                                <span class="omo3-tool-card__index">0<?= $index + 1 ?></span>
                                <h3><?= omo3Escape(t('tools.' . $tool . '.title', [], $lang, $sourceLang)) ?></h3>
                                <p><?= omo3Escape(t('tools.' . $tool . '.text', [], $lang, $sourceLang)) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="omo3-values" id="valeurs">
            <div class="omo3-container omo3-values__header">
                <p class="omo3-kicker omo3-kicker--dark"><span></span><?= omo3Escape(t('values.label', [], $lang, $sourceLang)) ?></p>
                <h2><?= omo3Escape(t('values.title', [], $lang, $sourceLang)) ?></h2>
            </div>
            <div class="omo3-container omo3-value-grid">
                <?php foreach ($values as $index => $value): ?>
                    <article class="omo3-value-card omo3-value-card--<?= omo3Escape($value) ?>">
                        <span class="omo3-value-card__mark">0<?= $index + 1 ?></span>
                        <h3><?= omo3Escape(t('values.' . $value . '.title', [], $lang, $sourceLang)) ?></h3>
                        <p><?= omo3Escape(t('values.' . $value . '.text', [], $lang, $sourceLang)) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="omo3-foundations" id="fondations">
            <div class="omo3-container omo3-foundations__grid">
                <div class="omo3-foundations__intro">
                    <p class="omo3-kicker"><span></span><?= omo3Escape(t('foundations.label', [], $lang, $sourceLang)) ?></p>
                    <h2><?= omo3Escape(t('foundations.title', [], $lang, $sourceLang)) ?></h2>
                </div>
                <div class="omo3-foundation-list">
                    <?php foreach ($foundations as $foundation): ?>
                        <article>
                            <h3><?= omo3Escape(t('foundation.' . $foundation . '.title', [], $lang, $sourceLang)) ?></h3>
                            <p><?= omo3Escape(t('foundation.' . $foundation . '.text', [], $lang, $sourceLang)) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="omo3-closing">
            <div class="omo3-container omo3-closing__inner">
                <p class="omo3-closing__star" aria-hidden="true">✦</p>
                <h2><?= omo3Escape(t('cta.title', [], $lang, $sourceLang)) ?></h2>
                <p><?= omo3Escape(t('cta.text', [], $lang, $sourceLang)) ?></p>
                <a class="generic-action-button generic-action-button--main omo3-button omo3-button--light" href="<?= omo3Escape($demoUrl) ?>"><?= omo3Escape(t('action.demo', [], $lang, $sourceLang)) ?> <span aria-hidden="true">→</span></a>
            </div>
        </section>
    </main>

    <footer class="omo3-footer">
        <div class="omo3-container omo3-footer__inner">
            <p><?= omo3Escape(t('footer.text', [], $lang, $sourceLang)) ?></p>
            <nav aria-label="OMO">
                <a href="<?= omo3Escape($sourceUrl) ?>" target="_blank" rel="noopener noreferrer"><?= omo3Escape(t('footer.source', [], $lang, $sourceLang)) ?></a>
                <a href="<?= omo3Escape($privacyUrl) ?>"><?= omo3Escape(t('footer.privacy', [], $lang, $sourceLang)) ?></a>
            </nav>
        </div>
    </footer>

    <script>
    (function () {
        var menu = document.querySelector('.omo3-menu');
        var navigation = document.querySelector('.omo3-nav');
        if (!menu || !navigation) {
            return;
        }
        menu.addEventListener('click', function () {
            var isOpen = navigation.classList.toggle('is-open');
            menu.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        navigation.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                navigation.classList.remove('is-open');
                menu.setAttribute('aria-expanded', 'false');
            }
        });
    }());
    </script>
</body>
</html>
