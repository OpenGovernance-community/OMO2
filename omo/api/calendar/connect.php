<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/caldav.php';
require_once dirname(__DIR__, 3) . '/common/external_calendar.php';

use dbObject\ArrayExternalCalendar;
use dbObject\ExternalCalendar;
use dbObject\User;

$sourceLang = [
    'calendar.connect.popup.title' => ['text' => 'Connecter le calendrier', 'context' => 'Title of the calendar connection popup.'],
    'calendar.connect.tab.omo' => ['text' => 'Connecter OMO', 'context' => 'Tab with OMO CalDAV details.'],
    'calendar.connect.tab.external' => ['text' => 'Ajouter un agenda externe', 'context' => 'Tab used to connect a personal external CalDAV calendar.'],
    'calendar.connect.context' => ['text' => 'Holon de base', 'context' => 'Label for the base holon of a scoped CalDAV calendar.'],
    'calendar.connect.visibility' => ['text' => 'Visibilite', 'context' => 'Label for the visibility range of a scoped CalDAV calendar.'],
    'calendar.connect.scope.contextual' => ['text' => 'Contexte courant', 'context' => 'CalDAV range label for the current holon only.'],
    'calendar.connect.scope.children' => ['text' => 'Enfants directs', 'context' => 'CalDAV range label for the current holon and its direct children.'],
    'calendar.connect.scope.descendants' => ['text' => 'Descendants', 'context' => 'CalDAV range label for the current holon and all descendants.'],
    'calendar.connect.password_missing' => ['text' => 'Pour connecter ce calendrier, configurez d abord un mot de passe dans le menu Profil.', 'context' => 'Message shown instead of OMO CalDAV credentials when the account has no password.'],
    'calendar.connect.password_missing_detail' => ['text' => 'Le mot de passe OMO est necessaire pour autoriser la synchronisation CalDAV depuis votre telephone ou votre ordinateur.', 'context' => 'Additional explanation when the OMO account has no password.'],
    'calendar.connect.url' => ['text' => 'Adresse du serveur CalDAV', 'context' => 'Label for the scoped OMO CalDAV URL.'],
    'calendar.connect.color' => ['text' => 'Couleur du calendrier', 'context' => 'Label for a calendar color.'],
    'calendar.connect.username' => ['text' => 'Identifiant', 'context' => 'Label for a CalDAV login identifier.'],
    'calendar.connect.copy' => ['text' => 'Copier', 'context' => 'Button that copies a URL.'],
    'calendar.connect.steps.title' => ['text' => 'Marche a suivre', 'context' => 'Heading before the OMO connection instructions.'],
    'calendar.connect.steps' => ['text' => 'Dans les reglages de votre telephone ou de votre application de calendrier, ajoutez un compte CalDAV. Saisissez l adresse ci dessus, votre identifiant et votre mot de passe OMO. Deux agendas seront proposes : Modifiables contient les evenements que vous pouvez modifier, tandis que Lecture seule contient les autres evenements visibles.', 'context' => 'Complete OMO CalDAV connection instructions.'],
    'calendar.connect.external.title' => ['text' => 'Agendas CalDAV', 'context' => 'Heading for the external calendar connection form.'],
    'calendar.connect.external.intro' => ['text' => 'Connectez votre compte Nextcloud ou Infomaniak, puis choisissez les agendas a afficher. Les evenements restent prives et en lecture seule dans OMO.', 'context' => 'Introductory copy for external calendar discovery.'],
    'calendar.connect.external.name' => ['text' => 'Nom dans OMO', 'context' => 'Label for an external calendar display name.'],
    'calendar.connect.external.url' => ['text' => 'Adresse du serveur CalDAV', 'context' => 'Label for a CalDAV discovery URL.'],
    'calendar.connect.external.url_hint' => ['text' => 'Nextcloud : https://votre-cloud/remote.php/dav ; Infomaniak : https://sync.infomaniak.com. Un lien direct vers un agenda fonctionne aussi.', 'context' => 'Examples of supported CalDAV discovery entry points.'],
    'calendar.connect.external.password' => ['text' => 'Mot de passe d application', 'context' => 'Label for a Nextcloud app password.'],
    'calendar.connect.external.submit' => ['text' => 'Ajouter et synchroniser', 'context' => 'Button that saves and synchronizes an external calendar.'],
    'calendar.connect.external.empty' => ['text' => 'Aucun agenda externe connecte.', 'context' => 'Empty state for external calendars.'],
    'calendar.connect.external.sync' => ['text' => 'Synchroniser', 'context' => 'Button that manually synchronizes an external calendar.'],
    'calendar.connect.external.delete' => ['text' => 'Retirer', 'context' => 'Button that removes an external calendar.'],
    'calendar.connect.external.last_sync' => ['text' => 'Derniere synchronisation', 'context' => 'Label for a calendar last sync time.'],
    'calendar.connect.external.never' => ['text' => 'Jamais', 'context' => 'Fallback for a calendar that has not synchronized.'],
    'calendar.connect.external.error' => ['text' => 'Erreur', 'context' => 'Label for a calendar synchronization error.'],
    'calendar.connect.external.search' => ['text' => 'Rechercher les agendas', 'context' => 'Button starting CalDAV discovery.'],
    'calendar.connect.external.searching' => ['text' => 'Recherche des agendas...', 'context' => 'Discovery in progress.'],
    'calendar.connect.external.selection' => ['text' => 'Agendas disponibles', 'context' => 'Heading of discovery results.'],
    'calendar.connect.external.select' => ['text' => 'Afficher cet agenda', 'context' => 'Checkbox selecting a discovered calendar.'],
    'calendar.connect.external.connected' => ['text' => 'Deja connecte', 'context' => 'Badge for a previously connected calendar.'],
    'calendar.connect.external.connect_selected' => ['text' => 'Connecter les agendas selectionnes', 'context' => 'Button saving the selected calendars.'],
    'calendar.connect.external.choose' => ['text' => 'Cochez au moins un agenda.', 'context' => 'No discovered calendar selected.'],
    'calendar.connect.external.pending' => ['text' => 'Connexion et synchronisation...', 'context' => 'Selected calendars being saved.'],
    'calendar.connect.external.finished' => ['text' => 'Traitement termine. Consultez le resultat sous chaque agenda.', 'context' => 'All selected calendars have been processed.'],
    'calendar.connect.external.failed' => ['text' => 'Operation impossible. Reessayez.', 'context' => 'Network or unexpected calendar error.'],
    'calendar.connect.external.confirm_delete' => ['text' => 'Retirer cet agenda externe d OMO ?', 'context' => 'Confirm disconnecting a calendar from OMO only.'],
];

