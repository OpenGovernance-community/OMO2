<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\ArrayHolon;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = [
    'calendar.create.title' => [
        'text' => 'Nouvel evenement',
        'context' => 'Title shown at the top of the event creation form.',
    ],
    'calendar.edit.title' => [
        'text' => 'Modifier l evenement',
        'context' => 'Title shown at the top of the event edition form.',
    ],
    'calendar.create.description' => [
        'text' => 'Planifiez une date, un horaire et un contexte de rattachement.',
        'context' => 'Intro text shown in the event creation form.',
    ],
    'calendar.edit.description' => [
        'text' => 'Mettez a jour la date, l horaire et le contexte de rattachement.',
        'context' => 'Intro text shown in the event edition form.',
    ],
    'calendar.create.field.title' => [
        'text' => 'Titre',
        'context' => 'Label of the event title field.',
    ],
    'calendar.create.field.description' => [
        'text' => 'Description',
        'context' => 'Label of the event description field.',
    ],
    'calendar.create.field.start' => [
        'text' => 'Debut',
        'context' => 'Label of the event start date time field.',
    ],
    'calendar.create.field.end' => [
        'text' => 'Fin',
        'context' => 'Label of the event end date time field.',
    ],
    'calendar.create.field.holon' => [
        'text' => 'Cercle ou role',
        'context' => 'Label of the optional holon association field.',
    ],
    'calendar.create.field.all_day' => [
        'text' => 'Journee entiere',
        'context' => 'Label of the all day checkbox in the event creation form.',
    ],
    'calendar.create.field.none' => [
        'text' => 'Aucun rattachement',
        'context' => 'Empty option shown in the holon select field.',
    ],
    'calendar.create.submit' => [
        'text' => 'Creer l evenement',
        'context' => 'Submit button label of the event creation form.',
    ],
    'calendar.edit.submit' => [
        'text' => 'Enregistrer les modifications',
        'context' => 'Submit button label of the event edition form.',
    ],
    'calendar.create.success' => [
        'text' => 'Evenement cree.',
        'context' => 'Success message returned after an event is created.',
    ],
    'calendar.edit.success' => [
        'text' => 'Evenement mis a jour.',
        'context' => 'Success message returned after an event is updated.',
    ],
    'calendar.create.error.title' => [
        'text' => 'Le titre est obligatoire.',
        'context' => 'Validation error returned when the title is missing.',
    ],
    'calendar.create.error.start' => [
        'text' => 'La date de debut est invalide.',
        'context' => 'Validation error returned when the start date is invalid.',
    ],
    'calendar.create.error.end' => [
        'text' => 'La date de fin est invalide.',
        'context' => 'Validation error returned when the end date is invalid.',
    ],
    'calendar.create.error.holon' => [
        'text' => 'Le contexte choisi est invalide.',
        'context' => 'Validation error returned when the selected holon is not allowed.',
    ],
    'calendar.create.error.save' => [
        'text' => 'Impossible d enregistrer cet evenement.',
        'context' => 'Generic error returned when the event could not be saved.',
    ],
];

$lang = omoLoadTranslationBundle('omo_calendar_create', $sourceLang);

function omoCalendarCreateT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

