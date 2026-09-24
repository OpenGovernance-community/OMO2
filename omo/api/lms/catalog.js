
const lmsIndexViewer = {
    userId: window.omoLmsCatalogConfig.userId,
    organizationId: window.omoLmsCatalogConfig.organizationId,
    isEmbedded: window.omoLmsCatalogConfig.isEmbedded,
    canCreateParcours: window.omoLmsCatalogConfig.canCreateParcours,
    canEditParcours: window.omoLmsCatalogConfig.canEditParcours
};
const lmsIndexText = window.omoLmsCatalogConfig.lmsIndexText;

function formatLmsIndexText(template, replace) {
    let output = String(template || '');
    Object.keys(replace || {}).forEach(function (key) {
        output = output.replace(new RegExp('\\{' + key + '\\}', 'g'), String(replace[key]));
    });
    return output;
}

const lmsParcoursBasePath = window.omoLmsCatalogConfig.lmsParcoursBasePath;
const lmsParcoursCreatePath = window.omoLmsCatalogConfig.lmsParcoursCreatePath;
const lmsParcoursImportPath = window.omoLmsCatalogConfig.lmsParcoursImportPath;
const lmsParcoursImportSavePath = window.omoLmsCatalogConfig.lmsParcoursImportSavePath;
const lmsParcoursEditBasePath = window.omoLmsCatalogConfig.lmsParcoursCreatePath;
const lmsParcoursDeletePreviewPath = window.omoLmsCatalogConfig.lmsParcoursDeletePreviewPath;
const lmsParcoursDeletePath = window.omoLmsCatalogConfig.lmsParcoursDeletePath;
const lmsMissionEditBasePath = window.omoLmsCatalogConfig.lmsMissionEditBasePath;
const lmsParcoursMissionPanelBasePath = window.omoLmsCatalogConfig.lmsParcoursMissionPanelBasePath;
const lmsParcoursMissionAddPath = window.omoLmsCatalogConfig.lmsParcoursMissionAddPath;
const lmsParcoursMissionRemovePath = window.omoLmsCatalogConfig.lmsParcoursMissionRemovePath;
const lmsParcoursMissionCreatePath = window.omoLmsCatalogConfig.lmsParcoursMissionCreatePath;
const lmsParcoursMissionReorderPath = window.omoLmsCatalogConfig.lmsParcoursMissionReorderPath;
const lmsParcoursPackAddPath = window.omoLmsCatalogConfig.lmsParcoursPackAddPath;
const lmsParcoursPackRemovePath = window.omoLmsCatalogConfig.lmsParcoursPackRemovePath;
const lmsParcoursPackReorderPath = window.omoLmsCatalogConfig.lmsParcoursPackReorderPath;
const lmsParcoursPrerequisiteAddPath = window.omoLmsCatalogConfig.lmsParcoursPrerequisiteAddPath;
const lmsParcoursPrerequisiteRemovePath = window.omoLmsCatalogConfig.lmsParcoursPrerequisiteRemovePath;
const lmsMissionDependencyAddPath = window.omoLmsCatalogConfig.lmsMissionDependencyAddPath;
const lmsMissionDependencyRemovePath = window.omoLmsCatalogConfig.lmsMissionDependencyRemovePath;
const lmsMissionHomeworkReorderPath = window.omoLmsCatalogConfig.lmsMissionHomeworkReorderPath;
const lmsMissionQuestionReorderPath = window.omoLmsCatalogConfig.lmsMissionQuestionReorderPath;
const lmsParcoursEditorScrollMemory = {};

function getAnonymousProgressKey(parcoursId) {
    return `lms_progress_${lmsIndexViewer.organizationId}_${parcoursId}`;
}

function getAnonymousDoneMissionIds(parcoursId) {
    try {
        const rawValue = localStorage.getItem(getAnonymousProgressKey(parcoursId));
        if (!rawValue) {
            return [];
        }

        const parsed = JSON.parse(rawValue);
        const missions = parsed && parsed.missions && typeof parsed.missions === 'object'
            ? Object.keys(parsed.missions)
            : [];

        return missions
            .map(value => Number(value))
            .filter(value => Number.isInteger(value) && value > 0);
    } catch (error) {
        return [];
    }
}

function getAnonymousCompletedParcoursIds() {
    const completedIds = [];

    document.querySelectorAll('[data-parcours-card="1"]').forEach((card) => {
        if (card.getAttribute('data-is-pack') === '1') {
            return;
        }

        const parcoursId = Number(card.getAttribute('data-parcours-id') || 0);
        const total = Number(card.getAttribute('data-total-missions') || 0);
        if (parcoursId <= 0 || total <= 0) {
            return;
        }

        const done = getAnonymousDoneMissionIds(parcoursId).length;
        if (done >= total) {
            completedIds.push(parcoursId);
        }
    });

    return Array.from(new Set(completedIds));
}

function resolveCardPercent(card, fallbackPercent) {
    if (Number(lmsIndexViewer.userId || 0) > 0) {
        return fallbackPercent;
    }

    if (card.getAttribute('data-local-progress') !== '1') {
        return fallbackPercent;
    }

    const total = Number(card.getAttribute('data-total-missions') || 0);
    if (total <= 0) {
        return 0;
    }

    const parcoursId = Number(card.getAttribute('data-parcours-id') || 0);
    const done = getAnonymousDoneMissionIds(parcoursId).length;
    return Math.max(0, Math.min(100, Math.round((done / total) * 100)));
}

document.querySelectorAll('.progress-circle').forEach(el => {
    const card = el.closest('.card');
    const percent = resolveCardPercent(card, Number(el.getAttribute('data-percent') || 0));
    const radius = 25;
    const circumference = 2 * Math.PI * radius;

    el.innerHTML = `
        <svg width="60" height="60">
            <circle class="bg" cx="30" cy="30" r="${radius}"></circle>
            <circle class="progress" cx="30" cy="30" r="${radius}"></circle>
        </svg>
        <div class="label">${percent}%</div>
    `;

    const progressCircle = el.querySelector('.progress');
    progressCircle.style.strokeDasharray = circumference;
    progressCircle.style.strokeDashoffset = circumference * (1 - percent / 100);
});

function updateParcoursSectionsByProgress() {
    const pendingGrid = document.getElementById('lms-parcours-pending-grid');
    const completedGrid = document.getElementById('lms-parcours-completed-grid');
    const completedSection = document.getElementById('lms-parcours-section-completed');
    if (!pendingGrid || !completedGrid || !completedSection) {
        return;
    }

    const parcoursCards = Array.from(document.querySelectorAll('[data-parcours-card="1"]'));
    parcoursCards.forEach((card) => {
        if (card.getAttribute('data-is-pack') === '1') {
            return;
        }

        const progressElement = card.querySelector('.progress-circle');
        const percent = resolveCardPercent(card, progressElement ? Number(progressElement.getAttribute('data-percent') || 0) : 0);
        const targetGrid = percent >= 100 ? completedGrid : pendingGrid;

        if (card.parentElement !== targetGrid) {
            targetGrid.appendChild(card);
        }
    });

    completedSection.hidden = completedGrid.querySelector('[data-parcours-card="1"]') === null;
}

updateParcoursSectionsByProgress();

function goToParcours(id) {
    const targetUrl = new URL(lmsParcoursBasePath, window.location.origin);
    targetUrl.searchParams.set('idp', String(id));
    if (lmsIndexViewer.isEmbedded) {
        targetUrl.searchParams.set('embed', '1');
    }
    if (Number(lmsIndexViewer.userId || 0) <= 0) {
        const completedParcoursIds = getAnonymousCompletedParcoursIds();
        if (completedParcoursIds.length > 0) {
            targetUrl.searchParams.set('done_parcours_ids', completedParcoursIds.join(','));
        }
    }
    window.location.href = targetUrl.pathname + targetUrl.search + targetUrl.hash;
}

function closeAllParcoursCardMenus() {
    document.querySelectorAll('.card-menu.is-open').forEach((menu) => {
        menu.classList.remove('is-open');
    });
}

