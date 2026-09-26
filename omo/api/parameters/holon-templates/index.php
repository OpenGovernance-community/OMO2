<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/translation.php';
require_once __DIR__ . '/access.php';

use dbObject\Organization;

function omoHolonTemplateEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$targetHolonId = (int)($_GET['hid'] ?? 0);
$selectedTemplateId = (int)($_GET['tid'] ?? 0);
$isCompactMode = !empty($_GET['compact']);
$templateScope = 'descendants';
$organization = new Organization();
$editorData = null;
$errorMessage = '';
$isHolonDefinitionMode = false;
$adminLabel = 'Admin';
$adminLabelLower = 'admin';

if ($organizationId <= 0) {
    $errorMessage = omoHolonTemplateT('parameters.holon_templates.error.no_organization');
} elseif (!$organization->load($organizationId)) {
    $errorMessage = omoHolonTemplateT('parameters.holon_templates.error.organization_not_found');
} elseif (empty(($discoveryModeAccess = omoHolonTemplateDiscoveryModeAccess($organization, $targetHolonId))['status'])) {
    $errorMessage = (string)($discoveryModeAccess['message'] ?? omoHolonTemplateT('parameters.holon_templates.error.discovery_mode'));
} elseif (empty(($adminModeAccess = omoHolonTemplateAdminModeAccess($organizationId))['status'])) {
    $errorMessage = (string)($adminModeAccess['message'] ?? omoHolonTemplateT('parameters.holon_templates.error.admin_required'));
} elseif ($organization->getEnabledStructuralRootHolon() === null) {
    $errorMessage = omoHolonTemplateT('parameters.holon_templates.error.structure_required');
} else {
	$organizationLexicon = $organization->getLexicon();
	$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
	$adminLabelLower = function_exists('mb_strtolower')
		? mb_strtolower($adminLabel, 'UTF-8')
		: strtolower($adminLabel);
    $rootHolon = $organization->getEnabledStructuralRootHolon();
    if ($targetHolonId > 0) {
        $editorData = $organization->getHolonDefinitionEditorData($targetHolonId);
    }

    if ($editorData === null) {
        $editorData = $organization->getHolonTemplateEditorData((int)$rootHolon->getId(), $templateScope);
    }

    $isHolonDefinitionMode = (($editorData['editorMode'] ?? 'template') === 'holon-definition');
    if ($isHolonDefinitionMode) {
        $templateScope = 'contextual';
        $selectedTemplateId = (int)($editorData['targetHolonId'] ?? $targetHolonId);
    } elseif ($selectedTemplateId <= 0 && $targetHolonId > 0 && $rootHolon) {
        $targetTemplateHolon = new \dbObject\Holon();
        if (
            $targetTemplateHolon->load($targetHolonId)
            && $organization->containsHolon($targetTemplateHolon)
            && $targetTemplateHolon->isTemplateNode((int)$rootHolon->getId())
        ) {
            $selectedTemplateId = (int)$targetTemplateHolon->getId();
        }
    }
}

$showTemplateWelcome = !$isHolonDefinitionMode && $selectedTemplateId <= 0;

