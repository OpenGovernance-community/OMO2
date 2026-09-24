(function (window, document) {
    'use strict';
    function escapeHtml(value) { var node = document.createElement('div'); node.textContent = String(value == null ? '' : value); return node.innerHTML; }
    function clone(value) { return JSON.parse(JSON.stringify(value == null ? null : value)); }
    function strip(value) { var node = document.createElement('div'); node.innerHTML = String(value || ''); return (node.textContent || '').trim(); }

    function init(scope) {
        Array.prototype.forEach.call((scope || document).querySelectorAll('[data-governance-editor]'), function (root) {
            if (root.dataset.governanceBound) return;
            root.dataset.governanceBound = '1';
            var payload = {};
            try { payload = JSON.parse(root.querySelector('[data-governance-data]').textContent || '{}'); } catch (error) {}
            var texts = payload.texts || {}, blueprint = clone(payload.blueprint || []), editable = payload.editable !== false;
            var labels = payload.contextLabels || {}, permissions = payload.contextPermissions || {}, projectModes = payload.projectCreationModes || {};
            var form = root.querySelector('[data-governance-form]'), list = root.querySelector('[data-governance-proposals]'), input = root.querySelector('[data-governance-blueprint]'), feedback = root.querySelector('[data-governance-feedback]');
            var activeHolonCapture = null, activeObjectCapture = null;
            var intentionLink = root.querySelector('[data-governance-intention-open]');
            if (intentionLink) intentionLink.addEventListener('click', function () {
                var field = root.querySelector('[data-governance-intention-field]');
                if (!field) return;
                field.hidden = false;
                intentionLink.hidden = true;
                intentionLink.setAttribute('aria-expanded', 'true');
                field.querySelector('textarea').focus();
            });

            function actionParts(action) { var parts = String(action.type || '').split('.'); return {targetType:parts[0] || 'rule', operation:parts[1] || 'create'}; }
            function actionLabel(action) {
                var map = {'rule.create':texts.ruleCreate,'rule.update':texts.ruleUpdate,'rule.delete':texts.ruleDelete,'holon.create':texts.roleCreate,'holon.update':texts.roleUpdate,'holon.delete':texts.roleDelete,'project.create':texts.projectCreate,'project.update':texts.projectUpdate,'project.delete':texts.projectDelete,'recurring_task.create':texts.recurringTaskCreate,'recurring_task.update':texts.recurringTaskUpdate,'recurring_task.delete':texts.recurringTaskDelete,'indicator.create':texts.indicatorCreate,'indicator.update':texts.indicatorUpdate,'indicator.delete':texts.indicatorDelete};
                return map[action.type] || action.type;
            }
            function targetLabel(action) {
                var state = String(action.type).endsWith('.delete') ? action.before : action.after;
                return String((state && (state.title || state.name || state.full_name || state.label)) || 'Nouvel élément');
            }
            function statusLabel(status) {
                return {pending:texts.pending || 'En attente',applied:texts.applied || 'Appliquée',rejected:texts.rejected || 'Non acceptée',conflict:texts.conflict || 'Conflit',failed:texts.failed || 'Échec'}[status] || status || (texts.pending || 'En attente');
            }
            function changePayload(action) {
                var json = JSON.stringify({governanceAction:action, authorities:payload.authorities || [], responsibleLabels:payload.responsibleLabels || {}});
                return window.btoa(Array.from(new TextEncoder().encode(json), function (byte) { return String.fromCharCode(byte); }).join(''));
            }
            function summary(action) { return actionLabel(action) + ' : ' + targetLabel(action); }
            function sync() { input.value = JSON.stringify(blueprint); }
            function summaryInput(proposal) {
                return (proposal.actions || []).map(function (action) {
                    var changes = window.omoChoiceChangeDetails
                        ? window.omoChoiceChangeDetails.governanceChanges(action, payload.authorities || [], payload.responsibleLabels || {})
                        : [];
                    return {action:summary(action), fields:changes.map(function (change) {
                        return {name:change.label, before:change.before, after:change.after};
                    })};
                });
            }
            function generateSummary(proposal, card, button) {
                var field = card.querySelector('[data-description]'), message = card.querySelector('[data-summary-feedback]');
                function notifySummary(text, type) {
                    if (typeof window.commonNotify === 'function') {
                        message.hidden = true;
                        window.commonNotify(text, type);
                    } else {
                        message.textContent = text;
                        message.hidden = false;
                    }
                }
                if (!proposal.actions || !proposal.actions.length) {
                    notifySummary(texts.summaryEmpty || 'Ajoutez d’abord une modification à cette proposition.', 'warning');
                    return;
                }
                var request = new FormData(form);
                request.set('modifications', JSON.stringify(summaryInput(proposal)));
                button.disabled = true;
                button.textContent = texts.summaryLoading || 'Rédaction du résumé…';
                field.readOnly = true;
                message.hidden = true;
                fetch('/omo/api/decision/governance/summarize.php', {method:'POST',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},body:request})
                    .then(function (response) { return response.json().then(function (data) { if (!response.ok || !data.status) throw new Error(data.message || texts.summaryFailed); return data; }); })
                    .then(function (data) {
                        if (!card.isConnected) return;
                        proposal.description = String(data.text || '').trim();
                        field.value = proposal.description;
                        sync();
                        notifySummary(texts.summaryReady || 'Résumé ajouté à la description. Vous pouvez le modifier.', 'success');
                    })
                    .catch(function (error) {
                        if (!card.isConnected) return;
                        notifySummary(error.message || texts.summaryFailed || 'Impossible de générer le résumé.', 'error');
                    })
                    .finally(function () {
                        if (!card.isConnected) return;
                        button.disabled = false;
                        button.textContent = texts.summaryGenerate || 'Rédiger un résumé avec l’IA';
                        field.readOnly = false;
                    });
            }
            function modal(title, content) { if (typeof window.commonTopbarOpenModal === 'function') window.commonTopbarOpenModal(title, content, 'html'); }
            function modalBody() { return document.getElementById('commonTopbarModalBody'); }
            function closeModal() { if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal(); }
            function optionHtml(items, selected) { return (items || []).map(function (item) { return '<option value="' + Number(item.id || 0) + '"' + (Number(item.id) === Number(selected) ? ' selected' : '') + '>' + escapeHtml(item.label || '') + (item.typeLabel ? ' — ' + escapeHtml(item.typeLabel) : '') + '</option>'; }).join(''); }

            function render() {
                list.innerHTML = '';
                blueprint.forEach(function (proposal, pi) {
                    var card = document.createElement('article');
                    card.className = 'omo-governance-proposal generic-soft-panel generic-soft-panel--tinted generic-form-stack generic-form-stack--compact';
                    card.innerHTML = '<label class="generic-form-field"><span class="generic-form-label">' + escapeHtml(texts.proposalTitle || 'Titre de la proposition') + '</span><input class="generic-form-control generic-form-control--compact" data-title maxlength="190" value="' + escapeHtml(proposal.title || '') + '"' + (editable ? '' : ' readonly') + '></label>'
                        + '<div class="generic-form-stack generic-form-stack--compact"><div class="omo-governance-actions" data-actions' + ((proposal.actions || []).length ? '' : ' hidden') + '></div>'
                        + (editable ? '<div><button type="button" class="generic-action-button generic-action-button--choice generic-action-button--compact" data-add-action><span aria-hidden="true">+</span> ' + escapeHtml(texts.actionAdd || 'Ajouter une modification') + '</button></div>' : '') + '</div>'
                        + '<div class="generic-form-field"><div class="omo-governance-proposal__description-head"><label class="generic-form-label" for="omo-governance-description-' + pi + '">' + escapeHtml(texts.proposalDescription || 'Description de la proposition') + '</label>'
                        + (editable && payload.aiEnabled ? '<button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--compact" data-generate-summary' + ((proposal.actions || []).length ? '' : ' disabled') + '>' + escapeHtml(texts.summaryGenerate || 'Rédiger un résumé avec l’IA') + '</button>' : '')
                        + '</div><textarea id="omo-governance-description-' + pi + '" class="generic-form-control generic-form-control--compact" data-description rows="3" maxlength="10000"' + (editable ? '' : ' readonly') + '>' + escapeHtml(proposal.description || '') + '</textarea><p class="generic-feedback" data-summary-feedback hidden aria-live="polite"></p></div>'
                        + (editable ? '<div class="generic-action-row"><button type="button" class="generic-action-button generic-action-button--danger generic-action-button--compact" data-remove-proposal>' + escapeHtml(texts.proposalRemove || 'Retirer') + '</button></div>' : '');
                    if (editable) {
                        card.querySelector('[data-title]').addEventListener('input', function (event) { proposal.title = event.target.value; sync(); });
                        card.querySelector('[data-description]').addEventListener('input', function (event) { proposal.description = event.target.value; sync(); });
                        card.querySelector('[data-remove-proposal]').addEventListener('click', function () { blueprint.splice(pi, 1); render(); });
                        card.querySelector('[data-add-action]').addEventListener('click', function () { openChooser(pi, -1); });
                        var summaryButton = card.querySelector('[data-generate-summary]');
                        if (summaryButton) summaryButton.addEventListener('click', function () { generateSummary(proposal, card, summaryButton); });
                    }
                    (proposal.actions || []).forEach(function (action, ai) {
                        var item = document.createElement('article'), detailId = 'omo-governance-action-' + pi + '-' + ai, canChange = editable && (action.status || 'pending') === 'pending';
                        item.className = 'omo-governance-action generic-section generic-section--flush';
                        item.innerHTML = '<details class="generic-accordion omo-governance-action__accordion" data-omo-change-details-payload="' + escapeHtml(changePayload(action)) + '"><summary class="omo-governance-action__row"><strong class="omo-governance-action__summary">' + escapeHtml(summary(action)) + '</strong><span class="omo-governance-action__status omo-governance-action__status--' + escapeHtml(action.status || 'pending') + '">' + escapeHtml(statusLabel(action.status || 'pending')) + '</span></summary><div id="' + detailId + '" class="generic-accordion__content omo-governance-action__detail"><div data-omo-change-details-container></div></div></details>' + (canChange ? '<div class="omo-governance-action__menu generic-menu"><button type="button" class="generic-menu-toggle" aria-haspopup="menu" aria-expanded="false" aria-label="' + escapeHtml(texts.actionMore || 'Actions de la modification') + '">...</button><div class="generic-menu-panel generic-menu-panel--anchored" role="menu" hidden><button type="button" class="generic-menu-item" data-edit>Éditer</button><button type="button" class="generic-menu-item generic-menu-item--danger" data-remove>Supprimer</button></div></div>' : '');
                        var detail = item.querySelector('.omo-governance-action__detail');
                        if (action.statusMessage) { var message = document.createElement('p'); message.className = 'generic-feedback'; message.textContent = action.statusMessage; detail.appendChild(message); }
                        if (canChange) {
                            var menu = item.querySelector('.omo-governance-action__menu'), menuButton = menu.querySelector('.generic-menu-toggle'), menuPanel = menu.querySelector('.generic-menu-panel');
                            menuButton.addEventListener('click', function () { var open = !menuPanel.hidden; menuPanel.hidden = open; menuButton.setAttribute('aria-expanded', open ? 'false' : 'true'); });
                            item.querySelector('[data-edit]').addEventListener('click', function () { var parts = actionParts(action); openEditor(pi, ai, parts.targetType, parts.operation, Number(action.holonId || payload.contextHolonId), null); });
                            item.querySelector('[data-remove]').addEventListener('click', function () { proposal.actions.splice(ai, 1); render(); });
                        }
                        card.querySelector('[data-actions]').appendChild(item);
                    });
                    list.appendChild(card);
                });
                sync();
                if (window.omoChoiceChangeDetails) window.omoChoiceChangeDetails.hydrate(list);
            }

            function allowedContextIds(type, operation) { var byType = permissions[type] || {}, ids = byType[operation]; return Array.isArray(ids) ? ids.map(Number) : []; }
            function chooseContext(type, operation, preferred) { var ids = allowedContextIds(type, operation); return ids.indexOf(Number(preferred)) !== -1 ? Number(preferred) : Number(ids[0] || 0); }
            function contextUrl(type, operation, contextId) {
                return '/omo/api/decision/governance/proposal_context.php?oid=' + Number(payload.organizationId || 0) + '&cid=' + Number(payload.contextHolonId || 0) + '&id=' + Number(payload.decisionId || 0) + '&target_type=' + encodeURIComponent(type) + '&operation=' + encodeURIComponent(operation) + '&context_holon_id=' + Number(contextId || 0);
            }
            function openChooser(pi, ai) {
                var previous = ai >= 0 ? blueprint[pi].actions[ai] : null, parts = previous ? actionParts(previous) : {targetType:'rule',operation:'create'};
                var type = parts.targetType, operation = parts.operation, contextId = chooseContext(type, operation, previous ? previous.holonId : payload.contextHolonId), objects = [], picker = null, candidateId = contextId;
                modal(texts.actionAdd || 'Ajouter une modification', '<section class="generic-section generic-section--stack generic-section--roomy omo-deferred-workflow"><div class="generic-form-field"><span class="generic-form-label">' + escapeHtml(texts.objectType || 'Objet') + '</span><div class="omo-deferred-workflow__types"><button type="button" class="generic-soft-panel omo-deferred-workflow__type" data-type="rule"><strong>Règle</strong><span>Règles et politiques de l’espace</span></button><button type="button" class="generic-soft-panel omo-deferred-workflow__type" data-type="holon"><strong>Rôle ou cercle</strong><span>Éléments de la structure</span></button><button type="button" class="generic-soft-panel omo-deferred-workflow__type" data-type="project"><strong>Projet</strong><span>Projets de l’espace</span></button><button type="button" class="generic-soft-panel omo-deferred-workflow__type" data-type="recurring_task"><strong>Tâche récurrente</strong><span>Tâches planifiées de l’espace</span></button><button type="button" class="generic-soft-panel omo-deferred-workflow__type" data-type="indicator"><strong>Indicateur</strong><span>Indicateurs et mesures de l’espace</span></button></div></div><div class="generic-form-grid"><label class="generic-form-field"><span class="generic-form-label">Action</span><select class="generic-form-control" data-operation><option value="create">Création</option><option value="update">Modification</option><option value="delete">Suppression</option></select></label><label class="generic-form-field"><span class="generic-form-label">' + escapeHtml(texts.context || 'Contexte') + '</span><span class="omo-deferred-workflow__context-control"><input class="generic-form-control" readonly data-context-label><button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" data-context-open title="' + escapeHtml(texts.openContext || '') + '"><img src="/omo/images/tools/connection.png" alt=""></button></span></label></div><div class="generic-form-grid" data-object-row hidden><label class="generic-form-field"><span class="generic-form-label">' + escapeHtml(texts.object || 'Élément concerné') + '</span><select class="generic-form-control" data-object></select></label><div class="generic-form-field omo-deferred-workflow__launch-field"><span class="generic-form-label">&nbsp;</span><button type="button" class="generic-action-button generic-action-button--main" data-open-editor></button></div></div><div class="generic-action-row" data-create-row><button type="button" class="generic-action-button generic-action-button--main" data-open-editor></button></div><p class="generic-feedback" data-picker-feedback hidden></p><div class="omo-deferred-workflow__picker-layer" data-context-layer hidden><div class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack omo-deferred-workflow__picker-panel"><div data-context-map></div><div class="omo-deferred-workflow__picker-selection"><strong data-candidate-label></strong><span data-candidate-status></span></div><div class="generic-action-row"><button type="button" class="generic-action-button generic-action-button--main" data-context-choose>Choisir ce contexte</button><button type="button" class="generic-action-button generic-action-button--secondary" data-context-close>Annuler</button></div></div></div></section>');
                var body = modalBody(), operationSelect = body.querySelector('[data-operation]'), objectSelect = body.querySelector('[data-object]'), objectRow = body.querySelector('[data-object-row]'), createRow = body.querySelector('[data-create-row]'), contextLabel = body.querySelector('[data-context-label]'), pickerFeedback = body.querySelector('[data-picker-feedback]');
                operationSelect.value = operation;
                function showFeedback(message) { pickerFeedback.hidden = !message; pickerFeedback.textContent = message || ''; }
                function buttonText() { var key = type + '.' + operation, map = {'rule.create':texts.ruleCreate,'rule.update':texts.ruleUpdate,'rule.delete':texts.ruleDelete,'holon.create':texts.roleCreate,'holon.update':texts.roleUpdate,'holon.delete':texts.roleDelete,'project.create':String(projectModes[String(contextId)] || '') === 'propose' ? texts.projectPropose : texts.projectCreate,'project.update':texts.projectUpdate,'project.delete':texts.projectDelete,'recurring_task.create':texts.recurringTaskCreate,'recurring_task.update':texts.recurringTaskUpdate,'recurring_task.delete':texts.recurringTaskDelete,'indicator.create':texts.indicatorCreate,'indicator.update':texts.indicatorUpdate,'indicator.delete':texts.indicatorDelete}; return map[key] || texts.openEditor || 'Ouvrir l’éditeur'; }
                function update() { body.querySelectorAll('[data-type]').forEach(function (button) { var selected = button.dataset.type === type; button.classList.toggle('is-selected', selected); button.setAttribute('aria-pressed', selected ? 'true' : 'false'); }); contextLabel.value = String(labels[String(contextId)] || ''); objectRow.hidden = operation === 'create'; createRow.hidden = operation !== 'create'; body.querySelectorAll('[data-open-editor]').forEach(function (button) { button.textContent = buttonText(); button.disabled = !contextId || (operation !== 'create' && !Number(objectSelect.value || 0)); }); }
                function loadObjects(selectedId) { contextId = chooseContext(type, operation, contextId); objects = []; objectSelect.innerHTML = '<option>' + escapeHtml(texts.loading || 'Chargement…') + '</option>'; update(); if (!contextId) { showFeedback('Le collectif ne dispose pas du droit nécessaire pour cette action.'); return; } if (operation === 'create') { objectSelect.innerHTML = ''; update(); return; } fetch(contextUrl(type, operation, contextId), {credentials:'same-origin'}).then(function (response) { return response.json().then(function (data) { return {ok:response.ok,data:data}; }); }).then(function (result) { if (!result.ok || !result.data.status) throw new Error(result.data.message || 'Chargement impossible.'); objects = result.data.objects || []; if (result.data.context) { labels[String(contextId)] = result.data.context.label || ''; contextLabel.value = labels[String(contextId)]; } objectSelect.innerHTML = optionHtml(objects, selectedId); if (!objects.length) objectSelect.innerHTML = '<option value="">' + escapeHtml(texts.emptyObjects || 'Aucun élément disponible dans ce contexte.') + '</option>'; update(); }).catch(function (error) { objectSelect.innerHTML = ''; showFeedback(error.message); update(); }); }
                function selectedObject() { var id = Number(objectSelect.value || 0); return objects.find(function (item) { return Number(item.id) === id; }) || null; }
                body.querySelectorAll('[data-type]').forEach(function (button) { button.disabled = !!previous && button.dataset.type !== type; button.addEventListener('click', function () { if (button.disabled) return; type = button.dataset.type; operation = operationSelect.value; contextId = chooseContext(type, operation, payload.contextHolonId); loadObjects(0); }); });
                operationSelect.disabled = !!previous; operationSelect.addEventListener('change', function () { operation = operationSelect.value; contextId = chooseContext(type, operation, contextId); loadObjects(0); }); objectSelect.addEventListener('change', update);
                body.querySelectorAll('[data-open-editor]').forEach(function (button) { button.addEventListener('click', function () { openEditor(pi, ai, type, operation, contextId, selectedObject()); }); });
                body.querySelector('[data-context-open]').disabled = !!previous; body.querySelector('[data-context-open]').addEventListener('click', function () { var layer = body.querySelector('[data-context-layer]'), host = body.querySelector('[data-context-map]'), allowed = allowedContextIds(type, operation); if (!window.omoMountHolonScopePicker) return; candidateId = contextId; layer.hidden = false; picker = window.omoMountHolonScopePicker({host:host,organizationId:Number(payload.organizationId || 0),initialHolonId:contextId,selectableHolonIds:allowed,showModes:false,initialScope:'local',labelMode:'context',suppressInitialChange:true,onChange:setCandidate,onReady:setCandidate}); setCandidate(contextId); });
                function setCandidate(id) { candidateId = Number(id || 0); var allowed = allowedContextIds(type, operation).indexOf(candidateId) !== -1; body.querySelector('[data-candidate-label]').textContent = String(labels[String(candidateId)] || (picker && picker.getSelectedHolonLabel ? picker.getSelectedHolonLabel() : '')); body.querySelector('[data-candidate-status]').textContent = allowed ? 'Contexte disponible.' : 'Ce contexte n’est pas disponible.'; body.querySelector('[data-context-choose]').disabled = !allowed; }
                function closePicker() { body.querySelector('[data-context-layer]').hidden = true; if (picker && picker.destroy) picker.destroy(); picker = null; body.querySelector('[data-context-map]').innerHTML = ''; }
                body.querySelector('[data-context-close]').addEventListener('click', closePicker); body.querySelector('[data-context-choose]').addEventListener('click', function () { contextId = candidateId; closePicker(); loadObjects(0); });
                update(); loadObjects(previous ? previous.targetId : 0);
            }

            function saveAction(pi, ai, action) { if (ai >= 0) blueprint[pi].actions[ai] = action; else blueprint[pi].actions.push(action); closeModal(); render(); }
            function baseAction(pi, ai, type, operation, contextId, object) {
                var previous = ai >= 0 ? blueprint[pi].actions[ai] : null;
                return {id:previous ? previous.id : 0,storage:previous ? previous.storage : 'deferred',type:type + '.' + operation,targetId:operation === 'create' ? 0 : Number(previous ? previous.targetId : (object || {}).id || 0),holonId:Number(contextId || 0),before:operation === 'create' ? {} : clone(previous ? previous.before : ((object || {}).state || {})),after:{},status:previous ? previous.status : 'pending',statusMessage:previous ? previous.statusMessage : ''};
            }
            function openEditor(pi, ai, type, operation, contextId, object) {
                var previous = ai >= 0 ? blueprint[pi].actions[ai] : null;
                if (!object && previous && operation !== 'create') object = {id:previous.targetId,state:previous.before || {}};
                if (type === 'holon' && operation !== 'delete') { openHolonEditor(pi, ai, operation, contextId, object); return; }
                var action = baseAction(pi, ai, type, operation, contextId, object), state = clone(previous && operation !== 'delete' ? previous.after : (operation === 'create' ? {} : ((object || {}).state || {})));
                if (operation === 'delete') {
                    modal(actionLabel(action), '<section class="generic-section generic-section--stack generic-section--roomy"><p>La suppression sera appliquée uniquement si la proposition qui la contient est acceptée et si l’objet n’a pas changé.</p><strong>' + escapeHtml(targetLabel(action)) + '</strong><div class="generic-action-row"><button type="button" class="generic-action-button generic-action-button--danger" data-confirm>Ajouter la suppression</button><button type="button" class="generic-action-button generic-action-button--secondary" data-cancel>Annuler</button></div></section>');
                    modalBody().querySelector('[data-confirm]').addEventListener('click', function () { saveAction(pi, ai, action); }); modalBody().querySelector('[data-cancel]').addEventListener('click', closeModal); return;
                }
                if (type === 'rule') openRuleEditor(pi, ai, action, state); else if (type === 'project') openProjectEditor(pi, ai, action, state); else openObjectEditor(pi, ai, action, state, type);
            }
            function openFieldsEditor(pi, ai, action, state, targetType) {
                var template = root.querySelector('[data-governance-fields="' + targetType + '"]');
                if (!template) return;
                modal(actionLabel(action), template.innerHTML);
                var editor = modalBody().querySelector('[data-editor]');
                Object.keys(state).forEach(function (name) {
                    var field = editor.elements.namedItem(name);
                    if (field && field.type === 'checkbox') field.checked = Number(state[name] || 0) > 0;
                    else if (field) field.value = state[name] == null ? '' : String(state[name]);
                });
                editor.querySelector('[data-cancel]').addEventListener('click', closeModal);
                editor.addEventListener('submit', function (event) {
                    event.preventDefault();
                    if (!editor.reportValidity()) return;
                    var after = Object.assign({}, state);
                    editor.querySelectorAll('input[type="checkbox"][name]').forEach(function (field) { after[field.name] = field.checked ? String(field.value || '1') : '0'; });
                    new FormData(editor).forEach(function (value, name) { after[name] = String(value); });
                    after.IDholon = Number(action.holonId);
                    if (targetType === 'indicator') after.name = String(after.name || '').trim();
                    else after.title = String(after.title || '').trim();
                    if (targetType === 'rule') {
                        after.IDauthority = state.IDauthority || null;
                        after.scope = state.scope || 'local';
                        if (!strip(after.description)) return;
                    }
                    action.after = after;
                    saveAction(pi, ai, action);
                });
            }
            function openRuleEditor(pi, ai, action, state) {
                openFieldsEditor(pi, ai, action, Object.assign(clone(payload.defaultRuleState || {}), state || {}), 'rule');
            }
            function openProjectEditor(pi, ai, action, state) {
                openFieldsEditor(pi, ai, action, Object.assign({title:'',description:'',status:'in_progress',project_size:'M'}, state || {}), 'project');
            }
            function openObjectEditor(pi, ai, action, state, targetType) {
                var defaults = targetType === 'recurring_task'
                    ? {title:'',description:'',frequency:'weekly',schedule:'1',display_lead_value:0,display_lead_unit:'day',execution_duration_value:1,execution_duration_unit:'day'}
                    : {name:'',description:'',source_type:'manual',reference_type:'none',reference_scale:'cumulative',show_cumulative:0};
                var request = new FormData(), contextId = Number(action.holonId || 0), objectId = Number(action.targetId || 0), operation = actionParts(action).operation;
                request.set('oid', String(payload.organizationId || 0));
                request.set('cid', String(payload.contextHolonId || 0));
                request.set('id', String(payload.decisionId || 0));
                request.set('target_type', targetType);
                request.set('operation', operation);
                request.set('context_holon_id', String(contextId));
                request.set('object_id', String(objectId));
                request.set('state', JSON.stringify(Object.assign(defaults, state || {})));
                fetch('/omo/api/decision/governance/object_editor.php', {method:'POST', credentials:'same-origin', body:request})
                    .then(function (response) { return response.json().then(function (data) { if (!response.ok || !data.status) throw new Error(data.message || 'Chargement impossible.'); return data; }); })
                    .then(function (data) {
                        activeObjectCapture = {proposalIndex:pi, actionIndex:ai, action:action, targetType:targetType};
                        var url = '/omo/api/decision/governance/object_editor.php?oid=' + Number(payload.organizationId || 0)
                            + '&cid=' + Number(payload.contextHolonId || 0) + '&id=' + Number(payload.decisionId || 0)
                            + '&target_type=' + encodeURIComponent(targetType) + '&operation=' + encodeURIComponent(operation)
                            + '&context_holon_id=' + contextId + '&object_id=' + objectId + '&draft_key=' + encodeURIComponent(data.draftKey);
                        window.commonTopbarOpenModal(actionLabel(action), url, 'fetch');
                    }).catch(function (error) { if (window.commonNotify) window.commonNotify(error.message, 'error'); });
            }
            window.addEventListener('omo-deferred-object-capture', function (event) {
                if (!activeObjectCapture || !event.detail || event.detail.targetType !== activeObjectCapture.targetType) return;
                var capture = activeObjectCapture;
                activeObjectCapture = null;
                capture.action.after = event.detail.state || {};
                saveAction(capture.proposalIndex, capture.actionIndex, capture.action);
            });
            function openHolonEditor(pi, ai, operation, contextId, object) {
                var action = baseAction(pi, ai, 'holon', operation, contextId, object), previous = ai >= 0 ? blueprint[pi].actions[ai] : null, targetId = operation === 'create' ? 0 : Number(action.targetId || 0);
                activeHolonCapture = {proposalIndex:pi,actionIndex:ai,action:action};
                window.omoHolonGovernanceInitialPayload = previous && previous.after && previous.after.editor_payload ? clone(previous.after.editor_payload) : null;
                var url = '/omo/api/holons/create.php?cid=' + Number(contextId) + '&governance_capture=1&v=20260923-deferred-proposals'; if (targetId > 0) url += '&hid=' + targetId;
                window.commonTopbarOpenModal(actionLabel(action), url, 'fetch');
            }
            window.addEventListener('omo-holon-governance-capture', function (event) {
                if (!activeHolonCapture || !event.detail || !event.detail.payload) return;
                var capture = activeHolonCapture, editorPayload = event.detail.payload, action = capture.action;
                action.after = {editor_payload:editorPayload,name:String(editorPayload.name || '').trim(),full_name:String(editorPayload.fullName || '').trim(),color:String(editorPayload.color || '').trim(),template_id:Number(editorPayload.templateId || 0)};
                activeHolonCapture = null; window.omoHolonGovernanceInitialPayload = null; saveAction(capture.proposalIndex, capture.actionIndex, action);
            });

            var addProposalButton = root.querySelector('[data-governance-proposal-add]');
            if (addProposalButton) addProposalButton.addEventListener('click', function () { blueprint.push({id:0,title:(texts.proposalDefault || 'Proposition __INDEX__').replace('__INDEX__', blueprint.length + 1),description:'',actions:[]}); render(); });
            form.addEventListener('submit', function (event) {
                event.preventDefault(); if (!form.reportValidity()) return; sync();
                if (!blueprint.length || blueprint.some(function (proposal) { return !(proposal.actions || []).length; })) { feedback.hidden = false; feedback.textContent = texts.genericError || 'Ajoutez une proposition et une modification.'; return; }
                var formData = new FormData(form), submitButton = event.submitter || form.querySelector('[type="submit"]'), usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';
                if (usesSharedPendingState && !window.omoBeginPendingAction(form)) return; if (!usesSharedPendingState && submitButton) submitButton.disabled = true;
                fetch(form.action, {method:'POST',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},body:formData}).then(function (response) { return response.json().then(function (data) { return {ok:response.ok,data:data}; }); }).then(function (result) { if (!result.ok || !result.data.status) throw new Error(result.data.message); if (typeof window.omoDecisionOpenNestedDrawer === 'function') window.omoDecisionOpenNestedDrawer(result.data.drawerTitle || 'Prises de décision', result.data.redirectUrl, ''); else window.location.href = result.data.redirectUrl; }).catch(function (error) { feedback.hidden = false; feedback.textContent = error.message || texts.genericError; }).finally(function () { if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') window.omoEndPendingAction(form); else if (submitButton) submitButton.disabled = false; });
            });
            if (!blueprint.length && editable) blueprint.push({id:0,title:(texts.proposalDefault || 'Proposition __INDEX__').replace('__INDEX__', '1'),description:'',actions:[]});
            render();
        });
    }
    window.omoGovernanceEditorInit = init;
    // A fragment can arrive before its external scripts finish loading.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(document); }, {once:true});
    else init(document);
})(window, document);
