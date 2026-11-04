var omoUiText = window.omoUiText || function (key, fallback) { return fallback; };
(function () {
    if (typeof window.__omoPopupCleanup === 'function') {
        window.__omoPopupCleanup();
    }

    const root = document.getElementById('omoTensionPopup');
    if (!root) { return; }

    const form = document.getElementById('omoTensionForm');
    const feedback = document.getElementById('omoTensionFeedback');
    const titleInput = document.getElementById('omoTensionTitle');
    const titleHint = root.querySelector('[data-omo-tension-title-hint]');
    const submitButton = document.getElementById('omoTensionSubmit');
    const cancelButton = root.querySelector('[data-omo-tension-cancel]');
    const list = document.getElementById('omoTensionList');
    const listStatus = document.getElementById('omoTensionListStatus');
    const refreshButton = root.querySelector('[data-omo-tension-refresh]');
    const texts = JSON.parse(root.getAttribute('data-texts') || '{}');
    const requests = new AbortController();
    let disposed = false;
    let loading = false;
    let reloadRequested = false;
    let saving = false;
    let openMenu = null;

    function text(key, params) {
        let value = texts[key] || key;
        Object.keys(params || {}).forEach(function (name) {
            value = value.replace('{' + name + '}', String(params[name]));
        });
        return value;
    }

    function setFeedback(message, type) {
        if (disposed) { return; }
        if (feedback) {
            feedback.textContent = '';
            feedback.hidden = true;
        }
        const notify = window.commonNotify || window.omoNotify;
        if (message && typeof notify === 'function') {
            notify(String(message), type);
        } else if (feedback && message) {
            feedback.hidden = false;
            feedback.textContent = message;
            feedback.classList.toggle('is-error', type === 'error');
            feedback.classList.toggle('is-success', type === 'success');
        }
    }

    async function request(url, options) {
        const response = await fetch(url, Object.assign({
            credentials: 'same-origin',
            signal: requests.signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }, options));
        const data = await response.json().catch(function () { return null; });
        if (!response.ok || !data || !data.status) {
            throw new Error(data && data.message ? data.message : 'request_failed');
        }
        return data;
    }

    function errorMessage(error, fallback) {
        return error && error.message && error.message !== 'request_failed' ? error.message : fallback;
    }

    function refreshTitleHint() {
        if (!titleInput || !titleHint) { return true; }
        const value = titleInput.value.trim();
        const wordCount = value ? value.split(/\s+/).filter(Boolean).length : 0;
        const isValid = wordCount <= 3;
        titleHint.textContent = wordCount > 0
            ? wordCount + omoUiText('3_mots', ' / 3 mots')
            : omoUiText('3_mots_maximum', '3 mots maximum.');
        titleHint.style.color = isValid ? '' : '#b91c1c';
        return isValid;
    }

    function closeMenu(restoreFocus) {
        if (!openMenu) { return; }
        const toggle = openMenu.querySelector('[data-omo-tension-menu]');
        openMenu.querySelector('[role="menu"]').hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (restoreFocus) { toggle.focus(); }
        openMenu = null;
    }

    function element(tag, className, value) {
        const node = document.createElement(tag);
        node.className = className;
        if (value !== undefined) { node.textContent = value; }
        return node;
    }

    function renderList(items) {
        closeMenu(false);
        list.replaceChildren();
        items.forEach(function (item) {
            const row = element('article', 'omo-tension-popup__entry generic-soft-panel');
            row.dataset.tensionId = String(item.id);
            const copy = element('div', 'omo-tension-popup__entry-copy generic-stack generic-stack--compact');
            copy.appendChild(element('h3', 'generic-card-title generic-card-title--medium', item.title));
            const date = /^\d{4}-\d{2}-\d{2}/.test(item.date || '')
                ? item.date.slice(0, 10).split('-').reverse().join('.') : '';
            copy.appendChild(element('p', 'generic-help-text', [item.holon, item.author, date].filter(Boolean).join(' · ')));
            copy.appendChild(element('p', 'omo-tension-popup__entry-description generic-description generic-description--small', item.description));
            row.appendChild(copy);
            if (item.canDelete) {
                const menu = element('div', 'omo-tension-popup__menu generic-menu');
                const toggle = element('button', 'generic-menu-toggle', '...');
                toggle.type = 'button';
                toggle.dataset.omoTensionMenu = '';
                toggle.setAttribute('aria-label', text('actions', { title: item.title }));
                toggle.setAttribute('aria-haspopup', 'menu');
                toggle.setAttribute('aria-expanded', 'false');
                const panel = element('div', 'generic-menu-panel generic-menu-panel--anchored');
                panel.id = 'omoTensionMenu-' + item.id;
                panel.setAttribute('role', 'menu');
                toggle.setAttribute('aria-controls', panel.id);
                panel.hidden = true;
                const remove = element('button', 'generic-menu-item generic-menu-item--danger', text('delete'));
                remove.type = 'button';
                remove.setAttribute('role', 'menuitem');
                remove.dataset.omoTensionDelete = String(item.id);
                remove.dataset.tensionTitle = item.title;
                panel.appendChild(remove);
                menu.append(toggle, panel);
                row.appendChild(menu);
            }
            list.appendChild(row);
        });
        listStatus.textContent = items.length ? '' : text('empty');
        listStatus.hidden = items.length > 0;
    }

    async function loadList() {
        if (!list || disposed) { return; }
        if (loading) {
            reloadRequested = true;
            return;
        }
        loading = true;
        refreshButton.disabled = true;
        list.setAttribute('aria-busy', 'true');
        listStatus.hidden = false;
        listStatus.textContent = text('loading');
        try {
            const url = new URL(root.getAttribute('data-list-url'), window.location.origin);
            url.searchParams.set('oid', root.getAttribute('data-default-oid'));
            const data = await request(url.toString());
            if (!disposed) { renderList(data.items || []); }
        } catch (error) {
            if (!disposed) {
                // A persistent load error stays next to the retry action.
                listStatus.textContent = errorMessage(error, text('load_failed'));
            }
        } finally {
            loading = false;
            if (!disposed) {
                refreshButton.disabled = false;
                list.removeAttribute('aria-busy');
                if (reloadRequested) {
                    reloadRequested = false;
                    loadList();
                }
            }
        }
    }

    async function deleteTension(button) {
        if (button.disabled || !window.confirm(text('confirm_delete', { title: button.dataset.tensionTitle }))) { return; }
        button.disabled = true;
        button.textContent = text('deleting');
        const data = new FormData();
        data.set('oid', root.getAttribute('data-default-oid'));
        data.set('id', button.dataset.omoTensionDelete);
        data.set('_csrf', root.getAttribute('data-csrf'));
        try {
            const result = await request(root.getAttribute('data-delete-url'), { method: 'POST', body: data });
            if (disposed) { return; }
            const row = button.closest('[data-tension-id]');
            closeMenu(false);
            row.remove();
            if (!list.children.length) {
                listStatus.hidden = false;
                listStatus.textContent = text('empty');
            }
            refreshButton.focus();
            setFeedback(result.message || text('deleted'), 'success');
            if (loading) { reloadRequested = true; }
        } catch (error) {
            setFeedback(errorMessage(error, text('delete_failed')), 'error');
        } finally {
            if (!disposed) {
                button.disabled = false;
                button.textContent = text('delete');
            }
        }
    }

    function onDocumentClick(event) {
        if (openMenu && !openMenu.contains(event.target)) { closeMenu(false); }
    }

    function onKeydown(event) {
        if (event.key === 'Escape' && openMenu) {
            event.preventDefault();
            event.stopPropagation();
            closeMenu(true);
        }
    }

    if (list) {
        list.addEventListener('click', function (event) {
            const toggle = event.target.closest('[data-omo-tension-menu]');
            if (toggle) {
                const menu = toggle.parentElement;
                const wasOpen = openMenu === menu;
                closeMenu(false);
                if (!wasOpen) {
                    openMenu = menu;
                    menu.querySelector('[role="menu"]').hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                    menu.querySelector('[role="menuitem"]').focus();
                }
            }
            const remove = event.target.closest('[data-omo-tension-delete]');
            if (remove) { deleteTension(remove); }
        });
        document.addEventListener('click', onDocumentClick);
        root.addEventListener('keydown', onKeydown);
        refreshButton.addEventListener('click', loadList);
        loadList();
    }

    if (titleInput) {
        titleInput.addEventListener('input', refreshTitleHint);
        refreshTitleHint();
    }
    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            if (typeof window.commonTopbarCloseModal === 'function') { window.commonTopbarCloseModal(); }
        });
    }

    if (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (saving) { return; }
            if (!refreshTitleHint()) {
                setFeedback(omoUiText('le_titre_doit_contenir_au_maximum_3_mots', 'Le titre doit contenir au maximum 3 mots.'), 'error');
                titleInput.focus();
                return;
            }
            const label = submitButton.textContent;
            saving = true;
            setFeedback('', '');
            submitButton.disabled = true;
            submitButton.textContent = omoUiText('enregistrement', 'Enregistrement...');
            form.setAttribute('aria-busy', 'true');
            try {
                const result = await request(root.getAttribute('data-submit-url'), { method: 'POST', body: new FormData(form) });
                if (disposed) { return; }
                setFeedback(result.message || omoUiText('tension_enregistree', 'Tension enregistree.'), 'success');
                titleInput.value = '';
                form.elements.description.value = '';
                refreshTitleHint();
                titleInput.focus();
                loadList();
            } catch (error) {
                setFeedback(errorMessage(error, omoUiText('impossible_d_enregistrer_la_tension', 'Impossible d enregistrer la tension.')), 'error');
            } finally {
                saving = false;
                if (!disposed) {
                    submitButton.disabled = false;
                    submitButton.textContent = label;
                    form.removeAttribute('aria-busy');
                }
            }
        });
    }

    window.__omoPopupCleanup = function () {
        disposed = true;
        requests.abort();
        document.removeEventListener('click', onDocumentClick);
        closeMenu(false);
    };
})();