function closeAllMissionItemMenus() {
    document.querySelectorAll('.lms-parcours-mission-item__menu.is-open').forEach((menu) => {
        menu.classList.remove('is-open');
    });
    document.querySelectorAll('.lms-parcours-mission-item.is-menu-open').forEach((item) => {
        item.classList.remove('is-menu-open');
    });
}

function toggleParcoursCardMenu(event, parcoursId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(`parcours-card-menu-${parcoursId}`);
    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('is-open');
    closeAllParcoursCardMenus();
    menu.classList.toggle('is-open', willOpen);
}

function toggleMissionItemMenu(event, missionId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(`lms-mission-item-menu-${missionId}`);
    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('is-open');
    closeAllMissionItemMenus();
    menu.classList.toggle('is-open', willOpen);
    const item = menu.closest('.lms-parcours-mission-item');
    if (item) {
        item.classList.toggle('is-menu-open', willOpen);
    }
}

function buildMissionEditUrl(parcoursId, missionId) {
    const targetUrl = new URL(lmsMissionEditBasePath, window.location.origin);
    targetUrl.searchParams.set('pid', String(parcoursId));
    targetUrl.searchParams.set('mid', String(missionId));
    return targetUrl.pathname + targetUrl.search + targetUrl.hash;
}

function lmsRememberParcoursEditorScroll(parcoursId) {
    const drawerContent = document.getElementById('drawer-content');
    if (!drawerContent || parcoursId <= 0) {
        return;
    }

    lmsParcoursEditorScrollMemory[String(parcoursId)] = drawerContent.scrollTop;
}

function lmsGetRememberedParcoursEditorScroll(parcoursId) {
    if (parcoursId <= 0) {
        return 0;
    }

    const rememberedScroll = lmsParcoursEditorScrollMemory[String(parcoursId)];
    return typeof rememberedScroll === 'number' && Number.isFinite(rememberedScroll)
        ? Math.max(0, rememberedScroll)
        : 0;
}

function initLmsDrawerContent() {
    initMissionEditorDrawer();
    initParcoursEditorDrawer();
    initParcoursImportDrawer();
    initParcoursPrerequisiteManager();
    initParcoursMissionManager();
    initParcoursPackManager();
}

async function refreshMissionEditor(parcoursId, missionId) {
    if (parcoursId <= 0 || missionId <= 0) {
        return;
    }

    const drawerContent = document.getElementById('drawer-content');
    const currentScrollTop = drawerContent ? drawerContent.scrollTop : 0;
    await openDrawerFromUrl(buildMissionEditUrl(parcoursId, missionId), {
        simpleMode: true,
        scrollTop: currentScrollTop
    });
    initLmsDrawerContent();
}

function lmsInitAdminEditHtmlFields(scopeElement) {
    if (typeof window.adminEditInitHtmlFields === 'function') {
        try {
            return window.adminEditInitHtmlFields(scopeElement || document);
        } catch (error) {
            return Promise.resolve();
        }
    }

    return Promise.resolve();
}

function lmsSyncAdminEditHtmlFields(scopeElement) {
    if (typeof window.adminEditSyncHtmlFields === 'function') {
        try {
            window.adminEditSyncHtmlFields(scopeElement || document);
        } catch (error) {
        }
    }
}

function lmsSetHtmlFieldValue(field, value) {
    if (!field) {
        return;
    }

    const nextValue = String(value || '');
    if (window.jQuery) {
        const $field = window.jQuery(field);
        if ($field.data('adminEditSummernoteBound') === true && typeof $field.summernote === 'function') {
            try {
                $field.summernote('code', nextValue);
                $field.val(nextValue);
                return;
            } catch (error) {
            }
        }
    }

    field.value = nextValue;
}

function initParcoursEditorDrawer() {
    const drawerContent = document.getElementById('drawer-content');
    const form = drawerContent ? drawerContent.querySelector('#formulaire-edit') : null;
    const submitButton = drawerContent ? drawerContent.querySelector('#lms-create-parcours-submit') : null;

    if (!form || !submitButton || form.dataset.lmsParcoursEditorBound === '1') {
        return;
    }

    let isSubmitting = false;
    let isDirty = false;
    let initialState = '';
    form.dataset.lmsParcoursEditorBound = '1';

    const setSubmitState = () => {
        submitButton.disabled = isSubmitting || !isDirty;
    };

    const captureParcoursEditorState = () => {
        lmsSyncAdminEditHtmlFields(form);

        const serializedEntries = [];
        const formData = new FormData(form);
        formData.forEach((value, key) => {
            if (value instanceof File) {
                if (value && value.name) {
                    serializedEntries.push([key, `file:${value.name}:${value.size}:${value.type}`]);
                }
                return;
            }

            serializedEntries.push([key, String(value)]);
        });

        if (window.croppedImages && typeof window.croppedImages === 'object') {
            Object.keys(window.croppedImages).sort().forEach((key) => {
                const blob = window.croppedImages[key];
                if (!blob) {
                    return;
                }

                serializedEntries.push([`__cropped__${key}`, `${blob.type}:${blob.size}`]);
            });
        }

        serializedEntries.sort((entryA, entryB) => {
            const left = `${entryA[0]}::${entryA[1]}`;
            const right = `${entryB[0]}::${entryB[1]}`;
            return left.localeCompare(right);
        });

        return JSON.stringify(serializedEntries);
    };

    const refreshDirtyState = () => {
        isDirty = captureParcoursEditorState() !== initialState;
        setSubmitState();
    };

    submitButton.disabled = true;
    Promise.resolve(lmsInitAdminEditHtmlFields(form)).then(() => {
        initialState = captureParcoursEditorState();
        isDirty = false;
        setSubmitState();

        if (window.jQuery) {
            window.jQuery(form).find('textarea.summernote').each(function () {
                window.jQuery(this)
                    .off('.lmsParcoursDirty')
                    .on('summernote.change.lmsParcoursDirty summernote.keyup.lmsParcoursDirty', refreshDirtyState);
            });
        }
    }).catch(() => {
        initialState = captureParcoursEditorState();
        isDirty = false;
        setSubmitState();
    });

    form.addEventListener('input', refreshDirtyState, true);
    form.addEventListener('change', refreshDirtyState, true);

    const submitParcoursEditorForm = async (event) => {
        if (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        if (isSubmitting || !isDirty) {
            return;
        }

        const titleField = form.querySelector('[name="title"]');
        if (titleField && String(titleField.value || '').trim() === '') {
            window.alert(lmsIndexText.requiredTitle);
            titleField.focus();
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;

        try {
            lmsSyncAdminEditHtmlFields(form);
            const formData = new FormData(form);

            if (window.croppedImages && typeof window.croppedImages === 'object') {
                Object.keys(window.croppedImages).forEach((key) => {
                    const blob = window.croppedImages[key];
                    if (!blob) {
                        return;
                    }

                    let extension = 'jpg';
                    if (blob.type === 'image/png') {
                        extension = 'png';
                    } else if (blob.type === 'image/webp') {
                        extension = 'webp';
                    }

                    formData.append(key, blob, `${key}.${extension}`);
                });
            }

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const responseText = await response.text();
            let payload = null;

            try {
                payload = JSON.parse(responseText);
            } catch (error) {
                payload = null;
            }

            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible d enregistrer ce parcours.');
            }

            if (!payload || payload.success !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible d enregistrer ce parcours.');
            }

            initialState = captureParcoursEditorState();
            isDirty = false;
            setSubmitState();
            closeDrawer();
            window.location.reload();
        } catch (error) {
            window.alert(error && error.message ? error.message : 'Impossible d enregistrer ce parcours.');
        } finally {
            isSubmitting = false;
            setSubmitState();
        }
    };

    submitButton.addEventListener('click', submitParcoursEditorForm);
    form.addEventListener('submit', submitParcoursEditorForm, true);

    initParcoursPrerequisiteManager();
    initParcoursMissionManager();
}

function initParcoursImportDrawer() {
    const drawerContent = document.getElementById('drawer-content');
    const importer = drawerContent ? drawerContent.querySelector('[data-lms-parcours-importer="1"]') : null;
    if (!importer || importer.dataset.lmsParcoursImportBound === '1') {
        return;
    }

    importer.dataset.lmsParcoursImportBound = '1';
    const searchField = importer.querySelector('[data-lms-import-parcours-search="1"]');
    const items = Array.from(importer.querySelectorAll('[data-lms-import-parcours-item="1"]'));
    const emptySearch = importer.querySelector('[data-lms-import-parcours-empty-search="1"]');

    const applySearch = () => {
        if (!searchField) {
            return;
        }

        const rawNeedle = String(searchField.value || '').trim();
        const needle = rawNeedle.toLocaleLowerCase();
        let visibleCount = 0;

        items.forEach((item) => {
            const haystack = String(item.getAttribute('data-search-text') || '').toLocaleLowerCase();
            const isVisible = needle === '' || haystack.indexOf(needle) !== -1;
            item.hidden = !isVisible;
            if (isVisible) {
                visibleCount++;
            }
        });

        if (emptySearch) {
            emptySearch.hidden = visibleCount > 0 || needle === '';
        }
    };

    if (searchField) {
        searchField.addEventListener('input', applySearch);
        applySearch();
    }

    importer.querySelectorAll('[data-lms-import-parcours-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const parcoursId = Number(button.getAttribute('data-lms-import-parcours-id') || 0);
            if (parcoursId <= 0) {
                return;
            }

            button.disabled = true;

            try {
                const formData = new FormData();
                formData.set('parcours_id', String(parcoursId));

                const response = await fetch(lmsParcoursImportSavePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const responseText = await response.text();
                let payload = null;

                try {
                    payload = JSON.parse(responseText);
                } catch (error) {
                    payload = null;
                }

                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible d importer ce parcours.');
                }

                closeDrawer();
                window.location.reload();
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible d importer ce parcours.');
                button.disabled = false;
            }
        });
    });
}

