<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlList;
use dbObject\ControlTask;
use dbObject\RecurrenceSchedule;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoControlListResolveContext($organizationId, $currentHolonId);
$taskId = (int)($_GET['id'] ?? 0);
$task = new ControlTask();
$task = $taskId > 0 && $task->load($taskId) ? $task : new ControlTask();
$listId = $taskId > 0 ? (int)$task->get('IDcontrollist') : (int)($_GET['list_id'] ?? 0);
$list = omoControlListLoad($listId, $organizationId);
if (empty($context['status']) || !($list instanceof ControlList) || !omoControlListCanManage($list) || ($taskId > 0 && (int)$task->get('IDcontrollist') !== $listId)) {
    http_response_code(403); echo '<div class="omo-empty-state">' . omoApiEscape(omoControlListT('control.error.forbidden')) . '</div>'; exit;
}
$frequency = RecurrenceSchedule::normalizeFrequency($task->get('frequency')) ?: RecurrenceSchedule::FREQUENCY_WEEKLY;
$scheduleOptions = omoControlListScheduleOptions();
$schedule = RecurrenceSchedule::normalizeSchedule($frequency, $task->get('schedule')) ?: (string)($scheduleOptions[$frequency][0]['value'] ?? '1');
$returnUrl = '/omo/api/checklists/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . $listId . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
?>
<div class="omo-control-list-detail generic-drawer-content" data-control-drawer-title="<?= $taskId > 0 ? 'Modifier l activité' : 'Nouvelle activité' ?>" data-control-drawer-description="<?= omoApiEscape((string)$list->get('title')) ?>">
    <form class="generic-form-stack" action="/omo/api/checklists/action.php" method="post" data-control-form data-control-task-form data-control-schedule-options="<?= omoApiEscape(json_encode($scheduleOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
        <input type="hidden" name="control_action" value="save_task"><input type="hidden" name="oid" value="<?= $organizationId ?>"><input type="hidden" name="cid" value="<?= $currentHolonId ?>"><input type="hidden" name="list_id" value="<?= $listId ?>"><?php if ($taskId > 0): ?><input type="hidden" name="id" value="<?= $taskId ?>"><?php endif; ?>
        <section class="generic-section generic-section--stack"><div class="omo-control-form-grid"><label class="omo-control-field omo-control-field--wide"><span><?= omoApiEscape(omoControlListT('control.field.title')) ?></span><input class="generic-form-control" type="text" name="title" maxlength="255" required autofocus value="<?= omoApiEscape($task->get('title')) ?>"></label><label class="omo-control-field omo-control-field--wide"><span><?= omoApiEscape(omoControlListT('control.field.description')) ?></span><textarea class="generic-form-control" name="description" rows="5"><?= omoApiEscape($task->get('description')) ?></textarea></label><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.frequency')) ?></span><select class="generic-form-control" name="frequency" data-control-frequency><?php foreach (RecurrenceSchedule::getFrequencyCatalog() as $option): ?><option value="<?= omoApiEscape($option) ?>"<?= $option === $frequency ? ' selected' : '' ?>><?= omoApiEscape(omoControlListFrequencyLabel($option)) ?></option><?php endforeach; ?></select></label><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.reference')) ?></span><select class="generic-form-control" name="schedule" data-control-schedule data-selected-value="<?= omoApiEscape($schedule) ?>"></select></label></div></section>
        <section class="generic-section generic-section--stack"><h3 class="generic-card-title">Affichage et retard</h3><div class="omo-control-form-grid"><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.display_lead')) ?></span><input class="generic-form-control" type="number" name="display_lead_value" min="0" max="3650" value="<?= (int)$task->get('display_lead_value') ?>"></label><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.unit')) ?></span><select class="generic-form-control" name="display_lead_unit"><?php foreach (ControlTask::delayUnits() as $unit): ?><option value="<?= $unit ?>"<?= $unit === (string)$task->get('display_lead_unit') ? ' selected' : '' ?>><?= omoApiEscape(omoControlListT('control.delay.' . $unit)) ?></option><?php endforeach; ?></select></label><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.overdue_after')) ?></span><input class="generic-form-control" type="number" name="execution_duration_value" min="1" max="3650" value="<?= max(1, (int)$task->get('execution_duration_value')) ?>"></label><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.unit')) ?></span><select class="generic-form-control" name="execution_duration_unit"><?php foreach (ControlTask::delayUnits() as $unit): ?><option value="<?= $unit ?>"<?= $unit === ((string)$task->get('execution_duration_unit') ?: ControlTask::DELAY_DAY) ? ' selected' : '' ?>><?= omoApiEscape(omoControlListT('control.delay.' . $unit)) ?></option><?php endforeach; ?></select></label></div></section>
        <div class="omo-control-list-detail__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-control-open-url="<?= omoApiEscape($returnUrl) ?>">Annuler</button><button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoControlListT('control.action.save')) ?></button></div><div class="omo-control-feedback" data-control-feedback aria-live="polite"></div>
    </form>
</div>
