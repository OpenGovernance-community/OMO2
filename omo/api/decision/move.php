<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\DecisionProcess;
use dbObject\Organization;

$sourceLang = [
    'decisions.move.error.invalid' => ['text' => 'La prise de décision à déplacer est invalide.', 'context' => 'Error shown when the decision id is invalid.'],
    'decisions.move.error.not_found' => ['text' => 'La prise de décision demandée est introuvable.', 'context' => 'Error shown when the decision cannot be loaded.'],
    'decisions.move.error.organization_not_found' => ['text' => 'Organisation introuvable.', 'context' => 'Error shown when the organization cannot be loaded.'],
    'decisions.move.error.forbidden' => ['text' => 'Vous n avez pas le droit de déplacer cette prise de décision.', 'context' => 'Error shown when the viewer cannot move the decision.'],
    'decisions.move.error.no_destination' => ['text' => 'Aucune destination accessible n a été trouvée pour cette prise de décision.', 'context' => 'Error shown when no alternative destination is available.'],
    'decisions.move.field.destination' => ['text' => 'Destination', 'context' => 'Label shown above the decision destination picker.'],
    'decisions.move.field.holon' => ['text' => 'Holon de destination', 'context' => 'Label shown above the visual holon picker.'],
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
    <form id="omo-decision-move-form" class="generic-section generic-section--stack">
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
                        <input type="search" id="omo-decision-move-search" class="generic-form-control" placeholder="<?= omoApiEscape(omoDecisionsMoveT('decisions.move.field.search_placeholder')) ?>">
                    </span>
                </label>
                <select id="omo-decision-move-destination" class="generic-form-control" size="10" aria-label="<?= omoApiEscape(omoDecisionsMoveT('decisions.move.field.destination')) ?>"></select>
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
<script>
(() => {
const state = {
    data: <?= json_encode($moveData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    text: <?= json_encode([
        'invalidDestination' => omoDecisionsMoveT('decisions.move.status.invalid_destination'),
        'selectOther' => omoDecisionsMoveT('decisions.move.status.select_other'),
        'noMatch' => omoDecisionsMoveT('decisions.move.status.no_match'),
        'submitOther' => omoDecisionsMoveT('decisions.move.status.submit_other'),
        'failed' => omoDecisionsMoveT('decisions.move.error.failed'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    selectedHolonId: 0,
    holonPicker: null,
    restoringSelection: false
};

const form = document.getElementById('omo-decision-move-form');
if (!form) return;

const elements = {
    search: document.getElementById('omo-decision-move-search'),
    destination: document.getElementById('omo-decision-move-destination'),
    picker: document.querySelector('[data-omo-decision-move-holon-picker]'),
    path: document.querySelector('[data-omo-decision-move-path]'),
    hint: document.getElementById('omo-decision-move-hint'),
    status: document.getElementById('omo-decision-move-status'),
    cancel: document.getElementById('omo-decision-move-cancel'),
    submit: document.getElementById('omo-decision-move-submit')
};

function destinations() {
    return Array.isArray(state.data.destinations) ? state.data.destinations : [];
}

function destinationById(holonId) {
    return destinations().find(function (destination) {
        return Number(destination && destination.holonId || 0) === Number(holonId || 0);
    }) || null;
}

function selectableHolonIds() {
    return destinations().map(function (destination) {
        return Number(destination && destination.holonId || 0);
    }).filter(function (holonId) {
        return Number.isInteger(holonId) && holonId > 0;
    });
}

function isCurrentDestination(holonId) {
    return Number((state.data.currentDestination && state.data.currentDestination.holonId) || 0) === Number(holonId || 0);
}

function filteredDestinations() {
    const query = String(elements.search.value || '').trim().toLowerCase();
    if (query === '') return destinations();
    return destinations().filter(function (destination) {
        return [destination.pathLabel || '', destination.name || '', destination.typeLabel || '']
            .join(' ').toLowerCase().indexOf(query) !== -1;
    });
}

function showStatus(message) {
    elements.status.hidden = !message;
    elements.status.textContent = String(message || '');
    elements.status.className = 'generic-feedback is-error';
}

function updateSubmitState() {
    const destination = destinationById(state.selectedHolonId);
    const canSubmit = !!destination && !isCurrentDestination(state.selectedHolonId);
    elements.submit.disabled = !canSubmit;
    elements.hint.textContent = !destination
        ? String(state.text.invalidDestination || '')
        : (isCurrentDestination(state.selectedHolonId) ? String(state.text.selectOther || '') : '');
    elements.path.textContent = destination
        ? String(destination.pathLabel || destination.name || '')
        : '';
}

function renderDestinations() {
    const matches = filteredDestinations();
    elements.destination.innerHTML = '';
    if (matches.length === 0) {
        const option = document.createElement('option');
        option.disabled = true;
        option.textContent = String(state.text.noMatch || '');
        elements.destination.appendChild(option);
    } else {
        matches.forEach(function (destination) {
            const option = document.createElement('option');
            option.value = String(Number(destination.holonId || 0));
            option.textContent = String(destination.pathLabel || destination.name || '');
            option.selected = Number(destination.holonId || 0) === state.selectedHolonId;
            elements.destination.appendChild(option);
        });
    }
    updateSubmitState();
}

function selectDestination(holonId, syncPicker) {
    const destination = destinationById(holonId);
    if (!destination) return false;
    state.selectedHolonId = Number(destination.holonId || 0);
    renderDestinations();
    if (syncPicker && state.holonPicker && typeof state.holonPicker.setSelectedHolonId === 'function') {
        state.holonPicker.setSelectedHolonId(state.selectedHolonId);
    }
    return true;
}

const currentHolonId = Number((state.data.currentDestination && state.data.currentDestination.holonId) || 0);
const initialDestination = destinationById(currentHolonId) || destinations()[0] || null;
if (initialDestination) state.selectedHolonId = Number(initialDestination.holonId || 0);

if (elements.search) {
    elements.search.addEventListener('input', renderDestinations);
}
elements.destination.addEventListener('change', function () {
    selectDestination(Number(elements.destination.value || 0), true);
});

if (elements.picker instanceof Element && typeof window.omoMountHolonScopePicker === 'function') {
    state.holonPicker = window.omoMountHolonScopePicker({
        host: elements.picker,
        organizationId: Number(state.data.organizationId || 0),
        initialHolonId: state.selectedHolonId,
        selectableHolonIds: selectableHolonIds(),
        ignoreHolonAssignments: true,
        showModes: false,
        onChange: function (holonId) {
            if (destinationById(holonId)) {
                state.selectedHolonId = Number(holonId || 0);
                renderDestinations();
                return;
            }
            if (!state.restoringSelection && state.holonPicker && typeof state.holonPicker.setSelectedHolonId === 'function') {
                state.restoringSelection = true;
                state.holonPicker.setSelectedHolonId(state.selectedHolonId);
                state.restoringSelection = false;
            }
        }
    });
}

renderDestinations();

elements.cancel.addEventListener('click', function () {
    if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
});

form.addEventListener('submit', function (event) {
    event.preventDefault();
    showStatus('');
    if (!destinationById(state.selectedHolonId)) {
        showStatus(state.text.invalidDestination || '');
        return;
    }
    if (isCurrentDestination(state.selectedHolonId)) {
        showStatus(state.text.submitOther || '');
        return;
    }

    elements.submit.disabled = true;
    fetch('/omo/api/decision/move_action.php?id=' + encodeURIComponent(String(state.data.decisionId || 0)), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({targetHolonId: state.selectedHolonId})
    })
        .then(function (response) {
            return response.json().catch(function () { return null; }).then(function (data) {
                return {ok: response.ok, data: data};
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || !result.data.status) {
                throw new Error(result.data && result.data.message ? result.data.message : state.text.failed);
            }
            if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
            window.dispatchEvent(new CustomEvent('omo-decision-moved', {detail: result.data}));
        })
        .catch(function (error) {
            elements.submit.disabled = false;
            showStatus(error && error.message ? error.message : state.text.failed);
        });
});
})();
</script>
<?php endif; ?>
