<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$holonCreateSourceLang = [
    'project_picker.add' => ['text' => 'Ajouter', 'context' => 'Button opening the project selector for a holon property'],
    'project_picker.title' => ['text' => 'Ajouter des projets', 'context' => 'Project selector title in the holon editor'],
    'project_picker.search' => ['text' => 'Rechercher un projet…', 'context' => 'Project selector search placeholder in the holon editor'],
    'project_picker.empty' => ['text' => 'Aucun projet disponible dans cet espace.', 'context' => 'Empty project selector state in the holon editor'],
    'project_picker.selected_empty' => ['text' => 'Aucun projet sélectionné.', 'context' => 'Empty selected project list in the holon editor'],
    'project_picker.cancel' => ['text' => 'Annuler', 'context' => 'Project selector cancel button in the holon editor'],
    'project_picker.confirm' => ['text' => 'Ajouter la sélection', 'context' => 'Project selector confirmation button in the holon editor'],
    'project_picker.remove' => ['text' => 'Retirer {project}', 'context' => 'Accessible label for removing a selected project from a holon property'],
    'project_picker.scope_local' => ['text' => 'Local', 'context' => 'Project selector scope limited to the selected holon'],
    'project_picker.scope_children' => ['text' => 'Enfants', 'context' => 'Project selector scope including direct child holons'],
    'project_picker.scope_descendants' => ['text' => 'Descendants', 'context' => 'Project selector scope including every descendant holon'],
];
$holonCreateLang = omoLoadTranslationBundle('omo_holon_create', $holonCreateSourceLang);
$holonCreateT = static fn (string $key, array $variables = []): string => t($key, $variables, $holonCreateLang, $holonCreateSourceLang);

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$contextHolonId = (int)($_GET['cid'] ?? 0);
$holonId = (int)($_GET['hid'] ?? 0);
$governanceCapture = !empty($_GET['governance_capture']);
$organization = new Organization();
$editorData = null;
$errorMessage = '';
$adminLabel = 'Admin';
$adminLabelLower = 'admin';
$canEditHolonColor = false;
$canEditHolonPermissions = false;
$canEditHolonAdminBounds = false;
$canAddHolonProperties = false;
$hasCustomHolonAppearance = false;
$hasCustomHolonAdminBounds = false;
$hasDirectHolonPermissions = false;
$directPermissionLabel = 'Droits associés à l’élément';

if ($organizationId <= 0) {
    $errorMessage = "Aucune organisation n'est actuellement sélectionnée.";
} elseif (!$organization->load($organizationId)) {
    $errorMessage = "L'organisation demandée est introuvable.";
} else {
	$organizationLexicon = $organization->getLexicon();
	$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
	$adminLabelLower = function_exists('mb_strtolower')
		? mb_strtolower($adminLabel, 'UTF-8')
		: strtolower($adminLabel);
    $organizationInterfaceLevel = $organization->getInterfaceLevel();
    $canEditHolonColor = $organizationInterfaceLevel >= Organization::INTERFACE_LEVEL_EXPERT;
    $canEditHolonAdminBounds = !$organization->isDiscoveryMode();
    $editorData = $organization->getHolonCreationEditorData($contextHolonId, $holonId, $governanceCapture);
	$canEditHolonPermissions = $organization->canManageHolonPermissionAssignments(
		($editorData['editorType'] ?? 'holon') === 'template'
	);
	$canAddHolonProperties = !empty($editorData['canAddHolonProperties']);
	$editedHolonData = is_array($editorData['holon'] ?? null) ? $editorData['holon'] : array();
	$hasCustomHolonAppearance = trim((string)($editedHolonData['color'] ?? '')) !== ''
		|| trim((string)($editedHolonData['icon'] ?? '')) !== '';
	$hasCustomHolonAdminBounds = !empty($editedHolonData['adminMinOverride'])
		|| !empty($editedHolonData['adminMaxOverride']);
	$hasDirectHolonPermissions = !empty($editedHolonData['permissionAssignments']);
	if (($editorData['editorType'] ?? 'holon') === 'template') {
		$directPermissionLabel = 'Droits associés au modèle';
	}
    if ($holonId > 0 && (($editorData['mode'] ?? 'create') !== 'edit')) {
        $errorMessage = "L’élément demandé est introuvable.";
    } elseif (($editorData['mode'] ?? 'create') === 'edit' && !($editorData['canEdit'] ?? false)) {
        $errorMessage = "Cet élément ne peut pas être édité avec ce formulaire.";
    } elseif (($editorData['mode'] ?? 'create') !== 'edit' && !($editorData['canCreate'] ?? false)) {
        $errorMessage = "Cet élément n'autorise pas l'ajout d'enfant.";
    } elseif (count($editorData['templateCatalog'] ?? array()) === 0) {
        $errorMessage = ($editorData['mode'] ?? 'create') === 'edit'
            ? "Aucun modèle n'est disponible dans le contexte de ce holon."
            : "Aucun modèle n'est disponible dans ce contexte pour créer un nouvel élément.";
    }
}
$drawerTitle = (($editorData['mode'] ?? 'create') === 'edit') ? 'Modifier l’élément' : 'Nouvel élément';
?>
<div class="omo-holon-create omo-panel-view<?= $governanceCapture ? ' omo-holon-create--governance-capture' : '' ?>">
    <?php if ($errorMessage === ''): ?>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape($drawerTitle) ?>"
    ></div>
    <?php endif; ?>

    <div class="omo-panel-view__body">
        <?php if ($errorMessage !== ''): ?>
            <div class="omo-holon-create__empty generic-section"><?= omoApiEscape($errorMessage) ?></div>
        <?php else: ?>
            <div class="omo-holon-create__layout" id="omo-holon-create-editor">
                <section class="omo-holon-create__panel generic-drawer-content">
                    <div class="omo-holon-create__status" id="omo-holon-create-status" hidden></div>

                    <form id="omo-holon-create-form" class="omo-holon-create__form generic-form-stack">
                        <div class="omo-panel-view__body_content">
                        <section class="omo-holon-create__section generic-section generic-section--stack generic-form-section generic-form-section--divided">
                            <div class="omo-holon-create__grid generic-form-grid">
                                <label class="omo-holon-create__field generic-form-field">
                                    <span class="generic-form-label">Nom</span>
                                    <input type="text" id="omo-holon-create-name" class="generic-form-control" maxlength="255" required>
                                    <small class="generic-help-text" id="omo-holon-create-name-help"></small>
                                </label>

                                <label class="omo-holon-create__field generic-form-field">
                                    <span class="generic-form-label">Modèle</span>
                                    <select id="omo-holon-create-template" class="generic-form-control" required></select>
                                </label>

                                <label class="omo-holon-create__field omo-holon-create__field--full generic-form-field generic-form-field--full">
                                    <span class="generic-form-label">Nom complet</span>
                                    <input type="text" id="omo-holon-create-full-name" class="generic-form-control" maxlength="255">
                                    <small class="generic-help-text">Optionnel. Utilise dans la vue liste et dans la fiche contexte.</small>
                                </label>

                            </div>
                        </section>
                        <section class="omo-holon-create__section omo-holon-create__section--separated generic-section generic-section--stack generic-form-section generic-form-section--divided">
                            <div class="omo-holon-create__section-head generic-form-section__heading">
                                <div class="generic-form-section__copy">
                                    <div class="omo-holon-create__section-title generic-title generic-title--medium">Propriétés</div>
                                    <p class="omo-holon-create__section-description generic-description">
                                        Les propriétés héritées du modèle sont affichées ci-dessous.
                                    </p>
                                </div>
                            </div>

                            <div class="omo-holon-create__properties" id="omo-holon-create-properties"></div>
                            <?php if ($canAddHolonProperties): ?>
                            <div class="generic-action-row">
                            <button type="button" class="generic-action-button generic-action-button--secondary" id="omo-holon-create-add-property">Ajouter une propriété</button>
                            </div>
                            <?php endif; ?>
                        </section>

                        <?php if ($canEditHolonPermissions): ?>
                        <section
                            class="omo-holon-create__section omo-holon-create__section--separated generic-section generic-section--stack generic-form-section generic-form-section--divided generic-accordion generic-accordion--card generic-accordion--collapsible<?= $hasDirectHolonPermissions ? '' : ' is-collapsed' ?>"
                            data-generic-accordion
                            id="omo-holon-create-permissions-section"
                        >
                            <button
                                type="button"
                                class="omo-holon-create__section-head omo-holon-create__accordion-header-toggle generic-form-section__heading generic-accordion__header"
                                data-generic-accordion-toggle
                                aria-expanded="<?= $hasDirectHolonPermissions ? 'true' : 'false' ?>"
                                aria-controls="omo-holon-create-permissions-content"
                            >
                                <span class="generic-form-section__copy">
                                    <span class="omo-holon-create__section-title generic-title generic-title--medium">Droits</span>
                                </span>
                                <span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>
                            </button>

                            <div class="generic-accordion__content" id="omo-holon-create-permissions-content">
                                <div class="omo-holon-create__permission-summary" id="omo-holon-create-permissions-summary">
                                    <div class="omo-holon-create__permission-summary-line">
                                        <div class="omo-holon-create__permission-summary-label">Droits hérités</div>
                                        <div class="omo-holon-create__permission-summary-empty">aucun</div>
                                    </div>
                                    <div class="omo-holon-create__permission-summary-line">
                                        <div class="omo-holon-create__permission-summary-heading">
                                            <div class="omo-holon-create__permission-summary-label"><?= omoApiEscape($directPermissionLabel) ?></div>
                                            <button
                                                type="button"
                                                class="generic-action-button generic-action-button--secondary generic-action-button--compact"
                                                id="omo-holon-create-permissions-toggle"
                                                aria-expanded="false"
                                                aria-controls="omo-holon-create-permissions-editor"
                                            >Éditer</button>
                                        </div>
                                        <div class="omo-holon-create__permission-summary-empty">aucun</div>
                                    </div>
                                </div>
                                <div class="omo-holon-create__permissions" id="omo-holon-create-permissions-editor" hidden></div>
                            </div>
                        </section>
                        <?php endif; ?>
                        <?php if ($canEditHolonAdminBounds): ?>
                        <section
                            class="omo-holon-create__section omo-holon-create__section--separated generic-section generic-section--stack generic-form-section generic-form-section--divided generic-accordion generic-accordion--card generic-accordion--collapsible<?= $hasCustomHolonAdminBounds ? '' : ' is-collapsed' ?>"
                            data-generic-accordion
                            id="omo-holon-create-admin-bounds-section"
                        >
                            <button
                                type="button"
                                class="omo-holon-create__section-head omo-holon-create__accordion-header-toggle generic-form-section__heading generic-accordion__header"
                                data-generic-accordion-toggle
                                aria-expanded="<?= $hasCustomHolonAdminBounds ? 'true' : 'false' ?>"
                                aria-controls="omo-holon-create-admin-bounds-content"
                            >
                                <span class="generic-form-section__copy">
                                    <span class="omo-holon-create__section-title generic-title generic-title--medium">Équipe</span>
                                </span>
                                <span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>
                            </button>
                            <div class="generic-accordion__content" id="omo-holon-create-admin-bounds-content">
                                <div class="omo-holon-create__admin-bounds generic-form-grid">
                                    <label class="omo-holon-create__field generic-form-field">
                                        <span class="omo-holon-create__admin-bound-head">
                                            <span>Minimum de <?= omoApiEscape($adminLabelLower) ?></span>
                                            <span class="omo-holon-create__color-toggle">
                                                <input type="checkbox" id="omo-holon-create-admin-min-override">
                                                <span>Redéfinir</span>
                                            </span>
                                        </span>
                                        <input type="number" id="omo-holon-create-admin-min" class="generic-form-control" min="0" step="1">
                                    </label>
                                    <label class="omo-holon-create__field generic-form-field">
                                        <span class="omo-holon-create__admin-bound-head">
                                            <span>Maximum de <?= omoApiEscape($adminLabelLower) ?></span>
                                            <span class="omo-holon-create__color-toggle">
                                                <input type="checkbox" id="omo-holon-create-admin-max-override">
                                                <span>Redéfinir</span>
                                            </span>
                                        </span>
                                        <input type="number" id="omo-holon-create-admin-max" class="generic-form-control" min="0" step="1" placeholder="Sans limite">
                                    </label>
                                </div>
                                <small class="generic-help-text" id="omo-holon-create-admin-bounds-help"></small>
                            </div>
                        </section>
                        <?php endif; ?>
                        </div>
                        <?php if ($canEditHolonColor): ?>
                        <section
                            class="omo-holon-create__section generic-section generic-section--stack generic-form-section generic-form-section--divided generic-accordion generic-accordion--card generic-accordion--collapsible<?= $hasCustomHolonAppearance ? '' : ' is-collapsed' ?>"
                            data-generic-accordion
                            id="omo-holon-create-appearance"
                        >
                            <button
                                type="button"
                                class="omo-holon-create__section-head omo-holon-create__accordion-header-toggle generic-form-section__heading generic-accordion__header"
                                data-generic-accordion-toggle
                                aria-expanded="<?= $hasCustomHolonAppearance ? 'true' : 'false' ?>"
                                aria-controls="omo-holon-create-appearance-content"
                            >
                                <span class="generic-form-section__copy">
                                    <span class="omo-holon-create__section-title generic-title generic-title--medium">Apparence</span>
                                </span>
                                <span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>
                            </button>

                            <div class="generic-accordion__content" id="omo-holon-create-appearance-content">
                            <div class="omo-holon-create__grid generic-form-grid">
                                <label class="omo-holon-create__field generic-form-field" id="omo-holon-create-color-field">
                                    <span class="generic-form-label">Couleur</span>
                                    <div class="omo-holon-create__color-head">
                                        <span class="omo-holon-create__color-toggle">
                                            <input type="checkbox" id="omo-holon-create-color-enabled" aria-label="Redéfinir la couleur">
                                            <span id="omo-holon-create-color-enabled-label">Redéfinir</span>
                                            <span class="omo-holon-create__color-body" id="omo-holon-create-color-body">
                                                <input type="color" id="omo-holon-create-color" value="#f59e0b" aria-label="Couleur redéfinie">
                                            </span>
                                        </span>
                                    </div>
                                </label>

                                <div class="omo-holon-create__field omo-holon-create__field--full generic-form-field generic-form-field--full">
                                    <span class="generic-form-label">Illustrations</span>
                                    <div class="omo-holon-create__media-grid">
                                        <div class="omo-holon-create__media-card generic-form-field">
                                            <div class="generic-form-label">Icone</div>
                                            <div id="omo-holon-create-icon-field"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </section>
                        <?php endif; ?>

                        <div class="omo-holon-create__footer generic-section">
                            <div class="omo-holon-create__hint generic-help-text" id="omo-holon-create-hint"></div>
                            <div class="omo-holon-create__actions generic-form-actions generic-form-actions--stack-mobile">
                                <button type="button" class="generic-action-button generic-action-button--secondary" id="omo-holon-create-cancel">Fermer</button>
                                <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape((($editorData['mode'] ?? 'create') === 'edit') ? 'Enregistrer' : 'Créer un élément') ?></button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($editorData !== null && $errorMessage === ''): ?>