function initMissionEditorDrawer() {
    const drawerContent = document.getElementById('drawer-content');
    const editor = drawerContent ? drawerContent.querySelector('[data-lms-mission-editor]') : null;
    const form = drawerContent ? drawerContent.querySelector('#formulaire-edit') : null;
    const submitButton = drawerContent ? drawerContent.querySelector('#lms-save-mission-submit') : null;

    if (!editor || !form || !submitButton || form.dataset.lmsMissionEditorBound === '1') {
        return;
    }

    const parcoursId = Number(editor.getAttribute('data-parcours-id') || 0);
    const missionId = Number(editor.getAttribute('data-mission-id') || 0);
    let isSubmitting = false;
    form.dataset.lmsMissionEditorBound = '1';
    lmsInitAdminEditHtmlFields(form);

    const submitMissionEditorForm = async function (event) {
        if (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        if (isSubmitting || parcoursId <= 0 || missionId <= 0) {
            return;
        }

        const titleField = form.querySelector('[name="title"]');
        const resumeField = form.querySelector('[name="resume"]');

        if (titleField && String(titleField.value || '').trim() === '') {
            window.alert(lmsIndexText.requiredTitle);
            titleField.focus();
            return;
        }

        if (resumeField && String(resumeField.value || '').trim() === '') {
            window.alert(lmsIndexText.requiredResume);
            resumeField.focus();
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;

        try {
            lmsSyncAdminEditHtmlFields(form);
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const payload = await response.json();
            if (!response.ok || !payload || payload.success !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible d enregistrer cette mission.');
            }

            openDrawerFromUrl(buildMissionEditUrl(parcoursId, missionId), { simpleMode: true })
                .then(() => {
                    initLmsDrawerContent();
                })
                .catch(() => {
                    window.alert(lmsIndexText.saveMissionRefreshError);
                });
        } catch (error) {
            window.alert(error && error.message ? error.message : lmsIndexText.saveMissionError);
        } finally {
            isSubmitting = false;
            submitButton.disabled = false;
        }
    };

    submitButton.addEventListener('click', submitMissionEditorForm);
    form.addEventListener('submit', submitMissionEditorForm, true);

    editor.querySelectorAll('[data-lms-back-to-parcours-editor]').forEach((button) => {
        button.addEventListener('click', function () {
            openParcoursEditorDrawer(parcoursId, { restoreScroll: true });
        });
    });

    initMissionDependencyManager();
    initMissionRelatedManagers();
}

function buildParcoursMissionPanelUrl(parcoursId) {
    const targetUrl = new URL(lmsParcoursMissionPanelBasePath, window.location.origin);
    targetUrl.searchParams.set('pid', String(parcoursId));
    return targetUrl.pathname + targetUrl.search + targetUrl.hash;
}

async function refreshParcoursMissionManager(parcoursId) {
    const drawerContent = document.getElementById('drawer-content');
    const currentManager = drawerContent ? drawerContent.querySelector('[data-lms-parcours-content-manager]') : null;

    if (!drawerContent || !currentManager || parcoursId <= 0) {
        return;
    }

    const response = await fetch(buildParcoursMissionPanelUrl(parcoursId), { credentials: 'same-origin' });
    if (!response.ok) {
        throw new Error('Impossible de recharger les missions du parcours.');
    }

    const html = await response.text();
    currentManager.outerHTML = html;
    initParcoursPrerequisiteManager();
    initParcoursMissionManager();
    initParcoursPackManager();
}

function initParcoursMissionManager() {
    const drawerContent = document.getElementById('drawer-content');
    const manager = drawerContent ? drawerContent.querySelector('[data-lms-parcours-mission-manager]') : null;

    if (!manager || manager.dataset.lmsMissionManagerBound === '1') {
        return;
    }

    manager.dataset.lmsMissionManagerBound = '1';
    const parcoursId = Number(manager.getAttribute('data-parcours-id') || 0);
    const list = manager.querySelector('[data-lms-parcours-mission-list]');
    const picker = manager.querySelector('[data-lms-mission-picker]');
    const searchInput = manager.querySelector('[data-lms-mission-picker-search]');
    const searchEmptyState = manager.querySelector('[data-lms-mission-picker-empty-search]');
    const pickerLibrary = manager.querySelector('[data-lms-mission-picker-library]');
    const creatorView = manager.querySelector('[data-lms-mission-creator-view]');
    const creatorForm = manager.querySelector('[data-lms-mission-create-form]');
    const creatorSubmit = manager.querySelector('[data-lms-mission-create-submit]');

    function normalizeMissionPickerSearch(value) {
        let normalized = String(value || '').trim().toLowerCase();
        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return normalized;
    }

    function showPickerLibrary() {
        if (pickerLibrary) {
            pickerLibrary.hidden = false;
        }
        if (creatorView) {
            creatorView.hidden = true;
        }
    }

    function showMissionCreator() {
        if (pickerLibrary) {
            pickerLibrary.hidden = true;
        }
        if (creatorView) {
            creatorView.hidden = false;
        }
    }

    function closePicker() {
        if (picker) {
            picker.hidden = true;
        }
        showPickerLibrary();
    }

    function openPicker() {
        if (picker) {
            picker.hidden = false;
        }
        showPickerLibrary();
    }

    manager.querySelectorAll('[data-lms-open-mission-picker]').forEach((button) => {
        button.addEventListener('click', openPicker);
    });

    manager.querySelectorAll('[data-lms-toggle-mission-menu]').forEach((button) => {
        button.addEventListener('click', function (event) {
            const missionId = Number(button.getAttribute('data-mission-id') || 0);
            if (missionId <= 0) {
                return;
            }
            toggleMissionItemMenu(event, missionId);
        });
    });

    manager.querySelectorAll('[data-lms-edit-mission]').forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const missionId = Number(button.getAttribute('data-mission-id') || 0);
            if (parcoursId <= 0 || missionId <= 0) {
                return;
            }

            closeAllMissionItemMenus();
            openMissionEditorDrawer(null, parcoursId, missionId);
        });
    });

    manager.querySelectorAll('[data-lms-remove-mission]').forEach((button) => {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const missionId = Number(button.getAttribute('data-mission-id') || 0);
            if (parcoursId <= 0 || missionId <= 0) {
                return;
            }

            if (!window.confirm('Retirer cette mission du parcours ?')) {
                return;
            }

            closeAllMissionItemMenus();
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('mission_id', String(missionId));

                const response = await fetch(lmsParcoursMissionRemovePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible de retirer cette mission du parcours.');
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible de retirer cette mission du parcours.');
            } finally {
                button.disabled = false;
            }
        });
    });

    manager.querySelectorAll('[data-lms-close-mission-picker]').forEach((button) => {
        button.addEventListener('click', closePicker);
    });

    manager.querySelectorAll('[data-lms-open-mission-creator]').forEach((button) => {
        button.addEventListener('click', showMissionCreator);
    });

    manager.querySelectorAll('[data-lms-back-to-mission-picker]').forEach((button) => {
        button.addEventListener('click', showPickerLibrary);
    });

    if (searchInput) {
        const applyMissionPickerSearch = function () {
            const normalizedQuery = normalizeMissionPickerSearch(searchInput.value || '');
            let visibleCount = 0;

            manager.querySelectorAll('[data-lms-mission-picker-item]').forEach((item) => {
                const haystack = normalizeMissionPickerSearch(item.getAttribute('data-search-text') || '');
                const isVisible = normalizedQuery === '' || haystack.indexOf(normalizedQuery) !== -1;
                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (searchEmptyState) {
                searchEmptyState.hidden = !(normalizedQuery !== '' && visibleCount === 0);
            }
        };

        searchInput.addEventListener('input', applyMissionPickerSearch);
        searchInput.addEventListener('search', applyMissionPickerSearch);
        applyMissionPickerSearch();
    }

    manager.querySelectorAll('[data-lms-add-mission-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const missionId = Number(button.getAttribute('data-lms-add-mission-id') || 0);
            if (parcoursId <= 0 || missionId <= 0) {
                return;
            }

            button.disabled = true;
            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('mission_id', String(missionId));

                const response = await fetch(lmsParcoursMissionAddPath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible d ajouter cette mission.');
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible d ajouter cette mission.');
            } finally {
                button.disabled = false;
            }
        });
    });

    if (creatorForm && creatorSubmit) {
        let isCreatingMission = false;

        creatorForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (isCreatingMission || parcoursId <= 0) {
                return;
            }

            const titleField = creatorForm.querySelector('[name="title"]');
            const resumeField = creatorForm.querySelector('[name="resume"]');

            if (titleField && String(titleField.value || '').trim() === '') {
                window.alert(lmsIndexText.requiredTitle);
                titleField.focus();
                return;
            }

            if (resumeField && String(resumeField.value || '').trim() === '') {
                window.alert(lmsIndexText.requiredResume);
                resumeField.focus();
                return;
            }

            isCreatingMission = true;
            creatorSubmit.disabled = true;

            try {
                const formData = new FormData(creatorForm);
                formData.set('pid', String(parcoursId));

                const response = await fetch(lmsParcoursMissionCreatePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : lmsIndexText.createMissionError);
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : lmsIndexText.createMissionError);
            } finally {
                isCreatingMission = false;
                creatorSubmit.disabled = false;
            }
        });
    }

    if (list && typeof window.commonCreateVerticalSortableList === 'function') {
        window.commonCreateVerticalSortableList({
            list: list,
            itemSelector: '[data-mission-id]',
            handleSelector: '[data-lms-mission-drag-handle]',
            draggingClass: 'is-dragging',
            dropTargetClass: 'is-drop-target',
            onDrop: async function () {
                const missionIds = Array.from(list.querySelectorAll('[data-mission-id]'))
                    .map((item) => Number(item.getAttribute('data-mission-id') || 0))
                    .filter((id) => Number.isInteger(id) && id > 0);

                try {
                    const formData = new FormData();
                    formData.append('pid', String(parcoursId));
                    missionIds.forEach((missionId) => {
                        formData.append('mission_ids[]', String(missionId));
                    });

                    const response = await fetch(lmsParcoursMissionReorderPath, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload || payload.success !== true) {
                        throw new Error(payload && payload.message ? payload.message : 'Impossible de reordonner les missions.');
                    }

                    await refreshParcoursMissionManager(parcoursId);
                } catch (error) {
                    window.alert(error && error.message ? error.message : 'Impossible de reordonner les missions.');
                    await refreshParcoursMissionManager(parcoursId);
                }
            }
        });
    }
}

