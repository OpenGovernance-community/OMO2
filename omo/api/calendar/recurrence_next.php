<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/calendar/recurrence.php';

header('Content-Type: application/json; charset=UTF-8');
$sourceLang = commonMeetingRecurrenceSourceLang();
$lang = omoLoadTranslationBundle('omo_calendar_recurrence_next', $sourceLang);
$translate = static fn(string $key): string => t('calendar.recurrence.' . $key, [], $lang, $sourceLang);
$event = new \dbObject\Event();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$series = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || !hash_equals((string)($_SESSION['omo_event_recurrence_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))
    || empty($_SESSION['omo_event_recurrence_csrf'])
    || !$event->load((int)($_POST['id'] ?? 0))
    || (int)$event->get('IDorganization') !== $organizationId
    || !($series = $event->getRecurrence()) || !$series->canManage($organizationId, $userId)) {
    http_response_code(403); echo json_encode(['status' => false, 'message' => $translate('error')]); exit;
}
$pdo = \dbObject\DbObject::getPdo();
try {
    $start = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', (string)($_POST['next_meeting_start'] ?? ''), new \DateTimeZone($series->get('timezone')));
    if (!$start || $start->format('Y-m-d\TH:i') !== ($_POST['next_meeting_start'] ?? '')) { throw new \InvalidArgumentException('date'); }
    $pdo->beginTransaction();
    $id = $series->scheduleNext($event, $start);
    $pdo->commit();
    echo json_encode(['status' => true, 'id' => $id, 'message' => $translate('saved')]);
} catch (\Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('OMO next meeting failed: ' . $exception->getMessage());
    http_response_code(422); echo json_encode(['status' => false, 'message' => $translate($exception instanceof \InvalidArgumentException ? 'invalid' : 'error')]);
}
