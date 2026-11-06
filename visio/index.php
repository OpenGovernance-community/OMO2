<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/common/visio.php';

use dbObject\VideoRoom;
use dbObject\AuthRateLimit;

$sourceLang = [
    'title' => ['text' => 'Visioconférence OMO2', 'context' => 'Standalone video room page title'],
    'intro' => ['text' => 'Créez une réunion vidéo avec votre identité OMO2, puis partagez son lien.', 'context' => 'Video room creation introduction'],
    'login' => ['text' => 'Se connecter avec OMO2', 'context' => 'Video room login action'],
    'login_intro' => ['text' => 'Connectez-vous pour créer une réunion ou retrouver votre identité et vos droits.', 'context' => 'Video login introduction'],
    'room_title' => ['text' => 'Nom de la réunion', 'context' => 'Video room title field'],
    'default_title' => ['text' => 'Réunion vidéo', 'context' => 'Default video room title'],
    'create' => ['text' => 'Créer une réunion', 'context' => 'Create video room action'],
    'my_rooms' => ['text' => 'Mes dernières réunions', 'context' => 'Owned video room list heading'],
    'back' => ['text' => 'Mes réunions', 'context' => 'Return to video room creation'],
    'organizer' => ['text' => 'Animateur : {name}', 'context' => 'Video organizer identity'],
    'participant' => ['text' => 'Participant : {name}', 'context' => 'Video participant identity'],
    'share' => ['text' => 'Lien à partager avec les participants', 'context' => 'Public invitation link label'],
    'copy' => ['text' => 'Copier le lien', 'context' => 'Copy invitation action'],
    'copied' => ['text' => 'Lien copié.', 'context' => 'Copy invitation success notification'],
    'copy_failed' => ['text' => 'La copie a échoué. Sélectionnez et copiez le lien affiché.', 'context' => 'Copy invitation failure notification'],
    'your_name' => ['text' => 'Votre nom', 'context' => 'Anonymous video guest name field'],
    'join' => ['text' => 'Rejoindre la réunion', 'context' => 'Video guest join action'],
    'guest_intro' => ['text' => 'Vous pouvez vous connecter avec OMO2 ou demander à rejoindre comme invité. Les invités attendent l’acceptation d’un animateur.', 'context' => 'Video invitation introduction'],
    'loading' => ['text' => 'Chargement de la visioconférence…', 'context' => 'Video iframe loading progress'],
    'ended' => ['text' => 'Vous avez quitté la réunion. Rechargez cette page pour la rejoindre de nouveau.', 'context' => 'Video conference ended persistent state'],
    'video_failed' => ['text' => 'La visioconférence n’a pas pu être ouverte. Vérifiez la configuration du service et rechargez cette page.', 'context' => 'Video iframe persistent error'],
    'expiry' => ['text' => 'En cas de reconnexion après une longue interruption, rechargez cette page pour renouveler votre autorisation.', 'context' => 'Video token rejoin guidance'],
    'not_configured' => ['text' => 'La visioconférence n’est pas encore configurée. L’administrateur doit activer la connexion Jitsi et son authentification OMO2.', 'context' => 'Video service configuration error'],
    'storage_error' => ['text' => 'Le stockage des réunions n’est pas disponible. Vérifiez les migrations de la base de données.', 'context' => 'Video storage error'],
    'not_found' => ['text' => 'Cette invitation est introuvable.', 'context' => 'Video invitation not found'],
    'invalid_title' => ['text' => 'Le nom de la réunion doit contenir entre 1 et 190 caractères.', 'context' => 'Video title validation'],
    'invalid_name' => ['text' => 'Indiquez un nom de 1 à 120 caractères.', 'context' => 'Video guest name validation'],
    'csrf' => ['text' => 'Ce formulaire a expiré. Rechargez la page puis réessayez.', 'context' => 'Video CSRF validation error'],
    'rate' => ['text' => 'Trop de demandes. Patientez avant de réessayer.', 'context' => 'Video rate limit error'],
    'login_required' => ['text' => 'Reconnectez-vous à OMO2 pour continuer.', 'context' => 'Video session validation error'],
];
$bundle = loadTranslationBundle('visio_page', commonAuthGetTranslationLocale(), $sourceLang);
$vt = static fn(string $key, array $vars = []): string => translationBundleTranslate($key, $vars, $bundle, $sourceLang);
$escape = static fn($text): string => htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

