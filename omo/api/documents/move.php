<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\Organization;

$sourceLang = [
    'documents.move.error.invalid' => ['text' => 'Le document à déplacer est invalide.', 'context' => 'Error shown when the source document id is invalid.'],
    'documents.move.error.not_found' => ['text' => 'Le document demandé est introuvable.', 'context' => 'Error shown when the source document cannot be loaded.'],
    'documents.move.error.organization_not_found' => ['text' => 'Organisation introuvable.', 'context' => 'Error shown when the document organization cannot be loaded.'],
    'documents.move.error.forbidden' => ['text' => 'Vous n’avez pas le droit de déplacer ce document.', 'context' => 'Error shown when the user cannot move the document.'],
    'documents.move.error.no_destination' => ['text' => 'Aucune destination compatible n’a été trouvée pour ce document.', 'context' => 'Error shown when no destination is available.'],
    'documents.move.field.destination' => ['text' => 'Destination', 'context' => 'Label shown above the destination picker.'],
    'documents.move.field.search_placeholder' => ['text' => 'Rechercher une destination', 'context' => 'Search placeholder used in the move dialog.'],
    'documents.move.action.cancel' => ['text' => 'Annuler', 'context' => 'Button used to cancel document moving.'],
    'documents.move.action.submit' => ['text' => 'Déplacer', 'context' => 'Button used to submit document moving.'],
    'documents.move.status.invalid_destination' => ['text' => 'Choisissez une destination valide.', 'context' => 'Hint or error shown when no valid destination is selected.'],
    'documents.move.status.select_other' => ['text' => 'Sélectionnez une autre destination pour activer le déplacement.', 'context' => 'Hint shown when the current destination is selected.'],
    'documents.move.status.no_match' => ['text' => 'Aucune destination correspondante', 'context' => 'Option shown when the destination search yields no result.'],
    'documents.move.status.submit_other' => ['text' => 'Sélectionnez une autre destination avant de déplacer ce document.', 'context' => 'Error shown when trying to submit with the current destination.'],
];

$lang = omoLoadTranslationBundle('omo_documents_move', $sourceLang);

function omoDocumentsMoveT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$documentId = (int)($_GET['id'] ?? 0);
$document = new Document();
$organization = new Organization();
$moveData = null;
$errorMessage = '';

if ($documentId <= 0) {
    $errorMessage = omoDocumentsMoveT('documents.move.error.invalid');
} elseif (
    !$document->load($documentId)
    || (int)$document->get('IDorganization') <= 0
) {
    $errorMessage = omoDocumentsMoveT('documents.move.error.not_found');
} else {
    $organizationId = (int)$document->get('IDorganization');

    if (!$organization->load($organizationId)) {
        $errorMessage = omoDocumentsMoveT('documents.move.error.organization_not_found');
    } else {
        $moveData = $organization->getDocumentMoveEditorData($documentId);

        if (($moveData['documentId'] ?? 0) !== $documentId || !is_array($moveData['document'] ?? null)) {
            $errorMessage = omoDocumentsMoveT('documents.move.error.not_found');
        } elseif (empty($moveData['canMove'])) {
            $errorMessage = omoDocumentsMoveT('documents.move.error.forbidden');
        } else {
            $alternativeCount = 0;
            foreach (($moveData['destinations'] ?? array()) as $destination) {
                if (empty($destination['isCurrentDestination'])) {
                    $alternativeCount += 1;
                }
            }

            if ($alternativeCount <= 0) {
                $errorMessage = omoDocumentsMoveT('documents.move.error.no_destination');
            }
        }
    }
}
?>
<?php if ($errorMessage !== ''): ?>
    <div class="omo-document-move__empty generic-description"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form id="omo-document-move-form" class="omo-document-move generic-stack generic-stack--flush">
        <div class="omo-document-move__header generic-drawer-header generic-drawer-header--sticky">
            <div class="generic-drawer-header__copy omo-document-move__header-copy">
                <div class="generic-card-title generic-card-title--eyebrow">Document</div>
                <h3 class="generic-card-title generic-card-title--medium">Déplacer un document</h3>
            </div>
        </div>
        <div class="omo-document-move__shell generic-drawer-content">
        <div class="omo-document-move__intro generic-description">
            <strong><?= omoApiEscape((string)($moveData['document']['title'] ?? '')) ?></strong>
            <span>&rarr;</span>
        </div>

        <label class="omo-document-move__field generic-stack generic-stack--compact">
            <span><?= omoApiEscape(omoDocumentsMoveT('documents.move.field.destination')) ?></span>
            <input type="search" id="omo-document-move-search" class="generic-form-control" placeholder="<?= omoApiEscape(omoDocumentsMoveT('documents.move.field.search_placeholder')) ?>">
        </label>

        <label class="omo-document-move__field generic-stack generic-stack--compact">
            <select id="omo-document-move-destination" class="omo-document-move__select generic-form-control" size="10" required></select>
        </label>

        <div id="omo-document-move-hint" class="omo-document-move__hint generic-help-text"></div>
        <div id="omo-document-move-status" class="omo-document-move__status generic-feedback" hidden></div>

        <div class="omo-document-move__actions generic-action-row">
            <button type="button" class="omo-document-move__button generic-action-button generic-action-button--secondary" id="omo-document-move-cancel"><?= omoApiEscape(omoDocumentsMoveT('documents.move.action.cancel')) ?></button>
            <button type="submit" class="omo-document-move__button generic-action-button generic-action-button--main" id="omo-document-move-submit"><?= omoApiEscape(omoDocumentsMoveT('documents.move.action.submit')) ?></button>
        </div>
        </div>
    </form>
