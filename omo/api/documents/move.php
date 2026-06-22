<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\Organization;

$documentId = (int)($_GET['id'] ?? 0);
$document = new Document();
$organization = new Organization();
$moveData = null;
$errorMessage = '';

if ($documentId <= 0) {
    $errorMessage = 'Le document a deplacer est invalide.';
} elseif (
    !$document->load($documentId)
    || (int)$document->get('IDorganization') <= 0
) {
    $errorMessage = 'Le document demande est introuvable.';
} else {
    $organizationId = (int)$document->get('IDorganization');

    if (!$organization->load($organizationId)) {
        $errorMessage = 'Organisation introuvable.';
    } else {
        $moveData = $organization->getDocumentMoveEditorData($documentId);

        if (($moveData['documentId'] ?? 0) !== $documentId || !is_array($moveData['document'] ?? null)) {
            $errorMessage = 'Le document demande est introuvable.';
        } elseif (empty($moveData['canMove'])) {
            $errorMessage = "Vous n'avez pas les droits pour deplacer ce document.";
        } else {
            $alternativeCount = 0;
            foreach (($moveData['destinations'] ?? array()) as $destination) {
                if (empty($destination['isCurrentDestination'])) {
                    $alternativeCount += 1;
                }
            }

            if ($alternativeCount <= 0) {
                $errorMessage = 'Aucune destination compatible n a ete trouvee pour ce document.';
            }
        }
    }
}
?>
<?php if ($errorMessage !== ''): ?>
    <div class="omo-document-move__empty"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form id="omo-document-move-form" class="omo-document-move">
        <div class="omo-document-move__intro">
            <strong><?= omoApiEscape((string)($moveData['document']['title'] ?? '')) ?></strong>
            <span>&rarr;</span>
        </div>

        <label class="omo-document-move__field">
            <span>Ou ca va</span>
            <input type="search" id="omo-document-move-search" class="generic-form-control" placeholder="Rechercher une destination">
        </label>

        <label class="omo-document-move__field">
            <select id="omo-document-move-destination" class="omo-document-move__select generic-form-control" size="10" required></select>
        </label>

        <div id="omo-document-move-hint" class="omo-document-move__hint"></div>
        <div id="omo-document-move-status" class="omo-document-move__status" hidden></div>

        <div class="omo-document-move__actions">
            <button type="button" class="omo-document-move__button generic-action-button generic-action-button--secondary" id="omo-document-move-cancel">Annuler</button>
            <button type="submit" class="omo-document-move__button generic-action-button generic-action-button--main" id="omo-document-move-submit">Deplacer</button>
        </div>
    </form>
<?php endif; ?>

<?php if ($moveData !== null && $errorMessage === ''): ?>
<script>
(() => {
const state = {
    data: <?= json_encode($moveData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
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
    elements.status.className = 'omo-document-move__status';
    elements.status.innerHTML = '';
}

function showStatus(message, tone) {
    clearStatus();
    elements.status.hidden = false;
    elements.status.className = 'omo-document-move__status is-' + tone;
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
            elements.hint.textContent = 'Choisissez une destination valide.';
        } else if (selectedKey === currentKey) {
            elements.hint.textContent = 'Selectionnez une autre destination pour activer le deplacement.';
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
        option.textContent = 'Aucune destination correspondante';
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
        showStatus('Choisissez une destination valide.', 'error');
        return;
    }

    if (String(targetDestination.key || '') === currentKey) {
        showStatus('Selectionnez une autre destination avant de deplacer ce document.', 'error');
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
                throw new Error(result.data && result.data.message ? result.data.message : "Impossible de deplacer le document.");
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

            showStatus(error && error.message ? error.message : "Impossible de deplacer le document.", 'error');
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
.omo-document-move,
.omo-document-move__empty {
    display: grid;
    gap: 16px;
    padding: 8px 4px 4px;
    color: var(--color-text, #1f2937);
}

.omo-document-move__intro,
.omo-document-move__hint,
.omo-document-move__empty {
    color: var(--topbar-panel-muted, #64748b);
    line-height: 1.45;
}

.omo-document-move__intro {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.omo-document-move__field {
    display: grid;
    gap: 8px;
}

.omo-document-move__select {
    min-height: 240px;
}

.omo-document-move__status {
    min-height: 22px;
    color: #b91c1c;
    font-weight: 600;
}

.omo-document-move__status[hidden] {
    display: none !important;
}

.omo-document-move__status.is-error {
    color: #b91c1c;
}

.omo-document-move__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>
