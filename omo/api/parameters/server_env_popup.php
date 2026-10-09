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
$serverEnvGroups = [];
foreach ($serverEnvSections as $sectionKey => $section) {
    $groupKey = in_array($sectionKey, ['ethercalc', 'etherpad', 'spacedeck'], true) ? 'documents' : $sectionKey;
    if ($sectionKey === 'transcription') $groupKey = 'ai';
    if (!empty($section['technical'])) {
        $groupKey = 'technical';
    }
    $serverEnvGroups[$groupKey]['title'] = in_array($groupKey, ['documents', 'ai'], true)
        ? omoServerEnvT('parameters.server_env.tabs.' . $groupKey)
        : (string)$section['title'];
    $serverEnvGroups[$groupKey]['sections'][$sectionKey] = $section;
}
$serverEnvTechnicalGroup = $serverEnvGroups['technical'] ?? null;
unset($serverEnvGroups['technical']);
if ($serverEnvTechnicalGroup !== null) {
    $serverEnvGroups['technical'] = $serverEnvTechnicalGroup;
}
$serverEnvServiceGroups = array_diff_key($serverEnvGroups, ['general' => true, 'technical' => true]);
$serverEnvFirstService = array_key_first($serverEnvServiceGroups);
$serverEnvActualValues = $isUnlocked ? serverEnvAdminBuildCurrentValues() : array();
$serverEnvDisplayValues = $isUnlocked ? serverEnvAdminBuildDisplayValues($serverEnvActualValues) : array();
$serverEnvSecretStates = $isUnlocked ? serverEnvAdminBuildSecretStateMap($serverEnvActualValues) : array();
$serverEnvClientTexts = [
    'invalidResponse' => omoServerEnvT('parameters.server_env.feedback.invalid_response'),
    'unlockFailed' => omoServerEnvT('parameters.server_env.feedback.unlock_failed'),
    'unlockSuccess' => omoServerEnvT('parameters.server_env.feedback.unlock_success'),
    'operationDone' => omoServerEnvT('parameters.server_env.feedback.operation_done'),
    'saveFailed' => omoServerEnvT('parameters.server_env.feedback.save_failed', ['target' => $serverEnvTargetLabel]),
    'testFailed' => omoServerEnvT('parameters.server_env.feedback.test_failed'),
    'secretConfigured' => omoServerEnvT('parameters.server_env.secret.configured'),
    'secretEmpty' => omoServerEnvT('parameters.server_env.secret.empty'),
];
?>
<div
    class="omo-server-env-popup generic-stack generic-stack--flush"
    id="omoServerEnvPopup"
    data-popup-url="/omo/api/parameters/server_env_popup.php"
    data-unlock-url="/omo/api/parameters/server_env_unlock.php"
    data-save-url="/omo/api/parameters/server_env_save.php"
    data-test-url="/omo/api/parameters/server_env_test_connection.php"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/parameters/server-env.css') ?>">

    <div class="omo-server-env-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-server-env-popup__header-copy omo-server-env-popup__hero">
            <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.eyebrow'), ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.description'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="omo-server-env-popup__meta">
                <span class="generic-badge"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.target', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="generic-badge"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.hero.unlock_ttl', ['minutes' => $unlockTtlMinutes]), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
    <div class="omo-server-env-popup__shell generic-drawer-content">

    <?php if (!$hasLocalPassword): ?>
        <div class="omo-server-env-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.password.unavailable_title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(omoServerEnvT('parameters.server_env.password.unavailable_message', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php elseif (!$isUnlocked): ?>
        <div class="omo-server-env-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.unlock.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-server-env-popup__intro generic-description"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.unlock.description'), ENT_QUOTES, 'UTF-8') ?></p>

            <form id="omoServerEnvUnlockForm" class="omo-server-env-popup__unlock-form generic-form-stack">
                <div class="omo-server-env-popup__field generic-form-field">
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

                <div id="omoServerEnvUnlockFeedback" class="omo-server-env-popup__feedback generic-soft-panel generic-feedback" aria-live="polite"></div>

                <div class="omo-server-env-popup__actions generic-form-actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoServerEnvClose"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.action.close'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoServerEnvUnlockSubmit"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.unlock.submit'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="omo-server-env-popup__panel generic-section generic-section--stack generic-section--plain generic-section--flush">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.edit.title', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-server-env-popup__hint generic-help-text"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.edit.secret_hint'), ENT_QUOTES, 'UTF-8') ?></p>

            <form id="omoServerEnvForm" class="omo-server-env-popup__form generic-form-stack">
                <?php foreach ($serverEnvGroups as $groupKey => $group): ?>
                <?php if ($groupKey === $serverEnvFirstService): ?>
                <div class="generic-tabs generic-tabs--embedded" data-generic-tabs id="omoServerEnvTabs">
                    <div class="generic-tabs__list" aria-label="<?= htmlspecialchars(omoServerEnvT('parameters.server_env.tabs.aria'), ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($serverEnvServiceGroups as $tabKey => $tabGroup): ?>
                        <button type="button" class="generic-tabs__tab<?= $tabKey === $serverEnvFirstService ? ' is-active' : '' ?>" data-generic-tab data-generic-tab-target="omoServerEnvPanel-<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$tabGroup['title'], ENT_QUOTES, 'UTF-8') ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="generic-tabs__panels">
                <?php endif; ?>
                <?php if ($groupKey === 'technical'): ?>
                    </div>
                </div>
                <div class="generic-section generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed" data-generic-accordion id="omoServerEnvTechnical">
                    <div class="generic-accordion__header">
                        <button type="button" class="generic-accordion__trigger" data-generic-accordion-toggle aria-controls="omoServerEnvPanel-technical">
                            <span class="generic-inline-help">
                                <span class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.technical.title'), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="generic-badge generic-badge--warning">&#9888; <?= htmlspecialchars(omoServerEnvT('parameters.server_env.technical.badge'), ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                            <span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>
                        </button>
                    </div>
                    <div class="generic-accordion__content generic-form-stack">
                        <p class="generic-section generic-section--warning"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.technical.warning'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php $isServiceGroup = !in_array($groupKey, ['general', 'technical'], true); ?>
                <div id="omoServerEnvPanel-<?= htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8') ?>" class="generic-stack generic-stack--roomy<?= $isServiceGroup ? ' generic-tabs__panel' : '' ?>"<?= $isServiceGroup ? ' data-generic-tab-panel' : '' ?><?= $isServiceGroup && $groupKey !== $serverEnvFirstService ? ' hidden' : '' ?>>
                <?php foreach ($group['sections'] as $sectionKey => $section): ?>
                <?php if (!empty($section['technical'])): ?>
                <div class="generic-accordion generic-accordion--collapsible is-collapsed" data-generic-accordion data-server-env-technical-section="<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="generic-accordion__header">
                        <div class="generic-inline-help">
                            <button type="button" class="generic-accordion__trigger generic-accordion__trigger--inline" data-generic-accordion-toggle aria-controls="omoServerEnvSection-<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>
                                <span class="generic-card-title generic-card-title--medium"><?= htmlspecialchars((string)$section['title'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                            <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>
                                <summary aria-label="<?= htmlspecialchars(omoServerEnvT('parameters.server_env.field.help_label', ['field' => (string)$section['title']]), ENT_QUOTES, 'UTF-8') ?>">?</summary>
                                <div class="generic-context-help__content"><p><?= htmlspecialchars((string)$section['intro'], ENT_QUOTES, 'UTF-8') ?></p></div>
                            </details>
                        </div>
                    </div>
                    <div class="generic-accordion__content" id="omoServerEnvSection-<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <section class="generic-section generic-section--stack generic-section--roomy generic-section--plain generic-section--flush">
                    <?php if (empty($section['technical'])): ?>
                    <div class="omo-server-env-popup__section-header">
                        <div>
                            <h4 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars((string)$section['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="omo-server-env-popup__section-intro generic-description"><?= htmlspecialchars((string)$section['intro'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <?php if (in_array($sectionKey, ['etherpad', 'ethercalc', 'spacedeck'], true)): ?>
                        <button
                            type="button"
                            class="generic-action-button generic-action-button--secondary"
                            data-server-env-test="<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?>"
                        ><?= htmlspecialchars(omoServerEnvT('parameters.server_env.action.test_connection'), ENT_QUOTES, 'UTF-8') ?></button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($sectionKey === 'database'): ?>
                    <label class="generic-checkbox">
                        <input type="checkbox" id="omoServerEnvEnableDatabase">
                        <span><?= htmlspecialchars(omoServerEnvT('parameters.server_env.technical.enable_database'), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <?php endif; ?>

                    <div class="omo-server-env-popup__grid generic-form-grid">
                        <?php foreach ($section['fields'] as $field): ?>
                            <?php
                            $key = (string)$field['key'];
                            $fieldType = (string)($field['type'] ?? 'text');
                            $fieldValue = (string)($serverEnvDisplayValues[$key] ?? '');
                            $isSecret = !empty($field['secret']);
                            $isInactiveAiField = ($key === 'AI_BASE_URL' && ($serverEnvDisplayValues['AI_PROVIDER'] ?? '') !== 'openai_compatible')
                                || (str_starts_with($key, 'AI_') && $key !== 'AI_PROVIDER' && ($serverEnvDisplayValues['AI_PROVIDER'] ?? '') === 'disabled')
                                || (str_starts_with($key, 'TRANSCRIPTION_') && $key !== 'TRANSCRIPTION_PROVIDER' && ($serverEnvDisplayValues['TRANSCRIPTION_PROVIDER'] ?? '') === 'disabled');
                            ?>
                            <div class="omo-server-env-popup__field generic-form-field"<?= $isInactiveAiField ? ' hidden' : '' ?>>
                                <div class="omo-server-env-popup__label-row">
                                    <div class="generic-inline-help">
                                        <label class="omo-server-env-popup__label generic-form-label" for="omoServerEnvField<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$field['label'], ENT_QUOTES, 'UTF-8') ?></label>
                                        <?php if (!empty($field['help'])): ?>
                                        <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>
                                            <summary aria-label="<?= htmlspecialchars(omoServerEnvT('parameters.server_env.field.help_label', ['field' => (string)$field['label']]), ENT_QUOTES, 'UTF-8') ?>">?</summary>
                                            <div class="generic-context-help__content">
                                                <p><?= htmlspecialchars((string)$field['help'], ENT_QUOTES, 'UTF-8') ?></p>
                                                <?php if (!empty($field['help_url'])): ?>
                                                <a href="<?= htmlspecialchars((string)$field['help_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.field.help_source'), ENT_QUOTES, 'UTF-8') ?></a>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isSecret): ?>
                                    <span
                                        class="omo-server-env-popup__secret-state<?= !empty($serverEnvSecretStates[$key]) ? ' is-configured' : '' ?>"
                                        data-server-env-field-status="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                    ><?= htmlspecialchars(!empty($serverEnvSecretStates[$key]) ? omoServerEnvT('parameters.server_env.secret.configured') : omoServerEnvT('parameters.server_env.secret.empty'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($fieldType === 'select'): ?>
                                <select
                                    class="generic-form-control"
                                    id="omoServerEnvField<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                    name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <?php if ($fieldValue === ''): ?>
                                    <option value="" selected><?= htmlspecialchars(omoServerEnvT('parameters.server_env.secret.empty'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endif; ?>
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
                                    <?= $sectionKey === 'database' ? 'data-server-env-database-access disabled' : '' ?>
                                    <?= $isInactiveAiField ? 'disabled' : '' ?>
                                    value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>"
                                    placeholder="<?= htmlspecialchars((string)($field['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    <?php if (isset($field['min'])): ?>min="<?= (int)$field['min'] ?>"<?php endif; ?>
                                    <?php if (isset($field['max'])): ?>max="<?= (int)$field['max'] ?>"<?php endif; ?>
                                    autocomplete="off"
                                >
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (in_array($sectionKey, ['etherpad', 'ethercalc', 'spacedeck'], true)): ?>
                    <div
                        class="omo-server-env-popup__feedback generic-soft-panel generic-feedback"
                        data-server-env-test-feedback="<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?>"
                        aria-live="polite"
                    ></div>
                    <?php endif; ?>
                </section>
                <?php if (!empty($section['technical'])): ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                </div>
                <?php if ($groupKey === 'technical'): ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <div id="omoServerEnvFeedback" class="omo-server-env-popup__feedback generic-soft-panel generic-feedback" aria-live="polite"></div>

                <div class="omo-server-env-popup__actions generic-form-actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoServerEnvClose"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.action.close'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoServerEnvSubmit"><?= htmlspecialchars(omoServerEnvT('parameters.server_env.action.save', ['target' => $serverEnvTargetLabel]), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
</div>

<?= commonPageScriptTags('/omo/api/parameters/server_env_popup.js', [
    'envTargetLabel' => $serverEnvTargetLabel,
    'texts' => $serverEnvClientTexts,
    'transcriptionDefaultModels' => array_combine(['openai', 'groq', 'mistral'], array_map('commonAiGetTranscriptionDefaultModel', ['openai', 'groq', 'mistral'])),
]) ?>