<script src="/omo/assets/js/sized-image-field.js"></script>
<script src="/omo/assets/js/simple-html-field.js?v=20260904-highlight-clear"></script>
<script src="/common/assets/multiline-list-paste.js"></script>
<link rel="stylesheet" href="/common/permissions/editor.css?v=20260923-permission-align">
<script src="/common/permissions/editor.js?v=20260923-permission-align"></script>
<script>
(() => {
const state = {
    data: <?= json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    statusTimer: null
};
const adminLexiconLabel = <?= json_encode($adminLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const directPermissionLabel = <?= json_encode($directPermissionLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const canEditHolonColor = <?= $canEditHolonColor ? 'true' : 'false' ?>;
const governanceCapture = <?= $governanceCapture ? 'true' : 'false' ?>;
const projectPickerTexts = <?= json_encode([
    'add' => $holonCreateT('project_picker.add'),
    'title' => $holonCreateT('project_picker.title'),
    'search' => $holonCreateT('project_picker.search'),
    'empty' => $holonCreateT('project_picker.empty'),
    'selectedEmpty' => $holonCreateT('project_picker.selected_empty'),
    'cancel' => $holonCreateT('project_picker.cancel'),
    'confirm' => $holonCreateT('project_picker.confirm'),
    'remove' => $holonCreateT('project_picker.remove'),
    'scopeLocal' => $holonCreateT('project_picker.scope_local'),
    'scopeChildren' => $holonCreateT('project_picker.scope_children'),
    'scopeDescendants' => $holonCreateT('project_picker.scope_descendants'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const governanceInitialPayloadCandidate = governanceCapture && window.omoHolonGovernanceInitialPayload && typeof window.omoHolonGovernanceInitialPayload === 'object'
    ? window.omoHolonGovernanceInitialPayload
    : null;
const governanceInitialPayload = governanceInitialPayloadCandidate && Object.keys(governanceInitialPayloadCandidate).length > 0
    ? governanceInitialPayloadCandidate
    : null;

if (governanceInitialPayload) {
    const currentHolon = state.data.holon && typeof state.data.holon === 'object' ? state.data.holon : {};
    state.data.holon = Object.assign({}, currentHolon, {
        name: String(governanceInitialPayload.name || ''),
        fullName: String(governanceInitialPayload.fullName || ''),
        color: String(governanceInitialPayload.color || ''),
        icon: String(governanceInitialPayload.icon || ''),
        templateId: Number(governanceInitialPayload.templateId || 0),
        adminMin: governanceInitialPayload.adminMin,
        adminMax: governanceInitialPayload.adminMax,
        adminMinOverride: Boolean(governanceInitialPayload.adminMinOverride),
        adminMaxOverride: Boolean(governanceInitialPayload.adminMaxOverride),
        permissionAssignments: governanceInitialPayload.permissions || {},
        properties: Array.isArray(governanceInitialPayload.properties) ? governanceInitialPayload.properties : []
    });
}

const root = document.getElementById('omo-holon-create-editor');
if (!root) {
    return;
}

const elements = {
    status: root.querySelector('#omo-holon-create-status'),
    form: root.querySelector('#omo-holon-create-form'),
    template: root.querySelector('#omo-holon-create-template'),
    name: root.querySelector('#omo-holon-create-name'),
    fullName: root.querySelector('#omo-holon-create-full-name'),
    colorEnabled: root.querySelector('#omo-holon-create-color-enabled'),
	colorEnabledLabel: root.querySelector('#omo-holon-create-color-enabled-label'),
    colorBody: root.querySelector('#omo-holon-create-color-body'),
    color: root.querySelector('#omo-holon-create-color'),
	adminBoundsSection: root.querySelector('#omo-holon-create-admin-bounds-section'),
	adminMin: root.querySelector('#omo-holon-create-admin-min'),
	adminMax: root.querySelector('#omo-holon-create-admin-max'),
	adminMinOverride: root.querySelector('#omo-holon-create-admin-min-override'),
	adminMaxOverride: root.querySelector('#omo-holon-create-admin-max-override'),
	adminBoundsHelp: root.querySelector('#omo-holon-create-admin-bounds-help'),
    iconField: root.querySelector('#omo-holon-create-icon-field'),
    properties: root.querySelector('#omo-holon-create-properties'),
    addProperty: root.querySelector('#omo-holon-create-add-property'),
    permissions: root.querySelector('#omo-holon-create-permissions-editor'),
    permissionSummary: root.querySelector('#omo-holon-create-permissions-summary'),
    hint: root.querySelector('#omo-holon-create-hint'),
    nameHelp: root.querySelector('#omo-holon-create-name-help'),
    cancel: root.querySelector('#omo-holon-create-cancel'),
    submit: root.querySelector('button[type="submit"]')
};

const mediaFields = {
    icon: null
};

const editorAccordions = [
    root.querySelector('#omo-holon-create-appearance'),
    root.querySelector('#omo-holon-create-admin-bounds-section'),
    root.querySelector('#omo-holon-create-permissions-section')
].filter(Boolean);

if (editorAccordions.length > 0 && typeof window.initGenericComponents === 'function') {
    window.initGenericComponents(root);
}

function initEditorAccordion(accordion) {
    const toggle = accordion ? accordion.querySelector('[data-generic-accordion-toggle]') : null;
    if (!accordion || !toggle) {
        return;
    }

    function syncAccessibility() {
        toggle.setAttribute('aria-expanded', accordion.classList.contains('is-collapsed') ? 'false' : 'true');
    }

    if (typeof window.initGenericComponents === 'function') {
        toggle.addEventListener('click', syncAccessibility);
    } else {
        toggle.addEventListener('click', function () {
            accordion.classList.toggle('is-collapsed');
            syncAccessibility();
        });
    }
    syncAccessibility();
}

editorAccordions.forEach(initEditorAccordion);

function waitForGlobalLibrary(globalKey, timeoutMs) {
    const key = String(globalKey || '').trim();
    const maxWait = Number(timeoutMs || 4000);
    if (key !== '' && window[key]) {
        return Promise.resolve(true);
    }

    return new Promise(function (resolve) {
        const startedAt = Date.now();

        function checkAvailability() {
            if (key !== '' && window[key]) {
                resolve(true);
                return;
            }

            if (Date.now() - startedAt >= maxWait) {
                resolve(false);
                return;
            }

            window.setTimeout(checkAvailability, 30);
        }

        checkAvailability();
    });
}

// Échappe texte HTML
function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Parse valeurs liste
function parseStoredListValue(value) {
    const rawValue = value !== undefined && value !== null ? String(value) : '';
    if (!rawValue.trim()) {
        return [];
    }

    try {
        const decoded = JSON.parse(rawValue);
        return Array.isArray(decoded) ? decoded : [];
    } catch (error) {
        return rawValue.split(/\r\n|\r|\n|\|/).map(function (item) {
            return item.trim();
        }).filter(Boolean);
    }
}

function renderHtmlPreview(value, className) {
    if (window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.renderPreviewHtml === 'function') {
        return window.omoSimpleHtmlField.renderPreviewHtml(value, className);
    }

    return '<div class="' + escapeHtml(className || 'omo-holon-create__inherited-text generic-meta') + '">' + escapeHtml(value || '').replace(/\n/g, '<br>') + '</div>';
}

// Liste les modèles
function getPermissionCatalog() {
    return Array.isArray(state.data.permissionCatalog) ? state.data.permissionCatalog : [];
}

function getPermissionRangeOptions() {
    return Array.isArray(state.data.permissionRanges) ? state.data.permissionRanges : [];
}

function getPermissionRangeOptionsForCurrentHolon(rangeOptions) {
    const template = getCurrentTemplate();
    const editingHolon = getEditingHolon();
    const typeId = Number((template && template.typeId) || (editingHolon && editingHolon.typeId) || 0);

    return (rangeOptions || []).filter(function (range) {
        return String(range && range.key ? range.key : '') !== 'direct_children' || typeId === 2;
    });
}

function getInheritedPermissions() {
    const editingHolon = getEditingHolon();
    return editingHolon && editingHolon.inheritedPermissions && typeof editingHolon.inheritedPermissions === 'object'
        ? editingHolon.inheritedPermissions
        : {};
}

function normalizePermissionRanges(value) {
    const ranges = Array.isArray(value) ? value : (String(value || '').trim() !== '' ? [value] : []);
    const normalized = [];
    const seen = new Set();

    ranges.forEach(function (range) {
        const normalizedRange = String(range || '').trim();
        if (!normalizedRange || seen.has(normalizedRange)) {
            return;
        }

        seen.add(normalizedRange);
        normalized.push(normalizedRange);
    });

    return normalized;
}

function getPermissionRangeLabel(rangeKey, rangeOptions) {
    const range = (rangeOptions || []).find(function (item) {
        return String(item.key || '') === String(rangeKey || '');
    });

    return range ? String(range.label || range.key || '') : String(rangeKey || '');
}

function readPermissions() {
    if (!elements.permissions) {
        return {};
    }

    const assignments = { member: {}, admin: {}, collective: {} };
    Array.from(elements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
        const permissionKey = String(row.getAttribute('data-permission-key') || '').trim();
        if (!permissionKey) {
            return;
        }

        Array.from(row.querySelectorAll('[data-permission-scope]')).forEach(function (scope) {
            const range = String(scope.getAttribute('data-permission-token') || '').trim();
            if (!range) return;
            Array.from(scope.querySelectorAll('[data-permission-profile]:checked')).forEach(function (checkbox) {
                const profileKey = String(checkbox.getAttribute('data-permission-profile') || '').trim();
                if (!Object.prototype.hasOwnProperty.call(assignments, profileKey)) return;
                if (!assignments[profileKey][permissionKey]) assignments[profileKey][permissionKey] = [];
                assignments[profileKey][permissionKey].push(range);
            });
        });
    });

    return assignments;
}

function normalizePermissionProfiles(value) {
    const source = value && typeof value === 'object' ? value : {};
    const hasProfiles = Object.prototype.hasOwnProperty.call(source, 'member') || Object.prototype.hasOwnProperty.call(source, 'admin') || Object.prototype.hasOwnProperty.call(source, 'collective');
    return {
        member: hasProfiles && source.member && typeof source.member === 'object' ? source.member : (hasProfiles ? {} : source),
        admin: hasProfiles && source.admin && typeof source.admin === 'object' ? source.admin : {},
        collective: hasProfiles && source.collective && typeof source.collective === 'object' ? source.collective : {}
    };
}

function buildPermissionSummary(assignments) {
    const permissionCatalog = getPermissionCatalog();
    const titles = Object.keys(assignments || {}).map(function (permissionKey) {
        const permission = permissionCatalog.find(function (item) {
            return String(item && item.key ? item.key : '') === String(permissionKey || '');
        });

        return permission ? String(permission.title || permission.key || '').trim() : String(permissionKey || '').trim();
    }).filter(Boolean).sort(function (left, right) {
        return left.localeCompare(right, 'fr', { sensitivity: 'base' });
    });

    if (!titles.length) {
        return 'Droits associés à l’élément : aucun';
    }

    return 'Droits associés à l’élément : ' + titles.join(', ');
}

function getPermissionTitle(permissionKey) {
    const permissionCatalog = getPermissionCatalog();
    const permission = permissionCatalog.find(function (item) {
        return String(item && item.key ? item.key : '') === String(permissionKey || '');
    });

    return permission ? String(permission.title || permission.key || '').trim() : String(permissionKey || '').trim();
}

function renderPermissionSummaryCapsules(items, emptyText) {
    if (!Array.isArray(items) || !items.length) {
        return '<div class="omo-holon-create__permission-summary-empty">' + escapeHtml(emptyText || 'aucun') + '</div>';
    }

    return '<div class="omo-holon-create__permission-summary-capsules">'
        + items.map(function (item) {
            return ''
                + '<div class="omo-holon-create__permission-pill">'
                + '  <div class="omo-holon-create__permission-pill-title">' + escapeHtml(String(item.title || '')) + '</div>'
                + '  <div class="omo-holon-create__permission-pill-scope">' + escapeHtml(String(item.scope || '')) + '</div>'
                + '</div>';
        }).join('')
        + '</div>';
}

function buildLocalPermissionSummaryItems(assignments) {
    const defaultRangeOptions = getPermissionRangeOptions();
    const profiles = normalizePermissionProfiles(assignments);
    const profileLabels = { member: 'Membres', admin: adminLexiconLabel, collective: 'Collectif' };
    const items = [];

    Object.keys(profiles).forEach(function (profileKey) {
        Object.keys(profiles[profileKey] || {}).forEach(function (permissionKey) {
            const ranges = normalizePermissionRanges(profiles[profileKey][permissionKey]);
            if (!ranges.length) {
                return;
            }

            const permission = getPermissionCatalog().find(function (item) {
                return String(item && item.key ? item.key : '') === String(permissionKey || '');
            });
            const rangeOptions = permission && Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
                ? permission.rangeOptions
                : defaultRangeOptions;

            items.push({
                title: getPermissionTitle(permissionKey) + ' (' + profileLabels[profileKey] + ')',
                scope: ranges.map(function (rangeKey) {
                    return getPermissionRangeLabel(rangeKey, rangeOptions);
                }).filter(Boolean).join(' / ')
            });
        });
    });

    return items.sort(function (left, right) {
        return String(left.title || '').localeCompare(String(right.title || ''), 'fr', { sensitivity: 'base' });
    });
}

function buildInheritedPermissionSummary(inheritedPermissions) {
    const profiles = normalizePermissionProfiles(inheritedPermissions);
    const profileLabels = { member: 'Membres', admin: adminLexiconLabel, collective: 'Collectif' };
    const items = [];

    Object.keys(profiles).forEach(function (profileKey) {
        Object.keys(profiles[profileKey] || {}).forEach(function (permissionKey) {
            const permission = profiles[profileKey][permissionKey] || null;
            const visibleItems = permission && Array.isArray(permission.visibleItems) ? permission.visibleItems : [];
            const title = permission ? String(permission.name || permission.shortname || permissionKey || '').trim() : String(permissionKey || '').trim();
            if (!title) {
                return;
            }

            items.push({
                title: title + ' (' + profileLabels[profileKey] + ')',
                scope: visibleItems.map(function (item) {
                    return String(item && item.label ? item.label : '').trim();
                }).filter(Boolean).join(' / ')
            });
        });
    });

    return items.sort(function (left, right) {
        return String(left.title || '').localeCompare(String(right.title || ''), 'fr', { sensitivity: 'base' });
    });
}

function syncPermissionSummary() {
    if (!elements.permissionSummary) {
        return;
    }

    const permissionCatalog = getPermissionCatalog();
    if (!permissionCatalog.length) {
        elements.permissionSummary.innerHTML = ''
            + '<div class="omo-holon-create__permission-summary-line">'
            + '  <div class="omo-holon-create__permission-summary-label">Droits hérités</div>'
            + '  <div class="omo-holon-create__permission-summary-empty">aucun droit disponible</div>'
            + '</div>'
            + '<div class="omo-holon-create__permission-summary-line">'
            +      renderLocalPermissionSummaryHeading()
            + '  <div class="omo-holon-create__permission-summary-empty">aucun droit disponible</div>'
            + '</div>';
        return;
    }

    const inheritedItems = buildInheritedPermissionSummary(getInheritedPermissions());
    const localItems = buildLocalPermissionSummaryItems(readPermissions());
    elements.permissionSummary.innerHTML = ''
        + '<div class="omo-holon-create__permission-summary-line">'
        + '  <div class="omo-holon-create__permission-summary-label">Droits hérités</div>'
        +      renderPermissionSummaryCapsules(inheritedItems, 'aucun')
        + '</div>'
        + '<div class="omo-holon-create__permission-summary-line">'
        +      renderLocalPermissionSummaryHeading()
        +      renderPermissionSummaryCapsules(localItems, 'aucun')
        + '</div>';
}

function renderLocalPermissionSummaryHeading() {
    const isExpanded = Boolean(elements.permissions && elements.permissions.hidden === false);
    return ''
        + '<div class="omo-holon-create__permission-summary-heading">'
        + '  <div class="omo-holon-create__permission-summary-label">' + escapeHtml(directPermissionLabel) + '</div>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--compact" id="omo-holon-create-permissions-toggle" aria-expanded="' + (isExpanded ? 'true' : 'false') + '" aria-controls="omo-holon-create-permissions-editor">' + (isExpanded ? 'Fermer' : 'Éditer') + '</button>'
        + '</div>';
}

function setPermissionEditorExpanded(isExpanded) {
    if (elements.permissions) {
        elements.permissions.hidden = !isExpanded;
    }

    const permissionToggle = root.querySelector('#omo-holon-create-permissions-toggle');
    if (permissionToggle) {
        permissionToggle.textContent = isExpanded ? 'Fermer' : 'Éditer';
        permissionToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    }
}

function getPermissionProfiles() {
    return [
        { key: 'member', label: 'Membres' },
        { key: 'admin', label: adminLexiconLabel },
        { key: 'collective', label: 'Collectif' }
    ];
}

function getPermissionAssignmentsForKey(assignments, permissionKey) {
    const selected = {};
    getPermissionProfiles().forEach(function (profile) {
        selected[profile.key] = normalizePermissionRanges((assignments[profile.key] || {})[permissionKey]);
    });
    return selected;
}

function readPermissionRowAssignments(row) {
    const assignments = { member: [], admin: [], collective: [] };
    Array.from(row.querySelectorAll('[data-permission-scope]')).forEach(function (scope) {
        const range = String(scope.getAttribute('data-permission-token') || '').trim();
        if (!range) return;
        Array.from(scope.querySelectorAll('[data-permission-profile]:checked')).forEach(function (checkbox) {
            const profileKey = String(checkbox.getAttribute('data-permission-profile') || '').trim();
            if (Object.prototype.hasOwnProperty.call(assignments, profileKey)) assignments[profileKey].push(range);
        });
    });
    return assignments;
}

function setPermissionRowRanges(row, selectedAssignments, rangeOptions, profiles) {
    const tokensContainer = row.querySelector('[data-permission-tokens]');
    const select = row.querySelector('[data-permission-select]');
    const selectedByProfile = selectedAssignments && typeof selectedAssignments === 'object' ? selectedAssignments : {};
    const profileList = Array.isArray(profiles) ? profiles : getPermissionProfiles();
    const normalizedRanges = [];
    const seenRanges = new Set();

    profileList.forEach(function (profile) {
        normalizePermissionRanges(selectedByProfile[profile.key]).forEach(function (range) {
            if (seenRanges.has(range)) return;
            seenRanges.add(range);
            normalizedRanges.push(range);
        });
    });

    if (!tokensContainer) {
        return;
    }

    if (!normalizedRanges.length) {
        tokensContainer.innerHTML = '<span class="omo-holon-create__permission-empty">Aucune portee selectionnee.</span>';
    } else {
        tokensContainer.innerHTML = normalizedRanges.map(function (rangeKey) {
            return ''
                + '<div class="omo-holon-create__permission-token" data-permission-token="' + escapeHtml(rangeKey) + '" data-permission-scope>'
                + '  <span class="omo-holon-create__permission-scope-label">' + escapeHtml(getPermissionRangeLabel(rangeKey, rangeOptions)) + '</span>'
                + '  <span class="omo-permission-editor__profiles">'
                + profileList.map(function (profile) {
                    const isChecked = normalizePermissionRanges(selectedByProfile[profile.key]).includes(rangeKey);
                    return '<label title="' + escapeHtml(profile.label) + '"><input type="checkbox" data-permission-profile="' + escapeHtml(profile.key) + '" aria-label="' + escapeHtml(profile.label) + '"' + (isChecked ? ' checked' : '') + '></label>';
                }).join('')
                + '  </span>'
                + '  <button type="button" class="omo-holon-create__permission-token-remove" data-permission-remove="' + escapeHtml(rangeKey) + '" aria-label="Retirer cette portée">&times;</button>'
                + '</div>';
        }).join('');
    }

    if (select) {
        select.value = '';
    }

    syncPermissionSummary();
}

function bindPermissionRow(row, rangeOptions, labelRangeOptions) {
    const select = row.querySelector('[data-permission-select]');
    if (!select || String(select.dataset.bound || '') === '1') {
        return;
    }

    select.dataset.bound = '1';
    select.addEventListener('change', function () {
        const nextRange = String(select.value || '').trim();
        if (!nextRange) {
            return;
        }

        const selectedAssignments = readPermissionRowAssignments(row);
        selectedAssignments.member.push(nextRange);
        setPermissionRowRanges(row, selectedAssignments, labelRangeOptions || rangeOptions);
    });

    row.addEventListener('click', function (event) {
        const removeButton = event.target instanceof Element
            ? event.target.closest('[data-permission-remove]')
            : null;
        if (!removeButton) {
            return;
        }

        const removedRange = String(removeButton.getAttribute('data-permission-remove') || '').trim();
        const selectedAssignments = readPermissionRowAssignments(row);
        Object.keys(selectedAssignments).forEach(function (profileKey) {
            selectedAssignments[profileKey] = selectedAssignments[profileKey].filter(function (range) {
                return range !== removedRange;
            });
        });
        setPermissionRowRanges(row, selectedAssignments, labelRangeOptions || rangeOptions);
    });

    row.addEventListener('change', function () {
        syncPermissionSummary();
    });
}

function renderPermissions(permissionAssignments) {
    const permissionCatalog = getPermissionCatalog();
    const defaultRangeOptions = getPermissionRangeOptions();
    const assignments = normalizePermissionProfiles(permissionAssignments);
    const profiles = getPermissionProfiles();
    const permissionGroups = groupPermissionCatalog(permissionCatalog);

    if (!elements.permissions) {
        return;
    }

    if (!permissionCatalog.length) {
        elements.permissions.innerHTML = '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Aucun droit n est disponible.</div>';
        syncPermissionSummary();
        return;
    }

    let html = '';
    permissionGroups.forEach(function (group) {
        html += '<section class="omo-holon-create__permission-group" data-permission-group="' + escapeHtml(group.key) + '">'
            + '<div class="omo-holon-create__permission-group-title">' + escapeHtml(group.title) + '</div>'
            + '<div class="omo-holon-create__permission-table">';
        group.permissions.forEach(function (permission) {
            const allPermissionRangeOptions = Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
                ? permission.rangeOptions
                : defaultRangeOptions;
            const permissionRangeOptions = getPermissionRangeOptionsForCurrentHolon(allPermissionRangeOptions);

            html += '<div class="omo-holon-create__permission-row" data-permission-key="' + escapeHtml(permission.key) + '">'
                + '<div class="omo-holon-create__permission-main">'
                + '<div class="omo-holon-create__permission-title">' + escapeHtml(permission.title || permission.key) + '</div>'
                + '<div class="omo-holon-create__permission-meta">' + escapeHtml(permission.key) + '</div>';

            if (String(permission.description || '').trim() !== '') {
                html += '<div class="omo-holon-create__permission-description">' + escapeHtml(permission.description) + '</div>';
            }

            html += '</div><div class="omo-holon-create__permission-picker">'
                + '<div class="omo-holon-create__permission-tokens" data-permission-tokens></div>'
                + '<select class="omo-holon-create__permission-select generic-form-control" data-permission-select>'
                + '<option value="">Ajouter une portée...</option>';

            permissionRangeOptions.forEach(function (range) {
                html += '<option value="' + escapeHtml(range.key) + '">' + escapeHtml(range.label || range.key) + '</option>';
            });

            html += '</select></div></div>';
        });
        html += '</div></section>';
    });

    elements.permissions.innerHTML = html;

    Array.from(elements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
        const permissionKey = String(row.getAttribute('data-permission-key') || '').trim();
        const permission = permissionCatalog.find(function (item) {
            return String(item && item.key ? item.key : '') === permissionKey;
        }) || null;
        const allPermissionRangeOptions = permission && Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
            ? permission.rangeOptions
            : defaultRangeOptions;
        const permissionRangeOptions = getPermissionRangeOptionsForCurrentHolon(allPermissionRangeOptions);

        bindPermissionRow(row, permissionRangeOptions, allPermissionRangeOptions);
        setPermissionRowRanges(row, getPermissionAssignmentsForKey(assignments, permissionKey), allPermissionRangeOptions, profiles);
    });

    window.omoPermissionEditorEnhance(elements.permissions, getInheritedPermissions());
    syncPermissionSummary();
}

function groupPermissionCatalog(permissionCatalog) {
    const groupsByKey = {};
    const groups = [];

    permissionCatalog.forEach(function (permission) {
        const key = String(permission && permission.group ? permission.group : 'other');
        if (!groupsByKey[key]) {
            groupsByKey[key] = {
                key: key,
                title: String(permission && permission.groupTitle ? permission.groupTitle : 'Autres droits'),
                order: Number(permission && permission.groupOrder ? permission.groupOrder : 999),
                permissions: []
            };
            groups.push(groupsByKey[key]);
        }
        groupsByKey[key].permissions.push(permission);
    });

    return groups.sort(function (left, right) {
        return left.order - right.order || left.title.localeCompare(right.title, 'fr', { sensitivity: 'base' });
    });
}

function getTemplates() {
    const templates = Array.isArray(state.data.templateCatalog) ? state.data.templateCatalog : [];
    return governanceCapture ? templates.filter(function (template) {
        return [1, 2, 3].indexOf(Number(template && template.typeId ? template.typeId : 0)) !== -1;
    }) : templates;
}

// Liste les holons
function getHolonCatalog() {
    return Array.isArray(state.data.holonCatalog) ? state.data.holonCatalog : [];
}

function getProjectCatalog(scope) {
    const normalizedScope = ['local', 'children', 'descendants', 'global'].indexOf(String(scope || '')) >= 0
        ? String(scope)
        : 'local';
    const catalogs = state.data.projectCatalogs || {};

    if (Array.isArray(catalogs[normalizedScope])) {
        return catalogs[normalizedScope];
    }

    return normalizedScope === 'local' && Array.isArray(state.data.projectCatalog)
        ? state.data.projectCatalog
        : [];
}

function findProject(projectId) {
    const targetId = Number(projectId || 0);
    const scopes = ['local', 'children', 'descendants', 'global'];
    for (let index = 0; index < scopes.length; index += 1) {
        const project = getProjectCatalog(scopes[index]).find(function (candidate) {
            return Number(candidate.id || 0) === targetId;
        });
        if (project) {
            return project;
        }
    }

    return null;
}

function getAuthorityCatalog() {
    return Array.isArray(state.data.authorityCatalog) ? state.data.authorityCatalog : [];
}

function getAuthorityParentCatalog() {
    return Array.isArray(state.data.authorityParentCatalog) ? state.data.authorityParentCatalog : [];
}

function canCreateRootAuthority() {
    return Boolean(state.data.authorityCanCreateRoot);
}

// Trouve un modèle
function findTemplate(templateId) {
    return getTemplates().find(function (template) {
        return Number(template.id || 0) === Number(templateId || 0);
    }) || null;
}

// Lit modèle courant
function getCurrentTemplate() {
    return findTemplate(elements.template.value || 0);
}

// Lit mode courant
function getMode() {
    return String(state.data.mode || 'create');
}

function isTemplateEditing() {
    return false;
}


// Lit holon édité
function getEditingHolon() {
    return state.data && state.data.holon && typeof state.data.holon === 'object'
        ? state.data.holon
        : null;
}

// Synchronise nom verrouille
function syncNameField(template) {
    const editingHolon = getEditingHolon();
    const isLocked = Boolean((editingHolon && editingHolon.nameLocked) || (template && template.lockedName));
    const isUnique = Boolean(template && template.unique);

    if (!elements.name) {
        return;
    }

    if (isLocked) {
        elements.name.dataset.unlockedValue = String(elements.name.value || '');
        elements.name.value = template ? String(template.name || '') : String((editingHolon && editingHolon.name) || '');
        elements.name.disabled = true;
        elements.name.required = false;
        if (elements.nameHelp) {
            elements.nameHelp.textContent = 'Le nom est verrouillé par le modèle.';
        }
        return;
    }

    if (elements.name.disabled) {
        elements.name.value = getMode() === 'edit' && editingHolon
            ? String(editingHolon.name || '')
            : String(elements.name.dataset.unlockedValue || '');
    }

    elements.name.disabled = false;
    elements.name.required = !isUnique;
    if (elements.nameHelp) {
        elements.nameHelp.textContent = isUnique
            ? 'Si le nom est vide, celui du modèle sera utilisé.'
            : '';
    }
}

// Synchronise champ couleur
function syncColorField() {
    const isEnabled = Boolean(elements.colorEnabled && elements.colorEnabled.checked);

    if (elements.colorBody) {
        elements.colorBody.hidden = !isEnabled;
    }

	if (elements.colorEnabledLabel) {
		elements.colorEnabledLabel.hidden = isEnabled;
	}

    if (elements.color) {
        elements.color.disabled = !isEnabled;
    }
}

function normalizeAdminBound(value, allowEmpty) {
    if (allowEmpty && (value === null || value === undefined || String(value).trim() === '')) {
        return null;
    }

    return Math.max(0, Number(value || 0) || 0);
}

function syncAdminBounds(template) {
    const editingHolon = getEditingHolon();
    const minLocked = Boolean(template && template.lockedAdminMin);
    const maxLocked = Boolean(template && template.lockedAdminMax);
    const minOverridden = !minLocked && Boolean(editingHolon && editingHolon.adminMinOverride);
    const maxOverridden = !maxLocked && Boolean(editingHolon && editingHolon.adminMaxOverride);
    const minValue = minOverridden && editingHolon
        ? normalizeAdminBound(editingHolon.adminMin, false)
        : normalizeAdminBound(template && template.adminMin, false);
    const maxValue = maxOverridden && editingHolon
        ? normalizeAdminBound(editingHolon.adminMax, true)
        : normalizeAdminBound(template && template.adminMax, true);

    if (elements.adminBoundsSection) {
        elements.adminBoundsSection.hidden = !template;
    }
    if (elements.adminMinOverride) {
        elements.adminMinOverride.checked = minOverridden;
        elements.adminMinOverride.disabled = minLocked;
    }
    if (elements.adminMaxOverride) {
        elements.adminMaxOverride.checked = maxOverridden;
        elements.adminMaxOverride.disabled = maxLocked;
    }
    if (elements.adminMin) {
        elements.adminMin.value = String(minValue);
        elements.adminMin.disabled = minLocked || !minOverridden;
    }
    if (elements.adminMax) {
        elements.adminMax.value = maxValue === null ? '' : String(maxValue);
        elements.adminMax.disabled = maxLocked || !maxOverridden;
    }
    if (elements.adminBoundsHelp) {
        const locked = [];
        if (minLocked) {
            locked.push('minimum verrouille');
        }
        if (maxLocked) {
            locked.push('maximum verrouille');
        }
        elements.adminBoundsHelp.textContent = locked.length
            ? 'Le modèle impose le ' + locked.join(' et le ') + '.'
            : 'Cochez Redéfinir pour appliquer une limite propre à ce holon.';
    }
}

function getMediaDisplayConfig() {
    return {
        displayWidth: 160,
        displayHeight: 160,
        targetWidth: 320,
        targetHeight: 320,
        emptyText: 'Aucune icône définie pour ce holon.'
    };
}

function resolveMediaState(kind, template) {
    const editingHolon = getEditingHolon();
    const suffix = 'Icon';
    const locked = Boolean(template && template['effectiveLocked' + suffix]);
    const currentController = mediaFields[kind];
    const fallbackLocalValue = editingHolon && !locked
        ? String(editingHolon[kind] || '')
        : '';

    return {
        value: locked
            ? ''
            : (currentController ? currentController.getValue() : fallbackLocalValue),
        inheritedValue: template ? String(template['effective' + suffix] || '') : '',
        locked: locked
    };
}

function renderMediaFields(template) {
    if (!window.omoSizedImageField) {
        return;
    }

    [
        ['icon', elements.iconField, 'Icône']
    ].forEach(function (entry) {
        const kind = entry[0];
        const target = entry[1];
        const label = entry[2];
        if (!target) {
            return;
        }

        const mediaState = resolveMediaState(kind, template);
        const config = getMediaDisplayConfig();
        mediaFields[kind] = window.omoSizedImageField.mount(target, {
            inputName: 'holon_' + kind,
            uploadFieldName: kind,
            value: mediaState.value,
            inheritedValue: mediaState.inheritedValue,
            locked: mediaState.locked,
            displayWidth: config.displayWidth,
            displayHeight: config.displayHeight,
            targetWidth: config.targetWidth,
            targetHeight: config.targetHeight,
            emptyText: config.emptyText,
            labels: {
                choose: 'Choisir une ' + label.toLowerCase(),
                clear: 'Effacer',
                zoom: 'Zoom'
            }
        });
    });
}

// Déduit type liste
function getListInputType(listItemType) {
    if (String(listItemType || 'text') === 'number') {
        return 'number';
    }
    if (String(listItemType || 'text') === 'date') {
        return 'date';
    }
    return 'text';
}

function normalizeDetailedListItem(item) {
    if (item && typeof item === 'object' && !Array.isArray(item)) {
        return {
            title: String(item.title || item.label || item.value || '').trim(),
            description: String(item.description || item.text || '').trim()
        };
    }

    return {
        title: String(item || '').trim(),
        description: ''
    };
}

// Rend ligne liste
function renderSimpleListRow(listItemType, value) {
    if (String(listItemType || 'text') === 'detail') {
        const detailItem = normalizeDetailedListItem(value);
        return ''
            + '<div class="omo-holon-create__list-row omo-holon-create__list-row--detail">'
            + '  <div class="omo-holon-create__list-detail-fields">'
            + '      <input type="text" class="omo-holon-create__property-value-item omo-holon-create__property-value-item--detail-title generic-form-control" value="' + escapeHtml(detailItem.title) + '" placeholder="Titre">'
            + '      <textarea class="omo-holon-create__property-value-item omo-holon-create__property-value-item--detail-description generic-form-control" rows="3" placeholder="Description">' + escapeHtml(detailItem.description) + '</textarea>'
            + '  </div>'
            + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only omo-holon-create__list-move" data-list-move="-1" aria-label="Monter">&#8593;</button>'
            + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only omo-holon-create__list-move" data-list-move="1" aria-label="Descendre">&#8595;</button>'
            + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only omo-holon-create__list-remove" data-list-remove="1" aria-label="Retirer">&times;</button>'
            + '</div>';
    }

    const inputType = getListInputType(listItemType);
    const stepAttribute = inputType === 'number' ? ' step="any"' : '';
    return ''
        + '<div class="omo-holon-create__list-row">'
        + '  <input type="' + inputType + '" class="omo-holon-create__property-value-item generic-form-control" value="' + escapeHtml(value !== undefined && value !== null ? value : '') + '"' + stepAttribute + '>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only omo-holon-create__list-move" data-list-move="-1" aria-label="Monter">&#8593;</button>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only omo-holon-create__list-move" data-list-move="1" aria-label="Descendre">&#8595;</button>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only omo-holon-create__list-remove" data-list-remove="1" aria-label="Retirer">&times;</button>'
        + '</div>';
}

// Rend saisie liste
function renderSimpleListInput(listItemType, values) {
    const rows = Array.isArray(values) && values.length ? values : [''];
    return ''
        + '<div class="omo-holon-create__list" data-list-item-type="' + escapeHtml(listItemType) + '">'
        + '  <div class="omo-holon-create__list-items">'
        + rows.map(function (item) {
            return renderSimpleListRow(listItemType, item);
        }).join('')
        + '  </div>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary omo-holon-create__list-add" data-list-add="1">Ajouter une valeur</button>'
        + '</div>';
}

function getAuthorityId(item) {
    return item && typeof item === 'object' && !Array.isArray(item)
        ? Number(item.id || 0)
        : Number(item || 0);
}

function getAuthorityDeletionImpact(authorityId, authorityDisposition, childrenDisposition) {
    const catalog = getAuthorityCatalog();
    const descendants = [];
    const knownIds = {};
    let pendingIds = [Number(authorityId || 0)];
    while (pendingIds.length) {
        const parentId = pendingIds.shift();
        catalog.forEach(function (entry) {
            const entryId = Number(entry.id || 0);
            if (Number(entry.parentId || 0) !== parentId || entryId <= 0 || knownIds[entryId]) {
                return;
            }
            knownIds[entryId] = true;
            descendants.push(entry);
            pendingIds.push(entryId);
        });
    }

    const affectedAuthorities = [];
    if (authorityDisposition === 'delete') {
        const authority = catalog.find(function (entry) {
            return Number(entry.id || 0) === Number(authorityId || 0);
        });
        if (authority) {
            affectedAuthorities.push(authority);
        }
    }
    if (childrenDisposition === 'delete') {
        descendants.forEach(function (entry) {
            affectedAuthorities.push(entry);
        });
    }

    return {
        descendants: descendants.length,
        rules: affectedAuthorities.reduce(function (total, entry) {
            return total + Number(entry.ruleCount || 0);
        }, 0)
    };
}

function formatAuthorityDeletionCount(count, singular, plural) {
    return ' (' + String(count) + ' ' + (count === 1 ? singular : plural) + ')';
}

function updateAuthorityDeletionCounts(authorityRow) {
    const authorityId = Number(authorityRow && authorityRow.getAttribute('data-authority-id') || 0);
    if (authorityId <= 0) {
        return;
    }
    const getChoice = function (name, fallback) {
        const checked = authorityRow.querySelector('[data-authority-deletion-choice="' + name + '"]:checked');
        return checked ? String(checked.value || fallback) : fallback;
    };
    const impact = getAuthorityDeletionImpact(
        authorityId,
        getChoice('authority', 'reassign'),
        getChoice('children', 'reassign')
    );
    const counts = {
        authority: formatAuthorityDeletionCount(1, 'autorité', 'autorités'),
        children: formatAuthorityDeletionCount(impact.descendants, 'sous-autorité', 'sous-autorités'),
        rules: formatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees')
    };
    Object.keys(counts).forEach(function (name) {
        authorityRow.querySelectorAll('[data-authority-deletion-count="' + name + '"]').forEach(function (element) {
            element.textContent = counts[name];
        });
    });
    authorityRow.querySelectorAll('[data-authority-deletion-group="rules"]').forEach(function (element) {
        element.hidden = impact.rules <= 0;
    });
}

function getAuthorityEntryPayload(authorityRow) {
    const authorityId = Number(authorityRow.getAttribute('data-authority-id') || 0);
    const authority = authorityId > 0 ? getAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === authorityId;
    }) : null;
    if (authorityId > 0 && authorityRow.getAttribute('data-authority-delete') === '1') {
        const getDeletionChoice = function (name, fallback) {
            const checked = authorityRow.querySelector('[data-authority-deletion-choice="' + name + '"]:checked');
            return checked ? String(checked.value || fallback) : fallback;
        };
        return {
            id: authorityId,
            delete: true,
            deletionPlan: {
                authority: getDeletionChoice('authority', 'reassign'),
                children: getDeletionChoice('children', 'reassign'),
                rules: getDeletionChoice('rules', 'reassign')
            }
        };
    }

    const labelField = authorityRow.querySelector('.omo-holon-create__authority-label');
    const parentField = authorityRow.querySelector('.omo-holon-create__authority-parent');
    const descriptionField = authorityRow.querySelector('.omo-holon-create__authority-description');
    const delegationField = authorityRow.querySelector('.omo-holon-create__authority-delegation');
    if (authorityId > 0 && !labelField && !parentField && !descriptionField) {
        return { id: authorityId };
    }

    const label = String(labelField && labelField.value ? labelField.value : '').trim();
    const parentId = Number(parentField && parentField.value ? parentField.value : 0);
    let description = String(descriptionField && descriptionField.value ? descriptionField.value : '').trim();
    if (authority && authority.needsParent && parentId <= 0) {
        description = '[OMO1_IMPORT_NEEDS_PARENT] ' + description;
    }
    const delegationMode = String(delegationField && delegationField.value ? delegationField.value : 'partial');
    if (authorityId > 0) {
        return { id: authorityId, label: label, parentId: parentId, description: description };
    }

    if (delegationMode === 'complete') {
        return parentId > 0 ? { parentId: parentId, delegationMode: 'complete' } : null;
    }
    return label !== '' || parentId > 0 || description !== '' ? { label: label, parentId: parentId, description: description, delegationMode: 'partial' } : null;
}

function renderAuthorityDeletionChoices(authorityId, draft) {
    const plan = draft && draft.deletionPlan && typeof draft.deletionPlan === 'object' ? draft.deletionPlan : {};
    const checked = function (name, value, fallback) {
        return String(plan[name] || fallback) === value ? ' checked' : '';
    };
    const prefix = 'authority-delete-' + String(authorityId);
    const impact = getAuthorityDeletionImpact(authorityId, String(plan.authority || 'reassign'), String(plan.children || 'reassign'));
    return ''
        + '<div class="omo-holon-create__authority-deletion" data-authority-deletion-options>'
        + '  <p>Choisissez ce qui doit etre conserve avant validation.</p>'
        + '  <fieldset><legend>Cette autorité</legend>'
        + '    <label><input type="radio" name="' + prefix + '-authority" value="delete" data-authority-deletion-choice="authority"' + checked('authority', 'delete', 'reassign') + '> Supprimer definitivement</label>'
            + '    <label><input type="radio" name="' + prefix + '-authority" value="reassign" data-authority-deletion-choice="authority"' + checked('authority', 'reassign', 'reassign') + '> Remonter à l’espace parent</label>'
        + '  </fieldset>'
        + (impact.descendants > 0 ? '  <fieldset data-authority-deletion-group="children"><legend>Sous-autorités</legend>'
        + '    <label><input type="radio" name="' + prefix + '-children" value="delete" data-authority-deletion-choice="children"' + checked('children', 'delete', 'reassign') + '> Supprimer les branches<span data-authority-deletion-count="children">' + formatAuthorityDeletionCount(impact.descendants, 'sous-autorité', 'sous-autorités') + '</span></label>'
            + '    <label><input type="radio" name="' + prefix + '-children" value="reassign" data-authority-deletion-choice="children"' + checked('children', 'reassign', 'reassign') + '> Remonter les branches à l’espace parent<span data-authority-deletion-count="children">' + formatAuthorityDeletionCount(impact.descendants, 'sous-autorité', 'sous-autorités') + '</span></label>'
        + '  </fieldset>' : '')
        + '  <fieldset data-authority-deletion-group="rules"' + (impact.rules <= 0 ? ' hidden' : '') + '><legend>Règles des autorités supprimées</legend>'
        + '    <label><input type="radio" name="' + prefix + '-rules" value="delete" data-authority-deletion-choice="rules"' + checked('rules', 'delete', 'reassign') + '> Supprimer les regles<span data-authority-deletion-count="rules">' + formatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees') + '</span></label>'
        + '    <label><input type="radio" name="' + prefix + '-rules" value="reassign" data-authority-deletion-choice="rules"' + checked('rules', 'reassign', 'reassign') + '> Remonter à l’autorité la plus proche et demander une revue sous 2 mois<span data-authority-deletion-count="rules">' + formatAuthorityDeletionCount(impact.rules, 'règle concernée', 'règles concernées') + '</span></label>'
        + '  </fieldset>'
        + '</div>';
}

function renderAuthorityListRow(value) {
    const authorityId = getAuthorityId(value);
    const authority = authorityId > 0 ? getAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === authorityId;
    }) : null;
    const draft = value && typeof value === 'object' && !Array.isArray(value) ? value : {};
    if (authorityId > 0 && !draft.editing) {
        const label = authority ? String(authority.label || '') : 'Autorite #' + String(authorityId);
        const authorityPath = authority ? String(authority.pathLabel || authority.holonLabel || '') : '';
        const detailsPrefix = authority
            ? (authority.templateOriginLost
                ? 'Origine du modèle supprimée'
                : (authority.isTemplateInstance || authority.isTemplateSource
                    ? 'Définie par le modèle'
                    : (authority.needsParent ? 'A rattacher manuellement' : '')))
            : '';
        const details = detailsPrefix + (detailsPrefix && authorityPath ? ' - ' : '') + authorityPath;
        const isManagedTemplateAuthority = Boolean(authority && (authority.isTemplateInstance || authority.isTemplateSource));
        const labelMarkup = authority && (authority.isShell || isManagedTemplateAuthority)
            ? '<em>' + escapeHtml(label) + '</em>'
            : '<strong>' + escapeHtml(label) + '</strong>';
        return ''
            + '<div class="omo-holon-create__authority-row omo-holon-create__authority-row--existing' + (authority && authority.needsParent ? ' is-needs-parent' : '') + (isManagedTemplateAuthority ? ' is-template-instance' : '') + (authority && authority.templateOriginLost ? ' is-template-origin-lost' : '') + '" data-authority-entry data-authority-id="' + authorityId + '">'
            + (isManagedTemplateAuthority
                ? '  <div class="omo-holon-create__authority-edit" aria-disabled="true">' + labelMarkup + (details ? '<small>' + escapeHtml(details) + '</small>' : '') + '</div>'
                : '  <button type="button" class="omo-holon-create__authority-edit" data-authority-edit="1">' + labelMarkup + (details ? '<small>' + escapeHtml(details) + '</small>' : '') + '</button>')
            + (isManagedTemplateAuthority ? '' : '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" data-authority-delete="1" aria-label="Supprimer l’autorité">&times;</button>')
            + (isManagedTemplateAuthority ? '' : renderAuthorityDeletionChoices(authorityId, draft))
            + '</div>';
    }

    const parentId = Number(draft.parentId || (authority ? authority.parentId : 0) || 0);
    const label = String(draft.label || (authority ? authority.label : '') || '');
    const description = String(draft.description || (authority ? authority.description : '') || '');
    const requestedDelegationMode = String(draft.delegationMode || 'partial');
    const selectedParent = getAuthorityParentCatalog().find(function (entry) {
        return Number(entry.id || 0) === parentId;
    }) || null;
    const partialAllowed = !(selectedParent && selectedParent.isShell);
    const delegationMode = !partialAllowed && requestedDelegationMode !== 'complete' ? 'complete' : requestedDelegationMode;
    const parentOptions = getAuthorityParentCatalog().map(function (authority) {
        if (Number(authority.id || 0) === authorityId) {
            return '';
        }
        const selected = Number(authority.id || 0) === parentId ? ' selected' : '';
        const authorityLabel = String(authority.label || '');
        const sourceHolonLabel = String(authority.holonLabel || '');
        const optionLabel = authorityLabel + (sourceHolonLabel !== '' ? ' - ' + sourceHolonLabel : '');
        return '<option value="' + Number(authority.id || 0) + '"' + selected + '>'
            + escapeHtml(optionLabel)
            + '</option>';
    }).join('');

    const rootAuthoritySelected = canCreateRootAuthority() && parentId <= 0;
    const delegationField = authorityId > 0 || rootAuthoritySelected ? '' : ''
        + '      <select class="omo-holon-create__authority-delegation generic-form-control"' + (parentId <= 0 ? ' disabled' : '') + '>'
        + '          <option value="partial"' + (delegationMode !== 'complete' ? ' selected' : '') + (partialAllowed ? '' : ' disabled') + '>Delegation partielle</option>'
        + '          <option value="complete"' + (delegationMode === 'complete' ? ' selected' : '') + '>Delegation complete</option>'
        + '      </select>';
    const authorityDetails = (authorityId > 0 || parentId > 0 || rootAuthoritySelected) && (authorityId > 0 || delegationMode !== 'complete')
        ? '      <input type="text" class="omo-holon-create__authority-label generic-form-control" value="' + escapeHtml(label) + '" placeholder="Nouvelle autorité">'
            + '      <textarea class="omo-holon-create__authority-description generic-form-control" rows="3" placeholder="Description">' + escapeHtml(description) + '</textarea>'
        : parentId > 0 && delegationMode === 'complete'
            ? '      <div class="omo-holon-create__authority-complete-note">L autorite parente sera deleguee completement. Une coquille sera conservee si le chemin doit rester marque.</div>'
            : '';
    const rootOption = canCreateRootAuthority()
        ? '<option value="0"' + (parentId <= 0 ? ' selected' : '') + '>Sans racine</option>'
        : '<option value="0">Autorite parente</option>';

    return ''
        + '<div class="omo-holon-create__authority-row" data-authority-entry' + (authorityId > 0 ? ' data-authority-id="' + authorityId + '"' : '') + '>'
        + '  <div class="omo-holon-create__authority-fields">'
        + '      <select class="omo-holon-create__authority-parent generic-form-control">'
        + '          ' + rootOption + parentOptions
        + '      </select>'
        + delegationField
        + authorityDetails
        + '  </div>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" ' + (authorityId > 0 ? 'data-authority-delete="1" aria-label="Supprimer l’autorité"' : 'data-authority-remove="1" aria-label="Retirer"') + '>&times;</button>'
        + (authorityId > 0 ? renderAuthorityDeletionChoices(authorityId, draft) : '')
        + '</div>';
}

function renderAuthorityListInput(values) {
    const authorities = getAuthorityParentCatalog();
    const rows = Array.isArray(values) && values.length ? values : [];
    const canCreateAuthority = authorities.length > 0 || canCreateRootAuthority();
    if (!canCreateAuthority && !rows.length) {
        return '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Une autorité parente existante est nécessaire avant de pouvoir en créer une nouvelle.</div>';
    }

    return ''
        + '<div class="omo-holon-create__authority-list">'
        + '  <div class="omo-holon-create__authority-items">' + rows.map(renderAuthorityListRow).join('') + '</div>'
        + (canCreateAuthority
            ? '  <button type="button" class="generic-action-button generic-action-button--secondary" data-authority-add="1">Ajouter une autorité</button>'
            : '  <div class="omo-holon-create__empty-note generic-description generic-description--compact">Les autorités existantes restent disponibles, mais une autorité parente est nécessaire pour en créer une nouvelle.</div>')
        + '</div>';
}

// Rend champ propriété
if (window.genericMultilineListPaste && typeof window.genericMultilineListPaste.attach === 'function') {
    window.genericMultilineListPaste.attach(root, {
        inputSelector: '.omo-holon-create__property-value-item',
        rowSelector: '.omo-holon-create__list-row',
        listSelector: '.omo-holon-create__list',
        itemsSelector: '.omo-holon-create__list-items',
        renderRow: renderSimpleListRow
    });
}

function readProjectPickerSelectedIds(projectPicker) {
    return projectPicker
        ? String(projectPicker.dataset.selectedIds || '').split(',').map(function (value) {
            return Number(value || 0);
        }).filter(function (value, index, values) {
            return value > 0 && values.indexOf(value) === index;
        })
        : [];
}

function compareProjectsByTitle(left, right) {
    const leftTitle = String(left && left.title || '').trim();
    const rightTitle = String(right && right.title || '').trim();
    const titleComparison = leftTitle.localeCompare(rightTitle, 'fr', {
        sensitivity: 'base',
        numeric: true
    });

    return titleComparison !== 0
        ? titleComparison
        : Number(left && left.id || 0) - Number(right && right.id || 0);
}

function getProjectPickerCandidates(projectPicker) {
    const globalCatalog = getProjectCatalog('global');
    const catalog = globalCatalog.length > 0
        ? globalCatalog
        : getProjectCatalog(String(projectPicker && projectPicker.dataset.projectScope || 'local'));

    return catalog.slice().sort(compareProjectsByTitle);
}

function renderSelectedProjectRows(selectedIds) {
    if (!selectedIds.length) {
        return '<div class="omo-holon-create__project-selected-empty generic-description generic-description--compact">' + escapeHtml(projectPickerTexts.selectedEmpty) + '</div>';
    }

    return selectedIds.map(function (projectId) {
        return {
            id: Number(projectId),
            project: findProject(projectId)
        };
    }).sort(function (left, right) {
        return compareProjectsByTitle(
            left.project || { id: left.id, title: '#' + String(left.id) },
            right.project || { id: right.id, title: '#' + String(right.id) }
        );
    }).map(function (entry) {
        const projectId = entry.id;
        const project = entry.project;
        const title = project ? String(project.title || '') : ('#' + String(projectId));
        const removeLabel = String(projectPickerTexts.remove || '').replace('{project}', title);
        return ''
            + '<div class="omo-holon-create__project-selected-row" data-selected-project-id="' + Number(projectId) + '">'
            + '  <span class="omo-holon-create__project-selected-copy"><strong>' + escapeHtml(title) + '</strong>'
            + (project && project.holonLabel ? '<small>' + escapeHtml(project.holonLabel) + '</small>' : '') + '</span>'
            + '  <button type="button" class="generic-action-button generic-action-button--quiet-icon generic-action-button--icon-only" data-project-remove="' + Number(projectId) + '" aria-label="' + escapeHtml(removeLabel) + '">&times;</button>'
            + '</div>';
    }).join('');
}

function renderProjectPicker(property, selectedIds, scope) {
    const projectScope = ['local', 'children', 'descendants', 'global'].indexOf(String(scope || '')) >= 0
        ? String(scope)
        : 'local';

    return ''
        + '<div class="omo-holon-create__project-picker" data-project-picker data-project-scope="' + projectScope + '" data-selected-ids="' + selectedIds.join(',') + '">'
        + '  <div class="omo-holon-create__project-selected-list" data-project-selected-list>' + renderSelectedProjectRows(selectedIds) + '</div>'
        + '  <div><button type="button" class="generic-action-button generic-action-button--secondary" data-project-picker-open>' + escapeHtml(projectPickerTexts.add) + '</button></div>'
        + '</div>';
}

function syncProjectPickerSelectedList(projectPicker) {
    const selectedList = projectPicker ? projectPicker.querySelector('[data-project-selected-list]') : null;
    if (selectedList) {
        selectedList.innerHTML = renderSelectedProjectRows(readProjectPickerSelectedIds(projectPicker));
    }
}

function destroyProjectPickerController(projectPicker) {
    if (projectPicker && projectPicker.__omoProjectPickerController && typeof projectPicker.__omoProjectPickerController.destroy === 'function') {
        projectPicker.__omoProjectPickerController.destroy();
    }
    if (projectPicker) {
        projectPicker.__omoProjectPickerController = null;
    }
}

function openProjectPicker(projectPicker) {
    if (!projectPicker || typeof window.commonTopbarPushModal !== 'function' || typeof window.commonTopbarPopModal !== 'function') {
        return;
    }

    const selectedIds = readProjectPickerSelectedIds(projectPicker);
    const selectedProject = selectedIds.map(findProject).find(function (project) {
        return project && Number(project.holonId || 0) > 0;
    });
    let initialHolonId = Number(projectPicker.dataset.projectPickerHolonId || (selectedProject && selectedProject.holonId) || state.data.holonId || state.data.contextHolonId || 0);

    projectPicker.dataset.projectPickerHolonId = String(initialHolonId || 0);
    const modalHtml = ''
        + '<div class="omo-document-embed-picker omo-resource-picker omo-holon-create__project-picker-content generic-drawer-content" data-holon-project-picker-modal data-topbar-modal-max-width="1100px">'
        + '  <aside class="omo-resource-picker__navigation" data-project-holon-scope></aside>'
        + '  <div class="omo-resource-picker__content omo-holon-create__project-picker-results">'
        + '    <label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-project-picker-search placeholder="' + escapeHtml(projectPickerTexts.search) + '" aria-label="' + escapeHtml(projectPickerTexts.search) + '"></label>'
        + '    <div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select omo-holon-create__project-choice-select" data-project-choice-select size="10" multiple></select></div>'
        + '    <p class="generic-description generic-description--compact" data-project-picker-empty hidden>' + escapeHtml(projectPickerTexts.empty) + '</p>'
        + '    <div class="omo-document-embed-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-project-picker-cancel>' + escapeHtml(projectPickerTexts.cancel) + '</button><button type="button" class="generic-action-button generic-action-button--main" data-project-picker-confirm>' + escapeHtml(projectPickerTexts.confirm) + '</button></div>'
        + '  </div>'
        + '</div>';

    if (!window.commonTopbarPushModal(projectPickerTexts.title, modalHtml, 'html')) {
        return;
    }
    const modalBody = document.getElementById('commonTopbarModalBody');
    const modalRoot = modalBody ? modalBody.querySelector('[data-holon-project-picker-modal]') : null;
    const search = modalRoot ? modalRoot.querySelector('[data-project-picker-search]') : null;
    const cancelButton = modalRoot ? modalRoot.querySelector('[data-project-picker-cancel]') : null;
    const confirmButton = modalRoot ? modalRoot.querySelector('[data-project-picker-confirm]') : null;
    let closed = false;

    const cleanup = function () {
        if (closed) {
            return;
        }
        closed = true;
        window.removeEventListener('common-topbar-modal-pop', handleModalPop);
        destroyProjectPickerController(projectPicker);
    };
    const closeAndRestoreEditor = function () {
        cleanup();
        window.commonTopbarPopModal();
    };
    const handleModalPop = function () {
        cleanup();
    };

    window.addEventListener('common-topbar-modal-pop', handleModalPop);

    if (modalRoot && typeof window.commonMountProjectPicker === 'function') {
        projectPicker.__omoProjectPickerController = window.commonMountProjectPicker({
            root: modalRoot,
            scopeHost: '[data-project-holon-scope]',
            searchInput: '[data-project-picker-search]',
            selectElement: '[data-project-choice-select]',
            emptyElement: '[data-project-picker-empty]',
            projects: getProjectPickerCandidates(projectPicker),
            organizationId: <?= $organizationId ?>,
            initialHolonId: initialHolonId,
            initialScope: 'local',
            selectedIds: selectedIds,
            multiple: true,
            showModes: true,
            scopeLabels: {
                local: projectPickerTexts.scopeLocal,
                children: projectPickerTexts.scopeChildren,
                descendants: projectPickerTexts.scopeDescendants
            },
            labelMode: 'context',
            getHolonId: function (project) { return Number(project.holonId || 0); },
            getSearchText: function (project) { return [project.title, project.holonLabel].join(' '); },
            getOptionLabel: function (project) {
                return String(project.title || '') + (project.holonLabel ? ' — ' + String(project.holonLabel) : '');
            },
            onHolonChange: function (holonId) {
                projectPicker.dataset.projectPickerHolonId = String(Number(holonId || 0));
            }
        });
    }
    if (cancelButton) {
        cancelButton.addEventListener('click', closeAndRestoreEditor);
    }
    if (confirmButton) {
        confirmButton.addEventListener('click', function () {
            if (projectPicker.__omoProjectPickerController && typeof projectPicker.__omoProjectPickerController.getSelectedIds === 'function') {
                projectPicker.dataset.selectedIds = projectPicker.__omoProjectPickerController.getSelectedIds().join(',');
                syncProjectPickerSelectedList(projectPicker);
            }
            closeAndRestoreEditor();
        });
    }
    if (search) {
        search.focus();
    }
}

function renderPropertyInput(property) {
    const formatId = Number(property.formatId || 0);
    const localValue = property.value !== undefined && property.value !== null
        ? String(property.value)
        : '';

    if (!property.canEditValue) {
        return renderReadonlyPropertyValue(property, localValue)
            + '<div class="omo-holon-create__permission-note generic-description generic-description--compact">Vous n\'avez pas les droits de modification.</div>';
    }

    if (formatId === 2) {
        if (String(property.listItemType || 'text') === 'authority') {
            return renderAuthorityListInput(parseStoredListValue(localValue));
        }

        if (String(property.listItemType || 'text') === 'holon') {
            const allowedTypeIds = Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds.map(Number) : [];
            const holonOptions = getHolonCatalog().filter(function (holon) {
                return allowedTypeIds.length === 0 || allowedTypeIds.indexOf(Number(holon.typeId || 0)) >= 0;
            });
            const selectedIds = parseStoredListValue(localValue).map(Number);

            if (!holonOptions.length) {
        return '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Aucun élément disponible pour les types autorisés.</div>';
            }

            return '<div class="omo-holon-create__check-grid">'
                + holonOptions.map(function (holon) {
                    const checked = selectedIds.indexOf(Number(holon.id)) >= 0 ? ' checked' : '';
                    return ''
                        + '<label class="omo-holon-create__check-option">'
                        + '  <input type="checkbox" class="omo-holon-create__property-value omo-holon-create__property-value--holon" value="' + Number(holon.id) + '"' + checked + '>'
                        + '  <span>' + escapeHtml(holon.name) + '<small>' + escapeHtml(holon.pathLabel || holon.typeLabel || '') + '</small></span>'
                        + '</label>';
                }).join('')
                + '</div>';
        }

        if (String(property.listItemType || 'text') === 'project') {
            const selectedIds = parseStoredListValue(localValue).map(Number);
            return renderProjectPicker(property, selectedIds, property.projectScope || 'local');
        }

        return renderSimpleListInput(property.listItemType || 'text', parseStoredListValue(localValue));
    }

    if (formatId === 6) {
        let parts = {};
        try { parts = JSON.parse(localValue) || {}; } catch (error) {}
        return '<input type="text" class="omo-holon-create__property-value-text-html-title generic-form-control" value="' + escapeHtml(parts.text || '') + '" placeholder="Texte affiche">'
            + '<textarea class="omo-holon-create__property-value-text-html-detail generic-form-control" rows="5" placeholder="Detail HTML">' + escapeHtml(parts.detail || '') + '</textarea>';
    }

    if (formatId === 7) {
        let parts = {};
        try { parts = JSON.parse(localValue) || {}; } catch (error) {}
        const listControl = renderPropertyInput(Object.assign({}, property, { formatId: 2, value: JSON.stringify(Array.isArray(parts.items) ? parts.items : []) }));
        return '<div class="omo-holon-create__composite-html" data-omo-composite-html="before" data-value="' + escapeHtml(parts.before || '') + '"></div>'
            + '<div class="omo-holon-create__composite-list">' + listControl + '</div>'
            + '<div class="omo-holon-create__composite-html" data-omo-composite-html="after" data-value="' + escapeHtml(parts.after || '') + '"></div>';
    }

    if (formatId === 3) {
        return '<input type="number" step="any" class="omo-holon-create__property-value generic-form-control" value="' + escapeHtml(localValue) + '" placeholder="Ex.: 42">';
    }

    if (formatId === 4) {
        return '<input type="date" class="omo-holon-create__property-value generic-form-control" value="' + escapeHtml(localValue) + '">';
    }

    if (formatId === 5) {
        return '<div class="omo-holon-create__html-editor"></div>';
    }

    return '<textarea class="omo-holon-create__property-value generic-form-control" rows="4" placeholder="Renseignez une valeur locale si nécessaire.">' + escapeHtml(localValue) + '</textarea>';
}

// Formate holon hérité
function formatInheritedHolonItem(item) {
    const holonId = Number(item || 0);
    const holon = getHolonCatalog().find(function (entry) {
        return Number(entry.id || 0) === holonId;
    });

    return holon ? holon.pathLabel : String(item || '');
}

function formatInheritedProjectItem(item) {
    const projectId = Number(item || 0);
    const project = findProject(projectId);

    return project ? project.title : String(item || '');
}

function formatInheritedAuthorityItem(item) {
    const authorityId = getAuthorityId(item);
    const authority = getAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === authorityId;
    });

    return authority ? String(authority.pathLabel || authority.label || '') : String(item || '');
}

