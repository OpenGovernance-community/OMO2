var brandProductName = 'Collabora Online Development Edition (CODE)';
var brandProductURL = 'https://www.collaboraonline.com/code/';
var brandProductFAQURL = 'https://www.collaboraonline.com/code/#code-scalability';

(function () {
    'use strict';

    var integrationVariables = null;

    function readIntegrationVariables() {
        var source = document.getElementById('init-css-vars');
        if (!source || !source.value) {
            return [];
        }

        try {
            var stylesheet = window.atob(source.value);
            var declarations = stylesheet.match(/:root\s*\{([^}]*)\}/);
            if (!declarations) {
                return [];
            }

            return declarations[1].split(';').map(function (declaration) {
                var separator = declaration.indexOf(':');
                if (separator === -1) {
                    return null;
                }

                var name = declaration.slice(0, separator).trim();
                var value = declaration.slice(separator + 1).replace(/\s*!important\s*$/i, '').trim();
                return name.indexOf('--') === 0 && value !== '' ? {name: name, value: value} : null;
            }).filter(Boolean);
        } catch (error) {
            return [];
        }
    }

    function applyIntegrationVariables() {
        if (integrationVariables === null) {
            integrationVariables = readIntegrationVariables();
        }

        integrationVariables.forEach(function (variable) {
            document.documentElement.style.setProperty(variable.name, variable.value, 'important');
        });
    }

    applyIntegrationVariables();
    document.addEventListener('DOMContentLoaded', applyIntegrationVariables);
    window.addEventListener('load', applyIntegrationVariables);

    new MutationObserver(function (mutations) {
        if (mutations.some(function (mutation) {
            return mutation.type === 'attributes';
        })) {
            applyIntegrationVariables();
        }
    }).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme', 'data-bg-theme', 'data-doctype']
    });
}());
