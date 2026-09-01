<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Checklist;
use dbObject\ChecklistItem;
use dbObject\ChecklistItemDependency;
use dbObject\ChecklistItemRecurrence;
use dbObject\ChecklistTrigger;
use dbObject\Holon;
use dbObject\Project;
use dbObject\RecurrenceSchedule;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$checklistId = isset($_GET['checklist_id']) && is_numeric($_GET['checklist_id']) ? (int)$_GET['checklist_id'] : 0;
$itemId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoChecklistResolveContext($organizationId, $currentHolonId);
$checklist = !empty($context['status']) ? omoChecklistLoad($checklistId, $organizationId) : null;
if (!($checklist instanceof Checklist) || !omoChecklistCanManage($checklist)) {
    http_response_code($checklist instanceof Checklist ? 403 : 404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT($checklist instanceof Checklist ? 'checklist.error.forbidden' : 'checklist.error.not_found')) . '</div>';
    exit;
}

$isEdit = $itemId > 0;
$item = new ChecklistItem();
$project = new Project();
if ($isEdit) {
    if (!$item->load($itemId) || (int)$item->get('IDchecklist') !== $checklistId || (int)$item->get('active') !== 1) {
        http_response_code(404);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT('checklist.error.item_not_found')) . '</div>';
        exit;
    }
    $project = $item->getProjectTemplate();
    if (!($project instanceof Project)) {
        http_response_code(404);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT('checklist.error.item_not_found')) . '</div>';
        exit;
    }
}

$templateRoot = $checklist->getTemplateRoot();
$primaryTrigger = omoChecklistGetPrimaryTrigger($checklist);
$isContainerChecklist = $primaryTrigger instanceof ChecklistTrigger
    && ChecklistTrigger::normalizeTriggerType($primaryTrigger->get('trigger_type')) === ChecklistTrigger::TYPE_CONTAINER;
$itemKind = $isContainerChecklist ? 'activity' : 'step';
$recurrence = $isEdit ? $item->getRecurrence() : null;
$recurrenceFrequency = $recurrence instanceof ChecklistItemRecurrence
    ? RecurrenceSchedule::normalizeFrequency($recurrence->get('frequency'))
    : null;
$recurrenceSchedule = $recurrence instanceof ChecklistItemRecurrence
    ? RecurrenceSchedule::normalizeSchedule($recurrenceFrequency, $recurrence->get('schedule'))
    : null;
$recurrenceDisplayLeadValue = $recurrence instanceof ChecklistItemRecurrence
    ? max(0, (int)$recurrence->get('display_lead_value'))
    : 0;
$recurrenceDisplayLeadUnit = $recurrence instanceof ChecklistItemRecurrence
    ? (string)($recurrence->get('display_lead_unit') ?: ChecklistItem::DELAY_DAY)
    : ChecklistItem::DELAY_DAY;
$recurrenceExecutionDurationValue = $recurrence instanceof ChecklistItemRecurrence
    ? max(0, (int)$recurrence->get('execution_duration_value'))
    : 0;
$recurrenceExecutionDurationUnit = $recurrence instanceof ChecklistItemRecurrence
    ? (string)($recurrence->get('execution_duration_unit') ?: ChecklistItem::DELAY_DAY)
    : ChecklistItem::DELAY_DAY;
$displayLeadValue = $isEdit ? max(0, (int)$item->get('display_lead_value')) : 0;
$displayLeadUnit = $isEdit
    ? (string)($item->get('display_lead_unit') ?: ChecklistItem::DELAY_DAY)
    : ChecklistItem::DELAY_DAY;
$executionDurationValue = $isEdit ? max(0, (int)$item->get('execution_duration_value')) : 0;
$executionDurationUnit = $isEdit
    ? (string)($item->get('execution_duration_unit') ?: ChecklistItem::DELAY_DAY)
    : ChecklistItem::DELAY_DAY;
if ($isContainerChecklist && $recurrence instanceof ChecklistItemRecurrence && $displayLeadValue === 0 && $executionDurationValue === 0) {
    $displayLeadValue = $recurrenceDisplayLeadValue;
    $displayLeadUnit = $recurrenceDisplayLeadUnit;
    $executionDurationValue = $recurrenceExecutionDurationValue;
    $executionDurationUnit = $recurrenceExecutionDurationUnit;
}
$recurrenceScheduleOptions = [];
foreach (RecurrenceSchedule::getFrequencyCatalog() as $frequencyKey) {
    $recurrenceScheduleOptions[$frequencyKey] = omoChecklistScheduleOptions($frequencyKey);
}
$defaultHolon = $templateRoot instanceof Project ? $templateRoot->getHolon() : null;
$selectedHolonId = $isEdit
    ? (int)$project->get('IDholon')
    : ($defaultHolon instanceof Holon ? (int)$defaultHolon->getId() : 0);
