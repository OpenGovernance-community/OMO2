<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__) . '/translation.php';

use dbObject\Organization;
use dbObject\Holon;

function omoHolonTemplateEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? 0);
$targetHolonId = (int)($_GET['hid'] ?? 0);
$selectedTemplateId = (int)($_GET['tid'] ?? 0);
$isCompactMode = !empty($_GET['compact']);
$templateScope = strtolower(trim((string)($_GET['template_scope'] ?? 'contextual')));
$availableTemplateScopes = ['contextual'];
$organization = new Organization();
$editorData = null;
$errorMessage = '';
$isHolonDefinitionMode = false;

if ($organizationId <= 0) {
    $errorMessage = omoHolonTemplateT('parameters.holon_templates.error.no_organization');
} elseif (!$organization->load($organizationId)) {
    $errorMessage = omoHolonTemplateT('parameters.holon_templates.error.organization_not_found');
} elseif ($organization->getEnabledStructuralRootHolon() === null) {
    $errorMessage = omoHolonTemplateT('parameters.holon_templates.error.structure_required');
} else {
    $rootHolon = $organization->getEnabledStructuralRootHolon();
    $templateContextHolon = $rootHolon;
    if ($contextHolonId > 0) {
        $contextCandidate = new Holon();
        if ($contextCandidate->load($contextHolonId) && $organization->containsHolon($contextCandidate)) {
            $templateContextHolon = $contextCandidate;
        }
    }
    $availableTemplateScopes = omoApiGetAvailableContextScopes(true, $templateContextHolon, $rootHolon);
    $templateScope = omoApiNormalizeContextScope($templateScope, $availableTemplateScopes);
    if ($targetHolonId > 0) {
        $editorData = $organization->getHolonDefinitionEditorData($targetHolonId);
    }

    if ($editorData === null) {
        $editorData = $organization->getHolonTemplateEditorData($contextHolonId, $templateScope);
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

$omoHolonTemplateTexts = [
    'scopeAria' => omoHolonTemplateT('parameters.holon_templates.scope.aria'),
    'scopeContextual' => omoHolonTemplateT('parameters.holon_templates.scope.contextual'),
    'scopeChildren' => omoHolonTemplateT('parameters.holon_templates.scope.children'),
    'scopeDescendants' => omoHolonTemplateT('parameters.holon_templates.scope.descendants'),
    'mediaIconLabel' => omoHolonTemplateT('parameters.holon_templates.media.icon_label'),
    'mediaBannerLabel' => omoHolonTemplateT('parameters.holon_templates.media.banner_label'),
    'permissionSelf' => omoHolonTemplateT('parameters.holon_templates.permission.self'),
    'permissionChildren' => omoHolonTemplateT('parameters.holon_templates.permission.children'),
    'permissionParentCircleElements' => omoHolonTemplateT('parameters.holon_templates.permission.parent_circle_elements'),
    'permissionOrganization' => omoHolonTemplateT('parameters.holon_templates.permission.organization'),
    'permissionNoneAvailable' => omoHolonTemplateT('parameters.holon_templates.permission.none_available'),
    'permissionAddRange' => omoHolonTemplateT('parameters.holon_templates.permission.add_range'),
    'permissionNoneSelected' => omoHolonTemplateT('parameters.holon_templates.permission.none_selected'),
    'permissionRemoveRange' => omoHolonTemplateT('parameters.holon_templates.permission.remove_range'),
    'confirmInheritanceChange' => omoHolonTemplateT('parameters.holon_templates.confirm.inheritance_change'),
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
    'saveErrorOrganization' => omoHolonTemplateT('parameters.holon_templates.error.save_organization'),
    'saveErrorModel' => omoHolonTemplateT('parameters.holon_templates.error.save_model'),
    'savedModel' => omoHolonTemplateT('parameters.holon_templates.status.saved_model'),
    'deleteModel' => omoHolonTemplateT('parameters.holon_templates.action.delete_model'),
    'confirmDeleteModel' => omoHolonTemplateT('parameters.holon_templates.confirm.delete_model', ['templateName' => '{templateName}']),
    'deleteErrorModel' => omoHolonTemplateT('parameters.holon_templates.error.delete_model'),
    'deletedModel' => omoHolonTemplateT('parameters.holon_templates.status.deleted_model'),
];
?>
<div
    class="omo-template-editor omo-panel-view"
    id="omo-holon-template-page"
    data-omo-template-scope="<?= omoHolonTemplateEscape($templateScope) ?>"
    data-omo-template-cid="<?= (int)$contextHolonId ?>"
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
            <?php if (!$isHolonDefinitionMode): ?>
            <div class="omo-panel-view__body_content">
                <div class="omo-scope-toolbar">
                    <div class="omo-scope-toolbar__main">
                        <div
                            class="omo-scope-toggle"
                            role="tablist"
                            aria-label="<?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.scope.aria'), ENT_QUOTES, 'UTF-8') ?>"
                            data-omo-scope-switch="<?= omoHolonTemplateEscape($templateScope) ?>"
                            style="--omo-scope-option-count: <?= (int)count($availableTemplateScopes) ?>; --omo-scope-active-index: <?= (int)omoApiResolveContextScopeIndex($templateScope, $availableTemplateScopes) ?>;"
                        >
                            <?php foreach ($availableTemplateScopes as $scopeIndex => $scopeKey): ?>
                                <?php $scopeLabel = omoHolonTemplateT('parameters.holon_templates.scope.' . $scopeKey); ?>
                                <button
                                    type="button"
                                    class="omo-scope-toggle__button<?= $templateScope === $scopeKey ? ' is-active' : '' ?>"
                                    aria-label="<?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-omo-template-scope-toggle="<?= omoHolonTemplateEscape($scopeKey) ?>"
                                    data-omo-scope-option="<?= omoHolonTemplateEscape($scopeKey) ?>"
                                    data-omo-scope-index="<?= (int)$scopeIndex ?>"
                                    aria-pressed="<?= $templateScope === $scopeKey ? 'true' : 'false' ?>"
                                ><span class="omo-scope-toggle__text"><?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?></span></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="omo-scope-toolbar__note">
                            <strong><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.scope.note_' . $templateScope . '_prefix'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.scope.note_' . $templateScope, ['contextName' => ($editorData['contextHolonName'] ?? ($editorData['organizationName'] ?? omoHolonTemplateT('parameters.holon_templates.scope.current_context_fallback')))]), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="omo-template-editor__layout<?= $isCompactMode ? ' omo-template-editor__layout--compact' : '' ?><?= $isHolonDefinitionMode ? ' omo-template-editor__layout--holon-definition' : '' ?>" id="omo-holon-template-editor">
                <aside class="omo-template-sidebar">
                    <div class="omo-template-sidebar__hero">
                        <div class="omo-template-editor__eyebrow"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.hero.current_context'), ENT_QUOTES, 'UTF-8') ?></div>
                        <h3><?= omoHolonTemplateEscape($editorData['contextHolonName'] ?? ($editorData['organizationName'] ?? '')) ?></h3>
                        <p>
                            <?= $isHolonDefinitionMode
                                ? htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.hero.definition_mode'), ENT_QUOTES, 'UTF-8')
                                : htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.hero.template_mode'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div class="omo-template-sidebar__stats" id="omo-template-summary"></div>

                    <div class="omo-template-sidebar__actions"<?= $isHolonDefinitionMode ? ' hidden' : '' ?>>
                        <button type="button" class="omo-button omo-button--secondary" data-template-action="new-root"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.new_model'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="button" class="omo-button omo-button--ghost" data-template-action="new-child" disabled><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.new_submodel'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>

                    <div class="omo-template-tree-wrap">
                        <div class="omo-template-tree-wrap__title"><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.tree.current_holon') : omoHolonTemplateT('parameters.holon_templates.tree.models'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="omo-template-tree" id="omo-template-tree"></div>
                    </div>
                </aside>

                <section class="omo-template-form-panel">
                    <div class="omo-template-form-panel__header">
                        <div>
                            <div class="omo-template-editor__eyebrow"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.eyebrow'), ENT_QUOTES, 'UTF-8') ?></div>
                            <h3 class="omo-template-form-panel__title" id="omo-template-form-title"><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.form.organization') : omoHolonTemplateT('parameters.holon_templates.form.new_model'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="omo-template-form-panel__description" id="omo-template-form-description">
                                <?= $isHolonDefinitionMode
                                    ? htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.organization_description'), ENT_QUOTES, 'UTF-8')
                                    : htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.form.new_model_description'), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <div class="omo-template-form-panel__badges" id="omo-template-form-badges"></div>
                    </div>

                    <div class="omo-template-editor__status" id="omo-template-status" hidden></div>

                    <form id="omo-template-form" class="omo-template-form">
                        <section class="omo-template-section">
                            <div class="omo-template-section__title"><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.section.holon') : omoHolonTemplateT('parameters.holon_templates.section.structure'), ENT_QUOTES, 'UTF-8') ?></div>

                            <div class="omo-template-form__grid">
                                <label class="omo-field<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>" id="omo-template-type-field">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.base_type'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <select name="typeId" id="omo-template-type" required></select>
                                </label>

                                <label class="omo-field<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.inherits_from'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <select name="parentId" id="omo-template-parent"></select>
                                </label>

                                <label class="omo-field omo-field--full">
                                    <span><?= htmlspecialchars($isHolonDefinitionMode ? omoHolonTemplateT('parameters.holon_templates.field.name') : omoHolonTemplateT('parameters.holon_templates.field.model_name'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <input type="text" name="name" id="omo-template-name" maxlength="255" required>
                                </label>

                                <div class="omo-template-flags omo-field--full<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-visible">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.visible'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.visible_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-mandatory">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.mandatory'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.mandatory_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-locked-name">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.locked_name'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.locked_name_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-unique">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.unique'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.unique_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-link">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.link'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.flag.link_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                </div>

                            </div>
                        </section>

                        <section class="omo-template-section">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.properties'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <p class="omo-template-section__description">
                                        <?= $isHolonDefinitionMode
                                            ? htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.properties_description_organization'), ENT_QUOTES, 'UTF-8')
                                            : htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.properties_description_model'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <button type="button" class="omo-button omo-button--secondary" id="omo-template-add-property"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.action.add_property'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>

                            <div class="omo-template-properties" id="omo-template-properties"></div>
                        </section>

                        <section class="omo-template-section">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.permissions'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <p class="omo-template-section__description">
                                        <?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.permissions_description'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </div>

                            <div class="omo-template-permissions" id="omo-template-permissions"></div>
                        </section>

                        <section class="omo-template-section">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.appearance'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <p class="omo-template-section__description">
                                        <?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.appearance_description'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </div>

                            <div class="omo-template-form__grid">
                                <div class="omo-field omo-color-field" id="omo-template-color-field">
                                    <div class="omo-color-field__head">
                                        <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.color'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <label class="omo-color-field__toggle">
                                            <input type="checkbox" id="omo-template-color-enabled">
                                            <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.override'), ENT_QUOTES, 'UTF-8') ?></span>
                                        </label>
                                    </div>
                                    <div class="omo-color-field__body" id="omo-template-color-body">
                                        <input type="color" name="color" id="omo-template-color" value="#f59e0b">
                                        <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.color_empty_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </div>
                                </div>

                                <div class="omo-field omo-field--full<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.shared_media'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="omo-template-media-grid">
                                        <div class="omo-template-media-card">
                                            <div class="omo-template-media-card__head">
                                                <div class="omo-template-media-card__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.icon'), ENT_QUOTES, 'UTF-8') ?></div>
                                                <label class="omo-template-media-card__lock">
                                                    <input type="checkbox" id="omo-template-locked-icon">
                                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.locked_icon'), ENT_QUOTES, 'UTF-8') ?></span>
                                                </label>
                                            </div>
                                            <div id="omo-template-icon-field"></div>
                                        </div>
                                        <div class="omo-template-media-card">
                                            <div class="omo-template-media-card__head">
                                                <div class="omo-template-media-card__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.banner'), ENT_QUOTES, 'UTF-8') ?></div>
                                                <label class="omo-template-media-card__lock">
                                                    <input type="checkbox" id="omo-template-locked-banner">
                                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.locked_banner'), ENT_QUOTES, 'UTF-8') ?></span>
                                                </label>
                                            </div>
                                            <div id="omo-template-banner-field"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <?php if ($isHolonDefinitionMode): ?>
                        <section class="omo-template-section">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.public_share'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <p class="omo-template-section__description">
                                        <?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.section.public_share_description'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </div>

                            <div class="omo-template-form__grid">
                                <label class="omo-template-flags__option omo-field--full">
                                    <input type="checkbox" id="omo-template-share-public">
                                    <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.share_public'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <small><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.share_public_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                </label>

                                <div class="omo-field omo-field--full" id="omo-template-public-share-fields" hidden>
                                    <div class="omo-template-form__grid">
                                        <label class="omo-field omo-field--full">
                                            <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.public_model_name'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <input type="text" id="omo-template-public-name" maxlength="255">
                                        </label>

                                        <div class="omo-field omo-field--full">
                                            <span><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.shared_model_media'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <div class="omo-template-media-grid">
                                                <div class="omo-template-media-card">
                                                    <div class="omo-template-media-card__head">
                                                        <div class="omo-template-media-card__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.logo_icon'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    </div>
                                                    <div id="omo-template-public-icon-field"></div>
                                                </div>
                                                <div class="omo-template-media-card">
                                                    <div class="omo-template-media-card__head">
                                                        <div class="omo-template-media-card__title"><?= htmlspecialchars(omoHolonTemplateT('parameters.holon_templates.field.banner'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    </div>
                                                    <div id="omo-template-public-banner-field"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <?php endif; ?>

                        <div class="omo-template-form__footer">
                            <div class="omo-template-form__hint" id="omo-template-selection-hint"></div>
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
                </section>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.omo-template-permissions__table {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.omo-template-permissions__row {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    gap: 0.9rem;
    align-items: start;
    padding: 0.85rem 1rem;
    border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 88%, transparent);
    border-radius: 0.9rem;
    background: color-mix(in srgb, var(--color-surface, #ffffff) 88%, var(--color-surface-alt, #f8fafc));
}

.omo-template-permissions__main {
    min-width: 0;
}

.omo-template-permissions__title {
    font-weight: 600;
    color: var(--color-text, #111827);
}

.omo-template-permissions__meta,
.omo-template-permissions__description {
    font-size: 0.88rem;
    color: var(--color-text-light, #6b7280);
}

.omo-template-permissions__picker {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.omo-template-permissions__tokens {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    min-height: 2rem;
}

.omo-template-permissions__token {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 20%, var(--color-border, #d1d5db));
    background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
    color: var(--color-text, #111827);
    font-size: 0.85rem;
}

.omo-template-permissions__token-remove {
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    padding: 0;
    font-size: 1rem;
    line-height: 1;
}

.omo-template-permissions__select {
    width: 100%;
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: 0.75rem;
    padding: 0.7rem 0.85rem;
    background: var(--color-surface-alt, #f8fafc);
    color: var(--color-text, #111827);
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.omo-template-permissions__select:focus {
    outline: none;
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 55%, var(--color-border, #d1d5db));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 14%, transparent);
    background: var(--color-surface, #ffffff);
}

.omo-template-permissions__empty {
    font-size: 0.85rem;
    color: var(--color-text-light, #6b7280);
}

.omo-template-permissions__checkbox {
    width: 1.1rem;
    height: 1.1rem;
}

@media (max-width: 900px) {
    .omo-template-permissions__header {
        display: none;
    }

    .omo-template-permissions__row {
        grid-template-columns: 1fr 1fr;
    }

    .omo-template-permissions__main {
        grid-column: 1 / -1;
    }
}
</style>

<?php if ($editorData !== null): ?>
<script src="/omo/assets/js/sized-image-field.js"></script>
<script src="/omo/assets/js/simple-html-field.js?v=20260721-composite-property-fields-v5"></script>
<script src="/common/assets/multiline-list-paste.js"></script>
<script>
(() => {
const omoHolonTemplatePageRoot = document.getElementById('omo-holon-template-page');
const omoHolonTemplateTexts = <?= json_encode($omoHolonTemplateTexts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const omoHolonTemplateState = {
    data: <?= json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    selectedId: <?= (int)$selectedTemplateId ?>,
    compactMode: <?= $isCompactMode ? 'true' : 'false' ?>,
    statusTimer: null
};

if (!omoHolonTemplatePageRoot) {
    return;
}

const omoHolonTemplateRoot = document.getElementById('omo-holon-template-editor');
if (!omoHolonTemplateRoot) {
    return;
}

const omoHolonTemplateElements = {
    root: omoHolonTemplateRoot,
    tree: omoHolonTemplateRoot.querySelector('#omo-template-tree'),
    status: omoHolonTemplateRoot.querySelector('#omo-template-status'),
    form: omoHolonTemplateRoot.querySelector('#omo-template-form'),
    type: omoHolonTemplateRoot.querySelector('#omo-template-type'),
    parent: omoHolonTemplateRoot.querySelector('#omo-template-parent'),
    name: omoHolonTemplateRoot.querySelector('#omo-template-name'),
    colorEnabled: omoHolonTemplateRoot.querySelector('#omo-template-color-enabled'),
    colorBody: omoHolonTemplateRoot.querySelector('#omo-template-color-body'),
    color: omoHolonTemplateRoot.querySelector('#omo-template-color'),
    iconField: omoHolonTemplateRoot.querySelector('#omo-template-icon-field'),
    bannerField: omoHolonTemplateRoot.querySelector('#omo-template-banner-field'),
    sharePublic: omoHolonTemplateRoot.querySelector('#omo-template-share-public'),
    publicShareFields: omoHolonTemplateRoot.querySelector('#omo-template-public-share-fields'),
    publicName: omoHolonTemplateRoot.querySelector('#omo-template-public-name'),
    publicIconField: omoHolonTemplateRoot.querySelector('#omo-template-public-icon-field'),
    publicBannerField: omoHolonTemplateRoot.querySelector('#omo-template-public-banner-field'),
    visible: omoHolonTemplateRoot.querySelector('#omo-template-visible'),
    mandatory: omoHolonTemplateRoot.querySelector('#omo-template-mandatory'),
    lockedName: omoHolonTemplateRoot.querySelector('#omo-template-locked-name'),
    lockedIcon: omoHolonTemplateRoot.querySelector('#omo-template-locked-icon'),
    lockedBanner: omoHolonTemplateRoot.querySelector('#omo-template-locked-banner'),
    unique: omoHolonTemplateRoot.querySelector('#omo-template-unique'),
    link: omoHolonTemplateRoot.querySelector('#omo-template-link'),
    addProperty: omoHolonTemplateRoot.querySelector('#omo-template-add-property'),
    properties: omoHolonTemplateRoot.querySelector('#omo-template-properties'),
    permissions: omoHolonTemplateRoot.querySelector('#omo-template-permissions'),
    selectionHint: omoHolonTemplateRoot.querySelector('#omo-template-selection-hint'),
    cancel: omoHolonTemplateRoot.querySelector('#omo-template-cancel'),
    deleteButton: omoHolonTemplateRoot.querySelector('#omo-template-delete'),
    newChildButton: omoHolonTemplateRoot.querySelector('[data-template-action="new-child"]'),
    summary: omoHolonTemplateRoot.querySelector('#omo-template-summary'),
    formTitle: omoHolonTemplateRoot.querySelector('#omo-template-form-title'),
    formDescription: omoHolonTemplateRoot.querySelector('#omo-template-form-description'),
    formBadges: omoHolonTemplateRoot.querySelector('#omo-template-form-badges')
};

const omoHolonTemplateMediaFields = {
    icon: null,
    banner: null
};

function omoHolonTemplateWaitForGlobalLibrary(globalKey, timeoutMs) {
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

function omoHolonTemplateBootstrapInitialRender() {
    omoHolonTemplateRenderTree();
    if (Number(omoHolonTemplateState.selectedId || 0) > 0 && omoHolonTemplateFind(omoHolonTemplateState.selectedId)) {
        omoHolonTemplateSelect(omoHolonTemplateState.selectedId);
    } else if (
        omoHolonTemplateIsHolonDefinitionMode()
        && Array.isArray(omoHolonTemplateState.data.templates)
        && omoHolonTemplateState.data.templates.length > 0
        && Number(omoHolonTemplateState.data.templates[0].id || 0) > 0
    ) {
        omoHolonTemplateSelect(Number(omoHolonTemplateState.data.templates[0].id || 0));
    } else {
        omoHolonTemplateFillForm(omoHolonTemplateBuildDraft(0));
    }
}

function omoHolonTemplateNormalizeScope(scope) {
    const normalizedScope = String(scope || '').trim().toLowerCase();
    if (normalizedScope === 'global') {
        return 'descendants';
    }
    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
}

function omoHolonTemplateGetScope() {
    return omoHolonTemplateNormalizeScope(omoHolonTemplatePageRoot.getAttribute('data-omo-template-scope') || 'contextual');
}

function omoHolonTemplateIsHolonDefinitionMode() {
    return String((omoHolonTemplateState.data || {}).editorMode || 'template') === 'holon-definition';
}

function omoHolonTemplateBuildScopeUrl(scope, options) {
    const resolvedScope = omoHolonTemplateNormalizeScope(scope);
    const settings = options && typeof options === 'object' ? options : {};
    const query = [];
    const requestedContextHolonId = Number(omoHolonTemplatePageRoot.getAttribute('data-omo-template-cid') || 0);
    const contextHolonId = Number(settings.contextHolonId || requestedContextHolonId || 0);
    const templateId = Number(settings.templateId || 0);

    if (contextHolonId > 0) {
        query.push('cid=' + encodeURIComponent(String(contextHolonId)));
    }
    if (templateId > 0) {
        query.push('tid=' + encodeURIComponent(String(templateId)));
    }
    if (resolvedScope !== 'contextual') {
        query.push('template_scope=' + encodeURIComponent(resolvedScope));
    }

    return '/omo/api/parameters/holon-templates/index.php' + (query.length ? ('?' + query.join('&')) : '');
}

function omoHolonTemplateSetScopeLoadingState(isLoading, nextScope) {
    omoHolonTemplatePageRoot.classList.toggle('is-loading', !!isLoading);
    omoHolonTemplatePageRoot.setAttribute('aria-busy', isLoading ? 'true' : 'false');

    const scopeSwitch = omoHolonTemplatePageRoot.querySelector('[data-omo-scope-switch]');
    if (scopeSwitch && nextScope) {
        scopeSwitch.setAttribute('data-omo-scope-switch', omoHolonTemplateNormalizeScope(nextScope));
    }

    omoHolonTemplatePageRoot.querySelectorAll('[data-omo-template-scope-toggle]').forEach(function (button) {
        button.disabled = !!isLoading;
    });
}

function omoHolonTemplateReloadPage(scope, options) {
    const targetScope = omoHolonTemplateNormalizeScope(scope);
    const targetUrl = omoHolonTemplateBuildScopeUrl(targetScope, options);

    if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
        window.location.href = targetUrl;
        return Promise.resolve(null);
    }

    return window.omoReplaceFetchedPanelRoot({
        rootSelector: '#omo-holon-template-page',
        currentRoot: omoHolonTemplatePageRoot,
        url: targetUrl,
        setLoadingState: function (isLoading) {
            omoHolonTemplateSetScopeLoadingState(isLoading, targetScope);
        }
    });
}

if (omoHolonTemplateIsHolonDefinitionMode()) {
    omoHolonTemplateElements.iconField = omoHolonTemplateElements.publicIconField;
    omoHolonTemplateElements.bannerField = omoHolonTemplateElements.publicBannerField;
    omoHolonTemplateElements.lockedIcon = null;
    omoHolonTemplateElements.lockedBanner = null;
}

function omoHolonTemplateToggleTypeField(isInherited) {
    const typeField = omoHolonTemplateRoot.querySelector('#omo-template-type-field');
    const typeSelect = omoHolonTemplateElements.type;
    if (!typeField) {
        return;
    }

    typeField.hidden = !!isInherited;
    if (typeSelect) {
        typeSelect.hidden = !!isInherited;
        typeSelect.disabled = !!isInherited;
        typeSelect.required = !isInherited;
    }
}

function omoHolonTemplateGetEffectiveInheritanceIdFromParent(parentId) {
    return Number(parentId || 0);
}

function omoHolonTemplateGetEffectiveTypeId(typeId, inheritsFromId) {
    const inheritanceTemplate = omoHolonTemplateFind(inheritsFromId);
    if (inheritanceTemplate && Number(inheritanceTemplate.typeId || 0) > 0) {
        return Number(inheritanceTemplate.typeId || 0);
    }

    return Number(typeId || 0);
}

// Synchronise champ couleur
function omoHolonTemplateSyncColorField() {
    const isEnabled = Boolean(omoHolonTemplateElements.colorEnabled && omoHolonTemplateElements.colorEnabled.checked);

    if (omoHolonTemplateElements.colorBody) {
        omoHolonTemplateElements.colorBody.hidden = !isEnabled;
    }

    if (omoHolonTemplateElements.color) {
        omoHolonTemplateElements.color.disabled = !isEnabled;
    }
}

function omoHolonTemplateSyncPublicShareFields() {
    if (!omoHolonTemplateIsHolonDefinitionMode()) {
        return;
    }

    const isEnabled = Boolean(omoHolonTemplateElements.sharePublic && omoHolonTemplateElements.sharePublic.checked);

    if (omoHolonTemplateElements.publicShareFields) {
        omoHolonTemplateElements.publicShareFields.hidden = !isEnabled;
    }

    if (omoHolonTemplateElements.publicName) {
        omoHolonTemplateElements.publicName.disabled = !isEnabled;
        omoHolonTemplateElements.publicName.required = isEnabled;
    }
}

function omoHolonTemplateGetMediaDisplayConfig(kind) {
    if (kind === 'banner') {
        return {
            displayWidth: 360,
            displayHeight: 202,
            targetWidth: 960,
            targetHeight: 540,
            emptyText: 'Aucune bannière transmise par ce modèle.'
        };
    }

    return {
        displayWidth: 160,
        displayHeight: 160,
        targetWidth: 500,
        targetHeight: 500,
        emptyText: 'Aucune icône transmise par ce modèle.'
    };
}

function omoHolonTemplateResolveMediaState(kind, template) {
    const suffix = kind === 'icon' ? 'Icon' : 'Banner';
    const currentController = omoHolonTemplateMediaFields[kind];
    const currentTemplateId = Number(omoHolonTemplateElements.form.dataset.templateId || 0);
    const targetTemplateId = Number((template && template.id) || 0);
    const shouldReuseController = Boolean(currentController) && currentTemplateId === targetTemplateId;
    const localValue = shouldReuseController
        ? currentController.getValue()
        : String((template && template[kind]) || '');
    const inheritedLocked = Boolean(template && template['inheritedLocked' + suffix]);

    return {
        value: inheritedLocked ? '' : localValue,
        inheritedValue: String((template && template['inherited' + suffix]) || ''),
        locked: Boolean(template && template['effectiveLocked' + suffix]),
        inheritedLocked: inheritedLocked,
        localLocked: Boolean(template && template['locked' + suffix])
    };
}

function omoHolonTemplateRenderMediaFields(template) {
    if (!window.omoSizedImageField) {
        return;
    }

    [
        ['icon', omoHolonTemplateElements.iconField, omoHolonTemplateElements.lockedIcon, 'Icône'],
        ['banner', omoHolonTemplateElements.bannerField, omoHolonTemplateElements.lockedBanner, 'Bannière']
    ].forEach(function (entry) {
        const kind = entry[0];
        const target = entry[1];
        const lockField = entry[2];
        const label = entry[3];
        if (!target) {
            return;
        }

        const mediaState = omoHolonTemplateResolveMediaState(kind, template);
        const config = omoHolonTemplateGetMediaDisplayConfig(kind);
        if (lockField) {
            lockField.checked = Boolean(mediaState.localLocked || mediaState.inheritedLocked);
            lockField.disabled = Boolean(mediaState.inheritedLocked);
            lockField.dataset.localValue = mediaState.localLocked ? '1' : '0';
        }

        omoHolonTemplateMediaFields[kind] = window.omoSizedImageField.mount(target, {
            inputName: 'template_' + kind,
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

function omoHolonTemplateFlatten(templates, items) {
    const target = items || [];
    (templates || []).forEach(function (template) {
        target.push(template);
        omoHolonTemplateFlatten(template.children || [], target);
    });
    return target;
}

function omoHolonTemplateGetAll() {
    return omoHolonTemplateFlatten(omoHolonTemplateState.data.templates || [], []);
}

function omoHolonTemplateFind(templateId) {
    const numericId = Number(templateId || 0);
    const localTemplate = omoHolonTemplateGetAll().find(function (template) {
        return Number(template.id) === numericId;
    });

    if (localTemplate) {
        return localTemplate;
    }

    return (omoHolonTemplateState.data.templateCatalog || []).find(function (template) {
        return Number(template.id) === numericId;
    }) || null;
}

function omoHolonTemplateEscapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function omoHolonTemplateGetTypeLabel(typeId) {
    const numericTypeId = Number(typeId || 0);
    const type = (omoHolonTemplateState.data.types || []).find(function (entry) {
        return Number(entry.id) === numericTypeId;
    });

    return type ? String(type.name || '') : '';
}

function omoHolonTemplateCloneProperty(property) {
    return JSON.parse(JSON.stringify(property || {}));
}

function omoHolonTemplateBuildListStorageValue(items) {
    const normalizedItems = Array.isArray(items) ? items.filter(function (item) {
        if (Array.isArray(item)) {
            return false;
        }

        if (item && typeof item === 'object') {
            return Object.keys(item).length > 0;
        }

        return String(item || '').trim() !== '';
    }) : [];

    return normalizedItems.length
        ? JSON.stringify(normalizedItems)
        : '';
}

function omoHolonTemplateGetVisiblePropertyValue(property) {
    const normalizedProperty = omoHolonTemplateNormalizeProperty(property);
    const inheritedValue = normalizedProperty.inheritedValue !== undefined && normalizedProperty.inheritedValue !== null
        ? String(normalizedProperty.inheritedValue)
        : '';
    const localValue = normalizedProperty.value !== undefined && normalizedProperty.value !== null
        ? String(normalizedProperty.value)
        : '';

    if (normalizedProperty.inheritedLocked) {
        return inheritedValue;
    }

    if ([2, 7].indexOf(Number(normalizedProperty.formatId || 0)) >= 0) {
        const mergedItems = [];
        const seen = new Set();

        omoHolonTemplateParseStoredListValue(inheritedValue)
            .concat(omoHolonTemplateParseStoredListValue(localValue))
            .forEach(function (item) {
                const key = item && typeof item === 'object'
                    ? JSON.stringify(item)
                    : String(item || '').trim();
                if (!key || seen.has(key)) {
                    return;
                }

                seen.add(key);
                mergedItems.push(item);
            });

        return omoHolonTemplateBuildListStorageValue(mergedItems);
    }

    return localValue.trim() !== '' ? localValue : inheritedValue;
}

function omoHolonTemplateBuildInheritedProperties(inheritsFromId) {
    const parentTemplate = omoHolonTemplateFind(inheritsFromId);
    if (!parentTemplate || !Array.isArray(parentTemplate.properties)) {
        return [];
    }

    return parentTemplate.properties.map(function (property) {
        const normalizedProperty = omoHolonTemplateNormalizeProperty(omoHolonTemplateCloneProperty(property));
        const inheritedMandatory = Boolean(normalizedProperty.effectiveMandatory);
        const inheritedLocked = Boolean(normalizedProperty.effectiveLocked);

        return omoHolonTemplateNormalizeProperty(Object.assign({}, normalizedProperty, {
            holonPropertyId: 0,
            value: '',
            inheritedValue: omoHolonTemplateGetVisiblePropertyValue(normalizedProperty),
            mandatory: false,
            locked: false,
            inheritedMandatory: inheritedMandatory,
            inheritedLocked: inheritedLocked,
            effectiveMandatory: inheritedMandatory,
            effectiveLocked: inheritedLocked,
            isInherited: true,
            isLocal: false,
            canDelete: !inheritedMandatory,
            canEditValue: !inheritedLocked
        }));
    });
}

function omoHolonTemplateComputeDraftProperties(inheritsFromId, currentProperties) {
    const inheritedProperties = omoHolonTemplateBuildInheritedProperties(inheritsFromId);
    const inheritedById = new Map();
    inheritedProperties.forEach(function (property) {
        inheritedById.set(Number(property.id || 0), property);
    });

    const result = [];
    (currentProperties || []).forEach(function (property) {
        const normalizedProperty = omoHolonTemplateNormalizeProperty(omoHolonTemplateCloneProperty(property));
        const propertyId = Number(normalizedProperty.id || 0);
        const inheritedProperty = propertyId > 0 ? inheritedById.get(propertyId) : null;
        const hasLocalContribution = Number(normalizedProperty.holonPropertyId || 0) > 0
            || Boolean(normalizedProperty.isLocal)
            || Boolean(normalizedProperty.mandatory)
            || Boolean(normalizedProperty.locked)
            || String(normalizedProperty.value || '').trim() !== '';

        if (inheritedProperty) {
            inheritedById.delete(propertyId);
            result.push(omoHolonTemplateNormalizeProperty(Object.assign({}, inheritedProperty, {
                holonPropertyId: Number(normalizedProperty.holonPropertyId || 0),
                value: hasLocalContribution ? String(normalizedProperty.value || '') : '',
                mandatory: hasLocalContribution ? Boolean(normalizedProperty.mandatory) : false,
                locked: hasLocalContribution ? Boolean(normalizedProperty.locked) : false,
                effectiveMandatory: Boolean(inheritedProperty.inheritedMandatory) || (hasLocalContribution ? Boolean(normalizedProperty.mandatory) : false),
                effectiveLocked: Boolean(inheritedProperty.inheritedLocked) || (hasLocalContribution ? Boolean(normalizedProperty.locked) : false),
                isInherited: true,
                isLocal: hasLocalContribution,
                canDelete: !Boolean(inheritedProperty.inheritedMandatory),
                canEditValue: !Boolean(inheritedProperty.inheritedLocked)
            })));
            return;
        }

        if (propertyId > 0 && normalizedProperty.isInherited) {
            return;
        }

        result.push(omoHolonTemplateNormalizeProperty(Object.assign({}, normalizedProperty, {
            inheritedValue: '',
            inheritedMandatory: false,
            inheritedLocked: false,
            effectiveMandatory: Boolean(normalizedProperty.mandatory),
            effectiveLocked: Boolean(normalizedProperty.locked),
            isInherited: false,
            isLocal: true,
            canDelete: true,
            canEditValue: true
        })));
    });

    inheritedProperties.forEach(function (property) {
        if (inheritedById.has(Number(property.id || 0))) {
            result.push(property);
        }
    });

    return result;
}

function omoHolonTemplateReadCurrentFormState() {
    const currentId = Number(omoHolonTemplateElements.form.dataset.templateId || 0);
    const selectedParentId = Number(omoHolonTemplateElements.parent.value || 0);
    const effectiveInheritanceId = omoHolonTemplateGetEffectiveInheritanceIdFromParent(selectedParentId);
    const effectiveTypeId = omoHolonTemplateGetEffectiveTypeId(omoHolonTemplateElements.type.value || 0, effectiveInheritanceId);

    return {
        id: currentId,
        name: String(omoHolonTemplateElements.name.value || ''),
        color: Boolean(omoHolonTemplateElements.colorEnabled && omoHolonTemplateElements.colorEnabled.checked)
            ? String(omoHolonTemplateElements.color && omoHolonTemplateElements.color.value ? omoHolonTemplateElements.color.value : '')
            : '',
        icon: omoHolonTemplateMediaFields.icon ? omoHolonTemplateMediaFields.icon.getValue() : '',
        banner: omoHolonTemplateMediaFields.banner ? omoHolonTemplateMediaFields.banner.getValue() : '',
        typeId: effectiveTypeId,
        typeLabel: omoHolonTemplateGetTypeLabel(effectiveTypeId),
        visible: Boolean(omoHolonTemplateElements.visible && omoHolonTemplateElements.visible.checked),
        mandatory: Boolean(omoHolonTemplateElements.mandatory && omoHolonTemplateElements.mandatory.checked),
        lockedName: Boolean(omoHolonTemplateElements.lockedName && omoHolonTemplateElements.lockedName.checked),
        lockedIcon: omoHolonTemplateElements.lockedIcon
            ? (omoHolonTemplateElements.lockedIcon.disabled
                ? String(omoHolonTemplateElements.lockedIcon.dataset.localValue || '0') === '1'
                : Boolean(omoHolonTemplateElements.lockedIcon.checked))
            : false,
        lockedBanner: omoHolonTemplateElements.lockedBanner
            ? (omoHolonTemplateElements.lockedBanner.disabled
                ? String(omoHolonTemplateElements.lockedBanner.dataset.localValue || '0') === '1'
                : Boolean(omoHolonTemplateElements.lockedBanner.checked))
            : false,
        unique: Boolean(omoHolonTemplateElements.unique && omoHolonTemplateElements.unique.checked),
        link: Boolean(omoHolonTemplateElements.link && omoHolonTemplateElements.link.checked),
        inheritsFromId: effectiveInheritanceId,
        permissionAssignments: omoHolonTemplateReadPermissions(),
        properties: omoHolonTemplateReadProperties()
    };
}

function omoHolonTemplateRenderPermissions(permissionAssignments) {
    const permissionCatalog = Array.isArray(omoHolonTemplateState.data.permissionCatalog)
        ? omoHolonTemplateState.data.permissionCatalog
        : [];
    const rangeOptions = Array.isArray(omoHolonTemplateState.data.permissionRanges)
        ? omoHolonTemplateState.data.permissionRanges
        : [
            { key: 'self', label: omoHolonTemplateTexts.permissionSelf || '' },
            { key: 'parent_circle', label: 'Cercle englobant seul' },
            { key: 'parent_circle_elements', label: omoHolonTemplateTexts.permissionParentCircleElements || '' },
            { key: 'parent_circle_descendants', label: 'Cercle englobant et descendants' },
            { key: 'organization', label: omoHolonTemplateTexts.permissionOrganization || '' }
        ];
    const assignments = permissionAssignments && typeof permissionAssignments === 'object'
        ? permissionAssignments
        : {};

    if (!omoHolonTemplateElements.permissions) {
        return;
    }

    if (!permissionCatalog.length) {
        omoHolonTemplateElements.permissions.innerHTML = '<div class="omo-template-properties__empty">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.permissionNoneAvailable || '') + '</div>';
        return;
    }

    let html = '<div class="omo-template-permissions__table">';

    permissionCatalog.forEach(function (permission) {
        const permissionRangeOptions = Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
            ? permission.rangeOptions
            : rangeOptions;
        const selectedRanges = omoHolonTemplateNormalizePermissionRanges(assignments[permission.key]);

        html += ''
            + '<div class="omo-template-permissions__row" data-permission-key="' + omoHolonTemplateEscapeHtml(permission.key) + '">'
            + '  <div class="omo-template-permissions__main">'
            + '      <div class="omo-template-permissions__title">' + omoHolonTemplateEscapeHtml(permission.title || permission.key) + '</div>'
            + '      <div class="omo-template-permissions__meta">' + omoHolonTemplateEscapeHtml(permission.key) + '</div>';

        if (String(permission.description || '').trim() !== '') {
            html += '<div class="omo-template-permissions__description">' + omoHolonTemplateEscapeHtml(permission.description) + '</div>';
        }

        html += ''
            + '  </div>'
            + '  <div class="omo-template-permissions__picker">'
            + '      <div class="omo-template-permissions__tokens" data-permission-tokens></div>'
            + '      <select class="omo-template-permissions__select" data-permission-select>'
            + '          <option value="">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.permissionAddRange || '') + '</option>';

        permissionRangeOptions.forEach(function (range) {
            html += '<option value="' + omoHolonTemplateEscapeHtml(range.key) + '">' + omoHolonTemplateEscapeHtml(range.label || range.key) + '</option>';
        });

        html += ''
            + '      </select>'
            + '  </div>'
            + '</div>';

    });

    html += '</div>';
    omoHolonTemplateElements.permissions.innerHTML = html;

    Array.from(omoHolonTemplateElements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
        const permissionKey = String(row.getAttribute('data-permission-key') || '').trim();
        const permission = permissionCatalog.find(function (item) {
            return String(item && item.key ? item.key : '') === permissionKey;
        }) || null;
        const permissionRangeOptions = permission && Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
            ? permission.rangeOptions
            : rangeOptions;
        const selectedRanges = omoHolonTemplateNormalizePermissionRanges(assignments[permissionKey]);
        omoHolonTemplateBindPermissionRow(row, permissionRangeOptions);
        omoHolonTemplateSetPermissionRowRanges(row, selectedRanges, permissionRangeOptions);
    });
}

function omoHolonTemplateReadPermissions() {
    if (!omoHolonTemplateElements.permissions) {
        return {};
    }

    const assignments = {};
    Array.from(omoHolonTemplateElements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
        const permissionKey = String(row.getAttribute('data-permission-key') || '').trim();
        if (!permissionKey) {
            return;
        }

        const selectedRanges = Array.from(row.querySelectorAll('[data-permission-token]')).map(function (token) {
            return String(token.getAttribute('data-permission-token') || '').trim();
        }).filter(function (range) {
            return range !== '';
        });

        if (selectedRanges.length) {
            assignments[permissionKey] = selectedRanges;
        }
    });

    return assignments;
}

function omoHolonTemplateNormalizePermissionRanges(value) {
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

function omoHolonTemplateGetPermissionRangeLabel(rangeKey, rangeOptions) {
    const range = (rangeOptions || []).find(function (item) {
        return String(item.key || '') === String(rangeKey || '');
    });

    return range ? String(range.label || range.key || '') : String(rangeKey || '');
}

function omoHolonTemplateSetPermissionRowRanges(row, selectedRanges, rangeOptions) {
    const tokensContainer = row.querySelector('[data-permission-tokens]');
    const select = row.querySelector('[data-permission-select]');
    const normalizedRanges = omoHolonTemplateNormalizePermissionRanges(selectedRanges);

    if (!tokensContainer) {
        return;
    }

    if (!normalizedRanges.length) {
        tokensContainer.innerHTML = '<span class="omo-template-permissions__empty">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.permissionNoneSelected || '') + '</span>';
    } else {
        tokensContainer.innerHTML = normalizedRanges.map(function (rangeKey) {
            return ''
                + '<span class="omo-template-permissions__token" data-permission-token="' + omoHolonTemplateEscapeHtml(rangeKey) + '">'
                + '  <span>' + omoHolonTemplateEscapeHtml(omoHolonTemplateGetPermissionRangeLabel(rangeKey, rangeOptions)) + '</span>'
                + '  <button type="button" class="omo-template-permissions__token-remove" data-permission-remove="' + omoHolonTemplateEscapeHtml(rangeKey) + '" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.permissionRemoveRange || '') + '">&times;</button>'
                + '</span>';
        }).join('');
    }

    if (select) {
        select.value = '';
    }
}

function omoHolonTemplateBindPermissionRow(row, rangeOptions) {
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

        const currentRanges = Array.from(row.querySelectorAll('[data-permission-token]')).map(function (token) {
            return String(token.getAttribute('data-permission-token') || '').trim();
        });
        currentRanges.push(nextRange);
        omoHolonTemplateSetPermissionRowRanges(row, currentRanges, rangeOptions);
    });

    row.addEventListener('click', function (event) {
        const removeButton = event.target instanceof Element
            ? event.target.closest('[data-permission-remove]')
            : null;
        if (!removeButton) {
            return;
        }

        const removedRange = String(removeButton.getAttribute('data-permission-remove') || '').trim();
        const remainingRanges = Array.from(row.querySelectorAll('[data-permission-token]')).map(function (token) {
            return String(token.getAttribute('data-permission-token') || '').trim();
        }).filter(function (range) {
            return range !== '' && range !== removedRange;
        });

        omoHolonTemplateSetPermissionRowRanges(row, remainingRanges, rangeOptions);
    });
}

function omoHolonTemplatePropertyHasLocalData(property) {
    const normalizedProperty = omoHolonTemplateNormalizeProperty(property);
    const hasLocalValue = String(normalizedProperty.value || '').trim() !== '';
    const hasLocalFlags = Boolean(normalizedProperty.mandatory) || Boolean(normalizedProperty.locked);
    const hasLocalIdentity = Number(normalizedProperty.holonPropertyId || 0) > 0 || (!normalizedProperty.isInherited && String(normalizedProperty.name || '').trim() !== '');

    return hasLocalValue || hasLocalFlags || hasLocalIdentity;
}

function omoHolonTemplateRequiresInheritanceChangeConfirmation(previousParentId, nextParentId) {
    const current = omoHolonTemplateReadCurrentFormState();
    if (Number(previousParentId || 0) === Number(nextParentId || 0)) {
        return false;
    }

    if (Number(current.id || 0) > 0) {
        return true;
    }

    if (String(current.name || '').trim() !== '') {
        return true;
    }

    return (current.properties || []).some(function (property) {
        return omoHolonTemplatePropertyHasLocalData(property);
    });
}

function omoHolonTemplateConfirmInheritanceChange(previousParentId, nextParentId) {
    if (!omoHolonTemplateRequiresInheritanceChangeConfirmation(previousParentId, nextParentId)) {
        return true;
    }

    return window.confirm(
        omoHolonTemplateTexts.confirmInheritanceChange || ''
    );
}

function omoHolonTemplateRefreshInheritancePreview() {
    const current = omoHolonTemplateReadCurrentFormState();
    current.typeId = omoHolonTemplateGetEffectiveTypeId(current.typeId, current.inheritsFromId);
    current.typeLabel = omoHolonTemplateGetTypeLabel(current.typeId);
    current.properties = omoHolonTemplateComputeDraftProperties(current.inheritsFromId, current.properties);
    omoHolonTemplateApplyInheritedMediaState(current);
    omoHolonTemplateFillForm(current);
}

function omoHolonTemplateApplyInheritedMediaState(template) {
    const parentTemplate = omoHolonTemplateFind(template && template.inheritsFromId ? template.inheritsFromId : 0);

    template.inheritedIcon = parentTemplate ? String(parentTemplate.effectiveIcon || '') : '';
    template.inheritedLockedIcon = parentTemplate ? Boolean(parentTemplate.effectiveLockedIcon) : false;
    template.effectiveIcon = String(template.icon || '').trim() !== '' ? String(template.icon || '') : template.inheritedIcon;
    template.effectiveLockedIcon = Boolean(template.lockedIcon || template.inheritedLockedIcon);

    template.inheritedBanner = parentTemplate ? String(parentTemplate.effectiveBanner || '') : '';
    template.inheritedLockedBanner = parentTemplate ? Boolean(parentTemplate.effectiveLockedBanner) : false;
    template.effectiveBanner = String(template.banner || '').trim() !== '' ? String(template.banner || '') : template.inheritedBanner;
    template.effectiveLockedBanner = Boolean(template.lockedBanner || template.inheritedLockedBanner);

    return template;
}

function omoHolonTemplateBuildDraft(inheritsFromId) {
    const firstType = (omoHolonTemplateState.data.types || [])[0] || { id: 1, name: 'Holon' };
    const suggestedInheritanceId = Number(inheritsFromId || 0);
    const effectiveTypeId = omoHolonTemplateGetEffectiveTypeId(firstType.id || 1, suggestedInheritanceId);
    return omoHolonTemplateApplyInheritedMediaState({
        id: 0,
        name: '',
        color: '',
        icon: '',
        inheritedIcon: '',
        effectiveIcon: '',
        banner: '',
        inheritedBanner: '',
        effectiveBanner: '',
        typeId: effectiveTypeId,
        typeLabel: omoHolonTemplateGetTypeLabel(effectiveTypeId) || String(firstType.name || 'Holon'),
        visible: false,
        mandatory: false,
        lockedName: false,
        lockedIcon: false,
        inheritedLockedIcon: false,
        effectiveLockedIcon: false,
        lockedBanner: false,
        inheritedLockedBanner: false,
        effectiveLockedBanner: false,
        unique: false,
        link: false,
        permissionAssignments: {},
        inheritsFromId: suggestedInheritanceId,
        properties: omoHolonTemplateComputeDraftProperties(suggestedInheritanceId, [])
    });
}

function omoHolonTemplateRenderSummary() {
    const templates = omoHolonTemplateGetAll();
    const propertyCount = templates.reduce(function (total, template) {
        return total + ((template.properties || []).length || 0);
    }, 0);

    omoHolonTemplateElements.summary.innerHTML = ''
        + '<div class="omo-template-stat">'
        + '  <strong>' + templates.length + '</strong>'
        + '  <span>' + omoHolonTemplateEscapeHtml(templates.length > 1 ? (omoHolonTemplateTexts.summaryModelOther || '') : (omoHolonTemplateTexts.summaryModelOne || '')) + '</span>'
        + '</div>'
        + '<div class="omo-template-stat">'
        + '  <strong>' + propertyCount + '</strong>'
        + '  <span>' + omoHolonTemplateEscapeHtml(propertyCount > 1 ? (omoHolonTemplateTexts.summaryPropertyOther || '') : (omoHolonTemplateTexts.summaryPropertyOne || '')) + '</span>'
        + '</div>';
}

function omoHolonTemplateRenderTreeNodes(nodes) {
    if (!nodes || !nodes.length) {
        return '<div class="omo-template-tree__empty">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.treeEmpty || '') + '</div>';
    }

    let html = '<ul class="omo-template-tree__list">';
    nodes.forEach(function (node) {
        const isSelected = Number(omoHolonTemplateState.selectedId || 0) === Number(node.id);
        const propertyCount = Array.isArray(node.properties) ? node.properties.length : 0;
        const childCount = Array.isArray(node.children) ? node.children.length : 0;
        const definedInId = Number(node.definedInId || 0);
        const definedInName = String(node.definedInName || '').trim();
        const isForeignContext = omoHolonTemplateGetScope() !== 'contextual'
            && definedInId > 0
            && definedInId !== Number(omoHolonTemplateState.data.contextHolonId || 0);

        html += '<li class="omo-template-tree__item">';
        html += '<button type="button" class="omo-template-tree__button' + (isSelected ? ' is-selected' : '') + '" data-template-select="' + Number(node.id) + '" data-template-context-id="' + definedInId + '">';
        html += '  <span class="omo-template-tree__name">' + omoHolonTemplateEscapeHtml(node.name) + '</span>';
        html += '  <span class="omo-template-tree__meta-row">';
        html += '      <span class="omo-template-chip omo-template-chip--accent">' + omoHolonTemplateEscapeHtml(node.typeLabel || '') + '</span>';
        html += '      <span class="omo-template-chip">' + propertyCount + ' ' + omoHolonTemplateEscapeHtml(propertyCount > 1 ? (omoHolonTemplateTexts.summaryPropertyOther || '') : (omoHolonTemplateTexts.summaryPropertyOne || '')) + '</span>';
        if (childCount > 0) {
            html += '  <span class="omo-template-chip">' + childCount + ' ' + omoHolonTemplateEscapeHtml(childCount > 1 ? (omoHolonTemplateTexts.summarySubmodelOther || '') : (omoHolonTemplateTexts.summarySubmodelOne || '')) + '</span>';
        }
        if (isForeignContext && definedInName !== '') {
            html += '  <span class="omo-template-chip">' + omoHolonTemplateEscapeHtml(definedInName) + '</span>';
        }
        html += '  </span>';
        html += '</button>';

        if (node.children && node.children.length) {
            html += omoHolonTemplateRenderTreeNodes(node.children);
        }

        html += '</li>';
    });
    html += '</ul>';

    return html;
}

function omoHolonTemplateRenderTree() {
    omoHolonTemplateElements.tree.innerHTML = omoHolonTemplateRenderTreeNodes(omoHolonTemplateState.data.templates || []);
    omoHolonTemplateElements.newChildButton.disabled = !omoHolonTemplateState.selectedId;
    omoHolonTemplateRenderSummary();
}

function omoHolonTemplateFillTypeOptions(selectedTypeId) {
    omoHolonTemplateElements.type.innerHTML = '';
    (omoHolonTemplateState.data.types || []).forEach(function (type) {
        const option = document.createElement('option');
        option.value = Number(type.id);
        option.textContent = type.name;
        option.selected = Number(selectedTypeId || 0) === Number(type.id);
        omoHolonTemplateElements.type.appendChild(option);
    });
}

function omoHolonTemplateBuildParentOptions(selectedParentId, currentTemplateId) {
    const options = [{
        id: Number(omoHolonTemplateState.data.rootHolonId || 0),
        label: omoHolonTemplateTexts.treeRoot || ''
    }];

    function walk(nodes, prefix) {
        (nodes || []).forEach(function (node) {
            if (Number(node.id) !== Number(currentTemplateId || 0)) {
                options.push({
                    id: Number(node.id),
                    label: prefix + node.name
                });
                walk(node.children || [], prefix + '> ');
            }
        });
    }

    walk(omoHolonTemplateState.data.templates || [], '');

    omoHolonTemplateElements.parent.innerHTML = '';
    options.forEach(function (item) {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.label;
        option.selected = Number(selectedParentId || 0) === Number(item.id);
        omoHolonTemplateElements.parent.appendChild(option);
    });

    const parentTargetValue = String(Number(selectedParentId || omoHolonTemplateState.data.rootHolonId || 0));
    const hasMatchingParentOption = Array.from(omoHolonTemplateElements.parent.options).some(function (option) {
        return String(option.value) === parentTargetValue;
    });
    omoHolonTemplateElements.parent.value = hasMatchingParentOption
        ? parentTargetValue
        : String(Number(omoHolonTemplateState.data.rootHolonId || 0));
}

function omoHolonTemplateBuildParentOptions(selectedParentId, currentTemplateId) {
    const options = [{
        id: 0,
        label: 'Aucun heritage direct'
    }];

    (omoHolonTemplateState.data.templateCatalog || []).forEach(function (template) {
        if (Number(template.id) === Number(currentTemplateId || 0)) {
            return;
        }

        const contextSuffix = Number(template.definedInId || 0) > 0
            && Number(template.definedInId || 0) !== Number(omoHolonTemplateState.data.contextHolonId || 0)
            && String(template.definedInName || '').trim() !== ''
            ? ' > ' + template.definedInName
            : '';

        options.push({
            id: Number(template.id),
            label: String(template.name || '') + contextSuffix
        });
    });

    omoHolonTemplateElements.parent.innerHTML = '';
    options.forEach(function (item) {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.label;
        option.selected = Number(selectedParentId || 0) === Number(item.id);
        omoHolonTemplateElements.parent.appendChild(option);
    });

    const parentTargetValue = String(Number(selectedParentId || 0));
    const hasMatchingParentOption = Array.from(omoHolonTemplateElements.parent.options).some(function (option) {
        return String(option.value) === parentTargetValue;
    });
    omoHolonTemplateElements.parent.value = hasMatchingParentOption
        ? parentTargetValue
        : '0';
}

function omoHolonTemplateGetValueHelpText(formatId) {
    const numericFormatId = Number(formatId || 0);
    if (numericFormatId === 3) {
        return 'Laissez vide pour ne rien imposer. Utilisez un nombre entier ou decimal.';
    }
    if (numericFormatId === 4) {
        return 'Laissez vide pour ne rien imposer. La date sera heritee au format AAAA-MM-JJ.';
    }
    if (numericFormatId === 2) {
        return 'Laissez vide pour ne rien imposer. Utilisez une ligne par element si besoin.';
    }
    return 'Si cette valeur reste vide, chaque holon derive pourra definir librement son contenu.';
}

function omoHolonTemplateRenderValueInputHtml(formatId, value) {
    const numericFormatId = Number(formatId || 0);
    const safeValue = value !== undefined && value !== null ? String(value) : '';

    if (numericFormatId === 3) {
        return '<input type="number" step="any" class="omo-template-property__value" value="' + omoHolonTemplateEscapeHtml(safeValue) + '" placeholder="Ex.: 42">';
    }

    if (numericFormatId === 4) {
        return '<input type="date" class="omo-template-property__value" value="' + omoHolonTemplateEscapeHtml(safeValue) + '">';
    }

    return '<textarea class="omo-template-property__value" rows="4" placeholder="Laissez vide pour ne rien imposer.">' + omoHolonTemplateEscapeHtml(safeValue) + '</textarea>';
}

function omoHolonTemplateCreatePropertyRow(property) {
    const row = document.createElement('div');
    row.className = 'omo-template-property';
    row.dataset.propertyId = Number(property && property.id ? property.id : 0);
    row.dataset.holonPropertyId = Number(property && property.holonPropertyId ? property.holonPropertyId : 0);
    const propertyValue = property && property.value !== undefined && property.value !== null ? property.value : '';
    const propertyFormatId = Number(property && property.formatId ? property.formatId : 0);

    const formatOptions = (omoHolonTemplateState.data.formats || []).map(function (format) {
        const selected = propertyFormatId === Number(format.id) ? ' selected' : '';
        return '<option value="' + Number(format.id) + '"' + selected + '>' + omoHolonTemplateEscapeHtml(format.name) + '</option>';
    }).join('');

    row.innerHTML = ''
        + '<div class="omo-template-property__index"></div>'
        + '<div class="omo-template-property__body">'
        + '  <div class="omo-template-property__main">'
        + '      <label class="omo-field">'
        + '          <span>Nom</span>'
        + '          <input type="text" class="omo-template-property__name" maxlength="255" value="' + omoHolonTemplateEscapeHtml(property && property.name ? property.name : '') + '" placeholder="Ex.: Raison d etre">'
        + '      </label>'
        + '      <label class="omo-field">'
        + '          <span>Format</span>'
        + '          <select class="omo-template-property__format">' + formatOptions + '</select>'
        + '      </label>'
        + '  </div>'
        + '  <' + ([5, 7].indexOf(propertyFormatId) >= 0 ? 'div' : 'label') + ' class="omo-field omo-template-property__value-field">'
        + '      <span>Valeur heritee par defaut</span>'
        + '      <div class="omo-template-property__value-control">' + omoHolonTemplateRenderValueInputHtml(propertyFormatId, propertyValue) + '</div>'
        + '      <small>Si cette valeur reste vide, chaque holon derive pourra definir librement son contenu.</small>'
        + '  </' + ([5, 7].indexOf(propertyFormatId) >= 0 ? 'div' : 'label') + '>'
        + '  <div class="omo-template-property__actions">'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="-1">Monter</button>'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="1">Descendre</button>'
        + '      <button type="button" class="omo-button omo-button--danger" data-property-remove="1">Retirer</button>'
        + '  </div>'
        + '</div>';

    return row;
}

function omoHolonTemplateParseStoredListValue(value) {
    const rawValue = value !== undefined && value !== null ? String(value) : '';
    if (!rawValue.trim()) {
        return [];
    }

    try {
        const decoded = JSON.parse(rawValue);
        return Array.isArray(decoded) ? decoded : [];
    } catch (error) {
        return rawValue.split(/\r\n|\r|\n/).map(function (item) {
            return item.trim();
        }).filter(Boolean);
    }
}

function omoHolonTemplateGetListInputType(listItemType) {
    if (String(listItemType || 'text') === 'number') {
        return 'number';
    }
    if (String(listItemType || 'text') === 'date') {
        return 'date';
    }
    return 'text';
}

function omoHolonTemplateNormalizeDetailedListItem(item) {
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

function omoHolonTemplateRenderSimpleListRow(listItemType, value, disabled) {
    if (String(listItemType || 'text') === 'detail') {
        const detailItem = omoHolonTemplateNormalizeDetailedListItem(value);
        const disabledAttribute = disabled ? ' disabled' : '';

        return ''
            + '<div class="omo-template-list-input__row omo-template-list-input__row--detail">'
            + '  <div class="omo-template-list-input__detail-fields">'
            + '      <input type="text" class="omo-template-property__value-item omo-template-property__value-item--detail-title" value="' + omoHolonTemplateEscapeHtml(detailItem.title) + '" placeholder="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyPlaceholderTitle || '') + '"' + disabledAttribute + '>'
            + '      <textarea class="omo-template-property__value-item omo-template-property__value-item--detail-description" rows="3" placeholder="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyPlaceholderDescription || '') + '"' + disabledAttribute + '>' + omoHolonTemplateEscapeHtml(detailItem.description) + '</textarea>'
            + '  </div>'
            + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__move" data-list-move="-1" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyMoveUp || '') + '"' + disabledAttribute + '>&uarr;</button>'
            + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__move" data-list-move="1" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyMoveDown || '') + '"' + disabledAttribute + '>&darr;</button>'
            + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__remove" data-list-remove="1" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyRemove || '') + '"' + disabledAttribute + '>&times;</button>'
            + '</div>';
    }

    const inputType = omoHolonTemplateGetListInputType(listItemType);
    const safeValue = value !== undefined && value !== null ? String(value) : '';
    const stepAttribute = inputType === 'number' ? ' step="any"' : '';
    const disabledAttribute = disabled ? ' disabled' : '';

    return ''
        + '<div class="omo-template-list-input__row">'
        + '  <input type="' + inputType + '" class="omo-template-property__value-item" value="' + omoHolonTemplateEscapeHtml(safeValue) + '"' + stepAttribute + disabledAttribute + '>'
        + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__move" data-list-move="-1" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyMoveUp || '') + '"' + disabledAttribute + '>&uarr;</button>'
        + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__move" data-list-move="1" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyMoveDown || '') + '"' + disabledAttribute + '>&darr;</button>'
        + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__remove" data-list-remove="1" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyRemove || '') + '"' + disabledAttribute + '>&times;</button>'
        + '</div>';
}

function omoHolonTemplateRenderSimpleListInput(listItemType, values, disabled) {
    const normalizedValues = Array.isArray(values) && values.length ? values : [''];
    const disabledAttribute = disabled ? ' disabled' : '';

    return ''
        + '<div class="omo-template-list-input" data-list-item-type="' + omoHolonTemplateEscapeHtml(String(listItemType || 'text')) + '">'
        + '  <div class="omo-template-list-input__items">'
        + normalizedValues.map(function (item) {
            return omoHolonTemplateRenderSimpleListRow(listItemType, item, disabled);
        }).join('')
        + '  </div>'
        + '  <button type="button" class="omo-button omo-button--secondary omo-template-list-input__add" data-list-add="1"' + disabledAttribute + '>+</button>'
        + '</div>';
}

function omoHolonTemplateGetAuthorityCatalog() {
    return Array.isArray(omoHolonTemplateState.data.authorityCatalog)
        ? omoHolonTemplateState.data.authorityCatalog
        : [];
}

function omoHolonTemplateGetAuthorityParentCatalog() {
    return Array.isArray(omoHolonTemplateState.data.authorityParentCatalog)
        ? omoHolonTemplateState.data.authorityParentCatalog
        : [];
}

function omoHolonTemplateCanCreateRootAuthority() {
    return Boolean(omoHolonTemplateState.data.authorityCanCreateRoot);
}

function omoHolonTemplateGetAuthorityId(item) {
    return item && typeof item === 'object' && !Array.isArray(item)
        ? Number(item.id || 0)
        : Number(item || 0);
}

function omoHolonTemplateGetAuthorityDeletionImpact(authorityId, authorityDisposition, childrenDisposition) {
    const catalog = omoHolonTemplateGetAuthorityCatalog();
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

function omoHolonTemplateFormatAuthorityDeletionCount(count, singular, plural) {
    return ' (' + String(count) + ' ' + (count === 1 ? singular : plural) + ')';
}

function omoHolonTemplateUpdateAuthorityDeletionCounts(authorityRow) {
    const authorityId = Number(authorityRow && authorityRow.getAttribute('data-authority-id') || 0);
    if (authorityId <= 0) {
        return;
    }
    const getChoice = function (name, fallback) {
        const checked = authorityRow.querySelector('[data-authority-deletion-choice="' + name + '"]:checked');
        return checked ? String(checked.value || fallback) : fallback;
    };
    const impact = omoHolonTemplateGetAuthorityDeletionImpact(
        authorityId,
        getChoice('authority', 'reassign'),
        getChoice('children', 'reassign')
    );
    const counts = {
        authority: omoHolonTemplateFormatAuthorityDeletionCount(1, 'autorite', 'autorites'),
        children: omoHolonTemplateFormatAuthorityDeletionCount(impact.descendants, 'sous-autorite', 'sous-autorites'),
        rules: omoHolonTemplateFormatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees')
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

function omoHolonTemplateGetAuthorityEntryPayload(authorityRow) {
    const authorityId = Number(authorityRow.getAttribute('data-authority-id') || 0);
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

    const labelField = authorityRow.querySelector('.omo-template-authority__label');
    const parentField = authorityRow.querySelector('.omo-template-authority__parent');
    const descriptionField = authorityRow.querySelector('.omo-template-authority__description');
    const delegationField = authorityRow.querySelector('.omo-template-authority__delegation');
    if (authorityId > 0 && !labelField && !parentField && !descriptionField) {
        return { id: authorityId };
    }

    const label = String(labelField && labelField.value ? labelField.value : '').trim();
    const parentId = Number(parentField && parentField.value ? parentField.value : 0);
    const description = String(descriptionField && descriptionField.value ? descriptionField.value : '').trim();
    const delegationMode = String(delegationField && delegationField.value ? delegationField.value : 'partial');
    if (authorityId > 0) {
        return { id: authorityId, label: label, parentId: parentId, description: description };
    }

    if (delegationMode === 'complete') {
        return parentId > 0 ? { parentId: parentId, delegationMode: 'complete' } : null;
    }
    return label !== '' || parentId > 0 || description !== '' ? { label: label, parentId: parentId, description: description, delegationMode: 'partial' } : null;
}

function omoHolonTemplateRenderAuthorityDeletionChoices(authorityId, draft) {
    const plan = draft && draft.deletionPlan && typeof draft.deletionPlan === 'object' ? draft.deletionPlan : {};
    const checked = function (name, value, fallback) {
        return String(plan[name] || fallback) === value ? ' checked' : '';
    };
    const prefix = 'template-authority-delete-' + String(authorityId);
    const impact = omoHolonTemplateGetAuthorityDeletionImpact(authorityId, String(plan.authority || 'reassign'), String(plan.children || 'reassign'));
    return ''
        + '<div class="omo-template-authority__deletion" data-authority-deletion-options>'
        + '  <p>Choisissez ce qui doit etre conserve avant validation.</p>'
        + '  <fieldset><legend>Cette autorite</legend>'
        + '    <label><input type="radio" name="' + prefix + '-authority" value="delete" data-authority-deletion-choice="authority"' + checked('authority', 'delete', 'reassign') + '> Supprimer definitivement</label>'
        + '    <label><input type="radio" name="' + prefix + '-authority" value="reassign" data-authority-deletion-choice="authority"' + checked('authority', 'reassign', 'reassign') + '> Remonter au holon parent</label>'
        + '  </fieldset>'
        + (impact.descendants > 0 ? '  <fieldset data-authority-deletion-group="children"><legend>Sous-autorites</legend>'
        + '    <label><input type="radio" name="' + prefix + '-children" value="delete" data-authority-deletion-choice="children"' + checked('children', 'delete', 'reassign') + '> Supprimer les branches<span data-authority-deletion-count="children">' + omoHolonTemplateFormatAuthorityDeletionCount(impact.descendants, 'sous-autorite', 'sous-autorites') + '</span></label>'
        + '    <label><input type="radio" name="' + prefix + '-children" value="reassign" data-authority-deletion-choice="children"' + checked('children', 'reassign', 'reassign') + '> Remonter les branches au holon parent<span data-authority-deletion-count="children">' + omoHolonTemplateFormatAuthorityDeletionCount(impact.descendants, 'sous-autorite', 'sous-autorites') + '</span></label>'
        + '  </fieldset>' : '')
        + '  <fieldset data-authority-deletion-group="rules"' + (impact.rules <= 0 ? ' hidden' : '') + '><legend>Regles des autorites supprimees</legend>'
        + '    <label><input type="radio" name="' + prefix + '-rules" value="delete" data-authority-deletion-choice="rules"' + checked('rules', 'delete', 'reassign') + '> Supprimer les regles<span data-authority-deletion-count="rules">' + omoHolonTemplateFormatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees') + '</span></label>'
        + '    <label><input type="radio" name="' + prefix + '-rules" value="reassign" data-authority-deletion-choice="rules"' + checked('rules', 'reassign', 'reassign') + '> Remonter a l autorite la plus proche et demander une revue sous 2 mois<span data-authority-deletion-count="rules">' + omoHolonTemplateFormatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees') + '</span></label>'
        + '  </fieldset>'
        + '</div>';
}

function omoHolonTemplateRenderAuthorityRow(value) {
    const authorityId = omoHolonTemplateGetAuthorityId(value);
    const authority = authorityId > 0 ? omoHolonTemplateGetAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === authorityId;
    }) : null;
    const draft = value && typeof value === 'object' && !Array.isArray(value) ? value : {};
    if (authorityId > 0 && !draft.editing) {
        const label = authority ? String(authority.label || '') : 'Autorite #' + String(authorityId);
        const details = authority ? String(authority.pathLabel || authority.holonLabel || '') : '';
        const labelMarkup = authority && authority.isShell ? '<em>' + omoHolonTemplateEscapeHtml(label) + '</em>' : '<strong>' + omoHolonTemplateEscapeHtml(label) + '</strong>';
        return ''
            + '<div class="omo-template-authority__row omo-template-authority__row--existing" data-authority-entry data-authority-id="' + authorityId + '">'
            + '  <button type="button" class="omo-template-authority__edit" data-authority-edit="1">' + labelMarkup + (details ? '<small>' + omoHolonTemplateEscapeHtml(details) + '</small>' : '') + '</button>'
            + '  <button type="button" class="omo-button omo-button--ghost" data-authority-delete="1" aria-label="Supprimer l autorite">&times;</button>'
            + omoHolonTemplateRenderAuthorityDeletionChoices(authorityId, draft)
            + '</div>';
    }

    const parentId = Number(draft.parentId || (authority ? authority.parentId : 0) || 0);
    const label = String(draft.label || (authority ? authority.label : '') || '');
    const description = String(draft.description || (authority ? authority.description : '') || '');
    const requestedDelegationMode = String(draft.delegationMode || 'partial');
    const selectedParent = omoHolonTemplateGetAuthorityParentCatalog().find(function (entry) { return Number(entry.id || 0) === parentId; }) || null;
    const partialAllowed = !(selectedParent && selectedParent.isShell);
    const delegationMode = !partialAllowed && requestedDelegationMode !== 'complete' ? 'complete' : requestedDelegationMode;
    const parentOptions = omoHolonTemplateGetAuthorityParentCatalog().map(function (authority) {
        if (Number(authority.id || 0) === authorityId) {
            return '';
        }
        const selected = Number(authority.id || 0) === parentId ? ' selected' : '';
        const path = String(authority.label || '');
        return '<option value="' + Number(authority.id || 0) + '"' + selected + '>'
            + omoHolonTemplateEscapeHtml(path)
            + '</option>';
    }).join('');
    const rootAuthoritySelected = omoHolonTemplateCanCreateRootAuthority() && parentId <= 0;
    const rootOption = omoHolonTemplateCanCreateRootAuthority()
        ? '<option value="0"' + (parentId <= 0 ? ' selected' : '') + '>Sans racine</option>'
        : '<option value="0">Autorite parente</option>';

    const delegationField = authorityId > 0 || rootAuthoritySelected ? '' : '      <select class="omo-template-authority__delegation"' + (parentId <= 0 ? ' disabled' : '') + '>'
        + '<option value="partial"' + (delegationMode !== 'complete' ? ' selected' : '') + (partialAllowed ? '' : ' disabled') + '>Delegation partielle</option>'
        + '<option value="complete"' + (delegationMode === 'complete' ? ' selected' : '') + '>Delegation complete</option></select>';
    const authorityDetails = (authorityId > 0 || parentId > 0 || rootAuthoritySelected) && (authorityId > 0 || delegationMode !== 'complete')
        ? '      <input type="text" class="omo-template-authority__label" value="' + omoHolonTemplateEscapeHtml(label) + '" placeholder="Nouvelle autorite">'
            + '      <textarea class="omo-template-authority__description" rows="3" placeholder="Description">' + omoHolonTemplateEscapeHtml(description) + '</textarea>'
        : parentId > 0 && delegationMode === 'complete'
            ? '      <div class="omo-template-authority__complete-note">L autorite parente sera deleguee completement.</div>'
            : '';
    return ''
        + '<div class="omo-template-authority__row" data-authority-entry' + (authorityId > 0 ? ' data-authority-id="' + authorityId + '"' : '') + '>'
        + '  <div class="omo-template-authority__fields">'
        + '      <select class="omo-template-authority__parent">' + rootOption + parentOptions + '</select>'
        + delegationField + authorityDetails
        + '  </div>'
        + '  <button type="button" class="omo-button omo-button--ghost" ' + (authorityId > 0 ? 'data-authority-delete="1" aria-label="Supprimer l autorite"' : 'data-authority-remove="1" aria-label="Retirer"') + '>&times;</button>'
        + (authorityId > 0 ? omoHolonTemplateRenderAuthorityDeletionChoices(authorityId, draft) : '')
        + '</div>';
}

function omoHolonTemplateRenderAuthorityInput(values, disabled) {
    if (!omoHolonTemplateCanCreateRootAuthority()) {
        return '<div class="omo-template-property__empty-note">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyHelpListAuthority || '') + '</div>';
    }

    const rows = Array.isArray(values) && values.length ? values : [];
    const disabledAttribute = disabled ? ' disabled' : '';
    return ''
        + '<div class="omo-template-authority">'
        + '  <div class="omo-template-authority__items">' + rows.map(omoHolonTemplateRenderAuthorityRow).join('') + '</div>'
        + '  <button type="button" class="omo-button omo-button--secondary" data-authority-add="1"' + disabledAttribute + '>Ajouter une autorite</button>'
        + '</div>';
}

if (window.genericMultilineListPaste && typeof window.genericMultilineListPaste.attach === 'function') {
    window.genericMultilineListPaste.attach(omoHolonTemplatePageRoot, {
        inputSelector: '.omo-template-property__value-item',
        rowSelector: '.omo-template-list-input__row',
        listSelector: '.omo-template-list-input',
        itemsSelector: '.omo-template-list-input__items',
        renderRow: function (listItemType, value) {
            return omoHolonTemplateRenderSimpleListRow(listItemType, value, false);
        }
    });
}

function omoHolonTemplateNormalizeProperty(property) {
    const source = property || {};
    const normalized = Object.assign({
        id: 0,
        holonPropertyId: 0,
        name: '',
        formatId: 1,
        value: '',
        inheritedValue: '',
        listItemType: 'text',
        listHolonTypeIds: [],
        mandatory: false,
        locked: false,
        inheritedMandatory: false,
        inheritedLocked: false,
        effectiveMandatory: false,
        effectiveLocked: false,
        isInherited: false,
        isLocal: true,
        canDelete: true,
        canEditValue: true
    }, source);

    normalized.formatId = Number(normalized.formatId || 1);
    normalized.listItemType = String(normalized.listItemType || 'text');
    if ((omoHolonTemplateState.data.listItemTypes || []).every(function (itemType) {
        return String(itemType.id || '') !== normalized.listItemType;
    })) {
        normalized.listItemType = 'text';
        normalized.listHolonTypeIds = [];
    }
    normalized.mandatory = Boolean(normalized.mandatory);
    normalized.locked = Boolean(normalized.locked);
    normalized.inheritedMandatory = Boolean(normalized.inheritedMandatory);
    normalized.inheritedLocked = Boolean(normalized.inheritedLocked);
    normalized.effectiveMandatory = Object.prototype.hasOwnProperty.call(source, 'effectiveMandatory')
        ? Boolean(source.effectiveMandatory)
        : (normalized.inheritedMandatory || normalized.mandatory);
    normalized.effectiveLocked = Object.prototype.hasOwnProperty.call(source, 'effectiveLocked')
        ? Boolean(source.effectiveLocked)
        : (normalized.inheritedLocked || normalized.locked);
    normalized.isInherited = Boolean(normalized.isInherited);
    normalized.isLocal = Boolean(normalized.isLocal);
    normalized.canDelete = normalized.canDelete !== false;
    normalized.canEditValue = normalized.canEditValue !== false;
    normalized.listHolonTypeIds = Array.isArray(normalized.listHolonTypeIds)
        ? normalized.listHolonTypeIds.map(function (typeId) { return Number(typeId); }).filter(Boolean)
        : [];

    return normalized;
}

function omoHolonTemplateRenderHtmlPreview(value, className) {
    if (window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.renderPreviewHtml === 'function') {
        return window.omoSimpleHtmlField.renderPreviewHtml(value, className);
    }

    return '<div class="' + omoHolonTemplateEscapeHtml(className || 'omo-template-property__inherited-text') + '">'
        + omoHolonTemplateEscapeHtml(value || '').replace(/\n/g, '<br>')
        + '</div>';
}

function omoHolonTemplateGetListHelpText(property) {
    const listItemType = String(property.listItemType || 'text');
    if (listItemType === 'number') {
        return omoHolonTemplateTexts.propertyHelpListNumber || '';
    }
    if (listItemType === 'date') {
        return omoHolonTemplateTexts.propertyHelpListDate || '';
    }
    if (listItemType === 'detail') {
        return omoHolonTemplateTexts.propertyHelpListDetail || '';
    }
    if (listItemType === 'holon') {
        return omoHolonTemplateTexts.propertyHelpListHolon || '';
    }
    if (listItemType === 'project') {
        return omoHolonTemplateTexts.propertyHelpListProject || '';
    }
    if (listItemType === 'authority') {
        return omoHolonTemplateTexts.propertyHelpListAuthority || '';
    }
    return omoHolonTemplateTexts.propertyHelpListText || '';
}

function omoHolonTemplateGetValueHelpText(formatId, property) {
    const numericFormatId = Number(formatId || 0);
    if (numericFormatId === 2) {
        return omoHolonTemplateGetListHelpText(omoHolonTemplateNormalizeProperty(property));
    }
    if (numericFormatId === 3) {
        return omoHolonTemplateTexts.propertyHelpNumber || '';
    }
    if (numericFormatId === 4) {
        return omoHolonTemplateTexts.propertyHelpDate || '';
    }
    if (numericFormatId === 5) {
        return omoHolonTemplateTexts.propertyHelpHtml || '';
    }
    return omoHolonTemplateTexts.propertyHelpDefault || '';
}

function omoHolonTemplateRenderListConfigHtml(property) {
    if ([2, 7].indexOf(Number(property.formatId || 0)) < 0) {
        return '';
    }

    const configDisabled = property.isInherited || !property.canEditValue ? ' disabled' : '';

    const listItemTypeOptions = (omoHolonTemplateState.data.listItemTypes || []).map(function (itemType) {
        const selected = String(property.listItemType || 'text') === String(itemType.id) ? ' selected' : '';
        return '<option value="' + omoHolonTemplateEscapeHtml(itemType.id) + '"' + selected + '>' + omoHolonTemplateEscapeHtml(itemType.name) + '</option>';
    }).join('');

    let holonTypeOptions = '';
    if (String(property.listItemType) === 'holon') {
        holonTypeOptions = (omoHolonTemplateState.data.types || []).map(function (type) {
            const checked = property.listHolonTypeIds.indexOf(Number(type.id)) >= 0 ? ' checked' : '';
            return ''
                + '<label class="omo-template-property__check-option">'
                + '  <input type="checkbox" class="omo-template-property__list-holon-type" value="' + Number(type.id) + '"' + checked + configDisabled + '>'
                + '  <span>' + omoHolonTemplateEscapeHtml(type.name) + '</span>'
                + '</label>';
        }).join('');
    }

    return ''
        + '<div class="omo-template-property__list-options">'
        + '  <label class="omo-field">'
        + '      <span>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyListItemType || '') + '</span>'
        + '      <select class="omo-template-property__list-item-type"' + configDisabled + '>' + listItemTypeOptions + '</select>'
        + '  </label>'
        + (String(property.listItemType) === 'holon'
            ? '  <div class="omo-field omo-template-property__holon-types"><span>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyAllowedHolonTypes || '') + '</span><div class="omo-template-property__check-grid">' + holonTypeOptions + '</div></div>'
            : '')
        + '</div>';
}

function omoHolonTemplateRenderValueInputHtml(property) {
    const normalizedProperty = omoHolonTemplateNormalizeProperty(property);
    const formatId = Number(normalizedProperty.formatId || 0);
    const safeValue = normalizedProperty.value !== undefined && normalizedProperty.value !== null ? String(normalizedProperty.value) : '';
    const valueDisabled = normalizedProperty.canEditValue ? '' : ' disabled';

    if (formatId === 2) {
        const listValues = omoHolonTemplateParseStoredListValue(safeValue);

        if (String(normalizedProperty.listItemType) === 'authority') {
            return omoHolonTemplateRenderAuthorityInput(listValues, !normalizedProperty.canEditValue);
        }

        if (String(normalizedProperty.listItemType) === 'holon') {
            const allowedTypeIds = normalizedProperty.listHolonTypeIds || [];
            const templateOptions = (omoHolonTemplateState.data.templateCatalog || []).filter(function (template) {
                return allowedTypeIds.length === 0 || allowedTypeIds.indexOf(Number(template.typeId)) >= 0;
            });

            if (!templateOptions.length) {
                return '<div class="omo-template-property__empty-note">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyNoTemplateForTypes || '') + '</div>';
            }

            return '<div class="omo-template-property__check-grid">'
                + templateOptions.map(function (template) {
                    const checked = listValues.map(Number).indexOf(Number(template.id)) >= 0 ? ' checked' : '';
                    return ''
                        + '<label class="omo-template-property__check-option">'
                        + '  <input type="checkbox" class="omo-template-property__value omo-template-property__value--holon" value="' + Number(template.id) + '"' + checked + valueDisabled + '>'
                        + '  <span>' + omoHolonTemplateEscapeHtml(template.name) + ' <small>' + omoHolonTemplateEscapeHtml(template.typeLabel || '') + '</small></span>'
                        + '</label>';
                }).join('')
                + '</div>';
        }

        if (String(normalizedProperty.listItemType) === 'project') {
            const projectOptions = omoHolonTemplateState.data.projectCatalog || [];

            if (!projectOptions.length) {
                return '<div class="omo-template-property__empty-note">Aucun projet disponible.</div>';
            }

            return '<div class="omo-template-property__check-grid">'
                + projectOptions.map(function (project) {
                    const checked = listValues.map(Number).indexOf(Number(project.id)) >= 0 ? ' checked' : '';
                    return ''
                        + '<label class="omo-template-property__check-option">'
                        + '  <input type="checkbox" class="omo-template-property__value omo-template-property__value--project" value="' + Number(project.id) + '"' + checked + valueDisabled + '>'
                        + '  <span>' + omoHolonTemplateEscapeHtml(project.title) + (project.holonLabel ? ' <small>' + omoHolonTemplateEscapeHtml(project.holonLabel) + '</small>' : '') + '</span>'
                        + '</label>';
                }).join('')
                + '</div>';
        }

        return omoHolonTemplateRenderSimpleListInput(normalizedProperty.listItemType, listValues, !normalizedProperty.canEditValue);
    }

    if (formatId === 6) {
        let parts = {};
        try { parts = JSON.parse(safeValue) || {}; } catch (error) {}
        return '<input type="text" class="omo-template-property__value-text-html-title" value="' + omoHolonTemplateEscapeHtml(parts.text || '') + '" placeholder="Texte affiche"' + valueDisabled + '>'
            + '<textarea class="omo-template-property__value-text-html-detail" rows="5" placeholder="Detail HTML"' + valueDisabled + '>' + omoHolonTemplateEscapeHtml(parts.detail || '') + '</textarea>';
    }

    if (formatId === 7) {
        let parts = {};
        try { parts = JSON.parse(safeValue) || {}; } catch (error) {}
        const listControl = omoHolonTemplateRenderValueInputHtml(Object.assign({}, normalizedProperty, {
            formatId: 2,
            value: JSON.stringify(Array.isArray(parts.items) ? parts.items : [])
        }));
        return '<div class="omo-template-property__composite-html" data-omo-composite-html="before" data-value="' + omoHolonTemplateEscapeHtml(parts.before || '') + '"></div>'
            + '<div class="omo-template-property__composite-list">' + listControl + '</div>'
            + '<div class="omo-template-property__composite-html" data-omo-composite-html="after" data-value="' + omoHolonTemplateEscapeHtml(parts.after || '') + '"></div>';
    }

    if (formatId === 3) {
        return '<input type="number" step="any" class="omo-template-property__value" value="' + omoHolonTemplateEscapeHtml(safeValue) + '" placeholder="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyPlaceholderNumber || '') + '"' + valueDisabled + '>';
    }

    if (formatId === 4) {
        return '<input type="date" class="omo-template-property__value" value="' + omoHolonTemplateEscapeHtml(safeValue) + '"' + valueDisabled + '>';
    }

    if (formatId === 5) {
        return '<div class="omo-template-property__html-editor"></div>';
    }

    return '<textarea class="omo-template-property__value" rows="4" placeholder="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyPlaceholderEmpty || '') + '"' + valueDisabled + '>' + omoHolonTemplateEscapeHtml(safeValue) + '</textarea>';
}

function omoHolonTemplateRenderInheritedValueHtml(property) {
    const normalizedProperty = omoHolonTemplateNormalizeProperty(property);
    const inheritedValue = normalizedProperty.inheritedValue !== undefined && normalizedProperty.inheritedValue !== null
        ? String(normalizedProperty.inheritedValue)
        : '';

    if (!inheritedValue.trim()) {
        return '';
    }

    const previewHtml = omoHolonTemplateRenderValueInputHtml(Object.assign({}, normalizedProperty, {
        value: inheritedValue,
        canEditValue: false
    }))
        .replace(/omo-template-property__value-item/g, 'omo-template-property__inherited-item')
        .replace(/omo-template-property__value--holon/g, 'omo-template-property__inherited-holon')
        .replace(/omo-template-property__value/g, 'omo-template-property__inherited-value');

    return ''
        + '<div class="omo-template-property__inherited-block">'
        + '  <div class="omo-template-property__inherited-label">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyValueInherited || '') + '</div>'
        + '  <div class="omo-template-property__inherited-view">'
        +       previewHtml
        + '  </div>'
        + '</div>';
}

function omoHolonTemplateCreatePropertyRow(property) {
    const normalizedProperty = omoHolonTemplateNormalizeProperty(property);
    const row = document.createElement('div');
    row.className = 'omo-template-property';
    row.dataset.propertyId = Number(normalizedProperty.id || 0);
    row.dataset.holonPropertyId = Number(normalizedProperty.holonPropertyId || 0);
    row.dataset.isInherited = normalizedProperty.isInherited ? '1' : '0';

    const formatOptions = (omoHolonTemplateState.data.formats || []).map(function (format) {
        const selected = Number(normalizedProperty.formatId || 0) === Number(format.id) ? ' selected' : '';
        return '<option value="' + Number(format.id) + '"' + selected + '>' + omoHolonTemplateEscapeHtml(format.name) + '</option>';
    }).join('');
    const structureDisabled = normalizedProperty.isInherited ? ' disabled' : '';
    const flagsDisabled = normalizedProperty.isInherited ? ' disabled' : '';
    const mandatoryChecked = normalizedProperty.mandatory ? ' checked' : '';
    const lockedChecked = normalizedProperty.locked ? ' checked' : '';
    const removeDisabled = normalizedProperty.canDelete ? '' : ' disabled';
    const removeLabel = normalizedProperty.isInherited
        ? (omoHolonTemplateTexts.propertyExclude || '')
        : (omoHolonTemplateTexts.propertyRemove || '');
    const originBadge = normalizedProperty.isInherited
        ? '<span class="omo-template-chip">Heritee</span>'
        : '<span class="omo-template-chip omo-template-chip--accent">Locale</span>';

    row.innerHTML = ''
        + '<div class="omo-template-property__index"></div>'
        + '<div class="omo-template-property__body">'
        + '  <div class="omo-template-property__main">'
        + '      <label class="omo-field">'
        + '          <span>Nom</span>'
        + '          <input type="text" class="omo-template-property__name" maxlength="255" value="' + omoHolonTemplateEscapeHtml(normalizedProperty.name || '') + '" placeholder="Ex.: Raison d etre">'
        + '      </label>'
        + '      <label class="omo-field">'
        + '          <span>Format</span>'
        + '          <select class="omo-template-property__format">' + formatOptions + '</select>'
        + '      </label>'
        + '  </div>'
        + omoHolonTemplateRenderListConfigHtml(normalizedProperty)
        + '  <' + ([5, 7].indexOf(Number(normalizedProperty.formatId || 0)) >= 0 ? 'div' : 'label') + ' class="omo-field omo-template-property__value-field">'
        + '      <span>Valeur heritee par defaut</span>'
        + '      <div class="omo-template-property__value-control">' + omoHolonTemplateRenderValueInputHtml(normalizedProperty) + '</div>'
        + '      <small class="omo-template-property__value-help">' + omoHolonTemplateEscapeHtml(omoHolonTemplateGetValueHelpText(normalizedProperty.formatId, normalizedProperty)) + '</small>'
        + '  </' + ([5, 7].indexOf(Number(normalizedProperty.formatId || 0)) >= 0 ? 'div' : 'label') + '>'
        + '  <div class="omo-template-property__actions">'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="-1">Monter</button>'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="1">Descendre</button>'
        + '      <button type="button" class="omo-button omo-button--danger" data-property-remove="1">Retirer</button>'
        + '  </div>'
        + '</div>';

    return row;
}

function omoHolonTemplateRefreshPropertyIndexes() {
    Array.from(omoHolonTemplateElements.properties.querySelectorAll('.omo-template-property')).forEach(function (row, index) {
        const badge = row.querySelector('.omo-template-property__index');
        if (badge) {
            badge.textContent = 'P' + String(index + 1);
        }
    });
}

function omoHolonTemplateSerializePropertyValue(row, formatId, listItemType) {
    const htmlFieldHost = row.querySelector('[data-omo-html-field="1"]');
    if (Number(formatId || 0) === 5 && htmlFieldHost && htmlFieldHost.__omoSimpleHtmlField && typeof htmlFieldHost.__omoSimpleHtmlField.getValue === 'function') {
        return String(htmlFieldHost.__omoSimpleHtmlField.getValue() || '');
    }

    if (Number(formatId || 0) === 2) {
        if (String(listItemType || 'text') === 'authority') {
            const items = Array.from(row.querySelectorAll('[data-authority-entry]')).map(omoHolonTemplateGetAuthorityEntryPayload).filter(Boolean);
            return items.length ? JSON.stringify(items) : '';
        }

        if (String(listItemType || 'text') === 'holon') {
            const selectedIds = Array.from(row.querySelectorAll('.omo-template-property__value--holon:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        if (String(listItemType || 'text') === 'project') {
            const selectedIds = Array.from(row.querySelectorAll('.omo-template-property__value--project:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        if (String(listItemType || 'text') === 'detail') {
            const items = Array.from(row.querySelectorAll('.omo-template-list-input__row--detail')).map(function (detailRow) {
                const titleField = detailRow.querySelector('.omo-template-property__value-item--detail-title');
                const descriptionField = detailRow.querySelector('.omo-template-property__value-item--detail-description');
                const item = {
                    title: String(titleField && titleField.value ? titleField.value : '').trim(),
                    description: String(descriptionField && descriptionField.value ? descriptionField.value : '').trim()
                };

                return item.title !== '' || item.description !== '' ? item : null;
            }).filter(Boolean);

            return items.length ? JSON.stringify(items) : '';
        }

        const items = Array.from(row.querySelectorAll('.omo-template-property__value-item')).map(function (input) {
            return String(input.value || '').trim();
        }).filter(Boolean);
        return items.length ? JSON.stringify(items) : '';
    }

    if (Number(formatId || 0) === 6) {
        return JSON.stringify({
            text: String((row.querySelector('.omo-template-property__value-text-html-title') || {}).value || '').trim(),
            detail: String((row.querySelector('.omo-template-property__value-text-html-detail') || {}).value || '')
        });
    }

    if (Number(formatId || 0) === 7) {
        let items = [];
        if (String(listItemType || 'text') === 'authority') {
            items = Array.from(row.querySelectorAll('[data-authority-entry]')).map(omoHolonTemplateGetAuthorityEntryPayload).filter(Boolean);
        } else if (String(listItemType || 'text') === 'holon' || String(listItemType || 'text') === 'project') {
            const modifier = String(listItemType || 'text') === 'holon' ? 'holon' : 'project';
            items = Array.from(row.querySelectorAll('.omo-template-property__value--' + modifier + ':checked')).map(function (input) { return Number(input.value || 0); }).filter(Boolean);
        } else if (String(listItemType || 'text') === 'detail') {
            items = Array.from(row.querySelectorAll('.omo-template-list-input__row--detail')).map(function (detailRow) {
                return { title: String((detailRow.querySelector('.omo-template-property__value-item--detail-title') || {}).value || '').trim(), description: String((detailRow.querySelector('.omo-template-property__value-item--detail-description') || {}).value || '').trim() };
            }).filter(function (item) { return item.title || item.description; });
        } else {
            items = Array.from(row.querySelectorAll('.omo-template-property__value-item')).map(function (input) { return String(input.value || '').trim(); }).filter(Boolean);
        }
        const beforeHost = row.querySelector('[data-omo-composite-html="before"]');
        const afterHost = row.querySelector('[data-omo-composite-html="after"]');
        return JSON.stringify({
            before: beforeHost && beforeHost.__omoSimpleHtmlField ? String(beforeHost.__omoSimpleHtmlField.getValue() || '') : '',
            items: items,
            after: afterHost && afterHost.__omoSimpleHtmlField ? String(afterHost.__omoSimpleHtmlField.getValue() || '') : ''
        });
    }

    const valueField = row.querySelector('.omo-template-property__value');
    return valueField ? valueField.value : '';
}

function omoHolonTemplateReadPropertyState(row) {
    const formatId = Number((row.querySelector('.omo-template-property__format') || {}).value || 0);
    const listItemTypeField = row.querySelector('.omo-template-property__list-item-type');
    const listItemType = listItemTypeField ? String(listItemTypeField.value || 'text') : 'text';
    const listHolonTypeIds = Array.from(row.querySelectorAll('.omo-template-property__list-holon-type:checked')).map(function (input) {
        return Number(input.value || 0);
    }).filter(Boolean);

    return {
        id: Number(row.dataset.propertyId || 0),
        holonPropertyId: Number(row.dataset.holonPropertyId || 0),
        name: (row.querySelector('.omo-template-property__name') || {}).value || '',
        formatId: formatId,
        listItemType: listItemType,
        listHolonTypeIds: listHolonTypeIds,
        value: omoHolonTemplateSerializePropertyValue(row, formatId, listItemType)
    };
}

function omoHolonTemplateRenderPropertyMetaHtml(property) {
    const originBadge = property.isInherited
        ? '<span class="omo-template-chip">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyOriginInherited || '') + '</span>'
        : '<span class="omo-template-chip omo-template-chip--accent">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyOriginLocal || '') + '</span>';

    if (omoHolonTemplateIsHolonDefinitionMode()) {
        return ''
            + '<div class="omo-template-property__meta">'
            + '  <div class="omo-template-property__origin">' + originBadge + '</div>'
            + '</div>';
    }

    const mandatoryDisabled = property.inheritedMandatory || !property.canEditValue ? ' disabled' : '';
    const lockedDisabled = property.inheritedLocked || !property.canEditValue ? ' disabled' : '';

    return ''
        + '<div class="omo-template-property__meta">'
        + '  <div class="omo-template-property__origin">' + originBadge + '</div>'
        + '  <label class="omo-template-property__toggle"><input type="checkbox" class="omo-template-property__mandatory"' + (property.effectiveMandatory ? ' checked' : '') + mandatoryDisabled + '> <span>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyToggleMandatory || '') + '</span></label>'
        + '  <label class="omo-template-property__toggle"><input type="checkbox" class="omo-template-property__locked"' + (property.effectiveLocked ? ' checked' : '') + lockedDisabled + '> <span>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyToggleLocked || '') + '</span></label>'
        + '</div>';
}

function omoHolonTemplateCreatePropertyRow(property) {
    const normalizedProperty = omoHolonTemplateNormalizeProperty(property);
    const row = document.createElement('div');
    row.className = 'omo-template-property';
    row.dataset.propertyId = Number(normalizedProperty.id || 0);
    row.dataset.holonPropertyId = Number(normalizedProperty.holonPropertyId || 0);
    row.dataset.isInherited = normalizedProperty.isInherited ? '1' : '0';
    row.dataset.isLocal = normalizedProperty.isLocal ? '1' : '0';
    row.dataset.localMandatory = normalizedProperty.mandatory ? '1' : '0';
    row.dataset.localLocked = normalizedProperty.locked ? '1' : '0';
    row.dataset.inheritedMandatory = normalizedProperty.inheritedMandatory ? '1' : '0';
    row.dataset.inheritedLocked = normalizedProperty.inheritedLocked ? '1' : '0';
    row.dataset.inheritedValue = normalizedProperty.inheritedValue !== undefined && normalizedProperty.inheritedValue !== null
        ? String(normalizedProperty.inheritedValue)
        : '';
    row.dataset.canEditValue = normalizedProperty.canEditValue ? '1' : '0';
    row.dataset.canDelete = normalizedProperty.canDelete ? '1' : '0';

    const formatOptions = (omoHolonTemplateState.data.formats || []).map(function (format) {
        const selected = Number(normalizedProperty.formatId || 0) === Number(format.id) ? ' selected' : '';
        return '<option value="' + Number(format.id) + '"' + selected + '>' + omoHolonTemplateEscapeHtml(format.name) + '</option>';
    }).join('');
    const structureDisabled = normalizedProperty.isInherited || !normalizedProperty.canEditValue ? ' disabled' : '';
    const removeDisabled = normalizedProperty.canDelete ? '' : ' disabled';
    const removeLabel = normalizedProperty.isInherited
        ? (omoHolonTemplateTexts.propertyExclude || '')
        : (omoHolonTemplateTexts.propertyRemove || '');
    const inheritedValueHtml = omoHolonTemplateRenderInheritedValueHtml(normalizedProperty);
    const valueFieldTitle = normalizedProperty.isInherited
        ? (normalizedProperty.canEditValue ? (omoHolonTemplateTexts.propertyValueLocalAdded || '') : (omoHolonTemplateTexts.propertyValueDefault || ''))
        : (omoHolonTemplateTexts.propertyValueDefault || '');
    const valueFieldTag = [5, 7].indexOf(Number(normalizedProperty.formatId || 0)) >= 0 ? 'div' : 'label';
    const valueEditorHtml = normalizedProperty.isInherited && !normalizedProperty.canEditValue
        ? ''
        : '<' + valueFieldTag + ' class="omo-field omo-template-property__value-field">'
            + '      <span>' + omoHolonTemplateEscapeHtml(valueFieldTitle) + '</span>'
            + '      <div class="omo-template-property__value-control">' + omoHolonTemplateRenderValueInputHtml(normalizedProperty) + '</div>'
            + '      <small class="omo-template-property__value-help">' + omoHolonTemplateEscapeHtml(omoHolonTemplateGetValueHelpText(normalizedProperty.formatId, normalizedProperty)) + '</small>'
            + '  </' + valueFieldTag + '>';

    row.innerHTML = ''
        + '<div class="omo-template-property__index"></div>'
        + '<div class="omo-template-property__body">'
        + '  <div class="omo-template-property__main">'
        + '      <label class="omo-field">'
        + '          <span>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyName || '') + '</span>'
        + '          <input type="text" class="omo-template-property__name" maxlength="255" value="' + omoHolonTemplateEscapeHtml(normalizedProperty.name || '') + '" placeholder="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyPlaceholderGeneric || '') + '"' + structureDisabled + '>'
        + '      </label>'
        + '      <label class="omo-field">'
        + '          <span>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyFormat || '') + '</span>'
        + '          <select class="omo-template-property__format"' + structureDisabled + '>' + formatOptions + '</select>'
        + '      </label>'
        + '  </div>'
        + omoHolonTemplateRenderPropertyMetaHtml(normalizedProperty)
        + omoHolonTemplateRenderListConfigHtml(normalizedProperty)
        + inheritedValueHtml
        + valueEditorHtml
        + '  <div class="omo-template-property__actions">'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="-1"' + (normalizedProperty.canEditValue ? '' : ' disabled') + '>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyMoveUp || '') + '</button>'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="1"' + (normalizedProperty.canEditValue ? '' : ' disabled') + '>' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyMoveDown || '') + '</button>'
        + '      <button type="button" class="omo-button omo-button--danger" data-property-remove="1"' + removeDisabled + '>' + omoHolonTemplateEscapeHtml(removeLabel) + '</button>'
        + '  </div>'
        + '</div>';

    if ([5, 7].indexOf(Number(normalizedProperty.formatId || 0)) >= 0) {
        row.querySelectorAll('.omo-template-property__html-editor, [data-omo-composite-html]').forEach(function (htmlEditorHost) {
            if (!window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') { return; }
            window.omoSimpleHtmlField.mount(htmlEditorHost, {
                value: htmlEditorHost.hasAttribute('data-omo-composite-html') ? String(htmlEditorHost.getAttribute('data-value') || '') : (normalizedProperty.value !== undefined && normalizedProperty.value !== null ? String(normalizedProperty.value) : ''),
                placeholder: omoHolonTemplateTexts.propertyPlaceholderEmpty || '',
                disabled: !normalizedProperty.canEditValue
            });
        });
    }

    return row;
}

function omoHolonTemplateReadPropertyState(row) {
    const isHolonDefinitionMode = omoHolonTemplateIsHolonDefinitionMode();
    const formatId = Number((row.querySelector('.omo-template-property__format') || {}).value || 0);
    const listItemTypeField = row.querySelector('.omo-template-property__list-item-type');
    const listItemType = listItemTypeField ? String(listItemTypeField.value || 'text') : 'text';
    const mandatoryField = row.querySelector('.omo-template-property__mandatory');
    const lockedField = row.querySelector('.omo-template-property__locked');
    const inheritedMandatory = String(row.dataset.inheritedMandatory || '0') === '1';
    const inheritedLocked = String(row.dataset.inheritedLocked || '0') === '1';
    const listHolonTypeIds = Array.from(row.querySelectorAll('.omo-template-property__list-holon-type:checked')).map(function (input) {
        return Number(input.value || 0);
    }).filter(Boolean);

    const localMandatory = isHolonDefinitionMode
        ? false
        : (mandatoryField
        ? (mandatoryField.disabled && inheritedMandatory
            ? String(row.dataset.localMandatory || '0') === '1'
            : Boolean(mandatoryField.checked))
        : false);
    const localLocked = isHolonDefinitionMode
        ? false
        : (lockedField
        ? (lockedField.disabled && inheritedLocked
            ? String(row.dataset.localLocked || '0') === '1'
            : Boolean(lockedField.checked))
        : false);

    return {
        id: Number(row.dataset.propertyId || 0),
        holonPropertyId: Number(row.dataset.holonPropertyId || 0),
        name: (row.querySelector('.omo-template-property__name') || {}).value || '',
        formatId: formatId,
        listItemType: listItemType,
        listHolonTypeIds: listHolonTypeIds,
        mandatory: localMandatory,
        locked: localLocked,
        inheritedMandatory: isHolonDefinitionMode ? false : inheritedMandatory,
        inheritedLocked: isHolonDefinitionMode ? false : inheritedLocked,
        effectiveMandatory: isHolonDefinitionMode ? false : (inheritedMandatory || localMandatory),
        effectiveLocked: isHolonDefinitionMode ? false : (inheritedLocked || localLocked),
        isInherited: String(row.dataset.isInherited || '0') === '1',
        isLocal: String(row.dataset.isLocal || '0') === '1',
        inheritedValue: String(row.dataset.inheritedValue || ''),
        canDelete: String(row.dataset.canDelete || '0') === '1',
        canEditValue: String(row.dataset.canEditValue || '0') === '1',
        value: omoHolonTemplateSerializePropertyValue(row, formatId, listItemType)
    };
}

function omoHolonTemplateFormatInheritedItem(item, property) {
    const listItemType = String(property.listItemType || 'text');
    const rawValue = item !== undefined && item !== null ? String(item) : '';

    if (listItemType === 'holon') {
        const templateId = Number(item || 0);
        const template = (omoHolonTemplateState.data.templateCatalog || []).find(function (entry) {
            return Number(entry.id) === templateId;
        });
        return template ? template.name : rawValue;
    }

    if (listItemType === 'project') {
        const projectId = Number(item || 0);
        const project = (omoHolonTemplateState.data.projectCatalog || []).find(function (entry) {
            return Number(entry.id) === projectId;
        });
        return project ? project.title : rawValue;
    }

    if (listItemType === 'detail') {
        return omoHolonTemplateNormalizeDetailedListItem(item);
    }

    return rawValue;
}

function omoHolonTemplateRenderInheritedValueHtml(property) {
    const normalizedProperty = omoHolonTemplateNormalizeProperty(property);
    const inheritedValue = normalizedProperty.inheritedValue !== undefined && normalizedProperty.inheritedValue !== null
        ? String(normalizedProperty.inheritedValue)
        : '';

    if (!inheritedValue.trim()) {
        return '';
    }

    let contentHtml = '';
    if (Number(normalizedProperty.formatId || 0) === 2) {
        const items = omoHolonTemplateParseStoredListValue(inheritedValue).map(function (item) {
            return omoHolonTemplateFormatInheritedItem(item, normalizedProperty);
        }).filter(Boolean);

        if (!items.length) {
            return '';
        }

        if (String(normalizedProperty.listItemType || 'text') === 'detail') {
            contentHtml = '<div class="omo-template-property__inherited-detail-list">'
                + items.map(function (item) {
                    const detailItem = omoHolonTemplateNormalizeDetailedListItem(item);
                    return ''
                        + '<details class="omo-template-property__detail-card">'
                        + '  <summary>' + omoHolonTemplateEscapeHtml(detailItem.title || (omoHolonTemplateTexts.propertyDetailFallback || '')) + '</summary>'
                        + (detailItem.description !== ''
                            ? '  <div class="omo-template-property__detail-body">' + omoHolonTemplateEscapeHtml(detailItem.description).replace(/\n/g, '<br>') + '</div>'
                            : '')
                        + '</details>';
                }).join('')
                + '</div>';
        } else {
            contentHtml = '<ul class="omo-template-property__inherited-list">'
                + items.map(function (item) {
                    return '<li>' + omoHolonTemplateEscapeHtml(item) + '</li>';
                }).join('')
                + '</ul>';
        }
    } else if (Number(normalizedProperty.formatId || 0) === 5) {
        contentHtml = omoHolonTemplateRenderHtmlPreview(inheritedValue, 'omo-template-property__inherited-text');
    } else {
        contentHtml = '<div class="omo-template-property__inherited-text">'
            + omoHolonTemplateEscapeHtml(inheritedValue).replace(/\n/g, '<br>')
            + '</div>';
    }

    return ''
        + '<div class="omo-template-property__inherited-block">'
        + '  <div class="omo-template-property__inherited-label">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyValueInherited || '') + '</div>'
        + '  <div class="omo-template-property__inherited-view">'
        +        contentHtml
        + '  </div>'
        + '</div>';
}

function omoHolonTemplateRenderProperties(properties) {
    omoHolonTemplateElements.properties.innerHTML = '';

    if (!properties || !properties.length) {
        omoHolonTemplateElements.properties.innerHTML = '<div class="omo-template-properties__empty">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyEmpty || '') + '</div>';
        return;
    }

    properties.forEach(function (property) {
        omoHolonTemplateElements.properties.appendChild(omoHolonTemplateCreatePropertyRow(property));
    });

    omoHolonTemplateRefreshPropertyIndexes();
}

function omoHolonTemplateShowStatus(message, tone) {
    if (omoHolonTemplateState.statusTimer) {
        window.clearTimeout(omoHolonTemplateState.statusTimer);
        omoHolonTemplateState.statusTimer = null;
    }

    if (typeof window.commonNotify === 'function') {
        omoHolonTemplateClearStatus();
        window.commonNotify(message, tone === 'success' ? 'success' : 'error');
        return;
    }

    omoHolonTemplateElements.status.hidden = false;
    omoHolonTemplateElements.status.className = 'omo-template-editor__status is-' + tone;
    omoHolonTemplateElements.status.innerHTML = ''
        + '<div class="omo-template-editor__status-copy">' + omoHolonTemplateEscapeHtml(message) + '</div>'
        + '<button type="button" class="omo-template-editor__status-close" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.statusCloseMessage || '') + '">&times;</button>';
    window.requestAnimationFrame(function () {
        if (omoHolonTemplateElements.status && typeof omoHolonTemplateElements.status.scrollIntoView === 'function') {
            omoHolonTemplateElements.status.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
    omoHolonTemplateState.statusTimer = window.setTimeout(function () {
        omoHolonTemplateClearStatus();
    }, 40000);
}

function omoHolonTemplateGetCurrentDrawerRouteToken() {
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

function omoHolonTemplateIsHashManagedCompactDrawer() {
    return /^holon-template-edit-\d+-\d+$/i.test(omoHolonTemplateGetCurrentDrawerRouteToken());
}

function omoHolonTemplateGetExternalDrawerContext() {
    if (typeof window.omoGetExternalPanelDrawerContext !== 'function') {
        return null;
    }

    return window.omoGetExternalPanelDrawerContext(omoHolonTemplateRoot);
}

function omoHolonTemplateCloseCompactDrawer() {
    const externalDrawerContext = omoHolonTemplateGetExternalDrawerContext();
    if (externalDrawerContext && typeof window.omoCloseExternalPanelDrawer === 'function') {
        window.omoCloseExternalPanelDrawer();
        return;
    }

    if (omoHolonTemplateIsHashManagedCompactDrawer() && typeof window.omoSetDrawerHashState === 'function') {
        window.omoSetDrawerHashState({
            open: false
        });
        return;
    }

    if (typeof closeDrawer === 'function') {
        closeDrawer('drawer_holon_create');
    }
}

function omoHolonTemplateClearStatus() {
    if (omoHolonTemplateState.statusTimer) {
        window.clearTimeout(omoHolonTemplateState.statusTimer);
        omoHolonTemplateState.statusTimer = null;
    }

    omoHolonTemplateElements.status.hidden = true;
    omoHolonTemplateElements.status.innerHTML = '';
    omoHolonTemplateElements.status.className = 'omo-template-editor__status';
}

function omoHolonTemplateRenderFormBadges(template) {
    if (!template) {
        omoHolonTemplateElements.formBadges.innerHTML = '';
        return;
    }

    const propertyCount = Array.isArray(template.properties) ? template.properties.length : 0;
    const badges = [];

    if (template.typeLabel) {
        badges.push('<span class="omo-template-chip omo-template-chip--accent">' + omoHolonTemplateEscapeHtml(template.typeLabel) + '</span>');
    }

    badges.push('<span class="omo-template-chip">' + propertyCount + ' ' + omoHolonTemplateEscapeHtml(propertyCount > 1 ? (omoHolonTemplateTexts.summaryPropertyOther || '') : (omoHolonTemplateTexts.summaryPropertyOne || '')) + '</span>');

    if (!omoHolonTemplateIsHolonDefinitionMode() && Number(template.inheritsFromId || 0) > 0) {
        badges.push('<span class="omo-template-chip">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.badgeActiveInheritance || '') + '</span>');
    }

    omoHolonTemplateElements.formBadges.innerHTML = badges.join('');
}

function omoHolonTemplateFillForm(template) {
    const current = template || omoHolonTemplateBuildDraft();
    const isExisting = Number(current.id || 0) > 0;
    const isHolonDefinitionMode = omoHolonTemplateIsHolonDefinitionMode();
    const resolvedParentId = Number(current.inheritsFromId || 0);
    const effectiveInheritanceId = omoHolonTemplateGetEffectiveInheritanceIdFromParent(resolvedParentId);
    const effectiveTypeId = omoHolonTemplateGetEffectiveTypeId(current.typeId, effectiveInheritanceId);

    omoHolonTemplateElements.form.dataset.templateId = Number(current.id || 0);
    omoHolonTemplateElements.form.dataset.previousParentId = String(resolvedParentId);
    if (omoHolonTemplateElements.deleteButton) {
        omoHolonTemplateElements.deleteButton.hidden = !isExisting || isHolonDefinitionMode;
        omoHolonTemplateElements.deleteButton.disabled = !isExisting || isHolonDefinitionMode;
    }
    omoHolonTemplateToggleTypeField(effectiveInheritanceId > 0);
    omoHolonTemplateFillTypeOptions(effectiveTypeId);
    omoHolonTemplateBuildParentOptions(resolvedParentId, current.id);
    omoHolonTemplateElements.name.value = current.name || '';
    if (omoHolonTemplateElements.colorEnabled) {
        omoHolonTemplateElements.colorEnabled.checked = String(current.color || '').trim() !== '';
    }
    if (omoHolonTemplateElements.color) {
        omoHolonTemplateElements.color.value = current.color || '#f59e0b';
    }
    omoHolonTemplateSyncColorField();
    if (omoHolonTemplateElements.visible) {
        omoHolonTemplateElements.visible.checked = Boolean(current.visible);
    }
    if (omoHolonTemplateElements.mandatory) {
        omoHolonTemplateElements.mandatory.checked = Boolean(current.mandatory);
    }
    if (omoHolonTemplateElements.lockedName) {
        omoHolonTemplateElements.lockedName.checked = Boolean(current.lockedName);
    }
    if (omoHolonTemplateElements.lockedIcon) {
        omoHolonTemplateElements.lockedIcon.checked = Boolean(current.lockedIcon || current.inheritedLockedIcon);
        omoHolonTemplateElements.lockedIcon.disabled = Boolean(current.inheritedLockedIcon);
    }
    if (omoHolonTemplateElements.lockedBanner) {
        omoHolonTemplateElements.lockedBanner.checked = Boolean(current.lockedBanner || current.inheritedLockedBanner);
        omoHolonTemplateElements.lockedBanner.disabled = Boolean(current.inheritedLockedBanner);
    }
    if (omoHolonTemplateElements.unique) {
        omoHolonTemplateElements.unique.checked = Boolean(current.unique);
    }
    if (omoHolonTemplateElements.link) {
        omoHolonTemplateElements.link.checked = Boolean(current.link);
    }
    if (omoHolonTemplateElements.sharePublic) {
        omoHolonTemplateElements.sharePublic.checked = Boolean(current.shareAsTemplate);
    }
    if (omoHolonTemplateElements.publicName) {
        omoHolonTemplateElements.publicName.value = String(current.publicTemplateName || '');
    }
    if (isHolonDefinitionMode) {
        omoHolonTemplateElements.selectionHint.textContent = omoHolonTemplateTexts.selectionHintDefinition || '';
        omoHolonTemplateElements.formTitle.textContent = current.name || (omoHolonTemplateTexts.formOrganization || '');
        omoHolonTemplateElements.formDescription.textContent = omoHolonTemplateTexts.formDefinitionDescription || '';
    } else {
        omoHolonTemplateElements.selectionHint.textContent = isExisting
            ? (omoHolonTemplateTexts.selectionHintExisting || '')
            : (omoHolonTemplateTexts.selectionHintNew || '');
        omoHolonTemplateElements.formTitle.textContent = isExisting ? (current.name || (omoHolonTemplateTexts.formModelTitle || '')) : (omoHolonTemplateTexts.formNewModel || '');
        omoHolonTemplateElements.formDescription.textContent = isExisting
            ? (omoHolonTemplateTexts.formExistingModelDescription || '')
            : (omoHolonTemplateTexts.formNewModelDescriptionShort || '');
    }
    omoHolonTemplateRenderFormBadges(current);
    if (omoHolonTemplateElements.addProperty) {
        const canAddProperties = Object.prototype.hasOwnProperty.call(current, 'canAddProperties')
            ? Boolean(current.canAddProperties)
            : Boolean(omoHolonTemplateState.data.canAddTemplateProperties);
        omoHolonTemplateElements.addProperty.disabled = !canAddProperties;
    }
    omoHolonTemplateRenderPermissions(current.permissionAssignments || {});
    omoHolonTemplateRenderProperties(current.properties || []);
    omoHolonTemplateRenderMediaFields(current);
    omoHolonTemplateSyncPublicShareFields();
}

function omoHolonTemplateSelect(templateId) {
    const template = omoHolonTemplateFind(templateId);
    omoHolonTemplateState.selectedId = template ? Number(template.id) : null;
    omoHolonTemplateRenderTree();
    omoHolonTemplateFillForm(template || omoHolonTemplateBuildDraft());
}

function omoHolonTemplateReadProperties() {
    return Array.from(omoHolonTemplateElements.properties.querySelectorAll('.omo-template-property')).map(function (row) {
        const property = omoHolonTemplateReadPropertyState(row);
        property.name = String(property.name || '').trim();
        return property;
    }).filter(function (property) {
        return property.name !== '';
    });
}

function omoHolonTemplateDelete() {
    const templateId = Number(omoHolonTemplateElements.form.dataset.templateId || 0);
    const template = omoHolonTemplateFind(templateId);
    if (templateId <= 0 || !template || omoHolonTemplateIsHolonDefinitionMode()) {
        return;
    }

    const templateName = String(template.name || '').trim() || String(omoHolonTemplateTexts.formModelTitle || '');
    const confirmation = String(omoHolonTemplateTexts.confirmDeleteModel || '')
        .replace('{templateName}', templateName);
    if (!window.confirm(confirmation)) {
        return;
    }

    if (
        omoHolonTemplateElements.form
        && typeof window.omoBeginPendingAction === 'function'
        && !window.omoBeginPendingAction(omoHolonTemplateElements.form)
    ) {
        return;
    }

    omoHolonTemplateClearStatus();
    const query = [];
    if (Number(omoHolonTemplateState.data.contextHolonId || 0) > 0) {
        query.push('cid=' + encodeURIComponent(String(omoHolonTemplateState.data.contextHolonId || 0)));
    }
    if (omoHolonTemplateGetScope() !== 'contextual') {
        query.push('template_scope=' + encodeURIComponent(omoHolonTemplateGetScope()));
    }

    const formData = new FormData();
    formData.append('template_id', String(templateId));

    fetch('/omo/api/parameters/holon-templates/delete.php' + (query.length ? ('?' + query.join('&')) : ''), {
        method: 'POST',
        body: formData
    })
        .then(function (response) {
            return response.json().then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || result.data.status !== 'ok') {
                throw new Error(result.data && result.data.message ? result.data.message : (omoHolonTemplateTexts.deleteErrorModel || ''));
            }

            omoHolonTemplateState.data = result.data.data || omoHolonTemplateState.data;
            omoHolonTemplateState.selectedId = null;
            omoHolonTemplateRenderTree();
            omoHolonTemplateFillForm(omoHolonTemplateBuildDraft());
            omoHolonTemplateShowStatus(result.data.message || (omoHolonTemplateTexts.deletedModel || ''), 'success');

            if (omoHolonTemplateState.compactMode) {
                window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                    detail: {
                        cid: Number(omoHolonTemplateState.data.contextHolonId || 0) || null,
                        quickZoom: false
                    }
                }));
            }
        })
        .catch(function (error) {
            omoHolonTemplateShowStatus(error && error.message ? error.message : (omoHolonTemplateTexts.deleteErrorModel || ''), 'error');
        })
        .finally(function () {
            if (omoHolonTemplateElements.form && typeof window.omoEndPendingAction === 'function') {
                window.omoEndPendingAction(omoHolonTemplateElements.form);
            }
        });
}

function omoHolonTemplateSave(event) {
    event.preventDefault();

    if (
        omoHolonTemplateElements.form
        && typeof window.omoBeginPendingAction === 'function'
        && !window.omoBeginPendingAction(omoHolonTemplateElements.form)
    ) {
        return;
    }

    omoHolonTemplateClearStatus();

    const pendingMediaFlushes = [];
    if (omoHolonTemplateMediaFields.icon && typeof omoHolonTemplateMediaFields.icon.flushPending === 'function') {
        pendingMediaFlushes.push(omoHolonTemplateMediaFields.icon.flushPending());
    }
    if (omoHolonTemplateMediaFields.banner && typeof omoHolonTemplateMediaFields.banner.flushPending === 'function') {
        pendingMediaFlushes.push(omoHolonTemplateMediaFields.banner.flushPending());
    }

    Promise.all(pendingMediaFlushes)
        .then(function () {
            const payload = {
                id: Number(omoHolonTemplateElements.form.dataset.templateId || 0),
                typeId: omoHolonTemplateGetEffectiveTypeId(omoHolonTemplateElements.type.value || 0, omoHolonTemplateGetEffectiveInheritanceIdFromParent(omoHolonTemplateElements.parent.value || 0)),
                name: omoHolonTemplateElements.name.value.trim(),
                color: Boolean(omoHolonTemplateElements.colorEnabled && omoHolonTemplateElements.colorEnabled.checked)
                    ? String(omoHolonTemplateElements.color && omoHolonTemplateElements.color.value ? omoHolonTemplateElements.color.value : '')
                    : '',
                icon: omoHolonTemplateMediaFields.icon ? omoHolonTemplateMediaFields.icon.getValue() : '',
                banner: omoHolonTemplateMediaFields.banner ? omoHolonTemplateMediaFields.banner.getValue() : '',
                visible: Boolean(omoHolonTemplateElements.visible && omoHolonTemplateElements.visible.checked),
                mandatory: Boolean(omoHolonTemplateElements.mandatory && omoHolonTemplateElements.mandatory.checked),
                lockedName: Boolean(omoHolonTemplateElements.lockedName && omoHolonTemplateElements.lockedName.checked),
                lockedIcon: omoHolonTemplateElements.lockedIcon
                    ? (omoHolonTemplateElements.lockedIcon.disabled
                        ? String(omoHolonTemplateElements.lockedIcon.dataset.localValue || '0') === '1'
                        : Boolean(omoHolonTemplateElements.lockedIcon.checked))
                    : false,
                lockedBanner: omoHolonTemplateElements.lockedBanner
                    ? (omoHolonTemplateElements.lockedBanner.disabled
                        ? String(omoHolonTemplateElements.lockedBanner.dataset.localValue || '0') === '1'
                        : Boolean(omoHolonTemplateElements.lockedBanner.checked))
                    : false,
                unique: Boolean(omoHolonTemplateElements.unique && omoHolonTemplateElements.unique.checked),
                link: Boolean(omoHolonTemplateElements.link && omoHolonTemplateElements.link.checked),
                inheritsFromId: omoHolonTemplateGetEffectiveInheritanceIdFromParent(omoHolonTemplateElements.parent.value || 0),
                permissions: omoHolonTemplateReadPermissions(),
                properties: omoHolonTemplateReadProperties()
            };

            if (omoHolonTemplateIsHolonDefinitionMode()) {
                payload.shareAsTemplate = Boolean(omoHolonTemplateElements.sharePublic && omoHolonTemplateElements.sharePublic.checked);
                payload.publicTemplateName = payload.shareAsTemplate && omoHolonTemplateElements.publicName
                    ? String(omoHolonTemplateElements.publicName.value || '').trim()
                    : '';

                if (!payload.shareAsTemplate) {
                    payload.icon = '';
                    payload.banner = '';
                }
            }

            const saveUrl = '/omo/api/parameters/holon-templates/save.php'
                + (function () {
                    const query = [];
                    if (Number(omoHolonTemplateState.data.contextHolonId || 0) > 0) {
                        query.push('cid=' + Number(omoHolonTemplateState.data.contextHolonId || 0));
                    }
                    if (omoHolonTemplateIsHolonDefinitionMode() && Number(omoHolonTemplateState.data.targetHolonId || 0) > 0) {
                        query.push('hid=' + Number(omoHolonTemplateState.data.targetHolonId || 0));
                    }
                    if (!omoHolonTemplateIsHolonDefinitionMode() && omoHolonTemplateGetScope() !== 'contextual') {
                        query.push('template_scope=' + encodeURIComponent(omoHolonTemplateGetScope()));
                    }
                    return query.length ? ('?' + query.join('&')) : '';
                })();

            const formData = new FormData();
            formData.append('payload', JSON.stringify(payload));
            if (omoHolonTemplateMediaFields.icon) {
                omoHolonTemplateMediaFields.icon.appendToFormData(formData);
            }
            if (omoHolonTemplateMediaFields.banner) {
                omoHolonTemplateMediaFields.banner.appendToFormData(formData);
            }

            return fetch(saveUrl, {
                method: 'POST',
                body: formData
            });
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || result.data.status !== 'ok') {
                throw new Error(result.data && result.data.message ? result.data.message : (omoHolonTemplateIsHolonDefinitionMode() ? (omoHolonTemplateTexts.saveErrorOrganization || '') : (omoHolonTemplateTexts.saveErrorModel || '')));
            }

            omoHolonTemplateState.data = result.data.data;
            omoHolonTemplateState.selectedId = result.data.template ? Number(result.data.template.id) : null;
            omoHolonTemplateRenderTree();
            omoHolonTemplateFillForm(result.data.template || omoHolonTemplateBuildDraft());
            omoHolonTemplateShowStatus(result.data.message || (omoHolonTemplateTexts.savedModel || ''), 'success');
            if (omoHolonTemplateState.compactMode) {
                const route = typeof parseUrl === 'function'
                    ? parseUrl()
                    : {
                        oid: Number(omoHolonTemplateState.data.organizationId || 0),
                        cid: null
                    };
                const targetHolonId = result.data.template ? Number(result.data.template.id || 0) : 0;
                const externalDrawerContext = omoHolonTemplateGetExternalDrawerContext();
                const externalStructureHost = externalDrawerContext
                    && String(externalDrawerContext.hostRouteToken || '').trim().toLowerCase() === 'structure';
                const currentRouteCid = Number(route && route.cid ? route.cid : 0);
                const shouldNavigate = targetHolonId > 0
                    && typeof navigate === 'function'
                    && Number(route && route.oid ? route.oid : 0) > 0
                    && currentRouteCid !== targetHolonId;

                if (!shouldNavigate && targetHolonId > 0 && typeof loadContent === 'function') {
                    loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', 'api/getOrg.php?oid=' + Number(route.oid || omoHolonTemplateState.data.organizationId || 0) + '&cid=' + targetHolonId);
                }

                window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                    detail: {
                        cid: targetHolonId > 0 ? targetHolonId : null,
                        quickZoom: externalStructureHost ? false : true
                    }
                }));

                if (shouldNavigate) {
                    if (externalDrawerContext) {
                        omoHolonTemplateCloseCompactDrawer();
                    }

                    navigate(route.oid, targetHolonId, route.hash || null);

                    if (
                        externalDrawerContext
                        && !externalStructureHost
                        && typeof window.omoRefreshExternalPanelDrawerHost === 'function'
                    ) {
                        window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                    }

                    return;
                }

                if (externalDrawerContext && typeof window.omoRefreshExternalPanelDrawerHost === 'function') {
                    if (externalStructureHost) {
                        omoHolonTemplateCloseCompactDrawer();
                        return;
                    }

                    omoHolonTemplateCloseCompactDrawer();
                    window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                    return;
                }

                omoHolonTemplateCloseCompactDrawer();
            }
        })
        .catch(function (error) {
            omoHolonTemplateShowStatus(error && error.message ? error.message : (omoHolonTemplateIsHolonDefinitionMode() ? (omoHolonTemplateTexts.saveErrorOrganization || '') : (omoHolonTemplateTexts.saveErrorModel || '')), 'error');
        })
        .finally(function () {
            if (omoHolonTemplateElements.form && typeof window.omoEndPendingAction === 'function') {
                window.omoEndPendingAction(omoHolonTemplateElements.form);
            }
        });
}

if (omoHolonTemplateElements.form) {
    omoHolonTemplateElements.form.addEventListener('submit', omoHolonTemplateSave);
}

if (omoHolonTemplateElements.cancel) {
    omoHolonTemplateElements.cancel.addEventListener('click', function () {
        omoHolonTemplateCloseCompactDrawer();
    });
}

if (omoHolonTemplateElements.deleteButton) {
    omoHolonTemplateElements.deleteButton.addEventListener('click', omoHolonTemplateDelete);
}

if (omoHolonTemplateElements.sharePublic) {
    omoHolonTemplateElements.sharePublic.addEventListener('change', function () {
        omoHolonTemplateSyncPublicShareFields();
    });
}

omoHolonTemplatePageRoot.addEventListener('click', function (event) {
    const scopeToggleButton = event.target.closest('[data-omo-template-scope-toggle]');
    if (!scopeToggleButton || omoHolonTemplateIsHolonDefinitionMode()) {
        return;
    }

    const targetScope = scopeToggleButton.getAttribute('data-omo-template-scope-toggle') || 'contextual';
    if (targetScope !== omoHolonTemplateGetScope()) {
        omoHolonTemplateReloadPage(targetScope, {
            templateId: Number(omoHolonTemplateState.selectedId || 0)
        });
    }
});

if (omoHolonTemplateElements.root) {
    omoHolonTemplateElements.root.addEventListener('change', function (event) {
        if (event.target === omoHolonTemplateElements.parent) {
            const previousParentId = Number(omoHolonTemplateElements.form.dataset.previousParentId || 0);
            const nextParentId = Number(omoHolonTemplateElements.parent.value || 0);
            if (!omoHolonTemplateConfirmInheritanceChange(previousParentId, nextParentId)) {
                omoHolonTemplateElements.parent.value = String(previousParentId);
                return;
            }
            omoHolonTemplateRefreshInheritancePreview();
            return;
        }

        if (event.target === omoHolonTemplateElements.colorEnabled) {
            omoHolonTemplateSyncColorField();
            return;
        }

        if (event.target.matches('[data-authority-deletion-choice]')) {
            const authorityRow = event.target.closest('[data-authority-entry]');
            if (authorityRow) {
                omoHolonTemplateUpdateAuthorityDeletionCounts(authorityRow);
            }
            return;
        }

        if (event.target.matches('.omo-template-authority__parent, .omo-template-authority__delegation')) {
            const authorityRow = event.target.closest('[data-authority-entry]');
            if (authorityRow && Number(authorityRow.getAttribute('data-authority-id') || 0) <= 0) {
                authorityRow.outerHTML = omoHolonTemplateRenderAuthorityRow({
                    parentId: Number((authorityRow.querySelector('.omo-template-authority__parent') || {}).value || 0),
                    delegationMode: String((authorityRow.querySelector('.omo-template-authority__delegation') || {}).value || 'partial'),
                    label: String((authorityRow.querySelector('.omo-template-authority__label') || {}).value || ''),
                    description: String((authorityRow.querySelector('.omo-template-authority__description') || {}).value || '')
                });
                return;
            }
        }

        if (event.target.classList.contains('omo-template-permissions__checkbox')) {
            const permissionRow = event.target.closest('[data-permission-key]');
            if (!permissionRow) {
                return;
            }

            if (event.target.checked) {
                Array.from(permissionRow.querySelectorAll('.omo-template-permissions__checkbox')).forEach(function (checkbox) {
                    if (checkbox !== event.target) {
                        checkbox.checked = false;
                    }
                });
            }
            return;
        }

        const propertyField = event.target.closest('.omo-template-property__format, .omo-template-property__list-item-type, .omo-template-property__list-holon-type');
        if (!propertyField) {
            return;
        }

        const row = propertyField.closest('.omo-template-property');
        if (!row) {
            return;
        }

        const propertyState = omoHolonTemplateReadPropertyState(row);
        const replacement = omoHolonTemplateCreatePropertyRow(propertyState);
        row.replaceWith(replacement);
    });

    omoHolonTemplateElements.root.addEventListener('click', function (event) {
        const selectButton = event.target.closest('[data-template-select]');
        if (selectButton) {
            const targetTemplateId = Number(selectButton.getAttribute('data-template-select') || 0);
            const targetContextId = Number(selectButton.getAttribute('data-template-context-id') || 0);
            if (
                omoHolonTemplateGetScope() !== 'contextual'
                && targetTemplateId > 0
                && targetContextId > 0
                && targetContextId !== Number(omoHolonTemplateState.data.contextHolonId || 0)
            ) {
                omoHolonTemplateReloadPage('contextual', {
                    contextHolonId: targetContextId,
                    templateId: targetTemplateId
                });
                return;
            }

            omoHolonTemplateSelect(targetTemplateId);
            return;
        }

        const closeStatusButton = event.target.closest('.omo-template-editor__status-close');
        if (closeStatusButton) {
            omoHolonTemplateClearStatus();
            return;
        }

        const templateAction = event.target.closest('[data-template-action]');
        if (templateAction) {
            omoHolonTemplateClearStatus();

            if (templateAction.getAttribute('data-template-action') === 'new-child' && omoHolonTemplateState.selectedId) {
                omoHolonTemplateState.selectedId = null;
                omoHolonTemplateRenderTree();
                omoHolonTemplateFillForm(
                    omoHolonTemplateBuildDraft(Number(omoHolonTemplateElements.form.dataset.templateId || 0))
                );
                return;
            }

            omoHolonTemplateState.selectedId = null;
            omoHolonTemplateRenderTree();
            omoHolonTemplateFillForm(
                omoHolonTemplateBuildDraft(0)
            );
            return;
        }

        const addPropertyButton = event.target.closest('#omo-template-add-property');
        if (addPropertyButton) {
            if (addPropertyButton.disabled) {
                return;
            }
            if (omoHolonTemplateElements.properties.querySelector('.omo-template-properties__empty')) {
                omoHolonTemplateElements.properties.innerHTML = '';
            }

            const defaultFormat = (omoHolonTemplateState.data.formats || []).length
                ? Number(omoHolonTemplateState.data.formats[0].id || 1)
                : 1;

            omoHolonTemplateElements.properties.appendChild(omoHolonTemplateCreatePropertyRow({
                id: 0,
                holonPropertyId: 0,
                name: '',
                formatId: defaultFormat,
                listItemType: 'text',
                listHolonTypeIds: [],
                mandatory: false,
                locked: false,
                isInherited: false,
                isLocal: true,
                canDelete: true,
                canEditValue: true,
                value: ''
            }));
            omoHolonTemplateRefreshPropertyIndexes();
            return;
        }

        const addListItemButton = event.target.closest('[data-list-add]');
        const addAuthorityButton = event.target.closest('[data-authority-add]');
        if (addAuthorityButton) {
            const authorityField = addAuthorityButton.closest('.omo-template-authority');
            const authorityItems = authorityField ? authorityField.querySelector('.omo-template-authority__items') : null;
            if (authorityItems) {
                authorityItems.insertAdjacentHTML('beforeend', omoHolonTemplateRenderAuthorityRow({}));
                const labelField = authorityItems.lastElementChild
                    ? authorityItems.lastElementChild.querySelector('.omo-template-authority__label')
                    : null;
                if (labelField) {
                    labelField.focus();
                }
            }
            return;
        }

        const removeAuthorityButton = event.target.closest('[data-authority-remove]');
        if (removeAuthorityButton) {
            const authorityRow = removeAuthorityButton.closest('[data-authority-entry]');
            if (authorityRow) {
                authorityRow.remove();
            }
            return;
        }

        const editAuthorityButton = event.target.closest('[data-authority-edit]');
        if (editAuthorityButton) {
            const authorityRow = editAuthorityButton.closest('[data-authority-entry]');
            const authorityId = Number(authorityRow && authorityRow.getAttribute('data-authority-id') || 0);
            if (authorityRow && authorityId > 0) {
                authorityRow.outerHTML = omoHolonTemplateRenderAuthorityRow({ id: authorityId, editing: true });
                const labelField = root.querySelector('[data-authority-entry][data-authority-id="' + authorityId + '"] .omo-template-authority__label');
                if (labelField) {
                    labelField.focus();
                }
            }
            return;
        }

        const deleteAuthorityButton = event.target.closest('button[data-authority-delete]');
        if (deleteAuthorityButton) {
            const authorityRow = deleteAuthorityButton.closest('[data-authority-entry]');
            if (authorityRow) {
                const isPendingDeletion = authorityRow.getAttribute('data-authority-delete') === '1';
                if (isPendingDeletion) {
                    authorityRow.removeAttribute('data-authority-delete');
                    authorityRow.classList.remove('is-pending-delete');
                    deleteAuthorityButton.setAttribute('aria-label', 'Supprimer l autorite');
                    deleteAuthorityButton.title = 'Supprimer l autorite';
                } else {
                    authorityRow.setAttribute('data-authority-delete', '1');
                    authorityRow.classList.add('is-pending-delete');
                    deleteAuthorityButton.setAttribute('aria-label', 'Annuler la suppression');
                    deleteAuthorityButton.title = 'Annuler la suppression';
                    omoHolonTemplateUpdateAuthorityDeletionCounts(authorityRow);
                }
            }
            return;
        }

        if (addListItemButton) {
            const listField = addListItemButton.closest('.omo-template-list-input');
            const listItems = listField ? listField.querySelector('.omo-template-list-input__items') : null;
            if (!listField || !listItems) {
                return;
            }

            listItems.insertAdjacentHTML(
                'beforeend',
                omoHolonTemplateRenderSimpleListRow(listField.getAttribute('data-list-item-type') || 'text', '')
            );

            const inputs = listItems.querySelectorAll('.omo-template-property__value-item');
            if (inputs.length) {
                inputs[inputs.length - 1].focus();
            }
            return;
        }

        const moveListItemButton = event.target.closest('[data-list-move]');
        if (moveListItemButton) {
            const direction = Number(moveListItemButton.getAttribute('data-list-move') || 0);
            const row = moveListItemButton.closest('.omo-template-list-input__row');
            const listItems = row && row.parentNode ? row.parentNode : null;
            if (!row || !listItems || !direction) {
                return;
            }

            if (direction < 0) {
                const previousRow = row.previousElementSibling;
                if (previousRow) {
                    listItems.insertBefore(row, previousRow);
                }
            } else {
                const nextRow = row.nextElementSibling;
                if (nextRow) {
                    listItems.insertBefore(nextRow, row);
                }
            }

            const input = row.querySelector('.omo-template-property__value-item');
            if (input) {
                input.focus();
            }
            return;
        }

        const removeListItemButton = event.target.closest('[data-list-remove]');
        if (removeListItemButton) {
            const listField = removeListItemButton.closest('.omo-template-list-input');
            const row = removeListItemButton.closest('.omo-template-list-input__row');
            const listItems = listField ? listField.querySelector('.omo-template-list-input__items') : null;
            if (!listField || !row || !listItems) {
                return;
            }

            row.remove();
            if (!listItems.querySelector('.omo-template-list-input__row')) {
                listItems.insertAdjacentHTML(
                    'beforeend',
                    omoHolonTemplateRenderSimpleListRow(listField.getAttribute('data-list-item-type') || 'text', '')
                );
            }
            return;
        }

        const removePropertyButton = event.target.closest('[data-property-remove]');
        if (removePropertyButton) {
            const row = removePropertyButton.closest('.omo-template-property');
            if (row) {
                row.remove();
            }

            if (!omoHolonTemplateElements.properties.querySelector('.omo-template-property')) {
                omoHolonTemplateRenderProperties([]);
            } else {
                omoHolonTemplateRefreshPropertyIndexes();
            }
            return;
        }

        const moveButton = event.target.closest('[data-property-move]');
        if (moveButton) {
            const row = moveButton.closest('.omo-template-property');
            if (!row) {
                return;
            }

            const direction = Number(moveButton.getAttribute('data-property-move'));
            const sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;

            if (!sibling || !sibling.classList.contains('omo-template-property')) {
                return;
            }

            if (direction < 0) {
                sibling.parentNode.insertBefore(row, sibling);
            } else {
                sibling.parentNode.insertBefore(sibling, row);
            }

            omoHolonTemplateRefreshPropertyIndexes();
        }
    });
}

Promise.all([
    omoHolonTemplateWaitForGlobalLibrary('omoSizedImageField', 5000),
    omoHolonTemplateWaitForGlobalLibrary('omoSimpleHtmlField', 5000)
]).finally(function () {
    omoHolonTemplateBootstrapInitialRender();
});
})();
</script>
<?php endif; ?>

<style>
.omo-template-editor > .omo-panel-view__body {
    padding: 16px 18px 18px;
}

.omo-template-editor__layout {
    display: grid;
    grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

.omo-template-editor__layout--holon-definition {
    grid-template-columns: minmax(0, 1fr);
}

.omo-template-editor__layout--holon-definition .omo-template-sidebar {
    display: none;
}

.omo-template-field--hidden {
    display: none !important;
}

.omo-template-editor__layout--compact {
    grid-template-columns: minmax(0, 1fr);
}

.omo-template-editor__eyebrow {
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--color-text-light);
    margin-bottom: 8px;
}

.omo-template-sidebar,
.omo-template-form-panel {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
}

.omo-template-sidebar {
    position: sticky;
    top: 14px;
    display: grid;
    gap: 14px;
    padding: 16px;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--color-primary) 16%, transparent), transparent 42%),
        linear-gradient(180deg, color-mix(in srgb, var(--color-primary) 7%, var(--color-surface)) 0%, var(--color-surface) 140px);
}

.omo-template-editor__layout--compact .omo-template-sidebar {
    display: none;
}

.omo-template-sidebar__hero h3 {
    margin: 0 0 6px;
    font-size: 1.1rem;
}

.omo-template-sidebar__hero p {
    margin: 0;
    color: var(--color-text-light);
    line-height: 1.45;
}

.omo-template-sidebar__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.omo-template-stat {
    display: grid;
    gap: 3px;
    padding: 12px 14px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface) 86%, var(--color-primary) 14%);
    border: 1px solid color-mix(in srgb, var(--color-border) 68%, var(--color-primary) 32%);
}

.omo-template-stat strong {
    font-size: 1.2rem;
    line-height: 1;
}

.omo-template-stat span {
    color: var(--color-text-light);
    font-size: 0.82rem;
}

.omo-template-sidebar__actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.omo-template-tree-wrap {
    display: grid;
    gap: 10px;
    padding: 14px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface) 88%, var(--color-surface-alt));
    border: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
}

.omo-template-tree-wrap__title {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--color-text-light);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.omo-template-tree {
    max-height: calc(100dvh - 320px);
    overflow: auto;
    padding-right: 4px;
}

.omo-template-tree__empty,
.omo-template-properties__empty {
    padding: 16px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
    color: var(--color-text-light);
}

.omo-template-tree__list {
    list-style: none;
    margin: 0;
    padding: 0 0 0 16px;
    border-left: 1px solid color-mix(in srgb, var(--color-border) 78%, transparent);
}

.omo-template-tree > .omo-template-tree__list {
    padding-left: 0;
    border-left: 0;
}

.omo-template-tree__item + .omo-template-tree__item {
    margin-top: 8px;
}

.omo-template-tree__button {
    width: 100%;
    display: grid;
    gap: 8px;
    text-align: left;
    padding: 12px 13px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface-alt) 82%, var(--color-surface));
    color: var(--color-text);
    cursor: pointer;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.omo-template-tree__button:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));
    box-shadow: var(--shadow-sm);
}

.omo-template-tree__button.is-selected {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
    box-shadow:
        inset 3px 0 0 var(--color-primary),
        0 10px 20px color-mix(in srgb, var(--color-primary) 10%, transparent);
}

.omo-template-tree__name {
    font-weight: 700;
    line-height: 1.3;
}

.omo-template-tree__meta-row,
.omo-template-form-panel__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.omo-template-chip {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface) 72%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-border) 80%, transparent);
    color: var(--color-text-light);
    font-size: 0.76rem;
    white-space: nowrap;
}

.omo-template-chip--accent {
    color: var(--color-primary);
    border-color: color-mix(in srgb, var(--color-primary) 30%, transparent);
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
}

.omo-template-form-panel {
    display: grid;
    gap: 16px;
    padding: 18px;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--color-primary) 12%, transparent), transparent 38%),
        linear-gradient(180deg, color-mix(in srgb, var(--color-primary) 6%, var(--color-surface)) 0%, var(--color-surface) 160px);
}

.omo-template-form-panel__header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    padding-bottom: 14px;
    border-bottom: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
}

.omo-template-form-panel__title {
    margin: 0 0 6px;
    font-size: 1.18rem;
}

.omo-template-form-panel__description {
    margin: 0;
    color: var(--color-text-light);
    line-height: 1.45;
}

.omo-template-editor__status {
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    border: 1px solid transparent;
    box-shadow: var(--shadow-sm);
    backdrop-filter: blur(8px);
}

.omo-template-editor__status[hidden] {
    display: none !important;
}

.omo-template-editor__status.is-success {
    background: color-mix(in srgb, #16a34a 12%, white);
    color: #166534;
    border-color: color-mix(in srgb, #16a34a 24%, transparent);
}

.omo-template-editor__status.is-error {
    background: color-mix(in srgb, #dc2626 10%, white);
    color: #991b1b;
    border-color: color-mix(in srgb, #dc2626 20%, transparent);
}

.omo-template-editor__status-copy {
    flex: 1 1 auto;
    line-height: 1.45;
}

.omo-template-editor__status-close {
    flex: 0 0 auto;
    width: 32px;
    min-width: 32px;
    height: 32px;
    border: 0;
    border-radius: 999px;
    background: color-mix(in srgb, currentColor 12%, transparent);
    color: inherit;
    cursor: pointer;
    font-size: 1.2rem;
    line-height: 1;
}

.omo-template-editor__status-close:hover {
    background: color-mix(in srgb, currentColor 18%, transparent);
}

.omo-template-form {
    display: grid;
    gap: 16px;
    min-width: 0;
}

.omo-template-section {
    display: grid;
    gap: 14px;
    padding: 16px;
    border: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface) 90%, var(--color-surface-alt));
}

.omo-template-section__head {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
}

.omo-template-section__title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--color-text-light);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.omo-template-section__description {
    margin: 4px 0 0;
    color: var(--color-text-light);
    line-height: 1.45;
}

.omo-template-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.omo-template-form__grid > [hidden] {
    display: none !important;
}

.omo-color-field__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.omo-color-field__body[hidden] {
    display: none !important;
}

.omo-color-field__toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--color-text-light);
}

.omo-color-field__toggle input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: var(--color-primary);
}

#omo-template-type-field[hidden],
#omo-template-type[hidden] {
    display: none !important;
}

.omo-field {
    display: grid;
    gap: 7px;
    min-width: 0;
}

.omo-field--full {
    grid-column: 1 / -1;
}

.omo-template-flags {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.omo-template-flags__option {
    display: grid;
    gap: 6px;
    align-content: start;
    padding: 14px 15px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
    cursor: pointer;
}

.omo-template-flags__option input {
    width: 18px;
    height: 18px;
    min-height: 18px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-template-flags__option span {
    font-size: 0.92rem;
    font-weight: 700;
}

.omo-template-flags__option small {
    color: var(--color-text-light);
    line-height: 1.4;
}

.omo-template-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 14px;
    margin-top: 8px;
}

.omo-template-media-card {
    display: grid;
    gap: 12px;
    padding: 14px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
}

.omo-template-media-card__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.omo-template-media-card__title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--color-text);
}

.omo-template-media-card__lock {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: var(--color-text-light);
}

.omo-template-media-card__lock input {
    width: 16px;
    height: 16px;
    min-height: 16px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-field > span {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.35;
}

.omo-field input,
.omo-field select,
.omo-field textarea {
    display: block;
    width: 100%;
    min-height: 44px;
    padding: 11px 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
    color: var(--color-text);
    box-sizing: border-box;
    font: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.omo-field input:focus,
.omo-field select:focus,
.omo-field textarea:focus {
    outline: none;
    border-color: color-mix(in srgb, var(--color-primary) 55%, var(--color-border));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 14%, transparent);
    background: var(--color-surface);
}

.omo-field textarea {
    min-height: 108px;
    resize: vertical;
}

.omo-template-properties {
    display: grid;
    gap: 12px;
}

.omo-template-property {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    padding: 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface) 76%, var(--color-surface-alt));
}

.omo-template-property__index {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary) 12%, var(--color-surface));
    color: var(--color-primary);
    font-size: 0.8rem;
    font-weight: 700;
}

