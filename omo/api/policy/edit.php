<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$ruleId = isset($_GET['rule_id']) && is_numeric($_GET['rule_id']) ? (int)$_GET['rule_id'] : 0;
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$editingRule = null;
if ($ruleId > 0) {
    $candidateRule = new \dbObject\Rule();
    if (!$candidateRule->load($ruleId) || !($candidateRule->getHolon() instanceof \dbObject\Holon)) {
        http_response_code(404);
        ?><div class="omo-empty-state"><?= omoApiEscape(omoPolicyT('policy.error.load')) ?></div><?php
        exit;
    }

    $editingRule = $candidateRule;
    $currentHolonId = (int)$candidateRule->getHolon()->getId();
}
$context = omoPolicyResolveContext($organizationId, $currentHolonId);
if (empty($context['status']) || !omoPolicyCanCreateLocalRule($context) || ($editingRule instanceof \dbObject\Rule && !$editingRule->canEdit())) {
    http_response_code(403);
    ?><div class="omo-empty-state"><?= omoApiEscape(omoPolicyT('policy.error.forbidden')) ?></div><?php
    exit;
}
$today = new DateTimeImmutable('today');
$authorities = omoPolicyGetDirectAuthorities($context['currentHolon'], $context['organization']);
$isEditing = $editingRule instanceof \dbObject\Rule;
$reviewDate = $isEditing && $editingRule->get('review_date') instanceof DateTimeInterface
    ? $editingRule->get('review_date')->format('Y-m-d')
    : $today->modify('+6 months')->format('Y-m-d');
$expirationDate = $isEditing && $editingRule->get('expiration_date') instanceof DateTimeInterface
    ? $editingRule->get('expiration_date')->format('Y-m-d')
    : $today->modify('+1 year')->format('Y-m-d');
$selectedAuthorityId = $isEditing ? (int)$editingRule->get('IDauthority') : 0;
?>
<div class="generic-drawer-content">
    <form method="post" action="/omo/api/policy/action.php" class="generic-form-stack" data-policy-form data-policy-form-title="<?= omoApiEscape(omoPolicyT($isEditing ? 'policy.drawer.title_edit' : 'policy.drawer.title')) ?>" data-policy-form-description="<?= omoApiEscape(omoPolicyT($isEditing ? 'policy.drawer.description_edit' : 'policy.drawer.description')) ?>">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$context['currentHolon']->getId() ?>">
        <?php if ($isEditing): ?><input type="hidden" name="rule_id" value="<?= (int)$editingRule->getId() ?>"><?php endif; ?>

        <section class="generic-section generic-section--stack generic-form-section">
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoPolicyT('policy.field.title')) ?></span>
                <input class="generic-form-control" name="title" maxlength="255" value="<?= omoApiEscape($isEditing ? (string)$editingRule->get('title') : '') ?>" required autofocus>
            </label>
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoPolicyT('policy.field.authority')) ?></span>
                <select class="generic-form-control" name="authority_id">
                    <option value="0"><?= omoApiEscape(omoPolicyT('policy.field.authority_local')) ?></option>
                    <?php foreach ($authorities as $authority): ?>
                        <?php if ($authority instanceof \dbObject\Authority): ?>
                            <option value="<?= (int)$authority->getId() ?>"<?= $selectedAuthorityId === (int)$authority->getId() ? ' selected' : '' ?>><?= omoApiEscape((string)$authority->get('label')) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
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

        <p class="generic-feedback" data-policy-feedback hidden aria-live="polite"></p>
        <div class="generic-form-actions generic-form-actions--stack-mobile">
            <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoPolicyT('policy.save')) ?></button>
        </div>
    </form>
</div>
