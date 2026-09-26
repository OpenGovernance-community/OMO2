window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/holons/move.js"] = function (pageConfig, pageScript) {
(() => {
const state = {
    data: pageConfig.data,
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
    elements.status.className = 'omo-holon-move__status generic-feedback';
    elements.status.innerHTML = '';
}

function showStatus(message, tone) {
    clearStatus();
    elements.status.hidden = false;
    elements.status.className = 'omo-holon-move__status generic-feedback is-' + tone;
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
showStatus('Sélectionnez un autre parent avant de déplacer cet espace.', 'error');
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
throw new Error(result.data && result.data.message ? result.data.message : "Impossible de déplacer l’espace.");
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

                loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);
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

showStatus(error && error.message ? error.message : "Impossible de déplacer l’espace.", 'error');
        });
}

renderDestinations();

elements.search.addEventListener('input', renderDestinations);
elements.destination.addEventListener('change', updateSubmitState);
elements.form.addEventListener('submit', submitMove);
elements.cancel.addEventListener('click', closeMoveDrawer);
})();
};
