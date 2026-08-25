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
    'documents.move.error.bulk_not_found' => ['text' => 'Un des documents selectionnes est introuvable.', 'context' => 'Error shown when a selected document cannot be loaded for a bulk move.'],
    'documents.move.error.bulk_forbidden' => ['text' => 'Vous n avez pas le droit de deplacer tous les documents selectionnes.', 'context' => 'Error shown when the user cannot move every selected document.'],
    'documents.move.selected_count' => ['text' => '{count} documents selectionnes', 'context' => 'Summary shown when several documents are selected for moving.'],
    'documents.move.field.destination' => ['text' => 'Dossier de destination', 'context' => 'Label shown above the destination picker.'],
    'documents.move.field.search_placeholder' => ['text' => 'Rechercher une destination', 'context' => 'Search placeholder used in the move dialog.'],
    'documents.move.field.holon' => ['text' => 'Holon de destination', 'context' => 'Label shown above the visual holon picker in the move dialog.'],
    'documents.move.destination.holon_root' => ['text' => 'A la racine du holon', 'context' => 'Destination choice placing a document at the root of the selected holon.'],
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

$documentIds = [];
$rawDocumentIds = $_GET['ids'] ?? [];
if (!is_array($rawDocumentIds)) {
    $rawDocumentIds = preg_split('/[,\s]+/', (string)$rawDocumentIds, -1, PREG_SPLIT_NO_EMPTY);
}
foreach ($rawDocumentIds as $rawDocumentId) {
    $normalizedDocumentId = (int)$rawDocumentId;
    if ($normalizedDocumentId > 0 && !in_array($normalizedDocumentId, $documentIds, true)) {
        $documentIds[] = $normalizedDocumentId;
    }
}
$documentId = (int)($_GET['id'] ?? 0);
if ($documentId > 0 && !in_array($documentId, $documentIds, true)) {
    array_unshift($documentIds, $documentId);
}
$documentId = (int)($documentIds[0] ?? 0);
$isBulkMove = count($documentIds) > 1;
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
            foreach ($documentIds as $selectedDocumentId) {
                $selectedDocument = new Document();
                if (
                    !$selectedDocument->load($selectedDocumentId)
                    || (int)$selectedDocument->get('IDorganization') !== $organizationId
                ) {
                    $errorMessage = omoDocumentsMoveT('documents.move.error.bulk_not_found');
                    break;
                }
                if (!$selectedDocument->canMoveInOrganizationContext($organizationId, (int)commonGetCurrentUserId())) {
                    $errorMessage = omoDocumentsMoveT('documents.move.error.bulk_forbidden');
                    break;
                }
            }

            $alternativeCount = 0;
            foreach (($moveData['destinations'] ?? array()) as $destination) {
                if (empty($destination['isCurrentDestination'])) {
                    $alternativeCount += 1;
                }
            }

            if ($errorMessage === '' && $alternativeCount <= 0) {
                $errorMessage = omoDocumentsMoveT('documents.move.error.no_destination');
            }

            if ($errorMessage === '') {
                $moveData['documentIds'] = $documentIds;
                $moveData['documentCount'] = count($documentIds);
            }
        }
    }
}
?>
<?php if ($errorMessage !== ''): ?>
    <div class="omo-document-move__empty generic-description"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form id="omo-document-move-form" class="omo-document-move generic-stack generic-stack--flush">
        <div class="omo-document-move__shell generic-drawer-content">
        <div class="omo-document-move__intro generic-description">
            <strong><?= omoApiEscape($isBulkMove
                ? omoDocumentsMoveT('documents.move.selected_count', ['count' => count($documentIds)])
                : (string)($moveData['document']['title'] ?? '')) ?></strong>
            <span>&rarr;</span>
            <span data-omo-document-move-path></span>
        </div>

        <div class="omo-document-move__picker omo-resource-picker">
            <aside class="omo-resource-picker__navigation omo-document-move__navigation">
                <span class="generic-form-label"><?= omoApiEscape(omoDocumentsMoveT('documents.move.field.holon')) ?></span>
                <div class="omo-document-move__holon-picker" data-omo-document-move-holon-picker></div>
            </aside>

            <div class="omo-resource-picker__content omo-document-move__content">
                <label class="omo-document-move__field generic-stack generic-stack--compact">
                    <span class="generic-form-label"><?= omoApiEscape(omoDocumentsMoveT('documents.move.field.destination')) ?></span>
                    <span class="omo-resource-picker__quick-search">
                        <img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true">
                        <input type="search" id="omo-document-move-search" class="generic-form-control" placeholder="<?= omoApiEscape(omoDocumentsMoveT('documents.move.field.search_placeholder')) ?>">
                    </span>
                </label>

                <div class="omo-document-move__field generic-stack generic-stack--compact">
                    <div id="omo-document-move-destination" class="omo-document-move__destinations" aria-label="<?= omoApiEscape(omoDocumentsMoveT('documents.move.field.destination')) ?>"></div>
                </div>
            </div>
        </div>

        <div id="omo-document-move-status" class="omo-document-move__status generic-feedback" hidden></div>

        <div class="omo-document-move__actions generic-action-row">
            <div id="omo-document-move-hint" class="omo-document-move__hint generic-help-text"></div>
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
        'holonRoot' => omoDocumentsMoveT('documents.move.destination.holon_root'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    statusTimer: null,
    selectedHolonId: 0,
    selectedDestinationKey: '',
    holonPicker: null
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
    holonPicker: document.querySelector('[data-omo-document-move-holon-picker]'),
    path: document.querySelector('[data-omo-document-move-path]'),
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
    return String(state.selectedDestinationKey || '');
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

function getDestinationsForSelectedHolon() {
    return getFilteredDestinations().filter(function (destination) {
        return Number(destination && destination.holonId || 0) === Number(state.selectedHolonId || 0);
    });
}

function getSelectableHolonIds() {
    return getDestinations()
        .filter(function (destination) {
            return String(destination && destination.key || '').indexOf('holon-') === 0;
        })
        .map(function (destination) {
            return Number(destination && destination.holonId || 0);
        })
        .filter(function (holonId) {
            return Number.isInteger(holonId) && holonId > 0;
        });
}

function getDestinationLabel(destination) {
    if (String(destination && destination.key || '').indexOf('holon-') === 0) {
        return state.text.holonRoot || '';
    }

    return String(destination && destination.name || '');
}

function buildDestinationTree(destinations) {
    const rootDestination = destinations.find(function (destination) {
        return String(destination && destination.key || '').indexOf('holon-') === 0;
    }) || null;
    const folderNodes = new Map();
    const rootNode = rootDestination ? { destination: rootDestination, children: [] } : null;
    const roots = rootNode ? [rootNode] : [];

    destinations.forEach(function (destination) {
        if (String(destination && destination.key || '').indexOf('folder-') !== 0) {
            return;
        }
        folderNodes.set(Number(destination.parentDocumentId || 0), {
            destination: destination,
            children: []
        });
    });

    folderNodes.forEach(function (node) {
        const parentNode = folderNodes.get(Number(node.destination.folderParentDocumentId || 0));
        if (parentNode) {
            parentNode.children.push(node);
            return;
        }
        if (rootNode) {
            rootNode.children.push(node);
            return;
        }
        roots.push(node);
    });

    return roots;
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

    if (elements.path) {
        elements.path.textContent = selectedDestination
            ? String(selectedDestination.pathLabel || selectedDestination.name || '')
            : (state.holonPicker && typeof state.holonPicker.getSelectedHolonLabel === 'function'
                ? state.holonPicker.getSelectedHolonLabel()
                : '');
    }
}

function renderDestinations() {
    const destinations = getDestinationsForSelectedHolon();
    const previousSelection = getSelectedDestinationKey() || getCurrentDestinationKey();

    elements.destination.innerHTML = '';
    elements.destination.setAttribute('aria-busy', 'false');

    if (!destinations.length) {
        const empty = document.createElement('div');
        empty.className = 'omo-document-move__empty-destination generic-help-text';
        empty.textContent = state.text.noMatch || '';
        elements.destination.appendChild(empty);
        state.selectedDestinationKey = '';
        updateSubmitState();
        return;
    }

    let renderedOptionCount = 0;
    function renderTreeNode(node, depth) {
        const destination = node.destination || {};
        const destinationKey = String(destination.key || '');
        const option = document.createElement('button');
        const label = document.createElement('span');
        const meta = document.createElement('span');

        option.type = 'button';
        option.className = 'omo-document-move__destination';
        option.classList.toggle('is-nested', depth > 0);
        option.dataset.destinationKey = destinationKey;
        option.dataset.currentDestination = destination.isCurrentDestination ? '1' : '0';
        option.style.setProperty('--omo-document-move-tree-indent', String(depth * 22) + 'px');
        option.style.setProperty('--omo-document-move-tree-branch-indent', String(Math.max(0, depth - 1) * 22) + 'px');
        label.className = 'omo-document-move__destination-label';
        label.textContent = getDestinationLabel(destination) || ('#' + String(renderedOptionCount + 1));
        meta.className = 'omo-document-move__destination-meta';
        meta.textContent = String(destination.typeLabel || '');
        option.appendChild(label);
        if (meta.textContent !== '') {
            option.appendChild(meta);
        }

        if (destinationKey === previousSelection) {
            state.selectedDestinationKey = destinationKey;
        } else if (!previousSelection && renderedOptionCount === 0) {
            state.selectedDestinationKey = destinationKey;
        }

        elements.destination.appendChild(option);
        renderedOptionCount += 1;
        (Array.isArray(node.children) ? node.children : []).forEach(function (child) {
            renderTreeNode(child, depth + 1);
        });
    }

    buildDestinationTree(destinations).forEach(function (node) {
        renderTreeNode(node, 0);
    });

    elements.destination.querySelectorAll('[data-destination-key]').forEach(function (option) {
        const isSelected = option.dataset.destinationKey === state.selectedDestinationKey;
        option.classList.toggle('is-selected', isSelected);
        option.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
    });
    updateSubmitState();
}

function setSelectedHolonId(holonId) {
    state.selectedHolonId = Number(holonId || 0);
    state.selectedDestinationKey = '';
    renderDestinations();
}

function mountHolonPicker() {
    if (!(elements.holonPicker instanceof Element) || typeof window.omoMountHolonScopePicker !== 'function') {
        return;
    }

    state.holonPicker = window.omoMountHolonScopePicker({
        host: elements.holonPicker,
        organizationId: Number(state.data.organizationId || 0),
        initialHolonId: Number((state.data.currentDestination && state.data.currentDestination.holonId) || 0),
        selectableHolonIds: getSelectableHolonIds(),
        ignoreHolonAssignments: true,
        showModes: false,
        onChange: function (holonId) {
            setSelectedHolonId(holonId);
        }
    });
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
            ids: Array.isArray(state.data.documentIds) && state.data.documentIds.length > 0
                ? state.data.documentIds
                : [Number(state.data.documentId || 0)],
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

            if (Array.isArray(state.data.documentIds) && state.data.documentIds.length > 0) {
                window.dispatchEvent(new CustomEvent('omo-documents-bulk-move-complete', {
                    detail: {ids: state.data.documentIds}
                }));
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

state.selectedHolonId = Number((state.data.currentDestination && state.data.currentDestination.holonId) || 0) || -1;
renderDestinations();
mountHolonPicker();

elements.search.addEventListener('input', renderDestinations);
elements.destination.addEventListener('click', function (event) {
    const option = event.target.closest('[data-destination-key]');
    if (!option) {
        return;
    }
    state.selectedDestinationKey = String(option.dataset.destinationKey || '');
    renderDestinations();
});
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

.omo-document-move {
    max-width: 100%;
    min-width: 0;
}

#commonTopbarModalBody:has(.omo-document-move) {
    overflow-x: hidden;
}

.omo-document-move__intro {
    display: flex;
    align-items: center;
    gap: 8px;
}

.omo-document-move__picker {
    align-items: start;
}

.omo-document-move__navigation,
.omo-document-move__content {
    display: grid;
    gap: 12px;
}

.omo-document-move__holon-picker .omo-holon-scope-picker__map {
    width: min(100%, min(520px, calc(100dvh - 360px)));
    height: auto;
    margin-top: 0;
    aspect-ratio: 1;
}

.omo-document-move__destinations {
    display: grid;
    align-content: start;
    gap: 8px;
    min-height: 240px;
    max-height: 390px;
    overflow: auto;
    padding: 2px;
}

.omo-document-move__destination {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 2px 10px;
    width: 100%;
    padding: 11px 12px 11px calc(12px + var(--omo-document-move-tree-indent, 0px));
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    color: var(--color-text);
    font: inherit;
    text-align: left;
    background: var(--color-surface);
    cursor: pointer;
    overflow: hidden;
    box-sizing: border-box;
}

.omo-document-move__destination.is-nested::before {
    content: '\21B3';
    position: absolute;
    left: calc(10px + var(--omo-document-move-tree-branch-indent, 0px));
    top: 50%;
    color: var(--color-text-light);
    transform: translateY(-50%);
}

.omo-document-move__destination:hover,
.omo-document-move__destination:focus-visible,
.omo-document-move__destination.is-selected {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 8%, var(--color-surface));
}

.omo-document-move__destination-label {
    min-width: 0;
    overflow: hidden;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.omo-document-move__destination-meta {
    color: var(--color-text-light);
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
}

.omo-document-move__empty-destination {
    padding: 12px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
}

.omo-document-move__status[hidden] {
    display: none !important;
}

.omo-document-move__hint {
    margin-right: auto;
}

@media (max-width: 768px) {
    .omo-document-move__navigation {
        display: grid;
        padding: 0;
        border: 0;
    }
}

</style>
