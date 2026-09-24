window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/profile-totp.js"] = function (pageConfig, pageScript) {

        (function () {
            var script = pageScript;
            var section = script ? script.previousElementSibling : null;
            if (!section || section.getAttribute("data-profile-totp-section") !== "1") return;
            var toggle = section.querySelector("[data-profile-totp-toggle]");
            var setup = section.querySelector("[data-profile-totp-setup]");
            var endpoint = "/ajax/totp_setup.php";
            var disableConfirm = pageConfig.disableConfirm;

            function request(action, extra) {
                var body = new URLSearchParams();
                body.set("action", action);
                Object.keys(extra || {}).forEach(function (key) { body.set(key, extra[key]); });
                return fetch(endpoint, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { "Content-Type": "application/x-www-form-urlencoded", "X-Requested-With": "XMLHttpRequest" },
                    body: body.toString()
                }).then(function (response) { return response.json(); });
            }

            function setMessage(message, error) {
                setup.textContent = message || "";
                setup.className = error ? "profile-panel__feedback profile-panel__feedback--error" : "profile-panel__scope-help";
            }

            function renderSetup(data) {
                setup.innerHTML = "";
                var title = document.createElement("strong");
                title.className = "generic-card-title generic-card-title--small";
                title.textContent = ("" + pageConfig.setupTitle + "");
                var instructions = document.createElement("p");
                instructions.className = "profile-panel__scope-help";
                instructions.textContent = ("" + pageConfig.setupInstructions + "");
                setup.appendChild(title);
                setup.appendChild(instructions);
                if (data.qr_url) {
                    var image = document.createElement("img");
                    image.src = data.qr_url;
                    image.alt = "QR code TOTP";
                    image.width = 220;
                    image.height = 220;
                    setup.appendChild(image);
                }
                var manual = document.createElement("p");
                manual.className = "profile-panel__scope-help";
                manual.textContent = ("" + pageConfig.manualLabel + ": ") + (data.manual_secret || "");
                setup.appendChild(manual);
                var code = document.createElement("input");
                code.type = "text";
                code.inputMode = "numeric";
                code.autocomplete = "one-time-code";
                code.maxLength = 6;
                code.placeholder = ("" + pageConfig.codePlaceholder + "");
                var button = document.createElement("button");
                button.type = "button";
                button.className = "generic-action-button generic-action-button--main";
                button.textContent = ("" + pageConfig.confirmLabel + "");
                button.addEventListener("click", function () {
                    button.disabled = true;
                    request("confirm", { code: code.value }).then(function (result) {
                        button.disabled = false;
                        if (!result.status) { setMessage(result.message || "Erreur.", true); return; }
                        toggle.checked = true;
                        setMessage(result.message || "", false);
                    }).catch(function () { button.disabled = false; setMessage("Erreur.", true); });
                });
                setup.appendChild(code);
                setup.appendChild(button);
                code.focus();
            }

            toggle.addEventListener("change", function () {
                if (!toggle.checked) {
                    if (!window.confirm(disableConfirm)) { toggle.checked = true; return; }
                    request("disable", {}).then(function (result) {
                        if (!result.status) { toggle.checked = true; setMessage(result.message || "Erreur.", true); return; }
                        setMessage(result.message || "", false);
                    }).catch(function () { toggle.checked = true; setMessage("Erreur.", true); });
                    return;
                }
                request("start", {}).then(function (result) {
                    if (!result.status) { toggle.checked = false; setMessage(result.message || "Erreur.", true); return; }
                    renderSetup(result);
                }).catch(function () { toggle.checked = false; setMessage("Erreur.", true); });
            });
        })();
        
};
