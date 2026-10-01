(function () {
    function getConfig() {
        var config = window.commonTopbarConfig && window.commonTopbarConfig.notifications;
        return config && config.enabled ? config : null;
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    function markRead(data) {
        var config = getConfig();
        if (!config || !config.markReadUrl) {
            return Promise.resolve(false);
        }
        return fetch(config.markReadUrl, {
            method: 'POST',
            keepalive: true,
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({
                csrf_token: config.csrfToken,
                notification_id: Number(data.notificationId || 0),
                url: String(data.url || '')
            })
        }).then(function () {
            return true;
        }).catch(function () {
            return false;
        });
    }

    function markAllRead() {
        var config = getConfig();
        if (!config || !config.markReadUrl) {
            return Promise.resolve(false);
        }
        return fetch(config.markReadUrl, {
            method: 'POST',
            keepalive: true,
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({
                csrf_token: config.csrfToken,
                mark_all: true
            })
        }).then(function (response) {
            return response.ok;
        }).catch(function () {
            return false;
        });
    }

    function updateBadge(count) {
        var badge = document.querySelector('[data-omo-notification-badge]');
        if (!badge) {
            return;
        }
        var number = Math.max(0, Number(count) || 0);
        badge.textContent = number > 99 ? '99+' : String(number);
        badge.hidden = number === 0;
    }

    function renderInbox(payload) {
        var host = document.querySelector('[data-omo-notification-inbox]');
        if (!host) {
            return;
        }
        var items = Array.isArray(payload.notifications) ? payload.notifications : [];
        if (!items.length) {
            host.innerHTML = '<p class="omo-notification-inbox__empty">Aucune notification.</p>';
            return;
        }
        host.innerHTML = items.map(function (item) {
            var classes = 'omo-notification-inbox__item' + (item.readAt ? '' : ' is-unread');
            return '<a class="' + classes + '" href="' + escapeHtml(item.url || '#') + '" data-omo-notification-item data-notification-id="' + Number(item.id || 0) + '" data-notification-url="' + escapeHtml(item.url || '') + '">'
                + '<strong>' + escapeHtml(item.title) + '</strong>'
                + (item.body ? '<span>' + escapeHtml(item.body) + '</span>' : '')
                + '</a>';
        }).join('');
    }

    function refreshInbox() {
        var config = getConfig();
        if (!config || !config.inboxUrl) {
            return;
        }
        fetch(config.inboxUrl, {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || payload.status !== true) {
                    return;
                }
                updateBadge(payload.unreadCount);
                renderInbox(payload);
            }).catch(function () {
            });
    }

    document.addEventListener('click', function (event) {
        var markAllButton = event.target.closest('[data-omo-notification-mark-all-read]');
        if (!markAllButton) {
            return;
        }
        markAllButton.disabled = true;
        markAllRead().then(function (success) {
            markAllButton.disabled = false;
            if (success) {
                refreshInbox();
            }
        });
    });

    document.addEventListener('click', function (event) {
        var item = event.target.closest('[data-omo-notification-item]');
        if (!item) {
            return;
        }
        item.classList.remove('is-unread');
        markRead({
            notificationId: item.getAttribute('data-notification-id'),
            url: item.getAttribute('data-notification-url')
        }).then(refreshInbox);
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-topbar-menu-trigger="notifications"]')) {
            refreshInbox();
        }
    });

    window.addEventListener('DOMContentLoaded', function () {
        refreshInbox();
    });
}());