function togglePackItemMenu(event, childParcoursId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(`lms-pack-item-menu-${childParcoursId}`);
    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('is-open');
    closeAllMissionItemMenus();
    menu.classList.toggle('is-open', willOpen);
    const item = menu.closest('.lms-parcours-mission-item');
    if (item) {
        item.classList.toggle('is-menu-open', willOpen);
    }
}

function togglePrerequisiteItemMenu(event, requiredParcoursId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(`lms-prerequisite-item-menu-${requiredParcoursId}`);
    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('is-open');
    closeAllMissionItemMenus();
    menu.classList.toggle('is-open', willOpen);
    const item = menu.closest('.lms-parcours-mission-item');
    if (item) {
        item.classList.toggle('is-menu-open', willOpen);
    }
}

function toggleMissionDependencyItemMenu(event, requiredMissionId) {
    event.preventDefault();
    event.stopPropagation();

    const menu = document.getElementById(`lms-mission-dependency-item-menu-${requiredMissionId}`);
    if (!menu) {
        return;
    }

    const willOpen = !menu.classList.contains('is-open');
    closeAllMissionItemMenus();
    menu.classList.toggle('is-open', willOpen);
    const item = menu.closest('.lms-parcours-mission-item');
    if (item) {
        item.classList.toggle('is-menu-open', willOpen);
    }
}