function getPropertyPreviewItems(property, rawValue) {
    return parseStoredListValue(rawValue).map(function (item) {
        if (String(property.listItemType || 'text') === 'detail') {
            return normalizeDetailedListItem(item);
        }
        if (String(property.listItemType || 'text') === 'holon') {
            return formatInheritedHolonItem(item);
        }
        if (String(property.listItemType || 'text') === 'project') {
            return formatInheritedProjectItem(item);
        }
        if (String(property.listItemType || 'text') === 'authority') {
            return formatInheritedAuthorityItem(item);
        }
        return String(item || '');
    }).filter(Boolean);
}

function renderReadonlyListContent(property, rawValue) {
    const items = getPropertyPreviewItems(property, rawValue);
    if (!items.length) {
        return '';
    }

    if (String(property.listItemType || 'text') === 'detail') {
        return '<div class="omo-holon-create__inherited-detail-list">'
            + items.map(function (item) {
                return '<details class="omo-holon-create__detail-card">'
                    + '<summary>' + escapeHtml(item.title || 'Element') + '</summary>'
                    + (item.description !== ''
                        ? '<div class="omo-holon-create__detail-body">' + escapeHtml(item.description).replace(/\n/g, '<br>') + '</div>'
                        : '')
                    + '</details>';
            }).join('')
            + '</div>';
    }

    return '<ul class="omo-holon-create__inherited-list">'
        + items.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('')
        + '</ul>';
}

