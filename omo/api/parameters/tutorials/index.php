<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
if (!omoTutorialSettingsCanManage($organizationId)) {
    http_response_code(403);
    echo '<div class="generic-drawer-content"><p>' . omoApiEscape(omoTutorialSettingsT('forbidden')) . '</p></div>';
    return;
}
if (!$organization->load($organizationId)) { http_response_code(404); return; }
$settings = $organization->getTutorialSettings();
$_SESSION['omo_tutorial_settings_csrf'] ??= bin2hex(random_bytes(32));
?>
<form class="generic-drawer-content generic-form-stack" data-omo-tutorial-settings action="/omo/api/parameters/tutorials/save.php?oid=<?= $organizationId ?>" method="post">
    <input type="hidden" name="csrf" value="<?= omoApiEscape($_SESSION['omo_tutorial_settings_csrf']) ?>">
    <input type="hidden" name="organization_id" value="<?= $organizationId ?>">
    <div class="generic-form-section__copy">
        <h2 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoTutorialSettingsT('title')) ?></h2>
        <p class="generic-description"><?= omoApiEscape(omoTutorialSettingsT('hint')) ?></p>
    </div>
    <label class="generic-checkbox">
        <input type="checkbox" name="customVideoLayout" value="1" <?= $settings['customVideoLayout'] ? 'checked' : '' ?>>
        <span><?= omoApiEscape(omoTutorialSettingsT('custom')) ?></span>
    </label>
    <p class="generic-help-text"><?= omoApiEscape(omoTutorialSettingsT('supported')) ?></p>
    <label class="generic-form-field" data-tutorial-mask <?= $settings['customVideoLayout'] ? '' : 'hidden' ?>>
        <span class="generic-form-label"><?= omoApiEscape(omoTutorialSettingsT('mask')) ?></span>
        <input class="generic-form-control" type="text" name="videoMask" maxlength="2048" value="<?= omoApiEscape($settings['videoMask']) ?>">
        <small class="generic-help-text"><?= omoApiEscape(omoTutorialSettingsT('mask_help')) ?></small>
    </label>
    <p class="generic-feedback generic-feedback--collapse-empty" role="status" aria-live="polite" data-tutorial-settings-feedback></p>
    <div class="generic-drawer-footer generic-drawer-footer--sticky">
        <button type="submit" class="generic-action-button generic-action-button--secondary"><?= omoApiEscape(omoTutorialSettingsT('save')) ?></button>
    </div>
</form>
<?= commonPageScriptTags('/omo/api/parameters/tutorials/index.js', ['error' => omoTutorialSettingsT('error')]) ?>
