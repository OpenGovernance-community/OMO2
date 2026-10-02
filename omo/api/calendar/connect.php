<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/caldav.php';
require_once dirname(__DIR__, 3) . '/common/external_calendar.php';

use dbObject\ArrayExternalCalendar;
use dbObject\CalendarShare;
use dbObject\ExternalCalendar;
use dbObject\User;

$sourceLang = [
    'calendar.connect.external.availability' => ['text' => 'Ce calendrier définit mes plages de disponibilité', 'context' => 'Invert an external calendar into opening windows.'],
    'calendar.connect.external.availability_hint' => ['text' => 'Disponible uniquement pendant ces plages, sous réserve de vos autres rendez-vous. Les horaires hebdomadaires et la pause s’appliquent si la prise de rendez-vous est activée. Plusieurs calendriers de disponibilité additionnent leurs plages.', 'context' => 'Explanation of availability calendars.'],
    'calendar.connect.popup.title' => ['text' => 'Connecter le calendrier', 'context' => 'Title of the calendar connection popup.'],
    'calendar.connect.tab.omo' => ['text' => 'Connecter OMO', 'context' => 'Tab with OMO CalDAV details.'],
    'calendar.connect.tab.external' => ['text' => 'Ajouter un agenda externe', 'context' => 'Tab used to connect a personal external CalDAV calendar.'],
    'calendar.connect.context' => ['text' => 'Espace de base', 'context' => 'Label for the base space of a scoped CalDAV calendar.'],
    'calendar.connect.visibility' => ['text' => 'Visibilité', 'context' => 'Label for the visibility range of a scoped CalDAV calendar.'],
    'calendar.connect.scope.contextual' => ['text' => 'Contexte courant', 'context' => 'CalDAV range label for the current holon only.'],
    'calendar.connect.scope.children' => ['text' => 'Enfants directs', 'context' => 'CalDAV range label for the current holon and its direct children.'],
    'calendar.connect.scope.descendants' => ['text' => 'Descendants', 'context' => 'CalDAV range label for the current holon and all descendants.'],
    'calendar.connect.password_missing' => ['text' => 'Pour connecter ce calendrier, configurez d’abord un mot de passe dans le menu Profil.', 'context' => 'Message shown instead of OMO CalDAV credentials when the account has no password.'],
    'calendar.connect.password_missing_detail' => ['text' => 'Le mot de passe OMO est nécessaire pour autoriser la synchronisation CalDAV depuis votre téléphone ou votre ordinateur.', 'context' => 'Additional explanation when the OMO account has no password.'],
    'calendar.connect.url' => ['text' => 'Adresse du serveur CalDAV', 'context' => 'Label for the scoped OMO CalDAV URL.'],
    'calendar.connect.ics_url' => ['text' => 'Adresse du calendrier ICS', 'context' => 'Label for a public ICS subscription URL compatible with Google Calendar.'],
    'calendar.connect.ics_hint' => ['text' => 'Vous pouvez ajouter ce lien comme agenda par URL dans Google Calendar. Toute personne qui possède le lien peut le consulter.', 'context' => 'Privacy explanation for the public ICS subscription URL.'],
    'calendar.connect.ics_label' => ['text' => 'Agenda ICS - {context}', 'context' => 'Private label for an automatically managed scoped ICS subscription.'],
    'calendar.connect.ics_steps.title' => ['text' => 'Ajouter dans Google Calendar', 'context' => 'Accordion title for detailed Google Calendar ICS subscription instructions.'],
    'calendar.connect.ics_steps.one' => ['text' => 'Copiez l’adresse ICS ci-dessus.', 'context' => 'First step for subscribing to an ICS calendar in Google Calendar.'],
    'calendar.connect.ics_steps.two' => ['text' => 'Dans Google Calendar sur le Web, ouvrez « Autres agendas », puis choisissez « À partir de l’URL ».', 'context' => 'Second step for subscribing to an ICS calendar in Google Calendar.'],
    'calendar.connect.ics_steps.three' => ['text' => 'Collez l’adresse, puis ajoutez l’agenda. Les événements se consultent dans Google Calendar, mais se modifient dans OMO.', 'context' => 'Third step for subscribing to an ICS calendar in Google Calendar.'],
    'calendar.connect.ics_steps.four' => ['text' => 'Gardez cette adresse privée : toute personne qui la possède peut consulter cet agenda.', 'context' => 'Privacy warning for an ICS calendar subscription URL.'],
    'calendar.connect.color' => ['text' => 'Couleur du calendrier', 'context' => 'Label for a calendar color.'],
    'calendar.connect.username' => ['text' => 'Identifiant', 'context' => 'Label for a CalDAV login identifier.'],
    'calendar.connect.copy' => ['text' => 'Copier', 'context' => 'Button that copies a URL.'],
    'calendar.connect.caldav_steps.title' => ['text' => 'Configurer une application CalDAV', 'context' => 'Accordion title for detailed CalDAV connection instructions.'],
    'calendar.connect.caldav_steps.one' => ['text' => 'Dans les réglages de votre téléphone ou de votre application de calendrier, ajoutez un compte ou un agenda CalDAV.', 'context' => 'First step for connecting an OMO CalDAV calendar.'],
    'calendar.connect.caldav_steps.two' => ['text' => 'Copiez l’adresse du serveur ci-dessus et collez-la dans le champ « Serveur » ou « URL » de votre application.', 'context' => 'Second step for connecting an OMO CalDAV calendar.'],
    'calendar.connect.caldav_steps.three' => ['text' => 'Utilisez l’identifiant affiché ci-dessous et votre mot de passe OMO. Si vous n’avez pas de mot de passe, configurez-en un dans votre profil.', 'context' => 'Third step for connecting an OMO CalDAV calendar.'],
    'calendar.connect.caldav_steps.four' => ['text' => 'Validez la connexion, puis activez les agendas OMO proposés dans votre application.', 'context' => 'Final step for connecting an OMO CalDAV calendar.'],
    'calendar.connect.external.title' => ['text' => 'Agendas CalDAV', 'context' => 'Heading for the external calendar connection form.'],
    'calendar.connect.external.list' => ['text' => 'Calendriers connectes', 'context' => 'Connected calendar list heading.'],
    'calendar.connect.external.add' => ['text' => 'Ajouter un calendrier', 'context' => 'Calendar creation section heading.'],
    'calendar.connect.external.edit' => ['text' => 'Modifier', 'context' => 'Edit calendar button.'],
    'calendar.connect.external.edit_title' => ['text' => 'Modifier le calendrier', 'context' => 'Calendar settings form heading.'],
    'calendar.connect.external.save' => ['text' => 'Enregistrer', 'context' => 'Save calendar settings button.'],
    'calendar.connect.external.cancel' => ['text' => 'Annuler', 'context' => 'Cancel calendar editing button.'],
    'calendar.connect.external.keep_secret' => ['text' => 'Laisser vide pour conserver la valeur actuelle.', 'context' => 'Help for replacing a stored calendar credential.'],
    'calendar.connect.external.calendar_url' => ['text' => 'Lien direct du calendrier CalDAV', 'context' => 'URL of the selected CalDAV collection when editing.'],
    'calendar.connect.external.calendar_url_help' => ['text' => 'Utilisez le lien direct de cet agenda. Pour choisir un autre agenda depuis un serveur, utilisez le formulaire d ajout.', 'context' => 'Help for editing a CalDAV collection URL.'],
    'calendar.connect.external.intro' => ['text' => 'Connectez votre compte Nextcloud ou Infomaniak, puis choisissez les agendas à afficher. Les événements restent privés et en lecture seule dans OMO.', 'context' => 'Introductory copy for external calendar discovery.'],
    'calendar.connect.external.name' => ['text' => 'Nom dans OMO', 'context' => 'Label for an external calendar display name.'],
    'calendar.connect.external.url' => ['text' => 'Adresse du serveur CalDAV', 'context' => 'Label for a CalDAV discovery URL.'],
    'calendar.connect.external.url_hint' => ['text' => 'Nextcloud : https://votre-cloud/remote.php/dav ; Infomaniak : https://sync.infomaniak.com. Un lien direct vers un agenda fonctionne aussi.', 'context' => 'Examples of supported CalDAV discovery entry points.'],
    'calendar.connect.external.password' => ['text' => 'Mot de passe d’application', 'context' => 'Label for a Nextcloud app password.'],
    'calendar.connect.external.submit' => ['text' => 'Ajouter et synchroniser', 'context' => 'Button that saves and synchronizes an external calendar.'],
    'calendar.connect.external.empty' => ['text' => 'Aucun agenda externe connecté.', 'context' => 'Empty state for external calendars.'],
    'calendar.connect.external.sync' => ['text' => 'Synchroniser', 'context' => 'Button that manually synchronizes an external calendar.'],
    'calendar.connect.external.delete' => ['text' => 'Retirer', 'context' => 'Button that removes an external calendar.'],
    'calendar.connect.external.last_sync' => ['text' => 'Dernière synchronisation', 'context' => 'Label for a calendar last sync time.'],
    'calendar.connect.external.never' => ['text' => 'Jamais', 'context' => 'Fallback for a calendar that has not synchronized.'],
    'calendar.connect.external.error' => ['text' => 'Erreur', 'context' => 'Label for a calendar synchronization error.'],
    'calendar.connect.external.search' => ['text' => 'Rechercher les agendas', 'context' => 'Button starting CalDAV discovery.'],
    'calendar.connect.external.searching' => ['text' => 'Recherche des agendas...', 'context' => 'Discovery in progress.'],
    'calendar.connect.external.selection' => ['text' => 'Agendas disponibles', 'context' => 'Heading of discovery results.'],
    'calendar.connect.external.select' => ['text' => 'Afficher cet agenda', 'context' => 'Checkbox selecting a discovered calendar.'],
    'calendar.connect.external.connected' => ['text' => 'Déjà connecté', 'context' => 'Badge for a previously connected calendar.'],
    'calendar.connect.external.connect_selected' => ['text' => 'Connecter les agendas sélectionnés', 'context' => 'Button saving the selected calendars.'],
    'calendar.connect.external.choose' => ['text' => 'Cochez au moins un agenda.', 'context' => 'No discovered calendar selected.'],
    'calendar.connect.external.pending' => ['text' => 'Connexion et synchronisation...', 'context' => 'Selected calendars being saved.'],
    'calendar.connect.external.finished' => ['text' => 'Traitement terminé. Consultez le résultat sous chaque agenda.', 'context' => 'All selected calendars have been processed.'],
    'calendar.connect.external.failed' => ['text' => 'Opération impossible. Réessayez.', 'context' => 'Network or unexpected calendar error.'],
    'calendar.connect.external.confirm_delete' => ['text' => 'Retirer cet agenda externe d’OMO ?', 'context' => 'Confirm disconnecting a calendar from OMO only.'],
    'calendar.connect.external.ics_title' => ['text' => 'Agenda par lien ICS', 'context' => 'Heading for an external ICS subscription.'],
    'calendar.connect.external.ics_intro' => ['text' => 'Ajoutez l’adresse secrète au format iCal de Google Calendar ou d’un autre agenda. Les événements seront visibles dans OMO en lecture seule.', 'context' => 'Explanation of ICS subscription.'],
    'calendar.connect.external.ics_url' => ['text' => 'Adresse ICS privée', 'context' => 'ICS subscription URL label.'],
    'calendar.connect.external.ics_secret' => ['text' => 'Gardez cette adresse privée : elle donne accès à votre agenda.', 'context' => 'Warning about the bearer URL.'],
    'calendar.connect.external.ics_steps' => ['text' => 'Dans Google Calendar sur le Web : Paramètres > votre agenda > Intégrer l’agenda > Adresse secrète au format iCal.', 'context' => 'Location of Google Calendar secret iCal URL.'],
    'calendar.connect.external.ics_add' => ['text' => 'Ajouter l’agenda ICS', 'context' => 'Button to subscribe to an ICS feed.'],
    'calendar.connect.external.type.ics' => ['text' => 'ICS', 'context' => 'ICS source badge.'],
    'calendar.connect.external.type.caldav' => ['text' => 'CalDAV', 'context' => 'CalDAV source badge.'],
];

