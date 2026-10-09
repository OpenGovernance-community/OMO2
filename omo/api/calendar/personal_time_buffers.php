<?php
// Included by create.php only after organization, event and invitation checks.
if (!isset($event, $currentUserId, $organizationId) || !$event->canEditTimeBuffers($currentUserId)) {
    http_response_code(403);
    exit;
}
$sourceLang = [
    'hint' => ['text' => 'Ces temps sont personnels et deduits uniquement de vos disponibilites. Les informations de l evenement restent inchangees.', 'context' => 'Personal buffers for an invited participant without event editing permission.'],
    'saved' => ['text' => 'Vos temps avant / apres sont enregistres.', 'context' => 'Personal event buffers saved.'],
    'csrf' => ['text' => 'Rechargez le formulaire puis reessayez.', 'context' => 'Invalid personal event editor CSRF token.'],
    'failed' => ['text' => 'Impossible d enregistrer ces temps.', 'context' => 'Personal event time settings save failed.'],
    'invalid' => ['text' => 'Indiquez des durees entieres entre 0 et 1440 minutes.', 'context' => 'Invalid personal time duration.'],
    'busy' => ['text' => 'Une modification est en cours. Reessayez dans un instant.', 'context' => 'Personal calendar lock unavailable.'],
];
$bundle = omoLoadTranslationBundle('omo_calendar_invited_time_buffers', $sourceLang);
$translate = static fn(string $key): string => t($key, [], $bundle, $sourceLang);
$_SESSION['omo_event_time_buffers_csrf'] ??= bin2hex(random_bytes(32));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $locked = false;
    try {
        if (!hash_equals($_SESSION['omo_event_time_buffers_csrf'], (string)($_POST['csrf'] ?? ''))) {
            http_response_code(403);
            throw new RuntimeException('csrf');
        }
        $preparation = !empty($_POST['time_buffers_enabled']) ? \dbObject\Event::validateBufferMinutes($_POST['preparation_minutes'] ?? 0) : 0;
        $closing = !empty($_POST['time_buffers_enabled']) ? \dbObject\Event::validateBufferMinutes($_POST['closing_minutes'] ?? 0) : 0;
        $locked = \dbObject\MeetingProfile::lock($currentUserId);
        if (!$locked) { throw new RuntimeException('busy'); }
        if (!$event->saveTimeBuffers($currentUserId, $preparation, $closing)) { throw new RuntimeException('failed'); }
        echo json_encode(['status' => true, 'message' => $translate('saved'), 'eventId' => (int)$event->getId()]);
    } catch (Throwable $exception) {
        $key = $exception->getMessage() === 'buffer_invalid' ? 'invalid' : $exception->getMessage();
        echo json_encode(['status' => false, 'message' => $translate(isset($sourceLang[$key]) ? $key : 'failed')]);
    } finally { if ($locked) { \dbObject\MeetingProfile::unlock($currentUserId); } }
    exit;
}
$start = $event->get('start_at');
$end = $event->get('end_at');
$dateFormat = $event->get('is_all_day') ? 'd.m.Y' : 'd.m.Y H:i';
commonCalendarRenderPersonalTimeBufferForm([
    'formId' => 'omoEventPersonalTimeBuffers', 'description' => '',
    'title' => $event->get('title'),
    'schedule' => ($start instanceof DateTimeInterface ? $start->format($dateFormat) : '') . ' - ' . ($end instanceof DateTimeInterface ? $end->format($dateFormat) : ''),
    'hint' => $translate('hint'), 'buffers' => $event->getTimeBuffers($currentUserId),
    'csrf' => $_SESSION['omo_event_time_buffers_csrf'],
    'action' => '/omo/api/calendar/create.php?oid=' . $organizationId . '&id=' . (int)$event->getId(),
]);
exit;
