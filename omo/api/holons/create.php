<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? 0);
$holonId = (int)($_GET['hid'] ?? 0);
$organization = new Organization();
$editorData = null;
$errorMessage = '';

if ($organizationId <= 0) {
    $errorMessage = "Aucune organisation n'est actuellement sélectionnée.";
} elseif (!$organization->load($organizationId)) {
    $errorMessage = "L'organisation demandée est introuvable.";
} else {
    $editorData = $organization->getHolonCreationEditorData($contextHolonId, $holonId);
    if ($holonId > 0 && (($editorData['mode'] ?? 'create') !== 'edit')) {
        $errorMessage = "Le holon demandé est introuvable.";
    } elseif (($editorData['mode'] ?? 'create') === 'edit' && !($editorData['canEdit'] ?? false)) {
        $errorMessage = "Ce holon ne peut pas être édité avec ce formulaire.";
    } elseif (($editorData['mode'] ?? 'create') !== 'edit' && !($editorData['canCreate'] ?? false)) {
        $errorMessage = "Ce holon n'autorise pas l'ajout d'enfant.";
    } elseif (count($editorData['templateCatalog'] ?? array()) === 0) {
        $errorMessage = ($editorData['mode'] ?? 'create') === 'edit'
            ? "Aucun modèle n'est disponible dans le contexte de ce holon."
            : "Aucun modèle n'est disponible dans ce contexte pour créer un nouveau holon.";
    }
}
?>
<div class="omo-holon-create omo-panel-view">
    <div class="omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title"><?= omoApiEscape((($editorData['mode'] ?? 'create') === 'edit') ? 'Modifier le holon' : 'Nouveau holon') ?></h2>
            <p class="omo-panel-view__description">
                <?php if (($editorData['mode'] ?? 'create') === 'edit'): ?>
                    Modifiez ici ce holon à partir d'un modèle disponible dans
                    <?= omoApiEscape($editorData['contextHolonName'] ?? '') ?>.
                <?php else: ?>
                    Créez ici un nouveau cercle ou rôle à partir d'un modèle disponible dans
                    <?= omoApiEscape($editorData['contextHolonName'] ?? '') ?>.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="omo-panel-view__body">
        <?php if ($errorMessage !== ''): ?>
            <div class="omo-holon-create__empty"><?= omoApiEscape($errorMessage) ?></div>
        <?php else: ?>
            <div class="omo-holon-create__layout" id="omo-holon-create-editor">
                <section class="omo-holon-create__panel">
                    <div class="omo-holon-create__status" id="omo-holon-create-status" hidden></div>

                    <form id="omo-holon-create-form" class="omo-holon-create__form">
                        <div class="omo-panel-view__body_content">
                        <section class="omo-holon-create__section">
                            <div class="omo-holon-create__section-title"><?= omoApiEscape((($editorData['mode'] ?? 'create') === 'edit') ? 'Édition' : 'Création') ?></div>

                            <div class="omo-holon-create__grid">
                                <label class="omo-holon-create__field">
                                    <span>Modèle</span>
                                    <select id="omo-holon-create-template" required></select>
                                </label>

                                <label class="omo-holon-create__field omo-holon-create__field--full">
                                    <span>Nom</span>
                                    <input type="text" id="omo-holon-create-name" maxlength="255" required>
                                    <small id="omo-holon-create-name-help"></small>
                                </label>

                            </div>

                            <div class="omo-holon-create__template-meta" id="omo-holon-create-template-meta"></div>
                        </section>

                        <section class="omo-holon-create__section">
                            <div class="omo-holon-create__section-head">
                                <div>
                                    <div class="omo-holon-create__section-title">Propriétés</div>
                                    <p class="omo-holon-create__section-description">
                                        Les propriétés héritées du modèle sont affichées ci-dessous.
                                    </p>
                                </div>
                            </div>

                            <div class="omo-holon-create__properties" id="omo-holon-create-properties"></div>
                        </section>
                        </div>
                        <section class="omo-holon-create__section">
                            <div class="omo-holon-create__section-head">
                                <div>
                                    <div class="omo-holon-create__section-title">Apparence</div>
                                    <p class="omo-holon-create__section-description">
                                        Les choix visuels viennent ici, apres les proprietes plus importantes.
                                    </p>
                                </div>
                            </div>

                            <div class="omo-holon-create__grid">
                                <label class="omo-holon-create__field" id="omo-holon-create-color-field">
                                    <div class="omo-holon-create__color-head">
                                        <span>Couleur</span>
                                        <span class="omo-holon-create__color-toggle">
                                            <input type="checkbox" id="omo-holon-create-color-enabled">
                                            <span>Redefinir</span>
                                        </span>
                                    </div>
                                    <div class="omo-holon-create__color-body" id="omo-holon-create-color-body">
                                        <input type="color" id="omo-holon-create-color" value="#f59e0b">
                                        <small>Sinon la couleur reste vide et l'heritage s'applique.</small>
                                    </div>
                                </label>

                                <div class="omo-holon-create__field omo-holon-create__field--full">
                                    <span>Illustrations</span>
                                    <div class="omo-holon-create__media-grid">
                                        <div class="omo-holon-create__media-card">
                                            <div class="omo-holon-create__media-label">Icone</div>
                                            <div id="omo-holon-create-icon-field"></div>
                                        </div>
                                        <div class="omo-holon-create__media-card">
                                            <div class="omo-holon-create__media-label">Banniere</div>
                                            <div id="omo-holon-create-banner-field"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="omo-holon-create__footer">
                            <div class="omo-holon-create__hint" id="omo-holon-create-hint"></div>
                            <div class="omo-holon-create__actions">
                                <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost" id="omo-holon-create-cancel">Fermer</button>
                                <button type="submit" class="omo-holon-create__button omo-holon-create__button--primary"><?= omoApiEscape((($editorData['mode'] ?? 'create') === 'edit') ? 'Enregistrer' : 'Créer le holon') ?></button>
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
<script>
(() => {
const state = {
    data: <?= json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    statusTimer: null
};

const root = document.getElementById('omo-holon-create-editor');
if (!root) {
    return;
}

const elements = {
    status: root.querySelector('#omo-holon-create-status'),
    form: root.querySelector('#omo-holon-create-form'),
    template: root.querySelector('#omo-holon-create-template'),
    name: root.querySelector('#omo-holon-create-name'),
    colorEnabled: root.querySelector('#omo-holon-create-color-enabled'),
    colorBody: root.querySelector('#omo-holon-create-color-body'),
    color: root.querySelector('#omo-holon-create-color'),
    iconField: root.querySelector('#omo-holon-create-icon-field'),
    bannerField: root.querySelector('#omo-holon-create-banner-field'),
    meta: root.querySelector('#omo-holon-create-template-meta'),
    properties: root.querySelector('#omo-holon-create-properties'),
    hint: root.querySelector('#omo-holon-create-hint'),
    nameHelp: root.querySelector('#omo-holon-create-name-help'),
    cancel: root.querySelector('#omo-holon-create-cancel')
};

const mediaFields = {
    icon: null,
    banner: null
};

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

// Liste les modèles
function getTemplates() {
    return Array.isArray(state.data.templateCatalog) ? state.data.templateCatalog : [];
}

// Liste les holons
function getHolonCatalog() {
    return Array.isArray(state.data.holonCatalog) ? state.data.holonCatalog : [];
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
            elements.nameHelp.textContent = 'Le nom est verrouille par le modele.';
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
            ? 'Si le nom est vide, celui du modele sera utilise.'
            : '';
    }
}

// Synchronise champ couleur
function syncColorField() {
    const isEnabled = Boolean(elements.colorEnabled && elements.colorEnabled.checked);

    if (elements.colorBody) {
        elements.colorBody.hidden = !isEnabled;
    }

    if (elements.color) {
        elements.color.disabled = !isEnabled;
    }
}

function getMediaDisplayConfig(kind) {
    if (kind === 'banner') {
        return {
            displayWidth: 360,
            displayHeight: 202,
            targetWidth: 960,
            targetHeight: 540,
            emptyText: 'Aucune bannière définie pour ce holon.'
        };
    }

    return {
        displayWidth: 160,
        displayHeight: 160,
        targetWidth: 500,
        targetHeight: 500,
        emptyText: 'Aucune icône définie pour ce holon.'
    };
}

function resolveMediaState(kind, template) {
    const editingHolon = getEditingHolon();
    const suffix = kind === 'icon' ? 'Icon' : 'Banner';
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
        ['icon', elements.iconField, 'Icône'],
        ['banner', elements.bannerField, 'Bannière']
    ].forEach(function (entry) {
        const kind = entry[0];
        const target = entry[1];
        const label = entry[2];
        if (!target) {
            return;
        }

        const mediaState = resolveMediaState(kind, template);
        const config = getMediaDisplayConfig(kind);
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

// Rend ligne liste
function renderSimpleListRow(listItemType, value) {
    const inputType = getListInputType(listItemType);
    const stepAttribute = inputType === 'number' ? ' step="any"' : '';
    return ''
        + '<div class="omo-holon-create__list-row">'
        + '  <input type="' + inputType + '" class="omo-holon-create__property-value-item" value="' + escapeHtml(value !== undefined && value !== null ? value : '') + '"' + stepAttribute + '>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-move" data-list-move="-1" aria-label="Monter">&#8593;</button>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-move" data-list-move="1" aria-label="Descendre">&#8595;</button>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-remove" data-list-remove="1" aria-label="Retirer">&times;</button>'
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
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--secondary omo-holon-create__list-add" data-list-add="1">Ajouter une valeur</button>'
        + '</div>';
}

// Rend champ propriété
function renderPropertyInput(property) {
    const formatId = Number(property.formatId || 0);
    const localValue = property.value !== undefined && property.value !== null
        ? String(property.value)
        : '';

    if (!property.canEditValue) {
        return '<div class="omo-holon-create__locked-note">Cette valeur est verrouillée par le modèle.</div>';
    }

    if (formatId === 2) {
        if (String(property.listItemType || 'text') === 'holon') {
            const allowedTypeIds = Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds.map(Number) : [];
            const holonOptions = getHolonCatalog().filter(function (holon) {
                return allowedTypeIds.length === 0 || allowedTypeIds.indexOf(Number(holon.typeId || 0)) >= 0;
            });
            const selectedIds = parseStoredListValue(localValue).map(Number);

            if (!holonOptions.length) {
                return '<div class="omo-holon-create__empty-note">Aucun holon disponible pour les types autorisés.</div>';
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

        return renderSimpleListInput(property.listItemType || 'text', parseStoredListValue(localValue));
    }

    if (formatId === 3) {
        return '<input type="number" step="any" class="omo-holon-create__property-value" value="' + escapeHtml(localValue) + '" placeholder="Ex.: 42">';
    }

    if (formatId === 4) {
        return '<input type="date" class="omo-holon-create__property-value" value="' + escapeHtml(localValue) + '">';
    }

    return '<textarea class="omo-holon-create__property-value" rows="4" placeholder="Renseignez une valeur locale si nécessaire.">' + escapeHtml(localValue) + '</textarea>';
}

// Formate holon hérité
function formatInheritedHolonItem(item) {
    const holonId = Number(item || 0);
    const holon = getHolonCatalog().find(function (entry) {
        return Number(entry.id || 0) === holonId;
    });

    return holon ? holon.pathLabel : String(item || '');
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
        const items = parseStoredListValue(inheritedValue).map(function (item) {
            if (String(property.listItemType || 'text') === 'holon') {
                return formatInheritedHolonItem(item);
            }
            return String(item || '');
        }).filter(Boolean);

        if (!items.length) {
            return '';
        }

        return ''
            + '<div class="omo-holon-create__inherited">'
            + '  <div class="omo-holon-create__inherited-label">Valeur héritée</div>'
            + '  <ul class="omo-holon-create__inherited-list">'
            + items.map(function (item) {
                return '<li>' + escapeHtml(item) + '</li>';
            }).join('')
            + '  </ul>'
            + '</div>';
    }

    return ''
        + '<div class="omo-holon-create__inherited">'
        + '  <div class="omo-holon-create__inherited-label">Valeur héritée</div>'
        + '  <div class="omo-holon-create__inherited-text">' + escapeHtml(inheritedValue).replace(/\n/g, '<br>') + '</div>'
        + '</div>';
}

// Crée ligne propriété
function createPropertyRow(property, index) {
    const row = document.createElement('div');
    row.className = 'omo-holon-create__property';
    row.dataset.propertyId = Number(property.id || 0);
    row.dataset.holonPropertyId = Number(property.holonPropertyId || 0);
    row.dataset.formatId = Number(property.formatId || 0);
    row.dataset.listItemType = String(property.listItemType || 'text');
    row.dataset.propertyName = String(property.name || '');
    row.dataset.shortname = String(property.shortname || '');
    row.dataset.listHolonTypeIds = JSON.stringify(Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds : []);
    row.dataset.mandatory = property.mandatory ? '1' : '0';
    row.dataset.locked = property.locked ? '1' : '0';
    row.dataset.localMandatory = property.mandatory ? '1' : '0';
    row.dataset.localLocked = property.locked ? '1' : '0';
    row.dataset.inheritedMandatory = property.inheritedMandatory ? '1' : '0';
    row.dataset.inheritedLocked = property.inheritedLocked ? '1' : '0';
    row.dataset.isInherited = property.isInherited ? '1' : '0';
    row.dataset.isLocal = property.isLocal ? '1' : '0';
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
        + renderInheritedValue(property)
        + '  <label class="omo-holon-create__field">'
        + '      <span>Valeur locale</span>'
        + '      <div class="omo-holon-create__property-input">' + renderPropertyInput(property) + '</div>'
        + '  </label>'
        + '</div>';

    return row;
}

// Rend bloc propriétés
function renderProperties(properties) {
    elements.properties.innerHTML = '';

    if (!Array.isArray(properties) || !properties.length) {
        elements.properties.innerHTML = '<div class="omo-holon-create__empty-note">Ce modèle ne définit aucune propriété.</div>';
        return;
    }

    properties.forEach(function (property, index) {
        elements.properties.appendChild(createPropertyRow(property, index));
    });
}

// Prépare propriétés modèle
function buildPropertiesForTemplate(template, sourceProperties) {
    const sourceMap = new Map();
    (sourceProperties || []).forEach(function (property) {
        sourceMap.set(Number(property.id || 0), property);
    });

    if (!template) {
        return [];
    }

    return (template && Array.isArray(template.properties) ? template.properties : []).map(function (property) {
        const source = sourceMap.get(Number(property.id || 0));
        return Object.assign({}, property, {
            value: source && source.value !== undefined && source.value !== null ? String(source.value) : ''
        });
    });
}

// Rend options modèles
function renderTemplateOptions(preferredTemplateId) {
    const templates = getTemplates();

    elements.template.innerHTML = '';
    templates.forEach(function (template, index) {
        const option = document.createElement('option');
        option.value = Number(template.id);
        option.textContent = template.definedInName && Number(template.definedInId || 0) !== Number(state.data.contextHolonId || 0)
            ? template.name + ' · ' + template.definedInName
            : template.name;
        option.selected = Number(preferredTemplateId || 0) === Number(template.id) || (!preferredTemplateId && index === 0);
        elements.template.appendChild(option);
    });

    if (!elements.template.value && templates.length) {
        elements.template.value = String(Number(templates[0].id));
    }

    elements.template.required = true;
}

// Rend méta modèle
function renderTemplateMeta(template, sourceProperties) {
    if (!template) {
        elements.meta.innerHTML = '';
        elements.hint.textContent = '';
        renderProperties([]);
        return;
    }

    const meta = [];
    meta.push('<span class="omo-holon-create__chip omo-holon-create__chip--accent">' + escapeHtml(template.typeLabel || '') + '</span>');
    meta.push('<span class="omo-holon-create__chip">' + (Array.isArray(template.properties) ? template.properties.length : 0) + ' propriété' + ((template.properties || []).length > 1 ? 's' : '') + '</span>');
    if (template.definedInName && Number(template.definedInId || 0) !== Number(state.data.contextHolonId || 0)) {
        meta.push('<span class="omo-holon-create__chip">Défini dans ' + escapeHtml(template.definedInName) + '</span>');
    }

    elements.meta.innerHTML = meta.join('');
    elements.hint.textContent = getMode() === 'edit'
        ? 'Le type et les propriétés suivent le modèle sélectionné.'
        : 'Le type et les propriétés sont hérités du modèle sélectionné.';
    renderProperties(buildPropertiesForTemplate(template, sourceProperties));

    if (elements.color) {
        const editingHolon = getEditingHolon();
        const resolvedColor = getMode() === 'edit' && editingHolon
            ? String(editingHolon.color || template.color || '')
            : String(template.color || '');
        elements.color.value = resolvedColor.trim() !== '' ? resolvedColor : '#f59e0b';
    }
    if (elements.colorEnabled) {
        const editingHolon = getEditingHolon();
        elements.colorEnabled.checked = getMode() === 'edit'
            ? String((editingHolon && editingHolon.color) || '').trim() !== ''
            : false;
    }
    syncColorField();
}

// Synchronise modèle courant
function renderEditorMeta(template, sourceProperties) {
    const editingHolon = getEditingHolon();
    const properties = buildPropertiesForTemplate(template, sourceProperties);
    const propertyCount = Array.isArray(properties) ? properties.length : 0;
    const meta = [];
    const typeLabel = template
        ? String(template.typeLabel || '')
        : String((editingHolon && editingHolon.typeLabel) || '');

    if (elements.templateLabel) {
        elements.templateLabel.textContent = isTemplateEditing() ? 'Modèle parent' : 'Modèle';
    }

    if (typeLabel) {
        meta.push('<span class="omo-holon-create__chip omo-holon-create__chip--accent">' + escapeHtml(typeLabel) + '</span>');
    }
    meta.push('<span class="omo-holon-create__chip">' + propertyCount + ' propriété' + (propertyCount > 1 ? 's' : '') + '</span>');

    if (template && template.definedInName && Number(template.definedInId || 0) !== Number(state.data.contextHolonId || 0)) {
        meta.push('<span class="omo-holon-create__chip">Défini dans ' + escapeHtml(template.definedInName) + '</span>');
    }
    if (isTemplateEditing() && !template) {
        meta.push('<span class="omo-holon-create__chip">Sans modèle parent</span>');
    }

    elements.meta.innerHTML = meta.join('');
    elements.hint.textContent = isTemplateEditing()
        ? 'Les options et propriétés de ce template peuvent être redéfinies ici.'
        : getMode() === 'edit'
        ? 'Le type et les propriétés suivent le modèle sélectionné.'
        : 'Le type et les propriétés sont hérités du modèle sélectionné.';
    renderProperties(properties);

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
    renderMediaFields(template);
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

    if (!canEditValue) {
        return '';
    }

    if (formatId === 2) {
        if (listItemType === 'holon') {
            const selectedIds = Array.from(row.querySelectorAll('.omo-holon-create__property-value--holon:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        const items = Array.from(row.querySelectorAll('.omo-holon-create__property-value-item')).map(function (input) {
            return String(input.value || '').trim();
        }).filter(Boolean);

        return items.length ? JSON.stringify(items) : '';
    }

    const valueField = row.querySelector('.omo-holon-create__property-value');
    return valueField ? String(valueField.value || '') : '';
}

// Lit valeurs propriétés
function readProperties() {
    return Array.from(elements.properties.querySelectorAll('.omo-holon-create__property')).map(function (row) {
        const property = {
            id: Number(row.dataset.propertyId || 0),
            value: serializePropertyValue(row)
        };

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
        return Number(property.id || 0) > 0;
    });
}

// Remplit formulaire courant
function fillFormFromState() {
    const editingHolon = getEditingHolon();

    if (editingHolon) {
        elements.name.value = String(editingHolon.name || '');
        syncTemplateSelection(Number(editingHolon.templateId || 0), editingHolon.properties || []);
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
    elements.status.hidden = false;
    elements.status.className = 'omo-holon-create__status is-' + tone;
    elements.status.innerHTML = '<div class="omo-holon-create__status-copy">' + escapeHtml(message) + '</div>';
    state.statusTimer = window.setTimeout(clearStatus, 12000);
}

// Ferme drawer création
function closeCreateDrawer() {
    if (typeof closeDrawer === 'function') {
        closeDrawer('drawer_holon_create');
    }
}

// Enregistre holon courant
function saveHolon(event) {
    event.preventDefault();
    clearStatus();

    const payload = {
        templateId: Number(elements.template.value || 0),
        name: String(elements.name.value || '').trim(),
        color: Boolean(elements.colorEnabled && elements.colorEnabled.checked)
            ? String(elements.color && elements.color.value ? elements.color.value : '')
            : '',
        icon: mediaFields.icon ? mediaFields.icon.getValue() : '',
        banner: mediaFields.banner ? mediaFields.banner.getValue() : '',
        properties: readProperties()
    };

    if (isTemplateEditing()) {
        payload.visible = Boolean(elements.visible && elements.visible.checked);
        payload.mandatory = Boolean(elements.mandatory && elements.mandatory.checked);
        payload.link = Boolean(elements.link && elements.link.checked);
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
    if (mediaFields.banner) {
        mediaFields.banner.appendToFormData(formData);
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
                throw new Error(result.data && result.data.message ? result.data.message : (getMode() === 'edit' ? "Impossible d'enregistrer le holon." : "Impossible de créer le holon."));
            }

            const route = typeof parseUrl === 'function'
                ? parseUrl()
                : {
                    oid: Number(state.data.organizationId || 0),
                    cid: null,
                    hash: null
                };
            const targetHolonId = Number(result.data.holon.id || 0);

            if (getMode() === 'edit' && typeof loadContent === 'function') {
                let leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || state.data.organizationId || 0);

                if (targetHolonId > 0) {
                    leftUrl += '&cid=' + targetHolonId;
                }

                loadContent('#panel-left', leftUrl);

                window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                    detail: {
                        cid: targetHolonId > 0 ? targetHolonId : null
                    }
                }));
            } else if (typeof navigate === 'function') {
                const parentHolonId = Number((result.data.holon && result.data.holon.parentId) || state.data.contextHolonId || 0);
                const refreshPromise = typeof window.omoReloadStructureAndFocus === 'function'
                    ? window.omoReloadStructureAndFocus(parentHolonId > 0 ? parentHolonId : null, {
                        quickZoom: true
                    })
                    : Promise.resolve();

                refreshPromise
                    .catch(function () {
                        return null;
                    })
                    .then(function () {
                        navigate(route.oid, targetHolonId, route.hash || null);
                    });
            }

            closeCreateDrawer();
        })
        .catch(function (error) {
            showStatus(error && error.message ? error.message : (getMode() === 'edit' ? "Impossible d'enregistrer le holon." : "Impossible de créer le holon."), 'error');
        });
}

fillFormFromState();

elements.template.addEventListener('change', function () {
    renderEditorMeta(getCurrentTemplate(), readProperties());
});

if (elements.colorEnabled) {
    elements.colorEnabled.addEventListener('change', function () {
        syncColorField();
    });
}

elements.form.addEventListener('submit', saveHolon);

elements.cancel.addEventListener('click', function () {
    closeCreateDrawer();
});

root.addEventListener('click', function (event) {
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

<style>
.omo-holon-create__layout {
    display: block;
}

.omo-holon-create__panel {
    display: grid;
    gap: 16px;
}

.omo-holon-create__section,
.omo-holon-create__footer,
.omo-holon-create__property,
.omo-holon-create__empty {
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: var(--color-surface);
    box-shadow: var(--shadow-sm);
}

.omo-holon-create__section,
.omo-holon-create__footer,
.omo-holon-create__empty {
    padding: 16px;
}

.omo-holon-create__section-title,
.omo-holon-create__inherited-label {
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.omo-holon-create__section-description,
.omo-holon-create__hint,
.omo-holon-create__field small,
.omo-holon-create__locked-note,
.omo-holon-create__empty-note,
.omo-holon-create__inherited-text {
    color: var(--color-text-light);
    line-height: 1.45;
}

.omo-holon-create__status {
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid transparent;
    box-shadow: var(--shadow-sm);
}

.omo-holon-create__status[hidden] {
    display: none !important;
}

.omo-holon-create__status.is-error {
    color: #991b1b;
    background: color-mix(in srgb, #dc2626 10%, white);
    border-color: color-mix(in srgb, #dc2626 22%, transparent);
}

.omo-holon-create__form,
.omo-holon-create__section,
.omo-holon-create__properties {
    display: grid;
    gap: 16px;
}

.omo-holon-create__section-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.omo-holon-create__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.omo-holon-create__grid > [hidden] {
    display: none !important;
}

.omo-holon-create__field {
    display: grid;
    gap: 7px;
}

.omo-holon-create__field--full {
    grid-column: 1 / -1;
}

.omo-holon-create__color-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.omo-holon-create__color-body[hidden] {
    display: none !important;
}

.omo-holon-create__color-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--color-text-light);
}

.omo-holon-create__color-toggle input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-holon-create__toggles {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.omo-holon-create__toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    background: var(--color-surface-alt);
    color: var(--color-text);
    font-size: 0.9rem;
}

.omo-holon-create__toggle input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-holon-create__field span {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
}

.omo-holon-create__media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
    margin-top: 8px;
}

.omo-holon-create__media-card {
    display: grid;
    gap: 10px;
    padding: 14px;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: var(--color-surface);
}

.omo-holon-create__media-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--color-text);
}

.omo-holon-create__field input,
.omo-holon-create__field select,
.omo-holon-create__field textarea {
    display: block;
    width: 100%;
    min-height: 44px;
    padding: 11px 12px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface-alt);
    color: var(--color-text);
    font: inherit;
    box-sizing: border-box;
}

