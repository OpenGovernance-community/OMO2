<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoPolicyResolveContext($organizationId, $currentHolonId);
if (empty($context['status']) || !omoPolicyCanCreateLocalRule($context)) {
    http_response_code(403);
    ?><div class="omo-empty-state"><?= omoApiEscape(omoPolicyT('policy.error.forbidden')) ?></div><?php
    exit;
}
$today = new DateTimeImmutable('today');
$authorities = omoPolicyGetDirectAuthorities($context['currentHolon']);
?>
<form method="post" action="/omo/api/policy/action.php" class="generic-section generic-section--stack" data-policy-form>
    <input type="hidden" name="oid" value="<?= (int)$organizationId ?>"><input type="hidden" name="cid" value="<?= (int)$context['currentHolon']->getId() ?>">
    <label><?= omoApiEscape(omoPolicyT('policy.field.title')) ?><input class="generic-form-control" name="title" maxlength="255" required autofocus></label>
    <label><?= omoApiEscape(omoPolicyT('policy.field.authority')) ?><select class="generic-form-control" name="authority_id"><option value="0"><?= omoApiEscape(omoPolicyT('policy.field.authority_local')) ?></option><?php foreach ($authorities as $authority): ?><?php if ($authority instanceof \dbObject\Authority): ?><option value="<?= (int)$authority->getId() ?>"><?= omoApiEscape((string)$authority->get('label')) ?></option><?php endif; ?><?php endforeach; ?></select></label>
    <label><?= omoApiEscape(omoPolicyT('policy.field.intention')) ?><textarea class="generic-form-control" name="intention" rows="4" required></textarea></label>
    <label><?= omoApiEscape(omoPolicyT('policy.field.description')) ?><textarea class="generic-form-control" name="description" rows="7" required></textarea></label>
    <label><?= omoApiEscape(omoPolicyT('policy.field.review_date')) ?><input class="generic-form-control" type="date" name="review_date" value="<?= $today->modify('+6 months')->format('Y-m-d') ?>" required></label>
    <label><?= omoApiEscape(omoPolicyT('policy.field.expiration_date')) ?><input class="generic-form-control" type="date" name="expiration_date" value="<?= $today->modify('+1 year')->format('Y-m-d') ?>" required></label>
    <p data-policy-feedback hidden aria-live="polite"></p><button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoPolicyT('policy.save')) ?></button>
</form>
