<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
$errorMessage = '';
$settings = \dbObject\Organization::getDefaultStructureDisplaySettings();

if ($organizationId <= 0 || !$organization->load($organizationId)) {
    $errorMessage = omoStructureDisplayT('parameters.structure_display.error.organization');
} elseif ($organization->getEnabledStructuralRootHolon() === null) {
    $errorMessage = omoStructureDisplayT('parameters.structure_display.error.structure');
} else {
    $access = omoStructureDisplayAdminModeAccess($organizationId);
    if (empty($access['status'])) {
        $errorMessage = (string)($access['message'] ?? omoStructureDisplayT('parameters.structure_display.error.admin_required'));
    } else {
        $settings = $organization->getStructureDisplaySettings();
    }
}
?>
<div class="omo-structure-display-settings generic-stack generic-stack--roomy" data-omo-structure-display-settings>
    <?php if ($errorMessage !== ''): ?>
    <div class="omo-empty-state"><?= omoStructureDisplayEscape($errorMessage) ?></div>
    <?php else: ?>
    <div class="generic-stack generic-stack--compact">
        <h2 class="generic-card-title generic-card-title--large"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.title')) ?></h2>
        <p class="generic-description"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.description')) ?></p>
    </div>

    <form class="generic-form-stack" data-omo-structure-display-form>
        <section class="generic-section generic-section--stack generic-section--roomy generic-form-section">
            <div class="generic-form-section__heading">
                <div class="generic-form-section__copy">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.section.visibility')) ?></h3>
                </div>
            </div>
            <div class="generic-form-grid">
                <label class="generic-form-field">
                    <span><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.fade_step.label')) ?></span>
                    <input class="generic-form-control" type="number" name="fadeOpacityStep" min="0" max="1" step="0.01" value="<?= omoStructureDisplayEscape($settings['fadeOpacityStep']) ?>" required>
                    <small class="generic-help-text"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.fade_step.help')) ?></small>
                </label>
                <label class="generic-form-field">
                    <span><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.max_depth.label')) ?></span>
                    <input class="generic-form-control" type="number" name="maxDescendantDepth" min="0" max="20" step="1" value="<?= omoStructureDisplayEscape($settings['maxDescendantDepth']) ?>" required>
                    <small class="generic-help-text"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.max_depth.help')) ?></small>
                </label>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy generic-form-section">
            <div class="generic-form-section__heading">
                <div class="generic-form-section__copy">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.section.labels')) ?></h3>
                </div>
            </div>
            <div class="generic-form-grid">
                <label class="generic-form-field">
                    <span><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.label_auto_radius.label')) ?></span>
                    <input class="generic-form-control" type="number" name="labelAutoMinRadius" min="3" max="200" step="1" value="<?= omoStructureDisplayEscape($settings['labelAutoMinRadius']) ?>" required>
                    <small class="generic-help-text"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.label_auto_radius.help')) ?></small>
                </label>
                <label class="generic-form-field">
                    <span><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.label_hover_radius.label')) ?></span>
                    <input class="generic-form-control" type="number" name="labelHoverMinRadius" min="3" max="200" step="1" value="<?= omoStructureDisplayEscape($settings['labelHoverMinRadius']) ?>" required>
                    <small class="generic-help-text"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.label_hover_radius.help')) ?></small>
                </label>
                <label class="generic-form-field">
                    <span><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.label_font_size.label')) ?></span>
                    <input class="generic-form-control" type="number" name="labelMinFontSize" min="0" max="30" step="0.5" value="<?= omoStructureDisplayEscape($settings['labelMinFontSize']) ?>" required>
                    <small class="generic-help-text"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.label_font_size.help')) ?></small>
                </label>
                <label class="generic-checkbox">
                    <input type="checkbox" name="textOutlineEnabled" value="1"<?= !empty($settings['textOutlineEnabled']) ? ' checked' : '' ?>>
                    <span>
                        <span class="generic-card-title generic-card-title--small"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.outline.label')) ?></span>
                        <small class="generic-help-text"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.outline.help')) ?></small>
                    </span>
                </label>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy generic-form-section">
            <div class="generic-form-section__heading">
                <div class="generic-form-section__copy">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.section.members')) ?></h3>
                </div>
            </div>
            <label class="generic-checkbox">
                <input type="checkbox" name="showTerminalMembers" value="1"<?= !empty($settings['showTerminalMembers']) ? ' checked' : '' ?>>
                <span>
                    <span class="generic-card-title generic-card-title--small"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.terminal_members.label')) ?></span>
                    <small class="generic-help-text"><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.field.terminal_members.help')) ?></small>
                </span>
            </label>
        </section>

        <p class="generic-feedback generic-feedback--collapse-empty" data-omo-structure-display-feedback aria-live="polite"></p>
        <div class="generic-form-actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-structure-display-reset><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.action.reset')) ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main" data-omo-structure-display-save><?= omoStructureDisplayEscape(omoStructureDisplayT('parameters.structure_display.action.save')) ?></button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php if ($errorMessage === ''): ?>