function omoCalendarParseLocalDateTime($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return null;
    }

    $formats = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'];
    foreach ($formats as $format) {
        $value = \DateTime::createFromFormat($format, $rawValue);
        if ($value instanceof \DateTime) {
            return $value;
        }
    }

    try {
        return new \DateTime($rawValue);
    } catch (\Throwable $exception) {
        return null;
    }
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_REQUEST['oid'] ?? 0));
$currentHolonId = isset($_REQUEST['cid']) && is_numeric($_REQUEST['cid']) ? (int)$_REQUEST['cid'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$eventId = isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    if (commonIsAjaxJsonRequest()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Acces refuse.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Acces refuse.</div>';
    }
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
    http_response_code(403);
    if (commonIsAjaxJsonRequest()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Organisation invalide.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Organisation invalide.</div>';
    }
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();

$event = new Event();
$isEditMode = false;

if ($eventId > 0) {
    if (
        !$event->load($eventId)
        || (int)$event->get('IDorganization') !== $organizationId
        || (int)$event->get('IDuser') !== $currentUserId
    ) {
        http_response_code(403);
        if (commonIsAjaxJsonRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => false,
                'message' => 'Evenement invalide.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo '<div class="omo-empty-state">Evenement invalide.</div>';
        }
        exit;
    }

    $isEditMode = true;
}

$holons = new ArrayHolon();
$holons->loadVisibilityTargetsForOrganization($organizationId, [2, 1]);
$holonOptions = $holons->buildVisibilityTargetOptions();
$allowedHolonIds = [];

foreach (['circle', 'role'] as $typeKey) {
    foreach (($holonOptions[$typeKey] ?? []) as $option) {
        $allowedHolonIds[(int)($option['id'] ?? 0)] = $option;
    }
}

$currentContextHolon = null;
if ($currentHolonId > 0 && isset($allowedHolonIds[$currentHolonId])) {
    $candidateHolon = new Holon();
    if ($candidateHolon->load($currentHolonId)) {
        $currentContextHolon = $candidateHolon;
    }
}

$usePermissionSessionCache = $_SERVER['REQUEST_METHOD'] !== 'POST';
$createPermissionHolon = $currentContextHolon instanceof Holon ? $currentContextHolon : $rootHolon;
$canCreateEvent = $currentUserId > 0
    && (
        $createPermissionHolon instanceof Holon
            ? $createPermissionHolon->isAllowed('CAN_CREATE_EVENT', $usePermissionSessionCache, $currentUserId)
            : commonCurrentUserHasOrganizationAccess($organizationId)
    );

if (!$isEditMode && !$canCreateEvent) {
    http_response_code(403);
    if (commonIsAjaxJsonRequest()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Acces refuse.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Acces refuse.</div>';
    }
    exit;
}

$defaultHolonId = 0;
if ($currentHolonId > 0 && isset($allowedHolonIds[$currentHolonId])) {
    $defaultHolonId = $currentHolonId;
}

if ($isEditMode) {
    $loadedHolonId = (int)$event->get('IDholon');
    if ($loadedHolonId > 0 && isset($allowedHolonIds[$loadedHolonId])) {
        $defaultHolonId = $loadedHolonId;
    } else {
        $defaultHolonId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $selectedHolonId = isset($_POST['IDholon']) ? (int)$_POST['IDholon'] : 0;
    $startAt = omoCalendarParseLocalDateTime($_POST['start_at'] ?? '');
    $endAt = omoCalendarParseLocalDateTime($_POST['end_at'] ?? '');
    $isAllDay = !empty($_POST['is_all_day']);

    if ($title === '') {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.title'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!($startAt instanceof \DateTimeInterface)) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.start'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!($endAt instanceof \DateTimeInterface)) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.end'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($endAt < $startAt) {
        $endAt = (clone $startAt);
    }

    if ($selectedHolonId > 0 && !isset($allowedHolonIds[$selectedHolonId])) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.holon'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!$isEditMode) {
        $targetPermissionHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ($selectedHolonId > 0) {
            $targetPermissionHolon = new Holon();
            if (!$targetPermissionHolon->load($selectedHolonId)) {
                echo json_encode([
                    'status' => false,
                    'message' => omoCalendarCreateT('calendar.create.error.holon'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        $canCreateSelectedEvent = $targetPermissionHolon instanceof Holon
            ? $targetPermissionHolon->isAllowed('CAN_CREATE_EVENT', false, $currentUserId)
            : commonCurrentUserHasOrganizationAccess($organizationId);
        if (!$canCreateSelectedEvent) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'Acces refuse.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    if ($isAllDay) {
        $startAt = (clone $startAt)->setTime(0, 0, 0);
        $endAt = (clone $endAt)->setTime(23, 59, 59);
    }

    if (!$isEditMode) {
        $event = new Event();
        $event->set('IDuser', $currentUserId);
        $event->set('active', 1);
    }

    $event->set('IDorganization', $organizationId);
    $event->set('IDholon', $selectedHolonId > 0 ? $selectedHolonId : null);
    $event->set('title', $title);
    $event->set('description', $description !== '' ? $description : null);
    $event->set('status', Event::STATUS_CONFIRMED);
    $event->set('timezone', date_default_timezone_get());
    $event->set('start_at', $startAt);
    $event->set('end_at', $endAt);
    $event->set('is_all_day', $isAllDay ? 1 : 0);

    $saveResult = $event->save();
    if (!is_array($saveResult) || empty($saveResult['status'])) {
        echo json_encode([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? '')) !== ''
                ? trim((string)$saveResult['text'])
                : omoCalendarCreateT('calendar.create.error.save'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode([
        'status' => true,
        'message' => omoCalendarCreateT($isEditMode ? 'calendar.edit.success' : 'calendar.create.success'),
        'eventId' => (int)$event->getId(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$initialDate = trim((string)($_GET['date'] ?? ''));
$startDefault = $isEditMode
    ? $event->get('start_at')
    : (omoCalendarParseLocalDateTime($initialDate !== '' ? ($initialDate . 'T09:00') : '') ?: new \DateTime('today 09:00'));
$endDefault = $isEditMode
    ? $event->get('end_at')
    : (clone $startDefault)->modify('+1 hour');
$titleDefault = $isEditMode ? trim((string)$event->get('title')) : '';
$descriptionDefault = $isEditMode ? trim((string)$event->get('description')) : '';
$isAllDayDefault = $isEditMode ? (bool)$event->get('is_all_day') : false;
?>
<div class="omo-calendar-create">
    <div class="generic-section generic-section--stack omo-calendar-create__shell">
        <div class="omo-calendar-create__head">
            <h2 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarCreateT($isEditMode ? 'calendar.edit.title' : 'calendar.create.title')) ?></h2>
            <p class="omo-calendar-create__text"><?= omoApiEscape(omoCalendarCreateT($isEditMode ? 'calendar.edit.description' : 'calendar.create.description')) ?></p>
        </div>

        <form
            class="omo-calendar-create__form"
            method="post"
            action="/omo/api/calendar/create.php?oid=<?= (int)$organizationId ?><?= $currentHolonId > 0 ? '&cid=' . (int)$currentHolonId : '' ?><?= $isEditMode ? '&id=' . (int)$event->getId() : '' ?>"
            data-omo-calendar-create-form
        >
            <?php if ($isEditMode): ?>
                <input type="hidden" name="id" value="<?= (int)$event->getId() ?>">
            <?php endif; ?>
            <div class="omo-calendar-create__grid">
                <label class="omo-calendar-create__field">
                    <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.title')) ?></span>
                    <input
                        type="text"
                        name="title"
                        class="generic-form-control"
                        value="<?= omoApiEscape($titleDefault) ?>"
                        maxlength="190"
                        required
                    >
                </label>

                <label class="omo-calendar-create__field">
                    <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.holon')) ?></span>
                    <select name="IDholon" class="generic-form-control">
                        <option value="0"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.none')) ?></option>
                        <?php foreach (['circle', 'role'] as $typeKey): ?>
                            <?php foreach (($holonOptions[$typeKey] ?? []) as $option): ?>
                                <option value="<?= (int)$option['id'] ?>"<?= (int)$option['id'] === $defaultHolonId ? ' selected' : '' ?>>
                                    <?= omoApiEscape((string)$option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="omo-calendar-create__field">
                    <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.start')) ?></span>
                    <input
                        type="datetime-local"
                        name="start_at"
                        class="generic-form-control"
                        value="<?= omoApiEscape($startDefault->format('Y-m-d\TH:i')) ?>"
                        required
                    >
                </label>

                <label class="omo-calendar-create__field">
                    <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.end')) ?></span>
                    <input
                        type="datetime-local"
                        name="end_at"
                        class="generic-form-control"
                        value="<?= omoApiEscape($endDefault->format('Y-m-d\TH:i')) ?>"
                        required
                    >
                </label>
            </div>

            <label class="omo-calendar-create__field">
                <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.description')) ?></span>
                <textarea
                    name="description"
                    class="generic-form-control"
                ><?= omoApiEscape($descriptionDefault) ?></textarea>
            </label>

            <label class="omo-calendar-create__check">
                <input type="checkbox" name="is_all_day" value="1"<?= $isAllDayDefault ? ' checked' : '' ?>>
                <span><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.all_day')) ?></span>
            </label>

            <div class="omo-calendar-create__footer">
                <div class="omo-calendar-create__feedback" data-omo-calendar-create-feedback></div>
                <button type="submit" class="generic-action-button generic-action-button--main" data-omo-calendar-create-submit>
                    <?= omoApiEscape(omoCalendarCreateT($isEditMode ? 'calendar.edit.submit' : 'calendar.create.submit')) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.omo-calendar-create {
    padding: 18px;
}

.omo-calendar-create__shell,
.omo-calendar-create__form,
.omo-calendar-create__field,
.omo-calendar-create__head {
    display: grid;
    gap: 10px;
}

.omo-calendar-create__text {
    margin: 0;
    color: var(--color-text-light, #64748b);
    line-height: 1.6;
}

.omo-calendar-create__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.omo-calendar-create__check {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--color-text, #1f2937);
    font-weight: 600;
}

.omo-calendar-create__footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
}

.omo-calendar-create__feedback {
    min-height: 20px;
    color: var(--color-text-light, #64748b);
}

.omo-calendar-create__feedback.is-error {
    color: var(--color-danger, #b42318);
}

@media (max-width: 720px) {
    .omo-calendar-create {
        padding: 14px;
    }

    .omo-calendar-create__grid {
        grid-template-columns: 1fr;
    }

    .omo-calendar-create__footer {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
