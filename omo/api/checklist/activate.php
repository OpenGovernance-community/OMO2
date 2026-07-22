<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Checklist;
use dbObject\ChecklistTrigger;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$checklistId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoChecklistResolveContext($organizationId, $currentHolonId);
$checklist = !empty($context['status']) ? omoChecklistLoad($checklistId, $organizationId) : null;
$trigger = $checklist instanceof Checklist ? omoChecklistGetPrimaryTrigger($checklist) : null;
if (!($checklist instanceof Checklist) || !($trigger instanceof ChecklistTrigger) || !omoChecklistCanActivate($checklist, $trigger)) {
    http_response_code($checklist instanceof Checklist ? 403 : 404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT($checklist instanceof Checklist ? 'checklist.error.activation_unavailable' : 'checklist.error.not_found')) . '</div>';
    exit;
}

$templateRoot = $checklist->getTemplateRoot();
if (!($templateRoot instanceof Project)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoChecklistT('checklist.error.not_found')) . '</div>';
    exit;
}
$projectAttributeLength = Project::attributeLength();
$instanceTitleMaxLength = (int)($projectAttributeLength['title'] ?? 255);

$openRunCount = count($checklist->getOpenRuns());
$overlapPolicy = ChecklistTrigger::normalizeOverlapPolicy($trigger->get('overlap_policy'));
$detailUrl = '/omo/api/checklist/detail.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)$checklistId);
if ($currentHolonId > 0) {
    $detailUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
?>
<div class="omo-checklist-editor omo-checklist-activation" data-checklist-editor>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoChecklistT('checklist.form.activate_title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape((string)$templateRoot->get('title')) ?>"
    >
        <button type="submit" form="omo-checklist-activation-form" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action><?= omoApiEscape(omoChecklistT('checklist.action.activate')) ?></button>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-checklist-back-detail data-url="<?= omoApiEscape($detailUrl) ?>"><?= omoApiEscape(omoChecklistT('checklist.action.cancel')) ?></button>
    </div>
    <form id="omo-checklist-activation-form" action="/omo/api/checklist/action.php" method="post" data-checklist-activation-form>
        <input type="hidden" name="checklist_action" value="activate_checklist">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
        <input type="hidden" name="id" value="<?= (int)$checklistId ?>">

        <section class="generic-section omo-checklist-editor__section">
            <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape(omoChecklistT('checklist.form.activate_title')) ?></h3>
            <p class="omo-checklist-activation__intro"><?= omoApiEscape(omoChecklistT('checklist.form.activate_intro')) ?></p>
            <div class="omo-checklist-form-grid">
                <label class="omo-checklist-field omo-checklist-field--wide">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.instance_title')) ?></span>
                    <input class="generic-form-control" type="text" name="instance_title" value="<?= omoApiEscape((string)$templateRoot->get('title')) ?>" maxlength="<?= (int)$instanceTitleMaxLength ?>" required autofocus>
                    <small><?= omoApiEscape(omoChecklistT('checklist.form.instance_title_help')) ?></small>
                </label>
                <label class="omo-checklist-field omo-checklist-field--wide">
                    <span><?= omoApiEscape(omoChecklistT('checklist.form.reference_date')) ?></span>
                    <input class="generic-form-control" type="date" name="reference_date" value="<?= omoApiEscape((new DateTimeImmutable())->format('Y-m-d')) ?>" required>
                    <small><?= omoApiEscape(omoChecklistT('checklist.form.reference_help')) ?></small>
                </label>
                <?php if ($openRunCount > 0 && $overlapPolicy === ChecklistTrigger::OVERLAP_ASK): ?>
                    <label class="omo-checklist-activation__confirmation omo-checklist-field--wide">
                        <input type="checkbox" name="overlap_decision" value="create_new" required>
                        <span><?= omoApiEscape(omoChecklistT('checklist.form.confirm_overlap')) ?></span>
                    </label>
                <?php endif; ?>
            </div>
            <?php if ($openRunCount > 0): ?>
                <p class="omo-checklist-activation__notice"><?= omoApiEscape(omoChecklistT('checklist.detail.open_run_count', ['count' => $openRunCount])) ?></p>
            <?php endif; ?>
        </section>
        <div class="omo-checklist-feedback" data-checklist-editor-feedback aria-live="polite"></div>
    </form>
</div>