function initMissionDependencyManager() {
    const drawerContent = document.getElementById('drawer-content');
    const manager = drawerContent ? drawerContent.querySelector('[data-lms-mission-dependency-manager]') : null;

    if (!manager || manager.dataset.lmsMissionDependencyManagerBound === '1') {
        return;
    }

    manager.dataset.lmsMissionDependencyManagerBound = '1';
    const parcoursId = Number(manager.getAttribute('data-parcours-id') || 0);
    const missionId = Number(manager.getAttribute('data-mission-id') || 0);
    const picker = manager.querySelector('[data-lms-mission-dependency-picker]');
    const searchInput = manager.querySelector('[data-lms-mission-dependency-picker-search]');
    const searchEmptyState = manager.querySelector('[data-lms-mission-dependency-picker-empty-search]');

    function normalizeMissionDependencyPickerSearch(value) {
        let normalized = String(value || '').trim().toLowerCase();
        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return normalized;
    }

    function closePicker() {
        if (picker) {
            picker.hidden = true;
        }
    }

    function openPicker() {
        if (picker) {
            picker.hidden = false;
        }
    }

    manager.querySelectorAll('[data-lms-open-mission-dependency-picker]').forEach((button) => {
        button.addEventListener('click', openPicker);
    });

    manager.querySelectorAll('[data-lms-close-mission-dependency-picker]').forEach((button) => {
        button.addEventListener('click', closePicker);
    });

    manager.querySelectorAll('[data-lms-toggle-mission-dependency-menu]').forEach((button) => {
        button.addEventListener('click', function (event) {
            const requiredMissionId = Number(button.getAttribute('data-required-mission-id') || 0);
            if (requiredMissionId <= 0) {
                return;
            }
            toggleMissionDependencyItemMenu(event, requiredMissionId);
        });
    });

    manager.querySelectorAll('[data-lms-edit-required-mission]').forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const requiredMissionId = Number(button.getAttribute('data-required-mission-id') || 0);
            if (parcoursId <= 0 || requiredMissionId <= 0) {
                return;
            }

            closeAllMissionItemMenus();
            openMissionEditorDrawer(null, parcoursId, requiredMissionId);
        });
    });

    manager.querySelectorAll('[data-lms-remove-mission-dependency]').forEach((button) => {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const requiredMissionId = Number(button.getAttribute('data-required-mission-id') || 0);
            if (parcoursId <= 0 || missionId <= 0 || requiredMissionId <= 0) {
                return;
            }

            if (!window.confirm(lmsIndexText.removePrerequisiteConfirm)) {
                return;
            }

            closeAllMissionItemMenus();
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('mission_id', String(missionId));
                formData.append('required_mission_id', String(requiredMissionId));

                const response = await fetch(lmsMissionDependencyRemovePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : lmsIndexText.removePrerequisiteError);
                }

                await refreshMissionEditor(parcoursId, missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : lmsIndexText.removePrerequisiteError);
            } finally {
                button.disabled = false;
            }
        });
    });

    if (searchInput) {
        const applySearch = function () {
            const normalizedQuery = normalizeMissionDependencyPickerSearch(searchInput.value || '');
            let visibleCount = 0;

            manager.querySelectorAll('[data-lms-mission-dependency-picker-item]').forEach((item) => {
                const haystack = normalizeMissionDependencyPickerSearch(item.getAttribute('data-search-text') || '');
                const isVisible = normalizedQuery === '' || haystack.indexOf(normalizedQuery) !== -1;
                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (searchEmptyState) {
                searchEmptyState.hidden = !(normalizedQuery !== '' && visibleCount === 0);
            }
        };

        searchInput.addEventListener('input', applySearch);
        searchInput.addEventListener('search', applySearch);
        applySearch();
    }

    manager.querySelectorAll('[data-lms-add-mission-dependency-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const requiredMissionId = Number(button.getAttribute('data-lms-add-mission-dependency-id') || 0);
            if (parcoursId <= 0 || missionId <= 0 || requiredMissionId <= 0) {
                return;
            }

            button.disabled = true;
            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('mission_id', String(missionId));
                formData.append('required_mission_id', String(requiredMissionId));

                const response = await fetch(lmsMissionDependencyAddPath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : lmsIndexText.addPrerequisiteError);
                }

                await refreshMissionEditor(parcoursId, missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : lmsIndexText.addPrerequisiteError);
            } finally {
                button.disabled = false;
            }
        });
    });
}

function initParcoursPrerequisiteManager() {
    const drawerContent = document.getElementById('drawer-content');
    const manager = drawerContent ? drawerContent.querySelector('[data-lms-parcours-prerequisite-manager]') : null;

    if (!manager || manager.dataset.lmsPrerequisiteManagerBound === '1') {
        return;
    }

    manager.dataset.lmsPrerequisiteManagerBound = '1';
    const parcoursId = Number(manager.getAttribute('data-parcours-id') || 0);
    const picker = manager.querySelector('[data-lms-prerequisite-picker]');
    const searchInput = manager.querySelector('[data-lms-prerequisite-picker-search]');
    const searchEmptyState = manager.querySelector('[data-lms-prerequisite-picker-empty-search]');

    function normalizePrerequisitePickerSearch(value) {
        let normalized = String(value || '').trim().toLowerCase();
        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return normalized;
    }

    function closePicker() {
        if (picker) {
            picker.hidden = true;
        }
    }

    function openPicker() {
        if (picker) {
            picker.hidden = false;
        }
    }

    manager.querySelectorAll('[data-lms-open-prerequisite-picker]').forEach((button) => {
        button.addEventListener('click', openPicker);
    });

    manager.querySelectorAll('[data-lms-close-prerequisite-picker]').forEach((button) => {
        button.addEventListener('click', closePicker);
    });

    manager.querySelectorAll('[data-lms-toggle-prerequisite-menu]').forEach((button) => {
        button.addEventListener('click', function (event) {
            const requiredParcoursId = Number(button.getAttribute('data-required-parcours-id') || 0);
            if (requiredParcoursId <= 0) {
                return;
            }
            togglePrerequisiteItemMenu(event, requiredParcoursId);
        });
    });

    manager.querySelectorAll('[data-lms-edit-prerequisite-parcours]').forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const requiredParcoursId = Number(button.getAttribute('data-required-parcours-id') || 0);
            if (requiredParcoursId <= 0) {
                return;
            }

            closeAllMissionItemMenus();
            openParcoursEditorDrawer(requiredParcoursId);
        });
    });

    manager.querySelectorAll('[data-lms-remove-prerequisite]').forEach((button) => {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const requiredParcoursId = Number(button.getAttribute('data-required-parcours-id') || 0);
            if (parcoursId <= 0 || requiredParcoursId <= 0) {
                return;
            }

            closeAllMissionItemMenus();
            button.disabled = true;
            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('required_parcours_id', String(requiredParcoursId));

                const response = await fetch(lmsParcoursPrerequisiteRemovePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : lmsIndexText.removePrerequisiteError);
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : lmsIndexText.removePrerequisiteError);
            } finally {
                button.disabled = false;
            }
        });
    });

    if (searchInput) {
        const applyPrerequisitePickerSearch = function () {
            const normalizedQuery = normalizePrerequisitePickerSearch(searchInput.value || '');
            let visibleCount = 0;

            manager.querySelectorAll('[data-lms-prerequisite-picker-item]').forEach((item) => {
                const haystack = normalizePrerequisitePickerSearch(item.getAttribute('data-search-text') || '');
                const isVisible = normalizedQuery === '' || haystack.indexOf(normalizedQuery) !== -1;
                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (searchEmptyState) {
                searchEmptyState.hidden = !(normalizedQuery !== '' && visibleCount === 0);
            }
        };

        searchInput.addEventListener('input', applyPrerequisitePickerSearch);
        searchInput.addEventListener('search', applyPrerequisitePickerSearch);
        applyPrerequisitePickerSearch();
    }

    manager.querySelectorAll('[data-lms-add-prerequisite-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const requiredParcoursId = Number(button.getAttribute('data-lms-add-prerequisite-id') || 0);
            if (parcoursId <= 0 || requiredParcoursId <= 0) {
                return;
            }

            button.disabled = true;
            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('required_parcours_id', String(requiredParcoursId));

                const response = await fetch(lmsParcoursPrerequisiteAddPath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : lmsIndexText.addPrerequisiteError);
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : lmsIndexText.addPrerequisiteError);
            } finally {
                button.disabled = false;
            }
        });
    });
}

