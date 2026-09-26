<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 3) . '/common/choice/rule-scope-fields.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$ruleId = isset($_GET['rule_id']) && is_numeric($_GET['rule_id']) ? (int)$_GET['rule_id'] : 0;
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$editingRule = null;
if ($ruleId > 0) {
    $candidateRule = new \dbObject\Rule();
    if (!$candidateRule->load($ruleId) || $candidateRule->getOrganizationId() !== $organizationId) {
        http_response_code(404);
        ?><div class="omo-empty-state"><?= omoApiEscape(omoPolicyT('policy.error.load')) ?></div><?php
        exit;
    }

    $editingRule = $candidateRule;
    $ruleHolon = $candidateRule->getHolon();
    $currentHolonId = $ruleHolon instanceof \dbObject\Holon ? (int)$ruleHolon->getId() : 0;
}
$context = omoPolicyResolveContext($organizationId, $currentHolonId);
if (empty($context['status']) || ($editingRule instanceof \dbObject\Rule ? !$editingRule->canEdit() : !omoPolicyCanCreateLocalRule($context))) {
    http_response_code(403);
    ?><div class="omo-empty-state"><?= omoApiEscape(omoPolicyT('policy.error.forbidden')) ?></div><?php
    exit;
}
$today = new DateTimeImmutable('today');
$authorities = omoPolicyGetDirectAuthorities($context['currentHolon'], $context['organization']);
$hasAuthorities = $context['currentHolon'] instanceof \dbObject\Holon && count($authorities) > 0;
$scopeContext = \dbObject\Rule::getScopeContext($context['currentHolon']);
$isEditing = $editingRule instanceof \dbObject\Rule;
$drawerDescriptionKey = $isEditing ? 'policy.drawer.description_edit'
    : (!($context['currentHolon'] instanceof \dbObject\Holon)
        ? 'policy.drawer.description_organization'
        : ($hasAuthorities ? 'policy.drawer.description' : 'policy.drawer.description_local'));
$reviewDate = $isEditing && $editingRule->get('review_date') instanceof DateTimeInterface
    ? $editingRule->get('review_date')->format('Y-m-d')
    : $today->modify('+6 months')->format('Y-m-d');
$expirationDate = $isEditing && $editingRule->get('expiration_date') instanceof DateTimeInterface
    ? $editingRule->get('expiration_date')->format('Y-m-d')
    : $today->modify('+1 year')->format('Y-m-d');
$selectedAuthorityId = $isEditing ? (int)$editingRule->get('IDauthority') : 0;
?>
<div class="generic-drawer-content">
    <form method="post" action="/omo/api/policy/action.php" class="generic-form-stack" data-policy-form data-policy-form-title="<?= omoApiEscape(omoPolicyT($isEditing ? 'policy.drawer.title_edit' : 'policy.drawer.title')) ?>" data-policy-form-description="<?= omoApiEscape(omoPolicyT($drawerDescriptionKey)) ?>">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= $context['currentHolon'] instanceof \dbObject\Holon ? (int)$context['currentHolon']->getId() : 0 ?>">
        <?php if ($isEditing): ?><input type="hidden" name="rule_id" value="<?= (int)$editingRule->getId() ?>"><?php endif; ?>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided">
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoPolicyT('policy.field.title')) ?></span>
                <input class="generic-form-control" name="title" maxlength="255" value="<?= omoApiEscape($isEditing ? (string)$editingRule->get('title') : '') ?>" required autofocus>
            </label>
            <?php omoRuleScopeRenderFields(['scope' => $isEditing ? $editingRule->get('scope') : 'local', 'IDauthority' => $selectedAuthorityId], $scopeContext); ?>
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoPolicyT('policy.field.intention')) ?></span>
                <div class="omo-policy__html-field" data-policy-html-field></div>
                <input type="hidden" name="intention" value="<?= omoApiEscape($isEditing ? (string)$editingRule->get('intention') : '') ?>" data-policy-html-input>
            </label>
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoPolicyT('policy.field.description')) ?></span>
                <div class="omo-policy__html-field" data-policy-html-field></div>
                <input type="hidden" name="description" value="<?= omoApiEscape($isEditing ? (string)$editingRule->get('description') : '') ?>" data-policy-html-input>
            </label>
            <div class="generic-form-grid">
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoPolicyT('policy.field.review_date')) ?></span>
                    <input class="generic-form-control" type="date" name="review_date" value="<?= omoApiEscape($reviewDate) ?>" required>
                </label>
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoPolicyT('policy.field.expiration_date')) ?></span>
                    <input class="generic-form-control" type="date" name="expiration_date" value="<?= omoApiEscape($expirationDate) ?>" required>
                </label>
            </div>
        </section>

        <div class="generic-form-actions generic-form-actions--stack-mobile">
            <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoPolicyT('policy.save')) ?></button>
        </div>
    </form>
</div>
