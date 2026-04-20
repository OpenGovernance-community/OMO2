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
    const status = document.getElementById('authStatus');

    if (!input || !submit) {
        return;
    }

    let useOrgDomain = !!config.hasOrgDomain;

    function setStatus(message, type) {
        if (!status) return;
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
                toggle.textContent = 'Utiliser l’adresse de l’organisation';
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

    function submitLogin(answer) {
        const body = new URLSearchParams();
        body.set('email', buildEmail());
        body.set('remember', remember && remember.checked ? '1' : '0');
        body.set('return_to', config.returnTo || '/');

        if (answer !== undefined && answer !== null) {
            body.set('answer', String(answer));
        }

        submit.disabled = true;
        setStatus('Envoi en cours...');

        fetch(config.loginSendPath || '/common/login_send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                submit.disabled = false;

                if (data.challenge) {
                    challengeBox.style.display = 'flex';
                    challengeQuestion.textContent = data.challenge;
                    setStatus('Veuillez répondre à la question de vérification.');
                    return;
                }

                if (data.status === 'ok') {
                    setStatus('Un e-mail de connexion vient d’être envoyé.', 'success');
                    return;
                }

                if (data.error === 'email') {
                    setStatus('Veuillez saisir une adresse e-mail valide.', 'error');
                } else if (data.error === 'wrong_answer') {
                    setStatus('Réponse incorrecte. Merci de réessayer.', 'error');
                } else if (data.error === 'expired') {
                    setStatus('Le défi a expiré. Relancez la connexion.', 'error');
                } else if (data.error === 'no_challenge') {
                    setStatus('Merci de relancer la connexion.', 'error');
                } else {
                    setStatus('Une erreur est survenue.', 'error');
                }
            })
            .catch(function () {
                submit.disabled = false;
                setStatus('Impossible d’envoyer la demande.', 'error');
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

    if (challengeSubmit) {
        challengeSubmit.addEventListener('click', function () {
            submitLogin(challengeAnswer.value);
        });
    }

    refreshMode();
})();

