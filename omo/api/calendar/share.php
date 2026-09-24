<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\CalendarShare;

$sourceLang = [
    'intro' => ['text' => 'Partagez tous vos agendas personnels : événements OMO de vos organisations et agendas externes connectés.', 'context' => 'Scope of a personal calendar subscription.'],
    'privacy' => ['text' => 'Toute personne possédant le lien peut le consulter. Sa révocation bloque les prochaines lectures, mais ne supprime pas les copies déjà téléchargées.', 'context' => 'Capability URL privacy and revocation warning.'],
    'subscription' => ['text' => 'Ajoutez le lien comme abonnement dans un agenda plutôt que d’importer le fichier une seule fois. Les agendas externes utilisent la dernière synchronisation ; la fréquence de lecture dépend de l’application abonnée.', 'context' => 'How to subscribe and how freshness works.'],
    'instructions' => ['text' => 'Comment utiliser le lien ?', 'context' => 'Disclosure for calendar subscription instructions.'],
    'new' => ['text' => 'Nouveau lien', 'context' => 'Calendar share creation heading.'],
    'label' => ['text' => 'Nom du partage', 'context' => 'Private label to identify the recipient of a sharing link.'],
    'label_hint' => ['text' => 'Par exemple : famille. Ce nom reste privé.', 'context' => 'Private sharing label help.'],
    'months' => ['text' => 'Visibilité des événements', 'context' => 'Rolling forward calendar horizon.'],
    'months_value' => ['text' => '{count} mois', 'context' => 'Number of months available in a calendar share.'],
    'months_hint' => ['text' => 'Fenêtre glissante à partir d’aujourd’hui, sans historique.', 'context' => 'Clarify the start of the visibility window.'],
    'mode' => ['text' => 'Informations partagées', 'context' => 'Calendar share detail level.'],
    'busy' => ['text' => 'Occupé uniquement', 'context' => 'Hide event titles, descriptions and locations.'],
    'clear' => ['text' => 'En clair : titres, descriptions et lieux', 'context' => 'Explicit consent to disclose event content.'],
    'expiration' => ['text' => 'Date d’expiration', 'context' => 'Optional subscription expiration date.'],
    'expiration_hint' => ['text' => 'Laissez vide pour ne pas limiter la durée du lien. Sinon, il expire à la fin de la journée choisie.', 'context' => 'Expiration date semantics.'],
    'create' => ['text' => 'Créer le lien', 'context' => 'Create a new calendar share.'],
    'list' => ['text' => 'Mes liens de partage', 'context' => 'Existing calendar sharing links.'],
    'empty' => ['text' => 'Aucun lien de partage pour le moment.', 'context' => 'Empty share list.'],
    'link' => ['text' => 'Lien d’abonnement ICS', 'context' => 'Label for the secret calendar URL.'],
    'copy' => ['text' => 'Copier le lien', 'context' => 'Copy ICS URL.'],
    'copied' => ['text' => 'Lien copié.', 'context' => 'Clipboard success.'],
    'copy_manual' => ['text' => 'Sélectionnez et copiez le lien.', 'context' => 'Clipboard unavailable fallback.'],
    'revoke' => ['text' => 'Révoquer', 'context' => 'Immediately disable a calendar sharing capability.'],
    'revoked' => ['text' => 'Révoqué', 'context' => 'Revoked subscription state.'],
    'expired' => ['text' => 'Expiré', 'context' => 'Expired subscription state.'],
    'unlimited' => ['text' => 'Sans expiration', 'context' => 'Subscription has no expiry.'],
    'expires' => ['text' => 'Expire le {date}', 'context' => 'Last day on which the subscription is usable.'],
    'created' => ['text' => 'Lien créé. Vous pouvez maintenant le copier.', 'context' => 'Successful creation.'],
    'removed' => ['text' => 'Lien révoqué. Il ne donne plus accès à votre agenda.', 'context' => 'Successful revocation.'],
    'loading' => ['text' => 'Chargement...', 'context' => 'Share panel loading indicator.'],
    'saving' => ['text' => 'Enregistrement...', 'context' => 'Share update progress.'],
    'storage' => ['text' => 'Le partage est indisponible pour le moment.', 'context' => 'Unavailable calendar share storage.'],
    'invalid' => ['text' => 'Indiquez un nom de 100 caractères maximum et une durée de 1 à 12 mois.', 'context' => 'Invalid calendar share settings.'],
    'expiration_invalid' => ['text' => 'Choisissez une date d’expiration valide, aujourd’hui ou plus tard.', 'context' => 'Invalid expiry date.'],
    'missing' => ['text' => 'Lien introuvable.', 'context' => 'Share absent or not owned by user.'],
    'csrf' => ['text' => 'Rechargez cette fenêtre puis réessayez.', 'context' => 'Invalid CSRF token.'],
];
$lang = omoLoadTranslationBundle('omo_calendar_share', $sourceLang);
function calendarShareT($key, array $replace = [])
{
    global $sourceLang, $lang;
    return t($key, $replace, $lang, $sourceLang);
}