header('Cache-Control: no-store, private');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
$csrf = commonCsrfToken();
$userId = (int)commonGetCurrentUserId();
$config = visioConfig();
$code = is_string($_GET['room'] ?? null) ? $_GET['room'] : '';
$error = '';
$room = null;
$access = null;
$rooms = [];
$titleInput = '';
$guestName = '';
$ready = false;
$returnTo = '/visio/' . ($code !== '' ? rawurlencode($code) . '?login=1' : '');

if ((!$code || isset($_GET['login'])) && $userId <= 0) {
    commonRenderMagicLoginPage(['title' => $vt('title'), 'appName' => 'OMO2', 'intro' => $vt('login_intro'),
        'returnTo' => $returnTo, 'organization' => commonResolveOrganizationContext(1), 'layout' => 'public',
        'headHtml' => commonStylesheetTags('/common/assets/components.css')]);
    exit;
}

try {
    if (!$config['enabled']) { http_response_code(503); throw new RuntimeException('not_configured'); }
    if (!VideoRoom::isStorageAvailable()) { http_response_code(503); throw new RuntimeException('storage_error'); }
    $ready = true;
    if ($code !== '') {
        $room = VideoRoom::findByInvitation($code);
        if (!$room) { http_response_code(404); throw new RuntimeException('not_found'); }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['_csrf'] ?? null) || !hash_equals($csrf, $_POST['_csrf'])) { http_response_code(403); throw new RuntimeException('csrf'); }
        $limit = AuthRateLimit::consume('visio-submit', commonAuthHashIdentifier('visio-ip', commonGetRequestIp()), 30, 300);
        if (empty($limit['allowed'])) { http_response_code(429); throw new RuntimeException('rate'); }
        if (!$room && ($_POST['action'] ?? '') === 'create') {
            if (isset($_POST['title']) && !is_string($_POST['title'])) throw new RuntimeException('invalid_title');
            $titleInput = trim((string)($_POST['title'] ?? ''));
            $room = VideoRoom::createForCurrentUser($titleInput !== '' ? $titleInput : $vt('default_title'));
            header('Location: ' . $room->invitationPath(), true, 303);
            exit;
        }
        if ($room && ($_POST['action'] ?? '') === 'join') {
            if (!is_string($_POST['name'] ?? null)) throw new RuntimeException('invalid_name');
            $guestName = trim((string)($_POST['name'] ?? ''));
            // Validate before retaining the guest identity. The token is never stored in the session.
            visioIssueAccess($room, $userId, $guestName);
            $_SESSION['visio_guest_names'] = array_slice($_SESSION['visio_guest_names'] ?? [], -19, null, true);
            $_SESSION['visio_guest_names'][$code] = $guestName;
            header('Location: ' . $room->invitationPath() . '?join=1', true, 303);
            exit;
        }
        http_response_code(400);
        throw new RuntimeException('not_found');
    }
    if ($room && ($userId > 0 || (isset($_GET['join']) && isset($_SESSION['visio_guest_names'][$code])))) {
        $access = visioIssueAccess($room, $userId, (string)($_SESSION['visio_guest_names'][$code] ?? ''));
    }
    if (!$room && $userId > 0) $rooms = VideoRoom::listForCurrentUser();
} catch (RuntimeException $exception) {
    $key = $exception->getMessage();
    if (!isset($sourceLang[$key])) $key = 'storage_error';
    $error = $vt($key);
    if (http_response_code() < 400) http_response_code($key === 'storage_error' ? 503 : 400);
}

