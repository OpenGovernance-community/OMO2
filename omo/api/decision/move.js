window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/decision/move.js"] = function (pageConfig, pageScript) {
(() => {
const state = {
    data: pageConfig.data,
    text: pageConfig.text,
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
};