header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
$userId = (int)commonGetCurrentUserId();
if ($userId <= 0) { http_response_code(403); exit; }
if (!CalendarShare::isStorageAvailable()) { http_response_code(503); exit(omoApiEscape(calendarShareT('storage'))); }
$_SESSION['calendar_share_csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['calendar_share_csrf'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    try {
        if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { http_response_code(403); throw new RuntimeException('csrf'); }
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'create') {
            $months = filter_var($_POST['months'] ?? '', FILTER_VALIDATE_INT);
            $mode = (string)($_POST['mode'] ?? '');
            if ($months === false || !in_array($mode, ['busy', 'clear'], true)) { throw new InvalidArgumentException('invalid'); }
            CalendarShare::createForUser($userId, (string)($_POST['label'] ?? ''), $months, $mode === 'clear', (string)($_POST['expiration'] ?? ''));
        } elseif ($action === 'revoke') {
            CalendarShare::revokeForUser((int)($_POST['id'] ?? 0), $userId);
        } else { throw new InvalidArgumentException('invalid'); }
        echo json_encode(['status' => true, 'message' => calendarShareT($action === 'create' ? 'created' : 'removed')]);
    } catch (Throwable $exception) {
        $key = $exception->getMessage();
        if (!in_array($key, ['invalid', 'expiration_invalid', 'missing', 'csrf'], true)) { $key = 'storage'; }
        echo json_encode(['status' => false, 'message' => calendarShareT($key)]);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); header('Allow: GET, POST'); exit; }
$shares = CalendarShare::forUser($userId);
$text = [];
foreach (['saving', 'storage', 'copied', 'copy_manual'] as $key) { $text[$key] = calendarShareT($key); }
?>
<div class="generic-drawer-content generic-form-stack" data-topbar-modal-max-width="760px" data-calendar-share data-text="<?= omoApiEscape(json_encode($text)) ?>" data-csrf="<?= omoApiEscape($csrf) ?>">
    <p class="generic-help-text"><?= omoApiEscape(calendarShareT('intro')) ?></p>
    <section class="generic-form-section generic-form-section--divided generic-form-stack">
        <p class="generic-help-text"><?= omoApiEscape(calendarShareT('privacy')) ?></p>
        <details class="generic-accordion"><summary><?= omoApiEscape(calendarShareT('instructions')) ?></summary><div class="generic-accordion__content"><p class="generic-help-text"><?= omoApiEscape(calendarShareT('subscription')) ?></p></div></details>
    </section>
    <p class="generic-feedback generic-feedback--collapse-empty" data-calendar-share-feedback aria-live="polite"></p>
    <form class="generic-form-section generic-form-section--divided generic-form-stack" data-calendar-share-create method="post">
        <h3 class="generic-card-title"><?= omoApiEscape(calendarShareT('new')) ?></h3>
        <input type="hidden" name="csrf" value="<?= omoApiEscape($csrf) ?>">
        <input type="hidden" name="action" value="create">
        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(calendarShareT('label')) ?></span>
            <input class="generic-form-control" name="label" maxlength="100" required>
            <span class="generic-help-text"><?= omoApiEscape(calendarShareT('label_hint')) ?></span>
        </label>
        <div class="generic-form-grid">
            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(calendarShareT('months')) ?></span>
                <select class="generic-form-control" name="months"><?php for ($months = 1; $months <= 12; $months++): ?>
                    <option value="<?= $months ?>" <?= $months === 3 ? 'selected' : '' ?>><?= omoApiEscape(calendarShareT('months_value', ['count' => $months])) ?></option>
                <?php endfor; ?></select>
                <span class="generic-help-text"><?= omoApiEscape(calendarShareT('months_hint')) ?></span>
            </label>
            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(calendarShareT('mode')) ?></span>
                <select class="generic-form-control" name="mode"><option value="busy"><?= omoApiEscape(calendarShareT('busy')) ?></option><option value="clear"><?= omoApiEscape(calendarShareT('clear')) ?></option></select>
            </label>
        </div>
        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(calendarShareT('expiration')) ?></span>
            <input class="generic-form-control" type="date" name="expiration" min="<?= date('Y-m-d') ?>">
            <span class="generic-help-text"><?= omoApiEscape(calendarShareT('expiration_hint')) ?></span>
        </label>
        <div class="generic-form-actions generic-form-actions--sticky"><button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(calendarShareT('create')) ?></button></div>
    </form>
    <h3 class="generic-card-title"><?= omoApiEscape(calendarShareT('list')) ?></h3>
    <?php if (!$shares): ?><p class="generic-help-text"><?= omoApiEscape(calendarShareT('empty')) ?></p><?php endif; ?>
    <?php foreach ($shares as $share): $usable = $share->isUsable(); $expires = $share->get('expires_at'); ?>
        <section class="generic-soft-panel generic-soft-panel--stack" data-calendar-share-row>
            <h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape($share->get('label')) ?></h4>
            <p class="generic-help-text"><?= omoApiEscape(calendarShareT('months_value', ['count' => $share->get('months')])) ?> &middot; <?= omoApiEscape(calendarShareT($share->get('details') ? 'clear' : 'busy')) ?> &middot;
                <?= omoApiEscape(!$share->get('active') ? calendarShareT('revoked') : (!$usable ? calendarShareT('expired') : ($expires ? calendarShareT('expires', ['date' => DateTimeImmutable::createFromInterface($expires)->modify('-1 day')->format('d.m.Y')]) : calendarShareT('unlimited')))) ?></p>
            <?php if ($usable): ?>
                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(calendarShareT('link')) ?></span>
                    <input class="generic-form-control" readonly data-calendar-share-link value="<?= omoApiEscape(appGetCurrentSiteBaseUrl() . '/calendar/share/' . $share->get('token') . '.ics') ?>">
                </label>
                <div class="generic-form-actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-calendar-share-copy><?= omoApiEscape(calendarShareT('copy')) ?></button>
                    <button type="button" class="generic-action-button generic-action-button--danger" data-calendar-share-revoke="<?= (int)$share->getId() ?>"><?= omoApiEscape(calendarShareT('revoke')) ?></button>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