function initParcoursPackManager() {
    const drawerContent = document.getElementById('drawer-content');
    const manager = drawerContent ? drawerContent.querySelector('[data-lms-parcours-pack-manager]') : null;

    if (!manager || manager.dataset.lmsPackManagerBound === '1') {
        return;
    }

    manager.dataset.lmsPackManagerBound = '1';
    const parcoursId = Number(manager.getAttribute('data-parcours-id') || 0);
    const list = manager.querySelector('[data-lms-pack-parcours-list]');
    const picker = manager.querySelector('[data-lms-pack-picker]');
    const searchInput = manager.querySelector('[data-lms-pack-picker-search]');
    const searchEmptyState = manager.querySelector('[data-lms-pack-picker-empty-search]');

    function normalizePackPickerSearch(value) {
        let normalized = String(value || '').trim().toLowerCase();
        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return normalized;
    }

    function closePicker() {
        if (picker) {
            picker.hidden = true;
        }
    }

    function openPicker() {
        if (picker) {
            picker.hidden = false;
        }
    }

    manager.querySelectorAll('[data-lms-open-pack-picker]').forEach((button) => {
        button.addEventListener('click', openPicker);
    });

    manager.querySelectorAll('[data-lms-close-pack-picker]').forEach((button) => {
        button.addEventListener('click', closePicker);
    });

    manager.querySelectorAll('[data-lms-toggle-pack-item-menu]').forEach((button) => {
        button.addEventListener('click', function (event) {
            const childParcoursId = Number(button.getAttribute('data-child-parcours-id') || 0);
            if (childParcoursId <= 0) {
                return;
            }
            togglePackItemMenu(event, childParcoursId);
        });
    });

    manager.querySelectorAll('[data-lms-edit-pack-child]').forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const childParcoursId = Number(button.getAttribute('data-child-parcours-id') || 0);
            if (childParcoursId <= 0) {
                return;
            }

            closeAllMissionItemMenus();
            openParcoursEditorDrawer(childParcoursId);
        });
    });

    manager.querySelectorAll('[data-lms-remove-pack-child]').forEach((button) => {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const childParcoursId = Number(button.getAttribute('data-child-parcours-id') || 0);
            if (parcoursId <= 0 || childParcoursId <= 0) {
                return;
            }

            if (!window.confirm('Retirer ce parcours du pack ?')) {
                return;
            }

            closeAllMissionItemMenus();
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('child_parcours_id', String(childParcoursId));

                const response = await fetch(lmsParcoursPackRemovePath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible de retirer ce parcours du pack.');
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible de retirer ce parcours du pack.');
            } finally {
                button.disabled = false;
            }
        });
    });

    if (searchInput) {
        const applyPackPickerSearch = function () {
            const normalizedQuery = normalizePackPickerSearch(searchInput.value || '');
            let visibleCount = 0;

            manager.querySelectorAll('[data-lms-pack-picker-item]').forEach((item) => {
                const haystack = normalizePackPickerSearch(item.getAttribute('data-search-text') || '');
                const isVisible = normalizedQuery === '' || haystack.indexOf(normalizedQuery) !== -1;
                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (searchEmptyState) {
                searchEmptyState.hidden = !(normalizedQuery !== '' && visibleCount === 0);
            }
        };

        searchInput.addEventListener('input', applyPackPickerSearch);
        searchInput.addEventListener('search', applyPackPickerSearch);
        applyPackPickerSearch();
    }

    manager.querySelectorAll('[data-lms-add-pack-child-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const childParcoursId = Number(button.getAttribute('data-lms-add-pack-child-id') || 0);
            if (parcoursId <= 0 || childParcoursId <= 0) {
                return;
            }

            button.disabled = true;
            try {
                const formData = new FormData();
                formData.append('pid', String(parcoursId));
                formData.append('child_parcours_id', String(childParcoursId));

                const response = await fetch(lmsParcoursPackAddPath, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Impossible d ajouter ce parcours au pack.');
                }

                await refreshParcoursMissionManager(parcoursId);
            } catch (error) {
                window.alert(error && error.message ? error.message : 'Impossible d ajouter ce parcours au pack.');
            } finally {
                button.disabled = false;
            }
        });
    });

    if (list && typeof window.commonCreateVerticalSortableList === 'function') {
        window.commonCreateVerticalSortableList({
            list: list,
            itemSelector: '[data-child-parcours-id]',
            handleSelector: '[data-lms-pack-drag-handle]',
            draggingClass: 'is-dragging',
            dropTargetClass: 'is-drop-target',
            onDrop: async function () {
                const childParcoursIds = Array.from(list.querySelectorAll('[data-child-parcours-id]'))
                    .map((item) => Number(item.getAttribute('data-child-parcours-id') || 0))
                    .filter((id) => Number.isInteger(id) && id > 0);

                try {
                    const formData = new FormData();
                    formData.append('pid', String(parcoursId));
                    childParcoursIds.forEach((childParcoursId) => {
                        formData.append('child_parcours_ids[]', String(childParcoursId));
                    });

                    const response = await fetch(lmsParcoursPackReorderPath, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload || payload.success !== true) {
                        throw new Error(payload && payload.message ? payload.message : 'Impossible de reordonner les parcours du pack.');
                    }

                    await refreshParcoursMissionManager(parcoursId);
                } catch (error) {
                    window.alert(error && error.message ? error.message : 'Impossible de reordonner les parcours du pack.');
                    await refreshParcoursMissionManager(parcoursId);
                }
            }
        });
    }
}

function createMissionQuestionChoiceRow(index) {
    const row = document.createElement('div');
    row.className = 'lms-question-choice-row';
    row.setAttribute('data-lms-question-choice-row', '1');
    row.innerHTML = `
        <label class="lms-question-choice-row__label generic-form-field">
            <span class="generic-form-label">${lmsIndexText.choiceLabel}</span>
            <input type="text" name="choices[${index}][label]" class="generic-form-control" required>
        </label>
        <label class="lms-question-choice-row__correct">
            <input type="checkbox" name="choices[${index}][is_correct]" value="1">
            <span>${lmsIndexText.correctChoiceLabel}</span>
        </label>
        <button type="button" class="lms-question-choice-row__remove generic-action-button generic-action-button--secondary" data-lms-remove-question-choice="1">${lmsIndexText.removeLabel}</button>
    `;
    return row;
}

function setMissionFormButtonLabel(button, text) {
    if (button) {
        button.textContent = text;
    }
}

function resetMissionHomeworkForm(homeworkForm, submitButton) {
    if (!homeworkForm) {
        return;
    }

    homeworkForm.reset();
    const idField = homeworkForm.querySelector('[name="id"]');
    if (idField) {
        idField.value = '';
    }

    const detailField = homeworkForm.querySelector('[name="detail"]');
    const onlyAdminField = homeworkForm.querySelector('input[type="checkbox"][name="onlyAdmin"]');
    lmsSetHtmlFieldValue(detailField, '');
    if (onlyAdminField) {
        onlyAdminField.checked = false;
    }

    homeworkForm.hidden = true;
    setMissionFormButtonLabel(submitButton, lmsIndexText.createHomework);
}

function openMissionHomeworkFormForCreate(homeworkForm, submitButton) {
    if (!homeworkForm) {
        return;
    }

    homeworkForm.reset();
    const idField = homeworkForm.querySelector('[name="id"]');
    if (idField) {
        idField.value = '';
    }

    homeworkForm.hidden = false;
    setMissionFormButtonLabel(submitButton, lmsIndexText.createHomework);
    lmsSetHtmlFieldValue(homeworkForm.querySelector('[name="detail"]'), '');
    lmsInitAdminEditHtmlFields(homeworkForm);
}

