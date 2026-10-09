<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/calendar/time-buffers.php';

use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\MeetingProfile;

$sourceLang = [
    'hint' => ['text' => 'Ces temps sont enregistres dans OMO, conserves lors des synchronisations et deduits de vos disponibilites. Ils ne modifient pas votre agenda source.', 'context' => 'Local annotations for imported CalDAV and ICS events.'],
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
$start = $event->get('start_at');
$end = $event->get('end_at');
$dateFormat = $event->get('is_all_day') ? 'd.m.Y' : 'd.m.Y H:i';
commonCalendarRenderPersonalTimeBufferForm([
    'formId' => 'omoExternalEventTimeBuffers', 'external' => true,
    'description' => $calendar->get('title'), 'title' => $event->get('title'),
    'schedule' => ($start instanceof DateTimeInterface ? $start->format($dateFormat) : '') . ' - ' . ($end instanceof DateTimeInterface ? $end->format($dateFormat) : ''),
    'hint' => $translate('hint'),
    'buffers' => [(int)$event->get('preparation_minutes'), (int)$event->get('closing_minutes')],
    'csrf' => $_SESSION['omo_external_event_csrf'],
    'action' => '/omo/api/calendar/external_event.php?oid=' . (int)($_REQUEST['oid'] ?? 0) . '&id=' . $eventId,
]);
