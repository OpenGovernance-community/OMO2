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
<script src="/common/assets/property-list-conversion.js"></script>
<link rel="stylesheet" href="/common/permissions/editor.css?v=20260923-permission-align">
<script src="/common/permissions/editor.js?v=20260925-extended-authorities-label"></script>
<?= commonPageScriptTags('/omo/api/holons/editor.js', [
    'data' => $editorData,
    'adminLexiconLabel' => $adminLabel,
    'directPermissionLabel' => $directPermissionLabel,
    'canEditHolonColor' => ($canEditHolonColor),
    'governanceCapture' => ($governanceCapture),
    'projectPickerTexts' => [
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
],
    'organizationId' => $organizationId,
]) ?>
<?php endif; ?>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/holons/editor.css') ?>">
