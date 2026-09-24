window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/parameters/holon-templates/templates.js"] = function (pageConfig, pageScript) {
(() => {
const omoHolonTemplatePageRoot = document.getElementById('omo-holon-template-page');
const omoHolonTemplateTexts = pageConfig.omoHolonTemplateTexts;
const omoHolonTemplateState = {
    data: pageConfig.data,
    selectedId: pageConfig.selectedId,
    compactMode: pageConfig.compactMode,
    removedPropertyIds: [],
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
    definitionHolon: omoHolonTemplateRoot.querySelector('#omo-template-definition-holon'),
    name: omoHolonTemplateRoot.querySelector('#omo-template-name'),
    colorEnabled: omoHolonTemplateRoot.querySelector('#omo-template-color-enabled'),
    colorOverrideLabel: omoHolonTemplateRoot.querySelector('#omo-template-color-override-label'),
    colorBody: omoHolonTemplateRoot.querySelector('#omo-template-color-body'),
    color: omoHolonTemplateRoot.querySelector('#omo-template-color'),
    unassignedColorEnabled: omoHolonTemplateRoot.querySelector('#omo-template-unassigned-color-enabled'),
    unassignedColorOverrideLabel: omoHolonTemplateRoot.querySelector('#omo-template-unassigned-color-override-label'),
    unassignedColorBody: omoHolonTemplateRoot.querySelector('#omo-template-unassigned-color-body'),
    unassignedColor: omoHolonTemplateRoot.querySelector('#omo-template-unassigned-color'),
    iconField: omoHolonTemplateRoot.querySelector('#omo-template-icon-field'),
    mandatory: omoHolonTemplateRoot.querySelector('#omo-template-mandatory'),
    lockedName: omoHolonTemplateRoot.querySelector('#omo-template-locked-name'),
    lockedIcon: omoHolonTemplateRoot.querySelector('#omo-template-locked-icon'),
    unique: omoHolonTemplateRoot.querySelector('#omo-template-unique'),
    link: omoHolonTemplateRoot.querySelector('#omo-template-link'),
    adminParent: omoHolonTemplateRoot.querySelector('#omo-template-admin-parent'),
    adminMin: omoHolonTemplateRoot.querySelector('#omo-template-admin-min'),
    adminMax: omoHolonTemplateRoot.querySelector('#omo-template-admin-max'),
	lockedAdminMin: omoHolonTemplateRoot.querySelector('#omo-template-locked-admin-min'),
	lockedAdminMax: omoHolonTemplateRoot.querySelector('#omo-template-locked-admin-max'),
    addProperty: omoHolonTemplateRoot.querySelector('#omo-template-add-property'),
    properties: omoHolonTemplateRoot.querySelector('#omo-template-properties'),
    permissions: omoHolonTemplateRoot.querySelector('#omo-template-permissions'),
    welcome: omoHolonTemplateRoot.querySelector('#omo-template-welcome'),
    formContent: omoHolonTemplateRoot.querySelector('#omo-template-form-content'),
    selectionHint: omoHolonTemplateRoot.querySelector('#omo-template-selection-hint'),
    cancel: omoHolonTemplateRoot.querySelector('#omo-template-cancel'),
    deleteButton: omoHolonTemplateRoot.querySelector('#omo-template-delete'),
    newChildButton: omoHolonTemplateRoot.querySelector('[data-template-action="new-child"]'),
    summary: omoHolonTemplateRoot.querySelector('#omo-template-summary'),
    formTitle: omoHolonTemplateRoot.querySelector('#omo-template-form-title'),
    formDescription: omoHolonTemplateRoot.querySelector('#omo-template-form-description'),
    formBadges: omoHolonTemplateRoot.querySelector('#omo-template-form-badges'),
    resizeHandle: omoHolonTemplateRoot.querySelector('[data-omo-template-resize-handle]')
};

function omoHolonTemplateInitColumnResize() {
    const layout = omoHolonTemplateRoot;
    const sidebar = omoHolonTemplateRoot.querySelector('.omo-template-sidebar');
    const handle = omoHolonTemplateElements.resizeHandle;
    if (
        !layout
        || !sidebar
        || !handle
        || layout.classList.contains('omo-template-editor__layout--compact')
        || layout.classList.contains('omo-template-editor__layout--holon-definition')
    ) {
        return;
    }

    const minimumSidebarWidth = 240;
    const minimumFormWidth = 320;
    const separatorWidth = 10;
    let isResizing = false;

    function applyWidth(width) {
        const maximumSidebarWidth = Math.max(
            minimumSidebarWidth,
            layout.getBoundingClientRect().width - minimumFormWidth - separatorWidth
        );
        const resolvedWidth = Math.min(maximumSidebarWidth, Math.max(minimumSidebarWidth, Math.round(width)));
        layout.style.setProperty('--omo-template-sidebar-width', resolvedWidth + 'px');
        layout.style.gridTemplateColumns = resolvedWidth + 'px ' + separatorWidth + 'px minmax(' + minimumFormWidth + 'px, 1fr)';
        handle.setAttribute('aria-valuemin', String(minimumSidebarWidth));
        handle.setAttribute('aria-valuemax', String(Math.round(maximumSidebarWidth)));
        handle.setAttribute('aria-valuenow', String(resolvedWidth));
    }

    function applyPointerPosition(clientX) {
        applyWidth(clientX - layout.getBoundingClientRect().left);
    }

    function startResize(clientX) {
        isResizing = true;
        layout.classList.add('is-resizing');
        applyPointerPosition(clientX);
    }

    function finishResize() {
        if (!isResizing) {
            return;
        }

        isResizing = false;
        layout.classList.remove('is-resizing');
    }

    handle.addEventListener('mousedown', function (event) {
        if (event.button !== 0 || isResizing) {
            return;
        }

        startResize(event.clientX);
        event.preventDefault();
    });
    document.addEventListener('mousemove', function (event) {
        if (isResizing) {
            applyPointerPosition(event.clientX);
        }
    });
    document.addEventListener('mouseup', finishResize);
    window.addEventListener('blur', finishResize);
    handle.addEventListener('keydown', function (event) {
        const currentWidth = sidebar.getBoundingClientRect().width;
        if (event.key === 'ArrowLeft') {
            applyWidth(currentWidth - 24);
        } else if (event.key === 'ArrowRight') {
            applyWidth(currentWidth + 24);
        } else if (event.key === 'Home') {
            applyWidth(minimumSidebarWidth);
        } else if (event.key === 'End') {
            applyWidth(layout.getBoundingClientRect().width - minimumFormWidth - separatorWidth);
        } else {
            return;
        }

        event.preventDefault();
    });

    function syncResponsiveLayout() {
        if (window.matchMedia('(max-width: 1100px)').matches) {
            layout.style.removeProperty('grid-template-columns');
            return;
        }

        applyWidth(sidebar.getBoundingClientRect().width);
    }

    window.addEventListener('resize', syncResponsiveLayout);
    syncResponsiveLayout();
}

omoHolonTemplateInitColumnResize();

function omoHolonTemplateSyncPermissionGroupStickyOffset() {
    const navigation = omoHolonTemplateRoot.querySelector('.omo-template-permissions__sticky-navigation');
    if (!navigation) {
        return;
    }

    omoHolonTemplateRoot.style.setProperty(
        '--omo-template-permission-group-sticky-top',
        Math.max(0, navigation.getBoundingClientRect().height - 16) + 'px'
    );
    omoHolonTemplateRoot.style.setProperty(
        '--param-permission-legend-sticky-top',
        Math.max(0, navigation.getBoundingClientRect().height - 16) + 'px'
    );
}

omoHolonTemplateSyncPermissionGroupStickyOffset();
if (typeof window.ResizeObserver === 'function') {
    const omoHolonTemplatePermissionNavigationObserver = new window.ResizeObserver(omoHolonTemplateSyncPermissionGroupStickyOffset);
    const omoHolonTemplatePermissionNavigation = omoHolonTemplateRoot.querySelector('.omo-template-permissions__sticky-navigation');
    if (omoHolonTemplatePermissionNavigation) {
        omoHolonTemplatePermissionNavigationObserver.observe(omoHolonTemplatePermissionNavigation);
    }
}
window.addEventListener('resize', omoHolonTemplateSyncPermissionGroupStickyOffset);

const omoHolonTemplateMediaFields = {
    icon: null
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
        omoHolonTemplateShowWelcome();
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

if (omoHolonTemplateIsHolonDefinitionMode()) {
    omoHolonTemplateElements.iconField = null;
    omoHolonTemplateElements.lockedIcon = null;
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

function omoHolonTemplateSyncRoleFlags() {
    const roleFlag = omoHolonTemplateRoot.querySelector('[data-omo-template-role-flag]');
    if (!roleFlag || !omoHolonTemplateElements.adminParent) {
        return;
    }

    const effectiveTypeId = omoHolonTemplateGetEffectiveTypeId(
        omoHolonTemplateElements.type.value || 0,
        omoHolonTemplateGetEffectiveInheritanceIdFromParent(omoHolonTemplateElements.parent.value || 0)
    );
    const isRole = Number(effectiveTypeId) === 1;
    roleFlag.hidden = !isRole;
    omoHolonTemplateElements.adminParent.disabled = !isRole;
    if (!isRole) {
        omoHolonTemplateElements.adminParent.checked = false;
    }
}

function omoHolonTemplateReadAdminBound(input) {
    const rawValue = input ? String(input.value || '').trim() : '';
    return rawValue === '' ? null : Math.max(0, Number(rawValue) || 0);
}

function omoHolonTemplateReadAdminLock(input) {
    if (!input) {
        return false;
    }

    return input.disabled
        ? String(input.dataset.localValue || '0') === '1'
        : Boolean(input.checked);
}

function omoHolonTemplateFormatAdminBoundPlaceholder(value, isMaximum, isInherited) {
    const isUnlimited = isMaximum && (value === null || value === undefined || String(value).trim() === '');
    const formattedValue = isUnlimited
        ? (omoHolonTemplateTexts.adminMaxPlaceholder || 'sans limite')
        : String(Math.max(0, Number(value) || 0));

    if (!isInherited) {
        return formattedValue;
    }

    return String(omoHolonTemplateTexts.adminBoundInheritedPlaceholder || 'Hérité ({value})')
        .replace('{value}', formattedValue);
}

function omoHolonTemplateSyncAdminBounds(template) {
    const inheritedMinimumLocked = Boolean(template && template.inheritedLockedAdminMin);
    const inheritedMaximumLocked = Boolean(template && template.inheritedLockedAdminMax);
    const parentTemplate = template ? omoHolonTemplateFind(template.inheritsFromId) : null;
    const hasInheritedBounds = Boolean(parentTemplate);

    if (omoHolonTemplateElements.adminMin) {
        omoHolonTemplateElements.adminMin.value = template && template.adminMin !== null && template.adminMin !== undefined && String(template.adminMin).trim() !== ''
            ? String(Math.max(0, Number(template.adminMin) || 0))
            : '';
        omoHolonTemplateElements.adminMin.placeholder = omoHolonTemplateFormatAdminBoundPlaceholder(
            template ? template.inheritedAdminMin : 0,
            false,
            hasInheritedBounds
        );
        omoHolonTemplateElements.adminMin.disabled = inheritedMinimumLocked;
    }
    if (omoHolonTemplateElements.adminMax) {
        omoHolonTemplateElements.adminMax.value = template && template.adminMax !== null && template.adminMax !== undefined && String(template.adminMax).trim() !== ''
            ? String(Math.max(0, Number(template.adminMax) || 0))
            : '';
        omoHolonTemplateElements.adminMax.placeholder = omoHolonTemplateFormatAdminBoundPlaceholder(
            template ? template.inheritedAdminMax : null,
            true,
            hasInheritedBounds
        );
        omoHolonTemplateElements.adminMax.disabled = inheritedMaximumLocked;
    }
    if (omoHolonTemplateElements.lockedAdminMin) {
        omoHolonTemplateElements.lockedAdminMin.dataset.localValue = template && template.lockedAdminMin ? '1' : '0';
        omoHolonTemplateElements.lockedAdminMin.checked = Boolean(template && (template.lockedAdminMin || inheritedMinimumLocked));
        omoHolonTemplateElements.lockedAdminMin.disabled = inheritedMinimumLocked;
    }
    if (omoHolonTemplateElements.lockedAdminMax) {
        omoHolonTemplateElements.lockedAdminMax.dataset.localValue = template && template.lockedAdminMax ? '1' : '0';
        omoHolonTemplateElements.lockedAdminMax.checked = Boolean(template && (template.lockedAdminMax || inheritedMaximumLocked));
        omoHolonTemplateElements.lockedAdminMax.disabled = inheritedMaximumLocked;
    }
}

// Synchronise champ couleur
function omoHolonTemplateSyncColorField() {
    const isEnabled = Boolean(omoHolonTemplateElements.colorEnabled && omoHolonTemplateElements.colorEnabled.checked);

    if (omoHolonTemplateElements.colorBody) {
        omoHolonTemplateElements.colorBody.hidden = !isEnabled;
    }

    if (omoHolonTemplateElements.color) {
        omoHolonTemplateElements.color.disabled = !isEnabled;
        omoHolonTemplateElements.color.hidden = !isEnabled;
    }

    if (omoHolonTemplateElements.colorOverrideLabel) {
        omoHolonTemplateElements.colorOverrideLabel.hidden = isEnabled;
    }
}

function omoHolonTemplateSyncUnassignedColorField() {
    const isEnabled = Boolean(omoHolonTemplateElements.unassignedColorEnabled && omoHolonTemplateElements.unassignedColorEnabled.checked);

    if (omoHolonTemplateElements.unassignedColorBody) {
        omoHolonTemplateElements.unassignedColorBody.hidden = !isEnabled;
    }

    if (omoHolonTemplateElements.unassignedColor) {
        omoHolonTemplateElements.unassignedColor.disabled = !isEnabled;
        omoHolonTemplateElements.unassignedColor.hidden = !isEnabled;
    }

    if (omoHolonTemplateElements.unassignedColorOverrideLabel) {
        omoHolonTemplateElements.unassignedColorOverrideLabel.hidden = isEnabled;
    }
}

function omoHolonTemplateGetMediaDisplayConfig() {
    return {
        displayWidth: 160,
        displayHeight: 160,
        targetWidth: 320,
        targetHeight: 320,
        emptyText: 'Aucune icône transmise par ce modèle.'
    };
}

function omoHolonTemplateResolveMediaState(kind, template, preserveCurrentValue) {
    const suffix = 'Icon';
    const currentController = omoHolonTemplateMediaFields[kind];
    const shouldReuseController = Boolean(currentController) && Boolean(preserveCurrentValue);
    const localValue = shouldReuseController
        ? currentController.getValue()
        : String((template && template[kind]) || '');
    const inheritedLocked = Boolean(template && template['inheritedLocked' + suffix]);
    const isHolonDefinitionMode = omoHolonTemplateIsHolonDefinitionMode();

    return {
        value: isHolonDefinitionMode && inheritedLocked ? '' : localValue,
        inheritedValue: String((template && template['inherited' + suffix]) || ''),
        locked: isHolonDefinitionMode && Boolean(template && template['effectiveLocked' + suffix]),
        inheritedLocked: inheritedLocked,
        localLocked: Boolean(template && template['locked' + suffix])
    };
}

function omoHolonTemplateRenderMediaFields(template, preserveCurrentValue) {
    if (!window.omoSizedImageField) {
        return;
    }

    [
        ['icon', omoHolonTemplateElements.iconField, omoHolonTemplateElements.lockedIcon, 'Icône']
    ].forEach(function (entry) {
        const kind = entry[0];
        const target = entry[1];
        const lockField = entry[2];
        const label = entry[3];
        if (!target) {
            return;
        }

        const mediaState = omoHolonTemplateResolveMediaState(kind, template, preserveCurrentValue);
        const config = omoHolonTemplateGetMediaDisplayConfig();
        if (lockField) {
            lockField.checked = Boolean(mediaState.localLocked);
            lockField.disabled = false;
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
        unassignedColor: Boolean(omoHolonTemplateElements.unassignedColorEnabled && omoHolonTemplateElements.unassignedColorEnabled.checked)
            ? String(omoHolonTemplateElements.unassignedColor && omoHolonTemplateElements.unassignedColor.value ? omoHolonTemplateElements.unassignedColor.value : '')
            : '',
        icon: omoHolonTemplateMediaFields.icon ? omoHolonTemplateMediaFields.icon.getValue() : '',
        typeId: effectiveTypeId,
        typeLabel: omoHolonTemplateGetTypeLabel(effectiveTypeId),
        mandatory: Boolean(omoHolonTemplateElements.mandatory && omoHolonTemplateElements.mandatory.checked),
        lockedName: Boolean(omoHolonTemplateElements.lockedName && omoHolonTemplateElements.lockedName.checked),
        lockedIcon: omoHolonTemplateElements.lockedIcon
            ? (omoHolonTemplateElements.lockedIcon.disabled
                ? String(omoHolonTemplateElements.lockedIcon.dataset.localValue || '0') === '1'
                : Boolean(omoHolonTemplateElements.lockedIcon.checked))
            : false,
        unique: Boolean(omoHolonTemplateElements.unique && omoHolonTemplateElements.unique.checked),
        link: Boolean(omoHolonTemplateElements.link && omoHolonTemplateElements.link.checked),
        adminParent: Boolean(omoHolonTemplateElements.adminParent && omoHolonTemplateElements.adminParent.checked),
        adminMin: omoHolonTemplateReadAdminBound(omoHolonTemplateElements.adminMin),
        adminMax: omoHolonTemplateReadAdminBound(omoHolonTemplateElements.adminMax),
		lockedAdminMin: omoHolonTemplateReadAdminLock(omoHolonTemplateElements.lockedAdminMin),
		lockedAdminMax: omoHolonTemplateReadAdminLock(omoHolonTemplateElements.lockedAdminMax),
        inheritsFromId: effectiveInheritanceId,
        definitionHolonId: Number((omoHolonTemplateElements.definitionHolon || {}).value || omoHolonTemplateElements.form.dataset.definitionHolonId || omoHolonTemplateState.data.rootHolonId || 0),
        permissionAssignments: omoHolonTemplateReadPermissions(),
        removedPropertyIds: omoHolonTemplateState.removedPropertyIds.slice(),
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
            { key: 'direct_children', label: omoHolonTemplateTexts.permissionDirectChildren || '' },
            { key: 'parent_circle', label: 'Cercle englobant seul' },
            { key: 'parent_circle_elements', label: omoHolonTemplateTexts.permissionParentCircleElements || '' },
            { key: 'parent_circle_descendants', label: 'Cercle englobant et descendants' },
            { key: 'organization_root', label: omoHolonTemplateTexts.permissionOrganizationRoot || '' },
            { key: 'organization', label: omoHolonTemplateTexts.permissionOrganization || '' }
        ];
    const assignments = omoHolonTemplateNormalizePermissionProfiles(permissionAssignments);
    const currentTypeId = Number(omoHolonTemplateElements.type && omoHolonTemplateElements.type.value ? omoHolonTemplateElements.type.value : 0);

    if (!omoHolonTemplateElements.permissions) {
        return;
    }

    if (!permissionCatalog.length) {
        omoHolonTemplateElements.permissions.innerHTML = '<div class="omo-template-properties__empty">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.permissionNoneAvailable || '') + '</div>';
        omoHolonTemplateSyncPermissionGroupStickyOffset();
        return;
    }

    const profiles = omoHolonTemplateGetPermissionProfiles();
    const permissionGroups = omoHolonTemplateGroupPermissionCatalog(permissionCatalog);
    let html = '';

    permissionGroups.forEach(function (group) {
        html += '<section class="omo-template-permissions__group" data-permission-group="' + omoHolonTemplateEscapeHtml(group.key) + '">'
            + '<div class="omo-template-permissions__group-title">' + omoHolonTemplateEscapeHtml(group.title) + '</div>'
            + '<div class="omo-template-permissions__table">';
        group.permissions.forEach(function (permission) {
            const allPermissionRangeOptions = Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
                ? permission.rangeOptions
                : rangeOptions;
            const permissionRangeOptions = omoHolonTemplateFilterPermissionRangeOptions(allPermissionRangeOptions, currentTypeId);

            html += ''
                + '<div class="omo-template-permissions__row" data-permission-key="' + omoHolonTemplateEscapeHtml(permission.key) + '">'
                + '  <div class="omo-template-permissions__main">'
                + '      <div class="omo-template-permissions__title generic-title generic-title--compact">' + omoHolonTemplateEscapeHtml(permission.title || permission.key) + '</div>'
                + '      <div class="omo-template-permissions__meta generic-meta generic-meta--compact">' + omoHolonTemplateEscapeHtml(permission.key) + '</div>';

            if (String(permission.description || '').trim() !== '') {
                html += '<div class="omo-template-permissions__description generic-meta">' + omoHolonTemplateEscapeHtml(permission.description) + '</div>';
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
        html += '</div></section>';
    });

    omoHolonTemplateElements.permissions.innerHTML = html;
    omoHolonTemplateSyncPermissionGroupStickyOffset();

    Array.from(omoHolonTemplateElements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
        const permissionKey = String(row.getAttribute('data-permission-key') || '').trim();
        const permission = permissionCatalog.find(function (item) {
            return String(item && item.key ? item.key : '') === permissionKey;
        }) || null;
        const allPermissionRangeOptions = permission && Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
            ? permission.rangeOptions
            : rangeOptions;
        const permissionRangeOptions = omoHolonTemplateFilterPermissionRangeOptions(allPermissionRangeOptions, currentTypeId);
        omoHolonTemplateBindPermissionRow(row, permissionRangeOptions, allPermissionRangeOptions);
        omoHolonTemplateSetPermissionRowRanges(row, omoHolonTemplateGetPermissionAssignmentsForKey(assignments, permissionKey), allPermissionRangeOptions, profiles);
    });
    window.omoPermissionEditorEnhance(omoHolonTemplateElements.permissions);
}

function omoHolonTemplateGroupPermissionCatalog(permissionCatalog) {
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

function omoHolonTemplateNormalizePermissionProfiles(value) {
    const source = value && typeof value === 'object' ? value : {};
    const hasProfiles = Object.prototype.hasOwnProperty.call(source, 'member') || Object.prototype.hasOwnProperty.call(source, 'admin') || Object.prototype.hasOwnProperty.call(source, 'collective');
    return {
        member: hasProfiles && source.member && typeof source.member === 'object' ? source.member : (hasProfiles ? {} : source),
        admin: hasProfiles && source.admin && typeof source.admin === 'object' ? source.admin : {},
        collective: hasProfiles && source.collective && typeof source.collective === 'object' ? source.collective : {}
    };
}

function omoHolonTemplateGetPermissionProfiles() {
    return [
        { key: 'member', label: omoHolonTemplateTexts.permissionMembers || 'Membres' },
        { key: 'admin', label: omoHolonTemplateTexts.permissionAdmins || 'Admins' },
        { key: 'collective', label: omoHolonTemplateTexts.permissionCollective || 'Collectif' }
    ];
}

function omoHolonTemplateGetPermissionAssignmentsForKey(assignments, permissionKey) {
    const selected = {};
    omoHolonTemplateGetPermissionProfiles().forEach(function (profile) {
        selected[profile.key] = omoHolonTemplateNormalizePermissionRanges((assignments[profile.key] || {})[permissionKey]);
    });
    return selected;
}

function omoHolonTemplateReadPermissions() {
    if (!omoHolonTemplateElements.permissions) {
        return {};
    }

    const assignments = { member: {}, admin: {}, collective: {} };
    Array.from(omoHolonTemplateElements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
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

function omoHolonTemplateFilterPermissionRangeOptions(rangeOptions, typeId) {
    return (rangeOptions || []).filter(function (range) {
        return String(range && range.key ? range.key : '') !== 'direct_children' || Number(typeId || 0) === 2;
    });
}

function omoHolonTemplateSetPermissionRowRanges(row, selectedAssignments, rangeOptions, profiles) {
    const tokensContainer = row.querySelector('[data-permission-tokens]');
    const select = row.querySelector('[data-permission-select]');
    const selectedByProfile = selectedAssignments && typeof selectedAssignments === 'object' ? selectedAssignments : {};
    const profileList = Array.isArray(profiles) ? profiles : omoHolonTemplateGetPermissionProfiles();
    const normalizedRanges = [];
    const seenRanges = new Set();

    profileList.forEach(function (profile) {
        omoHolonTemplateNormalizePermissionRanges(selectedByProfile[profile.key]).forEach(function (range) {
            if (seenRanges.has(range)) return;
            seenRanges.add(range);
            normalizedRanges.push(range);
        });
    });

    if (!tokensContainer) {
        return;
    }

    if (!normalizedRanges.length) {
        tokensContainer.innerHTML = '<span class="omo-template-permissions__empty generic-description generic-description--small">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.permissionNoneSelected || '') + '</span>';
    } else {
        tokensContainer.innerHTML = normalizedRanges.map(function (rangeKey) {
            return ''
                + '<div class="omo-template-permissions__token" data-permission-token="' + omoHolonTemplateEscapeHtml(rangeKey) + '" data-permission-scope>'
                + '  <span class="omo-template-permissions__scope-label">' + omoHolonTemplateEscapeHtml(omoHolonTemplateGetPermissionRangeLabel(rangeKey, rangeOptions)) + '</span>'
                + '  <span class="omo-permission-editor__profiles">'
                + profileList.map(function (profile) {
                    const isChecked = omoHolonTemplateNormalizePermissionRanges(selectedByProfile[profile.key]).includes(rangeKey);
                    return '<label title="' + omoHolonTemplateEscapeHtml(profile.label) + '"><input type="checkbox" data-permission-profile="' + omoHolonTemplateEscapeHtml(profile.key) + '" aria-label="' + omoHolonTemplateEscapeHtml(profile.label) + '"' + (isChecked ? ' checked' : '') + '></label>';
                }).join('')
                + '  </span>'
                + '  <button type="button" class="omo-template-permissions__token-remove" data-permission-remove="' + omoHolonTemplateEscapeHtml(rangeKey) + '" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.permissionRemoveRange || '') + '">&times;</button>'
                + '</div>';
        }).join('');
    }

    if (select) {
        select.value = '';
    }
}

function omoHolonTemplateBindPermissionRow(row, rangeOptions, labelRangeOptions) {
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

        const selectedAssignments = omoHolonTemplateReadPermissionRowAssignments(row);
        selectedAssignments.member.push(nextRange);
        omoHolonTemplateSetPermissionRowRanges(row, selectedAssignments, labelRangeOptions || rangeOptions);
    });

    row.addEventListener('click', function (event) {
        const removeButton = event.target instanceof Element
            ? event.target.closest('[data-permission-remove]')
            : null;
        if (!removeButton) {
            return;
        }

        const removedRange = String(removeButton.getAttribute('data-permission-remove') || '').trim();
        const selectedAssignments = omoHolonTemplateReadPermissionRowAssignments(row);
        Object.keys(selectedAssignments).forEach(function (profileKey) {
            selectedAssignments[profileKey] = selectedAssignments[profileKey].filter(function (range) {
                return range !== removedRange;
            });
        });
        omoHolonTemplateSetPermissionRowRanges(row, selectedAssignments, labelRangeOptions || rangeOptions);
    });
}

function omoHolonTemplateReadPermissionRowAssignments(row) {
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
    omoHolonTemplateFillForm(current, { preserveMediaState: true });
}

function omoHolonTemplateApplyInheritedMediaState(template) {
    const parentTemplate = omoHolonTemplateFind(template && template.inheritsFromId ? template.inheritsFromId : 0);

    template.inheritedIcon = parentTemplate ? String(parentTemplate.effectiveIcon || '') : '';
    template.inheritedLockedIcon = parentTemplate ? Boolean(parentTemplate.effectiveLockedIcon) : false;
    template.effectiveIcon = String(template.icon || '').trim() !== '' ? String(template.icon || '') : template.inheritedIcon;
    template.effectiveLockedIcon = Boolean(template.lockedIcon || template.inheritedLockedIcon);

    template.inheritedAdminMin = parentTemplate ? Number(parentTemplate.effectiveAdminMin || 0) : 0;
    template.inheritedAdminMax = parentTemplate && parentTemplate.effectiveAdminMax !== null && parentTemplate.effectiveAdminMax !== undefined
        ? Number(parentTemplate.effectiveAdminMax)
        : null;
    template.inheritedLockedAdminMin = parentTemplate ? Boolean(parentTemplate.effectiveLockedAdminMin) : false;
    template.inheritedLockedAdminMax = parentTemplate ? Boolean(parentTemplate.effectiveLockedAdminMax) : false;
    template.effectiveAdminMin = template.inheritedLockedAdminMin || template.adminMin === null || template.adminMin === undefined || String(template.adminMin).trim() === ''
        ? template.inheritedAdminMin
        : Math.max(0, Number(template.adminMin) || 0);
    template.effectiveAdminMax = template.inheritedLockedAdminMax || template.adminMax === null || template.adminMax === undefined || String(template.adminMax).trim() === ''
        ? template.inheritedAdminMax
        : Math.max(0, Number(template.adminMax) || 0);
    template.effectiveLockedAdminMin = Boolean(template.lockedAdminMin || template.inheritedLockedAdminMin);
    template.effectiveLockedAdminMax = Boolean(template.lockedAdminMax || template.inheritedLockedAdminMax);

    return template;
}

function omoHolonTemplateBuildDraft(inheritsFromId, definitionHolonId) {
    const firstType = (omoHolonTemplateState.data.types || [])[0] || { id: 1, name: 'Élément' };
    const suggestedInheritanceId = Number(inheritsFromId || 0);
    const inheritedTemplate = omoHolonTemplateFind(suggestedInheritanceId);
    const suggestedDefinitionHolonId = Number(
        definitionHolonId
        || (inheritedTemplate && inheritedTemplate.definedInId)
        || omoHolonTemplateState.data.rootHolonId
        || 0
    );
    const effectiveTypeId = omoHolonTemplateGetEffectiveTypeId(firstType.id || 1, suggestedInheritanceId);
    return omoHolonTemplateApplyInheritedMediaState({
        id: 0,
        name: '',
        color: '',
        unassignedColor: '',
        icon: '',
        inheritedIcon: '',
        effectiveIcon: '',
        typeId: effectiveTypeId,
        typeLabel: omoHolonTemplateGetTypeLabel(effectiveTypeId) || String(firstType.name || 'Élément'),
        visible: false,
        mandatory: false,
        lockedName: false,
        lockedIcon: false,
        inheritedLockedIcon: false,
        effectiveLockedIcon: false,
        unique: false,
        link: false,
        adminParent: false,
        adminMin: null,
        adminMax: null,
        lockedAdminMin: false,
        lockedAdminMax: false,
        inheritedLockedAdminMin: false,
        inheritedLockedAdminMax: false,
        effectiveLockedAdminMin: false,
        effectiveLockedAdminMax: false,
        permissionAssignments: {},
        inheritsFromId: suggestedInheritanceId,
        definedInId: suggestedDefinitionHolonId,
        canAddProperties: inheritedTemplate
            ? Boolean(inheritedTemplate.canAddProperties)
            : Boolean(omoHolonTemplateState.data.canAddTemplateProperties),
        properties: omoHolonTemplateComputeDraftProperties(suggestedInheritanceId, [])
    });
}

function omoHolonTemplateRenderSummary() {
    const templates = omoHolonTemplateGetAll();
    const propertyCount = templates.reduce(function (total, template) {
        return total + ((template.properties || []).length || 0);
    }, 0);

    omoHolonTemplateElements.summary.innerHTML = ''
        + '<div class="omo-template-stat generic-soft-panel generic-stack generic-stack--compact">'
        + '  <strong>' + templates.length + '</strong>'
        + '  <span>' + omoHolonTemplateEscapeHtml(templates.length > 1 ? (omoHolonTemplateTexts.summaryModelOther || '') : (omoHolonTemplateTexts.summaryModelOne || '')) + '</span>'
        + '</div>'
        + '<div class="omo-template-stat generic-soft-panel generic-stack generic-stack--compact">'
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
        html += '<button type="button" class="omo-template-tree__button generic-soft-panel generic-stack generic-stack--compact' + (isSelected ? ' is-selected' : '') + '" data-template-select="' + Number(node.id) + '" data-template-context-id="' + definedInId + '">';
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

function omoHolonTemplateSetDefinitionHolonOptionLabels(showTree) {
    if (!omoHolonTemplateElements.definitionHolon) {
        return;
    }

    Array.from(omoHolonTemplateElements.definitionHolon.options).forEach(function (option) {
        option.textContent = showTree
            ? String(option.dataset.treeLabel || option.dataset.plainLabel || '')
            : String(option.dataset.plainLabel || option.dataset.treeLabel || '');
    });
}

function omoHolonTemplateBuildDefinitionHolonOptions(selectedHolonId, allowedHolonIds) {
    if (!omoHolonTemplateElements.definitionHolon) {
        return;
    }

    const options = Array.isArray(omoHolonTemplateState.data.definitionHolonCatalog)
        ? omoHolonTemplateState.data.definitionHolonCatalog
        : [];
    const selectedId = Number(selectedHolonId || omoHolonTemplateState.data.rootHolonId || 0);
    const allowedIds = Array.isArray(allowedHolonIds)
        ? allowedHolonIds.map(function (id) { return Number(id || 0); })
        : null;

    const visibleHolons = options.filter(function (holon) {
        return allowedIds === null || allowedIds.indexOf(Number(holon.id || 0)) !== -1;
    });
    const holonsById = {};
    const roots = [];

    visibleHolons.forEach(function (holon) {
        holonsById[Number(holon.id || 0)] = {
            holon: holon,
            children: []
        };
    });
    visibleHolons.forEach(function (holon) {
        const holonId = Number(holon.id || 0);
        const parentId = Number(holon.parentId || 0);
        const entry = holonsById[holonId];
        if (parentId > 0 && holonsById[parentId]) {
            holonsById[parentId].children.push(entry);
        } else {
            roots.push(entry);
        }
    });

    const sortEntries = function (entries) {
        entries.sort(function (left, right) {
            return String(left.holon.name || left.holon.pathLabel || '').localeCompare(
                String(right.holon.name || right.holon.pathLabel || ''),
                undefined,
                { sensitivity: 'base' }
            );
        });
        entries.forEach(function (entry) {
            sortEntries(entry.children);
        });
    };
    sortEntries(roots);

    omoHolonTemplateElements.definitionHolon.innerHTML = '';
    const appendEntries = function (entries, prefix, hasParent) {
        entries.forEach(function (entry, index) {
            const isLast = index === entries.length - 1;
            const branch = hasParent ? prefix + (isLast ? '└─ ' : '├─ ') : '';
            const plainLabel = String(entry.holon.name || entry.holon.pathLabel || '');
            const option = document.createElement('option');
            option.value = Number(entry.holon.id || 0);
            option.dataset.plainLabel = plainLabel;
            option.dataset.treeLabel = branch + plainLabel;
            option.textContent = option.dataset.plainLabel;
            option.selected = Number(entry.holon.id || 0) === selectedId;
            omoHolonTemplateElements.definitionHolon.appendChild(option);

            appendEntries(
                entry.children,
                hasParent ? prefix + (isLast ? '   ' : '│  ') : '',
                true
            );
        });
    };
    appendEntries(roots, '', false);

    const hasSelectedOption = Array.from(omoHolonTemplateElements.definitionHolon.options).some(function (option) {
        return Number(option.value || 0) === selectedId;
    });
    if (hasSelectedOption) {
        omoHolonTemplateElements.definitionHolon.value = String(selectedId);
    }
    omoHolonTemplateSetDefinitionHolonOptionLabels(false);
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
    row.className = 'omo-template-property generic-section';
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
        const items = [];
        rawValue.split(/\r\n|\r|\n|\|/).forEach(function (segment) {
            const normalizedSegment = String(segment || '').trim();
            if (!normalizedSegment) {
                return;
            }

            try {
                const decodedSegment = JSON.parse(normalizedSegment);
                if (Array.isArray(decodedSegment)) {
                    decodedSegment.forEach(function (item) {
                        items.push(item);
                    });
                    return;
                }
            } catch (segmentError) {
            }

            items.push(normalizedSegment);
        });
        return items;
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
    const authority = authorityId > 0 ? omoHolonTemplateGetAuthorityCatalog().find(function (entry) {
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

    const labelField = authorityRow.querySelector('.omo-template-authority__label');
    const parentField = authorityRow.querySelector('.omo-template-authority__parent');
    const descriptionField = authorityRow.querySelector('.omo-template-authority__description');
    const delegationField = authorityRow.querySelector('.omo-template-authority__delegation');
    const localField = authorityRow.querySelector('.omo-template-authority__local');
    if (authorityId > 0 && !labelField && !parentField && !descriptionField) {
        return { id: authorityId };
    }

    const label = String(labelField && labelField.value ? labelField.value : '').trim();
    const parentId = Number(parentField && parentField.value ? parentField.value : 0);
    const isLocal = Boolean(localField && localField.checked);
    let description = String(descriptionField && descriptionField.value ? descriptionField.value : '').trim();
    if (authority && authority.needsParent && parentId <= 0) {
        description = '[OMO1_IMPORT_NEEDS_PARENT] ' + description;
    }
    const delegationMode = String(delegationField && delegationField.value ? delegationField.value : 'partial');
    if (authorityId > 0) {
        return { id: authorityId, label: label, parentId: parentId, description: description, isLocal: isLocal };
    }

    if (delegationMode === 'complete') {
        return parentId > 0 ? { parentId: parentId, delegationMode: 'complete' } : null;
    }
    return label !== '' || parentId > 0 || description !== '' ? { label: label, parentId: parentId, description: description, isLocal: isLocal, delegationMode: 'partial' } : null;
}

function omoHolonTemplateRenderAuthorityDeletionChoices(authorityId, draft) {
    const plan = draft && draft.deletionPlan && typeof draft.deletionPlan === 'object' ? draft.deletionPlan : {};
    const authority = omoHolonTemplateGetAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === Number(authorityId || 0);
    }) || null;
    const authorityFallback = authority && authority.isLocal ? 'delete' : 'reassign';
    const checked = function (name, value, fallback) {
        return String(plan[name] || fallback) === value ? ' checked' : '';
    };
    const prefix = 'template-authority-delete-' + String(authorityId);
    const impact = omoHolonTemplateGetAuthorityDeletionImpact(authorityId, String(plan.authority || 'reassign'), String(plan.children || 'reassign'));
    return ''
        + '<div class="omo-template-authority__deletion" data-authority-deletion-options>'
        + '  <p>Choisissez ce qui doit etre conserve avant validation.</p>'
        + '  <fieldset><legend>Cette autorite</legend>'
        + '    <label><input type="radio" name="' + prefix + '-authority" value="delete" data-authority-deletion-choice="authority"' + checked('authority', 'delete', authorityFallback) + '> Supprimer definitivement</label>'
        + '    <label><input type="radio" name="' + prefix + '-authority" value="reassign" data-authority-deletion-choice="authority"' + checked('authority', 'reassign', authorityFallback) + '> Remonter au holon parent</label>'
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
        const details = authority ? (authority.needsParent ? 'A rattacher manuellement' + (authority.pathLabel || authority.holonLabel ? ' - ' : '') : '') + String(authority.pathLabel || authority.holonLabel || '') : '';
        const labelMarkup = authority && authority.isShell ? '<em>' + omoHolonTemplateEscapeHtml(label) + '</em>' : '<strong>' + omoHolonTemplateEscapeHtml(label) + '</strong>';
        return ''
            + '<div class="omo-template-authority__row omo-template-authority__row--existing' + (authority && authority.needsParent ? ' is-needs-parent' : '') + '" data-authority-entry data-authority-id="' + authorityId + '">'
            + '  <button type="button" class="omo-template-authority__edit" data-authority-edit="1">' + labelMarkup + (details ? '<small>' + omoHolonTemplateEscapeHtml(details) + '</small>' : '') + '</button>'
            + '  <button type="button" class="omo-button omo-button--ghost" data-authority-delete="1" aria-label="Supprimer l autorite">&times;</button>'
            + omoHolonTemplateRenderAuthorityDeletionChoices(authorityId, draft)
            + '</div>';
    }

    const parentId = Number(draft.parentId || (authority ? authority.parentId : 0) || 0);
    const isLocal = draft.isLocal !== undefined ? Boolean(draft.isLocal) : (authority ? Boolean(authority.isLocal) : true);
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
        const authorityLabel = String(authority.label || '');
        const sourceHolonLabel = String(authority.holonLabel || '');
        const path = authorityLabel + (sourceHolonLabel !== '' ? ' - ' + sourceHolonLabel : '');
        return '<option value="' + Number(authority.id || 0) + '"' + selected + '>'
            + omoHolonTemplateEscapeHtml(path)
            + '</option>';
    }).join('');
    const rootAuthoritySelected = isLocal && parentId <= 0;
    const rootOption = omoHolonTemplateCanCreateRootAuthority()
        ? '<option value="0"' + (parentId <= 0 ? ' selected' : '') + '>Sans autorite parente</option>'
        : '<option value="0">Autorite parente</option>';

    const delegationField = authorityId > 0 || rootAuthoritySelected ? '' : '      <select class="omo-template-authority__delegation"' + (parentId <= 0 ? ' disabled' : '') + '>'
        + '<option value="partial"' + (delegationMode !== 'complete' ? ' selected' : '') + (partialAllowed ? '' : ' disabled') + '>Delegation partielle</option>'
        + '<option value="complete"' + (delegationMode === 'complete' ? ' selected' : '') + '>Delegation complete</option></select>';
    const authorityDetails = (authorityId > 0 || parentId > 0 || rootAuthoritySelected) && (authorityId > 0 || delegationMode !== 'complete')
        ? '      <label class="omo-template-authority__local-label"><input type="checkbox" class="omo-template-authority__local"' + (isLocal ? ' checked' : '') + '> Autorite locale</label>'
            + '      <input type="text" class="omo-template-authority__label" value="' + omoHolonTemplateEscapeHtml(label) + '" placeholder="Nouvelle autorite">'
            + '      <textarea class="omo-template-authority__description" rows="3" placeholder="Description">' + omoHolonTemplateEscapeHtml(description) + '</textarea>'
        : parentId > 0 && delegationMode === 'complete'
            ? '      <div class="omo-template-authority__complete-note">L autorite parente sera deleguee completement.</div>'
            : '      <label class="omo-template-authority__local-label"><input type="checkbox" class="omo-template-authority__local"' + (isLocal ? ' checked' : '') + '> Autorite locale</label>';
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
    const rows = Array.isArray(values) && values.length ? values : [];
    const canCreateAuthority = omoHolonTemplateCanCreateRootAuthority() || omoHolonTemplateGetAuthorityParentCatalog().length > 0;
    if (!canCreateAuthority && !rows.length) {
        return '<div class="omo-template-property__empty-note">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyHelpListAuthority || '') + '</div>';
    }

    const disabledAttribute = disabled ? ' disabled' : '';
    return ''
        + '<div class="omo-template-authority">'
        + '  <div class="omo-template-authority__items">' + rows.map(omoHolonTemplateRenderAuthorityRow).join('') + '</div>'
        + (canCreateAuthority
            ? '  <button type="button" class="omo-button omo-button--secondary" data-authority-add="1"' + disabledAttribute + '>Ajouter une autorite</button>'
            : '  <div class="omo-template-property__empty-note">Les autorites locales de ce modele seront ajoutees automatiquement a ses instances.</div>')
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
        projectScope: 'local',
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
    normalized.projectScope = ['local', 'children', 'descendants', 'global'].indexOf(String(normalized.projectScope || '')) >= 0
        ? String(normalized.projectScope)
        : 'local';
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

function omoHolonTemplateGetProjectCatalog(scope) {
    const catalogs = omoHolonTemplateState.data.projectCatalogs || {};
    const normalizedScope = ['local', 'children', 'descendants', 'global'].indexOf(String(scope || '')) >= 0
        ? String(scope)
        : 'local';

    if (Array.isArray(catalogs[normalizedScope])) {
        return catalogs[normalizedScope];
    }

    return normalizedScope === 'local' ? (omoHolonTemplateState.data.projectCatalog || []) : [];
}

function omoHolonTemplateFindProject(projectId) {
    const targetId = Number(projectId || 0);
    const scopes = ['local', 'children', 'descendants', 'global'];
    for (let index = 0; index < scopes.length; index += 1) {
        const project = omoHolonTemplateGetProjectCatalog(scopes[index]).find(function (candidate) {
            return Number(candidate.id || 0) === targetId;
        });
        if (project) {
            return project;
        }
    }

    return null;
}

function omoHolonTemplateReadProjectPickerSelectedIds(picker) {
    if (!picker) {
        return [];
    }

    return String(picker.dataset.selectedIds || '').split(',').map(function (value) {
        return Number(value || 0);
    }).filter(Boolean);
}

function omoHolonTemplateRenderProjectPickerHtml(property, listValues, valueDisabled) {
    const scope = String(property.projectScope || 'local');
    const projectOptions = omoHolonTemplateGetProjectCatalog(scope);
    const selectedIds = Array.from(new Set(listValues.map(Number).filter(Boolean)));
    const selectedIdMap = new Set(selectedIds);
    const scopeButtons = [
        ['local', omoHolonTemplateTexts.propertyProjectScopeLocal || ''],
        ['children', omoHolonTemplateTexts.propertyProjectScopeChildren || ''],
        ['descendants', omoHolonTemplateTexts.propertyProjectScopeDescendants || ''],
        ['global', omoHolonTemplateTexts.propertyProjectScopeGlobal || '']
    ].map(function (option) {
        const isActive = scope === option[0];
        return '<button type="button" class="omo-segmented__button omo-template-project-picker__scope-button'
            + (isActive ? ' is-active' : '') + '" data-project-scope="' + option[0]
            + '" aria-pressed="' + (isActive ? 'true' : 'false') + '">'
            + omoHolonTemplateEscapeHtml(option[1]) + '</button>';
    }).join('');
    const searchHtml = '<label class="omo-view-filter__search omo-template-project-picker__search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" placeholder="'
        + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyProjectSearch || '') + '" aria-label="'
        + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyProjectSearch || '') + '"'
        + valueDisabled + '></label>';
    const optionsHtml = projectOptions.length === 0
        ? '<div class="omo-template-property__empty-note">' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyProjectEmpty || '') + '</div>'
        : projectOptions.map(function (project) {
            const checked = selectedIdMap.has(Number(project.id)) ? ' checked' : '';
            return ''
                + '<label class="omo-template-project-picker__option">'
                + '  <input type="checkbox" class="omo-template-property__value omo-template-property__value--project" value="' + Number(project.id) + '"' + checked + valueDisabled + '>'
                + '  <span>' + omoHolonTemplateEscapeHtml(project.title) + (project.holonLabel ? ' <small>' + omoHolonTemplateEscapeHtml(project.holonLabel) + '</small>' : '') + '</span>'
                + '</label>';
        }).join('');

    return ''
        + '<div class="omo-template-project-picker" data-project-picker data-selected-ids="' + selectedIds.join(',') + '">'
        + '  <div class="omo-view-filter__input omo-template-project-picker__toolbar">'
        + '  <div class="omo-segmented omo-template-project-picker__scopes" role="group" aria-label="' + omoHolonTemplateEscapeHtml(omoHolonTemplateTexts.propertyProjectScope || '') + '">' + scopeButtons + '</div>'
        + searchHtml
        + '  </div>'
        + '  <div class="omo-template-project-picker__list">' + optionsHtml + '</div>'
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
            return omoHolonTemplateRenderProjectPickerHtml(normalizedProperty, listValues, valueDisabled);
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
    row.className = 'omo-template-property generic-section';
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
            const projectPicker = row.querySelector('[data-project-picker]');
            if (projectPicker) {
                const selectedIds = omoHolonTemplateReadProjectPickerSelectedIds(projectPicker);
                return selectedIds.length ? JSON.stringify(selectedIds) : '';
            }
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
            const projectPicker = String(listItemType || 'text') === 'project'
                ? row.querySelector('[data-project-picker]')
                : null;
            if (projectPicker) {
                items = omoHolonTemplateReadProjectPickerSelectedIds(projectPicker);
            } else {
                const modifier = String(listItemType || 'text') === 'holon' ? 'holon' : 'project';
                items = Array.from(row.querySelectorAll('.omo-template-property__value--' + modifier + ':checked')).map(function (input) { return Number(input.value || 0); }).filter(Boolean);
            }
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
        projectScope: String((row.querySelector('.omo-template-project-picker__scope-button[aria-pressed="true"]') || { dataset: {} }).dataset.projectScope || 'local'),
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
    row.className = 'omo-template-property generic-section';
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
        projectScope: String((row.querySelector('.omo-template-project-picker__scope-button[aria-pressed="true"]') || { dataset: {} }).dataset.projectScope || 'local'),
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
        const project = omoHolonTemplateFindProject(projectId);
        return project ? project.title : rawValue;
    }

    if (listItemType === 'authority') {
        const authorityId = omoHolonTemplateGetAuthorityId(item);
        const authority = omoHolonTemplateGetAuthorityCatalog().find(function (entry) {
            return Number(entry.id || 0) === authorityId;
        });
        if (authority) {
            return String(authority.label || authority.pathLabel || rawValue);
        }
        if (item && typeof item === 'object' && !Array.isArray(item)) {
            return String(item.label || item.value || authorityId || '');
        }
        return rawValue;
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
                        + '<details class="generic-accordion generic-accordion--inset omo-template-property__detail-card">'
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

function omoHolonTemplateShowWelcome() {
    if (omoHolonTemplateIsHolonDefinitionMode()) {
        return;
    }

    omoHolonTemplateState.selectedId = null;
    if (omoHolonTemplateElements.welcome) {
        omoHolonTemplateElements.welcome.hidden = false;
    }
    if (omoHolonTemplateElements.formContent) {
        omoHolonTemplateElements.formContent.hidden = true;
    }
    omoHolonTemplateRenderTree();
}

function omoHolonTemplateShowForm() {
    if (omoHolonTemplateElements.welcome) {
        omoHolonTemplateElements.welcome.hidden = true;
    }
    if (omoHolonTemplateElements.formContent) {
        omoHolonTemplateElements.formContent.hidden = false;
    }
}

function omoHolonTemplateFillForm(template, options) {
    omoHolonTemplateShowForm();
    const current = template || omoHolonTemplateBuildDraft();
    const settings = options && typeof options === 'object' ? options : {};
    const isExisting = Number(current.id || 0) > 0;
    const isHolonDefinitionMode = omoHolonTemplateIsHolonDefinitionMode();
    omoHolonTemplateState.removedPropertyIds = [];
    const resolvedParentId = Number(current.inheritsFromId || 0);
    const effectiveInheritanceId = omoHolonTemplateGetEffectiveInheritanceIdFromParent(resolvedParentId);
    const effectiveTypeId = omoHolonTemplateGetEffectiveTypeId(current.typeId, effectiveInheritanceId);

    omoHolonTemplateElements.form.dataset.templateId = Number(current.id || 0);
    omoHolonTemplateElements.form.dataset.previousParentId = String(resolvedParentId);
    omoHolonTemplateElements.form.dataset.definitionHolonId = String(Number(current.definedInId || omoHolonTemplateState.data.rootHolonId || 0));
    if (omoHolonTemplateElements.deleteButton) {
        omoHolonTemplateElements.deleteButton.hidden = !isExisting || isHolonDefinitionMode;
        omoHolonTemplateElements.deleteButton.disabled = !isExisting || isHolonDefinitionMode;
    }
    omoHolonTemplateToggleTypeField(effectiveInheritanceId > 0);
    omoHolonTemplateFillTypeOptions(effectiveTypeId);
    omoHolonTemplateBuildParentOptions(resolvedParentId, current.id);
    omoHolonTemplateBuildDefinitionHolonOptions(
        Number(current.definedInId || omoHolonTemplateState.data.rootHolonId || 0),
        current.definitionHolonIds
    );
    omoHolonTemplateElements.name.value = current.name || '';
    if (omoHolonTemplateElements.colorEnabled) {
        omoHolonTemplateElements.colorEnabled.checked = String(current.color || '').trim() !== '';
    }
    if (omoHolonTemplateElements.color) {
        omoHolonTemplateElements.color.value = current.color || '#f59e0b';
    }
    omoHolonTemplateSyncColorField();
    if (omoHolonTemplateElements.unassignedColorEnabled) {
        omoHolonTemplateElements.unassignedColorEnabled.checked = String(current.unassignedColor || '').trim() !== '';
    }
    if (omoHolonTemplateElements.unassignedColor) {
        omoHolonTemplateElements.unassignedColor.value = current.unassignedColor || '#94a3b8';
    }
    omoHolonTemplateSyncUnassignedColorField();
    if (omoHolonTemplateElements.mandatory) {
        omoHolonTemplateElements.mandatory.checked = Boolean(current.mandatory);
    }
    if (omoHolonTemplateElements.lockedName) {
        omoHolonTemplateElements.lockedName.checked = Boolean(current.lockedName);
    }
    if (omoHolonTemplateElements.lockedIcon) {
        omoHolonTemplateElements.lockedIcon.checked = Boolean(current.lockedIcon);
        omoHolonTemplateElements.lockedIcon.disabled = false;
    }
    if (omoHolonTemplateElements.unique) {
        omoHolonTemplateElements.unique.checked = Boolean(current.unique);
    }
    if (omoHolonTemplateElements.link) {
        omoHolonTemplateElements.link.checked = Boolean(current.link);
    }
    if (omoHolonTemplateElements.adminParent) {
        omoHolonTemplateElements.adminParent.checked = Boolean(current.adminParent);
    }
    omoHolonTemplateSyncRoleFlags();
    omoHolonTemplateSyncAdminBounds(current);
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
    omoHolonTemplateRenderMediaFields(current, Boolean(settings.preserveMediaState));
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
            omoHolonTemplateShowWelcome();
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
    Promise.all(pendingMediaFlushes)
        .then(function () {
            const payload = {
                id: Number(omoHolonTemplateElements.form.dataset.templateId || 0),
                typeId: omoHolonTemplateGetEffectiveTypeId(omoHolonTemplateElements.type.value || 0, omoHolonTemplateGetEffectiveInheritanceIdFromParent(omoHolonTemplateElements.parent.value || 0)),
                name: omoHolonTemplateElements.name.value.trim(),
                color: Boolean(omoHolonTemplateElements.colorEnabled && omoHolonTemplateElements.colorEnabled.checked)
                    ? String(omoHolonTemplateElements.color && omoHolonTemplateElements.color.value ? omoHolonTemplateElements.color.value : '')
                    : '',
                unassignedColor: Boolean(omoHolonTemplateElements.unassignedColorEnabled && omoHolonTemplateElements.unassignedColorEnabled.checked)
                    ? String(omoHolonTemplateElements.unassignedColor && omoHolonTemplateElements.unassignedColor.value ? omoHolonTemplateElements.unassignedColor.value : '')
                    : '',
                icon: omoHolonTemplateMediaFields.icon ? omoHolonTemplateMediaFields.icon.getValue() : '',
                mandatory: Boolean(omoHolonTemplateElements.mandatory && omoHolonTemplateElements.mandatory.checked),
                lockedName: Boolean(omoHolonTemplateElements.lockedName && omoHolonTemplateElements.lockedName.checked),
                lockedIcon: omoHolonTemplateElements.lockedIcon
                    ? (omoHolonTemplateElements.lockedIcon.disabled
                        ? String(omoHolonTemplateElements.lockedIcon.dataset.localValue || '0') === '1'
                        : Boolean(omoHolonTemplateElements.lockedIcon.checked))
                    : false,
                unique: Boolean(omoHolonTemplateElements.unique && omoHolonTemplateElements.unique.checked),
                link: Boolean(omoHolonTemplateElements.link && omoHolonTemplateElements.link.checked),
                adminParent: Boolean(omoHolonTemplateElements.adminParent && omoHolonTemplateElements.adminParent.checked),
                adminMin: omoHolonTemplateReadAdminBound(omoHolonTemplateElements.adminMin),
                adminMax: omoHolonTemplateReadAdminBound(omoHolonTemplateElements.adminMax),
				lockedAdminMin: omoHolonTemplateReadAdminLock(omoHolonTemplateElements.lockedAdminMin),
				lockedAdminMax: omoHolonTemplateReadAdminLock(omoHolonTemplateElements.lockedAdminMax),
                inheritsFromId: omoHolonTemplateGetEffectiveInheritanceIdFromParent(omoHolonTemplateElements.parent.value || 0),
                definitionHolonId: Number((omoHolonTemplateElements.definitionHolon || {}).value || omoHolonTemplateElements.form.dataset.definitionHolonId || omoHolonTemplateState.data.rootHolonId || 0),
                permissions: omoHolonTemplateReadPermissions(),
                removedPropertyIds: omoHolonTemplateState.removedPropertyIds.slice(),
                properties: omoHolonTemplateReadProperties()
            };

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
            omoHolonTemplateFillForm(
                (result.data.template && omoHolonTemplateFind(result.data.template.id))
                || result.data.template
                || omoHolonTemplateBuildDraft()
            );
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

if (omoHolonTemplateElements.definitionHolon) {
    ['focus', 'pointerdown', 'keydown'].forEach(function (eventName) {
        omoHolonTemplateElements.definitionHolon.addEventListener(eventName, function () {
            omoHolonTemplateSetDefinitionHolonOptionLabels(true);
        });
    });
    ['blur', 'change'].forEach(function (eventName) {
        omoHolonTemplateElements.definitionHolon.addEventListener(eventName, function () {
            omoHolonTemplateSetDefinitionHolonOptionLabels(false);
        });
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
            if (Number(omoHolonTemplateElements.form.dataset.templateId || 0) <= 0) {
                const inheritanceTemplate = omoHolonTemplateFind(nextParentId);
                omoHolonTemplateElements.form.dataset.definitionHolonId = String(Number(
                    (inheritanceTemplate && inheritanceTemplate.definedInId)
                    || omoHolonTemplateState.data.rootHolonId
                    || 0
                ));
            }
            omoHolonTemplateRefreshInheritancePreview();
            return;
        }

        if (event.target === omoHolonTemplateElements.type) {
            omoHolonTemplateSyncRoleFlags();
            return;
        }

        if (event.target === omoHolonTemplateElements.colorEnabled) {
            omoHolonTemplateSyncColorField();
            return;
        }

        if (event.target === omoHolonTemplateElements.unassignedColorEnabled) {
            omoHolonTemplateSyncUnassignedColorField();
            return;
        }

        if (event.target.matches('.omo-template-property__value--project')) {
            const projectPicker = event.target.closest('[data-project-picker]');
            if (!projectPicker) {
                return;
            }

            const selectedIds = new Set(omoHolonTemplateReadProjectPickerSelectedIds(projectPicker));
            Array.from(projectPicker.querySelectorAll('.omo-template-property__value--project')).forEach(function (projectInput) {
                const projectId = Number(projectInput.value || 0);
                if (projectId <= 0) {
                    return;
                }
                if (projectInput.checked) {
                    selectedIds.add(projectId);
                } else {
                    selectedIds.delete(projectId);
                }
            });
            projectPicker.dataset.selectedIds = Array.from(selectedIds).join(',');
            return;
        }

        if (event.target.matches('[data-authority-deletion-choice]')) {
            const authorityRow = event.target.closest('[data-authority-entry]');
            if (authorityRow) {
                omoHolonTemplateUpdateAuthorityDeletionCounts(authorityRow);
            }
            return;
        }

        if (event.target.matches('.omo-template-authority__parent, .omo-template-authority__delegation, .omo-template-authority__local')) {
            const authorityRow = event.target.closest('[data-authority-entry]');
            if (authorityRow) {
                const authorityId = Number(authorityRow.getAttribute('data-authority-id') || 0);
                authorityRow.outerHTML = omoHolonTemplateRenderAuthorityRow({
                    id: authorityId,
                    editing: authorityId > 0,
                    parentId: Number((authorityRow.querySelector('.omo-template-authority__parent') || {}).value || 0),
                    delegationMode: String((authorityRow.querySelector('.omo-template-authority__delegation') || {}).value || 'partial'),
                    label: String((authorityRow.querySelector('.omo-template-authority__label') || {}).value || ''),
                    description: String((authorityRow.querySelector('.omo-template-authority__description') || {}).value || ''),
                    isLocal: Boolean((authorityRow.querySelector('.omo-template-authority__local') || {}).checked)
                });
                return;
            }
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

    omoHolonTemplateElements.root.addEventListener('input', function (event) {
        if (!event.target.matches('.omo-template-project-picker__search')) {
            return;
        }

        const searchValue = String(event.target.value || '').trim().toLocaleLowerCase();
        const projectPicker = event.target.closest('[data-project-picker]');
        if (!projectPicker) {
            return;
        }

        Array.from(projectPicker.querySelectorAll('.omo-template-project-picker__option')).forEach(function (option) {
            option.hidden = searchValue !== ''
                && !String(option.textContent || '').toLocaleLowerCase().includes(searchValue);
        });
    });

    omoHolonTemplateElements.root.addEventListener('click', function (event) {
        const projectScopeButton = event.target.closest('.omo-template-project-picker__scope-button');
        if (projectScopeButton) {
            const projectPicker = projectScopeButton.closest('[data-project-picker]');
            const propertyRow = projectScopeButton.closest('.omo-template-property');
            if (!projectPicker || !propertyRow || projectScopeButton.getAttribute('aria-pressed') === 'true') {
                return;
            }

            const propertyState = omoHolonTemplateReadPropertyState(propertyRow);
            propertyState.projectScope = String(projectScopeButton.getAttribute('data-project-scope') || 'local');
            propertyRow.replaceWith(omoHolonTemplateCreatePropertyRow(propertyState));
            return;
        }

        const selectButton = event.target.closest('[data-template-select]');
        if (selectButton) {
            const targetTemplateId = Number(selectButton.getAttribute('data-template-select') || 0);
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
                const selectedTemplate = omoHolonTemplateFind(omoHolonTemplateState.selectedId);
                omoHolonTemplateState.selectedId = null;
                omoHolonTemplateRenderTree();
                omoHolonTemplateFillForm(
                    omoHolonTemplateBuildDraft(
                        Number(omoHolonTemplateElements.form.dataset.templateId || 0),
                        selectedTemplate ? Number(selectedTemplate.definedInId || 0) : 0
                    )
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
                const propertyId = Number(row.dataset.propertyId || 0);
                if (propertyId > 0 && omoHolonTemplateState.removedPropertyIds.indexOf(propertyId) === -1) {
                    omoHolonTemplateState.removedPropertyIds.push(propertyId);
                }
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
};