$holonOptions = omoChecklistBuildHolonOptions($context);
$relatedItems = [];
$selectedParentItemId = 0;
$selectedDependencyItemId = 0;
$dependencyDelayValue = 0;
$dependencyDelayUnit = ChecklistItem::DELAY_DAY;
foreach ($checklist->getItems(true) as $candidateItem) {
    if (!($candidateItem instanceof ChecklistItem) || (int)$candidateItem->getId() === $itemId) {
        continue;
    }
    $candidateProject = $candidateItem->getProjectTemplate();
    if (!($candidateProject instanceof Project)) {
        continue;
    }
    $relatedItems[] = [
        'id' => (int)$candidateItem->getId(),
        'project_id' => (int)$candidateProject->getId(),
        'title' => trim((string)$candidateProject->get('title')),
    ];
    if ($isEdit && (int)$project->get('IDproject_parent') === (int)$candidateProject->getId()) {
        $selectedParentItemId = (int)$candidateItem->getId();
    }
}
if ($isEdit) {
    foreach ($item->getDependencies() as $dependency) {
        if ($dependency instanceof ChecklistItemDependency) {
            $selectedDependencyItemId = (int)$dependency->get('IDchecklistitem_required');
            $dependencyDelayValue = max(0, (int)$dependency->get('delay_value'));
            $dependencyDelayUnit = (string)($dependency->get('delay_unit') ?: ChecklistItem::DELAY_DAY);
            break;
        }
    }
}

$activationType = $isEdit
    ? ChecklistItem::normalizeActivationType($item->get('activation_type'))
    : ChecklistItem::ACTIVATION_IMMEDIATE;
$delayValue = $activationType === ChecklistItem::ACTIVATION_AFTER_COMPLETION
    ? $dependencyDelayValue
    : (int)$item->get('delay_value');
$delayUnit = $activationType === ChecklistItem::ACTIVATION_AFTER_COMPLETION
    ? $dependencyDelayUnit
    : (string)($item->get('delay_unit') ?: ChecklistItem::DELAY_DAY);
