(function (window, document) {
    'use strict';

    function escapeHtml(value) {
        var node = document.createElement('div');
        node.textContent = String(value == null ? '' : value);
        return node.innerHTML;
    }

    function clone(value) {
        return JSON.parse(JSON.stringify(value == null ? null : value));
    }

    function init(scope) {
        var roots = (scope || document).querySelectorAll ? (scope || document).querySelectorAll('[data-governance-editor]') : [];
        Array.prototype.forEach.call(roots, function (root) {
            if (root.getAttribute('data-governance-bound') === '1') return;
            root.setAttribute('data-governance-bound', '1');

            var dataNode = root.querySelector('[data-governance-data]');
            var payload = {};
            try { payload = JSON.parse(dataNode ? dataNode.textContent : '{}'); } catch (error) { payload = {}; }
            var texts = payload.texts || {};
            var rules = Array.isArray(payload.rules) ? payload.rules : [];
            var rulesById = {};
            rules.forEach(function (rule) { rulesById[String(rule.id)] = rule; });
            var defaultRuleState = payload.defaultRuleState ? clone(payload.defaultRuleState) : {};
            var blueprint = Array.isArray(payload.blueprint) ? clone(payload.blueprint) : [];
            var editable = payload.editable !== false;
            var list = root.querySelector('[data-governance-proposals]');
            var addProposalButton = root.querySelector('[data-governance-proposal-add]');
            var form = root.querySelector('[data-governance-form]');
            var blueprintInput = root.querySelector('[data-governance-blueprint]');
            var feedback = root.querySelector('[data-governance-feedback]');
            var submit = root.querySelector('[data-governance-submit]');
            var modal = root.querySelector('[data-governance-modal]');
            var chooser = root.querySelector('[data-governance-action-chooser]');
            var ruleEditor = root.querySelector('[data-governance-rule-editor]');
            var ruleSelectField = root.querySelector('[data-governance-rule-select-field]');
            var ruleSelect = root.querySelector('[data-governance-rule-select]');
            var authoritySelect = root.querySelector('[data-governance-authority-select]');
            var titleInput = root.querySelector('[data-governance-rule-title]');
            var reviewInput = root.querySelector('[data-governance-rule-review]');
            var expirationInput = root.querySelector('[data-governance-rule-expiration]');
            var actionApply = root.querySelector('[data-governance-action-apply]');
            var deleteEditor = root.querySelector('[data-governance-rule-delete-editor]');
            var deleteRuleSelect = root.querySelector('[data-governance-delete-rule-select]');
            var deletePreview = root.querySelector('[data-governance-delete-preview]');
            var deleteApply = root.querySelector('[data-governance-delete-apply]');
            var intentionEditor = null;
            var descriptionEditor = null;
            var activeProposalIndex = -1;
            var activeActionIndex = -1;
            var activeActionType = '';

            function showFeedback(message, isError) {
                if (!feedback) return;
                feedback.hidden = false;
                feedback.textContent = String(message || '');
                feedback.classList.toggle('is-error', !!isError);
            }

            function statusLabel(status) {
                return texts[String(status || 'pending')] || String(status || 'pending');
            }

            function actionTypeLabel(action) {
                if (action && action.type === 'rule.create') return texts.ruleCreate || 'Creer une regle';
                if (action && action.type === 'rule.delete') return texts.ruleDelete || 'Supprimer une regle';
                return texts.ruleUpdate || 'Modifier une regle';
            }

            function actionRuleLabel(action) {
                var rule = rulesById[String(action && action.targetId ? action.targetId : '')] || null;
                var state = action && action.type === 'rule.delete' ? action.before : (action ? action.after : null);
                return state && state.title ? String(state.title) : (rule ? String(rule.label || '') : 'Regle');
            }

            function stateSummary(state) {
                var labels = {title: 'Titre', intention: 'Intention', description: 'Regle', review_date: 'Requestionnement', expiration_date: 'Echeance'};
                var items = [];
                Object.keys(labels).forEach(function (field) {
                    var value = String(state && state[field] ? state[field] : '').replace(/<[^>]*>/g, '').trim();
                    if (value) items.push('<p><strong>' + escapeHtml(labels[field]) + '</strong> : ' + escapeHtml(value) + '</p>');
                });
                return items.join('');
            }

            function actionSummary(action) {
                var before = action && action.before ? action.before : {};
                var after = action && action.after ? action.after : {};
                if (action && action.type === 'rule.create') return stateSummary(after);
                if (action && action.type === 'rule.delete') return stateSummary(before);
                var labels = {title: 'Titre', intention: 'Intention', description: 'Regle', review_date: 'Requestionnement', expiration_date: 'Echeance'};
                var changes = [];
                Object.keys(labels).forEach(function (field) {
                    var beforeValue = String(before[field] || '').replace(/<[^>]*>/g, '').trim();
                    var afterValue = String(after[field] || '').replace(/<[^>]*>/g, '').trim();
                    if (beforeValue === afterValue) return;
                    changes.push('<p><strong>' + escapeHtml(labels[field]) + '</strong> : ' + escapeHtml(beforeValue) + ' &rarr; ' + escapeHtml(afterValue) + '</p>');
                });
                return changes.join('');
            }

            function syncInput() {
                if (blueprintInput) blueprintInput.value = JSON.stringify(blueprint);
            }

            function render() {
                if (!list) return;
                list.innerHTML = '';
                blueprint.forEach(function (proposal, proposalIndex) {
                    var card = document.createElement('article');
                    card.className = 'omo-governance-proposal generic-section generic-section--stack';
                    var proposalTitle = String(proposal.title || '').trim() || String(texts.proposalDefault || 'Proposition __INDEX__').replace('__INDEX__', String(proposalIndex + 1));
                    card.innerHTML = ''
                        + '<div class="omo-governance-proposal__head">'
                        + ' <div class="omo-governance-proposal__copy"><label class="generic-form-field"><span class="generic-form-label">' + escapeHtml(texts.proposalTitle || 'Titre de la proposition') + '</span><input class="generic-form-control" data-governance-proposal-title maxlength="190" value="' + escapeHtml(proposalTitle) + '"' + (editable ? '' : ' readonly') + '></label></div>'
                        + (editable ? '<button type="button" class="generic-action-button generic-action-button--danger" data-governance-proposal-remove>' + escapeHtml(texts.proposalRemove || 'Retirer') + '</button>' : '')
                        + '</div><div class="omo-governance-actions" data-governance-actions></div>'
                        + (editable ? '<button type="button" class="generic-action-button generic-action-button--secondary" data-governance-action-add>' + escapeHtml(texts.actionAdd || 'Ajouter une modification') + '</button>' : '');
                    var actionsNode = card.querySelector('[data-governance-actions]');
                    (Array.isArray(proposal.actions) ? proposal.actions : []).forEach(function (action, actionIndex) {
                        var actionCard = document.createElement('div');
                        var status = String(action.status || 'pending');
                        actionCard.className = 'omo-governance-action generic-soft-panel generic-soft-panel--stack';
                        actionCard.innerHTML = ''
                            + '<div class="omo-governance-action__head"><div><strong>' + escapeHtml(actionTypeLabel(action)) + ' : ' + escapeHtml(actionRuleLabel(action)) + '</strong></div><span class="omo-governance-action__status omo-governance-action__status--' + escapeHtml(status) + '">' + escapeHtml(statusLabel(status)) + '</span></div>'
                            + '<div class="omo-governance-action__summary">' + actionSummary(action) + (action.statusMessage ? '<p>' + escapeHtml(action.statusMessage) + '</p>' : '') + '</div>'
                            + (editable && status === 'pending' ? '<div class="omo-governance-action__buttons"><button type="button" class="generic-action-button generic-action-button--secondary" data-governance-action-edit>' + escapeHtml(texts.actionEdit || 'Modifier') + '</button><button type="button" class="generic-action-button generic-action-button--danger" data-governance-action-remove>' + escapeHtml(texts.actionRemove || 'Retirer') + '</button></div>' : '');
                        var editButton = actionCard.querySelector('[data-governance-action-edit]');
                        if (editButton) editButton.addEventListener('click', function () {
                            if (action.type === 'rule.delete') openDeleteEditor(proposalIndex, actionIndex);
                            else openRuleEditor(proposalIndex, actionIndex, action.type);
                        });
                        var removeButton = actionCard.querySelector('[data-governance-action-remove]');
                        if (removeButton) removeButton.addEventListener('click', function () { proposal.actions.splice(actionIndex, 1); render(); });
                        actionsNode.appendChild(actionCard);
                    });
                    var titleField = card.querySelector('[data-governance-proposal-title]');
                    if (titleField) titleField.addEventListener('input', function () { proposal.title = titleField.value; syncInput(); });
                    var removeProposal = card.querySelector('[data-governance-proposal-remove]');
                    if (removeProposal) removeProposal.addEventListener('click', function () { blueprint.splice(proposalIndex, 1); render(); });
                    var addAction = card.querySelector('[data-governance-action-add]');
                    if (addAction) addAction.addEventListener('click', function () { openChooser(proposalIndex); });
                    list.appendChild(card);
                });
                syncInput();
            }

            function mountHtmlEditors(after) {
                if (!window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') {
                    window.setTimeout(function () { mountHtmlEditors(after); }, 50);
                    return;
                }
                var intentionHost = root.querySelector('[data-governance-html-field="intention"]');
                var descriptionHost = root.querySelector('[data-governance-html-field="description"]');
                if (!intentionEditor) intentionEditor = window.omoSimpleHtmlField.mount(intentionHost, {value: String(after.intention || ''), simpleOnly: true});
                else intentionEditor.setValue(String(after.intention || ''));
                if (!descriptionEditor) descriptionEditor = window.omoSimpleHtmlField.mount(descriptionHost, {value: String(after.description || ''), simpleOnly: true});
                else descriptionEditor.setValue(String(after.description || ''));
            }

            function populateRuleSelect(select, selectedId) {
                if (!select) return;
                select.innerHTML = '';
                if (!rules.length) {
                    var empty = document.createElement('option');
                    empty.value = '';
                    empty.textContent = texts.emptyRules || 'Aucune regle';
                    select.appendChild(empty);
                    return;
                }
                rules.forEach(function (rule) {
                    var option = document.createElement('option');
                    option.value = String(rule.id);
                    option.textContent = String(rule.label || '');
                    option.selected = String(rule.id) === String(selectedId || '');
                    select.appendChild(option);
                });
            }

            function fillRuleEditor(action, actionType) {
                var isCreate = actionType === 'rule.create';
                var rule = isCreate ? null : (rulesById[String(action && action.targetId ? action.targetId : (ruleSelect ? ruleSelect.value : ''))] || rules[0] || null);
                var after = action && action.after ? clone(action.after) : (isCreate ? clone(defaultRuleState) : (rule ? clone(rule.state) : {}));
                if (!isCreate && ruleSelect && rule) ruleSelect.value = String(rule.id);
                if (authoritySelect) authoritySelect.value = String(after.IDauthority || 0);
                if (titleInput) titleInput.value = String(after.title || '');
                if (reviewInput) reviewInput.value = String(after.review_date || '');
                if (expirationInput) expirationInput.value = String(after.expiration_date || '');
                mountHtmlEditors(after);
            }

            function fillDeletePreview(action) {
                var targetId = action && action.targetId ? action.targetId : (deleteRuleSelect ? deleteRuleSelect.value : '');
                var rule = rulesById[String(targetId)] || rules[0] || null;
                var state = action && action.before ? action.before : (rule ? rule.state : {});
                if (deleteRuleSelect && rule) deleteRuleSelect.value = String(rule.id);
                if (deletePreview) deletePreview.innerHTML = stateSummary(state);
            }

            function openModal() {
                if (!modal) return;
                modal.hidden = false;
                document.documentElement.style.overflow = 'hidden';
            }

            function closeModal() {
                if (!modal) return;
                modal.hidden = true;
                document.documentElement.style.overflow = '';
                activeProposalIndex = -1;
                activeActionIndex = -1;
                activeActionType = '';
            }

            function openChooser(proposalIndex) {
                activeProposalIndex = proposalIndex;
                activeActionIndex = -1;
                if (chooser) chooser.hidden = false;
                if (ruleEditor) ruleEditor.hidden = true;
                if (deleteEditor) deleteEditor.hidden = true;
                openModal();
            }

            function openRuleEditor(proposalIndex, actionIndex, actionType) {
                activeProposalIndex = proposalIndex;
                activeActionIndex = typeof actionIndex === 'number' ? actionIndex : -1;
                var action = activeActionIndex >= 0 ? blueprint[proposalIndex].actions[activeActionIndex] : null;
                activeActionType = action && action.type ? action.type : (actionType || 'rule.update');
                populateRuleSelect(ruleSelect, action ? action.targetId : '');
                if (ruleSelect) ruleSelect.disabled = !!action;
                if (chooser) chooser.hidden = true;
                if (ruleEditor) ruleEditor.hidden = false;
                if (deleteEditor) deleteEditor.hidden = true;
                if (ruleSelectField) ruleSelectField.hidden = activeActionType === 'rule.create';
                if (actionApply) actionApply.textContent = action ? (texts.updateAction || 'Mettre a jour') : (texts.addAction || 'Ajouter');
                fillRuleEditor(action, activeActionType);
                openModal();
            }

            function openDeleteEditor(proposalIndex, actionIndex) {
                activeProposalIndex = proposalIndex;
                activeActionIndex = typeof actionIndex === 'number' ? actionIndex : -1;
                activeActionType = 'rule.delete';
                var action = activeActionIndex >= 0 ? blueprint[proposalIndex].actions[activeActionIndex] : null;
                populateRuleSelect(deleteRuleSelect, action ? action.targetId : '');
                if (deleteRuleSelect) deleteRuleSelect.disabled = !!action;
                if (chooser) chooser.hidden = true;
                if (ruleEditor) ruleEditor.hidden = true;
                if (deleteEditor) deleteEditor.hidden = false;
                if (deleteApply) deleteApply.textContent = action ? (texts.updateAction || 'Mettre a jour') : (texts.addAction || 'Ajouter');
                fillDeletePreview(action);
                openModal();
            }

            if (ruleSelect) ruleSelect.addEventListener('change', function () { fillRuleEditor(null, 'rule.update'); });
            if (deleteRuleSelect) deleteRuleSelect.addEventListener('change', function () { fillDeletePreview(null); });
            var chooseCreate = root.querySelector('[data-governance-choose-rule-create]');
            var chooseUpdate = root.querySelector('[data-governance-choose-rule-update]');
            var chooseDelete = root.querySelector('[data-governance-choose-rule-delete]');
            if (chooseCreate) chooseCreate.addEventListener('click', function () { openRuleEditor(activeProposalIndex, -1, 'rule.create'); });
            if (chooseUpdate) chooseUpdate.addEventListener('click', function () { openRuleEditor(activeProposalIndex, -1, 'rule.update'); });
            if (chooseDelete) chooseDelete.addEventListener('click', function () { openDeleteEditor(activeProposalIndex, -1); });
            root.querySelectorAll('[data-governance-modal-close]').forEach(function (button) { button.addEventListener('click', closeModal); });
            if (actionApply) actionApply.addEventListener('click', function () {
                var isCreate = activeActionType === 'rule.create';
                var rule = isCreate ? null : (rulesById[String(ruleSelect ? ruleSelect.value : '')] || null);
                if ((!isCreate && !rule) || activeProposalIndex < 0) return;
                var proposal = blueprint[activeProposalIndex];
                var previous = activeActionIndex >= 0 ? proposal.actions[activeActionIndex] : null;
                var authorityId = authoritySelect && parseInt(authoritySelect.value || '0', 10) > 0 ? parseInt(authoritySelect.value, 10) : null;
                var after = {
                    IDauthority: authorityId,
                    IDholon: authorityId ? null : (isCreate ? (defaultRuleState.IDholon || null) : (rule.state.IDholon || null)),
                    title: String(titleInput ? titleInput.value : '').trim(),
                    intention: intentionEditor ? intentionEditor.getValue() : '',
                    description: descriptionEditor ? descriptionEditor.getValue() : '',
                    scope: authorityId ? String((isCreate ? defaultRuleState.scope : rule.state.scope) || 'local') : 'local',
                    review_date: String(reviewInput ? reviewInput.value : ''),
                    expiration_date: String(expirationInput ? expirationInput.value : '')
                };
                if (!after.title || !String(after.description).replace(/<[^>]*>/g, '').trim() || !after.review_date || !after.expiration_date) {
                    showFeedback(texts.genericError || 'Champs obligatoires manquants.', true);
                    return;
                }
                var action = {
                    id: previous ? previous.id : 0,
                    type: isCreate ? 'rule.create' : 'rule.update',
                    targetId: isCreate ? (previous ? previous.targetId : 0) : rule.id,
                    before: isCreate ? {} : (previous ? previous.before : clone(rule.state)),
                    after: after,
                    status: previous ? previous.status : 'pending',
                    statusMessage: previous ? previous.statusMessage : ''
                };
                if (activeActionIndex >= 0) proposal.actions[activeActionIndex] = action; else proposal.actions.push(action);
                closeModal();
                render();
            });
            if (deleteApply) deleteApply.addEventListener('click', function () {
                var rule = rulesById[String(deleteRuleSelect ? deleteRuleSelect.value : '')] || null;
                if (!rule || activeProposalIndex < 0) return;
                var proposal = blueprint[activeProposalIndex];
                var previous = activeActionIndex >= 0 ? proposal.actions[activeActionIndex] : null;
                var action = {
                    id: previous ? previous.id : 0,
                    type: 'rule.delete',
                    targetId: rule.id,
                    before: previous ? previous.before : clone(rule.state),
                    after: {},
                    status: previous ? previous.status : 'pending',
                    statusMessage: previous ? previous.statusMessage : ''
                };
                if (activeActionIndex >= 0) proposal.actions[activeActionIndex] = action; else proposal.actions.push(action);
                closeModal();
                render();
            });
            if (addProposalButton) addProposalButton.addEventListener('click', function () {
                blueprint.push({id: 0, title: String(texts.proposalDefault || 'Proposition __INDEX__').replace('__INDEX__', String(blueprint.length + 1)), actions: []});
                render();
            });
            if (form) form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!form.reportValidity()) return;
                syncInput();
                if (!blueprint.length || blueprint.some(function (proposal) { return !Array.isArray(proposal.actions) || !proposal.actions.length; })) {
                    showFeedback(texts.genericError || 'Ajoutez une proposition et une modification.', true);
                    return;
                }
                if (submit) { submit.disabled = true; submit.dataset.label = submit.textContent; submit.textContent = texts.saving || 'Enregistrement...'; }
                fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)}).then(function (response) {
                    return response.json().then(function (result) { return {ok: response.ok, result: result}; });
                }).then(function (response) {
                    if (!response.ok || !response.result.status) throw new Error(response.result.message || texts.genericError);
                    if (response.result.redirectUrl && typeof window.omoDecisionOpenNestedDrawer === 'function') window.omoDecisionOpenNestedDrawer(response.result.drawerTitle || 'Prises de decision', response.result.redirectUrl, '');
                    else if (response.result.redirectUrl && typeof window.commonTopbarOpenDrawer === 'function') window.commonTopbarOpenDrawer(response.result.drawerTitle || 'Prises de decision', response.result.redirectUrl, 'fetch');
                    else if (response.result.redirectUrl) window.location.href = response.result.redirectUrl;
                }).catch(function (error) {
                    showFeedback(error && error.message ? error.message : texts.genericError, true);
                    if (submit) { submit.disabled = false; submit.textContent = submit.dataset.label || submit.textContent; }
                });
            });

            if (!blueprint.length && editable) blueprint.push({id: 0, title: String(texts.proposalDefault || 'Proposition __INDEX__').replace('__INDEX__', '1'), actions: []});
            render();
        });
    }

    window.omoGovernanceEditorInit = init;
})(window, document);