$lang = omoLoadTranslationBundle('omo_calendar_connect', $sourceLang);

function omoCalendarConnectT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$connectHelp = static function (string $labelKey, array $textKeys): string {
    return '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
        . '<summary aria-label="' . omoApiEscape(omoCalendarConnectT($labelKey)) . '">?</summary>'
        . '<div class="generic-context-help__content">'
        . implode('', array_map(static fn($key) => '<p>' . omoApiEscape(omoCalendarConnectT($key)) . '</p>', $textKeys))
        . '</div></details>';
};

$calendarIdentityFields = static function (bool $discovered = false): void {
    ?>
    <div class="generic-form-field">
        <span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.name')) ?></span>
        <div class="generic-control-action">
            <input class="generic-form-control omo-calendar-connect__color-control" type="color" <?= $discovered ? 'data-calendar-color' : 'name="color"' ?> value="#0f766e" aria-label="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.color')) ?>" title="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.color')) ?>">
            <input class="generic-form-control generic-form-control--compact" type="text" <?= $discovered ? 'data-calendar-title' : 'name="title" required' ?> maxlength="190" aria-label="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.name')) ?>">
        </div>
    </div>
    <input type="hidden" <?= $discovered ? '' : 'name="availability_only"' ?> value="0">
    <label class="generic-checkbox"><input type="checkbox" <?= $discovered ? 'data-calendar-availability' : 'name="availability_only"' ?> value="1"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.availability')) ?></label>
    <p class="generic-help-text"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.availability_hint')) ?></p>
    <?php
};

