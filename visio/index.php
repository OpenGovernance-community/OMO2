<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/common/visio.php';

use dbObject\VideoRoom;
use dbObject\AuthRateLimit;

$sourceLang = [
    'service_status' => ['text' => 'Etat du service', 'context' => 'Link to the public video service health checks'],
    'contributor_required' => ['text' => 'La creation de salles est reservee aux personnes ou organisations contributrices sur Patreon. Connectez une contribution personnelle valable ou choisissez une organisation contributrice dont vous etes membre.', 'context' => 'Video creation contribution requirement'],
    'room_inactive' => ['text' => 'Cette salle n est plus valide : la contribution Patreon de son createur ou de l organisation associee ne permet plus son utilisation.', 'context' => 'Inactive room explanation shown to owners and guests'],
    'organization_select' => ['text' => 'Choisir une organisation', 'context' => 'Video organization select placeholder'],
    'organization_required' => ['text' => 'Organisation contributrice (obligatoire)', 'context' => 'Organization-funded video creation'],
    'organization_optional' => ['text' => 'Organisation associee (facultatif)', 'context' => 'Personally-funded video creation'],
    'organization_policy' => ['text' => 'Sans contribution personnelle valable, une organisation contributrice dont vous etes membre est obligatoire. Avec une contribution personnelle valable, l organisation est facultative et toutes vos organisations sont disponibles.', 'context' => 'Video organization selection policy'],
    'rejoin' => ['text' => 'Rejoindre de nouveau', 'context' => 'Reload video room after leaving'],
    'audio_muted_on' => ['text' => 'Micro coupe au demarrage', 'context' => 'Meeting list option status'],
    'audio_muted_off' => ['text' => 'Micro actif au demarrage', 'context' => 'Meeting list option status'],
    'video_muted_on' => ['text' => 'Camera eteinte au demarrage', 'context' => 'Meeting list option status'],
    'video_muted_off' => ['text' => 'Camera active au demarrage', 'context' => 'Meeting list option status'],
    'camera_restricted_on' => ['text' => 'Activation de la camera bloquee', 'context' => 'Meeting list option status'],
    'camera_restricted_off' => ['text' => 'Activation de la camera autorisee', 'context' => 'Meeting list option status'],
    'screen_restricted_on' => ['text' => 'Partage ecran sur autorisation individuelle', 'context' => 'Meeting list option status'],
    'screen_restricted_off' => ['text' => 'Partage ecran libre', 'context' => 'Meeting list option status'],
    'lobby_on' => ['text' => 'Salle attente activee', 'context' => 'Meeting list option status'],
    'lobby_off' => ['text' => 'Salle attente desactivee', 'context' => 'Meeting list option status'],
    'password_on' => ['text' => 'Mot de passe defini', 'context' => 'Meeting list option status'],
    'password_off' => ['text' => 'Mot de passe indefini', 'context' => 'Meeting list option status'],
    'delete' => ['text' => 'Supprimer', 'context' => 'Delete owned meeting'],
    'delete_confirm' => ['text' => 'Supprimer cette reunion et son lien definitivement ?', 'context' => 'Deletion confirmation'],
    'title' => ['text' => 'Visioconférence OMO2', 'context' => 'Standalone video room page title'],
    'intro' => ['text' => 'Créez une réunion vidéo avec votre identité OMO2, puis partagez son lien.', 'context' => 'Video room creation introduction'],
    'login' => ['text' => 'Se connecter avec OMO2', 'context' => 'Video room login action'],
    'login_intro' => ['text' => 'Connectez-vous pour créer une réunion ou retrouver votre identité et vos droits.', 'context' => 'Video login introduction'],
    'room_title' => ['text' => 'Nom de la réunion', 'context' => 'Video room title field'],
    'default_title' => ['text' => 'Réunion vidéo', 'context' => 'Default video room title'],
    'create' => ['text' => 'Créer une réunion', 'context' => 'Create video room action'],
    'edit' => ['text' => 'Modifier la salle', 'context' => 'Edit owned video room action'],
    'open' => ['text' => 'Ouvrir la salle', 'context' => 'Open existing video room action'],
    'save' => ['text' => 'Enregistrer les réglages', 'context' => 'Save video room settings'],
    'saved' => ['text' => 'Les réglages de la salle ont été enregistrés.', 'context' => 'Video room settings success notification'],
    'general' => ['text' => 'La salle', 'context' => 'Video room general settings group'],
    'members' => ['text' => 'Membres identifiés via OMO2', 'context' => 'Video room member policy group'],
    'guests' => ['text' => 'Invités', 'context' => 'Video room guest policy group'],
    'owner_note' => ['text' => 'Le propriétaire conserve tous ses droits d’animateur. Ces réglages concernent les prochaines connexions ; pour changer les règles pendant un appel, utilisez les commandes de modération Jitsi.', 'context' => 'Room owner settings and activation guidance'],
    'organization_note' => ['text' => 'Sans organisation, tout compte OMO2 connecté est un membre. Avec une organisation, seuls ses membres actifs le sont ; les autres comptes OMO2 et les personnes non connectées sont des invités.', 'context' => 'Optional organization membership explanation'],
    'policy_note' => ['text' => 'Cochez les options à appliquer à ce groupe. Une option de démarrage ne force jamais l’ouverture du micro ou de la caméra sans l’accord de la personne. Une caméra bloquée reste éteinte ; le partage sur autorisation permet une demande individuelle.', 'context' => 'Role-specific room options explanation'],
    'guest_role' => ['text' => 'Invité : {name}', 'context' => 'Identified or anonymous guest room identity'],
    'camera_blocked' => ['text' => 'La caméra est désactivée pour votre groupe, y compris avant l’entrée dans la salle.', 'context' => 'Persistent room camera restriction explanation'],
    'guest_direct' => ['text' => 'Vous pouvez vous connecter avec OMO2 ou ouvrir la réunion comme invité.', 'context' => 'Guest invitation with no lobby'],
    'forbidden' => ['text' => 'Seul le propriétaire peut modifier cette salle.', 'context' => 'Room settings ownership error'],
    'invalid_settings' => ['text' => 'Vérifiez les options de la salle.', 'context' => 'Room settings validation'],
    'invalid_organization' => ['text' => 'Choisissez une organisation dont vous êtes membre actif, ou laissez ce champ vide.', 'context' => 'Room organization validation'],
    'password_title' => ['text' => 'Mot de passe Jitsi', 'context' => 'Native Jitsi room password settings heading'],
    'password_label' => ['text' => 'Mot de passe', 'context' => 'Room lobby password field'],
    'password_help' => ['text' => 'Ce mot de passe se saisit directement dans la salle d’attente Jitsi pour entrer sans attendre l’acceptation d’un animateur. Les autres droits restent inchangés. Sans mot de passe, la demande d’admission reste possible.', 'context' => 'Native Jitsi lobby password behavior'],
    'password_configured' => ['text' => 'Un mot de passe est déjà défini. Laissez le champ vide pour le conserver.', 'context' => 'Masked room password retention guidance'],
    'password_empty' => ['text' => 'Aucun mot de passe n’est défini. Laissez le champ vide pour garder ce fonctionnement.', 'context' => 'Room without lobby bypass password'],
    'password_remove' => ['text' => 'Supprimer le mot de passe', 'context' => 'Remove room lobby password checkbox'],
    'room_password_reset_required' => ['text' => 'Le propriétaire doit redéfinir le mot de passe de cette salle pour l’utiliser dans Jitsi.', 'context' => 'Old non-recoverable room password must be reset for native Jitsi'],
    'invalid_room_password' => ['text' => 'Choisissez un mot de passe plus court. Pour le supprimer, cochez la case et laissez le champ vide.', 'context' => 'Room password settings validation'],
    'my_rooms' => ['text' => 'Mes dernières réunions', 'context' => 'Owned video room list heading'],
    'back' => ['text' => 'Mes réunions', 'context' => 'Return to video room creation'],
    'organizer' => ['text' => 'Animateur : {name}', 'context' => 'Video organizer identity'],
    'participant' => ['text' => 'Participant : {name}', 'context' => 'Video participant identity'],
    'share' => ['text' => 'Lien à partager avec les participants', 'context' => 'Public invitation link label'],
    'copy' => ['text' => 'Copier le lien', 'context' => 'Copy invitation action'],
    'copied' => ['text' => 'Lien copié.', 'context' => 'Copy invitation success notification'],
    'copy_failed' => ['text' => 'La copie a échoué. Sélectionnez et copiez le lien affiché.', 'context' => 'Copy invitation failure notification'],
    'join' => ['text' => 'Rejoindre comme invite', 'context' => 'Video guest join action'],
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
$ready = false;
$roomActive = false;
$personalContribution = false;
$creationOrganizations = [];
$canCreate = false;
$editing = isset($_GET['edit']) || ($_POST['action'] ?? '') === 'update';
$returnTo = '/visio/' . ($code !== '' ? rawurlencode($code) . '?login=1' : '');

if ((!$code || isset($_GET['login'])) && $userId <= 0) {
    $loginOrganization = commonResolveOrganizationContext(1);
    if (commonGetRequestSubdomain(commonGetRequestHost()) === '' && commonGetRequestedOrganizationId() <= 0) {
        $loginOrganization['domain'] = '';
    }
    commonRenderMagicLoginPage(['title' => $vt('title'), 'appName' => 'OMO2', 'intro' => $vt('login_intro'),
        'returnTo' => $returnTo, 'organization' => $loginOrganization, 'layout' => 'public',
        'headHtml' => commonStylesheetTags('/common/assets/components.css')]);
    exit;
}

try {
    if (!$config['enabled']) { http_response_code(503); throw new RuntimeException('not_configured'); }
    if (!VideoRoom::isStorageAvailable()) { http_response_code(503); throw new RuntimeException('storage_error'); }
    $ready = true;
    if ($userId > 0) {
        $personalContribution = VideoRoom::hasPersonalContribution($userId);
        $creationOrganizations = VideoRoom::creationOrganizations($userId);
        $canCreate = $personalContribution || count($creationOrganizations) > 0;
    }
    if ($code !== '') {
        $room = VideoRoom::findByInvitation($code);
        if (!$room) { http_response_code(404); throw new RuntimeException('not_found'); }
        $roomActive = $room->hasValidContribution();
    }
    if ($editing && (!$room || !$room->isOwner($userId))) { http_response_code(403); throw new RuntimeException('forbidden'); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['_csrf'] ?? null) || !hash_equals($csrf, $_POST['_csrf'])) { http_response_code(403); throw new RuntimeException('csrf'); }
        $limit = AuthRateLimit::consume('visio-submit', commonAuthHashIdentifier('visio-ip', commonGetRequestIp()), 30, 300);
        if (empty($limit['allowed'])) { http_response_code(429); throw new RuntimeException('rate'); }
        if ($room && ($_POST['action'] ?? '') === 'delete') {
            $room->deleteForCurrentUser();
            header('Location: /visio/', true, 303);
            exit;
        }
        if ($room && ($_POST['action'] ?? '') === 'update') {
            $room->updateForCurrentUser($_POST);
            $_SESSION['visio_saved_room'] = $code;
            header('Location: ' . $room->invitationPath() . '?edit=1', true, 303);
            exit;
        }
        if (!$room && ($_POST['action'] ?? '') === 'create') {
            if (isset($_POST['title']) && !is_string($_POST['title'])) throw new RuntimeException('invalid_title');
            $titleInput = trim((string)($_POST['title'] ?? ''));
            $room = VideoRoom::createForCurrentUser($titleInput !== '' ? $titleInput : $vt('default_title'), $_POST['IDorganization'] ?? null);
            header('Location: ' . $room->invitationPath(), true, 303);
            exit;
        }
        if ($room && ($_POST['action'] ?? '') === 'join') {
            if (!$roomActive) { http_response_code(403); throw new RuntimeException('room_inactive'); }
            header('Location: ' . $room->invitationPath() . '?join=1', true, 303);
            exit;
        }
        http_response_code(400);
        throw new RuntimeException('not_found');
    }
    if (!$editing && $room && !$roomActive) { http_response_code(403); throw new RuntimeException('room_inactive'); }
    if (!$editing && $room && ($userId > 0 || isset($_GET['join']))) {
        $access = visioIssueAccess($room, $userId);
    }
    if (!$room && $userId > 0) $rooms = VideoRoom::listForCurrentUser();
} catch (RuntimeException $exception) {
    $key = $exception->getMessage();
    if (!isset($sourceLang[$key])) $key = 'storage_error';
    $error = $vt($key);
    if (http_response_code() < 400) http_response_code($key === 'storage_error' ? 503 : ($key === 'contributor_required' ? 403 : 400));
}