$omoHolonTemplateTexts = [
    'permissionSelf' => omoHolonTemplateT('parameters.holon_templates.permission.self'),
    'permissionMembers' => omoHolonTemplateT('parameters.holon_templates.permission.members'),
    'permissionAdmins' => omoHolonTemplateT('parameters.holon_templates.permission.admins', ['adminLabel' => $adminLabel]),
    'permissionCollective' => omoHolonTemplateT('parameters.holon_templates.permission.collective'),
    'permissionChildren' => omoHolonTemplateT('parameters.holon_templates.permission.children'),
    'permissionDirectChildren' => omoHolonTemplateT('parameters.holon_templates.permission.direct_children'),
    'permissionParentCircleElements' => omoHolonTemplateT('parameters.holon_templates.permission.parent_circle_elements'),
    'permissionOrganizationRoot' => omoHolonTemplateT('parameters.holon_templates.permission.organization_root'),
    'permissionOrganization' => omoHolonTemplateT('parameters.holon_templates.permission.organization'),
    'permissionNoneAvailable' => omoHolonTemplateT('parameters.holon_templates.permission.none_available'),
    'permissionAddRange' => omoHolonTemplateT('parameters.holon_templates.permission.add_range'),
    'permissionNoneSelected' => omoHolonTemplateT('parameters.holon_templates.permission.none_selected'),
    'permissionRemoveRange' => omoHolonTemplateT('parameters.holon_templates.permission.remove_range'),
    'confirmInheritanceChange' => omoHolonTemplateT('parameters.holon_templates.confirm.inheritance_change'),
    'adminMaxPlaceholder' => omoHolonTemplateT('parameters.holon_templates.field.admin_max_placeholder'),
    'adminBoundInheritedPlaceholder' => omoHolonTemplateT('parameters.holon_templates.field.admin_bound_inherited_placeholder'),
    'summaryModelOne' => omoHolonTemplateT('parameters.holon_templates.summary.model_one'),
    'summaryModelOther' => omoHolonTemplateT('parameters.holon_templates.summary.model_other'),
    'summaryPropertyOne' => omoHolonTemplateT('parameters.holon_templates.summary.property_one'),
    'summaryPropertyOther' => omoHolonTemplateT('parameters.holon_templates.summary.property_other'),
    'summarySubmodelOne' => omoHolonTemplateT('parameters.holon_templates.summary.submodel_one'),
    'summarySubmodelOther' => omoHolonTemplateT('parameters.holon_templates.summary.submodel_other'),
    'treeEmpty' => omoHolonTemplateT('parameters.holon_templates.tree.empty'),
    'treeRoot' => omoHolonTemplateT('parameters.holon_templates.tree.root'),
    'propertyName' => omoHolonTemplateT('parameters.holon_templates.property.name'),
    'propertyFormat' => omoHolonTemplateT('parameters.holon_templates.property.format'),
    'propertyValueDefault' => omoHolonTemplateT('parameters.holon_templates.property.value_default'),
    'propertyValueLocalAdded' => omoHolonTemplateT('parameters.holon_templates.property.value_local_added'),
    'propertyValueInherited' => omoHolonTemplateT('parameters.holon_templates.property.value_inherited'),
    'propertyOriginInherited' => omoHolonTemplateT('parameters.holon_templates.property.origin_inherited'),
    'propertyOriginLocal' => omoHolonTemplateT('parameters.holon_templates.property.origin_local'),
    'propertyToggleMandatory' => omoHolonTemplateT('parameters.holon_templates.property.toggle_mandatory'),
    'propertyToggleLocked' => omoHolonTemplateT('parameters.holon_templates.property.toggle_locked'),
    'propertyMoveUp' => omoHolonTemplateT('parameters.holon_templates.property.action.move_up'),
    'propertyMoveDown' => omoHolonTemplateT('parameters.holon_templates.property.action.move_down'),
    'propertyRemove' => omoHolonTemplateT('parameters.holon_templates.property.action.remove'),
    'propertyExclude' => omoHolonTemplateT('parameters.holon_templates.property.action.exclude'),
    'propertyPlaceholderReason' => omoHolonTemplateT('parameters.holon_templates.property.placeholder.reason'),
    'propertyPlaceholderGeneric' => omoHolonTemplateT('parameters.holon_templates.property.placeholder.generic'),
    'propertyPlaceholderTitle' => omoHolonTemplateT('parameters.holon_templates.property.placeholder.title'),
    'propertyPlaceholderDescription' => omoHolonTemplateT('parameters.holon_templates.property.placeholder.description'),
    'propertyPlaceholderNumber' => omoHolonTemplateT('parameters.holon_templates.property.placeholder.number'),
    'propertyPlaceholderEmpty' => omoHolonTemplateT('parameters.holon_templates.property.placeholder.empty'),
    'propertyListItemType' => omoHolonTemplateT('parameters.holon_templates.property.list_item_type'),
    'propertyAllowedHolonTypes' => omoHolonTemplateT('parameters.holon_templates.property.allowed_holon_types'),
    'propertyProjectScope' => omoHolonTemplateT('parameters.holon_templates.property.project_scope'),
    'propertyProjectScopeLocal' => omoHolonTemplateT('parameters.holon_templates.property.project_scope.local'),
    'propertyProjectScopeChildren' => omoHolonTemplateT('parameters.holon_templates.property.project_scope.children'),
    'propertyProjectScopeDescendants' => omoHolonTemplateT('parameters.holon_templates.property.project_scope.descendants'),
    'propertyProjectScopeGlobal' => omoHolonTemplateT('parameters.holon_templates.property.project_scope.global'),
    'propertyProjectSearch' => omoHolonTemplateT('parameters.holon_templates.property.project_search'),
    'propertyProjectEmpty' => omoHolonTemplateT('parameters.holon_templates.property.project_empty'),
    'propertyNoTemplateForTypes' => omoHolonTemplateT('parameters.holon_templates.property.no_template_for_types'),
    'propertyEmpty' => omoHolonTemplateT('parameters.holon_templates.property.empty'),
    'propertyDetailFallback' => omoHolonTemplateT('parameters.holon_templates.property.detail_fallback'),
    'propertyHelpDefault' => omoHolonTemplateT('parameters.holon_templates.property.help.default'),
    'propertyHelpNumber' => omoHolonTemplateT('parameters.holon_templates.property.help.number'),
    'propertyHelpDate' => omoHolonTemplateT('parameters.holon_templates.property.help.date'),
    'propertyHelpHtml' => omoHolonTemplateT('parameters.holon_templates.property.help.html'),
    'propertyHelpListText' => omoHolonTemplateT('parameters.holon_templates.property.help.list_text'),
    'propertyHelpListNumber' => omoHolonTemplateT('parameters.holon_templates.property.help.list_number'),
    'propertyHelpListDate' => omoHolonTemplateT('parameters.holon_templates.property.help.list_date'),
    'propertyHelpListDetail' => omoHolonTemplateT('parameters.holon_templates.property.help.list_detail'),
    'propertyHelpListHolon' => omoHolonTemplateT('parameters.holon_templates.property.help.list_holon'),
    'propertyHelpListProject' => omoHolonTemplateT('parameters.holon_templates.property.help.list_project'),
    'propertyHelpListAuthority' => omoHolonTemplateT('parameters.holon_templates.property.help.list_authority'),
    'statusCloseMessage' => omoHolonTemplateT('parameters.holon_templates.status.close_message'),
    'badgeActiveInheritance' => omoHolonTemplateT('parameters.holon_templates.badge.active_inheritance'),
    'selectionHintDefinition' => omoHolonTemplateT('parameters.holon_templates.form.selection_hint_definition'),
    'selectionHintExisting' => omoHolonTemplateT('parameters.holon_templates.form.selection_hint_existing'),
    'selectionHintNew' => omoHolonTemplateT('parameters.holon_templates.form.selection_hint_new'),
    'formOrganization' => omoHolonTemplateT('parameters.holon_templates.form.organization'),
    'formModelTitle' => omoHolonTemplateT('parameters.holon_templates.form.model_title'),
    'formNewModel' => omoHolonTemplateT('parameters.holon_templates.form.new_model'),
    'formDefinitionDescription' => omoHolonTemplateT('parameters.holon_templates.form.organization_description'),
    'formExistingModelDescription' => omoHolonTemplateT('parameters.holon_templates.form.existing_model_description'),
    'formNewModelDescriptionShort' => omoHolonTemplateT('parameters.holon_templates.form.new_model_description_short'),
    'formWelcomeTitle' => omoHolonTemplateT('parameters.holon_templates.form.welcome_title'),
    'formWelcomeDescription' => omoHolonTemplateT('parameters.holon_templates.form.welcome_description'),
    'formWelcomeAction' => omoHolonTemplateT('parameters.holon_templates.form.welcome_action'),
    'saveErrorOrganization' => omoHolonTemplateT('parameters.holon_templates.error.save_organization'),
    'saveErrorModel' => omoHolonTemplateT('parameters.holon_templates.error.save_model'),
    'savedModel' => omoHolonTemplateT('parameters.holon_templates.status.saved_model'),
    'deleteModel' => omoHolonTemplateT('parameters.holon_templates.action.delete_model'),
    'confirmDeleteModel' => omoHolonTemplateT('parameters.holon_templates.confirm.delete_model', ['templateName' => '{templateName}']),
    'deleteErrorModel' => omoHolonTemplateT('parameters.holon_templates.error.delete_model'),
    'deletedModel' => omoHolonTemplateT('parameters.holon_templates.status.deleted_model'),
];
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260807-project-picker-search">
<div
    class="omo-template-editor omo-panel-view"
    id="omo-holon-template-page"
    data-omo-template-scope="<?= omoHolonTemplateEscape($templateScope) ?>"
