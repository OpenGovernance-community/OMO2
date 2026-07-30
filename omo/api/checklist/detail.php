<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Checklist;
use dbObject\ChecklistItem;
use dbObject\ChecklistItemDependency;
use dbObject\ArrayChecklistItemOccurrence;
use dbObject\ChecklistTrigger;
use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$checklistId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoChecklistResolveContext($organizationId, $currentHolonId);
$checklist = !empty($context['status']) ? omoChecklistLoad($checklistId, $organizationId) : null;
if (!($checklist instanceof Checklist) || !omoChecklistCanView($checklist)) {
    http_response_code($checklist instanceof Checklist ? 403 : 404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT($checklist instanceof Checklist ? 'checklist.error.forbidden' : 'checklist.error.not_found')) . '</div>';
    exit;
}

$templateRoot = $checklist->getTemplateRoot();
if (!($templateRoot instanceof Project)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT('checklist.error.not_found')) . '</div>';
    exit;
}

$items = [];
$itemsById = [];
$itemsByProjectId = [];
foreach ($checklist->getItems(true) as $item) {
    if ($item instanceof ChecklistItem) {
        $items[] = $item;
        $itemsById[(int)$item->getId()] = $item;
        $itemsByProjectId[(int)$item->get('IDproject_template')] = $item;
    }
}
$trigger = omoChecklistGetPrimaryTrigger($checklist);
$isContainerChecklist = $trigger instanceof ChecklistTrigger
    && ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type')) === ChecklistTrigger::TYPE_CONTAINER;
$openRuns = [];
foreach ($checklist->getOpenRuns() as $run) {
    if ($run instanceof \dbObject\ChecklistRun) {
        $runRootProject = $run->getRootProject();
        $runItems = $run->getItems();
        $openRuns[] = [
            'run' => $run,
            'root' => $runRootProject instanceof Project ? $runRootProject : null,
            'itemCount' => count($runItems),
        ];
    }
}
$projectInstancesByItem = [];
$today = new DateTimeImmutable('today');
foreach ($checklist->getRuns() as $run) {
    if (!($run instanceof \dbObject\ChecklistRun)) {
        continue;
    }
    foreach ($run->getItems() as $runItem) {
        if (!($runItem instanceof \dbObject\ChecklistRunItem)) {
            continue;
        }
        $project = $runItem->getProject();
        if (!($project instanceof Project) || (int)$project->get('active') !== 1) {
            continue;
        }
        $projectInstancesByItem[(int)$runItem->get('IDchecklistitem')][] = [
            'project' => $project,
            'status' => Project::normalizeStatus($project->get('status')),
            'sortAt' => $runItem->get('activation_at'),
            'overdue' => Project::normalizeStatus($project->get('status')) !== Project::STATUS_DONE
                && ($project->get('planned_end_date') instanceof DateTimeInterface)
                && $project->get('planned_end_date')->format('Y-m-d') < $today->format('Y-m-d'),
        ];
    }
}
if ($isContainerChecklist) {
    foreach ($items as $item) {
        $occurrences = new ArrayChecklistItemOccurrence();
        $occurrences->loadForItem((int)$item->getId());
        foreach ($occurrences as $occurrence) {
            if (!($occurrence instanceof \dbObject\ChecklistItemOccurrence) || (int)$occurrence->get('IDproject') <= 0) {
                continue;
            }
            $project = new Project();
            if (!$project->load((int)$occurrence->get('IDproject')) || (int)$project->get('active') !== 1) {
                continue;
            }
            $instance = [
                'occurrence' => $occurrence,
                'project' => $project,
                'status' => Project::normalizeStatus($project->get('status')),
                'sortAt' => $occurrence->get('scheduled_for'),
                'overdue' => Project::normalizeStatus($project->get('status')) !== Project::STATUS_DONE
                    && ($project->get('planned_end_date') instanceof DateTimeInterface)
                    && $project->get('planned_end_date')->format('Y-m-d') < $today->format('Y-m-d'),
            ];
            $projectInstancesByItem[(int)$item->getId()][] = $instance;
        }
    }
}
foreach ($projectInstancesByItem as &$itemInstances) {
    usort($itemInstances, static function (array $left, array $right) {
        $leftDate = $left['sortAt'] ?? null;
        $rightDate = $right['sortAt'] ?? null;
        $leftValue = $leftDate instanceof DateTimeInterface ? $leftDate->format('Y-m-d H:i:s') : '';
        $rightValue = $rightDate instanceof DateTimeInterface ? $rightDate->format('Y-m-d H:i:s') : '';
        return strcmp($leftValue, $rightValue);
    });
}
unset($itemInstances);
$rootHolon = $templateRoot->getHolon();
$rootHolonLabel = $rootHolon instanceof Holon ? trim((string)$rootHolon->getDisplayName()) : '';
$updatedAt = $checklist->get('updated_at');
$canEdit = omoChecklistCanManage($checklist);
$canActivate = omoChecklistCanActivate($checklist, $trigger);
$editUrl = '/omo/api/checklist/edit.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$checklistId);
$itemCreateUrl = '/omo/api/checklist/item_edit.php?oid=' . rawurlencode((string)$organizationId) . '&checklist_id=' . rawurlencode((string)$checklistId);
$activationUrl = '/omo/api/checklist/activate.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$checklistId);
if ($currentHolonId > 0) {
    $editUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
    $itemCreateUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
    $activationUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}

