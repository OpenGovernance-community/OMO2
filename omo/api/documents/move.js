window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/move.js"] = function (pageConfig, pageScript) {
(() => {
const state = {
    data: pageConfig.data,
    text: pageConfig.text,
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
};