>
    <?php if ($isHolonDefinitionMode): ?>
    <div class="omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.header.organization_title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="omo-panel-view__description"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.header.organization_description'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="omo-panel-view__body">
        <?php if ($errorMessage !== ''): ?>
            <div class="omo-empty-state"><?= omoHolonTemplateEscape($errorMessage) ?></div>
        <?php else: ?>
            <div class="omo-template-editor__layout<?= $isCompactMode ? ' omo-template-editor__layout--compact' : '' ?><?= $isHolonDefinitionMode ? ' omo-template-editor__layout--holon-definition' : '' ?>" id="omo-holon-template-editor">
                <aside class="omo-template-sidebar generic-section generic-section--flush">
                    <div class="omo-template-sidebar__top">
                        <div class="omo-template-sidebar__stats" id="omo-template-summary"></div>

                        <div class="omo-template-sidebar__actions"<?= $isHolonDefinitionMode ? ' hidden' : '' ?>>
                            <button type="button" class="omo-button omo-button--secondary" data-template-action="new-root"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.new_model'), ENT_QUOTES, 'UTF-8') ?></button>
                            <button type="button" class="omo-button omo-button--ghost" data-template-action="new-child" disabled><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.new_submodel'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </div>

                    <div class="omo-template-tree-wrap">
                        <div class="omo-template-tree-wrap__title generic-title generic-title--subsection"><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.tree.current_holon') : omoHolonTemplateT('parameters.holon_templates.tree.models'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="omo-template-tree" id="omo-template-tree"></div>
                    </div>
                </aside>

                <div
                    class="resizer omo-template-editor__resize-handle"
                    role="separator"
                    aria-orientation="vertical"
                    aria-label="<?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.resize_columns'), ENT_QUOTES, 'UTF-8') ?>"
                    tabindex="0"
                    data-omo-template-resize-handle
                ></div>

                <section class="omo-template-form-panel generic-section generic-section--stack generic-section--roomy">
                    <div class="omo-template-editor__status" id="omo-template-status" hidden></div>

                    <div class="omo-template-welcome" id="omo-template-welcome"<?= $showTemplateWelcome ? '' : ' hidden' ?>>
                        <div class="generic-hero-panel accent omo-template-welcome__content">
                            <h3 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.welcome_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.welcome_description'), ENT_QUOTES, 'UTF-8') ?></p>
                            <button type="button" class="omo-button omo-button--primary" data-template-action="new-root"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.welcome_action'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </div>

                    <div id="omo-template-form-content"<?= $showTemplateWelcome ? ' hidden' : '' ?>>
                        <div class="omo-template-form-panel__header">
                            <div>
                                <div class="omo-template-editor__eyebrow"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.eyebrow'), ENT_QUOTES, 'UTF-8') ?></div>
                                <h3 class="omo-template-form-panel__title generic-title generic-title--big" id="omo-template-form-title"><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.form.organization') : omoHolonTemplateT('parameters.holon_templates.form.new_model'), ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="omo-template-form-panel__description generic-description" id="omo-template-form-description">
                                    <?= $isHolonDefinitionMode
                                        ? htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.organization_description'), ENT_QUOTES, 'UTF-8')
                                        : htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.new_model_description'), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <div class="omo-template-form-panel__badges" id="omo-template-form-badges"></div>
                        </div>

                        <form id="omo-template-form" class="omo-template-form generic-form-stack">
                        <section class="omo-template-section generic-section generic-section--stack generic-section--roomy">
                            <div class="omo-template-section__title generic-title generic-title--subsection"><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.section.holon') : omoHolonTemplateT('parameters.holon_templates.section.structure'), ENT_QUOTES, 'UTF-8') ?></div>

                            <div class="omo-template-form__grid">
                                <label class="omo-field<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>" id="omo-template-type-field">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.base_type'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <select name="typeId" id="omo-template-type" required></select>
                                </label>

                                <label class="omo-field<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.inherits_from'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <select name="parentId" id="omo-template-parent"></select>
                                </label>

                                <label class="omo-field<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.associated_holon'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <select name="definitionHolonId" id="omo-template-definition-holon"></select>
                                </label>

                                <label class="omo-field omo-field--full">
                                    <span><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.field.name') : omoHolonTemplateT('parameters.holon_templates.field.model_name'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <input type="text" name="name" id="omo-template-name" maxlength="255" required>
                                </label>

                                <div class="omo-template-flags omo-field--full<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <label class="omo-template-flags__option generic-soft-panel generic-stack generic-stack--compact">
                                        <input type="checkbox" id="omo-template-mandatory">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.mandatory'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.mandatory_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option generic-soft-panel generic-stack generic-stack--compact">
                                        <input type="checkbox" id="omo-template-locked-name">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.locked_name'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.locked_name_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option generic-soft-panel generic-stack generic-stack--compact">
                                        <input type="checkbox" id="omo-template-unique">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.unique'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.unique_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option generic-soft-panel generic-stack generic-stack--compact">
                                        <input type="checkbox" id="omo-template-link">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.link'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.link_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option generic-soft-panel generic-stack generic-stack--compact" data-omo-template-role-flag hidden>
                                        <input type="checkbox" id="omo-template-admin-parent">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.admin_parent', ['adminLabel' => $adminLabel]), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.admin_parent_help', ['adminLabel' => $adminLabelLower]), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                </div>

                                <div class="omo-template-admin-bounds generic-soft-panel generic-stack generic-stack--compact omo-field--full<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <div class="omo-template-admin-bounds__title generic-title generic-title--compact"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.admin_bounds', ['adminLabel' => $adminLabel]), ENT_QUOTES, 'UTF-8') ?></div>
                                    <p class="omo-template-admin-bounds__description generic-description generic-description--compact"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.admin_bounds_description', ['adminLabel' => $adminLabelLower]), ENT_QUOTES, 'UTF-8') ?></p>
                                    <div class="omo-template-admin-bounds__fields">
                                        <div class="omo-field">
                                            <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.admin_min', ['adminLabel' => $adminLabelLower]), ENT_QUOTES, 'UTF-8') ?></span>
                                            <input type="number" id="omo-template-admin-min" min="0" step="1" placeholder="0">
                                            <label class="omo-template-admin-bounds__lock">
                                                <input type="checkbox" id="omo-template-locked-admin-min">
                                                <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.lock_admin_min'), ENT_QUOTES, 'UTF-8') ?></span>
                                            </label>
                                        </div>
                                        <div class="omo-field">
                                            <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.admin_max', ['adminLabel' => $adminLabelLower]), ENT_QUOTES, 'UTF-8') ?></span>
                                            <input type="number" id="omo-template-admin-max" min="0" step="1" placeholder="<?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.admin_max_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                                            <label class="omo-template-admin-bounds__lock">
                                                <input type="checkbox" id="omo-template-locked-admin-max">
                                                <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.lock_admin_max'), ENT_QUOTES, 'UTF-8') ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </section>

                        <section class="omo-template-section generic-section generic-section--stack generic-section--roomy">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title generic-title generic-title--subsection"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.properties'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <p class="omo-template-section__description generic-description">
                                        <?= $isHolonDefinitionMode
                                            ? htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.properties_description_organization'), ENT_QUOTES, 'UTF-8')
                                            : htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.properties_description_model'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <button type="button" class="omo-button omo-button--secondary" id="omo-template-add-property"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.add_property'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>

                            <div class="omo-template-properties" id="omo-template-properties"></div>
                        </section>

                        <section class="omo-template-section generic-section generic-section--stack generic-section--roomy omo-template-section--permissions">
                            <div class="omo-template-permissions__sticky-navigation">
                                <div class="omo-template-section__head">
                                    <div>
                                        <div class="omo-template-section__title generic-title generic-title--subsection"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.permissions'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <p class="omo-template-section__description generic-description">
                                            <?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.permissions_description'), ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="omo-template-permissions" id="omo-template-permissions"></div>
                        </section>

                        <section class="omo-template-section generic-section generic-section--stack generic-section--roomy">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title generic-title generic-title--subsection"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.appearance'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <p class="omo-template-section__description generic-description">
                                        <?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.appearance_description'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </div>

                            <div class="omo-template-form__grid">
                                <div class="omo-field omo-color-field omo-color-field--compact" id="omo-template-color-field">
                                    <div class="omo-color-field__head">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.color'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <label class="omo-color-field__toggle">
                                            <input type="checkbox" id="omo-template-color-enabled" aria-label="<?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.override'), ENT_QUOTES, 'UTF-8') ?>">
                                            <span id="omo-template-color-override-label"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.override'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <input type="color" name="color" id="omo-template-color" value="#f59e0b" aria-label="<?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.color'), ENT_QUOTES, 'UTF-8') ?>" hidden>
                                        </label>
                                    </div>
                                </div>

                                <div class="omo-field omo-color-field omo-color-field--compact<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>" id="omo-template-unassigned-color-field">
                                    <div class="omo-color-field__head">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.unassigned_color'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <label class="omo-color-field__toggle">
                                            <input type="checkbox" id="omo-template-unassigned-color-enabled" aria-label="<?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.override'), ENT_QUOTES, 'UTF-8') ?>">
                                            <span id="omo-template-unassigned-color-override-label"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.override'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <input type="color" name="unassignedColor" id="omo-template-unassigned-color" value="#94a3b8" aria-label="<?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.unassigned_color'), ENT_QUOTES, 'UTF-8') ?>" hidden>
                                        </label>
                                    </div>
                                </div>

                                <div class="omo-field omo-field--full<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.shared_media'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="omo-template-media-grid">
                                        <div class="omo-template-media-card generic-section generic-section--stack generic-section--roomy">
                                            <div class="omo-template-media-card__head">
                                                <div class="omo-template-media-card__title generic-title generic-title--compact"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.icon'), ENT_QUOTES, 'UTF-8') ?></div>
                                                <label class="omo-template-media-card__lock">
                                                    <input type="checkbox" id="omo-template-locked-icon">
                                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.locked_icon'), ENT_QUOTES, 'UTF-8') ?></span>
                                                </label>
                                            </div>
                                            <div id="omo-template-icon-field"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="omo-template-form__footer">
                            <div class="omo-template-form__hint generic-help-text generic-help-text--regular" id="omo-template-selection-hint"></div>
                            <?php if (!$isHolonDefinitionMode): ?>
                                <button type="button" class="omo-button omo-button--danger omo-template-delete-button" id="omo-template-delete" hidden>
                                    <img src="/img/icon_delete.png" class="omo-template-delete-button__icon" alt="" aria-hidden="true">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.delete_model'), ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            <?php endif; ?>
                            <?php if ($isCompactMode): ?>
                                <button type="button" class="omo-button omo-button--ghost" id="omo-template-cancel"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.close'), ENT_QUOTES, 'UTF-8') ?></button>
                            <?php endif; ?>
                            <button type="submit" class="omo-button omo-button--primary"><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.action.save_organization') : omoHolonTemplateT('parameters.holon_templates.action.save_model'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                        </form>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/parameters/holon-templates/templates.css') ?>">

<?php if ($editorData !== null): ?>
<script src="/omo/assets/js/sized-image-field.js"></script>
<script src="/omo/assets/js/simple-html-field.js?v=20260904-highlight-clear"></script>
<script src="/common/assets/multiline-list-paste.js"></script>
<script src="/common/assets/property-list-conversion.js"></script>
<link rel="stylesheet" href="/common/permissions/editor.css?v=20260923-permission-align">
<script src="/common/permissions/editor.js?v=20260925-extended-authorities-label"></script>
<?= commonPageScriptTags('/omo/api/parameters/holon-templates/templates.js', [
    'omoHolonTemplateTexts' => $omoHolonTemplateTexts,
    'data' => $editorData,
    'selectedId' => (int)$selectedTemplateId,
    'compactMode' => ($isCompactMode),
]) ?>
<?php endif; ?>