$shareUrl = $room ? commonBuildUrl($room->invitationPath()) : '';
$client = null;
if ($access) {
    $parsedJitsi = parse_url($config['url']);
    $client = ['domain' => $parsedJitsi['host'] . (isset($parsedJitsi['port']) ? ':' . $parsedJitsi['port'] : ''),
        'room' => $room->jitsiRoomName(), 'title' => (string)$room->get('title'), 'access' => $access,
        'language' => commonAuthGetTranslationLocale(), 'url' => $config['url'],
        'messages' => ['copied' => $vt('copied'), 'copyFailed' => $vt('copy_failed'), 'ended' => $vt('ended'), 'error' => $vt('video_failed')]];
}
?>
<!doctype html>
<html lang="<?= $escape(commonAuthGetTranslationLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($room ? $room->get('title') : $vt('title')) ?></title>
    <?= commonStylesheetTags('/shared_css.css') ?>
    <?= commonStylesheetTags('/common/assets/components.css') ?>
    <?= commonStylesheetTags('/common/notifications/notifications.css') ?>
    <?= commonStylesheetTags('/visio/visio.css') ?>
    <script src="<?= $escape(commonAssetUrl('/shared_functions.js')) ?>"></script>
    <script>sharedApplyDocumentTheme();</script>
    <script src="<?= $escape(commonAssetUrl('/common/notifications/notifications.js')) ?>" defer></script>
    <?php if ($client): ?>
    <script id="visio-config" type="application/json"><?= json_encode($client, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
    <script src="<?= $escape($config['url'] . '/external_api.js') ?>" defer></script>
    <script src="<?= $escape(commonAssetUrl('/visio/visio.js')) ?>" defer></script>
    <?php endif; ?>
</head>
<body class="generic-public-page">
<main class="generic-page-shell generic-stack">
    <header class="generic-public-brand">
        <a href="/visio/"><img class="generic-public-brand__logo" src="/img/omo2/logo-omo-dark.png" alt="OMO2" width="1076" height="332"></a>
        <span class="generic-public-brand__label"><?= $escape($vt('title')) ?></span>
    </header>
    <?php if ($error !== ''): ?>
    <section class="generic-section generic-section--warning" role="alert"><?= $escape($error) ?></section>
    <?php endif; ?>
    <?php if ($room): ?>
    <section class="generic-soft-panel generic-soft-panel--stack">
        <h1 class="generic-card-title generic-card-title--display"><?= $escape($room->get('title')) ?></h1>
        <?php if ($access): ?>
        <p><?= $escape($vt($access['moderator'] ? 'organizer' : 'participant', ['name' => $access['name']])) ?></p>
        <div class="generic-form-field">
            <label class="generic-form-label" for="visio-share"><?= $escape($vt('share')) ?></label>
            <input id="visio-share" class="generic-form-control" type="url" readonly value="<?= $escape($shareUrl) ?>">
        </div>
        <div class="generic-form-actions">
            <button type="button" class="generic-action-button" data-visio-copy><?= $escape($vt('copy')) ?></button>
            <?php if ($userId > 0): ?><a class="generic-action-button" href="/visio/"><?= $escape($vt('back')) ?></a><?php endif; ?>
        </div>
        <?php elseif ($ready && $userId <= 0): ?>
        <p><?= $escape($vt('guest_intro')) ?></p>
        <a class="generic-action-button" href="<?= $escape($room->invitationPath() . '?login=1') ?>"><?= $escape($vt('login')) ?></a>
        <form method="post" class="generic-form-stack" action="<?= $escape($room->invitationPath()) ?>">
            <input type="hidden" name="_csrf" value="<?= $escape($csrf) ?>">
            <input type="hidden" name="action" value="join">
            <div class="generic-form-field">
                <label class="generic-form-label" for="guest-name"><?= $escape($vt('your_name')) ?></label>
                <input id="guest-name" class="generic-form-control" name="name" maxlength="120" required autocomplete="name" value="<?= $escape($guestName) ?>">
            </div>
            <div class="generic-form-actions"><button class="generic-action-button" type="submit"><?= $escape($vt('join')) ?></button></div>
        </form>
        <?php endif; ?>
    </section>
    <?php if ($access): ?>
    <p id="visio-status" role="status"><?= $escape($vt('loading')) ?></p>
    <div id="visio-frame"></div>
    <p><?= $escape($vt('expiry')) ?></p>
    <?php endif; ?>
    <?php elseif ($ready && $code === '' && $userId > 0): ?>
    <section class="generic-soft-panel generic-soft-panel--stack">
        <h1 class="generic-card-title generic-card-title--display"><?= $escape($vt('title')) ?></h1>
        <p><?= $escape($vt('intro')) ?></p>
        <form method="post" class="generic-form-stack" action="/visio/">
            <input type="hidden" name="_csrf" value="<?= $escape($csrf) ?>">
            <input type="hidden" name="action" value="create">
            <div class="generic-form-field">
                <label class="generic-form-label" for="room-title"><?= $escape($vt('room_title')) ?></label>
                <input id="room-title" class="generic-form-control" name="title" maxlength="190" value="<?= $escape($titleInput) ?>" placeholder="<?= $escape($vt('default_title')) ?>">
            </div>
            <div class="generic-form-actions"><button class="generic-action-button" type="submit"><?= $escape($vt('create')) ?></button></div>
        </form>
    </section>
    <?php if ($rooms): ?>
    <section class="generic-soft-panel generic-soft-panel--stack">
        <h2 class="generic-card-title"><?= $escape($vt('my_rooms')) ?></h2>
        <ul><?php foreach ($rooms as $owned): ?><li><a href="/visio/<?= $escape($owned['code']) ?>"><?= $escape($owned['title']) ?></a></li><?php endforeach; ?></ul>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
