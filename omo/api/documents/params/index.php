<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 4) . '/common/object_visibility_selector.php';

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
$documentStorageConfig = $organizationLoaded
    ? omoDocumentsParamsGetDocumentStorageConfig($organization, $organizationApplication)
    : omoDocumentsParamsNormalizeDocumentStorageConfig(array());
$storageConfigured = $organizationLoaded && omoDocumentsParamsHasDocumentStorageConfig($documentStorageConfig);
$storageType = (string)($documentStorageConfig['type'] ?? '');
$nextcloudConfig = $storageType === 'nextcloud'
    ? omoDocumentsParamsNormalizeNextcloudConfig($documentStorageConfig)
    : omoDocumentsParamsNormalizeNextcloudConfig(array());
$kdriveConfig = $storageType === 'kdrive'
    ? omoDocumentsParamsNormalizeKdriveConfig($documentStorageConfig)
    : omoDocumentsParamsNormalizeKdriveConfig(array());
$collaboraConfig = $organizationLoaded
    ? omoDocumentsParamsGetCollaboraConfig($organization, $organizationApplication)
    : omoCollaboraNormalizeConfig(array());
$collaboraConfigured = $organizationLoaded && $storageConfigured && omoDocumentsParamsHasCollaboraConfig($collaboraConfig);
$usesLegacyConfig = $organizationLoaded
    ? omoDocumentsParamsUsesLegacyNextcloudConfig($organization, $organizationApplication)
    : false;
$visibilityDefaults = $organizationLoaded
    ? omoDocumentsParamsGetVisibilityDefaults($organization, $organizationApplication)
    : \dbObject\Document::getApplicationDefaultScopeTypes(0);
