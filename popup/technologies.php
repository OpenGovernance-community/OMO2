<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';

$sourceLang = [
    'title' => ['text' => 'Les technologies qui font vivre OMO2', 'context' => 'Public technology acknowledgements page title.'],
    'intro' => ['text' => 'OMO2 grandit grâce à des logiciels, des bibliothèques et des services créés par d’autres. Merci aux équipes et aux communautés qui les font vivre.', 'context' => 'Technology acknowledgements introduction.'],
    'scope' => ['text' => 'Voici leur rôle dans le projet. Certains services sont optionnels ou dépendent de la configuration de chaque instance.', 'context' => 'Explains optional technologies in the public inventory.'],
    'navigation' => ['text' => 'Explorer les technologies', 'context' => 'Accessible name for technology category navigation.'],
    'group.collaboration' => ['text' => 'Applications collaboratives', 'context' => 'Technology category for collaborative applications on the VPS.'],
    'group.interface' => ['text' => 'Interface et édition', 'context' => 'Technology category for browser libraries.'],
    'group.maps' => ['text' => 'Cartographie', 'context' => 'Technology category for maps and geographic data.'],
    'group.php' => ['text' => 'Documents et bibliotheques PHP', 'context' => 'Technology category for PHP libraries and document exports.'],
    'group.infrastructure' => ['text' => 'Hébergement et outils', 'context' => 'Technology category for infrastructure and development tools.'],
    'group.services' => ['text' => 'Services et connexions', 'context' => 'Technology category for external service integrations.'],
    'group.delivery' => ['text' => 'Diffusion des ressources', 'context' => 'Technology category for the content delivery networks used by browser assets.'],
    'website' => ['text' => 'Site officiel', 'context' => 'Link to a technology official website.'],
    'source' => ['text' => 'Code source', 'context' => 'Link to a technology source repository.'],
    'packages.title' => ['text' => 'Toutes les dépendances PHP', 'context' => 'Disclosure title for the complete Composer dependency inventory.'],
    'packages.intro' => ['text' => 'Inventaire issu de composer.lock, avec les dépendances directes et indirectes. Les versions correspondent aux références du projet.', 'context' => 'Explains the origin of the detailed PHP package inventory.'],
    'packages.name' => ['text' => 'Bibliothèque', 'context' => 'PHP dependency table package column heading.'],
    'packages.version' => ['text' => 'Version referencee', 'context' => 'PHP dependency table version column heading.'],
    'packages.license' => ['text' => 'Licence declaree', 'context' => 'PHP dependency table license column heading.'],
    'packages.unknown' => ['text' => 'Non indiquée', 'context' => 'Fallback for missing dependency license metadata.'],
    'credits' => ['text' => 'Illustrations : logos issus de Simple Icons et des projets concernés, complétés par des repères graphiques propres à cette page.', 'context' => 'Attribution for the locally stored technology illustrations.'],
];

