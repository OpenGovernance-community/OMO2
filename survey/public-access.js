window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/survey/public-access.js"] = function (pageConfig, pageScript) {
    (function () {
        var labels = pageConfig.labels;
        var form = document.getElementById('surveyPublicAccessForm');
        var action = document.getElementById('surveyPublicAccessAction');
        var codeRow = document.getElementById('surveyPublicAccessCodeRow');
        var code = document.getElementById('surveyPublicAccessCode');
        var feedback = document.getElementById('surveyPublicAccessFeedback');
        var sendActions = document.getElementById('surveyPublicAccessSendActions');
        var verifyActions = document.getElementById('surveyPublicAccessVerifyActions');
        var send = document.getElementById('surveyPublicAccessSend');
        var resend = document.getElementById('surveyPublicAccessResend');
        var verify = document.getElementById('surveyPublicAccessVerify');

        function setMode(nextAction) {
            var verifyMode = nextAction === 'verify_code';
            action.value = verifyMode ? 'verify_code' : 'request_code';
            codeRow.hidden = !verifyMode;
            code.required = verifyMode;
            sendActions.hidden = verifyMode;
            verifyActions.hidden = !verifyMode;
        }

        function setBusy(busy) {
            send.disabled = busy;
            resend.disabled = busy;
            verify.disabled = busy;
        }

        function submit(nextAction) {
            action.value = nextAction === 'verify_code' ? 'verify_code' : 'request_code';
            feedback.textContent = '';
            feedback.classList.remove('is-success');
            setBusy(true);
            fetch(form.action || window.location.href, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.json().catch(function () { return { status: false, message: labels.processFailed }; });
            }).then(function (result) {
                if (!result || result.status !== true) {
                    feedback.textContent = result && result.message ? result.message : labels.processFailed;
                    setBusy(false);
                    return;
                }
                feedback.textContent = result.message || '';
                feedback.classList.add('is-success');
                if (result.redirectUrl) {
                    window.location.assign(String(result.redirectUrl));
                    return;
                }
                if (result.nextAction === 'verify_code') {
                    setMode('verify_code');
                    code.focus();
                }
                setBusy(false);
            }).catch(function () {
                feedback.textContent = labels.processFailed;
                setBusy(false);
            });
        }

        setMode('request_code');
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submit(action.value);
        });
        resend.addEventListener('click', function () { submit('request_code'); });
    }());
};