$organizationId = isset($_GET['oid']) && is_numeric($_GET['oid']) ? (int)$_GET['oid'] : 0;
$holonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$range = commonCalDavNormalizeScopedRange($_GET['scope'] ?? 'contextual');
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$viewer = new User();
if ($currentUserId <= 0 || !$viewer->load($currentUserId)) {
    http_response_code(403);
    exit('Accès refusé.');
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
$icsUrl = '';
if (CalendarShare::isStorageAvailable()) {
    try {
        $icsShare = CalendarShare::findOrCreateScopedCalendarForUser(
            $currentUserId,
            $organizationId,
            $holonId,
            $range,
            omoCalendarConnectT('calendar.connect.ics_label', ['context' => $holonLabel])
        );
        if ($icsShare instanceof CalendarShare) {
            $icsUrl = appGetCurrentSiteBaseUrl() . '/calendar/share/' . $icsShare->get('token') . '.ics';
        }
    } catch (Throwable $exception) {
        $icsUrl = '';
    }
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
<link rel="stylesheet" href="/omo/api/calendar/popups.css?v=20260917-calendar-ui-6">
<div class="omo-calendar-connect generic-drawer-content" data-topbar-modal-max-width="760px" data-omo-calendar-connect-popup
    data-omo-external-calendar-csrf="<?= omoApiEscape($_SESSION['omo_external_calendar_csrf']) ?>"
    data-omo-external-calendar-text="<?= omoApiEscape(json_encode($externalText, JSON_UNESCAPED_UNICODE)) ?>"
    data-omo-calendar-connect-title="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.popup.title')) ?>">
    <div class="generic-tabs generic-tabs--embedded generic-tabs--equal generic-tabs--no-lift" data-generic-tabs>
        <div class="generic-tabs__list" role="tablist">
            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoCalendarConnectOmo"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.tab.omo')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoCalendarConnectExternal"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.tab.external')) ?></button>
        </div>
        <div class="generic-tabs__panels">
            <div id="omoCalendarConnectOmo" class="generic-tabs__panel generic-form-stack" data-generic-tab-panel>
                <section class="generic-form-section generic-form-section--divided generic-form-stack">
                    <div class="generic-soft-panel generic-soft-panel--tinted">
                        <div class="omo-calendar-connect__summary">
                            <div><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.context')) ?></span><strong><?= omoApiEscape($holonLabel) ?></strong></div>
                            <div><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.visibility')) ?></span><strong><?= omoApiEscape(omoCalendarConnectT('calendar.connect.scope.' . $range)) ?></strong></div>
                        </div>
                    </div>
                </section>
                <?php if ($icsUrl !== ''): ?>
                    <section class="generic-form-section generic-form-section--divided generic-form-stack">
                        <div class="generic-form-field"><label class="generic-form-label" for="omoCalendarConnectIcsUrl"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.ics_url')) ?></label><div class="generic-control-action"><input id="omoCalendarConnectIcsUrl" class="generic-form-control" type="text" value="<?= omoApiEscape($icsUrl) ?>" readonly data-omo-calendar-connect-ics-url><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-calendar-connect-copy data-omo-calendar-connect-copy-target="ics"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.copy')) ?></button></div><span class="generic-help-text"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.ics_hint')) ?></span></div>
                        <details class="generic-accordion"><summary><?= omoApiEscape(omoCalendarConnectT('calendar.connect.ics_steps.title')) ?></summary><div class="generic-accordion__content"><ol><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.ics_steps.one')) ?></li><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.ics_steps.two')) ?></li><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.ics_steps.three')) ?></li><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.ics_steps.four')) ?></li></ol></div></details>
                    </section>
                <?php endif; ?>
                <?php if (!$hasPassword): ?>
                    <section class="generic-form-section generic-form-section--divided generic-form-stack"><p class="omo-calendar-connect__message"><strong><?= omoApiEscape(omoCalendarConnectT('calendar.connect.password_missing')) ?></strong></p><p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.password_missing_detail')) ?></p></section>
                <?php else: ?>
                    <section class="generic-form-section generic-form-section--divided generic-form-stack" data-omo-calendar-connect-details data-omo-calendar-connect-url-prefix="<?= omoApiEscape($urlPrefix) ?>">
                        <div class="generic-form-field"><label class="generic-form-label" for="omoCalendarConnectUrl"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.url')) ?></label><div class="generic-control-action"><input class="generic-form-control omo-calendar-connect__color-control" type="color" value="#<?= omoApiEscape($initialColor) ?>" aria-label="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.color')) ?>" title="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.color')) ?>" data-omo-calendar-connect-color><input id="omoCalendarConnectUrl" class="generic-form-control" type="text" value="<?= omoApiEscape($calDavUrl) ?>" readonly data-omo-calendar-connect-url><button type="button" class="generic-action-button generic-action-button--main" data-omo-calendar-connect-copy data-omo-calendar-connect-copy-target="caldav"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.copy')) ?></button></div></div>
                        <details class="generic-accordion"><summary><?= omoApiEscape(omoCalendarConnectT('calendar.connect.caldav_steps.title')) ?></summary><div class="generic-accordion__content"><ol><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.caldav_steps.one')) ?></li><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.caldav_steps.two')) ?></li><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.caldav_steps.three')) ?></li><li><?= omoApiEscape(omoCalendarConnectT('calendar.connect.caldav_steps.four')) ?></li></ol></div></details>
                        <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.username')) ?></span><input class="generic-form-control" type="text" value="<?= omoApiEscape($loginIdentifier) ?>" readonly></label>
                    </section>
                <?php endif; ?>
            </div>
            <div id="omoCalendarConnectExternal" class="generic-tabs__panel generic-form-stack generic-form-stack--compact" data-generic-tab-panel hidden>
                <section class="generic-form-section generic-form-section--divided generic-form-stack generic-form-stack--compact">
                    <h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.list')) ?></h4>
                    <?php if (count($externalCalendars) === 0): ?>
                        <p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.empty')) ?></p>
                    <?php else: ?>
                        <div class="omo-calendar-connect__external-list">
                            <?php foreach ($externalCalendars as $externalCalendar): ?>
                                <?php
                                    if (!($externalCalendar instanceof ExternalCalendar)): continue; endif;
                                    $editValues = [
                                        'id' => (int)$externalCalendar->getId(),
                                        'provider' => (string)$externalCalendar->get('provider'),
                                        'availability_only' => (bool)$externalCalendar->get('availability_only'),
                                        'title' => (string)$externalCalendar->get('title'),
                                        'color' => ExternalCalendar::normalizeColor($externalCalendar->get('color')),
                                        'calendar_url' => (string)$externalCalendar->get('provider') === 'ics' ? '' : (string)$externalCalendar->get('calendar_url'),
                                        'username' => (string)$externalCalendar->get('provider') === 'ics' ? '' : (string)$externalCalendar->get('username'),
                                    ];
                                ?>
                                <article class="omo-calendar-connect__external-item" data-omo-external-calendar-item>
                                    <span class="omo-calendar-connect__external-color" style="--param-external-calendar-color: <?= omoApiEscape(ExternalCalendar::normalizeColor($externalCalendar->get('color'))) ?>;"></span>
                                    <div><strong><?= omoApiEscape($externalCalendar->get('title')) ?></strong><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.type.' . ((string)$externalCalendar->get('provider') === 'ics' ? 'ics' : 'caldav'))) ?> · <?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.last_sync')) ?> : <?= omoApiEscape($externalCalendar->get('last_sync_at') instanceof DateTimeInterface ? $externalCalendar->get('last_sync_at')->format('d.m.Y H:i') : omoCalendarConnectT('calendar.connect.external.never')) ?></span><?php if (trim((string)$externalCalendar->get('last_sync_error')) !== ''): ?><span class="omo-calendar-connect__external-error"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.error')) ?> : <?= omoApiEscape($externalCalendar->get('last_sync_error')) ?></span><?php endif; ?></div>
                                    <div class="omo-calendar-connect__external-actions"><button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-omo-external-calendar-edit="<?= omoApiEscape(json_encode($editValues, JSON_UNESCAPED_UNICODE)) ?>" aria-controls="omoCalendarExternalEditor" aria-expanded="false"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.edit')) ?></button><button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-omo-external-calendar-sync data-omo-external-calendar-id="<?= (int)$externalCalendar->getId() ?>" data-omo-external-calendar-action="<?= omoApiEscape($externalActionUrl) ?>"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.sync')) ?></button><button type="button" class="generic-action-button generic-action-button--compact generic-action-button--danger" data-omo-external-calendar-delete data-omo-external-calendar-id="<?= (int)$externalCalendar->getId() ?>" data-omo-external-calendar-action="<?= omoApiEscape($externalActionUrl) ?>"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.delete')) ?></button></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                <form id="omoCalendarExternalEditor" class="generic-form-section generic-form-section--divided generic-form-stack generic-form-stack--compact" data-omo-external-calendar-edit-form hidden>
                    <h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.edit_title')) ?></h4>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="calendar_id">
                    <?php $calendarIdentityFields(); ?>
                    <div class="generic-form-field" data-omo-external-calendar-edit-ics>
                        <div class="generic-inline-help"><label class="generic-form-label" for="omoCalendarEditIcsUrl"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.ics_url')) ?></label><?= $connectHelp('calendar.connect.external.ics_url', ['calendar.connect.external.keep_secret', 'calendar.connect.external.ics_steps', 'calendar.connect.external.ics_secret']) ?></div>
                        <input id="omoCalendarEditIcsUrl" class="generic-form-control generic-form-control--compact" type="url" name="ics_url" maxlength="2000" autocomplete="off" placeholder="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.keep_secret')) ?>">
                    </div>
                    <div class="generic-form-stack generic-form-stack--compact" data-omo-external-calendar-edit-caldav>
                        <div class="generic-form-field">
                            <div class="generic-inline-help"><label class="generic-form-label" for="omoCalendarEditCalDavUrl"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.calendar_url')) ?></label><?= $connectHelp('calendar.connect.external.calendar_url', ['calendar.connect.external.calendar_url_help']) ?></div>
                            <input id="omoCalendarEditCalDavUrl" class="generic-form-control generic-form-control--compact" type="url" name="calendar_url" maxlength="2000">
                        </div>
                        <div class="generic-form-grid">
                            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.username')) ?></span><input class="generic-form-control generic-form-control--compact" type="text" name="username" maxlength="250" autocomplete="username"></label>
                            <div class="generic-form-field">
                                <div class="generic-inline-help"><label class="generic-form-label" for="omoCalendarEditPassword"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.password')) ?></label><?= $connectHelp('calendar.connect.external.password', ['calendar.connect.external.keep_secret']) ?></div>
                                <input id="omoCalendarEditPassword" class="generic-form-control generic-form-control--compact" type="password" name="password" maxlength="4096" autocomplete="new-password" placeholder="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.keep_secret')) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="omo-calendar-connect__external-actions">
                        <button type="submit" class="generic-action-button generic-action-button--compact generic-action-button--main"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.save')) ?></button>
                        <button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-omo-external-calendar-edit-cancel><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.cancel')) ?></button>
                        <p class="generic-feedback generic-feedback--collapse-empty" data-omo-external-calendar-feedback aria-live="polite"></p>
                    </div>
                </form>
                <section class="generic-form-section generic-form-section--divided generic-form-stack generic-form-stack--compact" data-omo-external-calendar-add>
                    <h4 class="generic-card-title generic-card-title--small" id="omoCalendarExternalAddTitle"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.add')) ?></h4>
                    <div class="omo-calendar-connect__external-actions" role="radiogroup" aria-labelledby="omoCalendarExternalAddTitle">
                        <label class="generic-checkbox"><input type="radio" name="external_calendar_type" value="ics" checked data-omo-external-calendar-type aria-controls="omoCalendarExternalIcs"><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.ics_title')) ?></span></label>
                        <label class="generic-checkbox"><input type="radio" name="external_calendar_type" value="caldav" data-omo-external-calendar-type aria-controls="omoCalendarExternalCalDav"><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.type.caldav')) ?></span></label>
                    </div>
                    <section id="omoCalendarExternalIcs" class="generic-form-stack generic-form-stack--compact" data-omo-external-calendar-provider="ics">
                        <form class="generic-form-stack generic-form-stack--compact" data-omo-external-calendar-ics-form>
                            <input type="hidden" name="action" value="save_ics">
                            <div class="generic-form-field">
                                <div class="generic-inline-help"><label class="generic-form-label" for="omoCalendarExternalIcsUrl"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.ics_url')) ?></label><?= $connectHelp('calendar.connect.external.ics_url', ['calendar.connect.external.ics_intro', 'calendar.connect.external.ics_steps', 'calendar.connect.external.ics_secret']) ?></div>
                                <input id="omoCalendarExternalIcsUrl" class="generic-form-control generic-form-control--compact" type="url" name="ics_url" required maxlength="2000" placeholder="https://calendar.google.com/calendar/ical/...">
                            </div>
                            <?php $calendarIdentityFields(); ?>
                            <div class="omo-calendar-connect__external-actions"><button type="submit" class="generic-action-button generic-action-button--compact generic-action-button--main"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.ics_add')) ?></button><p class="generic-feedback generic-feedback--collapse-empty" data-omo-external-calendar-feedback aria-live="polite"></p></div>
                        </form>
                    </section>
                    <section id="omoCalendarExternalCalDav" class="generic-form-stack generic-form-stack--compact" data-omo-external-calendar-provider="caldav" hidden>
                        <form class="omo-calendar-connect__external-form generic-form-stack generic-form-stack--compact" data-omo-external-calendar-form data-omo-external-calendar-action="<?= omoApiEscape($externalActionUrl) ?>">
                            <input type="hidden" name="action" value="discover">
                            <div class="generic-form-field">
                                <div class="generic-inline-help"><label class="generic-form-label" for="omoCalendarExternalServer"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.url')) ?></label><?= $connectHelp('calendar.connect.external.url', ['calendar.connect.external.intro', 'calendar.connect.external.url_hint']) ?></div>
                                <input id="omoCalendarExternalServer" class="generic-form-control generic-form-control--compact" type="url" name="server_url" required maxlength="2000" placeholder="https://cloud.example/remote.php/dav">
                            </div>
                            <div class="generic-form-grid">
                                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.username')) ?></span><input class="generic-form-control generic-form-control--compact" type="text" name="username" required maxlength="250" autocomplete="username"></label>
                                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.password')) ?></span><input class="generic-form-control generic-form-control--compact" type="password" name="password" required autocomplete="new-password"></label>
                            </div>
                            <div class="omo-calendar-connect__external-actions"><button type="submit" class="generic-action-button generic-action-button--compact generic-action-button--main"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.search')) ?></button><p class="generic-feedback generic-feedback--collapse-empty" data-omo-external-calendar-feedback aria-live="polite"></p></div>
                        </form>
                        <form class="generic-form-stack generic-form-stack--compact" data-omo-external-calendar-selection hidden>
                            <h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.selection')) ?></h4>
                            <div class="generic-form-stack generic-form-stack--compact" data-omo-external-calendar-results></div>
                            <button type="submit" class="generic-action-button generic-action-button--compact generic-action-button--main"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.connect_selected')) ?></button>
                            <p class="generic-feedback generic-feedback--collapse-empty" data-omo-external-calendar-feedback aria-live="polite"></p>
                        </form>
                        <template data-omo-external-calendar-template>
                            <section class="generic-soft-panel generic-soft-panel--stack" data-omo-external-calendar-result>
                                <label class="generic-checkbox"><input type="checkbox" data-calendar-selected><span class="generic-form-label"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.select')) ?></span><strong data-calendar-name></strong></label>
                                <span class="generic-help-text" data-calendar-connected hidden><?= omoApiEscape(omoCalendarConnectT('calendar.connect.external.connected')) ?></span>
                                <?php $calendarIdentityFields(true); ?>
                                <p class="generic-feedback generic-feedback--collapse-empty" data-calendar-status aria-live="polite"></p>
                            </section>
                        </template>
                    </section>
                </section>

            </div>
        </div>
    </div>
</div>
