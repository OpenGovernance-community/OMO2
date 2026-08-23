<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/caldav.php';

use dbObject\User;

$sourceLang = [
    'calendar.connect.popup.title' => [
        'text' => 'Connecter le calendrier',
        'context' => 'Title of the topbar popup that gives the CalDAV connection details for the current calendar scope.',
    ],
    'calendar.connect.context' => [
        'text' => 'Holon de base',
        'context' => 'Label for the base holon of a scoped CalDAV calendar.',
    ],
    'calendar.connect.visibility' => [
        'text' => 'Visibilite',
        'context' => 'Label for the visibility range of a scoped CalDAV calendar.',
    ],
    'calendar.connect.scope.contextual' => [
        'text' => 'Contexte courant',
        'context' => 'CalDAV range label for the current holon only.',
    ],
    'calendar.connect.scope.children' => [
        'text' => 'Enfants directs',
        'context' => 'CalDAV range label for the current holon and its direct children.',
    ],
    'calendar.connect.scope.descendants' => [
        'text' => 'Descendants',
        'context' => 'CalDAV range label for the current holon and all of its descendants.',
    ],
    'calendar.connect.password_missing' => [
        'text' => 'Pour connecter ce calendrier, configurez d abord un mot de passe dans le menu Profil.',
        'context' => 'Message shown instead of connection credentials when the account has no password.',
    ],
    'calendar.connect.password_missing_detail' => [
        'text' => 'Le mot de passe OMO est necessaire pour autoriser la synchronisation CalDAV depuis votre telephone ou votre ordinateur.',
        'context' => 'Additional explanation when the account has no password.',
    ],
    'calendar.connect.url' => [
        'text' => 'Adresse du serveur CalDAV',
        'context' => 'Label for the scoped CalDAV URL.',
    ],
    'calendar.connect.color' => [
        'text' => 'Couleur du calendrier',
        'context' => 'Label for the color encoded in the scoped CalDAV URL.',
    ],
    'calendar.connect.username' => [
        'text' => 'Identifiant',
        'context' => 'Label for the CalDAV login identifier.',
    ],
    'calendar.connect.copy' => [
        'text' => 'Copier',
        'context' => 'Button that copies the scoped CalDAV URL.',
    ],
    'calendar.connect.copied' => [
        'text' => 'Copiee',
        'context' => 'Temporary label shown once the scoped CalDAV URL has been copied.',
    ],
    'calendar.connect.steps.title' => [
        'text' => 'Marche a suivre',
        'context' => 'Heading before the CalDAV connection instructions.',
    ],
    'calendar.connect.steps' => [
        'text' => 'Dans les reglages de votre telephone ou de votre application de calendrier, ajoutez un compte CalDAV. Saisissez l adresse ci dessus, votre identifiant et votre mot de passe OMO. Les modifications se font ensuite directement dans OMO : la synchronisation est en lecture seule.',
        'context' => 'Complete CalDAV connection instructions for a user with a password.',
    ],
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
?>
<div
    class="omo-calendar-connect"
    data-omo-calendar-connect-popup
    data-omo-calendar-connect-title="<?= omoApiEscape(omoCalendarConnectT('calendar.connect.popup.title')) ?>"
>
    <section class="generic-section generic-soft-panel generic-soft-panel--stack">
        <div class="omo-calendar-connect__summary">
            <div><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.context')) ?></span><strong><?= omoApiEscape($holonLabel) ?></strong></div>
            <div><span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.visibility')) ?></span><strong><?= omoApiEscape(omoCalendarConnectT('calendar.connect.scope.' . $range)) ?></strong></div>
        </div>
    </section>

    <?php if (!$hasPassword): ?>
        <section class="generic-section generic-soft-panel generic-soft-panel--stack">
            <p class="omo-calendar-connect__message"><strong><?= omoApiEscape(omoCalendarConnectT('calendar.connect.password_missing')) ?></strong></p>
            <p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.password_missing_detail')) ?></p>
        </section>
    <?php else: ?>
        <section
            class="generic-section generic-soft-panel generic-soft-panel--stack"
            data-omo-calendar-connect-details
            data-omo-calendar-connect-url-prefix="<?= omoApiEscape($urlPrefix) ?>"
        >
            <label class="omo-calendar-connect__field">
                <span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.url')) ?></span>
                <div class="omo-calendar-connect__url-row">
                    <input class="generic-form-control" type="text" value="<?= omoApiEscape($calDavUrl) ?>" readonly data-omo-calendar-connect-url>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-calendar-connect-copy><?= omoApiEscape(omoCalendarConnectT('calendar.connect.copy')) ?></button>
                </div>
            </label>
            <label class="omo-calendar-connect__field">
                <span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.color')) ?></span>
                <input type="color" value="#<?= omoApiEscape($initialColor) ?>" data-omo-calendar-connect-color>
            </label>
            <label class="omo-calendar-connect__field">
                <span><?= omoApiEscape(omoCalendarConnectT('calendar.connect.username')) ?></span>
                <input class="generic-form-control" type="text" value="<?= omoApiEscape($loginIdentifier) ?>" readonly>
            </label>
        </section>
        <section class="generic-section generic-soft-panel generic-soft-panel--stack">
            <h4 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.steps.title')) ?></h4>
            <p class="omo-calendar-connect__message"><?= omoApiEscape(omoCalendarConnectT('calendar.connect.steps')) ?></p>
        </section>
    <?php endif; ?>
</div>

<style>
.omo-calendar-connect {
    display: grid;
    gap: 14px;
}

.omo-calendar-connect__summary {
    display: grid;
    gap: 10px;
}

.omo-calendar-connect__summary div,
.omo-calendar-connect__field {
    display: grid;
    gap: 4px;
}

.omo-calendar-connect__summary span,
.omo-calendar-connect__field > span {
    color: var(--color-text-light, #64748b);
    font-size: 0.82rem;
    font-weight: 700;
}

.omo-calendar-connect__summary strong {
    color: var(--color-text, #1f2937);
}

.omo-calendar-connect__url-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
}

.omo-calendar-connect__field input[type="color"] {
    width: 52px;
    min-height: 36px;
    padding: 2px;
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: var(--radius-md);
    background: var(--color-surface, #ffffff);
    cursor: pointer;
}

.omo-calendar-connect__message {
    margin: 0;
    color: var(--color-text, #1f2937);
    line-height: 1.55;
}

@media (max-width: 560px) {
    .omo-calendar-connect__url-row {
        grid-template-columns: 1fr;
    }
}
</style>