function renderReadonlyPropertyValue(property, rawValue) {
    const value = rawValue !== undefined && rawValue !== null ? String(rawValue) : '';
    if (!value.trim()) {
        return '';
    }

    const formatId = Number(property.formatId || 0);
    let content = '';

    if (formatId === 2) {
        content = renderReadonlyListContent(property, value);
    } else if (formatId === 5) {
        content = renderHtmlPreview(value, 'omo-holon-create__readonly-text generic-meta');
    } else if (formatId === 6) {
        let parts = {};
        try { parts = JSON.parse(value) || {}; } catch (error) {}
        content = (String(parts.text || '').trim() !== ''
            ? '<div class="omo-holon-create__readonly-text generic-meta">' + escapeHtml(parts.text) + '</div>'
            : '')
            + (String(parts.detail || '').trim() !== ''
                ? renderHtmlPreview(parts.detail, 'omo-holon-create__readonly-text generic-meta')
                : '');
    } else if (formatId === 7) {
        let parts = {};
        try { parts = JSON.parse(value) || {}; } catch (error) {}
        content = (String(parts.before || '').trim() !== ''
            ? renderHtmlPreview(parts.before, 'omo-holon-create__readonly-text generic-meta')
            : '')
            + renderReadonlyListContent(property, JSON.stringify(Array.isArray(parts.items) ? parts.items : []))
            + (String(parts.after || '').trim() !== ''
                ? renderHtmlPreview(parts.after, 'omo-holon-create__readonly-text generic-meta')
                : '');
    } else {
        content = '<div class="omo-holon-create__readonly-text generic-meta">' + escapeHtml(value).replace(/\n/g, '<br>') + '</div>';
    }

    return content
        ? '<div class="omo-holon-create__readonly-value generic-soft-panel generic-soft-panel--stack">' + content + '</div>'
        : '';
}