$lang = omoLoadTranslationBundle('omo_calendar_connect', $sourceLang);

function omoCalendarConnectT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$organizationId = isset($_GET['oid']) && is_numeric($_GET['oid']) ? (int)$_GET['oid'] : 0;
$holonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$range = commonCalDavNormalizeScopedRange($_GET['scope'] ?? 'contextual');
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$viewer = new User();
if ($currentUserId <= 0 || !$viewer->load($currentUserId)) {
    http_response_code(403);
    exit('Acces refuse.');
}

$initialColor = '2563eb';
$calendar = commonCalDavLoadScopedCalendarForViewer($viewer, $organizationId, $holonId, $range, $initialColor);
if (!is_array($calendar)) {
    http_response_code(404);
    exit('Calendrier introuvable.');
}

$hasPassword = commonUserHasPasswordHash((string)$viewer->get('password'));
$loginIdentifier = trim((string)$viewer->getScopedEmail($organizationId));
if ($loginIdentifier === '') {
    $loginIdentifier = trim((string)$viewer->get('email'));
}
if ($loginIdentifier === '') {
    $loginIdentifier = trim((string)$viewer->get('username'));
}
$holonLabel = trim((string)($calendar['displayName'] ?? ''));
$organizationName = trim((string)($calendar['organization']->get('name') ?? ''));
if ($organizationName !== '' && strpos($holonLabel, $organizationName . ' - ') === 0) {
    $holonLabel = substr($holonLabel, strlen($organizationName . ' - '));
}
$urlPrefix = commonCalDavBuildAbsoluteHref(commonCalDavBuildScopedCalendarHref($organizationId, $holonId, $range));
$calDavUrl = $urlPrefix . $initialColor . '/';
$externalCalendars = new ArrayExternalCalendar();
$externalCalendars->loadForUser($currentUserId);
$externalActionUrl = '/omo/api/calendar/external_calendars.php';
if (empty($_SESSION['omo_external_calendar_csrf'])) {
    $_SESSION['omo_external_calendar_csrf'] = bin2hex(random_bytes(32));
}
$externalText = [];
foreach (['searching', 'connected', 'choose', 'pending', 'finished', 'failed', 'confirm_delete'] as $key) {
    $externalText[$key] = omoCalendarConnectT('calendar.connect.external.' . $key);
}
?>
<div class="omo-calendar-connect generic-drawer-content" data-omo-calendar-connect-popup
    data-omo-external-calendar-csrf="<?= omoApiEscape($_SESSION['omo_external_calendar_csrf']) ?>"
    data-omo-external-calendar-text="<?= omoApiEscape(json_encode($externalText, JSON_UNESCAPED_UNICODE)) ?>"
    data-omo-calendar-connect-title="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.popup.title')) ?>">
    <div class="generic-tabs" data-generic-tabs>
        <div class="generic-tabs__list" role="tablist">
            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoCalendarConnectOmo"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.tab.omo')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoCalendarConnectExternal"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.tab.external')) ?></button>
        </div>
        <div class="generic-tabs__panels">
            <div id="omoCalendarConnectOmo" class="generic-tabs__panel" data-generic-tab-panel>
                <section class="generic-section generic-soft-panel generic-soft-panel--stack">
                    <div class="omo-calendar-connect__summary">
                        <div><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.context')) ?></span><strong><?= omoApiEscape($holonLabel) ?></strong></div>
                        <div><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.visibility')) ?></span><strong><?= omoApiEscape(omoCalendarConnectT('calendar.connect.scope.' . $range)) ?></strong></div>
                    </div>
                </section>
                <?php if (!$hasPassword): ?>
                    <section class="generic-section generic-soft-panel generic-soft-panel--stack"><p class="omo-calendar-connect__message"><strong><?= omoApiEscape(omoCalendarConnectT('calendar.connect.password_missing')) ?></strong></p><p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.password_missing_detail')) ?></p></section>
                <?php else: ?>
                    <section class="generic-section generic-soft-panel generic-soft-panel--stack" data-omo-calendar-connect-details data-omo-calendar-connect-url-prefix="<?= omoApiEscape($urlPrefix) ?>">
                        <label class="omo-calendar-connect__field"><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.url')) ?></span><div class="omo-calendar-connect__url-row"><input class="generic-form-control" type="text" value="<?= omoApiEscape($calDavUrl) ?>" readonly data-omo-calendar-connect-url><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-calendar-connect-copy><?= omoApiEscape(omoCalendarConnectT('calendar.connect.copy')) ?></button></div></label>
                        <label class="omo-calendar-connect__field"><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.color')) ?></span><input type="color" value="#<?= omoApiEscape($initialColor) ?>" data-omo-calendar-connect-color></label>
                        <label class="omo-calendar-connect__field"><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.username')) ?></span><input class="generic-form-control" type="text" value="<?= omoApiEscape($loginIdentifier) ?>" readonly></label>
                    </section>
                    <section class="generic-section generic-soft-panel generic-soft-panel--stack"><h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.steps.title')) ?></h4><p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.steps')) ?></p></section>
                <?php endif; ?>
            </div>
            <div id="omoCalendarConnectExternal" class="generic-tabs__panel" data-generic-tab-panel hidden>
                <section class="generic-section generic-soft-panel generic-soft-panel--stack">
                    <h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.title')) ?></h4>
                    <p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.intro')) ?></p>
                    <form class="omo-calendar-connect__external-form generic-form-stack" data-omo-external-calendar-form data-omo-external-calendar-action="<?= omoApiEscape($externalActionUrl) ?>">
                        <input type="hidden" name="action" value="discover">
                        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.url')) ?></span><input class="generic-form-control" type="url" name="server_url" required maxlength="2000" placeholder="https://cloud.example/remote.php/dav"><span class="generic-help-text"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.url_hint')) ?></span></label>
                        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.username')) ?></span><input class="generic-form-control" type="text" name="username" required maxlength="250" autocomplete="username"></label>
                        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.password')) ?></span><input class="generic-form-control" type="password" name="password" required autocomplete="new-password"></label>
                        <div class="omo-calendar-connect__external-actions"><button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.search')) ?></button><p class="generic-feedback" data-omo-external-calendar-feedback aria-live="polite"></p></div>
                    </form>
                </section>
                <form class="generic-form-stack" data-omo-external-calendar-selection hidden>
                    <h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.selection')) ?></h4>
                    <div class="generic-form-stack" data-omo-external-calendar-results></div>
                    <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.connect_selected')) ?></button>
                    <p class="generic-feedback" data-omo-external-calendar-feedback aria-live="polite"></p>
                </form>
                <template data-omo-external-calendar-template>
                    <section class="generic-soft-panel generic-soft-panel--stack" data-omo-external-calendar-result>
                        <label class="generic-checkbox"><input type="checkbox" data-calendar-selected><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.select')) ?></span><strong data-calendar-name></strong></label>
                        <span class="generic-help-text" data-calendar-connected hidden><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.connected')) ?></span>
                        <div class="generic-form-grid">
                            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.name')) ?></span><input class="generic-form-control" type="text" maxlength="190" data-calendar-title></label>
                            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.color')) ?></span><input class="generic-form-control" type="color" data-calendar-color></label>
                        </div>
                        <p class="generic-feedback" data-calendar-status aria-live="polite"></p>
                    </section>
                </template>
                <section class="generic-section generic-soft-panel generic-soft-panel--stack">
                    <?php if (count($externalCalendars) === 0): ?>
                        <p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.empty')) ?></p>
                    <?php else: ?>
                        <div class="omo-calendar-connect__external-list">
                            <?php foreach ($externalCalendars as $externalCalendar): ?>
                                <?php if (!($externalCalendar instanceof ExternalCalendar)): continue; endif; ?>
                                <article class="omo-calendar-connect__external-item" data-omo-external-calendar-item>
                                    <span class="omo-calendar-connect__external-color" style="--param-external-calendar-color: <?= omoApiEscape(ExternalCalendar::normalizeColor($externalCalendar->get('color'))) ?>;"></span>
                                    <div><strong><?= omoApiEscape($externalCalendar->get('title')) ?></strong><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.last_sync')) ?> : <?= omoApiEscape($externalCalendar->get('last_sync_at') instanceof DateTimeInterface ? $externalCalendar->get('last_sync_at')->format('d.m.Y H:i') : omoCalendarConnectT('calendar.connect.external.never')) ?></span><?php if (trim((string)$externalCalendar->get('last_sync_error')) !== ''): ?><span class="omo-calendar-connect__external-error"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.error')) ?> : <?= omoApiEscape($externalCalendar->get('last_sync_error')) ?></span><?php endif; ?></div>
                                    <div class="omo-calendar-connect__external-actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-external-calendar-sync data-omo-external-calendar-id="<?= (int)$externalCalendar->getId() ?>" data-omo-external-calendar-action="<?= omoApiEscape($externalActionUrl) ?>"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.sync')) ?></button><button type="button" class="generic-action-button generic-action-button--danger" data-omo-external-calendar-delete data-omo-external-calendar-id="<?= (int)$externalCalendar->getId() ?>" data-omo-external-calendar-action="<?= omoApiEscape($externalActionUrl) ?>"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.delete')) ?></button></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.omo-calendar-connect { display: grid; gap: 14px; }