.omo-holon-create__field textarea {
    min-height: 110px;
    resize: vertical;
}

.omo-holon-create__field input:focus,
.omo-holon-create__field select:focus,
.omo-holon-create__field textarea:focus {
    outline: none;
    border-color: color-mix(in srgb, var(--color-primary) 52%, var(--color-border));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 14%, transparent);
    background: var(--color-surface);
}

.omo-holon-create__template-meta,
.omo-holon-create__property-meta,
.omo-holon-create__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-holon-create__chip {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    font-size: 0.78rem;
    border: 1px solid color-mix(in srgb, var(--color-border) 84%, transparent);
    background: var(--color-surface-alt);
    color: var(--color-text-light);
}

.omo-holon-create__chip--accent {
    color: var(--color-primary);
    border-color: color-mix(in srgb, var(--color-primary) 28%, transparent);
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
}

.omo-holon-create__property {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 12px;
    padding: 12px;
}

.omo-holon-create__property-index {
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

.omo-holon-create__property-body {
    display: grid;
    gap: 12px;
    min-width: 0;
}

.omo-holon-create__property-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.omo-holon-create__property-name {
    font-weight: 700;
    line-height: 1.35;
}

.omo-holon-create__property-meta--toggles {
    margin-top: -4px;
}

.omo-holon-create__property-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 6px 10px;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    background: var(--color-surface-alt);
    color: var(--color-text);
    font-size: 0.82rem;
}

