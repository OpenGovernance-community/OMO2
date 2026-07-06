<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
$organizationLoaded = $organizationId > 0 && $organization->load($organizationId);
$canManage = $organizationLoaded && omoDocumentsParamsCanManage($organizationId, $currentUserId);
$organizationApplication = $organizationLoaded ? omoDocumentsParamsGetApplicationLink($organizationId, false) : null;
$application = $organizationApplication ? $organizationApplication->get('application') : null;
$applicationLabel = $application && (int)$application->getId() > 0
    ? trim((string)$application->get('label'))
    : 'Documents';
$applicationIcon = $application && (int)$application->getId() > 0
    ? trim((string)$application->get('icon'))
    : 'images/tools/documents-folder.png';
$nextcloudConfig = $organizationLoaded
    ? omoDocumentsParamsGetNextcloudConfig($organization, $organizationApplication, true)
    : omoDocumentsParamsNormalizeNextcloudConfig(array());
$nextcloudConfigured = omoDocumentsParamsHasNextcloudConfig($nextcloudConfig);
$usesLegacyConfig = $organizationLoaded
    ? omoDocumentsParamsUsesLegacyNextcloudConfig($organization, $organizationApplication)
    : false;
$iconUrl = $applicationIcon !== '' ? $applicationIcon : 'images/tools/documents-folder.png';
?>
<div class="omo-documents-params" data-omo-documents-params-root>
    <section class="generic-section generic-section--stack omo-documents-params__panel">
        <div class="omo-documents-params__hero">
            <span class="omo-documents-params__icon-shell">
                <img src="<?= htmlspecialchars($iconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="omo-documents-params__icon black-icon" loading="lazy">
            </span>
            <div class="omo-documents-params__copy">
                <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?></div>
                <h2 class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="omo-documents-params__description"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.description'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <?php if ($currentUserId <= 0): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.error.login'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$organizationLoaded): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.error.organization'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$organizationApplication): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.error.unavailable'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$canManage): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.error.forbidden'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <div class="omo-documents-params__status<?= $nextcloudConfigured ? ' is-ready' : '' ?>">
                <?= htmlspecialchars(
                    $nextcloudConfigured
                        ? omoDocumentsParamsT('documents.params.status.ready')
                        : omoDocumentsParamsT('documents.params.status.empty'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <?php if ($usesLegacyConfig): ?>
                <div class="omo-documents-params__legacy">
                    <?= htmlspecialchars(omoDocumentsParamsT('documents.params.status.legacy'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form class="omo-documents-params__form" data-omo-documents-params-form action="/omo/api/documents/params/save.php" method="post">
                <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">

                <div class="omo-documents-params__grid">
                    <label class="omo-documents-params__field omo-documents-params__field--full">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.base_url'), ENT_QUOTES, 'UTF-8') ?></span>
                        <input
                            type="url"
                            name="nextcloud_base_url"
                            class="generic-form-control"
                            maxlength="500"
                            autocomplete="off"
                            placeholder="https://cloud.example.com/nextcloud"
                            value="<?= htmlspecialchars((string)$nextcloudConfig['baseUrl'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <span class="omo-documents-params__hint"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.base_url_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>

                    <label class="omo-documents-params__field">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.username'), ENT_QUOTES, 'UTF-8') ?></span>
                        <input
                            type="text"
                            name="nextcloud_username"
                            class="generic-form-control"
                            maxlength="150"
                            autocomplete="off"
                            placeholder="nom.utilisateur"
                            value="<?= htmlspecialchars((string)$nextcloudConfig['username'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </label>

                    <label class="omo-documents-params__field">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.password'), ENT_QUOTES, 'UTF-8') ?></span>
                        <input
                            type="password"
                            name="nextcloud_app_password"
                            class="generic-form-control"
                            maxlength="255"
                            autocomplete="new-password"
                            placeholder="<?= htmlspecialchars(
                                $nextcloudConfigured
                                    ? omoDocumentsParamsT('documents.params.field.password_placeholder_keep')
                                    : omoDocumentsParamsT('documents.params.field.password_placeholder_new'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                        <span class="omo-documents-params__hint"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.password_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>

                    <label class="omo-documents-params__field omo-documents-params__field--full">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.folder'), ENT_QUOTES, 'UTF-8') ?></span>
                        <input
                            type="text"
                            name="nextcloud_folder"
                            class="generic-form-control"
                            maxlength="255"
                            autocomplete="off"
                            placeholder="Documents/OMO"
                            value="<?= htmlspecialchars((string)$nextcloudConfig['folder'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <span class="omo-documents-params__hint"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.folder_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>

                    <?php if ($nextcloudConfigured): ?>
                        <label class="omo-documents-params__checkbox omo-documents-params__field--full">
                            <input type="checkbox" name="nextcloud_clear_config" value="1">
                            <span><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.clear'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endif; ?>
                </div>

                <div class="omo-documents-params__actions">
                    <button type="submit" class="generic-action-button generic-action-button--main" data-omo-documents-params-submit>
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.action.save'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

                <div class="omo-documents-params__feedback" data-omo-documents-params-feedback hidden></div>
            </form>
        <?php endif; ?>
    </section>
</div>

<style>
.omo-documents-params {
    display: grid;
    gap: 18px;
}

.omo-documents-params__panel {
    --generic-section-padding-block: 18px;
    --generic-section-padding-inline: 18px;
}

.omo-documents-params__hero {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.omo-documents-params__icon-shell {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    min-width: 64px;
    border-radius: 18px;
    background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-border, #d1d5db));
}

.omo-documents-params__icon {
    width: 34px;
    height: 34px;
    object-fit: contain;
}

.omo-documents-params__copy {
    display: grid;
    gap: 6px;
}

.omo-documents-params__description {
    margin: 0;
    color: var(--color-text-light, #64748b);
    line-height: 1.55;
}

.omo-documents-params__status,
.omo-documents-params__legacy {
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid var(--color-border, #dbe4ee);
    background: var(--color-surface-alt, #f8fafc);
    color: var(--color-text-light, #475569);
}

.omo-documents-params__status.is-ready {
    color: #166534;
    border-color: color-mix(in srgb, #16a34a 18%, var(--color-border, #dbe4ee));
    background: color-mix(in srgb, #16a34a 8%, var(--color-surface, #ffffff));
}

.omo-documents-params__legacy {
    color: #92400e;
    border-color: color-mix(in srgb, #f59e0b 18%, var(--color-border, #dbe4ee));
    background: color-mix(in srgb, #f59e0b 8%, var(--color-surface, #ffffff));
}

.omo-documents-params__form {
    display: grid;
    gap: 16px;
}

.omo-documents-params__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.omo-documents-params__field {
    display: grid;
    gap: 8px;
}

.omo-documents-params__field--full {
    grid-column: 1 / -1;
}

.omo-documents-params__hint {
    color: var(--color-text-light, #64748b);
    font-size: 0.84rem;
    line-height: 1.45;
}

.omo-documents-params__checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--color-text, #0f172a);
}

.omo-documents-params__actions {
    display: flex;
    justify-content: flex-end;
}

.omo-documents-params__feedback {
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid var(--color-border, #dbe4ee);
    background: var(--color-surface-alt, #f8fafc);
    color: var(--color-text-light, #475569);
}

.omo-documents-params__feedback.is-success {
    color: #166534;
    border-color: color-mix(in srgb, #16a34a 18%, var(--color-border, #dbe4ee));
    background: color-mix(in srgb, #16a34a 8%, var(--color-surface, #ffffff));
}

.omo-documents-params__feedback.is-error {
    color: #b91c1c;
    border-color: color-mix(in srgb, #dc2626 18%, var(--color-border, #dbe4ee));
    background: color-mix(in srgb, #dc2626 8%, var(--color-surface, #ffffff));
}

@media (max-width: 720px) {
    .omo-documents-params__hero {
        flex-direction: column;
    }

    .omo-documents-params__grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function () {
    document.querySelectorAll('[data-omo-documents-params-root]').forEach(function (root) {
        if (!root || root.dataset.omoDocumentsParamsReady === '1') {
            return;
        }

        root.dataset.omoDocumentsParamsReady = '1';

        var form = root.querySelector('[data-omo-documents-params-form]');
        var submitButton = root.querySelector('[data-omo-documents-params-submit]');
        var feedback = root.querySelector('[data-omo-documents-params-feedback]');
        var idleLabel = <?= json_encode(omoDocumentsParamsT('documents.params.action.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var busyLabel = <?= json_encode(omoDocumentsParamsT('documents.params.action.saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        if (!form || !submitButton || !feedback) {
            return;
        }

        function renderFeedback(message, isError) {
            feedback.hidden = false;
            feedback.textContent = String(message || '');
            feedback.classList.toggle('is-error', !!isError);
            feedback.classList.toggle('is-success', !isError);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(form);
            submitButton.disabled = true;
            submitButton.textContent = busyLabel;
            feedback.hidden = true;
            feedback.classList.remove('is-error', 'is-success');

            window.fetch(form.getAttribute('action') || '/omo/api/documents/params/save.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        status: false,
                        message: 'Invalid response.'
                    };
                });
            }).then(function (payload) {
                var status = !!(payload && payload.status);
                renderFeedback(payload && payload.message ? payload.message : (status ? idleLabel : 'Error'), !status);
            }).catch(function () {
                renderFeedback(<?= json_encode(omoDocumentsParamsT('documents.params.error.save_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, true);
            }).finally(function () {
                submitButton.disabled = false;
                submitButton.textContent = idleLabel;
            });
        });
    });
})();
</script>
