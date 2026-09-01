<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlList;
use dbObject\ControlTask;
use dbObject\ControlTaskCheck;
use dbObject\RecurrenceSchedule;

header('Content-Type: application/json; charset=utf-8');

function omoControlListActionResponse($status, $message, array $extra = [])
{
    echo json_encode(array_merge(['status' => (bool)$status, 'message' => (string)$message], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    omoControlListActionResponse(false, omoControlListT('control.error.method'));
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$currentHolonId = isset($_POST['cid']) && is_numeric($_POST['cid']) ? (int)$_POST['cid'] : 0;
$context = omoControlListResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    omoControlListActionResponse(false, (string)($context['message'] ?? omoControlListT('control.error.context')));
}
$action = trim((string)($_POST['control_action'] ?? ''));
$routeSuffix = $currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '';

if ($action === 'save_list') {
    $listId = (int)($_POST['id'] ?? 0);
    $list = $listId > 0 ? omoControlListLoad($listId, $organizationId) : new ControlList();
    if (!($list instanceof ControlList) || ($listId > 0 && !omoControlListCanManage($list)) || ($listId === 0 && !omoControlListCanUsePermission($context['currentHolon'], 'CAN_CREATE_CONTROL_LIST'))) {
        omoControlListActionResponse(false, omoControlListT('control.error.forbidden'));
    }
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { omoControlListActionResponse(false, omoControlListT('control.error.title')); }
    if ($listId === 0) {
        $list->set('IDorganization', $organizationId);
        $list->set('IDholon', (int)$context['currentHolon']->getId());
        $list->set('active', 1);
    }
    $list->set('title', $title);
    $list->set('description', trim((string)($_POST['description'] ?? '')));
    $result = $list->save();
    if (!is_array($result) || empty($result['status'])) { omoControlListActionResponse(false, omoControlListT('control.error.save')); }
    $detailUrl = '/omo/api/checklists/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$list->getId() . $routeSuffix;
    omoControlListActionResponse(true, omoControlListT('control.success.saved'), ['detailUrl' => $detailUrl]);
}

if ($action === 'save_task') {
    $list = omoControlListLoad((int)($_POST['list_id'] ?? 0), $organizationId);
    $taskId = (int)($_POST['id'] ?? 0);
    $task = new ControlTask();
    if ($taskId > 0 && (!$task->load($taskId) || (int)$task->get('IDcontrollist') !== (int)($list instanceof ControlList ? $list->getId() : 0))) { $task = null; }
    if (!($list instanceof ControlList) || !omoControlListCanManage($list) || !($task instanceof ControlTask)) {
        omoControlListActionResponse(false, omoControlListT('control.error.forbidden'));
    }
    $title = trim((string)($_POST['title'] ?? ''));
    $frequency = RecurrenceSchedule::normalizeFrequency($_POST['frequency'] ?? '');
    $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $_POST['schedule'] ?? '');
    if ($title === '') { omoControlListActionResponse(false, omoControlListT('control.error.title')); }
    if ($frequency === null || $schedule === null) { omoControlListActionResponse(false, omoControlListT('control.error.schedule')); }
    if ($taskId === 0) {
        $task->set('IDcontrollist', (int)$list->getId());
        $task->set('position', count($list->getTasks(false)));
        $task->set('active', 1);
    }
    $task->set('title', $title);
    $task->set('description', trim((string)($_POST['description'] ?? '')));
    $task->set('frequency', $frequency);
    $task->set('schedule', $schedule);
    $task->set('display_lead_value', max(0, (int)($_POST['display_lead_value'] ?? 0)));
    $task->set('display_lead_unit', $_POST['display_lead_unit'] ?? ControlTask::DELAY_DAY);
    $task->set('execution_duration_value', max(1, (int)($_POST['execution_duration_value'] ?? 1)));
    $task->set('execution_duration_unit', $_POST['execution_duration_unit'] ?? ControlTask::DELAY_DAY);
    $result = $task->save();
    if (!is_array($result) || empty($result['status'])) { omoControlListActionResponse(false, omoControlListT('control.error.save')); }
    $detailUrl = '/omo/api/checklists/task_detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$task->getId() . $routeSuffix;
    omoControlListActionResponse(true, omoControlListT('control.success.saved'), ['detailUrl' => $detailUrl]);
}

if ($action === 'check_task') {
    $task = new ControlTask();
    if (!$task->load((int)($_POST['id'] ?? 0))) { omoControlListActionResponse(false, omoControlListT('control.error.not_found')); }
    $list = $task->getList();
    $userId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
    if (!($list instanceof ControlList) || (int)$list->get('IDorganization') !== $organizationId || !omoControlListCanView($list) || $userId <= 0) {
        omoControlListActionResponse(false, omoControlListT('control.error.forbidden'));
    }
    $now = new DateTimeImmutable('now');
    $state = $task->getOccurrenceState($now);
    if (!in_array((string)($state['state'] ?? ''), ['due', 'overdue'], true) || !($state['occurrenceAt'] instanceof DateTimeImmutable)) {
        omoControlListActionResponse(false, omoControlListT('control.error.forbidden'));
    }
    $check = new ControlTaskCheck();
    $check->set('IDcontroltask', (int)$task->getId());
    $check->set('IDuser', $userId);
    $check->set('scheduled_for', $state['occurrenceAt']);
    $check->set('checked_at', $now);
    $result = $check->save();
    if (!is_array($result) || empty($result['status'])) { omoControlListActionResponse(false, omoControlListT('control.error.save')); }
    $detailUrl = '/omo/api/checklists/task_detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$task->getId() . $routeSuffix;
    omoControlListActionResponse(true, omoControlListT('control.success.checked'), ['detailUrl' => $detailUrl]);
}

if ($action === 'delete_task') {
    $task = new ControlTask();
    if (!$task->load((int)($_POST['id'] ?? 0))) { omoControlListActionResponse(false, omoControlListT('control.error.not_found')); }
    $list = $task->getList();
    if (!($list instanceof ControlList) || (int)$list->get('IDorganization') !== $organizationId || !omoControlListCanManage($list) || !$task->deleteWithRelatedData()) {
        omoControlListActionResponse(false, omoControlListT('control.error.forbidden'));
    }
    $detailUrl = '/omo/api/checklists/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$list->getId() . $routeSuffix;
    omoControlListActionResponse(true, omoControlListT('control.success.saved'), ['detailUrl' => $detailUrl]);
}

if ($action === 'delete_list') {
    $list = omoControlListLoad((int)($_POST['id'] ?? 0), $organizationId);
    if (!($list instanceof ControlList) || !omoControlListCanDelete($list) || !$list->deleteWithRelatedData()) {
        omoControlListActionResponse(false, omoControlListT('control.error.forbidden'));
    }
    $detailUrl = '/omo/api/checklists/index.php?oid=' . rawurlencode((string)$organizationId) . $routeSuffix;
    omoControlListActionResponse(true, omoControlListT('control.success.saved'), ['detailUrl' => $detailUrl]);
}

omoControlListActionResponse(false, omoControlListT('control.error.not_found'));
?>
