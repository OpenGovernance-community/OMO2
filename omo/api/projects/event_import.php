<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__) . '/calendar/permissions_shared.php';

use dbObject\ArrayEvent;
use dbObject\ArrayExternalCalendarEvent;
use dbObject\ArrayProjectExternalEvent;
use dbObject\Event;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\Holon;
use dbObject\MeetingProfile;
use dbObject\Project;
use dbObject\ProjectExternalEvent;

header('Cache-Control: no-store');
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$input = $isPost ? $_POST : $_GET;
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$projectId = (int)($input['id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$context = omoProjectsResolveContext($organizationId, (int)($input['cid'] ?? 0));
$project = new Project();
$respond = static function (bool $success, string $message, array $extra = [], int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['success' => $success, 'status' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};
if (empty($context['status']) || $userId <= 0 || !$project->load($projectId)
    || (int)$project->get('IDorganization') !== $organizationId || !omoProjectsCanViewProject($project, $context)) {
    $respond(false, omoProjectsT('projects.error.not_found'), [], 404);
}
$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'];
if ($projectHolon instanceof Holon && (!($rootHolon instanceof Holon)
    || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true) || !$projectHolon->canViewDetail())) {
    $respond(false, omoProjectsT('projects.error.not_found'), [], 404);
}

// A project reader can open the intentionally shared external event, never its owner's calendar.
if (!$isPost && ($input['action'] ?? '') === 'detail') {
    $link = new ProjectExternalEvent();
    if (!$link->load((int)($input['link_id'] ?? 0)) || (int)$link->get('IDproject') !== $projectId || $link->isSourceMissing()) {
        $respond(false, omoProjectsT('projects.error.not_found'), [], 404);
    }
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div class="generic-drawer-content generic-form-stack">'
        . '<h3 class="generic-card-title generic-card-title--medium">' . omoApiEscape($link->get('title')) . '</h3>'
        . '<p class="generic-description">' . omoApiEscape(omoProjectsT('projects.events.external', ['calendar' => $link->get('calendar_title')])) . '</p>'
        . '<p class="generic-description">' . omoApiEscape($link->get('start_at')->format('d.m.Y H:i') . ' - ' . $link->get('end_at')->format('d.m.Y H:i')) . '</p>'
        . '<p class="generic-description">' . omoApiEscape($link->get('location')) . '</p>'
        . '<div class="generic-description">' . nl2br(omoApiEscape($link->get('description'))) . '</div></div>';
    exit;
}

if (!omoProjectsCanImportEvents($project, $context)) {
    $respond(false, omoProjectsT('projects.error.forbidden'), [], 403);
}
if ($isPost) {
    if (!is_string($input['_csrf'] ?? null) || !hash_equals(commonCsrfToken(), $input['_csrf'])) {
        $respond(false, omoProjectsT('projects.error.forbidden'), [], 403);
    }
    $eventId = (int)($input['event_id'] ?? 0);
    $external = ($input['source'] ?? '') === 'external';
    $detach = ($input['action'] ?? '') === 'detach';
    if (!in_array($input['action'] ?? '', ['attach', 'detach'], true) || $eventId <= 0) {
        $respond(false, omoProjectsT('projects.events.import.error'), [], 422);
    }
    $changed = true;
    if ($external && $detach) {
        $link = new ProjectExternalEvent();
        if (!$link->load($eventId) || (int)$link->get('IDproject') !== $projectId) {
            $respond(false, omoProjectsT('projects.error.not_found'), [], 404);
        }
        $title = (string)$link->get('title');
        $saved = $link->delete();
    } elseif ($external) {
        if (!MeetingProfile::lock($userId)) { $respond(false, omoProjectsT('projects.events.import.error'), [], 409); }
        try {
            $event = new ExternalCalendarEvent();
            $saved = $event->load($eventId, true) && $event->canEditTimeBuffers($userId);
            $existingLink = new ProjectExternalEvent();
            $changed = !$existingLink->load([['IDproject', $projectId], ['IDexternalcalendarevent', $eventId]]);
            $title = $saved ? (string)$event->get('title') : '';
            $saved = $saved && ProjectExternalEvent::attach($project, $event, $userId) instanceof ProjectExternalEvent;
        } finally { MeetingProfile::unlock($userId); }
    } else {
        $event = new Event();
        if (!$event->load($eventId, true) || !(bool)$event->get('active')
            || !$event->isDraftVisibleToViewer($userId)
            || !omoCalendarCanEditEvent($event, $organizationId, $userId, $rootHolon, false)) {
            $respond(false, omoProjectsT('projects.error.forbidden'), [], 403);
        }
        $eventHolon = omoCalendarResolveEventPermissionHolon($event, $rootHolon);
        if (!($eventHolon instanceof Holon) || !$eventHolon->canViewDetail()
            || ($detach && (int)$event->get('IDproject') !== $projectId)) {
            $respond(false, omoProjectsT('projects.error.forbidden'), [], 403);
        }
        $title = (string)$event->get('title');
        $changed = $detach || (int)$event->get('IDproject') !== $projectId;
        $saved = $detach ? $event->detachFromProject($projectId) : $event->attachToProject($project);
    }
    if (!$saved) { $respond(false, omoProjectsT('projects.events.import.error'), [], 409); }
    if ($changed) { $project->recordAssociationHistory('event', $eventId, $title, $detach ? 'removed' : 'added', $userId); }
    $respond(true, omoProjectsT($detach ? 'projects.events.detached' : 'projects.events.import.success'), ['projectId' => $projectId]);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    $respond(false, omoProjectsT('projects.error.method'), [], 405);
}

$items = [];
$events = new ArrayEvent();
$events->loadVisibleForOrganization($organizationId, $userId);
foreach ($events as $event) {
    if ((int)$event->get('IDproject') > 0 || !$event->isDraftVisibleToViewer($userId)
        || !omoCalendarCanEditEvent($event, $organizationId, $userId, $rootHolon)) { continue; }
    $holon = omoCalendarResolveEventPermissionHolon($event, $rootHolon);
    if (!($holon instanceof Holon) || !$holon->canViewDetail()) { continue; }
    $start = $event->get('start_at');
    $end = $event->get('end_at');
    if (!($start instanceof DateTimeInterface) || !($end instanceof DateTimeInterface)) { continue; }
    $items[] = ['id' => (int)$event->getId(), 'source' => 'internal', 'eventId' => (int)$event->getId(),
        'contextHolonId' => (int)$event->get('IDholon'), 'contextLabel' => $holon->getDisplayName(),
        'title' => trim((string)$event->get('title')) . ' - ' . $start->format('d.m.Y H:i'),
        'description' => $start->format('d.m.Y H:i') . ' - ' . $end->format('d.m.Y H:i')];
}
$attached = new ArrayProjectExternalEvent();
$attached->loadForProject($projectId);
$attachedIds = [];
foreach ($attached as $link) { $attachedIds[(int)$link->get('IDexternalcalendarevent')] = true; }
$externalEvents = new ArrayExternalCalendarEvent();
$externalEvents->loadImportableForUser($userId);
foreach ($externalEvents as $event) {
    if (isset($attachedIds[(int)$event->getId()])) { continue; }
    $calendar = new ExternalCalendar();
    if (!$calendar->load((int)$event->get('IDexternalcalendar'))) { continue; }
    $items[] = ['id' => -(int)$event->getId(), 'source' => 'external', 'eventId' => (int)$event->getId(),
        'contextHolonId' => 0, 'contextLabel' => (string)$calendar->get('title'),
        'title' => trim((string)$event->get('title')) . ' - ' . $event->get('start_at')->format('d.m.Y H:i'),
        'description' => $event->get('start_at')->format('d.m.Y H:i') . ' - ' . $event->get('end_at')->format('d.m.Y H:i')
            . ' - ' . (string)$event->get('location')];
}
$respond(true, '', ['projectId' => $projectId, 'organizationId' => $organizationId,
    'projectHolonId' => (int)$project->get('IDholon'), 'csrf' => commonCsrfToken(), 'items' => $items,
    'tabs' => [['value' => 'internal', 'label' => omoProjectsT('projects.events.import.internal')],
        ['value' => 'external', 'label' => omoProjectsT('projects.events.import.external'), 'scope' => false]],
    'scopeLabels' => ['local' => omoProjectsT('projects.scope.contextual'), 'children' => omoProjectsT('projects.scope.children'), 'descendants' => omoProjectsT('projects.scope.descendants')],
    'labels' => ['modalTitle' => omoProjectsT('projects.events.import'), 'search' => omoProjectsT('projects.events.import.search'),
        'quickSearchPlaceholder' => omoProjectsT('projects.events.import.search'), 'visibleDocuments' => omoProjectsT('projects.detail.tabs.events'),
        'none' => omoProjectsT('projects.events.import.none'), 'insert' => omoProjectsT('projects.events.import.attach'),
        'cancel' => omoProjectsT('projects.action.cancel'), 'hint' => omoProjectsT('projects.events.import.hint'),
        'error' => omoProjectsT('projects.events.import.error')]]);