function openMissionHomeworkFormForEdit(homeworkForm, submitButton, item) {
    if (!homeworkForm || !item) {
        return;
    }

    const idField = homeworkForm.querySelector('[name="id"]');
    const titleField = homeworkForm.querySelector('[name="title"]');
    const detailField = homeworkForm.querySelector('[name="detail"]');
    const onlyAdminField = homeworkForm.querySelector('input[type="checkbox"][name="onlyAdmin"]');

    if (idField) {
        idField.value = String(item.getAttribute('data-homework-id') || '');
    }
    if (titleField) {
        titleField.value = String(item.getAttribute('data-homework-title') || '');
    }
    lmsSetHtmlFieldValue(detailField, String(item.getAttribute('data-homework-detail') || ''));
    if (onlyAdminField) {
        onlyAdminField.checked = String(item.getAttribute('data-homework-only-admin') || '0') === '1';
    }

    homeworkForm.hidden = false;
    setMissionFormButtonLabel(submitButton, 'Mettre a jour le devoir');
    lmsInitAdminEditHtmlFields(homeworkForm);
    if (titleField) {
        window.setTimeout(function () {
            try {
                titleField.focus({ preventScroll: true });
            } catch (error) {
                titleField.focus();
            }
            titleField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 0);
    }
}

function resetMissionQuestionForm(questionForm, submitButton) {
    if (!questionForm) {
        return;
    }

    questionForm.reset();
    const idField = questionForm.querySelector('[name="id"]');
    if (idField) {
        idField.value = '';
    }

    const choiceList = questionForm.querySelector('[data-lms-question-choice-list]');
    if (choiceList) {
        choiceList.innerHTML = '';
        choiceList.appendChild(createMissionQuestionChoiceRow(0));
        choiceList.appendChild(createMissionQuestionChoiceRow(1));
        choiceList.dataset.nextIndex = '2';
        bindMissionQuestionChoiceRemoval(questionForm);
    }

    questionForm.hidden = true;
    setMissionFormButtonLabel(submitButton, lmsIndexText.createQuestion);
}

function openMissionQuestionFormForCreate(questionForm, submitButton) {
    if (!questionForm) {
        return;
    }

    resetMissionQuestionForm(questionForm, submitButton);
    questionForm.hidden = false;
    setMissionFormButtonLabel(submitButton, lmsIndexText.createQuestion);
}

function openMissionQuestionFormForEdit(questionForm, submitButton, item) {
    if (!questionForm || !item) {
        return;
    }

    const idField = questionForm.querySelector('[name="id"]');
    const questionField = questionForm.querySelector('[name="question"]');
    const answerField = questionForm.querySelector('[name="answer"]');
    const detailField = questionForm.querySelector('[name="detail"]');
    const choiceList = questionForm.querySelector('[data-lms-question-choice-list]');
    let choices = [];

    try {
        const rawChoices = String(item.getAttribute('data-question-choices') || '[]');
        const parsedChoices = JSON.parse(rawChoices);
        if (Array.isArray(parsedChoices)) {
            choices = parsedChoices;
        }
    } catch (error) {
        choices = [];
    }

    if (idField) {
        idField.value = String(item.getAttribute('data-question-id') || '');
    }
    if (questionField) {
        questionField.value = String(item.getAttribute('data-question-text') || '');
    }
    if (answerField) {
        answerField.value = String(item.getAttribute('data-question-answer') || '');
    }
    if (detailField) {
        detailField.value = String(item.getAttribute('data-question-detail') || '');
    }

    if (choiceList) {
        choiceList.innerHTML = '';
        const safeChoices = choices.length >= 2 ? choices : [{ label: '', is_correct: false }, { label: '', is_correct: false }];
        safeChoices.forEach((choice, index) => {
            const row = createMissionQuestionChoiceRow(index);
            const labelField = row.querySelector('input[type="text"]');
            const correctField = row.querySelector('input[type="checkbox"]');
            if (labelField) {
                labelField.value = String(choice.label || '');
            }
            if (correctField) {
                correctField.checked = !!choice.is_correct;
            }
            choiceList.appendChild(row);
        });
        choiceList.dataset.nextIndex = String(safeChoices.length);
        bindMissionQuestionChoiceRemoval(questionForm);
    }

    questionForm.hidden = false;
    setMissionFormButtonLabel(submitButton, lmsIndexText.updateQuestion);
}

function bindMissionQuestionChoiceRemoval(scopeElement) {
    scopeElement.querySelectorAll('[data-lms-remove-question-choice]').forEach((button) => {
        if (button.dataset.lmsChoiceRemoveBound === '1') {
            return;
        }

        button.dataset.lmsChoiceRemoveBound = '1';
        button.addEventListener('click', function () {
            const list = button.closest('[data-lms-question-choice-list]');
            const row = button.closest('[data-lms-question-choice-row]');
            if (!list || !row) {
                return;
            }

            const rows = list.querySelectorAll('[data-lms-question-choice-row]');
            if (rows.length <= 2) {
                window.alert(lmsIndexText.keepTwoChoices);
                return;
            }

            row.remove();
        });
    });
}

function reloadMissionEditorDrawer(parcoursId, missionId) {
    return openDrawerFromUrl(buildMissionEditUrl(parcoursId, missionId), { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        });
}

function bindMissionRelatedSortableList(options) {
    if (!options || typeof window.commonCreateVerticalSortableList !== 'function') {
        return;
    }

    const list = options.list;
    if (!list || list.dataset.lmsSortableBound === '1') {
        return;
    }

    list.dataset.lmsSortableBound = '1';
    window.commonCreateVerticalSortableList({
        list: list,
        itemSelector: options.itemSelector,
        handleSelector: options.handleSelector,
        draggingClass: 'is-dragging',
        dropTargetClass: 'is-drop-target',
        onDrop: async function () {
            const itemIds = Array.from(list.querySelectorAll(options.itemSelector))
                .map((item) => Number(item.getAttribute(options.idAttribute) || 0))
                .filter((id) => Number.isInteger(id) && id > 0);

            try {
                const formData = new FormData();
                formData.append('pid', String(options.parcoursId || 0));
                formData.append('mid', String(options.missionId || 0));
                itemIds.forEach((itemId) => {
                    formData.append(options.arrayFieldName, String(itemId));
                });

                const response = await fetch(options.url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : options.errorMessage);
                }

                await reloadMissionEditorDrawer(options.parcoursId, options.missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : options.errorMessage);
                await reloadMissionEditorDrawer(options.parcoursId, options.missionId);
            }
        }
    });
}