.omo-template-property__body {
    display: grid;
    gap: 12px;
    min-width: 0;
}

.omo-template-property__main {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
    gap: 14px;
    align-items: start;
}

.omo-template-property__actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.omo-template-property__main .omo-field {
    align-content: start;
}

.omo-template-property__value-field {
    gap: 8px;
}

.omo-template-property__value-field small {
    color: var(--color-text-light);
    line-height: 1.4;
}

.omo-template-property__list-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
    gap: 14px;
    align-items: start;
}

.omo-template-property__holon-types {
    gap: 8px;
}

.omo-template-property__meta {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.omo-template-property__origin {
    display: flex;
}

.omo-template-property__toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.92rem;
    color: var(--color-text-light);
}

.omo-template-property__check-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 8px;
}

.omo-template-property__check-option {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 9px 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
}

.omo-template-property__check-option small {
    display: block;
    color: var(--color-text-light);
    line-height: 1.3;
}

.omo-template-property__value-control {
    min-width: 0;
}

.omo-template-property__inherited-block {
    padding: 14px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface-alt) 78%, var(--color-surface));
}

.omo-template-property__inherited-label {
    margin-bottom: 8px;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--color-text-light);
}

.omo-template-property__inherited-view {
    min-width: 0;
}

.omo-template-property__inherited-text {
    color: var(--color-text-light);
    line-height: 1.55;
    white-space: pre-line;
}

