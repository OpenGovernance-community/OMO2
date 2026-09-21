<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlActivity;
use dbObject\ArrayUserOrganization;
use dbObject\DocumentPvPoint;
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
$pvMeetingQuery = omoActivityPvMeetingQuery($organizationId);
$suffix = ($currentHolonId > 0 ? '&cid=' . $currentHolonId : '') . $pvMeetingQuery;
$backUrl = $activityId > 0
    ? '/omo/api/activities/detail.php?oid=' . $organizationId . '&id=' . $activityId . $suffix
    : '';
$drawerTitle = omoActivityT($activityId > 0 ? 'activity.editor.edit_title' : 'activity.editor.create_title');
$activityResponsibleOptions = [];
$organizationMembers = new ArrayUserOrganization();
$organizationMembers->loadActiveForOrganization($organizationId);
foreach ($organizationMembers as $membership) {
    $userId = (int)$membership->get('IDuser');
    if ($userId > 0) {
        $activityResponsibleOptions[] = [
            'id' => $userId,
            'label' => DocumentPvPoint::getUserDisplayNameForOrganization($userId, $organizationId),
        ];
    }
}
$activityHelp = static function ($label, $text) {
    return '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
        . '<summary aria-label="' . omoApiEscape($label) . '">?</summary>'
        . '<div class="generic-context-help__content">' . omoApiEscape($text) . '</div></details>';
};
?>
<div class="omo-activity-detail generic-drawer-content">
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape($drawerTitle) ?>"
        data-omo-subdrawer-description=""
    >
        <button type="submit" form="omo-activity-editor-form" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action><?= omoApiEscape(omoActivityT('activity.save')) ?></button>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-activity-editor-cancel<?= $backUrl !== '' ? ' data-activity-open-url="' . omoApiEscape($backUrl) . '"' : '' ?>><?= omoApiEscape(omoActivityT('activity.cancel')) ?></button>
    </div>

    <form
        id="omo-activity-editor-form"
        class="generic-form-stack generic-form-stack--compact"
        action="/omo/api/activities/action.php"
        method="post"
        data-activity-form
        data-activity-task-form
        data-activity-schedule-options="<?= omoApiEscape(json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    >
        <input type="hidden" name="activity_action" value="save_activity">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
        <?php if ($pvMeetingQuery !== ''): ?>
            <input type="hidden" name="pv_meeting_document_id" value="<?= (int)($_GET['pv_meeting_document_id'] ?? 0) ?>">
            <input type="hidden" name="pv_meeting_editor_token" value="<?= omoApiEscape((string)($_GET['pv_meeting_editor_token'] ?? '')) ?>">
        <?php endif; ?>
        <?php if ($activityId > 0): ?><input type="hidden" name="id" value="<?= (int)$activityId ?>"><?php endif; ?>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-heading-with-help">
                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoActivityT('activity.editor.identity')) ?></h3>
                <?= $activityHelp(omoActivityT('activity.editor.identity'), omoActivityT('activity.editor.identity_help')) ?>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <div class="generic-form-field">
                    <label class="generic-form-label" for="activity-editor-title"><?= omoApiEscape(omoActivityT('activity.title_field')) ?></label>
                    <input id="activity-editor-title" class="generic-form-control generic-form-control--compact" type="text" name="title" maxlength="255" required autofocus value="<?= omoApiEscape((string)$activity->get('title')) ?>">
                </div>
                <div class="generic-form-field">
                    <div class="generic-inline-help">
                        <label class="generic-form-label" for="activity-editor-responsible"><?= omoApiEscape(omoActivityT('activity.editor.responsible')) ?></label>
                        <?= $activityHelp(omoActivityT('activity.editor.responsible'), omoActivityT('activity.editor.responsible_help')) ?>
                    </div>
                    <select id="activity-editor-responsible" class="generic-form-control generic-form-control--compact" name="IDuser_responsible">
                        <option value=""><?= omoApiEscape(omoActivityT('activity.editor.responsible_none')) ?></option>
                        <?php foreach ($activityResponsibleOptions as $responsible): ?>
                            <option value="<?= (int)$responsible['id'] ?>"<?= (int)$activity->get('IDuser_responsible') === (int)$responsible['id'] ? ' selected' : '' ?>><?= omoApiEscape((string)$responsible['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="generic-form-field generic-form-field--full">
                    <span class="generic-form-label"><?= omoApiEscape(omoActivityT('activity.description_field')) ?></span>
                    <div data-activity-html-editor-container>
                        <div data-activity-html-editor></div>
                        <textarea name="description" hidden aria-hidden="true" data-activity-html-value><?= omoApiEscape((string)$activity->get('description')) ?></textarea>
                    </div>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-heading-with-help">
                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoActivityT('activity.frequency')) ?></h3>
                <?= $activityHelp(omoActivityT('activity.frequency'), omoActivityT('activity.editor.recurrence_help')) ?>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <div class="generic-form-field">
                    <label class="generic-form-label" for="activity-editor-frequency"><?= omoApiEscape(omoActivityT('activity.frequency')) ?></label>
                    <select id="activity-editor-frequency" class="generic-form-control generic-form-control--compact" name="frequency" data-activity-frequency>
                        <?php foreach (RecurrenceSchedule::getFrequencyCatalog() as $option): ?>
                            <option value="<?= omoApiEscape($option) ?>"<?= $option === $frequency ? ' selected' : '' ?>><?= omoApiEscape(omoActivityFrequencyLabel($option)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="generic-form-field">
                    <label class="generic-form-label" for="activity-editor-schedule"><?= omoApiEscape(omoActivityT('activity.reference')) ?></label>
                    <select id="activity-editor-schedule" class="generic-form-control generic-form-control--compact" name="schedule" data-activity-schedule data-selected-value="<?= omoApiEscape($schedule) ?>"></select>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-heading-with-help">
                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoActivityT('activity.editor.window')) ?></h3>
                <?= $activityHelp(omoActivityT('activity.editor.window'), omoActivityT('activity.editor.window_help')) ?>
            </div>
            <div class="generic-form-grid generic-form-grid--pair">
                <div class="generic-form-field">
                    <div class="generic-inline-help">
                        <label class="generic-form-label" for="activity-editor-display-lead"><?= omoApiEscape(omoActivityT('activity.display_lead')) ?></label>
                        <?= $activityHelp(omoActivityT('activity.display_lead'), omoActivityT('activity.display_lead_help')) ?>
                    </div>
                    <div class="generic-setting-row">
                        <input id="activity-editor-display-lead" class="generic-form-control generic-form-control--compact" type="number" name="display_lead_value" min="0" max="3650" value="<?= (int)$activity->get('display_lead_value') ?>">
                        <select class="generic-form-control generic-form-control--compact" name="display_lead_unit" aria-label="<?= omoApiEscape(omoActivityT('activity.unit')) ?>">
                        <?php foreach (ControlActivity::delayUnits() as $unit): ?>
                            <option value="<?= omoApiEscape($unit) ?>"<?= $unit === (string)$activity->get('display_lead_unit') ? ' selected' : '' ?>><?= omoApiEscape(omoActivityT('activity.delay.' . $unit)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                </div>
                <div class="generic-form-field">
                    <div class="generic-inline-help">
                        <label class="generic-form-label" for="activity-editor-overdue-after"><?= omoApiEscape(omoActivityT('activity.overdue_after')) ?></label>
                        <?= $activityHelp(omoActivityT('activity.overdue_after'), omoActivityT('activity.overdue_after_help')) ?>
                    </div>
                    <div class="generic-setting-row">
                        <input id="activity-editor-overdue-after" class="generic-form-control generic-form-control--compact" type="number" name="execution_duration_value" min="1" max="3650" value="<?= max(1, (int)$activity->get('execution_duration_value')) ?>">
                        <select class="generic-form-control generic-form-control--compact" name="execution_duration_unit" aria-label="<?= omoApiEscape(omoActivityT('activity.unit')) ?>">
                        <?php foreach (ControlActivity::delayUnits() as $unit): ?>
                            <option value="<?= omoApiEscape($unit) ?>"<?= $unit === ((string)$activity->get('execution_duration_unit') ?: 'day') ? ' selected' : '' ?>><?= omoApiEscape(omoActivityT('activity.delay.' . $unit)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                </div>
            </div>
        </section>

        <div class="omo-activity-feedback" data-activity-feedback aria-live="polite"></div>
    </form>
</div>
