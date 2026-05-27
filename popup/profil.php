<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/patreon.php");
require_once("../common/user_profile_ui.php");
require_once(__DIR__ . "/profil_translation_helper.php");

$connected = checklogin();
if (!$connected) {
    die(profilPopupT('profile.popup.error.login_required'));
}

$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION["currentUser"] ?? 0);
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);

$user = new \dbObject\User();
$user->load($currentUserId);
if (!($user->get("id") > 0)) {
    die(profilPopupT('profile.popup.error.unknown_user'));
}

$organization = null;
$hasOrganizationScope = false;

if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $hasOrganizationScope = true;
    }
}

$activeEmail = $user->getScopedEmail($currentOrganizationId);
$activeUsername = $user->getScopedUsername($currentOrganizationId);
$activePhotoUrl = $user->getScopedProfilePhotoUrl($currentOrganizationId);
$activePresentation = $user->getScopedPresentation($currentOrganizationId);
$birthdate = $user->get('birthdate');
$birthdaySummary = commonUserProfileBuildBirthdaySummary($birthdate);
$birthdateLabel = commonUserProfileFormatBirthDate($birthdate);
$requestedScope = isset($_GET['scope']) && $_GET['scope'] === 'organization' ? 'organization' : 'general';
$initialScope = $hasOrganizationScope ? $requestedScope : 'general';

$patreonUiEnabled = patreonSupportUiIsEnabled();
$patreonConnection = false;
$patreonConnected = false;

if ($patreonUiEnabled) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId((int)$user->getId());
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
}

function profilFormatDateTime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d.m.Y H:i');
    }

    return profilPopupT('profile.popup.value.not_provided');
}