// Rend valeur héritée
function renderInheritedValue(property) {
    const inheritedValue = property.inheritedValue !== undefined && property.inheritedValue !== null
        ? String(property.inheritedValue)
        : '';

    if (!inheritedValue.trim()) {
        return '';
    }

    if (Number(property.formatId || 0) === 2) {
        const items = getPropertyPreviewItems(property, inheritedValue);

        if (!items.length) {
            return '';
        }

        if (String(property.listItemType || 'text') === 'detail') {
            return ''
                + '<div class="omo-holon-create__inherited">'
                + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur héritée</div>'
                + '  <div class="omo-holon-create__inherited-detail-list">'
                + items.map(function (item) {
                    return ''
                        + '<details class="omo-holon-create__detail-card">'
                        + '  <summary>' + escapeHtml(item.title || 'Element') + '</summary>'
                        + (item.description !== ''
                            ? '  <div class="omo-holon-create__detail-body">' + escapeHtml(item.description).replace(/\n/g, '<br>') + '</div>'
                            : '')
                        + '</details>';
                }).join('')
                + '  </div>'
                + '</div>';
        }

        return ''
            + '<div class="omo-holon-create__inherited">'
            + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur héritée</div>'
            + '  <ul class="omo-holon-create__inherited-list">'
            + items.map(function (item) {
                return '<li>' + escapeHtml(item) + '</li>';
            }).join('')
            + '  </ul>'
            + '</div>';
    }

    if (Number(property.formatId || 0) === 5) {
        return ''
            + '<div class="omo-holon-create__inherited">'
            + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur héritée</div>'
            +       renderHtmlPreview(inheritedValue, 'omo-holon-create__inherited-text generic-meta')
            + '</div>';
    }

    return ''
        + '<div class="omo-holon-create__inherited">'
        + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur héritée</div>'
        + '  <div class="omo-holon-create__inherited-text generic-meta">' + escapeHtml(inheritedValue).replace(/\n/g, '<br>') + '</div>'
        + '</div>';
}

