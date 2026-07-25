(function () {
    var notificationCounter = 0;
    var defaultDuration = 10000;
    var removeAnimationDuration = 240;

    function getRegion() {
        var region = document.getElementById('commonNotifications');

        if (!region) {
            region = document.createElement('div');
            region.id = 'commonNotifications';
            region.className = 'common-notifications';
            region.setAttribute('aria-live', 'polite');
            region.setAttribute('aria-atomic', 'false');
            document.body.appendChild(region);
        }

        return region;
    }

    function normalizeOptions(typeOrOptions, options) {
        var normalized = {};

        if (typeOrOptions && typeof typeOrOptions === 'object') {
            normalized = typeOrOptions;
        } else {
            normalized.type = typeOrOptions;
            if (options && typeof options === 'object') {
                normalized = Object.assign(normalized, options);
            }
        }

        return normalized;
    }

    function normalizeType(type) {
        var normalized = String(type || 'error').toLowerCase();

        if (normalized === 'confirmation' || normalized === 'confirm' || normalized === 'success') {
            return 'success';
        }

        if (normalized === 'warning' || normalized === 'warn') {
            return 'warning';
        }

        return 'error';
    }

    function getDuration(value) {
        var duration = Number(value);

        return Number.isFinite(duration) && duration > 0 ? duration : defaultDuration;
    }

    function clearDismissTimer(notification) {
        if (notification.__commonNotificationTimer !== null && notification.__commonNotificationTimer !== undefined) {
            window.clearTimeout(notification.__commonNotificationTimer);
            notification.__commonNotificationTimer = null;
        }
    }

    function scheduleDismissal(notification) {
        clearDismissTimer(notification);

        if (notification.dataset.commonNotificationState !== 'visible' || notification.__commonNotificationPaused) {
            return;
        }

        notification.__commonNotificationStartedAt = Date.now();
        notification.__commonNotificationTimer = window.setTimeout(function () {
            dismissNotification(notification, false);
        }, Math.max(0, notification.__commonNotificationRemaining));
    }

    function pauseDismissal(notification) {
        if (notification.dataset.commonNotificationState !== 'visible' || notification.__commonNotificationPaused) {
            return;
        }

        clearDismissTimer(notification);
        notification.__commonNotificationRemaining = Math.max(
            0,
            notification.__commonNotificationRemaining - (Date.now() - notification.__commonNotificationStartedAt)
        );
        notification.__commonNotificationStartedAt = 0;
        notification.__commonNotificationPaused = true;
    }

    function resumeDismissal(notification) {
        if (notification.dataset.commonNotificationState !== 'visible' || !notification.__commonNotificationPaused) {
            return;
        }

        notification.__commonNotificationPaused = false;
        if (notification.__commonNotificationRemaining <= 0) {
            dismissNotification(notification, false);
            return;
        }

        scheduleDismissal(notification);
    }

    function dismissNotification(notification, immediately) {
        if (!notification
            || notification.dataset.commonNotificationState === 'removed'
            || notification.dataset.commonNotificationState === 'leaving') {
            return;
        }

        clearDismissTimer(notification);
        notification.__commonNotificationPaused = false;

        notification.dataset.commonNotificationState = 'leaving';
        notification.classList.remove('is-visible');
        notification.classList.add('is-leaving');

        window.setTimeout(function () {
            notification.dataset.commonNotificationState = 'removed';
            notification.remove();
        }, immediately ? 0 : removeAnimationDuration);
    }

    function notify(message, typeOrOptions, options) {
        var normalizedOptions = normalizeOptions(typeOrOptions, options);
        var text = normalizedOptions.message !== undefined ? normalizedOptions.message : message;
        var messageText = text === null || text === undefined ? '' : String(text);
        var type = normalizeType(normalizedOptions.type);
        var region;
        var notification;
        var messageNode;
        var closeButton;
        var id;

        if (messageText.trim() === '') {
            return '';
        }

        region = getRegion();
        notificationCounter += 1;
        id = 'common-notification-' + notificationCounter;

        notification = document.createElement('div');
        notification.id = id;
        notification.className = 'common-notification common-notification--' + type;
        notification.dataset.commonNotificationState = 'visible';
        notification.setAttribute('role', type === 'error' ? 'alert' : 'status');
        notification.__commonNotificationRemaining = getDuration(normalizedOptions.duration);
        notification.__commonNotificationStartedAt = 0;
        notification.__commonNotificationTimer = null;
        notification.__commonNotificationPaused = false;
        notification.addEventListener('mouseenter', function () {
            pauseDismissal(notification);
        });
        notification.addEventListener('mouseleave', function () {
            resumeDismissal(notification);
        });

        messageNode = document.createElement('div');
        messageNode.className = 'common-notification__message';
        messageNode.textContent = messageText;

        closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'common-notification__close';
        closeButton.setAttribute('aria-label', 'Fermer');
        closeButton.textContent = '\u00d7';
        closeButton.addEventListener('click', function () {
            dismissNotification(notification, false);
        });

        notification.appendChild(messageNode);
        notification.appendChild(closeButton);
        region.appendChild(notification);

        window.requestAnimationFrame(function () {
            if (notification.dataset.commonNotificationState === 'visible') {
                notification.classList.add('is-visible');
            }
        });

        scheduleDismissal(notification);

        return id;
    }

    function dismissById(id) {
        var notification = document.getElementById(String(id || ''));

        if (!notification || !notification.classList.contains('common-notification')) {
            return false;
        }

        dismissNotification(notification, false);
        return true;
    }

    window.commonNotify = notify;
    window.commonShowNotification = notify;
    window.commonDismissNotification = dismissById;
}());
