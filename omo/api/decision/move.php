<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DecisionProcess;
use dbObject\Organization;

$sourceLang = [
    'decisions.move.error.invalid' => ['text' => 'La prise de décision à déplacer est invalide.', 'context' => 'Error shown when the decision id is invalid.'],
    'decisions.move.error.not_found' => ['text' => 'La prise de décision demandée est introuvable.', 'context' => 'Error shown when the decision cannot be loaded.'],
    'decisions.move.error.organization_not_found' => ['text' => 'Organisation introuvable.', 'context' => 'Error shown when the organization cannot be loaded.'],
    'decisions.move.error.forbidden' => ['text' => 'Vous n avez pas le droit de déplacer cette prise de décision.', 'context' => 'Error shown when the viewer cannot move the decision.'],
    'decisions.move.error.no_destination' => ['text' => 'Aucune destination accessible n’a été trouvée pour cette prise de décision.', 'context' => 'Error shown when no alternative destination is available.'],
    'decisions.move.field.destination' => ['text' => 'Destination', 'context' => 'Label shown above the decision destination picker.'],
            'decisions.move.field.holon' => ['text' => 'Espace de destination', 'context' => 'Label shown above the visual space picker.'],
    'decisions.move.field.search_placeholder' => ['text' => 'Rechercher une destination', 'context' => 'Search placeholder used in the move dialog.'],
    'decisions.move.action.cancel' => ['text' => 'Annuler', 'context' => 'Button used to close the decision move dialog.'],
    'decisions.move.action.submit' => ['text' => 'Déplacer', 'context' => 'Button used to submit a decision move.'],
    'decisions.move.status.invalid_destination' => ['text' => 'Choisissez une destination accessible.', 'context' => 'Hint shown when the selected destination is unavailable.'],
    'decisions.move.status.select_other' => ['text' => 'Sélectionnez une autre destination pour activer le déplacement.', 'context' => 'Hint shown when the current destination is selected.'],
    'decisions.move.status.no_match' => ['text' => 'Aucune destination correspondante.', 'context' => 'Empty state for destination search.'],
    'decisions.move.status.submit_other' => ['text' => 'Sélectionnez une autre destination avant de déplacer cette prise de décision.', 'context' => 'Error shown when trying to move to the current destination.'],
    'decisions.move.error.failed' => ['text' => 'Impossible de déplacer cette prise de décision.', 'context' => 'Fallback error shown when moving a decision fails.'],
];

$lang = omoLoadTranslationBundle('omo_decisions_move', $sourceLang);
function omoDecisionsMoveT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$decisionId = (int)($_GET['id'] ?? 0);
$decision = new DecisionProcess();
$organization = new Organization();
$moveData = null;
$errorMessage = '';

if ($decisionId <= 0) {
    $errorMessage = omoDecisionsMoveT('decisions.move.error.invalid');
} elseif (!$decision->load($decisionId) || (int)$decision->get('IDorganization') <= 0) {
    $errorMessage = omoDecisionsMoveT('decisions.move.error.not_found');
} elseif (!$organization->load((int)$decision->get('IDorganization'))) {
    $errorMessage = omoDecisionsMoveT('decisions.move.error.organization_not_found');
} else {
    $moveData = $organization->getDecisionMoveEditorData($decisionId);
    if (($moveData['decisionId'] ?? 0) !== $decisionId || !is_array($moveData['decision'] ?? null)) {
        $errorMessage = omoDecisionsMoveT('decisions.move.error.not_found');
    } elseif (empty($moveData['canMove'])) {
        $errorMessage = omoDecisionsMoveT('decisions.move.error.forbidden');
    } else {
        $alternativeCount = 0;
        foreach (($moveData['destinations'] ?? []) as $destination) {
            if (empty($destination['isCurrentDestination'])) {
                $alternativeCount++;
            }
        }
        if ($alternativeCount <= 0) {
            $errorMessage = omoDecisionsMoveT('decisions.move.error.no_destination');
        }
    }
}
?>
<?php if ($errorMessage !== ''): ?>
    <div class="generic-section generic-section--stack"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form id="omo-decision-move-form" class="generic-drawer-content generic-form-stack generic-form-stack--compact">
        <div class="generic-description">
            <strong><?= omoApiEscape((string)($moveData['decision']['title'] ?? '')) ?></strong>
            <span>&rarr;</span>
            <span data-omo-decision-move-path></span>
        </div>

        <div class="omo-resource-picker">
            <aside class="omo-resource-picker__navigation generic-stack generic-stack--compact">
                <span class="generic-form-label"><?= omoApiEscape(omoDecisionsMoveT('decisions.move.field.holon')) ?></span>
                <div data-omo-decision-move-holon-picker></div>
            </aside>
            <div class="omo-resource-picker__content generic-stack generic-stack--compact">
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoDecisionsMoveT('decisions.move.field.destination')) ?></span>
                    <span class="omo-resource-picker__quick-search">
                        <img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true">
                        <input type="search" id="omo-decision-move-search" class="generic-form-control generic-form-control--compact" placeholder="<?= omoApiEscape(omoDecisionsMoveT('decisions.move.field.search_placeholder')) ?>">
                    </span>
                </label>
                <select id="omo-decision-move-destination" class="generic-form-control generic-form-control--compact" size="10" aria-label="<?= omoApiEscape(omoDecisionsMoveT('decisions.move.field.destination')) ?>"></select>
            </div>
        </div>

        <div id="omo-decision-move-status" class="generic-feedback" hidden></div>
        <div class="generic-action-row">
            <div id="omo-decision-move-hint" class="generic-help-text"></div>
            <button type="button" class="generic-action-button generic-action-button--secondary" id="omo-decision-move-cancel"><?= omoApiEscape(omoDecisionsMoveT('decisions.move.action.cancel')) ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main" id="omo-decision-move-submit"><?= omoApiEscape(omoDecisionsMoveT('decisions.move.action.submit')) ?></button>
        </div>
    </form>
<?php endif; ?>

<?php if ($moveData !== null && $errorMessage === ''): ?>
<?= commonPageScriptTags('/omo/api/decision/move.js', [
    'data' => $moveData,
    'text' => [
        'invalidDestination' => omoDecisionsMoveT('decisions.move.status.invalid_destination'),
        'selectOther' => omoDecisionsMoveT('decisions.move.status.select_other'),
        'noMatch' => omoDecisionsMoveT('decisions.move.status.no_match'),
        'submitOther' => omoDecisionsMoveT('decisions.move.status.submit_other'),
        'failed' => omoDecisionsMoveT('decisions.move.error.failed'),
    ],
]) ?>
<?php endif; ?>