// Crée ligne propriété
function isDirectHolonProperty(property) {
    return !isTemplateEditing() && Boolean(property && property.isDirectProperty);
}

function getPropertyFormatOptions(formatId) {
    return (state.data.formats || []).map(function (format) {
        const selected = Number(format.id || 0) === Number(formatId || 0) ? ' selected' : '';
        return '<option value="' + Number(format.id || 0) + '"' + selected + '>' + escapeHtml(format.name || '') + '</option>';
    }).join('');
}

function renderDirectPropertyListConfig(property, disabled) {
    const formatId = Number(property.formatId || 0);
    if ([2, 7].indexOf(formatId) < 0) {
        return '';
    }

    const disabledAttribute = disabled ? ' disabled' : '';
    const itemType = String(property.listItemType || 'text');
    const itemTypeOptions = (state.data.listItemTypes || []).map(function (itemTypeOption) {
        const selected = String(itemTypeOption.id || '') === itemType ? ' selected' : '';
        return '<option value="' + escapeHtml(itemTypeOption.id || '') + '"' + selected + '>' + escapeHtml(itemTypeOption.name || '') + '</option>';
    }).join('');
    const selectedTypeIds = Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds.map(Number) : [];
    const holonTypeOptions = itemType === 'holon'
        ? '<div class="omo-holon-create__check-grid">' + (state.data.types || []).map(function (type) {
            const checked = selectedTypeIds.indexOf(Number(type.id || 0)) >= 0 ? ' checked' : '';
            return '<label class="omo-holon-create__check-option"><input type="checkbox" class="omo-holon-create__direct-property-holon-type" value="' + Number(type.id || 0) + '"' + checked + disabledAttribute + '><span>' + escapeHtml(type.name || '') + '</span></label>';
        }).join('') + '</div>'
        : '';

    return ''
        + '<label class="omo-holon-create__field generic-form-field">'
        + '  <span>Type des elements</span>'
        + '  <select class="omo-holon-create__direct-property-list-type generic-form-control"' + disabledAttribute + '>' + itemTypeOptions + '</select>'
        + '</label>'
        + holonTypeOptions;
}

function renderDirectPropertyDefinition(property) {
    if (!isDirectHolonProperty(property)) {
        return '';
    }

    const disabled = !property.canEditDefinition;
    const disabledAttribute = disabled ? ' disabled' : '';
    const deleteDisabled = property.canDelete ? '' : ' disabled';
    return ''
        + '<div class="omo-holon-create__direct-property-definition generic-soft-panel">'
        + '  <div class="omo-holon-create__grid">'
        + '    <label class="omo-holon-create__field generic-form-field"><span>Nom de la propriété</span><input type="text" class="omo-holon-create__direct-property-name generic-form-control" maxlength="255" value="' + escapeHtml(property.name || '') + '"' + disabledAttribute + '></label>'
        + '    <label class="omo-holon-create__field generic-form-field"><span>Format</span><select class="omo-holon-create__direct-property-format generic-form-control"' + disabledAttribute + '>' + getPropertyFormatOptions(property.formatId) + '</select></label>'
        +      renderDirectPropertyListConfig(property, disabled)
        + '  </div>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary" data-direct-property-remove="1"' + deleteDisabled + '>Retirer cette propriété</button>'
        + '</div>';
}

function createPropertyRow(property, index) {
    const row = document.createElement('div');
    row.className = 'omo-holon-create__property generic-section generic-section--plain generic-section--flush';
    row.dataset.propertyId = Number(property.id || 0);
    row.dataset.holonPropertyId = Number(property.holonPropertyId || 0);
    row.dataset.formatId = Number(property.formatId || 0);
    row.dataset.listItemType = String(property.listItemType || 'text');
    row.dataset.propertyName = String(property.name || '');
    row.dataset.shortname = String(property.shortname || '');
    row.dataset.value = property.value !== undefined && property.value !== null ? String(property.value) : '';
    row.dataset.listHolonTypeIds = JSON.stringify(Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds : []);
    row.dataset.mandatory = property.mandatory ? '1' : '0';
    row.dataset.locked = property.locked ? '1' : '0';
    row.dataset.localMandatory = property.mandatory ? '1' : '0';
    row.dataset.localLocked = property.locked ? '1' : '0';
    row.dataset.inheritedMandatory = property.inheritedMandatory ? '1' : '0';
    row.dataset.inheritedLocked = property.inheritedLocked ? '1' : '0';
    row.dataset.isInherited = property.isInherited ? '1' : '0';
    row.dataset.isLocal = property.isLocal ? '1' : '0';
    row.dataset.isDirectProperty = property.isDirectProperty ? '1' : '0';
    row.dataset.isTemplateProperty = property.isTemplateProperty ? '1' : '0';
    row.dataset.canEditDefinition = property.canEditDefinition ? '1' : '0';
    row.dataset.canDelete = property.canDelete ? '1' : '0';
    row.dataset.canEditValue = property.canEditValue ? '1' : '0';

    const chips = [];
    if (property.formatName) {
        chips.push('<span class="omo-holon-create__chip omo-holon-create__chip--accent">' + escapeHtml(property.formatName) + '</span>');
    }
    if (property.effectiveMandatory) {
        chips.push('<span class="omo-holon-create__chip">Obligatoire</span>');
    }
    if (property.effectiveLocked) {
        chips.push('<span class="omo-holon-create__chip">Verrouillée</span>');
    }

    row.innerHTML = ''
        + '<div class="omo-holon-create__property-index">P' + String(index + 1) + '</div>'
        + '<div class="omo-holon-create__property-body">'
        + '  <div class="omo-holon-create__property-head">'
        + '      <div>'
        + '          <div class="omo-holon-create__property-name">' + escapeHtml(property.name || ('Propriété ' + Number(property.id || 0))) + '</div>'
        + '          <div class="omo-holon-create__property-meta">' + chips.join('') + '</div>'
        + '      </div>'
        + '  </div>'
        + renderDirectPropertyDefinition(property)
        + renderInheritedValue(property)
        + '  <' + ([5, 7].indexOf(Number(property.formatId || 0)) >= 0 ? 'div' : 'label') + ' class="omo-holon-create__field generic-form-field">'
        + '      <span>Valeur locale</span>'
        + '      <div class="omo-holon-create__property-input">' + renderPropertyInput(property) + '</div>'
        + '  </' + ([5, 7].indexOf(Number(property.formatId || 0)) >= 0 ? 'div' : 'label') + '>'
        + '</div>';

    const propertyTitle = row.querySelector('.omo-holon-create__property-name');
    if (propertyTitle && (!property.name || Number(property.id || 0) <= 0)) {
        propertyTitle.textContent = String(property.name || '').trim() || 'Nouvelle propriété';
    }

    if ([5, 7].indexOf(Number(property.formatId || 0)) >= 0) {
        row.querySelectorAll('.omo-holon-create__html-editor, [data-omo-composite-html]').forEach(function (htmlEditorHost) {
        if (htmlEditorHost && window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.mount === 'function') {
            window.omoSimpleHtmlField.mount(htmlEditorHost, {
                value: htmlEditorHost.hasAttribute('data-omo-composite-html') ? String(htmlEditorHost.getAttribute('data-value') || '') : (property.value !== undefined && property.value !== null ? String(property.value) : ''),
                placeholder: 'Renseignez une valeur locale si necessaire.'
            });
        }
        });
    }

    return row;
}

// Rend bloc propriétés
function renderProperties(properties) {
    elements.properties.innerHTML = '';

    if (!Array.isArray(properties) || !properties.length) {
        elements.properties.innerHTML = '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Ce modèle ne définit aucune propriété.</div>';
        return;
    }

    properties.forEach(function (property, index) {
        elements.properties.appendChild(createPropertyRow(property, index));
    });
}

function getDirectPropertyDraft(row) {
    const nameField = row.querySelector('.omo-holon-create__direct-property-name');
    const formatField = row.querySelector('.omo-holon-create__direct-property-format');
    const listTypeField = row.querySelector('.omo-holon-create__direct-property-list-type');
    const formatId = Number(formatField && formatField.value ? formatField.value : row.dataset.formatId || 1);
    const format = (state.data.formats || []).find(function (item) {
        return Number(item.id || 0) === formatId;
    });
    return {
        id: Number(row.dataset.propertyId || 0),
        holonPropertyId: Number(row.dataset.holonPropertyId || 0),
        name: String(nameField && nameField.value ? nameField.value : row.dataset.propertyName || ''),
        shortname: String(row.dataset.shortname || ''),
        formatId: formatId,
        formatName: format ? String(format.name || '') : '',
        listItemType: String(listTypeField && listTypeField.value ? listTypeField.value : row.dataset.listItemType || 'text'),
        listHolonTypeIds: Array.from(row.querySelectorAll('.omo-holon-create__direct-property-holon-type:checked')).map(function (input) {
            return Number(input.value || 0);
        }).filter(Boolean),
        value: serializePropertyValue(row),
        isDirectProperty: true,
        isTemplateProperty: false,
        canEditDefinition: String(row.dataset.canEditDefinition || '0') === '1',
        canDelete: String(row.dataset.canDelete || '0') === '1',
        canEditValue: String(row.dataset.canEditValue || '0') === '1'
    };
}

// Prépare propriétés modèle
function buildPropertiesForTemplate(template, sourceProperties) {
    const sourceMap = new Map();
    const directProperties = [];
    (sourceProperties || []).forEach(function (property) {
        sourceMap.set(Number(property.id || 0), property);
        if (property && property.isDirectProperty) {
            directProperties.push(property);
        }
    });

    if (!template) {
        return [];
    }

    const templateProperties = (template && Array.isArray(template.properties) ? template.properties : []).map(function (property) {
        const source = sourceMap.get(Number(property.id || 0));
        const sourceInheritedValue = source && source.inheritedValue !== undefined && source.inheritedValue !== null
            ? String(source.inheritedValue)
            : '';
        const templateInheritedValue = property.inheritedValue !== undefined && property.inheritedValue !== null
            ? String(property.inheritedValue)
            : '';
        const inheritedValue = sourceInheritedValue.trim() !== '' ? sourceInheritedValue : templateInheritedValue;
        return Object.assign({}, property, {
            value: source && source.value !== undefined && source.value !== null ? String(source.value) : '',
            inheritedValue: inheritedValue,
            inheritedMandatory: source && source.inheritedMandatory !== undefined ? Boolean(source.inheritedMandatory) : property.inheritedMandatory,
            inheritedLocked: Boolean((source && source.inheritedLocked) || property.inheritedLocked),
            effectiveMandatory: Boolean((source && source.effectiveMandatory) || property.effectiveMandatory),
            effectiveLocked: Boolean((source && source.effectiveLocked) || property.effectiveLocked),
            canEditValue: source && source.canEditValue !== undefined ? Boolean(source.canEditValue) : property.canEditValue,
            isTemplateProperty: true,
            isDirectProperty: false,
            canEditDefinition: false,
            canDelete: false
        });
    });

    return templateProperties.concat(directProperties);
}

// Rend options modèles
function renderTemplateOptions(preferredTemplateId) {
    const templates = getTemplates();

    elements.template.innerHTML = '';
    let currentGroupId = null;
    let currentGroup = null;
    templates.forEach(function (template, index) {
        const definedInId = Number(template.definedInId || 0);
        const contextName = String(template.definedInName || template.definedInLabel || '').trim();
        const groupId = definedInId > 0 ? String(definedInId) : 'unassigned';
        if (groupId !== currentGroupId) {
            currentGroupId = groupId;
            currentGroup = document.createElement('optgroup');
            currentGroup.label = contextName || 'Élément';
            elements.template.appendChild(currentGroup);
        }

        const option = document.createElement('option');
        const name = String(template.name || '').trim();
        option.value = Number(template.id);
        option.textContent = name;
        option.selected = Number(preferredTemplateId || 0) === Number(template.id) || (!preferredTemplateId && index === 0);
        currentGroup.appendChild(option);
    });

    if (!elements.template.value && templates.length) {
        elements.template.value = String(Number(templates[0].id));
    }

    elements.template.required = true;
}