<script>
(function () {
    var root = document.querySelector('[data-omo-structure-display-settings]');
    if (!root || root.dataset.omoStructureDisplaySettingsReady === '1') {
        return;
    }

    root.dataset.omoStructureDisplaySettingsReady = '1';
    var form = root.querySelector('[data-omo-structure-display-form]');
    var feedback = root.querySelector('[data-omo-structure-display-feedback]');
    var saveButton = root.querySelector('[data-omo-structure-display-save]');
    var defaults = <?= json_encode(\dbObject\Organization::getDefaultStructureDisplaySettings(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    function setFeedback(message, kind) {
        if (!feedback) {
            return;
        }

        feedback.textContent = String(message || '');
        feedback.className = 'generic-feedback generic-feedback--collapse-empty' + (kind ? ' is-' + kind : '');
    }

    root.querySelector('[data-omo-structure-display-reset]').addEventListener('click', function () {
        form.elements.fadeOpacityStep.value = String(defaults.fadeOpacityStep);
        form.elements.maxDescendantDepth.value = String(defaults.maxDescendantDepth);
        form.elements.labelAutoMinRadius.value = String(defaults.labelAutoMinRadius);
        form.elements.labelHoverMinRadius.value = String(defaults.labelHoverMinRadius);
        form.elements.labelMinFontSize.value = String(defaults.labelMinFontSize);
        form.elements.textOutlineEnabled.checked = Boolean(defaults.textOutlineEnabled);
        form.elements.showTerminalMembers.checked = Boolean(defaults.showTerminalMembers);
        setFeedback('', '');
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        saveButton.disabled = true;
        setFeedback('', '');

        fetch('/omo/api/parameters/structure-display/save.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: new FormData(form),
            headers: { Accept: 'application/json' }
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== 'ok') {
                        throw new Error(payload && payload.message ? payload.message : <?= json_encode(omoStructureDisplayT('parameters.structure_display.status.error'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>);
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                setFeedback(payload.message || <?= json_encode(omoStructureDisplayT('parameters.structure_display.status.saved'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, 'success');
                if (typeof window.omoInvalidateStructureDataCache === 'function') {
                    window.omoInvalidateStructureDataCache();
                }
                if (typeof window.omoReloadStructureAndFocus === 'function') {
                    var holonId = typeof window.omoGetCurrentStructureHolonId === 'function'
                        ? window.omoGetCurrentStructureHolonId()
                        : null;
                    window.omoReloadStructureAndFocus(holonId || null, { quickZoom: true });
                }
            })
            .catch(function (error) {
                setFeedback(error && error.message ? error.message : <?= json_encode(omoStructureDisplayT('parameters.structure_display.status.error'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, 'error');
            })
            .finally(function () {
                saveButton.disabled = false;
            });
    });
}());
</script>
<?php endif; ?>