// Each entry contains the name, its actual role here, the website and the upstream repository.
// Evidence: Docker/Compose files, composer.json, library/, shared_functions.php and common/ integrations.
$technologies = [
    'collaboration' => [
        'etherpad' => ['Etherpad', 'Écriture de textes a plusieurs, en temps réel, dans les documents collaboratifs.', 'https://etherpad.org/', 'https://github.com/ether/etherpad'],
        'ethercalc' => ['EtherCalc', 'Tableurs collaboratifs intégrés aux espaces de documents.', 'https://ethercalc.net/', 'https://github.com/audreyt/ethercalc'],
        'collabora' => ['Collabora Online', 'Édition en ligne des documents bureautiques, avec une connexion WOPI.', 'https://www.collaboraonline.com/', 'https://github.com/CollaboraOnline/online'],
        'spacedeck' => ['SpaceDeck', 'Tableaux blancs collaboratifs pour réunir textes, images et idées. OMO2 utilise une adaptation du projet ouvert.', '', 'https://github.com/spacedeck/spacedeck-open'],
        'jitsi' => ['Jitsi Meet', 'Visioconférence hébergée sur le VPS et intégrée a OMO2, avec invitations, identités et droits de salle.', 'https://jitsi.org/', 'https://github.com/jitsi/jitsi-meet'],
    ],
    'interface' => [
        'summernote' => ['Summernote', 'Édition de texte riche dans les descriptions, les propositions et les documents HTML.', 'https://summernote.org/', 'https://github.com/summernote/summernote'],
        'jquery' => ['jQuery', 'Interactions de l’interface et socle de plusieurs composants historiques.', 'https://jquery.com/', 'https://github.com/jquery/jquery'],
        'jquery-ui' => ['jQuery UI', 'Composants interactifs utilises dans les formulaires et les interfaces historiques.', 'https://jqueryui.com/', 'https://github.com/jquery/jquery-ui'],
        'bootstrap' => ['Bootstrap', 'Styles et composants des pages historiques et des éditeurs de texte.', 'https://getbootstrap.com/', 'https://github.com/twbs/bootstrap'],
        'd3' => ['D3.js', 'Visualisation des cercles et de la structure des organisations ; les anciennes vues utilisent aussi d3-queue.', 'https://d3js.org/', 'https://github.com/d3/d3'],
        'driver' => ['Driver.js', 'Visites guidées et mise en évidence des éléments de l’interface pour accompagner la prise en main.', 'https://driverjs.com/', 'https://github.com/kamranahmedse/driver.js'],
        'stats-js' => ['stats.js', 'Suivi des performances graphiques dans l’ancienne interface des cercles.', '', 'https://github.com/mrdoob/stats.js'],
    ],
    'maps' => [
        'leaflet' => ['Leaflet', 'Cartes interactives pour localiser les organisations et choisir une position géographique.', 'https://leafletjs.com/', 'https://github.com/Leaflet/Leaflet'],
        'markercluster' => ['Leaflet.markercluster', 'Regroupement des organisations proches sur les cartes pour garder une vue lisible.', '', 'https://github.com/Leaflet/Leaflet.markercluster'],
        'openstreetmap' => ['OpenStreetMap', 'Données géographiques ouvertes qui contribuent aux fonds de carte.', 'https://www.openstreetmap.org/', 'https://github.com/openstreetmap/openstreetmap-website'],
        'openmaptiles' => ['OpenMapTiles', 'Schéma et outils des données cartographiques utilises par les fonds de carte.', 'https://openmaptiles.org/', 'https://github.com/openmaptiles/openmaptiles'],
        'stadia' => ['Stadia Maps', 'Fourniture des fonds de carte clairs et sombres, selon la configuration de l’instance.', 'https://stadiamaps.com/', ''],
    ],
    'php' => [
        'dompdf' => ['Dompdf', 'Conversion des comptes rendus et des documents HTML en fichiers PDF.', 'https://dompdf.github.io/', 'https://github.com/dompdf/dompdf'],
        'tcpdf' => ['TCPDF', 'Bibliothèque de génération PDF conservée dans les dépendances du projet.', 'https://tcpdf.org/', 'https://github.com/tecnickcom/TCPDF'],
        'phpword' => ['PHPWord', 'Génération des documents bureautiques et des exports de comptes rendus, notamment DOCX et ODT.', 'https://phpoffice.github.io/PHPWord/', 'https://github.com/PHPOffice/PHPWord'],
        'phpspreadsheet' => ['PhpSpreadsheet', 'Lecture des feuilles de calcul pour alimenter et synchroniser les statistiques.', 'https://phpspreadsheet.readthedocs.io/', 'https://github.com/PHPOffice/PhpSpreadsheet'],
        'commonmark' => ['League CommonMark', 'Conversion du Markdown en HTML dans les champs qui utilisent ce format.', 'https://commonmark.thephpleague.com/', 'https://github.com/thephpleague/commonmark'],
        'phpmailer' => ['PHPMailer', 'Envoi des e-mails, des invitations et des notifications via SMTP.', 'https://github.com/PHPMailer/PHPMailer', 'https://github.com/PHPMailer/PHPMailer'],
        'rrule' => ['php-rrule', 'Interprétation des règles de récurrence des événements dans les calendriers externes.', '', 'https://github.com/rlanvin/php-rrule'],
        'phpqrcode' => ['PHP QR Code', 'Génération des QR codes de partage et de configuration de la double authentification.', 'https://phpqrcode.sourceforge.net/', 'https://github.com/t0k4rt/phpqrcode'],
        'phinx' => ['Phinx', 'Outil de migration de base de données référence dans les dépendances PHP du projet.', 'https://phinx.org/', 'https://github.com/cakephp/phinx'],
        'phpdotenv' => ['phpdotenv', 'Bibliothèque de chargement de configuration .env référencée dans les dépendances PHP.', '', 'https://github.com/vlucas/phpdotenv'],
        'openai-php' => ['OpenAI PHP client', 'Client orhanerday/open-ai conservé dans les dépendances PHP. Les connecteurs IA partages disposent aussi de leurs propres appels HTTP.', '', 'https://github.com/orhanerday/open-ai'],
    ],
    'infrastructure' => [
        'php' => ['PHP', 'Langage du serveur OMO2, de ses API et de ses traitements en arriere-plan.', 'https://www.php.net/', 'https://github.com/php/php-src'],
        'apache' => ['Apache HTTP Server', 'Serveur web de l’environnement Docker OMO2, avec les règles de routage des pages et des API.', 'https://httpd.apache.org/', 'https://github.com/apache/httpd'],
        'mariadb' => ['MariaDB', 'Base de données relationnelle de l’environnement Docker et des services qui lui sont reliés.', 'https://mariadb.org/', 'https://github.com/MariaDB/server'],
        'nodejs' => ['Node.js', 'Exécution des applications collaboratives et des outils JavaScript côté serveur.', 'https://nodejs.org/', 'https://github.com/nodejs/node'],
        'sqlite' => ['SQLite', 'Stockage de la base de données du tableau blanc SpaceDeck.', 'https://www.sqlite.org/', 'https://github.com/sqlite/sqlite'],
        'docker' => ['Docker et Compose', 'Exécution des applications et de leurs dépendances dans des environnements reproductibles.', 'https://www.docker.com/', 'https://github.com/docker/compose'],
        'caddy' => ['Caddy', 'Proxy HTTPS des applications collaboratives sur le VPS et gestion de leurs certificats.', 'https://caddyserver.com/', 'https://github.com/caddyserver/caddy'],
        'composer' => ['Composer', 'Gestion des bibliothèques PHP et de leurs versions.', 'https://getcomposer.org/', 'https://github.com/composer/composer'],
        'graphicsmagick' => ['GraphicsMagick', 'Traitement des images dans l’environnement du tableau blanc SpaceDeck.', 'https://graphicsmagick.sourceforge.io/', 'https://foss.heptapod.net/graphicsmagick/graphicsmagick'],
        'mailpit' => ['Mailpit', 'Boite de réception de test pour vérifier les e-mails dans l’environnement de développement.', 'https://mailpit.axllent.org/', 'https://github.com/axllent/mailpit'],
        'phpmyadmin' => ['phpMyAdmin', 'Consultation et administration de la base de données dans l’environnement local.', 'https://www.phpmyadmin.net/', 'https://github.com/phpmyadmin/phpmyadmin'],
        'playwright' => ['Playwright', 'Tests des parcours dans le navigateur et vérification des interfaces sur ordinateur et mobile.', 'https://playwright.dev/', 'https://github.com/microsoft/playwright'],
        'infomaniak' => ['Infomaniak', 'Hébergement de l’instance officielle OMO2 et du VPS utilisé pour les services collaboratifs du projet.', 'https://www.infomaniak.com/', ''],
    ],
    'services' => [
        'patreon' => ['Patreon', 'Soutien financier au projet et connexion des abonnements des personnes et des organisations.', 'https://www.patreon.com/', ''],
        'vimeo' => ['Vimeo', 'Lecture des vidéos de présentation et des contenus vidéo des tutoriels.', 'https://vimeo.com/', 'https://github.com/vimeo/player.js'],
        'nextcloud' => ['Nextcloud', 'Connexion optionnelle d’un stockage de documents externe via WebDAV.', 'https://nextcloud.com/', 'https://github.com/nextcloud/server'],
        'kdrive' => ['kDrive', 'Connexion optionnelle au stockage documentaire Infomaniak via WebDAV.', 'https://www.infomaniak.com/fr/kdrive', ''],
        'infomaniak-ai' => ['Infomaniak AI Services', 'Fournisseur IA pris en charge pour l’assistance, la traduction et la transcription audio, selon la configuration de l’instance.', 'https://www.infomaniak.com/fr/hebergement/ai-services', ''],
        'openai' => ['OpenAI', 'Fournisseur IA pris en charge pour les fonctions d’assistance, selon la configuration.', 'https://openai.com/', ''],
        'anthropic' => ['Anthropic', 'Fournisseur IA pris en charge par les connecteurs partages, selon la configuration.', 'https://www.anthropic.com/', ''],
        'mistral' => ['Mistral AI', 'Fournisseur IA pris en charge par les connecteurs partages, selon la configuration.', 'https://mistral.ai/', ''],
        'telegram' => ['Telegram', 'Connexion au bot Telegram pour les fonctionnalités qui utilisent ce canal.', 'https://telegram.org/', 'https://github.com/tdlib/telegram-bot-api'],
        'github' => ['GitHub', 'Hébergement du code source, suivi des anomalies et connexion des signalements depuis OMO2.', 'https://github.com/', 'https://github.com/OpenGovernance-community/OMO2'],
        'uptimerobot' => ['UptimeRobot', 'Suivi de disponibilité du service, accessible depuis le pied de page. Une page de contrôle dédiée permet aussi de surveiller la visioconférence.', 'https://uptimerobot.com/', ''],
    ],
    'delivery' => [
        'cdnjs' => ['cdnjs', 'Diffusion de bibliothèques JavaScript et CSS, notamment pour les éditeurs HTML.', 'https://cdnjs.com/', 'https://github.com/cdnjs/cdnjs'],
        'jsdelivr' => ['jsDelivr', 'Diffusion de ressources JavaScript et CSS, notamment pour les visites guidées.', 'https://www.jsdelivr.com/', 'https://github.com/jsdelivr/jsdelivr'],
        'unpkg' => ['UNPKG', 'Diffusion de Leaflet et de ses extensions cartographiques.', 'https://unpkg.com/', 'https://github.com/unpkg/unpkg'],
    ],
];
foreach ($technologies as $items) {
    foreach ($items as $id => [$name, $description]) {
        $sourceLang['technology.' . $id] = ['text' => $description, 'context' => 'Actual role of ' . $name . ' in the OMO2 project.'];
    }
}
$locale = translationBundleResolveRequestLocale();
$lang = loadTranslationBundle('public_technologies', $locale, $sourceLang);
$t = static fn (string $key): string => t($key, [], $lang, $sourceLang);
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// Only public package metadata is rendered; no environment or runtime configuration is read here.
$lockPath = dirname(__DIR__) . '/composer.lock';
$lock = is_readable($lockPath) ? json_decode((string)file_get_contents($lockPath), true) : [];
$packages = is_array($lock['packages'] ?? null) ? $lock['packages'] : [];
usort($packages, static fn (array $a, array $b): int => strcasecmp((string)$a['name'], (string)$b['name']));
?>
<!doctype html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($t('title')) ?></title>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/technologies.css') ?>">
</head>
<body class="generic-public-page">
<main class="generic-page-shell generic-stack generic-stack--roomy">
    <header class="generic-hero-panel generic-stack">
        <h1 class="generic-card-title generic-card-title--large"><?= $e($t('title')) ?></h1>
        <p><?= $e($t('intro')) ?></p>
        <p class="generic-help-text generic-help-text--regular"><?= $e($t('scope')) ?></p>
    </header>
    <nav class="generic-action-row generic-action-row--start" aria-label="<?= $e($t('navigation')) ?>">
        <?php foreach ($technologies as $category => $items): ?>
        <a class="generic-action-button generic-action-button--secondary generic-action-button--compact" href="#technologies-<?= $e($category) ?>"><?= $e($t('group.' . $category)) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php foreach ($technologies as $category => $items): ?>
    <section class="generic-stack" aria-labelledby="technologies-<?= $e($category) ?>">
        <h2 id="technologies-<?= $e($category) ?>" class="generic-card-title generic-card-title--section"><?= $e($t('group.' . $category)) ?></h2>
        <div class="technology-grid">
        <?php foreach ($items as $id => [$name, $description, $website, $repository]): ?>
            <article class="generic-soft-panel generic-stack technology-card" id="technology-<?= $e($id) ?>">
                <img class="technology-card__image" src="<?= $e(commonAssetUrl('/img/technologies/' . $id . '.svg')) ?>" width="96" height="96" alt="" loading="lazy">
                <h3 class="generic-card-title generic-card-title--medium"><?= $e($name) ?></h3>
                <p><?= $e($t('technology.' . $id)) ?></p>
                <div class="generic-action-row generic-action-row--start technology-card__links">
                    <?php if ($website !== '' && $website !== $repository): ?><a class="generic-action-button generic-action-button--secondary generic-action-button--compact" href="<?= $e($website) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('website')) ?></a><?php endif; ?>
                    <?php if ($repository !== ''): ?><a class="generic-action-button generic-action-button--secondary generic-action-button--compact" href="<?= $e($repository) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('source')) ?></a><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <?php if ($packages !== []): ?>
    <details class="generic-accordion">
        <summary><?= $e($t('packages.title')) ?></summary>
        <div class="generic-accordion__content generic-stack">
            <p><?= $e($t('packages.intro')) ?></p>
            <div class="technology-packages">
                <table>
                    <caption class="generic-visually-hidden"><?= $e($t('packages.title')) ?></caption>
                    <thead><tr><th scope="col"><?= $e($t('packages.name')) ?></th><th scope="col"><?= $e($t('packages.version')) ?></th><th scope="col"><?= $e($t('packages.license')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($packages as $package): ?>
                        <?php $url = preg_replace('/\.git$/', '', (string)($package['source']['url'] ?? $package['homepage'] ?? '')); ?>
                        <tr><th scope="row"><?php if (preg_match('~^https://~i', $url)): ?><a href="<?= $e($url) ?>" target="_blank" rel="noopener noreferrer"><?= $e((string)$package['name']) ?></a><?php else: ?><?= $e((string)$package['name']) ?><?php endif; ?></th><td><?= $e((string)($package['version'] ?? '')) ?></td><td><?= $e(implode(', ', $package['license'] ?? []) ?: $t('packages.unknown')) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </details>
    <?php endif; ?>
    <footer class="generic-help-text"><p><?= $e($t('credits')) ?> <a href="https://simpleicons.org/" target="_blank" rel="noopener noreferrer">Simple Icons</a></p></footer>
</main>
</body>
</html>