function syncSubmitLabel(template) {
    if (!elements.submit) {
        return;
    }

    if (getMode() === 'edit') {
        elements.submit.textContent = 'Enregistrer l’élément';
        return;
    }

    const typeLabel = String(template && template.typeLabel ? template.typeLabel : '').trim();
    elements.submit.textContent = typeLabel !== ''
        ? 'Créer un élément de type ' + typeLabel
        : 'Créer un élément';
}

// Synchronise modèle courant
function renderEditorMeta(template, sourceProperties) {
    const editingHolon = getEditingHolon();
    const properties = buildPropertiesForTemplate(template, sourceProperties);
    elements.hint.textContent = '';
    renderProperties(properties);
    if (elements.addProperty) {
        elements.addProperty.disabled = isTemplateEditing() || !Boolean(state.data.canAddHolonProperties);
    }

    if (elements.color) {
        const resolvedColor = getMode() === 'edit' && editingHolon
            ? String(editingHolon.color || (template ? template.color : '') || '')
            : String((template ? template.color : '') || '');
        elements.color.value = resolvedColor.trim() !== '' ? resolvedColor : '#f59e0b';
    }

    if (elements.colorEnabled) {
        elements.colorEnabled.checked = getMode() === 'edit'
            ? String((editingHolon && editingHolon.color) || '').trim() !== ''
            : false;
    }

    syncNameField(template);
    syncColorField();
	 syncAdminBounds(template);
    renderMediaFields(template);
    syncSubmitLabel(template);
}

function syncTemplateSelection(preferredTemplateId, sourceProperties) {
    renderTemplateOptions(preferredTemplateId);
    renderEditorMeta(getCurrentTemplate(), sourceProperties);
}