function initMissionRelatedManagers() {
    const drawerContent = document.getElementById('drawer-content');
    const editor = drawerContent ? drawerContent.querySelector('[data-lms-mission-editor]') : null;
    if (!editor || editor.dataset.lmsMissionRelatedBound === '1') {
        return;
    }

    editor.dataset.lmsMissionRelatedBound = '1';
    const parcoursId = Number(editor.getAttribute('data-parcours-id') || 0);
    const missionId = Number(editor.getAttribute('data-mission-id') || 0);

    const homeworkForm = editor.querySelector('[data-lms-homework-create-form]');
    const questionForm = editor.querySelector('[data-lms-question-create-form]');
    const homeworkSubmitButton = homeworkForm ? homeworkForm.querySelector('[data-lms-homework-create-submit]') : null;
    const questionSubmitButton = questionForm ? questionForm.querySelector('[data-lms-question-create-submit]') : null;

    editor.querySelectorAll('[data-lms-open-homework-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            openMissionHomeworkFormForCreate(homeworkForm, homeworkSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-close-homework-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            resetMissionHomeworkForm(homeworkForm, homeworkSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-open-question-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            openMissionQuestionFormForCreate(questionForm, questionSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-close-question-creator]').forEach((button) => {
        button.addEventListener('click', function () {
            resetMissionQuestionForm(questionForm, questionSubmitButton);
        });
    });

    editor.querySelectorAll('[data-lms-edit-homework]').forEach((button) => {
        button.addEventListener('click', function () {
            const item = button.closest('[data-lms-homework-item]');
            openMissionHomeworkFormForEdit(homeworkForm, homeworkSubmitButton, item);
        });
    });

    editor.querySelectorAll('[data-lms-edit-question]').forEach((button) => {
        button.addEventListener('click', function () {
            const item = button.closest('[data-lms-question-item]');
            openMissionQuestionFormForEdit(questionForm, questionSubmitButton, item);
        });
    });

    const homeworkList = editor.querySelector('[data-lms-homework-list]');
    bindMissionRelatedSortableList({
        list: homeworkList,
        itemSelector: '[data-lms-homework-item]',
        handleSelector: '[data-lms-homework-drag-handle]',
        idAttribute: 'data-homework-id',
        arrayFieldName: 'homework_ids[]',
        url: lmsMissionHomeworkReorderPath,
        errorMessage: lmsIndexText.reorderHomeworks,
        parcoursId: parcoursId,
        missionId: missionId
    });

    const questionList = editor.querySelector('[data-lms-question-list]');
    bindMissionRelatedSortableList({
        list: questionList,
        itemSelector: '[data-lms-question-item]',
        handleSelector: '[data-lms-question-drag-handle]',
        idAttribute: 'data-question-id',
        arrayFieldName: 'question_ids[]',
        url: lmsMissionQuestionReorderPath,
        errorMessage: lmsIndexText.reorderQuestions,
        parcoursId: parcoursId,
        missionId: missionId
    });

    if (homeworkForm) {
        let isSubmittingHomework = false;
        const submitButton = homeworkSubmitButton;
        lmsInitAdminEditHtmlFields(homeworkForm);

        homeworkForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (isSubmittingHomework || parcoursId <= 0 || missionId <= 0) {
                return;
            }

            const titleField = homeworkForm.querySelector('[name="title"]');
            if (titleField && String(titleField.value || '').trim() === '') {
                window.alert(lmsIndexText.requiredTitle);
                titleField.focus();
                return;
            }

            isSubmittingHomework = true;
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                lmsSyncAdminEditHtmlFields(homeworkForm);
                const formData = new FormData(homeworkForm);
                formData.set('pid', String(parcoursId));
                formData.set('mid', String(missionId));

                const response = await fetch(homeworkForm.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : lmsIndexText.createHomeworkError);
                }

                await reloadMissionEditorDrawer(parcoursId, missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : lmsIndexText.createHomeworkError);
            } finally {
                isSubmittingHomework = false;
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    }

    if (questionForm) {
        let isSubmittingQuestion = false;
        const submitButton = questionSubmitButton;
        const choiceList = questionForm.querySelector('[data-lms-question-choice-list]');
        const addChoiceButton = questionForm.querySelector('[data-lms-add-question-choice]');

        if (choiceList && !choiceList.dataset.nextIndex) {
            choiceList.dataset.nextIndex = String(choiceList.querySelectorAll('[data-lms-question-choice-row]').length);
        }

        bindMissionQuestionChoiceRemoval(questionForm);

        if (addChoiceButton && choiceList) {
            addChoiceButton.addEventListener('click', function () {
                const nextIndex = Number(choiceList.dataset.nextIndex || choiceList.querySelectorAll('[data-lms-question-choice-row]').length || 0);
                choiceList.appendChild(createMissionQuestionChoiceRow(nextIndex));
                choiceList.dataset.nextIndex = String(nextIndex + 1);
                bindMissionQuestionChoiceRemoval(questionForm);
            });
        }

        questionForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (isSubmittingQuestion || parcoursId <= 0 || missionId <= 0) {
                return;
            }

            const questionField = questionForm.querySelector('[name="question"]');
            const answerField = questionForm.querySelector('[name="answer"]');
            const choiceRows = Array.from(questionForm.querySelectorAll('[data-lms-question-choice-row]'));
            const filledChoiceRows = choiceRows.filter((row) => {
                const input = row.querySelector('input[type="text"]');
                return input && String(input.value || '').trim() !== '';
            });
            const correctChoiceCount = filledChoiceRows.filter((row) => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                return !!(checkbox && checkbox.checked);
            }).length;

            if (questionField && String(questionField.value || '').trim() === '') {
                window.alert(lmsIndexText.questionRequired);
                questionField.focus();
                return;
            }

            if (answerField && String(answerField.value || '').trim() === '') {
                window.alert(lmsIndexText.answerRequired);
                answerField.focus();
                return;
            }

            if (filledChoiceRows.length < 2) {
                window.alert(lmsIndexText.minimumChoices);
                return;
            }

            if (correctChoiceCount <= 0) {
                window.alert(lmsIndexText.needCorrectChoice);
                return;
            }

            isSubmittingQuestion = true;
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const formData = new FormData(questionForm);
                formData.set('pid', String(parcoursId));
                formData.set('mid', String(missionId));

                const response = await fetch(questionForm.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : lmsIndexText.createQuestionError);
                }

                await reloadMissionEditorDrawer(parcoursId, missionId);
            } catch (error) {
                window.alert(error && error.message ? error.message : lmsIndexText.createQuestionError);
            } finally {
                isSubmittingQuestion = false;
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    }
}

function openCreateParcoursDrawer(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!lmsIndexViewer.canCreateParcours) {
        return;
    }

    closeAllParcoursCardMenus();
    openDrawerFromUrl(lmsParcoursCreatePath, { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert(lmsIndexText.loadFormError);
        });
}

function openImportParcoursDrawer(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!lmsIndexViewer.canCreateParcours) {
        return;
    }

    closeAllParcoursCardMenus();
    openDrawerFromUrl(lmsParcoursImportPath, { simpleMode: true })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert(lmsIndexText.loadCatalogError);
        });
}

function openParcoursEditorDrawer(parcoursId, options) {
    if (!lmsIndexViewer.canCreateParcours && !lmsIndexViewer.canEditParcours) {
        return Promise.resolve();
    }

    closeAllParcoursCardMenus();
    const resolvedOptions = options && typeof options === 'object' ? options : {};

    const targetUrl = new URL(lmsParcoursEditBasePath, window.location.origin);
    targetUrl.searchParams.set('pid', String(parcoursId));

    return openDrawerFromUrl(targetUrl.pathname + targetUrl.search + targetUrl.hash, {
        simpleMode: true,
        scrollTop: resolvedOptions.restoreScroll ? lmsGetRememberedParcoursEditorScroll(parcoursId) : 0
    })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert(lmsIndexText.loadParcoursError);
        });
}

function openEditParcoursDrawer(event, parcoursId) {
    event.preventDefault();
    event.stopPropagation();

    const card = document.querySelector(`[data-parcours-card="1"][data-parcours-id="${parcoursId}"]`);
    if (card && String(card.getAttribute('data-can-edit') || '0') !== '1') {
        return;
    }

    openParcoursEditorDrawer(parcoursId);
}

async function deleteParcoursFromCard(event, parcoursId) {
    event.preventDefault();
    event.stopPropagation();

    if (!lmsIndexViewer.canCreateParcours || parcoursId <= 0) {
        return;
    }

    const card = document.querySelector(`[data-parcours-card="1"][data-parcours-id="${parcoursId}"]`);
    if (card && String(card.getAttribute('data-can-manage') || '0') !== '1') {
        return;
    }

    closeAllParcoursCardMenus();

    const parcoursTitle = card ? String(card.getAttribute('data-parcours-title') || '').trim() : '';
    try {
        const previewFormData = new FormData();
        previewFormData.set('id', String(parcoursId));

        const previewResponse = await fetch(lmsParcoursDeletePreviewPath, {
            method: 'POST',
            body: previewFormData,
            credentials: 'same-origin'
        });
        const previewPayload = await previewResponse.json();
        if (!previewResponse.ok || !previewPayload || !previewPayload.status) {
            throw new Error(previewPayload && previewPayload.message ? previewPayload.message : lmsIndexText.deletePreviewError);
        }

        let confirmationMessage = String(previewPayload.confirmMessage || '').trim();
        if (parcoursTitle !== '') {
            confirmationMessage = `Parcours: "${parcoursTitle}"\n\n${confirmationMessage}`;
        }
        if (confirmationMessage === '') {
            confirmationMessage = parcoursTitle !== ''
                ? formatLmsIndexText(lmsIndexText.deleteConfirmNamed, { title: parcoursTitle })
                : lmsIndexText.deleteConfirmGeneric;
        }

        if (!window.confirm(confirmationMessage)) {
            return;
        }

        const formData = new FormData();
        formData.set('id', String(parcoursId));
        const response = await fetch(lmsParcoursDeletePath, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const payload = await response.json();
        if (!response.ok || !payload || !payload.status) {
            throw new Error(payload && payload.message ? payload.message : lmsIndexText.deleteFailed);
        }

        window.alert(payload.message || lmsIndexText.deleteSuccess);
        window.location.reload();
    } catch (error) {
        window.alert(error && error.message ? error.message : lmsIndexText.deleteFailed);
    }
}

function openMissionEditorDrawer(event, parcoursId, missionId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!lmsIndexViewer.canCreateParcours || parcoursId <= 0 || missionId <= 0) {
        return;
    }

    closeAllMissionItemMenus();
    lmsRememberParcoursEditorScroll(parcoursId);
    openDrawerFromUrl(buildMissionEditUrl(parcoursId, missionId), { simpleMode: true, scrollTop: 0 })
        .then(() => {
            initLmsDrawerContent();
        })
        .catch(() => {
            window.alert(lmsIndexText.loadMissionError);
        });
}

document.addEventListener('click', function () {
    closeAllParcoursCardMenus();
    closeAllMissionItemMenus();
});

