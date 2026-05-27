<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = (int)($_GET['hid'] ?? 0);
$organization = new Organization();
$moveData = null;
$errorMessage = '';

if ($organizationId <= 0) {
    $errorMessage = "Aucune organisation n'est actuellement selectionnee.";
} elseif ($holonId <= 0) {
    $errorMessage = 'Le holon a deplacer est invalide.';
} elseif (!$organization->load($organizationId)) {
    $errorMessage = "L'organisation demandee est introuvable.";
} else {
    $moveData = $organization->getHolonMoveEditorData($holonId);

    if (($moveData['holonId'] ?? 0) !== $holonId || !is_array($moveData['holon'] ?? null)) {
        $errorMessage = 'Le holon demande est introuvable.';
    } elseif (empty($moveData['canMove'])) {
        $errorMessage = "Vous n'avez pas les droits pour deplacer ce holon.";
    } else {
        $alternativeCount = 0;
        foreach (($moveData['destinations'] ?? array()) as $destination) {
            if (empty($destination['isCurrentParent'])) {
                $alternativeCount += 1;
            }
        }

        if ($alternativeCount <= 0) {
            $errorMessage = 'Aucune destination compatible n a ete trouvee pour ce holon.';
        }
    }
}
?>
<?php if ($errorMessage !== ''): ?>
    <div class="omo-holon-move__empty"><?= omoApiEscape($errorMessage) ?></div>
<?php else: ?>
    <form id="omo-holon-move-form" class="omo-holon-move">
        <div class="omo-holon-move__intro">
            <strong><?= omoApiEscape((string)($moveData['holon']['name'] ?? '')) ?></strong>
            <span>&rarr;</span>
        </div>

        <label class="omo-holon-move__field">
            <span>Ou ca va</span>
            <input type="search" id="omo-holon-move-search" class="generic-form-control" placeholder="Rechercher une destination">
        </label>

        <label class="omo-holon-move__field">
            <select id="omo-holon-move-destination" class="omo-holon-move__select generic-form-control" size="10" required></select>
        </label>

        <div id="omo-holon-move-hint" class="omo-holon-move__hint"></div>
        <div id="omo-holon-move-status" class="omo-holon-move__status" hidden></div>

        <div class="omo-holon-move__actions">
            <button type="button" class="omo-holon-move__button generic-action-button generic-action-button--secondary" id="omo-holon-move-cancel">Annuler</button>
            <button type="submit" class="omo-holon-move__button generic-action-button generic-action-button--main" id="omo-holon-move-submit">Deplacer</button>
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

const form = document.getElementById('omo-holon-move-form');
if (!form) {
    return;
}

const elements = {
    form: form,
    status: document.getElementById('omo-holon-move-status'),
    search: document.getElementById('omo-holon-move-search'),
    destination: document.getElementById('omo-holon-move-destination'),
    hint: document.getElementById('omo-holon-move-hint'),
    cancel: document.getElementById('omo-holon-move-cancel'),
    submit: document.getElementById('omo-holon-move-submit')
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
    elements.status.className = 'omo-holon-move__status';
    elements.status.innerHTML = '';
}

function showStatus(message, tone) {
    clearStatus();
    elements.status.hidden = false;
    elements.status.className = 'omo-holon-move__status is-' + tone;
    elements.status.innerHTML = '<div class="omo-holon-move__status-copy">' + escapeHtml(message) + '</div>';
    state.statusTimer = window.setTimeout(clearStatus, 12000);
}

function closeMoveDrawer() {
    if (typeof window.commonTopbarCloseModal === 'function') {
        window.commonTopbarCloseModal();
    }
}

function getDestinations() {
    return Array.isArray(state.data.destinations) ? state.data.destinations : [];
}

function getCurrentParentId() {
    return Number((state.data.currentParent && state.data.currentParent.id) || 0);
}

