<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$userId = (int)commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
$organizationLoaded = $organizationId > 0 && $organization->load($organizationId);
$applicationLink = $organizationLoaded ? omoProjectsParamsGetApplicationLink($organizationId, false) : null;
$canManage = $organizationLoaded && omoProjectsParamsCanManage($organizationId, $userId);
$config = $organizationLoaded ? omoProjectsParamsGetConfig($organization) : \dbObject\ProjectImportanceCalculator::getDefaultConfig();
$displayConfig = $organizationLoaded ? omoProjectsParamsGetDisplayConfig($organization) : omoProjectsDefaultDisplayConfig();
?>
<div class="omo-projects-params" data-omo-projects-params-root>
    <section class="generic-section generic-section--plain generic-form-stack">
        <div>
            <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoProjectsParamsT('projects.params.application'), ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoProjectsParamsT('projects.params.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="generic-description"><?= htmlspecialchars(omoProjectsParamsT('projects.params.description'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php if ($userId <= 0): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoProjectsParamsT('projects.params.error.login'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$organizationLoaded): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoProjectsParamsT('projects.params.error.organization'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$applicationLink): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoProjectsParamsT('projects.params.error.unavailable'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$canManage): ?>
            <div class="omo-empty-state"><?= htmlspecialchars(omoProjectsParamsT('projects.params.error.forbidden'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided">
                <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoProjectsParamsT('projects.params.display_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                <form class="generic-form-stack" action="/omo/api/projects/params/save.php" method="post" data-omo-projects-params-form data-omo-projects-params-saving-label="<?= htmlspecialchars(omoProjectsParamsT('projects.params.saving'), ENT_QUOTES, 'UTF-8') ?>" data-omo-projects-params-error="<?= htmlspecialchars(omoProjectsParamsT('projects.params.error.save'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
                    <input type="hidden" name="save_display" value="1">
                    <input type="hidden" name="parent_weight" value="<?= htmlspecialchars((string)$config['parentWeight'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="depth_penalty" value="<?= htmlspecialchars((string)$config['depthPenalty'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="omo-projects-params__grid generic-form-grid">
                        <fieldset class="generic-fieldset">
                            <legend class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoProjectsParamsT('projects.params.columns'), ENT_QUOTES, 'UTF-8') ?></legend>
                            <div class="generic-fieldset__body">
                            <p class="generic-help-text"><?= htmlspecialchars(omoProjectsParamsT('projects.params.columns_help'), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php foreach (omoProjectsStatusDisplayOrder() as $status): ?>
                                <?php $defaultStatusLabel = omoProjectsT('projects.status.' . $status); ?>
                                <div class="generic-setting-row">
                                    <label class="generic-checkbox"><input type="checkbox" name="enabled_statuses[]" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"<?= in_array($status, $displayConfig['enabledStatuses'], true) ? ' checked' : '' ?>> <span><?= htmlspecialchars($defaultStatusLabel, ENT_QUOTES, 'UTF-8') ?></span></label>
                                    <input class="generic-form-control" type="text" name="status_labels[<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>]" maxlength="60" value="<?= htmlspecialchars((string)($displayConfig['statusLabels'][$status] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($defaultStatusLabel, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(omoProjectsParamsT('projects.params.column_label', ['status' => $defaultStatusLabel]), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <fieldset class="generic-fieldset">
                            <legend class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoProjectsParamsT('projects.params.classification'), ENT_QUOTES, 'UTF-8') ?></legend>
                            <div class="generic-fieldset__body">
                            <label class="generic-checkbox"><input type="checkbox" name="use_priority" value="1"<?= !empty($displayConfig['usePriority']) ? ' checked' : '' ?>> <span><?= htmlspecialchars(omoProjectsParamsT('projects.params.use_priority'), ENT_QUOTES, 'UTF-8') ?></span></label>
                            <label class="generic-checkbox"><input type="checkbox" name="use_importance" value="1"<?= !empty($displayConfig['useImportance']) ? ' checked' : '' ?>> <span><?= htmlspecialchars(omoProjectsParamsT('projects.params.use_importance'), ENT_QUOTES, 'UTF-8') ?></span></label>
                            <label class="generic-checkbox"><input type="checkbox" name="use_size" value="1"<?= !empty($displayConfig['useSize']) ? ' checked' : '' ?>> <span><?= htmlspecialchars(omoProjectsParamsT('projects.params.use_size'), ENT_QUOTES, 'UTF-8') ?></span></label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="omo-projects-params__actions generic-form-actions">
                        <button class="generic-action-button generic-action-button--main" type="submit" data-omo-projects-params-submit><?= htmlspecialchars(omoProjectsParamsT('projects.params.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <div class="omo-projects-params__feedback generic-soft-panel generic-feedback" data-omo-projects-params-feedback hidden></div>
                </form>
            </section>
            <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided">
                <div class="generic-form-section__copy">
                    <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoProjectsParamsT('projects.params.calculation_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="generic-description"><?= htmlspecialchars(omoProjectsParamsT('projects.params.calculation_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <form class="generic-form-stack" action="/omo/api/projects/params/save.php" method="post" data-omo-projects-params-form data-omo-projects-params-saving-label="<?= htmlspecialchars(omoProjectsParamsT('projects.params.saving'), ENT_QUOTES, 'UTF-8') ?>" data-omo-projects-params-error="<?= htmlspecialchars(omoProjectsParamsT('projects.params.error.save'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
                    <fieldset class="generic-fieldset">
                        <legend class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoProjectsParamsT('projects.params.balance_title'), ENT_QUOTES, 'UTF-8') ?></legend>
                        <div class="generic-fieldset__body">
                            <div class="generic-form-grid generic-form-grid--pair">
                                <div class="generic-soft-panel generic-soft-panel--stack">
                                    <label class="generic-form-field">
                                        <span class="generic-form-label"><?= htmlspecialchars(omoProjectsParamsT('projects.params.parent_weight'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <input class="generic-form-control" name="parent_weight" type="number" min="0" max="1" step="0.01" required value="<?= htmlspecialchars((string)$config['parentWeight'], ENT_QUOTES, 'UTF-8') ?>" data-omo-projects-parent-weight>
                                    </label>
                                    <output class="generic-card-title generic-card-title--big" data-omo-projects-parent-share data-template="<?= htmlspecialchars(omoProjectsParamsT('projects.params.parent_share', ['percent' => '{percent}']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(omoProjectsParamsT('projects.params.parent_share', ['percent' => round($config['parentWeight'] * 100)]), ENT_QUOTES, 'UTF-8') ?></output>
                                    <small class="generic-help-text"><?= htmlspecialchars(omoProjectsParamsT('projects.params.parent_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <div class="generic-soft-panel generic-soft-panel--stack">
                                    <span class="generic-form-label"><?= htmlspecialchars(omoProjectsParamsT('projects.params.local_weight'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <output class="generic-card-title generic-card-title--large" data-omo-projects-local-weight aria-live="polite"><?= htmlspecialchars((string)round((1 - $config['parentWeight']) * 100), ENT_QUOTES, 'UTF-8') ?> %</output>
                                    <p class="generic-help-text"><?= htmlspecialchars(omoProjectsParamsT('projects.params.local_help'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="generic-fieldset">
                        <legend class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoProjectsParamsT('projects.params.depth_title'), ENT_QUOTES, 'UTF-8') ?></legend>
                        <div class="generic-fieldset__body">
                            <div class="generic-form-grid generic-form-grid--pair">
                                <label class="generic-form-field">
                                    <span class="generic-form-label"><?= htmlspecialchars(omoProjectsParamsT('projects.params.depth_penalty'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <input class="generic-form-control" name="depth_penalty" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string)$config['depthPenalty'], ENT_QUOTES, 'UTF-8') ?>" data-omo-projects-depth-penalty>
                                    <small class="generic-help-text"><?= htmlspecialchars(omoProjectsParamsT('projects.params.depth_help'), ENT_QUOTES, 'UTF-8') ?></small>
                                </label>
                                <div class="generic-soft-panel generic-soft-panel--stack">
                                    <output class="generic-help-text" data-omo-projects-depth-example aria-live="polite" data-template="<?= htmlspecialchars(omoProjectsParamsT('projects.params.depth_example', ['level1' => '{level1}', 'level2' => '{level2}', 'level3' => '{level3}']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(omoProjectsParamsT('projects.params.depth_example', ['level1' => round(exp(-$config['depthPenalty']) * 100), 'level2' => round(exp(-$config['depthPenalty'] * 2) * 100), 'level3' => round(exp(-$config['depthPenalty'] * 3) * 100)]), ENT_QUOTES, 'UTF-8') ?></output>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <details class="generic-accordion">
                        <summary><?= htmlspecialchars(omoProjectsParamsT('projects.params.rules_title'), ENT_QUOTES, 'UTF-8') ?></summary>
                        <div class="generic-accordion__content generic-form-stack">
                            <p class="generic-description"><?= htmlspecialchars(omoProjectsParamsT('projects.params.rules_balance'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="generic-description"><?= htmlspecialchars(omoProjectsParamsT('projects.params.rules_missing'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="generic-description"><?= htmlspecialchars(omoProjectsParamsT('projects.params.rules_depth'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </details>
                    <div class="generic-form-actions">
                        <button class="generic-action-button generic-action-button--main" type="submit" data-omo-projects-params-submit><?= htmlspecialchars(omoProjectsParamsT('projects.params.calculation_save'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <div class="omo-projects-params__feedback generic-soft-panel generic-feedback" data-omo-projects-params-feedback hidden></div>
                </form>
            </section>
        <?php endif; ?>
    </section>
</div>
<script src="/omo/api/projects/params/params.js?v=20260917-calculation"></script>
