(function () {
    'use strict';
    window.omoInitRuleScopeFields = function (root, context, state) {
        root.querySelectorAll('[data-rule-scope-fields]').forEach(function (host) {
            if (context) host.dataset.ruleScopeContext = JSON.stringify(context);
            var settings = JSON.parse(host.dataset.ruleScopeContext || '{}');
            var scope = host.querySelector('[name="scope"]');
            var authority = host.querySelector('[name="IDauthority"]');
            if (context) {
                var selected = authority.value;
                while (authority.options.length > 1) authority.remove(1);
                (settings.authorities || []).forEach(function (item) {
                    var option = document.createElement('option');
                    option.value = String(item.id);
                    option.textContent = item.label;
                    authority.appendChild(option);
                });
                authority.value = selected;
            }
            if (state) {
                scope.value = state.scope || 'local';
                authority.value = state.IDauthority ? String(state.IDauthority) : '';
            }
            function sync() {
                var settings = JSON.parse(host.dataset.ruleScopeContext || '{}');
                var required = !!settings.usesAuthorities && (scope.value === 'global' || scope.value === 'descendants' || (scope.value === 'circle' && settings.isRole));
                host.querySelectorAll('[data-rule-scope-choice]').forEach(function (button) {
                    var active = button.dataset.ruleScopeChoice === scope.value;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                host.querySelectorAll('[data-rule-scope-help]').forEach(function (help) {
                    help.hidden = help.dataset.ruleScopeHelp !== scope.value;
                });
                authority.required = required;
                host.querySelector('[data-rule-authority-field]').hidden = !settings.usesAuthorities;
                host.querySelector('[data-rule-authority-required]').hidden = !required;
                host.querySelector('[data-rule-authority-unavailable]').hidden = !required || authority.options.length > 1;
            }
            if (!host.dataset.ruleScopeReady) {
                host.dataset.ruleScopeReady = '1';
                host.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-rule-scope-choice]');
                    if (!button) return;
                    scope.value = button.dataset.ruleScopeChoice;
                    sync();
                    scope.dispatchEvent(new Event('change', {bubbles: true}));
                });
                scope.addEventListener('change', sync);
            }
            sync();
        });
    };
})();