.omo-template-property__inherited-list {
    margin: 0;
    padding-left: 20px;
    color: var(--color-text-light);
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.omo-template-property__inherited-detail-list {
    display: grid;
    gap: 8px;
}

.omo-template-property__detail-card {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface-alt) 65%, var(--color-surface));
    overflow: hidden;
}

.omo-template-property__detail-card summary {
    cursor: pointer;
    padding: 10px 12px;
    font-weight: 600;
}

.omo-template-property__detail-body {
    padding: 0 12px 12px;
    color: var(--color-text-light);
    line-height: 1.5;
    white-space: pre-line;
}

.omo-template-list-input {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.omo-template-list-input__items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.omo-template-list-input__row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px 42px 42px;
    gap: 8px;
    align-items: center;
}

.omo-template-list-input__row--detail {
    align-items: start;
}

.omo-template-list-input__detail-fields {
    display: grid;
    gap: 8px;
}

.omo-template-property__value-item {
    width: 100%;
}

.omo-template-property__value-item--detail-description {
    min-height: 88px;
    resize: vertical;
}

.omo-template-list-input__add,
.omo-template-list-input__move,
.omo-template-list-input__remove {
    min-width: 42px;
    padding-inline: 0;
}

.omo-template-authority,
.omo-template-authority__items {
    display: grid;
    gap: 8px;
}

