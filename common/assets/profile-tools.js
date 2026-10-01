window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/profile-tools.js"] = function (pageConfig, pageScript) {
(function () {
    var script = pageScript;
    var root = script ? script.previousElementSibling : null;
    var config = pageConfig.config;
    if (!root || !root.hasAttribute("data-profile-tools-root")) return;

    var reveal = root.querySelector("[data-merge-reveal]");
    var mergeRoot = root.querySelector("[data-profile-merge-root]");
    var emailInput = root.querySelector("[data-merge-email]");
    var codeInput = root.querySelector("[data-merge-code]");
    var passwordInput = root.querySelector("[data-merge-password]");
    var totpInput = root.querySelector("[data-merge-totp]");
    var confirmCheck = root.querySelector("[data-merge-confirm-check]");
    var completeButton = root.querySelector("[data-merge-complete]");
    var status = root.querySelector("[data-merge-status]");
    var token = config.token || "";
    var passwordLoginEnabled = !!config.passwordLoginEnabled;
    var currentIsSiteAdmin = !!config.currentIsSiteAdmin;
    var otherIsSiteAdmin = !!config.otherIsSiteAdmin;
    var mergePhase = "start";

    function updateMergeToggle() {
        var expanded = !mergeRoot.classList.contains("is-collapsed");
        reveal.setAttribute("aria-expanded", expanded ? "true" : "false");
        reveal.textContent = expanded ? config.text.collapse : config.text.mergeOpen;
    }

    function enforceSuperAdminChoice(data) {
        data = data || {};
        if (Object.prototype.hasOwnProperty.call(data, "current_is_siteadmin")) currentIsSiteAdmin = !!data.current_is_siteadmin;
        if (Object.prototype.hasOwnProperty.call(data, "other_is_siteadmin")) otherIsSiteAdmin = !!data.other_is_siteadmin;

        var forcedValue = currentIsSiteAdmin !== otherIsSiteAdmin
            ? (currentIsSiteAdmin ? "current" : "other")
            : "";
        Array.prototype.forEach.call(root.querySelectorAll("[data-merge-keep]"), function (radio) {
            radio.disabled = forcedValue !== "" && radio.value !== forcedValue;
            if (radio.value === forcedValue) radio.checked = true;
        });
        root.querySelector("[data-merge-superadmin-note]").hidden = forcedValue === "";
    }

    function setStatus(message, type) {
        status.textContent = message || "";
        status.className = "profile-merge-tool__status" + (type ? " is-" + type : "");
    }

    function setBusy(busy) {
        Array.prototype.forEach.call(mergeRoot.querySelectorAll("button, input"), function (control) {
            if (control === completeButton && !busy) {
                control.disabled = !confirmCheck.checked;
                return;
            }
            control.disabled = !!busy;
        });
        if (!busy) enforceSuperAdminChoice();
    }

    function post(action, values) {
        var body = new URLSearchParams();
        body.set("action", action);
        body.set("csrf_token", config.csrfToken);
        Object.keys(values || {}).forEach(function (key) { body.set(key, values[key]); });
        return fetch(config.endpoint, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: body.toString()
        }).then(function (response) {
            return response.text().then(function (text) {
                var payload;
                try { payload = JSON.parse(text); } catch (error) { payload = { status: false, message: config.text.invalidResponse }; }
                if (!response.ok && !payload.message) payload.message = config.text.invalidResponse;
                return payload;
            });
        });
    }

    function renderPhase(phase, data) {
        data = data || {};
        mergePhase = phase;
        if (phase !== "start") mergeRoot.classList.remove("is-collapsed");
        updateMergeToggle();
        root.querySelector("[data-merge-email-step]").hidden = phase !== "email";
        root.querySelector("[data-merge-code-step]").hidden = phase !== "code";
        root.querySelector("[data-merge-password-step]").hidden = phase !== "password";
        root.querySelector("[data-merge-totp-step]").hidden = phase !== "totp";
        root.querySelector("[data-merge-confirm-step]").hidden = phase !== "confirm";
        root.querySelector("[data-merge-use-password]").hidden = !passwordLoginEnabled;

        if (phase === "email") emailInput.focus();
        if (phase === "code") codeInput.focus();
        if (phase === "password") passwordInput.focus();
        if (phase === "totp") totpInput.focus();
        if (phase === "confirm") {
            root.querySelector("[data-merge-current-email]").textContent = data.current_email || config.currentEmail || "";
            root.querySelector("[data-merge-other-email]").textContent = data.other_email || config.otherEmail || "";
            enforceSuperAdminChoice(data);
            setStatus(config.text.verified, "success");
        }
    }

    function handleVerificationResult(result) {
        if (!result || !result.status) {
            setStatus(result && result.message ? result.message : config.text.invalidResponse, "error");
            return;
        }
        if (result.mfa_token) token = result.mfa_token;
        renderPhase(result.phase || "confirm", result);
        if (result.message) setStatus(result.message, "success");
    }

    reveal.addEventListener("click", function () {
        if (!mergeRoot.classList.contains("is-collapsed")) {
            mergeRoot.classList.add("is-collapsed");
            updateMergeToggle();
            return;
        }
        renderPhase(mergePhase === "start" ? "email" : mergePhase);
    });
    function startVerification(email) {
        setBusy(true);
        post("start", { email: email }).then(function (result) {
            setBusy(false);
            if (!result.status) { setStatus(result.message || config.text.invalidResponse, "error"); return; }
            token = result.request_token || "";
            passwordLoginEnabled = !!result.password_login_enabled;
            config.otherEmail = email;
            renderPhase("code");
            setStatus(result.message || "", result.delivery_uncertain ? "error" : "success");
        }).catch(function () { setBusy(false); setStatus(config.text.invalidResponse, "error"); });
    }
    root.querySelector("[data-merge-start]").addEventListener("click", function () {
        startVerification(emailInput.value);
    });
    root.querySelector("[data-merge-resend]").addEventListener("click", function () { startVerification(config.otherEmail); });
    root.querySelector("[data-merge-verify-code]").addEventListener("click", function () {
        setBusy(true);
        post("verify_code", { token: token, code: codeInput.value }).then(function (result) {
            setBusy(false);
            handleVerificationResult(result);
        }).catch(function () { setBusy(false); setStatus(config.text.invalidResponse, "error"); });
    });
    root.querySelector("[data-merge-verify-password]").addEventListener("click", function () {
        setBusy(true);
        post("verify_password", { password: passwordInput.value }).then(function (result) {
            setBusy(false);
            passwordInput.value = "";
            handleVerificationResult(result);
        }).catch(function () { setBusy(false); passwordInput.value = ""; setStatus(config.text.invalidResponse, "error"); });
    });
    root.querySelector("[data-merge-verify-totp]").addEventListener("click", function () {
        setBusy(true);
        post("verify_totp", { token: token, code: totpInput.value }).then(function (result) {
            setBusy(false);
            handleVerificationResult(result);
        }).catch(function () { setBusy(false); setStatus(config.text.invalidResponse, "error"); });
    });
    root.querySelector("[data-merge-use-password]").addEventListener("click", function () { renderPhase("password"); });
    root.querySelector("[data-merge-use-code]").addEventListener("click", function () { renderPhase("code"); });
    confirmCheck.addEventListener("change", function () { completeButton.disabled = !confirmCheck.checked; });
    completeButton.addEventListener("click", function () {
        var selected = root.querySelector("[data-merge-keep]:checked");
        setBusy(true);
        setStatus(config.text.processing);
        post("complete", { keep: selected ? selected.value : "", confirm: confirmCheck.checked ? "1" : "0" }).then(function (result) {
            if (!result.status) {
                setBusy(false);
                setStatus(result.message || config.text.invalidResponse, "error");
                return;
            }
            setStatus(result.message || "", "success");
            window.setTimeout(function () { window.top.location.reload(); }, 700);
        }).catch(function () { setBusy(false); setStatus(config.text.invalidResponse, "error"); });
    });
    Array.prototype.forEach.call(root.querySelectorAll("[data-merge-cancel]"), function (button) {
        button.addEventListener("click", function () {
            post("cancel", {}).then(function () {
                token = "";
                passwordLoginEnabled = false;
                otherIsSiteAdmin = false;
                emailInput.value = "";
                codeInput.value = "";
                passwordInput.value = "";
                totpInput.value = "";
                confirmCheck.checked = false;
                completeButton.disabled = true;
                setStatus("");
                renderPhase("start");
                mergeRoot.classList.add("is-collapsed");
                updateMergeToggle();
            });
        });
    });
    [emailInput, codeInput, passwordInput, totpInput].forEach(function (input) {
        input.addEventListener("keydown", function (event) {
            if (event.key !== "Enter") return;
            event.preventDefault();
            var button = input.closest("[data-merge-email-step], [data-merge-code-step], [data-merge-password-step], [data-merge-totp-step]").querySelector(".generic-action-button--main");
            if (button) button.click();
        });
    });
    totpInput.addEventListener("input", function () { totpInput.value = totpInput.value.replace(/\D/g, "").slice(0, 6); });
    codeInput.addEventListener("input", function () { codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, "").slice(0, 6); });

    if (config.phase === "confirm") {
        renderPhase("confirm", { current_email: config.currentEmail, other_email: config.otherEmail });
    } else if (config.phase === "totp") {
        renderPhase("totp");
        setStatus(pageConfig.profilePopupMergeStatusMfaRequired, "success");
    } else if (config.phase === "code") {
        renderPhase("code");
    } else {
        renderPhase("start");
    }

    if (config.initialToken && config.initialCode && token && config.initialToken === token) {
        codeInput.value = config.initialCode;
        root.querySelector("[data-merge-verify-code]").click();
    }

    var deletionRoot = root.querySelector("[data-profile-delete-root]");
    if (!deletionRoot) return;
    var deletionOpen = deletionRoot.querySelector("[data-delete-open]");
    var deletionStatus = deletionRoot.querySelector("[data-delete-status]");
    var deletionPlan = deletionRoot.querySelector("[data-delete-plan]");
    var deletionConfirmation = deletionRoot.querySelector("[data-delete-confirmation]");
    var deletionConfirmationLabel = deletionRoot.querySelector("[data-delete-confirmation-label]");
    var deletionConfirmationInput = deletionRoot.querySelector("[data-delete-confirmation-input]");
    var deletionComplete = deletionRoot.querySelector("[data-delete-complete]");
    var currentDeletionPlan = null;

    function updateDeletionToggle() {
        var expanded = !deletionRoot.classList.contains("is-collapsed");
        deletionOpen.setAttribute("aria-expanded", expanded ? "true" : "false");
        deletionOpen.textContent = expanded ? config.text.collapse : config.text.deletionOpen;
    }

    function setDeletionStatus(message, type) {
        deletionStatus.textContent = message || "";
        deletionStatus.className = "profile-account-delete__status" + (type ? " is-" + type : "");
    }

    function postDeletion(action, values) {
        var body = new URLSearchParams();
        body.set("action", action);
        body.set("csrf_token", config.deletionCsrfToken);
        Object.keys(values || {}).forEach(function (key) { body.set(key, values[key]); });
        return fetch(config.deletionEndpoint, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: body.toString()
        }).then(function (response) {
            return response.text().then(function (text) {
                var payload;
                try { payload = JSON.parse(text); } catch (error) { payload = { status: false, message: config.text.deletionInvalidResponse }; }
                if (!response.ok && !payload.message) payload.message = config.text.deletionInvalidResponse;
                return payload;
            });
        });
    }

    function appendDeletionList(title, organizations) {
        if (!organizations || !organizations.length) return;
        var heading = document.createElement("h4");
        heading.className = "generic-card-title generic-card-title--section";
        heading.textContent = title;
        deletionPlan.appendChild(heading);
        var list = document.createElement("ul");
        list.className = "profile-account-delete__list";
        organizations.forEach(function (organization) {
            var item = document.createElement("li");
            item.textContent = typeof organization === "string" ? organization : (organization.name || "");
            list.appendChild(item);
        });
        deletionPlan.appendChild(list);
    }

    function renderDeletionPlan(plan) {
        currentDeletionPlan = plan || null;
        deletionPlan.replaceChildren();
        deletionConfirmation.hidden = true;
        deletionConfirmationInput.value = "";
        deletionComplete.disabled = true;
        if (!plan || !plan.status) {
            setDeletionStatus((plan && plan.message) || config.text.deletionInvalidResponse, "error");
            return;
        }
        appendDeletionList(config.text.deletionWillDeleteOrganizations, plan.deleted_organizations || []);
        appendDeletionList(config.text.deletionWillLeave, plan.departures || []);
        appendDeletionList(config.text.deletionBlocked, plan.blockers || []);
        if (!(plan.deleted_organizations || []).length && !(plan.departures || []).length && !(plan.blockers || []).length) {
            var noOrganization = document.createElement("p");
            noOrganization.textContent = config.text.deletionNoOrganization;
            deletionPlan.appendChild(noOrganization);
        }
        if (!plan.can_delete) {
            setDeletionStatus((plan.blockers && plan.blockers[0]) || config.text.deletionInvalidResponse, "error");
            return;
        }
        deletionConfirmationLabel.textContent = config.text.deletionConfirmationPrefix + " " + (plan.confirmation_text || "");
        deletionConfirmationInput.placeholder = plan.confirmation_text || "";
        deletionConfirmation.hidden = false;
        setDeletionStatus("");
    }

    function loadDeletionPlan() {
        if (!deletionRoot.classList.contains("is-collapsed")) {
            deletionRoot.classList.add("is-collapsed");
            updateDeletionToggle();
            return;
        }
        deletionRoot.classList.remove("is-collapsed");
        updateDeletionToggle();
        deletionOpen.disabled = true;
        setDeletionStatus(config.text.deletionLoading);
        postDeletion("plan", {}).then(function (plan) {
            deletionOpen.disabled = false;
            renderDeletionPlan(plan);
        }).catch(function () {
            deletionOpen.disabled = false;
            setDeletionStatus(config.text.deletionInvalidResponse, "error");
        });
    }

    deletionOpen.addEventListener("click", loadDeletionPlan);
    deletionConfirmationInput.addEventListener("input", function () {
        deletionComplete.disabled = !currentDeletionPlan
            || deletionConfirmationInput.value.trim() !== String(currentDeletionPlan.confirmation_text || "");
    });
    deletionComplete.addEventListener("click", function () {
        if (!currentDeletionPlan) return;
        deletionComplete.disabled = true;
        deletionConfirmationInput.disabled = true;
        setDeletionStatus(config.text.deletionProcessing);
        postDeletion("delete", { confirmation: deletionConfirmationInput.value.trim() }).then(function (result) {
            if (!result.status) {
                deletionConfirmationInput.disabled = false;
                deletionComplete.disabled = false;
                setDeletionStatus(result.message || config.text.deletionInvalidResponse, "error");
                if (result.plan) renderDeletionPlan(result.plan);
                return;
            }
            setDeletionStatus(result.message || config.text.deletionSuccess, "success");
            window.setTimeout(function () { window.top.location.href = result.redirect || "/"; }, 700);
        }).catch(function () {
            deletionConfirmationInput.disabled = false;
            deletionComplete.disabled = false;
            setDeletionStatus(config.text.deletionInvalidResponse, "error");
        });
    });
})();
};
