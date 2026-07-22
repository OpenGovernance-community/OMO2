<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$sourceLang = array(
    'organization_import.action.cancel' => array('text' => 'Annuler', 'context' => 'Cancel button in the organization import popup.'),
    'organization_import.action.submit' => array('text' => 'Creer et importer', 'context' => 'Submit button in the organization import popup.'),
    'organization_import.error.auth' => array('text' => 'Connexion requise.', 'context' => 'Authentication message in the organization import popup.'),
    'organization_import.error.file' => array('text' => 'Choisissez un fichier JSON avant de continuer.', 'context' => 'Validation message when no import file is selected.'),
    'organization_import.error.generic' => array('text' => 'Impossible d importer la nouvelle organisation.', 'context' => 'Fallback error message for the organization import popup.'),
    'organization_import.field.file' => array('text' => 'Export JSON OMO 1', 'context' => 'File input label in the organization import popup.'),
    'organization_import.field.name' => array('text' => 'Nom de la nouvelle organisation', 'context' => 'Organization name label in the organization import popup.'),
    'organization_import.field.name_hint' => array('text' => 'Laissez vide pour reprendre le nom de l export.', 'context' => 'Organization name hint in the organization import popup.'),
    'organization_import.field.sections' => array('text' => 'Contenu a importer', 'context' => 'Section picker legend in the organization import popup.'),
    'organization_import.help' => array('text' => 'Cette action cree une nouvelle organisation. La structure est toujours importee. Les taches OMO 1 deviennent des projets enfants.', 'context' => 'Help text in the organization import popup.'),
    'organization_import.loading' => array('text' => 'Import en cours...', 'context' => 'Loading label shown during organization import.'),
    'organization_import.module.calendar' => array('text' => 'Calendrier', 'context' => 'Calendar module label in the organization import popup.'),
    'organization_import.module.documents' => array('text' => 'Documents', 'context' => 'Documents module label in the organization import popup.'),
    'organization_import.module.indicators' => array('text' => 'Indicateurs', 'context' => 'Indicators module label in the organization import popup.'),
    'organization_import.module.members' => array('text' => 'Membres et roles', 'context' => 'Members module label in the organization import popup.'),
    'organization_import.module.projects' => array('text' => 'Projets', 'context' => 'Projects module label in the organization import popup.'),
    'organization_import.module.pv' => array('text' => 'Proces-verbaux', 'context' => 'Meeting minutes module label in the organization import popup.'),
    'organization_import.module.structure' => array('text' => 'Structure', 'context' => 'Structure module label in the organization import popup.'),
    'organization_import.module.tasks' => array('text' => 'Taches', 'context' => 'Tasks module label in the organization import popup.'),
    'organization_import.title' => array('text' => 'Importer une organisation', 'context' => 'Main title in the organization import popup.'),
);

