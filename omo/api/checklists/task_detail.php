<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlTask;
use dbObject\ControlTaskCheck;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoControlListResolveContext($organizationId, $currentHolonId);
$task = new ControlTask();
$task = !empty($context['status']) && $task->load((int)($_GET['id'] ?? 0)) ? $task : null;
$list = $task instanceof ControlTask ? $task->getList() : null;
if (!($task instanceof ControlTask) || !($list instanceof \dbObject\ControlList) || (int)$list->get('IDorganization') !== $organizationId || !omoControlListCanView($list)) {
    http_response_code(404); echo '<div class="omo-empty-state">' . omoApiEscape(omoControlListT('control.error.not_found')) . '</div>'; exit;
}
$now = new DateTimeImmutable('now');
$state = $task->getOccurrenceState($now);
$canManage = omoControlListCanManage($list);
$returnUrl = '/omo/api/checklists/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$list->getId() . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
$editUrl = '/omo/api/checklists/task_edit.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$task->getId() . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
?>
<div class="omo-control-list-detail generic-drawer-content" data-control-drawer-title="<?= omoApiEscape((string)$task->get('title')) ?>" data-control-drawer-description="<?= omoApiEscape((string)$list->get('title')) ?>">
    <div class="omo-control-list-detail__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-control-open-url="<?= omoApiEscape($returnUrl) ?>">Retour à la liste</button><?php if ($canManage): ?><button type="button" class="generic-action-button generic-action-button--secondary" data-control-open-url="<?= omoApiEscape($editUrl) ?>"><?= omoApiEscape(omoControlListT('control.action.edit')) ?></button><button type="button" class="generic-action-button generic-action-button--danger" data-control-post-action="delete_task" data-control-id="<?= (int)$task->getId() ?>" data-control-confirm="Supprimer cette activité et son historique ?"><?= omoApiEscape(omoControlListT('control.action.delete')) ?></button><?php endif; ?></div>
    <section class="generic-section generic-section--stack"><div class="omo-control-task__status"><span class="omo-control-task__badge omo-control-task__badge--<?= omoApiEscape((string)$state['state']) ?>"><?= omoApiEscape(omoControlListT('control.state.' . $state['state'])) ?></span><?php if (($state['state'] ?? '') === 'due' || ($state['state'] ?? '') === 'overdue'): ?><button type="button" class="generic-action-button generic-action-button--main" data-control-post-action="check_task" data-control-id="<?= (int)$task->getId() ?>"><?= omoApiEscape(omoControlListT('control.action.check')) ?></button><?php endif; ?></div><p><strong><?= omoApiEscape(omoControlListFrequencyLabel($task->get('frequency'))) ?></strong> · <?= omoApiEscape(omoControlListScheduleLabel($task->get('frequency'), $task->get('schedule'))) ?></p><?php if (trim((string)$task->get('description')) !== ''): ?><p><?= nl2br(omoApiEscape(trim((string)$task->get('description')))) ?></p><?php endif; ?></section>
    <section class="generic-section generic-section--stack"><h3 class="generic-card-title"><?= omoApiEscape(omoControlListT('control.regularity')) ?></h3><div class="omo-control-regularity" role="list"><?php foreach ($task->getRegularity(12, $now) as $entry): $check = $entry['check']; $person = $check instanceof ControlTaskCheck ? $check->getUser() : null; $label = $entry['occurrenceAt']->format('d.m.Y') . ($person ? ' · ' . $person->getScopedDisplayName($organizationId) : ''); ?><span class="omo-control-regularity__entry omo-control-regularity__entry--<?= omoApiEscape($entry['state']) ?>" role="listitem" title="<?= omoApiEscape($label) ?>"><i></i><small><?= omoApiEscape($entry['occurrenceAt']->format('d.m')) ?></small></span><?php endforeach; ?></div></section>
</div>
