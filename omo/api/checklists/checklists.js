(function () {
    'use strict';

    var root = document.getElementById('omo-control-lists-root');
    if (!root || root.dataset.controlListsReady === '1') { return; }
    root.dataset.controlListsReady = '1';
    var drawer = root.querySelector('[data-control-drawer]');
    var drawerBody = root.querySelector('[data-control-drawer-body]');
    var drawerTitle = root.querySelector('[data-control-drawer-title]');
    var drawerDescription = root.querySelector('[data-control-drawer-description]');
    var requestToken = 0;
    var needsRefresh = false;

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function updateSchedule(form) {
        var frequency = form.querySelector('[data-control-frequency]');
        var schedule = form.querySelector('[data-control-schedule]');
        var options;
        var selected;
        if (!frequency || !schedule) { return; }
        try { options = JSON.parse(form.getAttribute('data-control-schedule-options') || '{}'); } catch (error) { options = {}; }
        selected = schedule.getAttribute('data-selected-value') || schedule.value;
        schedule.innerHTML = '';
        (options[frequency.value] || []).forEach(function (option) {
            var element = document.createElement('option');
            element.value = String(option.value || '');
            element.textContent = String(option.label || '');
            schedule.appendChild(element);
        });
        if (Array.prototype.some.call(schedule.options, function (option) { return option.value === selected; })) { schedule.value = selected; }
        schedule.removeAttribute('data-selected-value');
    }

    function openDrawer(url) {
        if (!url || !drawer || !drawerBody) { return; }
        var token = ++requestToken;
        drawerBody.innerHTML = '<div class="omo-empty-state">Chargement...</div>';
        drawer.hidden = false;
        window.requestAnimationFrame(function () { drawer.classList.add('is-open'); });
        fetch(resolveUrl(url), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, cache: 'no-store'})
            .then(function (response) { if (!response.ok) { throw new Error('load_failed'); } return response.text(); })
            .then(function (html) {
                if (token !== requestToken) { return; }
                drawerBody.innerHTML = html;
                var content = drawerBody.firstElementChild;
                if (content) {
                    if (drawerTitle && content.getAttribute('data-control-drawer-title')) { drawerTitle.textContent = content.getAttribute('data-control-drawer-title'); }
                    if (drawerDescription) { drawerDescription.textContent = content.getAttribute('data-control-drawer-description') || ''; }
                }
                drawerBody.querySelectorAll('[data-control-task-form]').forEach(updateSchedule);
                if (typeof window.initGenericComponents === 'function') { window.initGenericComponents(drawerBody); }
            })
            .catch(function () { if (token === requestToken) { drawerBody.innerHTML = '<div class="omo-empty-state">Impossible de charger cet élément.</div>'; } });
    }

    function refreshRoot() {
        if (!needsRefresh) { return; }
        needsRefresh = false;
        if (typeof window.omoReplaceFetchedPanelRoot === 'function') {
            window.omoReplaceFetchedPanelRoot({rootSelector: '#omo-control-lists-root', currentRoot: root, url: resolveUrl(root.getAttribute('data-control-current-url') || '')});
        } else {
            window.location.reload();
        }
    }

    function closeDrawer() {
        if (!drawer) { return; }
        requestToken++;
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (drawer.classList.contains('is-open')) { return; }
            drawer.hidden = true;
            drawerBody.innerHTML = '';
            refreshRoot();
        }, 180);
    }

    function submitAction(action, id, element) {
        var confirmText = element.getAttribute('data-control-confirm');
        if (confirmText && !window.confirm(confirmText)) { return; }
        var data = new FormData();
        data.append('control_action', action);
        data.append('id', id);
        var oid = new URLSearchParams(window.location.search).get('oid') || root.getAttribute('data-control-oid') || '';
        var url = drawerBody && drawerBody.querySelector('input[name="oid"]') ? drawerBody.querySelector('input[name="oid"]').value : '';
        data.append('oid', url || oid);
        var cid = drawerBody && drawerBody.querySelector('input[name="cid"]') ? drawerBody.querySelector('input[name="cid"]').value : '';
        data.append('cid', cid);
        fetch('/omo/api/checklists/action.php', {method: 'POST', body: data, credentials: 'same-origin'})
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (!result.status) { window.alert(result.message || 'Action impossible.'); return; }
                if (action.indexOf('delete_') === 0) { needsRefresh = true; }
                if (result.detailUrl) { openDrawer(result.detailUrl); }
            })
            .catch(function () { window.alert('Action impossible.'); });
    }

    root.addEventListener('click', function (event) {
        var close = event.target.closest('[data-control-close]');
        if (close) { event.preventDefault(); closeDrawer(); return; }
        var open = event.target.closest('[data-control-open-url]');
        if (open) { event.preventDefault(); openDrawer(open.getAttribute('data-control-open-url')); return; }
        var action = event.target.closest('[data-control-post-action]');
        if (action) { event.preventDefault(); submitAction(action.getAttribute('data-control-post-action'), action.getAttribute('data-control-id'), action); }
    });

    root.addEventListener('change', function (event) {
        if (event.target.matches('[data-control-frequency]')) { updateSchedule(event.target.closest('[data-control-task-form]')); }
    });

    root.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-control-form]');
        if (!form) { return; }
        event.preventDefault();
        if (!form.reportValidity()) { return; }
        var feedback = form.querySelector('[data-control-feedback]');
        var formData = new FormData(form);
        var usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';
        var submitButton = form.querySelector('[type="submit"]');
        if (usesSharedPendingState && !window.omoBeginPendingAction(form)) { return; }
        if (!usesSharedPendingState && submitButton) { submitButton.disabled = true; }
        fetch(form.action, {method: 'POST', body: formData, credentials: 'same-origin'})
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (feedback) { feedback.textContent = result.message || ''; feedback.classList.toggle('is-error', !result.status); }
                if (result.status && result.detailUrl) { openDrawer(result.detailUrl); }
            })
            .catch(function () { if (feedback) { feedback.textContent = 'Enregistrement impossible.'; feedback.classList.add('is-error'); } })
            .finally(function () {
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') { window.omoEndPendingAction(form); }
                else if (submitButton) { submitButton.disabled = false; }
            });
    });

    root.addEventListener('keydown', function (event) {
        var card = event.target.closest('.omo-control-list-card[role="button"]');
        if (card && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); openDrawer(card.getAttribute('data-control-open-url')); }
    });
}());
