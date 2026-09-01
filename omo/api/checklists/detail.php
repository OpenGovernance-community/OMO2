<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlList;
use dbObject\ControlTask;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoControlListResolveContext($organizationId, $currentHolonId);
$list = !empty($context['status']) ? omoControlListLoad((int)($_GET['id'] ?? 0), $organizationId) : null;
if (!($list instanceof ControlList) || !omoControlListCanView($list)) {
    http_response_code($list instanceof ControlList ? 403 : 404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoControlListT($list instanceof ControlList ? 'control.error.forbidden' : 'control.error.not_found')) . '</div>';
    exit;
}
$now = new DateTimeImmutable('now');
$canManage = omoControlListCanManage($list);
$canDelete = omoControlListCanDelete($list);
$returnUrl = '/omo/api/checklists/index.php?oid=' . rawurlencode((string)$organizationId) . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
$editUrl = '/omo/api/checklists/edit.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$list->getId() . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
$taskEditUrl = '/omo/api/checklists/task_edit.php?oid=' . rawurlencode((string)$organizationId) . '&list_id=' . (int)$list->getId() . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
?>
<div class="omo-control-list-detail generic-drawer-content" data-control-drawer-title="<?= omoApiEscape((string)$list->get('title')) ?>" data-control-drawer-description="<?= omoApiEscape(omoControlListT('control.drawer.description')) ?>">
    <div class="omo-control-list-detail__actions">
        <button type="button" class="generic-action-button generic-action-button--secondary" data-control-open-url="<?= omoApiEscape($returnUrl) ?>"><?= omoApiEscape(omoControlListT('control.action.back')) ?></button>
        <?php if ($canManage): ?><button type="button" class="generic-action-button generic-action-button--secondary" data-control-open-url="<?= omoApiEscape($editUrl) ?>"><?= omoApiEscape(omoControlListT('control.action.edit')) ?></button><button type="button" class="generic-action-button generic-action-button--main" data-control-open-url="<?= omoApiEscape($taskEditUrl) ?>"><?= omoApiEscape(omoControlListT('control.action.add_task')) ?></button><?php endif; ?>
        <?php if ($canDelete): ?><button type="button" class="generic-action-button generic-action-button--danger" data-control-post-action="delete_list" data-control-id="<?= (int)$list->getId() ?>" data-control-confirm="Supprimer cette liste et son historique ?"><?= omoApiEscape(omoControlListT('control.action.delete')) ?></button><?php endif; ?>
    </div>
    <?php if (trim((string)$list->get('description')) !== ''): ?><section class="generic-section generic-section--stack"><p class="omo-control-list-detail__description"><?= nl2br(omoApiEscape(trim((string)$list->get('description')))) ?></p></section><?php endif; ?>
    <section class="generic-section generic-section--stack"><h3 class="generic-card-title">Activités</h3>
        <?php $tasks = $list->getTasks(); if (count($tasks) === 0): ?><div class="omo-empty-state"><?= omoApiEscape(omoControlListT('control.empty.tasks')) ?></div>
        <?php else: ?><div class="omo-control-task-list"><?php foreach ($tasks as $task): if (!($task instanceof ControlTask)) { continue; } $state = $task->getOccurrenceState($now); $taskDetailUrl = '/omo/api/checklists/task_detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$task->getId() . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : ''); $check = $state['check'] ?? null; ?>
            <article class="omo-control-task omo-control-task--<?= omoApiEscape((string)($state['state'] ?? 'invalid')) ?>">
                <button type="button" class="omo-control-task__open" data-control-open-url="<?= omoApiEscape($taskDetailUrl) ?>"><strong><?= omoApiEscape($task->get('title')) ?></strong><span><?= omoApiEscape(omoControlListFrequencyLabel($task->get('frequency')) . ' · ' . omoControlListScheduleLabel($task->get('frequency'), $task->get('schedule'))) ?></span></button>
                <div class="omo-control-task__status"><span class="omo-control-task__badge"><?= omoApiEscape(omoControlListT('control.state.' . ($state['state'] ?? 'upcoming'))) ?></span><?php if ($check): ?><time><?= omoApiEscape($check->get('checked_at')->format('d.m.Y H:i')) ?></time><?php elseif (($state['state'] ?? '') === 'due' || ($state['state'] ?? '') === 'overdue'): ?><button type="button" class="generic-action-button generic-action-button--main" data-control-post-action="check_task" data-control-id="<?= (int)$task->getId() ?>"><?= omoApiEscape(omoControlListT('control.action.check')) ?></button><?php endif; ?></div>
            </article>
        <?php endforeach; ?></div><?php endif; ?>
    </section>
</div>
