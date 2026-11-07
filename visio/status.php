<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$sourceLang = [
    'title' => ['text' => 'Etat de la visioconference', 'context' => 'Public video service health page title'],
    'intro' => ['text' => 'Ce controle interroge la configuration OMO, la base de donnees, le frontal Jitsi, la signalisation XMPP, Jicofo et le pont audio/video. Aucune salle ni aucun enregistrement ne sont crees.', 'context' => 'Description of video service health probes'],
    'refresh' => ['text' => 'Verifier maintenant', 'context' => 'Run video health checks again'],
    'back' => ['text' => 'Ouvrir la visio', 'context' => 'Return to the video application'],
    'pending' => ['text' => 'Verification en cours...', 'context' => 'Video health check progress'],
    'ok' => ['text' => 'Controles techniques reussis', 'context' => 'All video health probes passed'],
    'error' => ['text' => 'Service indisponible ou verification incomplete', 'context' => 'Video health check failed'],
    'available' => ['text' => 'OK', 'context' => 'Healthy video component'],
    'unavailable' => ['text' => 'Indisponible', 'context' => 'Failed video component'],
    'unknown' => ['text' => 'Non verifie', 'context' => 'Video component not checked yet'],
    'component' => ['text' => 'Composant', 'context' => 'Video health table heading'],
    'state' => ['text' => 'Etat', 'context' => 'Video health table heading'],
    'checked' => ['text' => 'Dernier controle :', 'context' => 'Time of last completed health request'],
    'configuration' => ['text' => 'Configuration OMO', 'context' => 'Video settings validation'],
    'database' => ['text' => 'Base de donnees OMO', 'context' => 'Video database connectivity check'],
    'frontend' => ['text' => 'Interface et API navigateur Jitsi', 'context' => 'Jitsi frontend resources check'],
    'signaling' => ['text' => 'Signalisation XMPP / Prosody', 'context' => 'Real BOSH session check'],
    'jicofo' => ['text' => 'Jicofo : gestion des conferences', 'context' => 'Jicofo internal health check'],
    'videobridge' => ['text' => 'Videobridge : pont audio/video', 'context' => 'JVB internal health check'],
    'monitoring' => ['text' => 'Surveillance avec UptimeRobot', 'context' => 'Monitor setup section title'],
    'setup' => ['text' => 'Ajouter un moniteur HTTP(s) avec cette adresse absolue sur votre instance. Utiliser GET ou HEAD et considerer HTTP 200 comme disponible, HTTP 503 comme indisponible. Choisir par exemple une verification toutes les 5 minutes et associer les contacts qui doivent recevoir les alertes.', 'context' => 'UptimeRobot setup instructions'],
    'keyword' => ['text' => 'Pour un moniteur par mot-cle, utiliser GET et rechercher la presence exacte de "status":"ok".', 'context' => 'Optional UptimeRobot keyword monitor instructions'],
    'limits' => ['text' => 'Ces controles ne verifient pas une connexion complete a une salle, la correspondance des cles JWT ou le transport des medias a travers les pare-feu. Tester regulierement un appel avec trois participants depuis des reseaux distincts pour verifier aussi le pont audio/video et le port UDP 10000.', 'context' => 'Limits of HTTP health checks and manual call recommendation'],
    'nojs' => ['text' => 'JavaScript est necessaire pour afficher les controles ici. La page JSON reste accessible avec le lien ci-dessous.', 'context' => 'No JavaScript health page notice'],
];
$locale = translationBundleResolveRequestLocale();
$lang = loadTranslationBundle('visio_health', $locale, $sourceLang);
$t = static fn (string $key): string => t($key, [], $lang, $sourceLang);
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="<?= $e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($t('title')) ?></title>
    <link rel="stylesheet" href="<?= $e(commonAssetUrl('/common/assets/components.css')) ?>">
    <script src="<?= $e(commonAssetUrl('/visio/status.js')) ?>" defer></script>
</head>
<body class="generic-public-page">
<main class="generic-page-shell generic-stack generic-stack--roomy" data-visio-health
      data-pending="<?= $e($t('pending')) ?>" data-ok="<?= $e($t('ok')) ?>" data-error="<?= $e($t('error')) ?>"
      data-available="<?= $e($t('available')) ?>" data-unavailable="<?= $e($t('unavailable')) ?>" data-unknown="<?= $e($t('unknown')) ?>">
    <header class="generic-hero-panel generic-stack">
        <h1 class="generic-card-title generic-card-title--large"><?= $e($t('title')) ?></h1>
        <p><?= $e($t('intro')) ?></p>
        <div class="generic-action-row generic-action-row--start">
            <button class="generic-action-button" type="button" data-health-refresh><?= $e($t('refresh')) ?></button>
            <a class="generic-action-button generic-action-button--secondary" href="/visio/"><?= $e($t('back')) ?></a>
        </div>
    </header>
    <section class="generic-soft-panel generic-stack" aria-busy="true" data-health-results>
        <h2 class="generic-card-title" role="status" aria-live="polite" data-health-summary><?= $e($t('pending')) ?></h2>
        <noscript><p><?= $e($t('nojs')) ?></p></noscript>
        <div class="generic-data-table-scroll">
            <table class="generic-data-table">
                <thead><tr><th scope="col"><?= $e($t('component')) ?></th><th scope="col"><?= $e($t('state')) ?></th></tr></thead>
                <tbody>
                <?php foreach (['configuration', 'database', 'frontend', 'signaling', 'jicofo', 'videobridge'] as $check): ?>
                <tr><th scope="row"><?= $e($t($check)) ?></th><td data-health-check="<?= $e($check) ?>"><?= $e($t('unknown')) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="generic-help-text" hidden data-health-time><?= $e($t('checked')) ?> <time></time></p>
    </section>
    <section class="generic-section generic-stack">
        <h2 class="generic-card-title"><?= $e($t('monitoring')) ?></h2>
        <p><?= $e($t('setup')) ?></p>
        <p><a href="/visio/health.php" data-health-url><code>/visio/health.php</code></a></p>
        <p><?= $e($t('keyword')) ?></p>
        <p class="generic-help-text generic-help-text--regular"><?= $e($t('limits')) ?></p>
    </section>
</main>
</body>
</html>
