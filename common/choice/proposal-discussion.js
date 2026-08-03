(function () {
    'use strict';

    if (window.__omoProposalDiscussionReady === true) {
        return;
    }
    window.__omoProposalDiscussionReady = true;

    var discussionUrl = '/omo/api/decision/modules/proposals/discussion.php';
    var editUrl = '/omo/api/decision/modules/proposals/edit.php';

    function notify(message, type) {
        if (typeof window.commonNotify === 'function') {
            window.commonNotify(String(message || ''), type || 'error');
        }
    }

    function sanitizeProposalHtml(value) {
        value = String(value || '');
        if (window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.sanitizeHtml === 'function') {
            return window.omoSimpleHtmlField.sanitizeHtml(value);
        }

        var fallback = document.createElement('div');
        fallback.textContent = value;
        return fallback.innerHTML.replace(/\r\n?|\n/g, '<br>');
    }

    function parseContext(button) {
        try {
            var parsed = JSON.parse(String(button.getAttribute('data-proposal-context') || '{}'));
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function appendContext(target, context) {
        Object.keys(context || {}).forEach(function (key) {
            var value = context[key];
            if (value !== '' && value !== null && typeof value !== 'undefined') {
                target.append(key, String(value));
            }
        });
    }

    function requestJson(url, options) {
        return window.fetch(url, options || {}).then(function (response) {
            return response.json().catch(function () {
                return {status: false, message: 'Réponse invalide du serveur.'};
            }).then(function (payload) {
                if (!response.ok || !payload || payload.status !== true) {
                    var requestError = new Error(String(payload && payload.message ? payload.message : 'La requête a échoué.'));
                    requestError.payload = payload;
                    throw requestError;
                }
                return payload;
            });
        });
    }

    function getAnonymousPreferenceKey(root, payload) {
        var context = root.__omoContext || {};
        var organizationId = Number(context.oid || 0);
        var decisionId = Number(context.id || 0);
        var userId = Number(payload && payload.viewerUserId ? payload.viewerUserId : 0);
        if (organizationId <= 0 || decisionId <= 0 || userId <= 0) {
            return '';
        }
        return 'omo:proposal-discussion:anonymous:' + organizationId + ':' + decisionId + ':' + userId;
    }

    function getAnonymousPreference(root) {
        var key = String(root.__omoAnonymousPreferenceKey || '');
        var expiresAt = Number(root.__omoAnonymousPreferenceExpiresAt || 0);
        var stored;
        if (!key) {
            return false;
        }
        try {
            if (Number.isFinite(expiresAt) && expiresAt > 0 && expiresAt <= Date.now()) {
                window.localStorage.removeItem(key);
                return false;
            }
            stored = JSON.parse(String(window.localStorage.getItem(key) || '{}'));
        } catch (error) {
            return false;
        }
        if (!stored || stored.enabled !== true) {
            try {
                window.localStorage.removeItem(key);
            } catch (error) {
                // The preference is optional when browser storage is unavailable.
            }
            return false;
        }
        return true;
    }

    function saveAnonymousPreference(root, enabled) {
        var key = String(root.__omoAnonymousPreferenceKey || '');
        var expiresAt = Number(root.__omoAnonymousPreferenceExpiresAt || 0);
        if (!key) {
            return;
        }
        try {
            if (!enabled || (Number.isFinite(expiresAt) && expiresAt > 0 && expiresAt <= Date.now())) {
                window.localStorage.removeItem(key);
                return;
            }
            window.localStorage.setItem(key, JSON.stringify({
                enabled: true,
                expiresAt: expiresAt
            }));
        } catch (error) {
            // The preference is optional when browser storage is unavailable.
        }
    }

    function buildModalHtml() {
        return ''
            + '<div class="omo-proposal-popup-content omo-proposal-chat" data-omo-proposal-chat data-topbar-modal-max-width="760px">'
            + '  <div>'
            + '    <div class="omo-proposal-chat__toolbar">'
            + '      <p class="omo-proposal-chat__proposal-title" data-omo-proposal-chat-title></p>'
            + '    </div>'
            + '  </div>'
            + '  <div class="omo-proposal-chat__messages" data-omo-proposal-chat-messages role="log" aria-live="polite"><p class="omo-proposal-chat__loading">Chargement de la discussion…</p></div>'
            + '  <form class="omo-proposal-chat__composer" data-omo-proposal-chat-composer>'
            + '    <textarea class="generic-form-control" name="content" rows="2" maxlength="4000" placeholder="Écrire un message…" required></textarea>'
            + '    <div class="omo-proposal-chat__composer-actions">'
            + '      <button type="submit" class="generic-action-button generic-action-button--main">Envoyer</button>'
            + '      <label class="omo-proposal-chat__anonymous-option"><input type="checkbox" name="is_anonymous" value="1" data-omo-proposal-chat-anonymous> Publier anonymement</label>'
            + '    </div>'
            + '  </form>'
            + '</div>';
    }

    function buildEditorHtml() {
        return ''
            + '<form class="omo-proposal-popup-content omo-proposal-editor" data-omo-proposal-editor data-topbar-modal-max-width="660px">'
            + '  <label class="omo-proposal-editor__field"><span class="generic-card-title generic-card-title--small">Titre</span><input class="generic-form-control" type="text" name="title" maxlength="190" required></label>'
            + '  <label class="omo-proposal-editor__field"><span class="generic-card-title generic-card-title--small">Description</span><div data-omo-proposal-html-field><div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-proposal-editor-description></div><textarea hidden aria-hidden="true" name="description" data-omo-proposal-html-value></textarea></div></label>'
            + '  <label class="omo-proposal-editor__field"><span class="generic-card-title generic-card-title--small">Lien d information</span><input class="generic-form-control" type="url" name="info_url" maxlength="500" placeholder="https://..."></label>'
            + '  <div style="display:flex;justify-content:flex-end;gap:8px;"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-proposal-editor-cancel>Annuler</button><button type="submit" class="generic-action-button generic-action-button--main">Enregistrer</button></div>'
            + '</form>';
    }

    function isSafeImageUrl(value) {
        value = String(value || '').trim();
        return value.indexOf('/') === 0 || /^https?:\/\//i.test(value) || /^data:image\//i.test(value);
    }

    function createAvatar(message) {
        var avatar = document.createElement('span');
        avatar.className = 'omo-proposal-chat__avatar';
        avatar.setAttribute('aria-hidden', 'true');
        var initials = String(message.initials || '?');
        avatar.textContent = initials;

        if (isSafeImageUrl(message.photoUrl)) {
            var image = document.createElement('img');
            image.alt = '';
            image.src = String(message.photoUrl);
            image.addEventListener('error', function () {
                image.remove();
                avatar.textContent = initials;
            }, {once: true});
            avatar.textContent = '';
            avatar.appendChild(image);
        }
        return avatar;
    }

    function tokenizeForWordDiff(text) {
        var tokens = String(text || '').match(/(\s+|[^\s]+)/g);
        return Array.isArray(tokens) ? tokens : [];
    }

    function buildWordDiffOperations(beforeText, afterText) {
        var beforeTokens = tokenizeForWordDiff(beforeText);
        var afterTokens = tokenizeForWordDiff(afterText);
        var matrix = [];
        var operations = [];
        var i;
        var j;

        if ((beforeTokens.length + 1) * (afterTokens.length + 1) > 40000) {
            return null;
        }
        for (i = 0; i <= beforeTokens.length; i += 1) {
            matrix[i] = [];
            for (j = 0; j <= afterTokens.length; j += 1) {
                matrix[i][j] = 0;
            }
        }
        for (i = 1; i <= beforeTokens.length; i += 1) {
            for (j = 1; j <= afterTokens.length; j += 1) {
                matrix[i][j] = beforeTokens[i - 1] === afterTokens[j - 1]
                    ? matrix[i - 1][j - 1] + 1
                    : Math.max(matrix[i - 1][j], matrix[i][j - 1]);
            }
        }

        i = beforeTokens.length;
        j = afterTokens.length;
        while (i > 0 || j > 0) {
            if (i > 0 && j > 0 && beforeTokens[i - 1] === afterTokens[j - 1]) {
                operations.push({type: 'equal', value: beforeTokens[i - 1]});
                i -= 1;
                j -= 1;
            } else if (j > 0 && (i === 0 || matrix[i][j - 1] > matrix[i - 1][j])) {
                operations.push({type: 'added', value: afterTokens[j - 1]});
                j -= 1;
            } else {
                operations.push({type: 'removed', value: beforeTokens[i - 1]});
                i -= 1;
            }
        }
        operations.reverse();
        return operations;
    }

    function createDiffValue(label, value, operations, side, status) {
        var block = document.createElement('div');
        var heading = document.createElement('span');
        var content = document.createElement('div');
        block.className = 'omo-proposal-chat__diff-value omo-proposal-chat__diff-value--' + side;
        heading.className = 'omo-proposal-chat__diff-value-label';
        heading.textContent = label;
        content.className = 'omo-proposal-chat__diff-value-content';
        block.appendChild(heading);
        block.appendChild(content);

        if (!Array.isArray(operations) || operations.length === 0) {
            content.textContent = String(value || '(vide)');
            if (value) {
                content.classList.add(side === 'before' ? 'is-removed' : 'is-added');
            } else {
                content.classList.add('is-empty');
            }
            return block;
        }

        operations.forEach(function (operation) {
            var shouldRender = operation.type === 'equal'
                || (side === 'before' && operation.type === 'removed')
                || (side === 'after' && operation.type === 'added');
            if (!shouldRender) {
                return;
            }
            if (operation.type === 'equal') {
                content.appendChild(document.createTextNode(operation.value));
                return;
            }
            var highlight = document.createElement('span');
            highlight.className = operation.type === 'added'
                ? 'omo-proposal-chat__diff-added'
                : 'omo-proposal-chat__diff-removed';
            highlight.textContent = operation.value;
            content.appendChild(highlight);
        });
        if (!content.textContent) {
            content.textContent = '(vide)';
            content.classList.add('is-empty');
        }
        return block;
    }

    function createChangesDetails(changes) {
        var details = document.createElement('details');
        var summary = document.createElement('summary');
        var list = document.createElement('div');
        details.className = 'omo-proposal-chat__change-details';
        summary.textContent = 'Détail';
        list.className = 'omo-proposal-chat__diff-list';
        details.appendChild(summary);
        details.appendChild(list);

        changes.forEach(function (change) {
            var before = String(change.before || '');
            var after = String(change.after || '');
            var operations = before && after ? buildWordDiffOperations(before, after) : null;
            var card = document.createElement('section');
            var title = document.createElement('strong');
            card.className = 'omo-proposal-chat__diff-card omo-proposal-chat__diff-card--' + String(change.status || 'changed');
            title.className = 'omo-proposal-chat__diff-title';
            title.textContent = String(change.label || 'Modification');
            card.appendChild(title);
            card.appendChild(createDiffValue('Avant', before, operations, 'before', String(change.status || 'changed')));
            card.appendChild(createDiffValue('Après', after, operations, 'after', String(change.status || 'changed')));
            list.appendChild(card);
        });
        return details;
    }

    function createMessageNode(message) {
        var row = document.createElement('div');
        var type = String(message.type || 'user');
        if (type === 'system') {
            row.className = 'omo-proposal-chat__message-row omo-proposal-chat__message-row--system';
            var systemWrapper = document.createElement('div');
            systemWrapper.className = 'omo-proposal-chat__system-wrapper';
            var systemMessage = document.createElement('span');
            systemMessage.className = 'omo-proposal-chat__system-message';
            systemMessage.textContent = String(message.content || '');
            if (message.createdAtLabel) {
                systemMessage.title = String(message.createdAtLabel);
            }
            systemWrapper.appendChild(systemMessage);
            if (Array.isArray(message.changes) && message.changes.length > 0) {
                systemWrapper.appendChild(createChangesDetails(message.changes));
            }
            row.appendChild(systemWrapper);
            return row;
        }

        var isOwn = message.isOwn === true;
        row.className = 'omo-proposal-chat__message-row' + (isOwn ? ' omo-proposal-chat__message-row--own' : '');
        if (!isOwn) {
            row.appendChild(createAvatar(message));
        }

        var bubble = document.createElement('div');
        bubble.className = 'omo-proposal-chat__bubble';
        var authorRoles = Array.isArray(message.authorRoles) ? message.authorRoles : [];
        if ((!isOwn && message.authorName) || authorRoles.length > 0) {
            var authorMeta = document.createElement('div');
            authorMeta.className = 'omo-proposal-chat__author-meta';
            if (!isOwn && message.authorName) {
                var author = document.createElement('span');
                author.className = 'omo-proposal-chat__author';
                author.textContent = String(message.authorName);
                authorMeta.appendChild(author);
            }
            authorRoles.forEach(function (role) {
                if (!role || !role.label) {
                    return;
                }
                var roleBadge = document.createElement('span');
                var roleKey = String(role.key || '');
                roleBadge.className = 'omo-proposal-chat__author-role';
                if (roleKey === 'decision-administrator' || roleKey === 'proposal-author') {
                    roleBadge.classList.add('omo-proposal-chat__author-role--' + roleKey);
                }
                roleBadge.textContent = String(role.label);
                authorMeta.appendChild(roleBadge);
            });
            if (authorMeta.childNodes.length > 0) {
                bubble.appendChild(authorMeta);
            }
        }
        var content = document.createElement('p');
        content.className = 'omo-proposal-chat__content';
        content.textContent = String(message.content || '');
        bubble.appendChild(content);
        var time = document.createElement('span');
        time.className = 'omo-proposal-chat__time';
        time.textContent = String(message.createdAtLabel || '');
        bubble.appendChild(time);
        row.appendChild(bubble);
        return row;
    }

    function renderMessages(root, messages) {
        var list = root.querySelector('[data-omo-proposal-chat-messages]');
        if (!list) {
            return;
        }
        var isInitialRender = root.__omoMessagesInitialized !== true;
        var wasNearBottom = isInitialRender
            || (list.scrollHeight - list.scrollTop - list.clientHeight) < 90;
        var knownMessageIds = root.__omoKnownMessageIds || {};
        var normalizedMessages = Array.isArray(messages) ? messages : [];
        var appendedCount = 0;

        if (isInitialRender) {
            list.innerHTML = '';
        }
        if (isInitialRender && normalizedMessages.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'omo-proposal-chat__empty';
            empty.setAttribute('data-omo-proposal-chat-empty', '');
            empty.textContent = 'Aucun message pour le moment. Lancez la discussion autour de cette proposition.';
            list.appendChild(empty);
        } else {
            normalizedMessages.forEach(function (message) {
                var messageId = Number(message && message.id ? message.id : 0);
                if (messageId > 0 && knownMessageIds[messageId]) {
                    return;
                }
                var empty = list.querySelector('[data-omo-proposal-chat-empty]');
                if (empty) {
                    empty.remove();
                }
                list.appendChild(createMessageNode(message || {}));
                if (messageId > 0) {
                    knownMessageIds[messageId] = true;
                    root.__omoLastMessageId = Math.max(Number(root.__omoLastMessageId || 0), messageId);
                }
                appendedCount += 1;
            });
        }
        root.__omoKnownMessageIds = knownMessageIds;
        root.__omoMessagesInitialized = true;
        if (wasNearBottom && (isInitialRender || appendedCount > 0)) {
            list.scrollTop = list.scrollHeight;
        }
    }

    function syncProposal(root, proposal) {
        root.__omoProposal = proposal || {};
        var title = root.querySelector('[data-omo-proposal-chat-title]');
        if (title) {
            title.textContent = String(proposal.title || 'Proposition');
        }
    }

    function syncDiscussionMode(root, payload) {
        var anonymousInput = root.querySelector('[data-omo-proposal-chat-anonymous]');
        var anonymousOption = anonymousInput ? anonymousInput.closest('.omo-proposal-chat__anonymous-option') : null;
        var discussionIsAnonymous = payload && payload.discussionIsAnonymous === true;
        var expiresAt = Date.parse(String(payload && payload.anonymousPreferenceExpiresAt || ''));
        root.__omoDiscussionIsAnonymous = discussionIsAnonymous;
        root.__omoAnonymousPreferenceKey = getAnonymousPreferenceKey(root, payload);
        root.__omoAnonymousPreferenceExpiresAt = Number.isFinite(expiresAt) ? expiresAt : 0;
        if (!anonymousInput) {
            return;
        }
        if (discussionIsAnonymous) {
            anonymousInput.checked = true;
            anonymousInput.disabled = true;
            if (anonymousOption) {
                anonymousOption.hidden = true;
            }
        } else {
            anonymousInput.disabled = false;
            if (anonymousOption) {
                anonymousOption.hidden = false;
            }
            if (root.__omoAnonymousPreferenceInitialized !== true && root.__omoAnonymousPreferenceTouched !== true) {
                anonymousInput.checked = getAnonymousPreference(root);
            }
        }
        root.__omoAnonymousPreferenceInitialized = true;
    }

    function updateVisibleProposal(button, proposal) {
        var card = button.closest('.generic-section');
        if (!card) {
            return;
        }
        var title = card.querySelector('strong');
        if (title) {
            title.textContent = String(proposal.title || '');
        }
        if (card.hasAttribute('data-omo-decision-vote-result-title')) {
            card.setAttribute('data-omo-decision-vote-result-title', String(proposal.title || ''));
        }
        if (card.hasAttribute('data-omo-decision-mj-result-title')) {
            card.setAttribute('data-omo-decision-mj-result-title', String(proposal.title || ''));
        }

        var actions = card.querySelector('[data-omo-proposal-discussion-actions]');
        var knownDescription = card.querySelector('.omo-decision-vote__text, .omo-decision-majority-judgment__text, .omo-decision-consent__text, .omo-proposal-live-description');
        if (!knownDescription && proposal.description && actions) {
            knownDescription = document.createElement('div');
            knownDescription.className = 'omo-proposal-live-description';
            actions.parentNode.insertBefore(knownDescription, actions);
        }
        if (knownDescription) {
            knownDescription.innerHTML = sanitizeProposalHtml(proposal.description || '');
            knownDescription.hidden = !proposal.description;
        }
        var knownLink = card.querySelector('.omo-decision-vote__link, .omo-decision-majority-judgment__link, .omo-decision-consent__link, .omo-proposal-live-link');
        if (!knownLink && proposal.infoUrl && actions) {
            knownLink = document.createElement('a');
            knownLink.className = 'omo-proposal-live-link';
            knownLink.target = '_blank';
            knownLink.rel = 'noopener noreferrer';
            knownLink.textContent = 'Plus d infos';
            actions.parentNode.insertBefore(knownLink, actions);
        }
        if (knownLink) {
            knownLink.href = String(proposal.infoUrl || '#');
            knownLink.hidden = !proposal.infoUrl;
        }
        var dateNode = card.querySelector('[data-omo-proposal-date]');
        if (dateNode && proposal.updatedLabel) {
            dateNode.textContent = 'Modifiée le ' + String(proposal.updatedLabel);
        }
    }

    function loadDiscussion(root) {
        if (root.__omoDiscussionLoadPromise) {
            return root.__omoDiscussionLoadPromise;
        }
        var query = new URLSearchParams();
        appendContext(query, root.__omoContext || {});
        query.append('proposal_id', String(root.__omoProposalId || 0));
        if (root.__omoMessagesInitialized === true && Number(root.__omoLastMessageId || 0) > 0) {
            query.append('after_id', String(root.__omoLastMessageId));
        }
        root.__omoDiscussionLoadPromise = requestJson(discussionUrl + '?' + query.toString()).then(function (payload) {
            syncProposal(root, payload.proposal || {});
            syncDiscussionMode(root, payload);
            renderMessages(root, payload.messages || []);
            root.__omoLastMessageId = Math.max(
                Number(root.__omoLastMessageId || 0),
                Number(payload.lastMessageId || 0)
            );
            return payload;
        }).finally(function () {
            root.__omoDiscussionLoadPromise = null;
        });
        return root.__omoDiscussionLoadPromise;
    }

    function startDiscussionPolling(root) {
        var stopPolling;
        if (root.__omoDiscussionPollingId) {
            return;
        }

        stopPolling = function () {
            if (root.__omoDiscussionPollingId) {
                window.clearInterval(root.__omoDiscussionPollingId);
                root.__omoDiscussionPollingId = null;
            }
            window.removeEventListener('common-topbar-modal-close', stopPolling);
        };
        root.__omoDiscussionPollingId = window.setInterval(function () {
            var modal = document.getElementById('commonTopbarModal');
            if (!root.isConnected || !modal || modal.hidden) {
                stopPolling();
                return;
            }
            if (document.visibilityState === 'hidden') {
                return;
            }
            loadDiscussion(root).catch(function () {
                // A temporary polling failure must not interrupt the discussion.
            });
        }, 10000);
        window.addEventListener('common-topbar-modal-close', stopPolling, {once: true});
    }

    function bindModal(root, button) {
        var composer = root.querySelector('[data-omo-proposal-chat-composer]');
        var anonymousInput = composer.querySelector('[data-omo-proposal-chat-anonymous]');

        if (anonymousInput) {
            anonymousInput.addEventListener('change', function () {
                root.__omoAnonymousPreferenceTouched = true;
                saveAnonymousPreference(root, anonymousInput.checked);
            });
        }

        composer.addEventListener('submit', function (event) {
            event.preventDefault();
            var content = String(composer.elements.content.value || '').trim();
            if (!content) {
                return;
            }
            var submit = composer.querySelector('[type="submit"]');
            submit.disabled = true;
            var body = new FormData();
            appendContext(body, root.__omoContext || {});
            body.append('proposal_id', String(root.__omoProposalId || 0));
            body.append('content', content);
            if (anonymousInput && anonymousInput.checked) {
                body.append('is_anonymous', '1');
            }
            if (anonymousInput) {
                saveAnonymousPreference(root, anonymousInput.checked);
            }
            requestJson(discussionUrl, {method: 'POST', body: body})
                .then(function (payload) {
                    composer.elements.content.value = '';
                    syncProposal(root, payload.proposal || {});
                    syncDiscussionMode(root, payload);
                    renderMessages(root, payload.messages || []);
                })
                .catch(function (error) {
                    notify(error.message, 'error');
                })
                .finally(function () {
                    submit.disabled = false;
                    composer.elements.content.focus();
                });
        });

    }

    function openEditor(button) {
        if (typeof window.commonTopbarOpenModal !== 'function') {
            notify('La fenêtre de modification ne peut pas être ouverte.', 'error');
            return;
        }

        var context = parseContext(button);
        var proposalId = Number(button.getAttribute('data-proposal-id') || 0);
        window.commonTopbarOpenModal('Modifier la proposition', buildEditorHtml(), 'html');
        var form = document.querySelector('#commonTopbarModalBody [data-omo-proposal-editor]');
        if (!form) {
            return;
        }
        var descriptionEditor = form.querySelector('[data-omo-proposal-editor-description]');
        if (descriptionEditor && window.omoProposalHtml && typeof window.omoProposalHtml.mount === 'function') {
            window.omoProposalHtml.mount(descriptionEditor).catch(function () {
                descriptionEditor.setAttribute('data-omo-proposal-html-error', '1');
            });
        }

        var query = new URLSearchParams();
        appendContext(query, context);
        query.append('proposal_id', String(proposalId));
        Array.prototype.forEach.call(form.elements, function (field) {
            field.disabled = true;
        });
        requestJson(editUrl + '?' + query.toString())
            .then(function (payload) {
                var proposal = payload.proposal || {};
                form.elements.title.value = String(proposal.title || '');
                if (descriptionEditor && window.omoProposalHtml && typeof window.omoProposalHtml.setValue === 'function') {
                    window.omoProposalHtml.setValue(descriptionEditor, String(proposal.description || ''));
                } else {
                    form.elements.description.value = String(proposal.description || '');
                }
                form.elements.info_url.value = String(proposal.infoUrl || '');
                Array.prototype.forEach.call(form.elements, function (field) {
                    field.disabled = false;
                });
                form.elements.title.focus();
            })
            .catch(function (error) {
                notify(error.message, 'error');
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });

        form.querySelector('[data-omo-proposal-editor-cancel]').addEventListener('click', function () {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            var body = new FormData(form);
            appendContext(body, context);
            body.append('proposal_id', String(proposalId));
            requestJson(editUrl, {method: 'POST', body: body})
                .then(function (payload) {
                    updateVisibleProposal(button, payload.proposal || {});
                    if (typeof window.commonTopbarCloseModal === 'function') {
                        window.commonTopbarCloseModal();
                    }
                    notify(payload.message || 'Proposition modifiée.', 'success');
                })
                .catch(function (error) {
                    notify(error.message, 'error');
                })
                .finally(function () {
                    submit.disabled = false;
                });
        });
    }

    function openDiscussion(button) {
        if (typeof window.commonTopbarOpenModal !== 'function') {
            notify('La fenêtre de discussion ne peut pas être ouverte.', 'error');
            return;
        }

        window.commonTopbarOpenModal('Discussion de la proposition', buildModalHtml(), 'html');
        var root = document.querySelector('#commonTopbarModalBody [data-omo-proposal-chat]');
        if (!root) {
            return;
        }
        root.__omoContext = parseContext(button);
        root.__omoProposalId = Number(button.getAttribute('data-proposal-id') || 0);
        bindModal(root, button);
        loadDiscussion(root).catch(function (error) {
            var list = root.querySelector('[data-omo-proposal-chat-messages]');
            if (list) {
                list.innerHTML = '';
                var errorNode = document.createElement('p');
                errorNode.className = 'omo-proposal-chat__empty';
                errorNode.textContent = error.message;
                list.appendChild(errorNode);
            }
            notify(error.message, 'error');
        }).finally(function () {
            if (root.isConnected) {
                startDiscussionPolling(root);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-omo-proposal-discussion-open]');
        if (!button) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        openDiscussion(button);
    }, true);

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-omo-proposal-edit-open]');
        if (!button) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        openEditor(button);
    }, true);
})();
