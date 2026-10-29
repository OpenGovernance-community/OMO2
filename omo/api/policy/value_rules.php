<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 3) . '/common/choice/rule-value-fields.php';

commonReleaseReadOnlySession();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$context = omoPolicyResolveContext($organizationId);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoPolicyT('policy.error.context')) . '</div>';
    exit;
}
$value = \dbObject\Valeur::loadInOrganization((int)($_GET['id'] ?? 0), $organizationId);
if (!$value) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoPolicyT('policy.error.context')) . '</div>';
    exit;
}

$rules = new \dbObject\ArrayRule();
$rules->loadForValue((int)$value->getId(), $organizationId);
$entries = omoPolicyBuildRuleEntries($rules, $rules);
$popupId = 'omo-value-rules-' . (int)$value->getId();
?>
<div class="omo-policy omo-policy--embedded generic-section generic-section--plain generic-stack generic-stack--compact" id="<?= omoApiEscape($popupId) ?>" data-value-rules-list data-topbar-modal-max-width="960px">
    <?php if (!$entries): ?>
        <p class="omo-empty-state"><?= omoApiEscape(omoRuleValueT('rules_empty')) ?></p>
    <?php else: ?>
        <?php foreach ($entries as $index => $entry): ?>
            <?php
            $entry['number'] = (string)($index + 1);
            omoPolicyRenderRuleCard($entry, $context['organization'], [
                'showActions' => false,
                'idPrefix' => $popupId . '-content-',
                'detailsLabel' => 'policy.intention',
            ]);
            ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>if (window.initGenericComponents) window.initGenericComponents(document.getElementById(<?= json_encode($popupId) ?>));</script>