function getSelectedDestinationId() {
    return Number(elements.destination.value || 0);
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
    const selectedId = getSelectedDestinationId();
    const currentParentId = getCurrentParentId();
    const canSubmit = selectedId > 0 && selectedId !== currentParentId;

    if (elements.submit) {
        elements.submit.disabled = !canSubmit;
    }

    if (elements.hint) {
        if (selectedId <= 0) {
            elements.hint.textContent = 'Choisissez une destination valide.';
        } else if (selectedId === currentParentId) {
            elements.hint.textContent = 'Selectionnez un autre parent pour activer le deplacement.';
        } else {
            elements.hint.textContent = '';
        }
    }
}

function renderDestinations() {
    const destinations = getFilteredDestinations();
    const previousSelection = getSelectedDestinationId() || getCurrentParentId();

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
        const isCurrentParent = Boolean(destination.isCurrentParent);

        option.value = String(destination.id || 0);
        option.textContent = String(destination.pathLabel || destination.name || ('#' + String(destination.id || '')));
        option.dataset.currentParent = isCurrentParent ? '1' : '0';

        if (Number(destination.id || 0) === Number(previousSelection)) {
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

    const targetParentId = getSelectedDestinationId();
    const currentParentId = getCurrentParentId();

    if (targetParentId <= 0) {
        showStatus('Choisissez une destination valide.', 'error');
        return;
    }

    if (targetParentId === currentParentId) {
        showStatus('Selectionnez un autre parent avant de deplacer ce holon.', 'error');
        return;
    }

    if (elements.submit) {
        elements.submit.disabled = true;
    }

    fetch('/omo/api/holons/move_action.php?hid=' + Number(state.data.holonId || 0), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            targetParentId: targetParentId
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
                throw new Error(result.data && result.data.message ? result.data.message : "Impossible de deplacer le holon.");
            }

            const movedHolonId = Number(result.data.holon && result.data.holon.id || state.data.holonId || 0);
            closeMoveDrawer();

            const targetCid = movedHolonId > 0 ? movedHolonId : null;
            const route = typeof parseUrl === 'function'
                ? parseUrl()
                : {
                    oid: Number(state.data.organizationId || 0),
                    cid: null,
                    hash: null
                };

            if (typeof navigate === 'function' && String(route.cid || '') !== String(targetCid || '')) {
                navigate(route.oid, targetCid, route.hash || null);
            } else if (typeof loadContent === 'function') {
                let leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || state.data.organizationId || 0);

                if (targetCid) {
                    leftUrl += '&cid=' + targetCid;
                }

                loadContent('#panel-left', leftUrl);
            }

            if (typeof window.omoReloadStructureAndFocus === 'function') {
                window.omoReloadStructureAndFocus(targetCid, {
                    quickZoom: true
                });
            }
        })
        .catch(function (error) {
            if (elements.submit) {
                elements.submit.disabled = false;
            }

            showStatus(error && error.message ? error.message : "Impossible de deplacer le holon.", 'error');
        });
}

renderDestinations();

elements.search.addEventListener('input', renderDestinations);
elements.destination.addEventListener('change', updateSubmitState);
elements.form.addEventListener('submit', submitMove);
elements.cancel.addEventListener('click', closeMoveDrawer);
})();
</script>
<?php endif; ?>

<style>
.omo-holon-move,
.omo-holon-move__empty {
    display: grid;
    gap: 16px;
    padding: 8px 4px 4px;
    color: var(--color-text, #1f2937);
}

.omo-holon-move__intro,
.omo-holon-move__hint,
.omo-holon-move__empty {
    color: var(--topbar-panel-muted, #64748b);
    line-height: 1.45;
}

.omo-holon-move__intro {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.omo-holon-move__field {
    display: grid;
    gap: 8px;
}

.omo-holon-move__select {
    --generic-form-control-border: var(--topbar-panel-border, #dbe3ef);
    --generic-form-control-background: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-background-focus: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-color: inherit;
    min-height: 240px;
}

.omo-holon-move__field input {
    --generic-form-control-border: var(--topbar-panel-border, #dbe3ef);
    --generic-form-control-background: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-background-focus: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-color: inherit;
}

.omo-holon-move__status {
    min-height: 22px;
    color: #b91c1c;
    font-weight: 600;
}

.omo-holon-move__status[hidden] {
    display: none !important;
}

.omo-holon-move__status.is-error {
    color: #b91c1c;
}

.omo-holon-move__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

</style>
