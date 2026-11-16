window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/profile-email-change.js"] = function (pageConfig, pageScript) {
    var fragment = pageScript.closest(".profile-panel__scope-fragment");
    var section = fragment ? fragment.querySelector("[data-profile-email-change]") : null;
    if (!section) return;

    var toggle = section.querySelector("[data-email-change-toggle]");
    var content = section.querySelector("[data-email-change-content]");
    var startStep = section.querySelector("[data-email-change-start]");
    var confirmStep = section.querySelector("[data-email-change-confirm]");
    var startForm = fragment.querySelector("#profile-email-change-start-form");
    var confirmForm = fragment.querySelector("#profile-email-change-confirm-form");
    var progress = section.querySelector("[data-email-change-progress]");
    var token = "";
    var busy = false;

    function notify(message, type) {
        if (window.commonNotify) window.commonNotify(message, type);
    }

    function setBusy(value, message) {
        busy = value;
        section.querySelectorAll("button, input").forEach(function (control) { control.disabled = value; });
        progress.hidden = !value;
        progress.textContent = value ? message : "";
    }

    function resetCodes() {
        token = "";
        startStep.hidden = false;
        confirmStep.hidden = true;
        confirmForm.reset();
    }

    async function request(action, form) {
        var body = new URLSearchParams(new FormData(form));
        body.set("action", action);
        body.set("_csrf", pageConfig.csrf);
        if (action === "confirm") body.set("token", token);
        setBusy(true, action === "start" ? pageConfig.sending : pageConfig.verifying);
        try {
            var response = await fetch("/ajax/user_email_change.php", {
                method: "POST",
                credentials: "same-origin",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: body
            });
            return await response.json();
        } finally {
            setBusy(false);
        }
    }

    toggle.addEventListener("click", function () {
        content.hidden = !content.hidden;
        toggle.setAttribute("aria-expanded", String(!content.hidden));
        if (!content.hidden) (confirmStep.hidden ? startForm.elements.new_email : confirmForm.elements.old_code).focus();
    });
    section.querySelector("[data-email-change-close]").addEventListener("click", function () {
        content.hidden = true;
        toggle.setAttribute("aria-expanded", "false");
        toggle.focus();
    });
    section.querySelector("[data-email-change-restart]").addEventListener("click", function () {
        resetCodes();
        startForm.elements.new_email.focus();
    });

    startForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (busy) return;
        try {
            var result = await request("start", startForm);
            if (!result.status) { notify(result.message || pageConfig.error, "error"); return; }
            token = result.token;
            section.querySelector("[data-email-change-old-address]").textContent = result.old_email;
            section.querySelector("[data-email-change-new-address]").textContent = result.new_email;
            startStep.hidden = true;
            confirmStep.hidden = false;
            confirmForm.elements.old_code.focus();
            notify(result.message, "success");
        } catch (error) {
            notify(pageConfig.error, "error");
        }
    });

    confirmForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (busy) return;
        try {
            var result = await request("confirm", confirmForm);
            if (!result.status) {
                if (result.error === "expired" || result.error === "email_in_use") resetCodes();
                notify(result.message || pageConfig.error, "error");
                return;
            }
            var profileForm = fragment.querySelector("#formulaire-edit");
            if (profileForm) {
                var emailField = profileForm.querySelector("#row_email .admin-edit__field-content");
                if (emailField) emailField.textContent = result.email;
            }
            var profileRoot = section.closest("#profilePanelRoot");
            if (profileRoot) {
                var oldEmail = section.querySelector("[data-email-change-old-address]").textContent;
                profileRoot.querySelectorAll(".profile-panel__header-meta span").forEach(function (badge) {
                    if (badge.textContent === oldEmail) badge.textContent = result.email;
                });
            }
            resetCodes();
            startForm.reset();
            content.hidden = true;
            toggle.setAttribute("aria-expanded", "false");
            if (window.commonTopbarRefreshUserProfile) window.commonTopbarRefreshUserProfile("email-change");
            notify(result.message, "success");
        } catch (error) {
            notify(pageConfig.error, "error");
        }
    });
};
