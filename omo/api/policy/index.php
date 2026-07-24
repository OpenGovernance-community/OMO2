<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayRule;
use dbObject\Rule;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoPolicyResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    ?>
    <div class="omo-panel-view"><div class="omo-panel-view__body"><div class="omo-panel-view__body_content"><div class="omo-empty-state"><?= omoApiEscape((string)$context['message']) ?></div></div></div></div>
    <?php
    exit;
}

$currentHolon = $context['currentHolon'];
$rules = new ArrayRule();
$rules->loadForPolicyContext($organizationId, $currentHolon);
$canCreate = omoPolicyCanCreateLocalRule($context);
$createUrl = '/omo/api/policy/edit.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . rawurlencode((string)$currentHolon->getId());
?>
<div class="omo-policy omo-panel-view" id="omo-policy-root" data-policy-create-url="<?= omoApiEscape($createUrl) ?>" data-policy-load-error="<?= omoApiEscape(omoPolicyT('policy.error.load')) ?>" data-policy-save-error="<?= omoApiEscape(omoPolicyT('policy.error.save')) ?>">
    <header class="omo-panel-view__header">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <div class="omo-panel-view__header-copy">
                    <div class="omo-panel-view__title-row"><h2 class="omo-panel-view__title"><?= omoApiEscape(omoPolicyT('policy.title')) ?></h2><span class="omo-panel-view__count"><?= count($rules) ?></span></div>
                    <p class="omo-panel-view__description"><?= omoApiEscape(omoPolicyT('policy.description')) ?></p>
                </div>
            </div>
            <?php if ($canCreate): ?><button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-policy-new><?= omoApiEscape(omoPolicyT('policy.new')) ?></button><?php endif; ?>
        </div>
    </header>
    <div class="omo-panel-view__body"><div class="omo-panel-view__body_content generic-section generic-section--stack">
        <?php if (count($rules) === 0): ?>
            <div class="omo-empty-state"><?= omoApiEscape(omoPolicyT('policy.empty')) ?></div>
        <?php else: ?>
            <?php foreach ($rules as $rule): ?>
                <?php if (!($rule instanceof Rule)): continue; endif; ?>
                <article class="generic-soft-panel generic-soft-panel--stack">
                    <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape((string)$rule->get('title')) ?></h3>
                    <section><h4 class="generic-card-title"><?= omoApiEscape(omoPolicyT('policy.intention')) ?></h4><div><?= (string)$rule->get('intention') ?></div></section>
                    <section><h4 class="generic-card-title"><?= omoApiEscape(omoPolicyT('policy.description_label')) ?></h4><div><?= (string)$rule->get('description') ?></div></section>
                    <div class="generic-meta-row">
                        <span><?= omoApiEscape(omoPolicyT('policy.review', ['date' => $rule->get('review_date') instanceof DateTimeInterface ? $rule->get('review_date')->format('d.m.Y') : ''])) ?></span>
                        <span><?= omoApiEscape(omoPolicyT('policy.expiration', ['date' => $rule->get('expiration_date') instanceof DateTimeInterface ? $rule->get('expiration_date')->format('d.m.Y') : ''])) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div></div>
    <div class="omo-overlay-drawer" data-policy-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-policy-close></div>
        <div class="omo-overlay-drawer__panel"><div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky"><div class="generic-drawer-header__copy"><h3 class="omo-overlay-drawer__title"><?= omoApiEscape(omoPolicyT('policy.drawer.title')) ?></h3><p class="omo-overlay-drawer__description"><?= omoApiEscape(omoPolicyT('policy.drawer.description')) ?></p></div><div class="generic-drawer-header__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-policy-close><?= omoApiEscape(omoPolicyT('policy.close')) ?></button></div></div><div class="omo-overlay-drawer__body" data-policy-drawer-body></div></div>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('omo-policy-root');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    var drawer = root.querySelector('[data-policy-drawer]');
    var body = root.querySelector('[data-policy-drawer-body]');
    var close = function () { drawer.classList.remove('is-open'); window.setTimeout(function () { if (!drawer.classList.contains('is-open')) { drawer.hidden = true; body.innerHTML = ''; } }, 220); };
    root.querySelectorAll('[data-policy-close]').forEach(function (button) { button.addEventListener('click', close); });
    var create = root.querySelector('[data-policy-new]');
    if (create) create.addEventListener('click', function () { drawer.hidden = false; window.requestAnimationFrame(function () { drawer.classList.add('is-open'); }); body.textContent = '...'; fetch(root.dataset.policyCreateUrl, {credentials: 'same-origin'}).then(function (response) { if (!response.ok) throw new Error('load_failed'); return response.text(); }).then(function (html) { body.innerHTML = html; if (typeof window.initGenericComponents === 'function') window.initGenericComponents(body); }).catch(function () { body.textContent = root.dataset.policyLoadError; }); });
    body.addEventListener('submit', function (event) { var form = event.target.closest('[data-policy-form]'); if (!form) return; event.preventDefault(); if (!form.reportValidity()) return; var feedback = form.querySelector('[data-policy-feedback]'); fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)}).then(function (response) { return response.json().then(function (payload) { return {ok: response.ok, payload: payload}; }); }).then(function (result) { if (!result.ok || !result.payload.success) throw new Error(result.payload.message || root.dataset.policySaveError); feedback.hidden = false; feedback.textContent = result.payload.message; window.omoPolicyAfterSave(); }).catch(function (error) { feedback.hidden = false; feedback.textContent = error.message || root.dataset.policySaveError; }); });
    window.omoPolicyAfterSave = function () { window.location.reload(); };
})();
</script>