$shareUrl = $room ? commonBuildUrl($room->invitationPath()) : '';
$saved = $room && $room->isOwner($userId) && ($_SESSION['visio_saved_room'] ?? '') === $code;
if ($saved) unset($_SESSION['visio_saved_room']);
$messages = ['copied' => $vt('copied'), 'copyFailed' => $vt('copy_failed'), 'ended' => $vt('ended'), 'error' => $vt('video_failed'), 'saved' => $vt('saved')];
$client = ['messages' => $messages, 'saved' => $saved];
if ($access) {
    // Ancestor policy prevents camera capture even in Jitsi's prejoin/lobby screen.
    if (!empty($access['settings']['camera_restricted'])) header('Permissions-Policy: camera=()');
    $parsedJitsi = parse_url($config['url']);
    $client = ['domain' => $parsedJitsi['host'] . (isset($parsedJitsi['port']) ? ':' . $parsedJitsi['port'] : ''),
        'room' => $room->jitsiRoomName(), 'title' => (string)$room->get('title'), 'access' => $access,
        'language' => commonAuthGetTranslationLocale(), 'url' => $config['url'],
        'messages' => $messages, 'saved' => $saved];
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
    <script id="visio-config" type="application/json"><?= json_encode($client, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
    <?php if ($access): ?>
    <script src="<?= $escape($config['url'] . '/external_api.js') ?>" defer></script>
    <?php endif; ?>
    <script src="<?= $escape(commonAssetUrl('/visio/visio.js')) ?>" defer></script>
    <?php if ($editing && $room && $room->isOwner($userId)): ?><script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><?php endif; ?>
</head>
<body class="generic-public-page">
<main class="generic-page-shell generic-stack<?= $access ? ' generic-page-shell--compact-bottom' : '' ?>">
    <header class="generic-public-brand">
        <a href="/visio/"><img class="generic-public-brand__logo" src="/img/omo2/logo-omo-dark.png" alt="OMO2" width="1076" height="332"></a>
        <span class="generic-public-brand__label"><?= $escape($vt('title')) ?></span>
    </header>
    <?php if (!$room): ?><p><a href="/visio/status.php"><?= $escape($vt('service_status')) ?></a></p><?php endif; ?>
    <?php if ($error !== ''): ?>
    <section class="generic-section generic-section--warning" role="alert"><?= $escape($error) ?></section>
    <?php endif; ?>
    <?php if ($room): ?>
    <section class="generic-soft-panel generic-soft-panel--stack">
        <h1 class="generic-card-title generic-card-title--display"><?= $escape($room->get('title')) ?></h1>
        <?php if (!$editing && trim((string)$room->get('description')) !== ''): ?>
        <p class="visio-description-full"><?= nl2br($escape($room->get('description'))) ?></p>
        <?php endif; ?>
        <?php if ($editing && $room->isOwner($userId)): ?>
        <p class="generic-description"><?= $escape($vt('owner_note')) ?></p>
        <?php ob_start(); ?>
        <fieldset class="generic-fieldset">
            <legend class="generic-card-title generic-card-title--small"><?= $escape($vt('password_title')) ?></legend>
            <div class="generic-fieldset__body">
                <p class="generic-help-text"><?= $escape($vt('password_help')) ?></p>
                <div class="generic-form-field">
                    <label class="generic-form-label" for="room-password"><?= $escape($vt('password_label')) ?></label>
                    <input id="room-password" class="generic-form-control" type="password" name="room_password" autocomplete="new-password" maxlength="72">
                    <p class="generic-help-text"><?= $escape($vt($room->hasLobbyPassword() ? (str_starts_with((string)$room->get('lobby_password_encrypted'), 'v1:') ? 'password_configured' : 'room_password_reset_required') : 'password_empty')) ?></p>
                </div>
                <?php if ($room->hasLobbyPassword()): ?>
                <label class="generic-checkbox"><input type="checkbox" name="remove_room_password" value="1"><span><?= $escape($vt('password_remove')) ?></span></label>
                <?php endif; ?>
            </div>
        </fieldset>
        <input type="hidden" name="action" value="update">
        <?php $passwordFields = ob_get_clean(); $room->display('adminEdit.php', [
            'buttons' => false, 'bindFormScripts' => false, 'includeComponentAssets' => false,
            'action' => $room->invitationPath() . '?edit=1',
            'sections' => [
                ['title' => $vt('general'), 'description' => $vt('organization_note') . ' ' . $vt('organization_policy'), 'layout' => 'two-thirds', 'fullWidthFields' => ['description'], 'fields' => ['title', 'IDorganization', 'description']],
                ['title' => $vt('members'), 'description' => $vt('policy_note'), 'layout' => 'pair', 'fields' => array_values(array_filter(array_keys(VideoRoom::settingDefaults()), static fn($key) => str_starts_with($key, 'member_')))],
                ['title' => $vt('guests'), 'description' => $vt('policy_note'), 'layout' => 'pair', 'fields' => array_values(array_filter(array_keys(VideoRoom::settingDefaults()), static fn($key) => str_starts_with($key, 'guest_')))],
            ],
            'afterTableHtml' => $passwordFields,
        ]); ?>
        <div class="generic-form-actions">
            <button class="generic-action-button" type="submit" form="formulaire-edit"><?= $escape($vt('save')) ?></button>
            <a class="generic-action-button" href="<?= $escape($room->invitationPath()) ?>"><?= $escape($vt('open')) ?></a>
            <a class="generic-action-button" href="/visio/"><?= $escape($vt('back')) ?></a>
        </div>
        <?php elseif ($access): ?>
        <?php if ($access['name'] !== ''): ?><p><?= $escape($vt($access['moderator'] ? 'organizer' : ($access['role'] === 'guest' ? 'guest_role' : 'participant'), ['name' => $access['name']])) ?></p><?php endif; ?>
        <?php if (!empty($access['settings']['camera_restricted'])): ?><p class="generic-help-text"><?= $escape($vt('camera_blocked')) ?></p><?php endif; ?>
        <div class="generic-form-field">
            <label class="generic-form-label" for="visio-share"><?= $escape($vt('share')) ?></label>
            <input id="visio-share" class="generic-form-control" type="url" readonly value="<?= $escape($shareUrl) ?>">
        </div>
        <div class="generic-form-actions">
            <button type="button" class="generic-action-button" data-visio-copy><?= $escape($vt('copy')) ?></button>
            <?php if ($room->isOwner($userId)): ?><a class="generic-action-button" href="<?= $escape($room->invitationPath() . '?edit=1') ?>"><?= $escape($vt('edit')) ?></a><?php endif; ?>
            <?php if ($userId > 0): ?><a class="generic-action-button" href="/visio/"><?= $escape($vt('back')) ?></a><?php endif; ?>
        </div>
        <?php elseif ($roomActive && $ready && $userId <= 0): ?>
        <p><?= $escape($vt($room->policy()['guest']['lobby'] ? 'guest_intro' : 'guest_direct')) ?></p>
        <div class="generic-form-actions">
        <a class="generic-action-button" href="<?= $escape($room->invitationPath() . '?login=1') ?>"><?= $escape($vt('login')) ?></a>
        <form method="post" class="generic-form-stack" action="<?= $escape($room->invitationPath()) ?>">
            <input type="hidden" name="_csrf" value="<?= $escape($csrf) ?>">
            <input type="hidden" name="action" value="join">
            <div class="generic-form-actions"><button class="generic-action-button" type="submit"><?= $escape($vt('join')) ?></button></div>
        </form>
        </div>
        <?php elseif ($room->isOwner($userId)): ?>
        <a class="generic-action-button" href="<?= $escape($room->invitationPath() . '?edit=1') ?>"><?= $escape($vt('edit')) ?></a>
        <?php endif; ?>
    </section>
    <?php if ($access): ?>
    <div class="generic-form-actions" id="visio-status-row" hidden>
    <p id="visio-status" role="status" hidden></p>
    <div id="visio-rejoin" class="generic-form-actions" hidden><button class="generic-action-button generic-action-button--main" type="button" data-visio-reload><?= $escape($vt('rejoin')) ?></button></div>
    </div>
    <p class="generic-help-text"><?= $escape($vt('expiry')) ?></p>
    <?php endif; ?>
    <?php elseif ($ready && $code === '' && $userId > 0): ?>
    <section class="generic-soft-panel generic-soft-panel--stack">
        <h1 class="generic-card-title generic-card-title--display"><?= $escape($vt('title')) ?></h1>
        <p><?= $escape($vt('intro')) ?></p>
        <?php if (!$canCreate): ?><p><?= $escape($vt('contributor_required')) ?></p><?php else: ?>
        <form method="post" class="generic-form-grid generic-form-grid--inline" style="--param-form-grid-columns: minmax(0, 1fr) auto" action="/visio/">
            <input type="hidden" name="_csrf" value="<?= $escape($csrf) ?>">
            <input type="hidden" name="action" value="create">
            <div class="generic-form-field">
                <label class="generic-form-label" for="room-title"><?= $escape($vt('room_title')) ?></label>
                <input id="room-title" class="generic-form-control" name="title" maxlength="190" value="<?= $escape($titleInput) ?>" placeholder="<?= $escape($vt('default_title')) ?>">
            </div>
            <div class="generic-form-actions"><button class="generic-action-button generic-action-button--form-control generic-action-button--main" type="submit"><?= $escape($vt('create')) ?></button></div>
            <div class="generic-form-field generic-form-field--full">
                <label class="generic-form-label" for="create-organization"><?= $escape($vt($personalContribution ? 'organization_optional' : 'organization_required')) ?></label>
                <select class="generic-form-control" id="create-organization" name="IDorganization"<?= $personalContribution ? '' : ' required' ?>>
                    <option value=""><?= $escape($vt('organization_select')) ?></option>
                    <?php foreach ($creationOrganizations as $organization): ?><option value="<?= (int)$organization->getId() ?>"<?= is_scalar($_POST['IDorganization'] ?? null) && (string)$_POST['IDorganization'] === (string)$organization->getId() ? ' selected' : '' ?>><?= $escape($organization->get('name')) ?></option><?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php endif; ?>
    </section>
    <?php if ($rooms): ?>
    <section class="generic-soft-panel generic-soft-panel--stack">
        <h2 class="generic-card-title"><?= $escape($vt('my_rooms')) ?></h2>
        <div class="generic-stack"><?php foreach ($rooms as $owned): ?>
        <article class="generic-section generic-stack<?= $owned['contribution_active'] ? '' : ' generic-section--inactive' ?>">
            <div class="visio-heading">
                <h3 class="generic-card-title"><?php if ($owned['contribution_active']): ?><a href="/visio/<?= $escape($owned['code']) ?>"><?= $escape($owned['title']) ?></a><?php else: ?><?= $escape($owned['title']) ?><?php endif; ?></h3>
                <?php if (!empty($owned['organization_name'])):
                    $organizationColor = trim((string)($owned['organization_color'] ?? ''));
                    if (!preg_match('/\A(?:#[a-fA-F0-9]{3,8}|[a-zA-Z]{1,20})\z/D', $organizationColor)) $organizationColor = '#4f46e5'; ?>
                <span class="generic-badge generic-badge--compact" style="--param-badge-color: <?= $escape($organizationColor) ?>"><?= $escape($owned['organization_name']) ?></span>
                <?php endif; ?>
                <span class="visio-description" title="<?= $escape($owned['description'] ?? '') ?>"><?= $escape($owned['description'] ?? '') ?></span>
            </div>
            <?php if (!$owned['contribution_active']): ?><p class="generic-help-text"><?= $escape($vt('room_inactive')) ?></p><?php endif; ?>
            <div class="visio-room-controls">
            <div class="visio-policies">
            <?php foreach (['member' => 'members', 'guest' => 'guests'] as $group => $label): ?>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <h4 class="generic-card-title generic-card-title--small"><?= $escape($vt($label)) ?></h4>
                    <div class="visio-icons">
                    <?php foreach (['audio_muted' => 'micro', 'video_muted' => 'camera', 'camera_restricted' => 'camera-blocked', 'screen_restricted' => 'screen', 'lobby' => 'lobby'] as $key => $icon):
                        $field = $group . '_' . $key;
                        $active = (bool)($owned[$field] ?? VideoRoom::settingDefaults()[$field]);
                        $description = $vt($key . ($active ? '_on' : '_off')); ?>
                        <img class="visio-setting<?= $active ? ' is-active' : '' ?>" src="/visio/icons/<?= $escape($icon) ?>.png" width="20" height="20" alt="<?= $escape($description) ?>" title="<?= $escape($description) ?>">
                    <?php endforeach; ?>
                    <?php $passwordDefined = (string)($owned['lobby_password_encrypted'] ?? '') !== '';
                        $passwordDescription = $vt($passwordDefined ? 'password_on' : 'password_off'); ?>
                        <img class="visio-setting<?= $passwordDefined ? ' is-active' : '' ?>" src="/visio/icons/lock.png" width="20" height="20" alt="<?= $escape($passwordDescription) ?>" title="<?= $escape($passwordDescription) ?>">
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <div class="generic-form-actions visio-room-actions">
                <a class="generic-action-button" href="/visio/<?= $escape($owned['code']) ?>?edit=1"><?= $escape($vt('edit')) ?></a>
                <form method="post" action="/visio/<?= $escape($owned['code']) ?>" data-visio-delete data-confirm="<?= $escape($vt('delete_confirm')) ?>">
                    <input type="hidden" name="_csrf" value="<?= $escape($csrf) ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="generic-action-button" type="submit"><?= $escape($vt('delete')) ?></button>
                </form>
            </div>
            </div>
        </article>
        <?php endforeach; ?></div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</main>
<?php if ($access): ?>
<div id="visio-stage"><div id="visio-frame"></div></div>
<?php endif; ?>
</body>
</html>
