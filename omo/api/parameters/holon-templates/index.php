<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use dbObject\Organization;

function omoHolonTemplateEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? 0);
$selectedTemplateId = (int)($_GET['hid'] ?? 0);
$isCompactMode = !empty($_GET['compact']);
$organization = new Organization();
$editorData = null;
$errorMessage = '';
$isHolonDefinitionMode = false;

if ($organizationId <= 0) {
    $errorMessage = "Aucune organisation n'est actuellement selectionnee.";
} elseif (!$organization->load($organizationId)) {
    $errorMessage = "L'organisation demandee est introuvable.";
} else {
    if ($selectedTemplateId > 0) {
        $editorData = $organization->getHolonDefinitionEditorData($selectedTemplateId);
    }

    if ($editorData === null) {
        $editorData = $organization->getHolonTemplateEditorData($contextHolonId);
    }

    $isHolonDefinitionMode = (($editorData['editorMode'] ?? 'template') === 'holon-definition');
}
?>
<div class="omo-template-editor omo-panel-view">
    <div class="omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title"><?= $isHolonDefinitionMode ? 'Proprietes de l organisation' : 'Modeles de holons' ?></h2>
            <p class="omo-panel-view__description">
                <?php if ($isHolonDefinitionMode): ?>
                    Modifiez ici les proprietes, illustrations et reglages locaux du holon d organisation,
                    meme lorsqu il ne s agit pas d un template.
                <?php else: ?>
                    Definissez ici les types de noeuds reutilisables de votre organisation:
                    cercle, role, projet, tache ou toute autre structure hierarchique portee par les holons.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="omo-panel-view__body">
        <?php if ($errorMessage !== ''): ?>
            <div class="omo-empty-state"><?= omoHolonTemplateEscape($errorMessage) ?></div>
        <?php else: ?>
            <div class="omo-template-editor__layout<?= $isCompactMode ? ' omo-template-editor__layout--compact' : '' ?><?= $isHolonDefinitionMode ? ' omo-template-editor__layout--holon-definition' : '' ?>" id="omo-holon-template-editor">
                <aside class="omo-template-sidebar">
                    <div class="omo-template-sidebar__hero">
                        <div class="omo-template-editor__eyebrow">Contexte actuel</div>
                        <h3><?= omoHolonTemplateEscape($editorData['contextHolonName'] ?? ($editorData['organizationName'] ?? '')) ?></h3>
                        <p>
                            <?= $isHolonDefinitionMode
                                ? 'Ce panneau agit comme un editeur de definitions locales pour ce holon reel.'
                                : 'Creez une bibliotheque de modeles reutilisables pour vos cercles, roles, projets et autres structures.' ?>
                        </p>
                    </div>

                    <div class="omo-template-sidebar__stats" id="omo-template-summary"></div>

                    <div class="omo-template-sidebar__actions"<?= $isHolonDefinitionMode ? ' hidden' : '' ?>>
                        <button type="button" class="omo-button omo-button--secondary" data-template-action="new-root">Nouveau modele</button>
                        <button type="button" class="omo-button omo-button--ghost" data-template-action="new-child" disabled>Sous-modele</button>
                    </div>

                    <div class="omo-template-tree-wrap">
                        <div class="omo-template-tree-wrap__title"><?= $isHolonDefinitionMode ? 'Holon edite' : 'Arborescence des modeles' ?></div>
                        <div class="omo-template-tree" id="omo-template-tree"></div>
                    </div>
                </aside>

                <section class="omo-template-form-panel">
                    <div class="omo-template-form-panel__header">
                        <div>
                            <div class="omo-template-editor__eyebrow">Edition</div>
                            <h3 class="omo-template-form-panel__title" id="omo-template-form-title"><?= $isHolonDefinitionMode ? 'Organisation' : 'Nouveau modele' ?></h3>
                            <p class="omo-template-form-panel__description" id="omo-template-form-description">
                                <?= $isHolonDefinitionMode
                                    ? 'Ajustez ici les proprietes locales de cette organisation.'
                                    : "Choisissez un type de base, sa place dans l'arborescence et les proprietes qu'il transmettra." ?>
                            </p>
                        </div>
                        <div class="omo-template-form-panel__badges" id="omo-template-form-badges"></div>
                    </div>

                    <div class="omo-template-editor__status" id="omo-template-status" hidden></div>

                    <form id="omo-template-form" class="omo-template-form">
                        <section class="omo-template-section">
                            <div class="omo-template-section__title"><?= $isHolonDefinitionMode ? 'Holon' : 'Structure du modele' ?></div>

                            <div class="omo-template-form__grid">
                                <label class="omo-field<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>" id="omo-template-type-field">
                                    <span>Type de base</span>
                                    <select name="typeId" id="omo-template-type" required></select>
                                </label>

                                <label class="omo-field<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <span>Herite de</span>
                                    <select name="parentId" id="omo-template-parent"></select>
                                </label>

                                <label class="omo-field omo-field--full">
                                    <span><?= $isHolonDefinitionMode ? 'Nom' : 'Nom du modele' ?></span>
                                    <input type="text" name="name" id="omo-template-name" maxlength="255" required>
                                </label>

                                <div class="omo-template-flags omo-field--full<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-visible">
                                        <span>Visible</span>
                                        <small>Afficher ce template dans le cercle ou il est defini.</small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-mandatory">
                                        <span>Mandatory</span>
                                        <small>Indique que les sous-cercles devront implementer ce template.</small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-locked-name">
                                        <span>Locked name</span>
                                        <small>Impose le meme nom a toutes les instances de ce template.</small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-unique">
                                        <span>Unique</span>
                                        <small>Limite a une seule implementation par cercle, groupes compris.</small>
                                    </label>
                                    <label class="omo-template-flags__option">
                                        <input type="checkbox" id="omo-template-link">
                                        <span>Link</span>
                                        <small>Indique que le role appartient aussi au cercle englobant.</small>
                                    </label>
                                </div>

                            </div>
                        </section>

                        <section class="omo-template-section">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title">Proprietes</div>
                                    <p class="omo-template-section__description">
                                        <?= $isHolonDefinitionMode
                                            ? 'Ajoutez ici les proprietes directement portees par cette organisation.'
                                            : 'Ajoutez les proprietes visibles sur les noeuds derives de ce modele.' ?>
                                    </p>
                                </div>
                                <button type="button" class="omo-button omo-button--secondary" id="omo-template-add-property">Ajouter une propriete</button>
                            </div>

                            <div class="omo-template-properties" id="omo-template-properties"></div>
                        </section>

                        <section class="omo-template-section">
                            <div class="omo-template-section__head">
                                <div>
                                    <div class="omo-template-section__title">Apparence</div>
                                    <p class="omo-template-section__description">
                                        La couleur, l'icone et la banniere viennent apres la definition des proprietes.
                                    </p>
                                </div>
                            </div>

                            <div class="omo-template-form__grid">
                                <div class="omo-field omo-color-field" id="omo-template-color-field">
                                    <div class="omo-color-field__head">
                                        <span>Couleur</span>
                                        <label class="omo-color-field__toggle">
                                            <input type="checkbox" id="omo-template-color-enabled">
                                            <span>Redefinir</span>
                                        </label>
                                    </div>
                                    <div class="omo-color-field__body" id="omo-template-color-body">
                                        <input type="color" name="color" id="omo-template-color" value="#f59e0b">
                                        <small>Sinon la couleur reste vide.</small>
                                    </div>
                                </div>

                                <div class="omo-field omo-field--full<?= $isHolonDefinitionMode ? ' omo-template-field--hidden' : '' ?>">
                                    <span>Illustrations transmises</span>
                                    <div class="omo-template-media-grid">
                                        <div class="omo-template-media-card">
                                            <div class="omo-template-media-card__head">
                                                <div class="omo-template-media-card__title">Icone</div>
                                                <label class="omo-template-media-card__lock">
                                                    <input type="checkbox" id="omo-template-locked-icon">
                                                    <span>Locked icon</span>
                                                </label>
                                            </div>
                                            <div id="omo-template-icon-field"></div>
                                        </div>
                                        <div class="omo-template-media-card">
                                            <div class="omo-template-media-card__head">
                                                <div class="omo-template-media-card__title">Banniere</div>
                                                <label class="omo-template-media-card__lock">
                                                    <input type="checkbox" id="omo-template-locked-banner">
                                                    <span>Locked banner</span>
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
                                    <div class="omo-template-section__title">Partage public</div>
                                    <p class="omo-template-section__description">
                                        Ces champs servent uniquement quand l organisation est partagee comme modele reutilisable.
                                    </p>
                                </div>
                            </div>

                            <div class="omo-template-form__grid">
                                <label class="omo-template-flags__option omo-field--full">
                                    <input type="checkbox" id="omo-template-share-public">
                                    <span>Partager publiquement ce modele d'organisation</span>
                                    <small>Active un modele d organisation recuperable par d autres personnes.</small>
                                </label>

                                <div class="omo-field omo-field--full" id="omo-template-public-share-fields" hidden>
                                    <div class="omo-template-form__grid">
                                        <label class="omo-field omo-field--full">
                                            <span>Nom public du modele</span>
                                            <input type="text" id="omo-template-public-name" maxlength="255">
                                        </label>

                                        <div class="omo-field omo-field--full">
                                            <span>Illustrations du modele partage</span>
                                            <div class="omo-template-media-grid">
                                                <div class="omo-template-media-card">
                                                    <div class="omo-template-media-card__head">
                                                        <div class="omo-template-media-card__title">Logo / Icone</div>
                                                    </div>
                                                    <div id="omo-template-public-icon-field"></div>
                                                </div>
                                                <div class="omo-template-media-card">
                                                    <div class="omo-template-media-card__head">
                                                        <div class="omo-template-media-card__title">Banniere</div>
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
                            <?php if ($isCompactMode): ?>
                                <button type="button" class="omo-button omo-button--ghost" id="omo-template-cancel">Fermer</button>
                            <?php endif; ?>
                            <button type="submit" class="omo-button omo-button--primary"><?= $isHolonDefinitionMode ? 'Enregistrer l organisation' : 'Enregistrer le modele' ?></button>
                        </div>
                    </form>
                </section>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($editorData !== null): ?>
