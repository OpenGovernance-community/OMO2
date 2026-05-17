(function () {
    const config = window.commonLoginConfig || {};
    const input = document.getElementById('authEmailInput');
    const domain = document.getElementById('authEmailDomain');
    const toggle = document.getElementById('authToggleMode');
    const remember = document.getElementById('authRememberMe');
    const submit = document.getElementById('authLoginSubmit');
    const challengeBox = document.getElementById('authChallengeBox');
    const challengeQuestion = document.getElementById('authChallengeQuestion');
    const challengeAnswer = document.getElementById('authChallengeAnswer');
    const challengeSubmit = document.getElementById('authChallengeSubmit');
    const codeBox = document.getElementById('authCodeBox');
    const codeIntro = codeBox ? codeBox.querySelector('p') : null;
    const codeInput = document.getElementById('authCodeInput');
    const codeSubmit = document.getElementById('authCodeSubmit');
    const verifyForm = document.getElementById('authVerifyForm');
    const verifyToken = document.getElementById('authVerifyToken');
    const verifyCodeInput = document.getElementById('authVerifyCode');
    const resendLink = document.getElementById('authResendLink');
    const status = document.getElementById('authStatus');
    const copy = document.querySelector('.auth-copy');
    const languageSelect = document.querySelector('[data-auth-language-select]');

    if (!input || !submit) {
        return;
    }

    const authTexts = {
        'auth.button.resend_code': 'Envoyer un nouveau code',
        'auth.button.send_code': 'Envoyer le code',
        'auth.button.send_other_challenge': 'Envoyer un autre calcul',
        'auth.button.validate': 'Valider',
        'auth.button.validate_and_send_code': 'Valider et envoyer le code',
        'auth.button.validate_code': 'Valider le code',
        'auth.challenge.answer_placeholder': 'Votre reponse',
        'auth.code.instructions': 'Entrez le code recu par e-mail sur cet appareil.',
        'auth.code.placeholder': 'ABC123',
        'auth.copy.login_code': 'Un code de connexion vous sera envoye par e-mail. Il reste valable 5 minutes.',
        'auth.error.ask_new_code_first': "Demandez d'abord un nouveau code.",
        'auth.error.challenge_expired': 'Le defi a expire. Relancez la connexion.',
        'auth.error.enter_full_code': 'Veuillez saisir le code complet a 6 caracteres.',
        'auth.error.expired': 'Le code a expire. Demandez un nouveau code.',
        'auth.error.invalid_code': 'Code invalide. Demandez un nouveau code.',
        'auth.error.invalid_email': 'Veuillez saisir une adresse e-mail valide.',
        'auth.error.ip_changed': 'Votre reseau a change. Pour votre securite, demandez un nouveau code.',
        'auth.error.locked': 'Trop d essais. Demandez un nouveau code.',
        'auth.error.missing_code': 'Veuillez saisir le code recu par e-mail.',
        'auth.error.request_failed': "Impossible d'envoyer la demande.",
        'auth.error.restart_login': 'Merci de relancer la connexion.',
        'auth.error.send_failed': "Impossible d'envoyer le code par e-mail.",
        'auth.error.unexpected': 'Une erreur est survenue.',
        'auth.error.verify_failed': 'Impossible de verifier le code.',
        'auth.error.wrong_answer': 'Reponse incorrecte. Merci de reessayer.',
        'auth.error.wrong_code': 'Code incorrect. Il reste {count} essai(s).',
        'auth.placeholder.full_email': 'nom@domaine.ch',
        'auth.placeholder.username': 'username',
        'auth.status.answer_verification': 'Veuillez repondre a la question de verification.',
        'auth.status.code_pending': "Le code a peut-etre deja ete envoye. Si vous l'avez recu, saisissez-le ci-dessous.",
        'auth.status.code_sent': 'Le code de connexion a ete envoye par e-mail.',
        'auth.status.enter_received_code': 'Saisissez le code recu par e-mail.',
        'auth.status.sending': 'Envoi en cours...',
        'auth.status.verifying_code': 'Verification du code...',
        'auth.toggle.use_org_email': "Utiliser l'adresse de l'organisation",
        'auth.toggle.use_other_email': 'Utiliser une autre adresse e-mail'
    };

    let useOrgDomain = !!config.hasOrgDomain;
    let pendingToken = '';
    let loginRequestInFlight = false;
    const storageKey = 'commonLoginPendingToken';
    const translationsPath = config.authTranslationsPath || '/common/jstranslation/auth_js.php';

    function interpolate(text, variables) {
        let result = typeof text === 'string' ? text : '';
        Object.keys(variables || {}).forEach(function (name) {
            result = result.replaceAll('{' + name + '}', String(variables[name]));
        });
        return result;
    }

    function t(key, variables) {
        return interpolate(authTexts[key] || '', variables || {});
    }

    function mergeTranslations(payload) {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        Object.keys(payload).forEach(function (key) {
            if (typeof payload[key] === 'string') {
                authTexts[key] = payload[key];
            }
        });
    }

    function getInitialErrorMessage() {
        if (config.initialError === 'wrong_code') {
            return {
                message: t('auth.error.wrong_code', {
                    count: config.initialRemainingAttempts || 0
                }),
                type: 'error'
            };
        }

        if (config.initialError === 'locked') {
            return {
                message: t('auth.error.locked'),
                type: 'error'
            };
        }

        if (config.initialError === 'expired') {
            return {
                message: t('auth.error.expired'),
                type: 'error'
            };
        }

        if (config.initialError === 'ip_changed') {
            return {
                message: t('auth.error.ip_changed'),
                type: 'error'
            };
        }

        if (config.initialError === 'missing_code') {
            return {
                message: t('auth.error.missing_code'),
                type: 'error'
            };
        }

        if (config.initialError === 'invalid') {
            return {
                message: t('auth.error.invalid_code'),
                type: 'error'
            };
        }

        return null;
    }

    function setStatus(message, type) {
        if (!status) {
            return;
        }
        status.textContent = message || '';
        status.className = 'auth-status' + (type ? ' ' + type : '');
    }

    function refreshMode() {
        if (!domain) {
            useOrgDomain = false;
            input.placeholder = t('auth.placeholder.full_email');
            return;
        }

        if (useOrgDomain) {
            domain.style.display = 'flex';
            input.placeholder = t('auth.placeholder.username');
            if (toggle) {
                toggle.textContent = t('auth.toggle.use_other_email');
            }
        } else {
            domain.style.display = 'none';
            input.placeholder = t('auth.placeholder.full_email');
            if (toggle) {
                toggle.textContent = t('auth.toggle.use_org_email');
            }
        }
    }

    function buildEmail() {
        let value = (input.value || '').trim();
        if (useOrgDomain && config.orgDomain) {
            value = value.replace(/\s+/g, '');
            if (value !== '' && value.indexOf('@') === -1) {
                value += '@' + config.orgDomain;
            }
        }
        return value;
    }

    function storePendingToken(token) {
        pendingToken = token || '';
        if (!window.sessionStorage) {
            return;
        }

        if (pendingToken) {
            window.sessionStorage.setItem(storageKey, pendingToken);
        } else {
            window.sessionStorage.removeItem(storageKey);
        }
    }

    function loadPendingToken() {
        if (!window.sessionStorage) {
            return '';
        }

        return window.sessionStorage.getItem(storageKey) || '';
    }

    function showCodeBox() {
        if (codeBox) {
            codeBox.style.display = 'flex';
        }
        if (codeInput) {
            codeInput.focus();
        }
    }

    function updateChallengeControls(hasChallenge) {
        if (submit) {
            submit.textContent = hasChallenge ? t('auth.button.send_other_challenge') : t('auth.button.send_code');
        }
        if (challengeSubmit) {
            challengeSubmit.textContent = hasChallenge ? t('auth.button.validate_and_send_code') : t('auth.button.validate');
        }
    }

    function updateSendControls(hasPendingToken) {
        if (submit) {
            submit.style.display = hasPendingToken ? 'none' : 'block';
        }
        if (resendLink) {
            resendLink.style.display = hasPendingToken ? 'inline-flex' : 'none';
        }
    }

    function hideChallengeBox() {
        if (challengeBox) {
            challengeBox.style.display = 'none';
        }
        updateChallengeControls(false);
    }

    function setLoginControlsDisabled(disabled) {
        if (submit) {
            submit.disabled = !!disabled;
        }
        if (challengeSubmit) {
            challengeSubmit.disabled = !!disabled;
        }
        if (resendLink) {
            resendLink.disabled = !!disabled;
        }
    }

    function refreshStaticTexts() {
        if (copy) {
            copy.textContent = t('auth.copy.login_code');
        }
        if (codeIntro) {
            codeIntro.textContent = t('auth.code.instructions');
        }
        if (challengeAnswer) {
            challengeAnswer.placeholder = t('auth.challenge.answer_placeholder');
        }
        if (codeInput) {
            codeInput.placeholder = t('auth.code.placeholder');
        }
        if (codeSubmit) {
            codeSubmit.textContent = t('auth.button.validate_code');
        }
        if (resendLink) {
            resendLink.textContent = t('auth.button.resend_code');
        }

        refreshMode();
        updateChallengeControls(!!(challengeBox && challengeBox.style.display !== 'none'));
    }

    function refreshCurrentStatus() {
        if (!pendingToken) {
            return;
        }

        const initialError = getInitialErrorMessage();
        if (initialError) {
            setStatus(initialError.message, initialError.type);
        } else {
            setStatus(t('auth.status.enter_received_code'), 'success');
        }
    }

    function loadTranslations() {
        fetch(translationsPath, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load auth translations.');
                }
                return response.json();
            })
            .then(function (data) {
                mergeTranslations(data);
                refreshStaticTexts();
                refreshCurrentStatus();
            })
            .catch(function () {
                // Keep built-in French fallbacks when the translation endpoint is unavailable.
            });
    }

    function handleLanguageChange() {
        if (!languageSelect) {
            return;
        }

        if (typeof window.sharedSetLanguagePreference === 'function') {
            window.sharedSetLanguagePreference(languageSelect.value, true);
            return;
        }

        document.cookie = [
            'lang=' + encodeURIComponent(String(languageSelect.value || '').toLowerCase()),
            'path=/',
            'max-age=' + String(365 * 24 * 60 * 60),
            'SameSite=Lax'
        ].join('; ');
        window.location.reload();
    }

    function submitLogin(answer) {
        if (loginRequestInFlight) {
            return;
        }

        const body = new URLSearchParams();
        body.set('email', buildEmail());
        body.set('remember', remember && remember.checked ? '1' : '0');
        body.set('return_to', config.returnTo || '/');

        if (answer !== undefined && answer !== null) {
            body.set('answer', String(answer));
        }

        loginRequestInFlight = true;
        setLoginControlsDisabled(true);
        setStatus(t('auth.status.sending'));

        fetch(config.loginSendPath || '/common/login_send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body.toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                loginRequestInFlight = false;
                setLoginControlsDisabled(false);

                if (data.challenge) {
                    if (challengeBox) {
                        challengeBox.style.display = 'flex';
                    }
                    if (challengeQuestion) {
                        challengeQuestion.textContent = data.challenge;
                    }
                    if (challengeAnswer) {
                        challengeAnswer.focus();
                        challengeAnswer.select();
                    }
                    updateChallengeControls(true);
                    setStatus(t('auth.status.answer_verification'));
                    return;
                }

                if ((data.status === 'code_sent' || data.status === 'code_pending') && data.request_token) {
                    storePendingToken(data.request_token);
                    hideChallengeBox();
                    if (challengeAnswer) {
                        challengeAnswer.value = '';
                    }
                    showCodeBox();
                    updateSendControls(true);
                    if (data.status === 'code_pending') {
                        setStatus(t('auth.status.code_pending'), 'success');
                    } else {
                        setStatus(t('auth.status.code_sent'), 'success');
                    }
                    return;
                }

                if (data.error === 'email') {
                    setStatus(t('auth.error.invalid_email'), 'error');
                } else if (data.error === 'wrong_answer') {
                    setStatus(t('auth.error.wrong_answer'), 'error');
                } else if (data.error === 'expired') {
                    setStatus(t('auth.error.challenge_expired'), 'error');
                } else if (data.error === 'no_challenge') {
                    setStatus(t('auth.error.restart_login'), 'error');
                } else if (data.error === 'send_failed') {
                    setStatus(t('auth.error.send_failed') + (data.mail_error ? ' ' + data.mail_error : ''), 'error');
                } else {
                    setStatus(t('auth.error.unexpected'), 'error');
                }
            })
            .catch(function () {
                loginRequestInFlight = false;
                setLoginControlsDisabled(false);
                setStatus(t('auth.error.request_failed'), 'error');
            });
    }

    function verifyCode() {
        const token = pendingToken || loadPendingToken();
        const code = codeInput ? codeInput.value.trim().toUpperCase() : '';

        if (!token) {
            setStatus(t('auth.error.ask_new_code_first'), 'error');
            return;
        }

        if (code.length < 6) {
            setStatus(t('auth.error.enter_full_code'), 'error');
            return;
        }

        setStatus(t('auth.status.verifying_code'));

        if (verifyForm && verifyToken && verifyCodeInput) {
            verifyToken.value = token;
            verifyCodeInput.value = code;
            storePendingToken('');
            verifyForm.submit();
            return;
        }

        if (codeSubmit) {
            codeSubmit.disabled = true;
        }

        const body = new URLSearchParams();
        body.set('token', token);
        body.set('code', code);
        body.set('return_to', config.returnTo || '/');

        fetch(config.loginVerifyPath || '/common/login_verify.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body.toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (codeSubmit) {
                    codeSubmit.disabled = false;
                }

                if (data.status === 'ok') {
                    storePendingToken('');
                    updateSendControls(false);
                    window.location.href = data.redirect_to || (config.returnTo || '/');
                    return;
                }

                if (data.error === 'wrong_code') {
                    setStatus(t('auth.error.wrong_code', {
                        count: data.remaining_attempts || 0
                    }), 'error');
                    return;
                }

                if (data.error === 'locked') {
                    storePendingToken('');
                    updateSendControls(true);
                    setStatus(t('auth.error.locked'), 'error');
                    return;
                }

                if (data.error === 'expired') {
                    storePendingToken('');
                    updateSendControls(true);
                    setStatus(t('auth.error.expired'), 'error');
                    return;
                }

                if (data.error === 'ip_changed') {
                    storePendingToken('');
                    updateSendControls(true);
                    setStatus(t('auth.error.ip_changed'), 'error');
                    return;
                }

                if (data.error === 'missing_code') {
                    setStatus(t('auth.error.missing_code'), 'error');
                    return;
                }

                storePendingToken('');
                setStatus(t('auth.error.invalid_code'), 'error');
            })
            .catch(function () {
                if (codeSubmit) {
                    codeSubmit.disabled = false;
                }
                setStatus(t('auth.error.verify_failed'), 'error');
            });
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            useOrgDomain = !useOrgDomain;
            refreshMode();
        });
    }

    if (languageSelect) {
        languageSelect.addEventListener('change', handleLanguageChange);
    }

    submit.addEventListener('click', function () {
        submitLogin(null);
    });

    if (resendLink) {
        resendLink.addEventListener('click', function () {
            submitLogin(null);
        });
    }

    if (challengeSubmit) {
        challengeSubmit.addEventListener('click', function () {
            submitLogin(challengeAnswer ? challengeAnswer.value : '');
        });
    }

    if (challengeAnswer) {
        challengeAnswer.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitLogin(challengeAnswer.value);
            }
        });
    }

    if (codeSubmit) {
        codeSubmit.addEventListener('click', verifyCode);
    }

    if (input) {
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitLogin(null);
            }
        });
    }

    if (codeInput) {
        codeInput.addEventListener('input', function () {
            codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
        });

        codeInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                verifyCode();
            }
        });
    }

    pendingToken = config.initialPendingToken || loadPendingToken();
    if (config.initialPendingToken) {
        storePendingToken(config.initialPendingToken);
    }
    if (pendingToken) {
        showCodeBox();
        updateSendControls(true);
        updateChallengeControls(false);
        refreshCurrentStatus();
    } else {
        updateSendControls(false);
        updateChallengeControls(false);
    }

    refreshStaticTexts();
    loadTranslations();
})();