$iconUrl = $applicationIcon !== '' ? $applicationIcon : 'images/tools/documents-folder.png';
?>
<div class="omo-documents-params generic-stack generic-stack--roomy" data-omo-documents-params-root>
    <section class="generic-section generic-section--stack generic-section--roomy omo-documents-params__panel">
        <div class="omo-documents-params__hero">
            <span class="omo-documents-params__icon-shell">
                <img src="<?= htmlspecialchars($iconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="omo-documents-params__icon black-icon" loading="lazy">
            </span>
            <div class="omo-documents-params__copy">
                <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?></div>
                <h2 class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="omo-documents-params__description generic-description"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.description'), ENT_QUOTES, 'UTF-8') ?></p>
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
            <div class="omo-documents-params__status generic-soft-panel<?= $storageConfigured ? ' is-ready' : '' ?>">
                <?= htmlspecialchars(
                    $storageConfigured
                        ? omoDocumentsParamsT('documents.params.status.storage_ready')
                        : omoDocumentsParamsT('documents.params.status.storage_empty'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="omo-documents-params__status generic-soft-panel<?= $collaboraConfigured ? ' is-ready' : '' ?>">
                <?= htmlspecialchars(
                    $collaboraConfigured
                        ? omoDocumentsParamsT('documents.params.status.collabora_ready')
                        : ($storageConfigured
                            ? omoDocumentsParamsT('documents.params.status.collabora_empty')
                            : omoDocumentsParamsT('documents.params.status.collabora_requires_storage')),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <?php if ($usesLegacyConfig): ?>
                <div class="omo-documents-params__legacy generic-soft-panel">
                    <?= htmlspecialchars(omoDocumentsParamsT('documents.params.status.legacy'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form class="omo-documents-params__form generic-form-stack" data-omo-documents-params-form action="/omo/api/documents/params/save.php" method="post">
                <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">

                <div class="omo-documents-params__grid generic-form-grid">
                    <h3 class="generic-card-title generic-card-title--small generic-form-field--full">
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.section.storage'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <label class="omo-documents-params__checkbox omo-documents-params__field--full generic-form-field--full">
                        <input
                            type="checkbox"
                            name="document_storage_enabled"
                            value="1"
                            data-omo-documents-storage-toggle
                            <?= $storageConfigured ? ' checked' : '' ?>
                        >
                        <span><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.storage_enabled'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <p class="omo-documents-params__hint generic-help-text generic-form-field--full">
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.storage_enabled_hint'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <fieldset class="omo-documents-params__storage-choice generic-form-field--full" data-omo-documents-storage-choice<?= $storageConfigured ? '' : ' hidden' ?>>
                        <legend class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.storage_type'), ENT_QUOTES, 'UTF-8') ?></legend>
                        <label class="omo-documents-params__radio">
                            <input type="radio" name="storage_type" value="nextcloud" data-omo-documents-storage-type<?= $storageType === 'nextcloud' || !$storageConfigured ? ' checked' : '' ?>>
                            <span><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.storage_type_nextcloud'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                        <label class="omo-documents-params__radio">
                            <input type="radio" name="storage_type" value="kdrive" data-omo-documents-storage-type<?= $storageType === 'kdrive' ? ' checked' : '' ?>>
                            <span><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.storage_type_kdrive'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    </fieldset>

                    <div class="omo-documents-params__storage-fields generic-form-grid generic-form-field--full" data-omo-documents-nextcloud-fields<?= $storageType === 'nextcloud' || !$storageConfigured ? '' : ' hidden' ?>>

                    <label class="omo-documents-params__field omo-documents-params__field--full generic-form-field generic-form-field--full">
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
                        <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.base_url_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>

                    <label class="omo-documents-params__field generic-form-field">
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

                    <label class="omo-documents-params__field generic-form-field">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.password'), ENT_QUOTES, 'UTF-8') ?></span>
                        <input
                            type="password"
                            name="nextcloud_app_password"
                            class="generic-form-control"
                            maxlength="255"
                            autocomplete="new-password"
                            placeholder="<?= htmlspecialchars(
                                ($storageConfigured && $storageType === 'nextcloud')
                                    ? omoDocumentsParamsT('documents.params.field.password_placeholder_keep')
                                    : omoDocumentsParamsT('documents.params.field.password_placeholder_new'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                        <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.password_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>

                    <label class="omo-documents-params__field omo-documents-params__field--full generic-form-field generic-form-field--full">
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
                        <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.folder_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>

                    <div class="omo-documents-params__test-actions generic-form-actions generic-form-field--full">
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-documents-nextcloud-test>
                            <?= htmlspecialchars(omoDocumentsParamsT('documents.params.action.test_storage'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <span class="omo-documents-params__test-feedback generic-help-text" data-omo-documents-nextcloud-test-feedback aria-live="polite"></span>
                    </div>
                    </div>

                    <div class="omo-documents-params__storage-fields generic-form-grid generic-form-field--full" data-omo-documents-kdrive-fields<?= $storageType === 'kdrive' ? '' : ' hidden' ?>>
                        <label class="omo-documents-params__field generic-form-field">
                            <span class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.kdrive_id'), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="text" name="kdrive_id" class="generic-form-control" maxlength="30" inputmode="numeric" autocomplete="off" placeholder="123456" value="<?= htmlspecialchars((string)$kdriveConfig['driveId'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.kdrive_id_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                        <label class="omo-documents-params__field generic-form-field">
                            <span class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.kdrive_username'), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="email" name="kdrive_username" class="generic-form-control" maxlength="255" autocomplete="username" value="<?= htmlspecialchars((string)$kdriveConfig['username'], ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="omo-documents-params__field generic-form-field">
                            <span class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.kdrive_password'), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="password" name="kdrive_app_password" class="generic-form-control" maxlength="255" autocomplete="new-password" placeholder="<?= htmlspecialchars($storageType === 'kdrive' ? omoDocumentsParamsT('documents.params.field.kdrive_password_placeholder_keep') : omoDocumentsParamsT('documents.params.field.kdrive_password_placeholder_new'), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.kdrive_password_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                        <label class="omo-documents-params__field omo-documents-params__field--full generic-form-field generic-form-field--full">
                            <span class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.folder'), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="text" name="kdrive_folder" class="generic-form-control" maxlength="255" autocomplete="off" placeholder="Documents/OMO" value="<?= htmlspecialchars((string)$kdriveConfig['folder'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.kdrive_folder_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                        <div class="omo-documents-params__test-actions generic-form-actions generic-form-field--full">
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-documents-storage-test>
                                <?= htmlspecialchars(omoDocumentsParamsT('documents.params.action.test_storage'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <span class="omo-documents-params__test-feedback generic-help-text" data-omo-documents-storage-test-feedback aria-live="polite"></span>
                        </div>
                    </div>

                    <h3 class="generic-card-title generic-card-title--small generic-form-field--full">
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.section.collabora'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <label class="omo-documents-params__checkbox omo-documents-params__field--full generic-form-field--full">
                        <input
                            type="checkbox"
                            name="collabora_enabled"
                            value="1"
                            data-omo-documents-collabora-toggle
                            <?= $collaboraConfigured ? ' checked' : '' ?>
                            <?= !$storageConfigured && !$collaboraConfigured ? ' disabled' : '' ?>
                        >
                        <span><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.collabora_enabled'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <p class="omo-documents-params__hint generic-help-text generic-form-field--full">
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.collabora_enabled_hint'), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <div class="omo-documents-params__collabora-fields generic-form-grid generic-form-field--full" data-omo-documents-collabora-fields<?= $collaboraConfigured ? '' : ' hidden' ?>>
                        <label class="omo-documents-params__field generic-form-field">
                            <span class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.collabora_base_url'), ENT_QUOTES, 'UTF-8') ?></span>
                            <input
                                type="url"
                                name="collabora_base_url"
                                class="generic-form-control"
                                maxlength="500"
                                autocomplete="off"
                                placeholder="https://document.example.org"
                                value="<?= htmlspecialchars((string)$collaboraConfig['baseUrl'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                            <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.collabora_base_url_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>

                        <label class="omo-documents-params__field generic-form-field">
                            <span class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.collabora_internal_url'), ENT_QUOTES, 'UTF-8') ?></span>
                            <input
                                type="url"
                                name="collabora_internal_url"
                                class="generic-form-control"
                                maxlength="500"
                                autocomplete="off"
                                placeholder="http://collabora:9980"
                                value="<?= htmlspecialchars((string)($collaboraConfig['internalUrl'] !== $collaboraConfig['baseUrl'] ? $collaboraConfig['internalUrl'] : ''), ENT_QUOTES, 'UTF-8') ?>"
                            >
                            <span class="omo-documents-params__hint generic-help-text"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.collabora_internal_url_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>

                        <p class="omo-documents-params__hint generic-help-text generic-form-field--full">
                            <?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.collabora_appearance_hint'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div class="omo-documents-params__field omo-documents-params__field--full generic-form-field generic-form-field--full">
                        <?= commonRenderObjectVisibilitySelector(array(
                            'inputName' => 'default_visibility_type',
                            'fieldLabel' => omoDocumentsParamsT('documents.params.field.default_visibility'),
                            'ariaLabel' => omoDocumentsParamsT('documents.params.field.default_visibility'),
                            'selectedValue' => (string)($visibilityDefaults['visibilityType'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION),
                            'optionLabels' => \dbObject\ObjectVisibility::getVisibilityTypeOptions(),
                            'idPrefix' => 'omo-documents-default-visibility',
                            'hint' => omoDocumentsParamsT('documents.params.field.default_visibility_hint'),
                        )) ?>
                    </div>

                    <div class="omo-documents-params__field omo-documents-params__field--full generic-form-field generic-form-field--full">
                        <?= commonRenderObjectVisibilitySelector(array(
                            'inputName' => 'default_edit_visibility_type',
                            'fieldLabel' => omoDocumentsParamsT('documents.params.field.default_edit_visibility'),
                            'ariaLabel' => omoDocumentsParamsT('documents.params.field.default_edit_visibility'),
                            'selectedValue' => (string)($visibilityDefaults['editVisibilityType'] ?? \dbObject\Document::getDefaultEditVisibilityType()),
                            'optionLabels' => \dbObject\ObjectVisibility::getVisibilityTypeOptions(),
                            'idPrefix' => 'omo-documents-default-edit-visibility',
                            'hint' => omoDocumentsParamsT('documents.params.field.default_edit_visibility_hint'),
                        )) ?>
                    </div>

                </div>

                <div class="omo-documents-params__actions generic-form-actions">
                    <button type="submit" class="generic-action-button generic-action-button--main" data-omo-documents-params-submit>
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.action.save'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>

                <div class="omo-documents-params__feedback generic-soft-panel generic-feedback" data-omo-documents-params-feedback hidden></div>
            </form>
        <?php endif; ?>
    </section>
</div>

<style>
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
    border-radius: var(--radius-md);
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

.omo-documents-params__status,
.omo-documents-params__legacy {
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

.omo-documents-params__checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--color-text, #0f172a);
}

.omo-documents-params__storage-choice {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 20px;
    margin: 0;
    padding: 12px 14px;
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: var(--radius-md);
}

.omo-documents-params__storage-choice legend {
    width: 100%;
    padding: 0;
}

.omo-documents-params__radio {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.omo-documents-params__storage-fields[hidden],
.omo-documents-params__storage-choice[hidden] {
    display: none;
}

.omo-documents-params__feedback {
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

.omo-documents-params__test-actions {
    align-items: center;
}

.omo-documents-params__test-feedback.is-success {
    color: #166534;
}

.omo-documents-params__test-feedback.is-error {
    color: #b91c1c;
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
        var storageToggle = root.querySelector('[data-omo-documents-storage-toggle]');
        var storageChoice = root.querySelector('[data-omo-documents-storage-choice]');
        var storageTypeInputs = root.querySelectorAll('[data-omo-documents-storage-type]');
        var nextcloudFields = root.querySelector('[data-omo-documents-nextcloud-fields]');
        var kdriveFields = root.querySelector('[data-omo-documents-kdrive-fields]');
        var nextcloudTestButton = root.querySelector('[data-omo-documents-nextcloud-test]');
        var nextcloudTestFeedback = root.querySelector('[data-omo-documents-nextcloud-test-feedback]');
        var kdriveTestButton = root.querySelector('[data-omo-documents-storage-test]');
        var kdriveTestFeedback = root.querySelector('[data-omo-documents-storage-test-feedback]');
        var collaboraToggle = root.querySelector('[data-omo-documents-collabora-toggle]');
        var collaboraFields = root.querySelector('[data-omo-documents-collabora-fields]');
        var idleLabel = <?= json_encode(omoDocumentsParamsT('documents.params.action.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var busyLabel = <?= json_encode(omoDocumentsParamsT('documents.params.action.saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var storageTestLabel = <?= json_encode(omoDocumentsParamsT('documents.params.action.test_storage'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var storageTestingLabel = <?= json_encode(omoDocumentsParamsT('documents.params.action.testing_storage'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        if (!form || !submitButton || !feedback) {
            return;
        }

        function getSelectedStorageType() {
            var selected = root.querySelector('[data-omo-documents-storage-type]:checked');
            return selected ? selected.value : 'nextcloud';
        }

        function syncStorageFields() {
            var enabled = !!(storageToggle && storageToggle.checked);
            if (storageChoice) {
                storageChoice.hidden = !enabled;
            }
            var selectedType = getSelectedStorageType();
            if (nextcloudFields) {
                nextcloudFields.hidden = !enabled || selectedType !== 'nextcloud';
            }
            if (kdriveFields) {
                kdriveFields.hidden = !enabled || selectedType !== 'kdrive';
            }
            if (collaboraToggle && !enabled) {
                collaboraToggle.checked = false;
                collaboraToggle.disabled = true;
            } else if (collaboraToggle) {
                collaboraToggle.disabled = false;
            }
        }

        function syncCollaboraFields() {
            if (!collaboraFields || !collaboraToggle) {
                return;
            }

            collaboraFields.hidden = !collaboraToggle.checked;
        }

        if (storageToggle) {
            storageToggle.addEventListener('change', syncStorageFields);
        }
        storageTypeInputs.forEach(function (input) {
            input.addEventListener('change', syncStorageFields);
        });
        syncStorageFields();

        if (collaboraToggle) {
            collaboraToggle.addEventListener('change', syncCollaboraFields);
            syncCollaboraFields();
        }

        function testStorage(button, testFeedback) {
            if (!button) {
                return;
            }

            button.addEventListener('click', function () {
                var formData = new FormData(form);
                button.disabled = true;
                button.textContent = storageTestingLabel;
                if (testFeedback) {
                    testFeedback.textContent = '';
                    testFeedback.classList.remove('is-error', 'is-success');
                }

                window.fetch('/omo/api/documents/params/test_storage.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json().catch(function () {
                        return {
                            status: false,
                            message: <?= json_encode(omoDocumentsParamsT('documents.params.feedback.storage_test_invalid'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                        };
                    });
                }).then(function (payload) {
                    var status = !!(payload && payload.status);
                    if (testFeedback) {
                        testFeedback.textContent = String(payload && payload.message ? payload.message : '');
                        testFeedback.classList.toggle('is-error', !status);
                        testFeedback.classList.toggle('is-success', status);
                    }
                }).catch(function () {
                    if (testFeedback) {
                        testFeedback.textContent = <?= json_encode(omoDocumentsParamsT('documents.params.feedback.storage_test_invalid'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        testFeedback.classList.add('is-error');
                        testFeedback.classList.remove('is-success');
                    }
                }).finally(function () {
                    button.disabled = false;
                    button.textContent = storageTestLabel;
                });
            });
        }

        testStorage(nextcloudTestButton, nextcloudTestFeedback);
        testStorage(kdriveTestButton, kdriveTestFeedback);

        function renderFeedback(message, isError) {
            if (typeof window.commonNotify === 'function') {
                window.commonNotify(String(message || ''), isError ? 'error' : 'success');
                return;
            }

            feedback.hidden = false;
            feedback.textContent = String(message || '');
            feedback.classList.toggle('is-error', !!isError);
            feedback.classList.toggle('is-success', !isError);
        }

        function sendSettings(formData) {
            return window.fetch(form.getAttribute('action') || '/omo/api/documents/params/save.php', {
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
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(form);
            submitButton.disabled = true;
            submitButton.textContent = busyLabel;
            feedback.hidden = true;
            feedback.classList.remove('is-error', 'is-success');

            sendSettings(formData).then(function (payload) {
                if (payload && payload.confirmationRequired) {
                    if (window.confirm(String(payload.message || ''))) {
                        var confirmedFormData = new FormData(form);
                        confirmedFormData.append('storage_change_confirmed', '1');
                        return sendSettings(confirmedFormData);
                    }
                    return payload;
                }

                return payload;
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