// Sérialise valeur propriété
function serializePropertyValue(row) {
    const formatId = Number(row.dataset.formatId || 0);
    const listItemType = String(row.dataset.listItemType || 'text');
    const canEditValue = String(row.dataset.canEditValue || '0') === '1';
    const htmlFieldHost = row.querySelector('[data-omo-html-field="1"]');

    if (!canEditValue) {
        return String(row.dataset.value || '');
    }

    if (formatId === 5 && htmlFieldHost && htmlFieldHost.__omoSimpleHtmlField && typeof htmlFieldHost.__omoSimpleHtmlField.getValue === 'function') {
        return String(htmlFieldHost.__omoSimpleHtmlField.getValue() || '');
    }

    if (formatId === 2) {
        if (listItemType === 'authority') {
            const items = Array.from(row.querySelectorAll('[data-authority-entry]')).map(getAuthorityEntryPayload).filter(Boolean);
            return items.length ? JSON.stringify(items) : '';
        }

        if (listItemType === 'holon') {
            const selectedIds = Array.from(row.querySelectorAll('.omo-holon-create__property-value--holon:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        if (listItemType === 'project') {
            const projectPicker = row.querySelector('[data-project-picker]');
            if (projectPicker) {
                const selectedIds = readProjectPickerSelectedIds(projectPicker);
                return selectedIds.length ? JSON.stringify(selectedIds) : '';
            }
            const selectedIds = Array.from(row.querySelectorAll('.omo-holon-create__property-value--project:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        if (listItemType === 'detail') {
            const items = Array.from(row.querySelectorAll('.omo-holon-create__list-row--detail')).map(function (detailRow) {
                const titleField = detailRow.querySelector('.omo-holon-create__property-value-item--detail-title');
                const descriptionField = detailRow.querySelector('.omo-holon-create__property-value-item--detail-description');
                const item = {
                    title: String(titleField && titleField.value ? titleField.value : '').trim(),
                    description: String(descriptionField && descriptionField.value ? descriptionField.value : '').trim()
                };

                return item.title !== '' || item.description !== '' ? item : null;
            }).filter(Boolean);

            return items.length ? JSON.stringify(items) : '';
        }

        const items = Array.from(row.querySelectorAll('.omo-holon-create__property-value-item')).map(function (input) {
            return String(input.value || '').trim();
        }).filter(Boolean);

        return items.length ? JSON.stringify(items) : '';
    }

    if (formatId === 6) {
        return JSON.stringify({
            text: String((row.querySelector('.omo-holon-create__property-value-text-html-title') || {}).value || '').trim(),
            detail: String((row.querySelector('.omo-holon-create__property-value-text-html-detail') || {}).value || '')
        });
    }

    if (formatId === 7) {
        const projectPicker = listItemType === 'project' ? row.querySelector('[data-project-picker]') : null;
        const items = listItemType === 'authority'
            ? Array.from(row.querySelectorAll('[data-authority-entry]')).map(getAuthorityEntryPayload).filter(Boolean)
            : listItemType === 'project' ? (projectPicker ? readProjectPickerSelectedIds(projectPicker) : Array.from(row.querySelectorAll('.omo-holon-create__property-value--project:checked')).map(function (input) { return Number(input.value || 0); }).filter(Boolean)) : listItemType === 'holon' ? Array.from(row.querySelectorAll('.omo-holon-create__property-value--holon:checked')).map(function (input) { return Number(input.value || 0); }).filter(Boolean) : Array.from(row.querySelectorAll('.omo-holon-create__property-value-item')).map(function (input) { return String(input.value || '').trim(); }).filter(Boolean);
        const beforeHost = row.querySelector('[data-omo-composite-html="before"]');
        const afterHost = row.querySelector('[data-omo-composite-html="after"]');
        return JSON.stringify({
            before: beforeHost && beforeHost.__omoSimpleHtmlField ? String(beforeHost.__omoSimpleHtmlField.getValue() || '') : '',
            items: items,
            after: afterHost && afterHost.__omoSimpleHtmlField ? String(afterHost.__omoSimpleHtmlField.getValue() || '') : ''
        });
    }

    const valueField = row.querySelector('.omo-holon-create__property-value');
    return valueField ? String(valueField.value || '') : '';
}

// Lit valeurs propriétés
function readProperties() {
    return Array.from(elements.properties.querySelectorAll('.omo-holon-create__property')).map(function (row) {
        const listItemType = String(row.dataset.listItemType || 'text');
        const value = serializePropertyValue(row);
        const property = {
            id: Number(row.dataset.propertyId || 0),
            name: String(row.dataset.propertyName || ''),
            shortname: String(row.dataset.shortname || ''),
            formatId: Number(row.dataset.formatId || 0),
            listItemType: listItemType,
            value: value
        };

        if (listItemType === 'authority') {
            let authorityItems = [];
            try {
                const decodedAuthorityValue = JSON.parse(String(value || ''));
                authorityItems = Array.isArray(decodedAuthorityValue)
                    ? decodedAuthorityValue
                    : (decodedAuthorityValue && Array.isArray(decodedAuthorityValue.items) ? decodedAuthorityValue.items : []);
            } catch (error) {
                authorityItems = parseStoredListValue(value);
            }
            property.displayValue = authorityItems.filter(function (item) {
                return !(item && typeof item === 'object' && item.delete === true);
            }).map(function (item) {
                if (item && typeof item === 'object' && String(item.label || '').trim() !== '') {
                    return String(item.label).trim();
                }
                return formatInheritedAuthorityItem(item);
            }).filter(Boolean).join('; ');
        }

        const isDirectProperty = String(row.dataset.isDirectProperty || '0') === '1';
        if (isDirectProperty) {
            const nameField = row.querySelector('.omo-holon-create__direct-property-name');
            const formatField = row.querySelector('.omo-holon-create__direct-property-format');
            const listTypeField = row.querySelector('.omo-holon-create__direct-property-list-type');
            property.name = String(nameField && nameField.value ? nameField.value : row.dataset.propertyName || '').trim();
            property.shortname = String(row.dataset.shortname || '');
            property.formatId = Number(formatField && formatField.value ? formatField.value : row.dataset.formatId || 0);
            property.listItemType = String(listTypeField && listTypeField.value ? listTypeField.value : row.dataset.listItemType || 'text');
            property.listHolonTypeIds = Array.from(row.querySelectorAll('.omo-holon-create__direct-property-holon-type:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            property.position = Array.from(elements.properties.querySelectorAll('.omo-holon-create__property')).indexOf(row) + 1;
            property.isDirectProperty = true;
            property.isTemplateProperty = false;
            property.canEditDefinition = String(row.dataset.canEditDefinition || '0') === '1';
            property.canDelete = String(row.dataset.canDelete || '0') === '1';
        }

        if (isTemplateEditing()) {
            let listHolonTypeIds = [];
            try {
                listHolonTypeIds = JSON.parse(String(row.dataset.listHolonTypeIds || '[]'));
            } catch (error) {
                listHolonTypeIds = [];
            }

            const mandatoryField = row.querySelector('.omo-holon-create__property-mandatory');
            const lockedField = row.querySelector('.omo-holon-create__property-locked');
            const inheritedMandatory = String(row.dataset.inheritedMandatory || '0') === '1';
            const inheritedLocked = String(row.dataset.inheritedLocked || '0') === '1';
            const localMandatory = mandatoryField
                ? (mandatoryField.disabled && inheritedMandatory
                    ? String(row.dataset.localMandatory || '0') === '1'
                    : Boolean(mandatoryField.checked))
                : false;
            const localLocked = lockedField
                ? (lockedField.disabled && inheritedLocked
                    ? String(row.dataset.localLocked || '0') === '1'
                    : Boolean(lockedField.checked))
                : false;

            property.holonPropertyId = Number(row.dataset.holonPropertyId || 0);
            property.name = String(row.dataset.propertyName || '');
            property.shortname = String(row.dataset.shortname || '');
            property.formatId = Number(row.dataset.formatId || 0);
            property.listItemType = String(row.dataset.listItemType || 'text');
            property.listHolonTypeIds = Array.isArray(listHolonTypeIds) ? listHolonTypeIds.map(Number).filter(Boolean) : [];
            property.mandatory = localMandatory;
            property.locked = localLocked;
            property.inheritedMandatory = inheritedMandatory;
            property.inheritedLocked = inheritedLocked;
            property.effectiveMandatory = inheritedMandatory || localMandatory;
            property.effectiveLocked = inheritedLocked || localLocked;
            property.isInherited = String(row.dataset.isInherited || '0') === '1';
            property.isLocal = String(row.dataset.isLocal || '0') === '1';
        }

        return property;
    }).filter(function (property) {
        return Number(property.id || 0) > 0 || (property.isDirectProperty && String(property.name || '').trim() !== '');
    });
}

// Remplit formulaire courant
function fillFormFromState() {
    const editingHolon = getEditingHolon();

    if (editingHolon) {
        elements.name.value = String(editingHolon.name || '');
        if (elements.fullName) {
            elements.fullName.value = String(editingHolon.fullName || '');
        }
        syncTemplateSelection(Number(editingHolon.templateId || 0), editingHolon.properties || []);
        renderPermissions(editingHolon.permissionAssignments || {});
        if (isTemplateEditing()) {
            if (elements.visible) {
                elements.visible.checked = Boolean(editingHolon.visible);
            }
            if (elements.mandatory) {
                elements.mandatory.checked = Boolean(editingHolon.mandatory);
            }
            if (elements.link) {
                elements.link.checked = Boolean(editingHolon.link);
            }
        }
        return;
    }

    elements.name.value = '';
    if (elements.fullName) {
        elements.fullName.value = '';
    }
    elements.name.disabled = false;
    if (elements.visible) {
        elements.visible.checked = false;
    }
    if (elements.mandatory) {
        elements.mandatory.checked = false;
    }
    if (elements.link) {
        elements.link.checked = false;
    }
    syncTemplateSelection();
    renderPermissions({});
}

// Efface message statut
function clearStatus() {
    if (state.statusTimer) {
        window.clearTimeout(state.statusTimer);
        state.statusTimer = null;
    }

    elements.status.hidden = true;
    elements.status.className = 'omo-holon-create__status';
    elements.status.innerHTML = '';
}

// Affiche message statut
function showStatus(message, tone) {
    clearStatus();

    if (typeof window.commonNotify === 'function') {
        window.commonNotify(String(message || ''), tone === 'success' ? 'success' : 'error');
        return;
    }

    elements.status.hidden = false;
    elements.status.className = 'omo-holon-create__status is-' + tone;
    elements.status.innerHTML = '<div class="omo-holon-create__status-copy">' + escapeHtml(message) + '</div>';
    state.statusTimer = window.setTimeout(clearStatus, 12000);
}

// Ferme drawer création
function getCurrentDrawerRouteToken() {
    if (typeof parseUrl !== 'function') {
        return '';
    }

    const route = parseUrl();
    const rawHash = String(route && route.hash ? route.hash : '').trim();
    if (!rawHash) {
        return '';
    }

    return rawHash.split('|')[0] || '';
}

function isHashManagedHolonEditorDrawer() {
    return /^(holon-create-\d+|holon-edit-\d+)$/i.test(getCurrentDrawerRouteToken());
}

function isHashManagedCreateDrawer() {
    return /^holon-create-\d+$/i.test(getCurrentDrawerRouteToken());
}

function getExternalDrawerContext() {
    if (typeof window.omoGetExternalPanelDrawerContext !== 'function') {
        return null;
    }

    return window.omoGetExternalPanelDrawerContext(root);
}

function closeCreateDrawer() {
    const externalDrawerContext = getExternalDrawerContext();
    if (externalDrawerContext && typeof window.omoCloseExternalPanelDrawer === 'function') {
        window.omoCloseExternalPanelDrawer();
        return;
    }

    if (isHashManagedHolonEditorDrawer() && typeof window.omoSetDrawerHashState === 'function') {
        window.omoSetDrawerHashState({
            open: false
        });
        return;
    }

    if (typeof closeDrawer === 'function') {
        closeDrawer('drawer_holon_create');
    }
}

function refreshStructureViews(targetHolonId, options) {
    const cid = targetHolonId === null || targetHolonId === undefined || targetHolonId === ''
        ? null
        : Number(targetHolonId);
    const refreshOptions = options && typeof options === 'object'
        ? options
        : {};
    const detail = {
        cid: Number.isNaN(cid) ? null : cid,
        quickZoom: Boolean(refreshOptions.quickZoom)
    };

    if (typeof window.omoReloadStructureAndFocus === 'function') {
        return window.omoReloadStructureAndFocus(detail.cid, refreshOptions)
            .catch(function () {
                return null;
            });
    }

    window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
        detail: detail
    }));

    return Promise.resolve(null);
}

// Enregistre holon courant
function saveHolon(event) {
    event.preventDefault();

    if (elements.form && typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(elements.form)) {
        return;
    }

    clearStatus();

    const pendingMediaFlushes = [];
    if (mediaFields.icon && typeof mediaFields.icon.flushPending === 'function') {
        pendingMediaFlushes.push(mediaFields.icon.flushPending());
    }

    Promise.all(pendingMediaFlushes)
        .then(function () {
            const editingHolon = getEditingHolon() || {};
            const payload = {
                templateId: Number(elements.template.value || 0),
                name: String(elements.name.value || '').trim(),
                fullName: String(elements.fullName && elements.fullName.value ? elements.fullName.value : '').trim(),
                color: canEditHolonColor
                    ? (Boolean(elements.colorEnabled && elements.colorEnabled.checked)
                        ? String(elements.color && elements.color.value ? elements.color.value : '')
                        : '')
                    : String((getEditingHolon() || {}).color || ''),
                icon: mediaFields.icon
                    ? mediaFields.icon.getValue()
                    : String((getEditingHolon() || {}).icon || ''),
                adminMin: elements.adminMin
                    ? normalizeAdminBound(elements.adminMin.value, false)
                    : normalizeAdminBound(editingHolon.adminMin, false),
                adminMax: elements.adminMax
                    ? normalizeAdminBound(elements.adminMax.value, true)
                    : normalizeAdminBound(editingHolon.adminMax, true),
				adminMinOverride: elements.adminMinOverride
					? Boolean(elements.adminMinOverride.checked)
					: Boolean(editingHolon.adminMinOverride),
				adminMaxOverride: elements.adminMaxOverride
					? Boolean(elements.adminMaxOverride.checked)
					: Boolean(editingHolon.adminMaxOverride),
                permissions: readPermissions(),
                properties: readProperties()
            };

            if (isTemplateEditing()) {
                payload.visible = Boolean(elements.visible && elements.visible.checked);
                payload.mandatory = Boolean(elements.mandatory && elements.mandatory.checked);
                payload.link = Boolean(elements.link && elements.link.checked);
            }

            if (governanceCapture) {
                window.dispatchEvent(new CustomEvent('omo-holon-governance-capture', {
                    detail: {
                        payload: payload,
                        holonId: Number(state.data.holonId || 0),
                        contextHolonId: Number(state.data.contextHolonId || 0)
                    }
                }));
                return { governanceCapture: true };
            }

            let saveUrl = '/omo/api/holons/save.php?cid=' + Number(state.data.contextHolonId || 0);
            if (getMode() === 'edit' && Number(state.data.holonId || 0) > 0) {
                saveUrl += '&hid=' + Number(state.data.holonId || 0);
            }

            const formData = new FormData();
            formData.append('payload', JSON.stringify(payload));
            if (mediaFields.icon) {
                mediaFields.icon.appendToFormData(formData);
            }

            return fetch(saveUrl, {
                method: 'POST',
                body: formData
            });
        })
        .then(function (response) {
            if (response && response.governanceCapture) {
                return response;
            }
            return response.json().then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(function (result) {
            if (result && result.governanceCapture) {
                return;
            }
            if (!result.ok || !result.data || result.data.status !== 'ok') {
        throw new Error(result.data && result.data.message ? result.data.message : (getMode() === 'edit' ? "Impossible d’enregistrer l’élément." : "Impossible de créer l’élément."));
            }

            const hashManagedEditorDrawer = isHashManagedHolonEditorDrawer();
            const hashManagedCreateDrawer = getMode() !== 'edit' && isHashManagedCreateDrawer();
            const route = typeof parseUrl === 'function'
                ? parseUrl()
                : {
                    oid: Number(state.data.organizationId || 0),
                    cid: null,
                    hash: null
                };
            const targetHolonId = Number(result.data.holon.id || 0);
            const externalDrawerContext = getExternalDrawerContext();
            const externalStructureHost = externalDrawerContext
                && String(externalDrawerContext.hostRouteToken || '').trim().toLowerCase() === 'structure';
            const currentRouteCid = Number(route && route.cid ? route.cid : 0);
            const shouldNavigate = targetHolonId > 0
                && typeof navigate === 'function'
                && Number(route && route.oid ? route.oid : 0) > 0
                && currentRouteCid !== targetHolonId;

            if (getMode() === 'edit' && typeof loadContent === 'function' && !shouldNavigate) {
                let leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || state.data.organizationId || 0);

                if (targetHolonId > 0) {
                    leftUrl += '&cid=' + targetHolonId;
                }

                loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);

                refreshStructureViews(targetHolonId > 0 ? targetHolonId : null, {
                    quickZoom: externalStructureHost ? false : true
                });

                if (externalDrawerContext) {
                    closeCreateDrawer();
                    if (
                        typeof window.omoRefreshExternalPanelDrawerHost === 'function'
                        && !externalStructureHost
                        && !shouldNavigate
                    ) {
                        window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                    }
                } else if (hashManagedEditorDrawer && typeof window.omoSetDrawerHashState === 'function') {
                    window.omoSetDrawerHashState({
                        open: false,
                        replace: true
                    });
                } else {
                    closeCreateDrawer();
                }
            } else if (typeof navigate === 'function' && shouldNavigate) {
                if (externalStructureHost) {
                    closeCreateDrawer();
                    navigate(route.oid, targetHolonId, hashManagedCreateDrawer ? null : (route.hash || null));
                    return;
                }

                const parentHolonId = Number((result.data.holon && result.data.holon.parentId) || state.data.contextHolonId || 0);
                const refreshPromise = refreshStructureViews(parentHolonId > 0 ? parentHolonId : null, {
                    quickZoom: true
                });

                refreshPromise
                    .then(function () {
                        if (externalDrawerContext) {
                            closeCreateDrawer();
                        }

                        navigate(route.oid, targetHolonId, hashManagedCreateDrawer ? null : (route.hash || null));

                        if (
                            externalDrawerContext
                            && !externalStructureHost
                            && typeof window.omoRefreshExternalPanelDrawerHost === 'function'
                        ) {
                            window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                        }
                    });
            } else if (typeof loadContent === 'function') {
                let leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || state.data.organizationId || 0);

                if (targetHolonId > 0) {
                    leftUrl += '&cid=' + targetHolonId;
                }

                loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);

                refreshStructureViews(targetHolonId > 0 ? targetHolonId : null, {
                    quickZoom: externalStructureHost ? false : true
                });

                if (externalDrawerContext) {
                    closeCreateDrawer();
                    if (!externalStructureHost && typeof window.omoRefreshExternalPanelDrawerHost === 'function') {
                        window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                    }
                }
            }

            if (getMode() === 'edit') {
                return;
            }

            if (externalDrawerContext) {
                return;
            }

            if (!hashManagedCreateDrawer) {
                closeCreateDrawer();
            } else if (typeof navigate !== 'function' && typeof window.omoSetDrawerHashState === 'function') {
                window.omoSetDrawerHashState({
                    open: false,
                    replace: true
                });
            }
        })
        .catch(function (error) {
        showStatus(error && error.message ? error.message : (getMode() === 'edit' ? "Impossible d’enregistrer l’élément." : "Impossible de créer l’élément."), 'error');
        })
        .finally(function () {
            if (elements.form && typeof window.omoEndPendingAction === 'function') {
                window.omoEndPendingAction(elements.form);
            }
        });
}

Promise.all([
    waitForGlobalLibrary('omoSizedImageField', 5000),
    waitForGlobalLibrary('omoSimpleHtmlField', 5000)
]).finally(function () {
    fillFormFromState();
});

elements.template.addEventListener('change', function () {
    const permissionAssignments = readPermissions();
    renderEditorMeta(getCurrentTemplate(), readProperties());
    renderPermissions(permissionAssignments);
});

if (elements.addProperty) {
    elements.addProperty.addEventListener('click', function () {
        if (elements.addProperty.disabled || isTemplateEditing()) {
            return;
        }
        const defaultFormat = (state.data.formats || []).length
            ? state.data.formats[0]
            : { id: 1, name: 'Texte libre' };
        const emptyNote = elements.properties.querySelector('.omo-holon-create__empty-note');
        if (emptyNote) {
            emptyNote.remove();
        }
        const property = {
            id: 0,
            name: '',
            shortname: '',
            formatId: Number(defaultFormat.id || 1),
            formatName: String(defaultFormat.name || ''),
            listItemType: 'text',
            listHolonTypeIds: [],
            value: '',
            isDirectProperty: true,
            isTemplateProperty: false,
            canEditDefinition: true,
            canDelete: true,
            canEditValue: true
        };
        elements.properties.appendChild(createPropertyRow(property, elements.properties.querySelectorAll('.omo-holon-create__property').length));
        const nameField = elements.properties.lastElementChild ? elements.properties.lastElementChild.querySelector('.omo-holon-create__direct-property-name') : null;
        if (nameField) {
            nameField.focus();
        }
    });
}

if (elements.colorEnabled) {
    elements.colorEnabled.addEventListener('change', function () {
        syncColorField();
    });
}

if (elements.adminMinOverride) {
	elements.adminMinOverride.addEventListener('change', function () {
		if (elements.adminMin) {
			elements.adminMin.disabled = !elements.adminMinOverride.checked;
		}
	});
}

if (elements.adminMaxOverride) {
	elements.adminMaxOverride.addEventListener('change', function () {
		if (elements.adminMax) {
			elements.adminMax.disabled = !elements.adminMaxOverride.checked;
		}
	});
}

root.addEventListener('click', function (event) {
    const permissionToggle = event.target.closest('#omo-holon-create-permissions-toggle');
    if (permissionToggle) {
        const isExpanded = !(elements.permissions && elements.permissions.hidden === false);
        setPermissionEditorExpanded(isExpanded);
    }
});

elements.form.addEventListener('submit', saveHolon);

elements.cancel.addEventListener('click', function () {
    closeCreateDrawer();
});

root.addEventListener('change', function (event) {
    if (event.target.matches('.omo-holon-create__direct-property-format, .omo-holon-create__direct-property-list-type')) {
        const propertyRow = event.target.closest('.omo-holon-create__property');
        if (!propertyRow) {
            return;
        }
        const propertyDraft = getDirectPropertyDraft(propertyRow);
        if (event.target.matches('.omo-holon-create__direct-property-format')) {
            propertyDraft.value = '';
        }
        const propertyRows = Array.from(elements.properties.querySelectorAll('.omo-holon-create__property'));
        const index = propertyRows.indexOf(propertyRow);
        propertyRow.replaceWith(createPropertyRow(propertyDraft, index >= 0 ? index : 0));
        return;
    }
    if (!event.target.matches('[data-authority-deletion-choice]')) {
        const authorityField = event.target.closest('.omo-holon-create__authority-parent, .omo-holon-create__authority-delegation');
        const authorityRow = authorityField && authorityField.closest('[data-authority-entry]');
        if (!authorityRow || Number(authorityRow.getAttribute('data-authority-id') || 0) > 0) {
            return;
        }
        authorityRow.outerHTML = renderAuthorityListRow({
            parentId: Number((authorityRow.querySelector('.omo-holon-create__authority-parent') || {}).value || 0),
            delegationMode: String((authorityRow.querySelector('.omo-holon-create__authority-delegation') || {}).value || 'partial'),
            label: String((authorityRow.querySelector('.omo-holon-create__authority-label') || {}).value || ''),
            description: String((authorityRow.querySelector('.omo-holon-create__authority-description') || {}).value || '')
        });
        return;
    }
    const authorityRow = event.target.closest('[data-authority-entry]');
    if (authorityRow) {
        updateAuthorityDeletionCounts(authorityRow);
    }
});

root.addEventListener('input', function (event) {
    if (!event.target.matches('.omo-holon-create__direct-property-name')) {
        return;
    }
    const propertyRow = event.target.closest('.omo-holon-create__property');
    const propertyTitle = propertyRow ? propertyRow.querySelector('.omo-holon-create__property-name') : null;
    if (propertyTitle) {
        propertyTitle.textContent = String(event.target.value || '').trim() || 'Nouvelle propriete';
    }
});

root.addEventListener('click', function (event) {
    const projectPickerOpen = event.target.closest('[data-project-picker-open]');
    if (projectPickerOpen) {
        openProjectPicker(projectPickerOpen.closest('[data-project-picker]'));
        return;
    }

    const projectRemove = event.target.closest('[data-project-remove]');
    if (projectRemove) {
        const projectPicker = projectRemove.closest('[data-project-picker]');
        const projectId = Number(projectRemove.getAttribute('data-project-remove') || 0);
        if (projectPicker && projectId > 0) {
            projectPicker.dataset.selectedIds = readProjectPickerSelectedIds(projectPicker).filter(function (selectedId) {
                return selectedId !== projectId;
            }).join(',');
            syncProjectPickerSelectedList(projectPicker);
        }
        return;
    }

    const directPropertyRemoveButton = event.target.closest('[data-direct-property-remove]');
    if (directPropertyRemoveButton) {
        if (directPropertyRemoveButton.disabled) {
            return;
        }
        const propertyRow = directPropertyRemoveButton.closest('.omo-holon-create__property');
        if (propertyRow) {
            propertyRow.remove();
        }
        if (!elements.properties.querySelector('.omo-holon-create__property')) {
            elements.properties.innerHTML = '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Ce modele ne definit aucune propriete.</div>';
        }
        return;
    }

    const authorityAddButton = event.target.closest('[data-authority-add]');
    if (authorityAddButton) {
        const list = authorityAddButton.closest('.omo-holon-create__authority-list');
        const items = list ? list.querySelector('.omo-holon-create__authority-items') : null;
        if (items) {
            items.insertAdjacentHTML('beforeend', renderAuthorityListRow({}));
            const labelField = items.lastElementChild ? items.lastElementChild.querySelector('.omo-holon-create__authority-label') : null;
            if (labelField) {
                labelField.focus();
            }
        }
        return;
    }

    const authorityRemoveButton = event.target.closest('[data-authority-remove]');
    if (authorityRemoveButton) {
        const authorityRow = authorityRemoveButton.closest('[data-authority-entry]');
        if (authorityRow) {
            authorityRow.remove();
        }
        return;
    }

    const authorityEditButton = event.target.closest('[data-authority-edit]');
    if (authorityEditButton) {
        const authorityRow = authorityEditButton.closest('[data-authority-entry]');
        const authorityId = Number(authorityRow && authorityRow.getAttribute('data-authority-id') || 0);
        if (authorityRow && authorityId > 0) {
            authorityRow.outerHTML = renderAuthorityListRow({ id: authorityId, editing: true });
            const labelField = root.querySelector('[data-authority-entry][data-authority-id="' + authorityId + '"] .omo-holon-create__authority-label');
            if (labelField) {
                labelField.focus();
            }
        }
        return;
    }

    const authorityDeleteButton = event.target.closest('button[data-authority-delete]');
    if (authorityDeleteButton) {
        const authorityRow = authorityDeleteButton.closest('[data-authority-entry]');
        if (authorityRow) {
            const isPendingDeletion = authorityRow.getAttribute('data-authority-delete') === '1';
            if (isPendingDeletion) {
                authorityRow.removeAttribute('data-authority-delete');
                authorityRow.classList.remove('is-pending-delete');
                authorityDeleteButton.setAttribute('aria-label', 'Supprimer l autorite');
                authorityDeleteButton.title = 'Supprimer l autorite';
            } else {
                authorityRow.setAttribute('data-authority-delete', '1');
                authorityRow.classList.add('is-pending-delete');
                authorityDeleteButton.setAttribute('aria-label', 'Annuler la suppression');
                authorityDeleteButton.title = 'Annuler la suppression';
                updateAuthorityDeletionCounts(authorityRow);
            }
        }
        return;
    }

    const addButton = event.target.closest('[data-list-add]');
    if (addButton) {
        const list = addButton.closest('.omo-holon-create__list');
        const items = list ? list.querySelector('.omo-holon-create__list-items') : null;
        if (!list || !items) {
            return;
        }

        items.insertAdjacentHTML('beforeend', renderSimpleListRow(list.getAttribute('data-list-item-type') || 'text', ''));
        return;
    }

    const moveButton = event.target.closest('[data-list-move]');
    if (moveButton) {
        const direction = Number(moveButton.getAttribute('data-list-move') || 0);
        const row = moveButton.closest('.omo-holon-create__list-row');
        const items = row && row.parentNode ? row.parentNode : null;
        if (!row || !items || !direction) {
            return;
        }

        if (direction < 0) {
            const previousRow = row.previousElementSibling;
            if (previousRow) {
                items.insertBefore(row, previousRow);
            }
        } else {
            const nextRow = row.nextElementSibling;
            if (nextRow) {
                items.insertBefore(nextRow, row);
            }
        }

        const input = row.querySelector('.omo-holon-create__property-value-item');
        if (input) {
            input.focus();
        }
        return;
    }

    const removeButton = event.target.closest('[data-list-remove]');
    if (removeButton) {
        const row = removeButton.closest('.omo-holon-create__list-row');
        const list = removeButton.closest('.omo-holon-create__list');
        const items = list ? list.querySelector('.omo-holon-create__list-items') : null;
        if (!row || !list || !items) {
            return;
        }

        row.remove();
        if (!items.querySelector('.omo-holon-create__list-row')) {
            items.insertAdjacentHTML('beforeend', renderSimpleListRow(list.getAttribute('data-list-item-type') || 'text', ''));
        }
    }
});
})();
</script>
<?php endif; ?>

<link rel="stylesheet" href="/omo/api/holons/editor.css?v=20260923-permission-align">