.omo-holon-create__property-toggle input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-holon-create__property-toggle input:disabled + span {
    opacity: 0.65;
}

.omo-holon-create__inherited {
    padding: 14px;
    border: 1px dashed var(--color-border);
    border-radius: 14px;
    background: color-mix(in srgb, var(--color-surface-alt) 80%, var(--color-surface));
}

.omo-holon-create__inherited-list {
    margin: 8px 0 0;
    padding-left: 20px;
    display: grid;
    gap: 6px;
    color: var(--color-text-light);
}

.omo-holon-create__locked-note,
.omo-holon-create__empty-note {
    padding: 12px;
    border: 1px dashed var(--color-border);
    border-radius: 12px;
    background: var(--color-surface-alt);
}

.omo-holon-create__check-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 8px;
}

.omo-holon-create__check-option {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface);
}

.omo-holon-create__check-option small {
    display: block;
    color: var(--color-text-light);
    line-height: 1.35;
}

.omo-holon-create__list,
.omo-holon-create__list-items {
    display: grid;
    gap: 8px;
}

.omo-holon-create__list-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px 42px 42px;
    gap: 8px;
    align-items: center;
}

.omo-holon-create__footer {
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

.omo-holon-create__actions {
    justify-content: flex-end;
}

.omo-holon-create__button {
    min-height: 40px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid var(--color-border);
    background: var(--color-surface-alt);
    color: var(--color-text);
    cursor: pointer;
    font: inherit;
}

.omo-holon-create__button--primary {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-text-inverse);
}

.omo-holon-create__button--secondary {
    color: var(--color-primary);
    border-color: color-mix(in srgb, var(--color-primary) 24%, var(--color-border));
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
}

.omo-holon-create__button--ghost {
    background: var(--color-surface);
}

@media (max-width: 1024px) {
    .omo-holon-create__layout,
    .omo-holon-create__grid {
        grid-template-columns: 1fr;
    }

    .omo-holon-create__footer,
    .omo-holon-create__section-head {
        flex-direction: column;
        align-items: stretch;
    }

    .omo-holon-create__property {
        grid-template-columns: 1fr;
    }
}
</style>
