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
    'organization_import.field.template' => array('text' => 'Modele d organisation pour le calage', 'context' => 'Organization template selector label in the organization import popup.'),
    'organization_import.field.template_empty' => array('text' => 'Conserver les templates du fichier importe', 'context' => 'Empty option in the organization template selector.'),
    'organization_import.field.template_hint' => array('text' => 'Associez ensuite les roles structurels importes aux templates de ce modele.', 'context' => 'Hint below the organization template selector.'),
    'organization_import.mapping.empty' => array('text' => 'Conserver le template importe', 'context' => 'Empty option in a template mapping selector.'),
    'organization_import.mapping.help' => array('text' => 'Choisissez les equivalences a appliquer. Les templates non associes conservent leur definition importee.', 'context' => 'Help text for template mappings.'),
    'organization_import.mapping.duplicate' => array('text' => 'Un template du modele ne peut etre associe qu a un seul template importe.', 'context' => 'Validation error when a target template is mapped twice.'),
    'organization_import.mapping.none' => array('text' => 'Aucun template structurel a associer dans ce fichier.', 'context' => 'Empty template mapping state.'),
    'organization_import.mapping.title' => array('text' => 'Correspondance des templates structurels', 'context' => 'Title of template mapping section.'),
    'organization_import.field.sections' => array('text' => 'Contenu a importer', 'context' => 'Section picker legend in the organization import popup.'),
    'organization_import.help' => array('text' => 'Cette action cree une nouvelle organisation. La structure est toujours importee. Les taches OMO 1 deviennent des projets enfants et les checklistes recurrentes deviennent des conteneurs.', 'context' => 'Help text in the organization import popup.'),
    'organization_import.module.checklists' => array('text' => 'Checklists', 'context' => 'Checklists module label in the organization import popup.'),
    'organization_import.loading' => array('text' => 'Import en cours...', 'context' => 'Loading label shown during organization import.'),
    'organization_import.wait.title' => array('text' => 'Veuillez patienter', 'context' => 'Title of the full import waiting screen.'),
    'organization_import.wait.description' => array('text' => 'Ce processus peut prendre quelques minutes. Ne fermez pas cette fenetre pendant l import.', 'context' => 'Description of the full import waiting screen.'),
    'organization_import.wait.progress' => array('text' => 'Importation en cours...', 'context' => 'Indeterminate progress label of the full import waiting screen.'),
    'organization_import.module.calendar' => array('text' => 'Calendrier', 'context' => 'Calendar module label in the organization import popup.'),
    'organization_import.module.documents' => array('text' => 'Documents', 'context' => 'Documents module label in the organization import popup.'),
    'organization_import.module.indicators' => array('text' => 'Indicateurs', 'context' => 'Indicators module label in the organization import popup.'),
    'organization_import.module.members' => array('text' => 'Membres et roles', 'context' => 'Members module label in the organization import popup.'),
    'organization_import.module.projects' => array('text' => 'Projets', 'context' => 'Projects module label in the organization import popup.'),
    'organization_import.module.rules' => array('text' => 'Regles', 'context' => 'Rules module label in the organization import popup.'),
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
    'rules' => t('organization_import.module.rules', array(), $lang, $sourceLang),
    'documents' => t('organization_import.module.documents', array(), $lang, $sourceLang),
    'projects' => t('organization_import.module.projects', array(), $lang, $sourceLang),
    'tasks' => t('organization_import.module.tasks', array(), $lang, $sourceLang),
    'checklists' => t('organization_import.module.checklists', array(), $lang, $sourceLang),
    'indicators' => t('organization_import.module.indicators', array(), $lang, $sourceLang),
    'calendar' => t('organization_import.module.calendar', array(), $lang, $sourceLang),
    'pv' => t('organization_import.module.pv', array(), $lang, $sourceLang),
);
$templateCatalog = (new \dbObject\Organization())->getStructuralImportTemplateCatalog();
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

        <label class="omo-create-import__field">
            <span><?= htmlspecialchars(t('organization_import.field.template', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
            <select name="organization_template_id" class="generic-form-control" data-omo-create-import-template="1">
                <option value="0"><?= htmlspecialchars(t('organization_import.field.template_empty', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></option>
                <?php foreach ($templateCatalog as $template): ?>
                    <option value="<?= (int)($template['id'] ?? 0) ?>"><?= htmlspecialchars((string)($template['name'] ?? 'Modele'), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <small><?= htmlspecialchars(t('organization_import.field.template_hint', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></small>
        </label>

        <section class="omo-create-import__mappings generic-soft-panel" data-omo-create-import-mappings="1" hidden>
            <div class="generic-card-title generic-card-title--small"><?= htmlspecialchars(t('organization_import.mapping.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></div>
            <p><?= htmlspecialchars(t('organization_import.mapping.help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="omo-create-import__mapping-list" data-omo-create-import-mapping-list="1"></div>
            <input type="hidden" name="template_mappings" value="{}" data-omo-create-import-mapping-value="1">
        </section>

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

    <section class="omo-create-import__waiting generic-section" data-omo-create-import-waiting="1" hidden aria-live="polite" aria-busy="true">
        <div class="omo-create-import__waiting-spinner" aria-hidden="true"></div>
        <h3 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(t('organization_import.wait.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars(t('organization_import.wait.description', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="omo-create-import__waiting-progress" role="progressbar" aria-label="<?= htmlspecialchars(t('organization_import.wait.progress', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>" aria-valuemin="0" aria-valuemax="100">
            <span></span>
        </div>
        <small><?= htmlspecialchars(t('organization_import.wait.progress', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></small>
    </section>

    <div class="omo-create-import__feedback generic-soft-panel" data-omo-create-import-feedback="1" hidden></div>
</div>

<style>
.omo-create-import { display: flex; flex-direction: column; gap: 16px; color: var(--color-text, #1f2937); }
.omo-create-import .generic-drawer-header p { margin: 8px 0 0; color: var(--color-text-light, #64748b); line-height: 1.45; }
.omo-create-import__form { --generic-section-padding-block: 18px; --generic-section-padding-inline: 18px; }
.omo-create-import__form[hidden], .omo-create-import__waiting[hidden] { display: none !important; }
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
.omo-create-import__mappings { display: flex; flex-direction: column; gap: 10px; }
.omo-create-import__mappings p { margin: 0; color: var(--color-text-light, #64748b); line-height: 1.45; }
.omo-create-import__mapping-list { display: grid; gap: 9px; }
.omo-create-import__mapping-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: center; gap: 10px; }
.omo-create-import__mapping-source { font-weight: 600; }
.omo-create-import__mapping-empty { margin: 0; color: var(--color-text-light, #64748b); }
.omo-create-import__waiting { min-height: 290px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; text-align: center; }
.omo-create-import__waiting h3, .omo-create-import__waiting p { margin: 0; }
.omo-create-import__waiting p { max-width: 440px; color: var(--color-text-light, #64748b); line-height: 1.5; }
.omo-create-import__waiting-spinner { width: 34px; height: 34px; border: 4px solid color-mix(in srgb, var(--color-primary, #2563eb) 22%, transparent); border-top-color: var(--color-primary, #2563eb); border-radius: 50%; animation: omo-create-import-spin 0.9s linear infinite; }
.omo-create-import__waiting-progress { width: min(100%, 400px); height: 10px; overflow: hidden; border-radius: 999px; background: color-mix(in srgb, var(--color-primary, #2563eb) 16%, transparent); }
.omo-create-import__waiting-progress span { display: block; width: 38%; height: 100%; border-radius: inherit; background: var(--color-primary, #2563eb); animation: omo-create-import-progress 1.45s ease-in-out infinite; }
.omo-create-import__waiting small { color: var(--color-text-light, #64748b); }
@keyframes omo-create-import-spin { to { transform: rotate(360deg); } }
@keyframes omo-create-import-progress { from { transform: translateX(-120%); } to { transform: translateX(365%); } }
@media (max-width: 620px) { .omo-create-import__mapping-row { grid-template-columns: 1fr; } }
</style>

<script>
(function () {
    var root = document.querySelector('[data-omo-create-import="1"]');
    if (!root) { return; }
    var form = root.querySelector('[data-omo-create-import-form="1"]');
    var fileInput = form ? form.querySelector('input[name="omo1_export_file"]') : null;
    var nameInput = form ? form.querySelector('input[name="organization_name"]') : null;
    var templateSelect = form ? form.querySelector('[data-omo-create-import-template="1"]') : null;
    var mappingsPanel = root.querySelector('[data-omo-create-import-mappings="1"]');
    var mappingsList = root.querySelector('[data-omo-create-import-mapping-list="1"]');
    var mappingsValue = form ? form.querySelector('[data-omo-create-import-mapping-value="1"]') : null;
    var submitButton = root.querySelector('[data-omo-create-import-submit="1"]');
    var feedback = root.querySelector('[data-omo-create-import-feedback="1"]');
    var cancelButton = root.querySelector('[data-omo-create-import-cancel="1"]');
    var waiting = root.querySelector('[data-omo-create-import-waiting="1"]');
    var moduleNames = ['structure', 'rules', 'members', 'documents', 'projects', 'tasks', 'checklists', 'indicators', 'calendar', 'pv'];
    var templateCatalog = <?= json_encode($templateCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var importPayload = null;
    var templateMappings = {};
    var isImporting = false;
    var ui = <?= json_encode(array(
        'fileError' => t('organization_import.error.file', array(), $lang, $sourceLang),
        'genericError' => t('organization_import.error.generic', array(), $lang, $sourceLang),
        'loading' => t('organization_import.loading', array(), $lang, $sourceLang),
        'mappingDuplicate' => t('organization_import.mapping.duplicate', array(), $lang, $sourceLang),
        'mappingEmpty' => t('organization_import.mapping.empty', array(), $lang, $sourceLang),
        'mappingNone' => t('organization_import.mapping.none', array(), $lang, $sourceLang),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function setFeedback(message, isError) {
        feedback.hidden = !message;
        feedback.textContent = message || '';
        feedback.classList.toggle('is-error', !!isError);
    }

    function notifyGlobal(message, type) {
        var text = String(message || '');
        if (!text || typeof window.commonNotify !== 'function') {
            return false;
        }

        window.commonNotify(text, type || 'error');
        return true;
    }

    function showError(message) {
        if (notifyGlobal(message, 'error')) {
            setFeedback('', true);
            return;
        }

        setFeedback(message, true);
    }

    function closeModal() {
        if (isImporting) { return; }
        if (typeof window.commonTopbarCloseModal === 'function') { window.commonTopbarCloseModal(); }
    }

    function setImportWaiting(active) {
        isImporting = !!active;
        if (form) { form.hidden = !!active; }
        if (waiting) { waiting.hidden = !active; }
        if (active && typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                root.scrollIntoView({ block: 'start' });
            });
        }
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

    function flattenHolons(nodes, output) {
        (Array.isArray(nodes) ? nodes : []).forEach(function (node) {
            if (!node || typeof node !== 'object') { return; }
            output.push(node);
            flattenHolons(node.children, output);
        });
        return output;
    }

    function getImportedTemplateNodes(payload) {
        return flattenHolons(payload && payload.holons, []).filter(function (node) {
            return Number(node.id || 0) > 0
                && Number(node.typeId || 0) > 0
                && (String(node.templateName || '').trim() !== '' || node.visible === false);
        }).sort(function (left, right) {
            return String(left.templateName || left.name || '').localeCompare(String(right.templateName || right.name || ''));
        });
    }

    function selectedTemplateModel() {
        var selectedId = Number(templateSelect && templateSelect.value || 0);
        return templateCatalog.find(function (template) { return Number(template.id || 0) === selectedId; }) || null;
    }

    function syncTemplateMappingsValue() {
        if (mappingsValue) { mappingsValue.value = JSON.stringify(templateMappings); }
    }

    function hasDuplicateTemplateMappings() {
        var usedTargetIds = {};
        return Object.keys(templateMappings).some(function (sourceId) {
            var targetId = Number(templateMappings[sourceId] || 0);
            if (targetId <= 0) { return false; }
            if (usedTargetIds[targetId]) { return true; }
            usedTargetIds[targetId] = true;
            return false;
        });
    }

    function appendOption(select, value, label) {
        var option = document.createElement('option');
        option.value = String(value);
        option.textContent = String(label || '');
        select.appendChild(option);
    }

    function renderTemplateMappings() {
        if (!mappingsPanel || !mappingsList) { return; }
        var model = selectedTemplateModel();
        var sourceNodes = getImportedTemplateNodes(importPayload);
        mappingsPanel.hidden = !model || !importPayload;
        mappingsList.replaceChildren();
        if (!model || !importPayload) {
            syncTemplateMappingsValue();
            return;
        }

        if (!sourceNodes.length) {
            var empty = document.createElement('p');
            empty.className = 'omo-create-import__mapping-empty';
            empty.textContent = ui.mappingNone;
            mappingsList.appendChild(empty);
            syncTemplateMappingsValue();
            return;
        }

        sourceNodes.forEach(function (sourceNode) {
            var sourceId = Number(sourceNode.id || 0);
            var sourceLabel = String(sourceNode.templateName || sourceNode.name || 'Template');
            var candidates = (Array.isArray(model.nodes) ? model.nodes : []).filter(function (candidate) {
                return Number(candidate.typeId || 0) === Number(sourceNode.typeId || 0);
            });
            var row = document.createElement('label');
            row.className = 'omo-create-import__mapping-row';
            var source = document.createElement('span');
            source.className = 'omo-create-import__mapping-source';
            source.textContent = sourceLabel;
            var select = document.createElement('select');
            select.className = 'generic-form-control';
            select.setAttribute('data-omo-create-import-mapping-source', String(sourceId));
            appendOption(select, 0, ui.mappingEmpty);
            candidates.forEach(function (candidate) {
                appendOption(select, Number(candidate.id || 0), String(candidate.path || candidate.name || 'Template'));
            });
            var selectedId = Number(templateMappings[sourceId] || 0);
            if (selectedId && candidates.some(function (candidate) { return Number(candidate.id || 0) === selectedId; })) {
                select.value = String(selectedId);
                templateMappings[sourceId] = selectedId;
            } else {
                delete templateMappings[sourceId];
            }
            row.appendChild(source);
            row.appendChild(select);
            mappingsList.appendChild(row);
        });
        syncTemplateMappingsValue();
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) { return; }
            var reader = new FileReader();
            reader.onload = function () {
                try {
                    importPayload = JSON.parse(String(reader.result || ''));
                    templateMappings = {};
                    setModuleAvailability(importPayload);
                    renderTemplateMappings();
                } catch (error) { showError(ui.genericError); }
            };
            reader.readAsText(file);
        });
    }

    root.addEventListener('change', function (event) {
        var mappingSourceId = Number(event.target.getAttribute('data-omo-create-import-mapping-source') || 0);
        if (mappingSourceId > 0) {
            var mappingTargetId = Number(event.target.value || 0);
            if (mappingTargetId > 0) {
                templateMappings[mappingSourceId] = mappingTargetId;
            } else {
                delete templateMappings[mappingSourceId];
            }
            syncTemplateMappingsValue();
            return;
        }
        if (event.target === templateSelect) {
            templateMappings = {};
            renderTemplateMappings();
            return;
        }
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
        if (!fileInput || !fileInput.files || !fileInput.files[0]) { showError(ui.fileError); return; }
        if (hasDuplicateTemplateMappings()) { showError(ui.mappingDuplicate); return; }
        submitButton.disabled = true;
        setFeedback('', false);
        setImportWaiting(true);
        fetch('/omo/api/organizations/create_import.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
            .then(function (response) { return response.json().catch(function () { return null; }).then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) { throw new Error(result.data && result.data.message ? result.data.message : ui.genericError); }
                var message = result.data.message || '';
                var warnings = Array.isArray(result.data.warnings) && result.data.warnings.length ? result.data.warnings.join('\n') : '';
                var usedGlobalFeedback = false;
                if (message) { usedGlobalFeedback = notifyGlobal(message, 'success') || usedGlobalFeedback; }
                if (warnings) { usedGlobalFeedback = notifyGlobal(warnings, 'warning') || usedGlobalFeedback; }
                setFeedback(message + (warnings ? '\n\n' + warnings : ''), false);
                if (result.data.redirect) {
                    window.setTimeout(function () { window.location.href = result.data.redirect; }, warnings ? 5000 : 450);
                }
            })
            .catch(function (error) {
                setImportWaiting(false);
                showError(error && error.message ? error.message : ui.genericError);
            })
            .finally(function () { submitButton.disabled = false; });
    });
})();
</script>
