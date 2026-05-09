(function (window, document) {
    'use strict';

    if (window.omoSizedImageField) {
        return;
    }

    const stores = {};
    let stylesInjected = false;

    function ensureStyles() {
        if (stylesInjected) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = ''
            + '.omo-sized-image-field{display:flex;flex-direction:column;gap:10px;}'
            + '.omo-sized-image-field__toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}'
            + '.omo-sized-image-field input[type="file"]{display:none !important;}'
            + '.omo-sized-image-field__button{appearance:none;border:1px solid var(--color-border,#d1d5db);background:var(--color-surface,#fff);color:var(--color-text,#1f2937);border-radius:999px;padding:8px 14px;font:inherit;cursor:pointer;}'
            + '.omo-sized-image-field__button[disabled]{opacity:.55;cursor:not-allowed;}'
            + '.omo-sized-image-field__button--ghost{background:transparent;}'
            + '.omo-sized-image-field__viewport{position:relative;overflow:hidden;border:1px solid var(--color-border,#d1d5db);border-radius:16px;background:linear-gradient(135deg,#f8fafc,#e2e8f0);}'
            + '.omo-sized-image-field__viewport img{position:absolute;top:0;left:0;max-width:none;user-select:none;-webkit-user-drag:none;touch-action:none;cursor:grab;}'
            + '.omo-sized-image-field__viewport.is-draggable img{cursor:grab;}'
            + '.omo-sized-image-field__viewport.is-dragging img{cursor:grabbing;}'
            + '.omo-sized-image-field__placeholder{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:14px;text-align:center;color:var(--color-text-light,#6b7280);font-size:13px;line-height:1.45;}'
            + '.omo-sized-image-field__slider{width:100%;}'
            + '.omo-sized-image-field__meta{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--color-text-light,#6b7280);}'
            + '.omo-sized-image-field__meta strong{color:var(--color-text,#1f2937);font-weight:600;}';
        document.head.appendChild(style);
        stylesInjected = true;
    }

    function getStore(inputName) {
        if (!stores[inputName]) {
            stores[inputName] = {
                blob: null,
                previewUrl: '',
                workingSrc: '',
                workingObjectUrl: '',
                previewObjectUrl: '',
                cropTimer: null,
                currentValue: ''
            };
        }

        return stores[inputName];
    }

    function revokeUrl(url) {
        if (url && /^blob:/.test(url)) {
            URL.revokeObjectURL(url);
        }
    }

    function clearStoreBlob(store) {
        if (store.cropTimer) {
            window.clearTimeout(store.cropTimer);
            store.cropTimer = null;
        }

        store.blob = null;
        revokeUrl(store.previewObjectUrl);
        store.previewObjectUrl = '';
        store.previewUrl = '';
    }

    function clearStoreWorkingSource(store) {
        revokeUrl(store.workingObjectUrl);
        store.workingObjectUrl = '';
        store.workingSrc = '';
    }

    function clamp(value, minValue, maxValue) {
        return Math.max(minValue, Math.min(maxValue, value));
    }

    function resolveMetaText(state, value, inheritedValue) {
        if (state.locked) {
            return 'Cette image est verrouillée par le modèle.';
        }

        if (String(value || '').trim() !== '') {
            return 'Une image locale est définie pour ce holon.';
        }

        if (String(inheritedValue || '').trim() !== '') {
            return 'Aucune image locale: la valeur héritée du modèle sera utilisée.';
        }

        return state.emptyText || 'Aucune image définie pour le moment.';
    }

    function mount(container, options) {
        ensureStyles();

        if (typeof container.__omoSizedImageCleanup === 'function') {
            container.__omoSizedImageCleanup();
        }

        const state = Object.assign({
            inputName: '',
            uploadFieldName: '',
            value: '',
            inheritedValue: '',
            locked: false,
            emptyText: 'Aucune image définie.',
            displayWidth: 200,
            displayHeight: 200,
            targetWidth: 500,
            targetHeight: 500,
            labels: {
                choose: 'Choisir une image',
                clear: 'Effacer',
                zoom: 'Zoom'
            }
        }, options || {});

        if (!container || !state.inputName) {
            return null;
        }

        const store = getStore(state.inputName);
        const currentValue = String(state.value || '').trim();
        const inheritedValue = String(state.inheritedValue || '').trim();

        if (currentValue !== 'newimage') {
            clearStoreBlob(store);
        }

        store.currentValue = currentValue;
        if (currentValue && currentValue !== 'newimage') {
            clearStoreWorkingSource(store);
            store.workingSrc = currentValue;
        } else if (!currentValue && !inheritedValue) {
            clearStoreWorkingSource(store);
        } else if (!store.workingSrc) {
            store.workingSrc = inheritedValue;
        }

        const localSource = currentValue === 'newimage'
            ? (store.workingSrc || store.previewUrl || inheritedValue)
            : currentValue;
        const effectiveSource = localSource || inheritedValue;
        const canEdit = !state.locked;
        const hasLocalValue = currentValue !== '' && currentValue !== null;
        const canClear = canEdit && (hasLocalValue || currentValue === 'newimage');

        container.innerHTML = ''
            + '<div class="omo-sized-image-field">'
            + '  <input type="hidden" value="' + escapeHtml(currentValue) + '">'
            + '  <div class="omo-sized-image-field__toolbar">'
            + '      <input type="file" accept="image/*" hidden>'
            + '      <button type="button" class="omo-sized-image-field__button">' + escapeHtml(state.labels.choose) + '</button>'
            + '      <button type="button" class="omo-sized-image-field__button omo-sized-image-field__button--ghost"' + (canClear ? '' : ' disabled') + '>' + escapeHtml(state.labels.clear) + '</button>'
            + '  </div>'
            + '  <div class="omo-sized-image-field__viewport" style="width:' + Number(state.displayWidth || 200) + 'px;height:' + Number(state.displayHeight || 200) + 'px;">'
            + '      <img alt="" hidden>'
            + '      <div class="omo-sized-image-field__placeholder">' + escapeHtml(state.emptyText) + '</div>'
            + '  </div>'
            + '  <label class="omo-sized-image-field__meta">'
            + '      <strong>' + escapeHtml(state.labels.zoom) + '</strong>'
            + '      <input type="range" class="omo-sized-image-field__slider" min="0" max="100" step="1" value="0"' + (canEdit && effectiveSource ? '' : ' disabled') + '>'
            + '      <span>' + escapeHtml(resolveMetaText(state, currentValue, inheritedValue)) + '</span>'
            + '  </label>'
            + '</div>';

        const hiddenInput = container.querySelector('input[type="hidden"]');
        const fileInput = container.querySelector('input[type="file"]');
        const chooseButton = container.querySelector('.omo-sized-image-field__button');
        const clearButton = container.querySelector('.omo-sized-image-field__button--ghost');
        const viewport = container.querySelector('.omo-sized-image-field__viewport');
        const image = container.querySelector('img');
        const placeholder = container.querySelector('.omo-sized-image-field__placeholder');
        const slider = container.querySelector('.omo-sized-image-field__slider');

        let naturalWidth = 0;
        let naturalHeight = 0;
        let baseScale = 1;
        let zoomScale = 1;
        let posX = 0;
        let posY = 0;
        let dragState = null;
        const cleanupFns = [];

        function registerListener(target, eventName, handler, options) {
            if (!target) {
                return;
            }

            target.addEventListener(eventName, handler, options);
            cleanupFns.push(function () {
                target.removeEventListener(eventName, handler, options);
            });
        }

        function setHiddenValue(value) {
            hiddenInput.value = value;
            store.currentValue = value;
        }

        function updateMetaText() {
            const meta = container.querySelector('.omo-sized-image-field__meta span');
            if (!meta) {
                return;
            }

            meta.textContent = resolveMetaText(state, hiddenInput.value, inheritedValue);
        }

        function getDisplayWidth() {
            return naturalWidth * baseScale * zoomScale;
        }

        function getDisplayHeight() {
            return naturalHeight * baseScale * zoomScale;
        }

        function clampPosition() {
            const displayWidth = getDisplayWidth();
            const displayHeight = getDisplayHeight();
            const minX = Math.min(0, Number(state.displayWidth || 200) - displayWidth);
            const minY = Math.min(0, Number(state.displayHeight || 200) - displayHeight);
            posX = clamp(posX, minX, 0);
            posY = clamp(posY, minY, 0);
        }

        function renderImage() {
            if (!naturalWidth || !naturalHeight) {
                image.hidden = true;
                placeholder.hidden = false;
                viewport.classList.remove('is-draggable', 'is-dragging');
                return;
            }

            clampPosition();

            image.hidden = false;
            placeholder.hidden = true;
            image.style.width = getDisplayWidth() + 'px';
            image.style.height = getDisplayHeight() + 'px';
            image.style.left = posX + 'px';
            image.style.top = posY + 'px';
            viewport.classList.toggle('is-draggable', canEdit);
        }

        function commitCrop() {
            if (!canEdit || !naturalWidth || !naturalHeight || !store.workingSrc) {
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = Number(state.targetWidth || state.displayWidth || 200);
            canvas.height = Number(state.targetHeight || state.displayHeight || 200);

            const ratio = canvas.width / Number(state.displayWidth || 200);
            const displayWidth = getDisplayWidth();
            const displayHeight = getDisplayHeight();
            const context = canvas.getContext('2d');
            context.clearRect(0, 0, canvas.width, canvas.height);
            context.drawImage(
                image,
                posX * ratio,
                posY * ratio,
                displayWidth * ratio,
                displayHeight * ratio
            );

            canvas.toBlob(function (blob) {
                if (!blob) {
                    return;
                }

                clearStoreBlob(store);
                store.blob = blob;
                store.previewObjectUrl = URL.createObjectURL(blob);
                store.previewUrl = store.previewObjectUrl;
                setHiddenValue('newimage');
                updateMetaText();
                if (clearButton) {
                    clearButton.disabled = false;
                }
            }, 'image/jpeg', 0.92);
        }

        function scheduleCropCommit() {
            if (!canEdit) {
                return;
            }

            if (store.cropTimer) {
                window.clearTimeout(store.cropTimer);
            }

            store.cropTimer = window.setTimeout(function () {
                store.cropTimer = null;
                commitCrop();
            }, 120);
        }

        function centerImage() {
            posX = (Number(state.displayWidth || 200) - getDisplayWidth()) / 2;
            posY = (Number(state.displayHeight || 200) - getDisplayHeight()) / 2;
            clampPosition();
        }

        function syncSlider(enabled) {
            if (slider) {
                slider.disabled = !enabled;
            }
        }

        function initializeFromLoadedSource() {
            naturalWidth = image.naturalWidth || 0;
            naturalHeight = image.naturalHeight || 0;

            if (!naturalWidth || !naturalHeight) {
                renderImage();
                syncSlider(false);
                return;
            }

            baseScale = Math.max(
                Number(state.displayWidth || 200) / naturalWidth,
                Number(state.displayHeight || 200) / naturalHeight
            );
            zoomScale = 1;
            if (slider) {
                slider.value = '0';
            }
            centerImage();
            renderImage();
            syncSlider(canEdit);
        }

        function loadSource(source) {
            if (!source) {
                naturalWidth = 0;
                naturalHeight = 0;
                image.removeAttribute('src');
                renderImage();
                syncSlider(false);
                return;
            }

            if (hiddenInput.value !== 'newimage') {
                store.workingSrc = source;
            }

            image.onload = initializeFromLoadedSource;
            image.src = source;
        }

        function startDragging(clientX, clientY) {
            if (!canEdit || !naturalWidth || !naturalHeight) {
                return;
            }

            dragState = {
                startX: clientX,
                startY: clientY,
                originX: posX,
                originY: posY
            };
            viewport.classList.add('is-dragging');
        }

        function moveDragging(clientX, clientY) {
            if (!dragState) {
                return;
            }

            posX = dragState.originX + (clientX - dragState.startX);
            posY = dragState.originY + (clientY - dragState.startY);
            renderImage();
        }

        function stopDragging() {
            if (!dragState) {
                return;
            }

            dragState = null;
            viewport.classList.remove('is-dragging');
            scheduleCropCommit();
        }

        if (chooseButton) {
            chooseButton.disabled = !canEdit;
            registerListener(chooseButton, 'click', function () {
                if (!canEdit || !fileInput) {
                    return;
                }

                fileInput.click();
            });
        }

        if (clearButton) {
            registerListener(clearButton, 'click', function () {
                if (!canEdit) {
                    return;
                }

                clearStoreBlob(store);
                setHiddenValue('');
                clearStoreWorkingSource(store);
                if (inheritedValue) {
                    store.workingSrc = inheritedValue;
                }
                updateMetaText();
                clearButton.disabled = true;
                loadSource(inheritedValue);
            });
        }

        if (fileInput) {
            registerListener(fileInput, 'change', function () {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (!file) {
                    return;
                }

                clearStoreBlob(store);
                clearStoreWorkingSource(store);
                store.workingObjectUrl = URL.createObjectURL(file);
                store.workingSrc = store.workingObjectUrl;
                setHiddenValue('newimage');
                if (clearButton) {
                    clearButton.disabled = false;
                }
                updateMetaText();
                loadSource(store.workingSrc);
                window.setTimeout(scheduleCropCommit, 40);
                fileInput.value = '';
            });
        }

        if (slider) {
            registerListener(slider, 'input', function () {
                if (!naturalWidth || !naturalHeight) {
                    return;
                }

                const previousWidth = getDisplayWidth();
                const previousHeight = getDisplayHeight();
                const viewportCenterX = Number(state.displayWidth || 200) / 2;
                const viewportCenterY = Number(state.displayHeight || 200) / 2;
                const relativeX = previousWidth > 0 ? (viewportCenterX - posX) / previousWidth : 0.5;
                const relativeY = previousHeight > 0 ? (viewportCenterY - posY) / previousHeight : 0.5;

                zoomScale = 1 + (Number(slider.value || 0) / 100) * 2;
                posX = viewportCenterX - (getDisplayWidth() * relativeX);
                posY = viewportCenterY - (getDisplayHeight() * relativeY);
                renderImage();
                scheduleCropCommit();
            });
        }

        registerListener(viewport, 'mousedown', function (event) {
            if (event.button !== 0) {
                return;
            }

            event.preventDefault();
            startDragging(event.clientX, event.clientY);
        });

        registerListener(viewport, 'touchstart', function (event) {
            if (!event.touches || !event.touches[0]) {
                return;
            }

            startDragging(event.touches[0].clientX, event.touches[0].clientY);
        }, { passive: true });

        registerListener(window, 'mousemove', function (event) {
            moveDragging(event.clientX, event.clientY);
        });

        registerListener(window, 'touchmove', function (event) {
            if (!event.touches || !event.touches[0]) {
                return;
            }

            moveDragging(event.touches[0].clientX, event.touches[0].clientY);
        }, { passive: true });

        registerListener(window, 'mouseup', stopDragging);
        registerListener(window, 'touchend', stopDragging);

        loadSource(effectiveSource);

        container.__omoSizedImageCleanup = function () {
            cleanupFns.forEach(function (cleanup) {
                cleanup();
            });
            cleanupFns.length = 0;
        };

        return {
            getValue: function () {
                return String(hiddenInput.value || '');
            },
            appendToFormData: function (formData) {
                if (!formData || !store.blob) {
                    return;
                }

                const fieldName = state.uploadFieldName || state.inputName;
                formData.append(fieldName, store.blob, fieldName + '.jpg');
            }
        };
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    window.omoSizedImageField = {
        mount: mount
    };
})(window, document);
