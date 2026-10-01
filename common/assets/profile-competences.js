window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/profile-competences.js"] = function (pageConfig, pageScript) {
    (function () {
        var currentScript = pageScript;
        var fragment = currentScript ? currentScript.closest('.profile-panel__scope-fragment') : null;
        var profileDirtyKey = pageConfig.profileDirtyKey;
        var passwordFieldActionBlockedMessage = pageConfig.passwordFieldActionBlockedMessage;
        var jqueryRequiredMessage = pageConfig.jqueryRequiredMessage;

        if (!fragment) {
            return;
        }

        function decorateAdminEditForm() {
            var form = fragment.querySelector('#formulaire-edit');
            if (!form) {
                return;
            }

            Array.prototype.forEach.call(form.querySelectorAll('input, select, textarea'), function (field) {
                var type = String(field.type || '').toLowerCase();

                if (type === 'hidden' || type === 'checkbox' || type === 'radio' || type === 'button' || type === 'submit' || type === 'color' || type === 'file' || type === 'range') {
                    return;
                }

                field.classList.add('generic-form-control');
            });

            Array.prototype.forEach.call(form.querySelectorAll('[data-profile-password-field="1"]'), function (field) {
                ['paste', 'copy', 'cut', 'drop'].forEach(function (eventName) {
                    field.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        alert(passwordFieldActionBlockedMessage);
                    });
                });
            });
        }

        function initPasswordToggle() {
            var toggle = fragment.querySelector('[data-profile-password-toggle="1"]');
            var section = fragment.querySelector('[data-profile-password-section="1"]');
            if (!toggle || !section) {
                return;
            }

            function applyPasswordVisibility() {
                var isVisible = !!toggle.checked;
                section.hidden = !isVisible;

                if (isVisible) {
                    return;
                }

                Array.prototype.forEach.call(section.querySelectorAll('[data-profile-password-field="1"]'), function (field) {
                    field.value = '';
                });
            }

            toggle.addEventListener('change', applyPasswordVisibility);
            applyPasswordVisibility();
        }

        decorateAdminEditForm();
        initPasswordToggle();
        var profileForm = fragment.querySelector('#formulaire-edit');
        if (profileForm) {
            var markProfileDirty = function () {
                if (typeof window.profileMarkDirty === 'function') {
                    window.profileMarkDirty(profileDirtyKey);
                }
            };
            profileForm.addEventListener('input', markProfileDirty);
            profileForm.addEventListener('change', markProfileDirty);
        }
        if (typeof window.commonInitPasswordPolicy === 'function') {
            window.commonInitPasswordPolicy(fragment);
        }

        Array.prototype.forEach.call(fragment.querySelectorAll('[data-profile-submit-button="1"]'), function (button) {
            button.addEventListener('click', function () {
                var form = fragment.querySelector('#formulaire-edit');
                if (window.jQuery && form) {
                    window.jQuery(form).trigger('submit');
                    return;
                }

                alert(jqueryRequiredMessage);
            });
        });
    })();
};
