<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/calendar/availability-preview.php';
require_once dirname(__DIR__, 3) . '/common/calendar/recurrence.php';

header('Content-Type: text/html; charset=UTF-8');
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$event = new \dbObject\Event();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || strcasecmp((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest') !== 0
    || !$event->load((int)($_GET['id'] ?? 0)) || (int)$event->get('IDorganization') !== $organizationId
    || !$event->isDraftVisibleToViewer($userId) || $event->get('recurrence_exception')
    || !($series = $event->getRecurrence()) || !$series->get('active') || $series->get('frequency') !== 'on_close'
    || !$series->canManage($organizationId, $userId)) {
    http_response_code(403); exit;
}
$sourceLang = array_merge(commonCalendarAvailabilityPreviewSourceLang(), commonMeetingRecurrenceSourceLang());
$lang = omoLoadTranslationBundle('omo_calendar_create', $sourceLang);
$translate = static fn(string $key, array $values = []): string => t(
    $key === 'calendar.create.preview.selection_hint' ? 'calendar.recurrence.availability_hint' : $key, $values, $lang, $sourceLang);
// Match the frozen invitation identities used for the next occurrence; holon membership stays live.
$blueprint = $series->getParameter('blueprint');
$preview = new \dbObject\Event();
$preview->set('IDorganization', $organizationId);
foreach (['IDuser', 'IDholon'] as $field) { $preview->set($field, $blueprint['fields'][$field] ?? null); }
$invitations = [];
foreach ($blueprint['invitations'] as $values) {
    $invitation = new \dbObject\EventInvitation();
    foreach ($values as $field => $value) { $invitation->set($field, $value); }
    $invitation->set('active', 1); $invitation->set('status', \dbObject\EventInvitation::STATUS_INVITED);
    $invitations[] = $invitation;
}
$targets = $preview->getEffectiveInvitationTargets($organizationId, $invitations, fresh: true, activeOnly: true, explicitOnly: true);
commonCalendarRenderInviteeAvailability($preview, $targets, $translate, compactHelp: true);
