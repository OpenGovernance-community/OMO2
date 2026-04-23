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
    const codeInput = document.getElementById('authCodeInput');
    const codeSubmit = document.getElementById('authCodeSubmit');
    const resendLink = document.getElementById('authResendLink');
    const status = document.getElementById('authStatus');
    const copy = document.querySelector('.auth-copy');

    if (!input || !submit) {
        return;
    }

    let useOrgDomain = !!config.hasOrgDomain;
    let pendingToken = '';
    let loginRequestInFlight = false;
    let verifyRequestInFlight = false;
    const storageKey = 'commonLoginPendingToken';
    const defaultSubmitLabel = submit.textContent || 'Envoyer le code';
    const challengeSubmitLabel = challengeSubmit ? (challengeSubmit.textContent || 'Valider') : 'Valider';

    if (copy) {
        copy.textContent = 'Un code de connexion vous sera envoye par e-mail. Il reste valable 5 minutes.';
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
            input.placeholder = 'nom@domaine.ch';
            return;
        }

        if (useOrgDomain) {
            domain.style.display = 'flex';
            input.placeholder = 'username';
            if (toggle) {
                toggle.textContent = 'Utiliser une autre adresse e-mail';
            }
        } else {
            domain.style.display = 'none';
            input.placeholder = 'nom@domaine.ch';
            if (toggle) {
                toggle.textContent = "Utiliser l'adresse de l'organisation";
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
            submit.textContent = hasChallenge ? 'Envoyer un autre calcul' : defaultSubmitLabel;
        }
        if (challengeSubmit) {
            challengeSubmit.textContent = hasChallenge ? 'Valider et envoyer le code' : challengeSubmitLabel;
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
        setStatus('Envoi en cours...');

        fetch(config.loginSendPath || '/common/login_send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                    setStatus('Veuillez repondre a la question de verification.');
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
                        setStatus("Le code a peut-etre deja ete envoye. Si vous l'avez recu, saisissez-le ci-dessous.", 'success');
                    } else {
                        setStatus('Le code de connexion a ete envoye par e-mail.', 'success');
                    }
                    return;
                }

                if (data.error === 'email') {
                    setStatus('Veuillez saisir une adresse e-mail valide.', 'error');
                } else if (data.error === 'wrong_answer') {
                    setStatus('Reponse incorrecte. Merci de reessayer.', 'error');
                } else if (data.error === 'expired') {
                    setStatus('Le defi a expire. Relancez la connexion.', 'error');
                } else if (data.error === 'no_challenge') {
                    setStatus('Merci de relancer la connexion.', 'error');
                } else if (data.error === 'send_failed') {
                    setStatus("Impossible d'envoyer le code par e-mail.", 'error');
                } else {
                    setStatus('Une erreur est survenue.', 'error');
                }
            })
            .catch(function () {
                loginRequestInFlight = false;
                setLoginControlsDisabled(false);
                setStatus("Impossible d'envoyer la demande.", 'error');
            });
    }

    function verifyCode() {
        if (verifyRequestInFlight) {
            return;
        }

        const token = pendingToken || loadPendingToken();
        const code = codeInput ? codeInput.value.trim().toUpperCase() : '';

        if (!token) {
            setStatus("Demandez d'abord un nouveau code.", 'error');
            return;
        }

        if (code.length < 6) {
            setStatus('Veuillez saisir le code complet a 6 caracteres.', 'error');
            return;
        }

        if (codeSubmit) {
            codeSubmit.disabled = true;
        }
        verifyRequestInFlight = true;
        setStatus('Verification du code...');

        const body = new URLSearchParams();
        body.set('token', token);
        body.set('code', code);
        body.set('return_to', config.returnTo || '/');

        fetch(config.loginVerifyPath || '/common/login_verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body.toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                verifyRequestInFlight = false;
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
                    setStatus('Code incorrect. Il reste ' + (data.remaining_attempts || 0) + ' essai(s).', 'error');
                    return;
                }

                if (data.error === 'locked') {
                    storePendingToken('');
                    updateSendControls(true);
                    setStatus('Trop d essais. Demandez un nouveau code.', 'error');
                    return;
                }

                if (data.error === 'expired') {
                    storePendingToken('');
                    updateSendControls(true);
                    setStatus('Le code a expire. Demandez un nouveau code.', 'error');
                    return;
                }

                if (data.error === 'ip_changed') {
                    storePendingToken('');
                    updateSendControls(true);
                    setStatus('Votre reseau a change. Pour votre securite, demandez un nouveau code.', 'error');
                    return;
                }

                if (data.error === 'missing_code') {
                    setStatus('Veuillez saisir le code recu par e-mail.', 'error');
                    return;
                }

                storePendingToken('');
                setStatus('Code invalide. Demandez un nouveau code.', 'error');
            })
            .catch(function () {
                verifyRequestInFlight = false;
                if (codeSubmit) {
                    codeSubmit.disabled = false;
                }
                setStatus('Impossible de verifier le code.', 'error');
            });
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            useOrgDomain = !useOrgDomain;
            refreshMode();
        });
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

    pendingToken = loadPendingToken();
    if (pendingToken) {
        showCodeBox();
        updateSendControls(true);
        updateChallengeControls(false);
        setStatus('Saisissez le code recu par e-mail.', 'success');
    } else {
        updateSendControls(false);
        updateChallengeControls(false);
    }

    refreshMode();
})();
