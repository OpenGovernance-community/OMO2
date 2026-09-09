<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/account_merge.php';
require_once __DIR__ . '/profil_translation_helper.php';

$connected = checklogin();
$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION['currentUser'] ?? 0);
if (!$connected || $currentUserId <= 0) {
    die(htmlspecialchars(profilPopupT('profile.popup.error.login_required'), ENT_QUOTES, 'UTF-8'));
}

$currentUser = new \dbObject\User();
if (!$currentUser->load($currentUserId)) {
    die(htmlspecialchars(profilPopupT('profile.popup.error.unknown_user'), ENT_QUOTES, 'UTF-8'));
}

$state = commonAccountMergeGetState($currentUserId);
$configuration = array(
    'endpoint' => '/ajax/user_account_merge.php',
    'csrfToken' => commonAccountMergeGetCsrfToken(),
    'currentEmail' => (string)$currentUser->get('email'),
    'otherEmail' => is_array($state) ? (string)($state['other_email'] ?? '') : '',
    'phase' => is_array($state) ? (string)($state['phase'] ?? 'code') : 'start',
    'token' => is_array($state) ? (string)($state['login_token'] ?? '') : '',
    'passwordLoginEnabled' => is_array($state) && !empty($state['password_login_enabled']),
    'currentIsSiteAdmin' => is_array($state) ? !empty($state['current_is_siteadmin']) : $currentUser->isSiteAdmin(),
    'otherIsSiteAdmin' => is_array($state) && !empty($state['other_is_siteadmin']),
    'initialToken' => trim((string)($_GET['token'] ?? '')),
    'initialCode' => trim((string)($_GET['code'] ?? '')),
    'text' => array(
        'verified' => profilPopupT('profile.popup.merge.status.verified'),
        'processing' => profilPopupT('profile.popup.merge.status.processing'),
        'invalidResponse' => profilPopupT('profile.popup.js.invalid_response'),
    ),
);
?>
<style>
    .profile-merge-tool {
        display: grid;
        gap: 16px;
    }

    .profile-merge-tool__copy,
    .profile-merge-tool__step,
    .profile-merge-tool__choices {
        display: grid;
        gap: 12px;
    }

    .profile-merge-tool__copy p,
    .profile-merge-tool__step p {
        margin: 0;
        color: var(--color-text-light, #64748b);
        line-height: 1.5;
    }

    .profile-merge-tool__field {
        display: grid;
        gap: 7px;
        font-weight: 650;
    }

    .profile-merge-tool__current-email {
        overflow-wrap: anywhere;
        font-weight: 700;
    }

    .profile-merge-tool__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .profile-merge-tool__method {
        border: 0;
        padding: 0;
        background: transparent;
        color: var(--color-primary, #004663);
        cursor: pointer;
        font: inherit;
        font-weight: 700;
        text-decoration: underline;
    }

    .profile-merge-tool__choice,
    .profile-merge-tool__confirmation {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .profile-merge-tool__choice input,
    .profile-merge-tool__confirmation input {
        margin-top: 4px;
    }

    .profile-merge-tool__choice-copy {
        display: grid;
        gap: 3px;
    }

    .profile-merge-tool__choice-copy small {
        color: var(--color-text-light, #64748b);
        overflow-wrap: anywhere;
    }

    .profile-merge-tool__warning {
        border-color: color-mix(in srgb, #dc2626 28%, var(--color-border, #dbe4ee));
        background: color-mix(in srgb, #dc2626 7%, var(--color-surface, #fff));
        color: color-mix(in srgb, #dc2626 82%, var(--color-text, #0f172a));
    }

    .profile-merge-tool__status {
        min-height: 24px;
        line-height: 1.45;
        font-weight: 650;
    }

    .profile-merge-tool__status.is-error {
        color: #b91c1c;
    }

    .profile-merge-tool__status.is-success {
        color: #15803d;
    }
</style>

<section class="profile-merge-tool generic-section profile-panel__section" data-profile-merge-root>
    <div class="profile-merge-tool__copy">
        <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(profilPopupT('profile.popup.merge.title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars(profilPopupT('profile.popup.merge.intro'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div class="generic-soft-panel generic-soft-panel--stack">
        <strong><?= htmlspecialchars(profilPopupT('profile.popup.merge.current_email'), ENT_QUOTES, 'UTF-8') ?></strong>
        <span class="profile-merge-tool__current-email"><?= htmlspecialchars((string)$currentUser->get('email'), ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <button type="button" class="generic-action-button generic-action-button--main" data-merge-reveal>
        <?= htmlspecialchars(profilPopupT('profile.popup.merge.reveal'), ENT_QUOTES, 'UTF-8') ?>
    </button>

    <div class="profile-merge-tool__step" data-merge-email-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.other_email_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="email" class="generic-form-control" autocomplete="email" placeholder="<?= htmlspecialchars(profilPopupT('profile.popup.merge.other_email_placeholder'), ENT_QUOTES, 'UTF-8') ?>" data-merge-email>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-start><?= htmlspecialchars(profilPopupT('profile.popup.merge.start'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-code-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.code_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="text" class="generic-form-control" inputmode="text" autocomplete="one-time-code" maxlength="6" data-merge-code>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-verify-code><?= htmlspecialchars(profilPopupT('profile.popup.merge.verify_code'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="profile-merge-tool__method" data-merge-use-password hidden><?= htmlspecialchars(profilPopupT('profile.popup.merge.use_password'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="profile-merge-tool__method" data-merge-resend><?= htmlspecialchars(profilPopupT('profile.popup.merge.resend_code'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-password-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.password_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="password" class="generic-form-control" autocomplete="current-password" data-merge-password>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-verify-password><?= htmlspecialchars(profilPopupT('profile.popup.merge.verify_password'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="profile-merge-tool__method" data-merge-use-code><?= htmlspecialchars(profilPopupT('profile.popup.merge.use_code'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-totp-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.totp_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="text" class="generic-form-control" inputmode="numeric" autocomplete="one-time-code" maxlength="6" data-merge-totp>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-verify-totp><?= htmlspecialchars(profilPopupT('profile.popup.merge.verify_totp'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-confirm-step hidden>
        <div>
            <h4 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_title'), ENT_QUOTES, 'UTF-8') ?></h4>
            <p><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_help'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="profile-merge-tool__choices">
            <label class="profile-merge-tool__choice generic-soft-panel">
                <input type="radio" name="profile_merge_keep" value="current" checked data-merge-keep>
                <span class="profile-merge-tool__choice-copy">
                    <strong><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_current'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <small data-merge-current-email><?= htmlspecialchars((string)$currentUser->get('email'), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
            </label>
            <label class="profile-merge-tool__choice generic-soft-panel">
                <input type="radio" name="profile_merge_keep" value="other" data-merge-keep>
                <span class="profile-merge-tool__choice-copy">
                    <strong><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_other'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <small data-merge-other-email></small>
                </span>
            </label>
        </div>
        <div class="generic-soft-panel generic-soft-panel--stack" hidden data-merge-superadmin-note>
            <?= htmlspecialchars(profilPopupT('profile.popup.merge.superadmin_kept'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="profile-merge-tool__warning generic-soft-panel generic-soft-panel--stack">
            <?= htmlspecialchars(profilPopupT('profile.popup.merge.warning'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <label class="profile-merge-tool__confirmation">
            <input type="checkbox" value="1" data-merge-confirm-check>
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.confirm_label'), ENT_QUOTES, 'UTF-8') ?></span>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--danger" disabled data-merge-complete><?= htmlspecialchars(profilPopupT('profile.popup.merge.complete'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__status" aria-live="polite" data-merge-status></div>
</section>

<script>
(function () {
    var script = document.currentScript;
    var root = script ? script.previousElementSibling : null;
    var config = <?= json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    if (!root || !root.hasAttribute("data-profile-merge-root")) return;

    var reveal = root.querySelector("[data-merge-reveal]");
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
        Array.prototype.forEach.call(root.querySelectorAll("button, input"), function (control) {
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
        reveal.hidden = phase !== "start";
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

    reveal.addEventListener("click", function () { renderPhase("email"); });
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
        setStatus(<?= json_encode(profilPopupT('profile.popup.merge.status.mfa_required'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, "success");
    } else if (config.phase === "code") {
        renderPhase("code");
    } else {
        renderPhase("start");
    }

    if (config.initialToken && config.initialCode && token && config.initialToken === token) {
        codeInput.value = config.initialCode;
        root.querySelector("[data-merge-verify-code]").click();
    }
})();
</script>