.omo-template-authority__row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
    align-items: start;
    padding: 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
}

.omo-template-authority__row--existing {
    align-items: center;
}

.omo-template-authority__edit {
    border: 0;
    padding: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    text-align: left;
}

.omo-template-authority__row.is-pending-delete {
    color: var(--color-text-light);
    background: color-mix(in srgb, var(--color-danger, #dc2626) 6%, var(--color-surface));
    border-style: dashed;
}

.omo-template-authority__row.is-pending-delete .omo-template-authority__edit,
.omo-template-authority__row.is-pending-delete .omo-template-authority__fields {
    pointer-events: none;
    text-decoration: line-through;
    opacity: 0.62;
}

.omo-template-authority__deletion {
    display: none;
    grid-column: 1 / -1;
    gap: 10px;
    padding: 12px;
    border: 1px solid color-mix(in srgb, var(--color-danger, #dc2626) 35%, var(--color-border));
    border-radius: var(--radius-sm);
    background: var(--color-surface-alt);
    color: var(--color-text);
}

.omo-template-authority__row.is-pending-delete .omo-template-authority__deletion {
    display: grid;
}

.omo-template-authority__deletion p,
.omo-template-authority__deletion fieldset {
    margin: 0;
}

.omo-template-authority__deletion fieldset {
    display: grid;
    gap: 6px;
    min-width: 0;
    padding: 8px 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
}

.omo-template-authority__deletion fieldset[hidden] {
    display: none;
}

.omo-template-authority__complete-note {
    grid-column: 1 / -1;
    padding: 10px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-sm);
    color: var(--color-text-light);
}

.omo-template-authority__deletion legend {
    padding: 0 4px;
    font-weight: 700;
}

.omo-template-authority__deletion label {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    font-size: 0.9rem;
    line-height: 1.35;
}

.omo-template-authority__row strong,
.omo-template-authority__row small {
    display: block;
}

.omo-template-authority__row small,
.omo-template-authority__row > span {
    color: var(--color-text-light);
    font-size: 0.82rem;
    line-height: 1.35;
}

.omo-template-authority__fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.omo-template-authority__label,
.omo-template-authority__parent,
.omo-template-authority__description {
    width: 100%;
}

.omo-template-authority__description {
    grid-column: 1 / -1;
    min-height: 76px;
    resize: vertical;
}

.omo-template-property__empty-note {
    padding: 12px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
    color: var(--color-text-light);
    background: var(--color-surface);
}

.omo-template-form__footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    position: sticky;
    bottom: -18px;
    z-index: 10;
    padding: 16px 18px 18px;
    margin: 8px -18px -18px;
    border-top: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
    border-radius: 0 0 var(--radius-md) var(--radius-md);
    background: color-mix(in srgb, var(--color-surface) 92%, var(--color-surface-alt));
    box-shadow: 0 -8px 24px color-mix(in srgb, var(--color-shadow) 8%, transparent);
    backdrop-filter: blur(6px);
}

.omo-template-form__hint {
    color: var(--color-text-light);
    font-size: 0.9rem;
}

.omo-button {
    min-height: 40px;
    padding: 8px 13px;
    border-radius: 999px;
    border: 1px solid var(--color-border);
    background: var(--color-surface-alt);
    color: var(--color-text);
    cursor: pointer;
    font: inherit;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, color 0.15s ease;
}

.omo-button:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--color-primary) 28%, var(--color-border));
    box-shadow: var(--shadow-sm);
}