function profilFormatAmountCents($value)
{
    $amount = ((int)$value) / 100;
    return number_format($amount, 2, '.', "'");
}
?>
<style>
    .profile-panel {
        padding: 18px;
        background: var(--color-bg, var(--auth-page-bg, #f8fafc));
        color: var(--color-text, #0f172a);
        font-family: system-ui, sans-serif;
    }

    .profile-panel__sections {
        display: grid;
        gap: 18px;
    }

    .profile-panel__section {
        --generic-section-padding-block: 18px;
        --generic-section-padding-inline: 18px;
        --generic-section-border: var(--color-border, #dbe4ee);
    }

    .profile-panel__section h3 {
        margin: 0 0 14px;
    }

    .profile-panel__summary {
        display: grid;
        gap: 10px;
    }

    .profile-panel__item {
        --generic-soft-panel-padding-block: 12px;
        --generic-soft-panel-padding-inline: 14px;
        --generic-soft-panel-border: var(--color-border, #dbe4ee);
        --generic-soft-panel-background: var(--color-surface-alt, #f8fafc);
        --generic-soft-panel-radius: 12px;
    }

    .profile-panel__item strong {
        display: block;
        margin-bottom: 4px;
    }

    .profile-panel__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }

    .profile-panel button[type='button'],
    .profile-panel button[type='submit'] {
        font: inherit;
    }

    .profile-panel button[type='button'].profile-panel__button-secondary,
    .profile-panel button[type='submit'].profile-panel__button-secondary {
        background: color-mix(in srgb, var(--color-text, #0f172a) 92%, var(--color-surface, #ffffff));
        color: var(--color-text-inverse, #ffffff);
    }

    .profile-panel button[type='button'].profile-panel__button-muted {
        background: var(--color-surface-alt, #e2e8f0);
        color: var(--color-text, #0f172a);
    }

    .profile-panel button[disabled] {
        cursor: not-allowed;
        opacity: .75;
    }

    .profile-panel__note {
        margin-top: 14px;
        padding: 12px 14px;
        border-radius: 12px;
        background: color-mix(in srgb, #f59e0b 10%, var(--color-surface, #ffffff));
        border: 1px solid color-mix(in srgb, #f59e0b 22%, var(--color-border, #fed7aa));
        color: #9a3412;
    }

    .profile-panel .dbobjecttable {
        width: 100%;
    }

    .profile-panel .dbobjecttable th,
    .profile-panel .dbobjecttable td {
        vertical-align: top;
    }

    .profile-panel .dbobjecttable th {
        padding-right: 12px;
        text-align: left;
        white-space: nowrap;
    }

    .profile-panel__scope-switch {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .profile-panel__scope-button {
        color: var(--color-text, #0f172a) !important;
    }

    .profile-panel__scope-button.is-active {
        color: var(--color-text-inverse, #ffffff) !important;
    }

    .profile-panel__scope-panel[hidden] {
        display: none !important;
    }

    .profile-panel__photo {
        width: 72px;
        height: 72px;
        border-radius: 999px;
        background: var(--color-surface-alt, #dbe4ee) center center / cover no-repeat;
        border: 1px solid var(--color-border-strong, #cbd5e1);
    }

    .profile-panel__field small,
    .profile-panel__scope-help,
    .profile-panel__feedback {
        color: var(--color-text-light, #64748b);
        line-height: 1.45;
    }

    .profile-panel__feedback {
        min-height: 22px;
        font-weight: 600;
    }

    .profile-panel__feedback.is-success {
        color: #15803d;
    }

    .profile-panel__feedback.is-error {
        color: #b91c1c;
    }

    .profile-panel__photo-empty {
        color: var(--color-text-light, #64748b);
        font-size: 0.92rem;
    }

    .profile-panel__competence-section {
        --generic-section-gap: 14px;
    }

    .profile-panel__competence-head {
        display: grid;
        gap: 6px;
    }

    .profile-panel__competence-list {
        display: grid;
        gap: 12px;
    }

    .profile-panel__competence-card {
        display: grid;
        gap: 12px;
    }

    .profile-panel__competence-card--new {
        border-style: dashed;
    }

    .profile-panel__competence-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }

    .profile-panel__competence-field {
        display: grid;
        gap: 6px;
    }

    .profile-panel__competence-field span,
    .profile-panel__competence-validators-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text, #334155);
    }

    .profile-panel__competence-field input,
    .profile-panel__competence-field select {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid var(--color-border, #dbe4ee);
        border-radius: 12px;
        background: var(--color-surface, #fff);
        color: inherit;
        font: inherit;
        box-sizing: border-box;
    }

    .profile-panel__competence-meta,
    .profile-panel__competence-actions,
    .profile-panel__competence-validators,
    .profile-panel__competence-avatar-stack {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .profile-panel__competence-badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 14%, var(--color-surface, #ffffff));
        color: var(--color-primary, #1d4ed8);
        font-size: 12px;
        font-weight: 700;
    }

    .profile-panel__competence-badge--muted {
        background: var(--color-surface-alt, #e2e8f0);
        color: var(--color-text-light, #475569);
    }

    .profile-panel__competence-avatar,
    .profile-panel__competence-avatar--placeholder {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        border: 1px solid var(--color-border-strong, #cbd5e1);
        background: var(--color-surface-alt, #e2e8f0);
    }

    .profile-panel__competence-avatar {
        object-fit: cover;
        display: block;
    }

    .profile-panel__competence-avatar--placeholder {
        display: inline-grid;
        place-items: center;
        font-size: 11px;
        font-weight: 700;
        color: color-mix(in srgb, var(--color-primary, #2563eb) 78%, var(--color-text, #0f172a));
    }

    .profile-panel__competence-empty,
    .profile-panel__competence-feedback {
        color: var(--color-text-light, #64748b);
        line-height: 1.45;
    }

    .profile-panel__competence-feedback.is-success {
        color: #15803d;
    }

    .profile-panel__competence-feedback.is-error {
        color: #b91c1c;
    }

    .profile-panel__competence-scope-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .profile-panel__competence-scope-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid var(--color-border, #dbe4ee);
        border-radius: 12px;
        background: var(--color-surface, #fff);
        color: var(--color-text, #334155);
        font-size: 13px;
        font-weight: 600;
    }

    .profile-panel__competence-scope-toggle input {
        margin: 0;
    }
</style>

<div class="profile-panel" id="profilePanelRoot">
    <div class="profile-panel__sections">
        <section class="profile-panel__section generic-section">
            <h3 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(profilPopupT('profile.popup.section.active.title')) ?></h3>
            <div class="profile-panel__summary">
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.context.label')) ?></strong>
                    <?= htmlspecialchars($hasOrganizationScope && $organization
                        ? profilPopupT('profile.popup.active.context.organization', ['organizationName' => (string)$organization->get('name')])
                        : profilPopupT('profile.popup.active.context.general')) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.photo.label')) ?></strong>
                    <div class="profile-panel__photo"<?= $activePhotoUrl !== '' ? ' style="background-image:url(' . htmlspecialchars($activePhotoUrl, ENT_QUOTES, 'UTF-8') . ')"' : '' ?>></div>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.email.label')) ?></strong>
                    <?= htmlspecialchars($activeEmail !== '' ? $activeEmail : profilPopupT('profile.popup.value.not_provided')) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.username.label')) ?></strong>
                    <?= htmlspecialchars($activeUsername !== '' ? $activeUsername : profilPopupT('profile.popup.value.not_provided')) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.presentation.label')) ?></strong>
                    <?= nl2br(htmlspecialchars($activePresentation !== '' ? $activePresentation : profilPopupT('profile.popup.value.no_presentation'), ENT_QUOTES, 'UTF-8')) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.birthdate.label')) ?></strong>
                    <?= htmlspecialchars($birthdateLabel !== '' ? $birthdateLabel : profilPopupT('profile.popup.value.not_provided')) ?>
                </div>
                <?php if (is_array($birthdaySummary)): ?>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.birthday.label')) ?></strong>
                    <div><?= htmlspecialchars((string)$birthdaySummary['headline'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ((string)($birthdaySummary['detail'] ?? '') !== ''): ?>
                        <small><?= htmlspecialchars((string)$birthdaySummary['detail'], ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="profile-panel__section generic-section">
            <h3 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(profilPopupT('profile.popup.section.edit.title')) ?></h3>

            <?php if ($hasOrganizationScope): ?>
            <div class="profile-panel__scope-switch" role="tablist" aria-label="<?= htmlspecialchars(profilPopupT('profile.popup.scope.switch_aria')) ?>">
                <button
                    type="button"
                    class="profile-panel__scope-button generic-action-button<?= $initialScope === 'general' ? ' is-active generic-action-button--main' : ' generic-action-button--secondary' ?>"
                    data-profile-scope-target="general"
                ><?= htmlspecialchars(profilPopupT('profile.popup.scope.general_button')) ?></button>
                <button
                    type="button"
                    class="profile-panel__scope-button generic-action-button<?= $initialScope === 'organization' ? ' is-active generic-action-button--main' : ' generic-action-button--secondary' ?>"
                    data-profile-scope-target="organization"
                ><?= htmlspecialchars(profilPopupT('profile.popup.scope.organization_button')) ?></button>
            </div>
            <div class="profile-panel__scope-help">
                <?= htmlspecialchars(profilPopupT('profile.popup.scope.help')) ?>
            </div>
            <?php endif; ?>

            <div
                id="profileScopeContent"
                class="profile-panel__scope-panel"
                data-profile-scope-panel="<?= htmlspecialchars($initialScope, ENT_QUOTES, 'UTF-8') ?>"
                data-profile-scope-active="<?= htmlspecialchars($initialScope, ENT_QUOTES, 'UTF-8') ?>"
                data-profile-scope-url-general="/popup/profil_scope.php?scope=general"
                data-profile-scope-url-organization="/popup/profil_scope.php?scope=organization"
            >
                <div class="profile-panel__feedback"><?= htmlspecialchars(profilPopupT('profile.popup.scope.loading')) ?></div>
            </div>
        </section>

        <?php if ($patreonUiEnabled): ?>
        <section class="profile-panel__section generic-section">
            <h3 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(profilPopupT('profile.popup.section.patreon.title')) ?></h3>
            <div class="profile-panel__summary">
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.connection.label')) ?></strong>
                    <?= htmlspecialchars($patreonConnected
                        ? profilPopupT('profile.popup.patreon.connection.connected')
                        : profilPopupT('profile.popup.patreon.connection.disconnected')) ?>
                </div>
                <?php if ($patreonConnection !== false): ?>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.name.label')) ?></strong>
                    <?= htmlspecialchars((string)($patreonConnection->get('full_name') ?: profilPopupT('profile.popup.value.not_provided'))) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.status.label')) ?></strong>
                    <?= htmlspecialchars((string)($patreonConnection->get('patron_status') ?: profilPopupT('profile.popup.value.not_provided'))) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.payment.label')) ?></strong>
                    <?= htmlspecialchars((string)($patreonConnection->get('last_charge_status') ?: profilPopupT('profile.popup.value.not_provided'))) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.tiers.label')) ?></strong>
                    <?= nl2br(htmlspecialchars(trim((string)$patreonConnection->get('tier_titles')) !== '' ? (string)$patreonConnection->get('tier_titles') : profilPopupT('profile.popup.patreon.tiers.none'))) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.amount.label')) ?></strong>
                    <?= htmlspecialchars(profilFormatAmountCents((int)$patreonConnection->get('currently_entitled_amount_cents'))) ?>
                </div>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.sync_at.label')) ?></strong>
                    <?= htmlspecialchars(profilFormatDateTime($patreonConnection->get('last_sync_at'))) ?>
                </div>
                <?php if (trim((string)$patreonConnection->get('last_sync_error')) !== ''): ?>
                <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                    <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.sync_error.label')) ?></strong>
                    <?= nl2br(htmlspecialchars((string)$patreonConnection->get('last_sync_error'))) ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="profile-panel__actions">
                <button
                    type="button"
                    id="patreon_connect"
                    class="generic-action-button generic-action-button--main"
                ><?= htmlspecialchars($patreonConnected ? profilPopupT('profile.popup.patreon.reconnect') : profilPopupT('profile.popup.patreon.connect')) ?></button>
                <?php if ($patreonConnected): ?>
                <button type="button" id="patreon_sync" class="profile-panel__button-secondary generic-action-button"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.sync')) ?></button>
                <button type="button" id="patreon_disconnect" class="profile-panel__button-muted generic-action-button generic-action-button--secondary"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.disconnect')) ?></button>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var scopeContainer = document.getElementById("profileScopeContent");
    var initialScope = scopeContainer ? (scopeContainer.getAttribute("data-profile-scope-active") || "general") : "general";
    var invalidResponseMessage = <?= json_encode(profilPopupT('profile.popup.js.invalid_response'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var scopeLoadingMessage = <?= json_encode(profilPopupT('profile.popup.scope.loading'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var scopeLoadErrorMessage = <?= json_encode(profilPopupT('profile.popup.scope.load_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var disconnectConfirmMessage = <?= json_encode(profilPopupT('profile.popup.js.disconnect_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function parseJsonResponse(text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            return {
                status: false,
                message: invalidResponseMessage
            };
        }
    }

    function handleLegacyAjaxResponse(payload) {
        if (payload && payload.script) {
            try {
                eval(payload.script);
            } catch (error) {
                console.error(error);
            }
        }

        if (payload && payload.message) {
            alert(payload.message);
        }
    }

    function postUrl(url) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        }).then(function (response) {
            return response.text();
        }).then(parseJsonResponse);
    }

    function executeEmbeddedScripts(container) {
        if (!container) {
            return;
        }

        Array.prototype.forEach.call(container.querySelectorAll("script"), function (script) {
            var replacement = document.createElement("script");

            Array.prototype.forEach.call(script.attributes, function (attribute) {
                replacement.setAttribute(attribute.name, attribute.value);
            });

            if (!replacement.src) {
                replacement.textContent = script.textContent || "";
            }

            script.parentNode.replaceChild(replacement, script);
        });
    }

    function setActiveScopeButtons(target) {
        document.querySelectorAll("[data-profile-scope-target]").forEach(function (item) {
            var isActive = item.getAttribute("data-profile-scope-target") === target;
            item.classList.toggle("is-active", isActive);
            item.classList.toggle("generic-action-button--main", isActive);
            item.classList.toggle("generic-action-button--secondary", !isActive);
        });
    }

    function buildProfileModalUrl(scope) {
        return "/popup/profil.php?scope=" + encodeURIComponent(scope === "organization" ? "organization" : "general");
    }

    function loadProfileScope(target) {
        if (!scopeContainer) {
            return;
        }

        var normalizedTarget = target === "organization" ? "organization" : "general";
        var scopeUrl = scopeContainer.getAttribute(
            normalizedTarget === "organization"
                ? "data-profile-scope-url-organization"
                : "data-profile-scope-url-general"
        );

        scopeContainer.setAttribute("data-profile-scope-active", normalizedTarget);
        scopeContainer.setAttribute("data-profile-scope-panel", normalizedTarget);
        scopeContainer.innerHTML = '<div class="profile-panel__feedback">' + scopeLoadingMessage + '</div>';
        setActiveScopeButtons(normalizedTarget);

        fetch(scopeUrl, {
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Erreur de chargement");
                }

                return response.text();
            })
            .then(function (html) {
                scopeContainer.innerHTML = html;
                executeEmbeddedScripts(scopeContainer);
            })
            .catch(function () {
                scopeContainer.innerHTML = '<div class="profile-panel__feedback is-error">' + scopeLoadErrorMessage + '</div>';
            });
    }

    window.profileHandleGeneralSaved = function () {
        var targetUrl = buildProfileModalUrl("general");
        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(targetUrl);
        }
        if (window.jQuery && document.getElementById("popup_content")) {
            window.jQuery("#popup_content").load(targetUrl);
        }
    };

    window.profileHandleOrganizationSaved = function () {
        var targetUrl = buildProfileModalUrl("organization");
        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(targetUrl);
        }
        if (window.jQuery && document.getElementById("popup_content")) {
            window.jQuery("#popup_content").load(targetUrl);
        }
    };

    document.querySelectorAll("[data-profile-scope-target]").forEach(function (button) {
        button.addEventListener("click", function () {
            loadProfileScope(button.getAttribute("data-profile-scope-target"));
        });
    });

    if (scopeContainer) {
        loadProfileScope(initialScope);
    }

    var connectButton = document.getElementById("patreon_connect");
    if (connectButton) {
        connectButton.addEventListener("click", function () {
            var width = 720;
            var height = 860;
            var left = Math.max(0, (window.screen.width - width) / 2);
            var top = Math.max(0, (window.screen.height - height) / 2);
            window.open(
                "/common/patreon_connect.php",
                "patreon_connect",
                "width=" + width + ",height=" + height + ",left=" + left + ",top=" + top + ",resizable=yes,scrollbars=yes"
            );
        });
    }

    var disconnectButton = document.getElementById("patreon_disconnect");
    if (disconnectButton) {
        disconnectButton.addEventListener("click", function () {
            if (!confirm(disconnectConfirmMessage)) {
                return;
            }

            disconnectButton.disabled = true;
            postUrl("/ajax/patreon_disconnect.php")
                .then(handleLegacyAjaxResponse)
                .finally(function () {
                    disconnectButton.disabled = false;
                });
        });
    }

    var syncButton = document.getElementById("patreon_sync");
    if (syncButton) {
        syncButton.addEventListener("click", function () {
            syncButton.disabled = true;
            postUrl("/ajax/patreon_sync.php")
                .then(handleLegacyAjaxResponse)
                .finally(function () {
                    syncButton.disabled = false;
                });
        });
    }

    window.addEventListener("message", function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }

        if (event.data && event.data.type === "patreon-connected") {
            var currentScope = scopeContainer ? (scopeContainer.getAttribute("data-profile-scope-active") || "general") : "general";
            var targetUrl = buildProfileModalUrl(currentScope);
            if (window.commonTopbarRefreshModalContent) {
                window.commonTopbarRefreshModalContent(targetUrl);
            }
            if (window.jQuery && document.getElementById("popup_content")) {
                window.jQuery("#popup_content").load(targetUrl);
            }
        }
    });
})();
</script>