$formatDelay = static function ($value, $unit) {
    $value = (int)$value;
    if ($value === 0) {
        return omoChecklistT('checklist.detail.no_delay');
    }
    $unit = ChecklistItem::normalizeDelayUnit($unit) ?: ChecklistItem::DELAY_DAY;
    return $value . ' ' . omoChecklistT('checklist.delay.' . $unit);
};
?>
<div class="omo-checklist-detail">
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape((string)$templateRoot->get('title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape(omoChecklistT('checklist.detail.item_count', ['count' => count($items)])) ?>"
    >
        <?php if ($canActivate): ?>
            <button type="button" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action data-checklist-open-activation data-url="<?= omoApiEscape($activationUrl) ?>"><?= omoApiEscape(omoChecklistT('checklist.action.activate')) ?></button>
        <?php endif; ?>
        <?php if ($canEdit): ?>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-checklist-open-edit data-url="<?= omoApiEscape($editUrl) ?>"><?= omoApiEscape(omoChecklistT('checklist.action.edit')) ?></button>
        <?php endif; ?>
    </div>

    <section class="generic-hero-panel omo-checklist-detail__hero">
        <div class="omo-checklist-detail__hero-copy">
            <span class="omo-checklist-status omo-checklist-status--<?= omoApiEscape(Checklist::normalizeStatus($checklist->get('status'))) ?>"><?= omoApiEscape(omoChecklistStatusLabel($checklist->get('status'))) ?></span>
            <h3 class="generic-card-title generic-card-title--large"><?= omoApiEscape((string)$templateRoot->get('title')) ?></h3>
            <?php if (trim((string)$templateRoot->get('description')) !== ''): ?>
                <div class="omo-simple-html-render omo-checklist-detail__description generic-description generic-description--relaxed"><?= (string)$templateRoot->get('description') ?></div>
            <?php else: ?>
                <p><?= omoApiEscape(omoChecklistT('checklist.detail.no_description')) ?></p>
            <?php endif; ?>
        </div>
        <dl class="omo-checklist-detail__summary">
            <div><dt><?= omoApiEscape(omoChecklistT('checklist.detail.context')) ?></dt><dd><?= omoApiEscape($rootHolonLabel) ?></dd></div>
            <div><dt><?= omoApiEscape(omoChecklistT('checklist.detail.trigger')) ?></dt><dd><?= omoApiEscape(omoChecklistTriggerLabel($trigger)) ?></dd></div>
            <div><dt><?= omoApiEscape(omoChecklistT('checklist.detail.updated')) ?></dt><dd><?= omoApiEscape($updatedAt instanceof DateTimeInterface ? $updatedAt->format('d.m.Y H:i') : '') ?></dd></div>
        </dl>
    </section>

    <?php if (!$isContainerChecklist): ?>
        <section class="omo-checklist-detail__section">
            <div class="omo-checklist-detail__section-heading">
                <div>
                    <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoChecklistT('checklist.detail.open_runs')) ?></h3>
                    <p><?= omoApiEscape(omoChecklistT('checklist.detail.open_run_count', ['count' => count($openRuns)])) ?></p>
                </div>
            </div>
            <?php if (count($openRuns) === 0): ?>
                <div class="omo-empty-state"><?= omoApiEscape(omoChecklistT('checklist.detail.empty_runs')) ?></div>
            <?php else: ?>
                <div class="omo-checklist-runs">
                    <?php foreach ($openRuns as $runRow): ?>
                        <?php
                        $run = $runRow['run'];
                        $runRootProject = $runRow['root'];
                        $referenceAt = $run->getReferenceAt();
                        $createdAt = $run->get('created_at');
                        ?>
                        <article class="generic-section omo-checklist-run-card">
                            <div>
                                <h4><?= omoApiEscape($runRootProject instanceof Project ? (string)$runRootProject->get('title') : (string)$templateRoot->get('title')) ?></h4>
                                <span class="omo-checklist-run-card__status"><?= omoApiEscape(omoChecklistT('checklist.run.status.running')) ?></span>
                            </div>
                            <dl>
                                <div><dt><?= omoApiEscape(omoChecklistT('checklist.detail.reference_date')) ?></dt><dd><?= omoApiEscape($referenceAt instanceof DateTimeInterface ? $referenceAt->format('d.m.Y') : '') ?></dd></div>
                                <div><dt><?= omoApiEscape(omoChecklistT('checklist.detail.activated_at')) ?></dt><dd><?= omoApiEscape($createdAt instanceof DateTimeInterface ? $createdAt->format('d.m.Y H:i') : '') ?></dd></div>
                                <div><dt><?= omoApiEscape(omoChecklistT('checklist.detail.items')) ?></dt><dd><?= omoApiEscape(omoChecklistT('checklist.detail.run_item_count', ['count' => (int)$runRow['itemCount']])) ?></dd></div>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="omo-checklist-detail__section">
        <div class="omo-checklist-detail__section-heading">
            <div>
                <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoChecklistT('checklist.detail.items')) ?></h3>
                <p><?= omoApiEscape(omoChecklistT('checklist.detail.item_count', ['count' => count($items)])) ?></p>
            </div>
            <?php if ($canEdit): ?>
                <button type="button" class="generic-action-button generic-action-button--main" data-checklist-open-item-form data-url="<?= omoApiEscape($itemCreateUrl) ?>"><?= omoApiEscape(omoChecklistT('checklist.action.add_item')) ?></button>
            <?php endif; ?>
        </div>
        <div class="omo-checklist-flow">
            <?php if (count($items) === 0): ?>
                <div class="omo-empty-state"><?= omoApiEscape(omoChecklistT('checklist.detail.empty_items')) ?></div>
            <?php endif; ?>
            <?php foreach ($items as $position => $item): ?>
                <?php
                $project = $item->getProjectTemplate();
                if (!($project instanceof Project)) {
                    continue;
                }
                $itemHolon = $project->getHolon();
                $itemHolonLabel = $itemHolon instanceof Holon ? trim((string)$itemHolon->getDisplayName()) : '';
                $activationType = ChecklistItem::normalizeActivationType($item->get('activation_type'));
                $relationLabel = $isContainerChecklist ? '' : omoChecklistActivationLabel($activationType);
                $delayLabel = $isContainerChecklist ? '' : $formatDelay($item->get('delay_value'), $item->get('delay_unit'));
                $recurrenceLabel = '';
                $recurrenceTimingLabels = [];
                $recurrence = $item->getRecurrence();
                if ($isContainerChecklist && $recurrence instanceof \dbObject\ChecklistItemRecurrence && (int)$recurrence->get('enabled') === 1) {
                    $recurrenceFrequency = \dbObject\RecurrenceSchedule::normalizeFrequency($recurrence->get('frequency'));
                    $recurrenceSchedule = omoChecklistScheduleLabel($recurrenceFrequency, $recurrence->get('schedule'));
                    if ($recurrenceFrequency !== null && $recurrenceSchedule !== '') {
                        $recurrenceLabel = omoChecklistT('checklist.detail.recurrence', [
                            'schedule' => trim(omoChecklistFrequencyLabel($recurrenceFrequency) . ' · ' . $recurrenceSchedule),
                        ]);
                    }
                    if ($recurrence->getDisplayLeadValue() > 0) {
                        $recurrenceTimingLabels[] = omoChecklistT('checklist.detail.display_lead', [
                            'delay' => $formatDelay($recurrence->getDisplayLeadValue(), $recurrence->getDisplayLeadUnit()),
                        ]);
                    }
                    if ($recurrence->getExecutionDurationValue() > 0) {
                        $recurrenceTimingLabels[] = omoChecklistT('checklist.detail.execution_duration', [
                            'delay' => $formatDelay($recurrence->getExecutionDurationValue(), $recurrence->getExecutionDurationUnit()),
                        ]);
                    }
                }
                if (!$isContainerChecklist && $activationType === ChecklistItem::ACTIVATION_AFTER_COMPLETION) {
                    foreach ($item->getDependencies() as $dependency) {
                        if (!($dependency instanceof ChecklistItemDependency)) {
                            continue;
                        }
                        $requiredItem = $itemsById[(int)$dependency->get('IDchecklistitem_required')] ?? null;
                        $requiredProject = $requiredItem instanceof ChecklistItem ? $requiredItem->getProjectTemplate() : null;
                        if ($requiredProject instanceof Project) {
                            $relationLabel .= ' · ' . (string)$requiredProject->get('title');
                        }
                        $delayLabel = $formatDelay($dependency->get('delay_value'), $dependency->get('delay_unit'));
                        break;
                    }
                }
                $parentItem = $isContainerChecklist ? null : ($itemsByProjectId[(int)$project->get('IDproject_parent')] ?? null);
                $parentProject = $parentItem instanceof ChecklistItem ? $parentItem->getProjectTemplate() : null;
                $itemProjectInstances = $projectInstancesByItem[(int)$item->getId()] ?? [];
                $itemEditUrl = '/omo/api/checklist/item_edit.php?oid=' . rawurlencode((string)$organizationId)
                    . '&checklist_id=' . rawurlencode((string)$checklistId)
                    . '&id=' . rawurlencode((string)$item->getId());
                if ($currentHolonId > 0) {
                    $itemEditUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
                }
                ?>
                <article class="generic-section omo-checklist-flow__item" style="--checklist-depth: <?= $parentProject instanceof Project ? '1' : '0' ?>">
                    <div class="omo-checklist-flow__index"><?= (int)$position + 1 ?></div>
                    <div class="omo-checklist-flow__copy">
                        <div class="omo-checklist-flow__title-row">
                            <h4 class="generic-title generic-title--item"><?= omoApiEscape((string)$project->get('title')) ?></h4>
                            <div class="omo-checklist-flow__title-actions">
                                <span class="omo-checklist-flow__role"><?= omoApiEscape($itemHolonLabel) ?></span>
                                <?php if ($canEdit): ?><button type="button" class="generic-action-button generic-action-button--secondary" data-checklist-open-item-form data-url="<?= omoApiEscape($itemEditUrl) ?>"><?= omoApiEscape(omoChecklistT('checklist.action.edit_item')) ?></button><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($parentProject instanceof Project): ?><div class="omo-checklist-flow__parent generic-description generic-description--small"><?= omoApiEscape(omoChecklistT('checklist.form.parent')) ?> : <?= omoApiEscape((string)$parentProject->get('title')) ?></div><?php endif; ?>
                        <?php if (trim((string)$project->get('description')) !== ''): ?><div class="omo-simple-html-render omo-checklist-flow__description generic-description generic-description--small"><?= (string)$project->get('description') ?></div><?php endif; ?>
                        <?php if (count($itemProjectInstances) > 0): ?>
                            <div class="omo-checklist-flow__instances" aria-label="<?= omoApiEscape(omoChecklistT('checklist.detail.project_instance_count', ['count' => count($itemProjectInstances)])) ?>">
                                <?php foreach ($itemProjectInstances as $instance): ?>
                                    <?php
                                    $instanceProject = $instance['project'];
                                    $plannedStart = $instanceProject->get('planned_start_date');
                                    $plannedEnd = $instanceProject->get('planned_end_date');
                                    $tooltipParts = [
                                        (string)$instanceProject->get('title'),
                                        omoChecklistT('checklist.detail.project_status', [
                                            'status' => omoChecklistT('checklist.detail.project_status.' . $instance['status']),
                                        ]),
                                        omoChecklistT('checklist.detail.recurring_planned_start', [
                                            'date' => $plannedStart instanceof DateTimeInterface ? $plannedStart->format('d.m.Y') : '',
                                        ]),
                                    ];
                                    if ($plannedEnd instanceof DateTimeInterface) {
                                        $tooltipParts[] = omoChecklistT('checklist.detail.recurring_deadline', [
                                            'date' => $plannedEnd->format('d.m.Y'),
                                        ]);
                                    }
                                    if (!empty($instance['overdue'])) {
                                        $tooltipParts[] = omoChecklistT('checklist.detail.overdue');
                                    }
                                    ?>
                                    <a
                                        class="omo-checklist-flow__instance omo-checklist-flow__instance--<?= omoApiEscape($instance['status']) ?><?= !empty($instance['overdue']) ? ' omo-checklist-flow__instance--overdue' : '' ?>"
                                        href="#projects-d<?= (int)$instanceProject->getId() ?>"
                                        title="<?= omoApiEscape(implode(' - ', $tooltipParts)) ?>"
                                        aria-label="<?= omoApiEscape(implode(' - ', $tooltipParts)) ?>"
                                    ></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="omo-checklist-flow__meta">
                            <?php if ($relationLabel !== ''): ?><span><?= omoApiEscape($relationLabel) ?></span><?php endif; ?>
                            <?php if (!$isContainerChecklist && ($activationType !== ChecklistItem::ACTIVATION_IMMEDIATE || $delayLabel !== omoChecklistT('checklist.detail.no_delay'))): ?><span><?= omoApiEscape($delayLabel) ?></span><?php endif; ?>
                            <?php if ($recurrenceLabel !== ''): ?><span><?= omoApiEscape($recurrenceLabel) ?></span><?php endif; ?>
                            <?php foreach ($recurrenceTimingLabels as $timingLabel): ?><span><?= omoApiEscape($timingLabel) ?></span><?php endforeach; ?>
                            <?php if ($project->get('priority') !== null && $project->get('priority') !== ''): ?><span>P<?= (int)$project->get('priority') ?></span><?php endif; ?>
                            <?php if ($project->get('importance') !== null && $project->get('importance') !== ''): ?><span>IS<?= (int)$project->get('importance') ?>/5</span><?php endif; ?>
                            <span><?= omoApiEscape(Project::normalizeSize($project->get('project_size'))) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
