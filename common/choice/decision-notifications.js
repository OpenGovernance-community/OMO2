(function (window) {
    'use strict';

    window.omoDecisionNotifyError = function (message) {
        const text = String(message || '').trim();
        if (text === '' || typeof window.omoNotify !== 'function') {
            return false;
        }

        window.omoNotify(text, 'error');
        return true;
    };
})(window);
