window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/organizations/create_import_popup.js"] = function (pageConfig, pageScript) {
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
    var propertyMappingsValue = form ? form.querySelector('[data-omo-create-import-property-mapping-value="1"]') : null;
    var memberInvitationEmailChoice = form ? form.querySelector('[data-omo-create-import-member-invitation-email-choice="1"]') : null;
    var submitButton = root.querySelector('[data-omo-create-import-submit="1"]');
    var feedback = root.querySelector('[data-omo-create-import-feedback="1"]');
    var cancelButton = root.querySelector('[data-omo-create-import-cancel="1"]');
    var waiting = root.querySelector('[data-omo-create-import-waiting="1"]');
    var moduleNames = ['structure', 'rules', 'members', 'documents', 'projects', 'tasks', 'checklists', 'indicators', 'calendar', 'pv'];
    var templateCatalog = pageConfig.templateCatalog;
    var importPayload = null;
    var templateMappings = {};
    var touchedTemplateMappings = {};
    var propertyMappings = {};
    var touchedPropertyMappings = {};
    var isImporting = false;
    var ui = pageConfig.ui;

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

    function findAutomaticTemplateMapping(sourceNode, candidates, usedTargetIds) {
        var sourceId = Number(sourceNode && sourceNode.id || 0);
        var matchingIds = candidates.filter(function (candidate) {
            return Number(candidate && candidate.id || 0) === sourceId
                && !usedTargetIds[sourceId];
        });
        if (matchingIds.length === 1) {
            return sourceId;
        }

        var sourceName = normalizeMappingKey(sourceNode && (sourceNode.templateName || sourceNode.name));
        if (!sourceName) {
            return 0;
        }

        matchingIds = candidates.filter(function (candidate) {
            var candidateId = Number(candidate && candidate.id || 0);
            return candidateId > 0
                && !usedTargetIds[candidateId]
                && normalizeMappingKey(candidate && candidate.name) === sourceName;
        });

        return matchingIds.length === 1 ? Number(matchingIds[0].id || 0) : 0;
    }

    function selectedTemplateModel() {
        var selectedId = Number(templateSelect && templateSelect.value || 0);
        return templateCatalog.find(function (template) { return Number(template.id || 0) === selectedId; }) || null;
    }

    function syncTemplateMappingsValue() {
        if (mappingsValue) { mappingsValue.value = JSON.stringify(templateMappings); }
    }

    function syncPropertyMappingsValue() {
        if (propertyMappingsValue) { propertyMappingsValue.value = JSON.stringify(propertyMappings); }
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

    function hasDuplicatePropertyMappings() {
        return Object.keys(propertyMappings).some(function (sourceTemplateId) {
            var usedTargetIds = {};
            var mappings = propertyMappings[sourceTemplateId] || {};
            return Object.keys(mappings).some(function (sourcePropertyId) {
                var targetId = Number(mappings[sourcePropertyId] || 0);
                if (targetId <= 0) { return false; }
                if (usedTargetIds[targetId]) { return true; }
                usedTargetIds[targetId] = true;
                return false;
            });
        });
    }

    function normalizeMappingKey(value) {
        var normalized = String(value || '').trim().toLocaleLowerCase();
        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return normalized.replace(/[^a-z0-9]+/g, '');
    }

    function propertyMatchKeys(property) {
        var keys = {};
        [property && property.shortname, property && property.name].forEach(function (value) {
            var key = normalizeMappingKey(value);
            if (key) { keys[key] = true; }
        });
        var aliasGroups = [
            ['rde', 'raisonetre', 'raisondetre', 'purpose'],
            ['autorite', 'autorites', 'domainedautorite', 'domainesdautorite', 'domainautorite', 'domainesautorite', 'authority', 'authorities', 'authoritydomain', 'authoritydomains']
        ];
        aliasGroups.forEach(function (aliases) {
            if (aliases.some(function (alias) { return !!keys[alias]; })) {
                aliases.forEach(function (alias) { keys[alias] = true; });
            }
        });
        return Object.keys(keys);
    }

    function isAuthorityListProperty(property) {
        var listItemType = String(property && property.listItemType || '').trim().toLocaleLowerCase();
        return listItemType === 'authority' || listItemType === 'autorite';
    }

    function propertyFormatLabel(property) {
        var formatName = String(property && property.formatName || '').trim();
        var listItemType = String(property && property.listItemType || '').trim();
        if (formatName && listItemType) { return formatName + ' - ' + listItemType; }
        return formatName || listItemType || '';
    }

    function importedHolonsById() {
        var byId = {};
        flattenHolons(importPayload && importPayload.holons, []).forEach(function (node) {
            var nodeId = Number(node && node.id || 0);
            if (nodeId > 0) { byId[nodeId] = node; }
        });
        return byId;
    }

    function collectImportedTemplatePropertyIds(sourceTemplateId, nodesById) {
        var propertyIds = {};
        var visited = {};
        var currentId = Number(sourceTemplateId || 0);
        while (currentId > 0 && !visited[currentId] && nodesById[currentId]) {
            visited[currentId] = true;
            var node = nodesById[currentId];
            (Array.isArray(node.properties) ? node.properties : []).forEach(function (row) {
                var propertyId = Number(row && row.propertyId || 0);
                if (propertyId > 0) { propertyIds[propertyId] = true; }
            });
            currentId = Number(node.templateId || 0);
        }
        return propertyIds;
    }

    function findAutomaticPropertyMapping(sourceProperty, targetProperties) {
        var sourcePropertyId = Number(sourceProperty && sourceProperty.id || 0);
        if (sourcePropertyId > 0) {
            var identifierMatches = targetProperties.filter(function (targetProperty) {
                return Number(targetProperty && targetProperty.id || 0) === sourcePropertyId;
            });
            if (identifierMatches.length === 1) {
                return sourcePropertyId;
            }
        }
        var sourceKeys = propertyMatchKeys(sourceProperty);
        var namedMatches = targetProperties.filter(function (targetProperty) {
            var targetKeys = propertyMatchKeys(targetProperty);
            return sourceKeys.some(function (key) { return targetKeys.indexOf(key) !== -1; });
        });
        if (namedMatches.length === 1) {
            return Number(namedMatches[0].id || 0);
        }
        if (isAuthorityListProperty(sourceProperty)) {
            var authorityMatches = targetProperties.filter(isAuthorityListProperty);
            if (authorityMatches.length === 1) {
                return Number(authorityMatches[0].id || 0);
            }
        }
        return 0;
    }

    function renderPropertyMappingsForTemplate(propertyMappingsList, sourceNode, targetNode) {
        var sourceTemplateId = Number(sourceNode && sourceNode.id || 0);
        var sourceDefinitions = Array.isArray(importPayload && importPayload.propertyDefinitions)
            ? importPayload.propertyDefinitions
            : [];
        var sourceDefinitionsById = {};
        sourceDefinitions.forEach(function (definition) {
            var propertyId = Number(definition && definition.id || 0);
            if (propertyId > 0) { sourceDefinitionsById[propertyId] = definition; }
        });
        var nodesById = importedHolonsById();
        var sourcePropertyIds = collectImportedTemplatePropertyIds(sourceTemplateId, nodesById);
        var targetProperties = Array.isArray(targetNode && targetNode.properties)
            ? targetNode.properties.slice()
            : [];
        var targetPropertiesById = {};
        targetProperties.forEach(function (property) {
            var propertyId = Number(property && property.id || 0);
            if (propertyId > 0) { targetPropertiesById[propertyId] = property; }
        });

        var sourceProperties = Object.keys(sourcePropertyIds).map(function (propertyId) {
            return sourceDefinitionsById[Number(propertyId)] || null;
        }).filter(Boolean).sort(function (left, right) {
            var positionDifference = Number(left.position || 0) - Number(right.position || 0);
            return positionDifference || String(left.name || left.shortname || '').localeCompare(String(right.name || right.shortname || ''));
        });
        targetProperties.sort(function (left, right) {
            var positionDifference = Number(left.position || 0) - Number(right.position || 0);
            return positionDifference || String(left.name || left.shortname || '').localeCompare(String(right.name || right.shortname || ''));
        });

        var currentMappings = propertyMappings[sourceTemplateId] && typeof propertyMappings[sourceTemplateId] === 'object'
            ? propertyMappings[sourceTemplateId]
            : {};
        propertyMappings[sourceTemplateId] = currentMappings;
        var currentTouchedMappings = touchedPropertyMappings[sourceTemplateId] && typeof touchedPropertyMappings[sourceTemplateId] === 'object'
            ? touchedPropertyMappings[sourceTemplateId]
            : {};
        touchedPropertyMappings[sourceTemplateId] = currentTouchedMappings;

        Object.keys(currentMappings).forEach(function (sourcePropertyId) {
            var mappedTargetPropertyId = Number(currentMappings[sourcePropertyId] || 0);
            if (
                !sourcePropertyIds[sourcePropertyId]
                || (mappedTargetPropertyId > 0 && !targetPropertiesById[mappedTargetPropertyId])
            ) {
                delete currentMappings[sourcePropertyId];
                delete currentTouchedMappings[sourcePropertyId];
            }
        });

        if (sourceProperties.length === 0 || targetProperties.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'omo-create-import__mapping-empty';
            empty.textContent = ui.propertyMappingNone;
            propertyMappingsList.appendChild(empty);
            syncPropertyMappingsValue();
            return;
        }

        sourceProperties.forEach(function (sourceProperty) {
            var sourcePropertyId = Number(sourceProperty.id || 0);
            var hasStoredMapping = Object.prototype.hasOwnProperty.call(currentMappings, sourcePropertyId);
            var selectedId = hasStoredMapping ? Number(currentMappings[sourcePropertyId] || 0) : 0;
            if (!hasStoredMapping && !currentTouchedMappings[sourcePropertyId]) {
                selectedId = findAutomaticPropertyMapping(sourceProperty, targetProperties);
                if (selectedId > 0) { currentMappings[sourcePropertyId] = selectedId; }
            }

            var row = document.createElement('label');
            row.className = 'omo-create-import__mapping-row';
            var source = document.createElement('span');
            source.className = 'omo-create-import__mapping-source';
            var sourceFormat = propertyFormatLabel(sourceProperty);
            source.textContent = String(sourceProperty.name || sourceProperty.shortname || 'Propriete') + (sourceFormat ? ' (' + sourceFormat + ')' : '');
            var select = document.createElement('select');
            select.className = 'generic-form-control';
            select.setAttribute('data-omo-create-import-property-mapping-template', String(sourceTemplateId));
            select.setAttribute('data-omo-create-import-property-mapping-source', String(sourcePropertyId));
            appendOption(select, 0, ui.propertyMappingEmpty);
            appendOption(select, -1, ui.propertyMappingExclude);
            targetProperties.forEach(function (targetProperty) {
                var targetFormat = propertyFormatLabel(targetProperty);
                appendOption(
                    select,
                    Number(targetProperty.id || 0),
                    String(targetProperty.name || targetProperty.shortname || 'Propriete') + (targetFormat ? ' (' + targetFormat + ')' : '')
                );
            });
            if (selectedId === -1) {
                select.value = '-1';
                currentMappings[sourcePropertyId] = -1;
            } else if (selectedId > 0 && targetPropertiesById[selectedId]) {
                select.value = String(selectedId);
            } else {
                select.value = '0';
                if (selectedId > 0) { delete currentMappings[sourcePropertyId]; }
            }
            row.appendChild(source);
            row.appendChild(select);
            propertyMappingsList.appendChild(row);
        });
        syncPropertyMappingsValue();
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
            syncPropertyMappingsValue();
            return;
        }

        if (!sourceNodes.length) {
            var empty = document.createElement('p');
            empty.className = 'omo-create-import__mapping-empty';
            empty.textContent = ui.mappingNone;
            mappingsList.appendChild(empty);
            syncTemplateMappingsValue();
            syncPropertyMappingsValue();
            return;
        }

        var availableSourceIds = {};
        var usedTargetIds = {};
        Object.keys(templateMappings).forEach(function (sourceId) {
            var targetId = Number(templateMappings[sourceId] || 0);
            if (targetId > 0) {
                usedTargetIds[targetId] = true;
            }
        });
        sourceNodes.forEach(function (sourceNode) {
            var sourceId = Number(sourceNode.id || 0);
            availableSourceIds[sourceId] = true;
            var sourceLabel = String(sourceNode.templateName || sourceNode.name || 'Template');
            var candidates = (Array.isArray(model.nodes) ? model.nodes : []).filter(function (candidate) {
                return Number(candidate.typeId || 0) === Number(sourceNode.typeId || 0);
            });
            var item = document.createElement('div');
            item.className = 'omo-create-import__template-mapping generic-soft-panel';
            var row = document.createElement('label');
            row.className = 'omo-create-import__mapping-row';
            var source = document.createElement('span');
            source.className = 'omo-create-import__mapping-source';
            source.textContent = sourceLabel;
            var select = document.createElement('select');
            select.className = 'generic-form-control';
            select.setAttribute('data-omo-create-import-mapping-source', String(sourceId));
            appendOption(select, 0, ui.mappingEmpty);
            appendOption(select, -1, ui.mappingExclude);
            candidates.forEach(function (candidate) {
                appendOption(select, Number(candidate.id || 0), String(candidate.path || candidate.name || 'Template'));
            });
            var selectedId = Number(templateMappings[sourceId] || 0);
            if (selectedId === 0 && !touchedTemplateMappings[sourceId]) {
                var automaticTargetId = findAutomaticTemplateMapping(sourceNode, candidates, usedTargetIds);
                if (automaticTargetId > 0) {
                    selectedId = automaticTargetId;
                    templateMappings[sourceId] = selectedId;
                    usedTargetIds[selectedId] = true;
                }
            }
            if (selectedId === -1) {
                select.value = '-1';
                templateMappings[sourceId] = -1;
            } else if (selectedId > 0 && candidates.some(function (candidate) { return Number(candidate.id || 0) === selectedId; })) {
                select.value = String(selectedId);
                templateMappings[sourceId] = selectedId;
            } else if (selectedId > 0) {
                delete templateMappings[sourceId];
                delete propertyMappings[sourceId];
                delete touchedPropertyMappings[sourceId];
                delete touchedTemplateMappings[sourceId];
            } else {
                delete templateMappings[sourceId];
                delete propertyMappings[sourceId];
                delete touchedPropertyMappings[sourceId];
            }
            row.appendChild(source);
            row.appendChild(select);
            item.appendChild(row);

            if (selectedId > 0) {
                var targetNode = candidates.find(function (candidate) {
                    return Number(candidate.id || 0) === selectedId;
                }) || null;
                if (targetNode) {
                    var propertyPanel = document.createElement('div');
                    propertyPanel.className = 'omo-create-import__property-mappings';
                    var propertyTitle = document.createElement('div');
                    propertyTitle.className = 'generic-card-title generic-card-title--small';
                    propertyTitle.textContent = ui.propertyMappingTitle;
                    var propertyHelp = document.createElement('p');
                    propertyHelp.textContent = ui.propertyMappingHelp;
                    var propertyList = document.createElement('div');
                    propertyList.className = 'omo-create-import__mapping-list';
                    propertyPanel.appendChild(propertyTitle);
                    propertyPanel.appendChild(propertyHelp);
                    propertyPanel.appendChild(propertyList);
                    item.appendChild(propertyPanel);
                    renderPropertyMappingsForTemplate(propertyList, sourceNode, targetNode);
                }
            }
            mappingsList.appendChild(item);
        });

        Object.keys(templateMappings).forEach(function (sourceId) {
            if (!availableSourceIds[sourceId]) { delete templateMappings[sourceId]; }
        });
        Object.keys(touchedTemplateMappings).forEach(function (sourceId) {
            if (!availableSourceIds[sourceId]) { delete touchedTemplateMappings[sourceId]; }
        });
        Object.keys(propertyMappings).forEach(function (sourceId) {
            if (!availableSourceIds[sourceId] || Number(templateMappings[sourceId] || 0) <= 0) {
                delete propertyMappings[sourceId];
                delete touchedPropertyMappings[sourceId];
            }
        });
        syncTemplateMappingsValue();
        syncPropertyMappingsValue();
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
                    touchedTemplateMappings = {};
                    propertyMappings = {};
                    touchedPropertyMappings = {};
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
            touchedTemplateMappings[mappingSourceId] = true;
            if (mappingTargetId > 0 || mappingTargetId === -1) {
                templateMappings[mappingSourceId] = mappingTargetId;
            } else {
                delete templateMappings[mappingSourceId];
            }
            delete propertyMappings[mappingSourceId];
            delete touchedPropertyMappings[mappingSourceId];
            renderTemplateMappings();
            return;
        }
        var propertyMappingTemplateId = Number(event.target.getAttribute('data-omo-create-import-property-mapping-template') || 0);
        var propertyMappingSourceId = Number(event.target.getAttribute('data-omo-create-import-property-mapping-source') || 0);
        if (propertyMappingTemplateId > 0 && propertyMappingSourceId > 0) {
            var propertyMappingTargetId = Number(event.target.value || 0);
            if (!propertyMappings[propertyMappingTemplateId] || typeof propertyMappings[propertyMappingTemplateId] !== 'object') {
                propertyMappings[propertyMappingTemplateId] = {};
            }
            if (!touchedPropertyMappings[propertyMappingTemplateId] || typeof touchedPropertyMappings[propertyMappingTemplateId] !== 'object') {
                touchedPropertyMappings[propertyMappingTemplateId] = {};
            }
            touchedPropertyMappings[propertyMappingTemplateId][propertyMappingSourceId] = true;
            if (propertyMappingTargetId > 0 || propertyMappingTargetId === -1) {
                propertyMappings[propertyMappingTemplateId][propertyMappingSourceId] = propertyMappingTargetId;
            } else {
                propertyMappings[propertyMappingTemplateId][propertyMappingSourceId] = 0;
            }
            syncPropertyMappingsValue();
            return;
        }
        if (event.target === templateSelect) {
            templateMappings = {};
            touchedTemplateMappings = {};
            propertyMappings = {};
            touchedPropertyMappings = {};
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
        if (!memberInvitationEmailChoice || ['send', 'skip'].indexOf(memberInvitationEmailChoice.value) === -1) { showError(ui.memberInvitationEmailChoiceError); return; }
        if (hasDuplicateTemplateMappings()) { showError(ui.mappingDuplicate); return; }
        if (hasDuplicatePropertyMappings()) { showError(ui.propertyMappingDuplicate); return; }
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
};