.omo-button:disabled {
    opacity: 0.55;
    cursor: default;
}

.omo-button--primary {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-text-inverse);
}

.omo-button--secondary {
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
    border-color: color-mix(in srgb, var(--color-primary) 24%, var(--color-border));
    color: var(--color-primary);
}

.omo-button--ghost {
    background: var(--color-surface);
}

.omo-button--danger {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: color-mix(in srgb, #dc2626 10%, var(--color-surface));
    color: #b91c1c;
    border-color: color-mix(in srgb, #dc2626 26%, var(--color-border));
}

.omo-button--danger:hover:not(:disabled) {
    background: color-mix(in srgb, #dc2626 16%, var(--color-surface));
    border-color: color-mix(in srgb, #dc2626 46%, var(--color-border));
}

.omo-template-delete-button__icon {
    width: 16px;
    height: 16px;
    object-fit: contain;
    opacity: 0.78;
}

@media (max-width: 1100px) {
    .omo-template-sidebar__stats,
    .omo-template-form__grid,
    .omo-template-property__main,
    .omo-template-property__list-options {
        grid-template-columns: 1fr;
    }

    .omo-template-form-panel__header,
    .omo-template-section__head,
    .omo-template-form__footer {
        flex-direction: column;
        align-items: stretch;
    }

    .omo-template-form-panel__badges {
        justify-content: flex-start;
    }

    .omo-template-property {
        grid-template-columns: 1fr;
    }

    .omo-template-property__actions {
        justify-content: stretch;
    }
}
</style>