$detailUrl = '/omo/api/checklist/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$checklistId);
if ($currentHolonId > 0) {
    $detailUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
?>
<div class="omo-checklist-editor omo-checklist-step-editor generic-drawer-content" data-checklist-editor>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoChecklistT('checklist.form.' . ($isEdit ? 'edit_' : 'create_') . $itemKind . '_title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape((string)$templateRoot->get('title')) ?>"
    >
        <button type="submit" form="omo-checklist-item-form" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action><?= omoApiEscape(omoChecklistT('checklist.action.save_' . $itemKind)) ?></button>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-checklist-back-detail data-url="<?= omoApiEscape($detailUrl) ?>"><?= omoApiEscape(omoChecklistT('checklist.action.cancel')) ?></button>
    </div>
    <form id="omo-checklist-item-form" class="generic-form-stack" action="/omo/api/checklist/action.php" method="post" data-checklist-item-form data-checklist-container="<?= $isContainerChecklist ? '1' : '0' ?>" data-item-schedule-options="<?= omoApiEscape(json_encode($recurrenceScheduleOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
        <input type="hidden" name="checklist_action" value="save_item">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
        <input type="hidden" name="checklist_id" value="<?= (int)$checklistId ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$itemId ?>"><?php endif; ?>

        <section class="generic-section generic-section--stack generic-form-section omo-checklist-item-editor" data-checklist-item-row>
            <div class="omo-checklist-form-grid generic-form-grid">
                <label class="omo-checklist-field omo-checklist-field--wide">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.' . $itemKind . '_title')) ?></span>
                    <input class="generic-form-control" type="text" name="title" value="<?= omoApiEscape((string)$project->get('title')) ?>" maxlength="255" required autofocus>
                </label>
                <div class="omo-checklist-field omo-checklist-field--wide">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.item_description')) ?></span>
                    <div class="omo-checklist-html-editor-container" data-checklist-html-editor-container>
                        <div
                            class="omo-checklist-html-editor"
                            data-checklist-html-editor
                            data-checklist-html-editor-placeholder="<?= omoApiEscape(omoChecklistT('checklist.form.' . $itemKind . '_description_placeholder')) ?>"
                        ></div>
                        <textarea name="description" hidden aria-hidden="true" data-checklist-html-value><?= omoApiEscape((string)$project->get('description')) ?></textarea>
                    </div>
                </div>
                <label class="omo-checklist-field omo-checklist-field--wide">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.holon')) ?></span>
                    <select class="generic-form-control" name="IDholon" required>
                        <?php foreach ($holonOptions as $option): ?>
                            <option value="<?= (int)$option['id'] ?>"<?= (int)$option['id'] === $selectedHolonId ? ' selected' : '' ?>><?= omoApiEscape((string)$option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-checklist-field" data-checklist-item-parent-field<?= $isContainerChecklist ? ' hidden' : '' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.parent')) ?></span>
                    <select class="generic-form-control" name="parent_item_id">
                        <option value="0"><?= omoApiEscape(omoChecklistT('checklist.form.parent_root')) ?></option>
                        <?php foreach ($relatedItems as $relatedItem): ?>
                            <option value="<?= (int)$relatedItem['id'] ?>"<?= (int)$relatedItem['id'] === $selectedParentItemId ? ' selected' : '' ?>><?= omoApiEscape((string)$relatedItem['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-checklist-field" data-checklist-item-activation-field<?= $isContainerChecklist ? ' hidden' : '' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.activation')) ?></span>
                    <select class="generic-form-control" name="activation_type" data-checklist-activation-select>
                        <?php foreach ([ChecklistItem::ACTIVATION_IMMEDIATE, ChecklistItem::ACTIVATION_AFTER_START, ChecklistItem::ACTIVATION_AFTER_COMPLETION] as $activationOption): ?>
                            <option value="<?= omoApiEscape($activationOption) ?>"<?= $activationType === $activationOption ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistActivationLabel($activationOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-checklist-field" data-checklist-dependency-field<?= $isContainerChecklist || $activationType !== ChecklistItem::ACTIVATION_AFTER_COMPLETION ? ' hidden' : '' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.dependency')) ?></span>
                    <select class="generic-form-control" name="dependency_item_id">
                        <option value="0"><?= omoApiEscape(omoChecklistT('checklist.form.select_item')) ?></option>
                        <?php foreach ($relatedItems as $relatedItem): ?>
                            <option value="<?= (int)$relatedItem['id'] ?>"<?= (int)$relatedItem['id'] === $selectedDependencyItemId ? ' selected' : '' ?>><?= omoApiEscape((string)$relatedItem['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-checklist-field" data-checklist-delay-field<?= $isContainerChecklist || $activationType === ChecklistItem::ACTIVATION_IMMEDIATE ? ' hidden' : '' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.delay')) ?></span>
                    <input class="generic-form-control" type="number" name="delay_value" value="<?= (int)$delayValue ?>" min="<?= $activationType === ChecklistItem::ACTIVATION_AFTER_COMPLETION ? '0' : '-3650' ?>" max="3650" step="1">
                </label>
                <label class="omo-checklist-field" data-checklist-delay-field<?= $isContainerChecklist || $activationType === ChecklistItem::ACTIVATION_IMMEDIATE ? ' hidden' : '' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.unit')) ?></span>
                    <select class="generic-form-control" name="delay_unit">
                        <?php foreach (ChecklistItem::delayUnits() as $unit): ?>
                            <option value="<?= omoApiEscape($unit) ?>"<?= $delayUnit === $unit ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistT('checklist.delay.' . $unit)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="generic-section generic-section--stack generic-form-section omo-checklist-field omo-checklist-field--wide">
                    <div class="omo-checklist-item-editor__header">
                        <div>
                            <h3 class="generic-card-title"><?= omoApiEscape(omoChecklistT('checklist.form.item_timing')) ?></h3>
                            <p><?= omoApiEscape(omoChecklistT('checklist.form.item_timing_help')) ?></p>
                        </div>
                    </div>
                    <div class="omo-checklist-form-grid generic-form-grid">
                        <label class="omo-checklist-field">
                            <span><?= omoApiEscape(omoChecklistT('checklist.form.display_lead')) ?></span>
                            <input class="generic-form-control" type="number" name="display_lead_value" value="<?= (int)$displayLeadValue ?>" min="0" max="3650" step="1">
                        </label>
                        <label class="omo-checklist-field">
                            <span><?= omoApiEscape(omoChecklistT('checklist.form.display_lead_unit')) ?></span>
                            <select class="generic-form-control" name="display_lead_unit">
                                <?php foreach (ChecklistItem::delayUnits() as $unit): ?>
                                    <option value="<?= omoApiEscape($unit) ?>"<?= $displayLeadUnit === $unit ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistT('checklist.delay.' . $unit)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="omo-checklist-field">
                            <span><?= omoApiEscape(omoChecklistT('checklist.form.execution_duration')) ?></span>
                            <input class="generic-form-control" type="number" name="execution_duration_value" value="<?= (int)$executionDurationValue ?>" min="0" max="3650" step="1">
                        </label>
                        <label class="omo-checklist-field">
                            <span><?= omoApiEscape(omoChecklistT('checklist.form.execution_duration_unit')) ?></span>
                            <select class="generic-form-control" name="execution_duration_unit">
                                <?php foreach (ChecklistItem::delayUnits() as $unit): ?>
                                    <option value="<?= omoApiEscape($unit) ?>"<?= $executionDurationUnit === $unit ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistT('checklist.delay.' . $unit)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </div>
                <?php if ($isContainerChecklist): ?>
                    <div class="generic-section generic-section--stack generic-form-section omo-checklist-field omo-checklist-field--wide" data-checklist-item-recurrence>
                        <div class="omo-checklist-item-editor__header">
                            <div>
                                <h3 class="generic-card-title"><?= omoApiEscape(omoChecklistT('checklist.form.item_recurrence')) ?></h3>
                                <p><?= omoApiEscape(omoChecklistT('checklist.form.item_recurrence_help')) ?></p>
                            </div>
                        </div>
                        <div class="omo-checklist-form-grid generic-form-grid">
                            <label class="omo-checklist-field">
                                <span><?= omoApiEscape(omoChecklistT('checklist.form.frequency')) ?></span>
                                <select class="generic-form-control" name="recurrence_frequency" data-checklist-item-frequency>
                                    <option value=""><?= omoApiEscape(omoChecklistT('checklist.schedule.none')) ?></option>
                                    <?php foreach (RecurrenceSchedule::getFrequencyCatalog() as $frequencyOption): ?>
                                        <option value="<?= omoApiEscape($frequencyOption) ?>"<?= $recurrenceFrequency === $frequencyOption ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistFrequencyLabel($frequencyOption)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="omo-checklist-field" data-checklist-item-schedule-field<?= $recurrenceFrequency === null ? ' hidden' : '' ?>>
                                <span><?= omoApiEscape(omoChecklistT('checklist.form.schedule')) ?></span>
                                <select class="generic-form-control" name="recurrence_schedule" data-checklist-item-schedule data-selected-value="<?= omoApiEscape((string)$recurrenceSchedule) ?>"></select>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <label class="omo-checklist-field">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.priority')) ?></span>
                    <select class="generic-form-control" name="priority">
                        <option value="">—</option>
                        <?php for ($level = 1; $level <= 5; $level++): ?><option value="<?= $level ?>"<?= (int)$project->get('priority') === $level ? ' selected' : '' ?>>P<?= $level ?></option><?php endfor; ?>
                    </select>
                </label>
                <label class="omo-checklist-field">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.importance')) ?></span>
                    <select class="generic-form-control" name="importance">
                        <option value="">—</option>
                        <?php for ($level = 1; $level <= 5; $level++): ?><option value="<?= $level ?>"<?= (int)$project->get('importance') === $level ? ' selected' : '' ?>><?= $level ?>/5</option><?php endfor; ?>
                    </select>
                </label>
                <label class="omo-checklist-field">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.size')) ?></span>
                    <select class="generic-form-control" name="project_size">
                        <?php foreach (Project::sizes() as $size): ?><option value="<?= omoApiEscape($size) ?>"<?= Project::normalizeSize($project->get('project_size')) === $size ? ' selected' : '' ?>><?= omoApiEscape($size) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>
        <div class="omo-checklist-feedback" data-checklist-editor-feedback aria-live="polite"></div>
    </form>
</div>
