<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlActivity;
use dbObject\RecurrenceSchedule;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = (int)($_GET['cid'] ?? 0);
$context = omoActivityResolveContext($organizationId, $currentHolonId);
$activityId = (int)($_GET['id'] ?? 0);
$activity = new ControlActivity();
$activity = $activityId > 0 && $activity->load($activityId) ? $activity : new ControlActivity();
if (empty($context['status'])
    || ($activityId > 0 && ((int)$activity->get('IDorganization') !== $organizationId || !omoActivityCanEdit($activity)))
    || ($activityId === 0 && !omoActivityCanUsePermission($context['currentHolon'], 'CAN_CREATE_CONTROL_ACTIVITY'))
) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoActivityT('activity.error.forbidden')) . '</div>';
    exit;
}

$frequency = RecurrenceSchedule::normalizeFrequency($activity->get('frequency')) ?: RecurrenceSchedule::FREQUENCY_WEEKLY;
$options = omoActivityScheduleOptions();
$schedule = RecurrenceSchedule::normalizeSchedule($frequency, $activity->get('schedule'))
    ?: (string)($options[$frequency][0]['value'] ?? '1');
$suffix = $currentHolonId > 0 ? '&cid=' . $currentHolonId : '';
$backUrl = $activityId > 0
    ? '/omo/api/activities/detail.php?oid=' . $organizationId . '&id=' . $activityId . $suffix
    : '';
$drawerTitle = omoActivityT($activityId > 0 ? 'activity.editor.edit_title' : 'activity.editor.create_title');
?>
<div class="omo-activity-detail generic-drawer-content">
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape($drawerTitle) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape(omoActivityT('activity.description')) ?>"
    >
        <button type="submit" form="omo-activity-editor-form" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action><?= omoApiEscape(omoActivityT('activity.save')) ?></button>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-activity-editor-cancel<?= $backUrl !== '' ? ' data-activity-open-url="' . omoApiEscape($backUrl) . '"' : '' ?>><?= omoApiEscape(omoActivityT('activity.cancel')) ?></button>
    </div>

    <form
        id="omo-activity-editor-form"
        class="generic-form-stack"
        action="/omo/api/activities/action.php"
        method="post"
        data-activity-form
        data-activity-task-form
        data-activity-schedule-options="<?= omoApiEscape(json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    >
        <input type="hidden" name="activity_action" value="save_activity">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
        <?php if ($activityId > 0): ?><input type="hidden" name="id" value="<?= (int)$activityId ?>"><?php endif; ?>

        <section class="generic-section generic-section--stack generic-form-section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoActivityT('activity.editor.identity')) ?></h3>
            <div class="omo-activity-form-grid generic-form-grid">
                <label class="omo-activity-field omo-activity-field--wide">
                    <span><?= omoApiEscape(omoActivityT('activity.title_field')) ?></span>
                    <input class="generic-form-control" type="text" name="title" maxlength="255" required autofocus value="<?= omoApiEscape((string)$activity->get('title')) ?>">
                </label>
                <label class="omo-activity-field omo-activity-field--wide">
                    <span><?= omoApiEscape(omoActivityT('activity.description_field')) ?></span>
                    <textarea class="generic-form-control" name="description" rows="5"><?= omoApiEscape((string)$activity->get('description')) ?></textarea>
                </label>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoActivityT('activity.frequency')) ?></h3>
            <div class="omo-activity-form-grid generic-form-grid">
                <label class="omo-activity-field">
                    <span><?= omoApiEscape(omoActivityT('activity.frequency')) ?></span>
                    <select class="generic-form-control" name="frequency" data-activity-frequency>
                        <?php foreach (RecurrenceSchedule::getFrequencyCatalog() as $option): ?>
                            <option value="<?= omoApiEscape($option) ?>"<?= $option === $frequency ? ' selected' : '' ?>><?= omoApiEscape(omoActivityFrequencyLabel($option)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-activity-field">
                    <span><?= omoApiEscape(omoActivityT('activity.reference')) ?></span>
                    <select class="generic-form-control" name="schedule" data-activity-schedule data-selected-value="<?= omoApiEscape($schedule) ?>"></select>
                </label>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoActivityT('activity.editor.window')) ?></h3>
            <div class="omo-activity-form-grid generic-form-grid">
                <label class="omo-activity-field">
                    <span><?= omoApiEscape(omoActivityT('activity.display_lead')) ?></span>
                    <input class="generic-form-control" type="number" name="display_lead_value" min="0" max="3650" value="<?= (int)$activity->get('display_lead_value') ?>">
                </label>
                <label class="omo-activity-field">
                    <span><?= omoApiEscape(omoActivityT('activity.unit')) ?></span>
                    <select class="generic-form-control" name="display_lead_unit">
                        <?php foreach (ControlActivity::delayUnits() as $unit): ?>
                            <option value="<?= omoApiEscape($unit) ?>"<?= $unit === (string)$activity->get('display_lead_unit') ? ' selected' : '' ?>><?= omoApiEscape(omoActivityT('activity.delay.' . $unit)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="omo-activity-field">
                    <span><?= omoApiEscape(omoActivityT('activity.overdue_after')) ?></span>
                    <input class="generic-form-control" type="number" name="execution_duration_value" min="1" max="3650" value="<?= max(1, (int)$activity->get('execution_duration_value')) ?>">
                </label>
                <label class="omo-activity-field">
                    <span><?= omoApiEscape(omoActivityT('activity.unit')) ?></span>
                    <select class="generic-form-control" name="execution_duration_unit">
                        <?php foreach (ControlActivity::delayUnits() as $unit): ?>
                            <option value="<?= omoApiEscape($unit) ?>"<?= $unit === ((string)$activity->get('execution_duration_unit') ?: 'day') ? ' selected' : '' ?>><?= omoApiEscape(omoActivityT('activity.delay.' . $unit)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <div class="omo-activity-feedback" data-activity-feedback aria-live="polite"></div>
    </form>
</div>
