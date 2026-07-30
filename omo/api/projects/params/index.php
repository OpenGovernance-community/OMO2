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
?>
<div class="omo-projects-params" data-omo-projects-params-root>
    <section class="generic-section generic-section--stack generic-section--roomy">
        <div>
            <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoProjectsParamsT('projects.params.application'), ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoProjectsParamsT('projects.params.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="omo-projects-params__description generic-description"><?= htmlspecialchars(omoProjectsParamsT('projects.params.description'), ENT_QUOTES, 'UTF-8') ?></p>
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
            <p class="omo-projects-params__hint generic-description"><?= htmlspecialchars(omoProjectsParamsT('projects.params.formula'), ENT_QUOTES, 'UTF-8') ?></p>
            <form class="generic-form-stack" action="/omo/api/projects/params/save.php" method="post" data-omo-projects-params-form>
                <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
                <div class="omo-projects-params__grid generic-form-grid">
                    <label class="generic-form-field">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoProjectsParamsT('projects.params.parent_weight'), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="generic-form-control" name="parent_weight" type="number" min="0" max="1" step="0.01" value="<?= htmlspecialchars((string)$config['parentWeight'], ENT_QUOTES, 'UTF-8') ?>">
                        <small><?= htmlspecialchars(omoProjectsParamsT('projects.params.parent_weight_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                    </label>
                    <div class="omo-projects-params__derived generic-soft-panel generic-form-field">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoProjectsParamsT('projects.params.local_weight'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong data-omo-projects-local-weight><?= htmlspecialchars((string)round((1 - $config['parentWeight']) * 100), ENT_QUOTES, 'UTF-8') ?> %</strong>
                    </div>
                    <label class="generic-form-field">
                        <span class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoProjectsParamsT('projects.params.depth_penalty'), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="generic-form-control" name="depth_penalty" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string)$config['depthPenalty'], ENT_QUOTES, 'UTF-8') ?>">
                        <small><?= htmlspecialchars(omoProjectsParamsT('projects.params.depth_penalty_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                    </label>
                </div>
                <div class="omo-projects-params__actions generic-form-actions">
                    <button class="generic-action-button generic-action-button--main" type="submit" data-omo-projects-params-submit><?= htmlspecialchars(omoProjectsParamsT('projects.params.save'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
                <div class="omo-projects-params__feedback generic-soft-panel generic-feedback" data-omo-projects-params-feedback hidden></div>
            </form>
        <?php endif; ?>
    </section>
</div>
<style>
.omo-projects-params__description, .omo-projects-params__hint { margin-top: var(--generic-space-2); }
.omo-projects-params__grid small { color: var(--color-text-light, #64748b); line-height: 1.4; }
</style>
<script>
(function () {
    document.querySelectorAll('[data-omo-projects-params-root]').forEach(function (root) {
        if (root.dataset.omoProjectsParamsReady === '1') return;
        root.dataset.omoProjectsParamsReady = '1';
        var form = root.querySelector('[data-omo-projects-params-form]');
        if (!form) return;
        var input = form.querySelector('[name="parent_weight"]');
        var localWeight = form.querySelector('[data-omo-projects-local-weight]');
        var submit = form.querySelector('[data-omo-projects-params-submit]');
        var feedback = form.querySelector('[data-omo-projects-params-feedback]');
        var saveLabel = <?= json_encode(omoProjectsParamsT('projects.params.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var savingLabel = <?= json_encode(omoProjectsParamsT('projects.params.saving'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var refreshWeight = function () { if (input && localWeight) localWeight.textContent = String(Math.round((1 - Math.max(0, Math.min(1, Number(input.value) || 0))) * 100)) + ' %'; };
        var showFeedback = function (message, isError) {
            if (typeof window.commonNotify === 'function') {
                window.commonNotify(String(message || ''), isError ? 'error' : 'success');
                return;
            }

            feedback.hidden = false;
            feedback.textContent = String(message || '');
            feedback.className = 'omo-projects-params__feedback generic-soft-panel generic-feedback ' + (isError ? 'is-error' : 'is-success');
        };
        if (input) input.addEventListener('input', refreshWeight);
        form.addEventListener('submit', function (event) {
            event.preventDefault(); submit.disabled = true; submit.textContent = savingLabel; feedback.hidden = true;
            window.fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (payload) { showFeedback(payload.message || '', !payload.status); })
                .catch(function () { showFeedback(<?= json_encode(omoProjectsParamsT('projects.params.error.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, true); })
                .finally(function () { submit.disabled = false; submit.textContent = saveLabel; });
        });
    });
})();
</script>
