<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/calendar/time-buffers.php';

use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\MeetingProfile;

$sourceLang = [
    'title' => ['text' => 'Temps avant / apres', 'context' => 'Edit attached time on an imported event.'],
    'hint' => ['text' => 'Ces temps sont enregistres dans OMO, conserves lors des synchronisations et deduits de vos disponibilites. Ils ne modifient pas votre agenda source.', 'context' => 'Local annotations for imported CalDAV and ICS events.'],
    'enable' => ['text' => 'Definir du temps de preparation/cloture', 'context' => 'Enable attached time on an imported event.'],
    'before' => ['text' => 'Preparation / deplacement avant', 'context' => 'Time before an imported event.'],
    'after' => ['text' => 'Cloture / deplacement apres', 'context' => 'Time after an imported event.'],
    'save' => ['text' => 'Enregistrer', 'context' => 'Save imported event local time settings.'],
    'saved' => ['text' => 'Temps du rendez-vous enregistres.', 'context' => 'Local event time settings saved.'],
    'missing' => ['text' => 'Evenement importe introuvable ou non modifiable.', 'context' => 'Imported event not owned, active or eligible for editing.'],
    'csrf' => ['text' => 'Rechargez le formulaire puis reessayez.', 'context' => 'Invalid local time editor CSRF token.'],
    'invalid' => ['text' => 'Indiquez des durees entieres entre 0 et 1440 minutes.', 'context' => 'Invalid imported event buffer duration.'],
    'failed' => ['text' => 'Impossible d enregistrer ces temps.', 'context' => 'Local time settings save failed.'],
    'busy' => ['text' => 'Une reservation ou une modification est en cours. Reessayez dans un instant.', 'context' => 'Owner calendar lock unavailable.'],
];
$lang = omoLoadTranslationBundle('omo_calendar_external_event', $sourceLang);
$translate = static fn(string $key): string => t($key, [], $lang, $sourceLang);
$userId = (int)commonGetCurrentUserId();
$eventId = (int)($_GET['id'] ?? 0);
$event = new ExternalCalendarEvent();
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if ($userId <= 0 || !$event->load($eventId, true) || !$event->canEditTimeBuffers($userId)) {
    http_response_code(404);
    if ($isPost) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => false, 'message' => $translate('missing')]);
    } else { echo omoApiEscape($translate('missing')); }
    exit;
}
$_SESSION['omo_external_event_csrf'] ??= bin2hex(random_bytes(32));
if ($isPost) {
    header('Content-Type: application/json; charset=UTF-8');
    $locked = false;
    try {
        if (!hash_equals($_SESSION['omo_external_event_csrf'], (string)($_POST['csrf'] ?? ''))) {
            http_response_code(403);
            throw new RuntimeException('csrf');
        }
        $preparation = !empty($_POST['time_buffers_enabled']) ? ExternalCalendarEvent::validateBufferMinutes($_POST['preparation_minutes'] ?? 0) : 0;
        $closing = !empty($_POST['time_buffers_enabled']) ? ExternalCalendarEvent::validateBufferMinutes($_POST['closing_minutes'] ?? 0) : 0;
        $locked = MeetingProfile::lock($userId);
        if (!$locked) { throw new RuntimeException('busy'); }
        if (!$event->saveLocalTimeBuffers($userId, $preparation, $closing)) { throw new RuntimeException('failed'); }
        echo json_encode(['status' => true, 'message' => $translate('saved')]);
    } catch (Throwable $exception) {
        $key = $exception->getMessage() === 'buffer_invalid' ? 'invalid' : $exception->getMessage();
        echo json_encode(['status' => false, 'message' => $translate(isset($sourceLang[$key]) ? $key : 'failed')]);
    } finally { if ($locked) { MeetingProfile::unlock($userId); } }
    exit;
}
$calendar = new ExternalCalendar();
$calendar->load((int)$event->get('IDexternalcalendar'));
$enabled = (int)$event->get('preparation_minutes') > 0 || (int)$event->get('closing_minutes') > 0;
$formId = 'omoExternalEventTimeBuffers';
$start = $event->get('start_at');
$end = $event->get('end_at');
?>
<div hidden data-omo-calendar-drawer-header data-omo-calendar-drawer-title="<?= omoApiEscape($translate('title')) ?>" data-omo-calendar-drawer-description="<?= omoApiEscape($calendar->get('title')) ?>">
    <button type="submit" form="<?= $formId ?>" class="generic-action-button generic-action-button--main" data-omo-calendar-drawer-action data-omo-calendar-create-submit><?= omoApiEscape($translate('save')) ?></button>
</div>
<form id="<?= $formId ?>" class="generic-drawer-content generic-form-stack" method="post" action="/omo/api/calendar/external_event.php?oid=<?= (int)($_REQUEST['oid'] ?? 0) ?>&amp;id=<?= $eventId ?>" data-omo-calendar-create-form data-omo-calendar-external-event-form>
    <input type="hidden" name="csrf" value="<?= omoApiEscape($_SESSION['omo_external_event_csrf']) ?>">
    <section class="generic-section generic-section--stack">
        <h3 class="generic-card-title"><?= omoApiEscape($event->get('title')) ?></h3>
        <p class="generic-meta-value"><?= omoApiEscape($start instanceof DateTimeInterface ? $start->format('d.m.Y H:i') : '') ?> - <?= omoApiEscape($end instanceof DateTimeInterface ? $end->format('d.m.Y H:i') : '') ?></p>
        <p class="generic-help-text"><?= omoApiEscape($translate('hint')) ?></p>
    </section>
    <label class="generic-checkbox"><input type="checkbox" name="time_buffers_enabled" value="1" data-omo-calendar-buffers-toggle<?= $enabled ? ' checked' : '' ?>><?= omoApiEscape($translate('enable')) ?></label>
    <div class="generic-form-grid generic-form-grid--pair" data-omo-calendar-buffers-fields<?= $enabled ? '' : ' hidden' ?>>
        <?php foreach (['preparation_minutes' => 'before', 'closing_minutes' => 'after'] as $field => $key): ?>
            <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape($translate($key)) ?></span>
                <?php commonCalendarRenderTimeBufferSelect($field, (int)$event->get($field), $enabled); ?>
            </label>
        <?php endforeach; ?>
    </div>
    <p class="generic-feedback generic-feedback--collapse-empty" data-omo-calendar-create-feedback></p>
</form>
