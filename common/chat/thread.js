(function () {
    'use strict';

    function parseJson(value) {
        try {
            var parsed = JSON.parse(String(value || '{}'));
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function appendContext(target, context) {
        Object.keys(context || {}).forEach(function (key) {
            var value = context[key];
            if (value !== null && typeof value !== 'undefined' && String(value) !== '') {
                target.append(key, String(value));
            }
        });
    }

    function notify(message) {
        if (typeof window.commonNotify === 'function') {
            window.commonNotify(String(message || ''), 'error');
        }
    }

    function requestJson(url, options) {
        return window.fetch(url, options || {}).then(function (response) {
            return response.json().catch(function () {
                return {status: false, message: 'Reponse invalide du serveur.'};
            }).then(function (payload) {
                if (!response.ok || !payload || payload.status !== true) {
                    throw new Error(String(payload && payload.message ? payload.message : 'La requete a echoue.'));
                }
                return payload;
            });
        });
    }

    function createAvatar(message) {
        var avatar = document.createElement('span');
        var photoUrl = String(message.photoUrl || '');
        avatar.className = 'omo-chat-popup__avatar';
        if (/^(?:https?:|\/|data:image\/)/i.test(photoUrl)) {
            var image = document.createElement('img');
            image.src = photoUrl;
            image.alt = '';
            avatar.appendChild(image);
        } else {
            avatar.textContent = String(message.initials || '?');
        }
        return avatar;
    }

    function plainText(value) {
        var source = document.createElement('div');
        source.innerHTML = String(value || '');
        source.querySelectorAll('[data-omo-embed-type]').forEach(function (embed) {
            var embedText = String(embed.textContent || '').replace(/\s+/g, ' ').trim();
            embed.textContent = ' [' + embedText + '] ';
        });
        return String(source.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function buildChangeExcerpt(beforeValue, afterValue) {
        var before = plainText(beforeValue);
        var after = plainText(afterValue);
        var contextLength = 20;
        var operations = window.omoChoiceWordDiff && typeof window.omoChoiceWordDiff.buildOperations === 'function'
            ? window.omoChoiceWordDiff.buildOperations(before, after)
            : null;
        var changedIndexes = [];
        var clusters = [];
        var currentCluster = null;
        var i;

        if (!Array.isArray(operations)) return buildFallbackChangeExcerpt(before, after);
        operations.forEach(function (operation, index) {
            if (operation.type !== 'equal') changedIndexes.push(index);
        });
        if (changedIndexes.length === 0) return {before: before, after: after};

        changedIndexes.forEach(function (index) {
            if (!currentCluster) {
                currentCluster = {start: index, end: index};
                return;
            }
            var equalLength = 0;
            for (i = currentCluster.end + 1; i < index; i += 1) {
                if (operations[i].type === 'equal') equalLength += String(operations[i].value || '').length;
            }
            if (equalLength <= contextLength * 2) {
                currentCluster.end = index;
                return;
            }
            clusters.push(currentCluster);
            currentCluster = {start: index, end: index};
        });
        if (currentCluster) clusters.push(currentCluster);

        function operationText(operation, side) {
            if (operation.type === 'equal') return String(operation.value || '');
            if (side === 'before' && operation.type === 'removed') return String(operation.value || '');
            if (side === 'after' && operation.type === 'added') return String(operation.value || '');
            return '';
        }

        function adjacentEqualText(index, direction) {
            var result = '';
            for (i = index; i >= 0 && i < operations.length && operations[i].type === 'equal'; i += direction) {
                result = direction < 0
                    ? String(operations[i].value || '') + result
                    : result + String(operations[i].value || '');
            }
            return result;
        }

        function excerptSide(side) {
            var fragments = clusters.map(function (cluster) {
                var prefix = adjacentEqualText(cluster.start - 1, -1);
                var suffix = adjacentEqualText(cluster.end + 1, 1);
                var body = operations.slice(cluster.start, cluster.end + 1).map(function (operation) {
                    return operationText(operation, side);
                }).join('');
                return prefix.slice(-contextLength) + body + suffix.slice(0, contextLength);
            });
            if (adjacentEqualText(clusters[0].start - 1, -1).length > contextLength) {
                fragments[0] = '... ' + fragments[0];
            }
            if (adjacentEqualText(clusters[clusters.length - 1].end + 1, 1).length > contextLength) {
                fragments[fragments.length - 1] += ' ...';
            }
            return fragments.join(' ... ');
        }

        return {
            before: excerptSide('before'),
            after: excerptSide('after')
        };
    }

    function buildFallbackChangeExcerpt(beforeValue, afterValue) {
        var before = String(beforeValue || '');
        var after = String(afterValue || '');
        var prefix = 0;
        var suffix = 0;
        var contextLength = 20;
        while (prefix < before.length && prefix < after.length && before.charAt(prefix) === after.charAt(prefix)) {
            prefix += 1;
        }
        while (
            suffix < before.length - prefix
            && suffix < after.length - prefix
            && before.charAt(before.length - suffix - 1) === after.charAt(after.length - suffix - 1)
        ) {
            suffix += 1;
        }
        function excerpt(value, sharedPrefix, sharedSuffix) {
            var start = Math.max(0, sharedPrefix - contextLength);
            var end = Math.min(value.length, value.length - sharedSuffix + contextLength);
            var result = value.slice(start, end).trim();
            if (start > 0) result = '... ' + result;
            if (end < value.length) result += ' ...';
            return result;
        }
        return {
            before: excerpt(before, prefix, suffix),
            after: excerpt(after, prefix, suffix)
        };
    }

    function createChangesDetails(changes, labels) {
        var normalizedChanges = (Array.isArray(changes) ? changes : []).map(function (change) {
            var normalized = Object.assign({}, change || {});
            if (String(normalized.field || '') === 'content') {
                var excerpt = buildChangeExcerpt(normalized.before, normalized.after);
                normalized.before = excerpt.before;
                normalized.after = excerpt.after;
                normalized.label = String(labels.contentExcerpt || 'Contenu (extrait)');
            }
            return normalized;
        });
        if (window.omoChoiceChangeDetails && typeof window.omoChoiceChangeDetails.create === 'function') {
            return window.omoChoiceChangeDetails.create(normalizedChanges, {
                label: String(labels.changeDetails || 'Voir les modifications')
            });
        }
        var details = document.createElement('details');
        var summary = document.createElement('summary');
        summary.textContent = String(labels.changeDetails || 'Voir les modifications');
        details.appendChild(summary);
        normalizedChanges.forEach(function (change) {
            var item = document.createElement('p');
            item.textContent = String(change.label || 'Modification') + ': '
                + String(change.before || '(vide)') + ' -> ' + String(change.after || '(vide)');
            details.appendChild(item);
        });
        return details;
    }

    function createMessageNode(message, labels) {
        var type = String(message.type || 'user');
        var row = document.createElement('div');
        row.className = 'omo-chat-popup__message-row omo-chat-popup__message-row--' + type;
        if (type === 'system') {
            var systemWrapper = document.createElement('div');
            systemWrapper.className = 'omo-chat-popup__system-wrapper';
            var systemMessage = document.createElement('span');
            systemMessage.className = 'omo-chat-popup__system-message';
            systemMessage.textContent = String(message.content || '');
            if (message.createdAtLabel) systemMessage.title = String(message.createdAtLabel);
            systemWrapper.appendChild(systemMessage);
            if (Array.isArray(message.changes) && message.changes.length > 0) {
                systemWrapper.appendChild(createChangesDetails(message.changes, labels || {}));
            }
            row.appendChild(systemWrapper);
            return row;
        }
        var isOwn = message.isOwn === true;
        if (isOwn) row.className += ' omo-chat-popup__message-row--own';
        if (!isOwn) row.appendChild(createAvatar(message));

        var bubble = document.createElement('article');
        bubble.className = 'omo-chat-popup__bubble';
        if (!isOwn && String(message.authorName || '')) {
            var authorMeta = document.createElement('div');
            authorMeta.className = 'omo-chat-popup__author-meta';
            var author = document.createElement('strong');
            author.className = 'omo-chat-popup__author';
            author.textContent = String(message.authorName || '');
            authorMeta.appendChild(author);
            bubble.appendChild(authorMeta);
        }
        var content = document.createElement('p');
        content.className = 'omo-chat-popup__content';
        content.textContent = String(message.content || '');
        var time = document.createElement('time');
        time.className = 'omo-chat-popup__time';
        time.textContent = String(message.createdAtLabel || '');
        bubble.appendChild(content);
        bubble.appendChild(time);
        row.appendChild(bubble);
        return row;
    }

    function buildModalHtml() {
        return ''
            + '<section class="omo-chat-popup-content omo-chat-popup" data-omo-chat-popup data-topbar-modal-max-width="760px">'
            + '<header class="omo-chat-popup__toolbar"><p class="omo-chat-popup__point-title" data-omo-chat-popup-point-title></p></header>'
            + '<div class="omo-chat-popup__messages" data-omo-chat-popup-messages role="log" aria-live="polite"></div>'
            + '<form class="omo-chat-popup__composer" data-omo-chat-popup-composer>'
            + '<textarea class="generic-form-control" name="content" rows="2" maxlength="4000" required></textarea>'
            + '<div class="omo-chat-popup__composer-actions"><button type="submit" class="generic-action-button generic-action-button--main"></button></div>'
            + '</form></section>';
    }

    function renderMessages(root, messages, append) {
        var list = root.querySelector('[data-omo-chat-popup-messages]');
        var items = Array.isArray(messages) ? messages : [];
        if (!list) return;
        var shouldScrollToEnd = !append || (list.scrollHeight - list.scrollTop - list.clientHeight < 80);
        if (!append) list.replaceChildren();
        if (append && items.length > 0 && list.querySelector('.omo-chat-popup__empty')) list.replaceChildren();
        items.forEach(function (message) { list.appendChild(createMessageNode(message, root.__omoChatLabels || {})); });
        if (!list.children.length) {
            var empty = document.createElement('p');
            empty.className = 'omo-chat-popup__empty';
            empty.textContent = String(root.__omoChatLabels.empty || '');
            list.appendChild(empty);
        }
        if (shouldScrollToEnd) list.scrollTop = list.scrollHeight;
    }

    function syncPayload(root, payload) {
        var title = root.querySelector('[data-omo-chat-popup-point-title]');
        var form = root.querySelector('[data-omo-chat-popup-composer]');
        if (title) title.textContent = String(payload.title || root.__omoChatPointTitle || '');
        if (form) form.hidden = payload.canPost !== true;
        if (typeof payload.messageCount === 'undefined' || !root.__omoChatTrigger) return;
        var messageCount = Math.max(0, Number(payload.messageCount) || 0);
        var trigger = root.__omoChatTrigger;
        var countDisplay = trigger.closest('.omo-chat-popup-actions');
        if (!countDisplay) return;
        countDisplay = countDisplay.querySelector('[data-omo-chat-message-count-display]');
        if (!countDisplay) return;
        var countLabel = String(root.__omoChatLabels.messageCount || '').replace('{count}', String(messageCount));
        trigger.setAttribute('data-omo-chat-message-count', String(messageCount));
        countDisplay.setAttribute('title', countLabel);
        countDisplay.setAttribute('aria-label', countLabel);
        var countValue = countDisplay.querySelector('.omo-chat-popup-count-value');
        if (countValue) countValue.textContent = String(messageCount);
    }

    function loadDiscussion(root) {
        if (root.__omoChatLoading) return root.__omoChatLoading;
        var query = new URLSearchParams();
        appendContext(query, root.__omoChatContext);
        if (root.__omoChatInitialized && root.__omoChatLastMessageId > 0) query.append('after_id', String(root.__omoChatLastMessageId));
        root.__omoChatLoading = requestJson(root.__omoChatEndpoint + '?' + query.toString()).then(function (payload) {
            syncPayload(root, payload);
            renderMessages(root, payload.messages || [], root.__omoChatInitialized === true);
            root.__omoChatInitialized = true;
            root.__omoChatLastMessageId = Math.max(Number(root.__omoChatLastMessageId || 0), Number(payload.lastMessageId || 0));
            return payload;
        }).finally(function () {
            root.__omoChatLoading = null;
        });
        return root.__omoChatLoading;
    }

    function startPolling(root) {
        var stop = function () {
            if (root.__omoChatPollingId) window.clearInterval(root.__omoChatPollingId);
            root.__omoChatPollingId = null;
            window.removeEventListener('common-topbar-modal-close', stop);
        };
        root.__omoChatPollingId = window.setInterval(function () {
            var modal = document.getElementById('commonTopbarModal');
            if (!root.isConnected || !modal || modal.hidden) return stop();
            if (document.visibilityState !== 'hidden') loadDiscussion(root).catch(function () {});
        }, 10000);
        window.addEventListener('common-topbar-modal-close', stop, {once: true});
    }

    function bindComposer(root) {
        var form = root.querySelector('[data-omo-chat-popup-composer]');
        if (!form) return;
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var content = String(form.elements.content.value || '').trim();
            var submit = form.querySelector('[type="submit"]');
            if (!content || !submit) return;
            submit.disabled = true;
            var body = new FormData();
            appendContext(body, root.__omoChatContext);
            body.append('content', content);
            requestJson(root.__omoChatEndpoint, {method: 'POST', body: body}).then(function (payload) {
                form.elements.content.value = '';
                syncPayload(root, payload);
                renderMessages(root, payload.messages || [], false);
                root.__omoChatInitialized = true;
                root.__omoChatLastMessageId = Number(payload.lastMessageId || 0);
                form.elements.content.focus();
            }).catch(function (error) {
                notify(error.message);
            }).finally(function () {
                submit.disabled = false;
            });
        });
    }

    function openDiscussion(button) {
        if (typeof window.commonTopbarOpenModal !== 'function') {
            notify('La fenetre de discussion ne peut pas etre ouverte.');
            return;
        }
        var labels = parseJson(button.getAttribute('data-omo-chat-labels'));
        window.commonTopbarOpenModal(String(button.getAttribute('data-omo-chat-title') || ''), buildModalHtml(), 'html');
        var root = document.querySelector('#commonTopbarModalBody [data-omo-chat-popup]');
        if (!root) return;
        root.__omoChatEndpoint = String(button.getAttribute('data-omo-chat-endpoint') || '');
        root.__omoChatContext = parseJson(button.getAttribute('data-omo-chat-context'));
        root.__omoChatLabels = labels;
        root.__omoChatTrigger = button;
        root.__omoChatReadOnly = button.getAttribute('data-omo-chat-readonly') === '1';
        root.__omoChatPointTitle = String(button.getAttribute('data-omo-chat-point-title') || '');
        root.__omoChatLastMessageId = 0;
        root.__omoChatInitialized = false;
        var form = root.querySelector('[data-omo-chat-popup-composer]');
        if (form) {
            form.elements.content.placeholder = String(labels.placeholder || '');
            form.querySelector('[type="submit"]').textContent = String(labels.send || '');
            if (root.__omoChatReadOnly) form.hidden = true;
        }
        var list = root.querySelector('[data-omo-chat-popup-messages]');
        if (list) list.textContent = String(labels.loading || '');
        if (!root.__omoChatReadOnly) bindComposer(root);
        loadDiscussion(root).catch(function (error) {
            if (list) list.textContent = error.message;
            notify(error.message);
        }).finally(function () {
            if (root.isConnected && !root.__omoChatReadOnly) startPolling(root);
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-omo-chat-open]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        openDiscussion(button);
    }, true);
}());
