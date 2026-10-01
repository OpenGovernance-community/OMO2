<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
$organizationLoaded = $organizationId > 0 && $organization->load($organizationId);
$application = $organizationLoaded ? omoDecisionParamsGetApplicationLink($organizationId, false) : null;
$canManage = $organizationLoaded && omoDecisionParamsCanManage($organizationId, $currentUserId);
$config = $organizationLoaded ? omoDecisionParamsGetConfig($organization) : omoDecisionParamsGetDefaultConfig();
$canUseGovernance = $organizationLoaded && omoDecisionParamsCanUseGovernance($organization);
$descriptionKey = $canUseGovernance ? 'decisions.params.description' : 'decisions.params.description.discovery';
$methods = $config['methods'];
$governance = $config['governance'];
?>
<section class="generic-drawer-content" data-omo-decision-params>
    <div class="generic-form-section__heading">
        <div class="generic-heading-with-help">
            <h2 class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <details class="generic-context-help">
                <summary aria-label="<?= htmlspecialchars(omoDecisionParamsT($descriptionKey), ENT_QUOTES, 'UTF-8') ?>">?</summary>
                <div class="generic-context-help__content"><?= htmlspecialchars(omoDecisionParamsT($descriptionKey), ENT_QUOTES, 'UTF-8') ?></div>
            </details>
        </div>
    </div>

    <?php if ($currentUserId <= 0): ?>
        <div class="omo-empty-state"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.error.login'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (!$organizationLoaded): ?>
        <div class="omo-empty-state"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.error.organization'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (!$application instanceof \dbObject\OrganizationApplication): ?>
        <div class="omo-empty-state"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.error.unavailable'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (!$canManage): ?>
        <div class="omo-empty-state"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.error.forbidden'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
    <form class="generic-form-stack generic-form-stack--compact" action="/omo/api/decision/params/save.php" method="post" data-omo-decision-params-form>
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-form-section__heading">
                <h3 class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.section.methods'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <label class="generic-checkbox"><input type="checkbox" name="methods[simple_vote]" value="1"<?= !empty($methods['simple_vote']) ? ' checked' : '' ?>><span><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.simple_vote'), ENT_QUOTES, 'UTF-8') ?></span></label>
            <label class="generic-checkbox"><input type="checkbox" name="methods[majority_judgment]" value="1"<?= !empty($methods['majority_judgment']) ? ' checked' : '' ?>><span><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.majority_judgment'), ENT_QUOTES, 'UTF-8') ?></span></label>
            <label class="generic-checkbox"><input type="checkbox" name="methods[consent]" value="1"<?= !empty($methods['consent']) ? ' checked' : '' ?>><span><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.consent'), ENT_QUOTES, 'UTF-8') ?></span></label>
            <label class="generic-checkbox"><input type="checkbox" name="methods[consultation_only]" value="1"<?= !empty($methods['consultation_only']) ? ' checked' : '' ?>><span><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.consultation_only'), ENT_QUOTES, 'UTF-8') ?></span></label>
        </section>

        <?php if ($canUseGovernance): ?>
        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-form-section__heading">
                <h3 class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.section.governance'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <label class="generic-checkbox"><input type="checkbox" name="governance[enabled]" value="1" data-omo-decision-params-governance-enabled<?= !empty($governance['enabled']) ? ' checked' : '' ?>><span><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.governance_enabled'), ENT_QUOTES, 'UTF-8') ?></span></label>
            <div class="generic-form-stack generic-form-stack--compact" data-omo-decision-params-governance-fields<?= !empty($governance['enabled']) ? '' : ' hidden' ?>>
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.governance_method'), ENT_QUOTES, 'UTF-8') ?></span>
                    <select class="generic-form-control generic-form-control--compact" name="governance[evaluation_method]">
                        <option value="simple_vote"<?= ($governance['evaluation_method'] ?? '') === 'simple_vote' ? ' selected' : '' ?>><?= htmlspecialchars(omoDecisionParamsT('decisions.params.option.governance_method.simple_vote'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="consent"<?= ($governance['evaluation_method'] ?? '') === 'consent' ? ' selected' : '' ?>><?= htmlspecialchars(omoDecisionParamsT('decisions.params.option.governance_method.consent'), ENT_QUOTES, 'UTF-8') ?></option>
                    </select>
                </label>
                <div class="generic-form-field">
                    <div class="generic-heading-with-help">
                        <label class="generic-form-label" for="omo-decision-params-question"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.governance_question'), ENT_QUOTES, 'UTF-8') ?></label>
                        <details class="generic-context-help">
                            <summary aria-label="<?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.governance_question_hint'), ENT_QUOTES, 'UTF-8') ?>">?</summary>
                            <div class="generic-context-help__content"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.governance_question_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                        </details>
                    </div>
                    <textarea class="generic-form-control generic-form-control--compact" id="omo-decision-params-question" name="governance[question]" rows="3" maxlength="1000"><?= htmlspecialchars((string)$governance['question'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="generic-form-grid">
                    <label class="generic-form-field"><span class="generic-form-label"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.consultation_days'), ENT_QUOTES, 'UTF-8') ?></span><input class="generic-form-control generic-form-control--compact" type="number" name="governance[consultation_days]" min="0" max="365" required value="<?= (int)$governance['consultation_days'] ?>"></label>
                    <label class="generic-form-field"><span class="generic-form-label"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.vote_days'), ENT_QUOTES, 'UTF-8') ?></span><input class="generic-form-control generic-form-control--compact" type="number" name="governance[vote_days]" min="1" max="365" required value="<?= (int)$governance['vote_days'] ?>"></label>
                </div>
                <label class="generic-checkbox"><input type="checkbox" name="governance[show_live_votes]" value="1" data-omo-decision-params-live-votes<?= !empty($governance['show_live_votes']) ? ' checked' : '' ?>><span><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.show_live_votes'), ENT_QUOTES, 'UTF-8') ?></span></label>
                <div class="generic-heading-with-help" data-omo-decision-params-anonymous-fields<?= !empty($governance['show_live_votes']) ? '' : ' hidden' ?>>
                    <label class="generic-checkbox"><input type="checkbox" name="governance[live_votes_anonymous]" value="1"<?= !empty($governance['live_votes_anonymous']) ? ' checked' : '' ?>><span><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.live_votes_anonymous'), ENT_QUOTES, 'UTF-8') ?></span></label>
                    <details class="generic-context-help">
                        <summary aria-label="<?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.live_votes_anonymous_hint'), ENT_QUOTES, 'UTF-8') ?>">?</summary>
                        <div class="generic-context-help__content"><?= htmlspecialchars(omoDecisionParamsT('decisions.params.field.live_votes_anonymous_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                    </details>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <div class="generic-form-actions"><button type="submit" class="generic-action-button generic-action-button--main" data-omo-decision-params-submit><?= htmlspecialchars(omoDecisionParamsT('decisions.params.action.save'), ENT_QUOTES, 'UTF-8') ?></button></div>
        <p class="generic-feedback" data-omo-decision-params-feedback hidden aria-live="polite"></p>
    </form>
    <?php endif; ?>
</section>
<?= commonPageScriptTags('/omo/api/decision/params/index.js', [
    'saveLabel' => omoDecisionParamsT('decisions.params.action.save'),
    'savingLabel' => omoDecisionParamsT('decisions.params.action.saving'),
    'successLabel' => omoDecisionParamsT('decisions.params.feedback.saved'),
]) ?>
