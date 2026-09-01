<?php
require_once dirname(__DIR__) . '/bootstrap.php'; require_once __DIR__ . '/shared.php'; use dbObject\ControlActivity; use dbObject\ControlTaskCheck; use dbObject\RecurrenceSchedule;
header('Content-Type: application/json; charset=utf-8');
function omoActivityRespond($status, $message, array $extra = []) { echo json_encode(array_merge(['status' => (bool)$status, 'message' => (string)$message], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') { omoActivityRespond(false, omoActivityT('activity.error.forbidden')); }
$oid = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0)); $cid = (int)($_POST['cid'] ?? 0); $context = omoActivityResolveContext($oid, $cid); if (empty($context['status'])) { omoActivityRespond(false, $context['message']); } $action = trim((string)($_POST['activity_action'] ?? '')); $suffix = $cid > 0 ? '&cid=' . $cid : '';
if ($action === 'save_activity') {
    $id = (int)($_POST['id'] ?? 0); $activity = new ControlActivity(); if ($id > 0 && (!$activity->load($id) || (int)$activity->get('IDorganization') !== $oid)) { omoActivityRespond(false, omoActivityT('activity.error.not_found')); }
    if ($id > 0 ? !omoActivityCanEdit($activity) : !omoActivityCanUsePermission($context['currentHolon'], 'CAN_CREATE_CONTROL_ACTIVITY')) { omoActivityRespond(false, omoActivityT('activity.error.forbidden')); }
    $title = trim((string)($_POST['title'] ?? '')); $frequency = RecurrenceSchedule::normalizeFrequency($_POST['frequency'] ?? ''); $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $_POST['schedule'] ?? ''); if ($title === '') { omoActivityRespond(false, omoActivityT('activity.error.title')); } if (!$frequency || !$schedule) { omoActivityRespond(false, omoActivityT('activity.error.schedule')); }
    if ($id === 0) { $activity->set('IDorganization', $oid); $activity->set('IDholon', (int)$context['currentHolon']->getId()); $activity->set('position', 0); $activity->set('active', 1); }
    $activity->set('title', $title); $activity->set('description', trim((string)($_POST['description'] ?? ''))); $activity->set('frequency', $frequency); $activity->set('schedule', $schedule); $activity->set('display_lead_value', max(0, (int)($_POST['display_lead_value'] ?? 0))); $activity->set('display_lead_unit', $_POST['display_lead_unit'] ?? 'day'); $activity->set('execution_duration_value', max(1, (int)($_POST['execution_duration_value'] ?? 1))); $activity->set('execution_duration_unit', $_POST['execution_duration_unit'] ?? 'day'); $result = $activity->save(); if (!is_array($result) || empty($result['status'])) { omoActivityRespond(false, omoActivityT('activity.error.save')); }
    omoActivityRespond(true, omoActivityT('activity.success.saved'), ['detailUrl' => '/omo/api/activities/detail.php?oid=' . $oid . '&id=' . (int)$activity->getId() . $suffix]);
}
if ($action === 'check_activity') {
    $activity = new ControlActivity(); if (!$activity->load((int)($_POST['id'] ?? 0)) || (int)$activity->get('IDorganization') !== $oid || !omoActivityCanView($activity)) { omoActivityRespond(false, omoActivityT('activity.error.not_found')); }
    $state = $activity->getOccurrenceState(new DateTimeImmutable('now')); $userId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0; if ($userId <= 0 || !in_array($state['state'] ?? '', ['due', 'missed'], true)) { omoActivityRespond(false, omoActivityT('activity.error.forbidden')); }
    $check = new ControlTaskCheck(); $check->set('IDcontroltask', (int)$activity->getId()); $check->set('IDuser', $userId); $check->set('scheduled_for', $state['occurrenceAt']); $check->set('checked_at', new DateTimeImmutable('now')); $result = $check->save(); if (!is_array($result) || empty($result['status'])) { omoActivityRespond(false, omoActivityT('activity.error.save')); }
    omoActivityRespond(true, omoActivityT('activity.success.checked'), ['detailUrl' => '/omo/api/activities/detail.php?oid=' . $oid . '&id=' . (int)$activity->getId() . $suffix]);
}
if ($action === 'delete_activity') { $activity = new ControlActivity(); if (!$activity->load((int)($_POST['id'] ?? 0)) || (int)$activity->get('IDorganization') !== $oid || !omoActivityCanDelete($activity) || !$activity->deleteWithRelatedData()) { omoActivityRespond(false, omoActivityT('activity.error.forbidden')); } omoActivityRespond(true, omoActivityT('activity.success.saved'), ['detailUrl' => '/omo/api/activities/index.php?oid=' . $oid . $suffix]); }
omoActivityRespond(false, omoActivityT('activity.error.not_found'));
?>
