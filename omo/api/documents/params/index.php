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
$pvSettings = $organizationLoaded
    ? $organization->getPvDocumentSettings()
    : array('enabled' => true, 'priorityLabels' => \dbObject\Organization::normalizePvPriorityLabels(array()));
$pvEnabled = !empty($pvSettings['enabled']);
$pvPriorityLabels = is_array($pvSettings['priorityLabels'] ?? null)
    ? $pvSettings['priorityLabels']
    : \dbObject\Organization::normalizePvPriorityLabels(array());
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
            <div class="omo-documents-params__copy generic-stack generic-stack--compact">
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

                    <label class="omo-documents-params__checkbox generic-checkbox omo-documents-params__field--full generic-form-field--full">
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
                        <p class="omo-documents-params__hint generic-help-text generic-form-field--full">
                            <?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.kdrive_subscription_hint'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
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
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.section.pv'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <label class="omo-documents-params__checkbox generic-checkbox omo-documents-params__field--full generic-form-field--full">
                        <input
                            type="checkbox"
                            name="pv_enabled"
                            value="1"
                            data-omo-documents-pv-toggle
                            <?= $pvEnabled ? ' checked' : '' ?>
                        >
                        <span><?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.pv_enabled'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <p class="omo-documents-params__hint generic-help-text generic-form-field--full">
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.field.pv_enabled_hint'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <div class="omo-documents-params__pv-fields generic-soft-panel generic-form-grid generic-form-field--full" data-omo-documents-pv-fields<?= $pvEnabled ? '' : ' hidden' ?>>
                        <h4 class="generic-card-title generic-card-title--small generic-form-field--full">
                            <?= htmlspecialchars(omoDocumentsParamsT('documents.params.pv.priority_labels'), ENT_QUOTES, 'UTF-8') ?>
                        </h4>
                        <?php for ($priority = 1; $priority <= 5; $priority++): ?>
                            <label class="omo-documents-params__field generic-form-field">
                                <span class="generic-form-label"><?= htmlspecialchars(omoDocumentsParamsT('documents.params.pv.priority_label', array('priority' => 'P' . $priority)), ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="text" name="pv_priority_label_<?= $priority ?>" class="generic-form-control" maxlength="60" value="<?= htmlspecialchars((string)($pvPriorityLabels[$priority] ?? 'P' . $priority), ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                        <?php endfor; ?>
                        <p class="omo-documents-params__hint generic-help-text generic-form-field--full">
                            <?= htmlspecialchars(omoDocumentsParamsT('documents.params.pv.priority_hint'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <h3 class="generic-card-title generic-card-title--small generic-form-field--full">
                        <?= htmlspecialchars(omoDocumentsParamsT('documents.params.section.collabora'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <label class="omo-documents-params__checkbox generic-checkbox omo-documents-params__field--full generic-form-field--full">
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

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/documents/params/parameters.css') ?>">

<?= commonPageScriptTags('/omo/api/documents/params/index.js', [
    'idleLabel' => omoDocumentsParamsT('documents.params.action.save'),
    'busyLabel' => omoDocumentsParamsT('documents.params.action.saving'),
    'storageTestLabel' => omoDocumentsParamsT('documents.params.action.test_storage'),
    'storageTestingLabel' => omoDocumentsParamsT('documents.params.action.testing_storage'),
    'message' => omoDocumentsParamsT('documents.params.feedback.storage_test_invalid'),
    'documentsParamsErrorSaveFailed' => omoDocumentsParamsT('documents.params.error.save_failed'),
]) ?>
