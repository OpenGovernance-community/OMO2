window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/assets/js/organization-create.js"] = function (pageConfig, pageScript) {
    (function () {
        var root = document.getElementById('organizationCreateRoot');
        var isEditMode = pageConfig.isEditMode;
        var organizationId = pageConfig.organizationId;
        var formAction = pageConfig.formAction;
        var shortnamePreviewScheme = pageConfig.shortnamePreviewScheme;
        var shortnamePreviewHost = pageConfig.shortnamePreviewHost;
        var shortnamePreviewPath = pageConfig.shortnamePreviewPath;
        var organizationSubdomainRoutingEnabled = pageConfig.organizationSubdomainRoutingEnabled;
        var canManageOrganizationRouting = pageConfig.canManageOrganizationRouting;
        var organizationRoutingLockedMessage = pageConfig.organizationRoutingLockedMessage;
        var refreshApplicationPrompt = pageConfig.refreshApplicationPrompt;
        var submitButton = document.getElementById('organization_create_submit');
        var cancelButton = document.getElementById('organization_create_cancel');
        var form = document.getElementById('organization_create_form');
        var feedback = document.getElementById('organization_create_feedback');
        var shortnameInput = document.getElementById('shortname') || document.querySelector('input[name="shortname"]');
        var domainInput = document.getElementById('domain') || document.querySelector('input[name="domain"]');
        var initialFormSnapshot = '';
        var initialApplicationSnapshot = '';

        if (!root || !form || !submitButton || !cancelButton || !feedback) {
            return;
        }

        function ensureRoutingLockHint(input, hintId) {
            if (!input) {
                return null;
            }

            var existingHint = document.getElementById(hintId);
            if (existingHint) {
                return existingHint;
            }

            var field = input.closest('.generic-form-field');
            var hint = document.createElement('div');
            hint.id = hintId;
            hint.className = 'organization-create-routing-lock';
            hint.innerHTML = '<strong>Acces reserve.</strong> ' + organizationRoutingLockedMessage;

            if (field) {
                field.appendChild(hint);
            } else {
                input.insertAdjacentElement('afterend', hint);
            }

            return hint;
        }

        function lockRoutingField(input, hintId) {
            if (!input) {
                return;
            }

            input.disabled = true;
            input.setAttribute('aria-disabled', 'true');

            var field = input.closest('.generic-form-field');
            if (field) {
                field.classList.add('organization-create-field--locked');
            }

            ensureRoutingLockHint(input, hintId);
        }

        function applyRoutingRestrictions() {
            if (canManageOrganizationRouting) {
                return;
            }

            lockRoutingField(shortnameInput, 'organization_create_shortname_lock');
            lockRoutingField(domainInput, 'organization_create_domain_lock');
        }

        function getFormSnapshot() {
            var values = [];

            Array.prototype.forEach.call(form.querySelectorAll('input[name], select[name], textarea[name]'), function (field) {
                var type = String(field.type || '').toLowerCase();
                if (field.disabled || type === 'file') {
                    return;
                }
                if ((type === 'checkbox' || type === 'radio') && !field.checked) {
                    return;
                }
                values.push(field.name + '=' + String(field.value || ''));
            });

            return values.join('\n');
        }

        function getApplicationSnapshot() {
            var fields = ['name', 'shortname', 'domain', 'color', 'interface_level', 'logo', 'banner'];

            return fields.map(function (fieldName) {
                var field = form.querySelector('[name="' + fieldName + '"]');
                return fieldName + '=' + (field ? String(field.value || '') : '');
            }).join('\n');
        }

        if (form) {
            form.setAttribute('action', formAction);
            form.setAttribute('method', 'post');
            form.setAttribute('enctype', 'multipart/form-data');
            applyRoutingRestrictions();
            initialFormSnapshot = getFormSnapshot();
            initialApplicationSnapshot = getApplicationSnapshot();
        }

        function buildShortnamePreviewUrl(value) {
            var normalizedValue = String(value || '').trim().toLowerCase();
            if (!shortnamePreviewHost) {
                return '';
            }

            if (!organizationSubdomainRoutingEnabled) {
                var targetId = organizationId > 0 ? organizationId : 123;
                return shortnamePreviewScheme + '://' + shortnamePreviewHost + '/omo/o/' + targetId;
            }

            if (!normalizedValue) {
                return '';
            }

            return shortnamePreviewScheme + '://' + normalizedValue + '.' + shortnamePreviewHost + shortnamePreviewPath;
        }

        function ensureShortnameHint() {
            if (!shortnameInput) {
                return null;
            }

            var existingHint = document.getElementById('organization_create_shortname_hint');
            if (existingHint) {
                return existingHint;
            }

            var shortnameField = shortnameInput.closest('.generic-form-field');
            var hint = document.createElement('div');
            hint.id = 'organization_create_shortname_hint';
            hint.className = 'organization-create-shortname-hint';
            if (shortnameField) {
                shortnameField.appendChild(hint);
            } else {
                shortnameInput.insertAdjacentElement('afterend', hint);
            }
            return hint;
        }

        function updateShortnameHint() {
            var hint = ensureShortnameHint();
            if (!hint) {
                return;
            }

            if (!canManageOrganizationRouting) {
                hint.style.display = 'none';
                return;
            }

            hint.style.display = '';

            var previewUrl = buildShortnamePreviewUrl(shortnameInput ? shortnameInput.value : '');
            if (previewUrl) {
                if (organizationSubdomainRoutingEnabled) {
                    hint.innerHTML = "Ce nom court sera utilise dans l'URL de base du site :<br><code>" + previewUrl + "</code>";
                } else {
                    hint.innerHTML = "Les sous-domaines d'organisation sont desactives sur ce serveur. L'acces se fera via une URL de type :<br><code>" + previewUrl + "</code>";
                }
                return;
            }

            hint.innerHTML = "Ce nom court sera utilise dans l'URL de base du site, par exemple :<br><code>" + shortnamePreviewScheme + "://nomcourt." + shortnamePreviewHost + shortnamePreviewPath + "</code>";
        }

        function setFeedback(message, isError) {
            if (message && typeof window.commonNotify === 'function') {
                window.commonNotify(message, isError ? 'error' : 'success');
                feedback.textContent = '';
                feedback.className = 'organization-create-feedback';
                feedback.style.display = 'none';
                return;
            }

            feedback.textContent = message || '';
            feedback.className = 'organization-create-feedback' + (message ? (isError ? ' is-error' : ' is-success') : '');
            feedback.style.display = message ? 'block' : 'none';
        }

        function closeModal() {
            var settingsRoot = document.querySelector('.omo-settings');
            if (settingsRoot && settingsRoot.querySelector('[data-omo-settings-nested-drawer]')) {
                settingsRoot.dispatchEvent(new Event('omo-settings-close-nested-drawer'));
                return;
            }

            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
                return;
            }

            if (window.parent && window.parent !== window && typeof window.parent.commonTopbarCloseModal === 'function') {
                window.parent.commonTopbarCloseModal();
                return;
            }

            if (window.parent && window.parent !== window) {
                window.parent.location.reload();
                return;
            }

            window.close();
        }

        function redirectTargetWindow(url) {
            if (!url) {
                return;
            }

            if (window.parent && window.parent !== window) {
                window.parent.location.href = url;
                return;
            }

            window.location.href = url;
        }

        function reloadTargetWindow() {
            if (window.parent && window.parent !== window) {
                window.parent.location.reload();
                return;
            }

            window.location.reload();
        }

        function getComparableLocationHref() {
            if (window.parent && window.parent !== window && window.parent.location) {
                return window.parent.location.href;
            }

            return window.location.href;
        }

        function normalizeComparableUrl(url) {
            return String(url.protocol || '') + '//' + String(url.host || '') + String(url.pathname || '') + String(url.search || '');
        }

        function handleSuccessfulSave(result, shouldRefreshApplication) {
            var redirectUrl = result && result.redirect ? String(result.redirect) : '';

            if (!isEditMode) {
                if (redirectUrl) {
                    redirectTargetWindow(redirectUrl);
                    return;
                }

                closeModal();
                return;
            }

            if (!shouldRefreshApplication) {
                closeModal();
                return;
            }

            if (!window.confirm(refreshApplicationPrompt)) {
                closeModal();
                return;
            }

            if (!redirectUrl) {
                reloadTargetWindow();
                return;
            }

            try {
                var currentUrl = new URL(getComparableLocationHref());
                var targetUrl = new URL(redirectUrl, currentUrl.href);

                if (normalizeComparableUrl(currentUrl) !== normalizeComparableUrl(targetUrl)) {
                    redirectTargetWindow(targetUrl.href);
                    return;
                }
            } catch (error) {
                redirectTargetWindow(redirectUrl);
                return;
            }

            reloadTargetWindow();
        }

        cancelButton.addEventListener('click', function () {
            closeModal();
        });

        if (shortnameInput) {
            shortnameInput.addEventListener('input', updateShortnameHint);
            shortnameInput.addEventListener('change', updateShortnameHint);
            updateShortnameHint();
        }

        function initializeLocationPicker() {
            var mapElement = document.getElementById('organization-create-location-map');
            var latInput = document.getElementById('latlong_lat');
            var longInput = document.getElementById('latlong_long');
            var initialLat;
            var initialLng;
            var map;
            var marker = null;

            if (!mapElement || !latInput || !longInput || typeof window.L === 'undefined' || mapElement.dataset.leafletReady === '1') {
                return;
            }

            mapElement.dataset.leafletReady = '1';
            initialLat = parseFloat(latInput.value);
            initialLng = parseFloat(longInput.value);
            if (Number.isNaN(initialLat) || Number.isNaN(initialLng)) {
                initialLat = null;
                initialLng = null;
            }

            map = window.L.map(mapElement).setView(
                initialLat === null ? [46.8182, 8.2275] : [initialLat, initialLng],
                initialLat === null ? 7 : 13
            );

            if (typeof window.commonBindLeafletTheme === 'function') {
                window.commonBindLeafletTheme(map, { layer: null, theme: null });
            }

            function updateMarker(lat, lng, centerMap) {
                if (Number.isNaN(lat) || Number.isNaN(lng)) {
                    return;
                }
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = window.L.circleMarker([lat, lng], {
                        radius: 9,
                        color: '#0f766e',
                        weight: 2,
                        fillColor: '#14b8a6',
                        fillOpacity: 0.85
                    }).addTo(map);
                }
                if (centerMap) {
                    map.setView([lat, lng], Math.max(map.getZoom(), 13));
                }
            }

            function setCoordinates(lat, lng, centerMap) {
                latInput.value = Number(lat).toFixed(6);
                longInput.value = Number(lng).toFixed(6);
                updateMarker(Number(lat), Number(lng), centerMap);
            }

            function syncInputs() {
                var lat = parseFloat(latInput.value);
                var lng = parseFloat(longInput.value);
                updateMarker(lat, lng, true);
            }

            map.on('click', function (event) {
                setCoordinates(event.latlng.lat, event.latlng.lng, true);
            });
            latInput.addEventListener('change', syncInputs);
            longInput.addEventListener('change', syncInputs);
            if (initialLat !== null) {
                updateMarker(initialLat, initialLng, false);
            }
            window.setTimeout(function () { map.invalidateSize(); }, 0);
            window.setTimeout(function () { map.invalidateSize(); }, 250);
        }

        if (typeof window.commonWhenLeafletReady === 'function') {
            window.commonWhenLeafletReady(initializeLocationPicker);
        } else {
            initializeLocationPicker();
        }

        function initializeImageEditor(editor) {
            var field = editor.getAttribute('data-organization-image-editor');
            var crop = editor.querySelector('[data-organization-image-crop]');
            var fileInput = editor.querySelector('[data-organization-image-file]');
            var chooseButton = editor.querySelector('[data-organization-image-choose]');
            var zoomInput = editor.querySelector('[data-organization-image-zoom]');
            var valueInput = document.getElementById(field);
            var image = crop ? crop.querySelector('img') : null;
            var naturalWidth = 0;
            var naturalHeight = 0;
            var baseScale = 1;
            var scale = 1;
            var positionX = 0;
            var positionY = 0;
            var exportMime = 'image/jpeg';
            var pointerStart = null;

            if (!field || !crop || !fileInput || !chooseButton || !zoomInput || !valueInput) {
                return;
            }

            function cropWidth() {
                return crop.clientWidth;
            }

            function cropHeight() {
                return crop.clientHeight;
            }

            function clampPosition() {
                var imageWidth = naturalWidth * scale;
                var imageHeight = naturalHeight * scale;
                positionX = Math.min(0, Math.max(cropWidth() - imageWidth, positionX));
                positionY = Math.min(0, Math.max(cropHeight() - imageHeight, positionY));
            }

            function renderImage() {
                if (!image || !naturalWidth || !naturalHeight) {
                    return;
                }
                clampPosition();
                image.style.width = (naturalWidth * scale) + 'px';
                image.style.height = (naturalHeight * scale) + 'px';
                image.style.left = positionX + 'px';
                image.style.top = positionY + 'px';
            }

            function saveCrop() {
                var sourceX;
                var sourceY;
                var sourceWidth;
                var sourceHeight;
                var canvas;
                var context;
                var outputWidth;
                var outputHeight;

                if (!image || !naturalWidth || !naturalHeight || !window.HTMLCanvasElement) {
                    return;
                }

                sourceX = -positionX / scale;
                sourceY = -positionY / scale;
                sourceWidth = cropWidth() / scale;
                sourceHeight = cropHeight() / scale;
                outputWidth = parseInt(crop.getAttribute('data-output-width'), 10) || cropWidth();
                outputHeight = parseInt(crop.getAttribute('data-output-height'), 10) || cropHeight();
                canvas = document.createElement('canvas');
                canvas.width = outputWidth;
                canvas.height = outputHeight;
                context = canvas.getContext('2d');
                context.drawImage(image, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, outputWidth, outputHeight);
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        return;
                    }
                    window.croppedImages = window.croppedImages || {};
                    window.croppedImages[field] = blob;
                    valueInput.value = 'newimage';
                }, exportMime, exportMime === 'image/png' ? undefined : 0.9);
            }

            function resetImageLayout(retryCount, onReady) {
                if (!image || !image.naturalWidth || !image.naturalHeight) {
                    return;
                }
                if (!cropWidth() || !cropHeight()) {
                    if ((retryCount || 0) < 20) {
                        window.setTimeout(function () {
                            resetImageLayout((retryCount || 0) + 1, onReady);
                        }, 50);
                    }
                    return;
                }
                naturalWidth = image.naturalWidth;
                naturalHeight = image.naturalHeight;
                baseScale = Math.max(cropWidth() / naturalWidth, cropHeight() / naturalHeight);
                scale = baseScale;
                positionX = (cropWidth() - (naturalWidth * scale)) / 2;
                positionY = (cropHeight() - (naturalHeight * scale)) / 2;
                zoomInput.value = '0';
                renderImage();
                if (typeof onReady === 'function') {
                    onReady();
                }
            }

            function setImageSource(source, mimeType, shouldSave) {
                if (!image) {
                    image = document.createElement('img');
                    image.setAttribute('data-organization-image-preview', field);
                    crop.appendChild(image);
                }
                exportMime = mimeType === 'image/png' ? 'image/png' : 'image/jpeg';
                image.onload = function () {
                    resetImageLayout(0, shouldSave ? saveCrop : null);
                };
                image.src = source;
            }

            chooseButton.addEventListener('click', function () {
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
                var reader;
                if (!file) {
                    return;
                }
                reader = new FileReader();
                reader.onload = function (event) {
                    setImageSource(event.target.result, file.type, true);
                };
                reader.readAsDataURL(file);
            });

            zoomInput.addEventListener('input', function () {
                var oldScale = scale;
                var centerX;
                var centerY;
                if (!naturalWidth || !naturalHeight) {
                    return;
                }
                centerX = (cropWidth() / 2 - positionX) / oldScale;
                centerY = (cropHeight() / 2 - positionY) / oldScale;
                scale = baseScale * (1 + (parseInt(zoomInput.value, 10) || 0) / 100 * 3);
                positionX = cropWidth() / 2 - centerX * scale;
                positionY = cropHeight() / 2 - centerY * scale;
                renderImage();
                saveCrop();
            });

            crop.addEventListener('pointerdown', function (event) {
                if (!image || !naturalWidth || !naturalHeight) {
                    return;
                }
                pointerStart = {
                    id: event.pointerId,
                    x: event.clientX,
                    y: event.clientY,
                    positionX: positionX,
                    positionY: positionY
                };
                crop.setPointerCapture(event.pointerId);
            });

            crop.addEventListener('pointermove', function (event) {
                if (!pointerStart || pointerStart.id !== event.pointerId) {
                    return;
                }
                positionX = pointerStart.positionX + event.clientX - pointerStart.x;
                positionY = pointerStart.positionY + event.clientY - pointerStart.y;
                renderImage();
            });

            function finishImageMove(event) {
                if (!pointerStart || pointerStart.id !== event.pointerId) {
                    return;
                }
                pointerStart = null;
                saveCrop();
            }

            crop.addEventListener('pointerup', finishImageMove);
            crop.addEventListener('pointercancel', finishImageMove);

            if (image) {
                setImageSource(image.currentSrc || image.src, /\.png(?:$|\?)/i.test(image.currentSrc || image.src) ? 'image/png' : 'image/jpeg', false);
            }
        }

        Array.prototype.forEach.call(document.querySelectorAll('[data-organization-image-editor]'), initializeImageEditor);

        function submitOrganization() {
            var shouldRefreshApplication;

            if (!form) {
                setFeedback("Le formulaire n'est pas disponible.", true);
                return;
            }

            if (isEditMode && getFormSnapshot() === initialFormSnapshot) {
                closeModal();
                return;
            }

            shouldRefreshApplication = !isEditMode || getApplicationSnapshot() !== initialApplicationSnapshot;

            submitButton.disabled = true;
            setFeedback(pageConfig.pendingLabel, false);

            var formData = new FormData(form);
            if (isEditMode && organizationId > 0) {
                formData.set('id', String(organizationId));
            }

            if (window.croppedImages) {
                Object.keys(window.croppedImages).forEach(function (key) {
                    var blob = window.croppedImages[key];

                    if (blob) {
                        var extension = 'jpg';

                        if (blob.type === 'image/png') {
                            extension = 'png';
                        }

                        formData.append(key, blob, key + '.' + extension);
                    }
                });
            }

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.text().then(function (text) {
                        var data = null;

                        try {
                            data = JSON.parse(text);
                        } catch (error) {
                            data = null;
                        }

                        return {
                            ok: response.ok,
                            data: data
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data || result.data.success !== true) {
                        throw new Error(result.data && result.data.message ? result.data.message : pageConfig.message);
                    }

                    setFeedback(result.data.message || pageConfig.successLabel, false);

                    window.setTimeout(function () {
                        handleSuccessfulSave(result.data, shouldRefreshApplication);
                    }, 250);
                })
                .catch(function (error) {
                    setFeedback(error && error.message ? error.message : pageConfig.message, true);
                    submitButton.disabled = false;
                });
        }

        submitButton.addEventListener('click', submitOrganization);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitOrganization();
        });
    })();
};