.omo-calendar-connect__summary, .omo-calendar-connect__external-list { display: grid; gap: 10px; }
.omo-calendar-connect__summary div, .omo-calendar-connect__field { display: grid; gap: 4px; }
.omo-calendar-connect__summary span, .omo-calendar-connect__field > span { color: var(--color-text-light, #64748b); font-size: 0.82rem; font-weight: 700; }
.omo-calendar-connect__summary strong { color: var(--color-text, #1f2937); }
.omo-calendar-connect__url-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; }
.omo-calendar-connect__field input[type="color"] { width: 52px; min-height: 36px; padding: 2px; border: 1px solid var(--color-border, #d1d5db); border-radius: var(--radius-md); background: var(--color-surface, #ffffff); cursor: pointer; }
.omo-calendar-connect__message { margin: 0; color: var(--color-text, #1f2937); line-height: 1.55; }
.omo-calendar-connect__external-form { margin-top: 4px; }
.omo-calendar-connect__external-item { display: grid; grid-template-columns: 10px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 12px; border: 1px solid var(--color-border, #d1d5db); border-radius: var(--radius-md); }
.omo-calendar-connect__external-color { align-self: stretch; border-radius: 999px; background: var(--param-external-calendar-color); }
.omo-calendar-connect__external-item > div:nth-child(2) { display: grid; gap: 3px; min-width: 0; }
.omo-calendar-connect__external-item > div:nth-child(2) span { color: var(--color-text-light, #64748b); font-size: 0.82rem; overflow-wrap: anywhere; }
.omo-calendar-connect__external-error { color: var(--color-danger, #b91c1c) !important; }
.omo-calendar-connect__external-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.omo-calendar-connect__external-form .generic-feedback { margin: 0; }
@media (max-width: 560px) { .omo-calendar-connect__url-row { grid-template-columns: 1fr; } .omo-calendar-connect__external-item { grid-template-columns: 8px minmax(0, 1fr); } .omo-calendar-connect__external-actions { grid-column: 2; } }
</style>
