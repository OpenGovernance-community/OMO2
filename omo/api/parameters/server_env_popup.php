<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/translation.php';
require_once dirname(__DIR__, 3) . '/includes/server_env_admin.php';

$currentUserId = (int)commonGetCurrentUserId();
$isSiteAdmin = commonCurrentUserIsSiteAdminModeEnabled();
$isUnlocked = $isSiteAdmin && serverEnvAdminIsUnlocked($currentUserId);
$hasLocalPassword = $isSiteAdmin && serverEnvAdminHasLocalPassword($currentUserId);
$unlockTtlMinutes = (int)max(1, round(serverEnvAdminGetUnlockTtlSeconds() / 60));
$serverEnvTargetLabel = serverEnvAdminGetEnvTargetLabel();

if ($currentUserId <= 0) {
    http_response_code(401);
    ?>
    <div class="omo-server-env-popup">
        <div class="generic-section generic-section--stack omo-server-env-popup__error">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.auth.required_title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(omoServerEnvT('parameters.server_env.auth.required_message'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <?php
    exit;
}

if (!$isSiteAdmin) {
    http_response_code(403);
    ?>
    <div class="omo-server-env-popup">
        <div class="generic-section generic-section--stack omo-server-env-popup__error">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.auth.forbidden_title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(omoServerEnvT('parameters.server_env.auth.forbidden_message'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <?php
    exit;
}

$serverEnvSections = $isUnlocked ? serverEnvAdminGetEditableSections() : array();
$serverEnvActualValues = $isUnlocked ? serverEnvAdminBuildCurrentValues() : array();
$serverEnvDisplayValues = $isUnlocked ? serverEnvAdminBuildDisplayValues($serverEnvActualValues) : array();
$serverEnvSecretStates = $isUnlocked ? serverEnvAdminBuildSecretStateMap($serverEnvActualValues) : array();
$serverEnvClientTexts = [
    'invalidResponse' => omoServerEnvT('parameters.server_env.feedback.invalid_response'),
    'unlockFailed' => omoServerEnvT('parameters.server_env.feedback.unlock_failed'),
    'unlockSuccess' => omoServerEnvT('parameters.server_env.feedback.unlock_success'),
    'operationDone' => omoServerEnvT('parameters.server_env.feedback.operation_done'),
    'saveFailed' => omoServerEnvT('parameters.server_env.feedback.save_failed', ['target' => $serverEnvTargetLabel]),
    'secretConfigured' => omoServerEnvT('parameters.server_env.secret.configured'),
    'secretEmpty' => omoServerEnvT('parameters.server_env.secret.empty'),
];
?>
<div
    class="omo-server-env-popup"
    id="omoServerEnvPopup"
    data-popup-url="/omo/api/parameters/server_env_popup.php"
    data-unlock-url="/omo/api/parameters/server_env_unlock.php"
    data-save-url="/omo/api/parameters/server_env_save.php"
>
    <style>
    .omo-server-env-popup {
        display: grid;
        gap: 0;
        color: var(--color-text, #1f2937);
    }

    .omo-server-env-popup__panel,
    .omo-server-env-popup__error,
    .omo-server-env-popup__feedback {
        --generic-section-padding-block: 18px;
    }

    .omo-server-env-popup__header {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .omo-server-env-popup__header-copy {
        display: grid;
        gap: 10px;
    }

    .omo-server-env-popup__shell {
        display: grid;
        gap: 16px;
        padding: 16px 18px 18px;
    }

    .omo-server-env-popup__hero {
        display: grid;
        gap: 10px;
    }

    .omo-server-env-popup__hero p,
    .omo-server-env-popup__intro,
    .omo-server-env-popup__section-intro,
    .omo-server-env-popup__help,
    .omo-server-env-popup__hint {
        margin: 0;
        line-height: 1.5;
        color: var(--color-text-light, #6b7280);
    }

    .omo-server-env-popup__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .omo-server-env-popup__badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 24%, var(--color-border, #e5e7eb));
        color: var(--color-primary, #2563eb);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .omo-server-env-popup__error {
        border: 1px solid rgba(185, 28, 28, 0.18);
        background: rgba(185, 28, 28, 0.06);
        color: #991b1b;
    }

    .omo-server-env-popup__error p {
        margin: 0;
        color: inherit;
    }

    .omo-server-env-popup__form,
    .omo-server-env-popup__unlock-form {
        display: grid;
        gap: 16px;
    }

    .omo-server-env-popup__field {
        display: grid;
        gap: 8px;
    }

    .omo-server-env-popup__field label,
    .omo-server-env-popup__label {
        font-weight: 700;
    }

    .omo-server-env-popup__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .omo-server-env-popup__label-row {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
    }

    .omo-server-env-popup__secret-state {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 2px 9px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
    }

    .omo-server-env-popup__secret-state.is-configured {
        background: #dcfce7;
        color: #166534;
    }

    .omo-server-env-popup__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .omo-server-env-popup__feedback {
        display: none;
        border: 1px solid #d1d5db;
        background: #f8fafc;
        color: #475569;
    }

    .omo-server-env-popup__feedback.is-visible {
        display: block;
    }

    .omo-server-env-popup__feedback.is-error {
        border-color: #fecaca;
        background: #fff1f2;
        color: #9f1239;
    }

    .omo-server-env-popup__feedback.is-success {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }

    @media (max-width: 900px) {
        .omo-server-env-popup__grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <div class="omo-server-env-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-server-env-popup__header-copy omo-server-env-popup__hero">
            <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.eyebrow'), ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.description'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="omo-server-env-popup__meta">
                <span class="omo-server-env-popup__badge"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.target', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="omo-server-env-popup__badge"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.unlock_ttl', ['minutes' => $unlockTtlMinutes]), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
    <div class="omo-server-env-popup__shell">

    <?php if (!$hasLocalPassword): ?>
        <div class="omo-server-env-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.password.unavailable_title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(omoServerEnvT('parameters.server_env.password.unavailable_message', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php elseif (!$isUnlocked): ?>
        <div class="omo-server-env-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.unlock.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-server-env-popup__intro"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.unlock.description'), ENT_QUOTES, 'UTF-8') ?></p>

            <form id="omoServerEnvUnlockForm" class="omo-server-env-popup__unlock-form">
                <div class="omo-server-env-popup__field">
                    <label for="omoServerEnvUnlockPassword"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.unlock.password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="password"
                        id="omoServerEnvUnlockPassword"
                        name="password"
                        class="generic-form-control"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div id="omoServerEnvUnlockFeedback" class="omo-server-env-popup__feedback" aria-live="polite"></div>

                <div class="omo-server-env-popup__actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoServerEnvClose"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.action.close'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoServerEnvUnlockSubmit"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.unlock.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="omo-server-env-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.edit.title', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-server-env-popup__hint"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.edit.secret_hint'), ENT_QUOTES, 'UTF-8') ?></p>

            <form id="omoServerEnvForm" class="omo-server-env-popup__form">
                <?php foreach ($serverEnvSections as $section): ?>
                <section class="generic-soft-panel generic-soft-panel--stack">
                    <div>
                        <h4 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars((string)$section['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <p class="omo-server-env-popup__section-intro"><?= htmlspecialchars((string)$section['intro'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="omo-server-env-popup__grid">
                        <?php foreach ($section['fields'] as $field): ?>
                            <?php
                            $key = (string)$field['key'];
                            $fieldType = (string)($field['type'] ?? 'text');
                            $fieldValue = (string)($serverEnvDisplayValues[$key] ?? '');
                            $isSecret = !empty($field['secret']);
                            ?>
                            <label class="omo-server-env-popup__field" for="omoServerEnvField<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="omo-server-env-popup__label-row">
                                    <span class="omo-server-env-popup__label"><?= htmlspecialchars((string)$field['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isSecret): ?>
                                    <span
                                        class="omo-server-env-popup__secret-state<?= !empty($serverEnvSecretStates[$key]) ? ' is-configured' : '' ?>"
                                        data-server-env-field-status="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                    ><?= htmlspecialchars(!empty($serverEnvSecretStates[$key]) ? omoServerEnvT('parameters.server_env.secret.configured') : omoServerEnvT('parameters.server_env.secret.empty'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </span>

                                <?php if ($fieldType === 'select'): ?>
                                <select
                                    class="generic-form-control"
                                    id="omoServerEnvField<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                    name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <?php foreach (($field['options'] ?? array()) as $optionValue => $optionLabel): ?>
                                    <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>"<?= $fieldValue === (string)$optionValue ? ' selected' : '' ?>>
                                        <?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <input
                                    type="<?= htmlspecialchars($fieldType, ENT_QUOTES, 'UTF-8') ?>"
                                    id="omoServerEnvField<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                    name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                    class="generic-form-control"
                                    value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>"
                                    placeholder="<?= htmlspecialchars((string)($field['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    autocomplete="off"
                                >
                                <?php endif; ?>

                                <?php if (!empty($field['help'])): ?>
                                <span class="omo-server-env-popup__help"><?= htmlspecialchars((string)$field['help'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>

                <div id="omoServerEnvFeedback" class="omo-server-env-popup__feedback" aria-live="polite"></div>

                <div class="omo-server-env-popup__actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoServerEnvClose"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.action.close'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoServerEnvSubmit"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.action.save', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('omoServerEnvPopup');
    if (!root) {
        return;
    }

    var envTargetLabel = <?= json_encode($serverEnvTargetLabel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var texts = <?= json_encode($serverEnvClientTexts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var popupUrl = root.getAttribute('data-popup-url') || '/omo/api/parameters/server_env_popup.php';
    var unlockUrl = root.getAttribute('data-unlock-url') || '/omo/api/parameters/server_env_unlock.php';
    var saveUrl = root.getAttribute('data-save-url') || '/omo/api/parameters/server_env_save.php';

    function closeModal() {
        if (typeof window.commonTopbarCloseModal === 'function') {
            window.commonTopbarCloseModal();
        }
    }

    function refreshPopup() {
        if (typeof window.commonTopbarRefreshModalContent === 'function') {
            window.commonTopbarRefreshModalContent(popupUrl);
            return;
        }

        window.location.href = popupUrl;
    }

    function bindCloseButtons() {
        root.querySelectorAll('#omoServerEnvClose').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });
    }

    function setFeedback(node, message, type) {
        if (!node) {
            return;
        }

        node.textContent = message || '';
        node.className = 'omo-server-env-popup__feedback';

        if (!message) {
            return;
        }

        node.classList.add('is-visible');
        if (type === 'error') {
            node.classList.add('is-error');
            return;
        }

        if (type === 'success') {
            node.classList.add('is-success');
        }
    }

    bindCloseButtons();

    var unlockForm = document.getElementById('omoServerEnvUnlockForm');
    if (unlockForm) {
        var unlockFeedback = document.getElementById('omoServerEnvUnlockFeedback');
        var unlockSubmit = document.getElementById('omoServerEnvUnlockSubmit');
        var unlockInput = document.getElementById('omoServerEnvUnlockPassword');

        if (unlockInput) {
            unlockInput.focus();
        }

        unlockForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (unlockSubmit) {
                unlockSubmit.disabled = true;
            }

            setFeedback(unlockFeedback, '', '');

            fetch(unlockUrl, {
                method: 'POST',
                body: new FormData(unlockForm),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                return response.json().catch(function () {
                        return {
                            status: false,
                            message: texts.invalidResponse || ''
                        };
                    });
            })
            .then(function (payload) {
                if (!payload || !payload.status) {
                    setFeedback(unlockFeedback, payload && payload.message ? payload.message : (texts.unlockFailed || ''), 'error');
                    if (unlockInput) {
                        unlockInput.value = '';
                        unlockInput.focus();
                    }
                    return;
                }

                setFeedback(unlockFeedback, payload.message || (texts.unlockSuccess || ''), 'success');
                window.setTimeout(refreshPopup, 150);
            })
            .catch(function () {
                setFeedback(unlockFeedback, texts.unlockFailed || '', 'error');
            })
            .finally(function () {
                if (unlockSubmit) {
                    unlockSubmit.disabled = false;
                }
            });
        });

        return;
    }

    var form = document.getElementById('omoServerEnvForm');
    if (!form) {
        return;
    }

    var feedback = document.getElementById('omoServerEnvFeedback');
    var submitButton = document.getElementById('omoServerEnvSubmit');
    var firstInput = form.querySelector('input, select, textarea');
    if (firstInput) {
        firstInput.focus();
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submitButton) {
            submitButton.disabled = true;
        }

        setFeedback(feedback, '', '');

        fetch(saveUrl, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (response) {
            return response.json().catch(function () {
                return {
                    status: false,
                    message: texts.invalidResponse || ''
                };
            });
        })
        .then(function (payload) {
            if (payload && payload.requiresUnlock) {
                refreshPopup();
                return;
            }

            setFeedback(
                feedback,
                payload && payload.message ? payload.message : (texts.operationDone || ''),
                payload && payload.status ? 'success' : 'error'
            );

            if (!(payload && payload.status)) {
                return;
            }

            if (payload.configuredSecrets) {
                Object.keys(payload.configuredSecrets).forEach(function (key) {
                    var statusNode = root.querySelector('[data-server-env-field-status="' + key + '"]');
                    if (!statusNode) {
                        return;
                    }

                    var configured = !!payload.configuredSecrets[key];
                    statusNode.textContent = configured ? (texts.secretConfigured || '') : (texts.secretEmpty || '');
                    statusNode.classList.toggle('is-configured', configured);
                });
            }

            form.querySelectorAll('input[type="password"]').forEach(function (input) {
                input.value = '';
            });
        })
        .catch(function () {
            setFeedback(feedback, texts.saveFailed || ('Impossible d enregistrer le fichier ' + envTargetLabel + '.'), 'error');
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled = false;
            }
        });
    });
})();
</script>
