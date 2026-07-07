(function () {
    function parseRequiredKeys(value) {
        return String(value || 'length,lower,upper,digit,special')
            .split(',')
            .map(function (item) {
                return item.trim();
            })
            .filter(function (item) {
                return item !== '';
            });
    }

    function evaluatePassword(password, email, minLength) {
        var normalizedPassword = String(password || '');
        var normalizedEmail = String(email || '').toLowerCase();
        var emailLocalPart = '';
        var atIndex = normalizedEmail.indexOf('@');

        if (atIndex > 0) {
            emailLocalPart = normalizedEmail.slice(0, atIndex);
        }

        return {
            length: normalizedPassword.length >= minLength,
            lower: /[a-z]/.test(normalizedPassword),
            upper: /[A-Z]/.test(normalizedPassword),
            digit: /\d/.test(normalizedPassword),
            special: /[^a-zA-Z0-9]/.test(normalizedPassword),
            email: emailLocalPart.length < 4 || normalizedPassword.toLowerCase().indexOf(emailLocalPart) === -1
        };
    }

    function setNodeState(node, isValid, isInvalid) {
        if (!node) {
            return;
        }

        node.classList.toggle('is-valid', !!isValid);
        node.classList.toggle('is-invalid', !!isInvalid);
    }

    function resolveScopedNode(scope, selector) {
        if (!selector) {
            return null;
        }

        if (scope && typeof scope.querySelector === 'function') {
            var localNode = scope.querySelector(selector);
            if (localNode) {
                return localNode;
            }
        }

        return document.querySelector(selector);
    }

    function initializePanel(panel, scope) {
        if (!panel || panel.getAttribute('data-password-policy-bound') === '1') {
            return;
        }

        var passwordInput = resolveScopedNode(scope, panel.getAttribute('data-password-policy-password-selector'));
        var confirmInput = resolveScopedNode(scope, panel.getAttribute('data-password-policy-confirm-selector'));
        var emailInput = resolveScopedNode(scope, panel.getAttribute('data-password-policy-email-selector'));
        var statusNode = panel.querySelector('[data-password-status]');
        var matchNode = panel.querySelector('[data-password-match]');
        var ruleNodes = panel.querySelectorAll('[data-password-rule]');
        var minLength = parseInt(panel.getAttribute('data-password-policy-min-length') || '12', 10);
        var requiredKeys = parseRequiredKeys(panel.getAttribute('data-password-policy-required-keys'));
        var emailValue = panel.getAttribute('data-password-policy-email-value') || '';

        if (!passwordInput || !statusNode || ruleNodes.length === 0) {
            return;
        }

        if (!(minLength > 0)) {
            minLength = 12;
        }

        function getEmailValue() {
            if (emailInput) {
                return emailInput.value || '';
            }

            return emailValue;
        }

        function updatePasswordUi() {
            var password = passwordInput.value || '';
            var checks = evaluatePassword(password, getEmailValue(), minLength);
            var isEmpty = password.length === 0;
            var isValid = requiredKeys.every(function (key) {
                return !!checks[key];
            });

            ruleNodes.forEach(function (node) {
                var ruleName = node.getAttribute('data-password-rule');
                var passed = !!checks[ruleName];
                setNodeState(node, !isEmpty && passed, !isEmpty && !passed);
            });

            if (isEmpty) {
                statusNode.textContent = panel.getAttribute('data-password-policy-status-empty') || '';
                setNodeState(statusNode, false, false);
            } else if (isValid) {
                statusNode.textContent = panel.getAttribute('data-password-policy-status-valid') || '';
                setNodeState(statusNode, true, false);
            } else {
                statusNode.textContent = panel.getAttribute('data-password-policy-status-invalid') || '';
                setNodeState(statusNode, false, true);
            }

            if (matchNode && confirmInput) {
                var confirmation = confirmInput.value || '';

                if (confirmation === '') {
                    matchNode.textContent = panel.getAttribute('data-password-policy-match-empty') || '';
                    setNodeState(matchNode, false, false);
                } else if (confirmation === password) {
                    matchNode.textContent = panel.getAttribute('data-password-policy-match-valid') || '';
                    setNodeState(matchNode, true, false);
                } else {
                    matchNode.textContent = panel.getAttribute('data-password-policy-match-invalid') || '';
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

        panel.setAttribute('data-password-policy-bound', '1');
        updatePasswordUi();
    }

    function init(scope) {
        var root = scope && typeof scope.querySelectorAll === 'function' ? scope : document;
        root.querySelectorAll('[data-password-policy]').forEach(function (panel) {
            initializePanel(panel, root);
        });
    }

    window.commonInitPasswordPolicy = init;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init(document);
        });
    } else {
        init(document);
    }
})();
