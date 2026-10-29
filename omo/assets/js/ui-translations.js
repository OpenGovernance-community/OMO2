(function () {
    'use strict';
    var texts = window.omoUiTranslations || (window.omoConfig && window.omoConfig.uiTexts) || null;

    window.omoUiText = function (key, fallback, variables) {
        var text = texts && Object.prototype.hasOwnProperty.call(texts, key) ? texts[key] : fallback;
        if (text && typeof text === 'object') {
            text = variables && Number(variables.count) === 1 ? (text.one || text.other) : (text.other || text.one);
        }
        return String(text == null ? key : text).replace(/\{([a-zA-Z0-9_]+)\}/g, function (placeholder, name) {
            return variables && Object.prototype.hasOwnProperty.call(variables, name) ? String(variables[name]) : placeholder;
        });
    };
    window.omoUiHtml = function (key, fallback, variables) {
        return window.omoUiText(key, fallback, variables).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    };

    // The main OMO page hydrates the bundle before loading its application scripts.
    // Standalone consumers use the same shared source through the JSON endpoint.
    window.omoUiReady = texts ? Promise.resolve() : fetch('/common/jstranslation/omo_ui.php', {credentials: 'same-origin'})
        .then(function (response) { if (!response.ok) throw new Error('translation_unavailable'); return response.json(); })
        .then(function (payload) { if (payload && typeof payload === 'object' && !Array.isArray(payload)) texts = payload; })
        .catch(function () { /* Keep each control's local French fallback available. */ });
}());
