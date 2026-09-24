window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/auto-install.js"] = function (pageConfig, pageScript) {
        (function () {
            document.querySelectorAll('[data-auto-install-flash]').forEach(function (flash) {
                var closeButton = flash.querySelector('[data-auto-install-flash-close]');
                var dismiss = function () {
                    flash.remove();
                };

                if (closeButton) {
                    closeButton.addEventListener('click', dismiss);
                }

                var autoDismissMs = parseInt(flash.getAttribute('data-auto-dismiss-ms') || '0', 10);
                if (autoDismissMs > 0) {
                    window.setTimeout(dismiss, autoDismissMs);
                }
            });

            var passwordInput = document.getElementById('INSTALL_ADMIN_PASSWORD');
            var emailInput = document.getElementById('INSTALL_ADMIN_EMAIL');
            var confirmInput = document.getElementById('INSTALL_ADMIN_PASSWORD_CONFIRM');
            var statusNode = document.querySelector('[data-password-status]');
            var matchNode = document.querySelector('[data-password-match]');
            var ruleNodes = document.querySelectorAll('[data-password-rule]');

            if (!passwordInput || !statusNode || ruleNodes.length === 0) {
                return;
            }

            function evaluatePassword(password, email) {
                var emailLocalPart = '';
                var atIndex = email.indexOf('@');

                if (atIndex > 0) {
                    emailLocalPart = email.slice(0, atIndex).toLowerCase();
                }

                return {
                    length: password.length >= 12,
                    lower: /[a-z]/.test(password),
                    upper: /[A-Z]/.test(password),
                    digit: /\d/.test(password),
                    special: /[^a-zA-Z0-9]/.test(password),
                    email: emailLocalPart.length < 4 || password.toLowerCase().indexOf(emailLocalPart) === -1
                };
            }

            function setNodeState(node, isValid, isInvalid) {
                node.classList.toggle('is-valid', isValid);
                node.classList.toggle('is-invalid', isInvalid);
            }

            function updatePasswordUi() {
                var password = passwordInput.value || '';
                var email = emailInput ? (emailInput.value || '') : '';
                var checks = evaluatePassword(password, email);
                var requiredKeys = ['length', 'lower', 'upper', 'digit', 'special'];
                var isEmpty = password.length === 0;
                var isValid = requiredKeys.every(function (key) {
                    return checks[key];
                });

                ruleNodes.forEach(function (node) {
                    var ruleName = node.getAttribute('data-password-rule');
                    var passed = !!checks[ruleName];
                    setNodeState(node, !isEmpty && passed, !isEmpty && !passed);
                });

                if (isEmpty) {
                    statusNode.textContent = 'Le mot de passe doit respecter les criteres ci-dessous.';
                    setNodeState(statusNode, false, false);
                } else if (isValid) {
                    statusNode.textContent = 'Mot de passe OK.';
                    setNodeState(statusNode, true, false);
                } else {
                    statusNode.textContent = 'Mot de passe encore incomplet.';
                    setNodeState(statusNode, false, true);
                }

                if (matchNode && confirmInput) {
                    var confirmation = confirmInput.value || '';

                    if (confirmation === '') {
                        matchNode.textContent = 'Retapez le meme mot de passe pour confirmation.';
                        setNodeState(matchNode, false, false);
                    } else if (confirmation === password) {
                        matchNode.textContent = 'Confirmation OK.';
                        setNodeState(matchNode, true, false);
                    } else {
                        matchNode.textContent = 'La confirmation ne correspond pas encore.';
                        setNodeState(matchNode, false, true);
                    }
                }
            }

            passwordInput.addEventListener('input', updatePasswordUi);
            if (emailInput) {
                emailInput.addEventListener('input', updatePasswordUi);
            }
            if (confirmInput) {
                confirmInput.addEventListener('input', updatePasswordUi);
            }

            updatePasswordUi();

            if (pageConfig.shouldFocusVerificationCode) {
            var verificationCodeInput = document.getElementById('INSTALL_MAIL_VERIFICATION_CODE');
            if (verificationCodeInput) {
                window.setTimeout(function () {
                    verificationCodeInput.focus();
                    verificationCodeInput.select();
                }, 0);
            }
            }
        })();
};