$lang = translationBundleInit('omo_organization_create_import_popup', omoGetTranslationLocale(), $sourceLang);
$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="generic-soft-panel"><?= htmlspecialchars(t('organization_import.error.auth', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$modules = array(
    'structure' => t('organization_import.module.structure', array(), $lang, $sourceLang),
    'members' => t('organization_import.module.members', array(), $lang, $sourceLang),
    'documents' => t('organization_import.module.documents', array(), $lang, $sourceLang),
    'projects' => t('organization_import.module.projects', array(), $lang, $sourceLang),
    'tasks' => t('organization_import.module.tasks', array(), $lang, $sourceLang),
    'indicators' => t('organization_import.module.indicators', array(), $lang, $sourceLang),
    'calendar' => t('organization_import.module.calendar', array(), $lang, $sourceLang),
    'pv' => t('organization_import.module.pv', array(), $lang, $sourceLang),
);
?>
<div class="omo-create-import" data-omo-create-import="1">
    <header class="generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy">
            <div class="generic-card-title generic-card-title--eyebrow">OMO 1 vers OMO 2</div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(t('organization_import.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('organization_import.help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>

    <form class="omo-create-import__form generic-section generic-section--stack" data-omo-create-import-form="1" enctype="multipart/form-data">
        <label class="omo-create-import__field">
            <span><?= htmlspecialchars(t('organization_import.field.file', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="file" name="omo1_export_file" class="generic-form-control" accept=".json,application/json" required>
        </label>

        <label class="omo-create-import__field">
            <span><?= htmlspecialchars(t('organization_import.field.name', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="text" name="organization_name" class="generic-form-control" maxlength="100">
            <small><?= htmlspecialchars(t('organization_import.field.name_hint', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></small>
        </label>

        <fieldset class="omo-create-import__modules">
            <legend><?= htmlspecialchars(t('organization_import.field.sections', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></legend>
            <input type="hidden" name="modules[]" value="structure">
            <?php foreach ($modules as $module => $label): ?>
                <label class="omo-create-import__module" data-omo-create-import-module="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>">
                    <input
                        type="checkbox"
                        <?php if ($module !== 'structure'): ?>name="modules[]" value="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>" checked<?php else: ?>checked disabled<?php endif; ?>
                        data-omo-create-import-module-input="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    <small data-omo-create-import-module-count="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>"></small>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="omo-create-import__actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-create-import-cancel="1"><?= htmlspecialchars(t('organization_import.action.cancel', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main" data-omo-create-import-submit="1"><?= htmlspecialchars(t('organization_import.action.submit', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </form>

    <div class="omo-create-import__feedback generic-soft-panel" data-omo-create-import-feedback="1" hidden></div>
</div>

<style>
.omo-create-import { display: flex; flex-direction: column; gap: 16px; color: var(--color-text, #1f2937); }
.omo-create-import .generic-drawer-header p { margin: 8px 0 0; color: var(--color-text-light, #64748b); line-height: 1.45; }
.omo-create-import__form { --generic-section-padding-block: 18px; --generic-section-padding-inline: 18px; }
.omo-create-import__field { display: flex; flex-direction: column; gap: 7px; font-weight: 600; }
.omo-create-import__field small, .omo-create-import__module small { color: var(--color-text-light, #64748b); font-size: 12px; font-weight: 400; }
.omo-create-import__modules { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 9px; margin: 0; padding: 14px; border: 1px solid var(--color-border, #d1d5db); border-radius: var(--radius-md); }
.omo-create-import__modules legend { padding: 0 6px; font-weight: 700; }
.omo-create-import__module { display: grid; grid-template-columns: auto 1fr; column-gap: 8px; align-items: center; padding: 7px; border-radius: 8px; }
.omo-create-import__module input { grid-row: 1 / span 2; }
.omo-create-import__module.is-unavailable { opacity: 0.5; }
.omo-create-import__actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
.omo-create-import__feedback { line-height: 1.45; }
.omo-create-import__feedback.is-error { color: #b91c1c; border-color: rgba(220, 38, 38, 0.25); background: rgba(220, 38, 38, 0.06); }
</style>

<script>
(function () {
    var root = document.querySelector('[data-omo-create-import="1"]');
    if (!root) { return; }
    var form = root.querySelector('[data-omo-create-import-form="1"]');
    var fileInput = form ? form.querySelector('input[name="omo1_export_file"]') : null;
    var nameInput = form ? form.querySelector('input[name="organization_name"]') : null;
    var submitButton = root.querySelector('[data-omo-create-import-submit="1"]');
    var feedback = root.querySelector('[data-omo-create-import-feedback="1"]');
    var cancelButton = root.querySelector('[data-omo-create-import-cancel="1"]');
    var moduleNames = ['structure', 'members', 'documents', 'projects', 'tasks', 'indicators', 'calendar', 'pv'];
    var ui = <?= json_encode(array(
        'fileError' => t('organization_import.error.file', array(), $lang, $sourceLang),
        'genericError' => t('organization_import.error.generic', array(), $lang, $sourceLang),
        'loading' => t('organization_import.loading', array(), $lang, $sourceLang),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function setFeedback(message, isError) {
        feedback.hidden = !message;
        feedback.textContent = message || '';
        feedback.classList.toggle('is-error', !!isError);
    }

    function closeModal() {
        if (typeof window.commonTopbarCloseModal === 'function') { window.commonTopbarCloseModal(); }
    }

    function setModuleAvailability(payload) {
        var moduleData = payload && payload.modules ? payload.modules : {};
        moduleNames.forEach(function (module) {
            var input = root.querySelector('[data-omo-create-import-module-input="' + module + '"]');
            var row = root.querySelector('[data-omo-create-import-module="' + module + '"]');
            var count = root.querySelector('[data-omo-create-import-module-count="' + module + '"]');
            if (!input || !row || !count) { return; }
            var details = moduleData[module] || {};
            var available = module === 'structure' || !!details.selected;
            var total = Number(details.count || 0);
            count.textContent = module === 'structure' ? String((payload.scope && payload.scope.holonCount) || 0) + ' holons' : (available ? String(total) + ' elements' : 'Absent du fichier');
            row.classList.toggle('is-unavailable', !available);
            if (module !== 'structure') {
                input.disabled = !available;
                input.checked = available;
            }
        });
        if (nameInput && !nameInput.value && payload && payload.organization && payload.organization.name) {
            nameInput.value = String(payload.organization.name);
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) { return; }
            var reader = new FileReader();
            reader.onload = function () {
                try { setModuleAvailability(JSON.parse(String(reader.result || ''))); } catch (error) { setFeedback(ui.genericError, true); }
            };
            reader.readAsText(file);
        });
    }

    root.addEventListener('change', function (event) {
        var module = event.target.getAttribute('data-omo-create-import-module-input');
        if (module === 'tasks' && event.target.checked) {
            var projects = root.querySelector('[data-omo-create-import-module-input="projects"]');
            if (projects && !projects.disabled) { projects.checked = true; }
        }
        if (module === 'projects' && !event.target.checked) {
            var tasks = root.querySelector('[data-omo-create-import-module-input="tasks"]');
            if (tasks) { tasks.checked = false; }
        }
        if (module === 'pv' && event.target.checked) {
            var calendar = root.querySelector('[data-omo-create-import-module-input="calendar"]');
            if (calendar && !calendar.disabled) { calendar.checked = true; }
        }
    });

    if (cancelButton) { cancelButton.addEventListener('click', closeModal); }
    if (!form) { return; }
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!fileInput || !fileInput.files || !fileInput.files[0]) { setFeedback(ui.fileError, true); return; }
        submitButton.disabled = true;
        setFeedback(ui.loading, false);
        fetch('/omo/api/organizations/create_import.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
            .then(function (response) { return response.json().catch(function () { return null; }).then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) { throw new Error(result.data && result.data.message ? result.data.message : ui.genericError); }
                var warnings = Array.isArray(result.data.warnings) && result.data.warnings.length ? '\n\n' + result.data.warnings.join('\n') : '';
                setFeedback((result.data.message || '') + warnings, false);
                if (result.data.redirect) { window.setTimeout(function () { window.location.href = result.data.redirect; }, 450); }
            })
            .catch(function (error) { setFeedback(error && error.message ? error.message : ui.genericError, true); })
            .finally(function () { submitButton.disabled = false; });
    });
})();
</script>
