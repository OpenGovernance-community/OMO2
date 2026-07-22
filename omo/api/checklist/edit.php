<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Checklist;
use dbObject\ChecklistTrigger;
use dbObject\Holon;
use dbObject\Project;
use dbObject\RecurrenceSchedule;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$checklistId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoChecklistResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape((string)($context['message'] ?? omoChecklistT('checklist.error.context'))) . '</div>';
    exit;
}

$isEdit = $checklistId > 0;
$checklist = $isEdit ? omoChecklistLoad($checklistId, $organizationId) : new Checklist();
if ($isEdit && (!($checklist instanceof Checklist) || !omoChecklistCanManage($checklist))) {
    http_response_code($checklist instanceof Checklist ? 403 : 404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT($checklist instanceof Checklist ? 'checklist.error.forbidden' : 'checklist.error.not_found')) . '</div>';
    exit;
}
if (!$isEdit && !omoChecklistCanCreateContext($context)) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT('checklist.error.forbidden')) . '</div>';
    exit;
}

$templateRoot = $isEdit ? $checklist->getTemplateRoot() : new Project();
if (!($templateRoot instanceof Project)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT('checklist.error.not_found')) . '</div>';
    exit;
}

$trigger = $isEdit ? omoChecklistGetPrimaryTrigger($checklist) : null;
$triggerType = $trigger instanceof ChecklistTrigger
    ? ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type'))
    : ChecklistTrigger::TYPE_MANUAL;
$frequency = $trigger instanceof ChecklistTrigger ? RecurrenceSchedule::normalizeFrequency($trigger->get('frequency')) : null;
$schedule = $trigger instanceof ChecklistTrigger ? RecurrenceSchedule::normalizeSchedule($frequency, $trigger->get('schedule')) : null;
$overlapPolicy = $trigger instanceof ChecklistTrigger
    ? ChecklistTrigger::normalizeOverlapPolicy($trigger->get('overlap_policy'))
    : ChecklistTrigger::OVERLAP_REUSE_OPEN;
$scheduleOptions = [];
foreach (RecurrenceSchedule::getFrequencyCatalog() as $frequencyKey) {
    $scheduleOptions[$frequencyKey] = omoChecklistScheduleOptions($frequencyKey);
}
$detailUrl = '';
if ($isEdit) {
    $detailUrl = '/omo/api/checklist/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . rawurlencode((string)$checklistId);
    if ($currentHolonId > 0) {
        $detailUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
    }
}
?>
<div class="omo-checklist-editor" data-checklist-editor>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoChecklistT($isEdit ? 'checklist.form.edit_title' : 'checklist.form.create_title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape(omoChecklistT('checklist.form.base_intro')) ?>"
    >
        <button type="submit" form="omo-checklist-editor-form" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action><?= omoApiEscape(omoChecklistT('checklist.action.save')) ?></button>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-checklist-editor-cancel<?= $detailUrl !== '' ? ' data-url="' . omoApiEscape($detailUrl) . '"' : '' ?>><?= omoApiEscape(omoChecklistT('checklist.action.cancel')) ?></button>
    </div>
    <form
        id="omo-checklist-editor-form"
        action="/omo/api/checklist/action.php"
        method="post"
        data-checklist-editor-form
        data-schedule-options="<?= omoApiEscape(json_encode($scheduleOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    >
        <input type="hidden" name="checklist_action" value="save_checklist">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$checklistId ?>"><?php endif; ?>

        <section class="generic-section omo-checklist-editor__section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoChecklistT('checklist.form.identity')) ?></h3>
            <div class="omo-checklist-form-grid">
                <label class="omo-checklist-field omo-checklist-field--wide">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.title')) ?></span>
                    <input class="generic-form-control" type="text" name="title" value="<?= omoApiEscape((string)$templateRoot->get('title')) ?>" maxlength="255" required autofocus>
                </label>
                <label class="omo-checklist-field omo-checklist-field--wide">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.description')) ?></span>
                    <textarea class="generic-form-control" name="description" rows="5"><?= omoApiEscape((string)$templateRoot->get('description')) ?></textarea>
                </label>
                <label class="omo-checklist-field">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.status')) ?></span>
                    <select class="generic-form-control" name="status">
                        <?php foreach ([Checklist::STATUS_DRAFT, Checklist::STATUS_PUBLISHED] as $status): ?>
                            <option value="<?= omoApiEscape($status) ?>"<?= Checklist::normalizeStatus($checklist->get('status')) === $status ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistStatusLabel($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-checklist-field">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.revision_note')) ?></span>
                    <input class="generic-form-control" type="text" name="revision_note" value="<?= omoApiEscape((string)$checklist->get('revision_note')) ?>">
                </label>
            </div>
        </section>

        <section class="generic-soft-panel omo-checklist-editor__section" data-checklist-trigger-section>
            <div class="omo-checklist-editor__section-heading">
                <div>
                    <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoChecklistT('checklist.form.trigger')) ?></h3>
                    <p><?= omoApiEscape(omoChecklistT('checklist.form.trigger_help')) ?></p>
                </div>
            </div>
            <div class="omo-checklist-form-grid">
                <label class="omo-checklist-field">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.trigger_type')) ?></span>
                    <select class="generic-form-control" name="trigger_type" data-checklist-trigger-type>
                        <?php foreach ([ChecklistTrigger::TYPE_MANUAL, ChecklistTrigger::TYPE_SCHEDULED, ChecklistTrigger::TYPE_CONTAINER] as $triggerOption): ?>
                            <option value="<?= omoApiEscape($triggerOption) ?>"<?= $triggerType === $triggerOption ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistT('checklist.trigger.' . $triggerOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-checklist-field" data-checklist-recurrence-field<?= $triggerType === ChecklistTrigger::TYPE_SCHEDULED ? '' : ' hidden' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.frequency')) ?></span>
                    <select class="generic-form-control" name="frequency" data-checklist-frequency>
                        <option value=""><?= omoApiEscape(omoChecklistT('checklist.schedule.none')) ?></option>
                        <?php foreach (RecurrenceSchedule::getFrequencyCatalog() as $frequencyOption): ?>
                            <option value="<?= omoApiEscape($frequencyOption) ?>"<?= $frequency === $frequencyOption ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistFrequencyLabel($frequencyOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-checklist-field" data-checklist-recurrence-field<?= $triggerType === ChecklistTrigger::TYPE_SCHEDULED ? '' : ' hidden' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.schedule')) ?></span>
                    <select class="generic-form-control" name="schedule" data-checklist-schedule data-selected-value="<?= omoApiEscape((string)$schedule) ?>"></select>
                </label>
                <label class="omo-checklist-field" data-checklist-overlap-field<?= $triggerType === ChecklistTrigger::TYPE_CONTAINER ? ' hidden' : '' ?>>
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.overlap')) ?></span>
                    <select class="generic-form-control" name="overlap_policy">
                        <?php foreach ([ChecklistTrigger::OVERLAP_REUSE_OPEN, ChecklistTrigger::OVERLAP_CREATE_NEW, ChecklistTrigger::OVERLAP_SKIP, ChecklistTrigger::OVERLAP_ASK] as $policy): ?>
                            <option value="<?= omoApiEscape($policy) ?>"<?= $overlapPolicy === $policy ? ' selected' : '' ?>><?= omoApiEscape(omoChecklistT('checklist.overlap.' . $policy)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>
        <div class="omo-checklist-feedback" data-checklist-editor-feedback aria-live="polite"></div>
    </form>
</div>
