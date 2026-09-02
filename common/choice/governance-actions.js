(function (window, document) {
    'use strict';
    function escapeHtml(value) { var node = document.createElement('div'); node.textContent = String(value == null ? '' : value); return node.innerHTML; }
    function clone(value) { return JSON.parse(JSON.stringify(value == null ? null : value)); }

    function init(scope) {
        Array.prototype.forEach.call((scope || document).querySelectorAll('[data-governance-editor]'), function (root) {
            if (root.dataset.governanceBound) return;
            root.dataset.governanceBound = '1';
            var payload = {};
            try { payload = JSON.parse(root.querySelector('[data-governance-data]').textContent || '{}'); } catch (error) {}
            var texts = payload.texts || {}, blueprint = clone(payload.blueprint || []), rules = payload.rules || [], roles = payload.roles || [], templates = payload.roleTemplates || [], editable = payload.editable !== false;
            var ruleById = {}, roleById = {}, form = root.querySelector('[data-governance-form]'), list = root.querySelector('[data-governance-proposals]'), input = root.querySelector('[data-governance-blueprint]'), feedback = root.querySelector('[data-governance-feedback]');
            rules.forEach(function (item) { ruleById[String(item.id)] = item; }); roles.forEach(function (item) { roleById[String(item.id)] = item; });
            function label(action) { var labels = {'rule.create': texts.ruleCreate, 'rule.update': texts.ruleUpdate, 'rule.delete': texts.ruleDelete, 'holon.create': texts.roleCreate, 'holon.update': texts.roleUpdate, 'holon.delete': texts.roleDelete}; return labels[action.type] || action.type; }
            function target(action) { var item = action.type.indexOf('rule.') === 0 ? ruleById[String(action.targetId)] : roleById[String(action.targetId)]; var state = action.type.endsWith('.delete') ? action.before : action.after; return (state && (state.title || state.name)) || (item && item.label) || 'Nouvel element'; }
            function actionChanges(action) {
                return window.omoChoiceChangeDetails && typeof window.omoChoiceChangeDetails.governanceChanges === 'function'
                    ? window.omoChoiceChangeDetails.governanceChanges(action, payload.authorities || [])
                    : [];
            }
            function actionSummaryText(action, changes) {
                var count = changes.length, objectLabel = action.type.indexOf('rule.') === 0 ? 'la regle' : 'le role';
                if (action.type.endsWith('.create')) return 'Cette proposition cree ' + objectLabel + ' « ' + target(action) + ' »' + (count ? ' avec ' + count + ' element(s) configure(s).' : '.');
                if (action.type.endsWith('.delete')) return 'Cette proposition supprime ' + objectLabel + ' « ' + target(action) + ' ».';
                var counts = {changed:0, added:0, removed:0}; changes.forEach(function (change) { if (Object.prototype.hasOwnProperty.call(counts, change.status)) counts[change.status]++; });
                var parts = [];
                if (counts.changed) parts.push(counts.changed + ' modification' + (counts.changed > 1 ? 's' : ''));
                if (counts.added) parts.push(counts.added + ' ajout' + (counts.added > 1 ? 's' : ''));
                if (counts.removed) parts.push(counts.removed + ' suppression' + (counts.removed > 1 ? 's' : ''));
                return 'Cette proposition comporte ' + (parts.length ? parts.join(', ').replace(/, ([^,]*)$/, ' et $1') : 'aucun changement') + ' pour ' + objectLabel + ' « ' + target(action) + ' ».';
            }
            function sync() { input.value = JSON.stringify(blueprint); }
            function render() {
                list.innerHTML = '';
                blueprint.forEach(function (proposal, pi) {
                    var card = document.createElement('article'); card.className = 'omo-governance-proposal generic-section generic-section--stack';
                    card.innerHTML = '<div class="omo-governance-proposal__head"><div class="omo-governance-proposal__copy"><label class="generic-form-field"><span class="generic-form-label">' + escapeHtml(texts.proposalTitle || 'Titre de la proposition') + '</span><input class="generic-form-control" data-title maxlength="190" value="' + escapeHtml(proposal.title || '') + '"' + (editable ? '' : ' readonly') + '></label><label class="generic-form-field"><span class="generic-form-label">' + escapeHtml(texts.proposalDescription || 'Description de la proposition') + '</span><textarea class="generic-form-control" data-description rows="3" maxlength="10000"' + (editable ? '' : ' readonly') + '>' + escapeHtml(proposal.description || '') + '</textarea></label></div>' + (editable ? '<button type="button" class="generic-action-button generic-action-button--danger" data-remove-proposal>' + escapeHtml(texts.proposalRemove || 'Retirer') + '</button>' : '') + '</div><div class="omo-governance-actions" data-actions></div>' + (editable ? '<button type="button" class="generic-action-button generic-action-button--secondary" data-add-action>' + escapeHtml(texts.actionAdd || 'Ajouter une modification') + '</button>' : '');
                    if (editable) card.querySelector('[data-title]').addEventListener('input', function (e) { proposal.title = e.target.value; sync(); });
                    if (editable) card.querySelector('[data-description]').addEventListener('input', function (e) { proposal.description = e.target.value; sync(); });
                    if (editable) card.querySelector('[data-remove-proposal]').addEventListener('click', function () { blueprint.splice(pi, 1); render(); });
                    if (editable) card.querySelector('[data-add-action]').addEventListener('click', function () { openChooser(pi); });
                    proposal.actions.forEach(function (action, ai) { var item = document.createElement('div'), changes = actionChanges(action); item.className = 'omo-governance-action generic-soft-panel generic-soft-panel--stack'; item.innerHTML = '<div class="omo-governance-action__head"><strong>' + escapeHtml(label(action)) + ' : ' + escapeHtml(target(action)) + '</strong></div><p class="omo-governance-action__summary">' + escapeHtml(actionSummaryText(action, changes)) + '</p><div data-change-details></div>' + (editable && action.status === 'pending' ? '<div class="omo-governance-action__buttons"><button type="button" class="generic-action-button generic-action-button--secondary" data-edit>Modifier</button><button type="button" class="generic-action-button generic-action-button--danger" data-remove>Retirer</button></div>' : ''); if (changes.length && window.omoChoiceChangeDetails) item.querySelector('[data-change-details]').appendChild(window.omoChoiceChangeDetails.create(changes, {label:'Détail'})); if (editable && action.status === 'pending') { item.querySelector('[data-edit]').addEventListener('click', function () { openEditor(pi, ai, action.type); }); item.querySelector('[data-remove]').addEventListener('click', function () { proposal.actions.splice(ai, 1); render(); }); } card.querySelector('[data-actions]').appendChild(item); });
                    list.appendChild(card);
                }); sync();
            }
            function modal(title, content) { if (typeof window.commonTopbarOpenModal !== 'function') return; window.commonTopbarOpenModal(title, content, 'html'); }
            function modalBody() { return document.getElementById('commonTopbarModalBody'); }
            function options(items, selected) { return items.map(function (item) { return '<option value="' + escapeHtml(item.id) + '"' + (String(item.id) === String(selected) ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>'; }).join(''); }
            function openChooser(pi) {
                modal(texts.actionAdd || 'Ajouter une modification', '<div class="generic-section generic-section--stack"><div class="generic-action-row"><button class="generic-action-button generic-action-button--main" data-kind="rule.create">' + escapeHtml(texts.ruleCreate || 'Creer une regle') + '</button><button class="generic-action-button generic-action-button--secondary" data-kind="rule.update"' + (!rules.length ? ' disabled' : '') + '>' + escapeHtml(texts.ruleUpdate || 'Modifier une regle') + '</button><button class="generic-action-button generic-action-button--danger" data-kind="rule.delete"' + (!rules.length ? ' disabled' : '') + '>' + escapeHtml(texts.ruleDelete || 'Supprimer une regle') + '</button></div><div class="generic-action-row"><button class="generic-action-button generic-action-button--main" data-kind="holon.create"' + (!templates.length ? ' disabled' : '') + '>' + escapeHtml(texts.roleCreate || 'Creer un role') + '</button><button class="generic-action-button generic-action-button--secondary" data-kind="holon.update"' + (!roles.length ? ' disabled' : '') + '>' + escapeHtml(texts.roleUpdate || 'Modifier un role') + '</button><button class="generic-action-button generic-action-button--danger" data-kind="holon.delete"' + (!roles.length ? ' disabled' : '') + '>' + escapeHtml(texts.roleDelete || 'Supprimer un role') + '</button></div></div>');
                modalBody().querySelectorAll('[data-kind]').forEach(function (button) { button.addEventListener('click', function () { openEditor(pi, -1, button.dataset.kind); }); });
            }
            function openEditor(pi, ai, type) {
                if (type.indexOf('holon.') === 0 && !type.endsWith('.delete')) {
                    openRoleDefinitionEditor(pi, ai, type);
                    return;
                }
                var previous = ai >= 0 ? blueprint[pi].actions[ai] : null, isRule = type.indexOf('rule.') === 0, isCreate = type.endsWith('.create'), isDelete = type.endsWith('.delete'), items = isRule ? rules : roles, index = isRule ? ruleById : roleById, selected = previous ? previous.targetId : (items[0] || {}).id, entity = isCreate ? null : index[String(selected)], state = previous ? (isDelete ? previous.before : previous.after) : (entity ? clone(entity.state) : (isRule ? clone(payload.defaultRuleState || {}) : {name:'', full_name:'', color:'', template_id:(templates[0] || {}).id || 0}));
                var fields = '<label class="generic-form-field"' + (isCreate ? ' hidden' : '') + '><span class="generic-form-label">' + (isRule ? 'Regle' : 'Role') + '</span><select class="generic-form-control" data-target>' + options(items, selected) + '</select></label>';
                if (!isRule && isCreate) fields += '<label class="generic-form-field"><span class="generic-form-label">Modele de role</span><select class="generic-form-control" data-template>' + options(templates, state.template_id) + '</select></label>';
                if (isDelete) fields += '<div class="generic-soft-panel">Cette suppression sera appliquee uniquement si la proposition est acceptee et si le contenu n a pas change.</div>';
                else if (isRule) fields += '<label class="generic-form-field"><span class="generic-form-label">Domaine d autorite</span><select class="generic-form-control" data-authority><option value="0">Regle locale au holon</option>' + options(payload.authorities || [], state.IDauthority || 0) + '</select></label><label class="generic-form-field"><span class="generic-form-label">Titre</span><input class="generic-form-control" data-title value="' + escapeHtml(state.title || '') + '"></label><label class="generic-form-field"><span class="generic-form-label">Intention</span><textarea class="generic-form-control" data-intention rows="3">' + escapeHtml(state.intention || '') + '</textarea></label><label class="generic-form-field"><span class="generic-form-label">Regle</span><textarea class="generic-form-control" data-description rows="5">' + escapeHtml(state.description || '') + '</textarea></label><div class="generic-form-grid"><label class="generic-form-field"><span class="generic-form-label">Date de requestionnement</span><input class="generic-form-control" type="date" data-review value="' + escapeHtml(state.review_date || '') + '"></label><label class="generic-form-field"><span class="generic-form-label">Date d echeance</span><input class="generic-form-control" type="date" data-expiration value="' + escapeHtml(state.expiration_date || '') + '"></label></div>';
                else fields += '<label class="generic-form-field"><span class="generic-form-label">Nom</span><input class="generic-form-control" data-name value="' + escapeHtml(state.name || '') + '"></label><label class="generic-form-field"><span class="generic-form-label">Nom complet</span><input class="generic-form-control" data-full-name value="' + escapeHtml(state.full_name || '') + '"></label><label class="generic-form-field"><span class="generic-form-label">Couleur</span><input class="generic-form-control" data-color value="' + escapeHtml(state.color || '') + '"></label>';
                modal(label({type:type}), '<div class="generic-section generic-section--stack"><div class="generic-form-stack">' + fields + '<div class="generic-form-actions"><button type="button" class="generic-action-button ' + (isDelete ? 'generic-action-button--danger' : 'generic-action-button--main') + '" data-save>' + escapeHtml(texts.actionApply || 'Ajouter a la proposition') + '</button></div></div></div>');
                var body = modalBody(), targetSelect = body.querySelector('[data-target]');
                if (targetSelect && !previous) targetSelect.addEventListener('change', function () { openEditor(pi, ai, type); body.querySelector('[data-target]').value = targetSelect.value; });
                body.querySelector('[data-save]').addEventListener('click', function () {
                    var targetId = isCreate ? 0 : parseInt(body.querySelector('[data-target]').value || '0', 10), base = isCreate ? null : index[String(targetId)]; if (!isCreate && !base) return;
                    var after = {};
                    if (!isDelete && isRule) { var authorityId = parseInt(body.querySelector('[data-authority]').value || '0', 10); after = {IDauthority:authorityId || null, IDholon:authorityId ? null : ((payload.defaultRuleState || {}).IDholon || null), title:body.querySelector('[data-title]').value.trim(), intention:body.querySelector('[data-intention]').value, description:body.querySelector('[data-description]').value, scope:authorityId ? (state.scope || 'local') : 'local', review_date:body.querySelector('[data-review]').value, expiration_date:body.querySelector('[data-expiration]').value}; }
                    if (!isDelete && !isRule) after = {name:body.querySelector('[data-name]').value.trim(), full_name:body.querySelector('[data-full-name]').value.trim(), color:body.querySelector('[data-color]').value.trim(), template_id:isCreate ? parseInt(body.querySelector('[data-template]').value || '0',10) : base.state.template_id};
                    if (!isDelete && (!isRule ? !after.name : (!after.title || !strip(after.description) || !after.review_date || !after.expiration_date))) return;
                    var action = {id:previous ? previous.id : 0, type:type, targetId:targetId, before:isCreate ? {} : (previous ? previous.before : clone(base.state)), after:isDelete ? {} : after, status:previous ? previous.status : 'pending', statusMessage:previous ? previous.statusMessage : ''};
                    if (ai >= 0) blueprint[pi].actions[ai] = action; else blueprint[pi].actions.push(action); window.commonTopbarCloseModal(); render();
                });
            }
            var activeRoleCapture = null;
            function openRoleDefinitionEditor(pi, ai, type) {
                var previous = ai >= 0 ? blueprint[pi].actions[ai] : null;
                if (type.endsWith('.update') && !previous) {
                    modal(texts.roleUpdate || 'Modifier un role', '<div class="generic-section generic-section--stack"><label class="generic-form-field"><span class="generic-form-label">Role</span><select class="generic-form-control" data-role-target>' + options(roles, (roles[0] || {}).id) + '</select></label><div class="generic-form-actions"><button type="button" class="generic-action-button generic-action-button--main" data-role-continue>Continuer</button></div></div>');
                    var selectionBody = modalBody();
                    selectionBody.querySelector('[data-role-continue]').addEventListener('click', function () {
                        var chosenId = Number(selectionBody.querySelector('[data-role-target]').value || 0);
                        var role = roleById[String(chosenId)];
                        if (!role) return;
                        activeRoleCapture = {proposalIndex:pi, actionIndex:ai, type:type, targetId:chosenId, previous:null};
                        window.omoHolonGovernanceInitialPayload = null;
                        window.commonTopbarOpenModal(texts.roleUpdate || 'Modifier un role', '/omo/api/holons/create.php?cid=' + Number(payload.contextHolonId || 0) + '&hid=' + chosenId + '&governance_capture=1&v=20260815-authority-labels', 'fetch');
                    });
                    return;
                }
                var roleId = previous ? previous.targetId : (type.endsWith('.update') ? Number((roles[0] || {}).id || 0) : 0);
                if (type.endsWith('.update') && roleId <= 0) return;
                activeRoleCapture = {proposalIndex:pi, actionIndex:ai, type:type, targetId:roleId, previous:previous};
                window.omoHolonGovernanceInitialPayload = previous && previous.after && previous.after.editor_payload ? clone(previous.after.editor_payload) : null;
                var url = '/omo/api/holons/create.php?cid=' + Number(payload.contextHolonId || 0) + '&governance_capture=1&v=20260815-authority-labels';
                if (roleId > 0) url += '&hid=' + roleId;
                if (typeof window.commonTopbarOpenModal === 'function') window.commonTopbarOpenModal(type.endsWith('.create') ? (texts.roleCreate || 'Creer un role') : (texts.roleUpdate || 'Modifier un role'), url, 'fetch');
            }
            window.addEventListener('omo-holon-governance-capture', function (event) {
                if (!activeRoleCapture || !event.detail || !event.detail.payload) return;
                var capture = activeRoleCapture, editorPayload = event.detail.payload, previous = capture.previous;
                var after = {editor_payload:editorPayload, name:String(editorPayload.name || '').trim(), full_name:String(editorPayload.fullName || '').trim(), color:String(editorPayload.color || '').trim(), template_id:Number(editorPayload.templateId || 0)};
                var base = roleById[String(capture.targetId)];
                var action = {id:previous ? previous.id : 0, type:capture.type, targetId:capture.type.endsWith('.create') ? 0 : capture.targetId, before:capture.type.endsWith('.create') ? {} : (previous ? previous.before : clone(base.state)), after:after, status:previous ? previous.status : 'pending', statusMessage:previous ? previous.statusMessage : ''};
                if (capture.actionIndex >= 0) blueprint[capture.proposalIndex].actions[capture.actionIndex] = action; else blueprint[capture.proposalIndex].actions.push(action);
                activeRoleCapture = null;
                window.omoHolonGovernanceInitialPayload = null;
                if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
                render();
            });
            var addProposalButton = root.querySelector('[data-governance-proposal-add]');
            if (addProposalButton) addProposalButton.addEventListener('click', function () { blueprint.push({id:0,title:(texts.proposalDefault || 'Proposition __INDEX__').replace('__INDEX__', blueprint.length + 1),description:'',actions:[]}); render(); });
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!form.reportValidity()) return;
                sync();
                if (!blueprint.length || blueprint.some(function (p) { return !p.actions.length; })) {
                    feedback.hidden = false;
                    feedback.textContent = texts.genericError || 'Ajoutez une proposition et une modification.';
                    return;
                }
                var formData = new FormData(form);
                var usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';
                var submitButton = form.querySelector('[type="submit"]');
                if (usesSharedPendingState && !window.omoBeginPendingAction(form)) return;
                if (!usesSharedPendingState && submitButton) submitButton.disabled = true;
                fetch(form.action, {method:'POST', credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}, body:formData})
                    .then(function (r) { return r.json().then(function (j) { return {ok:r.ok, json:j}; }); })
                    .then(function (r) {
                        if (!r.ok || !r.json.status) throw new Error(r.json.message);
                        if (typeof window.omoDecisionOpenNestedDrawer === 'function') window.omoDecisionOpenNestedDrawer(r.json.drawerTitle || 'Prises de decision', r.json.redirectUrl, '');
                        else window.location.href = r.json.redirectUrl;
                    })
                    .catch(function (error) {
                        feedback.hidden = false;
                        feedback.textContent = error.message || texts.genericError;
                    })
                    .finally(function () {
                        if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') window.omoEndPendingAction(form);
                        else if (submitButton) submitButton.disabled = false;
                    });
            });
            if (!blueprint.length && editable) blueprint.push({id:0,title:(texts.proposalDefault || 'Proposition __INDEX__').replace('__INDEX__', '1'),description:'',actions:[]}); render();
        });
    }
    window.omoGovernanceEditorInit = init;
})(window, document);
