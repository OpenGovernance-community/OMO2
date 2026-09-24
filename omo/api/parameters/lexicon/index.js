window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/parameters/lexicon/index.js"] = function (pageConfig, pageScript) {
(function () {
    var root = document.querySelector('[data-omo-lexicon-editor]');
    if (!root || root.dataset.omoLexiconInitialized === '1') {
        return;
    }

    root.dataset.omoLexiconInitialized = '1';
    if (typeof window.initGenericComponents === 'function') {
        window.initGenericComponents(root);
    }
    var form = root.querySelector('[data-omo-lexicon-form]');
    var feedback = root.querySelector('[data-omo-lexicon-feedback]');
    var saveButton = root.querySelector('[data-omo-lexicon-save]');
    var resetButton = root.querySelector('[data-omo-lexicon-reset]');
    var defaultValues = {
        space_label: 'Espace',
        circle_label: 'Cercle',
        role_label: 'Rôle',
        group_label: 'Groupe',
        tension_label: 'Tension',
        tension_article: 'une',
        admin_label: 'Admin'
    };

    function setFeedback(message, kind) {
        if (!feedback) {
            return;
        }

        feedback.textContent = String(message || '');
        feedback.className = 'omo-lexicon-editor__feedback generic-feedback' + (kind ? ' is-' + kind : '');
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            Object.keys(defaultValues).forEach(function (key) {
                var input = form ? form.elements[key] : null;
                if (input) {
                    input.value = defaultValues[key];
                }
            });
            setFeedback('', '');
        });
    }

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (saveButton) {
            saveButton.disabled = true;
        }
        setFeedback('', '');

        fetch('/omo/api/parameters/lexicon/save.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== 'ok') {
                        throw new Error(payload && payload.message ? payload.message : pageConfig.message);
                    }
                    return payload;
                });
            })
            .then(function () {
                setFeedback(pageConfig.parametersLexiconStatusSaved, 'success');
                window.setTimeout(function () {
                    window.location.reload();
                }, 450);
            })
            .catch(function (error) {
                setFeedback(error && error.message ? error.message : pageConfig.message, 'error');
                if (saveButton) {
                    saveButton.disabled = false;
                }
            });
    });
}());
};