<?php endif; ?>

<?php if ($moveData !== null && $errorMessage === ''): ?>
<script>
(() => {
const state = {
    data: <?= json_encode($moveData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    text: <?= json_encode([
        'invalidDestination' => omoDocumentsMoveT('documents.move.status.invalid_destination'),
        'selectOther' => omoDocumentsMoveT('documents.move.status.select_other'),
        'noMatch' => omoDocumentsMoveT('documents.move.status.no_match'),
        'submitOther' => omoDocumentsMoveT('documents.move.status.submit_other'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    statusTimer: null
};

const form = document.getElementById('omo-document-move-form');
if (!form) {
    return;
}

const elements = {
    form: form,
    status: document.getElementById('omo-document-move-status'),
    search: document.getElementById('omo-document-move-search'),
    destination: document.getElementById('omo-document-move-destination'),
    hint: document.getElementById('omo-document-move-hint'),
    cancel: document.getElementById('omo-document-move-cancel'),
    submit: document.getElementById('omo-document-move-submit')
};

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function clearStatus() {
    if (state.statusTimer) {
        window.clearTimeout(state.statusTimer);
        state.statusTimer = null;
    }

    elements.status.hidden = true;
    elements.status.className = 'omo-document-move__status generic-feedback';
    elements.status.innerHTML = '';
}

function showStatus(message, tone) {
    clearStatus();
    elements.status.hidden = false;
    elements.status.className = 'omo-document-move__status generic-feedback is-' + tone;
    elements.status.innerHTML = '<div class="omo-document-move__status-copy">' + escapeHtml(message) + '</div>';
    state.statusTimer = window.setTimeout(clearStatus, 12000);
}

function closeMovePopup() {
    if (typeof window.commonTopbarCloseModal === 'function') {
        window.commonTopbarCloseModal();
    }
}

function getDestinations() {
    return Array.isArray(state.data.destinations) ? state.data.destinations : [];
}

function getCurrentDestinationKey() {
    return String((state.data.currentDestination && state.data.currentDestination.key) || 'organization');
}

function getSelectedDestinationKey() {
    return String(elements.destination.value || '');
}

function findDestinationByKey(key) {
    const normalizedKey = String(key || '');
    if (normalizedKey === '') {
        return null;
    }

    return getDestinations().find(function (destination) {
        return String(destination && destination.key ? destination.key : '') === normalizedKey;
    }) || null;
}

function getFilteredDestinations() {
    const query = String(elements.search.value || '').trim().toLowerCase();
    if (!query) {
        return getDestinations();
    }

    return getDestinations().filter(function (destination) {
        const haystack = [
            destination.pathLabel || '',
            destination.name || '',
            destination.typeLabel || ''
        ].join(' ').toLowerCase();

        return haystack.indexOf(query) !== -1;
    });
}

function updateSubmitState() {
    const selectedKey = getSelectedDestinationKey();
    const currentKey = getCurrentDestinationKey();
    const selectedDestination = findDestinationByKey(selectedKey);
    const canSubmit = !!selectedDestination && selectedKey !== currentKey;

    if (elements.submit) {
        elements.submit.disabled = !canSubmit;
    }

    if (elements.hint) {
        if (!selectedDestination) {
            elements.hint.textContent = state.text.invalidDestination || '';
        } else if (selectedKey === currentKey) {
            elements.hint.textContent = state.text.selectOther || '';
        } else {
            elements.hint.textContent = '';
        }
    }
}

function renderDestinations() {
    const destinations = getFilteredDestinations();
    const previousSelection = getSelectedDestinationKey() || getCurrentDestinationKey();

    elements.destination.innerHTML = '';

    if (!destinations.length) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = state.text.noMatch || '';
        option.disabled = true;
        option.selected = true;
        elements.destination.appendChild(option);
        updateSubmitState();
        return;
    }

    destinations.forEach(function (destination, index) {
        const option = document.createElement('option');
        const destinationKey = String(destination && destination.key ? destination.key : '');

        option.value = destinationKey;
        option.textContent = String(destination.pathLabel || destination.name || destinationKey || ('#' + String(index + 1)));
        option.dataset.currentDestination = destination.isCurrentDestination ? '1' : '0';

        if (destinationKey === previousSelection) {
            option.selected = true;
        } else if (!previousSelection && index === 0) {
            option.selected = true;
        }

        elements.destination.appendChild(option);
    });

    updateSubmitState();
}

function submitMove(event) {
    event.preventDefault();
    clearStatus();

    const targetDestination = findDestinationByKey(getSelectedDestinationKey());
    const currentKey = getCurrentDestinationKey();

    if (!targetDestination) {
        showStatus(state.text.invalidDestination || '', 'error');
        return;
    }

    if (String(targetDestination.key || '') === currentKey) {
        showStatus(state.text.submitOther || '', 'error');
        return;
    }

    if (elements.submit) {
        elements.submit.disabled = true;
    }

    fetch('/omo/api/documents/move_action.php?id=' + Number(state.data.documentId || 0), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            targetHolonId: Number(targetDestination.holonId || 0),
            targetParentDocumentId: Number(targetDestination.parentDocumentId || 0)
        })
    })
        .then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || result.data.status !== 'ok') {
                throw new Error(result.data && result.data.message ? result.data.message : "Impossible de déplacer le document.");
            }

            closeMovePopup();

            if (typeof window.omoRefreshDocumentsPanel === 'function') {
                window.omoRefreshDocumentsPanel().catch(function () {
                });
            }
        })
        .catch(function (error) {
            if (elements.submit) {
                elements.submit.disabled = false;
            }

            showStatus(error && error.message ? error.message : "Impossible de déplacer le document.", 'error');
        });
}

renderDestinations();

elements.search.addEventListener('input', renderDestinations);
elements.destination.addEventListener('change', updateSubmitState);
elements.form.addEventListener('submit', submitMove);
elements.cancel.addEventListener('click', closeMovePopup);
})();
</script>
<?php endif; ?>

<style>
.omo-document-move__empty {
    display: grid;
    gap: 16px;
    color: var(--color-text, #1f2937);
}

.omo-document-move__empty {
    padding: 18px;
}

.omo-document-move__intro {
    display: flex;
    align-items: center;
    gap: 8px;
}

.omo-document-move__select {
    min-height: 240px;
}

.omo-document-move__status[hidden] {
    display: none !important;
}

</style>