<script src="/omo/assets/js/sized-image-field.js"></script>
<script>
(() => {
const omoHolonTemplateState = {
    data: <?= json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    selectedId: <?= (int)$selectedTemplateId ?>,
    compactMode: <?= $isCompactMode ? 'true' : 'false' ?>,
    statusTimer: null
};

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
    properties: omoHolonTemplateRoot.querySelector('#omo-template-properties'),
    selectionHint: omoHolonTemplateRoot.querySelector('#omo-template-selection-hint'),
    cancel: omoHolonTemplateRoot.querySelector('#omo-template-cancel'),
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

function omoHolonTemplateIsHolonDefinitionMode() {
    return String((omoHolonTemplateState.data || {}).editorMode || 'template') === 'holon-definition';
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

    if (Number(normalizedProperty.formatId || 0) === 2) {
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
        properties: omoHolonTemplateReadProperties()
    };
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
        "Changer l'heritage de ce modele peut modifier ou masquer des proprietes et des valeurs sur ce modele, ainsi que sur les modeles et holons qui en heritent.\n\nConfirmer cette operation ?"
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
        + '  <span>modele' + (templates.length > 1 ? 's' : '') + '</span>'
        + '</div>'
        + '<div class="omo-template-stat">'
        + '  <strong>' + propertyCount + '</strong>'
        + '  <span>propriete' + (propertyCount > 1 ? 's' : '') + '</span>'
        + '</div>';
}

function omoHolonTemplateRenderTreeNodes(nodes) {
    if (!nodes || !nodes.length) {
        return '<div class="omo-template-tree__empty">Aucun modele n est encore defini.</div>';
    }

    let html = '<ul class="omo-template-tree__list">';
    nodes.forEach(function (node) {
        const isSelected = Number(omoHolonTemplateState.selectedId || 0) === Number(node.id);
        const propertyCount = Array.isArray(node.properties) ? node.properties.length : 0;
        const childCount = Array.isArray(node.children) ? node.children.length : 0;

        html += '<li class="omo-template-tree__item">';
        html += '<button type="button" class="omo-template-tree__button' + (isSelected ? ' is-selected' : '') + '" data-template-select="' + Number(node.id) + '">';
        html += '  <span class="omo-template-tree__name">' + omoHolonTemplateEscapeHtml(node.name) + '</span>';
        html += '  <span class="omo-template-tree__meta-row">';
        html += '      <span class="omo-template-chip omo-template-chip--accent">' + omoHolonTemplateEscapeHtml(node.typeLabel || '') + '</span>';
        html += '      <span class="omo-template-chip">' + propertyCount + ' propriete' + (propertyCount > 1 ? 's' : '') + '</span>';
        if (childCount > 0) {
            html += '  <span class="omo-template-chip">' + childCount + ' sous-modele' + (childCount > 1 ? 's' : '') + '</span>';
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
        label: 'Racine des modeles'
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
        + '  <label class="omo-field omo-template-property__value-field">'
        + '      <span>Valeur heritee par defaut</span>'
        + '      <div class="omo-template-property__value-control">' + omoHolonTemplateRenderValueInputHtml(propertyFormatId, propertyValue) + '</div>'
        + '      <small>Si cette valeur reste vide, chaque holon derive pourra definir librement son contenu.</small>'
        + '  </label>'
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

function omoHolonTemplateRenderSimpleListRow(listItemType, value, disabled) {
    const inputType = omoHolonTemplateGetListInputType(listItemType);
    const safeValue = value !== undefined && value !== null ? String(value) : '';
    const stepAttribute = inputType === 'number' ? ' step="any"' : '';
    const disabledAttribute = disabled ? ' disabled' : '';

    return ''
        + '<div class="omo-template-list-input__row">'
        + '  <input type="' + inputType + '" class="omo-template-property__value-item" value="' + omoHolonTemplateEscapeHtml(safeValue) + '"' + stepAttribute + disabledAttribute + '>'
        + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__move" data-list-move="-1" aria-label="Monter"' + disabledAttribute + '>&uarr;</button>'
        + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__move" data-list-move="1" aria-label="Descendre"' + disabledAttribute + '>&darr;</button>'
        + '  <button type="button" class="omo-button omo-button--ghost omo-template-list-input__remove" data-list-remove="1" aria-label="Retirer"' + disabledAttribute + '>&times;</button>'
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

function omoHolonTemplateGetListHelpText(property) {
    const listItemType = String(property.listItemType || 'text');
    if (listItemType === 'number') {
        return 'Une ligne par nombre. Laissez vide pour ne rien imposer.';
    }
    if (listItemType === 'date') {
        return 'Une ligne par date au format AAAA-MM-JJ. Laissez vide pour ne rien imposer.';
    }
    if (listItemType === 'holon') {
        return 'Cochez les holons de base a inclure dans ce template. Les instances pourront ensuite en ajouter.';
    }
    return 'Une ligne par element. Laissez vide pour ne rien imposer.';
}

function omoHolonTemplateGetValueHelpText(formatId, property) {
    const numericFormatId = Number(formatId || 0);
    if (numericFormatId === 2) {
        return omoHolonTemplateGetListHelpText(omoHolonTemplateNormalizeProperty(property));
    }
    if (numericFormatId === 3) {
        return 'Laissez vide pour ne rien imposer. Utilisez un nombre entier ou decimal.';
    }
    if (numericFormatId === 4) {
        return 'Laissez vide pour ne rien imposer. La date sera heritee au format AAAA-MM-JJ.';
    }
    return 'Si cette valeur reste vide, chaque holon derive pourra definir librement son contenu.';
}

function omoHolonTemplateRenderListConfigHtml(property) {
    if (Number(property.formatId || 0) !== 2) {
        return '';
    }

    const configDisabled = property.isInherited ? ' disabled' : '';

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
        + '      <span>Type des elements de liste</span>'
        + '      <select class="omo-template-property__list-item-type"' + configDisabled + '>' + listItemTypeOptions + '</select>'
        + '  </label>'
        + (String(property.listItemType) === 'holon'
            ? '  <div class="omo-field omo-template-property__holon-types"><span>Types de holons autorises</span><div class="omo-template-property__check-grid">' + holonTypeOptions + '</div></div>'
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

        if (String(normalizedProperty.listItemType) === 'holon') {
            const allowedTypeIds = normalizedProperty.listHolonTypeIds || [];
            const templateOptions = (omoHolonTemplateState.data.templateCatalog || []).filter(function (template) {
                return allowedTypeIds.length === 0 || allowedTypeIds.indexOf(Number(template.typeId)) >= 0;
            });

            if (!templateOptions.length) {
                return '<div class="omo-template-property__empty-note">Aucun template disponible pour les types choisis.</div>';
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

        return omoHolonTemplateRenderSimpleListInput(normalizedProperty.listItemType, listValues, !normalizedProperty.canEditValue);
    }

    if (formatId === 3) {
        return '<input type="number" step="any" class="omo-template-property__value" value="' + omoHolonTemplateEscapeHtml(safeValue) + '" placeholder="Ex.: 42"' + valueDisabled + '>';
    }

    if (formatId === 4) {
        return '<input type="date" class="omo-template-property__value" value="' + omoHolonTemplateEscapeHtml(safeValue) + '"' + valueDisabled + '>';
    }

    return '<textarea class="omo-template-property__value" rows="4" placeholder="Laissez vide pour ne rien imposer."' + valueDisabled + '>' + omoHolonTemplateEscapeHtml(safeValue) + '</textarea>';
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
        + '  <div class="omo-template-property__inherited-label">Valeur heritee</div>'
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
    const removeLabel = normalizedProperty.isInherited ? 'Exclure' : 'Retirer';
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
        + '  <label class="omo-field omo-template-property__value-field">'
        + '      <span>Valeur heritee par defaut</span>'
        + '      <div class="omo-template-property__value-control">' + omoHolonTemplateRenderValueInputHtml(normalizedProperty) + '</div>'
        + '      <small class="omo-template-property__value-help">' + omoHolonTemplateEscapeHtml(omoHolonTemplateGetValueHelpText(normalizedProperty.formatId, normalizedProperty)) + '</small>'
        + '  </label>'
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
    if (Number(formatId || 0) === 2) {
        if (String(listItemType || 'text') === 'holon') {
            const selectedIds = Array.from(row.querySelectorAll('.omo-template-property__value--holon:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        const items = Array.from(row.querySelectorAll('.omo-template-property__value-item')).map(function (input) {
            return String(input.value || '').trim();
        }).filter(Boolean);
        return items.length ? JSON.stringify(items) : '';
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
        ? '<span class="omo-template-chip">Heritee</span>'
        : '<span class="omo-template-chip omo-template-chip--accent">Locale</span>';

    if (omoHolonTemplateIsHolonDefinitionMode()) {
        return ''
            + '<div class="omo-template-property__meta">'
            + '  <div class="omo-template-property__origin">' + originBadge + '</div>'
            + '</div>';
    }

    const mandatoryDisabled = property.inheritedMandatory ? ' disabled' : '';
    const lockedDisabled = property.inheritedLocked ? ' disabled' : '';

    return ''
        + '<div class="omo-template-property__meta">'
        + '  <div class="omo-template-property__origin">' + originBadge + '</div>'
        + '  <label class="omo-template-property__toggle"><input type="checkbox" class="omo-template-property__mandatory"' + (property.effectiveMandatory ? ' checked' : '') + mandatoryDisabled + '> <span>Mandatory</span></label>'
        + '  <label class="omo-template-property__toggle"><input type="checkbox" class="omo-template-property__locked"' + (property.effectiveLocked ? ' checked' : '') + lockedDisabled + '> <span>Locked</span></label>'
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
    const structureDisabled = normalizedProperty.isInherited ? ' disabled' : '';
    const removeDisabled = normalizedProperty.canDelete ? '' : ' disabled';
    const removeLabel = normalizedProperty.isInherited ? 'Exclure' : 'Retirer';
    const inheritedValueHtml = omoHolonTemplateRenderInheritedValueHtml(normalizedProperty);
    const valueFieldTitle = normalizedProperty.isInherited
        ? (normalizedProperty.canEditValue ? 'Valeur locale ajoutee' : 'Valeur heritee par defaut')
        : 'Valeur heritee par defaut';
    const valueEditorHtml = normalizedProperty.isInherited && !normalizedProperty.canEditValue
        ? ''
        : '<label class="omo-field omo-template-property__value-field">'
            + '      <span>' + omoHolonTemplateEscapeHtml(valueFieldTitle) + '</span>'
            + '      <div class="omo-template-property__value-control">' + omoHolonTemplateRenderValueInputHtml(normalizedProperty) + '</div>'
            + '      <small class="omo-template-property__value-help">' + omoHolonTemplateEscapeHtml(omoHolonTemplateGetValueHelpText(normalizedProperty.formatId, normalizedProperty)) + '</small>'
            + '  </label>';

    row.innerHTML = ''
        + '<div class="omo-template-property__index"></div>'
        + '<div class="omo-template-property__body">'
        + '  <div class="omo-template-property__main">'
        + '      <label class="omo-field">'
        + '          <span>Nom</span>'
        + '          <input type="text" class="omo-template-property__name" maxlength="255" value="' + omoHolonTemplateEscapeHtml(normalizedProperty.name || '') + '" placeholder="Ex.: Propriete"' + structureDisabled + '>'
        + '      </label>'
        + '      <label class="omo-field">'
        + '          <span>Format</span>'
        + '          <select class="omo-template-property__format"' + structureDisabled + '>' + formatOptions + '</select>'
        + '      </label>'
        + '  </div>'
        + omoHolonTemplateRenderPropertyMetaHtml(normalizedProperty)
        + omoHolonTemplateRenderListConfigHtml(normalizedProperty)
        + inheritedValueHtml
        + valueEditorHtml
        + '  <div class="omo-template-property__actions">'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="-1">Monter</button>'
        + '      <button type="button" class="omo-button omo-button--ghost" data-property-move="1">Descendre</button>'
        + '      <button type="button" class="omo-button omo-button--danger" data-property-remove="1"' + removeDisabled + '>' + removeLabel + '</button>'
        + '  </div>'
        + '</div>';

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

        contentHtml = '<ul class="omo-template-property__inherited-list">'
            + items.map(function (item) {
                return '<li>' + omoHolonTemplateEscapeHtml(item) + '</li>';
            }).join('')
            + '</ul>';
    } else {
        contentHtml = '<div class="omo-template-property__inherited-text">'
            + omoHolonTemplateEscapeHtml(inheritedValue).replace(/\n/g, '<br>')
            + '</div>';
    }

    return ''
        + '<div class="omo-template-property__inherited-block">'
        + '  <div class="omo-template-property__inherited-label">Valeur heritee</div>'
        + '  <div class="omo-template-property__inherited-view">'
        +        contentHtml
        + '  </div>'
        + '</div>';
}

function omoHolonTemplateRenderProperties(properties) {
    omoHolonTemplateElements.properties.innerHTML = '';

    if (!properties || !properties.length) {
        omoHolonTemplateElements.properties.innerHTML = '<div class="omo-template-properties__empty">Aucune propriete pour ce modele. Vous pouvez commencer par en ajouter une.</div>';
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

    omoHolonTemplateElements.status.hidden = false;
    omoHolonTemplateElements.status.className = 'omo-template-editor__status is-' + tone;
    omoHolonTemplateElements.status.innerHTML = ''
        + '<div class="omo-template-editor__status-copy">' + omoHolonTemplateEscapeHtml(message) + '</div>'
        + '<button type="button" class="omo-template-editor__status-close" aria-label="Fermer le message">&times;</button>';
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

    badges.push('<span class="omo-template-chip">' + propertyCount + ' propriete' + (propertyCount > 1 ? 's' : '') + '</span>');

    if (!omoHolonTemplateIsHolonDefinitionMode() && Number(template.inheritsFromId || 0) > 0) {
        badges.push('<span class="omo-template-chip">Heritage actif</span>');
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
        omoHolonTemplateElements.selectionHint.textContent = 'Modification des proprietes locales de cette organisation.';
        omoHolonTemplateElements.formTitle.textContent = current.name || 'Organisation';
        omoHolonTemplateElements.formDescription.textContent = 'Ajustez ce holon et les proprietes qu il porte directement.';
    } else {
        omoHolonTemplateElements.selectionHint.textContent = isExisting
            ? 'Modification du modele selectionne.'
            : 'Nouveau modele non encore enregistre.';
        omoHolonTemplateElements.formTitle.textContent = isExisting ? (current.name || 'Modele') : 'Nouveau modele';
        omoHolonTemplateElements.formDescription.textContent = isExisting
            ? 'Ajustez ce modele et ses proprietes heritables.'
            : 'Choisissez son type de base puis ajoutez les proprietes a transmettre.';
    }
    omoHolonTemplateRenderFormBadges(current);
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

function omoHolonTemplateSave(event) {
    event.preventDefault();
    omoHolonTemplateClearStatus();

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

    fetch(saveUrl, {
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
                throw new Error(result.data && result.data.message ? result.data.message : (omoHolonTemplateIsHolonDefinitionMode() ? "Impossible d'enregistrer l'organisation." : "Impossible d'enregistrer le modele."));
            }

            omoHolonTemplateState.data = result.data.data;
            omoHolonTemplateState.selectedId = result.data.template ? Number(result.data.template.id) : null;
            omoHolonTemplateRenderTree();
            omoHolonTemplateFillForm(result.data.template || omoHolonTemplateBuildDraft());
            omoHolonTemplateShowStatus(result.data.message || 'Modele enregistre.', 'success');
            if (omoHolonTemplateState.compactMode) {
                const route = typeof parseUrl === 'function'
                    ? parseUrl()
                    : {
                        oid: Number(omoHolonTemplateState.data.organizationId || 0),
                        cid: null
                    };
                const targetHolonId = result.data.template ? Number(result.data.template.id || 0) : 0;

                if (targetHolonId > 0 && typeof loadContent === 'function') {
                    loadContent('#panel-left', 'api/getOrg.php?oid=' + Number(route.oid || omoHolonTemplateState.data.organizationId || 0) + '&cid=' + targetHolonId);
                }

                window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                    detail: {
                        cid: targetHolonId > 0 ? targetHolonId : null
                    }
                }));

                if (typeof closeDrawer === 'function') {
                    closeDrawer('drawer_holon_create');
                }
            }
        })
        .catch(function (error) {
            omoHolonTemplateShowStatus(error && error.message ? error.message : (omoHolonTemplateIsHolonDefinitionMode() ? "Impossible d'enregistrer l'organisation." : "Impossible d'enregistrer le modele."), 'error');
        });
}

if (omoHolonTemplateElements.form) {
    omoHolonTemplateElements.form.addEventListener('submit', omoHolonTemplateSave);
}

if (omoHolonTemplateElements.cancel) {
    omoHolonTemplateElements.cancel.addEventListener('click', function () {
        if (typeof closeDrawer === 'function') {
            closeDrawer('drawer_holon_create');
        }
    });
}

if (omoHolonTemplateElements.sharePublic) {
    omoHolonTemplateElements.sharePublic.addEventListener('change', function () {
        omoHolonTemplateSyncPublicShareFields();
    });
}

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
            omoHolonTemplateSelect(Number(selectButton.getAttribute('data-template-select')));
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

omoHolonTemplateRenderTree();
if (Number(omoHolonTemplateState.selectedId || 0) > 0 && omoHolonTemplateFind(omoHolonTemplateState.selectedId)) {
    omoHolonTemplateSelect(omoHolonTemplateState.selectedId);
} else {
    omoHolonTemplateFillForm(omoHolonTemplateBuildDraft(0));
}
})();
</script>
<?php endif; ?>

<style>
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
    border-radius: 16px;
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
    border-radius: 14px;
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
    border-radius: 16px;
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
    border-radius: 12px;
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
    border-radius: 12px;
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
    border-radius: 12px;
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
    border-radius: 16px;
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
    border-radius: 14px;
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
    border-radius: 16px;
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

.omo-field span {
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
    border-radius: 12px;
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
    border-radius: 14px;
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
    grid-template-columns: minmax(320px, 1.6fr) minmax(240px, 0.8fr);
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
    grid-template-columns: minmax(220px, 320px) minmax(0, 1fr);
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
    border-radius: 12px;
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
    border-radius: 14px;
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

.omo-template-property__value-item {
    width: 100%;
}

.omo-template-list-input__add,
.omo-template-list-input__move,
.omo-template-list-input__remove {
    min-width: 42px;
    padding-inline: 0;
}

.omo-template-property__empty-note {
    padding: 12px;
    border: 1px dashed var(--color-border);
    border-radius: 12px;
    color: var(--color-text-light);
    background: var(--color-surface);
}

.omo-template-form__footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    position: sticky;
    bottom: 0;
    z-index: 10;
    padding: 16px;
    margin-top: 8px;
    border-top: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
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
    color: #b91c1c;
    border-color: color-mix(in srgb, #dc2626 26%, var(--color-border));
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
