<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
if (!omoCalendarSettingsCanManage($organizationId)) {
    http_response_code(403);
    echo '<div class="generic-drawer-content"><p class="generic-soft-panel">' . omoApiEscape(omoCalendarSettingsT('forbidden')) . '</p></div>';
    return;
}
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo '<div class="generic-drawer-content"><p class="generic-soft-panel">' . omoApiEscape(omoCalendarSettingsT('organization')) . '</p></div>';
    return;
}
$settings = $organization->getCalendarSettings();
$_SESSION['omo_calendar_settings_csrf'] ??= bin2hex(random_bytes(32));
?>
<form class="generic-drawer-content generic-form-stack" data-omo-calendar-settings action="/omo/api/parameters/calendar/save.php" method="post">
    <input type="hidden" name="csrf" value="<?= omoApiEscape($_SESSION['omo_calendar_settings_csrf']) ?>">
    <input type="hidden" name="organization_id" value="<?= $organizationId ?>">
    <div class="generic-form-section__copy">
        <h2 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarSettingsT('title')) ?></h2>
        <p class="generic-description"><?= omoApiEscape(omoCalendarSettingsT('hint')) ?></p>
    </div>
    <div class="generic-form-grid generic-form-grid--pair">
        <?php foreach (['dayStart' => 'start', 'dayEnd' => 'end'] as $field => $label): ?>
        <label class="generic-form-field">
            <span class="generic-form-label"><?= omoApiEscape(omoCalendarSettingsT($label)) ?></span>
            <select class="generic-form-control" name="<?= $field ?>" required>
                <?php for ($slot = $field === 'dayEnd' ? 1 : 0; $slot <= ($field === 'dayEnd' ? 48 : 47); $slot++):
                    $time = sprintf('%02d:%02d', intdiv($slot, 2), ($slot % 2) * 30); ?>
                <option value="<?= $time ?>" <?= $settings[$field] === $time ? 'selected' : '' ?>><?= $time ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <?php endforeach; ?>
    </div>
    <p class="generic-help-text"><?= omoApiEscape(omoCalendarSettingsT('step')) ?></p>
    <p class="generic-feedback generic-feedback--collapse-empty" role="status" aria-live="polite" data-calendar-settings-feedback></p>
    <div class="generic-drawer-footer generic-drawer-footer--sticky">
        <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoCalendarSettingsT('save')) ?></button>
    </div>
</form>
<?= commonPageScriptTags('/omo/api/parameters/calendar/index.js', ['error' => omoCalendarSettingsT('error')]) ?>
